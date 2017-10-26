<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:41
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class DateStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['date'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $date = $this->findById($connection, 'dates', $item['item_id']);
        if ($date) {
            $logger->info('Moving date from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->move($connection, 'dates', $date, $newPortalPrivateRoom, $logger)) {
                throw new \Exception("move failed");
            }

            $logger->info('Copying date files from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->copyFiles($connection, $date, $newPortalPrivateRoom, $oldPortalPrivateRoom, $logger)) {
                throw new \Exception("copy failed");
            }
        } else {
            $logger->info('no date found - skipping');
        }
    }
}