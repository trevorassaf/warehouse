<?php

/**
 * Represents a generic datatype for a particular database.
 * C
 * - 
 */
abstract class DbDataType {
  
  private
    $name,
    $hasLength,
    $length;

  public __construct($name, $has_length, $length) {
    $this->name = $name;
    $this->hasLength = $has_length;
    $this->length = $length;    
  }

  public function getName() {
    return $this->name;
  }

  public function hasLength() {
    return $this->hasLength;
  }

  public function getLength() {
    assert($this->hasLength);

    return $this->length;
  }

  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  public function setLength($length) {
    assert($length > 0);

    $this->length = $length;
    return $this;
  }

  public function unsetLength() {
    $this->hasLength = false;
    return $this;
  }
}

