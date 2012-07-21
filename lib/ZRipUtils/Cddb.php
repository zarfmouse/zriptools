<?php

namespace ZRipUtils;
use Exception;

class Cddb {
  private $full;
  private $pick;
  private $data;
  private $options;

  static public $server = 'freedb.freedb.org';

  public function __construct() {
  }

  public function setFull($full) {
    $this->full = $full;
  }

  public function setPick($pick) {
    $this->pick = $pick;
  }

  public function getFull() {
    if(is_null($this->full))
      throw new CddbFullNotSetException();
    return $this->full;
  }

  public function getOptions() {
    $server = self::$server;
    if(is_null($this->options)) {
      $full = $this->getFull();
      $cddb_options = `/usr/bin/cddbcmd -m http -l 6 -h $server cddb query $full`;
      $this->options = explode("\n", $cddb_options);
    }
    return $this->options;
  }

  public function getPick() {
    if(is_null($this->pick))
      throw new CddbPickNotSetException();
    return $this->pick;
  }

  public function getData() {
    $server = self::$server;
    if(is_null($this->data)) {
      $pick = $this->getPick();
      list($category, $cddb, $rest) = explode(' ', $pick, 3);
      $this->data = `/usr/bin/cddbcmd -m http -l 6 -h $server cddb read $category $cddb`;
    }
    return $this->data;
  }
}

class CddbException extends Exception {};  
class CddbFullNotSetException extends CddbException {};
class CddbPickNotSetException extends CddbException {};

