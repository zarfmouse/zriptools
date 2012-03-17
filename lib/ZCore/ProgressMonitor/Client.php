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
    $n = $d->createProxy("us.zarfmouse.ZRipTools.ProgressMonitor",
			 "/us/zarfmouse/zriptools", 
			 "us.zarfmouse.ZRipTools.ProgressMonitor"
			 );
    $memcached = new Memcached();
    $memcached->addServer('localhost', 11211);
    $flag = true;
    do
      {
	$s = $d->waitLoop( 10000 );
	if($s instanceof DbusSignal &&
	   $s->matches('us.zarfmouse.ZRipTools.ProgressMonitor', 
		       'ProgressSignal')) {
	  $monitors = array();
	  foreach($memcached->get(ProgressMonitor::ID_FIELD) as $id) {
	    $val = $memcached->get($id);
	    $monitors[$id] = $val;
	  }
	  $flag = call_user_func($callback, $monitors);
	}
      }
    while ( $flag );
  }
}