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

namespace Tests\Factory;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class RoomUserFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'city' => self::faker()->city(),
            'status' => 2,
        ];
    }

    /**
     * status 0 with a non-guest user_id — a member whose request was rejected.
     *
     * Note: the dedicated portal-level guest user (status 0 + user_id='guest')
     * is NOT created via this factory — guests are a portal singleton without
     * a per-room profile. See PermissionMatrixStory or insert directly into
     * the users table if you need a guest cs_user_item.
     */
    public function asRejected(): static
    {
        return $this->with(['status' => 0]);
    }

    /** status 1 — membership request pending moderator approval. */
    public function asRequested(): static
    {
        return $this->with(['status' => 1]);
    }

    /** status 2 — the default. Regular room member. */
    public function asUser(): static
    {
        return $this->with(['status' => 2]);
    }

    /** status 3 — room moderator. */
    public function asModerator(): static
    {
        return $this->with(['status' => 3]);
    }

    /** status 4 — read-only user. */
    public function asReadOnly(): static
    {
        return $this->with(['status' => 4]);
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->afterInstantiate(function(User $user): void {
                $account = $user->getAccount();
                $room = $user->getRoom();

                if (!$account instanceof Account) {
                    throw new LogicException('RoomUserFactory requires "account" (App\Entity\Account).');
                }
                if (!$room instanceof Room) {
                    throw new LogicException('RoomUserFactory requires "room" (App\Entity\Room).');
                }
                if (!$room->getItemId()) {
                    throw new LogicException('Room must be persisted (item_id set) before creating a RoomUser.');
                }

                $user->setUserId($account->getUsername());
                $user->setFirstname($account->getFirstname());
                $user->setLastname($account->getLastname());
                $user->setEmail($account->getEmail());
                $user->setAuthSource($account->getAuthSource()?->getId());
                $user->setPortal($account->getAuthSource()?->getPortal());

                $conn = $this->entityManager->getConnection();
                $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

                // 1) Insert in items
                $conn->insert('items', [
                    'context_id' => $room->getItemId(),
                    'modification_date' => $now,
                    'type' => 'user',
                ]);

                $itemId = (int) $conn->lastInsertId();
                $user->itemId = $itemId;

                // 2) Insert in user
                $conn->insert('user', [
                    'item_id' => $itemId,
                    'context_id' => $room->getItemId(),
                    'portal_id' => $user->getPortal()?->getId(),
                    'creator_id' => null,
                    'modifier_id' => null,
                    'deleter_id' => null,
                    'creation_date' => $now,
                    'modification_date' => $now,
                    'deletion_date' => null,
                    'account_id' => $account->getId(),
                    'user_id' => $user->getUserId(),
                    'status' => $user->getStatus(),
                    'is_contact' => (int) $user->getIsContact(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                    'city' => $user->getCity(),
                    'auth_source' => $user->getAuthSource(),
                    'visible' => 1,
                    'extras' => $user->getExtras() ? serialize($user->getExtras()) : null,
                    'description' => $user->getDescription(),
                    'use_portal_email' => 0,
                    'lastlogin' => null,
                ]);
            });
    }
}
