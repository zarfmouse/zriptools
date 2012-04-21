<?php

namespace ZRipEntities;
use Exception;

/**
 * @Entity
 */
class DiscId extends ActiveRecord {
  /** 
   * @Id 
   * @Column(type="integer") 
   * @GeneratedValue 
   **/
  protected $id;

  /** 
   * @Column(type="string", length=8) 
   **/
  protected $cddb; 

  /** 
   * @Column(type="string") 
   **/
  protected $cddbFull; 

  /** 
   * @Column(type="string", length=28) 
   **/
  protected $musicbrainz;

  /** 
   * @Column(type="string") 
   **/
  protected $musicbrainzSubmit;

  /** 
   * @Column(type="string") 
   **/
  protected $musicbrainzWS;

  /**
   * @OneToOne(targetEntity="RipAudio", mappedBy="discId", cascade={"all"})
   */
  protected $ripAudio;

  public function initFromDevice(Device $device) {
    $dev = $device->getDeviceFile();

    if(is_null($dev) || 
       ! (preg_match('(^/dev/.*)', $dev) && file_exists($dev))) 
      throw new InvalidDeviceException("$dev");

    $command = __DIR__.'/../../misc/discid';
    $output = `$command $dev`;
    $lines = array();
    foreach(explode("\n", $output) as $line) {
      $keyval = preg_split('/\s*\:\s*/', $line, 2);
      if(strlen($keyval[0]) && strlen($keyval[1])) {
	$lines[trim($keyval[0])] = trim($keyval[1]);
      }
    }
    $this->setCddb($lines['FreeDB DiscID']);
    $this->setMusicbrainz($lines['DiscID']);
    $this->setMusicbrainzSubmit($lines['Submit via']);
    $this->setMusicbrainzWS($lines['WS url']);
    
    $cddb_full = `/usr/bin/cd-discid $dev`;
    $cddb_full = trim($cddb_full);
    $this->setCddbFull($cddb_full);
  }

  public function getMusicbrainzWS2() {
    $url = $this->getMusicbrainzWS();
    $matches = array();
    preg_match('{/ws/1/release?.*discid=([^&]+)&toc=([^&]+)}', $url, $matches);
    $discid = $matches[1];
    $toc = $matches[2];
    return "http://mm.musicbrainz.org/ws/2/discid/$discid?toc=$toc&cdstubs=no";
  }
  
}
  
