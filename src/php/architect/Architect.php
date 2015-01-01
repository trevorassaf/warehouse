<?php

require_once(dirname(__FILE__)."/../access_layer/Import.php");

class Architect {

  public function create($database, $path) {
    // Fail due to invalid parent dir
    assert(is_dir($path));

    $this->createSqlFiles($database, $path);
    $this->createPhpFiles($database, $path);
  }

  private function createSqlFiles($database, $path) {
    // Create sql files
    $create_db_query = $this->genCreateDbQuery($database->getName()); 
    $drop_db_query = $this->genDropDbQuery($database->getName());
    $create_tables_query = $this->genCreateTablesQuery($database);

    // Connect to db
    $config = $this->loadMySqlConfig();
    $mysql_db_factory = MySqlPdoFactory::get($config);
    $dbh = null;
    try {
      $dbh = $mysql_db_factory->getConnection();
      $dbh->beginTransaction();
echo "\nCreate database\n";
$result_stmt = $dbh->query("CREATE DATABASE " . $database->getName());
echo "\nFinishd creating database\n";
      var_dump($result_stmt->fetchAllRows());

      $dbh->commit();
    } catch (PDOException $e) {
      if (isset($dbh)) {
        $dbh->rollback();
      }
      die("ERROR: " . $e->getMessage());
    }
  }

  private function genCreateDbQuery($db_name) {
    return "CREATE DATABASE " . $db_name;
  }

  private function genDropDbQuery($db_name) {
    return "DROP DATABASE " . $db_name;
  }

  private function genCreateTableQuery($db_name, $table) {
    $create_table_query = $this->genCreateTableQueryHeader($db_name, $table->getName());
    foreach ($table->getColumnMap() as $col_name => $column) {
      var_dump($column);
    }
  }

  private function genCreateTablesQuery($database) {
    $tables_creation_str = "";
    foreach ($database->getTableMap() as $table_name => $table) {
      $tables_creation_str .= $this->genCreateTableQuery($database->getName(), $table);
    }
    return $tables_creation_str;
  }

  private function genCreateTableQueryHeader($db_name, $table_name) {
    return "CREATE TABLE " . $db_name . "." . $table_name . " (\n"
      . "\t" . SqlRecord::ID_KEY . " SERIAL,\n"
      . "\tPRIMARY KEY(" . SqlRecord::ID_KEY . ")\n"
      . "\t" . SqlRecord::CREATED_KEY . " TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n"
      . "\t" . SqlRecord::LAST_UPDATED_TIME . " TIMESTAMP DEFAULT CURRENT_TIMSTAMP ON UPDATE CURRENT_TIMSTAMP,\n";
  }

  private function loadMySqlConfig() {
    $builder = new DbhConfigBuilder();
    return $builder
        ->setUsername("trevor")
        ->setPassword("password")
        ->build();
  }

  private function createPhpFiles($database, $path) {
     
  }
}
