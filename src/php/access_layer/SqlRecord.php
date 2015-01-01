<?php
// -- DEPENDENCIES
require_once(dirname(__FILE__)."/AccessLayerObject.php");
require_once(dirname(__FILE__)."/exceptions/InvalidUniqueKeyException.php");
require_once(dirname(__FILE__)."/exceptions/InvalidObjectStateException.php");

/**
 * Represents row in a sql table.
 *
 * Child class definitions:
 *   @requires class name matches table name 
 * 
 * Vars:
 *   @requires defines 'alternateKeys' to indicate alternate keys 
 *   @requires defines 'compositeKeys' to indicate composite keys
 * 
 * Functions:
 *   @requires defines 'getDbFields()' for additional db fields
 *   @requires defines 'initInstanceVars()' for additional db fields
 *   @requires defines 'validateOrThrow()' to add validation logic
 *   @requires defines 'deleteChildren()' for records connected by foreign key
 *   @requires defines 'deleteAssets()' for records associated with other data outisde of db
 */
abstract class SqlRecord extends AccessLayerObject {
  
  // Db Keys
  const ID_KEY = "id"; 
  const CREATED_KEY = "created"; 
  const LAST_UPDATED_TIME = "last_updated";
  
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
   * The number of times a transaction has been opened by the user.
   * We only ever keep actual db transaction open. This pattern supports
   * nested transactions subsumed into the global one. 
   */
  private static $numTransactions = 0;

  /**
   * AssetDeletors scheduled for deletion at the next commit.
   */
  private static $assetDeletors = null;

  /**
   * Cached prepared statements applicable to all SqlRecord instances.
   */
  private static $fetchAllRecordsPreparedStatementCache = null;
  private static $deleteRecordPreparedStatementCache = null;
  private static $saveRecordPreparedStatementCache = null;
  private static $insertRecordPreparedStatementCache = null;

  /**

   * Cached prepared statements for fetching records from this table.
   * Map of string:query-str => PDOStatement:prepared-statement
   */
  private static $fetchRecordPreparedStatementsCacheTable = null;

  // Db Fields
  private
    $id,
    $createdTime,
    $lastUpdatedTime;

  /**
   * True iff 'delete' has been called on this instance.
   */
  private $hasBeenDeleted;

  /**
   * beginTx()
   * - Initiate database connection and start a transaction.
   * @requires non-existant database connection
   */
  public final static function beginTx($connection_type=null) {
    // Fail due to invalid num-transactions
    assert(self::$numTransactions >= 0);
    
    // Track number of transactions 
    ++self::$numTransactions;

    // Short circuit so that we never initiate more than 1 transaction 
    if (self::$numTransactions > 1) {
      return;
    }
    
    // Fail due to pre-existing database transaction 
    assert(!isset(self::$databaseHandle));

    // Fail due to uninitialized database factory
    assert(isset(self::$databaseFactory));

    try {
      // Initialize db connection
      self::$databaseHandle = parent::$databaseFactory->getConnection($connection_type);
      self::$databaseHandle->beginTransaction();
    } catch (PDOException $e) {
      die("\nERROR: " . $e->getMessage() . "\n");
    }
       
    // Initialize asset deletors
    self::$assetDeletors = array();
  }

  /**
   * endTx()
   * - Close transaction and end database connection.
   * @requires existing database connection
   */
  public final static function endTx() {
    // Fail due to calling endTx without initiating a transaction
    assert(self::$numTransactions > 0);

    // Track the number of transaction-close calls so we know when to
    // close the actual db transaction.
    --self::$numTransactions;

    // Short circuit so we wait to close the encapsulating transaction
    if (self::$numTransactions > 0) {
      return;
    }

    // Fail due to non-existant transaction
    assert(isset(self::$databaseHandle));

    // Conclude transaction
    try {
      self::$databaseHandle->commit();
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }

    // Remove assets from file system.
    self::deleteAssets();

    // Close connection 
    self::$databaseHandle = null;

    // Invalidate prepared statement cache
    self::$fetchAllRecordsPreparedStatementCache = null;
    self::$deleteRecordPreparedStatementCache = null;
    self::$insertRecordPreparedStatementCache = null;
    self::$fetchRecordPreparedStatementsCacheTable = null;
  }

  /**
   * fetchAll()
   * - Atomically fetch all records from table associated 
   *   with calling class.
   * @return array of SqlRecords
   */
  public static function fetchAll() {
    // Initialize fetch-all cache, if necessary
    if (!isset(self::$fetchAllRecordsPreparedStatementCache)) {
      self::$fetchAllRecordsPreparedStatementCache = array();
    }

    // Cache calling subclass name 
    $called_class_name = get_called_class();
    
    // Begin implicit transaction if explicit one doesn't exist
    $is_implicit_tx = false;
    if (self::$numTransactions == 0) {
      $is_implicit_tx = true; 
      self::beginTx();
    }
    
    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    try {
      // Create prepared statement if nonextant 
      if (!isset(self::$fetchAllRecordsPreparedStatementCache[$called_class_name])) {
        $query_str = "SELECT * FROM " . $called_class_name;
        self::$fetchAllRecordsPreparedStatement[$called_class_name] =
            self::$databaseHandle->prepare($query_str); 
      } 

      // Fetch all records
      $fetch_all_stmt = self::$fetchAllRecordsPreparedStatementCache[$called_class_name]; 
      $fetch_all_stmt->execute();
      $raw_record_set = $fetch_all_stmt->fetchAll();
      
      // Conclude implicit tx
      if ($is_implicit_tx) {
        self::endTx();
      }

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
    // Initiate implicit tx if explicit one doesn't exist
    $is_implicit_tx = false;
    if (self::$numTransactions == 0) {
      $is_implicit_tx = true; 
      self::beginTx();
    }

    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    try {
      $sql_records = static::fetchAll();
      foreach ($sql_records as $record) {
        $record->delete();
      } 
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }

    // Close implicit tx
    if ($is_implicit_tx) {
      self::endTx();
    }
  }

  /**
   * insert()
   * @Override AccessLayerObject
   */
  protected static function insert($init_params) {
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
    // Cache calling subclass name
    $called_class_name = get_called_class();
    
    // Generate db query
    $query_str = self::genFetchByParamsQuery($params);

    // Initialize cache, if necessary
    if (!isset(self::$fetchRecordPreparedStatementsCacheTable)) {
      self::$fetchRecordPreparedStatementsCacheTable = array();
    }
    
    if (!isset(self::$fetchRecordPreparedStatementsCacheTable[$called_class_name])) {
      self::$fetchRecordPreparedStatementsCacheTable[$called_class_name] = array();
    }

    // Cache fetch record
    $fetch_record_cache = self::$fetchRecordPreparedStatementsCacheTable[$called_class_name];

    // Initiate implicit transaction, if necessary
    $is_implicit_tx = false;
    if (self::$numTransactions == 0) {
      $is_implicit_tx = true;
      self::beginTx();
    }
    
    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    try {
      // Create prepared statement, if nonextant
      if (!isset($fetch_record_cache[$query_str])) {
        $fetch_record_cache[$query_str] = self::$databaseHandle->prepare($query_str);
      }

      // Fetch record
      $fetch_stmt = $fetch_record_cache[$query_str];
      foreach ($params as $p) {
        $key_for_prepared_statement = self::transformForPreparedStatement($p->getKeyName()); 
        $fetch_stmt->bindValue(
            $key_for_prepared_statement,
            $p->getValue(),
            $p->getType()
        );
      }
      $fetch_stmt->execute();
      $raw_record_set = $fetch_stmt->fetchAll();

      // Close implicit tx
      if ($is_implicit_tx) {
        self::endTx();
      }

      // Extrude raw records to objects
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
    $query = "SELECT * FROM " . get_called_class() . " WHERE ";
    foreach ($params as $p) {
      $transformed_key_name = self::transformForPreparedStatement($p->getKeyName());
      $query .= $p->getKeyName() .'=' . $transformed_key_name;
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
    $query = "INSERT INTO " . get_called_class() . " (";
		$values_string = ") VALUES (";
		foreach ($init_params as $param) {
      // Skip key if value is null
      if ($param->getValue() === null) {
        continue;
      }
      
      $query .= $param->getKeyName() . ", ";

      // Accumulate value specification string with PDO param bindings 
      $transformed_key_name = self::transformForPreparedStatement($param->getKeyName());
      $values_string .= "'$transformed_key_name', "; 
		}
    
    // Trim redundant commas from strings
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
    return static::fetchByKey(self::ID_KEY, $id);
	}
  
  /**
   * __construct()
   * - Initializes SqlRecord object. Inserts row if 'is_new_object' == true.
   * @param init_params: map of instance vars for the object (string:key => prim:value)
   */
  protected function __construct($init_params, $is_new_object=false) {
    // Inidicate this instance's association with stored db record 
    $this->hasBeenDeleted = false;
    
    // Create new record if indicated by client, otherwise fetch
    // from existing record
    if ($is_new_object) {
      $this->initInstanceVars($init_params);
      $this->validateOrThrow();
      $this->insertRecord($init_params);
    } else {
      $this->initParentInstanceVars($init_params);
    }
  }

  /**
   * insertRecord()
   * - Transform 'init_params' into record. 
   */
  private function insertRecord($init_params) {
    // Initialize insert record cache, if nonextant
    if (!isset(self::$insertRecordPreparedStatementCache)) {
      self::$insertRecordPreparedStatementCache = array();
    }

    $called_class_name = get_called_class();

    // Initiate implicit transaction, if no explicit transaction
    $is_implicit_tx = false;
    if (self::$numTransactions == 0) {
      $is_implicit_tx = true;
      self::beginTx();
    }

    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    try {
      // Create prepared statement if necessary 
      if (!isset(self::$insertRecordPreparedStatementCache[$called_class_name])) {
        $insert_query = static::genInsertRecordQuery($init_params);
        self::$insertRecordPreparedStatementCache[$called_class_name] = 
            self::$databaseHandle->prepare($insert_query);
      }

      $insert_stmt = self::$insertRecordPreparedStatementCache[$called_class_name];

      // Bind params to query
      foreach ($init_params as $field) {
        $insert_stmt->bindValue(
            self::transformForPreparedStatement($field->getKeyName()),
            $field->getValue(),
            $field->getType()
        );
      }

      // Insert record
      $insert_stmt->execute();
      $this->id = self::$databaseHandle->lastInsertId(); 
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }

    // Close implicit transaction
    if ($is_implicit_tx) {
      self::endTx();
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
  protected function initInstanceVars($init_params) {}
  
  /**
   * getDbFields()
   * - Returns map of fields to insert into db. Map is derived from object's instance variables
   * (string:key => prim:value).
   */
  protected function getDbFields() { return array(); }

  /**
   * validateOrThrow()
   * - Throw exception if record is in invalid state.
   */
  protected function validateOrThrow() {}

  /**
   * deleteChildren()
   * - Remove children records.
   */
  protected function deleteChildren() {}

  /**
   * getAssets()
   * - Get assets for this instance.
   * @return array(AssetDeletor)
   */
  protected function getAssets() { return array(); }

  /**
   * delete()
   * - Delete object from db.
   */
  public function delete() {
    // Fail due to double delete
    assert(!$this->hasBeenDeleted);

    // Initialize delete record cache, if nonextant
    if (!isset(self::$deleteRecordPreparedStatementCache)) {
      self::$deleteRecordPreparedStatementCache = array();
    }
    
    $called_class_name = get_called_class();

    // Initiate implicit tx, if nonextant explicit tx 
    $is_implicit_tx = false;
    if (self::$numTransactions == 0) {
      $is_implicit_tx = true; 
      self::beginTx();
    }

    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));
    
    try {
      // Create delete recortd statement, if nonextant 
      if (!isset(self::$deleteRecordPreparedStatementCache[$called_class_name])) {
        $delete_query = "DELETE FROM " . $called_class_name  . " "
            . $this->genPrimaryKeyWhereClause();
        $delete_record_cache = self::$databaseHandle->prepare($delete_query);
      }

      $delete_record_stmt = self::$deleteRecordPreparedStatementCache[$called_class_name];
      
      // Delete children, schedule assets for deletion
      $this->deleteChildren();
      self::$assetDeletors = array_merge(self::$assetDeletors, $this->getAssets());

      // Bind 'id'
      $delete_record_stmt->bindValue(
          self::transformForPreparedStatement(self::ID_KEY),
          $this->id,
          PDO::PARAM_INT
      );

      // Remove record
      $delete_record_stmt->execute();
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }

    // Close implicit transaction
    if ($is_implicit_tx) {
      self::endTx();
    }

    // Indicate deletion of this instance
    $this->hasBeenDeleted = true;
  }

  /**
   * deleteAssets()
   * - Remove assets from file system.
   */
  private static function deleteAssets() {
    // Fail due to double delete
    assert(!$this->hasBeenDeleted);

    // Fail due to invalid asset deletors 
    assert(isset(self::$assetDeletors));

    // Remove assets 
    foreach (self::$assetDeletors as $deletor) {
      $deletor->delete();
    }

    // Clear AssetDeletors cache
    self::$assetDeletors = array();
  }

  /**
   * save()
   * - Update record associated with this instance.
   */
  public function save() {
    // Fail due to attempting to save deleted record 
    assert(!$this->hasBeenDeleted);

    // Fail if fields are invalid
    $this->validateOrThrow();

    // Get db fields for this object
    $record_fields = $this->getParentDbFields();

    // Initialize save record cache, if necessary
    if (!isset(self::$saveRecordPreparedStatementCache)) {
      self::$saveRecordPreparedStatementCache = array();
    }

    $called_class_name = get_called_class();
    
    $is_implicit_tx = false;

    try {
      // Create save-record prepared statement if non-existant
      if (!isset(self::$saveRecordPreparedStatement[$called_class_name])) {
        // Create query string
        $save_query_str = self::genSaveRecordQuery($record_fields);
        self::$saveRecordPreparedStatement[$called_class_name] = 
            self::$databaseHandle->prepare($save_query_str);
      }

      $save_record_stmt = self::$saveRecordPreparedStatementCache[$called_class_name];

      // Bind record fields 
      foreach ($record_fields as $field) {
        $save_record_stmt->bindValue(
            self::transformForPreparedStatement($field->getKeyName()),
            $field->getValue(),
            $field->getType()
        );
      }
      
      // Initiate implicit transaction, if needed
      if (self::$numTransactions == 0) {
        $is_implicit_tx = true; 
        self::beginTx();
      }

      // Fail due to invalid database connection
      assert(isset(self::$databaseHandle));

      // Save record fields
      $save_record_stmt->execute();
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }

    // Close implicit tx
    if ($is_implicit_tx) {
      self::endTx();
    }
  }

  /**
   * genSaveRecordQuery()
   * - Produce query for saving record.
   * @return string : query
   */
  private static function genSaveRecordQuery($record_fields) {
		$save_query = "UPDATE " . get_called_class() . " SET ";
		foreach ($record_fields as $field) {
			$save_query .= $field->getKeyName() . "=" . self::transformForPreparedStatement($field->getKeyName()) . ", ";
		}
    
    return substr($save_query, 0, strlen($save_query) - 2)
        . " " . static::genPrimaryKeyWhereClause(); 
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
