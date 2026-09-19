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

namespace Tests\Integration\Account;

use App\Account\LastModeratorChecker;
use App\Entity\Account;
use App\Entity\Room;
use App\Services\LegacyEnvironment;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * {@see LastModeratorChecker::isLastModerator()} must not depend on process state.
 *
 * It used to ask the legacy room list, which widens to group rooms whenever
 * `cs_environment::getCurrentPortalItem()` returns something. In the
 * messenger worker that depends on what was handled before, so the same
 * account was protected on some nights and deprovisioned on others — and the
 * guard's reset wiped the state each time it said yes.
 *
 * The pairs below build the same account and differ only in the legacy
 * portal state. That state has to be set explicitly: the Foundry factories
 * prime the environment while building fixtures (see
 * {@see \Tests\Factory\Concerns\PrimesLegacyEnvironment}), which is the
 * same accident that primes the worker in production.
 */
#[WithStory(AccountStory::class)]
final class AccountManagerLastModeratorTest extends KernelTestCase
{
    private LastModeratorChecker $lastModeratorChecker;
    private Account $account;

    public function testSoleGroupRoomModeratorIsProtectedWithoutAPrimedContext(): void
    {
        $this->makeSoleModeratorOfAGroupRoom();
        $this->forgetLegacyPortal();

        self::assertTrue($this->lastModeratorChecker->isLastModerator($this->account));
    }

    public function testSoleGroupRoomModeratorIsProtectedWithAPrimedContext(): void
    {
        $projectRoom = $this->makeSoleModeratorOfAGroupRoom();
        $this->primeLegacyContext($projectRoom);

        self::assertTrue($this->lastModeratorChecker->isLastModerator($this->account));
    }

    public function testPlainMemberIsNotProtectedWithoutAPrimedContext(): void
    {
        $this->makePlainMember();
        $this->forgetLegacyPortal();

        self::assertFalse($this->lastModeratorChecker->isLastModerator($this->account));
    }

    public function testPlainMemberIsNotProtectedWithAPrimedContext(): void
    {
        $projectRoom = $this->makePlainMember();
        $this->primeLegacyContext($projectRoom);

        self::assertFalse($this->lastModeratorChecker->isLastModerator($this->account));
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->lastModeratorChecker = self::getContainer()->get(LastModeratorChecker::class);
        $this->account = AccountStory::get('account');
    }

    /**
     * The reported constellation: sole moderator of a group room, plain
     * member of a project room that is moderated by someone else.
     *
     * @return Room the project room
     */
    private function makeSoleModeratorOfAGroupRoom(): Room
    {
        $projectRoom = $this->makePlainMember();

        $groupRoom = RoomFactory::new()->groupRoom()->create([
            'contextId' => $this->portalId(),
            'portal' => $this->account->getPortal(),
        ]);
        RoomUserFactory::new()->asModerator()->create([
            'account' => $this->account,
            'room' => $groupRoom,
        ]);

        return $projectRoom;
    }

    /** @return Room the project room */
    private function makePlainMember(): Room
    {
        $projectRoom = RoomFactory::new()->project()->create([
            'contextId' => $this->portalId(),
            'portal' => $this->account->getPortal(),
        ]);

        RoomUserFactory::new()->asUser()->create([
            'account' => $this->account,
            'room' => $projectRoom,
        ]);
        RoomUserFactory::new()->asModerator()->create([
            'account' => $this->createSecondAccount(),
            'room' => $projectRoom,
        ]);

        return $projectRoom;
    }

    /**
     * Drops the portal the fixtures left on the environment, so the legacy
     * room list falls back to project rooms only — the state a freshly
     * started worker is in.
     */
    private function forgetLegacyPortal(): void
    {
        $environment = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment();
        $environment->currentPortal = null;
        $environment->_current_portal_id = 0;
        $environment->setCurrentContextID(99);
    }

    /** The opposite state: some earlier work left a room context behind. */
    private function primeLegacyContext(Room $room): void
    {
        $environment = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment();
        $environment->currentPortal = null;
        $environment->_current_portal_id = 0;
        $environment->setCurrentContextID($room->getItemId());
    }

    private function createSecondAccount(): Account
    {
        $portal = $this->account->getPortal();

        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal?->getAuthSources()->first(),
        ]);
    }

    private function portalId(): int
    {
        return $this->account->getPortal()->getId();
    }
}
