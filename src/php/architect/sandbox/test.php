<?php

require_once(dirname(__FILE__)."/../Import.php");

$arch = new Architect(
    new SqlDbBuilder(),
    new PhpAccessLayerBuilder()  
);

$test_db = new Database("test_db");

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
$baz_enum->addElement("a");
$baz_enum->addElement("b");
$baz_enum->addElement("c");

$test_db->addEnum($baz_enum);

$arch->create($test_db, "./");
