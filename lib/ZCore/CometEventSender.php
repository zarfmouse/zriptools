<?php

namespace ZCore;

class CometEventSender {
  private $boundary = "\n";
  private $total_sent = 0;
  
  public function __construct() {
    // Do everything in our power to prevent buffering.
    if(function_exists('apache_setenv')) {
      apache_setenv('no-gzip', 1);
    }
    ini_set('zlib.output_compression', 0);
    ini_set('implicit_flush', 1);
    for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
    ob_implicit_flush(1);
    header("Content-type: application/json");
  }

  public function setBoundary($boundary) { 
    $this->boundary = $boundary;
  }

  public function send($data) {
    $string = json_encode($data).$this->boundary;
    $this->total_sent += strlen($string);
    print $string;
  }

  public function getTotalBytesSent() {
    return $this->total_sent;
  }
}
