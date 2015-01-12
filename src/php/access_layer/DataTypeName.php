<?php

require_once(dirname(__FILE__)."/../util/Enum.php");

final class DataTypeName extends Enum {

  // DataType names
  const INT = 'INT';
  const UNSIGNED_INT = 'INT UNSIGNED';
  const SERIAL = 'SERIAL';
  const BOOL = 'BIT'; 
  const STRING = 'VARCHAR';
  const TIMESTAMP = 'TIMESTAMP';
  const FOREIGN_KEY = 'BIGINT UNSIGNED NOT NULL';

  protected static $SUPPORTED_TYPES = array(
    self::INT,
    self::UNSIGNED_INT,
    self::BOOL,
    self::STRING,
    self::TIMESTAMP,
  );
}
