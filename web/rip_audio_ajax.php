<?php

require_once __DIR__."/../lib/autoload-init.php";
require_once __DIR__."/../lib/doctrine-init.php";
ini_set("display_errors", 1);
use Doctrine\Common\Persistence\PersistentObject;
use ZRipEntities\RipAudioMeta;
use ZCore\MemcachedSingleton;

date_default_timezone_set('US/Central');

$entityManager = PersistentObject::getObjectManager();
#$entityManager->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

$actions = ['cddb', 'note', 'musicbrainz', 'barcode', 'slot', 'resolve', 'kill', 'dev'];

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
  $options = [];
  foreach(explode("\n", $cddb_options) as $option) {
    if(strlen($option)) {
      $options[$option] = $option;
    } else {
      $options[''] = "Not found.";
    }
  }
  $retval = [ 'options' => $options ];
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
  $options = [];
  if((bool)$xml->disc) {
    $releases = $xml->disc->{'release-list'}->release;
  } else if((bool)$xml->{'release-list'}) {
    $releases = $xml->{'release-list'}->release;
  } else {
    $releases = array();
  }
  foreach($releases as $release) {
    $id = $release['id'];
    $title = $release->title;
    $country = $release->country;
    $barcode = $release->barcode;
    $options[(string)$id] = "$title $country $barcode";
  }
  $options[''] = "Not found.";
  $retval = [ 'options' => $options ];
  if($method == 'POST') {
    $meta->setMusicbrainzRelease($_REQUEST['data']);
    $meta->save();
    $ripAudio->save();
  }
  $retval['chosen'] = $meta->getMusicbrainzRelease(); 
  print json_encode($retval);
}

if($action == 'note') {
  $retval = [];
  if($method == 'POST') {
    $meta->setNote($_REQUEST['data']);
    $meta->save();
    $ripAudio->save();
  }
  $retval['chosen'] = $meta->getNote(); 
  print json_encode($retval);
}

if($action == 'barcode') {
  $retval = [];
  if($method == 'POST') {
    $meta->setBarcode($_REQUEST['data']);
    $meta->save();
    $ripAudio->save();
  }
  $retval['chosen'] = $meta->getBarcode(); 
  print json_encode($retval);
}

if($action == 'slot') {
  $retval = [];
  if($method == 'POST') {
    $meta->setSlot($_REQUEST['data']);
    $meta->save();
    $ripAudio->save();
  }
  $retval['chosen'] = $meta->getSlot(); 
  print json_encode($retval);
}

if($action == 'resolve') {
  $retval = [];
  if($method == 'POST' & $_REQUEST['resolve']) {
    $ripAudio->setResolved(true);
    $ripAudio->save();
  }
  $retval['resolved'] = $ripAudio->getResolved();
  print json_encode($retval);
}

if($action == 'kill') {
  $retval = [];
  if($method == 'POST' & $_REQUEST['kill']) {
    $memcached = MemcachedSingleton::get();
    $memcached->set("KILL-".$ripAudio->getUuid(), 1);
  }
  print json_encode(array('kill' => 'requested'));
}

if($action == 'dev') {
  print json_encode(array('dev' => $ripAudio->getDevice()->getDeviceFile()));
}


