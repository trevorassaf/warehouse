<?php

require_once(dirname(__FILE__)."/../DbDataType.php");

$dt = DbDataType::create(
  "int",
  false,
  false,
  4,
  null,
  1,
  1
);

var_dump($dt);
