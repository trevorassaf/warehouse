<?php

class Table {

  private
    $name,
    $uniqueColumnSetList,
    $columnMap;

  /**
   * __construct()
   * - Ctor for Table.
   */
  public function __construct($name) {
    $this->name = $name;
    $this->uniqueColumnSetList = array();
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
    // Fail due to duplicate column entry
    assert(!isset($this->columnMap[$column->getName()]));

    $this->columnMap[$column->getName()] = $column;
  }

  /**
   * setColumns()
   * - Set columns for table.
   * @param Map<string:key, Column:value>
   * @return void
   */
  public function setColumns($column_map) {
    $this->columnMap = array();

    foreach ($column_map as $key => $column) {
      $this->addColumn($column); 
    }
  }

  /**
   * geColumnSet()
   * - Return column set. 
   * @return Set<Column>
   */
  public function getColumns() {
    return $this->columnMap;
  }

  /**
   * getNumColumns()
   * - Return size of column-set.
   * @return unsigned int : number of cols
   */
  public function getNumColumns() {
    return sizeof($this->columnSet);
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

  /**
   * hasColumn()
   * - Return true iff the table contains a column by the
   *    specified name.
   * @param name : string
   * @return bool : true iff table contains specified column.
   */
  public function hasColumn($name) {
    foreach ($this->columnSet as $key => $col) {
      if ($key == $name) {
        return true;
      }
    }

    return false;
  }
}
