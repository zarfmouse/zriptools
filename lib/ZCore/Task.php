<?php

namespace ZCore;

abstract class Task {
  private $arguments;
  private $listeners;

  public function __construct() {
    $this->listeners = array();
  }

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
