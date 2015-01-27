<?php

require_once("Table.php");

final class EnumTable extends Table {

  /**
   * Name for enum field.
   */
  const FIELD_NAME = "value";

  private
    $elementSet,
    $elementMaxLength;

  /**
   * __construct()
   * - Ctor for EnumTable.
   * @param name : name of enum table
   */
  public function __construct($name) {
    parent::__construct($name);
    $this->elementSet = array();
    $this->elementMaxLength = 0;

    // Init enum column
    $this->configureEnumColumn();
  }

  /**
   * getElementMaxLength()
   * - Return string-size of longest element.
   * @return unsigned int : length of longest element
   */
  public function getElementMaxLength() {
    return $this->elementMaxLength;
  }

  /**
   * addElement()
   * - Add element to set.
   * @param element : mixed
   * @return void
   */
  public function addElement($element) {
    $this->elementSet[] = $element; 

    // Update 'elementMaxLength' if necessary
    if (strlen($element) > $this->elementMaxLength) {
      $this->elementMaxLength = strlen($element);

      // Update enum columns
      $this->configureEnumColumn();
    }
  }

  /**
   * setElements()
   * - Save element set.
   * @param element_set : Set<mixed>
   * @return void
   */
  public function setElements($element_set) {
    foreach ($element_set as $e) {
      $this->addElement($e);
    } 
  }

  /**
   * configureEnumColumn()
   * - Compose and set column for enum table.
   * @return Column : column for this eum
   */ 
  private function configureEnumColumn() {
    $column_builder = new ColumnBuilder();
    $enum_col = $column_builder
        ->setName(self::FIELD_NAME)
        ->setDataType(DataType::string()) 
        ->setFirstLength($this->elementMaxLength)
        ->build();

    // Set unique enum column 
    parent::setColumns(array($enum_col));
    parent::setCompositeKeyList(array(array($enum_col)));
  }

  /**
   * getEementSet()
   * - Return element set.
   * @return Set<mixed>
   */
  public function getElementSet() {
    return $this->elementSet;
  }
}
