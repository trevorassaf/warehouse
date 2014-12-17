<?php

require_once(dirname(__FILE__)."/../Import.php");

class WhColumnTest extends PHPUnit_Framework_TestCase {

  // Test construction 
  public function testSuccessfulConstruction() {
    try {
         
    } catch (Exception $e) {
      // These constructions shouldn't throw...
      $this->assertFalse();
    }
    
    $this->assertTrue();
  }
}
