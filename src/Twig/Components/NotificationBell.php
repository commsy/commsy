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

namespace App\Twig\Components;

use App\Entity\Account;
use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use App\Room\RoomMembershipDecider;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use function Symfony\Component\Clock\now;

/**
 * The navbar bell: everything addressed to the person rather than to a room's
 * content — join-request tasks a moderator can decide right here, and word back
 * about a request of one's own. Entry activity is deliberately absent; it lives
 * in the room and dashboard panels.
 *
 * There is no page behind the bell, so the dropdown is the whole surface: the
 * newest few notifications, the decision buttons, and mark-all-read for the
 * informational ones. Tasks are never marked read — they count until they are
 * decided, otherwise the badge would fall silent while work is still waiting.
 */
#[AsLiveComponent]
final class NotificationBell
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Account $account = null;

    /** Picks UIKit 3 vs UIKit 2 markup, matching the surrounding navbar. */
    #[LiveProp]
    public bool $uikit3 = false;

    /** Set when a decision came too late, so the dropdown can say so. */
    #[LiveProp(writable: true)]
    public bool $alreadyDecided = false;

    private const LIMIT = 10;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly RoomMembershipDecider $membershipDecider,
    ) {
    }

    #[LiveAction]
    public function accept(#[LiveArg] int $id): void
    {
        $this->decide($id, true);
    }

    #[LiveAction]
    public function reject(#[LiveArg] int $id): void
    {
        $this->decide($id, false);
    }

    #[LiveAction]
    public function markAllRead(): void
    {
        if ($this->account === null) {
            return;
        }

        // Only the informational ones: an open task must stay countable.
        $readable = array_values(array_filter(
            NotificationType::bellTypes(),
            static fn (NotificationType $type): bool => !$type->isTask(),
        ));

        $this->notificationRepository->markAllReadForAccount($this->account, now(), null, $readable);
    }

    /**
     * @return Notification[]
     */
    public function getNotifications(): array
    {
        return $this->account !== null
            ? $this->notificationRepository->findForAccount($this->account, null, self::LIMIT, NotificationType::bellTypes())
            : [];
    }

    public function getUnreadCount(): int
    {
        return $this->account !== null
            ? $this->notificationRepository->countUnreadForAccount($this->account, null, NotificationType::bellTypes())
            : 0;
    }

    /**
     * $id is the requesting user's item id, carried as the task's source item.
     */
    private function decide(int $id, bool $accept): void
    {
        if ($this->account === null) {
            return;
        }

        // Act only on a task actually addressed to this account.
        if (!$this->notificationRepository->existsOfTypeForSourceItem(NotificationType::RoomJoinRequest, $id)) {
            $this->alreadyDecided = true;

            return;
        }

        try {
            $decided = $this->membershipDecider->decide($id, $accept);
        } catch (AccessDeniedException) {
            // No longer a moderator of that room: drop the stale task quietly.
            $this->notificationRepository->removeOfTypeForSourceItem(NotificationType::RoomJoinRequest, $id);
            $this->alreadyDecided = true;

            return;
        }

        if (!$decided) {
            // Someone got there first — resolve the leftover task silently.
            $this->notificationRepository->removeOfTypeForSourceItem(NotificationType::RoomJoinRequest, $id);
            $this->alreadyDecided = true;
        }
    }
}
