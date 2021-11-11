<?php

namespace App\EventSubscriber;

use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\ZzzRoom;
use App\Mail\Factories\AccountMessageFactory;
use App\Mail\Mailer;
use App\Repository\PortalRepository;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

class RoomActivityStateSubscriber implements EventSubscriberInterface
{
    /**
     * @var PortalRepository
     */
    private PortalRepository $portalRepository;

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
        AccountMessageFactory $messageFactory,
        Mailer $mailer
    ) {
        $this->portalRepository = $portalRepository;
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
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

        // Block all transitions if the portal configuration has disabled the account activity feature
        /** @var Portal $portal */
        $portal = $this->portalRepository->find($room->getContextId());
        if (!$portal->isClearInactiveRoomsFeatureEnabled()) {
            $event->setBlocked(true);
            return;
        }
    }

    /**
     * Decides if a room can make the transition to the active_notified state
     *
     * @param GuardEvent $event
     * @throws Exception
     */
    public function guardNotifyLock(GuardEvent $event)
    {
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

        /** @var Portal $portal */
        $portal = $this->portalRepository->find($room->getContextId());
//        if (!$this->datePassedDays($account->getLastLogin(), $portal->getClearInactiveAccountsNotifyLockDays())) {
//            $event->setBlocked(true);
//        }
    }

    /**
     * Decides if a room can make the transition to the idle state
     *
     * @param GuardEvent $event
     * @throws Exception
     */
    public function guardLock(GuardEvent $event)
    {
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

        /** @var Portal $portal */
        $portal = $this->portalRepository->find($room->getContextId());
//        if (!$this->datePassedDays($account->getActivityStateUpdated(), $portal->getClearInactiveAccountsLockDays())) {
//            $event->setBlocked(true);
//        }
    }

    /**
     * Decides if a room can make the transition to the idle_notified state
     *
     * @param GuardEvent $event
     * @throws Exception
     */
    public function guardNotifyForsake(GuardEvent $event)
    {
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

        // Deny transition if the inactive period is not long enough
        /** @var Portal $portal */
        $portal = $this->portalRepository->find($room->getContextId());
//        if (!$this->datePassedDays($account->getActivityStateUpdated(), $portal->getClearInactiveAccountsNotifyDeleteDays())) {
//            $event->setBlocked(true);
//        }
    }

    /**
     * Decides if a room can make the transition to the abandoned state
     *
     * @param GuardEvent $event
     * @throws Exception
     */
    public function guardForsake(GuardEvent $event)
    {
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

        // Deny transition if the inactive period is not long enough
        /** @var Portal $portal */
        $portal = $this->portalRepository->find($room->getContextId());
//        if (!$this->datePassedDays($account->getActivityStateUpdated(), $portal->getClearInactiveAccountsDeleteDays())) {
//            $event->setBlocked(true);
//        }
    }

    /**
     * The room has entered a new state and the marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function entered(EnteredEvent $event)
    {
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

//        $this->accountManager->renewActivityUpdated($account);
    }

    /**
     * The room has entered the active_notified state. The marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function enteredActiveNotified(EnteredEvent $event)
    {
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

//        $message = $this->accountMessageFactory->createAccountActivityLockWarningMessage($account);
//        if ($message) {
//            $this->mailer->send($message, RecipientFactory::createAccountRecipient($account));
//        }
    }

    /**
     * The room has entered the idle state. The marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function enteredIdle(EnteredEvent $event)
    {
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

//        $this->accountManager->lock($account);
//
//        $message = $this->accountMessageFactory->createAccountActivityLockedMessage($account);
//        if ($message) {
//            $this->mailer->send($message, RecipientFactory::createAccountRecipient($account));
//        }
    }

    /**
     * The room has entered the idle_notified state. The marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function enteredIdleNotified(EnteredEvent $event)
    {
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

//        $message = $this->accountMessageFactory->createAccountActivityDeleteWarningMessage($account);
//        if ($message) {
//            $this->mailer->send($message, RecipientFactory::createAccountRecipient($account));
//        }
    }

    /**
     * The room has entered the abandoned state. The marking is updated.
     *
     * @param EnteredEvent $event
     */
    public function enteredAbandoned(EnteredEvent $event)
    {
        /** @var Room|ZzzRoom $room */
        $room = $event->getSubject();

//        $this->accountManager->delete($account);
//
//        $message = $this->accountMessageFactory->createAccountActivityDeletedMessage($account);
//        if ($message) {
//            $this->mailer->send($message, RecipientFactory::createAccountRecipient($account));
//        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.room_activity.guard' => ['guard'],
            'workflow.room_activity.guard.notify_lock' => ['guardNotifyLock'],
            'workflow.room_activity.guard.lock' => ['guardLock'],
            'workflow.room_activity.guard.notify_forsake' => ['guardNotifyForsake'],
            'workflow.room_activity.guard.forsake' => ['guardForsake'],
            'workflow.room_activity.entered' => ['entered'],
            'workflow.room_activity.entered.active_notified' =>  ['enteredActiveNotified'],
            'workflow.room_activity.entered.idle' =>  ['enteredIdle'],
            'workflow.room_activity.entered.idle_notified' =>  ['enteredIdleNotified'],
            'workflow.room_activity.entered.abandoned' => ['enteredAbandoned'],
        ];
    }
}