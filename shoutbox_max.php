<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_PHPBB', true);
$phpbb_root_path = './';
include($phpbb_root_path . 'extension.inc');
include($phpbb_root_path . 'common.' . $phpEx);
include_once($phpbb_root_path . 'includes/bbcode.' . $phpEx);
include_once($phpbb_root_path . 'includes/functions_post.' . $phpEx);
include_once($phpbb_root_path . 'includes/functions_groups.' . $phpEx);
define('NUM_SHOUT', 20);

// Start session management
$userdata = session_pagestart($user_ip);
init_userprefs($userdata);
// End session management

$start = isset($_POST['start']) ? intval($_POST['start']) : (isset($_GET['start']) ? intval($_GET['start']) : 0);
$start = ($start < 0) ? 0 : $start;

$page_number = (isset($_GET['page_number']) ? intval($_GET['page_number']) : (isset($_POST['page_number']) ? intval($_POST['page_number']) : false));
$page_number = ($page_number < 1) ? false : $page_number;

$start = (!$page_number) ? $start : (($page_number * $board_config['topics_per_page']) - $board_config['topics_per_page']);

$cms_page_id = '19';
$cms_page_name = 'shoutbox';
$auth_level_req = $board_config['auth_view_shoutbox'];
if ($auth_level_req > AUTH_ALL)
{
	if (($auth_level_req == AUTH_REG) && (!$userdata['session_logged_in']))
	{
		message_die(GENERAL_MESSAGE, $lang['Not_Auth_View']);
	}
	if ($userdata['user_level'] != ADMIN)
	{
		if ($auth_level_req == AUTH_ADMIN)
		{
			message_die(GENERAL_MESSAGE, $lang['Not_Auth_View']);
		}
		if (($auth_level_req == AUTH_MOD) && ($userdata['user_level'] != MOD))
		{
			message_die(GENERAL_MESSAGE, $lang['Not_Auth_View']);
		}
	}
}
$cms_global_blocks = ($board_config['wide_blocks_shoutbox'] == 1) ? true : false;


// Start auth check
switch ($userdata['user_level'])
{
	//Customize this, if you need other permission settings
	// please also make same changes to other shoutbox php files
	case ADMIN :
	case MOD : $is_auth['auth_mod'] = 1;
	default:
			$is_auth['auth_read'] = 1;
			$is_auth['auth_view'] = 1;
			if ($userdata['user_id'] == ANONYMOUS)
			{
				$is_auth['auth_delete'] = 0;
				$is_auth['auth_post'] = 0;
			}
			else
			{
				$is_auth['auth_delete'] = 1;
				$is_auth['auth_post'] = 1;
			}
}

if(!$is_auth['auth_read'])
{
	message_die(GENERAL_MESSAGE, $lang['Not_Authorised']);
}

$forum_id=PAGE_SHOUTBOX_MAX;
$refresh = (isset($_POST['auto_refresh']) || isset($_POST['refresh'])) ? 1 : 0;
$preview = (isset($_POST['preview'])) ? 1 : 0;
$submit = (isset($_POST['shout']) && isset($_POST['message'])) ? 1 : 0;
if (isset($_POST['mode']) || isset($_GET['mode']))
{
	$mode = (isset($_POST['mode'])) ? $_POST['mode'] : $_GET['mode'];
}
else
{
	$mode = '';
}

//
// Set toggles for various options
//
if (!$board_config['allow_html'])
{
	$html_on = 0;
}
else
{
	$html_on = ($submit || $refresh || preview) ? ((!empty($_POST['disable_html'])) ? 0 : 1) : (($userdata['user_id'] == ANONYMOUS) ? $board_config['allow_html'] : $userdata['user_allowhtml']);
}
if (!$board_config['allow_bbcode'])
{
	$bbcode_on = 0;
}
else
{
	$bbcode_on = ($submit || $refresh || preview) ? ((!empty($_POST['disable_bbcode'])) ? 0 : 1) : (($userdata['user_id'] == ANONYMOUS) ? $board_config['allow_bbcode'] : $userdata['user_allowbbcode']);
}

if (!$board_config['allow_smilies'])
{
	$smilies_on = 0;
}
else
{
	$smilies_on = ($submit || $refresh || preview) ? ((!empty($_POST['disable_smilies'])) ? 0 : 1) : (($userdata['user_id'] == ANONYMOUS) ? $board_config['allow_smilies'] : $userdata['user_allowsmile']);
}
if(!$userdata['session_logged_in'] || ($mode == 'editpost' && $post_info['poster_id'] == ANONYMOUS))
{
	$template->assign_block_vars('switch_username_select', array());
}
$username = (!empty($_POST['username'])) ? $_POST['username'] : '';
// Check username
if (!empty($username))
{
	$username = phpbb_clean_username($username);
	if (!$userdata['session_logged_in'])
	{
		require_once($phpbb_root_path . 'includes/functions_validate.' . $phpEx);
		$result = validate_username($username);
		if ($result['error'])
		{
			$error = true;
			$error_msg .= (!empty($error_msg)) ? '<br />' . $result['error_msg'] : $result['error_msg'];
		}
	}
}

if ($refresh || $preview)
{
	$message = (!empty($_POST['message'])) ? htmlspecialchars(trim(stripslashes($_POST['message']))) : '';
	if (!empty($message))
	{
		if ($preview)
		{
			if (!$userdata['user_allowswearywords'])
			{
				$orig_word = array();
				$replacement_word = array();
				obtain_word_list($orig_word, $replacement_word);
			}

			$bbcode_uid = ($bbcode_on) ? make_bbcode_uid() : '';
			$preview_message = stripslashes(prepare_message(addslashes(unprepare_message($message)), $html_on, $bbcode_on, $smilies_on, $bbcode_uid));
			if ($board_config['img_shoutbox'] == true)
			{
				$preview_message = preg_replace ("#\[url=(http://)([^ \"\n\r\t<]*)\]\[img\](http://)([^ \"\n\r\t<]*)\[/img\]\[/url\]#i", '[url=\\1\\2]\\4[/url]', $preview_message);
				$preview_message = preg_replace ("#\[img\](http://)([^ \"\n\r\t<]*)\[/img\]#i", '[url=\\1\\2]\\2[/url]', $preview_message);
				$preview_message = preg_replace ("#\[img align=left\](http://)([^ \"\n\r\t<]*)\[/img\]#i", '[url=\\1\\2]\\2[/url]', $preview_message);
				$preview_message = preg_replace ("#\[img align=right\](http://)([^ \"\n\r\t<]*)\[/img\]#i", '[url=\\1\\2]\\2[/url]', $preview_message);
			}
			$bbcode->allow_html = ($board_config['allow_html'] ? true : false);
			$bbcode->allow_bbcode = ($board_config['allow_bbcode'] && $bbcode_on ? true : false);
			$bbcode->allow_smilies = ($board_config['allow_smilies'] && $smilies_on ? true : false);
			$preview_message = $bbcode->parse($preview_message, $bbcode_uid);

			if(!empty($orig_word))
			{
				$preview_message = (!empty($preview_message)) ? preg_replace($orig_word, $replacement_word, $preview_message) : '';
			}
			$orig_autolink = array();
			$replacement_autolink = array();
			obtain_autolink_list($orig_autolink, $replacement_autolink, 99999999);
			if(function_exists('acronym_pass'))
			{
				$preview_message = acronym_pass($preview_message);
			}
			if(count($orig_autolink))
			{
				$preview_message = autolink_transform($preview_message, $orig_autolink, $replacement_autolink);
			}
			//$preview_message = kb_word_wrap_pass ($preview_message);
			$preview_message = str_replace("\n", '<br />', $preview_message);
			$template->set_filenames(array('preview' => 'posting_preview.tpl'));
			$template->assign_vars(array(
				'USERNAME' => $username,
				'POST_DATE' => create_date2($board_config['default_dateformat'], time(), $board_config['board_timezone']),
				'MESSAGE' => $preview_message,
				'L_POSTED' => $lang['Posted'],
				'L_PREVIEW' => $lang['Preview']
				)
			);
			$template->assign_var_from_handle('POST_PREVIEW_BOX', 'preview');
		}
		$template->assign_var('MESSAGE', $message);
	}
}
elseif ($submit || isset($_POST['message']))
{
	$current_time = time();
	// Flood control
	$where_sql = ($userdata['user_id'] == ANONYMOUS) ? "shout_ip = '$user_ip'" : 'shout_user_id = ' . $userdata['user_id'];
	$sql = "SELECT MAX(shout_session_time) AS last_post_time
	FROM " . SHOUTBOX_TABLE . "
	WHERE $where_sql";
	if ($result = $db->sql_query($sql))
	{
		if ($row = $db->sql_fetchrow($result))
		{
			if (($row['last_post_time'] > 0) && (($current_time - $row['last_post_time']) < $board_config['flood_interval']) && ($userdata['user_level'] != ADMIN))
			{
				$error = true;
				$error_msg .= (!empty($error_msg)) ? '<br />' . $lang['Flood_Error'] : $lang['Flood_Error'];
			}
		}
	}

	$message = (isset($_POST['message'])) ? trim($_POST['message']) : '';
	// insert shout !
	if (!empty($message) && $is_auth['auth_post'] && !$error)
	{
		$bbcode_uid = ($bbcode_on) ? make_bbcode_uid() : '';

		if ($board_config['img_shoutbox'] == true)
		{
			$message = preg_replace ("#\[url=(http://)([^ \"\n\r\t<]*)\]\[img\](http://)([^ \"\n\r\t<]*)\[/img\]\[/url\]#i", '[url=\\1\\2]\\4[/url]', $message);
			$message = preg_replace ("#\[img\](http://)([^ \"\n\r\t<]*)\[/img\]#i", '[url=\\1\\2]\\2[/url]', $message);
			$message = preg_replace ("#\[img align=left\](http://)([^ \"\n\r\t<]*)\[/img\]#i", '[url=\\1\\2]\\2[/url]', $message);
			$message = preg_replace ("#\[img align=right\](http://)([^ \"\n\r\t<]*)\[/img\]#i", '[url=\\1\\2]\\2[/url]', $message);
		}

		$message = prepare_message(trim($message), $html_on, $bbcode_on, $smilies_on, $bbcode_uid);
		//$message = (!get_magic_quotes_gpc()) ? addslashes($message) : stripslashes($message);
		$sql = "INSERT INTO " . SHOUTBOX_TABLE. " (shout_text, shout_session_time, shout_user_id, shout_ip, shout_username, shout_bbcode_uid,enable_bbcode,enable_html,enable_smilies)
				VALUES ('$message', '".time()."', '".$userdata['user_id']."', '$user_ip', '".$username."', '".$bbcode_uid."',$bbcode_on,$html_on,$smilies_on)";
		if (!$result = $db->sql_query($sql))
		{
			message_die(GENERAL_ERROR, 'Error inserting shout.', '', __LINE__, __FILE__, $sql);
		}
		// auto prune
		if ($board_config['prune_shouts'])
		{
			$sql = "DELETE FROM " . SHOUTBOX_TABLE . " WHERE shout_session_time<=" . (time() - (86400 * $board_config['prune_shouts']));
			if (!$result = $db->sql_query($sql))
			{
				message_die(GENERAL_ERROR, 'Error autoprune shouts.', '', __LINE__, __FILE__, $sql);
			}
		}
	}
}
elseif ($mode=='delete' || $mode=='censor')
{
	// make shout inactive
	if (isset($_GET[POST_POST_URL]) || isset($_POST[POST_POST_URL]))
	{
		$post_id = (isset($_POST[POST_POST_URL])) ? intval($_POST[POST_POST_URL]) : intval($_GET[POST_POST_URL]);
	}
	else
	{
		message_die(GENERAL_ERROR, 'Error no shout id specifyed for delete/censor.', '', __LINE__, __FILE__);
	}
	$sql = "SELECT s.shout_user_id, shout_ip FROM " . SHOUTBOX_TABLE . " s WHERE s.shout_id='$post_id'";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not get shoutbox information', '', __LINE__, __FILE__, $sql);
	}
	$shout_identifyer = $db->sql_fetchrow($result);
	$user_id = $shout_identifyer['shout_user_id'];

	if (($userdata['user_id'] != ANONYMOUS || ($userdata['user_id'] == ANONYMOUS && $userdata['session_ip'] == $shout_identifyer['shout_ip'])) && (($userdata['user_id'] == $user_id && $is_auth['auth_delete']) || $is_auth['auth_mod']) && $mode == 'censor')
	{
		$sql = "UPDATE " . SHOUTBOX_TABLE . " SET shout_active='" . $userdata['user_id'] . "' WHERE shout_id='$post_id'";
		if (!$result = $db->sql_query($sql))
		{
			message_die(GENERAL_ERROR, 'Error censor shout.', '', __LINE__, __FILE__, $sql);
		}
	}
	elseif ($is_auth['auth_mod'] && $mode=='delete')
	{
		$sql = "DELETE FROM ".SHOUTBOX_TABLE." WHERE shout_id='$post_id'";
		if (!$result = $db->sql_query($sql))
		{
			message_die(GENERAL_ERROR, 'Error removing shout.', '', __LINE__, __FILE__, $sql);
		}
	}
	else
	{
		message_die(GENERAL_MESSAGE, 'Not allowed.', '', __LINE__, __FILE__);
	}
}
elseif ($mode=='ip')
{
	//	show the ip
	if (!$is_auth['auth_mod'])
	{
		message_die(GENERAL_MESSAGE, 'Not allowed.', '', __LINE__, __FILE__);
	}
	if (isset($_GET[POST_POST_URL]) || isset($_POST[POST_POST_URL]))
	{
		$post_id = (isset($_POST[POST_POST_URL])) ? intval($_POST[POST_POST_URL]) : intval($_GET[POST_POST_URL]);
	}
	else
	{
		message_die(GENERAL_ERROR, 'Error no shout id specifyed for show ip', '', __LINE__, __FILE__);
	}
	$sql = "SELECT s.shout_user_id, shout_username, shout_ip FROM " . SHOUTBOX_TABLE . " s WHERE s.shout_id='$post_id'";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not get shoutbox information', '', __LINE__, __FILE__, $sql);
	}
	$shout_identifyer = $db->sql_fetchrow($result);
	$poster_id = $shout_identifyer['shout_user_id'];
	$rdns_ip_num = (isset($_GET['rdns'])) ? $_GET['rdns'] : "";

	$ip_this_post = decode_ip($shout_identifyer['shout_ip']);
	$ip_this_post = ($rdns_ip_num == $ip_this_post) ? gethostbyaddr($ip_this_post) : $ip_this_post;
	$page_title = $lang['Shoutbox'];
	$meta_description = '';
	$meta_keywords = '';
	include($phpbb_root_path . 'includes/page_header.' . $phpEx);

	// Set template files
	$template->set_filenames(array('viewip' => 'modcp_viewip.tpl'));
	$template->assign_vars(array(
		'L_IP_INFO' => $lang['IP_info'],
		'L_THIS_POST_IP' => $lang['This_posts_IP'],
		'L_OTHER_IPS' => $lang['Other_IP_this_user'],
		'L_OTHER_USERS' => $lang['Users_this_IP'],
		'L_LOOKUP_IP' => $lang['Lookup_IP'],
		'L_SEARCH' => $lang['Search'],
		'SEARCH_IMG' => $images['icon_search'],
		'IP' => $ip_this_post,
		'U_LOOKUP_IP' => append_sid('shoutbox_max.' . $phpEx . '?mode=ip&amp;' . POST_POST_URL . '=' . $post_id . '&amp;rdns=' . $ip_this_post)
		)
	);

	// Get other IP's this user has posted under
	$sql = "SELECT shout_ip, COUNT(*) AS postings
		FROM " . SHOUTBOX_TABLE . "
		WHERE shout_user_id = $poster_id
		GROUP BY shout_ip
		ORDER BY " . ((SQL_LAYER == 'msaccess') ? 'COUNT(*)' : 'postings') . " DESC";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not get IP information for this user', '', __LINE__, __FILE__, $sql);
	}
	if ($row = $db->sql_fetchrow($result))
	{
		$i = 0;
		do
		{
			if ($row['shout_ip'] == $post_row['shout_ip'])
			{
				$template->assign_vars(array(
					'POSTS' => $row['postings'] . ' ' . (($row['postings'] == 1) ? $lang['Post'] : $lang['Posts']))
				);
				continue;
			}

			$ip = decode_ip($row['shout_ip']);
			$ip = ($rdns_ip_num == $row['shout_ip'] || $rdns_ip_num == 'all') ? gethostbyaddr($ip) : $ip;

			$row_color = (!($i % 2)) ? $theme['td_color1'] : $theme['td_color2'];
			$row_class = (!($i % 2)) ? $theme['td_class1'] : $theme['td_class2'];

			$template->assign_block_vars('iprow', array(
				'ROW_COLOR' => '#' . $row_color,
				'ROW_CLASS' => $row_class,
				'IP' => $ip,
				'POSTS' => $row['postings'] . ' ' . (($row['postings'] == 1) ? $lang['Post'] : $lang['Posts']),

				'U_LOOKUP_IP' => append_sid('shoutbox_max.' . $phpEx . '?mode=ip&amp;' . POST_POST_URL . '=' . $post_id . '&amp;rdns=' . $row['shout_ip'])
				)
			);

			$i++;
		}
		while ($row = $db->sql_fetchrow($result));
	}

	//
	// Get other users who've posted under this IP
	//
	$sql = "SELECT u.user_id, u.username, COUNT(*) as postings
		FROM " . USERS_TABLE ." u, " . POSTS_TABLE . " p
		WHERE p.poster_id = u.user_id
			AND p.poster_ip = '" . $shout_identifyer['shout_ip'] . "'
		GROUP BY u.user_id, u.username
		ORDER BY " . ((SQL_LAYER == 'msaccess') ? 'COUNT(*)' : 'postings') . " DESC";

	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Could not get posters information based on IP', '', __LINE__, __FILE__, $sql);
	}

	if ($row = $db->sql_fetchrow($result))
	{
		$i = 0;
		do
		{
			$id = $row['user_id'];
//				$username = ($id == ANONYMOUS) ? $lang['Guest'] : $row['username'];
			$shout_username = ($id == ANONYMOUS && $row['username'] == '') ? $lang['Guest'] : $row['username'];

			$row_color = (!($i % 2)) ? $theme['td_color1'] : $theme['td_color2'];
			$row_class = (!($i % 2)) ? $theme['td_class1'] : $theme['td_class2'];

			$template->assign_block_vars('userrow', array(
				'ROW_COLOR' => '#' . $row_color,
				'ROW_CLASS' => $row_class,
				'SHOUT_USERNAME' => $shout_username,
				'POSTS' => $row['postings'] . ' ' . (($row['postings'] == 1) ? $lang['Post'] : $lang['Posts']),
				'L_SEARCH_POSTS' => sprintf($lang['Search_user_posts'], $shout_username),

				'U_PROFILE' => append_sid(PROFILE_MG . '?mode=viewprofile&amp;' . POST_USERS_URL . '=' . $id),
				'U_SEARCHPOSTS' => append_sid(SEARCH_MG . '?search_author=' . urlencode($shout_username) . '&amp;showresults=topics')
				)
			);

			$i++;
		}
		while ($row = $db->sql_fetchrow($result));
	}


	$template->pparse('viewip');
	include($phpbb_root_path . 'includes/page_tail.' . $phpEx);
	exit;
}

// display the defult page

$page_title = $lang['Shoutbox'];
$meta_description = '';
$meta_keywords = '';
include($phpbb_root_path . 'includes/page_header.' . $phpEx);

// Was a highlight request part of the URI?
$highlight_match = $highlight = '';
if (isset($_GET['highlight']))
{
	// Split words and phrases
	$words = explode(' ', trim(htmlspecialchars($_GET['highlight'])));

	for($i = 0; $i < count($words); $i++)
	{
		if (trim($words[$i]) != '')
		{
			$highlight_match .= (($highlight_match != '') ? '|' : '') . str_replace('*', '\w*', phpbb_preg_quote($words[$i], '#'));
		}
	}
	unset($words);

	$highlight = urlencode($_GET['highlight']);
	$highlight_match = phpbb_rtrim($highlight_match, "\\");
}


$sql = "SELECT * FROM " . RANKS_TABLE . " ORDER BY rank_special ASC, rank_min ASC";
if (!($result = $db->sql_query($sql. false, true)))
{
	message_die(GENERAL_ERROR, "Could not obtain ranks information.", '', __LINE__, __FILE__, $sql);
}

$ranksrow = array();
while ($row = $db->sql_fetchrow($result))
{
	$ranksrow[] = $row;
}
$db->sql_freeresult($result);

//
// Define censored word matches
//
if (!$userdata['user_allowswearywords'])
{
	$orig_word = array();
	$replacement_word = array();
	obtain_word_list($orig_word, $replacement_word);
}

// get statistics
$sql = "SELECT COUNT(*) as total FROM " . SHOUTBOX_TABLE;
if (!($result = $db->sql_query($sql)))
{
	message_die(GENERAL_ERROR, 'Could not get shoutbox stat information', '', __LINE__, __FILE__, $sql);
}
$total_shouts = $db->sql_fetchrow($result);
$total_shouts = $total_shouts['total'];
// parse post permission
if ($is_auth['auth_post'])
{
	$template->set_filenames(array('body' => 'shoutbox_max_body.tpl'));
}
else
{
	$template->set_filenames(array('body' => 'shoutbox_max_guest_body.tpl'));
}

// Generate smilies listing for page output
//generate_smilies('inline');

// Smilies toggle selection
if ($board_config['allow_smilies'])
{
	$smilies_status = $lang['Smilies_are_ON'];
	$template->assign_block_vars('switch_smilies_checkbox', array());
}
else
{
	$smilies_status = $lang['Smilies_are_OFF'];
}

// HTML toggle selection
if ($board_config['allow_html'])
{
	$html_status = $lang['HTML_is_ON'];
	$template->assign_block_vars('switch_html_checkbox', array());
}
else
{
	$html_status = $lang['HTML_is_OFF'];
}

// BBCode toggle selection
if ($board_config['allow_bbcode'])
{
	$bbcode_status = $lang['BBCode_is_ON'];
	$template->assign_block_vars('switch_bbcode_checkbox', array());
}
else
{
	$bbcode_status = $lang['BBCode_is_OFF'];
}

// display the shoutbox
$sql = "SELECT s.*, u.* FROM " . SHOUTBOX_TABLE . " s, " . USERS_TABLE . " u
	WHERE s.shout_user_id=u.user_id ORDER BY s.shout_session_time DESC LIMIT $start, " . $board_config['posts_per_page'];
if (!($result = $db->sql_query($sql)))
{
	message_die(GENERAL_ERROR, 'Could not get shoutbox information', '', __LINE__, __FILE__, $sql);
}

while ($shout_row = $db->sql_fetchrow($result))
{
	$i++;
	$row_color = (!($i % 2)) ? $theme['td_color1'] : $theme['td_color2'];
	$row_class = (!($i % 2)) ? $theme['td_class1'] : $theme['td_class2'];
	$user_id = $shout_row['shout_user_id'];
	$shout_username = ($user_id == ANONYMOUS) ? (($shout_row['shout_username'] == '') ? $lang['Guest'] : $shout_row['shout_username']) : colorize_username($shout_row['shout_user_id']) ;

	$user_profile = append_sid(PROFILE_MG . '?mode=viewprofile&amp;' . POST_USERS_URL . '=' . $user_id);
	$user_posts = ($shout_row['user_id'] != ANONYMOUS) ? $lang['Posts'] . ': ' . $shout_row['user_posts'] : '';
	$user_from = ($shout_row['user_from'] && $shout_row['user_id'] != ANONYMOUS) ? $lang['Location'] . ': ' . $shout_row['user_from'] : '';
	$user_joined = ($shout_row['user_id'] != ANONYMOUS) ? $lang['Joined'] . ': ' . create_date($lang['JOINED_DATE_FORMAT'], $shout_row['user_regdate'], $board_config['board_timezone']) : '';

	$user_avatar = user_get_avatar($shout_row['user_id'], $shout_row['user_avatar'], $shout_row['user_avatar_type'], $shout_row['user_allowavatar']);

	$shout = (! $shout_row['shout_active']) ? $shout_row['shout_text'] : $lang['Shout_censor'].(($is_auth['auth_mod']) ? '<br/><hr/><br/>'.$shout_row['shout_text'] : '');
	$user_sig = ($shout_row['enable_sig'] && $shout_row['user_sig'] != '' && $board_config['allow_sig']) ? $shout_row['user_sig'] : '';
	$user_sig_bbcode_uid = $shout_row['user_sig_bbcode_uid'];

	$rank_image = '';
	if ($shout_row['user_rank'])
	{
		for($j = 0; $j < count($ranksrow); $j++)
		{
			if ($shout_row['user_rank'] == $ranksrow[$j]['rank_id'] && $ranksrow[$j]['rank_special'])
			{
				$user_rank = ($shout_row['user_id'] != ANONYMOUS) ? $ranksrow[$j]['rank_title'] : '';
				$rank_image = ($ranksrow[$j]['rank_image'] && $shout_row['user_id'] != ANONYMOUS) ? '<img src="' . $ranksrow[$j]['rank_image'] . '" alt="' . $user_rank . '" title="' . $user_rank . '" /><br />' : '';
			}
		}
	}
	else
	{
		for($j = 0; $j < count($ranksrow); $j++)
		{
			if ($shout_row['user_posts'] >= $ranksrow[$j]['rank_min'] && !$ranksrow[$j]['rank_special'])
			{
				$user_rank = ($shout_row['user_id'] != ANONYMOUS) ? $ranksrow[$j]['rank_title'] : '';
				$rank_image = ($ranksrow[$j]['rank_image'] && $shout_row['user_id'] != ANONYMOUS) ? '<img src="' . $ranksrow[$j]['rank_image'] . '" alt="' . $user_rank . '" title="' . $user_rank . '" /><br />' : '';
			}
		}
	}

	if ($user_sig != '')
	{
		$bbcode->allow_html = ($board_config['allow_html'] ? true : false);
		$bbcode->allow_bbcode = ($board_config['allow_bbcode'] ? true : false);
		$bbcode->allow_smilies = ($board_config['allow_smilies'] ? true : false);
		$bbcode->is_sig = ($board_config['allow_all_bbcode'] == 0) ? true : false;
		$user_sig = $bbcode->parse($user_sig, $bbcode_uid);
		$bbcode->is_sig = false;
	}


	// Highlight active words (primarily for search)
	if ($highlight_match)
	{
		$shout = str_replace('\"', '"', substr(@preg_replace('#(\>(((?>([^><]+|(?R)))*)\<))#se', "@preg_replace('#\b(" . str_replace('\\', '\\\\', addslashes($highlight_match)) . ")\b#i', '<span style=\"color:#" . $theme['fontcolor3'] . "\"><b>\\\\1</b></span>', '\\0')", '>' . $shout . '<'), 1, -1));
	}

	// Replace naughty words
	if (count($orig_word))
	{
		if ($user_sig != '')
		{
			$user_sig = preg_replace($orig_word, $replacement_word, $user_sig);
		}
		$shout = preg_replace($orig_word, $replacement_word, $shout);
	}
	$bbcode->allow_html = ($board_config['allow_html'] ? true : false);
	$bbcode->allow_bbcode = ($board_config['allow_bbcode'] && $shout_row['enable_bbcode'] ? true : false);
	$bbcode->allow_smilies = ($board_config['allow_smilies'] && $shout != '' && $shout_row['enable_smilies'] ? true : false);

	$shout = $bbcode->parse($shout,$shout_row['shout_bbcode_uid']);
	$shout = str_replace("\n", "\n<br />\n", $shout);

	$orig_autolink = array();
	$replacement_autolink = array();
	obtain_autolink_list($orig_autolink, $replacement_autolink, 99999999);
	if(function_exists('acronym_pass'))
	{
		$shout = acronym_pass($shout);
	}
	if(count($orig_autolink))
	{
		$shout = autolink_transform($shout, $orig_autolink, $replacement_autolink);
	}
	//$shout = kb_word_wrap_pass ($shout);
	if ($is_auth['auth_mod'] && $is_auth['auth_delete'])
	{
		$temp_url = append_sid('shoutbox_max.' . $phpEx . '?mode=ip&amp;' . POST_POST_URL . '=' . $shout_row['shout_id']);
		$ip_img = '<a href="' . $temp_url . '"><img src="' . $images['icon_ip'] . '" alt="' . $lang['View_IP'] . '" title="' . $lang['View_IP'] . '" /></a>';
		$ip = '<a href="' . $temp_url . '">' . $lang['View_IP'] . '</a>';

		$temp_url = append_sid('shoutbox_max.' . $phpEx . '?mode=delete&amp;' . POST_POST_URL . '=' . $shout_row['shout_id']);
		$delshout_img = '<a href="' . $temp_url . '"><img src="' . $images['icon_delpost'] . '" alt="' . $lang['Delete_post'] . '" title="' . $lang['Delete_post'] . '" /></a>&nbsp;';
		$delshout = '<a href="' . $temp_url . '">' . $lang['Delete_post'] . '</a>';

		$temp_url = append_sid('shoutbox_max.' . $phpEx . '?mode=censor&amp;' . POST_POST_URL . '=' . $shout_row['shout_id']);
		$censorshout_img = '<a href="' . $temp_url . '"><img src="' . $images['icon_censor'] . '" alt="' . $lang['Censor'] . '" title="' . $lang['Censor'] . '" /></a>&nbsp;';
		$censorshout = '<a href="' . $temp_url . '">' . $lang['Delete_post'] . '</a>';
	}
	else
	{
		$ip_img = '';
		$ip = '';

		if (($userdata['user_id'] == $user_id && $is_auth['auth_delete']) && ($userdata['user_id'] != ANONYMOUS || ($userdata['user_id'] == ANONYMOUS && $userdata['session_ip'] == $shout_row['shout_ip'])))
		{
			$temp_url = append_sid('shoutbox_max.' . $phpEx . '?mode=censor&amp;' . POST_POST_URL . '=' . $shout_row['shout_id']);
			$censorshout_img = '<a href="' . $temp_url . '"><img src="' . $images['icon_censor'] . '" alt="' . $lang['Censor'] . '" title="' . $lang['Censor'] . '" /></a>&nbsp;';
			$censorshout = '<a href="' . $temp_url . '">' . $lang['Delete_post'] . '</a>';
		}
		else
		{
			$delshout_img = '';
			$delshout = '';
			$censorshout_img = '';
			$censorshout = '';
		}
	}

	$template->assign_block_vars('shoutrow', array(
		'ROW_COLOR' => '#' . $row_color,
		'ROW_CLASS' => $row_class,
		'SHOUT' => $shout,
		'TIME' => create_date2($board_config['default_dateformat'], $shout_row['shout_session_time'], $board_config['board_timezone']),
		'SHOUT_USERNAME' => $shout_username,
		'U_VIEW_USER_PROFILE' => $user_profile,
		'RANK_IMAGE' => $rank_image,
		'IP_IMG' => $ip_img,
		'IP' => $ip,

		'DELETE_IMG' => $delshout_img,
		'DELETE' => $delshout,
		'CENSOR_IMG' => $censorshout_img,
		'CENSOR' => $censorshout,
		'USER_JOINED' => $user_joined,
		'USER_POSTS' => $user_posts,
		'USER_FROM' => $user_from,
		'USER_AVATAR' => $user_avatar,
		'U_SHOUT_ID' => $shout_row['shout_id']
		)
	);
}

// Show post options
if ($is_auth['auth_post'])
{
	$template->assign_block_vars('switch_auth_post', array());
}
else
{
	$template->assign_block_vars('switch_auth_no_post', array());
}

$template->assign_vars(array(
	'USERNAME' => $username,
	'NUMBER_OF_SHOUTS' => $total_shouts,
	'HTML_STATUS' => $html_status,
	'BBCODE_STATUS' => sprintf($bbcode_status, '<a href="' . append_sid('faq.' . $phpEx . '?mode=bbcode') . '" target="_phpbbcode">', '</a>'),
	'L_SHOUTBOX_LOGIN' => $lang['Login_join'],
	'L_POSTED' => $lang['Posted'],
	'L_AUTHOR' => $lang['Author'],
	'L_MESSAGE' => $lang['Message'],
	'U_SHOUTBOX' => append_sid('shoutbox_max.' . $phpEx . '?start=' . $start),
	'T_NAME' => $theme['template_name'],
	'T_URL' => 'templates/' . $theme['template_name'],
	'L_SHOUTBOX' => $lang['Shoutbox'],
	'L_SHOUT_PREVIEW' => $lang['Preview'],
	'L_SHOUT_SUBMIT' => $lang['Go'],
	'L_SHOUT_TEXT' => $lang['Shout_text'],
	'L_SHOUT_REFRESH' => $lang['Shout_refresh'],
	'S_HIDDEN_FIELDS' => $s_hidden_fields,

	'SMILIES_STATUS' => $smilies_status,
	'L_EMPTY_MESSAGE' => $lang['Empty_message'],

	'L_DISABLE_HTML' => $lang['Disable_HTML_post'],
	'L_DISABLE_BBCODE' => $lang['Disable_BBCode_post'],
	'L_DISABLE_SMILIES' => $lang['Disable_Smilies_post'],

	'L_BBCODE_CLOSE_TAGS' => $lang['Close_Tags'],
	'L_STYLES_TIP' => $lang['Styles_tip'],
	'S_HTML_CHECKED' => (!$html_on) ? 'checked="checked"' : '',
	'S_BBCODE_CHECKED' => (!$bbcode_on) ? 'checked="checked"' : '',
	'S_SMILIES_CHECKED' => (!$smilies_on) ? 'checked="checked"' : ''
	)
);

if($error_msg != '')
{
	$template->set_filenames(array('reg_header' => 'error_body.tpl'));
	$template->assign_vars(array('ERROR_MESSAGE' => $error_msg));
	$template->assign_var_from_handle('ERROR_BOX', 'reg_header');
	$message = (!empty($_POST['message'])) ? htmlspecialchars(trim(stripslashes($_POST['message']))) : '';
	$template->assign_var('MESSAGE', $message);
}

// BBCBMG - BEGIN
include_once($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_bbcb_mg.' . $phpEx);
include($phpbb_root_path . 'includes/bbcb_mg.' . $phpEx);
$template->assign_var_from_handle('BBCB_MG', 'bbcb_mg');
// BBCBMG - END
// BBCBMG SMILEYS - BEGIN
generate_smilies('inline');
include($phpbb_root_path . 'includes/bbcb_smileys_mg.' . $phpEx);
$template->assign_var_from_handle('BBCB_SMILEYS_MG', 'bbcb_smileys_mg');
// BBCBMG SMILEYS - END

// Generate pagination for shoutbox view
$pagination = ($highlight_match) ? generate_pagination('shoutbox_max.' . $phpEx . '?highlight=' . $highlight, $total_shouts, $board_config['posts_per_page'], $start) : generate_pagination('shoutbox_max.' . $phpEx . '?dummy=1', $total_shouts, $board_config['posts_per_page'], $start);

$template->assign_vars(array(
	'PAGINATION' => $pagination,
	'NUMBER_OF_SHOUTS' => $total_shouts,
	)
);

$template->pparse('body');

// Include page tail
include($phpbb_root_path . 'includes/page_tail.' . $phpEx);

?>