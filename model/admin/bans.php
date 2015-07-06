<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function add_ban_info($feather)
{
    global $db, $lang_common, $lang_admin_bans;
    
    $ban = array();
    
    // If the ID of the user to ban was provided through GET (a link from profile.php)
    if ($feather->request->get('find_ban')) {
        $ban['user_id'] = intval($feather->request->get('find_ban'));
        if ($ban['user_id'] < 2) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $result = $db->query('SELECT group_id, username, email FROM '.$db->prefix.'users WHERE id='.$ban['user_id']) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
        if ($db->num_rows($result)) {
            list($group_id, $ban['ban_user'], $ban['email']) = $db->fetch_row($result);
        } else {
            message($lang_admin_bans['No user ID message']);
        }
    } else {
        // Otherwise the username is in POST

        $ban['ban_user'] = pun_trim($feather->request->post('new_ban_user'));

        if ($ban['ban_user'] != '') {
            $result = $db->query('SELECT id, group_id, username, email FROM '.$db->prefix.'users WHERE username=\''.$db->escape($ban['ban_user']).'\' AND id>1') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
            if ($db->num_rows($result)) {
                list($ban['user_id'], $group_id, $ban['ban_user'], $ban['email']) = $db->fetch_row($result);
            } else {
                message($lang_admin_bans['No user message']);
            }
        }
    }

    // Make sure we're not banning an admin or moderator
    if (isset($group_id)) {
        if ($group_id == FEATHER_ADMIN) {
            message(sprintf($lang_admin_bans['User is admin message'], pun_htmlspecialchars($ban['ban_user'])));
        }

        $result = $db->query('SELECT g_moderator FROM '.$db->prefix.'groups WHERE g_id='.$group_id) or error('Unable to fetch group info', __FILE__, __LINE__, $db->error());
        $is_moderator_group = $db->result($result);

        if ($is_moderator_group) {
            message(sprintf($lang_admin_bans['User is mod message'], pun_htmlspecialchars($ban['ban_user'])));
        }
    }

    // If we have a $ban['user_id'], we can try to find the last known IP of that user
    if (isset($ban['user_id'])) {
        $result = $db->query('SELECT poster_ip FROM '.$db->prefix.'posts WHERE poster_id='.$ban['user_id'].' ORDER BY posted DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
        $ban['ip'] = ($db->num_rows($result)) ? $db->result($result) : '';

        if ($ban['ip'] == '') {
            $result = $db->query('SELECT registration_ip FROM '.$db->prefix.'users WHERE id='.$ban['user_id']) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
            $ban['ip'] = ($db->num_rows($result)) ? $db->result($result) : '';
        }
    }

    $ban['mode'] = 'add';
    
    return $ban;
}

function edit_ban_info($id)
{
    global $db, $lang_common, $feather_user;
    
    $ban = array();

    $ban['id'] = $id;

    $result = $db->query('SELECT username, ip, email, message, expire FROM '.$db->prefix.'bans WHERE id='.$ban['id']) or error('Unable to fetch ban info', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        list($ban['ban_user'], $ban['ip'], $ban['email'], $ban['message'], $ban['expire']) = $db->fetch_row($result);
    } else {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $diff = ($feather_user['timezone'] + $feather_user['dst']) * 3600;
    $ban['expire'] = ($ban['expire'] != '') ? gmdate('Y-m-d', $ban['expire'] + $diff) : '';

    $ban['mode'] = 'edit';
    
    return $ban;
}

function insert_ban($feather)
{
    global $db, $lang_admin_bans, $feather_user;

    confirm_referrer(array(get_link_r('admin/bans/add/'), get_link_r('admin/bans/edit/'.$feather->request->post('ban_id').'/')));

    $ban_user = pun_trim($feather->request->post('ban_user'));
    $ban_ip = pun_trim($feather->request->post('ban_ip'));
    $ban_email = strtolower(pun_trim($feather->request->post('ban_email')));
    $ban_message = pun_trim($feather->request->post('ban_message'));
    $ban_expire = pun_trim($feather->request->post('ban_expire'));

    if ($ban_user == '' && $ban_ip == '' && $ban_email == '') {
        message($lang_admin_bans['Must enter message']);
    } elseif (strtolower($ban_user) == 'guest') {
        message($lang_admin_bans['Cannot ban guest message']);
    }

    // Make sure we're not banning an admin or moderator
    if (!empty($ban_user)) {
        $result = $db->query('SELECT group_id FROM '.$db->prefix.'users WHERE username=\''.$db->escape($ban_user).'\' AND id>1') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
        if ($db->num_rows($result)) {
            $group_id = $db->result($result);

            if ($group_id == FEATHER_ADMIN) {
                message(sprintf($lang_admin_bans['User is admin message'], pun_htmlspecialchars($ban_user)));
            }

            $result = $db->query('SELECT g_moderator FROM '.$db->prefix.'groups WHERE g_id='.$group_id) or error('Unable to fetch group info', __FILE__, __LINE__, $db->error());
            $is_moderator_group = $db->result($result);

            if ($is_moderator_group) {
                message(sprintf($lang_admin_bans['User is mod message'], pun_htmlspecialchars($ban_user)));
            }
        }
    }

    // Validate IP/IP range (it's overkill, I know)
    if ($ban_ip != '') {
        $ban_ip = preg_replace('%\s{2,}%S', ' ', $ban_ip);
        $addresses = explode(' ', $ban_ip);
        $addresses = array_map('pun_trim', $addresses);

        for ($i = 0; $i < count($addresses); ++$i) {
            if (strpos($addresses[$i], ':') !== false) {
                $octets = explode(':', $addresses[$i]);

                for ($c = 0; $c < count($octets); ++$c) {
                    $octets[$c] = ltrim($octets[$c], "0");

                    if ($c > 7 || (!empty($octets[$c]) && !ctype_xdigit($octets[$c])) || intval($octets[$c], 16) > 65535) {
                        message($lang_admin_bans['Invalid IP message']);
                    }
                }

                $cur_address = implode(':', $octets);
                $addresses[$i] = $cur_address;
            } else {
                $octets = explode('.', $addresses[$i]);

                for ($c = 0; $c < count($octets); ++$c) {
                    $octets[$c] = (strlen($octets[$c]) > 1) ? ltrim($octets[$c], "0") : $octets[$c];

                    if ($c > 3 || preg_match('%[^0-9]%', $octets[$c]) || intval($octets[$c]) > 255) {
                        message($lang_admin_bans['Invalid IP message']);
                    }
                }

                $cur_address = implode('.', $octets);
                $addresses[$i] = $cur_address;
            }
        }

        $ban_ip = implode(' ', $addresses);
    }

    require FEATHER_ROOT.'include/email.php';
    if ($ban_email != '' && !is_valid_email($ban_email)) {
        if (!preg_match('%^[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,63})$%', $ban_email)) {
            message($lang_admin_bans['Invalid e-mail message']);
        }
    }

    if ($ban_expire != '' && $ban_expire != 'Never') {
        $ban_expire = strtotime($ban_expire.' GMT');

        if ($ban_expire == -1 || !$ban_expire) {
            message($lang_admin_bans['Invalid date message'].' '.$lang_admin_bans['Invalid date reasons']);
        }

        $diff = ($feather_user['timezone'] + $feather_user['dst']) * 3600;
        $ban_expire -= $diff;

        if ($ban_expire <= time()) {
            message($lang_admin_bans['Invalid date message'].' '.$lang_admin_bans['Invalid date reasons']);
        }
    } else {
        $ban_expire = 'NULL';
    }

    $ban_user = ($ban_user != '') ? '\''.$db->escape($ban_user).'\'' : 'NULL';
    $ban_ip = ($ban_ip != '') ? '\''.$db->escape($ban_ip).'\'' : 'NULL';
    $ban_email = ($ban_email != '') ? '\''.$db->escape($ban_email).'\'' : 'NULL';
    $ban_message = ($ban_message != '') ? '\''.$db->escape($ban_message).'\'' : 'NULL';

    if ($feather->request->post('mode') == 'add') {
        $db->query('INSERT INTO '.$db->prefix.'bans (username, ip, email, message, expire, ban_creator) VALUES('.$ban_user.', '.$ban_ip.', '.$ban_email.', '.$ban_message.', '.$ban_expire.', '.$feather_user['id'].')') or error('Unable to add ban', __FILE__, __LINE__, $db->error());
    } else {
        $db->query('UPDATE '.$db->prefix.'bans SET username='.$ban_user.', ip='.$ban_ip.', email='.$ban_email.', message='.$ban_message.', expire='.$ban_expire.' WHERE id='.intval($feather->request->post('ban_id'))) or error('Unable to update ban', __FILE__, __LINE__, $db->error());
    }

    // Regenerate the bans cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require FEATHER_ROOT.'include/cache.php';
    }

    generate_bans_cache();

    redirect(get_link('admin/bans/'), $lang_admin_bans['Ban edited redirect']);
}

function remove_ban($ban_id)
{
    global $db, $lang_common, $lang_admin_bans;
    
    confirm_referrer(get_link_r('admin/bans/'));

    $db->query('DELETE FROM '.$db->prefix.'bans WHERE id='.$ban_id) or error('Unable to delete ban', __FILE__, __LINE__, $db->error());

    // Regenerate the bans cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require FEATHER_ROOT.'include/cache.php';
    }

    generate_bans_cache();

    redirect(get_link('admin/bans/'), $lang_admin_bans['Ban removed redirect']);
}

function find_ban($feather)
{
    global $db, $db_type;
    
    $ban_info = array();

    // trim() all elements in $form
    $ban_info['conditions'] = $ban_info['query_str'] = array();

    $expire_after = $feather->request->get('expire_after') ? pun_trim($feather->request->get('expire_after')) : '';
    $expire_before = $feather->request->get('expire_before') ? pun_trim($feather->request->get('expire_before')) : '';
    $ban_info['order_by'] = $feather->request->get('order_by') && in_array($feather->request->get('order_by'), array('username', 'ip', 'email', 'expire')) ? 'b.'.$feather->request->get('order_by') : 'b.username';
    $ban_info['direction'] = $feather->request->get('direction') && $feather->request->get('direction') == 'DESC' ? 'DESC' : 'ASC';

    $ban_info['query_str'][] = 'order_by='.$ban_info['order_by'];
    $ban_info['query_str'][] = 'direction='.$ban_info['direction'];

    // Try to convert date/time to timestamps
    if ($expire_after != '') {
        $ban_info['query_str'][] = 'expire_after='.$expire_after;

        $expire_after = strtotime($expire_after);
        if ($expire_after === false || $expire_after == -1) {
            message($lang_admin_bans['Invalid date message']);
        }

        $ban_info['conditions'][] = 'b.expire>'.$expire_after;
    }
    if ($expire_before != '') {
        $ban_info['query_str'][] = 'expire_before='.$expire_before;

        $expire_before = strtotime($expire_before);
        if ($expire_before === false || $expire_before == -1) {
            message($lang_admin_bans['Invalid date message']);
        }

        $ban_info['conditions'][] = 'b.expire<'.$expire_before;
    }

    $like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

    if ($feather->request->get('username')) {
        $ban_info['conditions'][] = 'b.username ' . $like_command . ' \'' . $db->escape(str_replace('*', '%', $feather->request->get('username'))) . '\'';
        $ban_info['query_str'][] = 'username=' . urlencode($feather->request->get('username'));
    }

    if ($feather->request->get('ip')) {
        $ban_info['conditions'][] = 'b.ip ' . $like_command . ' \'' . $db->escape(str_replace('*', '%', $feather->request->get('ip'))) . '\'';
        $ban_info['query_str'][] = 'ip=' . urlencode($feather->request->get('ip'));
    }

    if ($feather->request->get('email')) {
        $ban_info['conditions'][] = 'b.email ' . $like_command . ' \'' . $db->escape(str_replace('*', '%', $feather->request->get('email'))) . '\'';
        $ban_info['query_str'][] = 'email=' . urlencode($feather->request->get('email'));
    }

    if ($feather->request->get('message')) {
        $ban_info['conditions'][] = 'b.message ' . $like_command . ' \'' . $db->escape(str_replace('*', '%', $feather->request->get('message'))) . '\'';
        $ban_info['query_str'][] = 'message=' . urlencode($feather->request->get('message'));
    }

    // Fetch ban count
    $result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'bans as b WHERE b.id>0'.(!empty($ban_info['conditions']) ? ' AND '.implode(' AND ', $ban_info['conditions']) : '')) or error('Unable to fetch ban list', __FILE__, __LINE__, $db->error());
    $ban_info['num_bans'] = $db->result($result);

    return $ban_info;
}

function print_bans($conditions, $order_by, $direction, $start_from)
{
    global $db;

    $ban_data = array();
    
    $result = $db->query('SELECT b.id, b.username, b.ip, b.email, b.message, b.expire, b.ban_creator, u.username AS ban_creator_username FROM '.$db->prefix.'bans AS b LEFT JOIN '.$db->prefix.'users AS u ON b.ban_creator=u.id WHERE b.id>0'.(!empty($conditions) ? ' AND '.implode(' AND ', $conditions) : '').' ORDER BY '.$db->escape($order_by).' '.$db->escape($direction).' LIMIT '.$start_from.', 50') or error('Unable to fetch ban list', __FILE__, __LINE__, $db->error());
    while ($cur_ban = $db->fetch_assoc($result)) {
        $ban_data[] = $cur_ban;
    }

    return $ban_data;
}
