<?php

require_once('/Users/ITLAYER/projects/warehouse/src/php/architect/../access_layer/SqlRecord.php');

abstract class test_db extends SqlRecord {}

class foo extends test_db {

	const self::NAME = 'name';
	const self::AGE = 'age';

	protected static $keys = array(
		array(self::NAME),
		array(self::NAME, self::AGE),
	);

	protected static function genChildDbFieldTableTemplate() {
		return array(
			self::self::NAME => new AccessLayerField(DataTypeName::STRING),
			self::self::AGE => new AccessLayerField(DataTypeName::UNSIGNED_INT),
		);
	}

	public function getName() { return $this->childDbFieldTable[self::self::NAME]->getValue(); }

	public function getAge() { return $this->childDbFieldTable[self::self::AGE]->getValue(); }

	public function setName($name) { $this->childDbFieldTable[self::self::NAME]->setValue($name); }

	public function setAge($age) { $this->childDbFieldTable[self::self::AGE]->setValue($age); }


}

class bar extends test_db {

	const self::NAME = 'name';

	protected static $keys = array();

	protected static function genChildDbFieldTableTemplate() {
		return array(
			self::self::NAME => new AccessLayerField(DataTypeName::STRING),
		);
	}

	public function getName() { return $this->childDbFieldTable[self::self::NAME]->getValue(); }

	public function setName($name) { $this->childDbFieldTable[self::self::NAME]->setValue($name); }


}

class foo_bar_join_table extends test_db {

	const self::BAR_ID = 'barId';
	const self::FOO_ID = 'fooId';

	protected static $keys = array();

	protected static function genChildDbFieldTableTemplate() {
		return array(
			self::self::BAR_ID => new AccessLayerField(DataTypeName::FOREIGN_KEY),
			self::self::FOO_ID => new AccessLayerField(DataTypeName::FOREIGN_KEY),
		);
	}

	public function getBarId() { return $this->childDbFieldTable[self::self::BAR_ID]->getValue(); }

	public function getFooId() { return $this->childDbFieldTable[self::self::FOO_ID]->getValue(); }

	public function setBarId($barId) { $this->childDbFieldTable[self::self::BAR_ID]->setValue($barId); }

	public function setFooId($fooId) { $this->childDbFieldTable[self::self::FOO_ID]->setValue($fooId); }


}

