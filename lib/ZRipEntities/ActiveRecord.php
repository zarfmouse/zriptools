<?php

namespace ZRipEntities;
use Doctrine\Common\Persistence\PersistentObject;

class ActiveRecord extends PersistentObject {
  public function save() {
    $em = PersistentObject::getObjectManager();
    $em->persist($this);
    $em->flush($this);
  }
}
