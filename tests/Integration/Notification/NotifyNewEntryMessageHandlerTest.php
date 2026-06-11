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

namespace Tests\Integration\Notification;

use App\Entity\Account;
use App\Message\NotifyNewEntryMessage;
use App\MessageHandler\NotifyNewEntryMessageHandler;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Confirms the message handler is wired and delegates the fan-out to the
 * manager (the bus → handler → rows path).
 */
#[WithStory(AccountStory::class)]
class NotifyNewEntryMessageHandlerTest extends KernelTestCase
{
    public function testHandlerFansOutToMembers(): void
    {
        self::bootKernel();
        $account = AccountStory::get('account');
        $portal = $account->getPortal();

        $room = RoomFactory::new()->project()->create([
            'contextId' => $portal?->getId(),
            'portal' => $portal,
        ]);
        $creator = RoomUserFactory::createOne(['account' => $account, 'room' => $room]);
        RoomUserFactory::createOne([
            'account' => AccountFactory::createOne(['portal' => $portal, 'authSource' => $portal?->getAuthSources()->first()]),
            'room' => $room,
        ]);

        $handler = self::getContainer()->get(NotifyNewEntryMessageHandler::class);
        $handler(new NotifyNewEntryMessage(
            sourceItemId: 4242,
            contextId: $room->getItemId(),
            sourceItemType: 'announcement',
            title: 'Hello room',
            creatorUserItemId: $creator->getItemId(),
            actorName: 'Creator Name',
        ));

        self::assertSame(1, self::getContainer()->get(NotificationRepository::class)->count([]));
    }
}
