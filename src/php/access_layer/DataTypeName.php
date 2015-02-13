<?php

require_once(dirname(__FILE__)."/../util/Enum.php");

final class DataTypeName extends Enum {

  // DataType names
  const INT = 0x00;
  const UNSIGNED_INT = 0x01;
  const SERIAL = 0x02;
  const BOOL = 0x03; 
  const STRING = 0x04;
  const TIMESTAMP = 0x05;
  const FOREIGN_KEY = 0x06;
  const DATE = 0x07;

  protected static $SUPPORTED_TYPES = array(
    self::INT,
    self::UNSIGNED_INT,
    self::BOOL,
    self::STRING,
    self::TIMESTAMP,
    self::FOREIGN_KEY,
    self::DATE,
  );
}
