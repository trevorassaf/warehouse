<?php

require_once(dirname(__FILE__)."/../DtCategory.php");

$cat = DtCategory::create(
  "Number" 
);

var_dump($cat);
