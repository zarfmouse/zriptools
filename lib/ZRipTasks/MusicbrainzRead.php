<?php

namespace ZRipTasks;

use ZCore\Task;
use Exception;

use ZRipEntities\RipAudio as RipAudioEntity;
use ZRipUtils\Musicbrainz;

class MusicbrainzRead extends Task {
  private $entity;
  private static $mark = "Fetching...";

  public function __construct(RipAudioEntity $ripAudio) {
    parent::__construct();
    $this->entity = $ripAudio->getMeta();
    $this->entity->setMusicbrainzData(self::$mark);
    $this->entity->save();
  }

  public function cleanup() {
    register_shutdown_function(array($this, 'cleanup'));
    $data = $this->entity->getMusicBrainzData();
    if($data == self::$mark) {
      $this->entity->setMusicBrainzData(null);
      $this->entity->save();
    }
  }

  public function run() {
    $release = $this->entity->getMusicbrainzRelease();
    $this->setProgress(0, $release);
    $mb = new Musicbrainz;
    $mb->setRelease($release);
    $this->entity->setMusicbrainzData($mb->getData());
    $this->entity->save();
    $this->setProgress(100, $release);
    sleep(1);
  }
}
  



