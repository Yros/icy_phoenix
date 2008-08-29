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
* @Icy Phoenix is based on phpBB
* @copyright (c) 2008 phpBB Group
*
*/

if(!defined('SQL_LAYER'))
{

	define('SQL_LAYER', 'mysql');

	class sql_db
	{
		var $db_connect_id;
		var $query_result;
		var $row = array();
		var $rowset = array();
		var $num_queries = 0;
		var $caching = false;
		var $cached = false;
		var $cache = array();
		var $sql_time = 0; // SQL excution time - added by Smartor

		//
		// Constructor
		//
		function sql_db($sqlserver, $sqluser, $sqlpassword, $database, $persistency = true)
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			$this->persistency = $persistency;
			$this->user = $sqluser;
			$this->password = $sqlpassword;
			$this->server = $sqlserver;
			$this->dbname = $database;

			if($this->persistency)
			{
				$this->db_connect_id = @mysql_pconnect($this->server, $this->user, $this->password);
			}
			else
			{
				$this->db_connect_id = @mysql_connect($this->server, $this->user, $this->password);
			}
			if($this->db_connect_id)
			{
				if($database != "")
				{
					$this->dbname = $database;
					$dbselect = @mysql_select_db($this->dbname);
					if(!$dbselect)
					{
						@mysql_close($this->db_connect_id);
						$this->db_connect_id = $dbselect;
					}
				}

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $this->db_connect_id;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}

		//
		// Other base methods
		//
		function sql_close()
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if($this->db_connect_id)
			{
				if($this->query_result)
				{
					@mysql_free_result($this->query_result);
				}
				$result = @mysql_close($this->db_connect_id);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}

		//
		// Base query method
		//
		function sql_query($query = "", $transaction = false, $cache = false)
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			// Remove any pre-existing queries
			unset($this->query_result);
			// Check cache
			$this->caching = false;
			$this->cache = array();
			$this->cached = false;
			if($query !== '' && $cache)
			{
				global $phpbb_root_path;
				$hash = md5($query);
				if(strlen($cache))
				{
					$hash = $cache . $hash;
				}
				$filename = $phpbb_root_path . MAIN_CACHE_FOLDER . 'sql_' . $hash . '.php';
				if(@file_exists($filename))
				{
					$set = array();
					include($filename);
					$this->cache = $set;
					$this->cached = true;
					$this->caching = false;
					return 'cache';
				}
	//			echo 'cache is missing: ', $filename, '<br />';
				$this->caching = $hash;
			}
			// not cached
	//		echo 'sql: ', htmlspecialchars($query), '<br />';

			if($query != "")
			{
				$this->num_queries++;

				$this->query_result = @mysql_query($query, $this->db_connect_id);
			}
			if($this->query_result)
			{
				unset($this->row[$this->query_result]);
				unset($this->rowset[$this->query_result]);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $this->query_result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return ( $transaction == END_TRANSACTION ) ? true : false;
			}
		}

		//
		// Other query methods
		//
		function sql_numrows($query_id = 0)
		{
			if($query_id === 'cache' && $this->cached)
			{
				return count($this->cache);
			}
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if(!$query_id)
			{
				$query_id = $this->query_result;
			}
			if($query_id)
			{
				$result = @mysql_num_rows($query_id);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_affectedrows()
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if($this->db_connect_id)
			{
				$result = @mysql_affected_rows($this->db_connect_id);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_numfields($query_id = 0)
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if(!$query_id)
			{
				$query_id = $this->query_result;
			}
			if($query_id)
			{
				$result = @mysql_num_fields($query_id);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_fieldname($offset, $query_id = 0)
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if(!$query_id)
			{
				$query_id = $this->query_result;
			}
			if($query_id)
			{
				$result = @mysql_field_name($query_id, $offset);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_fieldtype($offset, $query_id = 0)
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if(!$query_id)
			{
				$query_id = $this->query_result;
			}
			if($query_id)
			{
				$result = @mysql_field_type($query_id, $offset);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_fetchrow($query_id = 0)
		{
			if($query_id === 'cache' && $this->cached)
			{
				return count($this->cache) ? array_shift($this->cache) : false;
			}
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if(!$query_id)
			{
				$query_id = $this->query_result;
			}
			if($query_id)
			{
				$this->row[$query_id] = @mysql_fetch_array($query_id);
				if($this->caching)
				{
					if($this->row[$query_id] === false)
					{
						$this->write_cache();
					}
					$this->cache[] = $this->row[$query_id];
				}

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $this->row[$query_id];
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_fetchrowset($query_id = 0)
		{
			if($query_id === 'cache' && $this->cached)
			{
				return $this->cache;
			}
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if(!$query_id)
			{
				$query_id = $this->query_result;
			}
			if($query_id)
			{
				unset($this->rowset[$query_id]);
				unset($this->row[$query_id]);
				while($this->rowset[$query_id] = @mysql_fetch_array($query_id))
				{
					if($this->caching)
					{
						if($this->row[$query_id] === false)
						{
							$this->write_cache();
						}
						$this->cache[] = $this->row[$query_id];
					}
					$result[] = $this->rowset[$query_id];
				}
				if($this->caching)
				{
					$this->write_cache();
				}

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_fetchfield($field, $rownum = -1, $query_id = 0)
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if(!$query_id)
			{
				$query_id = $this->query_result;
			}
			if($query_id)
			{
				if($rownum > -1)
				{
					$result = @mysql_result($query_id, $rownum, $field);
				}
				else
				{
					if(empty($this->row[$query_id]) && empty($this->rowset[$query_id]))
					{
						if($this->sql_fetchrow())
						{
							$result = $this->row[$query_id][$field];
						}
					}
					else
					{
						if($this->rowset[$query_id])
						{
							$result = $this->rowset[$query_id][$field];
						}
						else if($this->row[$query_id])
						{
							$result = $this->row[$query_id][$field];
						}
					}
				}

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_rowseek($rownum, $query_id = 0)
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if(!$query_id)
			{
				$query_id = $this->query_result;
			}
			if($query_id)
			{
				$result = @mysql_data_seek($query_id, $rownum);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_nextid()
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if($this->db_connect_id)
			{
				$result = @mysql_insert_id($this->db_connect_id);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return $result;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_freeresult($query_id = 0)
		{
			if($query_id === 'cache')
			{
				$this->caching = false;
				$this->cached = false;
				$this->cache = array();
			}
			if($this->caching)
			{
				$this->write_cache();
			}

			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			if(!$query_id)
			{
				$query_id = $this->query_result;
			}

			if ( $query_id )
			{
				unset($this->row[$query_id]);
				unset($this->rowset[$query_id]);

				@mysql_free_result($query_id);

				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return true;
			}
			else
			{
				$mtime = microtime();
				$mtime = explode(" ",$mtime);
				$mtime = $mtime[1] + $mtime[0];
				$endtime = $mtime;

				$this->sql_time += $endtime - $starttime;

				return false;
			}
		}
		function sql_error($query_id = 0)
		{
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;

			$result["message"] = @mysql_error($this->db_connect_id);
			$result["code"] = @mysql_errno($this->db_connect_id);

			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$endtime = $mtime;

			$this->sql_time += $endtime - $starttime;

			return $result;
		}

		function write_cache()
		{
			if(!$this->caching)
			{
				return;
			}
			global $phpbb_root_path;
			$f = fopen($phpbb_root_path . MAIN_CACHE_FOLDER . 'sql_' . $this->caching . '.php', 'w');
			$data = var_export($this->cache, true);
			@fputs($f, '<' . '?php $set = ' . $data . ';' . "\n" . 'return;' . "\n" . '?' . '>');
			@fclose($f);
			@chmod($phpbb_root_path . MAIN_CACHE_FOLDER . 'sql_' . $this->caching . '.php', 0777);
			$this->caching = false;
			$this->cached = false;
			$this->cache = array();
		}

		function clear_cache($prefix = '')
		{
			global $phpbb_root_path;
			$this->caching = false;
			$this->cached = false;
			$this->cache = array();
			$prefix = 'sql_' . $prefix;
			$prefix_len = strlen($prefix);
			$res = opendir($phpbb_root_path . 'cache');
			if($res)
			{
				while(($file = readdir($res)) !== false)
				{
					if(substr($file, 0, $prefix_len) === $prefix)
					{
						@unlink($phpbb_root_path . MAIN_CACHE_FOLDER . $file);
					}
				}
			}
			@closedir($res);
		}
	} // class sql_db

} // if ... define

?>