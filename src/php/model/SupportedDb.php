<?php

// -- DEPENDENCIES
require_once("ModelObject.php");

class SupportedDb extends ModelObject {

  const NAME_KEY = "name";

  protected static $uniqueKeys = array(self::NAME_KEY);

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

  protected function initInstanceVars($params) {
    $this->name = $params[self::NAME_KEY];
  }

  // Assocs
  public function getDataTypes() {
    return DbDataTypes::fetchByDbId($this->getId());
  }

  // Getters
  public function getName() {
    return $this->name;
  }
}
