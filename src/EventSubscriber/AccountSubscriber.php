<?php

namespace App\EventSubscriber;

use App\Event\AccountDeletedEvent;
use App\Services\SavedSearchesService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AccountSubscriber implements EventSubscriberInterface
{
    /**
     * @var SavedSearchesService
     */
    private $savedSearchesService;

    public function __construct(SavedSearchesService $savedSearchesService)
    {
        $this->savedSearchesService = $savedSearchesService;
    }

    public static function getSubscribedEvents()
    {
        // NOTE: there's also an AccountChangedEvent which currently only UserRoomSubscriber subscribes to
        return [
            AccountDeletedEvent::class => 'onAccountDeleted',
        ];
    }

    public function onAccountDeleted(AccountDeletedEvent $event)
    {
        $portalUser = $event->getPortalUser();

        if (!$portalUser) {
            return;
        }

        $this->savedSearchesService->removeSavedSearchesForAccountId($portalUser->getItemID());
    }
}
