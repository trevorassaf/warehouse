<?php

require_once(dirname(__FILE__)."/../util/Enum.php");

final class TableMappingType extends Enum {

  const ONE_TO_ONE = 0;
  const ONE_TO_MANY = 1;
  const MANY_TO_MANY = 2;

  protected static $SUPPORTED_TYPES = array(
    self::ONE_TO_ONE,
    self::ONE_TO_MANY,
    self::MANY_TO_MANY,
  );
}

final class TableMapping {

  private
    $primaryTable,
    $secondaryTable,
    $tableMappingType;

  public function __construct(
    $primary_table,
    $secondary_table,
    $table_mapping_type
  ) {
    $this->primaryTable = $primary_table;
    $this->secondaryTable = $secondary_table;
    $this->tableMappingType = $table_mapping_type; 
  }

  /**
   * getPrimaryTable()
   * - Return primary table.
   * @return Table : primary table
   */
  public function getPrimaryTable() {
    return $this->primaryTable;
  }

  /**
   * getSecondaryTable()
   * - Return secondary table.
   * @return Table : secondary table
   */
  public function getSecondaryTable() {
    return $this->secondaryTable;
  }

  /**
   * getTableMappingType()
   * - Return mapping type.
   * @return TableMappingType
   */
  public function getTableMappingType() {
    return $this->tableMappingType;
  }
}

class Database {

  private
    $name,
    $tableSet,
    $mappingSet,
    $enumSet;

  /**
   * __construct()
   * - Ctor for Database
   * @param name : string
   */
  public function __construct($name) {
    $this->name = $name;
    $this->tableSet = array();
    $this->mappingSet = array();
    $this->enumSet = array();
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
   * @return Map<string:table-name, Table>
   */
  public function getTables() {
    return $this->tableSet;
  }

  /**
   * getMappingSet()
   * - Return inter-table mapping set.
   * @return Set<TableMapping>
   */
  public function getTableMappings() {
    return $this->mappingSet;
  }

  /**
   * getEnumMap()
   * - Return enum map.
   * @return Set<Enum>
   */
  public function getEnums() {
    return $this->enumSet; 
  }

  /**
   * addEnum()
   * - Add enum.
   * @param enum : Enum
   * @return void
   */
  public function addEnum($enum) {
    $this->enumSet[] = $enum;
  }

  /**
   * setTable()
   * - Add new table to db
   * @param table : Table
   * @return void
   */
  public function addTable($table) {
    // Fail due to null table
    assert(isset($table));
    $this->tableSet[] = $table;
  }

  /**
   * setTables()
   * - Add set of new tables to db
   * @param table_list : array<Table>
   * @return void
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
  public function addTableMapping($primary_table, $secondary_table, $mapping_type) {
    // Fail due to unset mapping list
    assert(isset($this->tableSet));

    // Fail due to unset primary-table
    assert(isset($primary_table));
    
    // Fail due to unset secondary-table
    assert(isset($secondary_table));
    
    // Fail due to unset mapping type 
    assert(isset($mapping_type));

    $this->mappingSet[] = new TableMapping($primary_table, $secondary_table, $mapping_type);
  }

  /**
   * addTableMappings()
   * - Add inter-table-mappings to set.
   * @param array<InterTableMapping> : undirected edges between tables w/mapping type
   * @return void
   */
  public function addTableMappings($table_mapping_list) {
    // Fail due to unset table_mapping_list
    assert(isset($table_mapping_list));

    foreach ($table_mapping_list as $table_mapping) {
      $this->addTableMapping($table_mapping);
    } 
  }
}
