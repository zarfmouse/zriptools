<?php

namespace ZCore;
use Exception;

class BackgroundRunner {
  private $tasks;
  private $taskManager;
  private $flag;
  private static $maxTasks = 4;

  public function __construct() {
    $this->tasks = array();
    $this->flag = true;
  }

  public function setTaskManager($task_manager) {
    $this->taskManager = $task_manager;
  }

  public function registerTask($fetchDataCallback, $getTaskCallback) {
    $this->tasks[] = array('data' => $fetchDataCallback, 
			   'task' => $getTaskCallback);
  }

  public function sigint() {
    $this->flag = false;
    print "Caught signal...\n";
  }

  public function run() {
    pcntl_signal(SIGINT, array($this,"sigint"));
    while($this->flag) {
      foreach($this->tasks as $pair) {
	// TODO: don't run if we already have 4 tasks running. Instead
	// wait for a task to end.
	$data = $pair['data']();
	if(isset($data)) {
	  $task = $pair['task']($data);
	  // TODO: run the task via taskManager rather than syncronously.
	  $task->run();
	  // TODO: don't exit after running only one task.
	  exit();
	}
      }
    }
    print "Done.\n";
  }
}

