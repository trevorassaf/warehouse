<?php

class Foo {
  protected static $a;

  public function setA() {
    static::$a = get_called_class();
  }

  public function printA() {
    echo "\n".static::$a . "\n";
  }

  final public static function clear() {
    static::$a = "clear";
  }
}

class Bar extends Foo {
  protected static $a;
}
class Baz extends Foo {
  protected static $a;
}

$bar = new Bar();
$baz = new Baz();

$bar->setA();
$bar->printA();

$baz->setA();
$baz->printA();


$bar->printA();
$baz->printA();

Foo::clear();

$bar->printA();
$baz->printA();
