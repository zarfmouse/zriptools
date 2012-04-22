<?php

require_once __DIR__."/../lib/autoload-init.php";

use ZCore\CometEventSender;
use \ZCore\ProgressMonitor\Client;

ini_set('display_errors', 1);

$sender = new CometEventSender();
Client::run(function($monitors) use ($sender) {
    $sender->send($monitors);
    return ($sender->getTotalBytesSent() < 1024*1024);
  });

