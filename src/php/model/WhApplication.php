<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/ModelObject.php");
require_once(dirname(__FILE__)."/WhUser.php");

abstract class WhApplicationError {
  const UNA = 0x00;
  const UOI = 0x01;
  const UCI = 0x02;
  const DNA = 0x03;
  const IOI = 0x04;
  const ICI = 0x05;
}

class WhApplication extends ModelObject {
  
  const NAME_KEY = "name";
  const OWNER_ID_KEY = "owner_id";
  const CREATOR_ID_KEY = "creator_id";

  protected static $tableName = "WhApplications";

  protected static $uniqueKeys = array(self::NAME_KEY);

  private
    $name,
    $ownerId,
    $creatorId;

  public static function create(
    $name,
    $owner_id,
    $creator_id
  ) {
    return static::createObject(
      array(
        self::NAME_KEY => $name,
        self::OWNER_ID_KEY => $owner_id,
        self::CREATOR_ID_KEY => $creator_id, 
      )
    );
  }

  public static function fetchByName($name) {
    return static::getObjectByUniqueKey(self::NAME_KEY, $name);
  }

  // -- OVERRIDE
  protected function getDbFields() {
    return array(
      self::NAME_KEY => $this->name,
      self::OWNER_ID_KEY => $this->ownerId,
      self::CREATOR_ID_KEY => $this->creatorId,
    );
  }

  protected function initInstanceVars($params) {
    $this->name = $params[self::NAME_KEY];
    $this->ownerId = $params[self::OWNER_ID_KEY];
    $this->creatorId = $params[self::CREATOR_ID_KEY];
  }

  protected function validateOrThrow() {

    if (!isset($this->name)) {
      throw new InvalidObjectStateException(WhApplicationError::UNA);
    }

    if (!isset($this->ownerId)) {
      throw new InvalidObjectStateException(WhApplicationError::UOI);
    }

    if (!isset($this->creatorId)) {
      throw new InvalidObjectStateException(WhApplicationError::UCI);
    }

    $app = WhApplication::fetchByName($this->name);
    if (isset($app) && $app->getId() != $this->getId()) {
      throw new InvalidObjectStateException(WhApplicationError::UCI);
    }

    $owner = WhUser::fetchById($this->ownerId);
    if (!isset($owner)) {
      throw new InvalidObjectStateException(WhDatabaseError::IOI);
    }

    $creator = WhUser::fetchById($this->creatorId);
    if (!isset($creator)) {
      throw new InvalidObjectStateException(WhDatabaseError::ICI);
    }
  }

  public function getName() {
    return $this->name;
  }

  public function getOwnerId() {
    return $this->ownerId;
  }

  public function getCreatorId() {
    return $this->creatorId;
  }
}
