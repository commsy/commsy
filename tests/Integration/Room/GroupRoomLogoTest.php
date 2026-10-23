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

namespace Tests\Integration\Room;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Files\FilesDirectory;
use App\Room\RoomType;
use App\Services\LegacyEnvironment;
use App\Utils\GroupService;
use cs_user_item;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\LabelFactory;
use Tests\Factory\RoomFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * Saving a group in its project room must leave the logo of its group room
 * alone: the logo is set in the group room's own settings.
 */
#[WithStory(RoomWithMemberStory::class)]
final class GroupRoomLogoTest extends KernelTestCase
{
    use Factories;

    private Connection $connection;
    private Room $projectRoom;
    private int $portalId;

    /** @var list<string> directories written by the test */
    private array $directories = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->connection = $container->get(EntityManagerInterface::class)->getConnection();
        $this->projectRoom = RoomWithMemberStory::get('room');
        $this->portalId = (int) $this->projectRoom->getContextId();

        /** @var User $user */
        $user = RoomWithMemberStory::get('roomUser');
        $legacyEnvironment = $container->get(LegacyEnvironment::class)->getEnvironment();
        $legacyEnvironment->setCurrentPortalID($this->portalId);
        $legacyEnvironment->setCurrentContextID((int) $this->projectRoom->getItemId());
        $userManager = $legacyEnvironment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextLimit((int) $this->projectRoom->getItemId());
        $userManager->setUserIDLimit($user->getUserId());
        $userManager->select();
        /** @var cs_user_item $userItem */
        $userItem = $userManager->get()->getFirst();
        $legacyEnvironment->setCurrentUserItem($userItem);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directories);
        parent::tearDown();
    }

    public function testSavingAGroupKeepsTheLogoOfItsGroupRoom(): void
    {
        [$groupId, $groupRoomId] = $this->groupWithRoom();

        self::getContainer()->get(GroupService::class)->getGroup($groupId)->save();

        self::assertSame("cid{$groupRoomId}_logo_Wappen.png", $this->extras('room', $groupRoomId)['LOGOFILENAME'] ?? null);
    }

    public function testAnOldGroupPictureDoesNotReplaceTheLogoOfItsGroupRoom(): void
    {
        [$groupId, $groupRoomId] = $this->groupWithRoom();
        $picture = "cid{$this->projectRoom->getItemId()}_Gruppe.png";
        $this->addExtras('labels', $groupId, ['LABELPICTURE' => $picture]);
        $directory = self::getContainer()->get(FilesDirectory::class)->contextDirectory($this->portalId, (int) $this->projectRoom->getItemId());
        (new Filesystem())->dumpFile("$directory/$picture", 'picture');
        $this->directories[] = $directory;
        $this->directories[] = self::getContainer()->get(FilesDirectory::class)->contextDirectory($this->portalId, $groupRoomId);

        self::getContainer()->get(GroupService::class)->getGroup($groupId)->save();

        self::assertSame("cid{$groupRoomId}_logo_Wappen.png", $this->extras('room', $groupRoomId)['LOGOFILENAME'] ?? null);
    }

    /**
     * A group of the project room and its group room with a logo of its own.
     *
     * @return array{int, int}
     */
    private function groupWithRoom(): array
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $groupRoomId = (int) RoomFactory::createOne([
            'contextId' => $this->portalId,
            'portal' => $account->getPortal(),
            'type' => RoomType::GroupRoom->value,
        ])->getItemId();
        $groupId = (int) LabelFactory::createOne([
            'room' => $this->projectRoom,
            'creator' => RoomWithMemberStory::get('roomUser'),
            'type' => 'group',
        ])->getItemId();

        $this->addExtras('labels', $groupId, ['GROUP_ROOM_ID' => $groupRoomId]);
        $this->addExtras('room', $groupRoomId, ['GROUP_ITEM_ID' => $groupId, 'LOGOFILENAME' => "cid{$groupRoomId}_logo_Wappen.png"]);

        return [$groupId, $groupRoomId];
    }

    /**
     * @return array<string, mixed>
     */
    private function extras(string $table, int $itemId): array
    {
        $extras = $this->connection->fetchOne("SELECT extras FROM `$table` WHERE item_id = ?", [$itemId]);
        $values = \is_string($extras) && '' !== $extras ? unserialize($extras, ['allowed_classes' => false]) : [];

        return \is_array($values) ? $values : [];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function addExtras(string $table, int $itemId, array $values): void
    {
        $this->connection->update($table, ['extras' => serialize([...$this->extras($table, $itemId), ...$values])], ['item_id' => $itemId]);
    }
}
