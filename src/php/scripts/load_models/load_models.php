<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/MySQLLoader.php");
require_once(dirname(__FILE__)."/DtCategoryLoader.php");
require_once(dirname(__FILE__)."/../../model/WhColumn.php");
require_once(dirname(__FILE__)."/../../model/DbDataType.php");

WhColumn::deleteAll();
DbDataType::deleteAll();
DtCategoryLoader::load();
MySQLLoader::loadModel();
