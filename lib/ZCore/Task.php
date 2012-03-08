<?php

namespace ZCore;

abstract class Task {
  private $arguments;
  private $listeners;

  final public function __construct() {
    $this->listeners = array();
  }

  final protected function setProgress($percent) {
    foreach($this->listeners as $listener) {
      call_user_func($listener, $percent);
    }
  }

  final public function registerProgressListener($callback) {
    $this->listeners[] = $callback;
    return $this;
  }

  final public function setArguments($arguments) {
    $this->arguments = $this->help()->validate($arguments);
    return $this;
  }

  abstract public function help();
  abstract public function run();
  abstract public function report();
}
