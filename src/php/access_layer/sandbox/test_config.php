<?php

require_once(dirname(__FILE__)."/../Import.php");

$dbh_config_builder = new DbhConfigBuilder();
$dbh_config_builder
  ->setPassword("password")
  ->setUsername("trevor");
$dbh_config = $dbh_config_builder->build();

$mysql_pdo_factory = MySqlPdoFactory::get($dbh_config);
$dbh = $mysql_pdo_factory->getConnection();

$pdo_stmt = $dbh->query("SHOW DATABASES");
$results = $pdo_stmt->fetchAllRows();
var_dump($results);
