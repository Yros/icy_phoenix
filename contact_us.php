<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_ICYPHOENIX', true);
if (!defined('IP_ROOT_PATH')) define('IP_ROOT_PATH', './');
if (!defined('PHP_EXT')) define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
include(IP_ROOT_PATH . 'common.' . PHP_EXT);
include_once(IP_ROOT_PATH . 'includes/functions_users_delete.' . PHP_EXT);
include_once(IP_ROOT_PATH . 'includes/functions_flood.' . PHP_EXT);
include_once(IP_ROOT_PATH . 'includes/bbcode.' . PHP_EXT);

define('ENABLE_VISUAL_CONFIRM', true);

// Start session management
$userdata = session_pagestart($user_ip);
init_userprefs($userdata);
// End session management

include(IP_ROOT_PATH . 'includes/class_form.' . PHP_EXT);
$class_form = new class_form();

$account_delete = false;
$cms_page['page_id'] = 'contact_us';
$cms_page['page_nav'] = (!empty($cms_config_layouts[$cms_page['page_id']]['page_nav']) ? true : false);
$cms_page['global_blocks'] = (!empty($cms_config_layouts[$cms_page['page_id']]['global_blocks']) ? true : false);

$account_delete_id = request_var('account_delete', 0);
$account_delete_id = ($account_delete_id <= 2) ? false : $account_delete_id;
if (!empty($account_delete_id))
{
	if (!$userdata['session_logged_in'] || ($account_delete_id == false) || ($account_delete_id != $userdata['user_id']))
	{
		message_die(GENERAL_MESSAGE, $lang['Not_Auth_View']);
	}
	$account_delete = true;
}
else
{
	$cms_auth_level = (isset($cms_config_layouts[$cms_page['page_id']]['view']) ? $cms_config_layouts[$cms_page['page_id']]['view'] : AUTH_ALL);
	check_page_auth($cms_page['page_id'], $cms_auth_level);
}

// Set default email variables
$server_url = create_server_url();
$contact_us_server_url = $server_url . 'contact_us.' . PHP_EXT;

// TICKETS - BEGIN
if (!$account_delete)
{
	$sql = "SELECT * FROM " . TICKETS_CAT_TABLE . " ORDER BY ticket_cat_id ASC";
	$result = $db->sql_query($sql);
	$ticket_rows = $db->sql_fetchrowset($result);
	$tickets_count = sizeof($ticket_rows);
	$db->sql_freeresult($result);

	if ($tickets_count > 0)
	{
		$tickets_array = array();
		$tickets_lang_array = array();
		$template->assign_var('S_TICKETS', true);
		for($i = 0; $i < $tickets_count; $i++)
		{
			$tickets_array[] = $ticket_rows[$i]['ticket_cat_id'];
			$tickets_lang_array[] = htmlspecialchars(stripslashes($ticket_rows[$i]['ticket_cat_title']));
		}

		$select_name = 'ticket_cat_id';
		$default = '';
		$select_js = '';
		$select_ticket = $class_form->build_select_box($select_name, $default, $tickets_array, $tickets_lang_array, $select_js);
	}
}
// TICKETS - END

check_flood_email(false);

$sender = request_var('sender', '', true);
$subject = request_var('subject', '', true);
$message = request_var('message', '', true);

if (isset($_POST['submit']))
{
	$error = false;

	if (ENABLE_VISUAL_CONFIRM && !$userdata['session_logged_in'])
	{
		if (empty($_POST['confirm_id']))
		{
			$error = true;
			$error_msg .= ((isset($error_msg)) ? '<br />' : '') . $lang['CONFIRM_CODE_WRONG'];
		}
		else
		{
			$confirm_id = htmlspecialchars($_POST['confirm_id']);
			$confirm_code = $_POST['confirm_code'];
			if (!preg_match('/^[A-Za-z0-9]+$/', $confirm_id))
			{
				$confirm_id = '';
			}

			$sql = "SELECT code
				FROM " . CONFIRM_TABLE . "
				WHERE confirm_id = '" . $confirm_id . "'
					AND session_id = '" . $userdata['session_id'] . "'";
			$result = $db->sql_query($sql);
			if ($row = $db->sql_fetchrow($result))
			{
				if ($row['code'] != $confirm_code)
				{
					$error = true;
					$error_msg .= ((isset($error_msg)) ? '<br />' : '') . $lang['CONFIRM_CODE_WRONG'];
				}
				else
				{
					$sql = "DELETE FROM " . CONFIRM_TABLE . "
						WHERE confirm_id = '" . $confirm_id . "'
							AND session_id = '" . $userdata['session_id'] . "'";
					$result = $db->sql_query($sql);
				}
			}
			else
			{
				$error = true;
				$error_msg .= ((isset($error_msg)) ? '<br />' : '') . $lang['CONFIRM_CODE_WRONG'];
			}
			$db->sql_freeresult($result);
		}
	}

	if (empty($sender))
	{
		$error = true;
		$error_msg = (!empty($error_msg)) ? $error_msg . '<br />' . $lang['Empty_sender_email'] : $lang['Empty_sender_email'];
	}

	if (empty($subject))
	{
		$error = true;
		$error_msg = (!empty($error_msg)) ? $error_msg . '<br />' . $lang['Empty_subject_email'] : $lang['Empty_subject_email'];
	}

	if (!empty($message))
	{
		if ($account_delete)
		{
			$message = sprintf($lang['ACCOUNT_DELETION_REQUEST'], $userdata['username']) . "<br />\r\n<hr /><br />\r\n<br />\r\n" . $message;
		}
		$message = preg_replace(array("/<br \/>\r\n/", "/<br>\r\n/", "/(\r\n|\n|\r)/"), array("\r\n", "\r\n", "<br />\r\n"), $message);
	}
	else
	{
		$error = true;
		$error_msg = (!empty($error_msg)) ? $error_msg . '<br />' . $lang['Empty_message_email'] : $lang['Empty_message_email'];
	}

	// TICKETS - BEGIN
	$bcc_list = '';
	$bcc_emails = array();
	if (!$account_delete)
	{
		$ticket_cat_id = request_var('ticket_cat_id', '');
		if (!empty($ticket_cat_id))
		{
			$sql = "SELECT * FROM " . TICKETS_CAT_TABLE . " WHERE ticket_cat_id = " . $ticket_cat_id;
			$result = $db->sql_query($sql);
			$ticket_row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			$bcc_emails = explode(';', str_replace(array("\r\n", "\n", "\r", "\t"), array('', '', '', ''), trim($ticket_row['ticket_cat_emails'])));
			if (!empty($bcc_emails))
			{
				for($i = 0; $i < sizeof($bcc_emails); $i++)
				{
					$bcc_list .= (($bcc_list != '') ? ', ' : '') . $bcc_emails[$i];
				}
			}
		}
	}
	// TICKETS - END

	if (!$error)
	{
		update_flood_time_email();

		include(IP_ROOT_PATH . 'includes/emailer.' . PHP_EXT);
		$emailer = new emailer();

		$emailer->headers('X-AntiAbuse: Board servername - ' . trim($config['server_name']));
		$emailer->headers('X-AntiAbuse: User_id - ' . $userdata['user_id']);
		$emailer->headers('X-AntiAbuse: Username - ' . $userdata['username']);
		$emailer->headers('X-AntiAbuse: User IP - ' . decode_ip($user_ip));

		$email_subject = htmlspecialchars_decode($subject, ENT_COMPAT);
		//$email_subject = utf8_decode($email_subject);

		$email_message = $message;
		/*
		$email_message = htmlspecialchars_decode($email_message, ENT_COMPAT);
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		if (!empty($config['html_email']))
		{
			$email_message = strtr($email_message, $trans_tbl);
		}
		*/

		$emailer->use_template('empty_email', $user_lang);
		$emailer->to($config['board_email']);
		$emailer->from($sender);
		foreach ($bcc_emails as $bcc_address)
		{
			if (!empty($bcc_address))
			{
				$emailer->bcc($bcc_address);
			}
		}
		// Send also to sender in BCC if needed...
		if (!empty($_POST['cc_email']))
		{
			$emailer->bcc($sender);
		}
		$emailer->replyto($sender);

		$emailer->set_subject($email_subject);

		$emailer->assign_vars(array(
			'MESSAGE' => $email_message
			)
		);
		$emailer->send();
		$emailer->reset();

		$redirect_url = append_sid(CMS_PAGE_HOME);
		meta_refresh(3, $redirect_url);

		$message = $lang['Email_sent'] . '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . append_sid(CMS_PAGE_HOME) . '">', '</a>');

		if ($account_delete)
		{
			$sql = "UPDATE " . USERS_TABLE . "
				SET user_active = '0'
				WHERE user_id = " . $userdata['user_id'];
			$result = $db->sql_query($sql);
			$clear_notification = user_clear_notifications($userdata['user_id']);
			$message = $lang['Email_sent'];
			$redirect_url = append_sid(CMS_PAGE_LOGIN . '?logout=true&amp;sid=' . $userdata['session_id']);
			meta_refresh(3, $redirect_url);
		}

		message_die(GENERAL_MESSAGE, $message);

	}
}

if ($error)
{
	$template->set_filenames(array('reg_header' => 'error_body.tpl'));
	$template->assign_vars(array(
		'ERROR_MESSAGE' => $error_msg
		)
	);
	$template->assign_var_from_handle('ERROR_BOX', 'reg_header');
}

if (ENABLE_VISUAL_CONFIRM && !$userdata['session_logged_in'])
{
	// Visual Confirmation
	$confirm_image = '';
	$sql = "SELECT session_id
		FROM " . SESSIONS_TABLE;
	$result = $db->sql_query($sql);

	if ($row = $db->sql_fetchrow($result))
	{
		$confirm_sql = '';
		do
		{
			$confirm_sql .= (($confirm_sql != '') ? ', ' : '') . "'" . $row['session_id'] . "'";
		}
		while ($row = $db->sql_fetchrow($result));
		$db->sql_freeresult($result);

		$sql_del = "DELETE FROM " . CONFIRM_TABLE . " WHERE session_id NOT IN (" . $confirm_sql . ")";
		$result_del = $db->sql_query($sql_del);
	}

	$sql = "SELECT COUNT(session_id) AS attempts
		FROM " . CONFIRM_TABLE . "
		WHERE session_id = '" . $userdata['session_id'] . "'";
	$result = $db->sql_query($sql);
	if ($row = $db->sql_fetchrow($result))
	{
		if ($row['attempts'] > 3)
		{
			message_die(GENERAL_MESSAGE, $lang['TOO_MANY_ATTEMPTS']);
		}
	}
	$db->sql_freeresult($result);
	// Generate the required confirmation code
	// NB 0 (zero) could get confused with O (the letter) so we make change it
	$code = unique_id();
	$code = substr(str_replace('0', 'Z', strtoupper(base_convert($code, 16, 35))), 2, 6);
	$confirm_id = md5(uniqid($user_ip));
	$sql = "INSERT INTO " . CONFIRM_TABLE . " (confirm_id, session_id, code)
		VALUES ('" . $confirm_id . "', '" . $userdata['session_id'] . "', '" . $code . "')";
	$result = $db->sql_query($sql);
	unset($code);
	$confirm_image = '<img src="' . append_sid(CMS_PAGE_PROFILE . '?mode=confirm&amp;id=' . $confirm_id) . '" alt="" title="" />';
	$s_hidden_fields .= '<input type="hidden" name="confirm_id" value="' . $confirm_id . '" />';
	$template->assign_block_vars('switch_confirm', array());
}

if ($account_delete == true)
{
	$template->assign_block_vars('delete_account', array());
	$s_hidden_fields .= '<input type="hidden" name="account_delete" value="' . $userdata['user_id'] . '" />';
}

$template->assign_vars(array(
	'SENDER' => $sender,
	'SUBJECT' => $subject,
	'MESSAGE' => $message,
	'SELECT_TICKET' => (!empty($select_ticket) ? $select_ticket : ''),
	'S_POST_ACTION' => append_sid('contact_us.' . PHP_EXT),
	'CONFIRM_IMG' => $confirm_image,
	'S_HIDDEN_FIELDS' => $s_hidden_fields,

	'L_CONTACT_US' => $lang['Contact_us'],
	'L_SEND_EMAIL_MSG' => $lang['Send_email_msg'],
	'L_SENDER' => $lang['TELL_FRIEND_SENDER_EMAIL'],
	'L_SUBJECT' => $lang['Subject'],
	'L_MESSAGE_BODY' => $lang['Message_body'],
	'L_MESSAGE_BODY_DESC' => $lang['Email_message_desc'],
	'L_EMPTY_SENDER_EMAIL' => $lang['Empty_sender_email'],
	'L_EMPTY_SUBJECT_EMAIL' => $lang['Empty_subject_email'],
	'L_EMPTY_MESSAGE_EMAIL' => $lang['Empty_message_email'],
	'L_DELETE_ACCOUNT' => $lang['Delete_My_Account'],
	'L_DELETE_ACCOUNT_EXPLAIN' => $lang['Delete_My_Account_Explain'],
	'L_OPTIONS' => $lang['Options'],
	'L_CC_EMAIL' => $lang['CC_email'],
	'L_CONFIRM_CODE_IMPAIRED' => sprintf($lang['CONFIRM_CODE_IMPAIRED'], '<a href="mailto:' . $config['board_email'] . '">', '</a>'),
	'L_SPELLCHECK' => $lang['Spellcheck'],
	'L_SEND_EMAIL' => $lang['Send_Email']
	)
);

full_page_generation('contact_us_body.tpl', $lang['Contact_us'], '', '');

?>