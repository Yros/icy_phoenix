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
* 2003 Freakin' Booty ;-P & Antony Bailey
*
*/

define('IN_PHPBB', true);

if( !empty($setmodules) )
{
	$filename = basename(__FILE__);
	$module['1200_Forums']['120_Permissions_List'] = $filename;
	return;
}

// Load default header
$phpbb_root_path = './../';
$no_page_header = true;
require($phpbb_root_path . 'extension.inc');
require('./pagestart.' . $phpEx);

// Start program - define vars
include($phpbb_root_path . './includes/def_auth.' . $phpEx);

// Get required information, for all forums
/*
$sql = "SELECT f.*
		FROM " . FORUMS_TABLE . " f, " . CATEGORIES_TABLE . " c
		WHERE c.cat_id = f.cat_id
		ORDER BY c.cat_order ASC, f.forum_order ASC";
*/
$sql = "SELECT f.*
		FROM " . FORUMS_TABLE . " f
		ORDER BY f.cat_id ASC, f.forum_order ASC";
if( !$result = $db->sql_query($sql) )
{
	message_die(GENERAL_ERROR, 'Couldn\'t obtain forum list', '', __LINE__, __FILE__, $sql);
}

$forum_rows = $db->sql_fetchrowset($result);
$db->sql_freeresult($result);

// Start program proper
if($_POST['submit'])
{
	for($i = 0; $i < count($forum_rows); $i++)
	{
		$sql = '';
		$forum_id = $forum_rows[$i]['forum_id'];

		for( $j = 0; $j < count($forum_auth_fields); $j++ )
		{
			$value = $_POST[$forum_auth_fields[$j]][$forum_id];

			if ( $forum_auth_fields[$j] == 'auth_vote' )
			{
				if ( $_POST['auth_vote'][$forum_id] == AUTH_ALL )
				{
					$value = AUTH_REG;
				}
			}

			$sql .= ( ( $sql != '' ) ? ', ' : '' ) . $forum_auth_fields[$j] . ' = ' . $value;
		}

		$sql = "UPDATE " . FORUMS_TABLE . " SET $sql WHERE forum_id = $forum_id";
		if( !$db->sql_query($sql) )
		{
			message_die(GENERAL_ERROR, 'Couldn\'t update forum permissions', '', __LINE__, __FILE__, $sql);
		}

	}
	cache_tree(true);
	$template->assign_vars(array(
		'META' => '<meta http-equiv="refresh" content="3; url=' . append_sid('admin_forumauth_list.' . $phpEx) . '" />'
		)
	);
	$message = $lang['Forum_auth_updated'] . '<br /><br />' . sprintf($lang['Click_return_forumauth'],  '<a href="' . append_sid('admin_forumauth_list.' . $phpEx) . '">', '</a>');
	message_die(GENERAL_MESSAGE, $message);
}


// Default page
$colspan = count($forum_auth_fields) + 1;

// Output the authorisation details
$template->set_filenames(array('body' => ADM_TPL . 'auth_forum_list_body.tpl'));

$template->assign_vars(array(
	'L_AUTH_LIST_TITLE' => $lang['Auth_list_Control_Forum'],
	'L_AUTH_LIST_EXPLAIN' => $lang['Forum_auth_list_explain'],
	'L_SUBMIT' => $lang['Submit'],
	'L_RESET' => $lang['Reset'],
	'COLSPAN' => $colspan,
	'S_FORM_ACTION' => append_sid('admin_forumauth_list.' . $phpEx)
	)
);

$template->assign_block_vars('forum_auth_titles', array(
	'CELL_TITLE' => 'Forum Name'
	)
);

for( $i = 0; $i < count($forum_auth_fields); $i++ )
{
	$template->assign_block_vars('forum_auth_titles', array(
		'CELL_TITLE' => $field_names[$forum_auth_fields[$i]]
		)
	);
}

for ($i = 0; $i < count($forum_rows); $i++)
{
	$temp_url = append_sid('admin_forumauth.' . $phpEx . '?' . POST_FORUM_URL . '=' . $forum_rows[$i]['forum_id']);
	$s_forum = '<a href="' . $temp_url . '">' . $forum_rows[$i]['forum_name'] . '</a>';

	$template->assign_block_vars('forum_row', array(
		'ROW_CLASS' => ( !($i % 2) ) ? 'row1' : 'row2',
		'S_FORUM' => $s_forum
		)
	);

	for($j = 0; $j < count($forum_auth_fields); $j++)
	{
		$custom_auth[$j] = '&nbsp;<select name="' . $forum_auth_fields[$j] . '[' . $forum_rows[$i]['forum_id'] . ']">';

		for($k = 0; $k < count($forum_auth_levels); $k++)
		{
			$selected = ( $forum_rows[$i][$forum_auth_fields[$j]] == $forum_auth_const[$k] ) ? ' selected="selected"' : '';
			$custom_auth[$j] .= '<option value="' . $forum_auth_const[$k] . '"' . $selected . '>' . $lang['Forum_' . $forum_auth_levels[$k]] . '</option>';
		}
		$custom_auth[$j] .= '</select>&nbsp;';

		$cell_title = $field_names[$forum_auth_fields[$j]];

		$template->assign_block_vars('forum_row.forum_auth_data', array(
			'S_AUTH_LEVELS_SELECT' => $custom_auth[$j]
			)
		);
	}
}

include('./page_header_admin.' . $phpEx);

$template->pparse('body');

include('./page_footer_admin.' . $phpEx);

?>