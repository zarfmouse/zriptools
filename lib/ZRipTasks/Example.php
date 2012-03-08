<?php

namespace ZRipTasks;
use ZCore\Task;
use ZCore\Help;

class Foo extends Task {
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
    $this->setProgress(0);
    sleep(1);
    $this->setProgress(50);
    sleep(2);
    $this->setProgress(100);
  }

  public function report() {
    return array('did' => 'some stuff');
  }
}