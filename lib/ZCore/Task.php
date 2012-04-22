<?php

namespace ZCore;

abstract class Task {
  private $arguments;
  private $listeners;
  private $uuid;

  public function __construct() {
    $this->listeners = array();
    $uuid = `/usr/bin/uuid`;
    $this->uuid = trim($uuid);
  }

  public function getUUID() {
    return $this->uuid;
  }

  abstract public function stop();
  abstract public function run();

  final protected function setProgress($percent, $status=null) {
    foreach($this->listeners as $listener) {
      call_user_func($listener, $percent, $status);
    }
  }

  final public function registerProgressListener($callback) {
    $this->listeners[] = $callback;
    return $this;
  }
}
