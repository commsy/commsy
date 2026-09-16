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

use App\Entity\Account;
use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Message\NotifyNewEntryMessage;
use App\Repository\NotificationRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Subject\ItemViewSubject;
use Doctrine\ORM\EntityManagerInterface;

use function Symfony\Component\Clock\now;

/**
 * Fans an activity signal out into per-recipient notification rows: one row for
 * every active room member who may actually see the entry — including the event's
 * own actor, whose row is stored already read. That mirrors the feed it replaces,
 * which listed your own entries too, while keeping them out of the unread count.
 *
 * Pure modern persistence — no legacy environment is touched, so it runs safely
 * from an async message handler. Recipient visibility is gated through the same
 * {@see ItemViewChecker} the UI uses (ITEM_SEE), so a member who cannot see the
 * entry never gets notified about it. The view subject is built straight from
 * the signal snapshot rather than re-loading the rubric entity (which may
 * already be gone by the time the async handler runs); the room is looked up once
 * for both the title and the deleted-context flag.
 */
class NotificationManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationRepository $notificationRepository,
        private readonly UserRepository $userRepository,
        private readonly RoomRepository $roomRepository,
        private readonly ItemViewChecker $itemViewChecker,
    ) {
    }

    public function notifyNewEntry(NotifyNewEntryMessage $signal): void
    {
        $occurredAt = $signal->occurredAt ?? now();

        // Per-event idempotency: a messenger retry of the same event must not
        // duplicate the fan-out, while a genuine edit (a new event time) is
        // logged as another notification.
        if ($this->notificationRepository->existsForSourceItemAt($signal->sourceItemId, $occurredAt)) {
            return;
        }

        $room = $this->roomRepository->find($signal->contextId);

        $subject = new ItemViewSubject(
            itemId: $signal->sourceItemId,
            contextId: $signal->contextId,
            creatorId: $signal->creatorUserItemId,
            isDeactivated: $signal->isDeactivated,
            contextIsDeleted: $room === null || $room->getDeletionDate() !== null,
        );

        [$recipients, $actorAccountId] = $this->resolveRecipients($signal->contextId, $signal->actorUserItemId, $subject);
        if ($recipients === []) {
            return;
        }

        $roomTitle = $room?->getTitle() ?? '';

        foreach ($recipients as $accountId => $recipient) {
            $notification = new Notification(
                $recipient,
                NotificationType::Entry,
                $signal->contextId,
                $signal->title,
                $roomTitle,
                $occurredAt,
                $signal->sourceItemId,
                $signal->sourceItemType,
                $signal->actorName,
                $signal->action,
                NotificationPayload::fromArray($signal->payload),
            );

            // The actor sees their own activity in the panel, but never as unread.
            if ($accountId === $actorAccountId) {
                $notification->markRead($occurredAt);
            }

            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }

    /**
     * Active members of the room who may see the entry, de-duplicated per account,
     * together with the account id of the event's actor (its creator on a create,
     * the editor on an edit, the annotator on an annotation) so the caller can
     * store that row as already read. The actor id is null when the actor is not
     * an active member of the room (e.g. root).
     *
     * @return array{0: array<int, Account>, 1: int|null}
     */
    private function resolveRecipients(int $contextId, int $actorUserItemId, ItemViewSubject $subject): array
    {
        $recipients = [];
        $actorAccountId = null;

        foreach ($this->userRepository->findActiveUsers($contextId) as $user) {
            if (!$this->itemViewChecker->canSee($user, $subject)) {
                continue; // respect ITEM_SEE: only notify members who may see it
            }
            $account = $user->getAccount();
            if ($account === null) {
                continue; // legacy user row without a portal account
            }
            $recipients[$account->getId()] = $account;

            if ($user->getItemId() === $actorUserItemId) {
                $actorAccountId = $account->getId();
            }
        }

        return [$recipients, $actorAccountId];
    }
}
