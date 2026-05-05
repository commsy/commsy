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

use App\Entity\Room;
use App\Security\Authorization\Voter\CalendarsVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for CalendarsVoter (CALENDARS_EDIT).
 *
 *   true iff: !room.archived AND (room.usersCanEditCalendars() OR user.isModerator())
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class CalendarsVoterTest extends KernelTestCase
{
    use BootsVoter;

    public function testRegularMemberCanEditCalendarsWhenAllowed(): void
    {
        $room = $this->createProjectRoom(usersCanEditCalendars: true);
        $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue($this->authChecker->isGranted(CalendarsVoter::EDIT));
    }

    public function testRegularMemberCannotEditCalendarsWhenRestricted(): void
    {
        $room = $this->createProjectRoom(usersCanEditCalendars: false);
        $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse($this->authChecker->isGranted(CalendarsVoter::EDIT));
    }

    public function testModeratorCanEditCalendarsEvenWhenRestricted(): void
    {
        $room = $this->createProjectRoom(usersCanEditCalendars: false);
        $this->createMember($this->portalAccount, $room, 3);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue($this->authChecker->isGranted(CalendarsVoter::EDIT));
    }

    public function testArchivedRoomBlocksCalendarsEdit(): void
    {
        $room = $this->createProjectRoom(usersCanEditCalendars: true, archived: true);
        $this->createMember($this->portalAccount, $room, 3);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse($this->authChecker->isGranted(CalendarsVoter::EDIT));
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }

    /**
     * cs_context_item::usersCanEditCalendars() reads from
     * extras['USERSCANEDITCALENDARS']. Value 1 → true, else false.
     */
    private function createProjectRoom(bool $usersCanEditCalendars, bool $archived = false): Room
    {
        $factory = RoomFactory::new()->project();
        if ($archived) {
            $factory = $factory->archived();
        }
        return $factory->create($this->roomDefaults() + [
            'extras' => ['USERSCANEDITCALENDARS' => $usersCanEditCalendars ? 1 : 0],
        ]);
    }
}
