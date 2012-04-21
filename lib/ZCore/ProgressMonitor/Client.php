<?php

namespace ZCore\ProgressMonitor;
use ZCore\ProgressMonitor;
use DbusSignal;
use Dbus;
use Memcached;

class Client {
  static public function run($callback) {
    $d = new Dbus(Dbus::BUS_SYSTEM);
    $d->addWatch('us.zarfmouse.ZRipTools.ProgressMonitor', 'ProgressSignal');
    $memcached = new Memcached();
    $memcached->addServer('localhost', 11211);
    self::task_list($memcached, $callback);
    $flag = true;
    do {
      $s = $d->waitLoop( 2000 );
      $flag = self::task_list($memcached, $callback);
    } while ($flag);
  }

  static private function task_list($memcached, $callback) {
    $monitors = array();    
    $ids = $memcached->get(ProgressMonitor::ID_FIELD);
    if(is_array($ids)) {
      foreach(array_keys($ids) as $id) {
	$val = $memcached->get($id);
	$monitors[$id] = $val;
      }
    }
    return call_user_func($callback, $monitors);
  }
}