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

namespace Tests\Unit\EventSubscriber;

use App\Event\CommsyEditEvent;
use App\Event\ItemDeletedEvent;
use App\EventSubscriber\NotificationEventSubscriber;
use App\Message\NotifyNewEntryMessage;
use App\Repository\NotificationRepository;
use cs_item;
use cs_user_item;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class NotificationEventSubscriberTest extends TestCase
{
    public function testPublishedEntrySaveDispatchesSignalWithSnapshot(): void
    {
        $creator = $this->createMock(cs_user_item::class);
        $creator->method('getFullName')->willReturn('Jane Doe');

        $item = $this->item('material', draft: false);
        $item->method('getItemID')->willReturn(123);
        $item->method('getContextID')->willReturn(45);
        $item->method('getTitle')->willReturn('My entry');
        $item->method('getCreatorID')->willReturn(7);
        $item->method('getCreatorItem')->willReturn($creator);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotifyNewEntryMessage $message): bool {
                return 123 === $message->sourceItemId
                    && 45 === $message->contextId
                    && 'material' === $message->sourceItemType
                    && 'My entry' === $message->title
                    && 7 === $message->creatorUserItemId
                    && 'Jane Doe' === $message->actorName;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->subscriber($bus)->onSave(new CommsyEditEvent($item));
    }

    public function testDraftSaveDispatchesNothing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->subscriber($bus)->onSave(new CommsyEditEvent($this->item('material', draft: true)));
    }

    public function testNonNotifiableTypeDispatchesNothing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        // A sub-item such as a discussion article must not notify.
        $this->subscriber($bus)->onSave(new CommsyEditEvent($this->item('discussionarticle', draft: false)));
    }

    public function testItemDeletedRemovesItsNotifications(): void
    {
        $item = $this->createMock(cs_item::class);
        $item->method('getItemID')->willReturn(99);

        $repository = $this->createMock(NotificationRepository::class);
        $repository->expects($this->once())->method('removeForSourceItem')->with(99);

        $subscriber = new NotificationEventSubscriber($this->createMock(MessageBusInterface::class), $repository);
        $subscriber->onItemDeleted(new ItemDeletedEvent($item));
    }

    private function item(string $type, bool $draft): cs_item
    {
        $item = $this->createMock(cs_item::class);
        $item->method('getItemType')->willReturn($type);
        $item->method('isDraft')->willReturn($draft);

        return $item;
    }

    private function subscriber(MessageBusInterface $bus): NotificationEventSubscriber
    {
        return new NotificationEventSubscriber($bus, $this->createMock(NotificationRepository::class));
    }
}
