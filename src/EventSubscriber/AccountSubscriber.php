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

use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Entity\SavedSearch;
use App\Event\AccountCreatedEvent;
use App\Event\AccountDeletedEvent;
use App\Mail\Mailer;
use App\Mail\Messages\AccountCreatedModerationMessage;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use cs_environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class AccountSubscriber implements EventSubscriberInterface
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Mailer $mailer,
        LegacyEnvironment $legacyEnvironment
    )
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents(): array
    {
        // NOTE: there's also an AccountChangedEvent which currently only UserRoomSubscriber subscribes to
        return [
            AccountDeletedEvent::class => 'onAccountDeleted',
            AccountCreatedEvent::class => 'onAccountCreated'
        ];
    }

    public function onAccountDeleted(AccountDeletedEvent $event): void
    {
        $portalUser = $event->getPortalUser();

        $repository = $this->entityManager->getRepository(SavedSearch::class);
        $repository->removeSavedSearchesByAccountId($portalUser->getItemID());
    }

    public function onAccountCreated(AccountCreatedEvent $event): void
    {
        $account = $event->getAccount();
        if (!$account->getAuthSource() instanceof AuthSourceLocal) {
            return;
        }

        $portalRepository = $this->entityManager->getRepository(Portal::class);

        /** @var Portal $portal */
        $portal = $portalRepository->find($account->getContextId());
        $portalModerators = $portal->getModeratorList($this->legacyEnvironment);
        $recipients = iterator_to_array(RecipientFactory::createRecipients(...$portalModerators));

        $message = new AccountCreatedModerationMessage($account);
        $this->mailer->sendMultiple($message, $recipients);
    }
}
