<?php

require_once("PdoFactory.php");

class MySqlPdoFactory extends PdoFactory {

  /**
   * createConnection()
   * @override PdoFactory
   */ 
  protected function createConnection($req_type=null) {
    return new MySqlPdoDbh($this->dbhConfig);
  }
}
