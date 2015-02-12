<?php

class ColumnBuilder {

  private
    $name,
    $dataType,
    $firstLength,
    $secondLength,
    $allowsNull,
    $isReadOnly,
    $foreignKeyTable;

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
    $this->isReadOnly = false;
    $this->foreignKeyTable = null;
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
   * setIsReadOnly()
   * - Indicate if column is read only 
   * @param is_read_only : bool
   * @return this
   */
  public function setIsReadOnly($is_read_only) {
    $this->isReadOnly = $is_read_only;
    return $this;
  }

  /**
   * setForeignKey()
   * - Specify foreign key for column. 
   * @param foreign_key_table : Table 
   * @return this
   */
  public function setForeignKey($foreign_key_table) {
    $this->foreignKeyTable = $foreign_key_table;
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
    if ($this->dataType->allowsFirstLength()) {
      if ($this->dataType->requiresFirstLength()) {
        assert(isset($this->firstLength));
      } 
    } else {
      assert(!isset($this->firstLength));
    }

    if ($this->dataType->allowsSecondLength()) {
      if ($this->dataType->requiresSecondLength()) {
        assert(isset($this->secondLength));
      } 
    } else {
      assert(!isset($this->secondLength));
    }

    return new Column(
      $this->name,
      $this->dataType,
      $this->firstLength,
      $this->secondLength,
      $this->allowsNull,
      $this->isReadOnly,
      $this->foreignKeyTable
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
    $isReadOnly,
    $foreignKeyTable;

  public function __construct(
    $name,
    $data_type,
    $first_length,
    $second_length,
    $allows_null,
    $is_read_only,
    $foreign_key_table
  ) {
    $this->name = $name;
    $this->dataType = $data_type;
    $this->firstLength = $first_length;
    $this->secondLength = $second_length;
    $this->allowsNull = $allows_null;
    $this->isReadOnly = $is_foreign_key;
    $this->foreignKeyTable = $foreign_key_table;
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
   * hasFirstLength()
   * - Return true iff first length was specified.
   * @return bool : true if has first length
   */
  public function hasFirstLength() {
    return isset($this->firstLength);
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
   * hasSecondLength()
   * - Return true iff second length was specified.
   * @return bool : true if has first length
   */
  public function hasSecondLength() {
    return isset($this->secondLength);
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
   * isReadOnly()
   * - Indicate if field is read-only.
   * @return bool : is read only 
   */
  public function isReadOnly() {
    return $this->isReadOnly;
  }

  /**
   * getForeignKeyTable()
   * - Return referenced table.
   * @return Table : referenced table
   */
  public function getForeignKeyTable() {
    return $this->foreignKeyTable;
  }
}
