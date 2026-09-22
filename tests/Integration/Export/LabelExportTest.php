<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Export;

use App\Entity\Room;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use App\Item\ItemType;
use App\Services\LegacyEnvironment;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\LabelFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * One rubric holding several kinds of entry: topics, groups, institutions.
 * Hashtags are labels too, but they belong to the room's vocabulary and would
 * otherwise stand in the document twice.
 */
final class LabelExportTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;
    private Connection $connection;

    #[WithStory(RoomWithMemberStory::class)]
    public function testALabelSaysWhichKindItIs(): void
    {
        $this->createLabel('topic', 'Kapitel 1');
        $this->createLabel('group', 'Arbeitsgruppe');
        $this->createLabel('institution', 'Fachbereich');

        $labels = $this->labels();

        $this->assertSame(
            [['Kapitel 1', 'topic'], ['Arbeitsgruppe', 'group'], ['Fachbereich', 'institution']],
            array_map(static fn (array $l): array => [$l['name'], $l['type']], $labels)
        );
        $this->assertSame($this->roomUser->getFullname(), $labels[0]['creator']['name']);
    }

    /**
     * A hashtag stands on the room; repeating it here would say it twice.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAHashtagIsNotALabelEntry(): void
    {
        $this->createLabel('buzzword', 'Prüfung');
        $this->createLabel('topic', 'Kapitel 1');

        $document = $this->document();

        $this->assertSame(['Kapitel 1'], array_column($document['rubrics']['label'], 'name'));
        $this->assertSame(['Prüfung'], array_column($document['room']['hashtags'], 'title'));
    }

    /**
     * Rows of the untyped legacy subtype carry no name and no rubric lists
     * them.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheUntypedLegacySubtypeStaysOut(): void
    {
        $label = $this->createLabel('topic', 'Kapitel 1');

        $this->connection->executeStatement(
            "UPDATE labels SET type = 'label', name = '' WHERE item_id = :id",
            ['id' => $label]
        );

        $this->assertSame([], $this->labels());
    }

    /**
     * A group keeps its members as links, and the people are rendered the same
     * way as anywhere else in the document.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAGroupNamesItsMembers(): void
    {
        $group = $this->createLabel('group', 'Arbeitsgruppe');

        LinkItemFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'firstItemId' => $group,
            'secondItemId' => $this->roomUser->getItemId(),
        ]);

        $members = $this->labels()[0]['members'];

        $this->assertCount(1, $members);
        $this->assertSame($this->roomUser->getItemId(), $members[0]['id']);
        $this->assertSame(['id', 'name', 'email', 'roomStatus'], array_keys($members[0]));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAGroupWithoutMembersCarriesAnEmptyList(): void
    {
        $this->createLabel('group', 'Arbeitsgruppe');

        $this->assertSame([], $this->labels()[0]['members']);
    }

    /**
     * Only a group shows members, so only a group is asked for them.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testATopicIsNotAskedForMembers(): void
    {
        $this->createLabel('topic', 'Kapitel 1');

        $this->assertArrayNotHasKey('members', $this->labels()[0]);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedLabelIsLeftOut(): void
    {
        $this->createLabel('topic', 'Bleibt');
        $deleted = $this->createLabel('topic', 'Gelöscht');

        $this->connection->executeStatement(
            'UPDATE labels SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );
        $this->connection->executeStatement(
            'UPDATE items SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );

        $this->assertSame(['Bleibt'], array_column($this->labels(), 'name'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithoutLabelsCarriesAnEmptyList(): void
    {
        $this->assertSame([], $this->labels());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->primeLegacyEnvironment($this->room, $this->roomUser);
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return self::getContainer()->get(LegacyEnvironment::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->clear();

        return self::getContainer()->get(RoomExportBuilder::class)
            ->build($manager->find(Room::class, $this->room->getItemId()))
            ->content;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function labels(): array
    {
        return $this->document()['rubrics']['label'];
    }

    private function createLabel(string $type, string $name): int
    {
        return LabelFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'type' => $type,
            'name' => $name,
        ])->getItemId();
    }
}
