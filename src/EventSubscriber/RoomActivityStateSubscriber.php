<?php

namespace App\EventSubscriber;

use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\ZzzRoom;
use App\Mail\Factories\RoomMessageFactory;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Repository\PortalRepository;
use App\Room\RoomManager;
use App\Utils\ItemService;
use cs_room_item;
use DateInterval;
use DateTime;
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
     * @var RoomManager
     */
    private RoomManager $roomManager;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    /**
     * @var RoomMessageFactory
     */
    private RoomMessageFactory $roomMessageFactory;

    /**
     * @var Mailer
     */
    private Mailer $mailer;

    public function __construct(
        PortalRepository $portalRepository,
        RoomManager $roomManager,
        ItemService $itemService,
        RoomMessageFactory $messageFactory,
        Mailer $mailer
    ) {
        $this->portalRepository = $portalRepository;
        $this->roomManager = $roomManager;
        $this->itemService = $itemService;
        $this->roomMessageFactory = $messageFactory;
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
        if (!$portal) {
            $event->setBlocked(true);
            return;
        }

        if (!$portal->isClearInactiveRoomsFeatureEnabled()) {
            $event->setBlocked(true);
            return;
        }

        // Block if room is a template
        if ($room->getTemplate()) {
            $event->setBlocked(true);
            return;
        }

        // Block if not project or community type
        if (!$room->isProjectRoom() && !$room->isCommunityRoom()) {
            $event->setBlocked(true);
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

        if (!$room->getLastLogin() ||
            !$this->datePassedDays($room->getLastLogin(), $portal->getClearInactiveRoomsNotifyLockDays())
        ) {
            $event->setBlocked(true);
        }
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
        if (!$room->getActivityStateUpdated() ||
            !$this->datePassedDays($room->getActivityStateUpdated(), $portal->getClearInactiveRoomsLockDays())
        ) {
            $event->setBlocked(true);
        }
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
        if (!$room->getActivityStateUpdated() ||
            !$this->datePassedDays($room->getActivityStateUpdated(), $portal->getClearInactiveRoomsNotifyDeleteDays())
        ) {
            $event->setBlocked(true);
        }
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
        if (!$room->getActivityStateUpdated() ||
            !$this->datePassedDays($room->getActivityStateUpdated(), $portal->getClearInactiveRoomsDeleteDays())
        ) {
            $event->setBlocked(true);
        }
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

        $this->roomManager->renewActivityUpdated($room, false);
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

        $message = $this->roomMessageFactory->createRoomActivityLockWarningMessage($room);
        if ($message) {
            /** @var cs_room_item $legacyRoom */
            $legacyRoom = $this->itemService->getTypedItem($room->getItemId());
            if ($legacyRoom) {
                $this->mailer->sendMultiple($message, RecipientFactory::createModerationRecipients($legacyRoom));
            }
        }
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

        $legacyRoom = $this->itemService->getTypedItem($room->getItemId());
        if ($legacyRoom) {
            $legacyRoom->lock();
            $legacyRoom->save();
        }
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

        $message = $this->roomMessageFactory->createRoomActivityDeleteWarningMessage($room);
        if ($message) {
            /** @var cs_room_item $legacyRoom */
            $legacyRoom = $this->itemService->getTypedItem($room->getItemId());
            if ($legacyRoom) {
                $this->mailer->sendMultiple($message, RecipientFactory::createModerationRecipients($legacyRoom));
            }
        }
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

        $legacyRoom = $this->itemService->getTypedItem($room->getItemId());
        if ($legacyRoom) {
            $legacyRoom->delete();
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