<?php

require_once(dirname(__FILE__)."/../util/Enum.php");

final class TableMappingType extends Enum {

  const ONE_TO_ONE = 0;
  const ONE_TO_MANY = 1;
  const MANY_TO_ONE = 2;
  const MANY_TO_MANY = 3;

  private static $ASYMMETRIC_TYPE_TABLE = array(
    self::ONE_TO_MANY => self::MANY_TO_ONE,
    self::MANY_TO_ONE => self::ONE_TO_MANY,
  );

  public static function isAsymmetricType($mapping_type) {
    static::validateType($mapping_type);
    return isset(self::$ASYMMETRIC_TYPE_TABLE[$mapping_type]);
  }

  public static function getAsymmetricConjugateType($asymmetric_type) {
    // Fail due to non-asymmetric type
    assert(self::isAsymmetricType($asymmetric_type));
    return self::$ASYMMETRIC_TYPE_TABLE[$asymmetric_type];
  }
  
  protected static $SUPPORTED_TYPES = array(
    self::ONE_TO_ONE,
    self::ONE_TO_MANY,
    self::MANY_TO_ONE,
    self::MANY_TO_MANY,
  );
}

final class TableWithMappingSet {

  private
    $table,
    $mappingSet;

  /**
   * __construct()
   * - Ctor for TableWithMappingSet
   * @param table : Table
   */
  public function __construct($table) {
    $this->table = $table;
    $this->mappingSet = array();
  }

  /**
   * getTable()
   * - Return table.
   * @return Tabe : table
   */
  public function getTable() {
    return $this->table;
  }

  /**
   * hasMappingToTable()
   * - Retun true iff mapping already exists for specified table.
   * @param table_name : string
   * @return bool : true iff mapping already exists.
   */
  public function hasMappingToTable($table_name) {
    return isset($this->mappingSet[$table_name]);
  }

  /**
   * getMappingSet()
   * - Return mapping set.
   * @return map<string:table-name, TableMappingType:mapping-type>
   */
  public function getMappingSet() {
    return $this->mappingSet;
  }

  /**
   * addMapping()
   * - Incorporate new table mapping.
   * @param table_name : string
   * @param mapping_type : TableMappingType
   * @return void
   */
  public function addMapping($table_name, $mapping_type) {
    $this->mappingSet[$table_name] = $mapping_type;
  }
}

class Database {

  private
    $name,
    $tableMap;

  /**
   * __construct()
   * - Ctor for Database
   * @param name : string
   */
  public function __construct($name) {
    $this->name = $name;
    $this->tableMap = array();
  }

  /**
   * getName()
   * - Return db name 
   * @return string : db name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * getTableMap()
   * - Return table map
   * @return Map<string:table-name, TableWithMappingSet>
   */
  public function getTableMap() {
    return $this->tableMap;
  }

  /**
   * hasTable()
   * - Return true iff table is set in db.
   * @param table_name : string
   * @return bool : true iff table is registered
   */
  public function hasTable($table_name) {
    return isset($this->tableMap[$table_name]);
  }

  /**
   * getTable()
   * - Fetch table by its name.
   * @param table_name : string
   * @return TableWithMappingSet : table associated with 'table_name'
   */
  public function getTableWithMappingSet($table_name) {
    // Fail due to nonextant table
    assert($this->hasTable($table_name));
    return $this->tableMap[$table_name];
  }

  /**
   * setTable()
   * - Add new table to db
   * @param table : Table
   */
  public function setTable($table) {
    // Fail due to null table
    assert(isset($table));
    $this->tableMap[$table->getName()] = new TableWithMappingSet($table);
  }

  /**
   * setTables()
   * - Add set of new tables to db
   * @param table_list : array<Table>
   */
  public function setTables($table_list) {
    // Fail due to null table-list
    assert(isset($table_list));

    foreach ($table_list as $table) {
      $this->addTable($table);
    }
  } 

  /**
   * setTableMapping()
   * - Add inter-table-mapping to set.
   * @param primary_table : Table
   * @param secondary_table : Table
   * @param mapping_type : TableMappingType
   * @return void
   */
  public function setTableMapping($primary_table, $secondary_table, $mapping_type) {
    // Fail due to unset mapping list
    assert(isset($this->tableMap));

    // Fail due to unset primary-table
    assert(isset($primary_table));
    
    // Fail due to unset secondary-table
    assert(isset($secondary_table));
    
    // Fail due to unset mapping type 
    assert(isset($mapping_type));

    // Register tables, if nonextant
    if (!$this->hasTable($primary_table->getName())) {
      $this->setTable($primary_table);
    }
    
    if (!$this->hasTable($secondary_table->getName())) {
      $this->setTable($secondary_table);
    }

    // Set mapping types
    $primary_table_with_mapping = $this->getTableWithMappingSet($primary_table->getName());
    $primary_table_with_mapping->addMapping($secondary_table->getName(), $mapping_type);

    if (TableMappingType::isAsymmetricType($mapping_type)) {
      $reciprocal_mapping_type = TableMappingType::getAsymmetricConjugateType($mapping_type);
      $secondary_table_with_mapping = $this->getTableWithMappingSet($secondary_table->getName());
      $secondary_table_with_mapping->addMapping($primary_table->getName(), $reciprocal_mapping_type);
    }
  }

  /**
   * addTableMappings()
   * - Add inter-table-mappings to set.
   * @param array<InterTableMapping> : undirected edges between tables w/mapping type
   */
  public function addTableMappings($table_mapping_list) {
    // Fail due to unset table_mapping_list
    assert(isset($table_mapping_list));

    foreach ($table_mapping_list as $table_mapping) {
      $this->addTableMapping($table_mapping);
    } 
  }
}
