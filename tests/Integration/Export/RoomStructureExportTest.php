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
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\LabelFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The room's own vocabulary stands once in the document; entries reference it
 * by id rather than repeating it.
 */
final class RoomStructureExportTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;

    #[WithStory(RoomWithMemberStory::class)]
    public function testTheCategoryTreeIsPartOfTheRoomDocument(): void
    {
        $parent = $this->createCategory('Vorlesung');
        $this->createCategory('Folien', $parent);
        $this->createCategory('Übung');

        $room = $this->document()['room'];

        $this->assertSame(['Übung', 'Vorlesung'], array_column($room['categories'], 'title'));
        $this->assertSame('Folien', $room['categories'][1]['children'][0]['title']);
        $this->assertSame($parent, $room['categories'][1]['id']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testTheHashtagVocabularyIsPartOfTheRoomDocument(): void
    {
        $this->createHashtag('Prüfung');
        $this->createHashtag('Abgabe');

        $room = $this->document()['room'];

        $this->assertSame(['Abgabe', 'Prüfung'], array_column($room['hashtags'], 'title'));
        $this->assertIsInt($room['hashtags'][0]['id']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithoutVocabularyCarriesEmptyLists(): void
    {
        $room = $this->document()['room'];

        $this->assertSame([], $room['categories']);
        $this->assertSame([], $room['hashtags']);
    }

    /**
     * The decorating normalizer must not cost the room its own fields.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheRoomsOwnFieldsSurviveTheDecoration(): void
    {
        $this->createCategory('Vorlesung');

        $room = $this->document()['room'];

        foreach (['itemId', 'title', 'type', 'archived', 'creationDate'] as $field) {
            $this->assertArrayHasKey($field, $room);
        }
    }

    /**
     * Labels of other types share the table with hashtags — a topic is a rubric
     * entry, not vocabulary.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testATopicIsNotMistakenForAHashtag(): void
    {
        $this->createHashtag('Prüfung');
        LabelFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'type' => 'topic',
        ]);

        $room = $this->document()['room'];

        $this->assertSame(['Prüfung'], array_column($room['hashtags'], 'title'));
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

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
        $managed = self::getContainer()->get(EntityManagerInterface::class)
            ->find(Room::class, $this->room->getItemId());

        return self::getContainer()->get(RoomExportBuilder::class)->build($managed)->content;
    }

    private function createCategory(string $title, ?int $parentId = null): int
    {
        return (int) self::getContainer()->get(CategoryService::class)
            ->addTag($title, $this->room->getItemId(), $parentId)
            ->getItemID();
    }

    private function createHashtag(string $name): void
    {
        LabelFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'type' => 'buzzword',
            'name' => $name,
        ]);
    }
}
