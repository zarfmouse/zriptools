<?php

namespace ZRipTasks;
use ZCore\Task;
use Exception;

class RipAudio extends Task {
  private $dev;
  private $pcm;
  private $toc;
  private $log;
  
  private static function tmp($path) {
    return "$path.tmp";
  }

  private static function time_to_frames($time) {
    list($m, $s, $f) = explode(':', $time);
    return ($m*60*75) + ($s*75) + $f;
  }

  public function __construct($dev, $pcm, $toc, $log=null) {
    parent::__construct();
    if(is_null($dev) || 
       ! (preg_match('(^/dev/.*)', $dev) && file_exists($dev))) 
      throw new InvalidDeviceException($dev);
    foreach(array($pcm, $toc, $log) as $path) {
      if(isset($path)) {
	if(! (is_dir(dirname($path)) && is_writable(dirname($path))))
	  throw new UnwritablePathException($path);
	if(file_exists($path))
	  throw new FileExistsException($path);
	if(file_exists(self::tmp($path)))
	  throw new FileExistsException(self::tmp($path));
      }
    }
    if(is_null($pcm) || is_null($toc)) {
      throw new InvalidArgumentsException();
    }
    $this->dev = $dev;
    $this->pcm = $pcm;
    $this->toc = $toc;
    $this->log = $log;
    $this->success = false;
  }
  
  private function rip($pcm, $toc, $log, $paranoia, $pass) {
    $dev = $this->dev;
    $wallclock_start = microtime(true);
    $handle = popen("/usr/bin/cdrdao read-cd --paranoia-mode $paranoia --device $dev --datafile $pcm $toc 2>&1", 'r');
    $buffer = '';
    $log_data = '';
    while(($char = fgetc($handle)) !== FALSE) {
      if(isset($log)) {
	$log_data .= $char;
      }
      if($char == "\r" || $char == "\n") {
	if(preg_match('/Copying audio tracks ([0-9]+)-([0-9]+): start ([0-9]+:[0-9]+:[0-9]+), length ([0-9]+:[0-9]+:[0-9]+)/', $buffer, $matches)) {
	  $first_track = $matches[1];
	  $last_track = $matches[2];
	  $start_time = $matches[3];
	  $length = $matches[4];
	  $total_frames = self::time_to_frames($length);
	} else if(isset($total_frames) && 
		  preg_match('/^([0-9]+:[0-9]+:[0-9]+)$/', $buffer, $matches)) {
	  $ripped_frames = self::time_to_frames($matches[1]);
	  $wallclock_current = microtime(true);
	  $rate = sprintf("% 3.1f", ($ripped_frames / 75) / ($wallclock_current - $wallclock_start));
	  if($rate > 0) {
	    $secs_remaining = ((($total_frames - $ripped_frames) / 75) / $rate);
	  } else {
	    $secs_remaining = $total_frames / 75;
	  }
	  $time_remaining = sprintf("%02dm%02ds", intval($secs_remaining/60), ($secs_remaining%60));
	  $this->setProgress(($ripped_frames / $total_frames) * 100, "Pass #$pass {$rate}x {$time_remaining}");
	}
	$buffer = '';
      } else {
	$buffer .= $char;
      }
    }
    $status = pclose($handle);
    if($status != 0) {
      exit;
    }
    if(isset($log) and !empty($log_data)) {
      file_put_contents($log, $log_data);
    }
    $this->setProgress(100, "Pass #$pass {$rate}x 00m00s");
  }

  public function cleanup() {
    $dev = $this->dev;
    $pcm = $this->pcm;
    $toc = $this->toc;
    $log = $this->log;

    if((!$this->success) || (!file_exists($pcm)) || (!file_exists($toc))) {
      if(file_exists($pcm))
	unlink($pcm);
      if(file_exists($toc))
	unlink($toc);
      if(file_exists($log))
	unlink($log);
    }
    if(file_exists("$pcm.2"))
      unlink("$pcm.2");
    if(file_exists("$toc.2"))
      unlink("$toc.2");
  }

  public function run() {
    $dev = $this->dev;
    $pcm = $this->pcm;
    $toc = $this->toc;
    $log = $this->log;

    register_shutdown_function(array($this, 'cleanup'));
    
    $this->rip($pcm, $toc, $log, 0, 1);
    $md51 = md5_file($pcm);
    $this->rip("$pcm.2", "$toc.2", null, 0, 2);
    $md52 = md5_file("$pcm.2");
    
    if($md51 != $md52) {
      $size = filesize($pcm);
      $diff_bytes = intval(shell_exec("/usr/bin/cmp -b -l $pcm $pcm.2 | wc -l"));
      $error = sprintf("% 2.4f", ($diff_bytes / $size) * 100);
      $this->rip($pcm, $toc, $log, 3, 3);
    } 
    $this->success = true;
  }

}
  
class RipAudioException extends Exception {};
class InvalidDeviceException extends RipAudioException {};
class UnwritablePathException extends RipAudioException {};
class FileExistsException extends RipAudioException {};
class InvalidArgumentsException extends RipAudioException {};
