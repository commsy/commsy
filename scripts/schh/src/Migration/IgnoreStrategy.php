<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:39
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class IgnoreStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['user', 'project', 'community'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return true;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
    }
}