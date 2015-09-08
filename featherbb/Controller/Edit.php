<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Edit
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\edit();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/register.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/prof_reg.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/post.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/bbeditor.mo');
    }

    public function editpost($id)
    {
        // Fetch some informations about the post, the topic and the forum
        $cur_post = $this->model->get_info_edit($id);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = ($this->user->g_id == $this->feather->forum_env['FEATHER_ADMIN'] || ($this->user->g_moderator == '1' && array_key_exists($this->user->username, $mods_array))) ? true : false;

        $can_edit_subject = $id == $cur_post['first_post_id'];

        if ($this->config['o_censoring'] == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
            $cur_post['message'] = Utils::censor($cur_post['message']);
        }

        // Do we have permission to edit this post?
        if (($this->user->g_edit_posts == '0' || $cur_post['poster_id'] != $this->user->id || $cur_post['closed'] == '1') && !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        if ($is_admmod && $this->user->g_id != $this->feather->forum_env['FEATHER_ADMIN'] && in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
            throw new Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = array();

        if ($this->feather->request()->isPost()) {
            // Let's see if everything went right
            $errors = $this->model->check_errors_before_edit($can_edit_subject, $errors);

            // Setup some variables before post
            $post = $this->model->setup_variables($cur_post, $is_admmod, $can_edit_subject, $errors);

            // Did everything go according to plan?
            if (empty($errors) && !$this->request->post('preview')) {
                // Edit the post
                $this->model->edit_post($id, $can_edit_subject, $post, $cur_post, $is_admmod);

                Url::redirect($this->feather->urlFor('viewPost', ['pid' => $id]).'#p'.$id, __('Post redirect'));
            }
        } else {
            $post = '';
        }

        if ($this->request->post('preview')) {
            $preview_message = $this->feather->parser->parse_message($post['message'], $post['hide_smilies']);
        } else {
            $preview_message = '';
        }

        $lang_bbeditor = array(
            'btnBold' => __('btnBold'),
            'btnItalic' => __('btnItalic'),
            'btnUnderline' => __('btnUnderline'),
            'btnColor' => __('btnColor'),
            'btnLeft' => __('btnLeft'),
            'btnRight' => __('btnRight'),
            'btnJustify' => __('btnJustify'),
            'btnCenter' => __('btnCenter'),
            'btnLink' => __('btnLink'),
            'btnPicture' => __('btnPicture'),
            'btnList' => __('btnList'),
            'btnQuote' => __('btnQuote'),
            'btnCode' => __('btnCode'),
            'promptImage' => __('promptImage'),
            'promptUrl' => __('promptUrl'),
            'promptQuote' => __('promptQuote')
        );

        $this->feather->template->setPageInfo(array(
                            'title' => array(Utils::escape($this->config['o_board_title']), __('Edit post')),
                            'required_fields' => array('req_subject' => __('Subject'), 'req_message' => __('Message')),
                            'focus_element' => array('edit', 'req_message'),
                            'cur_post' => $cur_post,
                            'errors' => $errors,
                            'preview_message' => $preview_message,
                            'id' => $id,
                            'checkboxes' => $this->model->get_checkboxes($can_edit_subject, $is_admmod, $cur_post, 1),
                            'can_edit_subject' => $can_edit_subject,
                            'lang_bbeditor'    =>    $lang_bbeditor,
                            'post' => $post,
                            )
                    )->addTemplate('edit.php')->display();
    }
}
