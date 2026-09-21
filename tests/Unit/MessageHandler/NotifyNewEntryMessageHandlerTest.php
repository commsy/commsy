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

namespace Tests\Unit\MessageHandler;

use App\Message\NotifyNewEntryMessage;
use App\MessageHandler\NotifyNewEntryMessageHandler;
use App\Notification\NotificationManager;
use PHPUnit\Framework\TestCase;

/**
 * The handler hands the signal on unchanged — that is all it does, and all that
 * is left to check here. What happens to the signal afterwards is covered by
 * {@see \Tests\Integration\Notification\NotificationManagerTest}, which is where
 * building a room full of members earns its keep.
 */
class NotifyNewEntryMessageHandlerTest extends TestCase
{
    public function testThePlainSignalReachesTheManager(): void
    {
        $signal = new NotifyNewEntryMessage(
            sourceItemId: 4242,
            contextId: 7,
            sourceItemType: 'announcement',
            title: 'Hello room',
            creatorUserItemId: 3,
            actorUserItemId: 3,
        );

        $manager = $this->createMock(NotificationManager::class);
        $manager->expects(self::once())->method('notifyNewEntry')->with($signal);

        (new NotifyNewEntryMessageHandler($manager))($signal);
    }
}
