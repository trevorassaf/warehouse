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
   * setColumns()
   * - Set columns for table.
   * @param Set<Column>
   * @return void
   */
  public function setColumns($column_set) {
    $this->columnSet = $column_set; 
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
   * @param column_set : Set<Column:column>
   * @return void
   */
  public function addCompositeKey($column_set) {
    $this->uniqueColumnSetList[] = $column_set;
  }

  /**
   * addUniqueKey()
   * - Make unique key from provided column name. 
   * @param column : Column:column 
   * @return void
   */
  public function addUniqueKey($column) {
    $this->uniqueColumnSetList[] = array($column);
  }

  /**
   * setCompositeKeyList()
   * - Establish column name set list.
   * @param column_set_list : Array<Array<Column>>
   * @return void
   */
  public function setCompositeKeyList($column_set_list) {
    $this->uniqueColumnSetList = array();
    foreach ($column_set_list as $column_set) {
      $this->addCompositeKey($column_set);
    }
  }
}
