<?php

namespace Tests;

abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    static private $pdo = null;

    private $connection = null;

    final public function getConnection()
    {
        if ($this->connection === null) {
            if (self::$pdo == null) {
                self::$pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
            }

            $this->connection = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }

        return $this->connection;
    }
}