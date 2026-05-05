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
use App\Security\Authorization\Voter\CategoryVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for CategoryVoter (CATEGORY_EDIT).
 *
 *   true iff (in current room):
 *     - account is logged in (not anonymous)
 *     - user is not isReallyGuest
 *     - room not archived
 *     - user.isUser() (status >= 2) AND (room.isTagEditedByAll() OR user.isModerator())
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class CategoryVoterTest extends KernelTestCase
{
    use BootsVoter;

    public function testModeratorCanEditCategoriesEvenWhenNotEditedByAll(): void
    {
        $room = $this->createProjectRoomWithTagSetting(false);
        $this->createMember($this->portalAccount, $room, 3);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue($this->authChecker->isGranted(CategoryVoter::EDIT));
    }

    public function testRegularUserCanEditCategoriesWhenEditedByAll(): void
    {
        $room = $this->createProjectRoomWithTagSetting(true);
        $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue($this->authChecker->isGranted(CategoryVoter::EDIT));
    }

    public function testRegularUserCannotEditCategoriesWhenRestricted(): void
    {
        $room = $this->createProjectRoomWithTagSetting(false);
        $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(CategoryVoter::EDIT),
            'When tags are not "edited by all", non-mods cannot edit categories',
        );
    }

    public function testArchivedRoomBlocksCategoryEdit(): void
    {
        $room = $this->createProjectRoomWithTagSetting(true, archived: true);
        $this->createMember($this->portalAccount, $room, 3);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse($this->authChecker->isGranted(CategoryVoter::EDIT));
    }

    public function testRequestedUserCannotEditCategories(): void
    {
        // status 1 (requested) → isUser() returns false → voter returns false.
        $room = $this->createProjectRoomWithTagSetting(true);
        $this->createMember($this->portalAccount, $room, 1);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse($this->authChecker->isGranted(CategoryVoter::EDIT));
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }

    /**
     * cs_context_item::isTagEditedByAll() reads from extras['TAGEDITEDBY'].
     * Value 2 → restricted (only mods); anything else → edited-by-all.
     * Set at factory time so the legacy room manager caches the right
     * value when first loaded.
     */
    private function createProjectRoomWithTagSetting(bool $editedByAll, bool $archived = false): Room
    {
        $factory = RoomFactory::new()->project();
        if ($archived) {
            $factory = $factory->archived();
        }
        return $factory->create($this->roomDefaults() + [
            'extras' => ['TAGEDITEDBY' => $editedByAll ? 1 : 2],
        ]);
    }
}
