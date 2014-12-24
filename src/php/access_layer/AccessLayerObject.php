<?php
// -- DEPENDENCIES
require_once("PdoFactory.php");

abstract class AccessLayerObject {

  /**
   * Factory for generating db connections.
   */ 
  private static $databaseFactory;

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
