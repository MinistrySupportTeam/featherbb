<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function fetch_groups()
{
	global $db;
	
	$groups = array();
	
	$result = $db->query('SELECT * FROM '.$db->prefix.'groups ORDER BY g_id') or error('Unable to fetch user groups', __FILE__, __LINE__, $db->error());
	$groups = array();
	while ($cur_group = $db->fetch_assoc($result)) {
		$groups[$cur_group['g_id']] = $cur_group;
	}

	return $groups;
}
 
function info_add_group($groups, $post_data, $get_data)
{
    global $lang_common;

    $group = array();

    if (isset($post_data['add_group'])) {
        $group['base_group'] = intval($post_data['base_group']);
        $group['info'] = $groups[$group['base_group']];

        $group['mode'] = 'add';
    }
    else {
		// We are editing a group
        $group['id'] = intval($get_data['edit_group']);
        if ($group['id'] < 1 || !isset($groups[$group['id']])) {
            message($lang_common['Bad request'], false, '404 Not Found');
		}

        $group['info'] = $groups[$group['id']];

        $group['mode'] = 'edit';
    }

    return $group;
}

function get_group_list($groups)
{
	global $db;
	
	foreach ($groups as $cur_group) {
		if (($cur_group['g_id'] != $group['info']['g_id'] || $group['mode'] == 'add') && $cur_group['g_id'] != PUN_ADMIN && $cur_group['g_id'] != PUN_GUEST)
		{
			if ($cur_group['g_id'] == $group['info']['g_promote_next_group']) {
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
			}
			else {
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
			}
		}
	}
}

function get_group_list_delete($group_id)
{
	global $db;
	
	$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups WHERE g_id!='.PUN_GUEST.' AND g_id!='.$group_id.' ORDER BY g_title') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

	while ($cur_group = $db->fetch_assoc($result)) {
		if ($cur_group['g_id'] == PUN_MEMBER) {
			// Pre-select the pre-defined Members group
			echo "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
		}
		else {
			echo "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
		}
	}
}

function add_edit_group($groups, $post_data)
{
	global $db, $lang_admin_groups;
	
		confirm_referrer('admin_groups.php');

	// Is this the admin group? (special rules apply)
	$is_admin_group = (isset($post_data['group_id']) && $post_data['group_id'] == PUN_ADMIN) ? true : false;

	$title = pun_trim($post_data['req_title']);
	$user_title = pun_trim($post_data['user_title']);

	$promote_min_posts = isset($post_data['promote_min_posts']) ? intval($post_data['promote_min_posts']) : '0';
	if (isset($post_data['promote_next_group']) &&
			isset($groups[$post_data['promote_next_group']]) &&
			!in_array($post_data['promote_next_group'], array(PUN_ADMIN, PUN_GUEST)) &&
			(!isset($post_data['group_id']) || $post_data['promote_next_group'] != $post_data['group_id'])) {
		$promote_next_group = $post_data['promote_next_group'];
	}
	else {
		$promote_next_group = '0';
	}

	$moderator = isset($post_data['moderator']) && $post_data['moderator'] == '1' ? '1' : '0';
	$mod_edit_users = $moderator == '1' && isset($post_data['mod_edit_users']) && $post_data['mod_edit_users'] == '1' ? '1' : '0';
	$mod_rename_users = $moderator == '1' && isset($post_data['mod_rename_users']) && $post_data['mod_rename_users'] == '1' ? '1' : '0';
	$mod_change_passwords = $moderator == '1' && isset($post_data['mod_change_passwords']) && $post_data['mod_change_passwords'] == '1' ? '1' : '0';
	$mod_ban_users = $moderator == '1' && isset($post_data['mod_ban_users']) && $post_data['mod_ban_users'] == '1' ? '1' : '0';
	$mod_promote_users = $moderator == '1' && isset($post_data['mod_promote_users']) && $post_data['mod_promote_users'] == '1' ? '1' : '0';
	$read_board = isset($post_data['read_board']) ? intval($post_data['read_board']) : '1';
	$view_users = (isset($post_data['view_users']) && $post_data['view_users'] == '1') || $is_admin_group ? '1' : '0';
	$post_replies = isset($post_data['post_replies']) ? intval($post_data['post_replies']) : '1';
	$post_topics = isset($post_data['post_topics']) ? intval($post_data['post_topics']) : '1';
	$edit_posts = isset($post_data['edit_posts']) ? intval($post_data['edit_posts']) : ($is_admin_group) ? '1' : '0';
	$delete_posts = isset($post_data['delete_posts']) ? intval($post_data['delete_posts']) : ($is_admin_group) ? '1' : '0';
	$delete_topics = isset($post_data['delete_topics']) ? intval($post_data['delete_topics']) : ($is_admin_group) ? '1' : '0';
	$post_links = isset($post_data['post_links']) ? intval($post_data['post_links']) : '1';
	$set_title = isset($post_data['set_title']) ? intval($post_data['set_title']) : ($is_admin_group) ? '1' : '0';
	$search = isset($post_data['search']) ? intval($post_data['search']) : '1';
	$search_users = isset($post_data['search_users']) ? intval($post_data['search_users']) : '1';
	$send_email = (isset($post_data['send_email']) && $post_data['send_email'] == '1') || $is_admin_group ? '1' : '0';
	$post_flood = (isset($post_data['post_flood']) && $post_data['post_flood'] >= 0) ? intval($post_data['post_flood']) : '0';
	$search_flood = (isset($post_data['search_flood']) && $post_data['search_flood'] >= 0) ? intval($post_data['search_flood']) : '0';
	$email_flood = (isset($post_data['email_flood']) && $post_data['email_flood'] >= 0) ? intval($post_data['email_flood']) : '0';
	$report_flood = (isset($post_data['report_flood']) && $post_data['report_flood'] >= 0) ? intval($post_data['report_flood']) : '0';

	if ($title == '') {
		message($lang_admin_groups['Must enter title message']);
	}

	$user_title = ($user_title != '') ? '\''.$db->escape($user_title).'\'' : 'NULL';

	if ($post_data['mode'] == 'add') {
		$result = $db->query('SELECT 1 FROM '.$db->prefix.'groups WHERE g_title=\''.$db->escape($title).'\'') or error('Unable to check group title collision', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result)) {
			message(sprintf($lang_admin_groups['Title already exists message'], pun_htmlspecialchars($title)));
		}

		$db->query('INSERT INTO '.$db->prefix.'groups (g_title, g_user_title, g_promote_min_posts, g_promote_next_group, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_mod_promote_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_post_links, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood) VALUES(\''.$db->escape($title).'\', '.$user_title.', '.$promote_min_posts.', '.$promote_next_group.', '.$moderator.', '.$mod_edit_users.', '.$mod_rename_users.', '.$mod_change_passwords.', '.$mod_ban_users.', '.$mod_promote_users.', '.$read_board.', '.$view_users.', '.$post_replies.', '.$post_topics.', '.$edit_posts.', '.$delete_posts.', '.$delete_topics.', '.$post_links.', '.$set_title.', '.$search.', '.$search_users.', '.$send_email.', '.$post_flood.', '.$search_flood.', '.$email_flood.', '.$report_flood.')') or error('Unable to add group', __FILE__, __LINE__, $db->error());
		$new_group_id = $db->insert_id();

		// Now lets copy the forum specific permissions from the group which this group is based on
		$result = $db->query('SELECT forum_id, read_forum, post_replies, post_topics FROM '.$db->prefix.'forum_perms WHERE group_id='.intval($post_data['base_group'])) or error('Unable to fetch group forum permission list', __FILE__, __LINE__, $db->error());
		while ($cur_forum_perm = $db->fetch_assoc($result)) {
			$db->query('INSERT INTO '.$db->prefix.'forum_perms (group_id, forum_id, read_forum, post_replies, post_topics) VALUES('.$new_group_id.', '.$cur_forum_perm['forum_id'].', '.$cur_forum_perm['read_forum'].', '.$cur_forum_perm['post_replies'].', '.$cur_forum_perm['post_topics'].')') or error('Unable to insert group forum permissions', __FILE__, __LINE__, $db->error());
		}
	}
	else {
		$result = $db->query('SELECT 1 FROM '.$db->prefix.'groups WHERE g_title=\''.$db->escape($title).'\' AND g_id!='.intval($post_data['group_id'])) or error('Unable to check group title collision', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result)) {
			message(sprintf($lang_admin_groups['Title already exists message'], pun_htmlspecialchars($title)));
		}

		$db->query('UPDATE '.$db->prefix.'groups SET g_title=\''.$db->escape($title).'\', g_user_title='.$user_title.', g_promote_min_posts='.$promote_min_posts.', g_promote_next_group='.$promote_next_group.', g_moderator='.$moderator.', g_mod_edit_users='.$mod_edit_users.', g_mod_rename_users='.$mod_rename_users.', g_mod_change_passwords='.$mod_change_passwords.', g_mod_ban_users='.$mod_ban_users.', g_mod_promote_users='.$mod_promote_users.', g_read_board='.$read_board.', g_view_users='.$view_users.', g_post_replies='.$post_replies.', g_post_topics='.$post_topics.', g_edit_posts='.$edit_posts.', g_delete_posts='.$delete_posts.', g_delete_topics='.$delete_topics.', g_post_links='.$post_links.', g_set_title='.$set_title.', g_search='.$search.', g_search_users='.$search_users.', g_send_email='.$send_email.', g_post_flood='.$post_flood.', g_search_flood='.$search_flood.', g_email_flood='.$email_flood.', g_report_flood='.$report_flood.' WHERE g_id='.intval($post_data['group_id'])) or error('Unable to update group', __FILE__, __LINE__, $db->error());

		// Promote all users who would be promoted to this group on their next post
		if ($promote_next_group) {
			$db->query('UPDATE '.$db->prefix.'users SET group_id = '.$promote_next_group.' WHERE group_id = '.intval($post_data['group_id']).' AND num_posts >= '.$promote_min_posts) or error('Unable to auto-promote existing users', __FILE__, __LINE__, $db->error());
		}
	}

	// Regenerate the quick jump cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
		require PUN_ROOT.'include/cache.php';
	}

	$group_id = $post_data['mode'] == 'add' ? $new_group_id : intval($post_data['group_id']);
	generate_quickjump_cache($group_id);

	if ($post_data['mode'] == 'edit') {
		redirect('admin_groups.php', $lang_admin_groups['Group edited redirect']);
	}
	else {
		redirect('admin_groups.php', $lang_admin_groups['Group added redirect']);
	}
}

function set_default_group($groups, $post_data)
{
	global $db, $lang_admin_groups;
	
	confirm_referrer('admin_groups.php');

	$group_id = intval($_POST['default_group']);

	// Make sure it's not the admin or guest groups
	if ($group_id == PUN_ADMIN || $group_id == PUN_GUEST) {
		message($lang_common['Bad request'], false, '404 Not Found');
	}

	// Make sure it's not a moderator group
	if ($groups[$group_id]['g_moderator'] != 0) {
		message($lang_common['Bad request'], false, '404 Not Found');
	}

	$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$group_id.' WHERE conf_name=\'o_default_user_group\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());

	// Regenerate the config cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
		require PUN_ROOT.'include/cache.php';
	}

	generate_config_cache();

	redirect('admin_groups.php', $lang_admin_groups['Default group redirect']);
}

function check_members($group_id)
{
	global $db;
	
	$result = $db->query('SELECT g.g_title, COUNT(u.id) FROM '.$db->prefix.'groups AS g INNER JOIN '.$db->prefix.'users AS u ON g.g_id=u.group_id WHERE g.g_id='.$group_id.' GROUP BY g.g_id, g_title') or error('Unable to fetch group info', __FILE__, __LINE__, $db->error());
	
	if ($db->num_rows($result)) {
		$is_member = true;
	}
	else {
		$is_member = false;
	}
	
	return $is_member;
}

function delete_group($post_data, $group_id)
{
	global $db, $lang_admin_groups;
	
	if (isset($post_data['del_group'])) {
		$move_to_group = intval($post_data['move_to_group']);
		$db->query('UPDATE '.$db->prefix.'users SET group_id='.$move_to_group.' WHERE group_id='.$group_id) or error('Unable to move users into group', __FILE__, __LINE__, $db->error());
	}

	// Delete the group and any forum specific permissions
	$db->query('DELETE FROM '.$db->prefix.'groups WHERE g_id='.$group_id) or error('Unable to delete group', __FILE__, __LINE__, $db->error());
	$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE group_id='.$group_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());

	// Don't let users be promoted to this group
	$db->query('UPDATE '.$db->prefix.'groups SET g_promote_next_group=0 WHERE g_promote_next_group='.$group_id) or error('Unable to remove group as promotion target', __FILE__, __LINE__, $db->error());

	redirect('admin_groups.php', $lang_admin_groups['Group removed redirect']);
}

function get_group_title($group_id)
{
	global $db;
	
	$result = $db->query('SELECT g_title FROM '.$db->prefix.'groups WHERE g_id='.$group_id) or error('Unable to fetch group title', __FILE__, __LINE__, $db->error());
	$group_title = $db->result($result);
	
	return $group_title;
}

function get_title_members($group_id)
{
	global $db;
	
	$result = $db->query('SELECT g.g_title, COUNT(u.id) FROM '.$db->prefix.'groups AS g INNER JOIN '.$db->prefix.'users AS u ON g.g_id=u.group_id WHERE g.g_id='.$group_id.' GROUP BY g.g_id, g_title') or error('Unable to fetch group info', __FILE__, __LINE__, $db->error());

	list($group_info['title'], $group_info['members']) = $db->fetch_row($result);
	
	return $group_info;
}