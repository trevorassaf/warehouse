<?php

require_once(dirname(__FILE__)."/../WhDatabase.php");

$db = WhDatabase::create("w-db", 1);

var_dump($db);
