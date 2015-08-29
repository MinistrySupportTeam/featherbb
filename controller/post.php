<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class post
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \model\post();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/prof_reg.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/post.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/register.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/antispam.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/bbeditor.mo');
    }

    public function newreply($fid = null, $tid = null, $qid = null)
    {
        $this->newpost('', $fid, $tid);
    }

    public function newpost($fid = null, $tid = null, $qid = null)
    {
        // Antispam feature
        require $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // If $_POST['username'] is filled, we are facing a bot
        if ($this->feather->request->post('username')) {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }

        // Fetch some info about the topic and/or the forum
        $cur_posting = $this->model->get_info_post($tid, $fid);

        $is_subscribed = $tid && $cur_posting['is_subscribed'];

        // Is someone trying to post into a redirect forum?
        if ($cur_posting['redirect_url'] != '') {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
        $is_admmod = ($this->feather->user->g_id == FEATHER_ADMIN || ($this->feather->user->g_moderator == '1' && array_key_exists($this->feather->user->username, $mods_array))) ? true : false;

        // Do we have permission to post?
        if ((($tid && (($cur_posting['post_replies'] == '' && $this->feather->user->g_post_replies == '0') || $cur_posting['post_replies'] == '0')) ||
                ($fid && (($cur_posting['post_topics'] == '' && $this->feather->user->g_post_topics == '0') || $cur_posting['post_topics'] == '0')) ||
                (isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
                !$is_admmod) {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = array();

        $post = '';

        // Did someone just hit "Submit" or "Preview"?
        if ($this->feather->request()->isPost()) {

                // Include $pid and $page if needed for confirm_referrer function called in check_errors_before_post()
                if ($this->feather->request->post('pid')) {
                    $pid = $this->feather->request->post('pid');
                } else {
                    $pid = '';
                }

            if ($this->feather->request->post('page')) {
                $page = $this->feather->request->post('page');
            } else {
                $page = '';
            }

                // Let's see if everything went right
                $errors = $this->model->check_errors_before_post($fid, $tid, $qid, $pid, $page, $errors);

                // Setup some variables before post
                $post = $this->model->setup_variables($errors, $is_admmod);

                // Did everything go according to plan?
                if (empty($errors) && !$this->feather->request->post('preview')) {
                        // If it's a reply
                        if ($tid) {
                            // Insert the reply, get the new_pid
                                $new = $this->model->insert_reply($post, $tid, $cur_posting, $is_subscribed);

                                // Should we send out notifications?
                                if ($this->feather->forum_settings['o_topic_subscriptions'] == '1') {
                                    $this->model->send_notifications_reply($tid, $cur_posting, $new['pid'], $post);
                                }
                        }
                        // If it's a new topic
                        elseif ($fid) {
                            // Insert the topic, get the new_pid
                                $new = $this->model->insert_topic($post, $fid);

                                // Should we send out notifications?
                                if ($this->feather->forum_settings['o_forum_subscriptions'] == '1') {
                                    $this->model->send_notifications_new_topic($post, $cur_posting, $new['tid']);
                                }
                        }

                        // If we previously found out that the email was banned
                        if ($this->feather->user->is_guest && isset($errors['banned_email']) && $this->feather->forum_settings['o_mailing_list'] != '') {
                            $this->model->warn_banned_user($post, $new['pid']);
                        }

                        // If the posting user is logged in, increment his/her post count
                        if (!$this->feather->user->is_guest) {
                            $this->model->increment_post_count($post, $new['tid']);
                        }

                    redirect($this->feather->url->get('post/'.$new['pid'].'/#p'.$new['pid']), __('Post redirect'));
                }
        }

        $quote = '';

        // If a topic ID was specified in the url (it's a reply)
        if ($tid) {
            $action = __('Post a reply');
            $form = '<form id="post" method="post" action="'.$this->feather->url->get('post/reply/'.$tid.'/').'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';

                // If a quote ID was specified in the url
                if (isset($qid)) {
                    $quote = $this->model->get_quote_throw new \FeatherBB\Error($qid, $tid);
                    $form = '<form id="post" method="post" action="'.$this->feather->url->get('post/reply/'.$tid.'/quote/'.$qid.'/').'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';
                }
        }
        // If a forum ID was specified in the url (new topic)
        elseif ($fid) {
            $action = __('Post new topic');
            $form = '<form id="post" method="post" action="'.$this->feather->url->get('post/new-topic/'.$fid.'/').'" onsubmit="return process_form(this)">';
        } else {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        $url_forum = $this->feather->url->url_friendly($cur_posting['forum_name']);

        $is_subscribed = $tid && $cur_posting['is_subscribed'];

        if (isset($cur_posting['subject'])) {
            $url_topic = $this->feather->url->url_friendly($cur_posting['subject']);
        } else {
            $url_topic = '';
        }

        $required_fields = array('req_email' => __('Email'), 'req_subject' => __('Subject'), 'req_message' => __('Message'));
        if ($this->feather->user->is_guest) {
            $required_fields['captcha'] = __('Robot title');
        }

        // Set focus element (new post or new reply to an existing post ?)
        $focus_element[] = 'post';
        if (!$this->feather->user->is_guest) {
            $focus_element[] = ($fid) ? 'req_subject' : 'req_message';
        } else {
            $required_fields['req_username'] = __('Guest name');
            $focus_element[] = 'req_username';
        }

        // Get the current state of checkboxes
        $checkboxes = $this->model->get_checkboxes($fid, $is_admmod, $is_subscribed);

        // Check to see if the topic review is to be displayed
        if ($tid && $this->feather->forum_settings['o_topic_review'] != '0') {
            $post_data = $this->model->topic_review($tid);
        } else {
            $post_data = '';
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

        $this->feather->view2->setPageInfo(array(
                            'title' => array($this->feather->utils->escape($this->feather->forum_settings['o_board_title']), $action),
                            'required_fields' => $required_fields,
                            'focus_element' => $focus_element,
                            'active_page' => 'post',
                            'post' => $post,
                            'tid' => $tid,
                            'fid' => $fid,
                            'cur_posting' => $cur_posting,
                            'lang_antispam' => $lang_antispam,
                            'lang_antispam_questions' => $lang_antispam_questions,
                            'lang_bbeditor'    =>    $lang_bbeditor,
                            'index_questions' => $index_questions,
                            'checkboxes' => $checkboxes,
                            'action' => $action,
                            'form' => $form,
                            'post_data' => $post_data,
                            'url_forum' => $url_forum,
                            'url_topic' => $url_topic,
                            'quote' => $quote,
                            'errors'    =>    $errors,
                            ))->addTemplate('post.php')->display();
    }
}
