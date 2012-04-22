<?php

namespace ZRipEntities;

/**
 * @Entity
 */
class RipDataSession extends ActiveRecord {
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
   * @Column(type="string") 
   **/
  protected $path;

  /** 
   * @Column(type="string") 
   **/
  protected $log;
  
  /** 
   * @Column(type="bigint", nullable=true) 
   **/  
  protected $size;

  /** 
   * @Column(type="integer", nullable=true) 
   **/  
  protected $speed;

  /** 
   * @OneToOne(targetEntity="RipAudio", inversedBy="ripDataSession", cascade={"all"})
   **/
  protected $ripAudio;

}
