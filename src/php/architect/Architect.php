<?php

require_once(dirname(__FILE__)."/../access_layer/Import.php");
require_once("Database.php");

class Architect {

  const COLUMN_NAME_DELIMITER = '_';

  const DB_SUPER_CLASS_NAME = 'SqlRecord';
  const SQL_RECORD_GLOBAL_PATH = '';

  const CREATE_SQL_DB_FILE_NAME = 'create.sql';
  const DROP_SQL_DB_FILE_NAME = 'drop.sql';

  const IMPORT_PHP_FILE_NAME = 'import.php';
  const ACCESS_LAYER_FILE_NAME = 'access_layer.php';

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
    SqlRecord::LAST_UPDATED_TIME => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
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
    $path .= $database->getName() . '/';

    // Can't be a normal file
    assert(!is_file($path) || is_dir($path));

    // Create sql queries 
    $drop_db_query = $this->genDropDbQuery($database->getName());
    $create_tables_query = $this->genCreateTablesQuery($database);
    echo "\n\n" . $create_tables_query . "\n\n"; 
    $access_layer_contents = $this->genCreateAccessLayer($database);;

    // Create directory
    if (!file_exists($path)) {
      if (!mkdir($path)) {
        die("Couldn't make dir: {$path}");
      }
    } else {
      assert(is_dir($path));
    }

    // Create 'create-db' script
    $create_sql_db_path = $path . self::CREATE_SQL_DB_FILE_NAME;
    if (!file_put_contents($create_sql_db_path, $create_tables_query)) {
      die("Couldn't create {$create_sql_db_path}");
    }

    // Create 'drop-db' script
    $drop_sql_db_path = $path . self::DROP_SQL_DB_FILE_NAME;
    if (!file_put_contents($drop_sql_db_path, $drop_db_query)) {
      unlink($create_sql_db_path);
      die("Couldn't create {$drop_sql_db_path}");
    }

    // Create php file for access-layer 
    $access_layer_path = $path . self::ACCESS_LAYER_FILE_NAME; 
    if (!file_put_contents($access_layer_path, $access_layer_contents)) {
      unlink($create_sql_db_path);
      unink($drop_sql_db_path); 
      die("Couldn't create {$access_layer_path}");
    }
  }

  /**
   * genCreateDbQuery()
   * - Generate query to create the specified database.
   * @param db_name : string
   * @return string : create database query 
   */
  private function genCreateDbQuery($db_name) {
    return "CREATE DATABASE {$db_name};";
  }

  /**
   * genDropDbQuery()
   * - Generate query to drop the database.
   * @param db_name : string
   * @return string : drop database query 
   */
  private function genDropDbQuery($db_name) {
    return "DROP DATABASE {$db_name};";
  }

  /**
   * genCreateTablesQuery()
   * - Generate query for creating specified tables.
   * @param database : Database
   * @return string : table creation query
   */
  private function genCreateTablesQuery($database) {
    // Generate 'create-database' query
    $create_all_tables_query = $this->genCreateDbQuery($database->getName()) . "\n\n";
    $table_map = $database->getTableMap();

    // Create tables query w/o foreign key columns, join tables, and constraints.
    foreach ($table_map as $table_name => $table_with_mapping_set) {
      $table = $table_with_mapping_set->getTable();
      $create_table_query = $this->genCreateTableQueryHeader($database->getName(), $table_name);
      foreach ($table->getColumnWithInfoMap() as $column_name => $column_with_info) {
        // Fail due to invalid column name
        assert(!isset(self::$DISALLOWED_COLUMN_NAMES[$column_name]));
        // Accumulate table-creation query
        $create_table_query .= "\t" . $this->genCreateColumnQuery($column_with_info->getColumn()) . ",\n";
      }

      // Add unique key constraints
      foreach ($table->getUniqueColumnSetList() as $col_name_set) {
        $create_table_query .= "\t" . $this->genCompositeKeyQuery($col_name_set);
      }

      // Terminate create table query and accumulate into create-all-tables query
      $create_all_tables_query .= substr($create_table_query, 0, -2) . ");\n\n";
    }

    // Create foreign key columns, join tables, and constraints.
    $create_all_table_mappings_query = '';
    foreach ($table_map as $primary_table_name => $table_with_mapping_set) {
      $create_table_mapping_query = '';
      foreach ($table_with_mapping_set->getMappingSet() as $secondary_table_name => $mapping_type) {
        switch ($mapping_type) {
        // Add id column and fk constraint to both tables.
        case TableMappingType::ONE_TO_ONE:
          $create_table_mapping_query = 
              $this->genCreateForeignKeyQuery(
                  $database->getName(),
                  $primary_table_name,
                  $secondary_table_name
              ) . "\n\n" .
              $this->genCreateForeignKeyQuery(
                  $database->getName(),
                  $secondary_table_name,
                  $primary_table_name
              );
          break;

        // Add id column and fk constraint to second table only.  
        case TableMappingType::MANY_TO_ONE:
          $create_table_mapping_query =
              $this->genCreateForeignKeyQuery(
                  $database->getName(),
                  $primary_table_name,
                  $secondary_table_name
          );
          break;

        // Create join table w/id columns and fk constraints.  
        case TableMappingType::MANY_TO_MANY:
          $create_table_mapping_query =
              $this->genCreateJoinTableQuery(
                  $database->getName(),
                  $primary_table_name,
                  $secondary_table_name
              );
          break;

        // Ignore because asymmetric conjugate type is handled instead.
        case TableMappingType::ONE_TO_MANY:
          break;

        // Shouldn't happen...  
        default:
          die("Unexpected MappingType: " . $mapping_type);
          break;  
          
        }
      }
      
      $create_all_table_mappings_query .= $create_table_mapping_query . "\n\n";
    }

    // Trim trailing new lines from queries 
    return $create_all_tables_query . substr($create_all_table_mappings_query, 0, -2);
  }

  /**
   * genCompositeKeyQuery()
   * - Return query component for creating a composite unique key constraint.
   * @param col_name_set : set<string:column-name>
   */
  private function genCompositeKeyQuery($col_name_set) {
    return 'UNIQUE KEY(' . implode(', ', $col_name_set) . ')';
  }
  
  /**
   * genUniqueKeyQuery()
   * - Return query component for creating a unique key constraint.
   * @param col_name_set : string:column-name
   */
  private function genUniqueKeyQuery($col_name) {
    return "UNIQUE KEY({$col_name})";
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
      . SqlRecord::ID_KEY . "),\n\t{$this->genUniqueKeyQuery($fk_col_name)});";
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
    $join_table_name = $this->createJoinTableNameWithDatabasePrefixFromTableNames($database_name, $source_table_name, $referenced_table_name);        
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
   * createJoinTableNameWithDatabasePrefixFromTableNames()
   * - Derive join table name from individual table names. Include database name prefix.
   * @param database_name : string 
   * @param table_name_a : string 
   * @param table_name_b : string 
   * @return string : name of join table
   */
  public static function createJoinTableNameWithDatabasePrefixFromTableNames($database_name, $table_name_a, $table_name_b) {
    return $database_name . "." . self::createJoinTableNameFromTableNames($table_name_a, $table_name_b);
  }

  /**
   * createForeignKeyColumnName()
   * - Return column name for foreign key.
   * @param referenced_table_name : string
   * @return string : name of foreign key column
   */
  private function createForeignKeyColumnName($referenced_table_name) {
    return $referenced_table_name . "_" . SqlRecord::ID_KEY;
  }

  /**
   * genCreateColumnQuery()
   * - Create query for specific column. Ignores foreign key designations.
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

  private function translateColumnDataTypeNameForSql($data_type_name) {
    // Fail due to invalid data-type-name
    assert(isset(self::$SQL_DATA_TYPE_MAP[$data_type_name]));
    return self::$SQL_DATA_TYPE_MAP[$data_type_name];
  }

  private function genCreateTableQueryHeader($db_name, $table_name) {
    return "CREATE TABLE {$db_name}.{$table_name}(\n"
      . "\t" . SqlRecord::ID_KEY . " " . self::$FUNDAMENTAL_DATA_TYPES[SqlRecord::ID_KEY]. ",\n"
      . "\tPRIMARY KEY(" . SqlRecord::ID_KEY . "),\n"
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

  /**
   * genCreateAccessLayer()
   * - Create access layer contents.
   * @param database : Database
   */
  private function genCreateAccessLayer($database) {
    $access_layer_contents = "<?php\n\n";
    $access_layer_contents .= $this->genAccessLayerRequiresCode($database) . "\n\n";
    $access_layer_contents .= $this->genDatabaseAccessLayerCode($database) . "\n\n"; 
    $access_layer_contents .= $this->genTablesAccessLayerCode($database);
    return $access_layer_contents;
  }

  /**
   * genAccessLayerRequiresCode()
   * - Return requires code for access layer.
   * @param database : Database
   * @return string : requires code for database
   */
  private function genAccessLayerRequiresCode($database) {
    return "require_once(" . self::SQL_RECORD_GLOBAL_PATH . ")";
  }

  /**
   * genDatabaseAccessLayerCode()
   * - Return code for the database access layer class.
   * @param database : Database
   * @return string : code for database class. 
   */
  private function genDatabaseAccessLayerCode($database) {
    $super_class_name = self::DB_SUPER_CLASS_NAME;
    return 
      "class {$database->getName()} extends {$super_class_name} {
         protected static $dbName = {$database->getName()};      
       }";
  }

  /**
   * genTablesAccessLayerCode()
   * - Return code for the tables access layer classes.
   * @param database : Database
   * @return string : code for table classes. 
   */
  private function genTablesAccessLayerCode($database) {
    $tables_code = '';
    $table_map = $database->getTableMap();
    foreach ($table_map as $table_name => $table_with_mapping_set) {
      $header_code = "final class {$table_name} extends {$database->getName()} {\n";
      $private_var_code = "private";
      $getter_code = '';
      $setter_code = '';
      $edge_traversal_code = '';
      $init_instance_vars_code = '';
      $factory_code = '';

      // Create ivar-related code
      foreach ($table_with_mapping_set->getTable()->getColumnWithInfoMap() as $column_name => $column) {
        $ivar_name_for_column = $this->convertColumnNameToInstanceVarName($column_name);
        $private_var_code .= "\n\t\${$ivar_name_for_column},";
        $getter_code .= $this->genGetterCodeForColumn($ivar_name_for_column) . "\n\n";
        $setter_code .= $this->genSetterCodeForColumn($ivar_name_for_column) . "\n\n";
      }

      // Create edge-traversal code 
      foreach ($table_with_mapping_set->getMappingSet() as $secondary_table_name => $mapping_type) {
        switch ($mapping_type) {
          case TableMappingType::ONE_TO_ONE:
            $edge_traversal_code .= $this->genOneToOneEdgeCode($table_name, $secondary_table_name);
            break;
          case TableMappingType::MANY_TO_ONE:
            $edge_traversal_code .= $this->genManyToOneEdgeCode($table_name, $secondary_table_name);
            break;
          case TableMappingType::MANY_TO_MANY:
            $edge_traversal_code .= $this->genManyToManyEdgeCode($table_name, $secondary_table_name);
            break;
          default:
            die("Shouldn't happen. Erroneaous mapping type: {$mapping_type}");
        } 
        
        $edge_traversal_code .= "\n";
      }

      $tables_code .= "\n{$header_code}\n{$private_var_code}\n{$getter_code}\n{$setter_code}\n{$edge_traversal_code}";
    }

    echo $tables_code;
  }

  /**
   * genOneToOneEdgeCode()
   * - Return code for traversing a one-to-one edge.
   * @param src_table_name : string : name of source table
   * @param target_table_name : string : name of targe table
   * @return string : traversal code
   */
  private function genOneToOneEdgeCode($src_table_name, $dest_table_name) {
    $function_name = 'fetch' . $this->uccase($dest_table_name);
    return "public function {$function_name}() {
      return {$dest_table_name}::fetchUnique({$dest_table_name}::{$target_table_name}); 
    }"; 
  }

  /**
   * genManyToOneEdgeCode()
   * - Return code for traversing a many-to-one edge.
   * @param src_table_name : string : name of source table
   * @param target_table_name : string : name of targe table
   * @return string : traversal code
   */
  private function genManyToOneEdgeCode($src_table_name, $dest_table_name) {
    $function_name = 'fetch' . $this->uccase($dest_table_name);
    return "public function {$function_name}() {
      return {$dest_table_name}::fetch({$dest_table_name}::{$target_table_name}); 
    }"; 
  }

  /**
   * genManyToManyEdgeCode()
   * - Return code for traversing a many-to-many edge.
   * @param src_table_name : string : name of source table
   * @param target_table_name : string : name of targe table
   * @return string : traversal code
   */
  private function genManyToManyEdgeCode($src_table_name, $dest_table_name) {
    $function_name = 'fetch' . $this->uccase($dest_table_name) . "List";
    $join_table_name = self::createJoinTableNameFromTableNames($src_table_name, $dest_table_name);
    return "public function {$function_name}() {
      \$id_list = {$join_table_name}::fetch(array({$join_table_name}::{$dest_table_name} => \$this->getId()));
      return {$dest_table_name}::fetchByIds(\$id_list);
    }";
  }

  /**
   * uccase()
   * - Return uppercammel case verison of str_in. Split on self::COLUMN_NAME_DELIMITER.
   * @param str_in : string
   * @return string : uppercammel case string
   */
  private function uccase($str_in) {
    $tokens = explode(self::COLUMN_NAME_DELIMITER, $str_in);
    for ($i = 1; $i < count($tokens); ++$i) {
      $tokens[$i] = ucfirst($tokens[$i]); 
    }
    return ucfirst(implode('', $tokens));
  }

  private function genSetterCodeForColumn($ivar_name_for_column) {
    $upper_camel_case_ivar_name = ucfirst($ivar_name_for_column);
    return "public function set{$upper_camel_case_ivar_name}(\${$ivar_name_for_column}) { \$this->{$ivar_name_for_column} = \${$ivar_name_for_column}; }";
  }

  private function genGetterCodeForColumn($ivar_name_for_column) {
    $upper_camel_case_ivar_name = ucfirst($ivar_name_for_column);
    return "public function get{$upper_camel_case_ivar_name}() { return \$this->{$ivar_name_for_column}; }";
  }

  private function convertColumnNameToInstanceVarName($column_name) {
    $tokens = explode(self::COLUMN_NAME_DELIMITER, $column_name);
    for ($i = 1; $i < count($tokens); ++$i) {
      $tokens[$i] = ucfirst($tokens[$i]); 
    }
    return implode('', $tokens);
  }
}
