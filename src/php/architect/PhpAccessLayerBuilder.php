<?php

require_once(dirname(__FILE__)."/AccessLayerBuilder.php");
require_once(dirname(__FILE__)."/PhpClassBuilder.php");
require_once(dirname(__FILE__)."/SqlDbBuilder.php");

final class PhpAccessLayerBuilder implements AccessLayerBuilder {

  /**
   * Super class name for access layer classes.
   */
  const DB_SUPER_CLASS_NAME = 'SqlRecord';

  /**
   * Relative path to access layer super class.
   */
  const RELATIVE_PATH_TO_SUPER_CLASS = "/../access_layer/SqlRecord.php";

  /**
   * Php access layer names.
   */
  const IMPORT_PHP_FILE_NAME = 'import.php';
  const ACCESS_LAYER_FILE_NAME = 'access_layer.php';

  /**
   * createAccessLayerFile()
   * - Assemble access-layer file and write to file system.
   * @override AccessLayerBuilder
   */
  public function createAccessLayerFiles($database, $path_to_dir) {
    // Create php access layer
    $file_contents =
        $this->genFileHeader() . "\n\n" .
        $this->genRequiresStatements() . "\n\n" .
        $this->genDatabaseClass($database->getName()) . "\n\n" .
        $this->genTablesClasses($database);

    // Create access layer file
    $path = "{$path_to_dir}/" . self::ACCESS_LAYER_FILE_NAME;
    file_put_contents($path, $file_contents);
  }

  /**
   * genFileHeader()
   * - Produce the file header for the access-layer.
   * @return string : file header
   */
  private function genFileHeader() {
    return "<?php";
  }

  /**
   * genRequiresStatements()
   * - Produce the requires statements for the access-layer file.
   * @return string : requires statements
   */
  private function genRequiresStatements() {
    $path = dirname(__FILE__) . self::RELATIVE_PATH_TO_SUPER_CLASS;
    return "require_once('{$path}');";
  }

  /**
   * genDatabaseClass()
   * - Compose database class.
   * @param db_name : string
   * @return string : database class definition
   */
  private function genDatabaseClass($db_name) {
    return "abstract class {$db_name} extends " . self::DB_SUPER_CLASS_NAME . " {}";
  }

  /**
   * genTablesClasses()
   * - Assemple class definitions for tables.
   * @param database : Database
   * @return string : class implementations for table classes
   */
  private function genTablesClasses($database) {
    $table_classes = "";

    $class_builder = new PhpClassBuilder();
    $class_builder->bindDatabaseName($database->getName());

    // Create table builders
    foreach ($database->getTables() as $name => $table) {
      $class_builder->bindTable($table);
      $table_classes .= $class_builder->build() . "\n\n";
    } 

    return $table_classes;
  }
}
