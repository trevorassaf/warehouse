<?php

class Database {

  private
    $name,
    $tableMap,
    $tableMappingList;

  /**
   * __construct()
   * - Ctor for Database
   */
  public function __construct($name) {
    $this->name = $name;
    $this->tableMap = array();
    $this->tableMappingList = array();
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
  public function getTableMap() {
    return $this->tableMap;
  }

  /**
   * getInterTableMappings()
   * - Return set of undirected edges between tables with
   *    enum specifying relationship between tables.
   * @return array<InterTableMapping>
   */
  public function getInterTableMappings() {
    return $this->tableMappingList; 
  }

  /**
   * getTable()
   * - Fetch table by its name.
   * @param table_name : string
   * @return Table : table associated with 'table_name'
   */
  public function getTable($table_name) {
    // Fail due to nonextant table
    assert(isset($this->tableMap[$table_name]));

    return $this->tableMap[$table_name];
  }

  /**
   * addTable()
   * - Add new table to db
   * @param table : Table
   */
  public function addTable($table) {
    // Fail due to pre-existing table entry
    assert(!isset($this->tableMap[$table->getName()]));
    $this->tableMap[$table->getName()] = $table;
  }

  /**
   * addTables()
   * - Add set of new tables to db
   * @param table_list : array<Table>
   */
  public function addTables($table_list) {
    // Fail due to null table-list
    assert(isset($table_list));

    foreach ($table_list as $table) {
      // Fail due to pre-existing table
      assert(!isset($this->tableMap[$table->getName()]));
      
      $this->tableMap[$table->getName()] = $table;
    }
  } 

  /**
   * addTableMapping()
   * - Add inter-table-mapping to set.
   * @param InterTableMapping : undirected edge between tables w/mapping type
   */
  public function addTableMapping($table_mapping) {
    // Fail due to unset mapping list
    assert(isset($this->tableMappingList));

    // Fail due to unset table-mapping arg
    assert(isset($table_mapping));

    $first_table = $table_mapping->getFirstTable();
    $second_table = $table_mapping->getSecondTable();

    // Fail due to unregistered table
    assert(isset($this->tableMap[$first_table->getName()])
        && $this->tableMap[$first_table->getName()] == $first_table);
    
    assert(isset($this->tableMap[$second_table->getName()])
        && $this->tableMap[$second_table->getName()] == $second_table);

    $this->tableMappingList[] = $table_mapping;
  }

  /**
   * addTableMappings()
   * - Add inter-table-mappings to set.
   * @param array<InterTableMapping> : undirected edges between tables w/mapping type
   */
  public function addTableMappings($table_mapping_list) {
    foreach ($table_mapping_list as $table_mapping) {
      $this->addTableMapping($table_mapping);
    } 
  }
}
