<?php

require_once __DIR__."/../lib/autoload-init.php";
require_once __DIR__."/../lib/doctrine-init.php";
ini_set("display_errors", 1);
use Doctrine\Common\Persistence\PersistentObject;
use ZRipEntities\RipAudioMeta;

$entityManager = PersistentObject::getObjectManager();
#$entityManager->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

$actions = ['cddb', 'note', 'musicbrainz', 'barcode', 'slot'];

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

$ripAudio = $entityManager->getRepository('ZRipEntities\RipAudio')->findOneBy(['uuid' => $key]);
if(is_null($ripAudio)) {
  die("$key not found.");
}

$meta = $ripAudio->getMeta();
if(is_null($meta)) {
  $meta = new RipAudioMeta(); 
  $ripAudio->setMeta($meta);
}

if($action == 'cddb') {
  $discId = $ripAudio->getDiscId();
  $cddbFull = $discId->getCddbFull();
  $cddb_options = `/usr/bin/cddbcmd -m http -l 6 -h freedb.freedb.org cddb query $cddbFull`;
  $cddb_options = explode("\n", $cddb_options);
  $retval = [ 'options' => $cddb_options ];
  $meta = $ripAudio->getMeta();
  if($method == 'POST') {
    $meta->setCddbPick($_REQUEST['data']);
    $meta->save();
    $ripAudio->save();
  }
  $retval['chosen'] = $meta->getCddbPick(); 
  print json_encode($retval);
}

if($action == 'musicbrainz') {
  $discId = $ripAudio->getDiscId();
  $url = $discId->getMusicbrainzWS2();
  $xml = simplexml_load_file($url);
  $options = array();
  foreach($xml->disc->{'release-list'}->release as $release) {
    $id = $release['id'];
    $title = $release->title;
    $country = $release->country;
    $barcode = $release->barcode;
    $options[(string)$id] = "$title $country $barcode";
  }
  $retval = [ 'options' => $options ];
  if($method == 'POST') {
    $meta->setMusicbrainzRelease($_REQUEST['data']);
    $meta->save();
    $ripAudio->save();
  }
  $retval['chosen'] = $meta->getMusicbrainzRelease(); 
  print json_encode($retval);
}


