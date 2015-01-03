<?php

require_once(dirname(__FILE__)."/../access_layer/Import.php");

class Architect {

  private static $SQL_DATA_TYPE_MAP = array(
    DataTypeName::INT => "INT",
    DataTypeName::UNSIGNED_INT => "UNSIGNED INT",
    DataTypeName::SERIAL => "SERIAL",
    DataTypeName::BOOL => "BIT",
    DataTypeName::STRING => "VARCHAR",
    DataTypeName::TIMESTAMP => "TIMESTAMP",
  );

  private static $DISALLOWED_COLUMN_NAMES = array(
    SqlRecord::ID_KEY,
    SqlRecord::CREATED_KEY,
    SqlRecord::LAST_UPDATED_TIME,
  );

  public function create($database, $path) {
    // Fail due to invalid parent dir
    assert(is_dir($path));

    $this->createSqlFiles($database, $path);
    $this->createPhpFiles($database, $path);
  }

  private function createSqlFiles($database, $path) {
    // Create sql files
    $create_db_query = $this->genCreateDbQuery($database->getName());
    echo "Create db query: " . $create_db_query . "\n";
    $drop_db_query = $this->genDropDbQuery($database->getName());
    echo "Drop db query: " . $drop_db_query . "\n";
    $create_tables_query = $this->genCreateTablesQuery($database);
    echo "Create tables query: " . $create_tables_query . "\n";
  }

  private function genCreateDbQuery($db_name) {
    return "CREATE DATABASE " . $db_name;
  }

  private function genDropDbQuery($db_name) {
    return "DROP DATABASE " . $db_name;
  }

  private function genCreateTablesQuery($database) {
    $create_all_tables_query = "";
    $create_all_foreign_keys_query = "";
    $table_map = $database->getTableMap();
    foreach ($table_map as $table_name => $table) {
      $create_table_query = $this->genCreateTableQueryHeader($database->getName(), $table_name);
      $create_table_foreign_keys_query = null;
      foreach ($table->getColumnMap() as $column_name => $column) {
        // Fail due to invalid column name
        assert(!isset(self::$DISALLOWED_COLUMN_NAMES[$column_name]));

        // Accumulate table-creation query
        $create_table_query .= "\t" . $this->genCreateColumnQuery($column) . ",\n";

        // Accumulate foreign key column
        if ($column->isForeignKey()) {
          // Create foreign key header for table, if nonextant
          if (!isset($create_table_foreign_keys_query)) {
            $create_table_foreign_keys_query = $this->genCreateForeignKeyHeaderQuery(
                $database->getName(),
                $table_name
            ) . "\n";
          }

          // Accumulate foreign key column queries
          $create_table_foreign_keys_query .=
            "\t" . $this->genCreateForeignKeyQuery(
                $database->getName(),
                $table_name,
                $column->getName(),
                $column->getForeignKeyTable()->getName()) . ",\n";
        }
      }

      // Terminate create table query and accumulate into create-all-tables query
      $create_all_tables_query .= substr($create_table_query, 0, -2) . ");\n\n";

      // Terminate create foreign key query, if foreign keys exist
      if (isset($create_table_foreign_keys_query)) {
        $create_all_foreign_keys_query .= 
            substr($create_table_foreign_keys_query, 0, -2) . ";\n\n";
      }
    }

    // Trim trailing new lines from queries 
    return empty($create_all_foreign_keys_query)
        ? substr($create_all_tables_query, 0, -2)
        : $create_all_tables_query . substr($create_all_foreign_keys_query, 0, -2);
  }

  /**
   * genCreateForeignKeyTableQuery()
   * - Create sql statement header for foreign key creation.
   * @param db_name : string
   * @param table_name : string
   * @return string : foreign key creation header 
   */
  private function genCreateForeignKeyHeaderQuery($db_name, $table_name) {
    return "ALTER TABLE {$db_name}.{$table_name} "; 
  }

  /**
   * genCreateForeignKeyQuery()
   * - Return create foreign key constraint.
   * @param db_name : string
   * @param table_name : string
   * @param column_name : string
   * @param foreign_table_name : string
   * @return string : foreign key constraint query
   */
  private function genCreateForeignKeyQuery(
    $db_name,
    $table_name,
    $column_name,
    $foregin_table_name
  ) {
    $foreign_key_constraint_name = strtolower($table_name . "_" . $column_name . "_fk");
    return "ADD CONSTRAINT {$foreign_key_constraint_name} FOREIGN KEY({$column_name}) REFERENCES {$db_name}.{$foregin_table_name}("
        . SqlRecord::ID_KEY . ")";
  }

  /**
   * genCreateColumnQuery()
   * - Create query for specific column. Ignores foreign key designations.
   * @param column : Column
   * @return string : query for creating column
   */
  private function genCreateColumnQuery($column) {
    $query = $column->getName() . " " . $this->genColumnDataTypeQueryComponent($column); 
    if ($column->isUnique()) {
      $query .= " UNIQUE";
    }
    if (!$column->getAllowsNull()) {
      $query .= " NOT NULL";  
    }
    return $query;
  }

  /**
   * genColumnDataTypeQueryComponent()
   * - Create query for establishing data type of column.
   * @param column : Column
   * @return string : query string component
   */
  private function genColumnDataTypeQueryComponent($column) {
    $data_type = $column->getDataType();
    $query = $this->translateColumnDataTypeNameForSql($data_type->getName());
    if ($data_type->allowsFirstLength() && $column->hasFirstLength()) {
      $query .= "(" . $column->getFirstLength();
    }
    if ($data_type->allowsSecondLength() && $column->hasSecondLength()) {
      $query .= ", " . $column->getSecondLength();
    }
    if ($column->hasFirstLength() || $column->hasSecondLength()) {
      $query .= ')';
    }
    return $query;
  }

  private function translateColumnDataTypeNameForSql($data_type_name) {
    // Fail due to invalid data-type-name
    assert(isset(self::$SQL_DATA_TYPE_MAP[$data_type_name]));
    return self::$SQL_DATA_TYPE_MAP[$data_type_name];
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
