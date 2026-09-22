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
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A comment belongs to no rubric of its own: it hangs under whatever entry it
 * was written on, whichever rubric that is.
 */
final class AnnotationExportTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;
    private Connection $connection;

    #[WithStory(RoomWithMemberStory::class)]
    public function testACommentHangsUnderTheEntryItWasWrittenOn(): void
    {
        $announcement = AnnouncementFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'title' => 'Ankündigung',
        ])->getItemId();

        $this->comment($announcement, 'Guter Hinweis');

        $entry = $this->document()['rubrics']['announcement'][0];

        $this->assertCount(1, $entry['annotations']);
        $this->assertSame('Guter Hinweis', $entry['annotations'][0]['description']);
        $this->assertSame($this->roomUser->getFullname(), $entry['annotations'][0]['creator']['name']);
    }

    /**
     * A comment records the version it was written on, but nothing reads it —
     * legacy asks for the entry alone. Asking for a version would drop a
     * material's comments as soon as it is edited.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testACommentSurvivesTheEntryBeingEdited(): void
    {
        $material = MaterialFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'title' => 'Skript',
        ]);

        $this->comment($material->getItemId(), 'Bitte ergänzen');

        $this->connection->executeStatement(
            'INSERT INTO materials (item_id, version_id, context_id, creator_id, creation_date, title, workflow_status)
             SELECT item_id, version_id + 1, context_id, creator_id, creation_date, :title, workflow_status
             FROM materials WHERE item_id = :id',
            ['title' => 'Skript, überarbeitet', 'id' => $material->getItemId()]
        );

        $entry = $this->document()['rubrics']['material'][0];

        $this->assertSame('Skript, überarbeitet', $entry['title']);
        $this->assertSame(['Bitte ergänzen'], array_column($entry['annotations'], 'description'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedCommentIsLeftOut(): void
    {
        $announcement = AnnouncementFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'title' => 'Ankündigung',
        ])->getItemId();

        $this->comment($announcement, 'Bleibt');
        $deleted = $this->comment($announcement, 'Gelöscht');

        $this->connection->executeStatement(
            'UPDATE annotations SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );

        $entry = $this->document()['rubrics']['announcement'][0];

        $this->assertSame(['Bleibt'], array_column($entry['annotations'], 'description'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAnEntryWithoutCommentsCarriesAnEmptyList(): void
    {
        AnnouncementFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'title' => 'Ankündigung',
        ]);

        $this->assertSame([], $this->document()['rubrics']['announcement'][0]['annotations']);
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

    private function comment(int $linkedItemId, string $description): int
    {
        return AnnotationFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'linkedItemId' => $linkedItemId,
            'description' => $description,
        ])->getItemId();
    }
}
