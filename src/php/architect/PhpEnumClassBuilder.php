<?php

require_once("PhpAccessLayerBuilder.php");

class PhpEnumClassBuilder extends PhpClassBuilder {

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
