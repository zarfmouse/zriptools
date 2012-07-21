<?php

namespace ZRipTasks;

use ZCore\Task;
use Exception;

use ZRipEntities\RipAudio as RipAudioEntity;
use ZRipUtils\Cddb;

class CddbRead extends Task {
  private $entity;
  private static $mark = "Fetching...";

  public function __construct(RipAudioEntity $ripAudio) {
    parent::__construct();
    $this->entity = $ripAudio->getMeta();
    $this->entity->setCddbData(self::$mark);
    $this->entity->save();
  }

  public function cleanup() {
    register_shutdown_function(array($this, 'cleanup'));
    $data = $this->entity->getCddbData();
    if($data == self::$mark) {
      $this->entity->setCddbData(null);
      $this->entity->save();
    }
  }

  public function run() {
    $pick = $this->entity->getCddbPick();
    $this->setProgress(0, $pick);
    $cddb = new Cddb;
    $cddb->setPick($pick);
    $this->entity->setCddbData($cddb->getData());
    $this->entity->save();
    $this->setProgress(100, $pick);
    sleep(1);
  }
}
  



