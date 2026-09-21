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

namespace App\Notification;

use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

use function Symfony\Component\Clock\now;

/**
 * Turns room membership requests into bell notifications: a task for the room's
 * moderators when someone asks to join, and word back to that person once the
 * request has been decided.
 *
 * Unlike the entry fan-out this runs synchronously — the audience is just the
 * room's moderators, needs no per-recipient visibility check, and a moderator
 * should see the task the moment the request arrives.
 *
 * The requesting user's item id is stored as the notification's source item, so
 * it doubles as the handle the bell needs to accept or reject the request, and
 * as the key under which the task is resolved for every moderator at once.
 */
class RoomMembershipNotifier
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationRepository $notificationRepository,
        private readonly UserRepository $userRepository,
        private readonly RoomRepository $roomRepository,
    ) {
    }

    /**
     * Someone asked to join: hand every moderator of the room a task.
     */
    public function requestReceived(int $roomId, int $requesterItemId, string $requesterName): void
    {
        if ($this->notificationRepository->existsOfTypeForSourceItem(NotificationType::RoomJoinRequest, $requesterItemId)) {
            return; // already announced
        }

        $roomTitle = $this->roomRepository->find($roomId)?->getTitle() ?? '';
        $occurredAt = now();
        $created = false;

        foreach ($this->userRepository->getModeratorsByRoomId($roomId) as $moderator) {
            $account = $moderator->getAccount();
            if ($account === null || $moderator->getItemId() === $requesterItemId) {
                continue;
            }

            $this->entityManager->persist(new Notification(
                $account,
                NotificationType::RoomJoinRequest,
                $roomId,
                $requesterName,
                $roomTitle,
                $occurredAt,
                $requesterItemId,
                'user',
                $requesterName,
                null,
                new NotificationPayload(actorId: $requesterItemId),
            ));
            $created = true;
        }

        if ($created) {
            $this->entityManager->flush();
        }
    }

    /**
     * Is a join request for this user still awaiting a decision?
     */
    private function hasOpenRequest(int $requesterItemId): bool
    {
        return $this->notificationRepository->existsOfTypeForSourceItem(NotificationType::RoomJoinRequest, $requesterItemId);
    }

    /**
     * The request was decided: clear the task for every moderator and tell the
     * person who asked. Returns silently when there was no open request.
     */
    public function requestDecided(int $roomId, int $requesterItemId, bool $accepted): void
    {
        if (!$this->hasOpenRequest($requesterItemId)) {
            return;
        }

        $this->notificationRepository->removeOfTypeForSourceItem(NotificationType::RoomJoinRequest, $requesterItemId);

        $account = $this->userRepository->findOneBy(['itemId' => $requesterItemId])?->getAccount();
        if ($account === null) {
            return; // no portal account to address
        }

        $roomTitle = $this->roomRepository->find($roomId)?->getTitle() ?? '';

        $this->notificationRepository->save(new Notification(
            $account,
            NotificationType::RoomJoinDecision,
            $roomId,
            $roomTitle,
            $roomTitle,
            now(),
            $requesterItemId,
            'user',
            null,
            null,
            new NotificationPayload(decision: $accepted ? 'accepted' : 'rejected'),
        ));
    }
}
