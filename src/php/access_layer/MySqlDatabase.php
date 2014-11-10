<?php
// -- REQUIRES
require_once("MySqlDatabaseConfig.php");

class MySqlDatabase {
// -- INSTANCE VARIABLES
	private $connection;
	private $previous_query;
	private $magic_quotes_active;
	private $real_escape_string_exists;

// -- CONSTRUCTOR
	/* Constructor: initiate connection with mysql database
	*
	*/
	function __construct() {
		$this->openConnection();
		// Check for magic quotes capability
		$this->magic_quotes_active = get_magic_quotes_gpc();
		$this->real_escape_string_exists = function_exists("mysql_real_escape_string");
	} 

// -- PUBLIC METHODS
	/* Function: perform query in mysql database, return result
	*
	*/
	public function query($sql) {
		$this->previous_query = $sql;
		$result = mysql_query($sql, $this->connection);
		$this->confirmQuery($result);
		return $result;
	}
	
	/* Function: Return sql records as php array of arrays.
	*
	*/
	public function fetchArraysFromQuery($sql) {
		$results = $this->query($sql);
		$array_results = array();
		while ($record = $this->fetchArray($results)) {
			array_push($array_results, $record);
		}
		return $array_results;
	}
  
  /**
   * Function: Return array of all records in table.
   */
  public function fetchAllArraysFromTable($db_table_concat) {
    $path = "Select * from ".$db_table_concat;
    return $this->fetchArraysFromQuery($path);
  }

	/* Function: Return sql result as php array.
	*
	*/
	public function fetchArrayFromQuery($sql) {
		return $this->fetchArray($this->query($sql));
	}
	
	/* Function: return mysql record as array.
	*
	*/
	public function fetchArray($result) {
		return mysql_fetch_array($result);
	}

	/* Function: return id of previous auto-increment object inserted into db.
	*
	*/
	public function fetchInsertedId() {
		return mysql_insert_id($this->connection);
	}

	/* Function: return number of rows in a mysql result set.
	*
	*/
	public function numRows($result) {
		return mysql_num_rows($result);
	}
	
	/* Function: return number of rows affected by previous query.
	*
	*/
	public function affectedRows() {
		return mysql_affected_rows($this->connection);
	}
	
	/* Function: escape special characters that irk mysql.
	*
	*/
	public function escapeValue($value) {
		if ($this->real_escape_string_exists) {
			if ($this->magic_quotes_active) {
				$value = stripslashes($value);
			}
			$value = mysql_real_escape_string($value);
		}
		else if (!$this->magic_quotes_active) {
			$value = addslashes($value);	
		}
		return $value;
	}
	
	/* Function: establish connection with mysql database 
	*
	*/
	public function openConnection() {
		$this->connection = mysql_connect(DB_SERVER, DB_USER_NAME, DB_PASSWORD);
		if (!$this->connection) {
			die ("ERROR: MySql connection failed: " . mysql_error());
		}
		else {
			$db_select = mysql_select_db(DB_NAME, $this->connection);
			if (!$db_select) {
				die("ERROR: MySql connection to database " . DB_NAME . " failed: " . mysql_error());
			}
		}
	}
	
	/* Function: terminate connection with mysql database
	*
	*/
	public function closeConnection() {
		if (isset($this->connection)) {
			mysql_close($this->connection);
			unset($this->connection);
		}
	}

// -- PRIVATE METHODS
	/* Function: terminate program if database query fails. 
	*	this method is called on every invocation of "query()".
	*/
	private function confirmQuery($result) {
		if (!$result) {
			$output = "ERROR: MySql query failed: " . mysql_error();
			$output .= ", Previous Query: " . $this->previous_query;
			die($output);
		}
	}
}

?>
