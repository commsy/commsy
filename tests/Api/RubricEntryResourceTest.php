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

namespace Tests\Api;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\Response;
use App\Entity\Announcement;
use App\Entity\Dates;
use App\Entity\Discussions;
use App\Entity\Labels;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\Todos;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use App\State\RubricEntryProvider;
use App\Utils\CategoryService;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\DatesFactory;
use Tests\Factory\DiscussionArticleFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\LabelFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\SectionFactory;
use Tests\Factory\StepFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The rubrics on the interface. What every one of them is held to: the entries
 * the export carries, in the same shape, and not one more — a page at a time.
 */
#[WithStory(RoomWithMemberStory::class)]
class RubricEntryResourceTest extends AbstractApiTestCase
{
    private Room $room;
    private User $roomUser;
    private Connection $connection;

    /**
     * Each rubric with what typically hangs under its entries, so the comparison
     * with the export reaches the sub-entries too.
     *
     * @return iterable<string, array{string, string, string, class-string, Closure(Room, User): int}>
     */
    public static function rubrics(): iterable
    {
        yield 'announcement' => ['announcements', 'announcement', 'announcement', Announcement::class,
            static function (Room $room, User $user): int {
                $id = AnnouncementFactory::createOne(['room' => $room, 'creator' => $user])->getItemId();
                AnnotationFactory::createOne(['room' => $room, 'creator' => $user, 'linkedItemId' => $id]);

                return $id;
            },
        ];
        yield 'date' => ['dates', 'date', 'dates', Dates::class,
            static fn (Room $room, User $user): int => DatesFactory::createOne(
                ['room' => $room, 'creator' => $user]
            )->getItemId(),
        ];
        yield 'discussion' => ['discussions', 'discussion', 'discussions', Discussions::class,
            static function (Room $room, User $user): int {
                $discussion = DiscussionFactory::createOne(['room' => $room, 'creator' => $user]);
                DiscussionArticleFactory::createOne(
                    ['room' => $room, 'creator' => $user, 'discussion' => $discussion, 'position' => '1']
                );

                return $discussion->getItemId();
            },
        ];
        yield 'label' => ['labels', 'label', 'labels', Labels::class,
            static function (Room $room, User $user): int {
                $group = LabelFactory::createOne(
                    ['room' => $room, 'creator' => $user, 'type' => 'group', 'name' => uniqid('Gruppe ', true)]
                )->getItemId();
                LinkItemFactory::createOne(
                    ['room' => $room, 'creator' => $user, 'firstItemId' => $group, 'secondItemId' => $user->getItemId()]
                );

                return $group;
            },
        ];
        yield 'material' => ['materials', 'material', 'materials', Materials::class,
            static function (Room $room, User $user): int {
                $material = MaterialFactory::createOne(['room' => $room, 'creator' => $user]);
                SectionFactory::createOne(['room' => $room, 'creator' => $user, 'material' => $material]);

                return $material->getItemId();
            },
        ];
        yield 'todo' => ['todos', 'todo', 'todos', Todos::class,
            static function (Room $room, User $user): int {
                $todo = TodoFactory::createOne(['room' => $room, 'creator' => $user]);
                StepFactory::createOne(['room' => $room, 'creator' => $user, 'todo' => $todo]);

                return $todo->getItemId();
            },
        ];
    }

    /**
     * The interface and the export hand out the same data objects; this is what
     * keeps them from drifting apart.
     */
    #[DataProvider('rubrics')]
    public function testARoomListsItsEntriesExactlyAsTheExportCarriesThem(
        string $path,
        string $rubric,
        string $table,
        string $class,
        Closure $create,
    ): void {
        $first = $create($this->room, $this->roomUser);
        $second = $create($this->room, $this->roomUser);

        $listed = $this->list($path);

        $this->assertResponseIsSuccessful();
        $this->assertSame([$first, $second], array_column($listed, 'itemId'));
        $this->assertEquals($this->exported()['rubrics'][$rubric], $listed);
    }

    #[DataProvider('rubrics')]
    public function testASingleEntryAnswersLikeItsListEntry(
        string $path,
        string $rubric,
        string $table,
        string $class,
        Closure $create,
    ): void {
        $itemId = $create($this->room, $this->roomUser);

        $listed = $this->list($path)[0];
        $single = $this->request('/api/v2/'.$path.'/'.$itemId)->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertEquals($listed, $single);
    }

    #[DataProvider('rubrics')]
    public function testADraftIsLeftOutAndNotFound(
        string $path,
        string $rubric,
        string $table,
        string $class,
        Closure $create,
    ): void {
        $kept = $create($this->room, $this->roomUser);
        $draft = $create($this->room, $this->roomUser);

        $this->connection->executeStatement('UPDATE items SET draft = 1 WHERE item_id = :id', ['id' => $draft]);

        $this->assertSame([$kept], array_column($this->list($path), 'itemId'));

        $this->request('/api/v2/'.$path.'/'.$draft);
        $this->assertResponseStatusCodeSame(404);
    }

    #[DataProvider('rubrics')]
    public function testADeletedEntryIsLeftOutAndNotFound(
        string $path,
        string $rubric,
        string $table,
        string $class,
        Closure $create,
    ): void {
        $kept = $create($this->room, $this->roomUser);
        $deleted = $create($this->room, $this->roomUser);

        foreach ([$table, 'items'] as $deleteFrom) {
            $this->connection->executeStatement(
                'UPDATE '.$deleteFrom.' SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
                ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
            );
        }

        $this->assertSame([$kept], array_column($this->list($path), 'itemId'));

        $this->request('/api/v2/'.$path.'/'.$deleted);
        $this->assertResponseStatusCodeSame(404);
    }

    #[DataProvider('rubrics')]
    public function testAnUnknownEntryIsNotFound(string $path): void
    {
        $this->request('/api/v2/'.$path.'/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    #[DataProvider('rubrics')]
    public function testAListIsAPage(
        string $path,
        string $rubric,
        string $table,
        string $class,
        Closure $create,
    ): void {
        $first = $create($this->room, $this->roomUser);
        $second = $create($this->room, $this->roomUser);

        $page = $this->request($this->listUrl($path).'?itemsPerPage=1&page=2');

        $this->assertSame([$second], array_column($page->toArray(), 'itemId'));
        $this->assertSame('2', $page->getHeaders()['x-total-count'][0]);
        $this->assertNotContains($first, array_column($page->toArray(), 'itemId'));
    }

    /**
     * Each resource declares its page sizes itself; one that forgot would fall
     * back to API Platform's defaults without anyone noticing.
     */
    #[DataProvider('rubrics')]
    public function testAResourceTakesThePageSizesOfTheRubrics(
        string $path,
        string $rubric,
        string $table,
        string $class,
    ): void {
        $operations = self::getContainer()->get(ResourceMetadataCollectionFactoryInterface::class)->create($class);

        $collections = [];
        foreach ($operations as $resource) {
            foreach ($resource->getOperations() ?? [] as $operation) {
                if ($operation instanceof GetCollection) {
                    $collections[] = $operation;
                }
            }
        }

        $this->assertCount(1, $collections);
        $this->assertSame(RubricEntryProvider::ITEMS_PER_PAGE, $collections[0]->getPaginationItemsPerPage());
        $this->assertSame(
            RubricEntryProvider::MAXIMUM_ITEMS_PER_PAGE,
            $collections[0]->getPaginationMaximumItemsPerPage()
        );
        $this->assertTrue($collections[0]->getPaginationClientItemsPerPage());
    }

    /**
     * The room resource answers 404 for a private room, so its content must not
     * be reachable by the side door.
     */
    #[DataProvider('rubrics')]
    public function testARoomTheRoomResourceDoesNotShowIsNotFound(string $path): void
    {
        $private = RoomFactory::createOne([
            'type' => 'privateroom',
            'contextId' => $this->room->getContextId(),
            'portal' => $this->room->getPortal(),
        ]);

        $this->request('/api/v2/rooms/'.$private->getItemId().'/'.$path);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnUnknownRoomIsNotFound(): void
    {
        $this->request('/api/v2/rooms/999999999/announcements');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAReadOnlyTokenSuffices(): void
    {
        $this->request($this->listUrl('announcements'), $this->getReadOnlyToken());

        $this->assertResponseIsSuccessful();
    }

    public function testAnAnonymousRequestIsRefused(): void
    {
        static::createClient()->request('GET', $this->listUrl('announcements'), [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * A comment carries its own references. They are what goes missing when a
     * decorating normalizer's marker travels into a sub-entry.
     */
    public function testACommentKeepsItsOwnReferences(): void
    {
        $announcement = AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser])
            ->getItemId();
        $comment = AnnotationFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'linkedItemId' => $announcement,
        ])->getItemId();
        $category = (int) self::getContainer()->get(CategoryService::class)
            ->addTag('Vorlesung', $this->room->getItemId())
            ->getItemID();
        LinkItemFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'firstItemId' => $comment,
            'secondItemId' => $category,
        ]);

        $listed = $this->list('announcements');

        $this->assertSame([$category], $listed[0]['annotations'][0]['categories']);
        $this->assertEquals($this->exported()['rubrics']['announcement'], $listed);
    }

    public function testAPageIsAsLongAsTheRubricsSayUnlessAskedOtherwise(): void
    {
        for ($i = 0; $i <= RubricEntryProvider::ITEMS_PER_PAGE; ++$i) {
            AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        }

        $this->assertCount(RubricEntryProvider::ITEMS_PER_PAGE, $this->list('announcements'));
    }

    /**
     * The body is the bare list, so the headers say where the other pages are.
     * Whatever else the request asked for carries over into every link.
     */
    public function testAPageSaysWhereTheOthersAre(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        }
        $url = $this->listUrl('announcements');

        $headers = $this->request($url.'?itemsPerPage=1&page=2')->getHeaders();

        $this->assertSame('3', $headers['x-total-count'][0]);
        $this->assertSame(
            sprintf(
                '<%1$s?page=1&itemsPerPage=1>; rel="first", <%1$s?page=1&itemsPerPage=1>; rel="prev", '
                .'<%1$s?page=3&itemsPerPage=1>; rel="next", <%1$s?page=3&itemsPerPage=1>; rel="last"',
                $url
            ),
            $headers['link'][0]
        );
    }

    public function testThereIsNoNextAfterTheLastPage(): void
    {
        AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        $url = $this->listUrl('announcements');

        $headers = $this->request($url)->getHeaders();

        $this->assertSame(
            sprintf('<%1$s?page=1>; rel="first", <%1$s?page=1>; rel="last"', $url),
            $headers['link'][0]
        );
    }

    public function testARoomWithoutEntriesIsOneEmptyPage(): void
    {
        $response = $this->request($this->listUrl('announcements'));

        $this->assertSame([], $response->toArray());
        $this->assertSame('0', $response->getHeaders()['x-total-count'][0]);
    }

    // ------------------------------------------------------------------

    public function setUp(): void
    {
        parent::setUp();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    private function listUrl(string $path): string
    {
        return '/api/v2/rooms/'.$this->room->getItemId().'/'.$path;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function list(string $path): array
    {
        return $this->request($this->listUrl($path))->toArray();
    }

    private function request(string $url, ?string $token = null): Response
    {
        return $this->createClientWithCredentials($token)->request('GET', $url, [
            'headers' => ['Accept' => 'application/json'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function exported(): array
    {
        $managed = self::getContainer()->get(EntityManagerInterface::class)
            ->find(Room::class, $this->room->getItemId());

        return self::getContainer()->get(RoomExportBuilder::class)->build($managed)->content;
    }
}
