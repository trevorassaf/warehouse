<?php

require_once(dirname(__FILE__)."/../util/Import.php");

abstract class CharSet extends Enum {
  
  // Char sets 
  const UTF8 = "utf8";
  const ASCII = "ascii";

  // Supported char sets
  protected static $SUPPORTED_TYPES = array(
    self::UTF8,
    self::ASCII,
  );
}

abstract class ConnectionType extends Enum {

  // Db connection types
  const LOCALHOST = 'localhost';
  const REMOTE_HOST = 'remote-host';
  const UNIX_SOCKET = 'unix-socket';

  // Supported db connection types
  protected static $SUPPORTED_TYPES = array(
    self::LOCALHOST,
    self::REMOTE_HOST,
    self::UNIX_SOCKET,
  );
}

class DbhConfig {
  
  private
    $connectionType,
    $host,
    $port,
    $unixSocket,
    $username,
    $password,
    $charSet,
    $dbName;

  public function __construct(
    $connection_type,
    $host,
    $port,
    $unix_socket,
    $username,
    $password,
    $char_set,
    $db_name
  ) {
    // Fail due to unset mandatory fields
    assert(isset($username));
    assert(isset($password));

    // Fail due to invalid db config
    if (!isset($connection_type)) {
      $connection_type = ConnectionType::LOCALHOST; 
    } else {
      ConnectionType::validateType($connection_type);
    }
    
    if (isset($unix_socket)) {
      assert(!isset($host) && !isset($port));
    } else if (isset($host)) {
      assert(!isset($unix_socket));
    }

    if (!isset($char_set)) {
      $char_set = CharSet::ASCII;
    } else {
      CharSet::validateType($char_set);
    }

    // Cache db config fields
    $this->connectionType = $connection_type;
    $this->host = $host;
    $this->port = $port;
    $this->unixSocket = $unixSocket;
    $this->username = $username;
    $this->password = $password;
    $this->charSet = $char_set;
    $this->dbName = $db_name;
  }

  public function getConnectionType() {
    return $this->connectionType;
  }

  public function getHost() {
    return $this->host;
  }

  public function getPort() {
    return $this->port;
  }

  public function getUnixSocket() {
    return $this->unixSocket;
  }

  public function getUsername() {
    return $this->username;
  }

  public function getPassword() {
    return $this->password;
  }

  public function getCharSet() {
    return $this->charSet;
  }

  public function getDbName() {
    return $this->dbName;
  }

  public function hasDbName() {
    return isset($this->dbName);
  }
}

class DbhConfigBuilder {

  private
    $connectionType,
    $host,
    $port,
    $unixSocket,
    $username,
    $password,
    $charSet,
    $dbName;

  public function __construct() {
    $this->connectionType = null;
    $this->host = null;
    $this->port = null;
    $this->unixSocket = null;
    $this->username = null;
    $this->password = null;
    $this->charSet = null;
    $this->dbName = null;
  }

  public function setConnectionType($connection_type) {
    ConnectionType::validateType($connection_type); 
    $this->connectionType = $connection_type;
    return $this;
  }

  public function setHost($host) {
    $this->host = $host;
    return $this;
  }

  public function setPort($port) {
    assert(is_int($port) && $port >= 0);
    $this->port = $port;
    return $this;
  }

  public function setUnixSocket($unix_socket) {
    $this->unixSocket = $unixSocket;
    return $this;
  }

  public function setUsername($username) {
    $this->username = $username;
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
    $this->dbName = $db_name;
    return $this;
  }

  public function build() {
    return new DbhConfig(
      $this->connectionType,
      $this->host,
      $this->port,
      $this->unixSocket,
      $this->username,
      $this->password,
      $this->charSet,
      $this->dbName
    );
  }
}
