<?php
// -- DEPENDENCIES
require_once("MySqlDatabase.php");

// -- CONSTANTS

/* -- CLASS DEFINITION
*	@abstraction: {
*		- protected save()	
*		- public delete()
*	}
*
*/
abstract class AccessLayerObject {
// -- CLASS VARIABLES
	public static $database;
// -- INSTANCE VARIABLES

// -- STATIC FUNCTIONS
	/* Function: Return database object..
	*
	*/
	public static function getDatabase() {
		if (!isset(self::$database)) {
			self::$database = new MySqlDatabase();
		}
		return self::$database;
	}
// -- ABSTRACT METHODS
	/* Function: Saves all owned database objects to db.
	*	Should be called by each setter in subclasses.
	*/
	protected abstract function save();

	/* Function: Removes all owned database objects from db.
	*
	*/
	public abstract function delete();
}

// Initialize database object
AccessLayerObject::$database = new MySqlDatabase();

?>
