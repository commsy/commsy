<?php

namespace Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\RoomRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Story\PortalStory;
use Tests\Story\RoomStory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(PortalStory::class)]
final class RoomRepositoryTest extends KernelTestCase
{
    #[WithStory(RoomWithMemberStory::class)]
    public function testFindAllIdsReturnsOnlyExistingNonDeletedRooms(): void
    {
        self::bootKernel();

        $portal = PortalStory::get('portal');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $repository = self::getContainer()->get(RoomRepository::class);

        $activeRoom = RoomFactory::createOne([
            'portal' => $portal,
            'type' => 'project',
            'deleter' => null,
            'deletionDate' => null,
        ]);

        $deletedRoom = RoomFactory::createOne([
            'portal' => $portal,
            'type' => 'project',
            'deleter' => $roomUser,
            'deletionDate' => new DateTimeImmutable(),
        ]);

        $otherTypeRoom = RoomFactory::createOne([
            'portal' => $portal,
            'type' => 'grouproom',
            'deleter' => null,
            'deletionDate' => null,
        ]);

        $ids = $repository->findAllIds(['grouproom', 'userroom', 'privateroom']);

        $this->assertContains($activeRoom->getItemId(), $ids);
        $this->assertNotContains($deletedRoom->getItemId(), $ids);
        $this->assertNotContains($otherTypeRoom->getItemId(), $ids);
    }
}
