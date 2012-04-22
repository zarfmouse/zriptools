<?php

namespace ZCore;
use Memcache;

class MemcacheSingleton {
  private static $memcache;
  public static function get() {
    if(!(self::$memcache instanceof Memcache)) {
      self::$memcache = new Memcache();
      self::$memcache->connect('localhost', 11211);
    }
    return self::$memcache;
  }
  public static function disconnect() {
    self::$memcache->close();
    self::$memcache = null;
  }
}
  
