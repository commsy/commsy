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
use Doctrine\ORM\EntityManagerInterface;

/**
 * Fans a "new entry published" signal out into per-recipient notification rows:
 * one row for every active room member except the entry's creator.
 *
 * Pure modern persistence — no legacy environment is touched, so it runs safely
 * from an async message handler. The signal already carries the item-derived
 * snapshot (captured in-request by the subscriber), so the fan-out only needs
 * Doctrine lookups for the room title and the member list.
 */
class NotificationManager
{
    /**
     * Lowest room-membership status that should receive notifications: status
     * 0 (rejected) and 1 (pending request) are excluded, 2+ (member, moderator,
     * read-only) are included.
     */
    private const MIN_MEMBER_STATUS = 2;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationRepository $notificationRepository,
        private readonly UserRepository $userRepository,
        private readonly RoomRepository $roomRepository,
    ) {
    }

    public function notifyNewEntry(NotifyNewEntryMessage $signal): void
    {
        // First-publish idempotency: re-saving an entry must not re-notify.
        if ($this->notificationRepository->existsForSourceItem($signal->sourceItemId)) {
            return;
        }

        $recipients = $this->resolveRecipients($signal->contextId, $signal->creatorUserItemId);
        if ($recipients === []) {
            return;
        }

        $now = new \DateTimeImmutable();
        $roomTitle = $this->roomRepository->find($signal->contextId)?->getTitle() ?? '';

        foreach ($recipients as $recipient) {
            $this->entityManager->persist(new Notification(
                $recipient,
                NotificationType::NewEntry,
                $signal->contextId,
                $signal->title,
                $roomTitle,
                $now,
                $signal->sourceItemId,
                $signal->sourceItemType,
                $signal->actorName,
            ));
        }

        $this->entityManager->flush();
    }

    /**
     * Active members of the room minus the creator, de-duplicated per account.
     *
     * @return Account[]
     */
    private function resolveRecipients(int $contextId, int $creatorUserItemId): array
    {
        $recipients = [];

        foreach ($this->userRepository->findActiveUsers($contextId) as $user) {
            if ($user->getItemId() === $creatorUserItemId) {
                continue; // never notify the author about their own entry
            }
            if ($user->getStatus() < self::MIN_MEMBER_STATUS) {
                continue; // skip rejected / not-yet-approved membership requests
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
