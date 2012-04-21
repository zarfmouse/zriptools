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
   * @Column(type="string", unique=true, length=36) 
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
   * @OneToOne(targetEntity="RipAudioMeta", inversedBy="ripAudio", cascade={"all"})
   **/
  protected $meta;

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
   * True when the ripping process exits.
   **/  
  protected $complete;

  /** 
   * @Column(type="boolean") 
   * True when the ripping process was succesful.
   **/  
  protected $success;

  /** 
   * @Column(type="bigint", nullable=true) 
   **/  
  protected $size;

  /** 
   * @Column(type="string", nullable=true, length=32) 
   **/  
  protected $md5;

  /** 
   * @Column(type="boolean")    
   * True when the user removes the task from the UI.
   **/  
  protected $resolved;

  
}
