<?php

// -- CONSTANTS
defined("COLLIDING_UNIQUE_KEY_MESSAGE") 
  ? null : define("COLLIDING_UNIQUE_KEY_MESSAGE", "Value for unique key already exists in table");
defined("NONEXISTANT_UNIQUE_KEY_MESSAGE")
  ? null : define("NONEXISTANT_UNIQUE_KEY_MESSAGE", "Specified unique key does not exist");

class InvalidUniqueKeyException extends Exception {

  // -- INSTANCE VARS 
  private
    $key,
    $value,
    $errorMessage;

  public function __construct($key, $value, $errorMessage) {
    $this->key = $key;
    $this->value = $value;
    $this->errorMessage = $errorMessage;
  }

  public static function createForCollidingUniqueKey($key, $value) {
    return new static($key, $value, COLLIDING_UNIQUE_KEY_MESSAGE); 
  }

  public static function createForNonexistantUniqueKey($key, $value) {
    return new static($key, $value, NONEXISTANT_UNIQUE_KEY_MESSAGE);
  }

  public function __toString() {
    return "ERROR: " . $this->errorMessage . " <key: $this->key, value: $this->value>";
  }
}
