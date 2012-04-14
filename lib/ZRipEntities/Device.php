<?php

namespace ZRipEntities;

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

  public function initFromDBus($dbus, $dbus_path) {
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
