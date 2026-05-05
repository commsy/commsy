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

use App\Security\Authorization\Voter\DateVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\DatesFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for DateVoter ('edit' on a calendar date subject).
 *
 *   true iff: $date->isPublic() OR $date->getCreatorID() === currentUserItem.itemID
 *
 * The voter receives the *legacy* cs_dates_item as subject (it relies on
 * isPublic() and getCreatorID() which are not on the Dates Doctrine entity),
 * so tests load the legacy item via the dates manager before voting.
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class DateVoterTest extends KernelTestCase
{
    use BootsVoter;

    public function testCreatorCanEditOwnDate(): void
    {
        $room = $this->createProjectRoom();
        $creator = $this->createMember($this->portalAccount, $room, 2);
        $date = DatesFactory::createOne(['room' => $room, 'creator' => $creator]);

        $this->loginAs($this->portalAccount, $room);

        $legacyDate = $this->legacyEnvironment->getDatesManager()->getItem($date->getItemId());

        self::assertTrue($this->authChecker->isGranted(DateVoter::EDIT, $legacyDate));
    }

    public function testNonCreatorCannotEditPrivateDate(): void
    {
        $room = $this->createProjectRoom();
        $creator = $this->createMember($this->portalAccount, $room, 2);
        $date = DatesFactory::createOne(['room' => $room, 'creator' => $creator]);

        $other = $this->createPortalAccount();
        $this->createMember($other, $room, 2);

        $this->loginAs($other, $room);
        $legacyDate = $this->legacyEnvironment->getDatesManager()->getItem($date->getItemId());

        self::assertFalse(
            $this->authChecker->isGranted(DateVoter::EDIT, $legacyDate),
            'A non-creator cannot edit a private (default) date — voter does not consider moderator status here',
        );
    }

    public function testNonCreatorCanEditPublicDate(): void
    {
        $room = $this->createProjectRoom();
        $creator = $this->createMember($this->portalAccount, $room, 2);
        $date = DatesFactory::createOne([
            'room' => $room,
            'creator' => $creator,
            'public' => true,
        ]);

        $other = $this->createPortalAccount();
        $this->createMember($other, $room, 2);

        $this->loginAs($other, $room);
        $legacyDate = $this->legacyEnvironment->getDatesManager()->getItem($date->getItemId());

        self::assertTrue(
            $this->authChecker->isGranted(DateVoter::EDIT, $legacyDate),
            'When date.public=1, anyone in the room can edit it',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
