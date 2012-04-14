<?php

namespace ZDoctrineInit;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\PersistentObject;

require_once "Doctrine/ORM/Tools/Setup.php";
require_once __DIR__."dbParams.php";
Setup::registerAutoloadPEAR();
$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/ZRipEntities"), $isDevMode);
$entityManager = EntityManager::create($dbParams, $config);
PersistentObject::setObjectManager($entityManager);

