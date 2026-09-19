<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace App\Account;

use App\Entity\Account;
use App\Repository\RoomRepository;
use App\Room\RoomType;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Answers whether an account is the only moderator left in one of the room
 * types that must keep a moderator. Such an account is exempt from
 * deprovisioning.
 *
 * The room types are decided here, not derived from the legacy environment:
 * the legacy room list widened to group rooms as soon as a current portal
 * happened to be set on the process, so the same account was protected on
 * some nights and not on others.
 *
 * Not `readonly`: the answer is memoised because the guard of the account
 * activity workflow and its message handler both ask for the same account
 * within one run. The service is a shared singleton, so the memo MUST be
 * cleared between requests and between messages — {@see reset()} is invoked
 * via the `kernel.reset` tag (autoconfigured for {@see ResetInterface}).
 */
final class LastModeratorChecker implements ResetInterface
{
    /**
     * Room types whose sole moderator must not be deprovisioned.
     *
     * Group rooms are included: they have no lifecycle of their own — the
     * room activity workflow skips them and they are only removed together
     * with their project room — so nothing would ever pick up a group room
     * that lost its last moderator.
     */
    private const array PROTECTED_ROOM_TYPES = [
        RoomType::Project->value,
        RoomType::Community->value,
        RoomType::GroupRoom->value,
    ];

    /** @var array<int, bool> */
    private array $memo = [];

    public function __construct(
        private readonly RoomRepository $roomRepository,
    ) {
    }

    public function isLastModerator(Account $account): bool
    {
        $id = $account->getId();
        if (null === $id) {
            return false;
        }

        return $this->memo[$id] ??= [] !== $this->roomRepository
            ->findRoomsWithSoleModerator($account, self::PROTECTED_ROOM_TYPES);
    }

    public function reset(): void
    {
        $this->memo = [];
    }
}
