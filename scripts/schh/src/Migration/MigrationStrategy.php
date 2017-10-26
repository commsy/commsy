<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:37
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

interface MigrationStrategy
{
    public static function getType();
    public function ignore(\PDO $connection, array $item, LoggerInterface $logger);
    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger);
}