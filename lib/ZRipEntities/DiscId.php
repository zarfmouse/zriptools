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
  protected $freedb; 

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
   * @OneToOne(targetEntity="RipAudio", mappedBy="device", cascade={"all"})
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
    $this->setFreedb($lines['FreeDB DiscID']);
    $this->setMusicbrainz($lines['DiscID']);
    $this->setMusicbrainzSubmit($lines['Submit via']);
    $this->setMusicbrainzWS($lines['WS url']);
  }
  
}
  
