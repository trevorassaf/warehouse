<?php

require_once("Table.php");

/**
 * This class extends the functionality of a regular Table by supporting
 * default values (the 'enum' elements). 
 */
final class EnumTable extends Table {

  private $rows;

  /**
   * __construct()
   * - Ctor for EnumTable. 
   *  @param name : name of enum table
   */
  public function __construct($name) {
    parent::__construct($name);
    $this->rows = array();
  }

  /**
   * addElement()
   * - Add row to list.
   * @param element : Map<string:key, mixed:value> 
   * @return void
   */
  public function addRow($field_map) {
    $this->rows[] = $field_map; 
  }

  /**
   * getElementIds()
   * - Return element ids of rows that match the provided
   *    field map.
   * @param field_map : Map<string:key, mixed:value>
   */
  public function getElementIds($field_map) {
    $field_map_size = sizeof($field_map);
    $num_rows = sizeof($this->rows);
    
    $result_map = array();

    for ($i = 0; $i < $num_rows; ++$i) {
      $row = $this->rows[$i];
      $is_match = true;

      // Short-circuit if non-match
      foreach ($field_map as $name => $value) {
        if (!isset($row[$name]) || $row[$name] != $value) {
          $is_match = false;
        }
      }

      // Accumulate result
      if ($is_match) {
        $result_map[$i] = $row;
      }
    }

    return $result_map;
  }

  /**
   * setElements()
   * - Save element list.
   * @param element_set : List<Map<string:key, mixed:value>>
   * @return void
   */
  public function setElements($element_list) {
    // Fail because 'element_list' s null
    assert(isset($element_list));

    $this->rows = array();

    foreach ($element_list as $e) {
      $this->addElement($e);
    } 
  }

  /**
   * getElementList()
   * - Return element list.
   * @return List<Map<string:key, mixed:value>>
   */
  public function getElements() {
    return $this->rows;
  }
}
