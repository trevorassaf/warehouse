<?php

require_once(dirname(__FILE__)."/../util/Enum.php");
require_once(dirname(__FILE__)."/../access_layer/DataTypeName.php");

final class DataTypeArgRequirement extends Enum {

  // DataType Requirement
  const NONE = 0;
  const ALLOWS_ONE = 1;
  const REQUIRES_ONE = 2;
  const REQUIRES_ONE_ALLOWS_TWO = 3;
  const ALLOWS_ONE_AND_TWO = 4;
  const REQUIRES_TWO = 5;

  protected static $SUPPORTED_TYPES = array(
    self::NONE,
    self::ALLOWS_ONE,
    self::REQUIRES_ONE,
    self::REQUIRES_ONE_ALLOWS_TWO,
    self::ALLOWS_ONE_AND_TWO,
    self::REQUIRES_TWO,
  );
}

final class DataType {

  private static
    $intDataType = null,
    $unsignedIntDataType = null,
    $boolDataType = null,
    $stringDataType = null,
    $timestampDataType = null,
    $dateDataType = null,
    $foreignKeyDataType = null,
    $floatDataType = null;

  private
    $name,
    $argRequirement;

  public function int() {
    if (!isset(self::$intDataType)) {
      self::$intDataType = new self(DataTypeName::INT, DataTypeArgRequirement::NONE);
    }
    return self::$intDataType;
  }

  public function unsignedInt() {
    if (!isset(self::$unsignedIntDataType)) {
      self::$unsignedIntDataType = new self(DataTypeName::UNSIGNED_INT, DataTypeArgRequirement::NONE);
    }
    return self::$unsignedIntDataType;
  }
  
  public function float() {
    if (!isset(self::$floatDataType)) {
      self::$floatDataType = new self(DataTypeName::FLOAT, DataTypeArgRequirement::NONE);
    }
    return self::$floatDataType;
  }

  public function bool() {
    if (!isset(self::$boolDataType)) {
      self::$boolDataType = new self(DataTypeName::BOOL, DataTypeArgRequirement::NONE);
    }
    return self::$boolDataType;
  }

  public function string() {
    if (!isset(self::$stringDataType)) {
      self::$stringDataType = new self(DataTypeName::STRING, DataTypeArgRequirement::REQUIRES_ONE);
    }
    return self::$stringDataType;
  }

  public function timestamp() {
    if (!isset(self::$timestampDataType)) {
      self::$timestampDataType = new self(DataTypeName::TIMESTAMP, DataTypeArgRequirement::NONE);
    }
    return self::$timestampDataType;
  }

  public function date() {
    if (!isset(self::$dateDataType)) {
      self::$dateDataType = new self(DataTypeName::DATE, DataTypeArgRequirement::NONE);
    }
    return self::$dateDataType;
  }

  public function foreignKey() {
    if (!isset(self::$foreignKeyDataType)) {
      self::$foreignKeyDataType = new self(DataTypeName::FOREIGN_KEY, DataTypeArgRequirement::NONE);
    }
    return self::$foreignKeyDataType;
  }

  private function __construct(
    $dt_name,
    $arg_requirement
  ) {
    // Fail if invalid enum type
    DataTypeName::validateType($dt_name);
    DataTypeArgRequirement::validateType($arg_requirement);

    $this->name = $dt_name;
    $this->argRequirement = $arg_requirement;
  }

  public function requiresFirstLength() {
    return $this->argRequirement == DataTypeArgRequirement::REQUIRES_ONE
        || $this->argRequirement == DataTypeArgRequirement::REQUIRES_ONE_ALLOWS_TWO
        || $this->argRequirement == DataTypeArgRequirement::REQUIRES_TWO;
  }

  public function allowsFirstLength() {
    return $this->requiresFirstLength() || $this->argRequirement == DataTypeArgRequirement::ALLOWS_ONE
       || $this->argRequirement == DataTypeArgRequirement::ALLOWS_ONE_AND_TWO; 
  }

  public function requiresSecondLength() {
    return $this->argRequirement == DataTypeArgRequirement::REQUIRES_TWO; 
  }

  public function allowsSecondLength() {
    return $this->requiresSecondLength() || $this->argRequirement == DataTypeArgRequirement::REQUIRES_ONE_ALLOWS_TWO
       || $this->argRequirement == DataTypeArgRequirement::ALLOWS_ONE_AND_TWO; 
  }

  public function getName() {
    return $this->name;
  }
}
