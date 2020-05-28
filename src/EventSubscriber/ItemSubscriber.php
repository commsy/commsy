<?php


namespace App\EventSubscriber;


use App\Event\ItemDeletedEvent;
use App\Mail\Mailer;
use App\Mail\Messages\ItemDeletedMessage;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ItemSubscriber implements EventSubscriberInterface
{
    private $mailer;
    private $itemService;
    private $legacyEnvironment;

    public function __construct(Mailer $mailer, LegacyEnvironment $legacyEnvironment, ItemService $itemService)
    {
        $this->mailer = $mailer;
        $this->itemService = $itemService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents()
    {
        return [
            ItemDeletedEvent::NAME => 'onItemDeleted',
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
}