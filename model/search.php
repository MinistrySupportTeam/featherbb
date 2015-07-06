<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function get_search_results($feather)
{
    global $db, $db_type, $lang_common, $lang_search, $feather_user, $feather_config;
    
    $search = array();
    
    $action = ($feather->request->get('action')) ? $feather->request->get('action') : null;
    $forums = $feather->request->get('forums') ? (is_array($feather->request->get('forums')) ? $feather->request->get('forums') : array_filter(explode(',', $feather->request->get('forums')))) : ($feather->request->get('forums') ? array($feather->request->get('forums')) : array());
    $sort_dir = ($feather->request->get('sort_dir') && $feather->request->get('sort_dir') == 'DESC') ? 'DESC' : 'ASC';

    $forums = array_map('intval', $forums);

    // Allow the old action names for backwards compatibility reasons
    if ($action == 'show_user') {
        $action = 'show_user_posts';
    } elseif ($action == 'show_24h') {
        $action = 'show_recent';
    }

    // If a search_id was supplied
    if ($feather->request->get('search_id')) {
        $search_id = intval($feather->request->get('search_id'));
        if ($search_id < 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }
    }
    // If it's a regular search (keywords and/or author)
    elseif ($action == 'search') {
        $keywords = ($feather->request->get('keywords')) ? utf8_strtolower(pun_trim($feather->request->get('keywords'))) : null;
        $author = ($feather->request->get('author')) ? utf8_strtolower(pun_trim($feather->request->get('author'))) : null;

        if (preg_match('%^[\*\%]+$%', $keywords) || (pun_strlen(str_replace(array('*', '%'), '', $keywords)) < FEATHER_SEARCH_MIN_WORD && !is_cjk($keywords))) {
            $keywords = '';
        }

        if (preg_match('%^[\*\%]+$%', $author) || pun_strlen(str_replace(array('*', '%'), '', $author)) < 2) {
            $author = '';
        }

        if (!$keywords && !$author) {
            message($lang_search['No terms']);
        }

        if ($author) {
            $author = str_replace('*', '%', $author);
        }

        $show_as = ($feather->request->get('show_as') && $feather->request->get('show_as') == 'topics') ? 'topics' : 'posts';
        $sort_by = ($feather->request->get('sort_by')) ? intval($feather->request->get('sort_by')) : 0;
        $search_in = (!$feather->request->get('search_in') || $feather->request->get('search_in') == '0') ? 0 : (($feather->request->get('search_in') == '1') ? 1 : -1);
    }
    // If it's a user search (by ID)
    elseif ($action == 'show_user_posts' || $action == 'show_user_topics' || $action == 'show_subscriptions') {
        $user_id = ($feather->request->get('user_id')) ? intval($feather->request->get('user_id')) : $feather_user['id'];
        if ($user_id < 2) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Subscribed topics can only be viewed by admins, moderators and the users themselves
        if ($action == 'show_subscriptions' && !$feather_user['is_admmod'] && $user_id != $feather_user['id']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }
    } elseif ($action == 'show_recent') {
        $interval = $feather->request->get('value') ? intval($feather->request->get('value')) : 86400;
    } elseif ($action == 'show_replies') {
        if ($feather_user['is_guest']) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }
    } elseif ($action != 'show_new' && $action != 'show_unanswered') {
        message($lang_common['Bad request'], false, '404 Not Found');
    }


    // If a valid search_id was supplied we attempt to fetch the search results from the db
    if (isset($search_id)) {
        $ident = ($feather_user['is_guest']) ? get_remote_address() : $feather_user['username'];

        $result = $db->query('SELECT search_data FROM '.$db->prefix.'search_cache WHERE id='.$search_id.' AND ident=\''.$db->escape($ident).'\'') or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());
        if ($row = $db->fetch_assoc($result)) {
            $temp = unserialize($row['search_data']);

            $search_ids = unserialize($temp['search_ids']);
            $num_hits = $temp['num_hits'];
            $sort_by = $temp['sort_by'];
            $sort_dir = $temp['sort_dir'];
            $show_as = $temp['show_as'];
            $search_type = $temp['search_type'];

            unset($temp);
        } else {
            message($lang_search['No hits']);
        }
    } else {
        $keyword_results = $author_results = array();

        // Search a specific forum?
        $forum_sql = (!empty($forums) || (empty($forums) && $feather_config['o_search_all_forums'] == '0' && !$feather_user['is_admmod'])) ? ' AND t.forum_id IN ('.implode(',', $forums).')' : '';

        if (!empty($author) || !empty($keywords)) {
            // Flood protection
            if ($feather_user['last_search'] && (time() - $feather_user['last_search']) < $feather_user['g_search_flood'] && (time() - $feather_user['last_search']) >= 0) {
                message(sprintf($lang_search['Search flood'], $feather_user['g_search_flood'], $feather_user['g_search_flood'] - (time() - $feather_user['last_search'])));
            }

            if (!$feather_user['is_guest']) {
                $db->query('UPDATE '.$db->prefix.'users SET last_search='.time().' WHERE id='.$feather_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());
            } else {
                $db->query('UPDATE '.$db->prefix.'online SET last_search='.time().' WHERE ident=\''.$db->escape(get_remote_address()).'\'') or error('Unable to update user', __FILE__, __LINE__, $db->error());
            }

            switch ($sort_by) {
                case 1:
                    $sort_by_sql = ($show_as == 'topics') ? 't.poster' : 'p.poster';
                    $sort_type = SORT_STRING;
                    break;

                case 2:
                    $sort_by_sql = 't.subject';
                    $sort_type = SORT_STRING;
                    break;

                case 3:
                    $sort_by_sql = 't.forum_id';
                    $sort_type = SORT_NUMERIC;
                    break;

                case 4:
                    $sort_by_sql = 't.last_post';
                    $sort_type = SORT_NUMERIC;
                    break;

                default:
                    $sort_by_sql = ($show_as == 'topics') ? 't.last_post' : 'p.posted';
                    $sort_type = SORT_NUMERIC;
                    break;
            }

            // If it's a search for keywords
            if ($keywords) {
                // split the keywords into words
                $keywords_array = split_words($keywords, false);

                if (empty($keywords_array)) {
                    message($lang_search['No hits']);
                }

                // Should we search in message body or topic subject specifically?
                $search_in_cond = ($search_in) ? (($search_in > 0) ? ' AND m.subject_match = 0' : ' AND m.subject_match = 1') : '';

                $word_count = 0;
                $match_type = 'and';

                $sort_data = array();
                foreach ($keywords_array as $cur_word) {
                    switch ($cur_word) {
                        case 'and':
                        case 'or':
                        case 'not':
                            $match_type = $cur_word;
                            break;

                        default:
                        {
                            if (is_cjk($cur_word)) {
                                $where_cond = str_replace('*', '%', $cur_word);
                                $where_cond = ($search_in ? (($search_in > 0) ? 'p.message LIKE \'%'.$db->escape($where_cond).'%\'' : 't.subject LIKE \'%'.$db->escape($where_cond).'%\'') : 'p.message LIKE \'%'.$db->escape($where_cond).'%\' OR t.subject LIKE \'%'.$db->escape($where_cond).'%\'');

                                $result = $db->query('SELECT p.id AS post_id, p.topic_id, '.$sort_by_sql.' AS sort_by FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE ('.$where_cond.') AND (fp.read_forum IS NULL OR fp.read_forum=1)'.$forum_sql, true) or error('Unable to search for posts', __FILE__, __LINE__, $db->error());
                            } else {
                                $result = $db->query('SELECT m.post_id, p.topic_id, '.$sort_by_sql.' AS sort_by FROM '.$db->prefix.'search_words AS w INNER JOIN '.$db->prefix.'search_matches AS m ON m.word_id = w.id INNER JOIN '.$db->prefix.'posts AS p ON p.id=m.post_id INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE w.word LIKE \''.$db->escape(str_replace('*', '%', $cur_word)).'\''.$search_in_cond.' AND (fp.read_forum IS NULL OR fp.read_forum=1)'.$forum_sql, true) or error('Unable to search for posts', __FILE__, __LINE__, $db->error());
                            }

                            $row = array();
                            while ($temp = $db->fetch_assoc($result)) {
                                $row[$temp['post_id']] = $temp['topic_id'];

                                if (!$word_count) {
                                    $keyword_results[$temp['post_id']] = $temp['topic_id'];
                                    $sort_data[$temp['post_id']] = $temp['sort_by'];
                                } elseif ($match_type == 'or') {
                                    $keyword_results[$temp['post_id']] = $temp['topic_id'];
                                    $sort_data[$temp['post_id']] = $temp['sort_by'];
                                } elseif ($match_type == 'not') {
                                    unset($keyword_results[$temp['post_id']]);
                                    unset($sort_data[$temp['post_id']]);
                                }
                            }

                            if ($match_type == 'and' && $word_count) {
                                foreach ($keyword_results as $post_id => $topic_id) {
                                    if (!isset($row[$post_id])) {
                                        unset($keyword_results[$post_id]);
                                        unset($sort_data[$post_id]);
                                    }
                                }
                            }

                            ++$word_count;
                            $db->free_result($result);

                            break;
                        }
                    }
                }

                // Sort the results - annoyingly array_multisort re-indexes arrays with numeric keys, so we need to split the keys out into a separate array then combine them again after
                $post_ids = array_keys($keyword_results);
                $topic_ids = array_values($keyword_results);

                array_multisort(array_values($sort_data), $sort_dir == 'DESC' ? SORT_DESC : SORT_ASC, $sort_type, $post_ids, $topic_ids);

                // combine the arrays back into a key=>value array (array_combine is PHP5 only unfortunately)
                $num_results = count($keyword_results);
                $keyword_results = array();
                for ($i = 0;$i < $num_results;$i++) {
                    $keyword_results[$post_ids[$i]] = $topic_ids[$i];
                }

                unset($sort_data, $post_ids, $topic_ids);
            }

            // If it's a search for author name (and that author name isn't Guest)
            if ($author && $author != 'guest' && $author != utf8_strtolower($lang_common['Guest'])) {
                switch ($db_type) {
                    case 'pgsql':
                        $result = $db->query('SELECT id FROM '.$db->prefix.'users WHERE username ILIKE \''.$db->escape($author).'\'') or error('Unable to fetch users', __FILE__, __LINE__, $db->error());
                        break;

                    default:
                        $result = $db->query('SELECT id FROM '.$db->prefix.'users WHERE username LIKE \''.$db->escape($author).'\'') or error('Unable to fetch users', __FILE__, __LINE__, $db->error());
                        break;
                }

                if ($db->num_rows($result)) {
                    $user_ids = array();
                    while ($row = $db->fetch_row($result)) {
                        $user_ids[] = $row[0];
                    }

                    $result = $db->query('SELECT p.id AS post_id, p.topic_id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.poster_id IN('.implode(',', $user_ids).')'.$forum_sql.' ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch matched posts list', __FILE__, __LINE__, $db->error());
                    while ($temp = $db->fetch_assoc($result)) {
                        $author_results[$temp['post_id']] = $temp['topic_id'];
                    }

                    $db->free_result($result);
                }
            }

            // If we searched for both keywords and author name we want the intersection between the results
            if ($author && $keywords) {
                $search_ids = array_intersect_assoc($keyword_results, $author_results);
                $search_type = array('both', array($keywords, pun_trim($feather->request->get('author'))), implode(',', $forums), $search_in);
            } elseif ($keywords) {
                $search_ids = $keyword_results;
                $search_type = array('keywords', $keywords, implode(',', $forums), $search_in);
            } else {
                $search_ids = $author_results;
                $search_type = array('author', pun_trim($feather->request->get('author')), implode(',', $forums), $search_in);
            }

            unset($keyword_results, $author_results);

            if ($show_as == 'topics') {
                $search_ids = array_values($search_ids);
            } else {
                $search_ids = array_keys($search_ids);
            }

            $search_ids = array_unique($search_ids);

            $num_hits = count($search_ids);
            if (!$num_hits) {
                message($lang_search['No hits']);
            }
        } elseif ($action == 'show_new' || $action == 'show_recent' || $action == 'show_replies' || $action == 'show_user_posts' || $action == 'show_user_topics' || $action == 'show_subscriptions' || $action == 'show_unanswered') {
            $search_type = array('action', $action);
            $show_as = 'topics';
            // We want to sort things after last post
            $sort_by = 0;
            $sort_dir = 'DESC';

            // If it's a search for new posts since last visit
            if ($action == 'show_new') {
                if ($feather_user['is_guest']) {
                    message($lang_common['No permission'], false, '403 Forbidden');
                }

                $result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.last_post>'.$feather_user['last_visit'].' AND t.moved_to IS NULL'.($feather->request->get('fid') ? ' AND t.forum_id='.intval($feather->request->get('fid')) : '').' ORDER BY t.last_post DESC') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
                $num_hits = $db->num_rows($result);

                if (!$num_hits) {
                    message($lang_search['No new posts']);
                }
            }
            // If it's a search for recent posts (in a certain time interval)
            elseif ($action == 'show_recent') {
                $result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.last_post>'.(time() - $interval).' AND t.moved_to IS NULL'.($feather->request->get('fid') ? ' AND t.forum_id='.intval($feather->request->get('fid')) : '').' ORDER BY t.last_post DESC') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
                $num_hits = $db->num_rows($result);

                if (!$num_hits) {
                    message($lang_search['No recent posts']);
                }
            }
            // If it's a search for topics in which the user has posted
            elseif ($action == 'show_replies') {
                $result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.poster_id='.$feather_user['id'].' GROUP BY t.id'.($db_type == 'pgsql' ? ', t.last_post' : '').' ORDER BY t.last_post DESC') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
                $num_hits = $db->num_rows($result);

                if (!$num_hits) {
                    message($lang_search['No user posts']);
                }
            }
            // If it's a search for posts by a specific user ID
            elseif ($action == 'show_user_posts') {
                $show_as = 'posts';

                $result = $db->query('SELECT p.id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON p.topic_id=t.id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.poster_id='.$user_id.' ORDER BY p.posted DESC') or error('Unable to fetch user posts', __FILE__, __LINE__, $db->error());
                $num_hits = $db->num_rows($result);

                if (!$num_hits) {
                    message($lang_search['No user posts']);
                }

                // Pass on the user ID so that we can later know whose posts we're searching for
                $search_type[2] = $user_id;
            }
            // If it's a search for topics by a specific user ID
            elseif ($action == 'show_user_topics') {
                $result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'posts AS p ON t.first_post_id=p.id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.poster_id='.$user_id.' ORDER BY t.last_post DESC') or error('Unable to fetch user topics', __FILE__, __LINE__, $db->error());
                $num_hits = $db->num_rows($result);

                if (!$num_hits) {
                    message($lang_search['No user topics']);
                }

                // Pass on the user ID so that we can later know whose topics we're searching for
                $search_type[2] = $user_id;
            }
            // If it's a search for subscribed topics
            elseif ($action == 'show_subscriptions') {
                if ($feather_user['is_guest']) {
                    message($lang_common['Bad request'], false, '404 Not Found');
                }

                $result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'topic_subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$user_id.') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) ORDER BY t.last_post DESC') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
                $num_hits = $db->num_rows($result);

                if (!$num_hits) {
                    message($lang_search['No subscriptions']);
                }

                // Pass on user ID so that we can later know whose subscriptions we're searching for
                $search_type[2] = $user_id;
            }
            // If it's a search for unanswered posts
            else {
                $result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.num_replies=0 AND t.moved_to IS NULL ORDER BY t.last_post DESC') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
                $num_hits = $db->num_rows($result);

                if (!$num_hits) {
                    message($lang_search['No unanswered']);
                }
            }

            $search_ids = array();
            while ($row = $db->fetch_row($result)) {
                $search_ids[] = $row[0];
            }

            $db->free_result($result);
        } else {
            message($lang_common['Bad request'], false, '404 Not Found');
        }


        // Prune "old" search results
        $old_searches = array();
        $result = $db->query('SELECT ident FROM '.$db->prefix.'online') or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

        if ($db->num_rows($result)) {
            while ($row = $db->fetch_row($result)) {
                $old_searches[] = '\''.$db->escape($row[0]).'\'';
            }

            $db->query('DELETE FROM '.$db->prefix.'search_cache WHERE ident NOT IN('.implode(',', $old_searches).')') or error('Unable to delete search results', __FILE__, __LINE__, $db->error());
        }

        // Fill an array with our results and search properties
        $temp = serialize(array(
            'search_ids'        => serialize($search_ids),
            'num_hits'            => $num_hits,
            'sort_by'            => $sort_by,
            'sort_dir'            => $sort_dir,
            'show_as'            => $show_as,
            'search_type'        => $search_type
        ));
        $search_id = mt_rand(1, 2147483647);

        $ident = ($feather_user['is_guest']) ? get_remote_address() : $feather_user['username'];

        $db->query('INSERT INTO '.$db->prefix.'search_cache (id, ident, search_data) VALUES('.$search_id.', \''.$db->escape($ident).'\', \''.$db->escape($temp).'\')') or error('Unable to insert search results', __FILE__, __LINE__, $db->error());

        if ($search_type[0] != 'action') {
            $db->end_transaction();
            $db->close();

            // Redirect the user to the cached result page
            header('Location: '.get_link('search/?search_id='.$search_id));
            exit;
        }
    }

    // If we're on the new posts search, display a "mark all as read" link
    if (!$feather_user['is_guest'] && $search_type[0] == 'action' && $search_type[1] == 'show_new') {
        $search['forum_actions'][] = '<a href="misc.php?action=markread">'.$lang_common['Mark all as read'].'</a>';
    }

    // Fetch results to display
    if (!empty($search_ids)) {
        // We have results
        $search['is_result'] = true;
        
        switch ($sort_by) {
            case 1:
                $sort_by_sql = ($show_as == 'topics') ? 't.poster' : 'p.poster';
                break;

            case 2:
                $sort_by_sql = 't.subject';
                break;

            case 3:
                $sort_by_sql = 't.forum_id';
                break;

            default:
                $sort_by_sql = ($show_as == 'topics') ? 't.last_post' : 'p.posted';
                break;
        }

        // Determine the topic or post offset (based on $_GET['p'])
        $per_page = ($show_as == 'posts') ? $feather_user['disp_posts'] : $feather_user['disp_topics'];
        $num_pages = ceil($num_hits / $per_page);

        $p = (!$feather->request->get('p') || $feather->request->get('p') <= 1 || $feather->request->get('p') > $num_pages) ? 1 : intval($feather->request->get('p'));
        $start_from = $per_page * ($p - 1);
        $search['start_from'] = $start_from;

        // Generate paging links
        $search['paging_links'] = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate_old($num_pages, $p, '?search_id='.$search_id);

        // throw away the first $start_from of $search_ids, only keep the top $per_page of $search_ids
        $search_ids = array_slice($search_ids, $start_from, $per_page);

        // Run the query and fetch the results
        if ($show_as == 'posts') {
            $result = $db->query('SELECT p.id AS pid, p.poster AS pposter, p.posted AS pposted, p.poster_id, p.message, p.hide_smilies, t.id AS tid, t.poster, t.subject, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.forum_id, f.forum_name FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id WHERE p.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());
        } else {
            $result = $db->query('SELECT t.id AS tid, t.poster, t.subject, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.closed, t.sticky, t.forum_id, f.forum_name FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id WHERE t.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());
        }

        $search['search_set'] = array();
        while ($row = $db->fetch_assoc($result)) {
            $search['search_set'][] = $row;
        }

        $search['crumbs_text']['show_as'] = $lang_search['Search'];

        if ($search_type[0] == 'action') {
            if ($search_type[1] == 'show_user_topics') {
                $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/?action=show_user_topics&amp;user_id='.$search_type[2]).'">'.sprintf($lang_search['Quick search show_user_topics'], pun_htmlspecialchars($search['search_set'][0]['poster'])).'</a>';
            } elseif ($search_type[1] == 'show_user_posts') {
                $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/?action=show_user_posts&amp;user_id='.$search_type[2]).'">'.sprintf($lang_search['Quick search show_user_posts'], pun_htmlspecialchars($search['search_set'][0]['pposter'])).'</a>';
            } elseif ($search_type[1] == 'show_subscriptions') {
                // Fetch username of subscriber
                $subscriber_id = $search_type[2];
                $result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE id='.$subscriber_id) or error('Unable to fetch username of subscriber', __FILE__, __LINE__, $db->error());

                if ($db->num_rows($result)) {
                    $subscriber_name = $db->result($result);
                } else {
                    message($lang_common['Bad request'], false, '404 Not Found');
                }

                $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/?action=show_subscription&amp;user_id='.$subscriber_id).'">'.sprintf($lang_search['Quick search show_subscriptions'], pun_htmlspecialchars($subscriber_name)).'</a>';
            } else {
                $search_url = str_replace('_', '/', $search_type[1]);
                $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/'.$search_url.'/').'">'.$lang_search['Quick search '.$search_type[1]].'</a>';
            }
        } else {
            $keywords = $author = '';

            if ($search_type[0] == 'both') {
                list($keywords, $author) = $search_type[1];
                $search['crumbs_text']['search_type'] = sprintf($lang_search['By both show as '.$show_as], pun_htmlspecialchars($keywords), pun_htmlspecialchars($author));
            } elseif ($search_type[0] == 'keywords') {
                $keywords = $search_type[1];
                $search['crumbs_text']['search_type'] = sprintf($lang_search['By keywords show as '.$show_as], pun_htmlspecialchars($keywords));
            } elseif ($search_type[0] == 'author') {
                $author = $search_type[1];
                $search['crumbs_text']['search_type'] = sprintf($lang_search['By user show as '.$show_as], pun_htmlspecialchars($author));
            }

            $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/?action=search&amp;keywords='.urlencode($keywords).'&amp;author='.urlencode($author).'&amp;forums='.$search_type[2].'&amp;search_in='.$search_type[3].'&amp;sort_by='.$sort_by.'&amp;sort_dir='.$sort_dir.'&amp;show_as='.$show_as).'">'.$search['crumbs_text']['search_type'].'</a>';
        }
    }
    
    $search['show_as'] = $show_as;
    
    return $search;
}

function display_search_results($search, $feather)
{
    global $feather_config, $feather_user, $lang_forum, $lang_common, $lang_topic, $lang_search, $pd;
    
    // Get topic/forum tracking data
    if (!$feather_user['is_guest']) {
        $tracked_topics = get_tracked_topics();
    }
    
    $post_count = $topic_count = 0;

    foreach ($search['search_set'] as $cur_search) {
        $forum = '<a href="'.get_link('forum/'.$cur_search['forum_id'].'/'.url_friendly($cur_search['forum_name']).'/').'">'.pun_htmlspecialchars($cur_search['forum_name']).'</a>';
        $url_topic = url_friendly($cur_search['subject']);

        if ($feather_config['o_censoring'] == '1') {
            $cur_search['subject'] = censor_words($cur_search['subject']);
        }

        if ($search['show_as'] == 'posts') {
            ++$post_count;
            $cur_search['icon_type'] = 'icon';

            if (!$feather_user['is_guest'] && $cur_search['last_post'] > $feather_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_search['tid']]) || $tracked_topics['topics'][$cur_search['tid']] < $cur_search['last_post']) && (!isset($tracked_topics['forums'][$cur_search['forum_id']]) || $tracked_topics['forums'][$cur_search['forum_id']] < $cur_search['last_post'])) {
                $cur_search['item_status'] = 'inew';
                $cur_search['icon_type'] = 'icon icon-new';
                $cur_search['icon_text'] = $lang_topic['New icon'];
            } else {
                $cur_search['item_status'] = '';
                $cur_search['icon_text'] = '<!-- -->';
            }

            if ($feather_config['o_censoring'] == '1') {
                $cur_search['message'] = censor_words($cur_search['message']);
            }

            $cur_search['message'] = parse_message($cur_search['message'], $cur_search['hide_smilies']);
            $pposter = pun_htmlspecialchars($cur_search['pposter']);

            if ($cur_search['poster_id'] > 1 && $feather_user['g_view_users'] == '1') {
                $cur_search['pposter_disp'] = '<strong><a href="'.get_link('user/'.$cur_search['poster_id'].'/').'">'.$pposter.'</a></strong>';
            } else {
                $cur_search['pposter_disp'] = '<strong>'.$pposter.'</strong>';
            }
            
            $feather->render('search/posts.php', array(
                'post_count' => $post_count,
                'url_topic' => $url_topic,
                'cur_search' => $cur_search,
                'forum' => $forum,
                'lang_common' => $lang_common,
                'lang_search' => $lang_search,
                'lang_topic' => $lang_topic,
                )
            );
        } else {
            ++$topic_count;
            $status_text = array();
            $cur_search['item_status'] = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';
            $cur_search['icon_type'] = 'icon';

            $subject = '<a href="'.get_link('topic/'.$cur_search['tid'].'/'.$url_topic.'/').'">'.pun_htmlspecialchars($cur_search['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_search['poster']).'</span>';

            if ($cur_search['sticky'] == '1') {
                $cur_search['item_status'] .= ' isticky';
                $status_text[] = '<span class="stickytext">'.$lang_forum['Sticky'].'</span>';
            }

            if ($cur_search['closed'] != '0') {
                $status_text[] = '<span class="closedtext">'.$lang_forum['Closed'].'</span>';
                $cur_search['item_status'] .= ' iclosed';
            }

            if (!$feather_user['is_guest'] && $cur_search['last_post'] > $feather_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_search['tid']]) || $tracked_topics['topics'][$cur_search['tid']] < $cur_search['last_post']) && (!isset($tracked_topics['forums'][$cur_search['forum_id']]) || $tracked_topics['forums'][$cur_search['forum_id']] < $cur_search['last_post'])) {
                $cur_search['item_status'] .= ' inew';
                $cur_search['icon_type'] = 'icon icon-new';
                $subject = '<strong>'.$subject.'</strong>';
                $subject_new_posts = '<span class="newtext">[ <a href="'.get_link('topic/'.$cur_search['tid'].'/action/new/').'" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';
            } else {
                $subject_new_posts = null;
            }

            // Insert the status text before the subject
            $subject = implode(' ', $status_text).' '.$subject;

            $num_pages_topic = ceil(($cur_search['num_replies'] + 1) / $feather_user['disp_posts']);

            if ($num_pages_topic > 1) {
                $subject_multipage = '<span class="pagestext">[ '.paginate($num_pages_topic, -1, 'topic/'.$cur_search['tid'].'/'.$url_topic.'/#').' ]</span>';
            } else {
                $subject_multipage = null;
            }

            // Should we show the "New posts" and/or the multipage links?
            if (!empty($subject_new_posts) || !empty($subject_multipage)) {
                $subject .= !empty($subject_new_posts) ? ' '.$subject_new_posts : '';
                $subject .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
            }
            
            if (!isset($cur_search['start_from'])) {
                $start_from = 0;
            } else {
                $start_from = $cur_search['start_from'];
            }
            
            $feather->render('search/topics.php', array(
                'cur_search' => $cur_search,
                'start_from' => $start_from,
                'topic_count' => $topic_count,
                'subject' => $subject,
                'forum' => $forum,
                'lang_common' => $lang_common,
                )
            );
        }
    }
}

function get_list_forums()
{
    global $db, $feather_config, $feather_user, $lang_search;
    
    $result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.redirect_url IS NULL ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

    // We either show a list of forums of which multiple can be selected
    if ($feather_config['o_search_all_forums'] == '1' || $feather_user['is_admmod']) {
        echo "\t\t\t\t\t\t".'<div class="conl multiselect">'.$lang_search['Forum search']."\n";
        echo "\t\t\t\t\t\t".'<br />'."\n";
        echo "\t\t\t\t\t\t".'<div class="checklist">'."\n";

        $cur_category = 0;
        while ($cur_forum = $db->fetch_assoc($result)) {
            if ($cur_forum['cid'] != $cur_category) {
                // A new category since last iteration?

                if ($cur_category) {
                    echo "\t\t\t\t\t\t\t\t".'</div>'."\n";
                    echo "\t\t\t\t\t\t\t".'</fieldset>'."\n";
                }
                
                echo "\t\t\t\t\t\t\t".'<fieldset><legend><span>'.pun_htmlspecialchars($cur_forum['cat_name']).'</span></legend>'."\n";
                echo "\t\t\t\t\t\t\t\t".'<div class="rbox">';
                $cur_category = $cur_forum['cid'];
            }

            echo "\t\t\t\t\t\t\t\t".'<label><input type="checkbox" name="forums[]" id="forum-'.$cur_forum['fid'].'" value="'.$cur_forum['fid'].'" />'.pun_htmlspecialchars($cur_forum['forum_name']).'</label>'."\n";
        }

        if ($cur_category) {
            echo "\t\t\t\t\t\t\t\t".'</div>'."\n";
            echo "\t\t\t\t\t\t\t".'</fieldset>'."\n";
        }
        
        echo "\t\t\t\t\t\t".'</div>'."\n";
        echo "\t\t\t\t\t\t".'</div>'."\n";
    }
    // ... or a simple select list for one forum only
    else {
        echo "\t\t\t\t\t\t".'<label class="conl">'.$lang_search['Forum search']."\n";
        echo "\t\t\t\t\t\t".'<br />'."\n";
        echo "\t\t\t\t\t\t".'<select id="forum" name="forum">'."\n";

        $cur_category = 0;
        while ($cur_forum = $db->fetch_assoc($result)) {
            if ($cur_forum['cid'] != $cur_category) {
                // A new category since last iteration?

                if ($cur_category) {
                    echo "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                echo "\t\t\t\t\t\t\t".'<optgroup label="'.pun_htmlspecialchars($cur_forum['cat_name']).'">'."\n";
                $cur_category = $cur_forum['cid'];
            }

            echo "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
        }

        echo "\t\t\t\t\t\t\t".'</optgroup>'."\n";
        echo "\t\t\t\t\t\t".'</select>'."\n";
        echo "\t\t\t\t\t\t".'<br /></label>'."\n";
    }
}
