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

/**
 * Fans a "new entry published" signal out into per-recipient notification rows:
 * one row for every active room member who may actually see the entry, except
 * the entry's creator.
 *
 * Pure modern persistence — no legacy environment is touched, so it runs safely
 * from an async message handler. Recipient visibility is gated through the same
 * {@see ItemViewChecker} the UI uses (ITEM_SEE), so a member who cannot see the
 * entry never gets notified about it. The view subject is built straight from
 * the signal snapshot rather than re-loading the rubric entity (which may
 * already be gone by the time the async handler runs): top-level rubric items
 * never carry overwritten content, and the room is looked up once for both the
 * title and the deleted-context flag.
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
        $occurredAt = $signal->occurredAt ?? new \DateTimeImmutable();

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
            hasOverwrittenContent: false,
        );

        $recipients = $this->resolveRecipients($signal->contextId, $signal->creatorUserItemId, $subject);
        if ($recipients === []) {
            return;
        }

        $roomTitle = $room?->getTitle() ?? '';

        foreach ($recipients as $recipient) {
            $this->entityManager->persist(new Notification(
                $recipient,
                NotificationType::NewEntry,
                $signal->contextId,
                $signal->title,
                $roomTitle,
                $occurredAt,
                $signal->sourceItemId,
                $signal->sourceItemType,
                $signal->actorName,
                $signal->action,
            ));
        }

        $this->entityManager->flush();
    }

    /**
     * Active members of the room who may see the entry, minus the creator,
     * de-duplicated per account.
     *
     * @return Account[]
     */
    private function resolveRecipients(int $contextId, int $creatorUserItemId, ItemViewSubject $subject): array
    {
        $recipients = [];

        foreach ($this->userRepository->findActiveUsers($contextId) as $user) {
            if ($user->getItemId() === $creatorUserItemId) {
                continue; // never notify the author about their own entry
            }
            if (!$this->itemViewChecker->canSee($user, $subject)) {
                continue; // respect ITEM_SEE: only notify members who may see it
            }
            $account = $user->getAccount();
            if ($account === null) {
                continue; // legacy user row without a portal account
            }
            $recipients[$account->getId()] = $account;
        }

        return $recipients;
    }
}
