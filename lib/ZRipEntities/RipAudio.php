<?php

namespace ZRipEntities;

/**
 * @Entity
 */
class RipAudio extends ActiveRecord {
  /** 
   * @Id 
   * @Column(type="integer") 
   * @GeneratedValue 
   **/
  protected $id;

  /** 
   * @Column(type="string", unique=true) 
   **/
  protected $uuid;

  /** 
   * @OneToOne(targetEntity="Device", inversedBy="ripAudio", cascade={"all"})
   **/
  protected $device;

  /** 
   * @OneToOne(targetEntity="DiscId", inversedBy="ripAudio", cascade={"all"})
   **/
  protected $discId;

  /** 
   * @OneToMany(targetEntity="RipAudioPass", mappedBy="ripAudio", cascade={"all"})
   **/
  protected $ripAudioPasses;

  /** 
   * @Column(type="string") 
   **/
  protected $pcm;

  /** 
   * @Column(type="string") 
   **/
  protected $toc;

  /** 
   * @Column(type="string") 
   **/
  protected $log;

  /** 
   * @Column(type="bigint", nullable=true) 
   **/
  protected $errorBytes;

  /** 
   * @Column(type="boolean") 
   **/  
  protected $complete;

  /** 
   * @Column(type="bigint", nullable=true) 
   **/  
  protected $size;

  /** 
   * @Column(type="string", nullable=true) 
   **/  
  protected $md5;

  /** 
   * @Column(type="boolean") 
   **/  
  protected $resolved;

  
}
