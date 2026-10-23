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

use App\Entity\License;
use App\Entity\Room;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use App\Services\LegacyEnvironment;
use App\State\PageSize;
use App\Utils\CategoryService;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tests\Factory\CalendarsFactory;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\LabelFactory;
use Tests\Factory\RoomFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A room's vocabulary on the interface: what its entries point at by id, each
 * kind on its own. What every kind is held to: the terms the export carries on
 * the room, in the same shape — all of them at once, never a page.
 */
#[WithStory(RoomWithMemberStory::class)]
class RoomVocabularyResourceTest extends AbstractApiTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;

    /**
     * Each kind with a room that has some of it.
     *
     * @return iterable<string, array{string, string, Closure(self): void}>
     */
    public static function vocabularies(): iterable
    {
        yield 'categories' => ['categories', 'categories', static function (self $test): void {
            $categories = self::getContainer()->get(CategoryService::class);
            $roomId = $test->room->getItemId();
            $sessions = (int) $categories->addTag('Sitzungen', $roomId)->getItemID();
            $categories->addTag('Woche 1', $roomId, $sessions);
            $categories->addTag('Literatur', $roomId);
        }];
        yield 'hashtags' => ['hashtags', 'hashtags', static function (self $test): void {
            foreach (['Prüfung', 'Abgabe'] as $name) {
                LabelFactory::createOne(
                    ['room' => $test->room, 'creator' => $test->roomUser, 'type' => 'buzzword', 'name' => $name]
                );
            }
        }];
        yield 'calendars' => ['calendars', 'calendars', static function (self $test): void {
            CalendarsFactory::createOne(['room' => $test->room, 'title' => 'Seminar', 'defaultCalendar' => true]);
            CalendarsFactory::createOne(['room' => $test->room, 'title' => 'Exkursion']);
        }];
        yield 'licenses' => ['licenses', 'licenses', static function (self $test): void {
            $manager = self::getContainer()->get(EntityManagerInterface::class);
            foreach (['CC BY 4.0', 'CC0'] as $position => $title) {
                $manager->persist((new License())->setContextId($test->room->getItemId())->setTitle($title)
                    ->setContent('<p>'.$title.'</p>')->setPosition($position));
            }
            $manager->flush();
        }];
        yield 'todo statuses' => ['todo_statuses', 'todoStatuses', static function (self $test): void {
            $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
            $extras = unserialize((string) $connection->fetchOne(
                'SELECT extras FROM room WHERE item_id = :id',
                ['id' => $test->room->getItemId()]
            )) ?: [];
            $connection->executeStatement('UPDATE room SET extras = :extras WHERE item_id = :id', [
                'extras' => serialize([...$extras, 'TODOEXTRASTATUSARRAY' => [4 => 'In Prüfung']]),
                'id' => $test->room->getItemId(),
            ]);
        }];
    }

    /**
     * The interface and the export hand out the same data objects; this is what
     * keeps them from drifting apart.
     *
     * @param Closure(self): void $fill
     */
    #[DataProvider('vocabularies')]
    public function testARoomListsItsVocabularyExactlyAsTheExportCarriesIt(
        string $path,
        string $field,
        Closure $fill,
    ): void {
        $fill($this);

        $listed = $this->list($path);

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($listed);
        $this->assertSame($this->exported()['room'][$field], $listed);
    }

    public function testTheCategoriesComeAsATree(): void
    {
        $categories = self::getContainer()->get(CategoryService::class);
        $sessions = (int) $categories->addTag('Sitzungen', $this->room->getItemId())->getItemID();
        $week = (int) $categories->addTag('Woche 1', $this->room->getItemId(), $sessions)->getItemID();

        $this->assertSame(
            [['id' => $sessions, 'title' => 'Sitzungen', 'children' => [
                ['id' => $week, 'title' => 'Woche 1', 'children' => []],
            ]]],
            $this->list('categories')
        );
    }

    /**
     * A tree cannot be cut into pages, and the other lists are short: a kind
     * comes whole, however long it is.
     */
    public function testAListIsNeverAPage(): void
    {
        for ($i = 0; $i <= PageSize::DEFAULT; ++$i) {
            LabelFactory::createOne(
                ['room' => $this->room, 'creator' => $this->roomUser, 'type' => 'buzzword', 'name' => 'Tag '.$i]
            );
        }

        $response = $this->request($this->listUrl('hashtags').'?page=2');

        $this->assertCount(PageSize::DEFAULT + 1, $response->toArray());
        $this->assertArrayNotHasKey('link', $response->getHeaders());
        $this->assertArrayNotHasKey('x-total-count', $response->getHeaders());
    }

    public function testARoomWithoutVocabularyListsNothing(): void
    {
        $this->assertSame([], $this->list('hashtags'));
    }

    /**
     * A term is only ever part of its room's list; it has no address of its own.
     */
    public function testATermIsNotFoundOnItsOwn(): void
    {
        $hashtag = LabelFactory::createOne(
            ['room' => $this->room, 'creator' => $this->roomUser, 'type' => 'buzzword', 'name' => 'Prüfung']
        );

        $this->request('/api/v2/room_hashtags/'.$hashtag->getItemId());

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * The room resource answers 404 for a private room, so its vocabulary must
     * not be reachable by the side door.
     */
    #[DataProvider('vocabularies')]
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
        $this->request('/api/v2/rooms/999999999/categories');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAReadOnlyTokenSuffices(): void
    {
        $this->request($this->listUrl('categories'), $this->getReadOnlyToken());

        $this->assertResponseIsSuccessful();
    }

    public function testAnAnonymousRequestIsRefused(): void
    {
        static::createClient()->request('GET', $this->listUrl('categories'), [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        // Legacy writes the category tree in the name of whoever is acting.
        $this->primeLegacyEnvironment($this->room, $this->roomUser);
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return self::getContainer()->get(LegacyEnvironment::class);
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

    private function request(string $url, ?string $token = null): ResponseInterface
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
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->clear();

        return self::getContainer()->get(RoomExportBuilder::class)
            ->build($manager->find(Room::class, $this->room->getItemId()))->content;
    }
}
