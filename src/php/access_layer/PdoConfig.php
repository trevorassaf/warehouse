<?php

abstract class PdoConfig {

  const LOCALHOST_IPV4 = '127.0.0.1';
  const LOCALHOST_DOMAIN = 'localhost';

  /**
   * Set of valid localhost aliases.
   */
  private static $LOCAL_HOST_NAMES = array(
    self::LOCALHOST_IPV4,
    self::LOCALHOST_DOMAIN,
  );

  /**
   * Dsn prefix for db.
   */
  protected static $DSN_PREFIX;

  /**
   * Lazy loaded dsn string.
   */
  private $dsn;

  /**
   * Generate map of dsn key/value pairs.
   */
  abstract protected function genDsnMap();

  /**
   * Return true iff 'host_name' is a valid local host alias
   */
  protected function isLocalHost($host_name) {
    return in_array($host_name, self::$LOCAL_HOST_NAMES);
  }

  /**
   * Generate the pdo dsn string from the child's dsn-map.
   */
  private function genDsn() {
    $dsn_map = $this->genDsnMap();
    $dsn = static::$DSN_PREFIX . ":";
    foreach ($dsn_map as $k => $v) {
      $dsn .= $k . "=" . $v . ";";
    }
    return $dsn;
  }

  /**
   * Lazy construction of dsn.
   */
  public function getDsn() {
    if (!isset($this->dsn)) {
      $this->dsn = $this->genDsn();
    }
    return $this->dsn;
  }
}
