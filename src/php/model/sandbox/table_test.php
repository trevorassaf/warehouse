<?php

require_once(dirname(__FILE__)."/../WhTable.php");

$table = WhTable::create(
  "table",
  1
);

var_dump($table);
