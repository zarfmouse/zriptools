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

  /**
   * This function should return a ZCore\Help object.
   **/
  abstract public function help();

  /**
   * This is a long running function that does the work of the task
   * and repeatedly calls setProgress. This function should not modify
   * the state of the Task class. Set state by updating the database
   * or filesystem as a side effect.
   * 
   * The intention is that the run() function may be run in a separate
   * forked process from the constructor and report() functions.
   **/
  abstract public function run();

  /**
   * This should return the "return value" of the task. Typically just
   * a nested array() structure. Because run() may run in a forked
   * process and report may run in the parent, report may rely on the
   * arguments passed to the Task and may look in the database or
   * filesystem for other state set by the run() function.
   **/
  abstract public function report();
}
