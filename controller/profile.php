<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Profile{
		
        function display($id, $section = null){
			
			global $feather, $lang_common, $lang_prof_reg, $lang_profile, $pun_config, $pun_user, $pun_start, $db, $pd, $forum_time_formats, $forum_date_formats;
			
			// Include UTF-8 function
			require PUN_ROOT.'include/utf8/substr_replace.php';
			require PUN_ROOT.'include/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
			require PUN_ROOT.'include/utf8/strcasecmp.php';
						
			// Load the prof_reg.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';

			// Load the profile.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

			// Load the profile.php model file
			require PUN_ROOT.'model/profile.php';
			
if (!empty($feather->request->post('update_group_membership'))) {
				if ($pun_user['g_id'] > PUN_ADMIN) {
					message($lang_common['No permission'], false, '403 Forbidden');
				}

				update_group_membership($id, $feather);
			} elseif (!empty($feather->request->post('update_forums'))) {
				if ($pun_user['g_id'] > PUN_ADMIN) {
					message($lang_common['No permission'], false, '403 Forbidden');
				}

				update_mod_forums($id, $feather);
			} elseif (!empty($feather->request->post('ban'))) {
				if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0')) {
					message($lang_common['No permission'], false, '403 Forbidden');
				}

				ban_user($id);
			} elseif (!empty($feather->request->post('delete_user')) || !empty($feather->request->post('delete_user_comply'))) {
				if ($pun_user['g_id'] > PUN_ADMIN) {
					message($lang_common['No permission'], false, '403 Forbidden');
				}
				
				delete_user($id, $feather);

				$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Confirm delete user']);
				if (!defined('PUN_ACTIVE_PAGE')) {
					define('PUN_ACTIVE_PAGE', 'profile');
				}
				require PUN_ROOT.'header.php';
				
				$feather->render('header.php', array(
					'lang_common' => $lang_common,
					'page_title' => $page_title,
					'p' => $p,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'_SERVER'	=>	$_SERVER,
					'page_head'		=>	'',
					'navlinks'		=>	$navlinks,
					'page_info'		=>	$page_info,
					'db'		=>	$db,
					)
				);
				
				$feather->render('profile/delete_user.php', array(
					'lang_common' => $lang_common,
					'username' => $username,
					'lang_profile' => $lang_profile,
					'id' => $id,
					)
				);

				$feather->render('footer.php', array(
					'lang_common' => $lang_common,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'pun_start' => $pun_start,
					'footer_style' => 'profile',
					)
				);
				
			} elseif (!empty($feather->request->post('form_sent'))) {

				// Fetch the user group of the user we are editing
				$info = fetch_user_group($id);

				if ($pun_user['id'] != $id &&                                                                    // If we aren't the user (i.e. editing your own profile)
					(!$pun_user['is_admmod'] ||                                                                    // and we are not an admin or mod
					($pun_user['g_id'] != PUN_ADMIN &&                                                            // or we aren't an admin and ...
					($pun_user['g_mod_edit_users'] == '0' ||                                                    // mods aren't allowed to edit users
					$info['group_id'] == PUN_ADMIN ||                                                                    // or the user is an admin
					$info['is_moderator'])))) {                                                                            // or the user is another mod
					message($lang_common['No permission'], false, '403 Forbidden');
				}

				update_profile($id, $info, $section, $feather);
			}
			
			$user = get_user_info($id);

			$last_post = format_time($user['last_post']);

			if ($user['signature'] != '') {
				require PUN_ROOT.'include/parser.php';
				$parsed_signature = parse_signature($user['signature']);
			}

			// View or edit?
			if ($pun_user['id'] != $id &&                                                                    // If we aren't the user (i.e. editing your own profile)
				(!$pun_user['is_admmod'] ||                                                                    // and we are not an admin or mod
				($pun_user['g_id'] != PUN_ADMIN &&                                                            // or we aren't an admin and ...
				($pun_user['g_mod_edit_users'] == '0' ||                                                    // mods aren't allowed to edit users
				$user['g_id'] == PUN_ADMIN ||                                                                // or the user is an admin
				$user['g_moderator'] == '1')))) {
				// or the user is another mod
				$user_info = parse_user_info($user);

				$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), sprintf($lang_profile['Users profile'], pun_htmlspecialchars($user['username'])));
				define('PUN_ALLOW_INDEX', 1);
				if (!defined('PUN_ACTIVE_PAGE')) {
					define('PUN_ACTIVE_PAGE', 'profile');
				}
				require PUN_ROOT.'header.php';
				
				$feather->render('header.php', array(
					'lang_common' => $lang_common,
					'page_title' => $page_title,
					'p' => $p,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'_SERVER'	=>	$_SERVER,
					'page_head'		=>	'',
					'navlinks'		=>	$navlinks,
					'page_info'		=>	$page_info,
					'db'		=>	$db,
					)
				);

				$feather->render('profile/view_profile.php', array(
					'lang_common' => $lang_common,
					'lang_profile' => $lang_profile,
					'user_info' => $user_info,
					)
				);
				
				$feather->render('footer.php', array(
					'lang_common' => $lang_common,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'pun_start' => $pun_start,
					'footer_style' => 'profile',
					)
				);

				require PUN_ROOT.'footer.php';
			} else {
				if (!$section || $section == 'essentials') {
					$user_disp = edit_essentials($id, $user);

					$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section essentials']);
					$required_fields = array('req_username' => $lang_common['Username'], 'req_email' => $lang_common['Email']);
					if (!defined('PUN_ACTIVE_PAGE')) {
						define('PUN_ACTIVE_PAGE', 'profile');
					}
					require PUN_ROOT.'header.php';
					
					$feather->render('header.php', array(
						'lang_common' => $lang_common,
						'page_title' => $page_title,
						'p' => $p,
						'pun_user' => $pun_user,
						'pun_config' => $pun_config,
						'_SERVER'	=>	$_SERVER,
						'page_head'		=>	'',
						'navlinks'		=>	$navlinks,
						'page_info'		=>	$page_info,
						'db'		=>	$db,
						)
					);

					generate_profile_menu('essentials', $id);
					
					$feather->render('profile/section_essentials.php', array(
						'lang_common' => $lang_common,
						'lang_profile' => $lang_profile,
						'lang_prof_reg' => $lang_prof_reg,
						'pun_user' => $pun_user,
						'id' => $id,
						'user' => $user,
						'user_disp' => $user_disp,
						'forum_time_formats' => $forum_time_formats,
						'forum_date_formats' => $forum_date_formats,
						)
					);
				
					
				} elseif ($section == 'personal') {
					if ($pun_user['g_set_title'] == '1') {
						$title_field = '<label>'.$lang_common['Title'].' <em>('.$lang_profile['Leave blank'].')</em><br /><input type="text" name="title" value="'.pun_htmlspecialchars($user['title']).'" size="30" maxlength="50" /><br /></label>'."\n";
					}

					$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section personal']);
					if (!defined('PUN_ACTIVE_PAGE')) {
						define('PUN_ACTIVE_PAGE', 'profile');
					}
					require PUN_ROOT.'header.php';
					
					$feather->render('header.php', array(
						'lang_common' => $lang_common,
						'page_title' => $page_title,
						'p' => $p,
						'pun_user' => $pun_user,
						'pun_config' => $pun_config,
						'_SERVER'	=>	$_SERVER,
						'page_head'		=>	'',
						'navlinks'		=>	$navlinks,
						'page_info'		=>	$page_info,
						'db'		=>	$db,
						)
					);

					generate_profile_menu('personal', $id);
					
					$feather->render('profile/section_personal.php', array(
						'lang_common' => $lang_common,
						'lang_profile' => $lang_profile,
						'user' => $user,
						)
					);
				} elseif ($section == 'messaging') {
					$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section messaging']);
					if (!defined('PUN_ACTIVE_PAGE')) {
						define('PUN_ACTIVE_PAGE', 'profile');
					}
					require PUN_ROOT.'header.php';
					
					$feather->render('header.php', array(
						'lang_common' => $lang_common,
						'page_title' => $page_title,
						'p' => $p,
						'pun_user' => $pun_user,
						'pun_config' => $pun_config,
						'_SERVER'	=>	$_SERVER,
						'page_head'		=>	'',
						'navlinks'		=>	$navlinks,
						'page_info'		=>	$page_info,
						'db'		=>	$db,
						)
					);

					generate_profile_menu('messaging', $id);
					
					$feather->render('profile/section_messaging.php', array(
						'lang_common' => $lang_common,
						'lang_profile' => $lang_profile,
						'user' => $user,
						)
					);
				} elseif ($section == 'personality') {
					if ($pun_config['o_avatars'] == '0' && $pun_config['o_signatures'] == '0') {
						message($lang_common['Bad request'], false, '404 Not Found');
					}

					$avatar_field = '<span><a href="'.get_link('user/'.$id.'/action/upload_avatar/').'">'.$lang_profile['Change avatar'].'</a></span>';

					$user_avatar = generate_avatar_markup($id);
					if ($user_avatar) {
						$avatar_field .= ' <span><a href="'.get_link('user/'.$id.'/action/delete_avatar/').'">'.$lang_profile['Delete avatar'].'</a></span>';
					} else {
						$avatar_field = '<span><a href="'.get_link('user/'.$id.'/action/upload_avatar/').'">'.$lang_profile['Upload avatar'].'</a></span>';
					}

					if ($user['signature'] != '') {
						$signature_preview = '<p>'.$lang_profile['Sig preview'].'</p>'."\n\t\t\t\t\t\t\t".'<div class="postsignature postmsg">'."\n\t\t\t\t\t\t\t\t".'<hr />'."\n\t\t\t\t\t\t\t\t".$parsed_signature."\n\t\t\t\t\t\t\t".'</div>'."\n";
					} else {
						$signature_preview = '<p>'.$lang_profile['No sig'].'</p>'."\n";
					}

					$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section personality']);
					if (!defined('PUN_ACTIVE_PAGE')) {
						define('PUN_ACTIVE_PAGE', 'profile');
					}
					require PUN_ROOT.'header.php';
					
					$feather->render('header.php', array(
						'lang_common' => $lang_common,
						'page_title' => $page_title,
						'p' => $p,
						'pun_user' => $pun_user,
						'pun_config' => $pun_config,
						'_SERVER'	=>	$_SERVER,
						'page_head'		=>	'',
						'navlinks'		=>	$navlinks,
						'page_info'		=>	$page_info,
						'db'		=>	$db,
						)
					);

					generate_profile_menu('personality', $id);
					
					$feather->render('profile/section_personality.php', array(
						'lang_common' => $lang_common,
						'lang_profile' => $lang_profile,
						'user_avatar' => $user_avatar,
						'avatar_field' => $avatar_field,
						'signature_preview' => $signature_preview,
						'user' => $user,
						)
					);
				} elseif ($section == 'display') {
					$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section display']);
					if (!defined('PUN_ACTIVE_PAGE')) {
						define('PUN_ACTIVE_PAGE', 'profile');
					}
					require PUN_ROOT.'header.php';
					
					$feather->render('header.php', array(
						'lang_common' => $lang_common,
						'page_title' => $page_title,
						'p' => $p,
						'pun_user' => $pun_user,
						'pun_config' => $pun_config,
						'_SERVER'	=>	$_SERVER,
						'page_head'		=>	'',
						'navlinks'		=>	$navlinks,
						'page_info'		=>	$page_info,
						'db'		=>	$db,
						)
					);

					generate_profile_menu('display', $id);

					$feather->render('profile/section_display.php', array(
						'lang_common' => $lang_common,
						'lang_profile' => $lang_profile,
						'user' => $user,
						)
					);
				} elseif ($section == 'privacy') {
					$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section privacy']);
					if (!defined('PUN_ACTIVE_PAGE')) {
						define('PUN_ACTIVE_PAGE', 'profile');
					}
					require PUN_ROOT.'header.php';
					
					$feather->render('header.php', array(
						'lang_common' => $lang_common,
						'page_title' => $page_title,
						'p' => $p,
						'pun_user' => $pun_user,
						'pun_config' => $pun_config,
						'_SERVER'	=>	$_SERVER,
						'page_head'		=>	'',
						'navlinks'		=>	$navlinks,
						'page_info'		=>	$page_info,
						'db'		=>	$db,
						)
					);

					generate_profile_menu('privacy', $id);
					
					$feather->render('profile/section_privacy.php', array(
						'lang_common' => $lang_common,
						'lang_profile' => $lang_profile,
						'lang_prof_reg' => $lang_prof_reg,
						'user' => $user,
						)
					);
				} elseif ($section == 'admin') {
					if (!$pun_user['is_admmod'] || ($pun_user['g_moderator'] == '1' && $pun_user['g_mod_ban_users'] == '0')) {
						message($lang_common['Bad request'], false, '403 Forbidden');
					}

					$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section admin']);

					if (!defined('PUN_ACTIVE_PAGE')) {
						define('PUN_ACTIVE_PAGE', 'profile');
					}
					require PUN_ROOT.'header.php';
					
					$feather->render('header.php', array(
						'lang_common' => $lang_common,
						'page_title' => $page_title,
						'p' => $p,
						'pun_user' => $pun_user,
						'pun_config' => $pun_config,
						'_SERVER'	=>	$_SERVER,
						'page_head'		=>	'',
						'navlinks'		=>	$navlinks,
						'page_info'		=>	$page_info,
						'db'		=>	$db,
						)
					);

					generate_profile_menu('admin', $id);
					
					$feather->render('profile/section_admin.php', array(
						'lang_common' => $lang_common,
						'lang_profile' => $lang_profile,
						'user' => $user,
						)
					);
				} else {
					message($lang_common['Bad request'], false, '404 Not Found');
				}
				
				$feather->render('footer.php', array(
					'lang_common' => $lang_common,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'pun_start' => $pun_start,
					'footer_style' => 'profile',
					)
				);

				require PUN_ROOT.'footer.php';
			}

		}
		
		function action($id, $action) {
			
			global $feather, $lang_common, $lang_prof_reg, $lang_profile, $pun_config, $pun_user, $pun_start, $db;
			
			// Include UTF-8 function
			require PUN_ROOT.'include/utf8/substr_replace.php';
			require PUN_ROOT.'include/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
			require PUN_ROOT.'include/utf8/strcasecmp.php';
						
			// Load the prof_reg.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';

			// Load the profile.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

			// Load the profile.php model file
			require PUN_ROOT.'model/profile.php';
			
			if ($action != 'change_pass' || empty($feather->request->get('key'))) {
				if ($pun_user['g_read_board'] == '0') {
					message($lang_common['No view'], false, '403 Forbidden');
				} elseif ($pun_user['g_view_users'] == '0' && ($pun_user['is_guest'] || $pun_user['id'] != $id)) {
					message($lang_common['No permission'], false, '403 Forbidden');
				}
			}
			
			if ($action == 'change_pass') {
				change_pass($id, $feather);

				$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Change pass']);
				$required_fields = array('req_old_password' => $lang_profile['Old pass'], 'req_new_password1' => $lang_profile['New pass'], 'req_new_password2' => $lang_profile['Confirm new pass']);
				$focus_element = array('change_pass', ((!$pun_user['is_admmod']) ? 'req_old_password' : 'req_new_password1'));
				if (!defined('PUN_ACTIVE_PAGE')) {
					define('PUN_ACTIVE_PAGE', 'profile');
				}
				require PUN_ROOT.'header.php';
				
				$feather->render('header.php', array(
					'lang_common' => $lang_common,
					'page_title' => $page_title,
					'p' => $p,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'_SERVER'	=>	$_SERVER,
					'page_head'		=>	'',
					'navlinks'		=>	$navlinks,
					'page_info'		=>	$page_info,
					'db'		=>	$db,
					)
				);
				
				$feather->render('profile/change_pass.php', array(
					'lang_common' => $lang_common,
					'pun_user' => $pun_user,
					'lang_profile' => $lang_profile,
					'id' => $id,
					)
				);

				$feather->render('footer.php', array(
					'lang_common' => $lang_common,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'pun_start' => $pun_start,
					'footer_style' => 'profile',
					)
				);

				require PUN_ROOT.'footer.php';
			} elseif ($action == 'change_email') {
				change_email($id, $feather);

				$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Change email']);
				$required_fields = array('req_new_email' => $lang_profile['New email'], 'req_password' => $lang_common['Password']);
				$focus_element = array('change_email', 'req_new_email');
				if (!defined('PUN_ACTIVE_PAGE')) {
					define('PUN_ACTIVE_PAGE', 'profile');
				}
				require PUN_ROOT.'header.php';
				
				$feather->render('header.php', array(
					'lang_common' => $lang_common,
					'page_title' => $page_title,
					'p' => $p,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'_SERVER'	=>	$_SERVER,
					'page_head'		=>	'',
					'navlinks'		=>	$navlinks,
					'page_info'		=>	$page_info,
					'db'		=>	$db,
					)
				);
				
				$feather->render('profile/change_mail.php', array(
					'lang_common' => $lang_common,
					'lang_profile' => $lang_profile,
					'id' => $id,
					)
				);
				
				$feather->render('footer.php', array(
					'lang_common' => $lang_common,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'pun_start' => $pun_start,
					'footer_style' => 'profile',
					)
				);

				require PUN_ROOT.'footer.php';
			} elseif ($action == 'upload_avatar' || $action == 'upload_avatar2') {
				if ($pun_config['o_avatars'] == '0') {
					message($lang_profile['Avatars disabled']);
				}

				if ($pun_user['id'] != $id && !$pun_user['is_admmod']) {
					message($lang_common['No permission'], false, '403 Forbidden');
				}

				if ($feather->request()->isPost()) {
					upload_avatar($id, $_FILES);
				}

				$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Upload avatar']);
				$required_fields = array('req_file' => $lang_profile['File']);
				$focus_element = array('upload_avatar', 'req_file');
				if (!defined('PUN_ACTIVE_PAGE')) {
					define('PUN_ACTIVE_PAGE', 'profile');
				}
				require PUN_ROOT.'header.php';
				
				$feather->render('header.php', array(
					'lang_common' => $lang_common,
					'page_title' => $page_title,
					'p' => $p,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'_SERVER'	=>	$_SERVER,
					'page_head'		=>	'',
					'navlinks'		=>	$navlinks,
					'page_info'		=>	$page_info,
					'db'		=>	$db,
					)
				);
				
				$feather->render('profile/upload_avatar.php', array(
					'lang_common' => $lang_common,
					'lang_profile' => $lang_profile,
					'pun_config' => $pun_config,
					'lang_profile' => $lang_profile,
					'id' => $id,
					)
				);

				$feather->render('footer.php', array(
					'lang_common' => $lang_common,
					'pun_user' => $pun_user,
					'pun_config' => $pun_config,
					'pun_start' => $pun_start,
					'footer_style' => 'profile',
					)
				);

				require PUN_ROOT.'footer.php';
			} elseif ($action == 'delete_avatar') {
				if ($pun_user['id'] != $id && !$pun_user['is_admmod']) {
					message($lang_common['No permission'], false, '403 Forbidden');
				}

				confirm_referrer(get_link_r('user/'.$id.'/section/personality/'));

				delete_avatar($id);

				redirect(get_link('user/'.$id.'/section/personality/'), $lang_profile['Avatar deleted redirect']);
			} elseif ($action == 'promote') {
				if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_promote_users'] == '0')) {
					message($lang_common['No permission'], false, '403 Forbidden');
				}

				promote_user($id, $feather);
			}
			else {
				message($lang_common['Bad request'], false, '404 Not Found');
			}
		}
	}
}