<?php


namespace App\EventSubscriber;


use App\Event\ItemDeletedEvent;
use App\Event\ItemReindexEvent;
use App\Mail\Mailer;
use App\Mail\Messages\ItemDeletedMessage;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ItemSubscriber implements EventSubscriberInterface
{
    private $mailer;
    private $itemService;
    private $legacyEnvironment;

    /**
     * @var ReaderService $readerService
     */
    private $readerService;

    public function __construct(Mailer $mailer, LegacyEnvironment $legacyEnvironment, ItemService $itemService, ReaderService $readerService)
    {
        $this->mailer = $mailer;
        $this->itemService = $itemService;
        $this->readerService = $readerService;
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
        $moderatorRecipients = \App\Mail\RecipientFactory::createModerationRecipients($context, function (\cs_user_item $moderator) {
            return $moderator->getDeleteEntryWantMail();
        });

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
     * @param \cs_item $item The item whose search index entry shall be updated.
     */
    private function updateSearchIndex(\cs_item $item) {
        if (method_exists($item, 'updateElastic')) {
            $item->updateElastic();

            // NOTE: read status cache items also get invalidated via the ReadStatusPreChangeEvent
            // which will be triggered when items get marked as read
            $this->readerService->invalidateCachedReadStatusForItem($item);
        }
    }
}
