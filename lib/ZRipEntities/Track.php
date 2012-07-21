<?php

namespace ZRipEntities;
use Exception;

/**
 * @Entity
 */
class Track extends ActiveRecord {
  /** 
   * @Id 
   * @Column(type="integer") 
   * @GeneratedValue 
   **/
  protected $id;
  
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
  protected $trackNum;

  /** 
   * @Column(type="integer", nullable=true) 
   **/
  protected $length;

  /**
   * @ManyToOne(targetEntity="RipAudio", inversedBy="tracks", cascade={"all"})
   */
  protected $ripAudio;
  
}
  
