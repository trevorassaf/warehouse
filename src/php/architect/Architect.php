<?php

require_once(dirname(__FILE__)."/../access_layer/Import.php");
require_once("InterTableMapping.php");
require_once("Database.php");

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

  private static $FUNDAMENTAL_DATA_TYPES = array(
    SqlRecord::ID_KEY => "SERIAL",
    SqlRecord::CREATED_KEY => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    SqlRecord::LAST_UPDATED_TIME => "TIMESTAMP DEFAULT CURRENT_TIMSTAMP ON UPDATE CURRENT_TIMSTAMP",
  );

  private static $FOREIGN_KEY_DATA_TYPES = array(
    SqlRecord::ID_KEY => "BIGINT UNSIGNED NOT NULL",
  );

  /**
   * create()
   * - Create php and sql files (create database, too)
   * @param database : Database
   * @param path : string
   * @return void
   */
  public function create($database, $path) {
    // Fail due to invalid parent dir
    assert(is_dir($path));

    $this->createSqlFiles($database, $path);
    $this->createPhpFiles($database, $path);
  }

  /**
   * createSqlFiles()
   * - Create sql files and make database. If failure encountered,
   *    removes sql files and db updates.
   * @param database : Database
   * @param path : string
   * @return void
   */
  private function createSqlFiles($database, $path) {
    // Create sql files
    $create_db_query = $this->genCreateDbQuery($database->getName());
    echo "Create db query: " . $create_db_query . "\n";
    $drop_db_query = $this->genDropDbQuery($database->getName());
    echo "Drop db query: " . $drop_db_query . "\n";
    $create_tables_query = $this->genCreateTablesQuery($database);
    echo "Create tables query: " . $create_tables_query . "\n";
  }

  /**
   * genCreateDbQuery()
   * - Generate query to create the specified database.
   * @param db_name : string
   * @return string : create database query 
   */
  private function genCreateDbQuery($db_name) {
    return "CREATE DATABASE " . $db_name;
  }

  /**
   * genDropDbQuery()
   * - Generate query to drop the database.
   * @param db_name : string
   * @return string : drop database query 
   */
  private function genDropDbQuery($db_name) {
    return "DROP DATABASE " . $db_name;
  }

  /**
   * genCreateTablesQuery()
   * - Generate query for creating specified tables.
   * @param database : Database
   * @return string : table creation query
   */
  private function genCreateTablesQuery($database) {
    $create_all_tables_query = "";
    $table_map = $database->getTableMap();

    // Create tables query w/o foreign key columns, join tables, and constraints.
    foreach ($table_map as $table_name => $table) {
      $create_table_query = $this->genCreateTableQueryHeader($database->getName(), $table_name);
      foreach ($table->getColumnMap() as $column_name => $column) {
        // Fail due to invalid column name
        assert(!isset(self::$DISALLOWED_COLUMN_NAMES[$column_name]));

        // Accumulate table-creation query
        $create_table_query .= "\t" . $this->genCreateColumnQuery($column) . ",\n";
      }

      // Terminate create table query and accumulate into create-all-tables query
      $create_all_tables_query .= substr($create_table_query, 0, -2) . ");\n\n";
    }

    // Create foreign key columns, join tables, and constraints.
    $create_all_foreign_keys_query = '';
    foreach ($database->getInterTableMappings() as $table_mapping) {
      $create_mapping_query = '';
      switch ($table_mapping->getMappingType()) {
        // Add id column and fk constraint to both tables.
        case MappingType::ONE_TO_ONE:
          $create_mapping_query .= 
            $this->genCreateForeignKeyQuery(
                $database->getName(),
                $table_mapping->getFirstTable()->getName(),
                $table_mapping->getSecondTable()->getName()
            ) . "\n\n" .
            $this->genCreateForeignKeyQuery(
                $database->getName(),
                $table_mapping->getSecondTable()->getName(),
                $table_mapping->getFirstTable()->getName()
            );
          break;

        // Add id column and fk constraint to second table only.  
        case MappingType::ONE_TO_MANY:
          $create_mapping_query .= $this->genCreateForeignKeyQuery(
              $database->getName(),
              $table_mapping->getSecondTable()->getName(),
              $table_mapping->getFirstTable()->getName()
          );
          break;

        // Create join table w/id columns and fk constraints.  
        case MappingType::MANY_TO_MANY:
          $create_mapping_query .= $this->genCreateJoinTableQuery(
              $database->getName(),
              $table_mapping->getSecondTable()->getName(),
              $table_mapping->getFirstTable()->getName()
          );
          break;

        // Shouldn't happen...  
        default:
          die("Unexpected MappingType: " . $table_mapping->getMappingType());
          break;  
      }
      
      $create_all_foreign_keys_query .= $create_mapping_query . "\n\n";
    }

    // Trim trailing new lines from queries 
    return $create_all_tables_query . substr($create_all_foreign_keys_query, 0, -2);
  }

  /**
   * genCreateForeignKeyQuery()
   * - Return query creating foreign key column and constraint.
   * @param database_name : string
   * @param source_table_name : string
   * @param referenced_table_name : string
   * @return string : query
   */
  private function genCreateForeignKeyQuery($database_name, $source_table_name, $referenced_table_name) {
    $fk_col_name = $this->createForeignKeyColumnName($referenced_table_name);
    return "ALTER TABLE {$database_name}.{$source_table_name}\n\tADD COLUMN {$fk_col_name} "
      . self::$FOREIGN_KEY_DATA_TYPES[SqlRecord::ID_KEY] . ",\n\t"
      . "ADD FOREIGN KEY({$fk_col_name}) REFERENCES {$database_name}.{$referenced_table_name}("
      . SqlRecord::ID_KEY . "));";
  }

  /**
   * genCreateJoinTableQuery()
   * - Return query creating join table.
   * @param database_name : string
   * @param source_table_name : string
   * @param referenced_table_name : string
   * @return string : query creating join table
   */
  private function genCreateJoinTableQuery($database_name, $source_table_name, $referenced_table_name) {
    $join_table_name = $this->createJoinTableNameFromTableNames($source_table_name, $referenced_table_name);        
    $source_column_name = $this->createForeignKeyColumnName($source_table_name);
    $referenced_column_name = $this->createForeignKeyColumnName($referenced_table_name);
    $fk_data_type = self::$FOREIGN_KEY_DATA_TYPES[SqlRecord::ID_KEY];
    $id_column_name = SqlRecord::ID_KEY;
    return "CREATE TABLE {$database_name}.{$join_table_name}(
        {$source_column_name} {$fk_data_type},
        {$referenced_column_name} {$fk_data_type},
        FOREIGN KEY({$source_column_name}) REFERENCES {$database_name}.{$source_table_name}({$id_column_name}),
        FOREIGN KEY({$referenced_column_name}) REFERENCES {$database_name}.{$referenced_table_name}({$id_column_name}));\n\n";
  }

  /**
   * createJoinTableNameFromTableNames()
   * - Derive join table name from individual table names.
   * @param database_name : string 
   * @param table_name_a : string 
   * @param table_name_b : string 
   * @return string : name of join table
   */
  public static function createJoinTableNameFromTableNames($database_name, $table_name_a, $table_name_b) {
    $lower_case_table_name_a = strtolower($table_name_a);
    $lower_case_table_name_b = strtolower($table_name_b);

   // Order tables lexicographically 
    $low_lex = '';
    $high_lex = '';
    if ($lower_case_table_name_a < $lower_case_table_name_b) {
      $low_lex = $lower_case_table_name_a;
      $high_lex = $lower_case_table_name_b;  
    } else {
      $low_lex = $lower_case_table_name_b;
      $high_lex = $lower_case_table_name_a;  
    }
    
    return "{$database_name}.{$low_lex}_{$high_lex}_join_table";
  }

  /**
   * createForeignKeyColumnName()
   * - Return column name for foreign key.
   * @param referenced_table_name : string
   * @return string : name of foreign key column
   */
  private function createForeignKeyColumnName($referenced_table_name) {
    return strtolower($referenced_table_name) . "_" . SqlRecord::ID_KEY;
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
    return "CREATE TABLE {$db_name}.{$table_name}(\n"
      . "\t" . SqlRecord::ID_KEY . " " . self::$FUNDAMENTAL_DATA_TYPES[SqlRecord::ID_KEY]. ",\n"
      . "\tPRIMARY KEY(" . SqlRecord::ID_KEY . ")\n"
      . "\t" . SqlRecord::CREATED_KEY . " " . self::$FUNDAMENTAL_DATA_TYPES[SqlRecord::CREATED_KEY] . ",\n"
      . "\t" . SqlRecord::LAST_UPDATED_TIME . " " . self::$FUNDAMENTAL_DATA_TYPES[SqlRecord::LAST_UPDATED_TIME] . ",\n";
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
