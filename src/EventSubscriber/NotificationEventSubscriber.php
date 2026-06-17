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

namespace App\EventSubscriber;

use App\Enum\NotificationAction;
use App\Event\CommsyEditEvent;
use App\Event\ItemDeletedEvent;
use App\Event\ItemPublishedEvent;
use App\Message\NotifyNewEntryMessage;
use App\Notification\NotificationPayloadFactory;
use App\Repository\NotificationRepository;
use cs_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Bridges item lifecycle events into the activity-notifications feature, mirroring
 * what the room/dashboard feed surfaces:
 *  - publishing (undrafting) a top-level entry is a "created" event;
 *  - saving an already-published top-level entry is an "edited" event — each
 *    edit is logged as its own notification;
 *  - deleting an item drops its notifications.
 *
 * Create vs edit hinges on the draft flag. The create flow saves while the entry
 * is still a draft (CommsyEditEvent::SAVE, skipped here) and only becomes
 * notification-worthy at publish time (ItemPublishedEvent); a SAVE on a
 * non-draft entry is therefore a genuine edit. First-publish never double-fires
 * because the undraft step emits ItemPublishedEvent, not SAVE. Only top-level
 * content rubrics notify; sub-items (discussion article, step, section, …) are
 * intentionally absent. Purely additive — it reads existing events and never
 * touches a legacy write path.
 */
final readonly class NotificationEventSubscriber implements EventSubscriberInterface
{
    /**
     * Top-level rubrics that produce an activity notification — the same set the
     * room/dashboard feed surfaces (groups and topics included), minus user.
     * Kept as literals so this modern subscriber does not depend on the legacy
     * constant bootstrap. Sub-items (discussion article, step, section, …) are
     * absent here on purpose: their controllers dispatch the SAVE event for the
     * parent entry, so editing a sub-item notifies about the entry it belongs to.
     */
    private const NOTIFIABLE_TYPES = ['announcement', 'material', 'date', 'discussion', 'todo', 'group', 'topic'];

    public function __construct(
        private MessageBusInterface $messageBus,
        private NotificationRepository $notificationRepository,
        private NotificationPayloadFactory $payloadFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ItemPublishedEvent::NAME => 'onPublished',
            CommsyEditEvent::SAVE => 'onSaved',
            ItemDeletedEvent::NAME => 'onItemDeleted',
        ];
    }

    public function onPublished(ItemPublishedEvent $event): void
    {
        $this->dispatchFor($event->getItem(), NotificationAction::Created);
    }

    public function onSaved(CommsyEditEvent $event): void
    {
        $item = $event->getItem();

        // A draft save belongs to the create flow (it notifies via
        // ItemPublishedEvent); only saving an already-published entry is an edit.
        if ($item->isDraft()) {
            return;
        }

        $this->dispatchFor($item, NotificationAction::Edited);
    }

    public function onItemDeleted(ItemDeletedEvent $event): void
    {
        $this->notificationRepository->removeForSourceItem($event->getItem()->getItemID());
    }

    private function dispatchFor(cs_item $item, NotificationAction $action): void
    {
        if (!in_array($item->getItemType(), self::NOTIFIABLE_TYPES, true)) {
            return;
        }

        $this->messageBus->dispatch($this->signalFor($item, $action));
    }

    private function signalFor(cs_item $item, NotificationAction $action): NotifyNewEntryMessage
    {
        return new NotifyNewEntryMessage(
            $item->getItemID(),
            $item->getContextID(),
            $item->getItemType(),
            $item->getTitle(),
            $item->getCreatorID(),
            // The actor is whoever caused this event: the modificator (on a
            // create that is the creator, on an edit the editor).
            $item->getModificatorItem()?->getFullName(),
            (bool) $item->isNotActivated(),
            $action,
            $this->occurredAt($item),
            $this->payloadFactory->fromItem($item)->toArray(),
        );
    }

    private function occurredAt(cs_item $item): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $item->getModificationDate());

        return $parsed instanceof \DateTimeImmutable ? $parsed : new \DateTimeImmutable();
    }
}
