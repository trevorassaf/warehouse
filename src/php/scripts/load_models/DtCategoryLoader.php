<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/DtCategoryNames.php");
require_once(dirname(__FILE__)."/../../model/DtCategory.php");

class DtCategoryLoader {

  public static function load() {
    self::clearCategories();
    self::loadCategories();
  }
  
  private static function loadCategories() {
    $category_names = DtCategoryNames::getNames();
    foreach ($category_names as $name) {
      DtCategory::create($name);
    }
  }

  private static function clearCategories() {
    DtCategory::deleteAll(); 
  }  
}
