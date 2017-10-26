<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:40
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class MaterialStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['material'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $materials = $this->findById($connection, 'materials', $item['item_id'], true);
        if ($materials) {
            foreach ($materials as $material) {
                $logger->info('Moving material from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
                if (!$this->move($connection, 'materials', $material, $newPortalPrivateRoom, $logger)) {
                    throw new \Exception("move failed");
                }

                $logger->info('Copying material files from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
                if (!$this->copyFiles($connection, $material, $newPortalPrivateRoom, $oldPortalPrivateRoom, $logger)) {
                    throw new \Exception("copy failed");
                }
            }
        } else {
            $logger->info('no materials found - skipping');
        }
    }
}