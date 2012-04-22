<?php

namespace ZCore\ProgressMonitor;
use ZCore\ProgressMonitor;
use ZCore\MemcachedSingleton;
use DbusSignal;
use Dbus;
use Memcached;

class Client {
  static public function run($callback) {
    $d = new Dbus(Dbus::BUS_SYSTEM);
    $d->addWatch('us.zarfmouse.ZRipTools.ProgressMonitor', 'ProgressSignal');
    $memcached = MemcachedSingleton::get();
    self::task_list($memcached, $callback);
    $flag = true;
    do {
      $s = $d->waitLoop( 2000 );
      if(connection_aborted() || (connection_status() != 0)) {
	$flag=false;
      }
      $flag = self::task_list($memcached, $callback);
    } while ($flag);
  }

  static private function task_list($memcached, $callback) {
    $monitors = array();    
    $ids = $memcached->get(ProgressMonitor::ID_FIELD);
    if(is_array($ids)) {
      foreach(array_keys($ids) as $id) {
	$val = $memcached->get($id);
	if(is_array($val)) {
	  $monitors[$id] = $val;
	}
      }
    }
    return call_user_func($callback, $monitors);
  }
}