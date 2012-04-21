<?php

require_once __DIR__."/../lib/autoload-init.php";

ini_set("display_errors", 1);

use ZCore\CometEventSender;
use \ZCore\ProgressMonitor\Client;

$sender = new CometEventSender();
Client::run(function($monitors) use ($sender) {
    $sender->send($monitors);
    return ($sender->getTotalBytesSent() < 1024*1024);
  });

