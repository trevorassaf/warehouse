<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/ModelObject.php");
require_once(dirname(__FILE__)."/WhDatabase.php");

abstract class WhTableError {
  const UNA = 0x00;
  const UDB = 0x01;
  const DNA = 0x02;
  const IDB = 0x03;
}

class WhTable extends ModelObject {
  
  const NAME_KEY = "name";
  const DB_ID_KEY = "db_id";

  protected static $tableName = "WhTables";

  private
    $name,
    $dbId;

  public static function create($name, $db_id) {
    return static::createObject(
      array(
        self::NAME_KEY => $name,
        self::DB_ID_KEY => $db_id,
      )
    );
  }

  public static function fetchTablesInDb($db_id) {
    return static::getObjectsByParams(
      array(
        self::DB_ID_KEY => $db_id,
      )
    );
  }

  // -- OVERRIDE
  protected function getDbFields() {
    return array(
      self::NAME_KEY => $this->name,
      self::DB_ID_KEY => $this->dbId,
    );
  }

  protected function initInstanceVars($params) {
    $this->name = $params[self::NAME_KEY];
    $this->dbId = $params[self::DB_ID_KEY];
  }

  protected function validateOrThrow() {
    if (!isset($this->name)) {
      throw new InvalidObjectStateException(WhApplicationError::UNA);
    }
    
    if (!isset($this->dbId)) {
      throw new InvalidObjectStateException(WhApplicationError::UDB);
    }

    $db = WhDatabase::fetchById($this->dbId);
    if (!isset($db)) {
      throw new InvalidObjectStateException(WhApplicationError::IDB);
    }

    $tables_in_db = static::fetchTablesInDb($this->dbId);
    foreach ($tables_in_db as $table) {
      if ($table->getName() == $this->name && $table->getId() != $this->getId()) {
        throw new InvalidObjectStateException(WhApplicationError::DNA);
      }
    }
  }


  public function getName() {
    return $this->name;
  }

  public function getDbId() {
    return $this->dbId;
  }
}

