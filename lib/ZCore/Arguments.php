<?php

namespace ZCore;

class Arguments {
  static public function parse() {
    // Parse command line arguments into a $task and the $arguments array.
    $arguments = array();
    $script = array_shift($_SERVER['argv']);
    while(count($_SERVER['argv']) > 0 && (count($_SERVER['argv'])%2 == 0)) {
      $key = array_shift($_SERVER['argv']);
      $val = array_shift($_SERVER['argv']);
      $key = preg_replace('/^\-+/', '', $key);
      // TODO: Support multi-valued arguments. ZCore\Help doesn't support
      // them yet either.
      $arguments[$key] = $val;
    }
    return $arguments;
  }
}
