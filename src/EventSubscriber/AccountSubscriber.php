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

use App\Entity\SavedSearch;
use App\Event\AccountDeletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AccountSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
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
