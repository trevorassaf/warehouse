<?php

require_once("Table.php");

/**
 * This class extends the functionality of a regular Table by supporting
 * default values (the 'enum' elements). 
 */
final class EnumTable extends Table {

  private $elementList;

  /**
   * __construct()
   * - Ctor for EnumTable. 
   *  @param name : name of enum table
   */
  public function __construct($name) {
    parent::__construct($name);
    $this->elementList = array();
  }

  /**
   * addElement()
   * - Add element to list.
   * @param element : Map<string:key, mixed:value> 
   * @return void
   */
  public function addElement($element_map) {
    $this->elementList[] = $element_map; 
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

    $this->elementList = array();

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
    return $this->elementList;
  }
}
