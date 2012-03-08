<?php

namespace ZRipTasks;
use ZCore\Task;
use ZCore\Help;

class Example extends Task {
  public function help() {
    $help = new Help;
    $help->addArgument('device', 
		       true, 
		       'Device path (e.g. /dev/scd0)', 
		       function($dev) {
			 return preg_match('(^/dev/.*)', $dev) ? true : false;
		       });
    return $help;
  }

  public function run() {
    for($i=0;$i<=100;$i++) {
      $this->setProgress($i, 'Pass #1');
      usleep(100000);
    }
    for($i=0;$i<=100;$i++) {
      $this->setProgress($i, 'Pass #2');
      usleep(50000);
    }
  }

  public function report() {
    return array('did' => 'some stuff');
  }
}