<?php
// -- DEPENDENCIES
require_once(dirname(__FILE__)."/AccessLayerObject.php");
require_once(dirname(__FILE__)."/exceptions/InvalidUniqueKeyException.php");
require_once(dirname(__FILE__)."/exceptions/InvalidObjectStateException.php");
require_once(dirname(__FILE__)."/AccessLayerField.php");

/**
 * Represents row in a sql table.
 *
 * ---- Public static functions ----
 * - List<SqlRecord> fetchAll()               : fetch all records from table
 * - void deleteAll()                         : remove all records from table
 * - List<SqlRecord >fetch(field-map)         : fetch record set from table 
 * - SqlRecord fetchById(id)                  : fetch single record by id
 * - SqlRecord fetchByKey(name, value)        : fetch single record by key
 * - SqlRecord fetchByCompositeKey(field-map) : fetch single record by composite record
 * - SqlRecord insert(field-map)              : insert record into db with field-map  
 *
 * ---- Public methods ----
 * - delete()             : remove record and related assets, disable instance
 * - save()               : update record
 * - getId()              : return record id
 * - getCreatedTime()     : return created time of this record
 * - getLastUpdatedTime() : return last updated time of this record
 *
 * ---- Child class requirements ---- 
 * Naming:
 *   @require class name matches table name 
 *
 * Hierarchy:
 *   @require database classes inherit from this class
 *   @require table classes inherit from corresponding database class
 * 
 * Vars:
 *   @require defines 'alternateKeys' to indicate alternate keys 
 *   @require defines 'compositeKeys' to indicate composite keys
 * 
 * Mandatory Functions:
 *   @require defines 'genChildDbFieldTableTemplate()' to define the table's fields
 *
 * Optional Functions:
 *   @option defines 'validateOrThrow()' to add validation logic
 *   @option defines 'deleteChildren()' for records connected by foreign key
 *   @option defines 'deleteAssets()' for records associated with other data outisde of db
 * -------------------------------
 */
abstract class SqlRecord extends AccessLayerObject {
  
  // Db Keys
  const ID_KEY = "id"; 
  const CREATED_KEY = "created"; 
  const LAST_UPDATED_TIME_KEY = "lastUpdated";

  /**
   * List of keys for this table.
   */
  protected static $keys = array();

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

  /**
   * Parent db fields.
   */
  private $parentDbFieldTable;

  /**
   * Table for child db fields.
   */
  protected $childDbFieldTable;

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
    assert(isset(static::$databaseFactory));

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

    try {
      // Conclude transaction
      self::$databaseHandle->commit();
      // Close connection 
      self::$databaseHandle = null;
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }

    // Remove assets
    self::deleteAssets();

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
    $table_name = static::getTableName();
    
    // Begin implicit transaction if explicit one doesn't exist
    $is_implicit_tx = false;
    if (self::$numTransactions == 0) {
      $is_implicit_tx = true; 
      self::beginTx();
    }
    
    // Fail because 'databaseHandle' wasn't initialized by 'beginTx()'
    assert(isset(self::$databaseHandle));

    try {
      // Create prepared statement if nonextant 
      if (!isset(self::$fetchAllRecordsPreparedStatementCache[$table_name])) {
        $fully_qualified_table_name = static::getFullyQualifiedTableName();
        $query_str = "SELECT * FROM {$fully_qualified_table_name}";
        self::$fetchAllRecordsPreparedStatementCache[$table_name] =
            self::$databaseHandle->prepare($query_str); 
      } 

      // Fetch all records
      $fetch_all_stmt = self::$fetchAllRecordsPreparedStatementCache[$table_name]; 
      $fetch_all_stmt->execute();
      $raw_record_set = $fetch_all_stmt->fetchAllRows();
      
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

    // Fail because 'databaseHandle' wasn't initialized by 'beginTx()' 
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
   * isValidKey()
   * - Return true iff 'composite_key' is valid composite key.
   * @param param_map : Map<string:key, mixed:value>
   */
  private static function isValidKey($param_map) {
    // Any 'param-map' containing 'id' key is valid
    if (isset($param_map[self::ID_KEY])) {
      return true;
    }

    // Return false if no unique keys exist
    if (empty(static::$keys)) {
      return false;
    }

    $param_map_size = count($param_map);

    foreach (static::$keys as $valid_key_set) {
      // Skip if the number of elements don't match
      $vck_size = count($valid_key_set);
      if ($param_map_size < $vck_size) {
        continue;
      }

      // Number of elements match, so check value equalities 
      $num_matched_keys = 0;
      foreach ($valid_key_set as $vck_element) {
        foreach ($param_map as $param_name => $param_value) {
          if ($vck_element == $param_name) {
            ++$num_matched_keys;
          }
        }   
      }

      // Provided composite key is valid 
      if ($num_matched_keys == $vck_size) {
        return true;
      }
    }
    return false;
  }
  
  /** 
   * fetch()
   * - Fetch all records matching 'params'. Records fetched from table
   *   associated with calling class.
   * @param params : Map(string:key => Value:value)
   */
	public static function fetch($param_map) {
    // Cache table name 
    $table_name = static::getTableName();
    
    // Generate db query
    $query_str = self::genFetchByParamsQuery($param_map);

    // Initialize cache, if necessary
    if (!isset(self::$fetchRecordPreparedStatementsCacheTable)) {
      self::$fetchRecordPreparedStatementsCacheTable = array();
    }
    
    if (!isset(self::$fetchRecordPreparedStatementsCacheTable[$table_name])) {
      self::$fetchRecordPreparedStatementsCacheTable[$table_name] = array();
    }

    // Cache fetch record
    $fetch_record_cache = self::$fetchRecordPreparedStatementsCacheTable[$table_name];

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
      $parent_db_field_map = static::genParentDbFieldTableTemplate();
      $child_db_field_map = static::genChildDbFieldTableTemplate(); 
      $db_field_map = array_merge($parent_db_field_map, $child_db_field_map);
      
      $fetch_stmt = $fetch_record_cache[$query_str];
      foreach ($param_map as $p_name => $p_value) {
        // Fail due to invalid field name
        assert(isset($db_field_map[$p_name]));

        // Bind value to field in prepared statement
        $key_for_prepared_statement = self::transformForPreparedStatement($p_name); 
        $fetch_stmt->bindValue(
            $key_for_prepared_statement,
            $p_value,
            $db_field_map[$p_name]->getDataType()
        );
      }
      $fetch_stmt->execute();
      $raw_record_set = $fetch_stmt->fetchAllRows();

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
  private static function genFetchByParamsQuery($param_map) {
    $fully_qualified_table_name = static::getFullyQualifiedTableName();
    $query = "SELECT * FROM {$fully_qualified_table_name} WHERE ";
    foreach ($param_map as $p_name => $p_value) {
      $transformed_key_name = self::transformForPreparedStatement($p_name);
      $query .= $p_name .'=' . $transformed_key_name;
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
    $fully_qualified_table_name = static::getFullyQualifiedTableName();
    $database_name =  
    $query = "INSERT INTO {$fully_qualified_table_name} (";
		$values_string = ") VALUES (";
		foreach ($init_params as $name => $value) {
      $query .= $name . ", ";

      // Accumulate value specification string with PDO param bindings 
      $transformed_key_name = self::transformForPreparedStatement($name);
      $values_string .= "$transformed_key_name, "; 
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
   * @return SqlRecord : record
   */
  public static function fetchById($id) {
    return static::fetchByKey(self::ID_KEY, $id);
  }

  /**
   * fetchByKey()
   * - Fetch sql record with specified unique key.
   * @param key : name of field
   * @param value : value of field
   * @return SqlRecord : record
   */
  public static function fetchByKey($key, $value) {
    $param_map = array($key => $value);   
    return static::fetchByCompositeKey($param_map);
  }

  /**
   * fetchByCompositeKey()
   * - Fetch sql record with specified params.
   * @param param_map : Map<string:field-name, mixed:values>
   * @return SqlRecord : record
   */
  public static function fetchByCompositeKey($param_map) {
    // Fail due to invalid key
    assert(self::isValidKey($param_map));  
    
    $sql_record_results = static::fetch($param_map);

    // Return null if no matching record
    if (empty($sql_record_results)) {
      return null;
    }

    // Fail because 'sql_record_results' should countain at most 1 element 
    assert(count($sql_record_results) == 1);

    return $sql_record_results[0];
  }
  
  /**
   * __construct()
   * - Initializes SqlRecord object. Inserts row if 'is_new_object' == true.
   * @param init_params : map of instance vars for the object (string:key => prim:value)
   */
  protected function __construct($init_params) {
    // Inidicate this instance's association with stored db record 
    $this->hasBeenDeleted = false;

    // Initialize and set values in field table
    $this->initDbFieldTables($init_params);
    $this->validateOrThrow();
  }

  /**
   * insertRecord()
   * @override AccessLayerObject
   */
  public static function insert($param_table) {
    // Fail because 'param_table' contains parent fields when it shouldn't
    assert($param_table == array_diff_key($param_table, static::genParentDbFieldTableTemplate()));
    
    // Initialize insert record cache, if nonextant
    if (!isset(self::$insertRecordPreparedStatementCache)) {
      self::$insertRecordPreparedStatementCache = array();
    }

    // Initiate implicit transaction, if no explicit transaction
    $is_implicit_tx = false;
    if (self::$numTransactions == 0) {
      $is_implicit_tx = true;
      self::beginTx();
    }

    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));
    
    // Insert params as new record
    $record = null;
    $table_name = static::getTableName();

    try {
      // Create and register new prepared statement, if nonextant 
      if (!isset(self::$insertRecordPreparedStatementCache[$table_name])) {
        $insert_query = static::genInsertRecordQuery($param_table);
        self::$insertRecordPreparedStatementCache[$table_name] = 
            self::$databaseHandle->prepare($insert_query);
      }

      // Bind child params to prepared statement
      $insert_stmt = self::$insertRecordPreparedStatementCache[$table_name];
      $child_db_field_table = static::genChildDbFieldTable($param_table);
      foreach ($child_db_field_table as $field_name => $field) {
        $insert_stmt->bindValue(
            self::transformForPreparedStatement($field_name),
            $field->getValue(),
            $field->getDataType()
        );
      }

      // Insert record
      $insert_stmt->execute();
      $id = self::$databaseHandle->getLastInsertId(); 

      // Fetch record from db and extrude to object 
      $record = static::fetchById($id);
    } catch (PDOException $e) {
      self::$databaseHandle->rollback();
      die("\nERROR: " . $e->getMessage() . "\n");
    }

    // Close implicit transaction
    if ($is_implicit_tx) {
      self::endTx();
    }

    return $record;
  }

  /**
   * genChildDbFieldTable()
   * - Return child db field table with values in field-table bound
   *    to the AccessLayerFields.
   * @param field_table : Map<string:field-key, mixed:field-value>
   * @return Map<string:db-key, AccessLayerField>
   */
  private static function genChildDbFieldTable($param_table) {
    $child_db_field_table = static::genChildDbFieldTableTemplate(); 
    self::bindValuesToDbTable($param_table, $child_db_field_table);
    return $child_db_field_table;
  }

  /**
   * genChildDbFieldTableTemplate()
   * - Return child db field table with null values for access layer fields.
   * - Intended to be overridden by child classes.
   * @return Map<string:db-key, AccessLayerField>
   */
  protected static function genChildDbFieldTableTemplate() { return array(); }

  /**
   * genParentDbFieldTableTemplate()
   * - Return parent db field table.
   * @return Map<string:db-key, AccessLayerField>
   */
  private static function genParentDbFieldTableTemplate() {
    return array(
      self::ID_KEY => new AccessLayerField(DataTypeName::UNSIGNED_INT),
      self::CREATED_KEY => new AccessLayerField(DataTypeName::TIMESTAMP),
      self::LAST_UPDATED_TIME_KEY => new AccessLayerField(DataTypeName::TIMESTAMP),
    );
  } 

  /**
   * bindValuesToDbTable()
   * - Bind values in 'param_table' to AccessLayerFields in 'db_field_table'
   * @param param_table : Map<string:key, mixed:value>
   * @param db_field_table : Map<string:key, AccessLayerField:field>
   * @return void
   */
  private static function bindValuesToDbTable($param_table, $db_field_table) {
    foreach ($db_field_table as $field_name => $field) {
      // Bind param, if it exists. Otherwise, leave as null.
      if (isset($param_table[$field_name])) {
        $field->setValue($param_table[$field_name]);
      }
    }
  }

  /**
   * initDbFieldTables()
   * - Create db field tables and bind values to them.
   * @param init_params : Map<string:key, mixed:value>
   * @return void
   */
  private function initDbFieldTables($init_params) {
    // Fail because 'parentDbFieldTable' or 'childDbFieldTable' already exists
    assert(!isset($this->parentDbFieldTable) && !isset($this->childDbFieldTable));

    // Initialize parent db field table
    $this->parentDbFieldTable = self::genParentDbFieldTableTemplate();
    $this->bindValuesToDbTable($init_params, $this->parentDbFieldTable);

    // Initialize child db field table
    $this->childDbFieldTable = static::genChildDbFieldTable($init_params);
  }

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
    
    // Initiate implicit tx, if nonextant explicit tx 
    $is_implicit_tx = false;
    if (self::$numTransactions == 0) {
      $is_implicit_tx = true; 
      self::beginTx();
    }

    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    // Delete this record and all child records 
    $table_name = static::getFullyQualifiedTableName();
    
    try {
      // Fetch prepared statement from cache, if present. Otherwise, create it.
      if (isset(self::$deleteRecordPreparedStatementCache[$table_name])) {
        // Fetch delete query from cache
        $delete_record_stmt = self::$deleteRecordPreparedStatementCache[$table_name];
      } else {
        $fully_qualified_table_name = static::getFullyQualifiedTableName();
        $delete_query = "DELETE FROM {$fully_qualified_table_name} "
          . $this->genPrimaryKeyWhereClause();

        $delete_record_stmt = self::$databaseHandle->prepare($delete_query);
        self::$deleteRecordPreparedStatementCache[$table_name] = $delete_record_stmt;
      }
      
      // Delete children, schedule assets for deletion
      $this->deleteChildren();
      self::$assetDeletors = array_merge(self::$assetDeletors, $this->getAssets());

      // Bind record's 'id' to delete-query
      $this->bindId($delete_record_stmt);

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

    // Indicate object's deletion
    $this->hasBeenDeleted = true;
  }

  /**
   * deleteAssets()
   * - Remove assets from file system.
   * - Should be called after committing a record deletion
   *    in order to avoid asset deletion getting stuck downstream
   *    of the db.
   * @return void
   */
  private static function deleteAssets() {
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

    // Initialize save record cache, if necessary
    if (!isset(self::$saveRecordPreparedStatementCache)) {
      self::$saveRecordPreparedStatementCache = array();
    }

    // Initiate implicit transaction, if needed
    $is_implicit_tx = false;
    if (self::$numTransactions == 0) {
      $is_implicit_tx = true; 
      self::beginTx();
    }
   
    // Fail due to invalid database connection
    assert(isset(self::$databaseHandle));

    $table_name = static::getTableName();

    try {
      // Create save-record prepared statement if non-existant
      if (!isset(self::$saveRecordPreparedStatementCache[$table_name])) {
        $save_query_str = self::genSaveRecordQuery($this->childDbFieldTable);
        self::$saveRecordPreparedStatementCache[$table_name] = 
            self::$databaseHandle->prepare($save_query_str);
      }

      $save_record_stmt = self::$saveRecordPreparedStatementCache[$table_name];

      // Bind record fields 
      foreach ($this->childDbFieldTable as $field_name => $field) {
        $save_record_stmt->bindValue(
            self::transformForPreparedStatement($field_name),
            $field->getValue(),
            $field->getDataType()
        );
      }

      // Bind 'id' to save query
      $this->bindId($save_record_stmt);
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
    // Build query header
    $fully_qualified_table_name = static::getFullyQualifiedTableName();
    $save_query = "UPDATE {$fully_qualified_table_name} SET ";

    // Specify fields to be updated
    foreach ($record_fields as $field_name => $field) {
      $transformed_field_name = self::transformForPreparedStatement($field_name);
      $save_query .= "{$field_name}={$transformed_field_name}, ";
		}

    // Append 'where clause' in order to pinpoint the record to update 
    return substr($save_query, 0, strlen($save_query) - 2)
        . " " . static::genPrimaryKeyWhereClause(); 
  }

  /**
   * genPrimaryKeyWhereClause()
   * - Create "where clause" sql string with unique keys for this object.
   * - Used to identify a specific record in the db by its primary key.
   * @return string : query 'where' clause
   */
  private static function genPrimaryKeyWhereClause() {
    return "WHERE " . self::ID_KEY . "=" . self::transformForPreparedStatement(self::ID_KEY);
  }

  /**
   * getTableName()
   * - Fetch name of table.
   * @return string : name of table
   */
  private static function getTableName() {
    return get_called_class();
  }

  /**
   * getDatabaseName()
   * - Fetch name of database.
   * @return string : name of database 
   */
  private static function getDatabaseName() {
    return get_parent_class(static::getTableName());
  }

  /**
   * genFullyQualifiedTableName() 
   * - Compose fully qualified table name.
   * @param database_name : string of database name
   * @param table_name : string of table name
   * @return string : fully qualified table name 
   */
  private static function genFullyQualifiedTableName($database_name, $table_name) {
    return "{$database_name}.{$table_name}";
  }

  /**
   * getFullyQualifiedTableName()
   * - Return fully qualified table name of table.
   * @return string : fully qualified table name 
   */
  private static function getFullyQualifiedTableName() {
    $database_name = static::getDatabaseName();
    $table_name = static::getTableName();
    return static::genFullyQualifiedTableName($database_name, $table_name);
  }

  /**
   * bindId()
   * - Bind 'id' to this prepared statement.
   * @param prepared_stmt : prepared statement to which 'id' will be bound
   * @return void
   */
  private function bindId($prepared_stmt) {
    $prepared_stmt->bindValue(
        self::transformForPreparedStatement(self::ID_KEY),
        $this->getId(),
        DataTypeName::UNSIGNED_INT 
    ); 
  }

  /**
   * getId()
   * - Getter for id
   * @return unsigned int : record id
   */
  public function getId() {
    // Fail due to double delete
    assert(!$this->hasBeenDeleted);
    
    // Fail due to unset 'id' field
    assert(isset($this->parentDbFieldTable[self::ID_KEY]));
    
    return $this->parentDbFieldTable[self::ID_KEY]->getValue();
  }

  /**
   * getCreatedTime()
   * - Getter for timestamp at which record was created.
   * @return string : unix timestamp
   */
  public function getCreatedTime() {
    // Fail due to double delete
    assert(!$this->hasBeenDeleted);
    
    // Fail due to unset 'created-time' field
    assert(isset($this->parentDbFieldTable[self::CREATED_KEY]));
    
    return $this->parentDbFieldTable[self::CREATED_KEY]->getValue();
  }

  /**
   * getLastUpdatedTime()
   * - Getter for timestamp at which record was last updated.
   * @return string : unix timestamp
   */
  public function getLastUpdatedTime() {
    // Fail due to double delete
    assert(!$this->hasBeenDeleted);
    
    // Fail due to unset 'last-updated-time' field
    assert(isset($this->parentDbFieldTable[self::LAST_UPDATED_TIME_KEY]));
    
    return $this->parentDbFieldTable[self::LAST_UPDATED_TIME_KEY]->getValue();
  }
}
