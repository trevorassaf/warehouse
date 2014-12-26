<?php

require_once(dirname(__FILE__)."/../Import.php");

$dbh = AccessLayerObject::$databaseFactory->getConnection();
$stmt = $dbh->prepare("SELECT * FROM test_table WHERE id=:id");
// $stmt->bindValue(":table_name", "test_table");
// $stmt->bindValue(":id", 1);
// $stmt->execute();
// var_dump($stmt->fetchAll());
var_dump($stmt);
$dbh = null;
$dbh = AccessLayerObject::$databaseFactory->getConnection();
var_dump($stmt->queryString);
var_dump($stmt->errorCode());
var_dump($stmt->errorInfo());
$stmt->execute();
var_dump($stmt->fetchAll());

