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
* CodeMonkeyX.net (webmaster@codemonkeyx.net)
*
*/

if (!defined('IN_PHPBB'))
{
	die('Hacking attempt');
}

/**
 * PhpBB Data Access class use to retrieve news posts from the phpBB
 * database.
 *
 * @author Nicholas Young-Soares <programs@codemonkeyx.net>
 * @see http://www.codemonkeyx.net
 */
function get_user_news_auth_access($forum_topic)
{
	global $userdata;

	$is_auth_ary = auth(AUTH_READ, AUTH_LIST_ALL, $userdata);
	$ignore_forum_sql = '';
	$auth_sql = '';

	while(list($key, $value) = each($is_auth_ary))
	{
		if (!$value['auth_read'])
		{
			$ignore_forum_sql .= (($ignore_forum_sql != '') ? ', ' : '') . $key;
		}
	}
	if ($ignore_forum_sql != '')
	{
		if ($forum_topic == 'forum')
		{
			$auth_sql .= " AND f.forum_id NOT IN ($ignore_forum_sql) ";
		}
		else
		{
			$auth_sql .= " AND t.forum_id NOT IN ($ignore_forum_sql) ";
		}
	}

	return $auth_sql;
}

class NewsDataAccess
{
	// {{{ properties

	/**
	* phpBB Database Object.
	*
	* @var object
	*/
	var $db;

	/**
	* Path to phpbb.
	*
	* @var string
	*/
	var $root_path;

	/**
	* Php extension used on this server. i.e. .php, .php3.
	*
	* @var string
	*/
	var $phpEx;

	/**
	* The configuration array of phpBB.
	*
	* @var string
	*/
	var $config;

	/**
	* The default number of items to retrieve.
	*
	* @var integer
	*/
	var $num_items;

	// }}}

	// {{{ constructor

	/**
	* PhpbbNewsAccess Constructor.
	*
	* @access public
	**/
	function NewsDataAccess($phpbb_root)
	{
		global $db, $phpEx, $board_config;

		$this->db = &$db;
		$this->root_path = $phpbb_root;
		$this->phpEx = $phpEx;
		$this->config = &$board_config;

		$this->setItemCount(DEFAULT_NUM_ITEMS);
	}

	// }}}

	// {{{ setItemCount()

	/**
	* Sets the defualt number of topics to be retrieved.
	*
	* @access public
	*
	* @param integer $num_topics The number of topics.
	*
	* @return null
	**/
	function setItemCount($num_items)
	{
		$num_items = intval($num_items);

		if($num_items > 0)
		{
			$this->num_items = $num_items;
		}
	}

	// }}}

	// {{{ getItemCount()

	/**
	* Sets the defualt number of topics to be retrieved.
	*
	* @access public
	*
	* @return integer The number of items to return.
	**/
	function getItemCount()
	{
		return $this->num_items;
	}

	// }}}

	// {{{ fetchCategories()

	/**
	* Fetches news categories from the phpBB database.
	*
	* @access public
	*
	* @param integer $sort (Optional) The sort order of the items.
	*                      SORT_ALPH_DEC - Sort by category name descending.
	*                      SORT_ALPH_ASC - Sort by category name ascending.
	* @param integer $start (Optional) The first news title to display.
	* @param integer $num_items (Optional) The number of items to retrieve.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchCategories($sort = 0)
	{
		// Validate parameters.
		$sort = intval($sort);
		$sort = ($sort < 0) ? 0 : $sort;

		// Begin SQL Construction.
		$sql = "SELECT n.* FROM " . NEWS_TABLE . " AS n ";

		switch($sort)
		{
			case SORT_ALPH_DEC:
			$sql .= 'ORDER BY n.news_category DESC';
			break;
			default:
			$sql .= 'ORDER BY n.news_category ASC';
			break;
		}

		if(!($result = $this->db->sql_query($sql, false, 'news_')))
		{
			echo 'Error ' . __LINE__ . ' ' . __FILE__;
			return array();
		}

		$cats = array();

		while($row = $this->db->sql_fetchrow($result))
		{
			$row['topic_count'] = $this->fetchArticlesCount($row['news_id']);
			$cats[] = $row;
		}

		$this->db->sql_freeresult($result);

		return $cats;
	}

	// }}}

	// {{{ fetchRecentCategories()

	/**
	* Fetches news categories from the phpBB database.
	*
	* @access public
	*
	* @param integer $num_items (Optional) The number of items to retrieve.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchRecentCategories($num_items = 0)
	{
		global $cms_config_var;
		// Validate parameters.
		$num_items = intval($num_items);

		if($num_items <= 0)
		{
			$num_items = $this->num_items;
		}

		$auth_sql = get_user_news_auth_access('topic');

		if (!empty($cms_config_var['md_news_sort']))
		{
			$news_sort = $cms_config_var['md_news_sort'];
		}
		else
		{
			$news_sort = '0';
		}

		if ($news_sort == '1')
		{
			$news_sort_sql = 'RAND()';
		}
		else
		{
			$news_sort_sql = 't.topic_time DESC';
		}

		// Begin SQL Construction.
		$sql = "SELECT n.*
			FROM " . TOPICS_TABLE . " AS t
			LEFT JOIN " . NEWS_TABLE . " AS n
			USING (news_id)
			WHERE t.news_id > 0 " . $auth_sql . "
			ORDER BY " . $news_sort_sql;
		// End SQL Construction.

		if(!($result = $this->db->sql_query($sql, false, 'news_')))
		{
			echo 'Error ' . __LINE__ . ' ' . __FILE__;
			return array();
		}

		$checked = array();
		$recent = array();

		while($row = $this->db->sql_fetchrow($result))
		{
			if(count($recent) >= $num_items)
			{
				break;
			}

			if(!in_array($row['news_id'], $checked))
			{
				$row['news_image'] = $this->root_path . $this->config['news_path'] . '/' . $row['news_image'];
				$recent[] = $row;
				$checked[] = $row['news_id'];
			}
		}

		$this->db->sql_freeresult($result);

		return $recent;
	}

	// }}}

	// {{{ fetchArticle()

	/**
	* Fetches a news article from the phpBB database.
	*
	* @access public
	*
	* @param integer $article_id ID of the article to be fetched.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchArticle($article_id)
	{
		$article_id = intval($article_id);

		if($article_id < 0)
		{
			return array();
		}

		$auth_sql = get_user_news_auth_access('forum');

		// Begin SQL Construction.
		$sql = 'SELECT
				t.forum_id, t.topic_id, t.topic_title, t.topic_time, t.topic_views, t.topic_replies, t.topic_attachment,
				n.*,
				p.post_id, p.post_username, p.post_time, p.post_edit_time, p.post_attachment, p.enable_bbcode, p.enable_html, p.enable_smilies, p.enable_autolinks_acronyms,
				pt.*,
				u.user_id, u.username, u.user_email, u.user_website, u.user_level, u.user_posts, u.user_rank
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . FORUMS_TABLE . ' AS f,
				' . USERS_TABLE . ' AS u,
				' . NEWS_TABLE . ' AS n,
				' . POSTS_TEXT_TABLE . ' AS pt,
				' . POSTS_TABLE . ' AS p
			WHERE
				t.topic_first_post_id = p.post_id
				AND t.forum_id = f.forum_id
				AND t.topic_first_post_id = pt.post_id
				AND t.topic_poster = u.user_id
				AND t.news_id = n.news_id
				AND t.topic_id = ' . $article_id . '
				' . $auth_sql . '
			LIMIT 1';
		if(!($result = $this->db->sql_query($sql)))
		{
			echo 'Error ' . __LINE__ . ' ' . __FILE__;
			return array();
		}

		$article = array();

		if($row = $this->db->sql_fetchrow($result))
		{
			$article[] = $row;
		}

		$this->db->sql_freeresult($result);

		return $article;
	}

	// }}}

	// {{{ fetchPosts()

	/**
	* Fetches a all posts under a topic.
	*
	* @access public
	*
	* @param integer $topic_id ID of the topic.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchPosts($topic_id, $start = 0)
	{
		$topic_id = intval($topic_id);
		$start = intval($start);
		$start = ($start < 0) ? 0 : $start;

		if($topic_id < 0)
		{
			return array();
		}

		$num_items = $this->num_items;

		$auth_sql = get_user_news_auth_access('topic');

		// Begin SQL Construction.
		$sql = 'SELECT t.forum_id, t.topic_id, t.topic_attachment,
				p.post_id, p.post_username, p.post_time, p.post_edit_time, p.post_attachment, p.enable_bbcode, p.enable_html, p.enable_smilies, p.enable_autolinks_acronyms,
				pt.*,
				u.user_id, u.username, u.user_email, u.user_website, u.user_level, u.user_posts, u.user_rank
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . USERS_TABLE . ' AS u,
				' . POSTS_TEXT_TABLE . ' AS pt,
				' . POSTS_TABLE . ' AS p
			WHERE
				t.topic_id = ' . $topic_id . '
				AND p.topic_id = t.topic_id
				AND p.post_id <> t.topic_first_post_id
				AND pt.post_id = p.post_id
				AND p.poster_id = u.user_id
				' . $auth_sql . '
			ORDER BY
				p.post_time ASC LIMIT ' . $start . ', ' . $num_items;

		if(!($result = $this->db->sql_query($sql)))
		{
			echo $sql;
			return array();
		}

		$article = array();

		while($row = $this->db->sql_fetchrow($result))
		{
			$article[] = $row;
		}

		$this->db->sql_freeresult($result);

		return $article;
	}

	// }}}

	// {{{ fetchPostsCount()

	/**
	* Fetches a all posts under a topic.
	*
	* @access public
	*
	* @param integer $topic_id ID of the topic.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchPostsCount($topic_id)
	{
		$topic_id = intval($topic_id);

		if($topic_id < 0)
		{
			return array();
		}

		$auth_sql = get_user_news_auth_access('topic');

		// Begin SQL Construction.
		$sql = 'SELECT
				COUNT(t.topic_id) AS news_count
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . USERS_TABLE . ' AS u,
				' . POSTS_TEXT_TABLE . ' AS pt,
				' . POSTS_TABLE . ' AS p
			WHERE
				t.topic_id = ' . $topic_id . '
				AND p.topic_id = t.topic_id
				AND p.post_id <> t.topic_first_post_id
				AND pt.post_id = p.post_id
				AND p.poster_id = u.user_id
				' . $auth_sql;

		if(!($result = $this->db->sql_query($sql)))
		{
			return array();
		}

		if($row = $this->db->sql_fetchrow($result))
		{
			$this->db->sql_freeresult($result);
			return $row['news_count'];
		}

		$this->db->sql_freeresult($result);

		return 0;
	}

	// }}}

	// {{{ fetchDay()
	/**
	* Fetches all news articles for the given day.
	*
	* @access public
	*
	* @param integer $day Selected day.
	* @param integer $month Selected month.
	* @param integer $year Selected year.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchDay($day, $month, $year)
	{
		global $board_config, $userdata;
		$tz = $board_config['board_timezone'];
		$tm = $board_config['default_time_mode'];
		$tl = $board_config['default_dst_time_lag'];
		if ($userdata['session_logged_in'])
		{
			$tz = $userdata['user_timezone'];
			$tm = $userdata['user_time_mode'];
			$tl = $userdata['user_dst_time_lag'];
		}
		switch ($tm)
		{
			case MANUAL_DST:
				$td = ($tl + ($tz * 60)) * 60;
				break;
			case SERVER_SWITCH:
				$td = ((date('I') * $tl) + ($tz * 60)) * 60;
				break;
			default:
				$td = ($tl + ($tz * 60)) * 60;
				break;
		}

		$interval_begin = gmmktime(0, 0, 0, $month, $day, $year) - $td;
		$interval_end = $interval_begin + 86400;
		/*
		$interval_begin = gmmktime(0, 0, 0, $month, $day, $year);
		*/
		// ubid = User Blog ID
		$ubid = (isset($_GET['ubid'])) ? intval($_GET['ubid']) : $ubid;
		$ubid_sql = '';
		if ($ubid >= 2)
		{
			$ubid_sql = 'AND t.topic_poster = \'' . $ubid . '\'';
		}

		$auth_sql = get_user_news_auth_access('topic');

		$sql = 'SELECT
				t.forum_id, t.topic_id, t.topic_title, t.topic_time, t.topic_views, t.topic_replies, t.topic_attachment,
				n.*,
				p.post_id, p.post_username, p.post_time, p.post_edit_time, p.post_attachment, p.enable_bbcode, p.enable_html, p.enable_smilies, p.enable_autolinks_acronyms,
				pt.*,
				u.user_id, u.username, u.user_email, u.user_website, u.user_level, u.user_posts, u.user_rank
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . FORUMS_TABLE . ' AS f,
				' . USERS_TABLE . ' AS u,
				' . NEWS_TABLE . ' AS n,
				' . POSTS_TEXT_TABLE . ' AS pt,
				' . POSTS_TABLE . ' AS p
			WHERE
				p.post_id = t.topic_first_post_id
				AND f.forum_id = t.forum_id
				AND pt.post_id = t.topic_first_post_id
				' . $ubid_sql . '
				AND u.user_id = t.topic_poster
				AND n.news_id = t.news_id
				AND t.news_id > 0
				AND t.topic_time >= ' . $interval_begin . '
				AND t.topic_time <= ' . $interval_end . '
				' . $auth_sql . '
			ORDER BY
				t.topic_time DESC';

		if(!($result = $this->db->sql_query($sql)))
		{
			echo 'Error ' . __LINE__ . ' ' . __FILE__;
			return array();
		}

		$articles = array();

		while($row = $this->db->sql_fetchrow($result))
		{
			$articles[] = $row;
		}

		$this->db->sql_freeresult($result);

		return $articles;
	}
	// }}}

	// {{{ fetchDays()
	/**
	* Fetches days of the month which have news posts.
	*
	* @access public
	*
	* @param integer $month Selected month.
	* @param integer $year Selected year.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchDays($month, $year)
	{
		global $board_config, $userdata;
		$tz = $board_config['board_timezone'];
		$tm = $board_config['default_time_mode'];
		$tl = $board_config['default_dst_time_lag'];
		if ($userdata['session_logged_in'])
		{
			$tz = $userdata['user_timezone'];
			$tm = $userdata['user_time_mode'];
			$tl = $userdata['user_dst_time_lag'];
		}
		switch ($tm)
		{
			case MANUAL_DST:
				$td = ($tl + ($tz * 60)) * 60;
				break;
			case SERVER_SWITCH:
				$td = ((date('I') * $tl) + ($tz * 60)) * 60;
				break;
			default:
				$td = ($tl + ($tz * 60)) * 60;
				break;
		}

		$interval_begin = gmmktime(0, 0, 0, $month, 1, $year) - $td;
		$interval_end = gmmktime(0, 0, 0, $month + 1, 1, $year) - $td;
		/*
		$interval_begin = gmmktime(0, 0, 0, $month, 1, $year);
		$interval_end = gmmktime(0, 0, 0, $month + 1, 1, $year);
		*/
		// ubid = User Blog ID
		$ubid = (isset($_GET['ubid'])) ? intval($_GET['ubid']) : $ubid;
		$ubid_sql = '';
		if ($ubid >= 2)
		{
			$ubid_sql = 'AND t.topic_poster = \'' . $ubid . '\'';
		}

		$auth_sql = get_user_news_auth_access('topic');

		$sql = 'SELECT
				t.topic_time
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . NEWS_TABLE . ' AS n
			WHERE
				n.news_id = t.news_id
				' . $ubid_sql . '
				AND t.news_id > 0
				AND t.topic_time > ' . $interval_begin . '
				AND t.topic_time < ' . $interval_end . '
				' . $auth_sql . '
			ORDER BY
				t.topic_time DESC';

		if (!($result = $this->db->sql_query($sql)))
		{
			message_die(GENERAL_ERROR, 'Could not query forum news information', '', __LINE__, __FILE__, $sql);
		}

		$days = array_fill(1, 32, 0);

		while($row = $this->db->sql_fetchrow($result))
		{
			$days[intval(create_date('j', $row['topic_time'], $tz))]++;
			//$days[intval(create_date('j', $row['topic_time'], 0))]++;
		}

		$this->db->sql_freeresult($result);

		return $days;
	}
	// }}}

	// {{{ fetchMonths()
	/**
	* Fetches months of the year which have news posts.
	*
	* @access public
	*
	* @param integer $year Selected year.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchMonths($year)
	{
		global $board_config, $userdata;
		$tz = $board_config['board_timezone'];
		$tm = $board_config['default_time_mode'];
		$tl = $board_config['default_dst_time_lag'];
		if ($userdata['session_logged_in'])
		{
			$tz = $userdata['user_timezone'];
			$tm = $userdata['user_time_mode'];
			$tl = $userdata['user_dst_time_lag'];
		}
		switch ($tm)
		{
			case MANUAL_DST:
				$td = ($tl + ($tz * 60)) * 60;
				break;
			case SERVER_SWITCH:
				$td = ((date('I') * $tl) + ($tz * 60)) * 60;
				break;
			default:
				$td = ($tl + ($tz * 60)) * 60;
				break;
		}

		$interval_begin = gmmktime(0, 0, 0, 1, 1, $year) - $td;
		$interval_end = gmmktime(0, 0, 0, 1, 1, $year + 1) - $td;
		/*
		$interval_begin = gmmktime(0, 0, 0, 1, 1, $year);
		$interval_end = gmmktime(0, 0, 0, 1, 1, $year + 1);
		*/
		// ubid = User Blog ID
		$ubid = (isset($_GET['ubid'])) ? intval($_GET['ubid']) : $ubid;
		$ubid_sql = '';
		if ($ubid >= 2)
		{
			$ubid_sql = 'AND t.topic_poster = \'' . $ubid . '\'';
		}

		$auth_sql = get_user_news_auth_access('topic');

		$sql = 'SELECT
				t.topic_time
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . NEWS_TABLE . ' AS n
			WHERE
				n.news_id = t.news_id
				' . $ubid_sql . '
				AND t.news_id > 0
				AND t.topic_time > ' . $interval_begin . '
				AND t.topic_time < ' . $interval_end . '
				' . $auth_sql . '
			ORDER BY
				t.topic_time DESC';

		if (!($result = $this->db->sql_query($sql)))
		{
			message_die(GENERAL_ERROR, 'Could not query forum news information', '', __LINE__, __FILE__, $sql);
		}

		$months = array_fill(1, 12, 0);

		while($row = $this->db->sql_fetchrow($result))
		{
			$months[intval(create_date('n', $row['topic_time'], $tz))]++;
			//$months[intval(create_date('n', $row['topic_time'], 0))]++;
		}

		$this->db->sql_freeresult($result);

		return $months;
	}
	// }}}

	// {{{ fetchYears()
	/**
	* Returns the range of years for which there are posts.
	*
	* @access public
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchYears()
	{
		$auth_sql = get_user_news_auth_access('topic');
		// ubid = User Blog ID
		$ubid = (isset($_GET['ubid'])) ? intval($_GET['ubid']) : $ubid;
		$ubid_sql = '';
		if ($ubid >= 2)
		{
			$ubid_sql = 'AND t.topic_poster = \'' . $ubid . '\'';
		}

		$sql = 'SELECT
				MAX(t.topic_time) AS max_time,
				MIN(t.topic_time) AS min_time
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . NEWS_TABLE . ' AS n
			WHERE
				n.news_id = t.news_id
				' . $ubid_sql . '
				AND t.news_id > 0
				' . $auth_sql;

		if (!($result = $this->db->sql_query($sql)))
		{
			message_die(GENERAL_ERROR, 'Could not query forum news information', '', __LINE__, __FILE__, $sql);
		}

		$years = array();

		if($row = $this->db->sql_fetchrow($result))
		{
			$years['min'] = intval(create_date('Y', $row['min_time'], $tz));
			$years['max'] = intval(create_date('Y', $row['max_time'], $tz));
			/*
			$years['min'] = intval(create_date('Y', $row['min_time'], 0));
			$years['max'] = intval(create_date('Y', $row['max_time'], 0));
			*/
		}

		$this->db->sql_freeresult($result);

		return $years;
	}
	// }}}

	// {{{ fetchArticles()

	/**
	* Fetches news articles from the phpBB database.
	*
	* @access public
	*
	* @param integer $sort (Optional) The sort order of the items.
	* @param integer $cat_id (Optional) A specific category of news to display.
	* @param integer $start (Optional) The first news title to display.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchArticles($sort = 0, $cat_id = 0, $start = 0, $ubid = 0)
	{
		global $cms_config_var;

		// Validate parameters.
		$sort = intval($sort);
		$sort = ($sort < 0) ? 0 : $sort;
		$cat_id = intval($cat_id);
		$cat_id = ($cat_id < 0) ? 0 : $cat_id;
		$start = intval($start);
		$start = ($start < 0) ? 0 : $start;
		// ubid = User Blog ID
		$ubid = (isset($_GET['ubid'])) ? intval($_GET['ubid']) : $ubid;
		$ubid_sql = '';
		if ($ubid >= 2)
		{
			$ubid_sql = 'AND t.topic_poster = \'' . $ubid . '\'';
		}

		$num_items = $this->num_items;

		$auth_sql = get_user_news_auth_access('forum');

		// Begin SQL Construction.
			//p.post_time, p.post_edit_time, p.post_attachment,
		$sql = 'SELECT
				t.forum_id, t.topic_id, t.topic_title, t.topic_time, t.topic_views, t.topic_replies, t.topic_attachment,
				n.*,
				p.post_id, p.post_username, p.post_time, p.post_edit_time, p.post_attachment, p.enable_bbcode, p.enable_html, p.enable_smilies, p.enable_autolinks_acronyms,
				pt.*,
				u.user_id, u.username, u.user_email, u.user_website, u.user_level, u.user_posts, u.user_rank
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . FORUMS_TABLE . ' AS f,
				' . USERS_TABLE . ' AS u,
				' . NEWS_TABLE . ' AS n,
				' . POSTS_TEXT_TABLE . ' AS pt,
				' . POSTS_TABLE . ' AS p
			WHERE
				p.post_id = t.topic_first_post_id
				AND f.forum_id = t.forum_id
				AND pt.post_id = t.topic_first_post_id
				' . $ubid_sql . '
				AND u.user_id = t.topic_poster
				AND n.news_id = t.news_id
				AND t.news_id > 0
				' . $auth_sql;

		if($cat_id > 0)
		{
			$sql .= 'AND t.news_id = ' . $cat_id . ' ';
		}

		$is_block = false;
		if (!empty($cms_config_var['md_news_sort']))
		{
			if ($cms_config_var['md_news_sort'] == '1')
			{
				$is_block = true;
				$sql .= 'ORDER BY RAND() LIMIT ';
			}
		}

		if ($is_block == false)
		{
			switch($sort)
			{
				case SORT_DATE_ASC:
				$sql .= 'ORDER BY t.topic_time ASC LIMIT ';
				break;
				case SORT_ALPH_ASC:
				$sql .= 'ORDER BY t.topic_title ASC LIMIT ';
				break;
				case SORT_ALPH_DEC:
				$sql .= 'ORDER BY t.topic_title DESC LIMIT ';
				break;
				default:
				$sql .= 'ORDER BY t.topic_time DESC LIMIT ';
				break;
			}
		}

		$sql .= $start . ', ' . $num_items;
		// End SQL Construction.

		if ($is_block == true)
		{
			if(!($result = $this->db->sql_query($sql)))
			{
				echo 'Error ' . __LINE__ . ' ' . __FILE__;
				return array();
			}
		}
		else
		{
			if(!($result = $this->db->sql_query($sql, false, 'posts_')))
			{
				echo 'Error ' . __LINE__ . ' ' . __FILE__;
				return array();
			}
		}

		$articles = array();

		while($row = $this->db->sql_fetchrow($result))
		{
			$articles[] = $row;
		}

		$this->db->sql_freeresult($result);

		return $articles;
	}

	// }}}

	// {{{ fetchArticlesCount()

	/**
	* Fetches news articles from the phpBB database.
	*
	* @access public
	*
	* @param integer $sort (Optional) The sort order of the items.
	* @param integer $cat_id (Optional) A specific category of news to display.
	* @param integer $start (Optional) The first news title to display.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchArticlesCount($cat_id = 0, $ubid = 0)
	{
		// Validate parameters.
		$cat_id = intval($cat_id);
		$cat_id = ($cat_id < 0) ? 0 : $cat_id;
		$ubid = (isset($_GET['ubid'])) ? intval($_GET['ubid']) : $ubid;
		$ubid_sql = '';
		if ($ubid >= 2)
		{
			$ubid_sql = 'AND t.topic_poster = \'' . $ubid . '\'';
		}

		$auth_sql = get_user_news_auth_access('forum');

		// Begin SQL Construction.
		$sql = 'SELECT
				COUNT(t.topic_id) AS a_count
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . FORUMS_TABLE . ' AS f,
				' . USERS_TABLE . ' AS u,
				' . NEWS_TABLE . ' AS n,
				' . POSTS_TEXT_TABLE . ' AS pt,
				' . POSTS_TABLE . ' AS p
			WHERE
				p.post_id = t.topic_first_post_id
				AND f.forum_id = t.forum_id
				AND pt.post_id = t.topic_first_post_id
				' . $ubid_sql . '
				AND u.user_id = t.topic_poster
				AND n.news_id = t.news_id
				AND t.news_id > 0
				' . $auth_sql;

		if($cat_id > 0)
		{
			$sql .= 'AND t.news_id = ' . $cat_id . ' ';
		}

		// End SQL Construction.

		if(!($result = $this->db->sql_query($sql)))
		{
			echo 'Error ' . __LINE__ . ' ' . __FILE__;
			return array();
		}

		if($row = $this->db->sql_fetchrow($result))
		{
			$this->db->sql_freeresult($result);
			return $row['a_count'];
		}

		$this->db->sql_freeresult($result);

		return 0;
	}

	// }}}

	// {{{ fetchTitles()

	/**
	* Fetches news titles from the phpBB database.
	*
	* @access public
	*
	* @param integer $sort (Optional) The sort order of the items.
	* @param integer $cat_id (Optional) A specific category of news to display.
	* @param integer $start (Optional) The first news title to display.
	*
	* @return array A multi element array containing the requested information.
	**/
	function fetchTitles($sort = 0, $cat_id = 0, $start = 0, $ubid = 0)
	{
		// Validate parameters.
		$sort = intval($sort);
		$sort = ($sort < 0) ? 0 : $sort;
		$cat_id = intval($cat_id);
		$cat_id = ($cat_id < 0) ? 0 : $cat_id;
		$start = intval($start);
		$start = ($start < 0) ? 0 : $start;
		// ubid = User Blog ID
		$ubid = (isset($_GET['ubid'])) ? intval($_GET['ubid']) : $ubid;
		$ubid_sql = '';
		if ($ubid >= 2)
		{
			$ubid_sql = 'AND t.topic_poster = \'' . $ubid . '\'';
		}

		$num_items = $this->num_items;

		$auth_sql = get_user_news_auth_access('topic');

		// Begin SQL Construction.
		$sql = 'SELECT
				t.forum_id, t.topic_id, t.topic_title, t.topic_time, t.topic_views, t.topic_replies,
				n.*,
				u.user_id, u.username, u.user_email, u.user_website, u.user_level, u.user_posts, u.user_rank
			FROM
				' . TOPICS_TABLE . ' AS t,
				' . USERS_TABLE . ' AS u,
				' . NEWS_TABLE . ' AS n
			WHERE
				u.user_id = t.topic_poster
				' . $ubid_sql . '
				AND n.news_id = t.news_id
				AND t.news_id > 0
				' . $auth_sql;

		if($cat_id > 0)
		{
			$sql .= 'AND t.news_id = ' . $cat_id . ' ';
		}

		switch($sort)
		{
			case SORT_DATE_ASC:
			$sql .= 'ORDER BY t.topic_time ASC LIMIT ';
			break;
			case SORT_ALPH_ASC:
			$sql .= 'ORDER BY t.topic_title ASC LIMIT ';
			break;
			case SORT_ALPH_DEC:
			$sql .= 'ORDER BY t.topic_title DESC LIMIT ';
			break;
			default:
			$sql .= 'ORDER BY t.topic_time DESC LIMIT ';
			break;
		}

		$sql .= $start . ', ' . $num_items;
		// End SQL Construction.

		if(!($result = $this->db->sql_query($sql, false, 'posts_')))
		{
			echo 'Error ' . __LINE__ . ' ' . __FILE__;
			return array();
		}

		$titles = array();

		while($row = $this->db->sql_fetchrow($result))
		{
			$titles[] = $row;
		}

		$this->db->sql_freeresult($result);

		return $titles;
	}

	// }}}
}
?>