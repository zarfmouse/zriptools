<?php

require_once __DIR__."/../lib/autoload-init.php";

use ZCore\MemcacheSingleton;

date_default_timezone_set('US/Central');

$actions = ['kill'];

$method = array_key_exists('method', $_REQUEST) ? $_REQUEST['method'] : $_SERVER['REQUEST_METHOD'];
if(!in_array($method, ['GET', 'POST'])) {
  die("Invalid method.");
}

$path = $_SERVER['PATH_INFO'];
list($null, $action, $key) = explode('/', $path);
if(!in_array($action, $actions)) {
  die("Invalid action.");
}
if(strlen($key) != 36 or !preg_match('/^[a-f0-9\-]+$/', $key)) {
  die("Invalid key.");
}

if($action == 'kill') {
  $retval = [];
  if($method == 'POST' & $_REQUEST['kill']) {
    MemcacheSingleton::get()->set("KILL-".$key, 1);
  }
  print json_encode(array('kill' => 'requested'));
}
