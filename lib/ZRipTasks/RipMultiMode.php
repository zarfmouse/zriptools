<?php

namespace ZRipTasks;
use Exception;
use ZRipEntities\Device;
use ZRipEntities\RipDataSession;

class RipMultiMode extends RipAudio {
  private $entity;
  private $path;
  private $success = false;

  public function __construct(Device $device) {
    parent::__construct($device);
    $uuid = $this->getUUID();
    $path = $this->getPath();
    $entity = new RipDataSession;
    $entity->setRipAudio($this->getRipAudio());
    $entity->setPath("$path/${uuid}.data_session");
    $entity->setLog("$path/$uuid.data_session.log");
    $entity->setUuid($uuid);
    $entity->save();
    $this->entity = $entity;
  }

  public function stop() {
    parent::stop();
  }

  public function cleanup2() {
    $dev = $this->getRipAudio()->getDevice()->getDeviceFile();
    $dest = $this->entity->getPath();
    $log = $this->entity->getLog();
    $mnt = preg_replace('/dev/', 'mnt', $dev);
    system("/bin/umount $dev >/dev/null 2>&1");
    if((!$this->success) || (!file_exists($log)) || (!file_exists($dest))) {
	if(file_exists($dest)) {
	  system("chmod -R u+w $dest");
	  system("rm -rf $dest/*");
	}
	unlink($log);
    }
    system("eject $dev 2>/dev/null");
  }

  public function run() {
    $dev = $this->getRipAudio()->getDevice()->getDeviceFile();
    $dest = $this->entity->getPath();
    $log = $this->entity->getLog();
    $mnt = preg_replace('/dev/', 'mnt', $dev);

    register_shutdown_function(array($this, 'cleanup2'));

    system("/bin/umount $dev >/dev/null 2>&1");
    system("/bin/mount $dev");
    if(file_exists($dest)) {
      system("chmod -R u+w $dest");
      system("rm -rf $dest/*");
    } else {
      mkdir($dest, 0755, true);
    }

    $wallclock_start = microtime(true);
    $descriptorspec = [
		       0 => array("pipe", "r"),
		       1 => array("pipe", "w"),
		       ];
    
    $resource = proc_open("/usr/bin/rsync --progress -r $mnt/ $dest/ 2>&1", $descriptorspec, $pipes);
    $proc_info = proc_get_status($resource);
    $this->pid = $proc_info['pid'];
    $handle = $pipes[1];
    $buffer = '';
    $log_data = '';
    $bytes_transferred = 0;
    $bytes_total = intval(`du -sb $mnt`);
    while(($char = fgetc($handle)) !== FALSE) {
      if(isset($log)) {
	$log_data .= $char;
      }
      if($char == "\r" || $char == "\n") {
	if(preg_match('/^\s*([0-9]+)\s+([0-9]+)\%/', $buffer, $matches)) {
	  $bytes_partial = $matches[1];
	  $percent = $matches[2];
	  $bytes_so_far = $bytes_transferred + $bytes_partial;
	  $wallclock_current = microtime(true);
	  $rate = sprintf("% 3.1f", ($bytes_so_far /  ($wallclock_current - $wallclock_start))/(1024*1024));
	  if($rate > 0) {
	    $secs_remaining = ($bytes_total-$bytes_so_far)/($rate*1024*1024);
	  } else {
	    $secs_remaining = $bytes_total / 1500000;
	  }
	  $time_remaining = sprintf("%02dm%02ds", intval($secs_remaining/60), ($secs_remaining%60));		      
	  $total_percent = ($bytes_so_far / $bytes_total) * 100;
	  if($total_percent > 100) {
	    $total_percent = 100;
	  }
	  $this->setProgress($total_percent, "Data: ${rate}Mb/s $time_remaining");
	  if($percent == 100) {
	    $bytes_transferred += $bytes_partial;
	  }
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
    system("/bin/umount $dev");
    if($status != 0) {
      exit;
    }
    $this->setProgress(100, "${rate}Mb/s 00m00s");
    $this->entity->setSize($bytes_total);
    $this->entity->setSpeed($rate);
    $this->success = true;
    parent::run();
  }

}
