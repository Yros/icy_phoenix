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
* Sven Ribienski (bigrib@gmx.de)
*
*/

define('IN_PHPBB', true);

if( !empty($setmodules) )
{
	$file = basename(__FILE__);
	$module['1000_Configuration']['130_UPI2DB_Mod'] = $file;
	return;
}

// Let's set the root dir for phpBB
$phpbb_root_path = './../';
require($phpbb_root_path . 'extension.inc');
require('./pagestart.' . $phpEx);
include($phpbb_root_path . 'includes/functions_selects.' . $phpEx);

//
// Pull all config data
//

$sql = "SELECT * FROM " . $table_prefix . "upi2db_topic_read";
if($result = $db->sql_query($sql))
{
	$message = '<b><u>UPI2DB Datenbank Installation</u></b>';
	$message .= '<br /><br />';
	$message .= 'A table from UPI2DB 2.x.x found.<br />You can update this now to use it later on or install a new one.<br /><br /> <a href="' . append_sid("../upi2db_db_update.$phpEx") . '" class="mainmenu">Update UPI2DB Database</a>';
	$message .= '<br /><br />';
	$message .= '<a href="' . append_sid("../upi2db_db_install.$phpEx") . '" class="mainmenu">Install NEW UPI2DB tables</a>';
	$message .= '<br /><hr><br />';
	$message .= 'Es wurde eine UPI2DB Mod 2.x.x Datenbank gefunden.<br />Du kannst diese nun updaten und weiter verwenden oder eine neue installieren.<br /><br /> <a href="' . append_sid("../upi2db_db_update.$phpEx") . '" class="mainmenu">UPI2DB Datenbank Updaten</a>';
	$message .= '<br /><br />';
	$message .= '<a href="' . append_sid("../upi2db_db_install.$phpEx") . '" class="mainmenu">UPI2DB Datenbank NEU Installieren</a>';

	message_die(GENERAL_MESSAGE, $message);
}

$sql = "SELECT * FROM " . CONFIG_TABLE . " WHERE config_name LIKE \"upi2db_%\"";
if( !$result = $db->sql_query($sql) )
{
	message_die(GENERAL_ERROR, "Could not read config data", '', __LINE__, __FILE__, $sql);
}

$counts = $db->sql_affectedrows($result);
if($counts == 0)
{
	$message = '<b><u>UPI2DB Database installation</u></b>';
	$message .= '<br /><br />';
	$message .= 'The needed table installation was yet not done.<br /><br /> <a href="' . append_sid("../upi2db_db_install.$phpEx") . '" class="mainmenu">Install UPI2DB tables</a>';
	$message .= '<br /><hr><br />';
	$message .= '<b><u>UPI2DB Datenbank Installation</u></b>';
	$message .= '<br /><br />';
	$message .= 'Die notwendige Datenbank Installation wurde noch nicht durchgef�hrt.<br /><br /> <a href="' . append_sid("../upi2db_db_install.$phpEx") . '" class="mainmenu">UPI2DB Datenbank Installieren</a>';

	message_die(GENERAL_MESSAGE, $message);
}

$datei = '../upi2db_db_up2latest.php';
if(file_exists($datei))
{
	$message = '<b><u>UPI2DB Database Update</u></b>';
	$message .= '<br /><br />';
	$message .= 'A UPI2DB Mod data base update file was found.<br />You can implement this file now with the following link. If you already implemented it, delete the file from the server.<br /><br /> <a href="' . append_sid($datei) . '" > >>>>>>> Update UPI2DB Database <<<<<<< </a><br /><br /><span class="text_red">PLEASE DELETE ALL upi2db_db_*.php FILES FROM THE SERVER!!!!!</span>';
	$message .= '<br /><hr><br />';
	$message .= '<b><u>UPI2DB Datenbank Update</u></b>';
	$message .= '<br /><br />';
	$message .= 'Es wurde eine UPI2DB Mod Datenbank Update Datei gefunden.<br />Du kannst diese Datei nun mit folgenden Link ausf�hren. Solltest du sie bereits ausgef�hrt haben l�sche sie bitte vom Server.<br /><br /> <a href="' . append_sid($datei) . '"> >>>>>>> UPI2DB Datenbank Updaten <<<<<<< </a><br /><br /><span class="text_red">BITTE L�SCHE ALLE upi2db_db_*.php DATEIEN VOM SERVER !!!!!</span>';

	message_die(GENERAL_MESSAGE, $message);
}

$file_found = 0;
$dir = @opendir("../");

while( $file = @readdir($dir) )
{
	if( preg_match("/^upi2db_db_.*?\." . $phpEx . "$/", $file) )
	{
		$file_found = 1;
	}
}

@closedir($dir);

if($file_found)
{
	$message = '<span class="text_red">Please delete all upi2db_db_*.php files from your server!!!</span>';
	$message .= '<br /><hr>';
	$message .= '<span class="text_red">Bitte l�sche alle upi2db_db_*.php Dateien von deinem Server!!!</span>';

	message_die(GENERAL_MESSAGE, $message);
}

while( $row = $db->sql_fetchrow($result) )
{
	$config_name = $row['config_name'];
	$config_value = $row['config_value'];
	$default_config[$config_name] = $config_value;

	$new[$config_name] = ( isset($HTTP_POST_VARS[$config_name]) ) ? $HTTP_POST_VARS[$config_name] : $default_config[$config_name];

	if( isset($HTTP_POST_VARS['submit']) )
	{
		$sql = "UPDATE " . CONFIG_TABLE . " SET
			config_value = '" . str_replace("\'", "''", $new[$config_name]) . "'
			WHERE config_name = '$config_name'";
		if( !$db->sql_query($sql) )
		{
			message_die(GENERAL_ERROR, "Failed to update upi2db configuration for $config_name", "", __LINE__, __FILE__, $sql);
		}
	}
}


$upi2db_on_1 = ( $new['upi2db_on'] == 1 ) ? 'checked="checked"' : '';
$upi2db_on_0 = ( $new['upi2db_on'] == 0 ) ? 'checked="checked"' : '';
$upi2db_on_2 = ( $new['upi2db_on'] == 2 ) ? 'checked="checked"' : '';

$no_group_upi2db_on_yes = ( $new['upi2db_no_group_upi2db_on']) ? 'checked="checked"' : '';
$no_group_upi2db_on_no = ( !$new['upi2db_no_group_upi2db_on']) ? 'checked="checked"' : '';

$edit_as_new_yes = ( $new['upi2db_edit_as_new'] ) ? 'checked="checked"' : '';
$edit_as_new_no = ( !$new['upi2db_edit_as_new'] ) ? 'checked="checked"' : '';

$last_edit_as_new_yes = ( $new['upi2db_last_edit_as_new'] ) ? 'checked="checked"' : '';
$last_edit_as_new_no = ( !$new['upi2db_last_edit_as_new'] ) ? 'checked="checked"' : '';

$edit_topic_first_yes = ( $new['upi2db_edit_topic_first'] ) ? 'checked="checked"' : '';
$edit_topic_first_no = ( !$new['upi2db_edit_topic_first'] ) ? 'checked="checked"' : '';

$template->set_filenames(array('body' => ADM_TPL . 'upi2db_config_body.tpl'));

$sql = "SELECT *
	FROM " . GROUPS_TABLE . "
	WHERE group_single_user <> " . TRUE;
if(!$result = $db->sql_query($sql))
{
	message_die(CRITICAL_ERROR, "Could not query upi2db config information", "", __LINE__, __FILE__, $sql);
}
else
{
	if( isset($HTTP_POST_VARS['submit']) )
	{
		$group_upi2db_on = ( isset($HTTP_POST_VARS[group_upi2db_on]) ) ? $HTTP_POST_VARS[group_upi2db_on] : 0;
		$group_min_posts = ( isset($HTTP_POST_VARS[group_min_posts]) ) ? $HTTP_POST_VARS[group_min_posts] : 0;
		$group_min_regdays = ( isset($HTTP_POST_VARS[group_min_regdays]) ) ? $HTTP_POST_VARS[group_min_regdays] : 0;

		while( $row = $db->sql_fetchrow($result) )
		{
			$sql = "UPDATE " . GROUPS_TABLE . "
				SET upi2db_on = " . $group_upi2db_on[$row['group_id']] . " ,
				upi2db_min_posts = " . $group_min_posts[$row['group_id']] . ",
				upi2db_min_regdays = " . $group_min_regdays[$row['group_id']] . "
				WHERE group_id = " . $row['group_id'];

			if ( !$db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, 'Could not update upi2db group auth', '', __LINE__, __FILE__, $sql);
			}
		}
	}
	while( $row = $db->sql_fetchrow($result) )
	{
		$group_upi2db_on_yes = $row['upi2db_on'] ? 'checked="checked"' : '';
		$group_upi2db_on_no = !$row['upi2db_on'] ? 'checked="checked"' : '';

		$upi2db_min_posts = (empty($row['upi2db_min_posts'])) ? 0 : $row['upi2db_min_posts'];
		$upi2db_min_regdays = (empty($row['upi2db_min_regdays'])) ? 0 : $row['upi2db_min_regdays'];

		$template->assign_block_vars('group_loop',array(
			'GROUP_ID' => $row['group_id'],
			'GROUP_NAME' => $row['group_name'],
			'GROUP_MIN_POSTS' => $upi2db_min_posts,
			'GROUP_MIN_REGDAYS' => $upi2db_min_regdays,
			'GROUP_UPI2DB_ON_YES' => $group_upi2db_on_yes,
			'GROUP_UPI2DB_ON_NO' => $group_upi2db_on_no
			)
		);
	}
}

if( isset($HTTP_POST_VARS['submit']) )
{
	$message = $lang['Config_updated'] . '<br /><br />' . sprintf($lang['Click_return_config'], '<a href="' . append_sid('admin_upi2db.' . $phpEx) . '">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_admin_index'], '<a href="' . append_sid('index.' . $phpEx . '?pane=right') . '">', '</a>');

	message_die(GENERAL_MESSAGE, $message);
}

$template->assign_block_vars('switch_upi2db_full', array());

$template->assign_vars(array(
	'UPI2DB_ON_1' => $upi2db_on_1,
	'UPI2DB_ON_0' => $upi2db_on_0,
	'UPI2DB_ON_2' => $upi2db_on_2,
	'UPI2DB_VERSION_NUMBER' => $board_config['upi2db_version'],

	'NO_GROUP_UPI2DB_ON_YES' => $no_group_upi2db_on_yes,
	'NO_GROUP_UPI2DB_ON_NO' => $no_group_upi2db_on_no,

	'LAST_EDIT_AS_NEW_YES' => $last_edit_as_new_yes,
	'LAST_EDIT_AS_NEW_NO' => $last_edit_as_new_no,
	'EDIT_AS_NEW_YES' => $edit_as_new_yes,
	'EDIT_AS_NEW_NO' => $edit_as_new_no,
	'EDIT_TOPIC_FIRST_YES' => $edit_topic_first_yes,
	'EDIT_TOPIC_FIRST_NO' => $edit_topic_first_no,
	'UPI2DB_DAYS' => $new[upi2db_auto_read],
	'UPI2DB_DEL_MARK' => $new[upi2db_del_mark],
	'UPI2DB_DEL_PERM' => $new[upi2db_del_perm],
	'MAX_NEW_POSTS' => $new[upi2db_max_new_posts],
	'MAX_NEW_POSTS_ADMIN' => $new[upi2db_max_new_posts_admin],
	'MAX_NEW_POSTS_MOD' => $new[upi2db_max_new_posts_mod],
	'MAX_PERMANENT_TOPICS' => $new[upi2db_max_permanent_topics],
	'MAX_MARK_POSTS' => $new[upi2db_max_mark_posts],
	'UNREAD_COLOR' => $new[upi2db_unread_color],
	'EDIT_COLOR' => $new[upi2db_edit_color],
	'MARK_COLOR' => $new[upi2db_mark_color],
	'ADMIN' => $lang['Forum_ADMIN'],
	'MOD' => $lang['Forum_MOD'],
	'NORMAL' => $lang['Forum_REG'],

	'NO_GROUP_MIN_REGDAYS' => $new[upi2db_no_group_min_regdays],
	'NO_GROUP_MIN_POSTS' => $new[upi2db_no_group_min_posts],

	'L_CONFIGURATION_TITLE' => $lang['setup_upi2db'],
	'L_CONFIGURATION_EXPLAIN' => $lang['setup_upi2db_explain'],
	'L_SETUP_UPI2DB' => $lang['setup_upi2db'],
	'L_CONDITION_SETUP' => $lang['upi2db_condition_setup'],
	'L_UPI2DB_ON' => $lang['upi2db_on'],
	'L_UPI2DB_ON_EXPLAIN' => $lang['upi2db_on_explain'],
	'L_UPI2DB_DAYS' => $lang['up2db_days'],
	'L_UPI2DB_DAYS_TAGEN' => $lang['up2db_days_tagen'],
	'L_UPI2DB_DAYS_EXPLAIN' => $lang['up2db_days_explain'],
	'L_UPI2DB_DEL_MARK' => $lang['up2db_del_mark'],
	'L_UPI2DB_DEL_PERM' => $lang['up2db_del_perm'],
	'L_UPI2DB_DEL_MARK_EXPLAIN' => $lang['up2db_del_mark_explain'],
	'L_UPI2DB_DEL_PERM_EXPLAIN' => $lang['up2db_del_perm_explain'],
	'L_EDIT_AS_NEW' => $lang['edit_as_new'],
	'L_EDIT_AS_NEW_EXPLAIN' => $lang['edit_as_new_explain'],
	'L_LAST_EDIT_AS_NEW' => $lang['last_edit_as_new'],
	'L_EDIT_TOPIC_FIRST' => $lang['edit_topic_first'],
	'L_EDIT_TOPIC_FIRST_EXPLAIN' => $lang['edit_topic_first_explain'],
	'L_UNREAD_COLOR' => $lang['upi2db_unread_color'],
	'L_EDIT_COLOR' => $lang['upi2db_edit_color'],
	'L_MARK_COLOR' => $lang['upi2db_mark_color'],
	'L_MAX_NEW_POSTS' => $lang['max_new_posts'],
	'L_MAX_NEW_POSTS_EXPLAIN' => $lang['max_new_posts_explain'],
	'L_MAX_PERMANENT_TOPICS' => $lang['max_permanent_topics'],
	'L_MAX_PERMANENT_TOPICS_EXPLAIN' => $lang['max_permanent_topics_explain'],
	'L_MAX_MARK_POSTS' => $lang['max_mark_posts'],
	'L_MAX_MARK_POSTS_EXPLAIN' => $lang['max_mark_posts_explain'],

	'L_GROUP_ALLOW_UPI2DB' => $lang['group_allow_upi2db'],
	'L_USER_ALLOW_UPI2DB' => $lang['user_allow_upi2db'],
	'L_GROUP_NAME' => $lang['group_name'],
	'L_GROUP_USER' => $lang['group_user'],
	'L_USER_WITHOUT_GROUP' => $lang['user_without_group'],
	'L_MIN_REG_DAYS' => $lang['upi2db_condition_min_regdays'],
	'L_MIN_POSTS' => $lang['upi2db_condition_min_posts'],

	'L_YES' => $lang['Yes'],
	'L_NO' => $lang['No'],
	'L_ENABLED' => $lang['Enabled'],
	'L_DISABLED' => $lang['Disabled'],
	'L_USER_SELECT' => $lang['user_select'],
	'L_SUBMIT' => $lang['Submit'],
	'L_RESET' => $lang['Reset']
	)
);

$template->pparse('body');

include('./page_footer_admin.' . $phpEx);

?>