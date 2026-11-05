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

use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use App\Security\Authorization\Voter\ItemVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * An entry is checked against the person's profile in the room of the entry,
 * whatever room the request comes from: the status there, and whether that
 * room is archived. The room named in the URL is only the way in.
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class ItemVoterEntryContextTest extends KernelTestCase
{
    use BootsVoter;

    /**
     * A moderator here, a reader there: annotating an entry of the other room
     * follows the reader's status.
     */
    public function testTheStatusInTheRoomOfTheEntryDecides(): void
    {
        [$here, $there] = [$this->createProjectRoom(), $this->createProjectRoom()];
        $this->createMember($this->portalAccount, $here, 3);
        $reader = $this->createMember($this->portalAccount, $there, 4);
        $entry = $this->material($there, $reader);

        $this->loginAs($this->portalAccount, $here);

        self::assertFalse($this->authChecker->isGranted(ItemVoter::ANNOTATE, $entry->getItemId()), 'a reader there does not annotate');
        self::assertTrue($this->authChecker->isGranted(ItemVoter::PARTICIPATE, $entry->getItemId()), 'a reader there takes part');
        self::assertTrue($this->authChecker->isGranted(ItemVoter::SEE, $entry->getItemId()));
    }

    /**
     * The request comes from the portal, where the person has no room
     * status: a member of the room of the entry still annotates it.
     */
    public function testAMemberOfTheRoomOfTheEntryIsOneWhereverTheRequestComesFrom(): void
    {
        $there = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $there, 2);
        $entry = $this->material($there, $member);

        $this->loginAs($this->portalAccount);

        self::assertTrue($this->authChecker->isGranted(ItemVoter::ANNOTATE, $entry->getItemId()));
        self::assertTrue($this->authChecker->isGranted(ItemVoter::PARTICIPATE, $entry->getItemId()));
        self::assertTrue($this->authChecker->isGranted(ItemVoter::SEE, $entry->getItemId()));
    }

    /**
     * The room of the entry is archived, the one the request comes from is
     * not: nothing is written to the entry.
     */
    public function testTheArchiveOfTheRoomOfTheEntryDecides(): void
    {
        $here = $this->createProjectRoom();
        $there = RoomFactory::new()->project()->archived()->create($this->roomDefaults());
        $this->createMember($this->portalAccount, $here, 2);
        $member = $this->createMember($this->portalAccount, $there, 2);
        $entry = $this->material($there, $member);

        $this->loginAs($this->portalAccount, $here);

        self::assertFalse($this->authChecker->isGranted(ItemVoter::ANNOTATE, $entry->getItemId()));
        self::assertFalse($this->authChecker->isGranted(ItemVoter::PARTICIPATE, $entry->getItemId()));
    }

    /**
     * Someone who is no member of the room of the entry gets nothing that
     * needs a membership there, however they stand where the request comes
     * from.
     */
    public function testNoMembershipInTheRoomOfTheEntryGivesNoWriting(): void
    {
        [$here, $there] = [$this->createProjectRoom(), $this->createProjectRoom()];
        $this->createMember($this->portalAccount, $here, 3);
        $owner = $this->createMember($this->createPortalAccount(), $there, 2);
        $entry = $this->material($there, $owner);

        $this->loginAs($this->portalAccount, $here);

        self::assertFalse($this->authChecker->isGranted(ItemVoter::ANNOTATE, $entry->getItemId()));
        self::assertFalse($this->authChecker->isGranted(ItemVoter::PARTICIPATE, $entry->getItemId()));
        self::assertFalse($this->authChecker->isGranted(ItemVoter::SEE, $entry->getItemId()));
        self::assertFalse($this->authChecker->isGranted(ItemVoter::EDIT, $entry->getItemId()));
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }

    private function material(Room $room, User $creator): Materials
    {
        return MaterialFactory::createOne(['room' => $room, 'creator' => $creator]);
    }
}
