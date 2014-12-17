<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/MySqlPdoConfig.php");

class MySqlPdoFactory implements PdoFactory {

  public function genConnection() {
    // Open new db connection
    $dbh = new PDO(
      $this->pdoConfig->getDsn(), 
      $this->pdoConfig->getUserName(),
      $this->pdoConfig->getPassword(),
      $this->getOptions());                    

    return $dbh;
  }
}
