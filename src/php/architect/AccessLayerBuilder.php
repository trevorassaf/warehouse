<?php

interface AccessLayerBuilder {
  
  /**
   * createAccessLayerFile()
   * - Generate query for droping the database.
   * @param database : Database
   * @param path_to_dir : string
   * @return void
   */
  public function createAccessLayerFiles($database, $path_to_dir);
}
