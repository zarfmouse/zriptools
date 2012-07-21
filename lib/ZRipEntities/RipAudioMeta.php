<?php

namespace ZRipEntities;
use Exception;

/**
 * @Entity
 */
class RipAudioMeta extends ActiveRecord {
  /** 
   * @Id 
   * @Column(type="integer") 
   * @GeneratedValue 
   **/
  protected $id;
  
  /** 
   * @Column(type="string", nullable=true) 
   **/
  protected $note; 

  /** 
   * @Column(type="string", length=16, nullable=true) 
   **/
  protected $barcode;

  /** 
   * @Column(type="string", length=8, nullable=true) 
   **/
  protected $slot;

  /** 
   * @Column(type="string", length=36, nullable=true) 
   **/
  protected $musicbrainzRelease;

  /** 
   * @Column(type="string", nullable=true) 
   **/
  protected $cddbPick;

  /** 
   * @Column(type="string", nullable=true) 
   **/
  protected $title;

  /** 
   * @Column(type="string", nullable=true) 
   **/
  protected $artist;

  /** 
   * @Column(type="integer", nullable=true) 
   **/
  protected $numTracks;

  /** 
   * @Column(type="text", nullable=true) 
   **/
  protected $cddbData;

  /** 
   * @Column(type="text", nullable=true) 
   **/
  protected $musicbrainzData;

  

  /**
   * @OneToOne(targetEntity="RipAudio", mappedBy="meta", cascade={"all"})
   */
  protected $ripAudio;
  
}
  
