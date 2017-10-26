<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:40
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class LabelStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['label'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $label = $this->findById($connection, 'labels', $item['item_id']);
        if ($label) {
            $logger->info('Moving label from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->move($connection, 'labels', $label, $newPortalPrivateRoom, $logger)) {
                throw new \Exception("move failed");
            }
        } else {
            $logger->info('no label found - skipping');
        }
    }
}