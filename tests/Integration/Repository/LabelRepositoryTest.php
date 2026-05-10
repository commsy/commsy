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

namespace Tests\Integration\Repository;

use App\Entity\Labels;
use App\Repository\LabelRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\LabelFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
final class LabelRepositoryTest extends KernelTestCase
{
    private LabelRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(LabelRepository::class);
    }

    public function testFindRoomHashtagsReturnsBuzzwordLabels(): void
    {
        [$room, $creator] = $this->makeRoomWithCreator();

        $hashtag = LabelFactory::createOne(['room' => $room, 'creator' => $creator, 'type' => 'buzzword']);
        $topic = LabelFactory::createOne(['room' => $room, 'creator' => $creator, 'type' => 'topic']);

        $results = $this->repository->findRoomHashtags($room->getItemId());
        $ids = array_map(static fn (Labels $l): int => $l->getItemId(), $results);

        self::assertContains($hashtag->getItemId(), $ids);
        self::assertNotContains($topic->getItemId(), $ids, 'topic-typed labels are not hashtags');
    }

    public function testFindLabelsByContextIdAndNameAndTypeMatchesAllThreeFields(): void
    {
        [$room, $creator] = $this->makeRoomWithCreator();
        $label = LabelFactory::createOne([
            'room' => $room,
            'creator' => $creator,
            'name' => 'specific-buzzword-name',
            'type' => 'buzzword',
        ]);

        $results = $this->repository->findLabelsByContextIdAndNameAndType(
            $room->getItemId(),
            'specific-buzzword-name',
            'buzzword',
        );

        self::assertCount(1, $results);
        self::assertSame($label->getItemId(), $results[0]->getItemId());
    }

    public function testFindLabelsByContextIdAndNameAndTypeReturnsEmptyOnMismatch(): void
    {
        [$room, $creator] = $this->makeRoomWithCreator();
        LabelFactory::createOne([
            'room' => $room,
            'creator' => $creator,
            'name' => 'present',
            'type' => 'buzzword',
        ]);

        self::assertSame(
            [],
            $this->repository->findLabelsByContextIdAndNameAndType(
                $room->getItemId(),
                'absent',
                'buzzword',
            ),
        );
    }

    private function makeRoomWithCreator(): array
    {
        $account = AccountStory::get('account');
        $room = RoomFactory::new()->project()->create([
            'contextId' => $account->getPortal()?->getId(),
            'portal' => $account->getPortal(),
        ]);
        $creator = RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
            'status' => 3,
        ]);
        return [$room, $creator];
    }
}
