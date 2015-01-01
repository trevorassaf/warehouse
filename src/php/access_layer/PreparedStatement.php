<?php

interface PreparedStatement {

  /**
   * execute()
   * - Execute prepared statement.
   */
  public function execute();

  /**
   * bindValue()
   * - Bind parameter to prepared statement.
   * @param param_name : string
   * @param value : mixed
   * @param type : DataType
   */
  public function bindValue($param_name, $value, $type);

  /**
   * fetchAllRows() 
   * - Return array of result rows.
   * @return array<mixed> : array of result rows
   */
  public function fetchAllRows();
}
