<?php

require_once(dirname(__FILE__)."/../util/Import.php")

abstract class DbTier extends Enum {

  const DB_TIER_DELIMITER = '_';

  // Tier names 
  const TESTING = "testing";
  const DEVELOPMENT = "development";
  const STAGING = "staging";
  const PRODUCTION = "production";

  protected static $SUPPORTED_TYPES = array(
    self::TESTING,
    self::DEVELOPMENT,
    self::STAGING,
    self::PRODUCTION,
  );

  public static function genDbNameWithTier($db_name, $tier_name) {
    static::validateType($tier_name);
    return $db_name . self::DB_TIER_DELIMITER . $tier_name; 
  }
}
