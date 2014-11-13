<?php

// -- DEPENDENCIES
require_once("ModelObject.php");

class DtCategoryError {
  const UNA = 0x00;
  const DNA = 0x01;
}

class DtCategory extends ModelObject {

  const NAME_KEY = "name";

  protected static $uniqueKeys = array(self::NAME_KEY);

  protected static $tableName = "DtCategories";

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
      throw new InvalidObjectStateException(DtCategoryError::UNA);
    }

    $category = static::fetchByName($this->name);
    if (isset($category) && $category->getId() != $this->getId()) {
      throw new InvalidObjectStateException(DtCategoryError::DNA);
    }
  }

  protected function initInstanceVars($params) {
    $this->name = $params[self::NAME_KEY];
  }

  // Getters
  public function getName() {
    return $this->name;
  }
}
