<?php

require_once("access_layer.php");

$dbh_config_builder = new DbhConfigBuilder();
$dbh_config = $dbh_config_builder
    ->setPassword("password")
    ->setUsername("trevor")
    ->build();

SqlRecord::initDatabaseFactory($dbh_config);


function deleteAllFoo() {
  foo::deleteAll();
}

function insertFoo() {
  SqlRecord::beginTx(); 
  foo::insert(
    array(
      foo::NAME => "Kaiser Willhelm II",
    )
  );
  foo::insert(
    array(
      foo::NAME => "Tsar Nicholas II"
    )
  );
  
  foo::insert(
    array(
      foo::NAME => "King George V"
    )
  );
  SqlRecord::endTx();
}

function fetchAll() {
  $sql_records = foo::fetchAll();
  var_dump($sql_records);
} 

// -- MAIN
function main() {
  insertFoo(); 
}

main();
