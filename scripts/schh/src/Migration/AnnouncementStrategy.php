<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:41
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class AnnouncementStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['announcement'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $announcement = $this->findById($connection, 'announcement', $item['item_id']);
        if ($announcement) {
            $logger->info('Moving announcement from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->move($connection, 'announcement', $announcement, $newPortalPrivateRoom, $logger)) {
                throw new \Exception("move failed");
            }

            $logger->info('Copying announcement files from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->copyFiles($connection, $announcement, $newPortalPrivateRoom, $oldPortalPrivateRoom, $logger)) {
                throw new \Exception("copy failed");
            }
        } else {
            $logger->info('no announcement found - skipping');
        }
    }
}