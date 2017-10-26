<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:39
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class LinkItemStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['link_item'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    /**
     * TODO: what about links to users?
     */
    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $linkItem = $this->findById($connection, 'link_items', $item['item_id']);
        if ($linkItem) {
            $logger->info('Moving link item from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->move($connection, 'link_items', $linkItem, $newPortalPrivateRoom, $logger)) {
                throw new \Exception("move failed");
            }
        } else {
            $logger->info('no link items found - skipping');
        }
    }
}