<?php

require_once(dirname(__FILE__)."/../util/Enum.php");

abstract class DataTypeName extends Enum {

  // DataType names
  const INT = 'INT';
  const UNSIGNED_INT = 'INT UNSIGNED';
  const SERIAL = 'SERIAL';
  const BOOL = 'BIT'; 
  const STRING = 'VARCHAR';
  const TIMESTAMP = 'TIMESTAMP';

  protected static $SUPPORTED_TYPES = array(
    self::INT,
    self::UNSIGNED_INT,
    self::SERIAL,
    self::BOOL,
    self::STRING,
    self::TIMESTAMP,
  );
}

abstract class DataTypeArgRequirement extends Enum {

  // DataType Requirement
  const NONE = 0;
  const ALLOWS_ONE = 1;
  const REQUIRES_ONE = 2;
  const ALLOWS_TWO = 3;
  const REQUIRES_TWO = 4;

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
    $serialDataType = null,
    $boolDataType = null,
    $stringDataType = null,
    $timestampDataType = null;

  private
    $name,
    $argRequirement;

  public function createInt() {
    if (!isset(self::$intDataType)) {
      self::$intDataType = new self(DataTypeName::INT, DataTypeArgRequirement::NONE);
    }
    return self::$intDataType;
  }

  public function createUnsignedInt() {
    if (!isset(self::$unsignedIntDataType)) {
      self::$unsignedIntDataType = new self(DataTypeName::UNSIGNED_INT, DataTypeArgRequirement::NONE);
    }
    return self::$unsignedIntDataType;
  }

  public function createSerial() {
    if (!isset(self::$serialDataType)) {
      self::$serialDataType = new self(DataTypeName::SERIAL, DataTypeArgRequirement::NONE);
    }
    return self::$serialDataType;
  }

  public function createBool() {
    if (!isset(self::$boolDataType)) {
      self::$boolDataType = new self(DataTypeName::BOOL, DataTypeArgRequirement::NONE);
    }
    return self::$boolDataType;
  }

  public function createString() {
    if (!isset(self::$stringDataType)) {
      self::$stringDataType = new self(DataTypeName::STRING, DataTypeArgRequirement::REQUIRES_ONE);
    }
    return self::$stringDataType;
  }

  public function createTimestamp() {
    if (!isset(self::$timestampDataType)) {
      self::$timestampDataType = new self(DataTypeName::TIMESTAMP, DataTypeArgRequirement::NONE);
    }
    return self::$timestampDataType;
  }

  private function __construct(
    $dt_name,
    $arg_requirement
  ) {
    // Fail if invalid enum type
    DataTypeName::validateType($dt_name);
    DataTypeArgRequirement::validateType($dt_name);

    $this->name = $dt_name;
    $this->argRequirement = $arg_requirement;
  }

}
