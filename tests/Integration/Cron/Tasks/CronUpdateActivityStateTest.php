<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Tests\Integration\Cron\Tasks;

use App\Cron\Tasks\CronUpdateActivityState;
use App\Entity\Account;
use App\Repository\AccountsRepository;
use App\Repository\RoomRepository;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class CronUpdateActivityStateTest extends KernelTestCase
{
    // tests
    public function testCronDeliversMessages(): void
    {
        self::bootKernel();

        $accountRepository = $this->createMock(AccountsRepository::class);
        $accountRepository->expects($this->once())
            ->method('findAllExceptRoot')
            ->willReturn([
                (new Account())->setId(1),
            ]);

        $roomRepository = $this->createMock(RoomRepository::class);
        $roomRepository->expects($this->once())
            ->method('findAllIds')
            ->willReturn([
                1,
            ]);

        $container = static::getContainer();
        $messageBus = $container->get(MessageBusInterface::class);

        $cronTask = new CronUpdateActivityState($accountRepository, $roomRepository, $messageBus);
        $cronTask->run(new DateTimeImmutable());

        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        $this->assertCount(2, $transport->getSent());
    }
}
