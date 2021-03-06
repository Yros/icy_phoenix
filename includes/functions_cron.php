<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

if (!defined('IN_ICYPHOENIX'))
{
	die('Hacking attempt');
}

/**
* Unlock cron script
*/
function unlock_cron()
{
	global $db, $cache;

	$sql = "UPDATE " . CONFIG_TABLE . "
		SET config_value = '0'
		WHERE config_name = 'cron_lock'
			AND config_value = '" . $db->sql_escape(CRON_ID) . "'";
	$db->sql_query($sql);
	$cache->destroy('config');
}

/**
* Process Digests
*/
function process_digests()
{
	global $db, $cache, $config, $user, $lang, $table_prefix;

	// Digests - BEGIN
	if (!empty($config['cron_digests_interval']) && ($config['cron_digests_interval'] > 0))
	{
		// MG PHP Cron Emulation For Digests - BEGIN
		$page_url = pathinfo($_SERVER['SCRIPT_NAME']);
		$digests_pages_array = array(CMS_PAGE_PROFILE, CMS_PAGE_POSTING);
		if (empty($config['cron_lock_hour']) && !in_array($page_url['basename'], $digests_pages_array))
		{
			if ((time() - $config['cron_digests_last_run']) > CRON_REFRESH)
			{
				$config['cron_digests_last_run'] = ($config['cron_digests_last_run'] == 0) ? (time() - 3600) : $config['cron_digests_last_run'];
				$last_send_time = @getdate($config['cron_digests_last_run']);
				$cur_time = @getdate();
				if (!empty($config['cron_digests_interval']) && ($config['cron_digests_interval'] > 0) && ($cur_time['hours'] != $last_send_time['hours']))
				{
					set_config('cron_lock_hour', 1);
					define('PHP_DIGESTS_CRON', true);
					include_once(IP_ROOT_PATH . 'mail_digests.' . PHP_EXT);
				}
			}
		}
		// MG PHP Cron Emulation For Digests - END
	}
	// Digests - END

	if (CRON_DEBUG == false)
	{
		set_config('cron_digests_last_run', time());
	}
}

/**
* Process Birthdays
*/
function process_birthdays()
{
	global $db, $cache, $config, $lang;

	if (!empty($config['cron_birthdays_interval']))
	{
		// MG PHP Cron Emulation For Birthdays - BEGIN
		$page_url = pathinfo($_SERVER['SCRIPT_NAME']);
		$birthdays_pages_array = array(CMS_PAGE_PROFILE, CMS_PAGE_POSTING);
		if (empty($config['cron_lock_hour']) && !in_array($page_url['basename'], $birthdays_pages_array))
		{
			if ((time() - $config['cron_birthdays_last_run']) > CRON_REFRESH)
			{
				$config['cron_birthdays_last_run'] = ($config['cron_birthdays_last_run'] == 0) ? (time() - 3600) : $config['cron_birthdays_last_run'];
				$last_send_time = @getdate($config['cron_birthdays_last_run']);
				$cur_time = @getdate();
				if (!empty($config['cron_birthdays_interval']) && ($config['cron_birthdays_interval'] > 0) && ($cur_time['hours'] != $last_send_time['hours']))
				{
					set_config('cron_lock_hour', 1);
					if (!function_exists('birthday_email_send'))
					{
						include_once(IP_ROOT_PATH . 'includes/functions_users.' . PHP_EXT);
					}
					birthday_email_send();
				}
			}
		}
		// MG PHP Cron Emulation For Birthdays - END
	}

	if (CRON_DEBUG == false)
	{
		set_config('cron_birthdays_last_run', time());
	}
}

/**
* Process Files
*/
function process_files()
{
	$files_array = array();
	$files_array = explode(',', CRON_FILES);
	foreach ($files_array as $cron_file)
	{
		$cron_file_full = CRON_REAL_PATH . $cron_file;
		@include($cron_file_full);
	}
	if (CRON_DEBUG == false)
	{
		set_config('cron_files_last_run', time());
	}
}

/**
* Tidy database
*/
function tidy_database()
{
	global $dbname, $db, $config, $user;

	$is_allowed = true;
	// If you want to assign the extra SQL charge to non registered users only, decomment this line... ;-)
	$is_allowed = (!$user->data['session_logged_in']) ? true : false;

	if ($is_allowed)
	{
		$current_time = time();

		@ignore_user_abort();
		// Get tables list
		$all_tables = array();
		$sql_list = "SHOW TABLES";
		$result_list = $db->sql_query($sql_list);
		// Optimize tables
		while ($row = $db->sql_fetchrow($result_list))
		{
			$all_tables[] = $row['Tables_in_' . $dbname];
		}
		$db->sql_freeresult($result_list);

		foreach ($all_tables as $table_name)
		{
			$sql = "OPTIMIZE TABLES $table_name";
			$db->sql_return_on_error(true);
			$result = $db->sql_query($sql);
			$db->sql_return_on_error(false);
		}

		if (CRON_DEBUG == false)
		{
			set_config('cron_db_count', ($config['cron_db_count'] + 1));
			set_config('cron_database_last_run', time());
		}
	}
}

/**
* Tidy cache
*/
function tidy_cache()
{
	empty_cache_folders(MAIN_CACHE_FOLDER);
	if (CRON_DEBUG == false)
	{
		set_config('cron_cache_last_run', time());
	}
}

/**
* Tidy sql
*/
function tidy_sql()
{
	empty_cache_folders(SQL_CACHE_FOLDER);
	if (CRON_DEBUG == false)
	{
		set_config('cron_sql_last_run', time());
	}
}

/**
* Tidy users
*/
function tidy_users()
{
	empty_cache_folders(USERS_CACHE_FOLDER);
	if (CRON_DEBUG == false)
	{
		set_config('cron_users_last_run', time());
	}
}

/**
* Tidy topics
*/
function tidy_topics()
{
	empty_cache_folders(POSTS_CACHE_FOLDER);
	empty_cache_folders(TOPICS_CACHE_FOLDER);
	empty_cache_folders(FORUMS_CACHE_FOLDER);
	if (CRON_DEBUG == false)
	{
		set_config('cron_topics_last_run', time());
	}
}

/**
* Tidy sessions
*/
function tidy_sessions()
{
	global $db;
	/*
	$sql = "DELETE FROM " . SESSIONS_TABLE;
	$db->sql_return_on_error(true);
	$result = $db->sql_query($sql);
	$db->sql_return_on_error(false);

	$sql = "DELETE FROM " . AJAX_SHOUTBOX_SESSIONS_TABLE;
	$db->sql_return_on_error(true);
	$result = $db->sql_query($sql);
	$db->sql_return_on_error(false);

	$sql = "DELETE FROM " . SEARCH_TABLE;
	$db->sql_return_on_error(true);
	$result = $db->sql_query($sql);
	$db->sql_return_on_error(false);
	*/

	if (CRON_DEBUG == false)
	{
		set_config('cron_session_last_run', time());
	}

}

?>