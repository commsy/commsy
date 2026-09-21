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
use App\Notification\NotificationLinkResolver;
use App\Notification\RoomActivitySummary;
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
 * The navbar bell, in two sections: what waits for a decision stays on top,
 * everything that merely informs sits below a divider. The lower section also
 * carries entry activity, but condensed to one line per room ("3 neu angelegt,
 * 5 bearbeitet") rather than entry by entry — the panels remain the place for
 * the detail.
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

    /**
     * Rendering asks for both of these several times — the badge alone is read
     * three times in the markup — and each ask is a query. They are remembered
     * for the length of one request and dropped again whenever an action
     * changes what they count.
     *
     * @var RoomActivitySummary[]|null
     */
    private ?array $roomActivity = null;
    private ?int $unreadCount = null;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly RoomMembershipDecider $membershipDecider,
        private readonly NotificationLinkResolver $linkResolver,
    ) {
    }

    /**
     * Where a row leads. Rows that name an entry link straight to it; the rest
     * fall back to the room they happened in.
     */
    public function linkFor(Notification $notification): ?string
    {
        return $this->linkResolver->resolve($notification);
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

        // Everything except open tasks — including the entry activity behind the
        // room lines, which is the same set of rows the panels show.
        $readable = array_values(array_filter(
            NotificationType::cases(),
            static fn (NotificationType $type): bool => !$type->awaitsDecision(),
        ));

        $this->notificationRepository->markAllReadForAccount($this->account, now(), null, $readable);
        $this->forgetCounts();
    }

    /**
     * The upper section: notifications waiting for a decision.
     *
     * @return Notification[]
     */
    public function getTasks(): array
    {
        return $this->ofTypes(static fn (NotificationType $type): bool => $type->awaitsDecision());
    }

    /**
     * The lower section: notifications that only inform. Read ones are dropped —
     * marking them read is how you say you have taken note, and unlike a task
     * there is nothing left to come back to. Room activity is summarised
     * separately, so it is excluded here.
     *
     * @return Notification[]
     */
    public function getMessages(): array
    {
        return $this->ofTypes(
            static fn (NotificationType $type): bool => !$type->awaitsDecision() && !$type->isContentActivity(),
            unreadOnly: true,
        );
    }

    /**
     * The lower section's room activity: one condensed line per room.
     *
     * @return RoomActivitySummary[]
     */
    public function getRoomActivity(): array
    {
        return $this->roomActivity ??= $this->account !== null
            ? $this->notificationRepository->summariseUnreadEntryActivity($this->account)
            : [];
    }

    /**
     * What the badge shows: the number of unread things visible in the dropdown.
     * A room counts once, however much happened inside it — the sum of changed
     * entries would be a confusing number to put on a bell.
     */
    public function getUnreadCount(): int
    {
        if ($this->account === null) {
            return 0;
        }

        if ($this->unreadCount !== null) {
            return $this->unreadCount;
        }

        $withoutActivity = array_values(array_filter(
            NotificationType::cases(),
            static fn (NotificationType $type): bool => !$type->isContentActivity(),
        ));

        return $this->unreadCount = $this->notificationRepository->countUnreadForAccount($this->account, null, $withoutActivity)
            + count($this->getRoomActivity());
    }

    /**
     * @param callable(NotificationType): bool $matches
     *
     * @return Notification[]
     */
    private function ofTypes(callable $matches, bool $unreadOnly = false): array
    {
        if ($this->account === null) {
            return [];
        }

        $types = array_values(array_filter(NotificationType::cases(), $matches));

        return $types === []
            ? []
            : $this->notificationRepository->findForAccount($this->account, null, self::LIMIT, $types, $unreadOnly);
    }

    /**
     * Anything that changes what the bell counts has to drop what it remembered.
     */
    private function forgetCounts(): void
    {
        $this->roomActivity = null;
        $this->unreadCount = null;
    }

    /**
     * $id is the requesting user's item id, carried as the task's source item.
     */
    private function decide(int $id, bool $accept): void
    {
        $this->forgetCounts();

        if ($this->account === null) {
            return;
        }

        // Is this request still open at all? Who may decide it is settled one
        // step down, where the room's own ROOM_MODERATOR check happens.
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
