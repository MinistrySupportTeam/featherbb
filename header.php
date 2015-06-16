<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN')) {
    exit;
}

// Send no-cache headers
header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache'); // For HTTP/1.0 compatibility

// Send the Content-type header in case the web server is setup to send something else
header('Content-type: text/html; charset=utf-8');

// Prevent site from being embedded in a frame
header('X-Frame-Options: deny');

// START SUBST - <pun_include "*"> TODO?
/*
foreach ($pun_includes as $cur_include) {
    ob_start();

    $file_info = pathinfo($cur_include[1]);
    
    if (!in_array($file_info['extension'], array('php', 'php4', 'php5', 'inc', 'html', 'txt'))) { // Allow some extensions
        error(sprintf($lang_common['Pun include extension'], pun_htmlspecialchars($cur_include[0]), basename($tpl_file), pun_htmlspecialchars($file_info['extension'])));
    }
        
    if (strpos($file_info['dirname'], '..') !== false) { // Don't allow directory traversal
        error(sprintf($lang_common['Pun include directory'], pun_htmlspecialchars($cur_include[0]), basename($tpl_file)));
    }

    // Allow for overriding user includes, too.
    if (file_exists($tpl_inc_dir.$cur_include[1])) {
        require $tpl_inc_dir.$cur_include[1];
    } elseif (file_exists(PUN_ROOT.'include/user/'.$cur_include[1])) {
        require PUN_ROOT.'include/user/'.$cur_include[1];
    } else {
        error(sprintf($lang_common['Pun include error'], pun_htmlspecialchars($cur_include[0]), basename($tpl_file)));
    }

    $tpl_temp = ob_get_contents();
    $tpl_main = str_replace($cur_include[0], $tpl_temp, $tpl_main);
    ob_end_clean();
}
*/
// END SUBST - <pun_include "*">

// Define $p if it's not set to avoid a PHP notice
$p = isset($p) ? $p : null;


// START SUBST - <body> TODO
/*if (isset($focus_element)) {
    $tpl_main = str_replace('<body onload="', '<body onload="document.getElementById(\''.$focus_element[0].'\').elements[\''.$focus_element[1].'\'].focus();', $tpl_main);
    $tpl_main = str_replace('<body>', '<body onload="document.getElementById(\''.$focus_element[0].'\').elements[\''.$focus_element[1].'\'].focus()">', $tpl_main);
}*/
// END SUBST - <body>


// START SUBST - <pun_navlinks>
$links = array();

// Index should always be displayed
$links[] = '<li id="navindex"'.((PUN_ACTIVE_PAGE == 'index') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/index.php">'.$lang_common['Index'].'</a></li>';

if ($pun_user['g_read_board'] == '1' && $pun_user['g_view_users'] == '1') {
    $links[] = '<li id="navuserlist"'.((PUN_ACTIVE_PAGE == 'userlist') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/userlist.php">'.$lang_common['User list'].'</a></li>';
}

if ($pun_config['o_rules'] == '1' && (!$pun_user['is_guest'] || $pun_user['g_read_board'] == '1' || $pun_config['o_regs_allow'] == '1')) {
    $links[] = '<li id="navrules"'.((PUN_ACTIVE_PAGE == 'rules') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/misc.php?action=rules">'.$lang_common['Rules'].'</a></li>';
}

if ($pun_user['g_read_board'] == '1' && $pun_user['g_search'] == '1') {
    $links[] = '<li id="navsearch"'.((PUN_ACTIVE_PAGE == 'search') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/search.php">'.$lang_common['Search'].'</a></li>';
}

if ($pun_user['is_guest']) {
    $links[] = '<li id="navregister"'.((PUN_ACTIVE_PAGE == 'register') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/register.php">'.$lang_common['Register'].'</a></li>';
    $links[] = '<li id="navlogin"'.((PUN_ACTIVE_PAGE == 'login') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/login.php">'.$lang_common['Login'].'</a></li>';
} else {
    $links[] = '<li id="navprofile"'.((PUN_ACTIVE_PAGE == 'profile') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/profile.php?id='.$pun_user['id'].'">'.$lang_common['Profile'].'</a></li>';

    if ($pun_user['is_admmod']) {
        $links[] = '<li id="navadmin"'.((PUN_ACTIVE_PAGE == 'admin') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/admin_index.php">'.$lang_common['Admin'].'</a></li>';
    }

    $links[] = '<li id="navlogout"><a href="'.get_base_url().'/login.php?action=out&amp;id='.$pun_user['id'].'&amp;csrf_token='.pun_hash($pun_user['id'].pun_hash(get_remote_address())).'">'.$lang_common['Logout'].'</a></li>';
}

// Are there any additional navlinks we should insert into the array before imploding it?
if ($pun_user['g_read_board'] == '1' && $pun_config['o_additional_navlinks'] != '') {
    if (preg_match_all('%([0-9]+)\s*=\s*(.*?)\n%s', $pun_config['o_additional_navlinks']."\n", $extra_links)) {
        // Insert any additional links into the $links array (at the correct index)
        $num_links = count($extra_links[1]);
        for ($i = 0; $i < $num_links; ++$i) {
            array_splice($links, $extra_links[1][$i], 0, array('<li id="navextra'.($i + 1).'">'.$extra_links[2][$i].'</li>'));
        }
    }
}

$navlinks = '<div id="brdmenu" class="inbox">'."\n\t\t\t".'<ul>'."\n\t\t\t\t".implode("\n\t\t\t\t", $links)."\n\t\t\t".'</ul>'."\n\t\t".'</div>';
// END SUBST - <pun_navlinks>


// START SUBST - <pun_status>
$page_statusinfo = $page_topicsearches = array();

if ($pun_user['is_guest']) {
    $page_statusinfo = '<p class="conl">'.$lang_common['Not logged in'].'</p>';
} else {
    $page_statusinfo[] = '<li><span>'.$lang_common['Logged in as'].' <strong>'.pun_htmlspecialchars($pun_user['username']).'</strong></span></li>';
    $page_statusinfo[] = '<li><span>'.sprintf($lang_common['Last visit'], format_time($pun_user['last_visit'])).'</span></li>';

    if ($pun_user['is_admmod']) {
        if ($pun_config['o_report_method'] == '0' || $pun_config['o_report_method'] == '2') {
            $result_header = $db->query('SELECT 1 FROM '.$db->prefix.'reports WHERE zapped IS NULL') or error('Unable to fetch reports info', __FILE__, __LINE__, $db->error());

            if ($db->result($result_header)) {
                $page_statusinfo[] = '<li class="reportlink"><span><strong><a href="'.get_base_url().'/admin_reports.php">'.$lang_common['New reports'].'</a></strong></span></li>';
            }
        }

        if ($pun_config['o_maintenance'] == '1') {
            $page_statusinfo[] = '<li class="maintenancelink"><span><strong><a href="'.get_base_url().'/admin_options.php#maintenance">'.$lang_common['Maintenance mode enabled'].'</a></strong></span></li>';
        }
    }

    if ($pun_user['g_read_board'] == '1' && $pun_user['g_search'] == '1') {
        $page_topicsearches[] = '<a href="'.get_base_url().'/search.php?action=show_replies" title="'.$lang_common['Show posted topics'].'">'.$lang_common['Posted topics'].'</a>';
        $page_topicsearches[] = '<a href="'.get_base_url().'/search.php?action=show_new" title="'.$lang_common['Show new posts'].'">'.$lang_common['New posts header'].'</a>';
    }
}

// Quick searches
if ($pun_user['g_read_board'] == '1' && $pun_user['g_search'] == '1') {
    $page_topicsearches[] = '<a href="'.get_base_url().'/search.php?action=show_recent" title="'.$lang_common['Show active topics'].'">'.$lang_common['Active topics'].'</a>';
    $page_topicsearches[] = '<a href="'.get_base_url().'/search.php?action=show_unanswered" title="'.$lang_common['Show unanswered topics'].'">'.$lang_common['Unanswered topics'].'</a>';
}


// Generate all that jazz
$page_info = '<div id="brdwelcome" class="inbox">';

// The status information
if (is_array($page_statusinfo)) {
    $page_info .= "\n\t\t\t".'<ul class="conl">';
    $page_info .= "\n\t\t\t\t".implode("\n\t\t\t\t", $page_statusinfo);
    $page_info .= "\n\t\t\t".'</ul>';
} else {
    $page_info .= "\n\t\t\t".$page_statusinfo;
}

// Generate quicklinks
if (!empty($page_topicsearches)) {
    $page_info .= "\n\t\t\t".'<ul class="conr">';
    $page_info .= "\n\t\t\t\t".'<li><span>'.$lang_common['Topic searches'].' '.implode(' | ', $page_topicsearches).'</span></li>';
    $page_info .= "\n\t\t\t".'</ul>';
}

$page_info .= "\n\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>';
// END SUBST - <pun_status>


// START SUBST - <pun_main>
ob_start();


//define('PUN_HEADER', 1);
