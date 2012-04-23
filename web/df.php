<?php

define('LIBRARY_ROOT', '/home/zach/CDLibrary');
$df=shell_exec("df -k ".LIBRARY_ROOT);
$matches = array();
if(preg_match('/\s([0-9]+)\%\s/', $df, $matches)) {
  $p = $matches[1];
  print json_encode(intval($p));
}
