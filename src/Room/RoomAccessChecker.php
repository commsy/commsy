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

declare(strict_types=1);

namespace App\Room;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use Closure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Decides whether a given Account (or a known user_item id) is allowed
 * to enter a Room. Doctrine-only — replaces `cs_context_item::mayEnter*`.
 *
 * Rules pinned by ItemVoter ENTER characterization tests:
 *  1. `root` → always true (except via the user_item_id variant, which
 *     mirrors legacy `mayEnterByUserItemID` and has no root short-circuit).
 *  2. Locked rooms → false.
 *  3. Community rooms with `is_open_for_guests` → true for everyone.
 *     Project / grouproom / userroom hardcode the legacy flag to false
 *     regardless of the column value.
 *  4. Otherwise: non-deleted membership with status >= 2.
 *
 * Verdicts are memoised in the request-scoped `permission.access_cache`
 * pool (ArrayAdapter, see `config/packages/cache.yaml`). Same scope as
 * the legacy `_cache_may_enter` — gone at end of request.
 */
final class RoomAccessChecker
{
    public function __construct(
        private readonly UserRepository $userRepository,
        #[Autowire(service: 'permission.access_cache')]
        private readonly CacheInterface $cache,
    ) {
    }

    public function canEnter(Account $account, Room $room): bool
    {
        $username = $account->getUsername();
        return $this->resolve(
            rootHint: $username,
            room: $room,
            cacheSubkey: 'account.' . bin2hex($username),
            membershipLoader: fn(): ?User => $this->userRepository->findInContext($account, $room->getItemId()),
        );
    }

    /**
     * Identity-triple check used by ItemVoter ENTER — the voter holds
     * a `cs_user_item`, not always an Account (hash-token flows).
     * Mirrors `cs_context_item::mayEnterByUserID`.
     */
    public function canEnterByLegacyIdentity(string $userId, ?int $authSourceId, Room $room): bool
    {
        return $this->resolve(
            rootHint: $userId,
            room: $room,
            // bin2hex(userId) guarantees PSR-6-safe characters without
            // central sanitisation: userId is the only freeform input
            // (auth source / room / account IDs are ints).
            cacheSubkey: 'identity.' . bin2hex($userId) . '.' . ($authSourceId ?? '_'),
            membershipLoader: fn(): ?User => $this->userRepository->findOneByLegacyIdentity(
                $userId,
                $room->getItemId(),
                $authSourceId,
            ),
        );
    }

    /**
     * Identifier-only check (RSS / iCal hash logins). Mirrors
     * `cs_context_item::mayEnterByUserItemID` — no root short-circuit.
     */
    public function canEnterByUserItemId(int $userItemId, Room $room): bool
    {
        return $this->resolve(
            rootHint: null,
            room: $room,
            cacheSubkey: 'useritem.' . $userItemId,
            membershipLoader: function () use ($userItemId, $room): ?User {
                $user = $this->userRepository->find($userItemId);
                if ($user === null || $user->getRoom()?->getItemId() !== $room->getItemId()) {
                    return null;
                }
                return $user;
            },
        );
    }

    /**
     * Shared envelope: root short-circuit → lock/openForGuests pre-checks
     * → memoised membership lookup → `isUser()` filter. Each caller's
     * loader returns the candidate `User` or `null`.
     */
    private function resolve(
        ?string $rootHint,
        Room $room,
        string $cacheSubkey,
        Closure $membershipLoader,
    ): bool {
        if ('root' === $rootHint) {
            return true;
        }
        if ($room->isLocked()) {
            return false;
        }
        if ($this->reachableViaGuestAccess($room)) {
            return true;
        }

        return $this->cache->get(
            'enter.' . $cacheSubkey . '.' . $room->getItemId(),
            function (ItemInterface $item) use ($membershipLoader): bool {
                $item->expiresAfter(null); // request-scoped, no TTL
                $member = $membershipLoader();
                return $member !== null && $member->isUser();
            },
        );
    }

    private function reachableViaGuestAccess(Room $room): bool
    {
        return $room->getOpenForGuests()
            && RoomType::tryFromLegacyString($room->getType()) === RoomType::Community;
    }
}
