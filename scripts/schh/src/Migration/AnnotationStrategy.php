<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:41
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class AnnotationStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['annotation'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $annotation = $this->findById($connection, 'annotations', $item['item_id']);
        if ($annotation) {
            $logger->info('Moving annotation from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->move($connection, 'annotations', $annotation, $newPortalPrivateRoom, $logger)) {
                throw new \Exception("move failed");
            }

            $logger->info('Copying annotation files from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->copyFiles($connection, $annotation, $newPortalPrivateRoom, $oldPortalPrivateRoom, $logger)) {
                throw new \Exception("copy failed");
            }
        } else {
            $logger->info('no annotation found - skipping');
        }
    }
}