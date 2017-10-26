<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:36
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class MigrationFactory
{
    private static $migrations = [];

    public static function registerMigration($migrationClassName)
    {
        $types = $migrationClassName::getType();
        foreach ($types as $type) {
            self::$migrations[$type] = $migrationClassName;
        }
    }

    public static function findMigration($type)
    {
        if (isset(self::$migrations[$type])) {
            $instance = new self::$migrations[$type];

            if (!$instance instanceof MigrationStrategy) {
                throw new \Exception("Strategy does not implement MigrationStrategy Interface");
            }

            return $instance;
        }

        throw new \Exception('no migration found for type ' . $type);
    }
}