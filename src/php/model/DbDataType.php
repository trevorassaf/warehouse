<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/ModelObject.php");
require_once(dirname(__FILE__)."/SupportedDb.php");
require_once(dirname(__FILE__)."/DtCategory.php");

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

  // Duplicate name
  const DNA = 0x10;
  
  // Invalid category id
  const ICI = 0x11;

  // Invalid db id
  const IDI = 0x12;
}

class DbDataType extends ModelObject {

  const NAME_KEY = "name";
  const ACCEPTS_LENGTH_KEY = "accepts_length";
  const REQUIRES_LENGTH_KEY = "requires_length";
  const DEFAULT_LENGTH_KEY = "default_length";
  const MAXIMUM_LENGTH_KEY = "maximum_length";
  const CATEGORY_ID_KEY = "category_id";
  const DB_ID_KEY = "db_id";

  protected static $tableName = "DbDataTypes";

  protected static $uniqueKeys = array(self::NAME_KEY);

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
    $db_id
  ) {
      $fields = array(
          self::NAME_KEY => $name,
          self::ACCEPTS_LENGTH_KEY => $accepts_length,
          self::REQUIRES_LENGTH_KEY => $requires_length,
          self::DEFAULT_LENGTH_KEY => $default_length,
          self::CATEGORY_ID_KEY => $category_id,
          self::DB_ID_KEY => $db_id,
        );
      if ($maximum_length !== null) {
        $fields[self::MAXIMUM_LENGTH_KEY] = $maximum_length;
      }
      return static::createObject($fields);
  }

  public static function fetchDataTypesForSupportedDb($db_id) {
    return static::getObjectsByParams(
      array(
        self::DB_ID_KEY => $db_id,
      )
    );
  }

  protected function validateOrThrow() {

    // Name must be set  
    if (!isset($this->name)) {
      throw new InvalidObjectStateException(DbDataTypeError::UNAME);
    }

    // Accepts-length must be set
    if (!isset($this->acceptsLength)) {
      throw new InvalidObjectStateException(DbDataTypeError::UAL);
    }

    // Requires-length must be set
    if (!isset($this->requiresLength)) {
      throw new InvalidObjectStateException(DbDataTypeError::URL);
    }

    // Category id must be set
    if (!isset($this->categoryId)) {
      throw new InvalidObjectStateException(DbDataTypeError::UCI);
    }

    // Db id must be set
    if (!isset($this->dbId)) {
      throw new InvalidObjectStateException(DbDataTypeError::UDI);
    }

    // Must specify max-length if this field is variable in length
    if ($this->acceptsLength && !isset($this->maximumLength)) {
      throw new InvalidObjectStateException(DbDataTypeError::UML);
    }

    // Default must be non-null if requires-length is false
    if (!$this->requiresLength && !isset($this->defaultLength)) {
      throw new InvalidObjectStateException(DbDataTypeError::UDL);
    }

    // A field with static length should not have a max-length
    if (!$this->acceptsLength && isset($this->maximumLength)) {
      throw new InvalidObjectStateException(DbDataTypeError::MWOR);
    }

    // A field requiring length specification should not specify a default
    // length
    if ($this->requiresLength && isset($this->defaultLength)) {
      throw new InvalidObjectStateException(DbDataTypeError::RWD);
    }

    // A field requiring length specification should also allow_length
    if ($this->requiresLength && !$this->acceptsLength) {
      throw new InvalidObjectStateException(DbDataTypeError::AWOR);
    }

    // Validate category
    $category = DtCategory::fetchById($this->categoryId);
    if (!isset($category)) {
      throw new InvalidObjectStateException(DbDataTypeError::ICI);
    }

    // Validate supported-db
    $supported_db = SupportedDb::fetchById($this->dbId);
    if (!isset($supported_db)) {
      throw new InvalidObjectStateException(DbDataTypeError::IDI);
    }

    // Ensure that no 2 data-types in the same db have the same name
    $db_data_types = static::fetchDataTypesForSupportedDb($this->dbId);
    foreach ($db_data_types as $dt) {
      if ($dt->getName() == $this->name && $dt->getId() != $this->getId()) {
        throw new InvalidObjectStateException(DbDataTypeError::DNA);
      }
    }
  }

  // Override
  protected function getDbFields() {
    $fields = array(
      self::NAME_KEY => $this->name,
      self::ACCEPTS_LENGTH_KEY => $this->acceptsLength,
      self::REQUIRES_LENGTH_KEY => $this->requiresLength,
      self::DEFAULT_LENGTH_KEY => $this->defaultLength,
      self::MAXIMUM_LENGTH_KEY => $this->maximumLength,
      self::CATEGORY_ID_KEY => $this->categoryId,
      self::DB_ID_KEY => $this->dbId,
    );

    if ($this->maximumLength !== null) {
      $fields[self::MAXIMUM_LENGTH_KEY] = $this->maximumLength;
    }

    return $fields;
  }

  protected function initInstanceVars($params) {
    $this->name = $params[self::NAME_KEY];
    $this->acceptsLength = $params[self::ACCEPTS_LENGTH_KEY];
    $this->requiresLength = $params[self::REQUIRES_LENGTH_KEY];
    $this->defaultLength = $params[self::DEFAULT_LENGTH_KEY];
    $this->maximumLength = $params[self::MAXIMUM_LENGTH_KEY];
    $this->categoryId = $params[self::CATEGORY_ID_KEY];
    $this->dbId = $params[self::DB_ID_KEY];
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
