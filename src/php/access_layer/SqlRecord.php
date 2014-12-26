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
   * These two connection ids are used collectively in order to 
   * maintain synchronization between db connections and prepared
   * statements. 'connectionId' is associated with the SqlRecord
   * class and 'connectionSyncId' is associated with each
   * subclass of SqlRecord. 'connectionId' is incremented each
   * time a new database connection is established. When performing
   * a database operation, a child class of SqlRecord compares its
   * 'connectionSyncId' to 'connectionId'. Inequality indicates
   * that a new database connection was made, thereby, invalidating
   * the cached prepared statements. At this point, the child class
   * must unset its existing prepared statements and spawn new ones
   * from the new database connection.
   *
   * @requires child classes define 'connectionSyncId' as 0
   */
  private static $connectionId = 0;
  protected static $connectionSyncId = 0;

  /**
   * Cached prepared statements applicable to all SqlRecord instances.
   */
  protected static $fetchAllRecordsPreparedStatement = null;
  protected static $fetchRecordPreparedStatement = null;
  protected static $deleteRecordPreparedStatement = null;
  protected static $saveRecordPreparedStatement = null;
  protected static $insertRecordPreparedStatement = null;

  /**
   * Cached prepared statements for fetching records from this table.
   * Map of string:query-str => PDOStatement:prepared-statement
   */
  protected static $fetchRecordPreparedStatementsTable = null;

  private
    $id,
    $createdTime,
    $lastUpdatedTime;

  /**
   * beginTx()
   * - Initiate database connection and start a transaction.
   * @requires non-existant database connection
   */
  public final static function beginTx($connection_type=null) {
    // Fail due to existing database transaction 
    assert(!isset(self::$databaseHandle));

    // Initialize db connection
    try {
      self::$databaseHandle = static::$databaseFactory->createConnection($connection_type);
      ++self::$connectionId;
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
  public final static function endTx() {
    // Fail due to non-existant transaction
    assert(isset(self::$databaseHandle));
    
    // Conclude transaction
    try {
      self::$databaseHandle->commit();
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }

    // Close connection 
    self::$databaseHandle = null;
  }

  /**
   * fetchAll()
   * - Atomically fetch all records from table associated 
   *   with calling class.
   * @return array of SqlRecords
   */
  public static function fetchAll() {
    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    // Ensure cache/db synchronization
    static::ensurePreparedStatementCacheSync();

    try {
      // Create prepared statement if non-existant 
      if (!isset(static::$fetchAllRecordsPreparedStatement)) {
        // Create query string
        $query_str = "SELECT * FROM " . static::$tableName;
        static::$fetchAllRecordsPreparedStatement = self::$databaseHandle->prepare($query_str); 
      } 

      // Fetch all records 
      static::$fetchAllRecordsPreparedStatement->execute();
      $raw_record_set = static::$fetchAllRecordsPreparedStatement->fetchAll();

      // Extrude raw records to objects
      $record_objects = array();
      foreach ($raw_record_set as $raw_record) {
        $record_objects[] = new static($raw_record); 
      }
      
      return $record_objects;
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }
  }

  /**
   * deleteAll()
   * - Atomically truncate table associated with the calling class.
   */
  public static function deleteAll() {
    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    // Ensure cache/db synchronization
    static::ensurePreparedStatementCacheSync();

    try {
      $sql_records = static::fetchAll();
      foreach ($sql_records as $record) {
        $record->delete();
      } 
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
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
   * @return SqlRecord instance
   */
  protected static function fetchByCompositeKey($composite_key) {
    // Fail due to invalid composite key
    assert(static::isValidCompositeKey($composite_key));

    // Fetch sql records 
    $results = static::fetchByParams($composite_key);

    // Return null, because query didn't match any record
    $num_results = count($results);
    if ($num_results == 0) {
      return null;
    } 

    // Return single sql record
    if ($num_results == 1) {
      return $results[0];
    } 

    // Failed due to composite key misuse 
    assert(false, "Fetch by composite key returned multiple records.");
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

    // Return single sql record
    if ($num_results == 1) {
      return $results[0];
    } 

    // Failed due to candidate key misuse 
    assert(false, "Candidate key returned multiple records.");
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

      // Number of elements match, so check value equalities 
      $num_matched_keys = 0;
      foreach ($valid_composite_key as $vck_element) {
        foreach ($composite_key as $ck_element) {
          if ($vck_element == $ck_element) {
            ++$num_matched_keys;
          }
        }   
      }

      // Provided composite key is valid 
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
   * @param params : Map(string:key => Value:value)
   */
	protected static function fetchByParams($params) {
    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    // Ensure cache/db synchronization
    static::ensurePreparedStatementCacheSync();
    
    // Generate db query
    $query_str = self::genFetchByParamsQuery($params);

    try {
      // Create prepared statement, if nonextant
      if (!isset(static::$fetchRecordPreparedStatementsTable[$query_str])) {
        static::$fetchRecordPreparedStatementsTable[$query_str] = 
            self::$databaseHandle->prepare($query_str);
      }

      // Fetch record
      $stmt = static::$fetchRecordPreparedStatementsTable[$query_str];
      foreach ($params as $key => $value) {
        $key_for_prepared_statement = self::transformForPreparedStatement($key); 
        $stmt->bindValue(
            $key_for_prepared_statement,
            $value->getValue(),
            $value->getType()
        );
      }
      $stmt->execute();
      
      // Extrude raw records to objects
      $raw_record_set = $stmt->fetchAll();
      foreach ($raw_record_set as $raw_record) {
        $record_objects[] = new static($raw_record); 
      }
      
      return $record_objects;
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    } 
  }

  /**
   * transformForPreparedStatement()
   * - Alter 'term' into format compatible with prepared statements
   * @param term : string
   * @return string : pdo key string
   */
  private static function transformForPreparedStatement($term) {
    // Fail because 'term' has already been transformed
    assert($term[0] != ":");
    return ":" . $term;
  }

  /**
   * genFetchByParamsQuery()
   * - Return string containing query for fetching objects with fields 
   *   matching those in 'params'
   * @param params: map of parameters (string:key => prim:value)
   * @return string : query string
   */
  private static function genFetchByParamsQuery($params) {
    $query = "SELECT * FROM " . static::$tableName . " WHERE ";
    foreach ($params as $key) {
      $transformed_key_name = self::transformForPreparedStatement($key);
      $query .= $key .'=' . $transformed_key_name;
      $query .= ' AND ';
    }
    $query = substr($query, 0, -5);
    return $query;
  } 
  
  /** 
   * genInsertRecordQuery()
   * - Create sql query for fetching object from db.
   * @param init_params: map of parameters for object (string:key => prim:value)
   * @return string : query string
	 */
  private static function genInsertRecordQuery($init_params) {
    $query = "INSERT INTO " . static::$tableName . " (";
		$values_string = ") VALUES (";
		foreach ($init_params as $key => $value) {
      // Skip key if value is null
      if ($value === null) {
        continue;
      }

      // Accumulate key specification string
      $query .= $key . ", ";

      // Accumulate value specification string with PDO param bindings 
      $transformed_key_name = self::transformForPreparedStatement($key);
      $values_string .= "'$transformed_key_name', "; 
		}
    
    // Trim terminal commas from strings
		$query = substr($query, 0, strlen($query) - 2);
		$values_string = substr($values_string, 0, strlen($values_string) - 2);
    
    // Assemble full query
    return $query . $values_string . ")";
	}

  /**
   * fetchById()
   * - Fetch sql record with specified id.
   * @param id : unsigned int 
   */
  public static function fetchById($id) {
    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    // Ensure cache/db synchronization
    static::ensurePreparedStatementCacheSync();

    try {
      // Create prepared statement if non-existant
      if (!isset(static::$fetchRecordPreparedStatement)) {
        $fetch_query = "SELECT * FROM " . static::$tableName . " WHERE " 
            . self::ID_KEY . "=" . self::transformForPreparedStatement(self::ID_KEY);
        static::$fetchRecordPreparedStatement =
            self::$databaseHandle->prepare($fetch_query); 
      }  

      // Fetch sql record
      $transformed_id_key = self::transformForPreparedStatement(self::ID_KEY);
      static::$fetchRecordPreparedStatement
          ->bindValue($transformed_id_key, $id, PDO::PARAM_INT);
      static::$fetchRecordPreparedStatement->execute();
      $results = static::$fetchRecordPreparedStatement->fetchAll();
      
      // Return null because not elements found
      if ($results == null || empty($results)) {
        return null;
      }

      // Faile due to multiple records returned in 'id' fetch
      if (count($results) > 1) {
        die("Fetch by 'id' returned multiple records for " . static::$tableName);
      }

      return new static($results[0]);
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    } 
	}
  
  /**
   * __construct()
   * - Initializes SqlRecord object. Inserts row if 'is_new_object' == true.
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
    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    // Ensure cache/db synchronization
    static::ensurePreparedStatementCacheSync();

    try {
      // Create prepared statement if non-existant
      if (!isset(static::$insertRecordPreparedStatement)) {
        $insert_query = static::genInsertRecordQuery($init_params);
        static::$insertRecordPreparedStatement = 
            self::$databaseHandle->prepare($insert_query);
      }

      // Bind params to query
      foreach ($init_params as $field) {
        static::$insertRecordPreparedStatement
          ->bindValue(
              self::transformForPreparedStatement($field->getKey()),
              $field->getValue(),
              $field->getType());
      }

      // Insert record
      static::$insertRecordPreparedStatement->execute();
      $this->id = static::$databaseHandle::lastInsertId(); 
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }
  }

  /**
   * getParentDbFields()
   * - Generate field set for inserting record into db.
   */
  protected function getParentDbFields() {
    $parent_db_fields = array(
      self::ID_KEY => $this->id,
    );

    $child_db_fields = $this->getDbFields();
    $db_fields = array_merge($parent_db_fields, $child_db_fields);
    return $db_fields;
  }

  /**
   * initParentInstanceVars()
   * - Initialize instance vars from raw sql record.
   */
  protected function initParentInstanceVars($init_params) {
    $this->id = $init_params[self::ID_KEY];
    $this->createdTime = $init_params[self::CREATED_KEY];
    $this->lastUpdatedTime = $init_params[self::LAST_UPDATED_TIME];

    // Init child instance vars
    $this->initInstanceVars($init_params);
  }

  /**
   * initInstanceVars()
   * - Sets instance vars of object equal to corresponding parameters in $init_params.
   * @param init_params: map of instance vars for this object (string:key => prim:value)
   */
  protected abstract function initInstanceVars($init_params);
  
  /**
   * getDbFields()
   * - Returns map of fields to insert into db. Map is derived from object's instance variables
   * (string:key => prim:value).
   */
  protected abstract function getDbFields(); 

  protected abstract function validateOrThrow();

  protected function deleteChildren() {}
  protected function deleteAssets() {}

  /**
   * delete()
   * - Delete object from db.
   */
  private function delete() {
    $this->deleteChildren();
    $this->deleteAssets();

		// Construct delete query
    $delete_query = "DELETE FROM " . static::$tableName  . " "
      . $this->genPrimaryKeyWhereClause();
    // Execute delete query
    self::$database->query($delete_query);
  }

  /**
   * save()
   * - Update record associated with this instance.
   */
  public function save() {
    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));
    
    // Validate fields
    $this->validateOrThrow();

    // Ensure cache/db synchronization
    static::ensurePreparedStatementCacheSync();

    try {
      // Get db fields for this object
      $record_fields = $this->getParentDbFields();

      // Create save-record prepared statement if non-existant
      if (!isset(static::$saveRecordPreparedStatement)) {
        // Create query string
        $save_query_str = self::genSaveRecordQuery($record_fields);
        static::$saveRecordPreparedStatement = 
            self::$databaseHandle->prepare($save_query_str);
      }

      // Bind record fields 
      foreach ($record_fields as $key => $value) {
        static::$saveRecordPreparedStatement->bindValue(
          self::transformForPreparedStatement($key),
          $value->getValue(),
          $value->getType()
        );
      }
      
      // Save record fields
      static::$saveRecordPreparedStatement->execute();
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }    
  }

  /**
   * genSaveRecordQuery()
   * - Produce query for saving record.
   * @return string : query
   */
  private static function genSaveRecordQuery($record_fields) {
		$save_query = "UPDATE " . static::$tableName . " SET ";
		foreach ($vars as $key) {
			$save_query .= $key . "=" . self::transformForPreparedStatement($key) . ", ";
		}
    return substr($save_query, 0, strlen($save_query) - 2)
      . " " . static::genPrimaryKeyWhereClause(); 
  }

  /**
   * ensurePreparedStatementCacheSync()
   * - Invalidate cache if it's unsynchronized with the database
   *   connection. Synchronization is determined by equality
   *   between 'connectionSyncId' and 'connectionId'
   */
  private static function ensurePreparedStatementCacheSync() {
    // Exit early because prepared statement cache is synchornized
    // with the current database connection
    if (static::$connectionSyncId == self::$connectionId) {
      return;
    }

    // Invalidate prepared statement cache due to unsynchronized cache/database
    static::$fetchAllRecordsPreparedStatement = null;
    static::$fetchRecordPreparedStatement = null;
    static::$deleteRecordPreparedStatement = null;
    static::$saveRecordPreparedStatement = null;
    static::$insertRecordPreparedStatement = null;
    static::$fetchRecordPreparedStatementsTable = array();

    // Resynchronize cache and database
    static::$connectionSyncId = self::$connectionId;
  }

  /**
   * genPrimaryKeyWhereClause()
   * - Create "where clause" sql string with unique keys for this object.
   * @return string : query 'where' clause
   */
  private static function genPrimaryKeyWhereClause() {
    return "WHERE " . self::ID_KEY . "=" . self::transformForPreparedStatement(self::ID_KEY);
  }

  /**
   * getId()
   * - Getter for id
   * @return unsigned int : record id
   */
  public function getId() {
    return $this->id;
  }

  /**
   * getCreatedTime()
   * - Getter for timestamp at which record was created.
   * @return string : unix timestamp
   */
  public function getCreatedTime() {
    return $this->createdTime;
  }

  /**
   * getLastUpdatedTime()
   * - Getter for timestamp at which record was last updated.
   * @return string : unix timestamp
   */
  public function getLastUpdatedTime() {
    return $this->lastUpdatedTime;
  }
}
