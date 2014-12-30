<?php

class FileSystemAssetException extends Exception {
  
  private $path;

  public function __construct($path) {
    $this->path = $path;
  }

  public function getPath() {
    return $this->path;
  }
} 

class BadFileAssetException extends FileSystemAssetException {
  
  public function __toString() {
    "File does not exist: " . $this->getPath() . "\n";
  }
}

class CannotDeleteAssetException extends FileSystemAssetException {

  public function __toString() {
    "Cannot delete asset: " . $this->getPath() . "\n";
  }
}

interface AssetDeletor {

  /**
   * delete()
   * - Remove asset.
   */
  public function delete();
}

class LocalFileDeletor implements AssetDeletor {

  private $path;

  /**
   * __construct()
   * - Ctor for LocalFileDeletor
   */
  public function __construct($path) {
    // Fail because file does not exist
    if (!file_exists($path)) {
      throw new BadFileAssetException($path);
    }

    // Fail becuase file cannot be deleted
    if (!is_writable($path)) {
      throw new CannotDeleteFileException($path);
    }
    
    $this->path = $path;
  }

  /**
   * delete()
   * @override AssetDeletor
   */
  public function delete() {
    // Fail due to unsuccessful file deletion
    if (!unlink($this->path)) {
      throw new CannotDeleteFileException($this->path);
    } 
  }
}
