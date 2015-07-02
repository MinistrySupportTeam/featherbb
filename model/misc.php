<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function update_last_visit()
{
    global $db, $feather_user;
    
    $db->query('UPDATE '.$db->prefix.'users SET last_visit='.$feather_user['logged'].' WHERE id='.$feather_user['id']) or error('Unable to update user last visit data', __FILE__, __LINE__, $db->error());
}

function get_info_mail($recipient_id)
{
    global $db, $lang_common;
    
    $mail = array();
    
    $result = $db->query('SELECT username, email, email_setting FROM '.$db->prefix.'users WHERE id='.$recipient_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    list($mail['recipient'], $mail['recipient_email'], $mail['email_setting']) = $db->fetch_row($result);
    
    return $mail;
}

function send_email($feather, $mail, $id)
{
    global $db, $lang_misc, $feather_user, $feather_config;
    
    confirm_referrer(get_link_r('email/'.$id.'/'));

    // Clean up message and subject from POST
    $subject = pun_trim($feather->request->post('req_subject'));
    $message = pun_trim($feather->request->post('req_message'));

    if ($subject == '') {
        message($lang_misc['No email subject']);
    } elseif ($message == '') {
        message($lang_misc['No email message']);
    }
    // Here we use strlen() not pun_strlen() as we want to limit the post to PUN_MAX_POSTSIZE bytes, not characters
    elseif (strlen($message) > PUN_MAX_POSTSIZE) {
        message($lang_misc['Too long email message']);
    }

    if ($feather_user['last_email_sent'] != '' && (time() - $feather_user['last_email_sent']) < $feather_user['g_email_flood'] && (time() - $feather_user['last_email_sent']) >= 0) {
        message(sprintf($lang_misc['Email flood'], $feather_user['g_email_flood'], $feather_user['g_email_flood'] - (time() - $feather_user['last_email_sent'])));
    }

    // Load the "form email" template
    $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$feather_user['language'].'/mail_templates/form_email.tpl'));

    // The first row contains the subject
    $first_crlf = strpos($mail_tpl, "\n");
    $mail_subject = pun_trim(substr($mail_tpl, 8, $first_crlf-8));
    $mail_message = pun_trim(substr($mail_tpl, $first_crlf));

    $mail_subject = str_replace('<mail_subject>', $subject, $mail_subject);
    $mail_message = str_replace('<sender>', $feather_user['username'], $mail_message);
    $mail_message = str_replace('<board_title>', $feather_config['o_board_title'], $mail_message);
    $mail_message = str_replace('<mail_message>', $message, $mail_message);
    $mail_message = str_replace('<board_mailer>', $feather_config['o_board_title'], $mail_message);

    require_once FEATHER_ROOT.'include/email.php';

    pun_mail($mail['recipient_email'], $mail_subject, $mail_message, $feather_user['email'], $feather_user['username']);

    $db->query('UPDATE '.$db->prefix.'users SET last_email_sent='.time().' WHERE id='.$feather_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());

    // Try to determine if the data in redirect_url is valid (if not, we redirect to index.php after the email is sent)
    $redirect_url = validate_redirect($feather->request->post('redirect_url'), 'index.php');

    redirect(get_base_url(), $lang_misc['Email sent redirect']);
}

function get_redirect_url($feather, $recipient_id)
{
    // Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to the user's profile after the email is sent)
    if ($feather->request->getReferrer()) {
        $redirect_url = validate_redirect($feather->request->getReferrer(), null);
    }

    if (!isset($redirect_url)) {
        $redirect_url = get_link('user/'.$recipient_id.'/');
    } elseif (preg_match('%viewtopic\.php\?pid=(\d+)$%', $redirect_url, $matches)) {
        $redirect_url .= '#p'.$matches[1];
    }
    
    return $redirect_url;
}

function insert_report($feather, $post_id)
{
    global $db, $lang_misc, $feather_user, $lang_common, $feather_config;
    
    // Make sure they got here from the site
    confirm_referrer(get_link_r('report/'.$post_id.'/'));
    
    // Clean up reason from POST
    $reason = pun_linebreaks(pun_trim($feather->request->post('req_reason')));
    if ($reason == '') {
        message($lang_misc['No reason']);
    } elseif (strlen($reason) > 65535) { // TEXT field can only hold 65535 bytes
        message($lang_misc['Reason too long']);
    }

    if ($feather_user['last_report_sent'] != '' && (time() - $feather_user['last_report_sent']) < $feather_user['g_report_flood'] && (time() - $feather_user['last_report_sent']) >= 0) {
        message(sprintf($lang_misc['Report flood'], $feather_user['g_report_flood'], $feather_user['g_report_flood'] - (time() - $feather_user['last_report_sent'])));
    }

    // Get the topic ID
    $result = $db->query('SELECT topic_id FROM '.$db->prefix.'posts WHERE id='.$post_id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $topic_id = $db->result($result);

    // Get the subject and forum ID
    $result = $db->query('SELECT subject, forum_id FROM '.$db->prefix.'topics WHERE id='.$topic_id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    list($subject, $forum_id) = $db->fetch_row($result);

    // Should we use the internal report handling?
    if ($feather_config['o_report_method'] == '0' || $feather_config['o_report_method'] == '2') {
        $db->query('INSERT INTO '.$db->prefix.'reports (post_id, topic_id, forum_id, reported_by, created, message) VALUES('.$post_id.', '.$topic_id.', '.$forum_id.', '.$feather_user['id'].', '.time().', \''.$db->escape($reason).'\')') or error('Unable to create report', __FILE__, __LINE__, $db->error());
    }

    // Should we email the report?
    if ($feather_config['o_report_method'] == '1' || $feather_config['o_report_method'] == '2') {
        // We send it to the complete mailing-list in one swoop
        if ($feather_config['o_mailing_list'] != '') {
            // Load the "new report" template
            $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$feather_user['language'].'/mail_templates/new_report.tpl'));

            // The first row contains the subject
            $first_crlf = strpos($mail_tpl, "\n");
            $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
            $mail_message = trim(substr($mail_tpl, $first_crlf));

            $mail_subject = str_replace('<forum_id>', $forum_id, $mail_subject);
            $mail_subject = str_replace('<topic_subject>', $subject, $mail_subject);
            $mail_message = str_replace('<username>', $feather_user['username'], $mail_message);
            $mail_message = str_replace('<post_url>', get_base_url().'/viewtopic.php?pid='.$post_id.'#p'.$post_id, $mail_message);
            $mail_message = str_replace('<reason>', $reason, $mail_message);
            $mail_message = str_replace('<board_mailer>', $feather_config['o_board_title'], $mail_message);

            require FEATHER_ROOT.'include/email.php';

            pun_mail($feather_config['o_mailing_list'], $mail_subject, $mail_message);
        }
    }

    $db->query('UPDATE '.$db->prefix.'users SET last_report_sent='.time().' WHERE id='.$feather_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());

    redirect(get_link('forum/'.$forum_id.'/'.url_friendly($subject).'/'), $lang_misc['Report redirect']);
}

function get_info_report($post_id)
{
    global $db, $feather_user, $lang_common;
    
    $result = $db->query('SELECT f.id AS fid, f.forum_name, t.id AS tid, t.subject FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$post_id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $cur_post = $db->fetch_assoc($result);
    
    return $cur_post;
}

function subscribe_topic($topic_id)
{
    global $db, $lang_common, $lang_misc, $feather_user, $feather_config;
    
    if ($feather_config['o_topic_subscriptions'] != '1') {
        message($lang_common['No permission'], false, '403 Forbidden');
    }
    
    // Make sure the user can view the topic
    $result = $db->query('SELECT 1 FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$topic_id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $result = $db->query('SELECT 1 FROM '.$db->prefix.'topic_subscriptions WHERE user_id='.$feather_user['id'].' AND topic_id='.$topic_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        message($lang_misc['Already subscribed topic']);
    }

    $db->query('INSERT INTO '.$db->prefix.'topic_subscriptions (user_id, topic_id) VALUES('.$feather_user['id'].' ,'.$topic_id.')') or error('Unable to add subscription', __FILE__, __LINE__, $db->error());

    redirect(get_link('topic/'.$topic_id.'/'), $lang_misc['Subscribe redirect']);
}

function unsubscribe_topic($topic_id)
{
    global $db, $lang_common, $lang_misc, $feather_user, $feather_config;
    
    if ($feather_config['o_topic_subscriptions'] != '1') {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

    $result = $db->query('SELECT 1 FROM '.$db->prefix.'topic_subscriptions WHERE user_id='.$feather_user['id'].' AND topic_id='.$topic_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_misc['Not subscribed topic']);
    }

    $db->query('DELETE FROM '.$db->prefix.'topic_subscriptions WHERE user_id='.$feather_user['id'].' AND topic_id='.$topic_id) or error('Unable to remove subscription', __FILE__, __LINE__, $db->error());

    redirect(get_link('topic/'.$topic_id.'/'), $lang_misc['Unsubscribe redirect']);
}

function unsubscribe_forum($forum_id)
{
    global $db, $lang_common, $lang_misc, $feather_user, $feather_config;
    
    if ($feather_config['o_forum_subscriptions'] != '1') {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

    $result = $db->query('SELECT 1 FROM '.$db->prefix.'forum_subscriptions WHERE user_id='.$feather_user['id'].' AND forum_id='.$forum_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_misc['Not subscribed forum']);
    }

    $db->query('DELETE FROM '.$db->prefix.'forum_subscriptions WHERE user_id='.$feather_user['id'].' AND forum_id='.$forum_id) or error('Unable to remove subscription', __FILE__, __LINE__, $db->error());

    redirect(get_link('forum/'.$forum_id.'/'), $lang_misc['Unsubscribe redirect']);
}

function subscribe_forum($forum_id)
{
    global $db, $lang_common, $lang_misc, $feather_user, $feather_config;
    
    if ($feather_config['o_forum_subscriptions'] != '1') {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

    // Make sure the user can view the forum
    $result = $db->query('SELECT 1 FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $result = $db->query('SELECT 1 FROM '.$db->prefix.'forum_subscriptions WHERE user_id='.$feather_user['id'].' AND forum_id='.$forum_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        message($lang_misc['Already subscribed forum']);
    }

    $db->query('INSERT INTO '.$db->prefix.'forum_subscriptions (user_id, forum_id) VALUES('.$feather_user['id'].' ,'.$forum_id.')') or error('Unable to add subscription', __FILE__, __LINE__, $db->error());

    redirect(get_link('forum/'.$forum_id.'/'), $lang_misc['Subscribe redirect']);
}
