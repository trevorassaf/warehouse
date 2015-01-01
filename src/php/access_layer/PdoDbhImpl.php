<?php

require_once("DatabaseHandle.php");
require_once("DbhException.php");

abstract class PdoDbh implements DatabaseHandle {

  private 
    $pdoDbHandle,
    $prevQueryStr;

  protected function init($config) {
    $this->pdoDbHandle = null;
    $this->prevQueryStr = null;


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
      return $this->pdoDbHandle->isInTransaction();
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
      // TODO return PreparedStatement
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
      return $this->pdoDbHandle->getLastInsertId();
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
