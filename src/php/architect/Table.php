<?php

class Table {

  private
    $name,
    $uniqueColumnSetList,
    $columnSet;

  /**
   * __construct()
   * - Ctor for Table.
   */
  public function __construct($name) {
    $this->name = $name;
    $this->uniqueColumnSetList = array();
    $this->columnSet = array();
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
    $this->columnSet[] = $column;
  }

  /**
   * addColumns()
   * - Add set of columns to table.
   * @param column_list : array<Column>
   */
  public function addColumnSet($column_list) {
    foreach ($column_list as $column) {
      $this->addColumn($column);
    }
  } 

  /**
   * geColumnSet()
   * - Return column set. 
   * @return Set<Column>
   */
  public function getColumns() {
    return $this->columnSet;
  }

  /**
   * getUniqueColumnSetList()
   * - Return list of unique column sets.
   * @return Set<Set<string:column-name>>
   */
  public function getUniqueColumnSetList() {
    return $this->uniqueColumnSetList;
  }

  /**
   * addCompositeKey()
   * - Make composite key from provided column name set
   * @param column_name_set : Set<string:column-name>
   * @return void
   */
  public function addCompositeKey($column_name_set) {
    $this->uniqueColumnSetList[] = $column_name_set;
  }

  /**
   * addUniqueKey()
   * - Make unique key from provided column name. 
   * @param column_name : Set<string:column-name>
   * @return void
   */
  public function addUniqueKey($column_name) {
    $this->uniqueColumnSetList[] = array($column_name);
  }
}
