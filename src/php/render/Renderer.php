<?php

require_once(dirname(__FILE__)."/../model/WhDatabase.php");

abstract class Renderer {

  abstract public static function render($wh_db);
}
