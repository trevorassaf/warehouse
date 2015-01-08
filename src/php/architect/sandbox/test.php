<?php

require_once(dirname(__FILE__)."/../Import.php");

$test_db = new Database("test_db");

$foo_table = new Table("foo");

$test_db->setTable($foo_table);

$foo_name_col_builder = new ColumnBuilder();
$foo_name_col = $foo_name_col_builder
    ->setName("name")
    ->setDataType(DataType::string())
    ->setFirstLength(20)
    ->build();

$foo_table->addColumn($foo_name_col);

$bar_table = new Table("bar");
$test_db->setTable($bar_table);

$test_db->setTableMapping($foo_table, $bar_table, TableMappingType::ONE_TO_ONE);

$arch = new Architect();
$arch->create($test_db, "./");
