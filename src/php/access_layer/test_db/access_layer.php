<?php

require_once('/Users/ITLAYER/projects/warehouse/src/php/architect/../access_layer/SqlRecord.php');

abstract class test_db extends SqlRecord {}

class foo extends test_db {

const NAME = 'name';

public static function genChildDbFieldTableTemplate() {
	return array(
		self::NAME => new AccessLayerField(DataTypeName::STRING),
	);
}

public function getName() { return $this->childDbKeys[self::NAME]; }

public function setName($name) { $this->childDbKeys[self::NAME]->setValue($name); }

}

class bar extends test_db {

public static function genChildDbFieldTableTemplate() {
	return array();
}

}

class bar_foo_join_table extends SqlRecord {

const FOO_ID = 'foo_id';
const BAR_ID = 'bar_id';

public static function genChildDbFieldTableTemplate() {
	return array(
		self::FOO_ID => new AccessLayerField(DataTypeName::UNSIGNED_INT),
		self::BAR_ID => new AccessLayerField(DataTypeName::UNSIGNED_INT),
	);
}

public function getFooId() { return $this->childDbKeys[self::FOO_ID]; }

public function getBarId() { return $this->childDbKeys[self::BAR_ID]; }

public function setFooId($foo_id) { $this->childDbKeys[self::FOO_ID]->setValue($foo_id); }

public function setBarId($bar_id) { $this->childDbKeys[self::BAR_ID]->setValue($bar_id); }

}

