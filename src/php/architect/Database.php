<?php

require_once(dirname(__FILE__)."/../util/Enum.php");

final class TableMappingType extends Enum {

  const ONE_TO_ONE = 0;
  const ONE_TO_MANY = 1;
  const MANY_TO_MANY = 2;

  protected static $SUPPORTED_TYPES = array(
    self::ONE_TO_ONE,
    self::ONE_TO_MANY,
    self::MANY_TO_MANY,
  );
}

final class TableMapping {

  private
    $primaryTable,
    $secondaryTable,
    $tableMappingType;

  /**
   * __construct()
   * - Ctor for TableMapping
   */
  public function __construct(
    $primary_table,
    $secondary_table,
    $table_mapping_type 
  ) {
    $this->primaryTable = $primary_table;
    $this->secondaryTable = $secondary_table;
    $this->tableMappingType = $table_mapping_type; 
  }

  /**
   * getPrimaryTable()
   * - Return primary table.
   * @return Table : primary table
   */
  public function getPrimaryTable() {
    return $this->primaryTable;
  }

  /**
   * getSecondaryTable()
   * - Return secondary table.
   * @return Table : secondary table
   */
  public function getSecondaryTable() {
    return $this->secondaryTable;
  }

  /**
   * getTableMappingType()
   * - Return mapping type.
   * @return TableMappingType
   */
  public function getTableMappingType() {
    return $this->tableMappingType;
  }
}

class Database {

  /**
   * Suffix for all default foreign keys.
   */
  const FOREIGN_KEY_SUFFIX = "id";

  /**
   * Default delimitter for fields.
   */
  const FIELD_DELIMITER = "_";

  private
    $name,
    $tableMap,
    $enumMap;

  /**
   * __construct()
   * - Ctor for Database
   * @param name : string
   */
  public function __construct($name) {
    $this->name = $name;
    $this->tableMap = array();
    $this->enumMap = array();
  }

  /**
   * getName()
   * - Return db name 
   * @return string : db name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * getTableMap()
   * - Return table map
   * @return Map<string:table-name, Table>
   */
  public function getTables() {
    return $this->tableMap;
  }

  /**
   * getEnumMap()
   * - Return enum map.
   * @return Map<string:enum-name, Enum>
   */
  public function getEnums() {
    return $this->enumMap; 
  }

  /**
   * addEnum()
   * - Add enum.
   * @param enum : Enum
   * @return void
   */
  public function addEnum($enum) {
    // Fail due to invalid enum
    assert(isset($enum));

    // Fail due to duplicate enum
    assert(!$this->hasTable($enum->getName()));

    $this->enumMap[$enum->getName()] = $enum;
  }

  /**
   * setTable()
   * - Add new table to db
   * @param table : Table
   * @return void
   */
  public function addTable($table) {
    // Fail due to null table
    assert(isset($table));

    // Fail due to duplicate table
    assert(!$this->hasTable($table->getName()));

    $this->tableMap[$table->getName()] = $table;
  }

  /**
   * setTables()
   * - Add set of new tables to db
   * @param table_list : array<Table>
   * @return void
   */
  public function setTables($table_list) {
    // Fail due to null table-list
    assert(isset($table_list));

    foreach ($table_list as $table) {
      $this->addTable($table);
    }
  } 

  /**
   * hasTable()
   * - Return true iff the db contains the specified table or enum.
   * @param table_name : string
   * @return bool : true iff db contains the table
   */
  public function hasTable($table_name) {
    // Search tables
    foreach ($this->tableMap as $name => $table) {
      if ($name == $table_name) {
        return true;
      }
    }

    // Search enums
    foreach ($this->enumMap as $name => $enum) {
      if ($name == $enum->getName()) {
        return true;
      }
    }

    return false;
  }

  /**
   * addOneToOneMapping()
   * - Add columns to primary and secondary tables that point to each other.
   * @param primary_table: Table
   * @param secondary_table: Table
   * @param primary_field_name: name of primary field (optional)
   * @param secondary_field_name: name of secondary field (optional)
   * @return void 
   */
  public function addOneToOneMapping(
    $primary_table,
    $secondary_table,
    $primary_field_name=null,
    $secondary_field_name=null
  ) {
    // Create default name for new primary-table column
    if ($primary_field_name == null) {
      $primary_field_name = $this->genDefaultForeignKeyColumnName($secondary_table->getName());
    }  
    
    // Create default name for new secondary-table column
    if ($secondary_field_name == null) {
      $secondary_field_name = $this->genDefaultForeignKeyColumnName($primary_table->getName());
    }  

    // Validate names of new columns
    assert(!$primary_table->hasColumn($primary_field_name));
    assert(!$secondary_table->hasColumn($secondary_field_name));

    // Incorporate new foreign key columns into the tables
    $primary_fk_col = $this->genForeignKeyColumn($primary_field_name, $secondary_table);
    $secondary_fk_col = $this->genForeignKeyColumn($secondary_field_name, $primary_table);

    $primary_table->addColumn($primary_fk_col);
    $secondary_table->addColumn($secondary_fk_col);
  }

  /**
   * addOneToManyMapping()
   * - Add column to secondary table that points to primary table.
   * @param primary_table: Table
   * @param secondary_table: Table
   * @param secondary_field_name: name of field (optional)
   * @return void 
   */
  public function addOneToManyMapping($primary_table, $secondary_table, $secondary_field_name=null) {
    // Compose default column name, if necessary
    if ($secondary_field_name == null) {
      $secondary_field_name = $this->genDefaultForeignKeyColumnName($primary_table->getName());
    } 

    // Validate new column name 
    assert(!$secondary_table->hasColumn($secondary_field_name));

    // Incorporate new foreign key column
    $secondary_fk_col = $this->genForeignKeyColumn($secondary_field_name, $primary_table);
    $secondary_table->addColumn($secondary_fk_col);
  }

  /**
   * addManyToManyMapping()
   * - Assemble join table for these tables.
   * @param table_a: Table
   * @param table_b: Table
   * @param join_table_name: name of table (optional)
   * @return Table : join table
   */
  public function addManyToManyMapping($table_a, $table_b, $join_table_name=null) {
    // Create default table name, if necessary
    if ($join_table_name == null) {
      $join_table_name = $this->genDefaultJoinTableName($table_a->getName(), $table_b->getName());
    }

    // Validate table name
    assert(!$this->hasTable($join_table_name));
    
    // Assemble join table and add fk columns
    $join_table = new Table($join_table_name);

    $table_a_fk_col_name = $this->genDefaultForeignKeyColumnName($table_a->getName());
    $table_b_fk_col_name = $this->genDefaultForeignKeyColumnName($table_b->getName());

    $table_a_fk_col = $this->genForeignKeyColumn($table_a_fk_col_name, $table_b);
    $table_b_fk_col = $this->genForeignKeyColumn($table_b_fk_col_name, $table_a);

    $join_table->addColumn($table_a_fk_col);
    $join_table->addColumn($table_b_fk_col);

    return $join_table;
  }

  /**
   * genForeignKeyColumn()
   * - Produce column for foreign key.
   * @param col_name : string
   * @param referenced_table : Table
   * @return Column : fk column
   */
  private function genForeignKeyColumn($col_name, $referenced_table) {
    $builder = new ColumnBuilder();
    return $builder
      ->setName($col_name)
      ->setDataType(DataType::foreignKey())
      ->setForeignKey($referenced_table)
      ->build();
  }

  /**
   * genDefaultJoinTableName()
   * - Derive join table name from individual table names.
   * @param database_name : string 
   * @param table_name_a : string 
   * @param table_name_b : string 
   * @return string : name of join table
   */
  private function genDefaultJoinTableName($table_name_a, $table_name_b) {
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
   * genDefaultForeignKeyColumnName()
   * - Return column name for foreign key.
   * @param referenced_table_name : string
   * @return string : name of foreign key column
   */
  private function genDefaultForeignKeyColumnName($referenced_table_name) {
    return $referenced_table_name . "_" . self::FOREIGN_KEY_SUFFIX;
  }
}
