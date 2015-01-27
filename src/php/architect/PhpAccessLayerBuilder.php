<?php

require_once(dirname(__FILE__)."/AccessLayerBuilder.php");
require_once(dirname(__FILE__)."/SqlDbBuilder.php");
require_once("PhpEnumClassBuilder.php");
require_once("EnumTable.php");

class PhpClassBuilder {

  const COLUMN_NAME_DELIMITER = '_';

 /**
  * String builders for class components.
  */ 
  private
    $dbKeys,
    $accessLayerFields,
    $getters,
    $setters,
    $uniqueKeys;

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
    $this->uniqueKeys = $this->genDefaultUniqueKeys();
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
    $getter_str = "\tpublic function get{$this->genUpperCamelCase($secondary_table_name)}() { return {$secondary_table_name}::fetchById(\$this->childDbFieldTable[self::{$this->genConstantName($foreign_key_col_name)}]); }";
    $this->getters .= "\t".$getter_str . "\n";
  }

  private function appendOneToOneSetter($secondary_table_name, $foreign_key_col_name) {
    $setter_str = "\tpublic function set{$this->genUpperCamelCase($secondary_table_name)}({$secondary_table_name}) { \$this->childDbFieldTable[self::{$this->genConstantName($foreign_key_col_name)}] = \${$secondary_table_name}->getId(); }";
    $this->setters .= "\t".$setter_str . "\n";
  }

  public function addOneToManyMapping($primary_table_name, $secondary_table_name) {
    $foreign_key_col_name = $this->genForeignKeyField($secondary_table_name); 
    $this->appendAccessLayerFields($foreign_key_col_name, DataType::foreignKey());

    $this->appendOneToManyGetter($primary_table_name, $secondary_table_name, $foreign_key_col_name);
    $this->appendOneToManySetter($primary_table_name, $secondary_table_name, $foreign_key_col_name);
  }

  private function appendOneToManyGetter($primary_table_name, $secondary_table_name, $foreign_key_col_name) {
    $primary_table_foreign_key_constant_name = $this->genConstantName($this->genForeignKeyField($primary_table_name));
    $getter_str = "\tpublic function get{$this->genUpperCamelCase($secondary_table_name)}Set() { return {$secondary_table_name}::fetch(array({$primary_table_name}::{$primary_table_foreign_key_constant_name})); }";
    $this->getters .= "\n".$getter_str . "\n";
  }

  private function appendOneToManySetter($primary_table_name, $secondary_table_name, $foreign_key_col_name) {
    $primary_table_foreign_key_setter = "set{$this->genUpperCamelCase($this->genForeignKeyField($primary_table_name))}";
    $setter_str = "\tpublic function add{$this->genUpperCamelCase($secondary_table_name)}({$secondary_table_name}) { {$secondary_table_name}->set{$primary_table_foreign_key_setter}(\$this->getId()); }";
    $this->setters .= "\n".$setter_str . "\n";
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
    $this->getters .= "\n".$getter_str . "\n";
  } 

  private function appendManyToOneSetter($secondary_table_name) {
    $upper_camel_case_secondary_table_name = $this->genUpperCamelCase($secondary_table_name);
    $secondary_key_name = $this->genConstantName($this->genForeignKeyField($secondary_table_name)); 
    $setter_str = "public function set{$upper_camel_case_secondary_table_name}(\${$secondary_table_name}) {{$secondary_table_name}::fetchById(\$this->childDbFieldTable[self::{$secondary_key_name}]) = \${$secondary_table_name}->getId(); }"; 
    $this->setters .= "\n".$setter_str . "\n";
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

   // TODO finish this 
  }

  // TODO finish this
  private function appendManyToManySetter($primary_table_name, $secondary_table_name, $foreign_key_col_name) {}

  /**
   * genForeignKeyField()
   * - Compose name of foreign key field.
   * @param table_name : string
   * @return string : foreign key name
   */  
  private function genForeignKeyField($table_name) {
    return strtolower($table_name) . "_id";
  }

  /**
   * appendDbKey()
   * - Generate db key and append it to the cached sequence of access
   *    layer fields.
   * @param db_key_name : string
   * @return void
   */
  protected function appendDbKey($db_key_name) {
    $this->dbKeys .= "\tconst {$this->genConstantName($db_key_name)} = '{$db_key_name}';\n";
  }

  /**
   * appendAccessLayerFields()
   * - Generate access layer field delcaration and append it to
   *    the cached sequence of access layer fields.
   * @param db_key_name : string
   * @param data_type : DataType
   * @return void
   */
  protected function appendAccessLayerFields($db_key_name, $data_type) {
    $data_type_str = "DataTypeName::";
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
    
    $this->accessLayerFields .= "\t\t\tself::{$this->genConstantName($db_key_name)} => new AccessLayerField({$data_type_str}),\n"; 
  }

  /**
   * appendGetter()
   * - Generate getter of field and append to cached sequence of
   *    getters.
   * @param db_key_name : string
   * @return void
   */
  protected function appendGetter($db_key_name) {
    $getter_str = "\tpublic function get{$this->genUpperCamelCase($db_key_name)}() { return \$this->childDbFieldTable[self::{$this->genConstantName($db_key_name)}]->getValue(); }";
    $this->getters .= "\n".$getter_str . "\n";
  }

  /**
   * appendSetter()
   * - Generate setter from field name and append to cached sequence
   *    of setters.
   * @param db_key_name : string
   * @return void    
   */
  private function appendSetter($db_key_name) {
    $setter_str = "\tpublic function set{$this->genUpperCamelCase($db_key_name)}(\${$db_key_name}) { \$this->childDbFieldTable[self::{$this->genConstantName($db_key_name)}]->setValue(\${$db_key_name}); }";
    $this->setters .= "\n".$setter_str . "\n";
  }

  /**
   * genDefaultUniqueKeys()
   * - Generate default unique keys statement.
   * @return string : unique key declaration
   */
  private function genDefaultUniqueKeys() {
    return "\tprotected static \$keys = array();";
  }

  /**
   * genConstNameFromKeyName()
   * - Generate name of constant from name of key.
   * @param key_name : string
   * @return string : const definition
   */
  private function genConstNameFromKeyName($key_name) {
    $const_name = "";
    $key_name_size = strlen($key_name);
    for ($i = 0; $i < $key_name_size; ++$i) {
      $curr_letter = $key_name[$i];
      if (ord($curr_letter) >= 65 && ord($curr_letter) <= 90) {
        $const_name .= '_'; 
      }
      $const_name .= $curr_letter; 
    }
    
    return "self::" . strtoupper($const_name);
  }

  /**
   * setUniqueKeySetList()
   * - Set list of unique key-sets.
   * @param unique_key_set_list : Array<Array<string:key-name>>
   * @return void
   */
  public function setUniqueKeySetList($unique_key_set_list) {
    $this->uniqueKeys = "\tprotected static \$keys = array(";
    foreach ($unique_key_set_list as $key_set) {
      $key_set_str = "";
      foreach ($key_set as $key) {
        $key_name = $this->genConstNameFromKeyName($key->getName());
        $key_set_str .= "{$key_name}, ";
      }
      
      $key_set_str = substr($key_set_str, 0, -2);
      $this->uniqueKeys .= "\n\t\tarray({$key_set_str}),";
    }
    $this->uniqueKeys .= "\n\t);";
  }

  /**
   * genClassDefinition()
   * - Generate php definition of class.
   * @param table_class_name : string
   * @param parent_class_name : string
   * @param class_content : string
   * @return string : php class defintion
   */
  private function genClassDefinition($table_class_name, $parent_class_name, $class_content) {
    return "class {$table_class_name} extends {$parent_class_name} {\n\n$class_content\n}"; 
  }

  /**
   * build()
   * - Compose php access layer definition string.
   * @return string : php access layer definition
   */
  public function build() {
    $class_content = '';

    // Add db keys
    if (!empty($this->dbKeys)) {
      $class_content .= $this->dbKeys . "\n";
    }

    // Add unique key constraints
    $class_content .= $this->uniqueKeys . "\n\n";

    // Add access layer fields
    $access_layer_str = $this->genAccessLayerFunctionDef($this->accessLayerFields);
    if (!empty($access_layer_str)) {
      $class_content .= $access_layer_str . "\n";
    }

    // Add getters
    if (!empty($this->getters)) {
      $class_content .= $this->getters;
    }

    // Add setters 
    if (!empty($this->setters)) {
      $class_content .= $this->setters;
    }

    return $this->genClassDefinition($this->tableClassName, $this->parentClassName, $class_content);
  }

  /**
   * genAccessLayerFunctionDef()
   * - Compose access layer child table template function.
   * @param access_layer_field_str : string
   * @return string : genChildDbFieldTableTemplate() function definition
   */
  private function genAccessLayerFunctionDef($access_layer_field_str) {
    $function_prefix = "\tprotected static function genChildDbFieldTableTemplate() {\n\t\treturn array(";
    return $function_prefix . (empty($access_layer_field_str)
      ? ");\n\t}"
      : "\n{$access_layer_field_str}\t\t);\n\t}");
  }

  /**
   * genConstantName()
   * - Compose constant name.
   * @param db_key_name : string
   * @return string : const name
   */
  private function genConstantName($db_key_name) {
    return strtoupper($db_key_name);
  }

  /**
   * genLowerCamelCase()
   * - Generate lower camel case of string.
   * @param db_key_name : string
   * @return string : lower camel case version of string
   */
  private function genLowerCamelCase($db_key_name) {
    $token_list = explode(self::COLUMN_NAME_DELIMITER, $db_key_name); 
    for ($i = 1; $i < count($token_list); ++$i) {
      $token_list[$i] = ucfirst($token_list[$i]); 
    }
    return implode($token_list);
  }

  /**
   * genUpperCamelCase()
   * - Return upper-camelcase version of input.
   * @param db_key_name : string
   * @return string : upper-camelcase version of input
   */
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
   * Relative path to access layer super class.
   */
  const RELATIVE_PATH_TO_SUPER_CLASS = "/../access_layer/SqlRecord.php";

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
    // Create php access layer
    $file_contents =
        $this->genFileHeader() . "\n\n" .
        $this->genRequiresStatements() . "\n\n" .
        $this->genDatabaseClass($database->getName()) . "\n\n" .
        $this->genTablesClasses($database);

    // Create access layer file
    $path = "{$path_to_dir}/" . self::ACCESS_LAYER_FILE_NAME;
    file_put_contents($path, $file_contents);
  }

  private function genFileHeader() {
    return "<?php";
  }

  private function genRequiresStatements() {
    $path = dirname(__FILE__) . self::RELATIVE_PATH_TO_SUPER_CLASS;
    return "require_once('{$path}');";
  }

  private function genDatabaseClass($db_name) {
    return "abstract class {$db_name} extends " . self::DB_SUPER_CLASS_NAME . " {}";
  }

  private function genTablesClasses($database) {
    $php_class_builder_list = array();

    // Create canonical table builders
    foreach ($database->getTables() as $table) {
      $class_builder = new PhpClassBuilder($table->getName(), $database->getName());
      foreach ($table->getColumns() as $column) {
        $class_builder->addField($column);
      } 
      
      $class_builder->setUniqueKeySetList($table->getUniqueColumnSetList());
      $php_class_builder_list[] = $class_builder;
    } 

    // Create join-table builders
    foreach ($database->getTableMappings() as $mapping) {
      if ($mapping->getTableMappingType() == TableMappingType::MANY_TO_MANY) {
        $php_class_builder_list[] = $this->genJoinTableClassBuilder(
            $database->getName(),
            $mapping->getPrimaryTable(),
            $mapping->getSecondaryTable()
        );
      }
    }

    // Create enum-table builders
    foreach ($database->getEnums() as $enum) {
      $enum_class_builder = new PhpEnumClassBuilder($enum, $database->getName());
      $enum_class_builder->setUniqueKeySetList($enum->getUniqueColumnSetList());
      $php_class_builder_list[] = $enum_class_builder;
    }

    // Create class definition strings
    $class_definitions = '';
    foreach ($php_class_builder_list as $php_class_builder) {
      $class_definitions .= $php_class_builder->build() . "\n\n";
    }

    return $class_definitions;
  }

  /**
   * genJoinTableClassBuilder()
   * - Compose join table class builder.
   * @param database_name : string
   * @param table_a : Table
   * @param table_b : Table
   * @return PhpEnumClassBuilder : php enum class builder
   */
  private function genJoinTableClassBuilder($database_name, $table_a, $table_b) {
    $join_table_name = SqlDbBuilder::genJoinTableName(
        $table_a->getName(), $table_b->getName()
    );
    $class_builder = new PhpClassBuilder($join_table_name, $database_name);
    
    // Add join column pair
    $table_a_field_name = SqlDbBuilder::genForeignKeyColumnName($table_a->getName());
    $table_b_field_name = SqlDbBuilder::genForeignKeyColumnName($table_b->getName());

    $table_a_field_builder = new ColumnBuilder();
    $class_builder->addField(
       $table_a_field_builder
          ->setName($table_a_field_name)
          ->setDataType(DataType::unsignedInt())
          ->build()
    );
    
    $table_b_field_builder = new ColumnBuilder();
    $class_builder->addField(
       $table_b_field_builder
          ->setName($table_b_field_name)
          ->setDataType(DataType::unsignedInt())
          ->build()
    );

    return $class_builder;
  }
}
