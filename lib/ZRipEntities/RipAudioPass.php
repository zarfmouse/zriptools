<?php

namespace ZRipEntities;

/**
 * @Entity
 */
class RipAudioPass extends ActiveRecord {
  /** 
   * @Id 
   * @Column(type="integer") 
   * @GeneratedValue 
   **/
  protected $id;

  /** 
   * @Column(type="integer") 
   **/
  protected $pass;

  /** 
   * @Column(type="integer") 
   **/
  protected $paranoia;

  /** 
   * @Column(type="integer") 
   **/
  protected $totalFrames;

  /** 
   * @Column(type="float") 
   **/
  protected $speed;

  /** 
   * @Column(type="float") 
   **/
  protected $seconds;

  /** 
   * @Column(type="bigint") 
   **/  
  protected $size;

  /** 
   * @Column(type="string") 
   **/  
  protected $md5;

  /** 
   * @ManyToOne(targetEntity="RipAudio", inversedBy="ripAudioPasses", cascade={"all"})
   **/
  protected $ripAudio;
}
