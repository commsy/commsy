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

namespace Tests\Integration\Legacy;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Legacy\UserItemAdapter;
use App\Repository\UserRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins the one-way Doctrine-User -> faithful-legacy-cs_user_item bridge.
 *
 * The point of UserItemAdapter is that it does NOT stub the legacy object:
 * it re-hydrates the real one by id so the ~1700 lines of legacy behaviour
 * still work for the remaining legacy-internal callers. These tests assert
 * both the identity match (right row) and the faithfulness (a real,
 * fully-built cs_user_item, not an empty shell).
 */
#[WithStory(AccountStory::class)]
final class UserItemAdapterTest extends KernelTestCase
{
    private cs_environment $legacyEnvironment;
    private UserRepository $userRepository;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->account = AccountStory::get('account');
    }

    public function testReturnsFaithfulLegacyItemForTheResolvedDoctrineUser(): void
    {
        $room = $this->createRoom();
        $member = RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $room,
            'status' => 3,
        ]);

        $doctrineUser = $this->userRepository->findByAccountIdAndContext(
            $this->account->getId(),
            $room->getItemId(),
        );
        self::assertInstanceOf(User::class, $doctrineUser);

        $legacy = UserItemAdapter::userToLegacy($doctrineUser, $this->legacyEnvironment);

        self::assertInstanceOf(cs_user_item::class, $legacy);
        // same row...
        self::assertSame($member->getItemId(), $legacy->getItemID());
        self::assertSame(3, $legacy->getStatus());
        // ...and a faithful object: legacy-only behaviour is available,
        // proving this is the real manager-built item, not a hand stub.
        self::assertTrue($legacy->isModerator());
        self::assertNotSame('', (string) $legacy->getUserID());
    }

    public function testReturnsNullWhenTheLegacyRowDoesNotExist(): void
    {
        // A detached User entity whose itemId points at nothing.
        $ghost = new User();
        $ghost->itemId = 999999999;

        self::assertNull(
            UserItemAdapter::userToLegacy($ghost, $this->legacyEnvironment),
        );
    }

    private function createRoom(): Room
    {
        return RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
    }
}
