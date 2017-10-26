<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:40
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class DiscussionStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['discussion'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $discussion = $this->findById($connection, 'discussions', $item['item_id']);
        if ($discussion) {
            $logger->info('Moving discussion from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->move($connection, 'discussions', $discussion, $newPortalPrivateRoom, $logger)) {
                throw new \Exception("move failed");
            }

            $logger->info('Copying discussion files from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->copyFiles($connection, $discussion, $newPortalPrivateRoom, $oldPortalPrivateRoom, $logger)) {
                throw new \Exception("copy failed");
            }
        } else {
            $logger->info('no discussion found - skipping');
        }
    }
}