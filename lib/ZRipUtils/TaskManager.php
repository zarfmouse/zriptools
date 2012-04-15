<?php

namespace ZRipUtils;
use ZCore\Task;

class TaskManager {
  private $progressMonitor;
  private $pids;

  public function __construct() {
    $this->pids = array();
  }

  public function setProgressMonitor($progressMonitor) {
    $this->progressMonitor = $progressMonitor;
  }

  public function run(Task $task) {
    $uuid = $task->getUUID();
    $pid = pcntl_fork();
    if($pid == -1) {
      die('could not fork');
    } else if($pid) {
      // parent.
      $this->pids[$uuid] = $pid;
    } else {
      // child
      $progressMonitor = $this->progressMonitor;
      $progressMonitor->init($uuid);
      $task->registerProgressListener(function($p, $s) use ($uuid, $progressMonitor) { 
	  $progressMonitor->update($uuid, $p, $s, 'RipAudio');
	});
      $task->run();    
      $progressMonitor->remove($uuid);
      exit;
    }
  }

  public function reaper() {
    pcntl_waitpid(0, &$status, WNOHANG);
  }

}
