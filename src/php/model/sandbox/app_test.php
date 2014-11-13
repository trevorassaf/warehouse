<?php

require_once(dirname(__FILE__)."/../WhApplication.php");

$app = WhApplication::create(
  "warehouse",
  1,
  1 
);

var_dump($app);
