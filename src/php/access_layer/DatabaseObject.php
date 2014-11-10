<?php
// -- DEPENDENCIES
require_once(dirname(__FILE__)."/AccessLayerObject.php");
require_once(dirname(__FILE__)."/exceptions/InvalidUniqueKeyException.php");

/** -- CLASS DEFINITION
*   Description:
*     - Base class of all objects that can be represented as records in a db table.
*         Control of record insertion, retrieval, and deletion reside here. The constructor   
*         expects that the corresponding record resides in the db.
*
*   Instructions for sub-classes:
*     - REQUIRED:
*         - define "db_name" class variable with name of the db in which the tables reside.
*         - define "table_name" class variable with name of the table in which records reside.
*         - implement abstract methods:
*             - initAncillaryVars($init_params):
*                 - initialize all instance variables from key/value pairs in $init_params.
*                     NOTE: do not initialize "id," "created," or "updated."
*                 - $init_params is a dictionary containing all fields from db record.
*             - genAncillaryDbVars():
*                 - return dictionary containing all db keys mapped to corresponding 
*                     instance vars. NOTE: do not include "id," "created," "updated."
*     - OPTIONAL:
*         - define "unique_keys" class variable with array containing the names of all
*             fields that are considered "unique." NOTE: do not include "id" as a 
*             unique field; this "unique_keys" is for programmatically defined unique 
*             keys only!
*         - call $this->save() whenever instance variables are modified. 
*
*	@abstract
*		- DatabaseObject:
*			- protected initAncillaryDbVars($init_params)
*			- protected genAncillaryDbVars()
*/
abstract class DatabaseObject extends AccessLayerObject {
// -- CLASS VARIABLES
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

// -- STATIC FUNCTIONS
	public static function fetchAllObjectsFromTable() {
    $arrays = static::$database->fetchAllArraysFromTable(static::$table_name);
    $db_objects = array();
    foreach ($arrays as $array) {
      $db_object = new static($array);
      $db_objects[] = $object;
    }
    return $db_objects;
  }
  
  /**
   * Insert object into database and return model.
   * 
   * @param init_params: map of params (string:param_name => string:value).
   * @throws DuplicateDbCreationViolation 
	 */
  public static function createObject($init_params) {
    // Generate db query
    $query = static::genCreateObjectQuery($init_params);
    // Insert into db	
    static::$database->query($query);
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
      $query .= $k ."='".$v."' AND ";
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
      $query .= $key . ", ";
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
    if ($is_new_object) {
      $init_params = $this->createObjectCallback($init_params);
    }
    $this->initInstanceVars($init_params);
  }

// -- STATIC FUNCTIONS
	/** 
  *  Function: Return instance of calling base class.
	*/
	public static function fetchDbObjectById($id) {
          $init_query = self::genRowSelectQueryWithId($id);
          $record = self::$database->fetchArrayFromQuery($init_query);
          return new static($record); // may be problematic
	}
	
	/** 
  *  Function: Return true iff a database object exists with the specified id.
	*/
	public static function doesDbObjectExistWithId($id) {
          $init_query = self::genRowSelectQueryWithId($id);
          $record = self::$database->query($init_query);
          return 1 == $db->numRows($record);
	}
	
	/** 
  *  Function: Return query string
	*/
	public static function genRowSelectQueryWithId($id) {
          return "SELECT * FROM " . self::$tableName . " WHERE " . DB_ID_KEY . "=$id";
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

  /**
   * Returns map of primary keys for this db table. (string:key => prim:value)
   */
  protected abstract function getPrimaryKeys();

  /**
   * Callback that runs after object is created.
   */
  protected function createObjectCallback($init_params) {}

// -- PUBLIC METHODS
  /**
   * Delete object from db.
   */
  public function delete() {
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
    // Get db fields for this object
    $vars = $this->getDbFields();

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
    $unique_keys = $this->getPrimaryKeys(); 
    $query = "WHERE ";
    foreach ($unique_keys as $key => $value) {
      $query .= $key . " = '" . $value . "' AND ";  
    }
    $query = substr($query, 0, strlen($query) - 5);
    return $query;
  }
}
?>
