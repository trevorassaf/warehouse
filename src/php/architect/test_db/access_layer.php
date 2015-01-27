<?php

require_once('/Users/ITLAYER/projects/warehouse/src/php/architect/../access_layer/SqlRecord.php');

abstract class test_db extends SqlRecord {}

class foo extends test_db {

	const NAME = 'name';

	protected static $keys = array(
		array(self::NAME),
	);

	protected static function genChildDbFieldTableTemplate() {
		return array(
			self::NAME => new AccessLayerField(DataTypeName::STRING),
		);
	}

	public function getName() { return $this->childDbFieldTable[self::NAME]->getValue(); }

	public function setName($name) { $this->childDbFieldTable[self::NAME]->setValue($name); }

}

class bar extends test_db {

	protected static $keys = array(
	);

	protected static function genChildDbFieldTableTemplate() {
		return array();
	}

}

class bar_foo_join_table extends test_db {

	const FOO_ID = 'foo_id';
	const BAR_ID = 'bar_id';

	protected static $keys = array();

	protected static function genChildDbFieldTableTemplate() {
		return array(
			self::FOO_ID => new AccessLayerField(DataTypeName::UNSIGNED_INT),
			self::BAR_ID => new AccessLayerField(DataTypeName::UNSIGNED_INT),
		);
	}

	public function getFooId() { return $this->childDbFieldTable[self::FOO_ID]->getValue(); }

	public function getBarId() { return $this->childDbFieldTable[self::BAR_ID]->getValue(); }

	public function setFooId($foo_id) { $this->childDbFieldTable[self::FOO_ID]->setValue($foo_id); }

	public function setBarId($bar_id) { $this->childDbFieldTable[self::BAR_ID]->setValue($bar_id); }

}

class baz extends test_db {

	const VALUE = 'value';

	protected static $keys = array(
		array(self::VALUE),
	);

	protected static function genChildDbFieldTableTemplate() {
		return array(
			self::VALUE => new AccessLayerField(DataTypeName::STRING),
		);
	}

	public function getValue() { return $this->childDbFieldTable[self::VALUE]->getValue(); }

}

