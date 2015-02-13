<?php

class ColumnBuilder {

  private
    $name,
    $dataType,
    $firstLength,
    $secondLength,
    $allowsNull,
    $isReadOnly,
    $referencedTableName;

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
    $this->referencedTableName = null;
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
   * setReferencedTableName()
   * - Specify foreign key for column. 
   * @param referenced_table_name : string 
   * @return this
   */
  public function setReferencedTableName($referenced_table_name) {
    $this->referencedTableName = $referenced_table_name;
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
    
    // Fail due to invalid datatype/length values
    if ($this->dataType->allowsFirstLength()) {
      if ($this->dataType->requiresFirstLength()) {
        assert(isset($this->firstLength));
      } 
    } 
    
    if ($this->dataType->allowsSecondLength()) {
      if ($this->dataType->requiresSecondLength()) {
        assert(isset($this->secondLength));
      } 
    }

    // Produce column
    return new Column(
      $this->name,
      $this->dataType,
      $this->firstLength,
      $this->secondLength,
      $this->allowsNull,
      $this->isReadOnly,
      $this->referencedTableName
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
    $referencedTableName;

  /**
   * __construct()
   * - Ctor for Column.
   * @param name : string
   * @param data_type : DataType
   * @param first_length : unsigned int
   * @param second_length : unsigned int
   * @param allows_null : bool
   * @param is_read_only : bool
   * @param referenced_table_name : string 
   */
  public function __construct(
    $name,
    $data_type,
    $first_length,
    $second_length,
    $allows_null,
    $is_read_only,
    $referenced_table_name
  ) {
    $this->name = $name;
    $this->dataType = $data_type;
    $this->firstLength = $first_length;
    $this->secondLength = $second_length;
    $this->allowsNull = $allows_null;
    $this->isReadOnly = $is_read_only;
    $this->referencedTableName = $referenced_table_name;
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
   * getReferencedTableName()
   * - Return referenced table name.
   * @return string : referenced table
   */
  public function getReferencedTableName() {
    return $this->referencedTableName;
  }

  /**
   * isForeignKey()
   * - Return true iff this column is a foreign key.
   * @return bool : true iff column is foreign key
   */
  public function isForeignKey() {
    return isset($this->referencedTableName);
  }
}
