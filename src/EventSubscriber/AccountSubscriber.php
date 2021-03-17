<?php

namespace App\EventSubscriber;

use App\Entity\SavedSearch;
use App\Event\AccountDeletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AccountSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface $entityManager
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        $repository = $this->entityManager->getRepository(SavedSearch::class);
        $repository->removeSavedSearchesByAccountId($portalUser->getItemID());
    }
}
