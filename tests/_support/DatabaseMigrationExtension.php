<?php

namespace App\Tests;

use App\Entity\Account;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Module\Symfony;

class DatabaseMigrationExtension extends Extension
{
    public static $events = [
        Events::TEST_BEFORE => 'beforeTest',
    ];

    public function beforeTest(TestEvent $event)
    {
        if ($this->hasModule('Symfony')) {
            /** @var Symfony $symfony */
            $symfony = $this->getModule('Symfony');

            $symfony->runSymfonyConsoleCommand('doctrine:migrations:migrate', ['--no-interaction' => true]);

            $accountRepository = $symfony->grabRepository(Account::class);

            /** @var Account $root */
            $root = $accountRepository->find(1);
            $root->setPasswordMd5(null);
            $root->setPassword('$2a$12$B66lysmWqS4bOd2gypsHT.tif.UANr4sERFiRfteQKHKfU4I0AMli'); // pcxEmQj6QzE5

            $entityManager = $symfony->_getEntityManager();
            $entityManager->persist($root);
            $entityManager->flush();
        }
    }
}
