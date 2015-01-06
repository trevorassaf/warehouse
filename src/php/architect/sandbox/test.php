<?php

require_once(dirname(__FILE__)."/../Import.php");

$test_db = new Database("test-db");

$foo_table = new Table("foo");

$test_db->addTable($foo_table);

$foo_name_col_builder = new ColumnBuilder();
$foo_name_col = $foo_name_col_builder
    ->setName("name")
    ->setDataType(DataType::string())
    ->setFirstLength(20)
    ->build();

$foo_table->addColumn($foo_name_col);

$bar_table = new Table("bar");
$test_db->addTable($bar_table);

$test_db->addTableMapping(InterTableMapping::oneToOne($foo_table, $bar_table));

$arch = new Architect();
$arch->create($test_db, "./");
