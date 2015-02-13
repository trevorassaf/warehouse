<?php

require_once(dirname(__FILE__)."/../access_layer/Import.php");

class TableBuilder {

  /**
   * Suffix for all default foreign keys.
   */
  const FOREIGN_KEY_SUFFIX = "Id";

  private
    $name,
    $columnMap,
    $uniqueColumnsSet,
    $rowList,
    $isReadOnly;

  /**
   * makeOneToOne()
   * - Add columns to primary and secondary tables that point to each other.
   * @param primary_table_builder: TableBuilder
   * @param secondary_table_builder: TableBuilder
   * @param primary_field_name: name of primary field (optional)
   * @param secondary_field_name: name of secondary field (optional)
   * @return void 
   */
  public function makeOneToOne(
    $primary_table_builder,
    $secondary_table_builder,
    $primary_field_name=null,
    $secondary_field_name=null
  ) {
    // Create default name for new primary-table column
    if ($primary_field_name == null) {
      $primary_field_name = self::genDefaultForeignKeyColumnName(
          $secondary_table_builder->getName());
    }  
    
    // Create default name for new secondary-table column
    if ($secondary_field_name == null) {
      $secondary_field_name = self::genDefaultForeignKeyColumnName(
          $primary_table_builder->getName());
    }  

    // Incorporate new foreign key columns into the tables
    $primary_fk_col = self::genForeignKeyColumn(
        $primary_field_name,
        $secondary_table_builder->getName()
      );

    $secondary_fk_col = self::genForeignKeyColumn(
        $secondary_field_name,
        $primary_table_builder->getName()
    );

    // Bind fk columns to table builders
    $primary_table_builder->bindColumn($primary_fk_col);
    $secondary_table_builder->bindColumn($secondary_fk_col);
  }

  /**
   * makeOneToMany()
   * - Add column to secondary table that points to primary table.
   * @param primary_table_builder: Table
   * @param secondary_table_builder_builder: Table
   * @param secondary_field_name: name of field (optional)
   * @return void 
   */
  public static function makeOneToMany(
    $primary_table_builder,
    $secondary_table_builder,
    $secondary_field_name=null
  ) {
    // Compose default column name, if necessary
    if ($secondary_field_name == null) {
      $secondary_field_name = self::genDefaultForeignKeyColumnName(
          $primary_table_builder->getName()
      );
    } 

    // Incorporate new foreign key column
    $secondary_fk_col = self::genForeignKeyColumn(
        $secondary_field_name,
        $primary_table_builder
    );

    $secondary_table_builder->bindColumn($secondary_fk_col);
  }

  /**
   * makeManyToMany()
   * - Assemble join table for these tables.
   * @param table_a_builder: TableBuilder
   * @param table_b_builder: TableBuilder
   * @param join_table_name: name of table (optional)
   * @return TableBuilder : builder for join table 
   */
  public static function makeManyToMany(
    $table_a_builder,
    $table_b_builder,
    $join_table_name=null
  ) {
    // Create default table name, if necessary
    if ($join_table_name == null) {
      $join_table_name = self::genDefaultJoinTableName(
          $table_a_builder->getName(),
          $table_b_builder->getName()
      );
    }
    
    // Assemble join table and add fk columns
    $join_table_builder = new TableBuilder();

    $join_table_builder->setName($join_table_name);

    // Create column names
    $table_a_fk_col_name = self::genDefaultForeignKeyColumnName(
        $table_a_builder->getName());
    $table_b_fk_col_name = self::genDefaultForeignKeyColumnName(
        $table_b_builder->getName());

    // Create columns
    $table_a_fk_col = self::genForeignKeyColumn(
        $table_a_fk_col_name,
        $table_b_builder->getName()
    );
    $table_b_fk_col = self::genForeignKeyColumn(
        $table_b_fk_col_name,
        $table_a_builder->getName()
    );

    // Bind columns
    $join_table_builder->bindColumn($table_a_fk_col);
    $join_table_builder->bindColumn($table_b_fk_col);

    return $join_table_builder;
  }

  /**
   * genForeignKeyColumn()
   * - Produce column for foreign key.
   * @param col_name : string
   * @param referenced_table_name : string 
   * @return Column : fk column
   */
  private static function genForeignKeyColumn($col_name, $referenced_table_name) {
    $builder = new ColumnBuilder();
    return $builder
      ->setName($col_name)
      ->setDataType(DataType::foreignKey())
      ->setReferencedTableName($referenced_table_name)
      ->build();
  }

  /**
   * genDefaultForeignKeyColumnName()
   * - Return column name for foreign key.
   * @param referenced_table_name : string
   * @return string : name of foreign key column
   */
  private static function genDefaultForeignKeyColumnName($referenced_table_name) {
    return $referenced_table_name . self::FOREIGN_KEY_SUFFIX;
  }
  
  /**
   * genDefaultJoinTableName()
   * - Derive join table name from individual table names.
   * @param database_name : string 
   * @param table_name_a : string 
   * @param table_name_b : string 
   * @return string : name of join table
   */
  private static function genDefaultJoinTableName($table_name_a, $table_name_b) {
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
   * __construct()
   * - Ctor for TableBulder. Initializes constructions fields
   *    to default values.
   */
  public function __construct() {
    $this->name = null;
    $this->columnMap = array();
    $this->uniqueColumnsSet = array();
    $this->rowList = array();
    $this->isReadOnly = false;
  }
  
  /**
   * setName()
   * - Bind table name.
   * @param name : string
   * @return this
   */
  public function setName($name) {
    $this->name = $name;
    return $this; 
  }

  /**
   * getName()
   * - Return name of table.
   * @return string : table name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * addColumn()
   * - Accumulate new column.
   * @param column : Column
   * @return this
   */
  public function bindColumn($column) {
    // Fail for binding column to invalid name
    assert(!isset(Table::$RESERVED_COLUMN_NAMES[$column->getName()]));

    $this->columnMap[$column->getName()] = $column;
    return $this;
  }
  
  /**
   * addUniqueColumnSet()
   * - Accumulate unique column set.
   * @param unique_col_set : Set(Column)
   * @return this
   */
  public function addUniqueColumnSet($unique_col_set) {
    $this->uniqueColumnsSet[] = $unique_col_set; 
    return $this;
  }

  /**
   * addUniqueColumn()
   * - Accumulate unique column.
   * @param unique_col_set : Column 
   * @return this
   */
  public function addUniqueColumn($unique_col) {
    $this->uniqueColumnsSet[] = array($unique_col); 
    return $this;
  }

  /**
   * addRow()
   * - Insert row into table.
   * @param row : Map<string:key, mixed:value>
   * @return this
   */
  public function addRow($row) {
    $this->rowList[] = $row; 
    return $this;
  }

  /**
   * setIsReadOnly()
   * - Specify if table is read only or not
   * @param is_read_only : bool
   * @return this
   */
  public function setIsReadOnly($is_read_only) {
    $this->isReadOnly = $is_read_only;
    return $this;
  }
  
  /**
   * addCompositeKey()
   * - Make composite key from provided column name set
   * @param column_set : Set<Column:column>
   * @return void
   */
  public function addCompositeKey($column_set) {
    $this->uniqueColumnsSet[] = $column_set;
  }

  /**
   * addUniqueKey()
   * - Make unique key from provided column name. 
   * @param column : Column:column 
   * @return void
   */
  public function addUniqueKey($column) {
    $this->uniqueColumnsSet[] = array($column);
  }

  /**
   * build()
   * - Assemble table from data bound to this builder.
   * @return Table
   */
  public function build() {
    // Fail due to unspecified table name
    assert(isset($this->name));

    return new Table(
      $this->name,
      $this->columnMap,
      $this->uniqueColumnsSet,
      $this->rowList,
      $this->isReadOnly
    );
  }
}

class Table {

  /**
   * Disallowed column names.
   */
  public static $RESERVED_COLUMN_NAMES = array(
    SqlRecord::ID_KEY,
    SqlRecord::CREATED_KEY,
    SqlRecord::LAST_UPDATED_TIME_KEY,  
  );

  private
    $name,
    $columnMap,
    $uniqueColumnsSet,
    $rowList,
    $isReadOnly;

  /**
   * __construct()
   * - Ctor for Table.
   * @param name : string
   * @param column_map : Map<string:name, Column>
   * @param unique_columns_set : Set<Set<Column>>
   * @param row_list : List<Map<string:column-name, mixed:value>>
   * @param is_read_only : bool
   */
  public function __construct(
    $name,
    $column_map,
    $unique_columns_set,
    $row_list,
    $is_read_only
  ) {
    $this->name = $name;
    $this->columnMap = $column_map;
    $this->uniqueColumnsSet = $unique_columns_set;
    $this->rowList = $row_list;
    $this->isReadOnly = $is_read_only;
  }

  /**
   * getName()
   * - Return name of table.
   * @return string : table name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * geColumnSet()
   * - Return column set. 
   * @return Set<Column>
   */
  public function getColumns() {
    return $this->columnMap;
  }

  /**
   * getNumColumns()
   * - Return size of column-set.
   * @return unsigned int : number of cols
   */
  public function getNumColumns() {
    return sizeof($this->columnSet);
  }

  /**
   * getUniqueColumnsSet()
   * - Return list of unique column sets.
   * @return Set<Set<Column>>
   */
  public function getUniqueColumnsSet() {
    return $this->uniqueColumnsSet;
  }

  /**
   * getRowList()
   * - Return list of rows to insert.
   * @return List<Map<string:col-name, mixed:value>>
   */
  public function getRows() {
    return $this->rowList;
  }

  /**
   * hasColumn()
   * - Return true iff the table contains a column by the
   *    specified name.
   * @param name : string
   * @return bool : true iff table contains specified column.
   */
  public function hasColumn($name) {
    return isset($this->columnMap[$name]) 
        || isset(self::$RESERVED_COLUMN_NAMES[$name]);
  }
}
