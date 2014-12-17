<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/Enum.php");
require_once(dirname(__FILE__)."/DbTiers.php");
require_once(dirname(__FILE__)."/PdoConfig.php");

// Define mysql database connection constants
// Define Server
defined("DB_SERVER") ? null : define("DB_SERVER", "127.0.0.1");
// Define Username
defined("DB_USER_NAME") ? null : define("DB_USER_NAME", "trevor");
// Define Password
defined("DB_PASSWORD") ? null : define("DB_PASSWORD", "password");

abstract class MySqlDbCharSet extends Enum {
  // PDO char-set prefix
  const PDO_CHAR_SET_STR_PREFIX = 'SET NAMES ';
  
  // Char sets 
  const UTF8 = "utf8";
  const ASCII = "ascii";

  // Supported char sets
  protected static $SUPPORTED_TYPES = array(
    self::UTF8,
    self::ASCII,
  );

  public static function genPdoCharSetStr($char_set) {
    static::validateType($char_set);
    return self::PDO_CHAR_SET_STR_PREFIX . $char_set;
  }
}

abstract class MySqlConnectionType extends Enum {

  // MySql PDO db connection types
  const LOCALHOST = 'localhost';
  const REMOTE_HOST = 'remote-host';
  const UNIX_SOCKET = 'unix-socket';

  // Supported MySql PDO db connection types
  protected static $SUPPORTED_TYPES = array(
    self::LOCALHOST,
    self::REMOTE_HOST,
    self::UNIX_SOCKET,
  );
}

/**
 * Immutable class representing configuration info for
 * MySql PDO db connection.
 */
final class MySqlDbConfig extends PdoConfig {

  // DSN keys
  const DSN_HOST_KEY = "host";
  const DSN_DBNAME_KEY = "dbname";
  const DSN_PORT_KEY = "port";
  const DSN_UNIX_SOCKET_KEY = "unix_socket";

  protected static $DSN_PREFIX = "mysql";

  private
    $connectionType,
    $hostName,
    $port,
    $unixSocket,
    $userName,
    $password,
    $charSet,
    $dbName,
    $dbTier,
    $dbNameWithTier;

  public function __construct(
    $connection_type,
    $host_name,
    $port,
    $unix_socket,
    $user_name,
    $password,
    $char_set,
    $db_name,
    $db_tier
  ) {
    // Validate mandatory fields
    if (!isset($username)) {
      throw new RuntimeException("Must set 'username' in MySQl PDO configuration");
    }
    
    if (!isset($password)) {
      throw new RuntimeException("Must set 'password' in MySQl PDO configuration");
    }

    MySqlDbCharSet::validateType($charSet);
    if (!isset($charSet)) {
      throw new RuntimeException("Must set 'charSet' in MySQl PDO configuration");
    }

    if (!isset($dbName)) {
      throw new RuntimeException("Must set 'dbName' in MySQl PDO configuration");
    }

    DbTiers::validateType($dbTier);
    if (!isset($dbTier)) {
      throw new RuntimeException("Must set 'dbTier' in MySQl PDO configuration");
    }

    // Validate connection type 
    MySqlConnectionType::validateType($connection_type);
    $this->connectionType = $connection_type;

    switch ($connection_type) {
      case MySqlConnectionType::LOCALHOST:
        if (!isset($host)) {
          $host = PdoConfig::LOCALHOST_IPV4;
        }

        if (isset($port)) {
          throw new RuntimeException("Port shouldn't be set if connecting to localhost.");
        }
        if (isset($unix_socket)) {
          throw new RuntimeException("Unix socket shouldn't be set if connecting to localhost.");
        }

        $this->host = $host;
        break;
      case MySqlConnectionType::REMOTE_HOST:
        if (!isset($host) || !isset($port)) {
          throw new RuntimeException("Host/port must be set if connecting to remote db.");
        }
        if (isset($unix_socket)) {
          throw new RuntimeException("Unix socket shouldn't be set if connecting to remote host.");
        }

        $this->host = $host;
        $this->port = $port;
        break;  
      case MySqlConnectionType::UNIX_SOCKET:
        if (isset($host) || isset($port)) {
          throw new RuntimeException("Host/port shouldn't be set when connecting to db over unix socket.");
        }
        if (!isset($unix_socket)) {
          throw new RuntimeException("Unix socket must be set when connecting to db over unix socket.");
        }

        $this->unixSocket = $unix_socket;
        break;  
      default:
        throw new RuntimeException("Invalid db connection type.");
    }

    $this->userName = $user_name;
    $this->password = $password;
    $this->charSet = $char_set;
    $this->dbName = $db_name;
    $this->dbTier = $db_tier;
    $this->dbNameWithTier = DbTiers::genDbNameWithTier($db_name, $db_tier);
  }

  protected function genDsnMap() {
    $dsn_map = array(
      self::DSN_DBNAME_KEY => $this->dbNameWithTier,
    );

    // Specify db connection over unix socket or host/port
    switch ($this->connectionType) {
      case MySqlConnectionType::REMOTE_HOST:
        $dsn_map[self::DSN_PORT_KEY] = $this->port;
      case MySqlConnectionType::LOCALHOST: /* Default port */
        $dsn_map[self::DSN_HOST_KEY] = $this->hostName;
        break;
      case MySqlConnectionType::UNIX_SOCKET:
        $dsn_map[self::DSN_UNIX_SOCKET_KEY] = $this->unixSocket;
        break;
      default:
        throw new RuntimeException("Invalid MySql connection type.");  
    }

    return $dsn_map;
  }

  public function getUserName() {
    return $this->userName;
  }

  public funcion getPassword() {
    return $this->password;
  }

  public function getOptions() {
    return array(
      PDO::MYSQL_ATTR_INIT_COMMAND => MySqlPdoConfig::genPdoCharSetStr($this->charSet),
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => false,
    );
  }
}

final class MySqlPdoConfigBuilder extends Builder {
  
  private
    $connectionType,
    $hostName,
    $port,
    $unixSocket,
    $userName,
    $password,
    $charSet,
    $dbName,
    $dbTier;

  public function build() {
    return new MySqlPdoConfig(
      $this->connectionType,
      $this->host,
      $this->port,
      $this->unixSocket,
      $this->username,
      $this->password,
      $this->charSet,
      $this->dbName,
      $this->dbTier
    ); 
  }

  public function setConnectionType($connection_type) {
    $this->connectionType = $connection_type;
    return $this;
  }

  public function setHost($host) {
    $this->host = $host;
    return $this;
  }

  public function setPort($port) {
    $this->port = $port;
    return $this;
  }

  public function setUnixSocket($unix_socket) {
    $this->unixSocket = $unix_socket;
    return $this;
  }

  public function setUserName($username) {
    $this->userName = $username;
    return $this;
  }

  public function setPassword($password) {
    $this->password = $password;
    return $this;
  }
  
  public function setCharSet($char_set) {
    $this->charSet = $char_set;
    return $this;
  }

  public function setDbName($db_name) {
    $this->dbName = $dbName;
    return $this;
  }

  public function setTier($db_tier) {
    $this->dbTier = $db_tier;
    return $this; 
  }
}
