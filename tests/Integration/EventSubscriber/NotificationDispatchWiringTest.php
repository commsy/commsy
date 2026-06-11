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

namespace Tests\Integration\EventSubscriber;

use App\Event\ItemPublishedEvent;
use App\Message\NotifyNewEntryMessage;
use cs_item;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * End-to-end wiring: publishing a notifiable entry must enqueue the async
 * fan-out message. Exercises the real path dispatch → subscriber → message bus
 * → async transport (in-memory in tests), the seam the unit tests cannot see.
 */
class NotificationDispatchWiringTest extends KernelTestCase
{
    public function testPublishingNotifiableItemQueuesFanOutMessage(): void
    {
        self::bootKernel();

        $item = $this->createMock(cs_item::class);
        $item->method('getItemType')->willReturn('material');
        $item->method('getItemID')->willReturn(4242);
        $item->method('getContextID')->willReturn(7);
        $item->method('getTitle')->willReturn('Published material');
        $item->method('getCreatorID')->willReturn(3);
        $item->method('getCreatorItem')->willReturn(null);
        $item->method('isNotActivated')->willReturn(false);

        self::getContainer()->get(EventDispatcherInterface::class)
            ->dispatch(new ItemPublishedEvent($item), ItemPublishedEvent::NAME);

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $fanOut = array_values(array_filter(
            array_map(static fn ($envelope) => $envelope->getMessage(), $transport->getSent()),
            static fn ($message): bool => $message instanceof NotifyNewEntryMessage,
        ));

        self::assertCount(1, $fanOut, 'publishing a notifiable entry must queue exactly one fan-out message');
        self::assertSame(4242, $fanOut[0]->sourceItemId);
    }
}
