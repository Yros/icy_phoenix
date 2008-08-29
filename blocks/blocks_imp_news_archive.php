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

if(!function_exists(imp_news_archive_block_func))
{
	function imp_news_archive_block_func()
	{
		global $phpbb_root_path, $phpEx, $lang, $template, $board_config, $db, $bbcode, $block_id, $cms_config_var, $cms_config_vars;
		@include_once($phpbb_root_path . ATTACH_MOD_PATH . 'displaying.' . $phpEx);
		@include_once($phpbb_root_path . 'includes/news.' . $phpEx);

		//$template->_tpldata['news_detail.'] = array();
		//reset($template->_tpldata['news_detail.']);
		//$template->_tpldata['newscol.'] = array();
		//reset($template->_tpldata['newscol.']);
		$template->_tpldata['articles_fp.'] = array();
		//reset($template->_tpldata['articles_fp.']);
		$template->_tpldata['news_categories.'] = array();
		//reset($template->_tpldata['news_categories.']);
		$template->_tpldata['newsrow.'] = array();
		//reset($template->_tpldata['newsrow.']);
		$template->_tpldata['news_archives.'] = array();
		//reset($template->_tpldata['news_archives.']);
		$template->_tpldata['arch.'] = array();
		//reset($template->_tpldata['arch.']);
		$template->_tpldata['yes_news.'] = array();
		//reset($template->_tpldata['yes_news.']);
		$template->_tpldata['no_news.'] = array();
		//reset($template->_tpldata['no_news.']);

		$img_width = ($cms_config_vars['md_news_images_width'][$block_id] < 20) ? '' : ('width="' . $cms_config_vars['md_news_images_width'][$block_id] . '"');

		//$index_file = PORTAL_MG;
		$index_file = (!empty($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
		//$page_query = $_SERVER['QUERY_STRING'];
		//$page_query = (!empty($_SERVER['QUERY_STRING'])) ? explode('&', $_SERVER['QUERY_STRING']) : explode('&', getenv('QUERY_STRING'));
		$portal_page_id = '';
		if(isset($_GET['page']))
		{
			$portal_page_id = 'page=' . intval($_GET['page']) . '&amp;';
		}

		$ubid_link = '';
		if(isset($_GET['ubid']))
		{
			$ubid_link = 'ubid=' . htmlspecialchars(intval($_GET['ubid'])) . '&amp;';
		}

		$template->set_filenames(array('news' => 'blocks/news_archive_block.tpl'));

		$content =& new NewsModule($phpbb_root_path);

		$content->setVariables(array(
			'INDEX_FILE' => htmlspecialchars(urldecode($index_file)),
			'PORTAL_PAGE_ID' => $portal_page_id . $ubid_link,
			'L_INDEX' => $lang['Index'],
			'L_CATEGORIES' => $lang['Categories'],
			'L_NO_NEWS_CATS' => $lang['No_News_Cats'],
			'L_NO_NEWS' => $lang['No_News'],
			'L_NEWS_CATEGORIES' => $lang['News_Categories'],
			'L_ALL_NEWS_CATEGORIES' => $lang['All_News_Categories'],
			'L_NEWS_ARCHIVES' => $lang['News_Archives'],
			'L_ALL_NEWS_ARCHIVES' => $lang['All_News_Archives'],
			'L_NEWS_SUMMARY' => $lang['News_Summary'],
			'L_NEWS_VIEWS' => $lang['News_Views'],
			'L_NEWS_CATS' => $lang['News_Cats'],
			'L_ARCHIVES' => $lang['Archives']
			)
		);

		if ($cms_config_vars['md_news_archive_type'][$block_id] == 1)
		{
			// View the news categories.
			$data_access = new NewsDataAccess($phpbb_root_path);
			$news_cats = $data_access->fetchCategories();
			$template->assign_block_vars('news_categories', array());
			$cats = count($news_cats);

			if ($cats == 0)
			{
				$template->assign_block_vars('no_news', array());
			}
			else
			{
				$template->assign_block_vars('yes_news', array());
			}
			for ($i = 0; $i < count($news_cats); $i++)
			{
				$img_w = (!empty($cms_config_vars['md_news_images_width'][$block_id]) ? (' width="' . $cms_config_vars['md_news_images_width'][$block_id] . '"') : '');
				$template->assign_block_vars('newsrow', array(
					//'THUMBNAIL' => $N_this->root_path . 'images/news/' . $news_cats[$j]['news_image'],
					'THUMBNAIL' => $N_this->root_path . $board_config['news_path'] . '/' . $news_cats[$i]['news_image'],
					'ID' => $news_cats[$i]['news_id'],
					'DESC' => $news_cats[$i]['news_category'],
					'NEWSCAT' => $news_cats[$i]['news_category'],
					'CATEGORY' => $newsrow[$i]['news_category'],
					'IMG_W' => $img_w,
					)
				);
			}
			$content->setVariables(array('TITLE' => $lang['News_Cmx'] . ' ' . $lang['Categories']));
			$content->renderTopics();
		}
		else
		{
			// View the news Archives.
			$year = (isset($_GET['year'])) ? $_GET['year'] : 0;
			$month = (isset($_GET['month'])) ? $_GET['month'] : 0;
			$day = (isset($_GET['day'])) ? $_GET['day'] : 0;
			$key = (isset($_GET['key'])) ? $_GET['key'] : '';

			$template->assign_block_vars('news_archives', array());
			$content->setVariables(array('TITLE' => $lang['News_Cmx'] . ' ' . $lang['Archives']));
			$content->renderArchives($year, $month, $day, $key, false);
		}
	}
}

imp_news_archive_block_func();

?>