<?php

require_once(dirname(__FILE__)."/../access_layer/Import.php");
require_once("DbBuilder.php");
require_once("EnumTable.php");

final class SqlDbBuilder implements DbBuilder {

  /**
   * Sql file names.
   */
  const DROP_DB_FILE_NAME = "drop_db.sql";
  const CREATE_DB_FILE_NAME = "create_db.sql";
  
  /**
  * Fundamental data types.
  */ 
  private static $FUNDAMENTAL_DATA_TYPES = array(
      SqlRecord::ID_KEY => "SERIAL",
      SqlRecord::CREATED_KEY => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
      SqlRecord::LAST_UPDATED_TIME_KEY => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
  );

  /**
   * Mapping of DataTypeName types to corresponding sql data type strings. 
   */
  private static $SQL_DATA_TYPE_MAP = array(
      DataTypeName::INT => "INT",
      DataTypeName::UNSIGNED_INT => "INT UNSIGNED",
      DataTypeName::SERIAL => "SERIAL",
      DataTypeName::BOOL => "BIT",
      DataTypeName::STRING => "VARCHAR",
      DataTypeName::TIMESTAMP => "TIMESTAMP",
      DataTypeName::DATE => "DATE",
      DataTypeName::FOREIGN_KEY => "BIGINT UNSIGNED",
  );
  
  /**
   * createDatabaseQueryFiles()
   * - Create database query files for dropping and
   *    creating the warehouse database.
   * @param database : Database
   * @param path : string
   * @return void
   */
  public function createDatabaseQueryFiles($database, $path) {
    $this->genDropDatabaseQueryFile($database, $path);
    $this->genCreateDatabaseQueryFile($database, $path);
  }

  /**
   * createDropDatabaseQuery()
   * - Create sql file for dropping database.
   * @param database : Database
   * @param path : string
   * @return void
   */
  private function genDropDatabaseQueryFile($database, $path_to_dir) {
    // Create drop db query
    $drop_db_query = "DROP DATABASE {$database->getName()}";

    // Create drop db file
    $path = "{$path_to_dir}/" . self::DROP_DB_FILE_NAME;
    file_put_contents($path, $drop_db_query);
  }

  /**
   * createDatabaseQueryFile()
   * - Generate query files for creating warehouse database.
   * @param database : Database
   * @param path_to_dir : string
   * @return void
   */
  private function genCreateDatabaseQueryFile($database, $path_to_dir) {
    // Generate create db query
    $create_database_query = $this->createDatabaseQuery($database);

    // Create file 
    $path = "{$path_to_dir}/" . self::CREATE_DB_FILE_NAME;
    file_put_contents($path, $create_database_query);
// TODO put more error checking here!
  }

  /**
   * createDatabaseQuery()
   * - Generate query for creating database.
   * @param database : Database
   * @return string : database creation query string
   */
  public function createDatabaseQuery($database) {
    $create_db_query = "CREATE DATABASE {$database->getName()};\n\n";
    
    $create_tables_query = '';
    $create_foreign_key_queries = '';

    // Create table definitions and row insertions 
    // (w/o foreign keys constraints)
    foreach ($database->getTables() as $table) {
      // Table definitions
      $create_tables_query .= $this->genCreateTableQuery(
            $database->getName(),
            $table) . "\n\n";

      // Row insertions
      $create_tables_query .= $this->genInsertRowQueries(
        $database->getName(),
        $table) . "\n\n";
    }

    // Create foreign key constraint queries
    foreach ($database->getTables() as $table) {
      $create_foreign_key_queries .= $this->genCreateForeignKeyQuery(
          $database->getName(),
          $table) . "\n\n"; 
    }

    return
      $create_db_query .
      $create_tables_query .
      $create_foreign_key_queries;
  }

  /**
   * genCreateTableQuery()
   * - Generate query for creating table.
   * @param db_name : string
   * @param table : Table
   * @return string : create table query
   */
  private function genCreateTableQuery($db_name, $table) {
    // Create table definition header
    $create_table_query = $this->genCreateTableQueryHeader(
        $db_name,
        $table->getName()
    );
    
    // Add column definitions
    foreach ($table->getColumns() as $column) {
      // Fail due to invalid column name
      assert(!isset(Table::$RESERVED_COLUMN_NAMES[$column->getName()])); 

      $create_table_query .= "\t{$this->genCreateColumnQuery($column)},\n";
    }

    // Add unique key definitions
    foreach ($table->getUniqueColumnsSet() as $col_set) {
      $create_table_query .= "\t{$this->genUniqueKeyQuery($col_set)},\n";
    }
    
    return substr($create_table_query, 0, -2) . ");";
  }

  /**
   * genCreateTableQueryHeader()
   * - Generate query for creating header of table sql definition.
   * @param db_name : string
   * @param table_name : string
   * @return string : query for header of table
   */
  private function genCreateTableQueryHeader($db_name, $table_name) {
    return "CREATE TABLE {$this->genFullyQualifiedTableName($db_name, $table_name)}(\n"
      . "\t" . SqlRecord::ID_KEY . " " . self::$FUNDAMENTAL_DATA_TYPES[SqlRecord::ID_KEY]. ",\n"
      . "\tPRIMARY KEY(" . SqlRecord::ID_KEY . "),\n"
      . "\t" . SqlRecord::CREATED_KEY . " " . self::$FUNDAMENTAL_DATA_TYPES[SqlRecord::CREATED_KEY] . ",\n"
      . "\t" . SqlRecord::LAST_UPDATED_TIME_KEY . " " . self::$FUNDAMENTAL_DATA_TYPES[SqlRecord::LAST_UPDATED_TIME_KEY] . ",\n";
  }

  /**
   * genCreateColumnQuery()
   * - Generate query for creating column.
   * @param column : Column
   * @return string : query for creating column
   */
  private function genCreateColumnQuery($column) {
    $query = $column->getName() . " " . $this->genColumnDataTypeQueryComponent($column); 
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

  /**
   * translateColumnDataTypeNameForSql()
   * - Return sql string representing data type.
   * @param data_type_name : string
   * @return string : sql data type name
   */
  private function translateColumnDataTypeNameForSql($data_type_name) {
    // Fail due to invalid data-type-name
    assert(isset(self::$SQL_DATA_TYPE_MAP[$data_type_name]));

    return self::$SQL_DATA_TYPE_MAP[$data_type_name];
  }
  
  /**
   * genUniqueKeyQuery()
   * - Return query component for creating a composite unique key constraint.
   * @param col_name_set : set<string:column-name>
   */
  private function genUniqueKeyQuery($col_set) {
    $unique_key_str = "UNIQUE KEY(";
    foreach ($col_set as $col) {
      $unique_key_str .= "{$col->getName()}, ";
    }

    return substr($unique_key_str, 0, -2) . ')';
  }
  

  /**
   * genCreateForeignKeyQuery()
   * - Return query for adding foreign key to table.
   * @param database_name : string
   * @param primary_table_name : string
   * @param secondary_table_name : string
   * @return string : query
   */
  private function genCreateForeignKeyQuery(
    $database_name,
    $table
  ) {
    $fk_queries = '';
    foreach ($table->getColumns() as $name => $col) {
      if ($col->isForeignKey()) {
        $referenced_table_fqn = $this->genFullyQualifiedTableName($database_name, $table->getName());
        $fk_queries .= "ALTER TABLE {$referenced_table_fqn}\n\t"
          . "ADD FOREIGN KEY({$col->getName()}) REFERENCES {$referenced_table_fqn}(" . SqlRecord::ID_KEY . ");\n"; 
      } 
    } 

    return $fk_queries;
  }

  /**
   * genFullyQualifiedTableName()
   * - Generate fully qualified table name (includes database name prefix)
   * @param database_name : string
   * @param table_name : string
   * @return string : fully qualified table name
   */
  private function genFullyQualifiedTableName($database_name, $table_name) {
    return "{$database_name}.{$table_name}";
  }

  /**
   * genInsertRowQueries()
   * - Compose queries for inserting rows into specified table.
   * @param db_name : string
   * @param table : Table
   * @return string : insert queries
   */
  private function genInsertRowQueries($db_name, $table) {
    $insert_queries = '';

    // Gen table name    
    $fully_qualified_table_name = 
        $this->genFullyQualifiedTableName(
            $db_name,
            $table->getName()
        );

    // Compose insert queries 
    $table_col_map = $table->getColumns();

    foreach ($table->getRows() as $row) {
      $query_key_list = '';
      $query_elements = '';
      
      foreach ($row as $key => $value) {
        // Skip because we have nothing to insert
        if (!isset($value)) {
          continue; 
        }         

        // Fail due to invalid field-key
        assert(isset($table_col_map[$key]));

        // Accumulate field for insertion into row
        $table_col = $table_col_map[$key];
        $value_str = ($table_col->getDataType == DataType::string()) 
            ? "\"{$value}\""
            : "{$value}";
        $query_value_list .= "{$value_str}, ";
        
        $query_key_list .= "{$key}, ";
      }

      // Include surrounding parens
      $query_key_list = substr($query_key_list, 0, -2);
      $query_value_list = substr($query_value_list, 0, -2);

      $insert_queries .= "INSERT INTO {$fully_qualified_table_name} " 
          . "({$query_key_list}) VALUES ({$query_value_list});\n";
    }

    return $insert_queries;
  } 
}
