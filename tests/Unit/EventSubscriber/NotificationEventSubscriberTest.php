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

use App\Enum\NotificationAction;
use App\Event\CommsyEditEvent;
use App\Event\ItemDeletedEvent;
use App\Event\ItemPublishedEvent;
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
    public function testPublishedEntryDispatchesCreatedSignalWithSnapshot(): void
    {
        $creator = $this->createMock(cs_user_item::class);
        $creator->method('getFullName')->willReturn('Jane Doe');

        $item = $this->item('material', notActivated: true);
        $item->method('getItemID')->willReturn(123);
        $item->method('getContextID')->willReturn(45);
        $item->method('getTitle')->willReturn('My entry');
        $item->method('getCreatorID')->willReturn(7);
        $item->method('getCreatorItem')->willReturn($creator);
        $item->method('getModificationDate')->willReturn('2026-06-15 12:00:00');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotifyNewEntryMessage $message): bool {
                return 123 === $message->sourceItemId
                    && 45 === $message->contextId
                    && 'material' === $message->sourceItemType
                    && 'My entry' === $message->title
                    && 7 === $message->creatorUserItemId
                    && 'Jane Doe' === $message->actorName
                    && true === $message->isDeactivated
                    && NotificationAction::Created === $message->action;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->subscriber($bus)->onPublished(new ItemPublishedEvent($item));
    }

    public function testSavingPublishedEntryDispatchesEditedSignal(): void
    {
        $item = $this->item('material', isDraft: false);
        $item->method('getItemID')->willReturn(50);
        $item->method('getContextID')->willReturn(9);
        $item->method('getTitle')->willReturn('Edited title');
        $item->method('getCreatorID')->willReturn(3);
        $item->method('getCreatorItem')->willReturn(null);
        $item->method('getModificationDate')->willReturn('2026-06-16 09:30:00');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotifyNewEntryMessage $message): bool {
                return 50 === $message->sourceItemId
                    && NotificationAction::Edited === $message->action;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->subscriber($bus)->onSaved(new CommsyEditEvent($item));
    }

    public function testSavingDraftDispatchesNothing(): void
    {
        // The create flow saves while still a draft; that must notify via
        // ItemPublishedEvent, not as an edit.
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->subscriber($bus)->onSaved(new CommsyEditEvent($this->item('material', isDraft: true)));
    }

    public function testSavingNonNotifiableTypeDispatchesNothing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->subscriber($bus)->onSaved(new CommsyEditEvent($this->item('discussionarticle', isDraft: false)));
    }

    public function testPublishingNonNotifiableTypeDispatchesNothing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        // A sub-item such as a discussion article must not notify.
        $this->subscriber($bus)->onPublished(new ItemPublishedEvent($this->item('discussionarticle')));
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

    public function testGroupsAndTopicsNotifyForFeedParity(): void
    {
        // Groups and topics surface in the room/dashboard feed, so they notify too.
        foreach (['group', 'topic'] as $type) {
            $item = $this->item($type, isDraft: false);
            $item->method('getItemID')->willReturn(11);
            $item->method('getContextID')->willReturn(22);
            $item->method('getTitle')->willReturn('Org entry');
            $item->method('getCreatorID')->willReturn(3);
            $item->method('getCreatorItem')->willReturn(null);
            $item->method('getModificationDate')->willReturn('2026-06-16 09:30:00');

            $bus = $this->createMock(MessageBusInterface::class);
            $bus->expects($this->once())
                ->method('dispatch')
                ->with($this->callback(static fn (NotifyNewEntryMessage $m): bool => $m->sourceItemType === $type))
                ->willReturn(new Envelope(new \stdClass()));

            $this->subscriber($bus)->onSaved(new CommsyEditEvent($item));
        }
    }

    private function item(string $type, bool $notActivated = false, bool $isDraft = false): cs_item
    {
        $item = $this->createMock(cs_item::class);
        $item->method('getItemType')->willReturn($type);
        $item->method('isNotActivated')->willReturn($notActivated);
        $item->method('isDraft')->willReturn($isDraft);

        return $item;
    }

    private function subscriber(MessageBusInterface $bus): NotificationEventSubscriber
    {
        return new NotificationEventSubscriber($bus, $this->createMock(NotificationRepository::class));
    }
}
