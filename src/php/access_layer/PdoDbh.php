<?php

require_once("DatabaseHandle.php");
require_once("exceptions/DbhException.php");
require_once("PdoStmt.php");

abstract class PdoDbh extends DatabaseHandle {

  private 
    $pdoDbHandle,
    $prevQueryStr;

  /**
   * genPdoDsn()
   * - Create pdo dsn.
   * @param config : DbhConfig
   * @return string : driver-specific pdo dsn
   */
  abstract protected function genPdoDsn($config);

  /**
   * genPdoOptions()
   * - Create pdo options map.
   * @param config : DbhConfig
   * @return Map<pdo-option-key, pdo-option-value> 
   *    : driver-specific pdo option map 
   */
  abstract protected function genPdoOptions($config);

  /**
   * init()
   * @override DatabaseHandle
   */
  protected function init($config) {
    $this->pdoDbHandle = null;
    $this->prevQueryStr = null;
  
    $this->pdoDbHandle = new PDO(
        $this->genPdoDsn($config),
        $config->getUsername(),
        $config->getPassword(),
        $this->genPdoOptions($config) 
    );
  }

  /**
   * serializeDsnData()
   * - Convert dsn data to string pdo dsn format.
   * @param driver_name : string
   * @param dsn_field_map : Map<string:field-key, string:field-value>
   * @return string : serialized dsn string 
   */
  protected function serializeDsnData($driver_name, $dsn_field_map) {
    $dsn_str = $driver_name . ":";
    foreach ($dsn_field_map as $k => $v) {
      $dsn_str .= $k . "=" . $v . ";";
    }
    return $dsn_str;
  }

 /**
  * beginTransaction()
  * @override DatabaseHandle
  */ 
  public function beginTransaction() {
    // Fail due to existing transaction
    assert(!$this->isInTransaction());

    // Initiate transaction
    try {
      $this->pdoDbHandle->beginTransaction(); 
    } catch (PDOException $e) {
      throw new DbhException($e, $this->prevQueryStr); 
    }
  }
  
  /**
   * commit()
   * @override DatabaseHandle
   */ 
  public function commit() {
    // Fail due to existing transaction
    assert($this->isInTransaction());

    // Commit changes 
    try {
      $this->pdoDbHandle->commit(); 
    } catch (PDOException $e) {
      throw new DbhException($e, $this->prevQueryStr); 
    }
  }
  
  /**
   * isInTransaction()
   * @override DatabaseHandle
   */ 
  public function isInTransaction() {
    try {
      return $this->pdoDbHandle->inTransaction();
    } catch (PDOException $e) {
      throw new DbhException($e, $this->prevQueryStr); 
    }
  }

  /**
   * query()
   * @override DatabaseHandle
   */ 
  public function query($query_str) {
    $this->prevQueryStr = $query_str;
    try {
      $pdo_stmt = $this->pdoDbHandle->query($query_str);
      return new PdoStmt($pdo_stmt);
      // TODO return PreparedStatement
    } catch (PDOException $e) {
      throw new DbhException($e, $this->prevQueryStr); 
    }
  }

  /**
   * prepare()
   * @override DatabaseHandle
   */ 
  public function prepare($prepared_query_str) {
    try {
      $pdo_stmt = $this->pdoDbHandle->prepare($prepared_query_str);
      return new PdoStmt($pdo_stmt);
    } catch (PDOException $e) {
      throw new DbhException($e, $this->prevQueryStr); 
    }
  }

  /**
   * getPreviousQuery()
   * @override DatabaseHandle
   */ 
  public function getPreviousQuery() {
    return $this->prevQueryStr;
  }

  /**
   * transformParamName()
   * @override DatabaseHandle
   */ 
  public function transformParamName($param_name) {
    assert(!empty($param_name) && $param_name[0] != ':');
    return ':' . $param_name;
  }
  
  /**
   * getLastInsertId()
   * @override DatabaseHandle
   */ 
  public function getLastInsertId() {
    try {
      return $this->pdoDbHandle->lastInsertId();
    } catch (PDOException $e) {
      throw new DbhException($e, $this->prevQueryStr); 
    }
  }

  /**
   * close()
   * @override DatabaseHandle
   */ 
  public function close() {
    try {
      return $this->pdoDbHandle = null;
    } catch (PDOException $e) {
      throw new DbhException($e, $this->prevQueryStr); 
    }
  }
}
