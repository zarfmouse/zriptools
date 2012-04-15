<?php

namespace ZCore;
use Dbus;
use DbusSignal;
use Memcached;

class ProgressMonitor {
  private $lastUpdate = 0;
  private $dbus;
  private $memcached;
  const UPDATE_INTERVAL = 0.5;
  const ID_FIELD = 'ProgressMonitorIds';
  
  public function __construct() {
    $this->lastUpdate = microtime(true);
    $this->dbus = new Dbus( Dbus::BUS_SYSTEM);
    $this->memcached = new Memcached();
    $this->memcached->addServer('localhost', 11211);
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
      if ($this->memcached->getResultCode() != Memcached::RES_NOTFOUND) {
	unset($ids[$id]);
	$this->memcached->cas($cas, self::ID_FIELD, $ids);
      }   
    } while ($this->memcached->getResultCode() != Memcached::RES_SUCCESS);
    $this->memcached->delete($id);
    $this->signal();
  }

  public function update($id, $percent, $message, $type) {
    $old = $this->memcached->get($id);
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
	(microtime(true) - $this->lastUpdate) > self::UPDATE_INTERVAL)) {
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



