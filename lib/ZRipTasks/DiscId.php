<?php

namespace ZRipTasks;
use Exception;

class DiscId {
  private $dev;
  private $freedb;
  private $musicbrainz;
  private $musicbrainz_submit;
  private $musicbrainz_ws;

  public function __construct($dev) {
    if(is_null($dev) || 
       ! (preg_match('(^/dev/.*)', $dev) && file_exists($dev))) 
      throw new InvalidDeviceException($dev);
    $this->dev = $dev;

    $command = __DIR__.'/../../misc/discid';
    $output = `$command $this->dev`;
    $lines = array();
    foreach(explode("\n", $output) as $line) {
      $keyval = preg_split('/\s*\:\s*/', $line, 2);
      if(strlen($keyval[0]) && strlen($keyval[1])) {
	$lines[trim($keyval[0])] = trim($keyval[1]);
      }
    }
    $this->freedb = $lines['FreeDB DiscID'];
    $this->musicbrainz = $lines['DiscID'];
    $this->musicbrainz_submit = $lines['Submit via'];
    $this->musicbrainz_ws = $lines['WS url'];
  }
  
  public function cddbid() {
    return $this->freedb;
  }
  public function mbid() {
    return $this->musicbrainz;
  }
  public function mb_submit_url() {
    return $this->musicbrainz_submit;
  }
  public function mb_ws_url() {
    return $this->musicbrainz_ws;
  }

}
  
class DiscIdException extends Exception {};
class InvalidDeviceException extends DiscIdException {};
