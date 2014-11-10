<?php

// -- DEPENDENCIES
require_once("ModelObject.php");

abstract class DbDataTypeError {

  // Cannot require length AND specify a default length
  const RWD = 0x00; 
  
  // Cannot require length AND NOT accept length
  const AWOR = 0x01;

  // Cannot have max length AND NOT accept length
  const MWOR = 0x02;

  // Unset name
  const UNAME = 0x03;

  // Unset accepts_length
  const UAL = 0x04;

  // Unset requires length
  const URL = 0x05;

  // Unset category id
  const UCI = 0x06;

  // Unset db id
  const UDI = 0x07;

  // Unset max length
  const UML = 0x08;

  // Unset default length
  const UDL = 0x09;
}

class DbDataType extends ModelObject {

  const NAME_KEY = "name";
  const ACCEPTS_LENGTH_KEY = "accepts_length";
  const REQUIRES_LENGTH_KEY = "requires_length";
  const DEFAULT_LENGTH_KEY = "default_length";
  const MAXIMUM_LENGTH_KEY = "maximum_length";
  const CATEGORY_ID_KEY = "category_id";
  const DB_ID_KEY = "db_id";

  private 
    $name,
    $acceptsLength,
    $requiresLength,
    $defaultLength,
    $maximumLength,
    $categoryId,
    $dbId;

  public static function create(
      $name,
      $accepts_length,
      $requires_length,
      $default_length,
      $maximum_length,
      $category_id,
      $db_id) {

    // Validate input
    self::validateOrThrow(
        $name,
        $accepts_length,
        $requires_length,
        $default_length,
        $maximum_length,
        $category_id,
        $db_id);

    return static::createObject(
        array(
            self::NAME_KEY => $name,
            self::ACCEPTS_LENGTH_KEY => $accepts_length,
            self::REQUIRES_LENGTH_KEY => $requires_length,
            self::DEFAULT_LENGTH_KEY => $default_length,
            self::MAXIMUM_LENGTH_KEY => $maximum_length,
            self::CATEGORY_ID_KEY => $category_id,
            self::DB_ID_KEY => $db_id,
        )
    );
  }

  public static function validateOrThrow(
      $name,
      $accepts_length,
      $requires_length,
      $default_length,
      $maximum_length,
      $category_id,
      $db_id) {

    // Name must be set  
    if (!isset($name)) {
      throw new InvalidObjectStateException(DbDataTypeError::UNAME);
    }

    // Accepts-length must be set
    if (!isset($accepts_length)) {
      throw new InvalidObjectStateException(DbDataTypeError::UAL);
    }

    // Requires-length must be set
    if (!isset($requires_length)) {
      throw new InvalidObjectStateException(DbDataTypeError::URL);
    }

    // Category id must be set
    if (!isset($category_id)) {
      throw new InvalidObjectStateException(DbDataTypeError::UCI);
    }

    // Db id must be set
    if (!isset($db_id)) {
      throw new InvalidObjectStateException(DbDataTypeError::UDI);
    }

    // Must specify max-length if this field is variable in length
    if ($accepts_length && !isset($maximum_length)) {
      throw new InvalidObjectStateException(DbDataTypeError::UML);
    }

    // Default must be non-null if requires-length is false
    if (!$requires_length && !isset($default_length)) {
      throw new InvalidObjectStateException(DbDataTypeError::UDL);
    }

    // A field with static length should not have a max-length
    if (!$accepts_length && isset($maximum_length)) {
      throw new InvalidObjectStateException(DbDataTypeError::MWOR);
    }

    // A field requiring length specification should not specify a default
    // length
    if ($requires_length && isset($default_length)) {
      throw new InvalidObjectStateException(DbDataTypeError::RWD);
    }

    // A field requiring length specification should also allow_length
    if ($requires_length && !$accepts_length) {
      throw new InvalidObjectStateException(DbDataTypeError::AWOR);
    }
  }
  

  // Override
  protected function getDbFields() {
    return array(
      self::NAME_KEY => $this->name,
    );
  }

  protected function initInstanceVars($params) {
    $this->name = $params[self::NAME_KEY];
  }

  // Getters
  public function getName() {
    return $this->name;
  }

  public function doesAcceptLength() {
    return $this->acceptsLength;
  }

  public function doesRequireLength() {
    return $this->doesRequireLength;
  }

  public function getDefaultLength() {
    return $this->defaultLength;
  }

  public function getMaximumLength() {
    return $this->getMaximumLength;
  }

  public function getCategoryId() {
    return $this->categoryId;
  }

  public function getDbId() {
    return $this->dbId;
  }
}
