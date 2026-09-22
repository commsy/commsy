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
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\DatesFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Dates differ from announcements in two ways that matter to an importer: they
 * belong to a calendar, and a recurring date is many entries rather than one
 * entry with a rule.
 */
final class DateExportTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;
    private Connection $connection;

    #[WithStory(RoomWithMemberStory::class)]
    public function testADateStandsInTheDocumentWithItsTimesAndItsAuthor(): void
    {
        $itemId = $this->createDate('Sitzung');

        $dates = $this->dates();

        $this->assertCount(1, $dates);
        $this->assertSame($itemId, $dates[0]['itemId']);
        $this->assertSame('Sitzung', $dates[0]['title']);
        $this->assertSame($this->roomUser->getFullname(), $dates[0]['creator']['name']);
        $this->assertStringContainsString('10:00:00', $dates[0]['datetimeStart']);
        $this->assertStringContainsString('11:00:00', $dates[0]['datetimeEnd']);
        $this->assertFalse($dates[0]['wholeDay']);
        $this->assertFalse($dates[0]['external']);
    }

    /**
     * The room's list shows every date whatever `date_mode` says, so the export
     * does too — the column is legacy bookkeeping nothing writes any more.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testADateIsExportedWhateverItsLegacyDateMode(): void
    {
        $itemId = $this->createDate('Sondertermin');

        $this->connection->executeStatement(
            'UPDATE dates SET date_mode = 1 WHERE item_id = :id',
            ['id' => $itemId]
        );

        $this->assertSame(['Sondertermin'], $this->titles());
    }

    /**
     * Each occurrence is an entry of its own; the group id is what says they
     * belong together.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testOccurrencesOfARecurringDateNameTheirCommonGroup(): void
    {
        $this->createDate('Jour fixe', recurrenceId: 4711);
        $this->createDate('Jour fixe', recurrenceId: 4711);

        $dates = $this->dates();

        $this->assertCount(2, $dates);
        $this->assertSame(4711, $dates[0]['recurrenceGroupId']);
        $this->assertSame(4711, $dates[1]['recurrenceGroupId']);
    }

    /**
     * A date that stands alone is written with no group today, but rows from
     * older versions carry a zero. Carried through as it is, that would read as
     * one group everything belongs to.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testADateThatStandsAloneBelongsToNoGroup(): void
    {
        $itemId = $this->createDate('Einzeltermin');

        $this->connection->executeStatement(
            'UPDATE dates SET recurrence_id = 0 WHERE item_id = :id',
            ['id' => $itemId]
        );

        $this->assertNull($this->dates()[0]['recurrenceGroupId']);
    }

    /**
     * A date points at a calendar; the calendar itself stands on the room.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testADatePointsAtACalendarTheRoomDescribes(): void
    {
        $this->createDate('Sitzung');

        $document = $this->document();
        $calendarId = $document['rubrics']['date'][0]['calendarId'];

        $this->assertNotNull($calendarId);
        $this->assertSame(
            [$calendarId],
            array_column($document['room']['calendars'], 'id')
        );
        $this->assertSame('Standard', $document['room']['calendars'][0]['title']);
        $this->assertTrue($document['room']['calendars'][0]['isDefault']);
    }

    /**
     * The subscription URL of an external calendar is a way in, not content,
     * and some carry a token.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testACalendarsSubscriptionUrlIsNotExported(): void
    {
        $this->createDate('Sitzung');

        $this->connection->executeStatement(
            'UPDATE calendars SET external_url = :url WHERE context_id = :room',
            ['url' => 'https://example.org/feed.ics?token=geheim', 'room' => $this->room->getItemId()]
        );

        $this->assertSame(
            ['id', 'title', 'color', 'isDefault'],
            array_keys($this->document()['room']['calendars'][0])
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedDateIsLeftOut(): void
    {
        $this->createDate('Bleibt');
        $deleted = $this->createDate('Gelöscht');

        $this->connection->executeStatement(
            'UPDATE dates SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );
        $this->connection->executeStatement(
            'UPDATE items SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );

        $this->assertSame(['Bleibt'], $this->titles());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADraftIsLeftOut(): void
    {
        $this->createDate('Fertig');
        $draft = $this->createDate('Entwurf');

        $this->connection->executeStatement('UPDATE items SET draft = 1 WHERE item_id = :id', ['id' => $draft]);

        $this->assertSame(['Fertig'], $this->titles());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithoutDatesCarriesAnEmptyList(): void
    {
        $this->assertSame([], $this->dates());
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
        $managed = self::getContainer()->get(EntityManagerInterface::class)
            ->find(Room::class, $this->room->getItemId());

        return self::getContainer()->get(RoomExportBuilder::class)->build($managed)->content;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dates(): array
    {
        return $this->document()['rubrics']['date'];
    }

    /**
     * @return list<string>
     */
    private function titles(): array
    {
        return array_column($this->dates(), 'title');
    }

    private function createDate(string $title, ?int $recurrenceId = null): int
    {
        return DatesFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'title' => $title,
            'recurrenceId' => $recurrenceId,
        ])->getItemId();
    }
}
