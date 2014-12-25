<?php
// -- DEPENDENCIES
require_once("PdoFactory.php");

abstract class AccessLayerObject {

  /**
   * Factory for generating db connections.
   */ 
  private static $databaseFactory;

  /**
   * insert() 
   * - Insert object into database and return model.
   * - @param init_params: map of params (string:param_name => string:value).
	 */
  public static function insert($init_params);

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

// Initialize database factory 
AccessLayerObject::$databaseFactory = MySqlPdoFactory::get();

?>
