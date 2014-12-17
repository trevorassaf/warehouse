<?php

require_once(dirname(__FILE__)."/../Import.php");

class WhUserTest extends PHPUnit_Framework_TestCase {

  /**
   * Test construction of successful users
   */
  public function testSuccessfulConstruction() {
    try {
      // Insert first user
      $user1 = WhUser::create("u1first", "u1last", "u1pass", "u1user", "u1email");
      $user1->delete();

      // Insert second user, shouldn't throw exception
      $user2 = WhUser::create("u2first", "u2last", "u2pass", "u2user", "u2email");
      $user2->delete();

      // Insert third user, same name as first user
      $user3 = WhUser::create("u1first", "u1last", "u3pass", "u3user", "u3email");
      $user3->delete();
    } catch (Exception $e) {
      // These constructions shouldn't throw...
      $this->assertFalse();
    }

    // Should succeed 
    $this->assertTrue();
  }

  /**
   * Test construction of invalid users: null values for required fields
   */
  public function testInvalidConstructionWithNullValues() {
    try {
      // User with null first name
      User     
    } catch (InvalidObjectStateException $e) {
    }
  }

  public function setUp() {
    
  }

  public function tearDown() {
  
  }

  public static function setUpBeforeClass() {
  
  }
}
