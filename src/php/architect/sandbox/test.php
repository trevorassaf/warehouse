<?php

require_once(dirname(__FILE__)."/../Import.php");

$arch = new Architect(
    new SqlDbBuilder(),
    new PhpAccessLayerBuilder()  
);

$test_db = new Database("test_db");

// Foo table
$foo_table_builder = new TableBuilder();
$foo_table_builder->setName("foo");

$col_builder = new ColumnBuilder();

$name_col = $col_builder
  ->setName("name")
  ->setDataType(DataType::string())
  ->setFirstLength(10)
  ->build();
$age_col = $col_builder
  ->setName("age")
  ->setDataType(DataType::unsignedInt())
  ->build();

$foo_table_builder->bindColumn($name_col);
$foo_table_builder->bindColumn($age_col);

$foo_table_builder->addUniqueColumn($name_col);
$foo_table_builder->addUniqueColumnSet(array($name_col, $age_col));

$bar_table_builder = new TableBuilder();
$bar_table_builder->setName("bar");

TableBuilder::makeOneToMany($bar_table_builder, $foo_table_builder);

$foo_table_builder->addRow(
  array(
    $name_col->getName() => "trevor",
    $age_col->getName() => 21,
  )
);

$foo = $foo_table_builder->build();
$bar = $bar_table_builder->build();

$test_db->addTable($foo);
$test_db->addTable($bar);

$arch->create($test_db, "./");

/*
$foo_table = new Table("foo");

$test_db->addTable($foo_table);

$foo_name_col_builder = new ColumnBuilder();
$foo_name_col = $foo_name_col_builder
    ->setName("name")
    ->setDataType(DataType::string())
    ->setFirstLength(20)
    ->build();

$foo_table->addColumn($foo_name_col);

$foo_table->addUniqueKey($foo_name_col);

$bar_table = new Table("bar");
$test_db->addTable($bar_table);

$test_db->addTableMapping($foo_table, $bar_table, TableMappingType::MANY_TO_MANY);

$baz_enum = new EnumTable("baz");
$baz_col_builder = new ColumnBuilder();
$baz_value_col = $baz_col_builder
    ->setName("value") 
    ->setDataType(DataType::string())
    ->setFirstLength(1)
    ->build();

$baz_enum->addColumn($baz_value_col);

$baz_enum->addElement(array("value" => "a"));
$baz_enum->addElement(array("value" => "b"));
$baz_enum->addElement(array("value" => "c"));

$test_db->addEnum($baz_enum);

$arch->create($test_db, "./");
