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
use App\Mail\Mailer;
use App\Mail\Messages\ItemDeletedMessage;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use cs_user_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Notification-side-effects on item lifecycle events.
 *
 * ES reindexing and read-status cache invalidation used to live here as well,
 * but have been split out into {@see ElasticaSubscriber::onItemReindex()} and
 * {@see ReadStatusSubscriber::onItemReindex()} so each subscriber now owns a
 * single fachliche responsibility.
 */
class ItemSubscriber implements EventSubscriberInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly Mailer $mailer,
        LegacyEnvironment $legacyEnvironment,
        private readonly ItemService $itemService,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ItemDeletedEvent::NAME => 'onItemDeleted',
        ];
    }

    public function onItemDeleted(ItemDeletedEvent $event): void
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
}
