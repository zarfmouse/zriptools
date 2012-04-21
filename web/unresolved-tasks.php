<?php

require_once __DIR__."/../lib/autoload-init.php";
require_once __DIR__."/../lib/doctrine-init.php";

use Doctrine\Common\Persistence\PersistentObject;

$entityManager = PersistentObject::getObjectManager();

$tasks = $entityManager->getRepository('ZRipEntities\RipAudio')->findBy(['resolved' => false, 'complete' => true]);
$output = [];
foreach($tasks as $task) {
  $message = $task->getSuccess() ? 'Success' : 'Failure';
  $output[$task->getUuid()] = ['percent' => 100,
			       'message' => $message,
			       'type' => 'RipAudio'];
}
header("Content-type: application/json");
print json_encode($output);

