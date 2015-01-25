<?php

require_once("Database.php");
require_once("DbBuilder.php");
require_once("AccessLayerBuilder.php");

class Architect {

  private
    $dbBuilder,
    $accessLayerBuilder;

  /**
   * __construct()
   * - Ctor for Architect. Initialize builders.
   * @param db_builder : DbBuilder
   * @param access_layer_builder : AccessLayerBuilder
   */
  public function __construct($db_builder, $access_layer_builder) {
    $this->dbBuilder = $db_builder;
    $this->accessLayerBuilder = $access_layer_builder;
  }

  /**
   * create()
   * - Create php and sql files (create database, too)
   * @param database : Database
   * @param path : string
   * @return void
   */
  public function create($database, $path) {
    // Can't be a normal file
    assert(!is_file($path) || is_dir($path));
    
    $warehouse_path .= "{$path}/{$database->getName()}/";

    // Create directory
    if (!file_exists($warehouse_path)) {
      if (!mkdir($warehouse_path)) {
        die("Couldn't make dir: {$warehouse_path}");
      }
    } else {
      assert(is_dir($warehouse_path));
    }
  
    // Create warehouse files
    $this->dbBuilder->createDatabaseQueryFiles($database, $warehouse_path);
    $this->accessLayerBuilder->createAccessLayerFiles($database, $warehouse_path);
  }
}
