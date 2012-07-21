<?php

namespace ZRipUtils;
use Exception;

class Musicbrainz {
  private $release;
  private $data;

  static public $base_url = 'http://musicbrainz.org/ws/2';

  public function __construct() {
  }

  public function setRelease($release) {
    $this->release = $release;
  }

  public function getRelease() {
    if(is_null($this->release))
      throw new MusicbrainzReleaseNotSetException();
    return $this->release;
  }

  public function getData() {
    if(is_null($this->data)) {
      $base_url = self::$base_url;
      $release = $this->getRelease();
      $url = "$base_url/release/$release?inc=recordings+artists+labels+release-groups+discids+media+puids+isrcs+artist-credits+aliases+tags+ratings+artist-rels+label-rels+recording-rels+release-rels+release-group-rels+url-rels+work-rels+recording-level-rels+work-level-rels";
      $this->data = file_get_contents($url);
    }
    return $this->data;
  }
}

class MusicbrainzException extends Exception {};  
class MusicbrainzReleaseNotSetException extends MusicbrainzException {};



