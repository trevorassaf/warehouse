<?php

require_once(dirname(__FILE__)."/PdoDbh.php");
require_once(dirname(__FILE__)."/DbhConfig.php");

class MySqlPdoDbh extends PdoDbh {

  // Pdo char set options prefix
  const PDO_CHAR_SET_PREFIX = "SET NAMES ";

  // DSN keys
  const DSN_DRIVER_NAME = "mysql";
  const DSN_HOST_KEY = "host";
  const DSN_DBNAME_KEY = "dbname";
  const DSN_PORT_KEY = "port";
  const DSN_UNIX_SOCKET_KEY = "unix_socket";

  /**
   * genPdoDsn()
   * - Create dsn string for mysql pdo.
   * @param config : DbhConfig
   * @return string : mysql dsn string
   */
  protected function genPdoDsn($config) {
    $dsn_map = array();
    if ($config->hasDbName()) {
      $dsn_map[self::DSN_DBNAME_KEY] = $config->getDbName();
    }

    // Configure connection type
    switch ($config->getConnectionType()) {
      case ConnectionType::REMOTE_HOST:
        $dsn_map[self::DSN_PORT_KEY] = $config->getPort();
      case ConnectionType::LOCALHOST:
        $dsn_map[self::DSN_HOST_KEY] = $config->getHost();
        break;
      case ConnectionType::UNIX_SOCKET:
        $dsn_map[self::DSN_UNIX_SOCKET_KEY] = $config->getUnixSocket();
        break;
      default:
        throw new RuntimeException(
            "Invalid connection type: " . $config->getConnectionType());
    }

    return $this->serializeDsnData(self::DSN_DRIVER_NAME, $dsn_map);
  }

  /**
   * genPdoOptions()
   * - Create options map for mysql pdo.
   * @param config : DbhConfig
   * @return Map<pdo-options-key, pdo-options-value>
   */
  protected function genPdoOptions($config) {
    return array(
      // TODO redo genPdoCharSetStr func!
      PDO::MYSQL_ATTR_INIT_COMMAND => $this->genPdoCharSetStr($config->getCharSet()),
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => false,
    ); 
  }

  /**
   * genPdoCharStr()
   * - Return pdo option string for char set.
   * @param char_set : CharSet
   * @return string : pdo options string
   */
  private function genPdoCharSetStr($char_set) {
    CharSet::validateType($char_set);
    return self::PDO_CHAR_SET_PREFIX . $char_set; 
  }
}
