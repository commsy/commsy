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

use App\Event\CommsyEditEvent;
use App\Event\ItemDeletedEvent;
use App\Message\NotifyNewEntryMessage;
use App\Repository\NotificationRepository;
use cs_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Bridges item lifecycle events into the notifications feature:
 *  - on save of a published top-level entry, dispatches an async
 *    NotifyNewEntryMessage (the handler fans it out to the room members);
 *  - on item deletion, drops any notifications pointing at that item.
 *
 * Purely additive — it only reads the existing CommsyEditEvent /
 * ItemDeletedEvent and never touches a legacy write path. First-publish
 * idempotency lives in the manager, so a re-saved entry never re-notifies.
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
            CommsyEditEvent::SAVE => 'onSave',
            ItemDeletedEvent::NAME => 'onItemDeleted',
        ];
    }

    public function onSave(CommsyEditEvent $event): void
    {
        $item = $event->getItem();

        if (!in_array($item->getItemType(), self::NOTIFIABLE_TYPES, true)) {
            return;
        }

        if ($item->isDraft()) {
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
