<?php

namespace Tests\Support;

use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Module\Db;
use Codeception\Module\Symfony;

class DatabaseMigrationExtension extends Extension
{
    public static array $events = [
        Events::TEST_BEFORE => 'beforeTest',
    ];

    public function beforeTest(TestEvent $event)
    {
        if ($this->hasModule('Symfony')) {
            // Run all database migrations
            /** @var Symfony $symfony */
            /** @noinspection PhpUnhandledExceptionInspection */
            $symfony = $this->getModule('Symfony');
            $symfony->runSymfonyConsoleCommand('doctrine:migrations:migrate', ['--no-interaction' => true]);
        }

        if ($this->hasModule('Db')) {
            // Set root password (skip password migration) to "pcxEmQj6QzE5"
            /** @var Db $db */
            /** @noinspection PhpUnhandledExceptionInspection */
            $db = $this->getModule('Db');
            $db->updateInDatabase('accounts', [
                'password_md5' => null,
                'password' => '$2a$12$B66lysmWqS4bOd2gypsHT.tif.UANr4sERFiRfteQKHKfU4I0AMli',
            ], ['id' => 1]);
        }
    }
}
