<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/../access_layer/Import.php");

abstract class ModelObject extends DatabaseObject {

  const DB_NAME = "model";
  
  protected static $dbName = self::DB_NAME;
}
