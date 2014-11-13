<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/ModelObject.php");
require_once(dirname(__FILE__)."/WhTable.php");
require_once(dirname(__FILE__)."/DbDataType.php");

abstract class WhColumnError {
  const UNA = 0x00;
  const ULN = 0x01;
  const UIU = 0x02;
  const UIF = 0x03;
  const UAN = 0x04;
  const UTI = 0x06;
  const UDI = 0x07;

  const ITI = 0x08;
  const IDI = 0x09;

  const DNA = 0x10;
}

class WhColumn extends ModelObject {

  const NAME_KEY = "name";
  const LENGTH_KEY = "length";
  const IS_UNIQUE_KEY = "is_unique";
  const IS_FOREIGN_KEY = "is_foreign_key";
  const ALLOWS_NULL_KEY = "allows_null";
  const TABLE_ID_KEY = "table_id";
  const DT_ID_KEY = "dt_id";

  protected static $tableName = "WhColumns";

  private
    $name,
    $length,
    $isUnique,
    $isForeignKey,
    $allowsNull,
    $tableId,
    $dtId;

  public static function create(
    $name,
    $length,
    $is_unique,
    $is_foreign_key,
    $allows_null,
    $table_id,
    $dt_id
  ) {
    return static::createObject(
      array(
        self::NAME_KEY => $name,
        self::LENGTH_KEY => $length,
        self::IS_UNIQUE_KEY => $is_unique,
        self::IS_FOREIGN_KEY => $is_foreign_key,
        self::ALLOWS_NULL_KEY => $allows_null,
        self::TABLE_ID_KEY => $table_id,
        self::DT_ID_KEY => $dt_id,
      )
    );
  }

  public static function fetchColumnsForTable($table_id) {
    return static::getObjectsByParams(
      array(
        self::TABLE_ID_KEY => $table_id,
      )
    );
  }

  protected function getDbFields() {
    return array(
      self::NAME_KEY => $this->name,
      self::LENGTH_KEY => $this->length,
      self::IS_UNIQUE_KEY => $this->isUnique,
      self::IS_FOREIGN_KEY => $this->isForeignKey,
      self::ALLOWS_NULL_KEY => $this->allowsNull,
      self::TABLE_ID_KEY => $this->tableId,
      self::DT_ID_KEY => $this->dtId,
    );
  }

  protected function initInstanceVars($params) {
    $this->name = $params[self::NAME_KEY];
    $this->length = $params[self::LENGTH_KEY];
    $this->isUnique = $params[self::IS_UNIQUE_KEY];
    $this->isForeignKey = $params[self::IS_FOREIGN_KEY];
    $this->allowsNull = $params[self::ALLOWS_NULL_KEY];
    $this->tableId = $params[self::TABLE_ID_KEY];
    $this->dtId = $params[self::DT_ID_KEY];
  }

  protected function validateOrThrow() {
    if (!isset($this->name)) {
      throw new InvalidObjectStateException(WhColumnError::UNA);
    }
    
    if (!isset($this->length)) {
      throw new InvalidObjectStateException(WhColumnError::ULN);
    }

    if (!isset($this->isUnique)) {
      throw new InvalidObjectStateException(WhColumnError::UIU);
    }

    if (!isset($this->isForeignKey)) {
      throw new InvalidObjectStateException(WhColumnError::UIF);
    }

    if (!isset($this->allowsNull)) {
      throw new InvalidObjectStateException(WhColumnError::UTI);
    }

    if (!isset($this->dtId)) {
      throw new InvalidObjectStateException(WhColumnError::UDI);
    }

    // Check for invalid fields
    $table = WhTable::fetchById($this->tableId);
    if (!isset($table)) {
      throw new InvalidObjectStateException(WhColumnError::ITI);
    }

    $data_type = DbDataType::fetchById($this->dtId);
    if (!isset($data_type)) {
      throw new InvalidObjectStateException(WhColumnError::IDI);
    }

    $table_columns = static::fetchColumnsForTable($this->tableId);
    foreach ($table_columns as $col) {
      if ($col->getName() == $this->name && $col->getId() != $this->getId()) {
        throw new InvalidObjectStateException(WhColumnError::DNA);
      }
    }
  }

  public function getName() {
    return $this->name;
  }

  public function getLength() {
    return $this->length;
  }

  public function getIsUnique() {
    return $this->isUnique;
  }

  public function getIsForeignKey() {
    return $this->isForeignKey;
  }

  public function getAllowsNull() {
    return $this->allowsNull;
  }

  public function getTableId() {
    return $this->tableId;
  }

  public function getDtId() {
    return $this->dtId;
  }
}


