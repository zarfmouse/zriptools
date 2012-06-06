<?php

namespace ZRipTasks;

use ZCore\Task;
use Exception;
use Datetime;

use ZRipEntities\RipAudio as RipAudioEntity;
use ZRipEntities\EncodeFlac as EncodeFlacEntity;

class EncodeFlac extends Task {
  private $entity;

  public function __construct(RipAudioEntity $ripAudio) {
    parent::__construct();
    $uuid = $this->getUUID();

    $toc = $ripAudio->getToc();
    $cue = dirname($toc).'/'.basename($toc, '.toc').".cue";
    
    $pcm = $ripAudio->getPcm();
    $flac = dirname($pcm).'/'.basename($pcm, '.pcm').".flac";

    $entity = new EncodeFlacEntity();
    $entity->setUuid($uuid);
    $entity->setRipAudio($ripAudio);
    $entity->setFlac($flac);
    $entity->setCue($cue);
    $entity->setComplete(false);
    $entity->setSuccess(false);
    $entity->setStartTime(new DateTime('now'));
    $entity->save();
    $this->entity = $entity;
  }

  public function cleanup() {
    $flac = $this->entity->getFlac();
    $cue = $this->entity->getCue();

    $this->entity->setComplete(true);
    if((!$this->entity->getSuccess()) || (!file_exists($cue)) || (!file_exists($flac))) {
      $this->entity->setSuccess(false);
      /*
      if(file_exists($flac))
	unlink($flac);
      if(file_exists($cue))
	unlink($cue);
      */
    }
    $this->entity->save();
  }
  
  public function run() {
    register_shutdown_function(array($this, 'cleanup'));
    $flac = $this->entity->getFlac();
    $cue = $this->entity->getCue();
    print "Encoding $flac.\n";
    
    $this->entity->setSuccess(true);
    $this->entity->save();
  }

}
  
class EncodeFlacException extends Exception {};
