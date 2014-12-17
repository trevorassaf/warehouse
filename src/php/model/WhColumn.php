<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/ModelObject.php");
require_once(dirname(__FILE__)."/WhTable.php");
require_once(dirname(__FILE__)."/DbDataType.php");

abstract class WhColumnError {
  // Unset fields
  const UNA = "Unset name";
  const ULN = "Unset length";
  const UIU = "Unset is-unique-col";
  const UIF = "Unset is-foreign-key";
  const UFT = "Unset foreign-table-id";
  const UAN = "Unset allows-null";
  const UTI = "Unset table-id";
  const UDI = "Unset datatype-id";

  // Invalid IDs
  const ITI = "Invalid table-id";
  const IDI = "Invalid datatype-id";
  const IFT = "Invalid foreign-table-id";

  // Duplicates
  const DNA = "Duplicate name";
}

class WhColumn extends ModelObject {

  // Db keys
  const NAME_KEY = "name";
  const LENGTH_KEY = "length";
  const IS_UNIQUE_KEY = "is_unique";
  const IS_FOREIGN_KEY = "is_foreign_key";
  const FOREIGN_TABLE_ID = "foreign_table_id";
  const ALLOWS_NULL_KEY = "allows_null";
  const TABLE_ID_KEY = "table_id";
  const DT_ID_KEY = "dt_id";

  // Table name
  protected static $tableName = "WhColumns";

  private
    $name,
    $length,
    $isUnique,
    $isForeignKey,
    $foreignTableId,
    $allowsNull,
    $tableId,
    $dtId;

  public static function create(
    $name,
    $length,
    $is_unique,
    $is_foreign_key,
    $foreign_table_id,
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
        self::FOREIGN_TABLE_ID => $foreign_table_id,
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
      self::FOREIGN_TABLE_ID => $this->foreignTableId,
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
    $this->foreignTableId = $params[self::FOREIGN_TABLE_ID];
    $this->allowsNull = $params[self::ALLOWS_NULL_KEY];
    $this->tableId = $params[self::TABLE_ID_KEY];
    $this->dtId = $params[self::DT_ID_KEY];
  }

  protected function validateOrThrow() {
    // Check for unset fields
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

    // Check duplicate column name
    $table_columns = static::fetchColumnsForTable($this->tableId);
    foreach ($table_columns as $col) {
      if ($col->getName() == $this->name && $col->getId() != $this->getId()) {
        throw new InvalidObjectStateException(WhColumnError::DNA);
      }
    }

    // Unset and invalid foreign key
    if ($this->isForeignKey) {
      if (!isset($this->foreignTableId)) {
        throw new InvalidObjectStateException(WhColumnError::UFT);
      }
      $foreign_table = WhTable::fetchById($this->foreignTableId);
      if (!isset($foreign_table)) {
        throw new InvalidObjectStateException(WhColumnError::IFT);
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

  public function getForeignTableId() {
    return $this->foreignTableId;
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
