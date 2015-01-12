<?php

require_once(dirname(__FILE__)."/DataTypeName.php");

final class AccessLayerField {

  private
    $value,
    $datatype;

  public function __construct($datatype, $value=null) {
    $this->datatype = $datatype;
    $this->value = $value;
  }

  public function getValue() {
    return $this->value;
  }

  public function getDataType() {
    return $this->datatype;
  }

  public function setValue($value) {
    $this->value = $value;
  }
}
