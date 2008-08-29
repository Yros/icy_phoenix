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
* masterdavid - Ronald John David
*
*/

if (!defined('IN_PHPBB'))
{
	die('Hacking attempt');
}
if(!function_exists(imp_kb_func))
{
	function imp_kb_func()
	{
		global $phpbb_root_path, $template, $cms_config_vars, $block_id, $table_prefix, $phpEx, $db, $lang, $board_config, $theme, $images, $userdata;

		@include_once($phpbb_root_path . ATTACH_MOD_PATH . 'displaying.' . $phpEx);
		@include_once($phpbb_root_path . 'includes/functions_groups.' . $phpEx);
		@include_once($phpbb_root_path . 'fetchposts.' . $phpEx);

		$bbcode->allow_html = true;
		$bbcode->allow_bbcode = true;
		$bbcode->allow_smilies = true;

		$template->_tpldata['kb_list.'] = array();
		//reset($template->_tpldata['kb_list.']);
		$template->_tpldata['kb_article.'] = array();
		//reset($template->_tpldata['kb_article.']);
		$template->_tpldata['cat_row.'] = array();
		//reset($template->_tpldata['cat_row.']);
		$template->_tpldata['menu_row.'] = array();
		//reset($template->_tpldata['menu_row.']);

		$template->set_filenames(array('kb_block' => 'blocks/kb_block.tpl'));

		$template->assign_vars(array(
			'L_COMMENTS' => $lang['Comments'],
			'L_VIEW_COMMENTS' => $lang['View_comments'],
			'L_POST_COMMENT' => $lang['Post_your_comment'],
			'L_POSTED' => $lang['Posted'],
			'L_ANNOUNCEMENT' => $lang['Post_Announcement'],
			'L_REPLIES' => $lang['Replies'],
			'L_REPLY_ARTICLE' => $lang['Article_Reply'],
			'L_PRINT_ARTICLE' => $lang['Article_Print'],
			'L_EMAIL_ARTICLE' => $lang['Article_Email'],
			'L_TOPIC' => $lang['Topic'],
			'L_ARTICLES' => $lang['Articles'],
			'L_TIME' => $lang['Articles_time'],
			'L_OPTIONS' => $lang['Articles_options'],
			'MINIPOST_IMG' => $images['icon_minipost'],
			'ARTICLE_COMMENTS_IMG' => $images['vf_topic_nor'],
			'ARTICLE_REPLY_IMG' => $images['news_reply'],
			'ARTICLE_PRINT_IMG' => $images['news_print'],
			'ARTICLE_EMAIL_IMG' => $images['news_email'],
			)
		);

		if(isset($_GET['kb']) && ($_GET['kb'] == 'article'))
		{
			$template->assign_block_vars('kb_article', array());

			$forum_id = (isset($_GET['f'])) ? intval($_GET['f']) : intval($_POST['f']);

			$fetchposts = phpbb_fetch_posts($forum_id, 0, 0);

			$id = (isset($_GET[POST_TOPIC_URL])) ? intval($_GET[POST_TOPIC_URL]) : intval($_POST[POST_TOPIC_URL]);
			$i = 0;

			while($fetchposts[$i]['topic_id'] <> $id)
			{
				$i++;
			}

			init_display_post_attachments($fetchposts[$i]['topic_attachment'], $fetchposts[$i], true, $block_id);

			$template->assign_vars(array(
				'TOPIC_ID' => $fetchposts[$i]['topic_id'],
				'TITLE' => $fetchposts[$i]['topic_title'],
				'TOPIC_DESC' => $fetchposts[$i]['topic_desc'],
				'POSTER' => $fetchposts[$i]['username'],
				'POSTER_CG' => colorize_username($fetchposts[$i]['user_id']),
				'TIME' => $fetchposts[$i]['topic_time'],
				'TEXT' => $fetchposts[$i]['post_text'],
				'REPLIES' => $fetchposts[$i]['topic_replies'],

				'U_VIEW_COMMENTS' => append_sid(VIEWTOPIC_MG . '?' . POST_FORUM_URL . '=' . $forum_id . '&amp;' . POST_TOPIC_URL . '=' . $fetchposts[$i]['topic_id'], true),
				'U_POST_COMMENT' => append_sid('posting.' . $phpEx . '?mode=reply&amp;' . POST_FORUM_URL . '=' . $forum_id . '&amp;' . POST_TOPIC_URL . '=' . $fetchposts[$i]['topic_id']),
				'U_PRINT_TOPIC' => append_sid('printview.' . $phpEx . '?' . POST_FORUM_URL . '=' . $forum_id . '&amp;' . POST_TOPIC_URL . '=' . $fetchposts[$i]['topic_id'] . '&amp;start=0'),
				'U_EMAIL_TOPIC' => append_sid('tellafriend.' . $phpEx . '?topic=' . urlencode(utf8_decode($fetchposts[$i]['topic_title'])) . '&amp;link=' . urlencode(utf8_decode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?kb=article&' . POST_FORUM_URL . '=' . $forum_id . '&' . POST_TOPIC_URL . '=' . $fetchposts[$i]['topic_id'])), true),
				)
			);
			display_attachments($fetchposts[$i]['post_id'], 'articles_fp');

		}
		else
		{
			if(isset($_GET['kb']) && ($_GET['kb'] == 'category'))
			{
				$template->assign_block_vars('kb_list', array());

				$forum_id = (isset($_GET['f'])) ? intval($_GET['f']) : intval($_POST['f']);

				$fetchposts = phpbb_fetch_posts($forum_id, 0, 0);

				for ($i = 0; $i < count($fetchposts); $i++)
				{
					init_display_post_attachments($fetchposts[$i]['topic_attachment'], $fetchposts[$i], true, $block_id);
					$template->assign_block_vars('kb_list.kb_articles', array(
						'TOPIC_ID' => $fetchposts[$i]['topic_id'],
						'TOPIC_TITLE' => $fetchposts[$i]['topic_title'],
						'TOPIC_DESC' => $fetchposts[$i]['topic_desc'],
						'POSTER' => $fetchposts[$i]['username'],
						'POSTER_CG' => colorize_username($fetchposts[$i]['user_id']),
						'TIME' => $fetchposts[$i]['topic_time'],
						'REPLIES' => $fetchposts[$i]['topic_replies'],
						'U_VIEW_ARTICLE' => append_sid($_SERVER['PHP_SELF'] . '?kb=article&f=' . $forum_id . '&' . POST_TOPIC_URL . '=' . $fetchposts[$i]['topic_id'], true),

						'U_VIEW_COMMENTS' => append_sid(VIEWTOPIC_MG . '?' . POST_FORUM_URL . '=' . $forum_id . '&amp;' . POST_TOPIC_URL . '=' . $fetchposts[$i]['topic_id'], true),
						'U_POST_COMMENT' => append_sid('posting.' . $phpEx . '?mode=reply&amp;' . POST_FORUM_URL . '=' . $forum_id . '&amp;' . POST_TOPIC_URL . '=' . $fetchposts[$i]['topic_id']),
						'U_PRINT_TOPIC' => append_sid('printview.' . $phpEx . '?' . POST_FORUM_URL . '=' . $forum_id . '&amp;' . POST_TOPIC_URL . '=' . $fetchposts[$i]['topic_id'] . '&amp;start=0'),
						'U_EMAIL_TOPIC' => append_sid('tellafriend.' . $phpEx . '?topic=' . urlencode(utf8_decode($fetchposts[$i]['topic_title'])) . '&amp;link=' . urlencode(utf8_decode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?kb=article&' . POST_FORUM_URL . '=' . $forum_id . '&' . POST_TOPIC_URL . '=' . $fetchposts[$i]['topic_id'])), true),
						)
					);

					display_attachments($fetchposts[$i]['post_id'], 'articles_fp');
				}
				$template->assign_vars(array(
					'TITLE' => $lang['Kb_name'],
					)
				);
			}
			else
			{

				$template->assign_block_vars('cat_row', array());

				$sql = "SELECT * FROM " . CMS_NAV_MENU_TABLE . "
							WHERE menu_id = '" . intval($cms_config_vars['kb_cat_id'][$block_id]) . "'
							LIMIT 1";
				if (!($result = $db->sql_query($sql, false, 'dyn_menu_')))
				{
					message_die(GENERAL_ERROR, 'Could not query dynamic menu table');
				}
				//$row = $db->sql_fetchrow($result);
				while ($row = $db->sql_fetchrow($result))
				{
					break;
				}

				if (($row['menu_name_lang'] != '') && isset($lang[$row['menu_name_lang']]))
				{
					$main_menu_name = $lang[$row['menu_name_lang']];
				}
				else
				{
					$main_menu_name = (($row['menu_name'] != '') ? $row['menu_name'] : $lang['quick_links']) ;
				}

				$sql = "SELECT * FROM " . CMS_NAV_MENU_TABLE . "
							WHERE menu_parent_id = '" . intval($cms_config_vars['kb_cat_id'][$block_id]) . "'
							ORDER BY cat_parent_id ASC, menu_order ASC";
				if (!($result = $db->sql_query($sql, false, 'dyn_menu_')))
				{
					message_die(GENERAL_ERROR, 'Could not query dynamic menu table');
				}

				$menu_cat = array();
				$cat_item = array();
				$menu_item = array();
				while ($menu_item = $db->sql_fetchrow($result))
				{
					if ($menu_item['cat_id'] > 0)
					{
						$cat_item[$menu_item['cat_id']] = $menu_item;
					}
					if ($menu_item['cat_parent_id'] > 0)
					{
						$menu_cat[$menu_item['cat_parent_id']][$menu_item['menu_item_id']] = $menu_item;
					}
				}

				foreach($cat_item as $cat_item_data)
				{
					if ($cat_item_data['menu_status'] == false)
					{
						$cat_allowed = false;
					}
					else
					{
						$cat_allowed = true;
						$auth_level_req = $cat_item_data['auth_view'];
						switch($auth_level_req)
						{
							case '0':
								$cat_allowed = true;
								break;
							case '1':
								$cat_allowed = ($userdata['session_logged_in'] ? false : true);
								break;
							case '2':
								$cat_allowed = ($userdata['session_logged_in'] ? true : false);
								break;
							case '3':
								$cat_allowed = ((($userdata['user_level'] == MOD) || ($userdata['user_level'] == ADMIN)) ? true : false);
								break;
							case '4':
								$cat_allowed = (($userdata['user_level'] == ADMIN)? true : false);
								break;
							default:
								$cat_allowed = true;
								break;
						}
					}

					if ($cat_allowed == true)
					{
						//echo($cat_item_data['menu_name'] . '<br />');
						$cat_id = ($cat_item_data['cat_id']);
						if (($cat_item_data['menu_name_lang'] != '') && isset($lang[$cat_item_data['menu_name_lang']]))
						{
							$cat_name = $lang[$cat_item_data['menu_name_lang']];
						}
						else
						{
							$cat_name = (($cat_item_data['menu_name'] != '') ? stripslashes($cat_item_data['menu_name']) : 'cat_item' . $cat_item_data['cat_id']) ;
						}
						$cat_icon = (($cat_item_data['menu_icon'] != '') ? '<img src="' . $cat_item_data['menu_icon'] . '" alt="" title="' . $cat_name . '" style="vertical-align:middle;" />&nbsp;&nbsp;' : '<img src="' . $images['nav_menu_sep'] . '" alt="" title="" style="vertical-align:middle;" />&nbsp;&nbsp;');
						//$cat_icon = (($cat_item_data['menu_icon'] != '') ? '<img src="' . $cat_item_data['menu_icon'] . '" alt="" title="' . $cat_name . '" style="vertical-align:middle;" />&nbsp;&nbsp;' : '&nbsp;');
						if ($cat_item_data['menu_link'] != '')
						{
							$cat_link = append_sid($cat_item_data['menu_link']);
							if ($cat_item_data['menu_link_external'] == true)
							{
								$cat_link .= '" target="_blank';
							}
						}

						$template->assign_block_vars('cat_row', array(
							'CAT_ID' => $cat_item_data['cat_id'],
							'CAT_ITEM' => $cat_name,
							'CAT_ICON' => $cat_icon,
							)
						);

						foreach($menu_cat[$cat_id] as $menu_cat_item_data)
						{

							if ($menu_cat_item_data['menu_status'] == false)
							{
								$menu_allowed = false;
							}
							else
							{
								$menu_allowed = true;
								$auth_level_req = $menu_cat_item_data['auth_view'];
								switch($auth_level_req)
								{
									case '0':
										$menu_allowed = true;
										break;
									case '1':
										$menu_allowed = ($userdata['session_logged_in'] ? false : true);
										break;
									case '2':
										$menu_allowed = ($userdata['session_logged_in'] ? true : false);
										break;
									case '3':
										$menu_allowed = ((($userdata['user_level'] == MOD) || ($userdata['user_level'] == ADMIN)) ? true : false);
										break;
									case '4':
										$menu_allowed = (($userdata['user_level'] == ADMIN)? true : false);
										break;
									default:
										$menu_allowed = true;
										break;
								}
							}

							if ($menu_allowed == true)
							{
								//echo($menu_cat_item_data['menu_name'] . '<br />');
								if (($menu_cat_item_data['menu_name_lang'] != '') && isset($lang[$menu_cat_item_data['menu_name_lang']]))
								{
									$menu_name = $lang[$menu_cat_item_data['menu_name_lang']];
								}
								else
								{
									$menu_name = (($menu_cat_item_data['menu_name'] != '') ? stripslashes($menu_cat_item_data['menu_name']) : 'cat_item' . $menu_cat_item_data['cat_id']) ;
								}
								if ($menu_cat_item_data['menu_link_external'] == true)
								{
									$menu_link .= '" target="_blank';
									$menu_link = $menu_cat_item_data['menu_link'];
								}
								else
								{
									$menu_link = append_sid($menu_cat_item_data['menu_link']);
								}
								$menu_icon = (($menu_cat_item_data['menu_icon'] != '') ? '<img src="' . $menu_cat_item_data['menu_icon'] . '" alt="" title="' . $menu_name . '" style="vertical-align:middle;" />' : '<img src="' . $images['nav_menu_sep'] . '" alt="" title="" style="vertical-align:middle;" />');
								$menu_desc = $menu_cat_item_data['menu_desc'];

								$template->assign_block_vars('cat_row.menu_row', array(
									'MENU_ITEM' => $menu_name,
									'MENU_LINK' => $menu_link,
									'MENU_ICON' => $menu_icon,
									'MENU_DESC' => $menu_desc,
									)
								);
							}
						}
					}
				}

				$template->assign_vars(array(
					'TITLE' => $lang['Kb_name'],
					)
				);
			}
		}
	}

}

imp_kb_func();

?>