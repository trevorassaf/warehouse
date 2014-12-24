<?php
// -- DEPENDENCIES
require_once(dirname(__FILE__)."/AccessLayerObject.php");
require_once(dirname(__FILE__)."/exceptions/InvalidUniqueKeyException.php");
require_once(dirname(__FILE__)."/exceptions/InvalidObjectStateException.php");

abstract class SqlRecord extends AccessLayerObject {
  // -- CLASS CONSTANTS
  const ID_KEY = "id"; 
  const CREATED_KEY = "created"; 
  const LAST_UPDATED_TIME = "last_updated";
  
  /**
   * String containing name for this database.
   * 
   * @requires must be defined by subclasses.
   */
  protected static $dbName;
  
  /**
   * String containing name for this table.
   *
   * @requires must be defined by subclasses.
   */
  protected static $tableName;
  
  /**
   * List of unique keys for this table. Empty array => no unique keys.
   */
  protected static $uniqueKeys = array();

  private
    $id,
    $createdTime,
    $lastUpdatedTime;

  // -- STATIC FUNCTIONS
  public static function fetchAllSqlRecords() {
    $arrays = static::$database->fetchAllTuplesFromTable(static::$tableName);
    $db_objects = array();
    foreach ($arrays as $array) {
      $db_object = new static($array);
      $db_objects[] = $db_object;
    }
    return $db_objects;
  }
  
  public static function deleteAll() {
    $objects = static::fetchAllObjectsFromTable();
    foreach ($objects as $obj) {
      $obj->delete();
      unset($obj);
    }  
  }
  
  /**
   * Insert object into database and return model.
   * 
   * @param init_params: map of params (string:param_name => string:value).
	 */
  public static function createRecord($init_params) {
    return new static($init_params, true);
  }
  	
	/**
   *  Fetch object from db by unique key.
   *
   *  @param unique_key_map: map of unique key (string:key_name => string:value)
   *  @throws InvalidUniqueKeyException 
   */
  protected static function getObjectByUniqueKey($key, $value) {
    // Verify key is unique
    if (!static::isUniqueKey($key)) {
      throw new InvalidUniqueKeyException($key);
    }

    // Fetch object
    $results = static::getObjectsByParams(
      array($key => $value)
    );

    // Return result
    $num_results = count($results);
    if ($num_results == 1) {
      return $results[0];
    } else if ($num_results == 0) {
      return null;
    } else {
      throw new InvalidUniqueKeyException();
    }
  }

  /**
   * Return true iff $key is a unique key for this object.
   *
   * @param key: string representing db table key.
   */
  protected static function isUniqueKey($key) {
    // Id is a primary key => unique key
    if ($key == ID_KEY) {
      return true;
    }

    // Search through user-defined unique keys
    foreach (static::$uniqueKeys as $unique_key) {
      if ($key == $unique_key) {
        return true;
      }
    }

    return false;
  }
  
  /** 
   * Fetch from db all objects with fields matching those in $params.
   *
   * @param map of parameters (string:key => prim:value)
   */
	protected static function getObjectsByParams($params) {
		// Generate db query
		$query = self::genGetAllObjectsByParamsQuery($params);
    // Retrieve from db
		$records = static::$database->fetchArraysFromQuery($query);
		// Instantiate objects and populate array
    $objects = array();
    foreach ($records as $record) {
      $objects[] = new static($record);
    }
	  return $objects;
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
  public function delete() {
    $this->deleteChildren();

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
