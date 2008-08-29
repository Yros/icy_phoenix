<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
*
* @Extra credits for this file
* Jamer (Colin James) - (http://www.jamer.co.uk/scripts/phpbb2)
*
*/

define('IN_PHPBB', true);
if( !empty($setmodules) )
{
	$filename = basename(__FILE__);
	$module['1610_Users']['140_Email_List'] = append_sid($filename);
	return;
}

// Load default header
$phpbb_root_path = './../';
require($phpbb_root_path . 'extension.inc');
require('./pagestart.' . $phpEx);
include_once($phpbb_root_path . 'includes/functions_groups.' . $phpEx);

$start = ( isset($_GET['start']) ) ? intval($_GET['start']) : 0;
$start = ($start < 0) ? 0 : $start;

if ( isset($_GET['show']) || isset($_POST['show']) )
{
	$show = ( isset($_POST['show']) ) ? intval($_POST['show']) : intval($_GET['show']);
}
else
{
	$show = $board_config['topics_per_page'];
}

// Generate page
$template->set_filenames(array('body' => ADM_TPL . 'admin_users_email_list_body.tpl'));

$template->assign_vars(array(
	'L_ADMIN_USERS_LIST_MAIL_TITLE' => $lang['Admin_Users_List_Mail_Title'],
	'L_ADMIN_USERS_LIST_MAIL_EXPLAIN' => $lang['Admin_Users_List_Mail_Explain'],
	'L_USERNAME' => $lang['Usersname'],
	'L_EMAIL' => $lang['Email']
	)
);

$sql = "SELECT user_id, username, user_email FROM " . USERS_TABLE . "
				WHERE user_id <> " . ANONYMOUS . "
				ORDER BY username ASC
				LIMIT $start, $show";
if(!$result = $db->sql_query($sql))
{
	message_die(GENERAL_ERROR, "Could not count Users", "", __LINE__, __FILE__, $sql);
}
$i = 0;
while($row = $db->sql_fetchrow($result))
{
	$i++;
	$row_color = (($i % 2) == 0) ? 'row1' : 'row2';

	$template->assign_block_vars('userrow', array(
		'COLOR' => $row_color,
		'NUMBER' => ($start + $i + 1),
		'USERNAME' => colorize_username($row['user_id']),
		'U_ADMIN_USER' => append_sid('admin_users.' . $phpEx . '?mode=edit&amp;' . POST_USERS_URL . '=' . $row['user_id']),
		'EMAIL' => $row['user_email']
		)
	);
}
$db->sql_freeresult($result);

$count_sql = "SELECT count(user_id) AS total
	FROM " . USERS_TABLE . "
	WHERE user_id <> " . ANONYMOUS;

if ( !($count_result = $db->sql_query($count_sql)) )
{
	message_die(GENERAL_ERROR, 'Error getting total users', '', __LINE__, __FILE__, $sql);
}

if ($total = $db->sql_fetchrow($count_result))
{
	$total_members = $total['total'];
	$pagination = generate_pagination($phpbb_root_path . ADM . '/admin_email_list.' . $phpEx . '?show=' . $show, $total_members, $show, $start);
}

$template->assign_vars(array(
	'PAGINATION' => $pagination,
	'PAGE_NUMBER' => sprintf($lang['Page_of'], (floor($start / $show) + 1), ceil($total_members / $show))
	)
);

$template->pparse('body');
include('./page_footer_admin.' . $phpEx);

?>