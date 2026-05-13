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

namespace Tests\Integration\Security\Voter;

use App\Security\Authorization\Voter\RubricVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for RubricVoter (RUBRIC_* attributes — gated
 * sidebar/menu visibility per rubric type).
 *
 * Voter logic:
 *   - room.isDeleted → false
 *   - private rooms → true for {material, date, discussion, announcement, todo}
 *   - 'user' rubric AND moderator → true (mods always see user list)
 *   - else: in_array(rubric, room.getAvailableRubrics())
 *
 * Default-configured project room exposes ALL rubrics.
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class RubricVoterTest extends KernelTestCase
{
    use BootsVoter;

    public function testRubricVisibleByDefaultInProjectRoom(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue($this->authChecker->isGranted(RubricVoter::MATERIAL));
        self::assertTrue($this->authChecker->isGranted(RubricVoter::DATE));
        self::assertTrue($this->authChecker->isGranted(RubricVoter::ANNOUNCEMENT));
    }

    public function testDeletedRoomBlocksAllRubrics(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 3);
        $this->loginAs($this->portalAccount, $room);

        // Soft-delete the room while currentUserItem is already cached, then
        // evict the legacy caches so the voter re-reads the deletion flag.
        $this->softDeleteRoom($room->getItemId());
        $this->evictLegacyCache($room->getItemId());

        self::assertFalse($this->authChecker->isGranted(RubricVoter::MATERIAL));
        self::assertFalse($this->authChecker->isGranted(RubricVoter::DATE));
    }

    public function testPrivateRoomShortcutsRubricsForCoreContent(): void
    {
        $privateRoom = $this->createPrivateRoom();
        $this->createMember($this->portalAccount, $privateRoom, 3);
        $this->loginAs($this->portalAccount, $privateRoom);

        // Private rooms unconditionally allow material/date/discussion/
        // announcement/todo (regardless of getAvailableRubrics).
        self::assertTrue($this->authChecker->isGranted(RubricVoter::MATERIAL));
        self::assertTrue($this->authChecker->isGranted(RubricVoter::DISCUSSION));
        self::assertTrue($this->authChecker->isGranted(RubricVoter::ANNOUNCEMENT));
    }

    public function testUserRubricForModeratorIsAlwaysGranted(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 3);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(RubricVoter::USER),
            'Moderators always see the user rubric, regardless of room config',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
