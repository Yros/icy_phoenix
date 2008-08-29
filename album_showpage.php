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
* Smartor (smartor_xp@hotmail.com)
*
*/

define('IN_PHPBB', true);
$phpbb_root_path = './';
include($phpbb_root_path . 'extension.inc');
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_validate.' . $phpEx);
include($phpbb_root_path . 'includes/functions_post.' . $phpEx);

// Start session management
$userdata = defined('IS_ICYPHOENIX') ? session_pagestart($user_ip) : session_pagestart($user_ip, PAGE_ALBUM_PICTURE);
init_userprefs($userdata);
// End session management

// Get general album information
$album_root_path = $phpbb_root_path . ALBUM_MOD_PATH;
include($album_root_path . 'album_common.' . $phpEx);

if ( defined('IS_ICYPHOENIX') )
{
	include_once($phpbb_root_path . 'includes/functions_groups.' . $phpEx);
	include_once($phpbb_root_path . 'includes/bbcode.' . $phpEx);
	if ( isset($_POST['message']) )
	{
		$_POST['comment'] = $_POST['message'];
	}
}
else
{
	if ($album_config['album_bbcode'] == 1)
	{
		include_once($album_root_path . 'album_bbcode.' . $phpEx);
	}
}

if( isset($_GET['mode']) && $_GET['mode'] == 'smilies' )
{
	if ( defined('IS_ICYPHOENIX') )
	{
		generate_smilies('window');
		exit;
	}
	else
	{
		generate_smilies_album('window', PAGE_ALBUM_PICTURE);
		exit;
	}
}

// ------------------------------------
// Check the request
// ------------------------------------

if( isset($_GET['pic_id']) || isset($_POST['pic_id']) )
{
	$pic_id = ( isset($_GET['pic_id']) ) ? intval($_GET['pic_id']) : intval($_POST['pic_id']);
}
else
{
	if( isset($_GET['comment_id']) || isset($_POST['comment_id']) )
	{
		$pic_id = ( isset($_GET['pic_id']) ) ? intval($_GET['pic_id']) : intval($_POST['pic_id']);
		$comment_id = (isset($_GET['comment_id'])) ? intval($_GET['comment_id']) : intval($_POST['comment_id']);
	}
	else
	{
		message_die(GENERAL_ERROR, 'Bad request');
	}
}

// Midthumb & Full Pic
if( isset($_GET['full']) || isset($_POST['full']) )
{
	$picm = false;
	$full_size_param = '&amp;full=true';
}
else
{
	if ($album_config['midthumb_use'] == 1)
	{
		$picm = true;
		$full_size_param = '';
	}
	else
	{
		$picm = false;
		$full_size_param = '&amp;full=true';
	}
}

if( isset($_GET['sort_method']) || isset($_POST['sort_method']) )
{
	$sort_method = (isset($_GET['sort_method'])) ? $_GET['sort_method'] : $_POST['sort_method'];
	$sort_method = ( ($sort_method == 'comments') || ($sort_method == 'rating') ) ? $album_config['sort_method'] : $sort_method;
}
else
{
	$sort_method = $album_config['sort_method'];
}

if( isset($_GET['sort_order']) || isset($_POST['sort_order']) )
{
	$sort_order = (isset($_GET['sort_order'])) ? $_GET['sort_order'] : $_POST['sort_order'];
}
else
{
	$sort_order = $album_config['sort_order'];
}

$sort_order = (strtoupper($sort_order) == 'DESC') ? 'DESC' : 'ASC';

$sort_append = '&amp;sort_method=' . $sort_method . '&amp;sort_order=' . $sort_order;


// ------------------------------------
// TEMPLATE ASSIGNEMENT
// ------------------------------------
if ((isset($_GET['slideshow']) && (intval($_GET['slideshow']) > 0)) || (isset($_POST['slideshow']) && (intval($_POST['slideshow']) > 0)))
{
	$gen_simple_header = true;
	$show_template = 'album_slideshow_body.tpl';
	$nuffimage_pic = ( $picm == false ) ? 'album_pic.' : 'album_picm.';
}
else
{
	//$show_template = 'album_showpage_body.tpl';
	if ( (isset($_GET['nuffimage']) || isset($_POST['nuffimage'])) & ($album_config['enable_nuffimage'] == 1) )
	{
		include($album_root_path . 'album_nuffimage_box.' . $phpEx);
		$template->assign_var_from_handle('NUFFIMAGE_BOX', 'nuffimage_box');
		$show_template = 'album_pic_nuffed_body.tpl';
		$nuffimage_vars = '&amp;nuffimage=true';
		$nuffimage_pic = 'album_pic_nuffed.';
		$nuff_http_full_string = $nuff_http['full_string'];
		$template->assign_block_vars('disable_pic_nuffed', array(
			'L_PIC_UNNUFFED_CLICK' => $lang['Nuff_UnClick'],
			'U_PIC_UNNUFFED_CLICK' => append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . $sort_append)),
			)
		);
	}
	else
	{
		$show_template = 'album_showpage_body.tpl';
		$nuffimage_vars = '';
		$nuffimage_pic = ( $picm == false ) ? 'album_pic.' : 'album_picm.';
		$nuff_http_full_string = '';
	}
}


// ------------------------------------
// PREVIOUS / NEXT / PICS NAV
// ------------------------------------

$sql = "SELECT pic_id, pic_cat_id, pic_user_id, pic_time
		FROM ". ALBUM_TABLE ."
		WHERE pic_id = $pic_id";

if( !($result = $db->sql_query($sql)) )
{
	message_die(GENERAL_ERROR, 'Could not query pic information', '', __LINE__, __FILE__, $sql);
}

$row = $db->sql_fetchrow($result);

if( empty($row) )
{
	message_die(GENERAL_ERROR, $lang['Pic_not_exist']);
}

$pic_id_old = $pic_id;
$pic_id_tmp = $row['pic_id'];
$pic_cat_id_tmp = $row['pic_cat_id'];
$pic_time_tmp = $row['pic_time'];
$pic_user_id_tmp = $row['pic_user_id'];
$db->sql_freeresult($result);

$sql_order = 'ORDER BY a.' . $sort_method . ' ' . $sort_order;
$sql = "SELECT *
		FROM " . ALBUM_TABLE . " AS a
		WHERE a.pic_cat_id = " . $pic_cat_id_tmp . "
			AND a.pic_approval = 1
		" . $sql_order;

if( !($result = $db->sql_query($sql)) )
{
	message_die(GENERAL_ERROR, 'Could not query pic information', '', __LINE__, __FILE__, $sql);
}

$total_pic_count = $db->sql_numrows($result);
$total_pic_rows = $db->sql_fetchrowset($result);
$db->sql_freeresult($result);

if ( $album_config['slideshow_script'] == 1 )
{
	$template->assign_block_vars('switch_slideshow_scripts', array());

	$pic_link = ( $picm == false ) ? 'album_pic.' : 'album_picm.';

	$pic_list = '';
	$tit_list = '';
	$des_list = '';

	for($i = 0; $i < $total_pic_count; $i++)
	{
		if ($pic_id == $total_pic_rows[$i]['pic_id'])
		{
			$pic_array_id = $i;
		}
		$pic_list .= 'Pic[' . $i . '] = \'' . append_sid(album_append_uid($pic_link . $phpEx . '?pic_id=' . $total_pic_rows[$i]['pic_id']), true) . '\'; ' . "\n";
		$tit_list .= 'Tit[' . $i . '] = \'' . str_replace('\'', '%27', $total_pic_rows[$i]['pic_title']) . '\'; ' . "\n";
		$des_list .= 'Des[' . $i . '] = \'' . str_replace('\'', '%27', $total_pic_rows[$i]['pic_desc']) . '\'; ' . "\n";
		/*
		$pic_list .= 'Pic[' . $i . '] = \'' . ALBUM_UPLOAD_PATH . $total_pic_rows[$i]['pic_filename'] . '\'; ' . "\n";
		*/
	}

	$template->assign_vars(array(
		'PIC_LIST' => $pic_list,
		'TIT_LIST' => $tit_list,
		'DES_LIST' => $des_list,
		)
	);
}
else
{
	for($i = 0; $i < $total_pic_count; $i++)
	{
		if ($pic_id == $total_pic_rows[$i]['pic_id'])
		{
			$pic_array_id = $i;
		}
	}
}

$first_pic_id = $total_pic_rows[0]['pic_id'];
$last_pic_id = $total_pic_rows[$total_pic_count - 1]['pic_id'];

if ($pic_array_id == 0)
{
	$no_prev_pic = true;
	if( isset($_GET['mode']) && ($_GET['mode'] == 'next') )
	{
		message_die(GENERAL_ERROR, $lang['Pic_not_exist']);
	}
}

if ($pic_array_id == ($total_pic_count - 1))
{
	$no_next_pic = true;
	if( isset($_GET['mode']) && ($_GET['mode'] == 'prev') )
	{
		message_die(GENERAL_ERROR, $lang['Pic_not_exist']);
	}
}

// ------------------------------------
// PREVIOUS & NEXT
// ------------------------------------
$pic_id_old = $total_pic_rows[$pic_array_id]['pic_id'];
if( isset($_GET['mode']) && ($_GET['mode'] == 'next') )
{
	$new_pic_array_id = $pic_array_id - 1;
	if ($new_pic_array_id == 0)
	{
		$no_prev_pic = true;
	}
	else
	{
		$no_prev_pic = false;
		$no_next_pic = false;
	}
}
elseif( isset($_GET['mode']) && ($_GET['mode'] == 'prev') )
{
	$new_pic_array_id = $pic_array_id + 1;
	if ($new_pic_array_id == ($total_pic_count - 1))
	{
		$no_next_pic = true;
	}
	else
	{
		$no_next_pic = false;
		$no_prev_pic = false;
	}
}
else
{
	$new_pic_array_id = $pic_array_id;
}
$pic_id_tmp = $total_pic_rows[$new_pic_array_id]['pic_id'];
$pic_cat_id_tmp = $total_pic_rows[$new_pic_array_id]['pic_cat_id'];
$pic_time_tmp = $total_pic_rows[$new_pic_array_id]['pic_time'];
$pic_user_id_tmp = $total_pic_rows[$new_pic_array_id]['pic_user_id'];
$next_pic_count = ($total_pic_count - $new_pic_array_id - 1);
$prev_pic_count = ($new_pic_array_id);

if( isset($_GET['mode']) )
{
	if ( ($_GET['mode'] == 'next') || ($_GET['mode'] == 'prev') )
	{
		$pic_id = $pic_id_tmp;
	}
}

if ($album_config['show_pics_nav'] == 1)
{
	$template->assign_block_vars('pics_nav', array(
		'L_PICS_NAV' => $lang['Pics_Nav'],
		'L_PICS_NAV_NEXT' => $lang['Pics_Nav_Next'],
		'L_PICS_NAV_PREV' => $lang['Pics_Nav_Prev'],
		)
	);
}

if ( $album_config['invert_nav_arrows'] == 0 )
{
	$max_pic_counter = min(($total_pic_count - 1), ($new_pic_array_id + 2));
	$min_pic_counter = max(0, ($new_pic_array_id - 2));
	for($i = $min_pic_counter; $i <= $max_pic_counter; $i++)
	{
		$thumbnail_file = append_sid(album_append_uid('album_thumbnail.' . $phpEx . '?pic_id=' . $total_pic_rows[$i]['pic_id']));
		if ( ($album_config['thumbnail_cache'] == true) && ($album_config['quick_thumbs'] == true) )
		{
			$pic_filename = $total_pic_rows[$i]['pic_filename'];
			$file_part = explode('.', strtolower($pic_filename));
			$pic_filetype = $file_part[count($file_part) - 1];
			$pic_title = substr($pic_filename, 0, strlen($pic_filename) - strlen($pic_filetype) - 1);
			//$pic_filetype = strtolower(substr($pic_filename, strlen($pic_filename) - 4, 4));
			//$pic_title = ucfirst(substr($pic_filename, 0, strlen($pic_filename) - 4));
			$pic_thumbnail = ( $total_pic_rows[$i]['pic_thumbnail'] == '' ) ? md5($pic_filename) . '.' . $pic_filetype : $total_pic_rows[$i]['pic_thumbnail'];
			//$pic_thumbnail = ( $total_pic_rows[$i]['pic_thumbnail'] == '' ) ? $pic_filename : $nav_pic_id[$i]['pic_thumbnail'];
			$pic_thumbnail_fullpath = ALBUM_CACHE_PATH . $pic_thumbnail;
			if ( file_exists($pic_thumbnail_fullpath) )
			{
				$thumbnail_file = $pic_thumbnail_fullpath;
			}
		}
		if ($album_config['lb_preview'] == 0)
		{
			$pic_preview = '';
		}
		else
		{
			$pic_preview = 'onmouseover="showtrail(\'' . append_sid(album_append_uid('album_picm.' . $phpEx . '?pic_id=' . $total_pic_rows[$i]['pic_id'])) . '\',\'' . addslashes($total_pic_rows[$i]['pic_title']) . '\', ' . $album_config['midthumb_width'] . ', ' . $album_config['midthumb_height'] . ')" onmouseout="hidetrail()"';
		}
		if ($album_config['show_pics_nav'] == 1)
		{
			$template->assign_block_vars('pics_nav.pics', array(
				'U_PIC_THUMB' => $thumbnail_file,
				'U_PIC_LINK' => ($i == $new_pic_array_id) ? '#' : append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $total_pic_rows[$i]['pic_id'] . $full_size_param . $nuffimage_vars . $sort_append)),
				'PIC_TITLE' => $total_pic_rows[$i]['pic_title'],
				'PIC_PREVIEW' => ($i == $new_pic_array_id) ? '' : $pic_preview,
				'STYLE' => ($i == $new_pic_array_id) ? 'border: solid 3px #FF5522;' : '',
				)
			);
		}
	}
}
else
{
	$max_pic_counter = max(0, ($new_pic_array_id - 2));
	$min_pic_counter = min(($total_pic_count - 1), ($new_pic_array_id + 2));
	for($i = $min_pic_counter; $i >= $max_pic_counter; $i--)
	{
		$thumbnail_file = append_sid(album_append_uid('album_thumbnail.' . $phpEx . '?pic_id=' . $total_pic_rows[$i]['pic_id']));
		if ( ($album_config['thumbnail_cache'] == true) && ($album_config['quick_thumbs'] == true) )
		{
			$pic_filename = $total_pic_rows[$i]['pic_filename'];
			$file_part = explode('.', strtolower($pic_filename));
			$pic_filetype = $file_part[count($file_part) - 1];
			$pic_title = substr($pic_filename, 0, strlen($pic_filename) - strlen($pic_filetype) - 1);
			//$pic_filetype = strtolower(substr($pic_filename, strlen($pic_filename) - 4, 4));
			//$pic_title = ucfirst(substr($pic_filename, 0, strlen($pic_filename) - 4));
			$pic_thumbnail = ( $total_pic_rows[$i]['pic_thumbnail'] == '' ) ? md5($pic_filename) . '.' . $pic_filetype : $total_pic_rows[$i]['pic_thumbnail'];
			//$pic_thumbnail = ( $total_pic_rows[$i]['pic_thumbnail'] == '' ) ? $pic_filename : $nav_pic_id[$i]['pic_thumbnail'];
			$pic_thumbnail_fullpath = ALBUM_CACHE_PATH . $pic_thumbnail;
			if ( file_exists($pic_thumbnail_fullpath) )
			{
				$thumbnail_file = $pic_thumbnail_fullpath;
			}
		}
		if ($album_config['lb_preview'] == 0)
		{
			$pic_preview = '';
		}
		else
		{
			$pic_preview = 'onmouseover="showtrail(\'' . append_sid(album_append_uid('album_picm.' . $phpEx . '?pic_id=' . $total_pic_rows[$i]['pic_id'])) . '\',\'' . addslashes($total_pic_rows[$i]['pic_title']) . '\', ' . $album_config['midthumb_width'] . ', ' . $album_config['midthumb_height'] . ')" onmouseout="hidetrail()"';
		}
		if ($album_config['show_pics_nav'] == 1)
		{
			$template->assign_block_vars('pics_nav.pics', array(
				'U_PIC_THUMB' => $thumbnail_file,
				'U_PIC_LINK' => ($i == $new_pic_array_id) ? '#' : append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $total_pic_rows[$i]['pic_id'] . $full_size_param . $nuffimage_vars . $sort_append)),
				'PIC_TITLE' => $total_pic_rows[$i]['pic_title'],
				'PIC_PREVIEW' => ($i == $new_pic_array_id) ? '' : $pic_preview,
				'STYLE' => ($i == $new_pic_array_id) ? 'border: solid 3px #FF5522;' : '',
				)
			);
		}
	}
}

// ------------------------------------
// SPECIAL FX
// ------------------------------------
if ($album_config['enable_nuffimage'] == 1)
{
	$template->assign_block_vars('pic_nuffed_enabled', array(
		'L_PIC_NUFFED_CLICK' => $lang['Nuff_Click'],
		'U_PIC_NUFFED_CLICK' => append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;nuffimage=true&amp;' . $sort_append)),
		)
	);
}
else
{
	$template->assign_block_vars('switch_slideshow_no_scripts', array());
}

// ------------------------------------
// Get $pic_id from $comment_id
// ------------------------------------

if( isset($comment_id) && $album_config['comment'] == 1 )
{
	$sql = "SELECT comment_id, comment_pic_id
			FROM ". ALBUM_COMMENT_TABLE ."
			WHERE comment_id = '$comment_id'";

	if( !($result = $db->sql_query($sql)) )
	{
		message_die(GENERAL_ERROR, 'Could not query comment and pic information', '', __LINE__, __FILE__, $sql);
	}

	$row = $db->sql_fetchrow($result);

	if( empty($row) )
	{
		message_die(GENERAL_ERROR, 'This comment does not exist');
	}

	$pic_id = $row['comment_pic_id'];
}

// ------------------------------------
// Get this pic info and current category info
// ------------------------------------

$sql = "SELECT p.*, ac.*, u.user_id, u.username, u.user_rank, r.rate_pic_id, AVG(r.rate_point) AS rating, COUNT( DISTINCT c.comment_id) AS comments_count
		FROM ". ALBUM_CAT_TABLE ." AS ac, ". ALBUM_TABLE ." AS p
			LEFT JOIN ". USERS_TABLE ." AS u ON p.pic_user_id = u.user_id
			LEFT JOIN ". ALBUM_COMMENT_TABLE ." AS c ON p.pic_id = c.comment_pic_id
			LEFT JOIN ". ALBUM_RATE_TABLE ." AS r ON p.pic_id = r.rate_pic_id
		WHERE pic_id = '$pic_id'
			AND ac.cat_id = p.pic_cat_id
		GROUP BY p.pic_id
		LIMIT 1";

if( !($result = $db->sql_query($sql)) )
{
	message_die(GENERAL_ERROR, 'Could not query pic information', '', __LINE__, __FILE__, $sql);
}
$thispic = $db->sql_fetchrow($result);

$cat_id = ($thispic['pic_cat_id'] != 0) ? $thispic['pic_cat_id'] : $thispic['cat_id'];
$album_user_id = $thispic['cat_user_id'];

$total_comments = $thispic['comments_count'];
$comments_per_page = $board_config['posts_per_page'];

if( empty($thispic) )
{
	message_die(GENERAL_ERROR, $lang['Pic_not_exist'] . $lang['Nav_Separator'] . $pic_id);
}

// ------------------------------------
// Check the permissions
// ------------------------------------
$check_permissions = ALBUM_AUTH_VIEW|ALBUM_AUTH_RATE|ALBUM_AUTH_COMMENT|ALBUM_AUTH_EDIT|ALBUM_AUTH_DELETE;
$auth_data = album_permissions($album_user_id, $cat_id, $check_permissions, $thispic);

if ( $auth_data['view'] == 0 )
{
	if ( !$userdata['session_logged_in'] )
	{
		redirect(append_sid(LOGIN_MG . '?redirect=album_showpage.' . $phpEx . '&amp;pic_id=' . $pic_id));
		exit;
	}
	else
	{
		message_die(GENERAL_ERROR, $lang['Not_Authorised']);
	}
}

// ------------------------------------
//RATING:  Additional Check: if this user already rated
// ------------------------------------
$own_pic_rate = false;
if( $userdata['session_logged_in'] )
{
	$sql = "SELECT *
			FROM ". ALBUM_RATE_TABLE ."
			WHERE rate_pic_id = '$pic_id'
				AND rate_user_id = '". $userdata['user_id'] ."'
			LIMIT 1";

	if( !$result = $db->sql_query($sql) )
	{
		message_die(GENERAL_ERROR, 'Could not query rating information', '', __LINE__, __FILE__, $sql);
	}

	if ($db->sql_numrows($result) > 0)
	{
		$already_rated = true;
	}
	else
	{
		$already_rated = false;
	}

	if ($thispic['pic_user_id'] == $userdata['user_id'])
	{
		$own_pic_rate = true;
	}
}
else
{
	$already_rated = false;
}

// Watch pic for comments - BEGIN
if( $userdata['session_logged_in'] )
{
	//$can_watch_comment = true;

	$sql = "SELECT notify_status
		FROM " . ALBUM_COMMENT_WATCH_TABLE . "
		WHERE pic_id = $pic_id
			AND user_id = " . $userdata['user_id'];
	if ( !($result = $db->sql_query($sql)) )
	{
		message_die(GENERAL_ERROR, "Could not obtain comment watch information", '', __LINE__, __FILE__, $sql);
	}

	if ( $row = $db->sql_fetchrow($result) )
	{
		$is_watching_comments = true;
		if ( isset($_GET['unwatch']) )
		{
			if ( $_GET['unwatch'] == 'comment' )
			{
				$sql_priority = (SQL_LAYER == 'mysql') ? 'LOW_PRIORITY' : '';
				$sql = "DELETE $sql_priority FROM " . ALBUM_COMMENT_WATCH_TABLE . "
					WHERE pic_id = $pic_id
						AND user_id = " . $userdata['user_id'];
				if ( !($result = $db->sql_query($sql)) )
				{
					message_die(GENERAL_ERROR, "Could not delete comment watch information", '', __LINE__, __FILE__, $sql);
				}
				$is_watching_comment = false;
			}

			$template->assign_vars(array('META' => '<meta http-equiv="refresh" content="5;url=' . append_sid('album.' .$phpEx) . '">'));

			$message = $lang['No_longer_watching_comment'] . '<br /><br />' . sprintf($lang['Click_return_pic'], '<a href="' . append_sid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id) . '">', '</a>');
			message_die(GENERAL_MESSAGE, $message);
		}
		else
		{
			$is_watching_comment = true;

			if ( $row['notify_status'] )
			{

				$sql_priority = (SQL_LAYER == 'mysql') ? 'LOW_PRIORITY' : '';
				$sql = "UPDATE $sql_priority " . ALBUM_COMMENT_WATCH_TABLE . "
					SET notify_status = 0
					WHERE pic_id = $pic_id
						AND user_id = " . $userdata['user_id'];
				if ( !($result = $db->sql_query($sql)) )
				{
					message_die(GENERAL_ERROR, "Could not update comment watch information", '', __LINE__, __FILE__, $sql);
				}
			}
		}
	}
	// Set pic for watch request
	if ( isset($_GET['watch']) )
	{
		if ( $_GET['watch'] == 'comment' )
		{
			$sql_priority = (SQL_LAYER == 'mysql') ? 'LOW_PRIORITY' : '';
			$sql = "INSERT $sql_priority INTO " . ALBUM_COMMENT_WATCH_TABLE . " (pic_id, user_id, notify_status)
				VALUES ($pic_id, " . $userdata['user_id'] . ", 0)";
			if ( !($result = $db->sql_query($sql)) )
			{
				message_die(GENERAL_ERROR, "Could not insert comment watch information", '', __LINE__, __FILE__, $sql);
			}
		}
		$template->assign_vars(array('META' => '<meta http-equiv="refresh" content="5;url=' . append_sid('album.' .$phpEx) . '">'));

		$message = $lang['Watching_comment'] . '<br /><br />' . sprintf($lang['Click_return_pic'], '<a href="' . append_sid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id) . '">', '</a>');
		message_die(GENERAL_MESSAGE, $message);

	}
}
// Watch pic for comments - END

/*
+----------------------------------------------------------
| Main work here...
+----------------------------------------------------------
*/
album_read_tree($album_user_id);
$album_nav_cat_desc = album_make_nav_tree($cat_id, 'album_cat.' . $phpEx, 'nav' , $album_user_id);
if ($album_nav_cat_desc != '')
{
	$album_nav_cat_desc = ALBUM_NAV_ARROW . $album_nav_cat_desc;
}

if( !isset($_POST['comment']) && !isset($_POST['rating']) )
{

	/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
					Comments Screen
	~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

	// ------------------------------------
	// Get the comments thread
	// Beware: when this script was called with comment_id (without start)
	// ------------------------------------
	if ( $album_config['comment'] == 1 )
	{
		if( !isset($comment_id) )
		{
			$start = isset($_GET['start']) ? intval($_GET['start']) : (isset($_POST['start']) ? intval($_POST['start']) : 0);
			$start = ($start < 0) ? 0 : $start;
		}
		else
		{
			// We must do a query to co-ordinate this comment
			$sql = "SELECT COUNT(comment_id) AS count
					FROM ". ALBUM_COMMENT_TABLE ."
					WHERE comment_pic_id = $pic_id
						AND comment_id < $comment_id";

			if( !$result = $db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, 'Could not obtain comments information from the database', '', __LINE__, __FILE__, $sql);
			}

			$row = $db->sql_fetchrow($result);

			if( !empty($row) )
			{
				$start = floor( $row['count'] / $comments_per_page ) * $comments_per_page;
			}
			else
			{
				$start = 0;
			}
		}

		if ($total_comments > 0)
		{
			$template->assign_block_vars('coment_switcharo_top', array());

			$limit_sql = ($start == 0) ? $comments_per_page : $start . ',' . $comments_per_page;
			$comment_sort_order = (!empty($_GET['comment_sort_order'])) ? $_GET['comment_sort_order'] : 'ASC';
			$comment_sort_order = (strtoupper($comment_sort_order) == 'DESC') ? 'DESC' : 'ASC';

			$sql = "SELECT c.*, u.*
				FROM ". ALBUM_COMMENT_TABLE ." AS c
					LEFT JOIN ". USERS_TABLE ." AS u ON c.comment_user_id = u.user_id
				WHERE c.comment_pic_id = '$pic_id'
				ORDER BY c.comment_id $comment_sort_order
				LIMIT $limit_sql";

			if( !$result = $db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, 'Could not obtain comments information from the database', '', __LINE__, __FILE__, $sql);
			}

			$commentrow = array();
			while( $row = $db->sql_fetchrow($result) )
			{
				$commentrow[] = $row;
			}
			$db->sql_freeresult($result);
			if ( defined('IS_ICYPHOENIX') )
			{
				$orig_autolink = array();
				$replacement_autolink = array();
				obtain_autolink_list($orig_autolink, $replacement_autolink, 99999999);
				if (!$userdata['user_allowswearywords'])
				{
					$orig_word = array();
					$replacement_word = array();
					obtain_word_list($orig_word, $replacement_word);
				}
			}

			//rank & rank image
			$sql = "SELECT * FROM " . RANKS_TABLE . " ORDER BY rank_special ASC, rank_min ASC";
			if ( defined('IS_ICYPHOENIX') )
			{
				if ( !($result = $db->sql_query($sql, false, 'ranks_')) )
				{
					message_die(GENERAL_ERROR, 'Could not obtain ranks information.', '', __LINE__, __FILE__, $sql);
				}
			}
			else
			{
				if ( !($result = $db->sql_query($sql)) )
				{
					message_die(GENERAL_ERROR, 'Could not obtain ranks information.', '', __LINE__, __FILE__, $sql);
				}
			}

			$ranksrow = array();
			while ( $row = $db->sql_fetchrow($result) )
			{
				$ranksrow[] = $row;
			}
			$db->sql_freeresult($result);

			for ($i = 0; $i < count($commentrow); $i++)
			{
				if( ($commentrow[$i]['user_id'] == ALBUM_GUEST) || ($commentrow[$i]['username'] == '') )
				{
					if ( defined('IS_ICYPHOENIX') )
					{
						$poster = ($commentrow[$i]['comment_username'] == '') ? $lang['Guest'] : colorize_username($commentrow[$i]['user_id']);
					}
					else
					{
						$poster = ($commentrow[$i]['comment_username'] == '') ? $lang['Guest'] : $commentrow[$i]['comment_username'];
					}
				}
				else
				{
					if ( defined('IS_ICYPHOENIX') )
					{
						$poster = colorize_username($commentrow[$i]['user_id']);
					}
					else
					{
						$poster = '<a href="' . append_sid(PROFILE_MG . '?mode=viewprofile&amp;' . POST_USERS_URL . '=' . $commentrow[$i]['user_id']) . '">' . $commentrow[$i]['username'] . '</a>';
					}
				}

				if ($commentrow[$i]['comment_edit_count'] > 0)
				{
					$sql = "SELECT c.comment_id, c.comment_edit_user_id, u.user_id, u.username
							FROM ". ALBUM_COMMENT_TABLE ." AS c
								LEFT JOIN ". USERS_TABLE ." AS u ON c.comment_edit_user_id = u.user_id
							WHERE c.comment_id = '".$commentrow[$i]['comment_id']."'
							LIMIT 1";

					if( !$result = $db->sql_query($sql) )
					{
						message_die(GENERAL_ERROR, 'Could not obtain last edit information from the database', '', __LINE__, __FILE__, $sql);
					}

					$lastedit_row = $db->sql_fetchrow($result);

					$edit_info = ($commentrow[$i]['comment_edit_count'] == 1) ? $lang['Edited_time_total'] : $lang['Edited_times_total'];

					if ( defined('IS_ICYPHOENIX') )
					{
						$edit_info = '<br /><br />&raquo;&nbsp;'. sprintf($edit_info, colorize_username($lastedit_row['user_id']), create_date2($board_config['default_dateformat'], $commentrow[$i]['comment_edit_time'], $board_config['board_timezone']), $commentrow[$i]['comment_edit_count']) .'<br />';
					}
					else
					{
						$edit_info = '<br /><br />&raquo;&nbsp;'. sprintf($edit_info, $lastedit_row['username'], create_date($board_config['default_dateformat'], $commentrow[$i]['comment_edit_time'], $board_config['board_timezone']), $commentrow[$i]['comment_edit_count']) .'<br />';
					}
				}
				else
				{
					$edit_info = '';
				}

				// Smilies
				if ( defined('IS_ICYPHOENIX') )
				{
					global $bbcode;
					$html_on = ( $userdata['user_allowhtml'] && $board_config['allow_html'] ) ? 1 : 0 ;
					$bbcode_on = ( $userdata['user_allowbbcode'] && $board_config['allow_bbcode'] ) ? 1 : 0 ;
					$smilies_on = ( $userdata['user_allowsmile'] && $board_config['allow_smilies'] ) ? 1 : 0 ;
					$bbcode->allow_html = $html_on;
					$bbcode->allow_bbcode = $bbcode_on;
					$bbcode->allow_smilies = $smilies_on;

					$commentrow[$i]['comment_text'] = $bbcode->parse($commentrow[$i]['comment_text'], $bbcode_uid);
					$commentrow[$i]['comment_text'] = strtr($commentrow[$i]['comment_text'], array_flip(get_html_translation_table(HTML_ENTITIES)));

					if( function_exists('acronym_pass') )
					{
						$commentrow[$i]['comment_text'] = acronym_pass($commentrow[$i]['comment_text']);
					}
					if( count($orig_autolink) )
					{
						$commentrow[$i]['comment_text'] = autolink_transform($commentrow[$i]['comment_text'], $orig_autolink, $replacement_autolink);
					}
					//$commentrow[$i]['comment_text'] = kb_word_wrap_pass ($commentrow[$i]['comment_text']);
					$commentrow[$i]['comment_text'] = ( count($orig_word) ) ? preg_replace($orig_word, $replacement_word, $commentrow[$i]['comment_text']) : $commentrow[$i]['comment_text'];

					$user_sig = ( $board_config['allow_sig'] ) ? trim($commentrow[$i]['user_sig']) : '';
					$user_sig_bbcode_uid = $commentrow[$i]['user_sig_bbcode_uid'];
					if($user_sig != '')
					{
						$bbcode->is_sig = ( $board_config['allow_all_bbcode'] == 0 ) ? true : false;
						$user_sig = $bbcode->parse($user_sig, $user_sig_bbcode_uid);
						$bbcode->is_sig = false;
					}

				}
				else
				{
					if ($album_config['album_bbcode'] == 1)
					{
						global $bbcode;
						$html_on = ( $userdata['user_allowhtml'] && $board_config['allow_html'] ) ? 1 : 0 ;
						$bbcode_on = ( $userdata['user_allowbbcode'] && $board_config['allow_bbcode'] ) ? 1 : 0 ;
						$smilies_on = ( $userdata['user_allowsmile'] && $board_config['allow_smilies'] ) ? 1 : 0 ;
						$bbcode->allow_html = $html_on;
						$bbcode->allow_bbcode = $bbcode_on;
						$bbcode->allow_smilies = $smilies_on;

						$commentrow[$i]['comment_text'] = $bbcode->parse($commentrow[$i]['comment_text'], $bbcode_uid);
						$commentrow[$i]['comment_text'] = strtr($commentrow[$i]['comment_text'], array_flip(get_html_translation_table(HTML_ENTITIES)));

						$user_sig = ( $board_config['allow_sig'] ) ? trim($commentrow[$i]['user_sig']) : '';
						$user_sig_bbcode_uid = $commentrow[$i]['user_sig_bbcode_uid'];
						if($user_sig != '')
						{
							//$bbcode->is_sig = true;
							$user_sig = $bbcode->parse($user_sig, $user_sig_bbcode_uid);
							//$bbcode->is_sig = false;
						}
					}
				}

				//email, profile, pm links
				if ( ($commentrow[$i]['user_viewemail'] == true) || ($userdata['user_level'] == ADMIN) )
				{
					$email_uri = ( $board_config['board_email_form'] ) ? append_sid(PROFILE_MG . '?mode=email&amp;' . POST_USERS_URL .'=' . $commentrow[$i]['user_id']) : 'mailto:' . $commentrow[$i]['user_email'];
				}
				else
				{
					$email_uri = '';
				}
				$profile_url = append_sid(PROFILE_MG . '?mode=viewprofile&amp;' . POST_USERS_URL . '=' . $commentrow[$i]['user_id']);
				$pm_url = append_sid('privmsg.' . $phpEx . '?mode=post&amp;' . POST_USERS_URL . '=' . $commentrow[$i]['user_id']);

				//avatar
				if ( defined('IS_ICYPHOENIX') )
				{
					$poster_avatar = user_get_avatar($commentrow[$i]['user_id'], $commentrow[$i]['user_avatar'],	$commentrow[$i]['user_avatar_type'], $commentrow[$i]['user_allowavatar']);
				}
				else
				{
					$poster_avatar = '';
					if ( $commentrow[$i]['user_avatar_type'] && $commentrow[$i]['user_id'] != ANONYMOUS && $commentrow[$i]['user_allowavatar'] )
					{
						switch( $commentrow[$i]['user_avatar_type'] )
						{
							case USER_AVATAR_UPLOAD:
								$poster_avatar = ( $board_config['allow_avatar_upload'] ) ? '<img src="' . $board_config['avatar_path'] . '/' . $commentrow[$i]['user_avatar'] . '" alt="" style="border:0px;vertical-align:middle;" />' : '';
								break;
							case USER_AVATAR_REMOTE:
								$poster_avatar = ( $board_config['allow_avatar_remote'] ) ? '<img src="' . $commentrow[$i]['user_avatar'] . '" alt="" style="border:0px;vertical-align:middle;" />' : '';
								break;
							case USER_AVATAR_GALLERY:
								$poster_avatar = ( $board_config['allow_avatar_local'] ) ? '<img src="' . $board_config['avatar_gallery_path'] . '/' . $commentrow[$i]['user_avatar'] . '" alt="" style="border:0px;vertical-align:middle;" />' : '';
								break;
						}
					}
				}

				$poster_rank = '&nbsp;';
				$rank_image = '';
				if ($commentrow[$i]['user_id'] == ANONYMOUS)
				{
					$poster_rank = $lang['Guest'];
				}
				elseif ( $commentrow[$i]['user_rank'] )
				{
					for($j = 0; $j < count($ranksrow); $j++)
					{
						if ( $commentrow[$i]['user_rank'] == $ranksrow[$j]['rank_id'] && $ranksrow[$j]['rank_special'] )
						{
							$poster_rank = $ranksrow[$j]['rank_title'];
							$rank_image = ( $ranksrow[$j]['rank_image'] ) ? '<img src="' . $ranksrow[$j]['rank_image'] . '" alt="' . $poster_rank . '" title="' . $poster_rank . '" style="border:0px;vertical-align:middle;" /><br />' : '';
						}
					}
				}
				else
				{
					for($j = 0; $j < count($ranksrow); $j++)
					{
						if ( $commentrow[$i]['user_posts'] >= $ranksrow[$j]['rank_min'] && !$ranksrow[$j]['rank_special'] )
						{
							$poster_rank = $ranksrow[$j]['rank_title'];
							$rank_image = ( $ranksrow[$j]['rank_image'] ) ? '<img src="' . $ranksrow[$j]['rank_image'] . '" alt="' . $poster_rank . '" title="' . $poster_rank . '" style="border:0px;vertical-align:middle;" /><br />' : '';
						}
					}
				}

				// Handle anon users posting with usernames
				if (($commentrow[$i]['user_id'] == ANONYMOUS) && ($commentrow[$i]['post_username'] != ''))
				{
					if (defined('IS_ICYPHOENIX'))
					{
						$poster = colorize_username($commentrow[$i]['user_id']);
					}
					else
					{
						$poster = $commentrow[$i]['post_username'];
					}
					$poster_rank = $lang['Guest'];
				}

				if (defined('IS_ICYPHOENIX'))
				{
					if (($userdata['user_level'] == ADMIN) || ($userdata['user_id'] == $poster_id) || $commentrow[$i]['user_allow_viewonline'])
					{
						if ($commentrow[$i]['user_session_time'] >= (time() - $board_config['online_time']))
						{
							$online_status_img = '<a href="' . append_sid('viewonline.' . $phpEx) . '"><img src="' . $images['icon_online2'] . '" alt="' . $lang['Online'] .'" title="' . $lang['Online'] .'" /></a>';
						}
						else
						{
							$online_status_img = '<img src="' . $images['icon_offline2'] . '" alt="' . $lang['Offline'] .'" title="' . $lang['Offline'] .'" />';
						}
					}
					else
					{
						$online_status_img = '<a href="' . append_sid('viewonline.' . $phpEx) . '"><img src="' . $images['icon_hidden2'] . '" alt="' . $lang['Hidden'] .'" title="' . $lang['Hidden'] .'" /></a>';
					}
					if ($userdata['user_level'] == ADMIN)
					{
						$ip_img = '<a href="http://www.nic.com/cgi-bin/whois.cgi?query=' . decode_ip($commentrow[$i]['comment_user_ip']) . '" target="_blank"><img src="' . $images['icon_ip2'] . '" alt="' . $lang['View_IP'] . ' (' . decode_ip($commentrow[$i]['comment_user_ip']) . ')" title="' . $lang['View_IP'] . ' (' . decode_ip($commentrow[$i]['comment_user_ip']) . ')" /></a>';
						$ip = '<a href="' . $temp_url . '">' . $lang['View_IP'] . '</a>';
					}
					else
					{
						$ip_img = '';
						$ip = '';
					}
					$icq_status_img = (!empty($commentrow[$i]['user_icq'])) ? '<a href="http://wwp.icq.com/' . $commentrow[$i]['user_icq'] . '#pager"><img src="http://web.icq.com/whitepages/online?icq=' . $commentrow[$i]['user_icq'] . '&img=5" width="18" height="18" /></a>' : '';
					$icq_img = (!empty($commentrow[$i]['user_icq'])) ? build_im_link('icq', $commentrow[$i]['user_icq'], $lang['ICQ'], $images['icon_icq2']) : '';
					$icq = (!empty($commentrow[$i]['user_icq'])) ? build_im_link('icq', $commentrow[$i]['user_icq'], $lang['ICQ'], false) : '';

					$aim_img = (!empty($commentrow[$i]['user_aim'])) ? build_im_link('aim', $commentrow[$i]['user_aim'], $lang['AIM'], $images['icon_aim2']) : '';
					$aim = (!empty($commentrow[$i]['user_aim'])) ? build_im_link('aim', $commentrow[$i]['user_aim'], $lang['AIM'], false) : '';

					$msn_img = (!empty($commentrow[$i]['user_msnm'])) ? build_im_link('msn', $commentrow[$i]['user_msnm'], $lang['MSNM'], $images['icon_msnm2']) : '';
					$msn = (!empty($commentrow[$i]['user_msnm'])) ? build_im_link('msn', $commentrow[$i]['user_msnm'], $lang['MSNM'], false) : '';

					$yim_img = (!empty($commentrow[$i]['user_yim'])) ? build_im_link('yahoo', $commentrow[$i]['user_yim'], $lang['YIM'], $images['icon_yim2']) : '';
					$yim = (!empty($commentrow[$i]['user_yim'])) ? build_im_link('yahoo', $commentrow[$i]['user_yim'], $lang['YIM'], false) : '';

					$skype_img = (!empty($commentrow[$i]['user_skype'])) ? build_im_link('skype', $commentrow[$i]['user_skype'], $lang['SKYPE'], $images['icon_skype2']) : '';
					$skype = (!empty($commentrow[$i]['user_skype'])) ? build_im_link('skype', $commentrow[$i]['user_skype'], $lang['SKYPE'], false) : '';
				}
				else
				{
					if (!empty($commentrow[$i]['user_icq']))
					{
						$icq_status_img = '<a href="http://wwp.icq.com/' . $commentrow[$i]['user_icq'] . '#pager"><img src="http://web.icq.com/whitepages/online?icq=' . $commentrow[$i]['user_icq'] . '&img=5" width="18" height="18" /></a>';
						$icq_img = '<a href="http://wwp.icq.com/scripts/search.dll?to=' . $commentrow[$i]['user_icq'] . '"><img src="' . $images['icon_icq'] . '" alt="' . $lang['ICQ'] . '" title="' . $lang['ICQ'] . '" /></a>';
						$icq =  '<a href="http://wwp.icq.com/scripts/search.dll?to=' . $commentrow[$i]['user_icq'] . '">' . $lang['ICQ'] . '</a>';
					}
					else
					{
						$icq_status_img = '';
						$icq_img = '';
						$icq = '';
					}

					$aim_img = ( $commentrow[$i]['user_aim'] ) ? '<a href="aim:goim?screenname=' . $commentrow[$i]['user_aim'] . '&amp;message=Hello+Are+you+there?"><img src="' . $images['icon_aim2'] . '" alt="' . $lang['AIM'] . '" title="' . $lang['AIM'] . '" /></a>' : '';
					$aim = ( $commentrow[$i]['user_aim'] ) ? '<a href="aim:goim?screenname=' . $commentrow[$i]['user_aim'] . '&amp;message=Hello+Are+you+there?">' . $lang['AIM'] . '</a>' : '';

					$temp_url = append_sid(PROFILE_MG . '?mode=viewprofile&amp;' . POST_USERS_URL . "=" . $commentrow[$i]['user_id']);
					$msn_img = ( $commentrow[$i]['user_msnm'] ) ? '<a href="' . $temp_url . '"><img src="' . $images['icon_msnm2'] . '" alt="' . $lang['MSNM'] . '" title="' . $lang['MSNM'] . '" /></a>' : '';
					$msn = ( $commentrow[$i]['user_msnm'] ) ? '<a href="' . $temp_url . '">' . $lang['MSNM'] . '</a>' : '';

					$yim_img = ( $commentrow[$i]['user_yim'] ) ? '<a href="http://edit.yahoo.com/config/send_webmesg?.target=' . $commentrow[$i]['user_yim'] . '&amp;.src=pg"><img src="' . $images['icon_yim2'] . '" alt="' . $lang['YIM'] . '" title="' . $lang['YIM'] . '" /></a>' : '';
					$yim = ( $commentrow[$i]['user_yim'] ) ? '<a href="http://edit.yahoo.com/config/send_webmesg?.target=' . $commentrow[$i]['user_yim'] . '&amp;.src=pg">' . $lang['YIM'] . '</a>' : '';

					$skype_img = '';
					$skype = '';

					$lang['JOINED_DATE_FORMAT'] = $lang['DATE_FORMAT'];
				}

				$template->assign_block_vars('commentrow', array(
					'ID' => $commentrow[$i]['comment_id'],
					'POSTER_NAME' => $poster,
					'COMMENT_TIME' => create_date2($board_config['default_dateformat'], $commentrow[$i]['comment_time'], $board_config['board_timezone']),
					'IP' => ($userdata['user_level'] == ADMIN) ? '<a href="http://www.nic.com/cgi-bin/whois.cgi?query=' . decode_ip($commentrow[$i]['comment_user_ip']) . '" target="_blank">' . decode_ip($commentrow[$i]['comment_user_ip']) .'</a><br />' : '',
					'IP_IMG' => $ip_img,
					'POSTER_ONLINE_STATUS_IMG' => $online_status_img,

					//users mesangers, website, email
					'PROFILE_IMG' => ( $commentrow[$i]['user_id'] != ANONYMOUS ) ? '<a href="' . $profile_url . '"><img src="' . $images['icon_profile'] . '" alt="' . $lang['Read_profile'] . '" title="' . $lang['Read_profile'] . '" style="border:0px;" /></a>' : '',
					'PM_IMG' => ( $commentrow[$i]['user_id'] != ANONYMOUS ) ? '<a href="' . $pm_url . '"><img src="' . $images['icon_pm'] . '" alt="' . $lang['Send_private_message'] . '" title="' . $lang['Send_private_message'] . '" style="border:0px;" /></a>' : '',
					'AIM_IMG' => ($commentrow[$i]['user_id'] != ANONYMOUS) ? $aim_img : '',
					'YIM_IMG' => ($commentrow[$i]['user_id'] != ANONYMOUS) ? $yim_img : '',
					'MSNM_IMG' => ($commentrow[$i]['user_id'] != ANONYMOUS) ? $msn_img : '',
					'ICQ_IMG' => ($commentrow[$i]['user_id'] != ANONYMOUS) ? $icq_img : '',
					'SKYPE_IMG' => ($commentrow[$i]['user_id'] != ANONYMOUS) ? $skype_img : '',
					'EMAIL_IMG' => ( ($commentrow[$i]['user_id'] != ANONYMOUS) && ($email_uri != '') ) ? '<a href="' . $email_uri . '"><img src="' . $images['icon_email'] . '" alt="' . $lang['Send_email'] . '" title="' . $lang['Send_email'] . '" style="border:0px;" /></a>' : '',
					'WWW_IMG' => ( $commentrow[$i]['user_id'] != ANONYMOUS ) ? ( $commentrow[$i]['user_website'] ) ? '<a href="' . $commentrow[$i]['user_website'] . '" target="_blank"><img src="' . $images['icon_www'] . '" alt="' . $lang['Visit_website'] . '" title="' . $lang['Visit_website'] . '" style="border:0px;" /></a>' : '' : '',

					'POSTER_AVATAR' => $poster_avatar,
					'POSTER_RANK' => $poster_rank,
					'POSTER_RANK_IMAGE' => $rank_image,
					'POSTER_JOINED' => ( $commentrow[$i]['user_id'] != ANONYMOUS ) ? $lang['Joined'] . ': ' . create_date($lang['JOINED_DATE_FORMAT'], $commentrow[$i]['user_regdate'], $board_config['board_timezone']) : '',
					'POSTER_POSTS' => ( $commentrow[$i]['user_id'] != ANONYMOUS ) ? $lang['Posts'] . ': ' . $commentrow[$i]['user_posts'] : '',
					'POSTER_FROM' => ( $commentrow[$i]['user_from'] && $commentrow[$i]['user_id'] != ANONYMOUS ) ? $lang['Location'] . ': ' . $commentrow[$i]['user_from'] : '',
					'POSTER_SIGNATURE' => $user_sig,

					'TEXT' => $commentrow[$i]['comment_text'],
					'EDIT_INFO' => $edit_info,

					'EDIT' => ( ( $auth_data['edit'] && ($commentrow[$i]['comment_user_id'] == $userdata['user_id']) ) || ($auth_data['moderator'] && ($thispic['cat_edit_level'] != ALBUM_ADMIN) ) || ($userdata['user_level'] == ADMIN) ) ? '<a href="'. append_sid(album_append_uid('album_comment_edit.' . $phpEx . '?comment_id=' . $commentrow[$i]['comment_id'])) .'"><img src="' . $images['icon_edit'] . '" alt="' . $lang['Edit_delete_post'] . '" title="' . $lang['Edit_delete_post'] . '" style="border:0px;" /></a>' : '',

					'DELETE' => ( ( $auth_data['delete'] && ($commentrow[$i]['comment_user_id'] == $userdata['user_id']) ) || ($auth_data['moderator'] && ($thispic['cat_delete_level'] != ALBUM_ADMIN) ) || ($userdata['user_level'] == ADMIN) ) ? '<a href="'. append_sid(album_append_uid('album_comment_delete.' . $phpEx . '?comment_id=' . $commentrow[$i]['comment_id'])) .'"><img src="' . $images['icon_delpost'] . '" alt="' . $lang['Delete_post'] . '" title="' . $lang['Delete_post'] . '" style="border:0px;" /></a>' : ''

					)
				);
			}
		}
	}

	// Start output of page
	$page_title = $lang['Album'];
	$meta_description = $lang['Album'] . ' - ' . $thispic['cat_title'] . ' - ' . $thispic['pic_title'] . ' - ' . $thispic['pic_desc'];
	$meta_keywords = $lang['Album'] . ', ' . $thispic['cat_title'] . ', ' . $thispic['pic_title'] . ', ' . $thispic['pic_desc'] . ', ';

	include($phpbb_root_path . 'includes/page_header.' . $phpEx);
	$template->set_filenames(array('body' => $show_template));

	if ( defined('IS_ICYPHOENIX') )
	{
		if( ($thispic['pic_user_id'] == ALBUM_GUEST) || ($thispic['username'] == '') )
		{
			$poster = ($thispic['pic_username'] == '') ? $lang['Guest'] : colorize_username($thispic['user_id']);
		}
		else
		{
			$poster = colorize_username($thispic['user_id']);
		}
	}
	else
	{
		if( ($thispic['pic_user_id'] == ALBUM_GUEST) || ($thispic['username'] == '') )
		{
			$poster = ($thispic['pic_username'] == '') ? $lang['Guest'] : $thispic['pic_username'];
		}
		else
		{
			$poster = '<a href="' . append_sid(PROFILE_MG . '?mode=viewprofile&amp;'. POST_USERS_URL . '=' . $thispic['user_id']) . '">' . $thispic['username'] . '</a>';
		}
	}


	//---------------------------------
	// Comment Posting Form
	//---------------------------------

	if ( ($auth_data['comment'] == 1) && ($album_config['comment'] == 1) )
	{
		$template->assign_block_vars('switch_comment_post', array());

		if( !$userdata['session_logged_in'] )
		{
			$template->assign_block_vars('switch_comment_post.logout', array());
		}
		if ( defined('IS_ICYPHOENIX') )
		{
		}
		else
		{
			//begin shows smilies
			$max_smilies = 20;

			$sql = 'SELECT emoticon, code, smile_url
							FROM ' . SMILIES_TABLE . '
							GROUP BY smile_url
							ORDER BY smilies_id LIMIT ' . $max_smilies;

			if (!$result = $db->sql_query($sql))
			{
				message_die(GENERAL_ERROR, "Couldn't retrieve smilies list", '', __LINE__, __FILE__, $sql);
			}
			$smilies_count = $db->sql_numrows($result);
			$smilies_data = $db->sql_fetchrowset($result);

			for ($i = 1; $i < ($smilies_count + 1); $i++)
			{
				$template->assign_block_vars('switch_comment_post.smilies', array(
						'CODE' => $smilies_data[$i - 1]['code'],
						'URL' => $board_config['smilies_path'] . '/' . $smilies_data[$i - 1]['smile_url'],
						'DESC' => $smilies_data[$i - 1]['emoticon']
					)
				);

				if ( is_integer($i / 5) )
				{
					$template->assign_block_vars('switch_comment_post.smilies.new_col', array());
				}
			}
		}
	}

	// Rating System
	if ( $album_config['rate'] == 1 )
	{
		$image_rating = ImageRating($thispic['rating']);
		$template->assign_block_vars('rate_switch', array());

		if ( $auth_data['rate'] == 1 && ($already_rated == false) && (($own_pic_rate == false) || ($userdata['user_level'] == ADMIN)) )
		{
			$template->assign_block_vars('rate_switch.rate_row', array());
			for ( $i = 0; $i < $album_config['rate_scale']; $i++ )
			{
				$template->assign_block_vars('rate_switch.rate_row.rate_scale_row', array(
					'POINT' => ($i + 1)
					)
				);
			}
		}
	}

	// Mighty Gorgon - Slideshow - BEGIN
	if ( ((isset($_GET['slideshow']) && (intval($_GET['slideshow']) > 0)) || (isset($_POST['slideshow']) && (intval($_POST['slideshow']) > 0))) )
	{
		$template->assign_block_vars('switch_slideshow', array());
		$slideshow_delay = (isset($_GET['slideshow']) ? intval($_GET['slideshow']) : intval($_POST['slideshow']));
		$slideshow_select = '';
		$slideshow_onoff = $lang['Slideshow_Off'];
		$slideshow_link = append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id));
		$slideshow_link_full = '<a href="' . $slideshow_link . '">' . $lang['Slideshow_Off'] . '</a>';
		$pic_link = append_sid(album_append_uid($nuffimage_pic . $phpEx . '?pic_id=' . $pic_id));
		if ( $album_config['invert_nav_arrows'] == 0 )
		{
			$next_pic = ($no_prev_pic == false) ? '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=next' . $nuffimage_vars . $sort_append)) . '#TopPic"><img src="' . $images['icon_left_arrow3'] . '" title="' . $lang['Next_Pic'] . '" style="border:0px;" alt="' . $lang['Next_Pic'] . '" align="middle" /></a>' : '';
			$prev_pic = ($no_next_pic == false) ? '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=prev' . $nuffimage_vars . $sort_append)) . '#TopPic"><img src="' . $images['icon_right_arrow3'] . '" title="' . $lang['Prev_Pic'] . '" style="border:0px;" alt="' . $lang['Prev_Pic'] . '" align="middle" /></a>' : '';

			$next_pic_url = ($no_prev_pic == false) ? append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=next&amp;slideshow=' . $slideshow_delay . $sort_append)) . '#TopPic' : append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $first_pic_id . $full_size_param . $sort_append)) . '#TopPic';
			$prev_pic_url = ($no_next_pic == false) ? append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=prev&amp;slideshow=' . $slideshow_delay . $sort_append)) . '#TopPic' : append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $last_pic_id . $full_size_param . $sort_append)) . '#TopPic';
		}
		else
		{
			$next_pic = ($no_next_pic== false) ? '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=prev' . $nuffimage_vars . $sort_append)) . '#TopPic"><img src="' . $images['icon_left_arrow3'] . '" title="' . $lang['Prev_Pic'] . '" style="border:0px;" alt="' . $lang['Prev_Pic'] . '" align="middle" /></a>' : '';
			$prev_pic = ($no_prev_pic  == false) ? '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=next' . $nuffimage_vars . $sort_append)) . '#TopPic"><img src="' . $images['icon_right_arrow3'] . '" title="' . $lang['Next_Pic'] . '" style="border:0px;" alt="' . $lang['Next_Pic'] . '" align="middle" /></a>' : '';

			$next_pic_url = ($no_next_pic == false) ? append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=prev&amp;slideshow=' . $slideshow_delay . $sort_append)) . '#TopPic' : append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $first_pic_id . $full_size_param . $sort_append)) . '#TopPic';
			$prev_pic_url = ($no_prev_pic == false) ? append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=next&amp;slideshow=' . $slideshow_delay . $sort_append)) . '#TopPic' : append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $last_pic_id . $full_size_param . $sort_append)) . '#TopPic';
		}
	}
	else
	{
		if ($album_config['show_slideshow'] == 1)
		{
			$template->assign_block_vars('switch_slideshow_enabled', array());
		}
		//$slideshow_delay = 5;
		$slideshow_select = $lang['Slideshow_Delay'] . ':&nbsp;';
		$slideshow_select .= '<select name="slideshow">';
		$slideshow_select .= '<option value="1">1 Sec</option>';
		$slideshow_select .= '<option value="3">3 Sec</option>';
		$slideshow_select .= '<option value="5" selected="selected">5 Sec</option>';
		$slideshow_select .= '<option value="7">7 Sec</option>';
		$slideshow_select .= '<option value="10">10 Sec</option>';
		$slideshow_select .= '</select>&nbsp;';
		$slideshow_onoff = $lang['Slideshow_On'];
		//$slideshow_link = append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . '&amp;full=true&amp;slideshow=' . $slideshow_delay));
		$slideshow_link = append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . '&amp;full=true'));
		$slideshow_link_full = '<a href="' . $slideshow_link . '">' . $lang['Slideshow_On'] . '</a>';
		$pic_link = append_sid(album_append_uid($nuffimage_pic . $phpEx . '?pic_id=' . $pic_id . $sort_append . $full_size_param . $nuff_http_full_string));
		if ( $album_config['invert_nav_arrows'] == 0 )
		{
			$next_pic = ($no_prev_pic == false) ? '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=next' . $nuffimage_vars . $sort_append)) . '#TopPic"><img src="' . $images['icon_left_arrow3'] . '" title="' . $lang['Next_Pic'] . '" style="border:0px;" alt="' . $lang['Next_Pic'] . '" align="middle" /></a>' : '';
			$prev_pic = ($no_next_pic == false) ? '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=prev' . $nuffimage_vars . $sort_append)) . '#TopPic"><img src="' . $images['icon_right_arrow3'] . '" title="' . $lang['Prev_Pic'] . '" style="border:0px;" alt="' . $lang['Prev_Pic'] . '" align="middle" /></a>' : '';
		}
		else
		{
			$next_pic = ($no_next_pic== false) ? '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=prev' . $nuffimage_vars . $sort_append)) . '#TopPic"><img src="' . $images['icon_left_arrow3'] . '" title="' . $lang['Prev_Pic'] . '" style="border:0px;" alt="' . $lang['Prev_Pic'] . '" align="middle" /></a>' : '';
			$prev_pic = ($no_prev_pic  == false) ? '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . $full_size_param . '&amp;mode=next' . $nuffimage_vars . $sort_append)) . '#TopPic"><img src="' . $images['icon_right_arrow3'] . '" title="' . $lang['Next_Pic'] . '" style="border:0px;" alt="' . $lang['Next_Pic'] . '" align="middle" /></a>' : '';
		}
	}

	//$temp_js = '<script type="text/javascript">window.attachEvent(\'onload\', runSlideShow();)</script>';
	if ( $album_config['slideshow_script'] == 1 )
	{
		$slideshow_refresh = '</body><body onload="runSlideShow()">';
		//$slideshow_refresh = $temp_js;
	}
	else
	{
		$slideshow_refresh = '</body><head><meta http-equiv="refresh" content="' . $slideshow_delay .  ';url=' . $next_pic_url . '"></head><body>';
	}
	// Mighty Gorgon - Slideshow - END

	// Mighty Gorgon - Pic Size - BEGIN
	$pic_size = @getimagesize(ALBUM_UPLOAD_PATH . $thispic['pic_filename']);
	$pic_width = $pic_size[0];
	$pic_height = $pic_size[1];
	$pic_filesize = filesize(ALBUM_UPLOAD_PATH . $thispic['pic_filename']);
	// Mighty Gorgon - Pic Size - END

	if ( ($album_config['show_exif'] == 1) && (function_exists(exif_read_data)) )
	{
		//echo(function_exists(exif_read_data));
		$template->assign_block_vars('switch_exif_enabled', array());
		$xif = @exif_read_data(ALBUM_UPLOAD_PATH . $thispic['pic_filename'], 0, true);
		if (!empty($xif[IFD0]) || !empty($xif[EXIF]))
		{
			include_once($album_root_path . 'album_exif_info.' . $phpEx);
		}
	}

	$server_protocol = ( $board_config['cookie_secure'] ) ? 'https://' : 'http://';
	$server_name = trim($board_config['server_name']);
	$server_port = ( $board_config['server_port'] <> 80 ) ? ':' . trim($board_config['server_port']) . '/' : '/';
	$script_name = preg_replace('/^\/?(.*?)\/?$/', '\1', trim($board_config['script_path']));
	$script_name = ( $script_name == '' ) ? '' : $script_name . '/';
	$server_path = $server_protocol . $server_name . $server_port . $script_name;

	$thumbnail_file = append_sid(album_append_uid('album_thumbnail.' . $phpEx . '?pic_id=' . $pic_id));
	if ( ($album_config['thumbnail_cache'] == true) && ($album_config['quick_thumbs'] == true) )
	{
		$pic_filename = $thispic['pic_filename'];
		$file_part = explode('.', strtolower($pic_filename));
		$pic_filetype = $file_part[count($file_part) - 1];
		$pic_title = substr($pic_filename, 0, strlen($pic_filename) - strlen($pic_filetype) - 1);
		//$pic_filetype = strtolower(substr($pic_filename, strlen($pic_filename) - 4, 4));
		//$pic_title = ucfirst(substr($pic_filename, 0, strlen($pic_filename) - 4));
		$pic_thumbnail = ( $thispic['pic_thumbnail'] == '' ) ? md5($pic_filename) . '.' . $pic_filetype : $thispic['pic_thumbnail'];
		//$pic_thumbnail = ( $thispic['pic_thumbnail'] == '' ) ? $pic_filename : $thispic['pic_thumbnail'];
		$pic_thumbnail_fullpath = ALBUM_CACHE_PATH . $pic_thumbnail;
		if ( file_exists($pic_thumbnail_fullpath) )
		{
			$thumbnail_file = $pic_thumbnail_fullpath;
		}
	}

	$edit_link_content = $lang['Edit_pic'];
	$delete_link_content = $lang['Delete_pic'];
	$lock_link_content = ($thispic['pic_lock'] == 0) ? $lang['Lock'] : $lang['Unlock'];
	$move_link_content = $lang['Move'];
	$copy_link_content = $lang['Copy'];
	if ( defined('IS_ICYPHOENIX') )
	{
		$style_used = explode('/', $template->files['body']);
		$allowed_styles = array(
			//'ca_aphrodite',
			'mg_themes',
		);
		if( in_array($style_used[2], $allowed_styles) && (!empty($template->xs_version)) )
		{
			$edit_link_content = '<img src="' . $images['icon_edit'] . '" alt="' . $lang['Edit_pic'] . '" title="' . $lang['Edit_pic'] . '" style="border:0px;" />';
			$delete_link_content = '<img src="' . $images['topic_mod_delete'] . '" alt="' . $lang['Delete_pic'] . '" title="' . $lang['Delete_pic'] . '" style="border:0px;" />';
			$lock_link_content = ($thispic['pic_lock'] == 0) ? '<img src="' . $images['topic_mod_lock'] . '" alt="' . $lang['Lock'] . '" title="' . $lang['Lock'] . '" style="border:0px;" />' : '<img src="' . $images['topic_mod_unlock'] . '" alt="' . $lang['Unlock'] . '" title="' . $lang['Unlock'] . '" style="border:0px;" />';
			$move_link_content = '<img src="' . $images['topic_mod_move'] . '" alt="' . $lang['Move'] . '" title="' . $lang['Move'] . '" style="border:0px;" />';
			$copy_link_content = '<img src="' . $images['topic_mod_split'] . '" alt="' . $lang['Copy'] . '" title="' . $lang['Copy'] . '" style="border:0px;" />';
		}
	}

	$template->assign_vars(array(
		'CAT_TITLE' => $thispic['cat_title'],
		'U_VIEW_CAT' => append_sid(album_append_uid('album_cat.' . $phpEx . '?cat_id=' . $cat_id)),
		'ALBUM_NAVIGATION_ARROW' => ALBUM_NAV_ARROW,
		'NAV_CAT_DESC' => $album_nav_cat_desc,
		'EDIT' => ( ($auth_data['moderator']) || ($userdata['user_id'] == $thispic['pic_user_id']) ) ? '<a href="' . append_sid(album_append_uid('album_edit.' . $phpEx . '?pic_id=' . $thispic['pic_id'])) . '">' . $edit_link_content . '</a>' : '',
		'DELETE' => ( ($auth_data['moderator']) || ($userdata['user_id'] == $thispic['pic_user_id']) ) ? '<a href="' . append_sid(album_append_uid('album_delete.' . $phpEx . '?pic_id=' . $thispic['pic_id'])) . '">' . $delete_link_content . '</a>' : '',
		'LOCK' => ($auth_data['moderator']) ? '<a href="' . append_sid(album_append_uid('album_modcp.' . $phpEx . '?mode=' . (($thispic['pic_lock'] == 0) ? 'lock' : 'unlock') . '&amp;pic_id=' . $thispic['pic_id'])) . '">' . $lock_link_content . '</a>' : '',
		'MOVE' => ($auth_data['moderator']) ? '<a href="' . append_sid(album_append_uid('album_modcp.' . $phpEx . '?mode=move&amp;pic_id=' . $thispic['pic_id'])) . '">' . $move_link_content . '</a>' : '',
		'COPY' => ($auth_data['moderator']) ? '<a href="'. append_sid(album_append_uid('album_modcp.' . $phpEx . '?mode=copy&amp;pic_id=' . $thispic['pic_id'])) . '">' . $copy_link_content . '</a>' : '',

		'U_PIC_FULL_URL' => $server_path . ALBUM_UPLOAD_PATH . $thispic['pic_filename'],

		//'U_PIC' => append_sid(album_append_uid($nuffimage_pic . $phpEx . '?pic_id=' . $pic_id . $sort_append . $full_size_param . $nuff_http_full_string)),
		'U_PIC' => $pic_link,
		//'U_PIC_L1' => ( $picm == false ) ? '' : '<a href="album_showpage.' . $phpEx . '?full=true&amp;pic_id=' . $pic_id . $nuffimage_vars . '">',
		'U_PIC_L1' => ( $picm == false ) ? '' : '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?full=true&amp;pic_id=' . $pic_id . $sort_append . $nuffimage_vars)) . '">',
		'U_PIC_L2' => ( $picm == false ) ? '' : '</a>',
		'U_PIC_CLICK' => ( $picm == false ) ? '' : $lang['Click_enlarge'],
		'U_PIC_THUMB' => append_sid(album_append_uid('album_thumbnail.' . $phpEx . '?pic_id=' . $pic_id . $sort_append)),
		'U_SMILEY_CREATOR' => append_sid('smiley_creator.' . $phpEx . '?mode=text2shield'),

		'PIC_COUNT' => sprintf($lang['Pics_Counter'], ($new_pic_array_id + 1), $total_pic_count),

		'NEXT_PIC' => $next_pic,
		'PREV_PIC' => $prev_pic,

		// Mighty Gorgon - Slideshow - BEGIN
		'L_SLIDESHOW' => $lang['Slideshow'],
		'L_SLIDESHOW_DELAY' => $lang['Slideshow_Delay'],
		'L_SLIDESHOW_ONOFF' => $slideshow_onoff,
		'SLIDESHOW_SELECT' => $slideshow_select,
		'SLIDESHOW_DELAY' => $slideshow_delay,
		'U_SLIDESHOW' => $slideshow_link,
		'U_SLIDESHOW_FULL' => $slideshow_link_full,
		'U_SLIDESHOW_REFRESH' => $slideshow_refresh,
		'U_SLIDESHOW_REFRESH_META' => '<meta http-equiv="refresh" content="' . $slideshow_delay .  ';url=' . $next_pic_url . '">',
		// Mighty Gorgon - Slideshow - END

		// Mighty Gorgon - Pic Size - BEGIN
		'L_PIC_DETAILS' => $lang['Pic_Details'],
		'L_PIC_SIZE' => $lang['Pic_Size'],
		'L_PIC_TYPE' => $lang['Pic_Type'],
		'PIC_SIZE' => $pic_width . ' x ' . $pic_height . ' (' . intval($pic_filesize/1024) . 'KB)',
		'PIC_TYPE' => strtoupper(substr($thispic['pic_filename'], strlen($thispic['pic_filename']) - 3, 3)),
		// Mighty Gorgon - Pic Size - END

		//'PIC_RATING' => $image_rating . ( ($already_rated == true) ? ('&nbsp;(' . $lang['Already_rated'] . ')') : ''),
		'PIC_RATING' => $image_rating . ( ($own_pic_rate == true) ? '&nbsp;(' . $lang['Own_Pic_Rate'] . ')' : ( ($already_rated == true) ? ('&nbsp;(' . $lang['Already_rated'] . ')') : '') ),

		'PIC_ID' => $pic_id,
		'PIC_BBCODE' => '[albumimg]' . $pic_id . '[/albumimg]',
		'PIC_TITLE' => $thispic['pic_title'],
		'PIC_DESC' => nl2br($thispic['pic_desc']),

		'POSTER' => $poster,

		'PIC_TIME' => create_date2($board_config['default_dateformat'], $thispic['pic_time'], $board_config['board_timezone']),
		'PIC_VIEW' => $thispic['pic_view_count'],
		'PIC_COMMENTS' => $total_comments,

		'TARGET_BLANK' => ($album_config['fullpic_popup']) ? 'target="_blank"' : '',

		'L_PIC_ID' => $lang['Pic_ID'],
		'L_PIC_BBCODE' => $lang['Pic_BBCode'],
		'L_PIC_TITLE' => $lang['Pic_Image'],
		'L_PIC_DESC' => $lang['Pic_Desc'],
		'L_POSTER' => $lang['Pic_Poster'],
		'L_POSTED' => $lang['Posted'],
		'L_VIEW' => $lang['Views'],
		'L_COMMENTS' => $lang['Comments'],
		'L_RATING' => $lang['Rating'],

		'L_POST_YOUR_COMMENT' => $lang['Post_your_comment'],
		'L_MESSAGE' => $lang['Message'],
		'L_USERNAME' => $lang['Username'],
		'L_COMMENT_NO_TEXT' => $lang['Comment_no_text'],
		'L_COMMENT_TOO_LONG' => $lang['Comment_too_long'],
		'L_MAX_LENGTH' => $lang['Max_length'],
		'S_MAX_LENGTH' => $album_config['desc_length'],

		'L_ORDER' => $lang['Order'],
		'L_SORT' => $lang['Sort'],
		'L_ASC' => $lang['Sort_Ascending'],
		'L_DESC' => $lang['Sort_Descending'],
		'L_BACK_TO_TOP' => $lang['Back_to_top'],
		'L_COMMENT_WATCH' =>$lang['Pic_comment_notification'],

		'SORT_ASC' => ($sort_order == 'ASC') ? 'selected="selected"' : '',
		'SORT_DESC' => ($sort_order == 'DESC') ? 'selected="selected"' : '',

		'L_SUBMIT' => $lang['Submit'],

		'S_ALBUM_ACTION' => append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id)),

		'U_COMMENT_WATCH_LINK' =>($is_watching_comments) ? '<a href="' . append_sid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . '&amp;unwatch=comment') . '">' . $lang['Unwatch_pic'] . '</a>' : ($userdata['session_logged_in'] ? '<a href="' . append_sid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . '&amp;watch=comment') . '">' . $lang['Watch_pic'] . '</a>' : ''),

		// Rating
		//'S_RATE_MSG' => ( !$userdata['session_logged_in'] && $auth_data['rate'] == 0 ) ? $lang['Login_To_Vote'] : ( ( $already_rated ) ? $lang['Already_rated'] : $lang['Please_Rate_It'] ),
		'S_RATE_MSG' => ( !$userdata['session_logged_in'] && $auth_data['rate'] == 0 ) ? $lang['Login_To_Vote'] : ( ($own_pic_rate == true) ? $lang['Own_Pic_Rate'] : ( ($already_rated == true) ? $lang['Already_rated'] : $lang['Please_Rate_It']) ),
		'PIC_RATING' => $image_rating . ( ($own_pic_rate == true) ? '&nbsp;(' . $lang['Own_Pic_Rate'] . ')' : ( ($already_rated == true) ? ('&nbsp;(' . $lang['Already_rated'] . ')') : '') ),
		'L_CURRENT_RATING' => $lang['Current_Rating'],
		'L_PLEASE_RATE_IT' => $lang['Please_Rate_It']
		)
	);

	// Social Bookmarks
	if ( defined('IS_ICYPHOENIX') )
	{
		if ( $board_config['show_social_bookmarks'] == true )
		{
			$template->assign_block_vars('social_bookmarks', array());
		}
		$topic_title_enc = urlencode(utf8_decode($thispic['pic_title']));
		$topic_url_enc = urlencode(utf8_decode(create_server_url() . 'album_showpage.' . $phpEx . '?pic_id=' . $thispic['pic_id'] . $full_size_param . '&amp;mode=prev' . $nuffimage_vars . $sort_append));
		$template->assign_vars(array(
			// Social Bookmarks - BEGIN
			'TOPIC_TITLE_ENC' => $topic_title_enc,
			'TOPIC_URL_ENC' => $topic_url_enc,
			'L_SHARE_TOPIC' => $lang['ShareThisTopic'],
			// Social Bookmarks - END
			)
		);
	}

	if ( defined('IS_ICYPHOENIX') )
	{
		// BBCBMG - BEGIN
		//$bbcbmg_in_acp = true;
		include_once($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_bbcb_mg.' . $phpEx);
		include($phpbb_root_path . 'includes/bbcb_mg.' . $phpEx);
		$template->assign_var_from_handle('BBCB_MG', 'bbcb_mg');
		// BBCBMG - END
		// BBCBMG SMILEYS - BEGIN
		generate_smilies('inline');
		include($phpbb_root_path . 'includes/bbcb_smileys_mg.' . $phpEx);
		$template->assign_var_from_handle('BBCB_SMILEYS_MG', 'bbcb_smileys_mg');
		// BBCBMG SMILEYS - END
	}

	if (($album_config['comment'] == 1) && ($total_comments > 0))
	{
		$template->assign_vars(array(
			'PAGINATION' => generate_pagination(append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id . '&amp;sort_order=' . $sort_order)), $total_comments, $comments_per_page, $start),
			'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $comments_per_page ) + 1 ), ceil( $total_comments / $comments_per_page ))
			)
		);
		$template->assign_block_vars('switch_comment', array());
		$template->assign_block_vars('comment_switcharo_bottom', array());
	}

	// Generate the page
	$template->pparse('body');

	include($phpbb_root_path . 'includes/page_tail.' . $phpEx);
}
else
{
	/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
				Comment Or Rate Submited
	~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

	// ------------------------------------
	// Check the permissions: COMMENT
	// ------------------------------------

	if ( $album_config['comment'] == 0 && $album_config['rate'] == 0 )
	{
		message_die(GENERAL_ERROR, $lang['Not_Authorised']);
	}
	if ( $auth_data['comment'] == 0 && $auth_data['rate'] == 0 )
	{
		if ( !$userdata['session_logged_in'] )
		{
			redirect(append_sid(LOGIN_MG . '?redirect=album_showpage.' . $phpEx . '&amp;pic_id=' . $pic_id));
		}
		else
		{
			message_die(GENERAL_ERROR, $lang['Not_Authorised']);
		}
	}

	// Comment System
	if ( $album_config['comment'] == 1 && $auth_data['comment'] == 1 )
	{
		$comment_text = str_replace("\'", "''", htmlspecialchars(substr(trim($_POST['comment']), 0, $album_config['desc_length'])));

		$comment_username = (!$userdata['session_logged_in']) ? str_replace("\'", "''", substr(htmlspecialchars(trim($_POST['comment_username'])), 0, 32)) : str_replace("'", "''", htmlspecialchars(trim($userdata['username'])));

		// Check Pic Locked
		if( ($thispic['pic_lock'] == 1) && (!$auth_data['moderator']) )
		{
			message_die(GENERAL_ERROR, $lang['Pic_Locked']);
		}

		// Check username for guest posting
		if (!$userdata['session_logged_in'])
		{
			if ($comment_username != '')
			{
				$result = validate_username($comment_username);
				if ( $result['error'] )
				{
					message_die(GENERAL_MESSAGE, $result['error_msg']);
				}
			}
		}


		// Prepare variables
		$comment_time = time();
		$comment_user_id = $userdata['user_id'];
		$comment_user_ip = $userdata['session_ip'];

		// Get $comment_id
		$sql = "SELECT MAX(comment_id) AS max
				FROM ". ALBUM_COMMENT_TABLE;

		if( !$result = $db->sql_query($sql) )
		{
			message_die(GENERAL_ERROR, 'Could not found comment_id', '', __LINE__, __FILE__, $sql);
		}

		$row = $db->sql_fetchrow($result);

		$comment_id = $row['max'] + 1;

		// Insert into DB
		// If user only rated, but didn't enter a comment... only update rating
		if ( $comment_text != '' )
		{
			$sql = "INSERT INTO " . ALBUM_COMMENT_TABLE ." (comment_id, comment_pic_id, comment_cat_id, comment_user_id, comment_username, comment_user_ip, comment_time, comment_text)
					VALUES ('$comment_id', '$pic_id', '$cat_id', '$comment_user_id', '$comment_username', '$comment_user_ip', '$comment_time', '$comment_text')";
			if( !$result = $db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, 'Could not insert new entry', '', __LINE__, __FILE__, $sql);
			}
			// Watch pic for comments - BEGIN
			// Here we send email notification
			album_comment_notify($pic_id);
			// Watch pic for comments - END
			$message = $lang['Stored'] . '<br /><br />';
		}
	}

	// Rating System
	if ( ($album_config['rate'] == 1) && ($auth_data['rate'] == 1) && ($userdata['session_logged_in']) )
	{
		// Check Pic Locked
		if( ($thispic['pic_lock'] == 1) && (!$auth_data['moderator']) )
		{
			message_die(GENERAL_ERROR, $lang['Pic_Locked']);
		}

		//$rate_point = intval($_POST['rating']);

		if (isset($_POST['rating']))
		{
			$rate_point = intval($_POST['rating']);
		}
		elseif (isset($_GET['rating']))
		{
			$rate_point = intval($_GET['rating']);
		}
		else
		{
			$rate_point = -1;
		}

		if ($rate_point != -1)//if user didnt vote, dont update database
		{
			if( ($rate_point <= 0) || ($rate_point > $album_config['rate_scale']) )
			{
				message_die(GENERAL_ERROR, 'Bad submitted value - ' . $rate_point);
			}

			$rate_user_id = $userdata['user_id'];
			$rate_user_ip = $userdata['session_ip'];

			$sql = "INSERT INTO " . ALBUM_RATE_TABLE . " (rate_pic_id, rate_user_id, rate_user_ip, rate_point)
					VALUES ('$pic_id', '$rate_user_id', '$rate_user_ip', '$rate_point')";
			if( !$result = $db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, 'Could not insert new rating', '', __LINE__, __FILE__, $sql);
			}
			$message = $lang['Album_rate_successfully'] . '<br /><br />';
		}
	}

	// --------------------------------
	// Complete... now send a message to user
	// --------------------------------

	$template->assign_vars(array(
		'META' => '<meta http-equiv="refresh" content="3;url=' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id)) . '">'
		)
	);

	$message .= sprintf($lang['Click_return_pic'], '<a href="' . append_sid(album_append_uid('album_showpage.' . $phpEx . '?pic_id=' . $pic_id)) . '">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_album_index'], '<a href="' . append_sid('album.' . $phpEx) . '">', '</a>');

	message_die(GENERAL_MESSAGE, $message);
}

?>