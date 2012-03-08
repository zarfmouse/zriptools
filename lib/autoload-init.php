<?php

namespace ZAutoLoad;

require_once 'Doctrine/Common/ClassLoader.php';
function autoload_init() {
  $dh = opendir(__DIR__);
  while($dir = readdir($dh)) {
    if(is_dir(__DIR__."/$dir") and preg_match('/^[A-Z]/', $dir)) {
      $classLoader = new \Doctrine\Common\ClassLoader($dir, __DIR__);
      $classLoader->register();
    }
  }
}
autoload_init();
