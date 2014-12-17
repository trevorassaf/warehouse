<?php

// -- DEPENDENCIES
require_once(dirname(__FILE__)."/ModelObject.php");

class WhUserError {
  // Unsets
  const UFN = "Unset first name";
  const ULN = "Unset last name";
  const UPW = "Unset password";
  const UUN = "Unset username";
  const UEM = "Unset email";

  // Duplicates
  const DPW = "Duplicate password";
  const DUN = "Duplicate username";
  const DEM = "Duplicate email";
}

class WhUser extends ModelObject {

  // Db keys 
  const FIRST_NAME_KEY = "first_name";
  const LAST_NAME_KEY = "last_name";
  const PASSWORD_KEY = "password";
  const USERNAME_KEY = "username";
  const EMAIL_KEY = "email";

  protected static $uniqueKeys = array(self::PASSWORD_KEY, self::USERNAME_KEY, self::EMAIL_KEY);

  protected static $tableName = "WhUsers";

  private
    $firstName,
    $lastName,
    $password,
    $username,
    $email;

  public static function create(
    $first_name,
    $last_name,
    $password,
    $username,
    $email
  ) {
    return static::createObject(
      array(
        self::FIRST_NAME_KEY => $first_name,
        self::LAST_NAME_KEY => $last_name,
        self::PASSWORD_KEY => $password,
        self::USERNAME_KEY => $username,
        self::EMAIL_KEY => $email,
      )
    );
  }

  public static function fetchByPassword($password) {
    return static::getObjectByUniqueKey(self::PASSWORD_KEY, $password);
  }

  public static function fetchByUsername($username) {
    return static::getObjectByUniqueKey(self::USERNAME_KEY, $username);
  }

  public static function fetchByEmail($email) {
    return static::getObjectByUniqueKey(self::EMAIL_KEY, $email);
  }

  // Override
    protected function getDbFields() {
      return array(
        self::FIRST_NAME_KEY => $this->firstName,
        self::LAST_NAME_KEY => $this->lastName,
        self::PASSWORD_KEY => $this->password,
        self::USERNAME_KEY => $this->username,
        self::EMAIL_KEY => $this->email,
      );
    }

  protected function initInstanceVars($params) {
    $this->firstName = $params[self::FIRST_NAME_KEY];
    $this->lastName = $params[self::LAST_NAME_KEY];
    $this->password = $params[self::PASSWORD_KEY];
    $this->username = $params[self::USERNAME_KEY];
    $this->email = $params[self::EMAIL_KEY];
  }

  protected function validateOrThrow() {
    // Unsets
    if (!isset($this->firstName)) {
      throw new InvalidObjectStateException(WhUserError::UFN);
    }

    if (!isset($this->lastName)) {
      throw new InvalidObjectStateException(WhUserError::ULN);
    }

    if (!isset($this->password)) {
      throw new InvalidObjectStateException(WhUserError::UPW);
    }

    if (!isset($this->username)) {
      throw new InvalidObjectStateException(WhUserError::UUN);
    }

    if (!isset($this->email)) {
      throw new InvalidObjectStateException(WhUserError::UEM);
    }

    // Duplicates
    $user = WhUser::fetchByUsername($this->username);
    if (isset($user) && $user->getId() != $this->getId()) {
      throw new InvalidObjectStateException(WhUserError::DUN);
    }
    
    $user = WhUser::fetchByPassword($this->password);
    if (isset($user) && $user->getId() != $this->getId()) {
      throw new InvalidObjectStateException(WhUserError::DPW);
    }

    $user = WhUser::fetchByEmail($this->email);
    if (isset($user) && $user->getId() != $this->getId()) {
      throw new InvalidObjectStateException(WhUserError::DEM);
    }
  }

  public function getFirstName() {
    return $this->firstName;
  }

  public function getLastName() {
    return $this->lastName;
  }

  public function getPassword() {
    return $this->password;
  }

  public function getUsername() {
    return $this->username;
  }

  public function getEmail() {
    return $this->email;
  }
}

