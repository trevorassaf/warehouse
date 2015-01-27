<?php

require_once("PhpAccessLayerBuilder.php");

class PhpEnumClassBuilder extends PhpClassBuilder {

  /**
   * __construct()
   * - Ctor for PhpEnumClassBuilder
   * @param enum : EnumTable
   * @param parent_class_name : string
   */
  public function __construct($enum, $parent_class_name) {
    parent::__construct($enum->getName(), $parent_class_name);

    // Set enum column
    $enum_columns = $enum->getColumns();
    
    // Fail due to invalid 'enum-columns'
    assert(sizeof($enum_columns) == 1);

    $this->addField($enum_columns[0]);
  }

  /**
   * addField()
   * - Add enum field w/out setters.
   * @override PhpAccessLayerBuilder
   */
  public function addField($column) {
    $column_name = $column->getName();
    $this->appendDbKey($column_name);
    $this->appendGetter($column_name);
    $this->appendAccessLayerFields($column_name, $column->getDataType());
  }
}
