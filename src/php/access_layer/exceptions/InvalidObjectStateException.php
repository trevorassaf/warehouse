<?php

class InvalidObjectStateException extends Exception {

  private $errorCode;

  public function __construct($error_code) {
    $this->errorCode = $error_code;
  }

  public function getErrorCode() {
    return $this->errorCode;
  }
}
