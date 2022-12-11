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

use App\Entity\Portal;
use App\Entity\Room;
use App\Mail\Factories\RoomMessageFactory;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Repository\PortalRepository;
use App\Room\RoomManager;
use App\Utils\ItemService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

class RoomActivityStateSubscriber implements EventSubscriberInterface
{
    public function __construct(private PortalRepository $portalRepository, private RoomManager $roomManager, private ItemService $itemService, private RoomMessageFactory $roomMessageFactory, private Mailer $mailer)
    {
    }

    /**
     * Called on all transitions, perform general checks here.
     */
    public function guard(GuardEvent $event)
    {
        /** @var Room $room */
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

        // Deny, if community room has linked project rooms (this will also reset the room state)
        if ($room->isCommunityRoom()) {
            if ($this->roomManager->getLinkedProjectRooms($room)->getCount() > 0) {
                $this->roomManager->resetInactivity($room, false, true, false);

                $event->setBlocked(true);
            }
        }
    }

    /**
     * Decides if a room can make the transition to the active_notified state.
     *
     * @throws \Exception
     */
    public function guardNotifyLock(GuardEvent $event)
    {
        /** @var Room $room */
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
     * Decides if a room can make the transition to the idle state.
     *
     * @throws \Exception
     */
    public function guardLock(GuardEvent $event)
    {
        /** @var Room $room */
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
     * Decides if a room can make the transition to the idle_notified state.
     *
     * @throws \Exception
     */
    public function guardNotifyForsake(GuardEvent $event)
    {
        /** @var Room $room */
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
     * Decides if a room can make the transition to the abandoned state.
     *
     * @throws \Exception
     */
    public function guardForsake(GuardEvent $event)
    {
        /** @var Room $room */
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
     */
    public function entered(EnteredEvent $event)
    {
        /** @var Room $room */
        $room = $event->getSubject();

        $this->roomManager->renewActivityUpdated($room, false);
    }

    /**
     * The room has entered the active_notified state. The marking is updated.
     */
    public function enteredActiveNotified(EnteredEvent $event)
    {
        /** @var Room $room */
        $room = $event->getSubject();

        $message = $this->roomMessageFactory->createRoomActivityLockWarningMessage($room);
        if ($message) {
            /** @var \cs_room_item $legacyRoom */
            $legacyRoom = $this->itemService->getTypedItem($room->getItemId());
            if ($legacyRoom) {
                $this->mailer->sendMultiple($message, RecipientFactory::createModerationRecipients($legacyRoom));
            }
        }
    }

    /**
     * The room has entered the idle state. The marking is updated.
     */
    public function enteredIdle(EnteredEvent $event)
    {
        /** @var Room $room */
        $room = $event->getSubject();

        $legacyRoom = $this->itemService->getTypedItem($room->getItemId());
        if ($legacyRoom) {
            $legacyRoom->lock();
            $legacyRoom->save();
        }
    }

    /**
     * The room has entered the idle_notified state. The marking is updated.
     */
    public function enteredIdleNotified(EnteredEvent $event)
    {
        /** @var Room $room */
        $room = $event->getSubject();

        $message = $this->roomMessageFactory->createRoomActivityDeleteWarningMessage($room);
        if ($message) {
            /** @var \cs_room_item $legacyRoom */
            $legacyRoom = $this->itemService->getTypedItem($room->getItemId());
            if ($legacyRoom) {
                $this->mailer->sendMultiple($message, RecipientFactory::createModerationRecipients($legacyRoom));
            }
        }
    }

    /**
     * The room has entered the abandoned state. The marking is updated.
     */
    public function enteredAbandoned(EnteredEvent $event)
    {
        /** @var Room $room */
        $room = $event->getSubject();

        $legacyRoom = $this->itemService->getTypedItem($room->getItemId());
        if ($legacyRoom) {
            $legacyRoom->delete();
        }
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    private function datePassedDays(\DateTime $compare, int $numDays): bool
    {
        $threshold = new \DateTime();
        $threshold->sub(new \DateInterval('P'.$numDays.'D'));

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
            'workflow.room_activity.entered.active_notified' => ['enteredActiveNotified'],
            'workflow.room_activity.entered.idle' => ['enteredIdle'],
            'workflow.room_activity.entered.idle_notified' => ['enteredIdleNotified'],
            'workflow.room_activity.entered.abandoned' => ['enteredAbandoned'],
        ];
    }
}
