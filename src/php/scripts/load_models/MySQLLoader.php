<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/ModelLoader.php");

class MySQLLoader extends ModelLoader {

  const INTEGER_NAME = "INT";
  const DOUBLE_NAME = "DOUBLE";
  const TINY_INT_NAME = "TINYINT";
  const DATE_TIME_NAME = "DATETIME";
  const VARCHAR_NAME = "VARCHAR";

  protected static $dbName = "MySQL";

  protected static function loadColumns($db) {

    // Integer
    $int_cat = static::loadCategoryOrCreate(DtCategoryNames::INTEGER_NAME);
    DbDataType::create(
      self::INTEGER_NAME,
      false,
      false,
      4,
      null,
      $int_cat->getId(),
      $db->getId()
    );

    // Double 
    $double_cat = static::loadCategoryOrCreate(DtCategoryNames::RATIONAL_NUMBER_NAME);
    DbDataType::create(
      self::DOUBLE_NAME,
      false,
      false,
      8,
      null,
      $double_cat->getId(),
      $db->getId()
    );

    // Boolean
    $boolean_cat = static::loadCategoryOrCreate(DtCategoryNames::BOOLEAN_NAME);
    DbDataType::create(
      self::TINY_INT_NAME,
      false,
      false,
      1,
      null,
      $boolean_cat->getId(),
      $db->getId()
    );

    // String 
    $string_cat = static::loadCategoryOrCreate(DtCategoryNames::STRING_NAME);
    DbDataType::create(
      self::VARCHAR_NAME,
      true,
      true,
      null,
      65535,
      $string_cat->getId(),
      $db->getId()
    );

    // Date time 
    $date_cat = static::loadCategoryOrCreate(DtCategoryNames::DATE_NAME);
    DbDataType::create(
      self::DATE_TIME_NAME,
      false,
      false,
      19,
      null,
      $date_cat->getId(),
      $db->getId()
    );
  }
}
