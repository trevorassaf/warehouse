<?php

require_once(dirname(__FILE__)."/AccessLayerBuilder.php");
require_once(dirname(__FILE__)."/SqlDbBuilder.php");

class PhpClassBuilder {

 /**
  * String builders for class components.
  */ 
  private
    $databaseName,
    $table;  

  /**
   * __construct()
   * - Ctor for PhpAccessLayerBuilder.
   */
  public function __construct() {
    $this->databaseName = null;
    $this->table = null;
  }

  /**
   * bindDatabaseName()
   * - Set database name.
   * @param database_name : string
   * @return this
   */
  public function bindDatabaseName($database_name) {
    $this->databaseName = $database_name;
    return $this; 
  }

  /**
   * bindTable()
   * - Set table.
   * @param table : Table
   * @return this
   */
  public function bindTable($table) {
    $this->table = $table;
    return $this;
  }

  /**
   * build()
   * - Produce string for php table class implementation.
   * @return string : implementation of php table class.
   */
  public function build() {
    // Fail due to unset database name
    assert(isset($this->databaseName)); 

    // Fail due to unset table
    assert(isset($this->table));

    // Compose components of class definition
    $columns = $this->table->getColumns();

    // Create string buffers
    $db_keys = '';
    $access_layer_fields = '';
    $getters = '';
    $setters = '';

    // Assemble string buffers
    $unique_keys = $this->genUniqueKeys($this->table->getUniqueColumnsSet());

    if (!empty($columns)) {
      $db_keys = $this->genDbKeys($columns);
      $getters = $this->genGetters($columns);
      $setters = $this->genSetters($columns);
    }
    
    $access_layer = $this->genAccessLayerFields($columns);

    // Compose class content 
    $class_content = '';

    if (!empty($db_keys)) {
      $class_content .= $db_keys . "\n";
    }

    $class_content .= $unique_keys . "\n\n";
    $class_content .= $access_layer . "\n";

    if (!empty($getters)) {
      $class_content .= $getters . "\n";
    }
    if (!empty($setters)) {
      $class_content .= $setters;
    }
    
    return $this->genClassDefinition($this->table->getName(), $this->databaseName, $class_content);
  }

  /**
   * genUniqueKeys()
   * - Compose unique keys statement.
   * @param unique_columns_set : Set<Set<Column>>
   * @return string : unique key string
   */
  private function genUniqueKeys($unique_columns_set) {
    // Generate empty set if no keys are specified
    if (empty($unique_columns_set)) {
      return "\tprotected static \$keys = array();";
    }

    // Produce non-empty keys list 
    $keys_str_buff = "\tprotected static \$keys = array(";
    foreach ($unique_columns_set as $key_set) {
      $key_str = "";
      foreach ($key_set as $key) {
        $key_name = $this->genConstNameFromKeyName($key->getName());
        $key_str .= "{$key_name}, ";
      }
      
      $key_str = substr($key_str, 0, -2);
      $keys_str_buff .= "\n\t\tarray({$key_str}),";
    }
    
    return $keys_str_buff . "\n\t);";
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
   * genDbKeys()
   * - Create database keys.
   * @param columns : Map<string:key, Column>
   * @return string : db keys 
   */
  private function genDbKeys($columns) {
    $db_keys = '';

    foreach ($columns as $name => $col) {
      $db_keys .= "\tconst {$this->genConstNameFromKeyName($name)} = '{$name}';\n";      
    } 

    return $db_keys;
  }

  /**
   * genAccessLayerFields()
   * - Assemble access layer fields.
   * @param columns : Map<string:key, Column>
   * @return string : access layer field string
   */
  private function genAccessLayerFields($columns) {
    $access_layer_fields = '';

    foreach ($columns as $name => $col) {
      $access_layer_fields .= $this->genAccessLayerFieldString($col) . "\n";
    } 

    return $this->genAccessLayerFunctionDef($access_layer_fields);
  }

  /**
   * genAccessLayerFieldString()
   * - Create string for individual access layer field.
   * @param column : Column
   * @return string : access layer field string
   */
  private function genAccessLayerFieldString($column) {
    $data_type_str = "DataTypeName::";
    switch ($column->getDataType()->getName()) {
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
      case DataTypeName::DATE:
        $data_type_str .= 'DATE';
        break;
      case DataTypeName::FOREIGN_KEY:
        $data_type_str .= 'FOREIGN_KEY';
        break;
      case DataTypeName::FLOAT:
        $data_type_str .= 'FLOAT';
        break;
      default:
        die("Shouldn't happen: bad datatype: {$data_type}");
    }
    
    return "\t\t\tself::{$this->genConstNameFromKeyName($column->getName())} " 
      . "=> new AccessLayerField({$data_type_str}),"; 
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
   * genFieldNameForFunction()
   * - Return upper-camelcase version of input.
   * @param db_key_name : string in lower camel case
   * @return string : upper-camelcase version of input
   */
  private function genFieldNameForFunction($db_key_name) {
    return ucfirst($db_key_name);
  }
  
  /**
   * genLowerCamelCase()
   * - Generate lower camel case of string.
   * @param db_key_name : string
   * @return string : lower camel case version of string
   */
  private function genLowerCamelCase($db_key_name) {
    $token_list = explode(Database::FIELD_DELIMITER, $db_key_name); 
    for ($i = 1; $i < count($token_list); ++$i) {
      $token_list[$i] = ucfirst($token_list[$i]); 
    }
    return implode($token_list);
  }

  /**
   * genGetters()
   * - Create getters.
   * @param columns : Map<string:key, Column>
   * @return string : getter 
   */
  private function genGetters($columns) {
    $getters = '';
    
    foreach ($columns as $name => $col) {
      $getters .= "\n\tpublic function get{$this->genFieldNameForFunction($name)}() { return " 
          . "\$this->childDbFieldTable[self::{$this->genConstNameFromKeyName($name)}]->getValue(); }\n";
    }

    return $getters;
  }

  /**
   * genSetters()
   * - Create setters.
   * @param columns : Map<string:key, Column>
   * @return string : setter
   */
  private function genSetters($columns) {
    $setters = '';
    
    foreach ($columns as $name => $col) {
      $setters .= "\tpublic function set{$this->genFieldNameForFunction($name)}(\${$name}) "
          . "{ \$this->childDbFieldTable[self::{$this->genConstNameFromKeyName($name)}]->setValue(\${$name}); }\n\n";
    }

    return $setters;
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
}
