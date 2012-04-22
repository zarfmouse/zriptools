<?php

namespace ZCore;
use Memcached;

class MemcachedSingleton {
  private static $memcached;
  public static function get() {
    if(!(self::$memcached instanceof Memcached)) {
      self::$memcached = new Memcached();
      self::$memcached->addServer('localhost', 11211);
    }
    return self::$memcached;
  }
  public static function disconnect() {
    self::$memcached = null;
  }
}
  
