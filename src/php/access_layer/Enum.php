<?php

abstract class Enum {
  
  protected static $SUPPORTED_TYPES;

  public static function validateType($enum_value) {
    if (!isset($enum_value) || !in_array($type, self::$SUPPORTED_TYPES)) {
      throw new InvalidEnumType(static::class, $enum_value);
    }
  }
}

class InvalidEnumType extends Exception {

  private
    $enumType,
    $enumValue;

  public function __construct($enum_type, $enum_value) {
    $this->enumType = $enum_type;
    $this->enumValue = $enum_value; 
  }

  public function __toString() {
    return "ERROR: enum value '{$enum_value}' is invalid type for Enum {$enum_type}\n\n"
      . $this->getTraceAsString();
  }
}
