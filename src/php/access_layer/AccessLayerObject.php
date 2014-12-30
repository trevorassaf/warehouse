<?php
// -- DEPENDENCIES
require_once("PdoFactory.php");

abstract class AccessLayerObject {

  /**
   * Factory for generating db connections.
   */ 
  protected static $databaseFactory = null;

  /**
   * initDatabaseFactory()
   * - Initialize database factory/
   */
  public static function initDatabaseFactory() {
    // Initialize db factory
    if (!isset(self::$databaseFactory)) {
      self::$databaseFactory = MySqlPdoFactory::get(); 
    }
  }

  /**
   * insert() 
   * - Insert object into database and return model.
   * - @param init_params: map of params (string:param_name => string:value).
	 */
  protected abstract static function insert($init_params);

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

AccessLayerObject::initDatabaseFactory();
?>
