<?php

abstract class DatabaseHandle {

  public function __construct($config) {
    $this->init($config);
  }

  /**
   * init()
   * - Initialize and cache database handle. 
   * @param config : DbConfig
   */
  abstract protected function init($config);

  /**
   * beginTransaction()
   * - Initiate transaction.
   */
  abstract public function beginTransaction();
  
  /**
   * commit()
   * - Conclude transaction, commit changes.
   */
  abstract public function commit();

  /**
   * isInTransaction()
   * - Return true iff transaction is in session.
   */ 
  abstract public function isInTransaction();

  /**
   * query()
   * - Perform query.
   * @param query_str : string
   * @return PreparedStatement : prepared statement containing
   *    results.
   */
  abstract public function query($query_str);

  /**
   * prepare()
   * - Prepare a query for later execution.
   * @param query_str : string
   * @return PreparedStatement : resulting prepared statement.
   */
  abstract public function prepare($query_str);

  /**
   * getPreviousQuery()
   * - Return last query performed.
   * @return string : query string
   */
  abstract public function getPreviousQuery();

  /**
   * getLastInsertId()
   * - Return id of last inserted record.
   * @return unsigned int : id
   */
  abstract public function getLastInsertId();

  /**
   * transformParamName()
   * - Convert param name to prepared-statement-compatible
   *    string.
   * @param param_name : string
   * @return string : transformed param name
   */
  abstract public function transformParamName($param_name);

  /**
   * close()
   * - Close database connection.
   */
  abstract public function close();
}
