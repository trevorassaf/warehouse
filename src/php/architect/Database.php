<?php

class Database {

  private
    $name,
    $tableMap;

  /**
   * __construct()
   * - Ctor for Database
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
   * @return Map<string:table-name, Table>
   */
  public function getTableMap() {
    return $this->tableMap;
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
      
      $this->tableMap[$table->getName()]
    }
  } 
}
