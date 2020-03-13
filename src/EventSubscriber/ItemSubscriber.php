<?php


namespace App\EventSubscriber;


use App\Event\ItemDeletedEvent;
use App\Mail\Mailer;
use App\Mail\Messages\ItemDeletedMessage;
use App\Services\LegacyEnvironment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ItemSubscriber implements EventSubscriberInterface
{
    private $mailer;
    private $legacyEnvironment;

    public function __construct(Mailer $mailer, LegacyEnvironment $legacyEnvironment)
    {
        $this->mailer = $mailer;
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
        $item = $event->getItem();
        $context = $item->getContextItem();

        // Ignore events in a private room
        if ($context->isPrivateRoom()) {
            return;
        }

        // According to the legacy implementation we are only looking for the following types
        $allowedTypes = ['material', 'discussion', 'date', 'announcement'];
        if (!in_array($item->getType(), $allowedTypes)) {
            return;
        }

        // Grab all moderators who wants to get informed about room openings
        $moderatorRecipients = \App\Mail\RecipientFactory::createModerationRecipients($context, function (\cs_user_item $moderator) {
            return $moderator->getDeleteEntryWantMail();
        });

        $message = new ItemDeletedMessage($item, $this->legacyEnvironment->getCurrentUserItem());
        $this->mailer->sendMultiple($message, $moderatorRecipients);
    }
}