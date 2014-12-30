<?php

class Table {

  private
    $name,
    $columnMap;

  /**
   * __construct()
   * - Ctor for Table.
   */
  public function __construct($name) {
    $this->name;
    $this->columnMap = array();
  }

  /**
   * getName()
   * - Return name of table.
   * @return string : table name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * addColumn()
   * - Add column to table.
   * @param column : Column
   */
  public function addColumn($column) {
    assert(!isset($this->columnMap[$column->getName()])); 
  }

  /**
   * addColumns()
   * - Add set of columns to table.
   * @param column_list : array<Column>
   */
  public function addColumns($column_list) {
    foreach ($column_list as $column) {
      $this->addColumn($column);
    }
  } 
}
