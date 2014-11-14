<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/../../model/DtCategory.php");
require_once(dirname(__FILE__)."/../../model/DbDataType.php");
require_once(dirname(__FILE__)."/DtCategoryNames.php");

abstract class ModelLoader {

  protected static $dbName;

  public function loadModel() {
    $db = static::refreshDb(static::$dbName);    
    static::loadColumns($db);
  }

  abstract protected static function loadColumns($db);
  
  protected static function loadCategoryOrCreate($cat_name) {
    $cat = DtCategory::fetchByName($cat_name);
   
    if ($cat == null) {
      $cat = DtCategory::create($cat_name);
    }
    
    return $cat;
  }

  protected static function refreshDb($db_name) {
    $db = SupportedDb::fetchByName($db_name);

    // Delete db 
    if ($db != null) {
      $db->delete(); 
    }

    // Create new database entry
    return SupportedDb::create($db_name);
  }
}
