<?php
/*
This file is part of yourTinyTodo by the yourTinyTodo community.
Copyrights for portions of this file are retained by their owners.

Based on myTinyTodo by Max Pozdeev
(C) Copyright 2009-2010 Max Pozdeev <maxpozdeev@gmail.com>

Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/

class DatabaseResult_Mysql implements IDatabaseResult
{
	/**
	 * @var Database
	 */
	var $parent;
	var $q;
	var $query;
	var $rows = NULL;
	var $affected = NULL;
	var $prefix = '';

	/**
	 * @param $query
	 * @param $h Database
	 * @param int $resultless
	 * @throws Exception
	 */
	function __construct($query, &$h, $resultless = 0)
	{
		$this->parent = $h;
		$this->query = $query;

		$this->q = mysql_query($query, $this->parent->dbh);

		if(!$this->q)
		{
			throw new Exception($this->parent->error());
		}
	}

	function affected()
	{
		if(is_null($this->affected))
		{
			$this->affected = mysql_affected_rows($this->parent->dbh);
		}
		return $this->affected;
	}

	function fetch_row()
	{
		return mysql_fetch_row($this->q);
	}

	function fetch_assoc()
	{
		return mysql_fetch_assoc($this->q);
	}
}

class Database_Mysql extends Database
{
	function __construct()
	{
	}

	function connect($host, $user = null, $pass = null, $db = null)
	{
		if(!$this->dbh = @mysql_connect($host,$user,$pass))
		{
			throw new Exception(mysql_error());
		}
		if( @!mysql_select_db($db, $this->dbh) )
		{
			throw new Exception($this->error());
		}
		return true;
	}

	function last_insert_id($tablename = null)
	{
		return mysql_insert_id($this->dbh);
	}	
	
	function error()
	{
		return mysql_error($this->dbh);
	}

	function sq($query, $p = NULL)
	{
		$q = $this->_dq($query, $p);

		if($q->rows()) $res = $q->fetch_row();
		else return NULL;

		if(sizeof($res) > 1) return $res;
		else return $res[0];
	}

	function sqa($query, $p = NULL)
	{
		$q = $this->_dq($query, $p);

		if($q->rows()) $res = $q->fetch_assoc();
		else return NULL;

		if(sizeof($res) > 1) return $res;
		else return $res[0];
	}

	function dq($query, $p = NULL)
	{
		return $this->_dq($query, $p);
	}

	function ex($query, $p = NULL)
	{
		return $this->_dq($query, $p, 1);
	}

	private function _dq($query, $p = NULL, $resultless = 0)
	{
		if(!isset($p)) $p = array();
		elseif(!is_array($p)) $p = array($p);

		$m = explode('?', $query);

		if(sizeof($p)>0)
		{
			if(sizeof($m)< sizeof($p)+1) {
				throw new Exception("params to set MORE than query params");
			}
			if(sizeof($m)> sizeof($p)+1) {
				throw new Exception("params to set LESS than query params");
			}
			$query = "";
			for($i=0; $i<sizeof($m)-1; $i++) {
				$query .= $m[$i]. (is_null($p[$i]) ? 'NULL' : $this->quote($p[$i]));
			}
			$query .= $m[$i];
		}
		return new DatabaseResult_Mysql($query, $this, $resultless);
	}

	function affected()
	{
		return	mysql_affected_rows($this->dbh);
	}

	function quote($s)
	{
		return '\''. mysql_real_escape_string($s). '\'';
	}

	function quoteForLike($format, $s)
	{
		$s = str_replace(array('%','_'), array('\%','\_'), mysql_real_escape_string($s));
		return '\''. sprintf($format, $s). '\'';
	}

	function table_exists($table)
	{
		$table = addslashes($table);
		$q = mysql_query("SELECT 1 FROM `$table` WHERE 1=0", $this->dbh);
		if($q === false) return false;
		else return true;
	}
}
