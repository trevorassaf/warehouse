<?php

require_once("PreparedStatement.php");
require_once("DataTypeName.php");

class PdoStmt implements PreparedStatement {

  private static $PDO_TYPE_MAP = array(
      DataTypeName::INT => PDO::PARAM_INT,
      DataTypeName::UNSIGNED_INT => PDO::PARAM_INT,
      DataTypeName::SERIAL => PDO::PARAM_INT,
      DataTypeName::BOOL => PDO::PARAM_BOOL,
      DataTypeName::STRING => PDO::PARAM_STR,
      DataTypeName::TIMESTAMP => PDO::PARAM_STR, 
  );

  private $pdoStmt;

  public function __construct($pdo_stmt) {
    // Fail due to null pdo_stmt
    assert(isset($pdo_stmt));
    $this->pdoStmt = $pdo_stmt; 
  }

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

  /**
   * fetchAllRows()
   * - Fetch all rows from result set.
   * @return array(mixed)
   */
  public function fetchAllRows() {
    // Fail due to invalid pdo statement
    assert($this->pdoStmt); 
  
    try {
      return $this->pdoStmt->fetchAll();
    } catch (PDOException $e) {
      throw new DbhException($e); 
    }
  }

  /**
   * translateDbParamType()
   * - Transform warehouse datatype to PDO datatype.
   * @param db_type : DataTypeName
   * @return PDO::PARAM_*
   */
  private function transalteDbParamType($db_type) {
    // Fail due to invalid type
    assert(self::$PDO_TYPE_MAP[$db_type]);
    return self::$PDO_TYPE_MAP[$db_type];
  }
}
