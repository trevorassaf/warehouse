<?php

// -- DEPENDENCIES
require_once("ModelObject.php");
require_once(dirname(__FILE__)."/DbDataType.php");

class SupportedDbError {
  const UNA = 0x00;
  const DNA = 0x01;
}

class SupportedDb extends ModelObject {

  const NAME_KEY = "name";

  protected static $uniqueKeys = array(self::NAME_KEY);

  protected static $tableName = "SupportedDbs";

  private $name;

  public static function create($name) {
    return static::createObject(
      array(self::NAME_KEY => $name)
    ); 
  }

  public static function fetchByName($name) {
    return static::getObjectByUniqueKey(self::NAME_KEY, $name);
  }

  // Override
  protected function getDbFields() {
    return array(
      self::NAME_KEY => $this->name,
    );
  }

  protected function validateOrThrow() {
    if (!isset($this->name)) {
      throw new InvalidObjectStateException(SupportedDbError::UNA);
    }

    $db = static::fetchByName($this->name);
    if (isset($db) && $db->getId() != $this->getId()) {
      throw new InvalidObjectStateException(SupportedDbError::DNA);
    }
  }

  protected function initInstanceVars($params) {
    $this->name = $params[self::NAME_KEY];
  }

  protected function deleteChildren() {
    $data_types = $this->getDataTypes();
    foreach ($data_types as $dt) {
      $dt->delete();
      unset($dt);
    }
  }

  // Getters
  public function getName() {
    return $this->name;
  }

  // Assocs
  public function getDataTypes() {
    return DbDataType::fetchForSupportedDb($this->getId()); 
  }
}
