<?php
namespace CommsyBundle\Migrations;

use Doctrine\ORM\EntityManager;

abstract class AbstractMigration
{
    protected function removeEvents(EntityManager $em, $object)
    {
        $em->getClassMetadata(get_class($object))->setLifecycleCallbacks([]);
    }
}