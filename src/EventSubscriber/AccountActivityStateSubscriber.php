<?php

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
    /**
     * @var PortalRepository
     */
    private PortalRepository $portalRepository;

    /**
     * @var AccountManager
     */
    private AccountManager $accountManager;

    /**
     * @var AccountMessageFactory
     */
    private AccountMessageFactory $accountMessageFactory;

    /**
     * @var Mailer
     */
    private Mailer $mailer;

    public function __construct(
        PortalRepository $portalRepository,
        AccountManager $accountManager,
        AccountMessageFactory $messageFactory,
        Mailer $mailer
    ) {
        $this->portalRepository = $portalRepository;
        $this->accountManager = $accountManager;
        $this->accountMessageFactory = $messageFactory;
        $this->mailer = $mailer;
    }

    /**
     * Called on all transitions, perform general checks here
     *
     * @param GuardEvent $event
     */
    public function guard(GuardEvent $event)
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
        if ($account->getUsername() === 'root') {
            $event->setBlocked(true);
            return;
        }

        // Deny, if account is last moderator (this will also reset the account state)
        if ($this->accountManager->isLastModerator($account)) {
            $this->accountManager->resetInactivity($account, false, true);

            $event->setBlocked(true);
        }
    }

    /**
     * Decides if an account can make the transition to the active_notified state
     *
     * @param GuardEvent $event
     * @throws Exception
     */
    public function guardNotifyLock(GuardEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        /** @var Portal $portal */
        $portal = $this->portalRepository->find($account->getContextId());
        if (!$this->datePassedDays($account->getLastLogin(), $portal->getClearInactiveAccountsNotifyLockDays())) {
            $event->setBlocked(true);
        }
    }

    /**
     * Decides if an account can make the transition to the idle state
     *
     * @param GuardEvent $event
     * @throws Exception
     */
    public function guardLock(GuardEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        /** @var Portal $portal */
        $portal = $this->portalRepository->find($account->getContextId());
        if (!$this->datePassedDays($account->getActivityStateUpdated(), $portal->getClearInactiveAccountsLockDays())) {
            $event->setBlocked(true);
        }
    }

    /**
     * Decides if an account can make the transition to the idle_notified state
     *
     * @param GuardEvent $event
     * @throws Exception
     */
    public function guardNotifyForsake(GuardEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        // Deny transition if the inactive period is not long enough
        /** @var Portal $portal */
        $portal = $this->portalRepository->find($account->getContextId());
        if (!$this->datePassedDays($account->getActivityStateUpdated(), $portal->getClearInactiveAccountsNotifyDeleteDays())) {
            $event->setBlocked(true);
        }
    }

    /**
     * Decides if an account can make the transition to the abandoned state
     *
     * @param GuardEvent $event
     * @throws Exception
     */
    public function guardForsake(GuardEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        // Deny transition if the inactive period is not long enough
        /** @var Portal $portal */
        $portal = $this->portalRepository->find($account->getContextId());
        if (!$this->datePassedDays($account->getActivityStateUpdated(), $portal->getClearInactiveAccountsDeleteDays())) {
            $event->setBlocked(true);
        }
    }

    /**
     * The account has entered a new state and the marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function entered(EnteredEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        $this->accountManager->renewActivityUpdated($account);
    }

    /**
     * The account has entered the active_notified state. The marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function enteredActiveNotified(EnteredEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        $message = $this->accountMessageFactory->createAccountActivityLockWarningMessage($account);
        if ($message) {
            $this->mailer->send($message, RecipientFactory::createAccountRecipient($account));
        }
    }

    /**
     * The account has entered the idle state. The marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function enteredIdle(EnteredEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        $this->accountManager->lock($account);

        $message = $this->accountMessageFactory->createAccountActivityLockedMessage($account);
        if ($message) {
            $this->mailer->send($message, RecipientFactory::createAccountRecipient($account));
        }
    }

    /**
     * The account has entered the idle_notified state. The marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function enteredIdleNotified(EnteredEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        $message = $this->accountMessageFactory->createAccountActivityDeleteWarningMessage($account);
        if ($message) {
            $this->mailer->send($message, RecipientFactory::createAccountRecipient($account));
        }
    }

    /**
     * The account has entered the abandoned state. The marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function enteredAbandoned(EnteredEvent $event)
    {
        /** @var Account $account */
        $account = $event->getSubject();

        $this->accountManager->delete($account);

        $message = $this->accountMessageFactory->createAccountActivityDeletedMessage($account);
        if ($message) {
            $this->mailer->send($message, RecipientFactory::createAccountRecipient($account));
        }
    }

    /**
     * @param DateTime $compare
     * @param int $numDays
     * @return void
     * @throws Exception
     */
    private function datePassedDays(DateTime $compare, int $numDays): bool
    {
        $threshold = new DateTime();
        $threshold->sub(new DateInterval('P' . $numDays . 'D'));
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
            'workflow.account_activity.entered.active_notified' =>  ['enteredActiveNotified'],
            'workflow.account_activity.entered.idle' =>  ['enteredIdle'],
            'workflow.account_activity.entered.idle_notified' =>  ['enteredIdleNotified'],
            'workflow.account_activity.entered.abandoned' => ['enteredAbandoned'],
        ];
    }
}