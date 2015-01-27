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
   * Column name delimiter.
   */
  const COLUMN_NAME_DELIMITER = '_';

  /**
   * Disallowed column names.
   */
  private static $DISALLOWED_COLUMN_NAMES = array(
      SqlRecord::ID_KEY,
        SqlRecord::CREATED_KEY,
      SqlRecord::LAST_UPDATED_TIME_KEY,
  );

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
      DataTypeName::UNSIGNED_INT => "UNSIGNED INT",
      DataTypeName::SERIAL => "SERIAL",
      DataTypeName::BOOL => "BIT",
      DataTypeName::STRING => "VARCHAR",
      DataTypeName::TIMESTAMP => "TIMESTAMP",
  );

  /**
   * Data type for foreign key columns.
   */
  private static $FOREIGN_KEY_DATA_TYPES = array(
      SqlRecord::ID_KEY => "BIGINT UNSIGNED NOT NULL",
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
    // TODO more error checking here
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
    foreach ($database->getTables() as $table) {
      // Fail due to previously set table
      $create_tables_query .= $this->genCreateTableQuery($database->getName(), $table) . "\n\n";
    }

    $create_foreign_key_queries = '';
    foreach ($database->getTableMappings() as $fk_mapping) {
      $create_foreign_key_queries .= $this->genCreateForeignKeyQuery($database->getName(), $fk_mapping) . "\n\n"; 
    }

    $create_enums_query = '';
    foreach ($database->getEnums() as $enum) {
      $create_enums_query .= $this->genEnumQuery($database->getName(), $enum) . "\n\n";
    }

    return
      $create_db_query .
      $create_tables_query .
      $create_foreign_key_queries .
      $create_enums_query;
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
    $create_table_query = $this->genCreateTableQueryHeader($db_name, $table->getName());
    
    // Add column definitions
    foreach ($table->getColumns() as $column) {
      // Fail due to invalid column name
      assert(!isset(self::$DISALLOWED_COLUMN_NAMES[$column->getName()])); 
      $create_table_query .= "\t{$this->genCreateColumnQuery($column)},\n";
    }

    // Add unique key definitions
    foreach ($table->getUniqueColumnSetList() as $col_set) {
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
   * genJoinTableName()
   * - Derive join table name from individual table names.
   * @param database_name : string 
   * @param table_name_a : string 
   * @param table_name_b : string 
   * @return string : name of join table
   */
  public static function genJoinTableName($table_name_a, $table_name_b) {
   // Order tables lexicographically 
    $low_lex = '';
    $high_lex = '';
    if ($table_name_a < $table_name_b) {
      $low_lex = $table_name_a;
      $high_lex = $table_name_b;  
    } else {
      $low_lex = $table_name_b;
      $high_lex = $table_name_a;  
    }
    
    return "{$low_lex}_{$high_lex}_join_table";
  }

  /**
   * createForeignKeyColumnName()
   * - Return column name for foreign key.
   * @param referenced_table_name : string
   * @return string : name of foreign key column
   */
  public static function genForeignKeyColumnName($referenced_table_name) {
    return $referenced_table_name . "_" . SqlRecord::ID_KEY;
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
   * - Return query creating foreign key column and constraint.
   * @param database_name : string
   * @param source_table_name : string
   * @param referenced_table_name : string
   * @return string : query
   */
  private function genCreateForeignKeyQuery($database_name, $table_mapping) {
    $table_mapping_type = $table_mapping->getTableMappingType();

    // Create query
    $query = '';
    switch ($table_mapping_type) {
      case TableMappingType::ONE_TO_ONE:
        return $this->genOneToOneQuery($database_name, $table_mapping);
      case TableMappingType::ONE_TO_MANY:
        return $this->genOneToManyQuery($database_name, $table_mapping);
      case TableMappingType::MANY_TO_MANY:
        return $this->genManyToManyQuery($database_name, $table_mapping);
      default:
        die("Shouldn't happen");
    }
  }

  /**
   * genAlterTableWithForeignKey()
   * - Return query for adding foreign key to table.
   * @param database_name : string
   * @param primary_table_name : string
   * @param secondary_table_name : string
   * @return string : query
   */
  private function genAlterTableWithForeignKey(
    $database_name,
    $primary_table_name,
    $secondary_table_name
  ) {
    // Look up sql data type
    $fk_col_name = self::genForeignKeyColumnName($secondary_table_name);
    $sql_data_type_string = self::$FOREIGN_KEY_DATA_TYPES[SqlRecord::ID_KEY];
    
    // Generate fully qualified name
    $fully_qualified_primary_table_name =
        $this->genFullyQualifiedTableName($database_name, $primary_table_name);
    $fully_qualified_secondary_table_name =
      $this->genFullyQualifiedTableName($database_name, $secondary_table_name);

    return "ALTER TABLE {$fully_qualified_primary_table_name}\n\t"
        . "ADD COLUMN {$fk_col_name} {$sql_data_type_string},\n\t"
        . "ADD FOREIGN KEY({$fk_col_name}) REFERENCES {$fully_qualified_secondary_table_name}("
        . SqlRecord::ID_KEY . ");";
  }

  /**
   * genOneToOneQuery()
   * - Create one-to-one query.
   * @param database_name : string
   * @param table_mapping : TableMapping
   * @return string : one-to-one query
   */
  private function genOneToOneQuery($database_name, $table_mapping) {
    return 
      $this->genAlterTableWithForeignKey(
          $database_name,
          $table_mapping->getPrimaryTable()->getName(),
          $table_mapping->getSecondaryTable()->getName()
      ) . "\n\n" .
      $this->genAlterTableWithForeignKey(
          $database_name,
          $table_mapping->getSecondaryTable()->getName(),
          $table_mapping->getPrimaryTable()->getName()
      );
  }

  /**
   * genOneToManyQuery()
   * - Create one-to-many query.
   * @param database_name : string
   * @param table_mapping : TableMapping
   * @return string : one-to-one query
   */
  private function genOneToManyQuery($database_name, $table_mapping) {
    return $this->genAlterTableWithForeignKey(
        $database_name,
        $table_mapping->getSecondaryTable()->getName(),
        $table_mapping->getPrimaryTable()->getName()
    );
  }

  /**
   * genManyToManyQuery()
   * - Return query for many-to-many mapping.
   * @param database_name : string
   * @param table_mapping : TableMapping
   * @return string : query string
   */
  private function genManyToManyQuery($database_name, $table_mapping) {
    $primary_table_name = $table_mapping->getPrimaryTable()->getName();
    $secondary_table_name = $table_mapping->getSecondaryTable()->getName();

    // Create join table name
    $join_table_name = self::genJoinTableName(
        $table_mapping->getPrimaryTable()->getName(),
        $table_mapping->getSecondaryTable()->getName()
    );

    // Generate fully qualified name
    $fully_qualified_primary_table_name =
        $this->genFullyQualifiedTableName($database_name, $primary_table_name);
    $fully_qualified_secondary_table_name =
        $this->genFullyQualifiedTableName($database_name, $secondary_table_name);
    $fully_qualified_join_table_name =
        $this->genFullyQualifiedTableName($database_name, $join_table_name);
    
    $fk_data_type = self::$FOREIGN_KEY_DATA_TYPES[SqlRecord::ID_KEY];
    $id_column_name = SqlRecord::ID_KEY;
    $source_column_name = self::genForeignKeyColumnName($primary_table_name);
    $referenced_column_name = self::genForeignKeyColumnName($secondary_table_name);

    return "CREATE TABLE {$fully_qualified_join_table_name}(
        {$source_column_name} {$fk_data_type},
        {$referenced_column_name} {$fk_data_type},
        FOREIGN KEY({$source_column_name}) REFERENCES {$fully_qualified_primary_table_name}({$id_column_name}),
        FOREIGN KEY({$referenced_column_name}) REFERENCES {$fully_qualified_secondary_table_name}({$id_column_name}));";
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
   * genEnumQuery()
   * - Compose query for creating an enum-table.
   * @param database_name : string
   * @param enum_table : EnumTable
   * @return string : query
   */
  private function genEnumQuery($database_name, $enum_table) { 
    $enum_table_query = $this->genCreateEnumTableQuery(
        $database_name,
        $enum_table->getName(),
        $enum_table->getElementMaxLength()
    );
    $insert_value_queries = $this->genEnumInsertValueQueries($database_name, $enum_table);

    return "{$enum_table_query}\n\n{$insert_value_queries}"; 
  }

  /**
   * genCreateEnumTableQuery()
   * - Generate query for creating an enum-table.
   * @param database_name : string
   * @param enum_table_name : string
   * @param max_value_length : unsigned int
   * @return string : query for creating enum table
   */
  private function genCreateEnumTableQuery($database_name, $enum_table_name, $max_value_length) {
    $table_header = $this->genCreateTableQueryHeader($database_name, $enum_table_name);
    $enum_field_name = EnumTable::FIELD_NAME;
    $enum_col_definition = "\t{$enum_field_name} VARCHAR({$max_value_length}) NOT NULL, \n\tUNIQUE KEY(value));";
    return $table_header . $enum_col_definition;
  }

  private function genEnumInsertValueQueries($db_name, $enum_table) {
    $insert_queries = '';
    $enum_field_name = EnumTable::FIELD_NAME;
    $fully_qualified_table_name = $this->genFullyQualifiedTableName($db_name, $enum_table->getName());
    foreach ($enum_table->getElementSet() as $element) {
      $insert_queries .= "INSERT INTO {$fully_qualified_table_name} ({$enum_field_name}) VALUES (\"{$element}\");\n";
    }
    return $insert_queries;
  } 
}
