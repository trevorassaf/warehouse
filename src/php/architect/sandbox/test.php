<?php

require_once(dirname(__FILE__)."/../Import.php");

$test_db = new Database("test");

$test_table = new Table("test-table");

$test_db->addTable($test_table);

$test_col_builder = new ColumnBuilder();
$test_col = $test_col_builder
    ->setName("test-col")
    ->setDataType(DataType::int())
    ->build();

$test_table->addColumn($test_col);

var_dump($test_db);
