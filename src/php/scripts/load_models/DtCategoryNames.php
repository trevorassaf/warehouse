<?php

abstract class DtCategoryNames {
  const INTEGER_NAME = "Integer";
  const RATIONAL_NUMBER_NAME = "Rational Number";
  const BOOLEAN_NAME = "Boolean";
  const ENUM_NAME = "Enumerated Type";
  const DATE_NAME = "Date/Time";
  const STRING_NAME = "String";

  private static $categoryNames = array(
    self::INTEGER_NAME,
    self::RATIONAL_NUMBER_NAME,
    self::BOOLEAN_NAME,
    self::ENUM_NAME,
    self::DATE_NAME,
    self::STRING_NAME,
  );

  public static function getNames() {
    return self::$categoryNames;
  }
}
