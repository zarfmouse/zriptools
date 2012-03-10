<?php

namespace ZRipTasks;
use DBus;
use DBusSignal;
use DBusArray;
use DBusObjectPath;
use Exception;

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
      $device = new Device($this->dbus, $dbus_path);
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
	  $device = new Device($this->dbus, $dbus_path);
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
    $detectTime = $device->getDeviceMediaDetectiontime();
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
  
class Device {
  private $DeviceIsMediaChangeDetected;
  private $DeviceIsRemovable;
  private $DriveVendor;
  private $DriveModel;
  private $DriveRevision;
  private $DeviceFile;
  private $OpticalDiscNumTracks;
  private $OpticalDiscNumAudioTracks;
  private $OpticalDiscNumSessions;
  private $DriveMedia;
  private $DeviceSize;
  private $DeviceMediaDetectionTime;
  private $IdLabel;
  private $IdType;

  public function __construct($dbus, $dbus_path) {
    $device = $dbus->createProxy('org.freedesktop.UDisks',
				 $dbus_path,
				 "org.freedesktop.DBus.Properties");
    $all = $device->GetAll("org.freedesktop.UDisks.Device")->getData();
    $devinfo = array();
    foreach($all as $key => $val) {
      $val = $val->getData();
      if($val instanceof DBusArray) {
	$val = $val->getData();
      }
      if($val instanceof DBusObjectPath) {
	$val = $val->getData();
      }
      if(is_array($val) and count($val) == 1) {
	$val = $val[0];
      }
      if(property_exists($this, $key)) {
	$this->{$key} = $val;
      }
    }
  }

  public function getDeviceIsMediaChangeDetected() {
    return $this->DeviceIsMediaChangeDetected;
  }
  public function getDeviceIsRemovable() {
    return $this->DeviceIsRemovable;
  }
  public function getDriveVendor() {
    return $this->DriveVendor;
  }
  public function getDriveModel() {
    return $this->DriveModel;
  }
  public function getDriveRevision() {
    return $this->DriveRevision;
  }
  public function getDeviceFile() {
    return $this->DeviceFile;
  }
  public function getOpticalDiscNumTracks() {
    return $this->OpticalDiscNumTracks;
  }
  public function getOpticalDiscNumAudioTracks() {
    return $this->OpticalDiscNumAudioTracks;
  }
  public function getOpticalDiscNumSessions() {
    return $this->OpticalDiscNumSessions;
  }
  public function getDriveMedia() {
    return $this->DriveMedia;
  }
  public function getDeviceSize() {
    return $this->DeviceSize;
  }
  public function getDeviceMediaDetectionTime() {
    return $this->DeviceMediaDetectionTime;
  }
  public function getIdLabel() {
    return $this->IdLabel;
  }
  public function getIdType() {
    return $this->IdType;
  }

  public function hasDisc() {
    if($this->getDeviceMediaDetectionTime() > 0 and 
       $this->getDeviceSize() > 0) {
      return true;
    } else {
      return false;
    }
  }

  public function isOptical() {
    if($this->getDeviceIsMediaChangeDetected() and 
       $this->getDeviceIsRemovable()) {
      return true;
    } else {
      return false;
    }
  }

  public function isCD() {
    if(preg_match('/^optical_cd/', $this->getDriveMedia())) {
      return true;
    } else {
      return false;
    }
  }
  
  public function isDVD() {
    if(preg_match('/^optical_dvd/', $this->getDriveMedia())) {
      return true;
    } else {
      return false;
    }
  }

  public function isAudioCD() {
    if($this->isCD() and 
       $this->getOpticalDiscNumAudioTracks() > 0 and
       $this->getOpticalDiscNumSessions() == 1) {
      return true;
    } else {
      return false;
    }
  }

  public function isDataCD() {
    if($this->isCD() and 
       $this->getOpticalDiscNumSessions() == 1 and
       $this->getIdType() == 'iso9660') {
      return true;
    } else {
      return false;
    }
  }
  
  public function isMultiModeCD() {
    if($this->isCD() and 
       $this->getOpticalDiscNumAudioTracks() > 0 and
       $this->getOpticalDiscNumSessions() > 1) {
      return true;
    } else {
      return false;
    }
  }
  
  public function isVideoDVD() {
    if($this->isDVD() and
       $this->getIdType() == 'udf') {
      return true;
    } else {
      return false;
    }
  }

  public function isDataDVD() {
    if($this->isDVD() and
       $this->getIdType() == 'iso9660') {
      return true;
    } else {
      return false;
    }
  }

}