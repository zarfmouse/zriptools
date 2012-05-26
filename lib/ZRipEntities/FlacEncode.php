<?php

namespace ZRipEntities;

/**
 * @Entity
 */
class FlacEncode extends ActiveRecord {
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
   * @OneToOne(targetEntity="RipAudio", inversedBy="flacEncode", cascade={"all"})
   **/
  protected $ripAudio;

  /** 
   * @Column(type="string") 
   **/
  protected $flac;

  /** 
   * @Column(type="string") 
   **/
  protected $cue;

  /** 
   * @Column(type="boolean") 
   * True when the process exits.
   **/  
  protected $complete;

  /** 
   * @Column(type="boolean") 
   * True when the process was succesful.
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
   * @Column(type="float", nullable=true) 
   **/
  protected $speed;

  /** 
   * @Column(type="float", nullable=true) 
   **/
  protected $ratio;

  /** 
   * @Column(type="float", nullable=true) 
   **/
  protected $seconds;

  /** 
   * @Column(type="datetime") 
   **/
  protected $startTime;

  /** 
   * @Column(type="datetime", nullable=true) 
   **/
  protected $endTime;
}
