<?php

require_once(dirname(__FILE__)."/../WhUser.php");

$user = WhUser::create(
  "Trevor",
  "Assaf",
  "pAs\$w0rd",
  "soccerbro93",
  "soccerbro93@douchtown.gov"
);

var_dump($user);
