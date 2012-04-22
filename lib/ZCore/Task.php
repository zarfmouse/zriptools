<?php

namespace ZCore;

abstract class Task {
  private $arguments;
  private $listeners;
  private $uuid;
  protected $pid;
  private $stopped;

  public function __construct() {
    $this->listeners = array();
    $uuid = `/usr/bin/uuid`;
    $this->uuid = trim($uuid);
  }

  public function getUUID() {
    return $this->uuid;
  }

  abstract public function run();

  public function stop() {
    if($this->stopped)
      return;
    $ppid = $this->pid;
    $uuid = $this->getUUID();
    if($ppid > 0) {
      $pids = `ps -ef| awk '$3 == '${ppid}' { print $2 }'`;
      foreach(explode("\n", $pids) as $pid) {
	trim($pid);
	if($pid > 0) {
	  print "Killing $pid for $uuid.\n";
	  posix_kill($pid, SIGTERM);
	}
      }
      $this->stopped = true;
    }
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
