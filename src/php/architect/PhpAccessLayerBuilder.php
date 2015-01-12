<?php

require_once(dirname(__FILE__)."/AccessLayerBuilder.php");

final class PhpClassBuilder {

  const COLUMN_NAME_DELIMITER = '_';

 /**
  * String builders for class components.
  */ 
  private
    $dbKeys,
    $accessLayerFields,
    $getters,
    $setters;

  /**
   * Names of table class and database class.
   */
  private
    $tableClassName,
    $parentClassName;

  public function __construct($table_name, $parent_class_name) {
    $this->tableClassName = $table_name;
    $this->parentClassName = $parent_class_name;

    // Initialize string builders
    $this->dbKeys = '';
    $this->accessLayerFields = '';
    $this->getters = '';
    $this->setters = '';
  }

  public function addField($column) {
    $column_name = $column->getName();
    $this->appendDbKey($column_name);
    $this->appendGetter($column_name);
    $this->appendSetter($column_name);
    $this->appendAccessLayerFields($column_name, $column->getDataType());
  }

  public function addOneToOneMapping($primary_table_name, $secondary_table_name) {
    $foreign_key_col_name = $this->genForeignKeyField($secondary_table_name); 
    $this->appendDbKey($foreign_key_col_name);
    $this->appendAccessLayerFields($foreign_key_col_name, DataType::foreignKey());

    $this->appendOneToOneGetter($secondary_table_name, $foreign_key_col_name);
    $this->appendOneToOneSetter($secondary_table_name, $foreign_key_col_name);
  }

  private function appendOneToOneGetter($secondary_table_name, $foreign_key_col_name) {
    $getter_str = "public function get{$this->genUpperCamelCase($secondary_table_name)}() { return {$secondary_table_name}::fetchById(\$this->childDbFieldTable[self::{$this->genConstantName($foreign_key_col_name)}]); }";
    $this->getters .= $getter_str . "\n";
  }

  private function appendOneToOneSetter($secondary_table_name, $foreign_key_col_name) {
    $setter_str = "public function set{$this->genUpperCamelCase($secondary_table_name)}({$secondary_table_name}) { \$this->childDbFieldTable[self::{$this->genConstantName($foreign_key_col_name)}] = \${$secondary_table_name}->getId(); }";
    $this->setters .= $setter_str . "\n";
  }

  public function addOneToManyMapping($primary_table_name, $secondary_table_name) {
    $foreign_key_col_name = $this->genForeignKeyField($secondary_table_name); 
    $this->appendAccessLayerFields($foreign_key_col_name, DataType::foreignKey());

    $this->appendOneToManyGetter($primary_table_name, $secondary_table_name, $foreign_key_col_name);
    $this->appendOneToManySetter($primary_table_name, $secondary_table_name, $foreign_key_col_name);
  }

  private function appendOneToManyGetter($primary_table_name, $secondary_table_name, $foreign_key_col_name) {
    $primary_table_foreign_key_constant_name = $this->genConstantName($this->genForeignKeyField($primary_table_name));
    $getter_str = "public function get{$this->genUpperCamelCase($secondary_table_name)}Set() { return {$secondary_table_name}::fetch(array({$primary_table_name}::{$primary_table_foreign_key_constant_name})); }";
    $this->getters .= $getter_str . "\n";
  }

  private function appendOneToManySetter($primary_table_name, $secondary_table_name, $foreign_key_col_name) {
    $primary_table_foreign_key_setter = "set{$this->genUpperCamelCase($this->genForeignKeyField($primary_table_name))}";
    $setter_str = "public function add{$this->genUpperCamelCase($secondary_table_name)}({$secondary_table_name}) { {$secondary_table_name}->set{$primary_table_foreign_key_setter}(\$this->getId()); }";
    $this->setters .= $setter_str . "\n";
  }
  
  public function addManyToOneMapping($primary_table_name, $secondary_table_name) {
    $foreign_key_col_name = $this->genForeignKeyField($secondary_table_name); 
    $this->appendDbKey($foreign_key_col_name);
    $this->appendAccessLayerFields($foreign_key_col_name, DataType::foreignKey());

    $this->appendManyToOneGetter($secondary_table_name, $foreign_key_col_name);
    $this->appendManyToOneSetter($secondary_table_name, $foreign_key_col_name);
  }

  private function appendManyToOneGetter($secondary_table_name) {
    $upper_camel_case_secondary_table_name = $this->genUpperCamelCase($secondary_table_name);
    $secondary_key_name = $this->genConstantName($this->genForeignKeyField($secondary_table_name)); 
    $getter_str = "public function get{$upper_camel_case_secondary_table_name}() { return {$secondary_table_name}::fetchById(\$this->childDbFieldTable[self::{$secondary_key_name}]); }"; 
    $this->getters .= $getter_str . "\n";
  } 

  private function appendManyToOneSetter($secondary_table_name) {
    $upper_camel_case_secondary_table_name = $this->genUpperCamelCase($secondary_table_name);
    $secondary_key_name = $this->genConstantName($this->genForeignKeyField($secondary_table_name)); 
    $setter_str = "public function set{$upper_camel_case_secondary_table_name}(\${$secondary_table_name}) {{$secondary_table_name}::fetchById(\$this->childDbFieldTable[self::{$secondary_key_name}]) = \${$secondary_table_name}->getId(); }"; 
    $this->setters .= $setter_str . "\n";
  }

  public function addManyToManyMapping($primary_table_name, $secondary_table_name) {
    $foreign_key_col_name = $this->genForeignKeyField($secondary_table_name); 
    $this->appendDbKey($foreign_key_col_name);
    $this->appendAccessLayerFields($foreign_key_col_name, DataType::foreignKey());

    $this->appendManyToManyGetter($primary_table_name, $secondary_table_name, $foreign_key_col_name);
    $this->appendManyToManySetter($primary_table_name, $secondary_table_name, $foreign_key_col_name);
  }

  private function appendManyToManyGetter($primary_table_name, $secondary_table_name, $foreign_key_col_name) {
    $upper_camel_case_primary_table_name = $this->genUpperCamelCase($primary_table_name);
    $primary_key_name = $this->genConstantName($this->genForeignKeyField($primary_table_name)); 
    
    $upper_camel_case_secondary_table_name = $this->genUpperCamelCase($secondary_table_name);
    $secondary_key_name = $this->genConstantName($this->genForeignKeyField($secondary_table_name)); 

     
  }

  private function appendManyToManySetter($primary_table_name, $secondary_table_name, $foreign_key_col_name) {}

  private function genForeignKeyField($table_name) {
    return strtolower($table_name) . "_id";
  }

  private function appendDbKey($db_key_name) {
    $this->dbKeys .= "const {$this->genConstantName($db_key_name)} = {$db_key_name};\n";
  }

  private function appendAccessLayerFields($db_key_name, $data_type) {
    $data_type_str = "DataTypeName::";
    var_dump($data_type->getName());
    switch ($data_type->getName()) {
      case DataTypeName::INT:
        $data_type_str .= 'INT';
        break;
      case DataTypeName::UNSIGNED_INT:
        $data_type_str .= 'UNSIGNED_INT';
        break;
      case DataTypeName::SERIAL:
        $data_type_str .= 'SERIAL';
        break;
      case DataTypeName::BOOL:
        $data_type_str .= 'BOOL';
        break;
      case DataTypeName::STRING:
        $data_type_str .= 'STRING';
        break;
      case DataTypeName::TIMESTAMP:
        $data_type_str .= 'TIMESTAMP';
        break;
      default:
        die("Shouldn't happen: bad datatype: {$data_type}");
    }
    
    $this->accessLayerFields .= "self::{$this->genConstantName($db_key_name)} => {$data_type_str},\n"; 
  }

  private function appendGetter($db_key_name) {
    $getter_str = "public function get{$this->genUpperCamelCase($db_key_name)}() { return \$this->childDbKeys[self::{$this->genConstantName($db_key_name)}]; }";
    $this->getters .= $getter_str . "\n";
  }

  private function appendSetter($db_key_name) {
    $setter_str = "public function set{$this->genUpperCamelCase($db_key_name)}({$db_key_name}) { \$this->childDbKeys[self::{$this->genConstantName($db_key_name)}]->setValue({$db_key_name}); }";
    $this->setters .= $setter_str . "\n";
  }

  private function genClassDefinition($table_class_name, $parent_class_name, $class_content) {
    return "class {$table_class_name} extends {$parent_class_name} {\n$class_content\n}"; 
  }

  public function build() {
    $class_content .= 
      $this->dbKeys . "\n" .
      $this->genAccessLayerFunctionDef($this->accessLayerFields) . "\n".
      $this->getters . "\n" .
      $this->setters; 

    return $this->genClassDefinition($this->tableClassName, $this->parentClassName, $class_content);
  }

  private function genAccessLayerFunctionDef($access_layer_field_str) {
    return
      "protected function loadChildDbFieldTable() {
        return array({$access_layer_field_str});
      }";
  }

  private function genConstantName($db_key_name) {
    return strtoupper($db_key_name);
  }

  private function genLowerCamelCase($db_key_name) {
    $token_list = explode(self::COLUMN_NAME_DELIMITER, $db_key_name); 
    for ($i = 1; $i < count($token_list); ++$i) {
      $token_list[$i] = ucfirst($token_list[$i]); 
    }
    return implode($token_list);
  }

  private function genUpperCamelCase($db_key_name) {
    return ucfirst($this->genLowerCamelCase($db_key_name));
  }
}

final class PhpAccessLayerBuilder implements AccessLayerBuilder {

  /**
   * Super class name for access layer classes.
   */
  const DB_SUPER_CLASS_NAME = 'SqlRecord';

  /**
   * Php access layer names.
   */
  const IMPORT_PHP_FILE_NAME = 'import.php';
  const ACCESS_LAYER_FILE_NAME = 'access_layer.php';

  /**
   * createAccessLayerFile()
   * @override AccessLayerBuilder
   */
  public function createAccessLayerFiles($database, $path_to_dir) {
    $file_contents =
        $this->genFileHeader() . "\n\n" .
        $this->genRequiresStatements() . "\n\n" .
        $this->genDatabaseClass($database->getName()) . "\n" .
        $this->genTablesClasses($database);
    echo "ACCESS LAYER -----------------------------------\n";
    echo $file_contents;
  }

  private function genFileHeader() {
    return "<?php\n";
  }

  private function genRequiresStatements() {
    return "requires_once(" . self::DB_SUPER_CLASS_NAME . ");";
  }

  private function genDatabaseClass($db_name) {
    return "abstract class {$db_name} extends " . self::DB_SUPER_CLASS_NAME . " {}";
  }

  private function genTablesClasses($database) {
    $php_class_builder_list = array();

    foreach ($database->getTables() as $table) {
      $class_builder = new PhpClassBuilder($table->getName(), self::DB_SUPER_CLASS_NAME);
      foreach ($table->getColumns() as $column) {
        $class_builder->addField($column);
      } 

      $php_class_builder_list[$table->getName()] = $class_builder; 
    } 

    // Create class definition strings
    $class_definitions = '';
    foreach ($php_class_builder_list as $table_name => $php_class_builder) {
      $class_definitions .= $php_class_builder->build() . "\n\n";
    }

    return $class_definitions;
  }

}
