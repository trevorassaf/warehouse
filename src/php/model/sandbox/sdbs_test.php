<?php

require_once(dirname(__FILE__)."/../SupportedDb.php");

$db = SupportedDb::create("mysql");

var_dump($db);
