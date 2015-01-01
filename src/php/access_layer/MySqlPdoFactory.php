<?php

// -- DEPENDENCIES
require_once("MySqlPdoConfig.php");
require_once("PdoFactory.php");
require_once("DbTier.php");

class MySqlPdoFactory extends PdoFactory {

  /**
   * createConnection()
   * @Override PdoFactory
   */
  protected function createConnection($req_type=null) {
    // TODO: add load balancing logic based on request type 
    if (isset($req_type)) {
      throw new Exception("No support for req type specification at this time."); 
    }

    // Open new db connection
    return new PDO(
      $this->pdoConfig->getDsn(), 
      $this->pdoConfig->getUserName(),
      $this->pdoConfig->getPassword(),
      $this->pdoConfig->getOptions());                    
  }

  /**
   * loadDbConfig()
   * @Override PdoFactory
   */
  /**
  protected function loadDbConfig() {
    $builder = new MySqlPdoConfigBuilder();
    $this->mySqlConfig = $builder
      ->setConnectionType(MySqlConnectionType::LOCALHOST)
      ->setUsername("trevor")
      ->setPassword("password")
      ->setDbName("ocr")
      ->setTier(DbTier::DEVELOPMENT)
      ->setCharSet(MySqlDbCharSet::ASCII)
      ->build();
  }
   */
}
