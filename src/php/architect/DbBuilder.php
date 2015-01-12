<?php

interface DbBuilder {

  /**
   * createDatabaseQuery()
   * - Generate query files for warehouse's database.
   * @param database : Database
   * @param path_to_dir : string
   * @return void
   */
  public function createDatabaseQueryFiles($database, $path_to_dir);
}
