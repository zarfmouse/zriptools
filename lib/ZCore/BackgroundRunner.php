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
	if($this->flag) {
	  if($this->taskManager->numTasks() < self::$maxTasks) {
	    $data = $pair['data']();
	    if(isset($data)) {
	      $task = $pair['task']($data);
	      $this->taskManager->run($task);
	    }
	  }
	}
      }
      sleep(1);
    }
    $this->flag = true;
    while($this->flag) {
      print "Waiting for death of children.\n";
      if($this->taskManager->numTasks() == 0)
	$this->flag = false;
      sleep(1);
    }
    print "Done.\n";
  }
}

