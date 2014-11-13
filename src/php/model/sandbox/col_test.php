<?php

require_once(dirname(__FILE__)."/../WhColumn.php");

$col = WhColumn::create(
  "col",
  6,
  true,
  false,
  true,
  1,
  1
);

var_dump($col);
