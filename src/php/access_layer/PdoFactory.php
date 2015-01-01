<?php

abstract class PdoFactory {

  /**
   * Pdo db handle.
   */
  private $pdoConnection;

  /**
   * Configuration for pdo factory.
   */
  protected $dbhConfig;

  /**
   * get()
   * - Provide reference to PdoFactory singleton.
   * @param config : DbhConfig 
   * @return PdoFactory : pdo db factory
   */
  public static function get($config) {
    return new static($config);
  }

  /**
   * __construct()
   * - This Ctor is private to enable factory pattern. 
   */
  private function __construct($config) {
    $this->dbhConfig = $config;
  }

  /**
   * createConnection()
   * - Initialize PDO connection.
   * - TODO connect to master for write reqs and connect
   *   to slaves for read reqs.
   */  
  protected abstract function createConnection($req_type=null);

  /**
   * getConnection()
   * - Return connection to db.
   * - 1-to-1 mapping from php process to data store (for now).
   * - Creates and caches connection if this is first access.
   */
  public function getConnection($req_type=null) {
    // Create and cache connection, if missing
    if (!isset($this->pdoConnection)) {
      $this->pdoConnection = $this->createConnection($req_type);
    }

    return $this->pdoConnection;
  }
}

