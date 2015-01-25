<?php
// -- DEPENDENCIES
require_once("PdoFactory.php");
require_once("DbhConfig.php");
require_once("MySqlPdoFactory.php");

abstract class AccessLayerObject {

  /**
   * Factory for generating db connections.
   */ 
  protected static $databaseFactory = null;

  /**
   * initDatabaseFactory()
   * - Initialize database factory/
   */
  public static function initDatabaseFactory($mysql_config) {
    // Initialize db factory
    if (!isset(static::$databaseFactory)) {
      static::$databaseFactory = MySqlPdoFactory::get($mysql_config); 
    }
  }

  /**
   * insert() 
   * - Insert object into database and return model.
   * - @param init_params: map of params (string:param_name => string:value).
	 */
  public abstract static function insert($init_params);

  /**
   * save()
   * - Save record to table.
   */
  public abstract function save();

  /**
   * delete()
   * - Delete record from table.
   */
	public abstract function delete();
}
