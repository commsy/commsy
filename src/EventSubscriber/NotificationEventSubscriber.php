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

use App\Event\ItemDeletedEvent;
use App\Event\ItemPublishedEvent;
use App\Message\NotifyNewEntryMessage;
use App\Repository\NotificationRepository;
use cs_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Bridges item lifecycle events into the notifications feature:
 *  - when a top-level entry is published (undrafted), dispatches an async
 *    NotifyNewEntryMessage (the handler fans it out to the room members);
 *  - on item deletion, drops any notifications pointing at that item.
 *
 * Hooks ItemPublishedEvent rather than CommsyEditEvent::SAVE on purpose: SAVE
 * fires while the entry is still a draft (ItemService::undraft defers elastic
 * indexing for the same reason), so a new entry only becomes notification-worthy
 * at publish time. Purely additive — it reads existing events and never touches
 * a legacy write path. First-publish idempotency lives in the manager, so a
 * re-published entry never re-notifies.
 */
final readonly class NotificationEventSubscriber implements EventSubscriberInterface
{
    /**
     * Top-level rubrics that produce a "new entry" notification. Mirrors the
     * CS_*_TYPE constants but kept as literals so this modern subscriber does
     * not depend on the legacy constant bootstrap. Sub-items (section, step,
     * discussion article, …) are intentionally absent.
     */
    private const NOTIFIABLE_TYPES = ['announcement', 'material', 'date', 'discussion', 'todo'];

    public function __construct(
        private MessageBusInterface $messageBus,
        private NotificationRepository $notificationRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ItemPublishedEvent::NAME => 'onPublished',
            ItemDeletedEvent::NAME => 'onItemDeleted',
        ];
    }

    public function onPublished(ItemPublishedEvent $event): void
    {
        $item = $event->getItem();

        if (!in_array($item->getItemType(), self::NOTIFIABLE_TYPES, true)) {
            return;
        }

        $this->messageBus->dispatch($this->signalFor($item));
    }

    public function onItemDeleted(ItemDeletedEvent $event): void
    {
        $this->notificationRepository->removeForSourceItem($event->getItem()->getItemID());
    }

    private function signalFor(cs_item $item): NotifyNewEntryMessage
    {
        return new NotifyNewEntryMessage(
            $item->getItemID(),
            $item->getContextID(),
            $item->getItemType(),
            $item->getTitle(),
            $item->getCreatorID(),
            $item->getCreatorItem()?->getFullName(),
            (bool) $item->isNotActivated(),
        );
    }
}
