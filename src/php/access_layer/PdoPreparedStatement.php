<?php

require_once("PreparedStatement.php");
require_once("DbType.php");

class PdoStmt implements PreparedStatement {

  private static $PDO_TYPE_MAP = array(
     
  );

  private $pdoStmt

  /**
   * execute()
   * @override PreparedStatement
   */
  public function execute() {
    // Fail due to invalid pdo statement
    assert($this->pdoStmt); 

    try {
      $this->pdoStmt->execute();
    } catch (PDOException $e) {
      throw new DbhException($e); 
    }
  }

  /**
   * bindValue()
   * @override PreparedStatement
   */
  public function bindValue($param_name, $value, $type) {
    // Fail due to invalid pdo statement
    assert($this->pdoStmt); 
  
    try {
      $this->pdoStmt->bindValue(
          $param_name,
          $value,
          $this->transalteDbParamType($type)
      );
    } catch (PDOException $e) {
      throw new DbhException($e); 
    }
  }

  private function transalteDbParamType($db_type) {
    
  }
}
