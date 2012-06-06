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
    $log = dirname($pcm).'/'.basename($pcm, '.pcm').".flacencode.log.txt";

    $entity = new EncodeFlacEntity();
    $entity->setUuid($uuid);
    $entity->setRipAudio($ripAudio);
    $entity->setFlac($flac);
    $entity->setCue($cue);
    $entity->setLog($log);
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
      if(file_exists($flac))
	unlink($flac);
      if(file_exists($cue))
	unlink($cue);
    }
    $this->entity->save();
  }
 
  private function createCue() {
    $cue = $this->entity->getCue();
    $toc = $this->entity->getRipAudio()->getToc();
    if(!file_exists($toc))
      throw new TocNotFoundException($toc);
    if(file_exists($cue))
      throw new CueExistsException($cue);
    system("toc2cue $toc $cue >/dev/null 2>&1");
    if(!file_exists($cue))
      throw new CreateCueFailedException($cue);
  }

  private function encodeFlac() {
    $pcm = $this->entity->getRipAudio()->getPcm();
    $flac = $this->entity->getFlac();
    $cue = $this->entity->getCue();
    $log = $this->entity->getLog();

    if(file_exists($log))
      unlink($log);

    $descriptorspec = [
		       0 => array("pipe", "r"),
		       1 => array("pipe", "w"),
		       ];
    $resource = proc_open("flac -V --best --endian=big --sign=signed --channels=2 --bps=16 --sample-rate=44100 --cuesheet=$cue $pcm 2>&1 >$log", $descriptorspec, $pipes, dirname($log));
    // TODO: DELETE input file.
    $proc_info = proc_get_status($resource);
    $this->pid = $proc_info['pid'];
    
    $handle = $pipes[1];
    $buffer = '';
    $log_data = '';
    $wallclock_start = microtime(true);
    $previous_percent = -1;
    $verify = false;
    while(($char = fgetc($handle)) !== FALSE) {
      if(isset($log)) {
	$log_data .= $char;
      }
      if($char == "\r" || $char == "\n") {
	if(preg_match('/: ([0-9\.]+)\% complete/', $buffer, $matches)) {
	  $percent = $matches[1];
	  $wallclock_current = microtime(true);
	  if($percent > 0) {
	    $secs_elapsed = $wallclock_current - $wallclock_start;
	    $secs_remaining = ($secs_elapsed / ($percent/100.0)) - $secs_elapsed;
	    $time_remaining = sprintf("%02dm%02ds", intval($secs_remaining/60), ($secs_remaining%60));
	  } else {
	    $time_remaining = "--m--s";
	  }
	  if($percent != $previous_percent) {
	    $this->setProgress($percent, $time_remaining);
	    $previous_percent = $percent;
	  }
	} else if(preg_match('/Verify OK, wrote ([0-9]+) bytes, ratio=([0-9\.]+)/', $buffer, $matches)) {
	  $bytes = $matches[1];
	  $ratio = $matches[2];
	  $verify = true;
	}
	$buffer = '';
      } else {
	$buffer .= $char;
      }
    }
    $status = proc_close($resource);
    if(isset($log) and !empty($log_data)) {
      file_put_contents($log, $log_data);
    }
    if($status != 0)
      throw new BadStatusException($status);
    if(!$verify)
      throw new VerificationFailedException();
    
    $size = filesize($flac);
    if($size != $bytes)
      throw new WrongSizeException("$size != $bytes");
    $md5 = md5_file($flac);

    $audio_bytes = $this->entity->getRipAudio()->getSize();
    $audio_seconds = $audio_bytes / 176400;
    $speed = sprintf("%3.1f", $audio_seconds / $secs_elapsed);

    $this->entity->setSuccess(true);
    $this->entity->setSeconds($secs_elapsed);
    $this->entity->setRatio($ratio);
    $this->entity->setSpeed($speed);
    $this->entity->setSize($size);
    $this->entity->setMd5($md5);
    $this->entity->setEndTime(new DateTime('now'));
    $this->entity->save();

    // If we got this far we can safely ditch the PCM file.
    $pcm = $this->entity->getRipAudio()->getPcm();
    unlink($pcm);
  }
  
  public function run() {
    register_shutdown_function(array($this, 'cleanup'));

    $this->createCue();
    $this->encodeFlac();
    
    $this->entity->setSuccess(true);
    $this->entity->save();
  }

}
  
class EncodeFlacException extends Exception {};
class TocNotFoundException extends EncodeFlacException {};
class CueExistsException extends EncodeFlacException {};
class CreateCueFailedException extends EncodeFlacException {};
class BadStatusException extends EncodeFlacException {};
class VerificationFailedException extends EncodeFlacException {};
class WrongSizeException extends EncodeFlacException {};



