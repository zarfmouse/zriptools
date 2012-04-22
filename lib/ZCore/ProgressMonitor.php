<?php

namespace ZCore;
use Dbus;
use DbusSignal;
use Memcached;

class ProgressMonitor {
  private $lastUpdate = 0;
  private $dbus;
  private $memcached;
  const UPDATE_INTERVAL = .5;
  const ID_FIELD = 'ProgressMonitorIds';
  
  public function __construct() {
    $this->lastUpdate = microtime(true);
    $this->dbus = new Dbus( Dbus::BUS_SYSTEM);
    $this->memcached = MemcachedSingleton::get();
  }

  public function init($id) {
    do {
      $ids = $this->memcached->get(self::ID_FIELD, null, $cas);
      if ($this->memcached->getResultCode() == Memcached::RES_NOTFOUND) {
        $ids = array($id => 1);
	$this->memcached->add(self::ID_FIELD, $ids);
      } else { 
        $ids[$id] = 1;
	$this->memcached->cas($cas, self::ID_FIELD, $ids);
      }   
    } while ($this->memcached->getResultCode() != Memcached::RES_SUCCESS);
    
    $this->memcached->set($id, array('percent' => 0,
				     'message' => '',
				     'type' => ''));
    $this->signal();
  }

  public function remove($id) {
    do {
      $ids = $this->memcached->get(self::ID_FIELD, null, $cas);
      if($ids === false) {
	var_dump($this->memcached->getResultCode());
	sleep(1);
      } else {
	if ($this->memcached->getResultCode() == Memcached::RES_NOTFOUND) {
	  $ids = array();
	  $this->memcached->set(self::ID_FIELD, $ids);
	} else { 
	  if(array_key_exists($id, $ids)) {
	    unset($ids[$id]);
	  }
	  var_dump($ids);
	  $this->memcached->cas($cas, self::ID_FIELD, $ids);
	}   
      } 
    } while ($this->memcached->getResultCode() != Memcached::RES_SUCCESS);
    $this->memcached->delete($id);
    $this->signal();
  }

  public function update($id, $percent, $message, $type) {
    $old = $this->memcached->get($id);
    if(is_array($old) && array_key_exists('percent', $old)) {
      $old_percent = $old['percent'];
    } else {
      $old_percent = 0;
    }
    if(is_array($old) && array_key_exists('message', $old)) {
      $old_message = $old['message'];
    } else {
      $old_message = '';
    }

    $signal = false;
    $percent = round($percent, 1);
    if($percent > 100) 
      $percent = 100;
    if($percent < 0)
      $percent = 0;
    if( ($percent == 100 ||
	 $percent == 0 ||
	 abs($percent - $old_percent) >= 1 ||
	 $old_message != $message) &&
	(microtime(true) - $this->lastUpdate) > self::UPDATE_INTERVAL) {
      $signal = true;
      $this->lastUpdate = microtime(true);
    }
    $this->memcached->set($id, array( 'percent' => $percent,
				      'message' => $message,
				      'type' => $type));
    if($signal)
      $this->signal();
  }
  
  private function signal() {
    $s = new DbusSignal($this->dbus,
			'/us/zarfmouse/zriptools', 
			'us.zarfmouse.ZRipTools.ProgressMonitor', 
			'ProgressSignal'
			);
    $s->send();
  }
}



