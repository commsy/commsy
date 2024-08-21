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

use App\Account\AccountManager;
use App\Account\AccountSetting;
use App\Account\AccountSettingsManager;
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
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class AccountSubscriber implements EventSubscriberInterface
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Mailer $mailer,
        private AccountManager $accountManager,
        private AccountSettingsManager $settingsManager,
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

        $filteredModerators = array_filter(iterator_to_array($portalModerators), function (cs_user_item $moderator): bool {
            $moderatorAccount = $this->accountManager->getAccount($moderator, $moderator->getContextID());
            $setting = $this->settingsManager->getSetting(
                $moderatorAccount,
                AccountSetting::NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION);

            return $setting['enabled'] === true;
        });


        $recipients = iterator_to_array(RecipientFactory::createRecipients(...$filteredModerators));

        $message = new AccountCreatedModerationMessage($account);
        $this->mailer->sendMultiple($message, $recipients);
    }
}
