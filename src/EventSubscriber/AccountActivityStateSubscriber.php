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
use App\Entity\Account;
use App\Entity\Portal;
use App\Mail\Factories\AccountMessageFactory;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Repository\PortalRepository;
use DateInterval;
use DateTime;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

class AccountActivityStateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PortalRepository $portalRepository,
        private AccountManager $accountManager,
        private AccountMessageFactory $accountMessageFactory,
        private Mailer $mailer
    ) {
    }

    /**
     * Called on all transitions, perform general checks here.
     */
    public function guard(GuardEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        // Block all transitions if the portal configuration has disabled the account activity feature
        /** @var Portal $portal */
        $portal = $this->portalRepository->find($account->getContextId());
        if (!$portal->isClearInactiveAccountsFeatureEnabled()) {
            $event->setBlocked(true);

            return;
        }

        // Deny, if the account is the root account
        if ('root' === $account->getUsername()) {
            $event->setBlocked(true);

            return;
        }

        // Deny, if account is last moderator (this will also reset the account state)
        if ($this->accountManager->isLastModerator($account)) {
            $this->accountManager->resetInactivity($account, false, true, false);

            $event->setBlocked(true);
        }
    }

    /**
     * Decides if an account can make the transition to the active_notified state.
     *
     * @throws Exception
     */
    public function guardNotifyLock(GuardEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        /** @var Portal $portal */
        $portal = $this->portalRepository->find($account->getContextId());
        if (!$account->getLastLogin() ||
            !$this->datePassedDays($account->getLastLogin(), $portal->getClearInactiveAccountsNotifyLockDays())
        ) {
            $event->setBlocked(true);
        }
    }

    /**
     * Decides if an account can make the transition to the idle state.
     *
     * @throws Exception
     */
    public function guardLock(GuardEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        /** @var Portal $portal */
        $portal = $this->portalRepository->find($account->getContextId());
        if (!$account->getActivityStateUpdated() ||
            !$this->datePassedDays($account->getActivityStateUpdated(), $portal->getClearInactiveAccountsLockDays())
        ) {
            $event->setBlocked(true);
        }
    }

    /**
     * Decides if an account can make the transition to the idle_notified state.
     *
     * @throws Exception
     */
    public function guardNotifyForsake(GuardEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        // Deny transition if the inactive period is not long enough
        /** @var Portal $portal */
        $portal = $this->portalRepository->find($account->getContextId());
        if (!$account->getActivityStateUpdated() ||
            !$this->datePassedDays($account->getActivityStateUpdated(), $portal->getClearInactiveAccountsNotifyDeleteDays())
        ) {
            $event->setBlocked(true);
        }
    }

    /**
     * Decides if an account can make the transition to the abandoned state.
     *
     * @throws Exception
     */
    public function guardForsake(GuardEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        // Deny transition if the inactive period is not long enough
        /** @var Portal $portal */
        $portal = $this->portalRepository->find($account->getContextId());
        if (!$account->getActivityStateUpdated() ||
            !$this->datePassedDays($account->getActivityStateUpdated(), $portal->getClearInactiveAccountsDeleteDays())
        ) {
            $event->setBlocked(true);
        }
    }

    /**
     * The account has entered a new state and the marking is updated.
     */
    public function entered(EnteredEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        $this->accountManager->renewActivityUpdated($account, false);
    }

    /**
     * The account has entered the active_notified state. The marking is updated.
     */
    public function enteredActiveNotified(EnteredEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        // Skip sending an email if the account is already locked (manually)
        if ($account->isLocked()) {
            return;
        }

        $message = $this->accountMessageFactory->createAccountActivityLockWarningMessage($account);
        if ($message) {
            $this->mailer->send($message, RecipientFactory::createFromAccount($account));
        }
    }

    /**
     * The account has entered the idle state. The marking is updated.
     */
    public function enteredIdle(EnteredEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        // Skip sending an email if the account is already locked (manually)
        if ($account->isLocked()) {
            return;
        }

        $this->accountManager->lock($account);

        $message = $this->accountMessageFactory->createAccountActivityLockedMessage($account);
        if ($message) {
            $this->mailer->send($message, RecipientFactory::createFromAccount($account));
        }
    }

    /**
     * The account has entered the idle_notified state. The marking is updated.
     */
    public function enteredIdleNotified(EnteredEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        $message = $this->accountMessageFactory->createAccountActivityDeleteWarningMessage($account);
        if ($message) {
            $this->mailer->send($message, RecipientFactory::createFromAccount($account));
        }
    }

    /**
     * The account has entered the abandoned state. The marking is updated.
     */
    public function enteredAbandoned(EnteredEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getSubject();

        $this->accountManager->delete($account);

        $message = $this->accountMessageFactory->createAccountActivityDeletedMessage($account);
        if ($message) {
            $this->mailer->send($message, RecipientFactory::createFromAccount($account));
        }
    }

    /**
     * @param DateTime $compare
     * @param int $numDays
     * @return bool
     *
     * @throws Exception
     */
    private function datePassedDays(DateTime $compare, int $numDays): bool
    {
        $threshold = new DateTime();
        $threshold->sub(new DateInterval('P'.$numDays.'D'));

        return $compare < $threshold;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.account_activity.guard' => ['guard'],
            'workflow.account_activity.guard.notify_lock' => ['guardNotifyLock'],
            'workflow.account_activity.guard.lock' => ['guardLock'],
            'workflow.account_activity.guard.notify_forsake' => ['guardNotifyForsake'],
            'workflow.account_activity.guard.forsake' => ['guardForsake'],
            'workflow.account_activity.entered' => ['entered'],
            'workflow.account_activity.entered.active_notified' => ['enteredActiveNotified'],
            'workflow.account_activity.entered.idle' => ['enteredIdle'],
            'workflow.account_activity.entered.idle_notified' => ['enteredIdleNotified'],
            'workflow.account_activity.entered.abandoned' => ['enteredAbandoned'],
        ];
    }
}
