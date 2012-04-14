<?php

namespace ZRipEntities;
use DBus;
use DBusSignal;
use DBusArray;
use DBusObjectPath;
use Doctrine\Common\Persistence\PersistentObject;

/**
 * @Entity
 */
class DeviceEntity extends PersistentObject {
  /** 
   * @Id 
   * @Column(type="integer") 
   * @GeneratedValue 
   **/
  protected $id;

  /** 
   * @Column(type="string") 
   **/
  protected $deviceIsMediaChangeDetected;

  /** 
   * @Column(type="string") 
   **/
  protected $deviceIsRemovable;

  /** 
   * @Column(type="string") 
   **/
  protected $driveVendor;

  /** 
   * @Column(type="string") 
   **/
  protected $driveModel;

  /** 
   * @Column(type="string") 
   **/
  protected $driveRevision;

  /** 
   * @Column(type="string") 
   **/
  protected $deviceFile;

  /** 
   * @Column(type="string") 
   **/
  protected $opticalDiscNumTracks;

  /** 
   * @Column(type="string") 
   **/
  protected $opticalDiscNumAudioTracks;

  /** 
   * @Column(type="string") 
   **/
  protected $opticalDiscNumSessions;

  /** 
   * @Column(type="string") 
   **/
  protected $driveMedia;

  /** 
   * @Column(type="string") 
   **/
  protected $deviceSize;

  /** 
   * @Column(type="string") 
   **/
  protected $deviceMediaDetectionTime;

  /** 
   * @Column(type="string") 
   **/
  protected $idLabel;

  /** 
   * @Column(type="string") 
   **/
  protected $idType;

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
      $key = lcfirst($key);
      if(property_exists($this, $key)) {
	$this->{$key} = $val;
      }
    }
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
