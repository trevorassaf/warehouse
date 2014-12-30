<?php

class ColumnBuilder {

  private
    $name,
    $dataType,
    $firstLength,
    $secondLength,
    $allowsNull,
    $isForeignKey,
    $foreignKeyTable,
    $isReadOnly;

  /**
   * __construct()
   * - Ctor for ColumnBuilder
   */
  public function __construct() {
    $this->name = null;
    $this->dataType = null;
    $this->firstLength = null;
    $this->secondLength = null;
    $this->allowsNull = false;
    $this->isForeignKey = false;
    $this->foreignKeyTable = null;
    $this->isReadOnly = false;
  }

  /**
   * setName()
   * - Set column name
   * @param name : string
   * @return this
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * setDataType()
   * - Set data type 
   * @param data-type : DataType 
   * @return this
   */
  public function setDataType($data_type) {
    $this->dataType = $data_type;
    return $this;
  }

  /**
   * setFirstLength()
   * - Set first length 
   * @param first length : unsigned int 
   * @return this
   */
  public function setFirstLength($first_length) {
    $this->firstLength = $first_length;
    return $this;
  }

  /**
   * setSecondLength()
   * - Set second length 
   * @param second_length : unsigned int 
   * @return this
   */
  public function setSecondLength($second_length) {
    $this->secondLength = $second_length;
    return $this;
  }

  /**
   * setAllowsNull()
   * - Set allows null 
   * @param allows_null : bool 
   * @return this
   */
  public function setAllowsNull($allows_null) {
    $this->allowsNull = $allows_null;
    return $this;
  }

  /**
   * setIsForiegnKey()
   * - Indicate that the column is a foreign key  
   * @param is_foreign_key : bool 
   * @return this
   */
  public function setForeignKey($is_foreign_key) {
    $this->isForeignKey = $is_foreign_key;
    return $this;
  }

  /**
   * setForeignKeyTableName()
   * - Set foreign key table name  
   * @param foreign_key_table : string 
   * @return this
   */
  public function setForeignKeyTableName($table_name) {
    $this-> = $table_name;
    return $this;
  }

  /**
   * setIsReadOnly()
   * - Indicate if column is read only 
   * @param is_read_only : string 
   * @return this
   */
  public function setIsReadOnly($is_read_only) {
    $this->foreignKeyTable = $table_name;
    return $this;
  }

  /**
   * build()
   * - Return Column instance
   */
  public function build() {
    // Fail due to unset name
    assert(isset($this->name));

    // Fail due to unset datatype
    assert(isset($this->dataType));

    // Fail due to invalid datatype
    assert($this->dataType->requiresFirstLength() ^ isset($this->firstLength));
    assert($this->dataType->requiresSecondLength() ^ isset($this->secondLength));

    return new Column(
      $this->name,
      $this->dataType,
      $this->firstLength,
      $this->secondLength,
      $this->allowsNull,
      $this->isForeignKey,
      $this->foreignKeyTable,
      $this->isReadOnly
    );
  }
}

class Column {

  private
    $name,
    $dataType,
    $firstLength,
    $secondLength,
    $allowsNull,
    $isForeignKey,
    $foreignKeyTable,
    $isReadOnly;

  public function __construct(
    $name,
    $data_type,
    $first_length,
    $second_length,
    $allows_null,
    $is_foreign_key,
    $foreign_key_table,
    $is_read_only
  ) {
    $this->name = $name;
    $this->dataType = $data_type;
    $this->firstLength = $first_length;
    $this->secondLength = $second_length;
    $this->allowsNull = $allows_null;
    $this->isForeignKey = $is_foreign_key;
    $this->foreignKeyTable = $foreign_key_table;
    $this->isReadOnly = $is_foreign_key;
  }

  /**
   * getName()
   * - Return name of column.
   * @return string : column name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * getDataType()
   * - Return data-type of column.
   * @return DataType : data-type of column 
   */
  public function getDataType() {
    return $this->dataType;
  }

  /**
   * getFirstLength()
   * - Return first-length of column.
   * @return unsigned int : field length
   */
  public function getFirstLength() {
    return $this->firstLength;
  }

  /**
   * getSecondLength()
   * - Return second-length of column.
   * @return unsigned int : second length, often precision 
   */
  public function getSecondLength() {
    return $this->secondLength;
  }

  /**
   * getAllowsNull()
   * - Indicate if field can be null.
   * @return bool : true iff field permits null 
   */
  public function getAllowsNull() {
    return $this->allowsNull;
  }

  /**
   * isForeignKey()
   * - Indicate if field is foreign key.
   * @return bool : true iff field is foreign key 
   */
  public function isForeignKey() {
    return $this->isForeignKey;
  }

  /**
   * getForeignKeyTable()
   * - Return table pointed to by this foreign key.
   * @return Table : foreign key table 
   */
  public function isForeignKey() {
    return $this->isForeignKey;
  }
}
