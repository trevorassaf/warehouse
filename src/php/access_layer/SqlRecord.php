<?php
// -- DEPENDENCIES
require_once(dirname(__FILE__)."/AccessLayerObject.php");
require_once(dirname(__FILE__)."/exceptions/InvalidUniqueKeyException.php");
require_once(dirname(__FILE__)."/exceptions/InvalidObjectStateException.php");

abstract class SqlRecord extends AccessLayerObject {
  
  // Db Keys
  const ID_KEY = "id"; 
  const CREATED_KEY = "created"; 
  const LAST_UPDATED_TIME = "last_updated";
  
  /**
   * String containing name for this table.
   * @requires must be defined by subclasses.
   */
  protected static $tableName;
  
  /**
   * List of alternate keys for this table. 'id' is always the primary key
   * and needn't be included in this list.
   */
  protected static $alternateKeys = array();

  /**
   * List of composite keys for this table.
   */
  protected static $compositeKey = array();

  /**
   * Handle to the database connection.
   */
  private static $databaseHandle = null;

  /**
   * Table of cached prepared statements that were created during this transaction. 
   */
  private static $cachedPreparedStatements = null;

  private
    $id,
    $createdTime,
    $lastUpdatedTime;

  /**
   * beginTx()
   * - Initiate database connection and start a transaction.
   * @requires non-existant database connection
   */
  public static function beginTx($connection_type=null) {
    // Fail due to existing transaction
    assert(isset(self::$databaseHandle));
    assert(isset(self::$cachedPreparedStatements));

    // Initialize db connection and cached prepared statement table
    try {
      self::$databaseHandle = static::$databaseFactory->createConnection($connection_type);
      self::$cachedPreparedStatements = array();
      self::$databaseHandle->beginTransaction();
    } catch (PDOException $e) {
      die("\nERROR: " . $e->getMessage() . "\n");
    }
  }

  /**
   * endTx()
   * - Close transaction and end database connection.
   * @requires existing database connection
   */
  public static function endTx() {
    // Fail due to non-existant transaction
    assert(!isset(self::$databaseHandle));
    assert(!isset(self::$cachedPreparedStatements));

    // Close connection and cached prepared statement table
    try {
      self::$databaseHandle = null;
      self::$cachedPreparedStatements = null;
    } catch (PDOException $e) {
      die("\nERROR: " . $e->getMessage() . "\n");
    }
  }

  /**
   * fetchAll()
   * - Atomically fetch all records from table associated 
   *   with calling class.
   */
  public static function fetchAll() {
    // Create query string
    $query_str = "SELECT * FROM :table_name";

    try {
      // Create and cache prepared statement, if it doesn't already exist
      if (!isset(self::$cachedPreparedStatements[$query_str])) {
        self::$cachedPreparedStatements[$query_str] = $dbh->prepare($query_str);
      }
      $stmt = self::$cachedPreparedStatements[$query_str]; 

      // Fetch all records 
      $stmt->bindValue(":table_name", static::$tableName, PDO::PARAM_STR);
      $stmt->execute();
      $raw_record_set = $stmt->fetchAll();

      // Extrude raw records to objects
      $record_objects = array();
      foreach ($raw_record_set as $raw_record) {
        $record_objects[] = new static($raw_record); 
      }

      return $record_objects;
    } catch (PDOException $e) {
      die("\nERROR: " . $e->getMessage() . "\n");
    }
  }

  /**
   * deleteAll()
   * - Atomically truncate table associated with the calling class.
   */
  public static function deleteAll() {
    try {
      $sql_records = static::fetchAll();
      foreach ($sql_records as $record) {
        $record->delete();
      } 
    } catch (PDOException $e) {
      die("\nERROR: " . $e->getMessage() . "\n");
    }
  }

  /**
   * insert()
   * @Override AccessLayerObject
   */
  public static function insert($init_params) {
    return new static($init_params, true);
  }

  /**
   * fetchByCompositeKey()
   * - Fetch sql record from db by composite key.
   */
  protected static function fetchByCompositeKey($composite_key) {
    // Fail due to invalid composite key
    assert(static::isValidCompositeKey($composite_key));

    // Fetch object
    $results = static::fetchByParams($composite_key);

    // Return null, because query didn't match any record
    $num_results = count($results);
    if ($num_results == 0) {
      return null;
    } 

    // Return single sql record.
    if ($num_results == 1) {
      return $results[0];
    } 

    // Failed due to composite key misuse 
    assert(false);
  }
  	
	/**
   * fetchByUniqueKey()
   * - Fetch sql record from db by key.
   */
  protected static function fetchByKey($key, $value) {
    // Fail due to invalid alternate key
    assert(static::isValidKey($key));

    // Fetch object
    $results = static::fetchByParams(
      array($key => $value)
    );

    // Return null, because query didn't match any record
    $num_results = count($results);
    if ($num_results == 0) {
      return null;
    } 

    // Return single sql record.
    if ($num_results == 1) {
      return $results[0];
    } 

    // Failed due to alternate key misuse 
    assert(false);
  }

  /**
   * isValidKey()
   * - Return true iff key is a candidate key for this object.
   * @param key : string representing db table key
   */
  private static function isValidKey($key) {
    // Return true if 'key' corresponds to the primary key 
    if ($key == self::ID_KEY) {
      return true;
    }

    // Return true if 'key' is valid alternate key
    foreach (static::$alternateKeys as $alternate_key) {
      if ($key == $alternate_key) {
        return true;
      }
    }

    return false;
  }

  /**
   * isValidCompositeKey()
   * - Return true iff 'composite_key' is valid composite key.
   */
  private static function isValidCompositeKey($composite_key) {
    $count_composite_key = count($composite_key);
    foreach (static::$compositeKeys as $valid_composite_key) {
      // Skip if the number of elements don't match
      if ($count_composite_key != count($valid_composite_key)) {
        continue;
      }

      // Check if the elements in the provided composite key
      // match the elements in this valid composite key
      $num_matched_keys = 0;
      foreach ($valid_composite_key as $vck_element) {
        foreach ($composite_key as $ck_element) {
          if ($vck_element == $ck_element) {
            ++$num_matched_keys;
          }
        }   
      }

      // Return true because the composite key is valid 
      if ($num_matched_keys == $count_composite_key) {
        return true;
      }
    }

    return false;
  }
  
  /** 
   * fetchByParams()
   * - Fetch all records matching 'params'. Records fetched from table
   *   associated with calling class.
   * @param params : Map(string:key => prim:value)
   */
	protected static function fetchByParams($params) {
		// Generate db query
    $query_str = self::genFetchByParamsQuery($params);

    try {
      // Create and cache prepared statement, if it doesn't already exist 
      if (!isset(self::$cachedPreparedStatements[$query_str])) {
        self::$cachedPreparedStatements[$query_str] = $dbh->prepare($query_str);
      }
      $stmt = self::$cachedPreparedStatements[$query_str]; 

      // Fetch record
      foreach ($params as $key => $value) {
        $key_for_prepared_key = PDOFactory::transformForPreparedStatement($key); 
        $stmt->bindValue($key_for_prepared_key, $value->getValue(), $value->getType());
      }
      $stmt->execute();
      
      // Extrude raw records to objects
      $raw_record_set = $stmt->fetchAll();
      foreach ($raw_record_set as $raw_record) {
        $record_objects[] = new static($raw_record); 
      }
      
      return $record_objects;
    } catch (PDOException $e) {
      die("\nERROR: " . $e->getMessage() . "\n");
    } 
  }

  /**
   * Return string containing query for fetching objects with fields matching those in $params.
   *
   * @param params: map of parameters (string:key => prim:value)
   */
  private static function genGetAllObjectsByParamsQuery($params) {
    $query = "SELECT * FROM " . static::$tableName . " WHERE ";
    foreach ($params as $k => $v) {
      $query .= $k .'=';
      if (is_string($v)) {
        $query .= "'".$v."'";
      } else {
        $query .= $v;  
      }
      $query .= ' AND ';
    }
    $query = substr($query, 0, -5);
    return $query;
  } 
  
  /** 
   * Create sql query for fetching object from db.
   *
   * @param init_params: map of parameters for object (string:key => prim:value)
	 */
  private static function genCreateObjectQuery($init_params) {
    $query = "INSERT INTO " . static::$tableName . " (";
		$values_string = ") VALUES (";
		foreach ($init_params as $key => $value) {
      if ($value === null) {
        continue;
      }
      $query .= $key . ", ";
      if ($value === false) {
        $value = 0;
      }
      
      $escaped_value = mysql_escape_string($value);
      $values_string .= "'$escaped_value', "; 
		}
		// Trim terminal commas from strings
		$query = substr($query, 0, strlen($query) - 2);
		$values_string = substr($values_string, 0, strlen($values_string) - 2);
		// Assemble full query
		$ret_str = $query . $values_string . ")";
    return $ret_str;
	}

// -- STATIC FUNCTIONS
	/** 
  *  Function: Return instance of calling base class.
	*/
	public static function fetchById($id) {
    $init_query = self::genRowSelectQueryWithId($id);
    $record = self::$database->fetchArrayFromQuery($init_query);
    if ($record == null) {
      return null; 
    }
    return new static($record);
	}
	
	/** 
  *  Function: Return true iff a database object exists with the specified id.
	*/
	public static function canFetchById($id) {
    $init_query = self::genRowSelectQueryWithId($id);
    $record = self::$database->query($init_query);
    return 1 == $db->numRows($record);
	}
	
	/** 
  *  Function: Return query string
	*/
	public static function genRowSelectQueryWithId($id) {
    return "SELECT * FROM " . static::$tableName . " WHERE " . self::ID_KEY . "=$id";
  }

  public static function genDateTime() {
    date_default_timezone_set("America/Chicago");
    return date("Y-m-d H:i:s");
  }
  
// -- CONSTRUCTOR
	/**
   * Initialize object with params. CAUTION: this function should be called only on params fetched
   * from the db. This is done to maintain synchronization between the db and the access layer
   * (as much as is necessary for the project :P) 
   *
   * @requires row already exists in db representing these params. 
   * @param init_params: map of instance vars for the object (string:key => prim:value)
   */
  protected function __construct($init_params, $is_new_object = false) {
    // Handle creation of new record
    if ($is_new_object) {
      // Initialize child field only. Parent fields haven't 
      // been computed yet (happens on insertion)
      $this->initInstanceVars($init_params);
      $this->validateOrThrow();
      $this->insertRecord($init_params);
      return;
    }

    // Handle fetch of existing record
    $this->initParentInstanceVars($init_params);
  }

  /**
   * insertRecord()
   * - Transform 'init_params' into record. 
   */
  private function insertRecord($init_params) {
    // Insert into db	
    $query = static::genCreateObjectQuery($init_params);
    static::$database->query($query);

    // Fetch id 
    $this->id = mysql_insert_id();
    return $obj;
  }

  protected function getParentDbFields() {
    $parent_db_fields = array(
      self::ID_KEY => $this->id,
      self::CREATED_KEY => $this->createdTime,
      self::LAST_UPDATED_TIME => $this->lastUpdatedTime,
    );

    $child_db_fields = $this->getDbFields();
    $db_fields = array_merge($parent_db_fields, $child_db_fields);
    return $db_fields;
  }

  protected function initParentInstanceVars($init_params) {
    $this->id = $init_params[self::ID_KEY];
    $this->createdTime = $init_params[self::CREATED_KEY];
    $this->lastUpdatedTime = $init_params[self::LAST_UPDATED_TIME];

    // Init child instance vars
    $this->initInstanceVars($init_params);
  }

// -- ABSTRACT METHODS
  /**
   * Sets instance vars of object equal to corresponding parameters in $init_params.
   *
   * @param init_params: map of instance vars for this object (string:key => prim:value)
   */
  protected abstract function initInstanceVars($init_params);
  
  /**
   * Returns map of fields to insert into db. Map is derived from object's instance variables
   * (string:key => prim:value).
   */
  protected abstract function getDbFields(); 

  protected abstract function validateOrThrow();

  protected function deleteChildren() {}

// -- PUBLIC METHODS
  /**
   * Delete object from db.
   */
  private function deleteWithoutTx() {
    $this->deleteChildren();
    $this->deleteAssets();

		// Construct delete query
    $delete_query = "DELETE FROM " . static::$tableName  . " "
      . $this->genPrimaryKeyWhereClause();
    // Execute delete query
    self::$database->query($delete_query);
  }

// -- PROTECTED METHODS
  /**
   * Save object to db.
   */
  public function save() {
    // Validate fields
    $this->validateOrThrow();

    // Get db fields for this object
    $vars = $this->getParentDbFields();
    $vars[self::LAST_UPDATED_TIME] = self::genDateTime();

    // Prepare save query
		$save_query = "UPDATE " . static::$tableName . " SET ";
		foreach ($vars as $key => $value) {
			$save_query .= $key . "='$value', ";
		}
    $save_query = substr($save_query, 0, strlen($save_query) - 2)
      . " " . $this->genPrimaryKeyWhereClause(); 
    
    // Execute save query
    self::$database->query($save_query);
  }

  /**
   * Create "where clause" sql string with unique keys for this object.
   */
  private function genPrimaryKeyWhereClause() {
    // Get unique keys
    return "WHERE ".self::ID_KEY."=".$this->id;
  }

  public function getId() {
    return $this->id;
  }

  public function getCreatedTime() {
    return $this->createdTime;
  }

  public function getLastUpdatedTime() {
    return $this->lastUpdatedTime;
  }
}
?>
