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

namespace Tests\Integration\Export;

use App\Entity\License;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use App\Export\RoomExportSchema;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use JsonSchema\Validator;
use ReflectionClassConstant;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\DatesFactory;
use Tests\Factory\DiscussionArticleFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\FilesFactory;
use Tests\Factory\LabelFactory;
use Tests\Factory\LinkFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\SectionFactory;
use Tests\Factory\StepFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The schema is generated from what produces the document; this holds the two
 * against each other. A room with something of every kind — every rubric, the
 * sub-entries, the vocabulary, an attachment, a citation, booked time, a value
 * never stored — has to come out as the schema says, and nothing else may.
 */
final class RoomExportSchemaTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;
    private Connection $connection;
    private string $fileDirectory;

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithSomethingOfEveryKindComesOutAsTheSchemaSays(): void
    {
        $this->fillRoom();

        $this->assertSame([], $this->violations($this->document()));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAnEmptyRoomComesOutAsTheSchemaSays(): void
    {
        $this->assertSame([], $this->violations($this->document()));
    }

    /**
     * What the schema does not know must not pass: otherwise a field added to
     * the export would go undescribed without anyone noticing.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAFieldTheSchemaDoesNotKnowIsRefused(): void
    {
        $this->fillRoom();
        $document = $this->document();

        $onEntry = $document;
        $onEntry['rubrics']['announcement'][0]['undescribed'] = true;
        $onSubEntry = $document;
        $onSubEntry['rubrics']['material'][0]['sections'][0]['undescribed'] = true;
        $onPerson = $document;
        $onPerson['rubrics']['date'][0]['creator']['undescribed'] = true;
        $onRoom = $document;
        $onRoom['room']['undescribed'] = true;

        foreach ([$onEntry, $onSubEntry, $onPerson, $onRoom] as $changed) {
            $this->assertNotSame([], $this->violations($changed));
        }
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testTheSchemaNamesEveryRubricTheExportKnows(): void
    {
        $schema = self::getContainer()->get(RoomExportSchema::class)->build();

        $this->assertSame(array_keys($this->document()['rubrics']), $schema['properties']['rubrics']['required']);
        $this->assertSame([RoomExportBuilder::FORMAT_VERSION], $schema['properties']['formatVersion']['enum']);
    }

    /**
     * The citation's fields are listed on the attribute; the entity reads them
     * from a constant. One must not grow without the other.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheCitationsFieldsAreTheOnesTheMaterialKnows(): void
    {
        $known = array_keys((new ReflectionClassConstant(Materials::class, 'BIBLIOGRAPHIC_FIELDS'))->getValue());

        $schema = self::getContainer()->get(RoomExportSchema::class)->build();
        $described = array_keys($schema['definitions']['Material-export']['properties']['bibliographic']['properties']);

        $this->assertEqualsCanonicalizing(['kind', 'text', ...$known], $described);
    }

    /**
     * API Platform drops the description of a field that lists other
     * definitions; the added ones get it back.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAListOfSubEntriesKeepsItsDescription(): void
    {
        $definitions = self::getContainer()->get(RoomExportSchema::class)->build()['definitions'];

        $this->assertArrayHasKey('description', $definitions['Material-export']['properties']['sections']);
        $this->assertArrayHasKey('description', $definitions['Label-export']['properties']['members']);
        $this->assertArrayHasKey('description', $definitions['Announcement-export']['properties']['annotations']);
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $this->fileDirectory = 'var/tmp/commsy-export-schema-'.getmypid();

        $this->primeLegacyEnvironment($this->room, $this->roomUser);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(
            self::getContainer()->getParameter('kernel.project_dir').'/'.$this->fileDirectory
        );

        parent::tearDown();
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return self::getContainer()->get(LegacyEnvironment::class);
    }

    private function fillRoom(): void
    {
        $room = $this->room;
        $user = $this->roomUser;
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $category = (int) self::getContainer()->get(CategoryService::class)
            ->addTag('Sitzungen', $room->getItemId())->getItemID();
        self::getContainer()->get(CategoryService::class)->addTag('Woche 1', $room->getItemId(), $category);
        $hashtag = LabelFactory::createOne(
            ['room' => $room, 'creator' => $user, 'type' => 'buzzword', 'name' => 'Klausur']
        )->getItemId();

        $license = (new License())->setContextId($room->getItemId())->setTitle('CC BY 4.0')
            ->setContent('<p>Namensnennung</p>')->setPosition(1);
        $manager->persist($license);
        $manager->flush();

        $announcement = AnnouncementFactory::createOne(['room' => $room, 'creator' => $user])->getItemId();
        LinkItemFactory::createOne(
            ['room' => $room, 'creator' => $user, 'firstItemId' => $announcement, 'secondItemId' => $category]
        );
        LinkFactory::createOne([
            'room' => $room, 'creator' => $user, 'fromItemId' => $announcement,
            'toItemId' => $hashtag, 'linkType' => 'buzzword_for',
        ]);
        AnnotationFactory::createOne(['room' => $room, 'creator' => $user, 'linkedItemId' => $announcement]);
        $this->attach($this->createFile('Plan.pdf', 'content'), $announcement);
        $this->attach($this->createFile('Weg.pdf', null), $announcement);
        // A value no write path leaves out, but the column allows.
        $this->connection->executeStatement(
            'UPDATE announcement SET modification_date = NULL WHERE item_id = :id',
            ['id' => $announcement]
        );

        DatesFactory::createOne(['room' => $room, 'creator' => $user]);

        $discussion = DiscussionFactory::createOne(['room' => $room, 'creator' => $user]);
        DiscussionArticleFactory::createOne(
            ['room' => $room, 'creator' => $user, 'discussion' => $discussion, 'position' => '0001']
        );

        LabelFactory::createOne(['room' => $room, 'creator' => $user, 'type' => 'topic', 'name' => 'Thema']);
        $group = LabelFactory::createOne(
            ['room' => $room, 'creator' => $user, 'type' => 'group', 'name' => 'Gruppe']
        )->getItemId();
        LinkItemFactory::createOne(
            ['room' => $room, 'creator' => $user, 'firstItemId' => $group, 'secondItemId' => $user->getItemId()]
        );
        LabelFactory::createOne(['room' => $room, 'creator' => $user, 'type' => 'institution', 'name' => 'Institut']);

        $material = MaterialFactory::createOne(['room' => $room, 'creator' => $user]);
        SectionFactory::createOne(['room' => $room, 'creator' => $user, 'material' => $material]);
        $this->connection->executeStatement(
            'UPDATE materials SET license_id = :license, extras = :extras WHERE item_id = :id',
            [
                'license' => $license->getId(),
                'extras' => serialize([
                    'BIB_KIND' => 'book',
                    'BIB_PUBLISHER' => 'Verlag',
                    'BIB_ISBN' => '978-3-00-000000-0',
                ]),
                'id' => $material->getItemId(),
            ]
        );

        $todo = TodoFactory::createOne(['room' => $room, 'creator' => $user]);
        StepFactory::createOne(['room' => $room, 'creator' => $user, 'todo' => $todo]);
        $this->connection->executeStatement(
            'UPDATE todos SET minutes = 90, time_type = 1 WHERE item_id = :id',
            ['id' => $todo->getItemId()]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->clear();

        $room = $manager->find(Room::class, $this->room->getItemId());

        return self::getContainer()->get(RoomExportBuilder::class)->build($room)->content;
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<string>
     */
    private function violations(array $document): array
    {
        $schema = self::getContainer()->get(RoomExportSchema::class)->build();

        // The validator reads objects where JSON has them, as a decoder would.
        $decoded = json_decode(json_encode($document, JSON_THROW_ON_ERROR));
        $validator = new Validator();
        $validator->validate($decoded, json_decode(json_encode($schema, JSON_THROW_ON_ERROR)));

        return array_map(
            static fn (array $error): string => $error['property'].': '.$error['message'],
            $validator->getErrors()
        );
    }

    private function createFile(string $name, ?string $contents): int
    {
        $relativePath = $this->fileDirectory.'/'.uniqid('file', true);
        if (null !== $contents) {
            (new Filesystem())->dumpFile(
                self::getContainer()->getParameter('kernel.project_dir').'/'.$relativePath,
                $contents
            );
        }

        return FilesFactory::createOne([
            'contextId' => $this->room->getItemId(),
            'filename' => $name,
            'filepath' => $relativePath,
            'size' => null === $contents ? 0 : strlen($contents),
        ])->getFilesId();
    }

    private function attach(int $fileId, int $itemId): void
    {
        $this->connection->insert('item_link_file', ['item_iid' => $itemId, 'item_vid' => 0, 'file_id' => $fileId]);
    }
}
