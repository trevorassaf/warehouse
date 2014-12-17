<?php
// -- DEPENDENCIES
require_once("MySqlDatabase.php");

/**
 * AccessLayerObject
 *  - class representing a generic ORM object.
 *  - in its current state, this class is NOT thread-safe because a single
 *    database connection is shared between all instances of this class, regardless
 *    of threading boundaries.
 */
abstract class AccessLayerObject {
  
  private static 
    $database,
    $databaseFactory;

// -- STATIC FUNCTIONS
	/* Function: Return database object..
	*
	*/
	protected static function getDatabase() {
		if (!isset(self::$database)) {
			self::$database = new MySqlDatabase();
		}
		return self::$database;
	}
// -- ABSTRACT METHODS
	/* Function: Saves all owned database objects to db.
	*	Should be called by each setter in subclasses.
	*/
	public abstract function save();

	/* Function: Removes all owned database objects from db.
	*
	*/
	public abstract function delete();
}

// Initialize database object
AccessLayerObject::$database = new MySqlDatabase();

?>
