<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/ModelObject.php");
require_once(dirname(__FILE__)."/WhApplication.php");

abstract class WhDatabaseError {
  const UNA = 0x00;
  const UAI = 0x01;
  const IAI = 0x02;
  const DNA = 0x03;
}

class WhDatabase extends ModelObject {

  const NAME_KEY = "name";
  const APP_ID_KEY = "app_id";

  protected static $tableName = "WhDatabases";

  private
    $name,
    $appId;

  public static function create(
    $name,
    $app_id
  ) {
    return static::createObject(
      array(
        self::NAME_KEY => $name,
        self::APP_ID_KEY => $app_id
      )
    );
  }
  
  public static function fetchDbsInApplication($app_id) {
    return static::getObjectsByParams(
      array(self::APP_ID_KEY => $app_id)
    );
  }

  // -- OVERRIDE
  protected function getDbFields() {
    return array(
      self::NAME_KEY => $this->name,
      self::APP_ID_KEY => $this->appId,
    );
  }

  protected function initInstanceVars($params) {
    $this->name = $params[self::NAME_KEY];
    $this->appId = $params[self::APP_ID_KEY];
  }

  protected function validateOrThrow() {
    if (!isset($this->name)) {
      throw new InvalidObjectStateException(WhDatabaseError::UNA);
    }
    
    if (!isset($this->appId)) {
      throw new InvalidObjectStateException(WhDatabaseError::UAI);
    }

    $parent_app = WhApplication::fetchById($this->appId);
    if (!isset($parent_app)) {
      throw new InvalidObjectStateException(WhDatabaseError::IAI);
    }

    $app_dbs = static::fetchDbsInApplication($this->appId);
    foreach ($app_dbs as $db) {
      if ($db->getName() == $this->name && $db->getId() != $this->getId()) {
        throw new InvalidObjectStateException(WhDatabaseError::DNA);
      }
    }
  }

  public function getName() {
    return $this->name;
  }

  public function getAppId() {
    return $this->appId;
  }
}
