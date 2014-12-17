<?php

abstract class PdoFactory {

  private $pdoConfig;

  public function __construct($pdo_config) {
    $this->pdoConfig = $pdo_config;
  }

  // todo abstract dsn gen logic
  abstract public function genConnection();
}
