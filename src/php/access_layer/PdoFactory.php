<?php

abstract class PdoFactory {

  /**
   * Singleton instance of PdoFactory.
   */
  private static $instance = null;

  /**
   * Pdo db handle.
   */
  private $pdoConnection;

  /**
   * get()
   * - Provide reference to PdoFactory singleton.
   */
  public static function get() {
    // Initialize singleton, if needed 
    if (!isset(self::$instance)) {
      self::$instance = new static();
    }

    return self::$instance;
  }

  /**
   * __construct()
   * - This Ctor is private to enable singleton pattern. 
   */
  private function __construct() {}

  /**
   * createConnection()
   * - Initialize PDO connection.
   * - TODO connect to master for write reqs and connect
   *   to slaves for read reqs.
   */  
  protected abstract function createConnection($req_type=null);

  /**
   * loadDbConfig()
   * - Load db configuration specification.
   * - TODO configure function to parse db configuration
   *   from a text file.
   */ 
  protected abstract function loadDbConfig();

  /**
   * getConnection()
   * - Return connection to db.
   * - 1-to-1 mapping from php process to data store (for now).
   * - Creates and caches connection if this is first access.
   */
  public function getConnection($req_type=null) {
    // Create and cache connection, if missing
    if (!isset($this->pdoConnection)) {
      $this->loadDbConfig();
      $this->pdoConnection = $this->createConnection($req_type);
    }

    return $this->pdoConnection;
  }
}

