<?php

class DbhException extends Exception {

  public function __construct($exception, $query_str=null) {
    // Create error message
    $error_message = $exception->getMessage();
    if (isset($query_str)) {
      $error_message .= " Previous query: " . $query_str . "\n"; 
    }

    parent::__construct($error_message);
  }
}
