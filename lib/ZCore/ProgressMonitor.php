<?php

namespace ZCore;
use Dbus;
use DbusSignal;
use Memcached;

class ProgressMonitor {
  static private $initialized = false;
  static private $lastUpdate = 0;
  static private $dbus;
  static private $memcached;
  const UPDATE_INTERVAL = 0.5;
  const ID_FIELD = 'ProgressMonitorIds';

  static private function initialize() {
    if(!self::$initialized) {
      self::$lastUpdate = microtime(true);
      self::$dbus = new Dbus( Dbus::BUS_SYSTEM);
      self::$memcached = new Memcached();
      self::$memcached->addServer('localhost', 11211);
      self::$initialized = true;
    }
  }

  static public function init($id) {
    self::initialize();
    
    do {
      $ids = self::$memcached->get(self::ID_FIELD, null, $cas);
      if (self::$memcached->getResultCode() == Memcached::RES_NOTFOUND) {
        $ids = array($id => 1);
	self::$memcached->add(self::ID_FIELD, $ids);
      } else { 
        $ids[$id] = 1;
	self::$memcached->cas($cas, self::ID_FIELD, $ids);
      }   
    } while (self::$memcached->getResultCode() != Memcached::RES_SUCCESS);
    
    self::$memcached->set($id, array('percent' => 0,
				     'message' => '',
				     'type' => ''));
  }

  static public function remove($id) {
    self::initialize();
    do {
      $ids = self::$memcached->get(self::ID_FIELD, null, $cas);
      if (self::$memcached->getResultCode() != Memcached::RES_NOTFOUND) {
	unset($ids[$id]);
	self::$memcached->cas($cas, self::ID_FIELD, $ids);
      }   
    } while (self::$memcached->getResultCode() != Memcached::RES_SUCCESS);
    self::$memcached->delete($id);
  }

  static public function update($id, $percent, $message, $type) {
    self::initialize();
    $old = self::$memcached->get($id);
    $signal = false;
    $percent = round($percent, 1);
    if($percent > 100) 
      $percent = 100;
    if($percent < 0)
      $percent = 0;
    if($percent == 100 ||
       $percent == 0 ||
       abs($percent - $old['percent']) >= 1 ||
       ($old['message'] != $message &&
	(microtime(true) - self::$lastUpdate) > self::UPDATE_INTERVAL)) {
      $signal = true;
      self::$lastUpdate = microtime(true);
    }
    self::$memcached->set($id, array( 'percent' => $percent,
				      'message' => $message,
				      'type' => $type));
    if($signal)
      self::signal();
  }
  
  static private function signal() {
    $s = new DbusSignal(self::$dbus,
			'/us/zarfmouse/zriptools', 
			'us.zarfmouse.ZRipTools.ProgressMonitor', 
			'ProgressSignal'
			);
    $s->send();
  }
}



