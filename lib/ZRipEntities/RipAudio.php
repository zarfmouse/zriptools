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
   * @Column(type="string") 
   **/
  protected $uuid;

  /** 
   * @OneToOne(targetEntity="Device", cascade={"all"})
   **/
  protected $device;

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

}
