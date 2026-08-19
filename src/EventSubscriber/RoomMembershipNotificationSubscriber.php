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

use App\Event\UserJoinedRoomEvent;
use App\Event\UserStatusChangedEvent;
use App\Notification\RoomMembershipNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Bridges room membership events into bell notifications.
 *
 * Both events already exist and fire on every path that matters
 * ({@see \App\Controller\ContextController::request} for the join flow,
 * {@see \App\Controller\UserController::changeStatus} for a moderator's
 * decision), so this stays purely additive — no controller is touched.
 *
 * A join is only a task when it still awaits moderation (status "requested");
 * a direct join needs no decision. On the way out, a status change is only
 * treated as a decision when an open request notification exists for that user,
 * which keeps ordinary status changes (moderator, read-only, contact person)
 * out of it.
 */
final readonly class RoomMembershipNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RoomMembershipNotifier $notifier,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserJoinedRoomEvent::class => 'onUserJoinedRoom',
            UserStatusChangedEvent::class => 'onUserStatusChanged',
        ];
    }

    public function onUserJoinedRoom(UserJoinedRoomEvent $event): void
    {
        $user = $event->getUser();

        if (!$user->isRequested()) {
            return; // joined outright, nothing to decide
        }

        $this->notifier->requestReceived(
            (int) $event->getRoom()->getItemID(),
            (int) $user->getItemID(),
            (string) $user->getFullName(),
        );
    }

    public function onUserStatusChanged(UserStatusChangedEvent $event): void
    {
        $user = $event->getUser();

        if ($user->isRequested()) {
            return; // still pending
        }

        $this->notifier->requestDecided(
            (int) $user->getContextID(),
            (int) $user->getItemID(),
            !$user->isRejected(),
        );
    }
}
