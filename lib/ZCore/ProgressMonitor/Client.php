<?php

namespace ZCore\ProgressMonitor;
use ZCore\ProgressMonitor;
use ZCore\MemcacheSingleton;
use DbusSignal;
use Dbus;

class Client {
  static public function run($callback) {
    $d = new Dbus(Dbus::BUS_SYSTEM);
    $d->addWatch('us.zarfmouse.ZRipTools.ProgressMonitor', 'ProgressSignal');
    $memcache = MemcacheSingleton::get();
    self::task_list($memcache, $callback);
    $flag = true;
    do {
      $s = $d->waitLoop( 2000 );
      if(connection_aborted() || (connection_status() != 0)) {
	$flag=false;
      }
      $flag = self::task_list($memcache, $callback);
    } while ($flag);
  }
  
  static private function task_list($memcache, $callback) {
    $monitors = array();    
    $ids = $memcache->get(ProgressMonitor::ID_FIELD);
    if(is_array($ids)) {
      foreach(array_keys($ids) as $id) {
	$val = $memcache->get($id);
	if(is_array($val)) {
	  $monitors[$id] = $val;
	}
      }
    }
    return call_user_func($callback, $monitors);
  }
}