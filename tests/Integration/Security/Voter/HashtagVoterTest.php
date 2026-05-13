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

use App\Security\Authorization\Voter\HashtagVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for HashtagVoter (HASHTAG_EDIT).
 *
 *   true iff: !user.isReallyGuest() AND !room.archived
 *
 * Notably: does NOT check isUser() / status — even requested users (status=1)
 * pass since isReallyGuest checks user_id="guest" + status=0. The voter
 * trusts the surrounding flow to gate this further, so the only blocks are
 * actual guest accounts and archived rooms.
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class HashtagVoterTest extends KernelTestCase
{
    use BootsVoter;

    public function testRegularMemberCanEditHashtags(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue($this->authChecker->isGranted(HashtagVoter::EDIT));
    }

    public function testReadOnlyMemberCanEditHashtags(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 4);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(HashtagVoter::EDIT),
            'HashtagVoter does not check isUser() — RO members pass too (only guests are blocked)',
        );
    }

    public function testArchivedRoomBlocksHashtagEdit(): void
    {
        $room = RoomFactory::new()->project()->archived()->create($this->roomDefaults());
        $this->createMember($this->portalAccount, $room, 3);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse($this->authChecker->isGranted(HashtagVoter::EDIT));
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
