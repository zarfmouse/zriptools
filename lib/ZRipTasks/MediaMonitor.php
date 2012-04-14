<?php

namespace ZRipTasks;
use DBus;
use DBusSignal;
use Exception;
use ZRipEntities\Device;
use Doctrine\Common\Persistence\PersistentObject;

class MediaMonitor {
  private $audioCDAction;
  private $multiModeCDAction;
  private $dataCDAction;
  private $videoDVDAction;
  private $dataDVDAction;
  private $ejectAction;
  private $dbus;
  private $lastDetectedTime; 

  public function __construct() {
    $this->dbus = new DBus(Dbus::BUS_SYSTEM);
    $this->lastDetectedTime = array();
  }

  public function registerAudioCDAction($callback) {
    $this->audioCDAction = $callback;
  }
  public function registerMultiModeCDAction($callback) {
    $this->multiModeCDAction = $callback;
  }
  public function registerDataCDAction($callback) {
    $this->dataCDAction = $callback;
  }
  public function registerVideoDVDAction($callback) {
    $this->videoDVDAction = $callback;
  }
  public function registerDataDVDAction($callback) {
    $this->dataDVDAction = $callback;
  }
  public function registerEjectAction($callback) {
    $this->ejectAction = $callback;    
  }
  public function run() {
    $this->scan();
    $this->loop();
  }

  private function scan() {
    $devices = $this->dbus->createProxy('org.freedesktop.UDisks',
					"/org/freedesktop/UDisks",
					"org.freedesktop.UDisks");
    foreach($devices->EnumerateDevices()->getData() as $obj) {
      $dbus_path = $obj->getData();
      $device = new Device();
      $device->initFromDBus($this->dbus, $dbus_path);
      if($device->isOptical() and $device->hasDisc()) {
	if($this->stateChanged($device)) {
	  $this->selectAction($device);
	}
      }
    }
  }
  
  private function loop() {
    $interface = 'org.freedesktop.UDisks';
    $method = 'DeviceChanged';
    $this->dbus->addWatch($interface, $method);

    do {
      $signal = $this->dbus->waitLoop(1000);
      if($signal instanceof DbusSignal) {  
	if($signal->matches($interface, $method)) {
	  $dbus_path = $signal->getData()->getData();
	  $device = new DeviceEntity();
	  $device->initFromDBus($this->dbus, $dbus_path);
	  if($device->isOptical()) {
	    if($this->stateChanged($device)) {
	      if($device->hasDisc()) {
		$this->selectAction($device);
	      } else {
		$this->runAction($this->ejectAction, $device);
	      }
	    }
	  }
	}
      }
    } while ( true );
  }

  private function stateChanged($device) {
    $detectTime = $device->getDeviceMediaDetectionTime()->getTimestamp();
    $dev= $device->getDeviceFile();
    if((!array_key_exists($dev, $this->lastDetectedTime)) or
       $this->lastDetectedTime[$dev] != $detectTime) {
      $this->lastDetectedTime[$dev] = $detectTime;
      return true;
    } else {
      return false;
    }
  }

  private function selectAction($device) {
    if($device->isAudioCD()) {
      $this->runAction($this->audioCDAction, $device);
    } else if($device->isDataCD()) {
      $this->runAction($this->dataCDAction, $device);
    } elseif($device->isMultiModeCD()) {
      $this->runAction($this->multiModeCDAction, $device);
    } else if($device->isVideoDVD()) {
      $this->runAction($this->videoDVDAction, $device);
    } else if($device->isDataDVD()) {
      $this->runAction($this->dataDVDAction, $device);
    }
  }

  private function runAction($callback, $device) {
    if(isset($callback) and is_callable($callback)) {
      call_user_func($callback, $device);
    }
  }
}
  
