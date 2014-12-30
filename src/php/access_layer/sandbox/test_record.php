<?php

require_once(dirname(__FILE__)."/../Import.php");

class Person extends SqlRecord {

  // Db Keys
  const NAME_KEY = "name";

  private $name;

  public static function create($name) {
    return static::insert(array(self::NAME_KEY => $name));
  }

  protected function getDbFields() {
    return array(self::NAME_KEY => $this->name);
  }

  protected function initInstanceVars($init_params) {
    $this->name = $init_params[self::NAME_KEY];
  }

  public function getName() {
    return $name;
  }
}

$person = Person::create("test-name");
var_dump($person);
