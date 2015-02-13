<?php

require_once(dirname(__FILE__)."/../util/Enum.php");

class Database {

  /**
   * Default delimitter for fields.
   */
  const FIELD_DELIMITER = "_";

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
   * @return Map<string:table-name, Table>
   */
  public function getTables() {
    return $this->tableMap;
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

    // Fail due to duplicate table
    assert(!$this->hasTable($table->getName()));

    $this->tableMap[$table->getName()] = $table;
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
   * hasTable()
   * - Return true iff the db contains the specified table.
   * @param table_name : string
   * @return bool : true iff db contains the table
   */
  public function hasTable($table_name) {
    return isset($this->tableMap[$table_name]);
  }
}
