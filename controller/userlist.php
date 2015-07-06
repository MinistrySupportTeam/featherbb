<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class userlist
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
    
    public function display()
    {
        global $lang_common, $feather_config, $feather_user, $db;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        } elseif ($feather_user['g_view_users'] == '0') {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the userlist.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/userlist.php';

        // Load the search.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/search.php';

        // Load the userlist.php model file
        require FEATHER_ROOT.'model/userlist.php';


        // Determine if we are allowed to view post counts
        $show_post_count = ($feather_config['o_show_post_count'] == '1' || $feather_user['is_admmod']) ? true : false;

        $username = $this->feather->request->get('username') && $feather_user['g_search_users'] == '1' ? pun_trim($this->feather->request->get('username')) : '';
        $show_group = $this->feather->request->get('show_group') ? intval($this->feather->request->get('show_group')) : -1;
        $sort_by = $this->feather->request->get('sort_by') && (in_array($this->feather->request->get('sort_by'), array('username', 'registered')) || ($this->feather->request->get('sort_by') == 'num_posts' && $show_post_count)) ? $this->feather->request->get('sort_by') : 'username';
        $sort_dir = $this->feather->request->get('sort_dir') && $this->feather->request->get('sort_dir') == 'DESC' ? 'DESC' : 'ASC';

        $num_users = fetch_user_count($username, $show_group);

                    // Determine the user offset (based on $page)
                    $num_pages = ceil($num_users / 50);

        $p = (!$this->feather->request->get('p') || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = 50 * ($p - 1);

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_common['User list']);
        if ($feather_user['g_search_users'] == '1') {
            $focus_element = array('userlist', 'username');
        }

        // Generate paging links
        $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate_old($num_pages, $p, '?username='.urlencode($username).'&amp;show_group='.$show_group.'&amp;sort_by='.$sort_by.'&amp;sort_dir='.$sort_dir);


        define('FEATHER_ALLOW_INDEX', 1);

        define('FEATHER_ACTIVE_PAGE', 'userlist');

        require FEATHER_ROOT.'include/header.php';

        $this->feather->render('userlist.php', array(
                            'lang_common' => $lang_common,
                            'lang_search' => $lang_search,
                            'lang_ul' => $lang_ul,
                            'feather_user' => $feather_user,
                            'username' => $username,
                            'show_group' => $show_group,
                            'sort_by' => $sort_by,
                            'sort_dir' => $sort_dir,
                            'show_post_count' => $show_post_count,
                            'paging_links' => $paging_links,
                            'feather_config' => $feather_config,
                            'userlist_data' => print_users($username, $start_from, $sort_by, $sort_dir, $show_post_count, $show_group),
                            )
                    );

        require FEATHER_ROOT.'include/footer.php';
    }
}
