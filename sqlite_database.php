<?php
/*
* A SQLite class.
*/
class Database {
	private static $__DB;
	private static $_database_file;
	private static $_params = array();
	private static $_lastSql;

	public static function init($config = array()) {
		self::$_database_file	= (isset($config["file"]) ? $config["file"] : null);

		self::_connection();
	}

	private static function _connection() {
		self::$__DB = new SQLite3(self::$_database_file);
	}

	/*
	* Give parameters using setParam function and use this params in your query like {param}, it will parse the value
	*/
	public static function query($query = null, $select = true) {
		$search		= array();
		$replace	= array();

		if(strpos($query, 'INSERT INTO') !== false || strpos($query, 'CREATE TABLE') !== false)
			$select = false;

		foreach(self::$_params as $param => $value) {
			$search[] = "{" . $param . "}";
			$replace[] = ($value === null ? 'null' : $value);
		}

		$query	= str_replace($search, $replace, $query);
		$sql = null;
		try {
			if($select) {
				$sql = self::$__DB->query($query);
			} else {
				$sql = self::$__DB->exec($query);
			}
		} catch(PDOException $e) {
			trigger_error("ERROR: Couldn't execute query: " . $e->getMessage());
		}

		self::$_lastSql = $sql;

		return $sql;
	}

	public static function getArray($sql = null) {
		$toReturn	= array();
		$sql = ($sql ? $sql : self::$_lastSql);

		while($data = $sql->fetchArray(SQLITE3_ASSOC))
			$toReturn[]	= $data;

		return $toReturn;
	}

	public static function lastInsertId() {
		return null;
	}

	/*
	* param can be a string and value as a string or
	* param can be an array (index, value) and value is null.
	*/
	public static function setParam($param, $value = null, $escape = true) {
		if(!$param)
			return false;

		if(is_array($param)) {
			foreach($param as $index => $value)
				self::$_params[$index] = ($escape && gettype($value) !== 'NULL' ? self::escape($value) : $value);
		} else {
			self::$_params[$param] = ($escape && gettype($value) !== 'NULL' ? self::escape($value) : $value);
		}
	}

	private static function escape($value) {
		return $value; // TODO: Implement something for catching sql-injection
	}

	/*
	* Close the MySQL Connection, function will be called in the beforeViewing because at
	* that moment al the processing is done.
	*/
	public static function dispatch() {
		// SQLITE DOES NOT REQUIRE CLOSING THE CONNECTION
	}
}
?>