<?php

final class ColumnWithInfo {

  private
    $column,
    $isInUniqueColumnSetList;

  public function __construct($column) {
    $this->column = $column;
    $this->isInUniqueColumnSetList = false;
  }

  public function getColumn() {
    return $this->column;
  }

  public function getIsInUniqueColumnSetList() {
    return $this->isInUniqueColumnSetList;
  }

  public function addColumnToUniqueList() {
    $this->isInUniqueColumnSetList = true;
  }
}

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
    // Fail because column already registered
    assert(!isset($this->columnMap[$column->getName()])); 
    $this->columnMap[$column->getName()] = new ColumnWithInfo($column);
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

  /**
   * geColumnMap()
   * - Return column map
   * @return Map<string:column-map, Column:column>
   */
  public function getColumnWithInfoMap() {
    return $this->columnMap;
  }

  /**
   * getUniqueColumnSetList()
   * - Return list of unique column sets.
   * @return array<set<string:column-name>>
   */
  public function getUniqueColumnSetList() {
    return $this->uniqueColumnSetList;
  }

  /**
   * addCompositeKey()
   * - Make composite key from provided column name set
   * @param column_name_set : set<string:column-name>
   * @return void
   */
  public function addCompositeKey($column_name_set) {
    foreach ($column_name_set as $column_name) {
      // Fail due to nonextant column
      assert(isset($this->columnMap[$column_name]));
      // Fail due to previously registered unique column
      assert(!$this->columnMap[$column_name]->getIsInUniqueColumnSetList());
      
      $this->columnMap[$column_name]->addColumnToUniqueList();
    } 
    
    $this->uniqueColumnSetList[] = $column_name_set;
  }

  /**
   * addUniqueKey()
   * - Make unique key from provided column name. 
   * @param column_name_set : string:column-name>
   * @return void
   */
  public function addUniqueKey($column_name) {
    // Fail due to nonextant column
    assert(isset($this->columnMap[$column_name]));
    // Fail due to previously registered unique column
    assert(!$this->columnMap[$column_name]->getIsInUniqueColumnSetList());
    
    $this->columnMap[$column_name]->addColumnToUniqueList();
    $this->uniqueColumnSetList[] = array($column_name_set);
  }
}
