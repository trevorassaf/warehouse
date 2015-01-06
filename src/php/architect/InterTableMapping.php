<?php

require_once(dirname(__FILE__)."/../util/Enum.php");

final class MappingType extends Enum {
  
  const ONE_TO_ONE = 0;
  const ONE_TO_MANY = 1;
  const MANY_TO_MANY = 2;

  protected static $SUPPORTED_TYPES = array(
    self::ONE_TO_ONE,
    self::ONE_TO_MANY,
    self::MANY_TO_MANY,
  );

}

class InterTableMapping {

  private
    $firstTable,
    $secondTable,
    $mappingType;

  public static function oneToOne($first_table, $second_table) {
    return new InterTableMapping($first_table, $second_table, MappingType::ONE_TO_ONE);
  }
  
  public static function oneToMany($first_table, $second_table) {
    return new InterTableMapping($first_table, $second_table, MappingType::ONE_TO_MANY);
  }
  
  public static function manyToMany($first_table, $second_table) {
    return new InterTableMapping($first_table, $second_table, MappingType::MANY_TO_MANY);
  }

  private function __construct(
    $first_table,
    $second_table,
    $mapping_type
  ) {
    // Fail due to identical tables
    assert($first_table != $second_table);
    MappingType::validateType($mapping_type);

    $this->firstTable = $first_table;
    $this->secondTable = $second_table;
    $this->mappingType = $mapping_type;
  }

  /**
   * getFirstTable()
   * - Return first table in mapping.
   * @return Table : first table
   */
  public function getFirstTable() {
    return $this->firstTable;
  }
  
  /**
   * getSecondTable()
   * - Return second table in mapping.
   * @return Table : second table
   */
  public function getSecondTable() {
    return $this->secondTable;
  }

  /**
   * getMappingType()
   * - Return inter-table mapping type.
   * @return MappingType : inter-table mapping
   */
  public function getMappingType() {
    return $this->mappingType;
  }
}
