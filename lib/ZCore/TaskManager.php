<?php

namespace ZCore;
use Doctrine\Common\Persistence\PersistentObject;

class TaskManager {
  private $progressMonitor;
  private $pids;

  public function __construct() {
    $this->pids = array();
    pcntl_signal(SIGCHLD, array($this,"reaper"));
  }

  public function setProgressMonitor(ProgressMonitor $progressMonitor) {
    $this->progressMonitor = $progressMonitor;
  }

  public function run(Task $task) {
    $uuid = $task->getUUID();
    $this->progressMonitor->init($uuid);
    PersistentObject::getObjectManager()->getConnection()->close();
    MemcacheSingleton::disconnect();
    $pid = pcntl_fork();
    if($pid == -1) {
      die('could not fork');
    } else if($pid) {
      // parent
      $this->pids[$uuid] = $pid;
    } else {
      // child
      // The child shouldn't run our reaper. 
      pcntl_signal(SIGCHLD, SIG_DFL);
      $memcache = MemcacheSingleton::get();
      $progressMonitor = $this->progressMonitor;
      $task->registerProgressListener(function($p, $s) use ($uuid, $progressMonitor, $task, $memcache) { 
	  $progressMonitor->update($uuid, $p, $s, 'RipAudio');
	  if($memcache->get("KILL-$uuid")) {
	    $task->stop();
	  }
	});
      $task->run();
      $memcache->delete("KILL-$uuid");
      exit;
    }
  }

  public function reaper() {
    // Only reap what we've sown.
    foreach($this->pids as $uuid => $pid) {
      $result = pcntl_waitpid($pid, $status, WNOHANG);
      if($result == $pid) {
	$status = pcntl_wexitstatus($status);
	if($status != 0) {
	  print "$uuid exited with status $status.\n";
	}
	MemcacheSingleton::get()->delete("KILL-$uuid");
	unset($this->pids[$uuid]);
	$this->progressMonitor->remove($uuid);
      }
    }
  }
}
