<?php

final class EnumTable extends Table {

  private $valueSetList;

  public function __construct($name) {
    parent::__construct($name);
    $this->valueSetList = array();
  }

  public function addValueSet($value_set) {
    $this->valueSetList[] = $value_set; 
  }

  public function getValueSetList() {
    return $this->valueSetList;
  }
}
