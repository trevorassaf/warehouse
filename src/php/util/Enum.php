<?php

class InvalidEnumType extends Exception {

  private
    $enumType,
    $enumValue;

  public function __construct($enum_type, $enum_value) {
    $this->enumType = $enum_type;
    $this->enumValue = $enum_value; 
  }

  public function __toString() {
    return "ERROR: enum value '{$this->enum_value}' is invalid type for Enum {$this->enum_type}\n\n"
      . $this->getTraceAsString();
  }
}

abstract class Enum {
  
  protected static $SUPPORTED_TYPES;

  private function __construct() {}

  public static function validateType($enum_value) {
    if (!isset($enum_value) || !in_array($enum_value, static::$SUPPORTED_TYPES)) {
      throw new InvalidEnumType(get_called_class(), $enum_value);
    }
  }
}
