<?php

namespace ZCore;
use Dbus;
use DbusSignal;

class ProgressMonitor {
  private $lastUpdate = 0;
  private $ids; 
  const UPDATE_INTERVAL = 1;
  const ID_FIELD = 'ProgressMonitorIds';
  
  public function __construct() {
    $this->lastUpdate = microtime(true);
    $this->ids = array();
  }

  /** 
   * Init from the master process. 
   */
  public function init($id) {
    $memcache = MemcacheSingleton::get();
    $this->ids[$id] = 1;
    $memcache->set(self::ID_FIELD, $this->ids);
    $memcache->set($id, array('percent' => 0,
			      'message' => '',
			      'type' => ''));
  }
  
  /** 
   * Remove from the master process.
   */
  public function remove($id) {
    $memcache = MemcacheSingleton::get();
    unset($this->ids[$id]);
    $memcache->set(self::ID_FIELD, $this->ids);
    $memcache->delete($id);
  }
  
  /**
   * Update from the slave process.
   */
  public function update($id, $percent, $message, $type, $signal=false) {
    $memcache = MemcacheSingleton::get();
    $old = $memcache->get($id);
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
    $memcache->set($id, array( 'percent' => $percent,
			       'message' => $message,
			       'type' => $type));
    if($signal) {
      $dbus = new Dbus( Dbus::BUS_SYSTEM);
      $s = new DbusSignal($dbus,
			  '/us/zarfmouse/zriptools', 
			  'us.zarfmouse.ZRipTools.ProgressMonitor', 
			  'ProgressSignal'
			  );
      $s->send();
    }
  }
}



