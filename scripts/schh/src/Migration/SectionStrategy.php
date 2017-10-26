<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:41
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class SectionStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['section'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $sections = $this->findById($connection, 'section', $item['item_id'], true);
        if ($sections) {
            foreach ($sections as $section) {
                $logger->info('Moving section from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
                if (!$this->move($connection, 'section', $section, $newPortalPrivateRoom, $logger)) {
                    throw new \Exception("move failed");
                }

                $logger->info('Copying section files from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
                if (!$this->copyFiles($connection, $section, $newPortalPrivateRoom, $oldPortalPrivateRoom, $logger)) {
                    throw new \Exception("copy failed");
                }
            }
        } else {
            $logger->info('no sections found - skipping');
        }
    }
}