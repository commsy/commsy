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
use App\Event\ItemReindexEvent;
use App\Mail\Mailer;
use App\Mail\Messages\ItemDeletedMessage;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use cs_item;
use cs_user_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ItemSubscriber implements EventSubscriberInterface
{
    private $legacyEnvironment;

    public function __construct(private Mailer $mailer, LegacyEnvironment $legacyEnvironment, private ItemService $itemService, private ReaderService $readerService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents()
    {
        return [
            ItemDeletedEvent::NAME => 'onItemDeleted',
            ItemReindexEvent::class => 'onItemReindex',
        ];
    }

    public function onItemDeleted(ItemDeletedEvent $event)
    {
        $typedItem = $event->getItem();

        $item = $this->itemService->getItem($typedItem->getItemID());
        if ($item->isDraft()) {
            return;
        }

        $context = $typedItem->getContextItem();

        // Ignore events in a private room
        if ($context->isPrivateRoom()) {
            return;
        }

        // According to the legacy implementation we are only looking for the following types
        $allowedTypes = ['material', 'discussion', 'date', 'announcement'];
        if (!in_array($typedItem->getType(), $allowedTypes)) {
            return;
        }

        // Grab all moderators who want to get informed about item deletions
        $moderatorRecipients = RecipientFactory::createModerationRecipients($context, fn (cs_user_item $moderator) => $moderator->getDeleteEntryWantMail());

        $message = new ItemDeletedMessage($typedItem, $this->legacyEnvironment->getCurrentUserItem());
        $this->mailer->sendMultiple($message, $moderatorRecipients);
    }

    public function onItemReindex(ItemReindexEvent $event)
    {
        if ($event->getItem()) {
            $typedItem = $event->getItem();

            $this->updateSearchIndex($typedItem);
        }
    }

    /**
     * Updates the Elastic search index for the given item, and invalidates its cached read status.
     *
     * @param cs_item $item the item whose search index entry shall be updated
     */
    private function updateSearchIndex(cs_item $item)
    {
        if (method_exists($item, 'updateElastic')) {
            $item->updateElastic();

            // NOTE: read status cache items also get invalidated via the ReadStatusPreChangeEvent
            // which will be triggered when items get marked as read
            $this->readerService->invalidateCachedReadStatusForItem($item);
        }
    }
}
