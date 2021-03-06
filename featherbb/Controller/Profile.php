<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Delete;

class Profile
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Profile();
        translate('profile');
        translate('register');
        translate('prof_reg');
        translate('misc');
    }

    public function display($req, $res, $args)
    {
        // Include UTF-8 function
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/substr_replace.php';
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/strcasecmp.php';

        $args['id'] = Container::get('hooks')->fire('controller.profile.display', $args['id']);

        if (Input::post('update_group_membership')) {
            if (User::get()->g_id > ForumEnv::get('FEATHER_ADMIN')) {
                throw new Error(__('No permission'), 403);
            }

            return $this->model->update_group_membership($args['id']);
        } elseif (Input::post('update_forums')) {
            if (User::get()->g_id > ForumEnv::get('FEATHER_ADMIN')) {
                throw new Error(__('No permission'), 403);
            }

            return $this->model->update_mod_forums($args['id']);
        } elseif (Input::post('ban')) {
            if (User::get()->g_id != ForumEnv::get('FEATHER_ADMIN') && (User::get()->g_moderator != '1' || User::get()->g_mod_ban_users == '0')) {
                throw new Error(__('No permission'), 403);
            }

            return $this->model->ban_user($args['id']);
        } elseif (Input::post('delete_user') || Input::post('delete_user_comply')) {
            if (User::get()->g_id > ForumEnv::get('FEATHER_ADMIN')) {
                throw new Error(__('No permission'), 403);
            }

            if (Input::post('delete_user_comply')) {
                return $this->model->delete_user($args['id']);
            } else {
                View::setPageInfo(array(
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Confirm delete user')),
                    'active_page' => 'profile',
                    'username' => $this->model->get_username($args['id']),
                    'id' => $args['id'],
                ))->addTemplate('profile/delete_user.php')->display();
            }
        } elseif (Input::post('form_sent')) {

            // Fetch the user group of the user we are editing
            $info = $this->model->fetch_user_group($args['id']);

            if (User::get()->id != $args['id'] &&                                                            // If we aren't the user (i.e. editing your own profile)
                                    (!User::get()->is_admmod ||                                      // and we are not an admin or mod
                                    (User::get()->g_id != ForumEnv::get('FEATHER_ADMIN') &&                           // or we aren't an admin and ...
                                    (User::get()->g_mod_edit_users == '0' ||                         // mods aren't allowed to edit users
                                    $info['group_id'] == ForumEnv::get('FEATHER_ADMIN') ||                            // or the user is an admin
                                    $info['is_moderator'])))) {                                      // or the user is another mod
                                    throw new Error(__('No permission'), 403);
            }

            return $this->model->update_profile($args['id'], $info, $args['section']);
        }

        $user = $this->model->get_user_info($args['id']);

        if ($user['signature'] != '') {
            $parsed_signature = Container::get('parser')->parse_signature($user['signature']);
        }

        // View or edit?
        if (User::get()->id != $args['id'] &&                                 // If we aren't the user (i.e. editing your own profile)
                (!User::get()->is_admmod ||                           // and we are not an admin or mod
                (User::get()->g_id != ForumEnv::get('FEATHER_ADMIN') &&                // or we aren't an admin and ...
                (User::get()->g_mod_edit_users == '0' ||              // mods aren't allowed to edit users
                $user['g_id'] == ForumEnv::get('FEATHER_ADMIN') ||                     // or the user is an admin
                $user['g_moderator'] == '1')))) {                     // or the user is another mod
                $user_info = $this->model->parse_user_info($user);

            View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), sprintf(__('Users profile'), Utils::escape($user['username']))),
                'active_page' => 'profile',
                'user_info' => $user_info,
                'id' => $args['id']
            ));

            View::addTemplate('profile/view_profile.php')->display();
        } else {
            if (!isset($args['section']) || $args['section'] == 'essentials') {
                $user_disp = $this->model->edit_essentials($args['id'], $user);

                View::setPageInfo(array(
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section essentials')),
                    'required_fields' => array('req_username' => __('Username'), 'req_email' => __('Email')),
                    'active_page' => 'profile',
                    'id' => $args['id'],
                    'page' => 'essentials',
                    'user' => $user,
                    'user_disp' => $user_disp,
                    'forum_time_formats' => Container::get('forum_time_formats'),
                    'forum_date_formats' => Container::get('forum_date_formats')
                ));

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_essentials.php')->display();

            } elseif ($args['section'] == 'personal') {
                if (User::get()->g_set_title == '1') {
                    $title_field = '<label>'.__('Title').' <em>('.__('Leave blank').')</em><br /><input type="text" name="title" value="'.Utils::escape($user['title']).'" size="30" maxlength="50" /><br /></label>'."\n";
                }

                View::setPageInfo(array(
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section personal')),
                    'active_page' => 'profile',
                    'id' => $args['id'],
                    'page' => 'personal',
                    'user' => $user,
                    'title_field' => $title_field,
                ));

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_personal.php')->display();

            } elseif ($args['section'] == 'messaging') {

                View::setPageInfo(array(
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section messaging')),
                    'active_page' => 'profile',
                    'page' => 'messaging',
                    'user' => $user,
                    'id' => $args['id']
                ));

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_messaging.php')->display();

            } elseif ($args['section'] == 'personality') {
                if (ForumSettings::get('o_avatars') == '0' && ForumSettings::get('o_signatures') == '0') {
                    throw new Error(__('Bad request'), 404);
                }

                $avatar_field = '<span><a href="'.Router::pathFor('profileAction', ['id' => $args['id'], 'action' => 'upload_avatar']).'">'.__('Change avatar').'</a></span>';

                $user_avatar = Utils::generate_avatar_markup($args['id']);
                if ($user_avatar) {
                    $avatar_field .= ' <span><a href="'.Router::pathFor('profileAction', ['id' => $args['id'], 'action' => 'delete_avatar']).'">'.__('Delete avatar').'</a></span>';
                } else {
                    $avatar_field = '<span><a href="'.Router::pathFor('profileAction', ['id' => $args['id'], 'action' => 'upload_avatar']).'">'.__('Upload avatar').'</a></span>';
                }

                if ($user['signature'] != '') {
                    $signature_preview = '<p>'.__('Sig preview').'</p>'."\n\t\t\t\t\t\t\t".'<div class="postsignature postmsg">'."\n\t\t\t\t\t\t\t\t".'<hr />'."\n\t\t\t\t\t\t\t\t".$parsed_signature."\n\t\t\t\t\t\t\t".'</div>'."\n";
                } else {
                    $signature_preview = '<p>'.__('No sig').'</p>'."\n";
                }

                View::setPageInfo(array(
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section personality')),
                    'active_page' => 'profile',
                    'user_avatar' => $user_avatar,
                    'avatar_field' => $avatar_field,
                    'signature_preview' => $signature_preview,
                    'page' => 'personality',
                    'user' => $user,
                    'id' => $args['id'],
                ));

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_personality.php')->display();

            } elseif ($args['section'] == 'display') {

                View::setPageInfo(array(
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section display')),
                    'active_page' => 'profile',
                    'page' => 'display',
                    'user' => $user,
                    'id' => $args['id']
                ));

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_display.php')->display();

            } elseif ($args['section'] == 'privacy') {

                View::setPageInfo(array(
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section privacy')),
                    'active_page' => 'profile',
                    'page' => 'privacy',
                    'user' => $user,
                    'id' => $args['id']
                ));

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_privacy.php')->display();

            } elseif ($args['section'] == 'admin') {

                if (!User::get()->is_admmod || (User::get()->g_moderator == '1' && User::get()->g_mod_ban_users == '0')) {
                    throw new Error(__('Bad request'), 404);
                }

                View::setPageInfo(array(
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section admin')),
                    'active_page' => 'profile',
                    'page' => 'admin',
                    'user' => $user,
                    'forum_list' => $this->model->get_forum_list($args['id']),
                    'group_list' => $this->model->get_group_list($user),
                    'id' => $args['id']
                ));

                return View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_admin.php')->display();
            } else {
                throw new Error(__('Bad request'), 404);
            }
        }
    }

    public function action($req, $res, $args)
    {
        // Include UTF-8 function
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/substr_replace.php';
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/strcasecmp.php';

        $args['id'] = Container::get('hooks')->fire('controller.profile.action', $args['id']);

        if ($args['action'] != 'change_pass' || !Input::query('key')) {
            if (User::get()->g_read_board == '0') {
                throw new Error(__('No view'), 403);
            } elseif (User::get()->g_view_users == '0' && (User::get()->is_guest || User::get()->id != $args['id'])) {
                throw new Error(__('No permission'), 403);
            }
        }

        if ($args['action'] == 'change_pass') {
            if (Request::isPost()) {
                // TODO: Check if security "if (User::get()->id != $id)" (l.58 of Model/Profile) isn't bypassed
                // FOR ALL chained if below
                return $this->model->change_pass($args['id']);
            }

            View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Change pass')),
                'active_page' => 'profile',
                'id' => $args['id'],
                'required_fields' => array('req_old_password' => __('Old pass'), 'req_new_password1' => __('New pass'), 'req_new_password2' => __('Confirm new pass')),
                'focus_element' => array('change_pass', ((!User::get()->is_admmod) ? 'req_old_password' : 'req_new_password1')),
            ));

            View::addTemplate('profile/change_pass.php')->display();

        } elseif ($args['action'] == 'change_email') {
            if (Request::isPost()) {
                return $this->model->change_email($args['id']);
            }

            View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Change email')),
                'active_page' => 'profile',
                'required_fields' => array('req_new_email' => __('New email'), 'req_password' => __('Password')),
                'focus_element' => array('change_email', 'req_new_email'),
                'id' => $args['id'],
            ));

            View::addTemplate('profile/change_mail.php')->display();

        } elseif ($args['action'] == 'upload_avatar' || $args['action'] == 'upload_avatar2') {
            if (ForumSettings::get('o_avatars') == '0') {
                throw new Error(__('Avatars disabled'), 400);
            }

            if (User::get()->id != $args['id'] && !User::get()->is_admmod) {
                throw new Error(__('No permission'), 403);
            }

            if (Request::isPost()) {
                return $this->model->upload_avatar($args['id'], $_FILES);
            }

            View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Upload avatar')),
                'active_page' => 'profile',
                'required_fields' =>  array('req_file' => __('File')),
                'focus_element' => array('upload_avatar', 'req_file'),
                'id' => $args['id'],
            ));

            View::addTemplate('profile/upload_avatar.php')->display();

        } elseif ($args['action'] == 'delete_avatar') {
            if (User::get()->id != $args['id'] && !User::get()->is_admmod) {
                throw new Error(__('No permission'), 403);
            }

            $this->model->delete_avatar($args['id']);

            return Router::redirect(Router::pathFor('profileSection', array('id' => $args['id'], 'section' => 'personality')), __('Avatar deleted redirect'));
        } elseif ($args['action'] == 'promote') {
            if (User::get()->g_id != ForumEnv::get('FEATHER_ADMIN') && (User::get()->g_moderator != '1' || User::get()->g_mod_promote_users == '0')) {
                throw new Error(__('No permission'), 403);
            }

            $this->model->promote_user($args['id']);
        } else {
            throw new Error(__('Bad request'), 404);
        }
    }

    public function email($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.profile.email', $args['id']);

        if (User::get()->g_send_email == '0') {
            throw new Error(__('No permission'), 403);
        }

        if ($args['id'] < 2) {
            throw new Error(__('Bad request'), 400);
        }

        $mail = $this->model->get_info_mail($args['id']);

        if ($mail['email_setting'] == 2 && !User::get()->is_admmod) {
            throw new Error(__('Form email disabled'), 403);
        }


        if (Request::isPost()) {
            $this->model->send_email($mail);
        }

        View::setPageInfo(array(
            'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Send email to').' '.Utils::escape($mail['recipient'])),
            'active_page' => 'email',
            'required_fields' => array('req_subject' => __('Email subject'), 'req_message' => __('Email message')),
            'focus_element' => array('email', 'req_subject'),
            'id' => $args['id'],
            'mail' => $mail
        ))->addTemplate('misc/email.php')->display();
    }

    public function gethostip($req, $res, $args)
    {
        $args['ip'] = Container::get('hooks')->fire('controller.profile.gethostip', $args['ip']);

        $this->model->display_ip_info($args['ip']);
    }
}
