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

use App\Enum\EntryAction;
use App\Event\CommsyEditEvent;
use App\Event\ItemAnnotatedEvent;
use App\Event\ItemDeletedEvent;
use App\Event\ItemPublishedEvent;
use App\EventSubscriber\NotificationEventSubscriber;
use App\Message\NotifyNewEntryMessage;
use App\Notification\NotificationPayload;
use App\Notification\NotificationPayloadFactory;
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
        $modificator = $this->createMock(cs_user_item::class);
        $modificator->method('getFullName')->willReturn('Jane Doe');

        $item = $this->item('material', notActivated: true);
        $item->method('getItemID')->willReturn(123);
        $item->method('getContextID')->willReturn(45);
        $item->method('getTitle')->willReturn('My entry');
        $item->method('getCreatorID')->willReturn(7);
        $item->method('getModificatorItem')->willReturn($modificator);
        $item->method('getModificationDate')->willReturn('2026-06-15 12:00:00');

        $payload = new NotificationPayload(creatorName: 'Jane Doe', hasAttachments: true);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotifyNewEntryMessage $message) use ($payload): bool {
                return 123 === $message->sourceItemId
                    && 45 === $message->contextId
                    && 'material' === $message->sourceItemType
                    && 'My entry' === $message->title
                    && 7 === $message->creatorUserItemId
                    && 7 === $message->actorUserItemId
                    && 'Jane Doe' === $message->actorName
                    && true === $message->isDeactivated
                    && EntryAction::Created === $message->action
                    && $message->payload === $payload->toArray();
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->subscriber($bus, $this->factory($payload))->onPublished(new ItemPublishedEvent($item));
    }

    public function testSavingPublishedEntryDispatchesEditedSignal(): void
    {
        $editor = $this->createMock(cs_user_item::class);
        $editor->method('getItemID')->willReturn(7);
        $editor->method('getFullName')->willReturn('The Editor');

        $item = $this->item('material', isDraft: false);
        $item->method('getItemID')->willReturn(50);
        $item->method('getContextID')->willReturn(9);
        $item->method('getTitle')->willReturn('Edited title');
        $item->method('getCreatorID')->willReturn(3);
        $item->method('getModificatorItem')->willReturn($editor);
        $item->method('getModificationDate')->willReturn('2026-06-16 09:30:00');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotifyNewEntryMessage $message): bool {
                return 50 === $message->sourceItemId
                    && EntryAction::Edited === $message->action
                    && 3 === $message->creatorUserItemId  // the item's creator (visibility)
                    && 7 === $message->actorUserItemId    // the editor, excluded from the fan-out
                    && 'The Editor' === $message->actorName;
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

    public function testGroupsAndTopicsNotifyForFeedParity(): void
    {
        // Groups and topics surface in the room/dashboard feed, so they notify too.
        foreach (['group', 'topic'] as $type) {
            $item = $this->item($type, isDraft: false);
            $item->method('getItemID')->willReturn(11);
            $item->method('getContextID')->willReturn(22);
            $item->method('getTitle')->willReturn('Org entry');
            $item->method('getCreatorID')->willReturn(3);
            $item->method('getModificatorItem')->willReturn(null);
            $item->method('getModificationDate')->willReturn('2026-06-16 09:30:00');

            $bus = $this->createMock(MessageBusInterface::class);
            $bus->expects($this->once())
                ->method('dispatch')
                ->with($this->callback(static fn (NotifyNewEntryMessage $m): bool => $m->sourceItemType === $type))
                ->willReturn(new Envelope(new \stdClass()));

            $this->subscriber($bus)->onSaved(new CommsyEditEvent($item));
        }
    }

    public function testAnnotationDispatchesAnnotatedSignalForTheParent(): void
    {
        $annotator = $this->createMock(cs_user_item::class);
        $annotator->method('getFullName')->willReturn('Cara Ann');

        $parent = $this->item('material');
        $parent->method('getItemID')->willReturn(70);
        $parent->method('getContextID')->willReturn(9);
        $parent->method('getTitle')->willReturn('Annotated material');
        $parent->method('getCreatorID')->willReturn(1);

        $annotation = $this->createMock(cs_item::class);
        $annotation->method('getCreatorID')->willReturn(88);
        $annotation->method('getCreatorItem')->willReturn($annotator);
        $annotation->method('getModificationDate')->willReturn('2026-06-18 08:00:00');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NotifyNewEntryMessage $message): bool {
                return 70 === $message->sourceItemId
                    && EntryAction::Annotated === $message->action
                    && 'Cara Ann' === $message->actorName
                    && 88 === $message->actorUserItemId   // the annotator, excluded from the fan-out
                    && 1 === $message->creatorUserItemId;  // the parent's creator (visibility only)
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->subscriber($bus)->onAnnotated(new ItemAnnotatedEvent($parent, $annotation));
    }

    public function testAnnotationOnNonNotifiableParentDispatchesNothing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->subscriber($bus)->onAnnotated(new ItemAnnotatedEvent($this->item('discussionarticle'), $this->createMock(cs_item::class)));
    }

    public function testItemDeletedRemovesItsNotifications(): void
    {
        $item = $this->createMock(cs_item::class);
        $item->method('getItemID')->willReturn(99);

        $repository = $this->createMock(NotificationRepository::class);
        $repository->expects($this->once())->method('removeForSourceItem')->with(99);

        $subscriber = new NotificationEventSubscriber(
            $this->createMock(MessageBusInterface::class),
            $repository,
            $this->factory(),
        );
        $subscriber->onItemDeleted(new ItemDeletedEvent($item));
    }

    private function item(string $type, bool $notActivated = false, bool $isDraft = false): cs_item
    {
        $item = $this->createMock(cs_item::class);
        $item->method('getItemType')->willReturn($type);
        $item->method('isNotActivated')->willReturn($notActivated);
        $item->method('isDraft')->willReturn($isDraft);

        return $item;
    }

    private function subscriber(MessageBusInterface $bus, ?NotificationPayloadFactory $factory = null): NotificationEventSubscriber
    {
        return new NotificationEventSubscriber(
            $bus,
            $this->createMock(NotificationRepository::class),
            $factory ?? $this->factory(),
        );
    }

    private function factory(NotificationPayload $payload = new NotificationPayload()): NotificationPayloadFactory
    {
        $factory = $this->createMock(NotificationPayloadFactory::class);
        $factory->method('fromItem')->willReturn($payload);

        return $factory;
    }
}
