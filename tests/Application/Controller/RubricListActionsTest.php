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

namespace Tests\Application\Controller;

use App\Entity\Room;
use App\Entity\Todos;
use App\Entity\User;
use App\Rubric\Todo\TodoStatus;
use App\Utils\CategoryService;
use App\Utils\LabelService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\StepFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Every rubric runs the list actions it offers on an entry: they answer and
 * leave their mark. The list actions are shared by the detail pages.
 */
#[WithStory(AccountStory::class)]
class RubricListActionsTest extends AbstractApplicationTestCase
{
    private const array TABLES = [
        'announcement' => 'announcement',
        'date' => 'dates',
        'discussion' => 'discussions',
        'group' => 'labels',
        'material' => 'materials',
        'todo' => 'todos',
        'topic' => 'labels',
    ];


    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $account = AccountStory::get('account');
        $portalId = (int) $account->getPortal()->getId();
        $this->loginAsUser($portalId, $account->getUsername(), $account->getPlainPassword());
        $this->roomId = $this->createRoom($portalId, 'Projekt');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function activatable(): iterable
    {
        foreach (['announcement', 'date', 'discussion', 'material', 'todo', 'topic'] as $rubric) {
            yield $rubric => [$rubric];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function all(): iterable
    {
        foreach (array_keys(self::TABLES) as $rubric) {
            yield $rubric => [$rubric];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function markable(): iterable
    {
        foreach (['announcement', 'date', 'discussion', 'material', 'todo'] as $rubric) {
            yield $rubric => [$rubric];
        }
    }

    #[DataProvider('activatable')]
    public function testActivate(string $rubric): void
    {
        $itemId = $this->createEntry($rubric);
        $this->connection()->update(self::TABLES[$rubric], ['activation_date' => '2099-01-01 00:00:00'], ['item_id' => $itemId]);

        $this->act($rubric, 'activate', $itemId);

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->column($rubric, $itemId, 'activation_date'));
    }

    #[DataProvider('activatable')]
    public function testDeactivate(string $rubric): void
    {
        $itemId = $this->createEntry($rubric);

        $this->act($rubric, 'deactivate', $itemId);

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($this->column($rubric, $itemId, 'activation_date'));
    }

    #[DataProvider('all')]
    public function testPinAndUnpin(string $rubric): void
    {
        $itemId = $this->createEntry($rubric);

        $this->act($rubric, 'pin', $itemId);
        $this->assertResponseIsSuccessful();
        $this->assertSame(1, (int) $this->connection()->fetchOne('SELECT pinned FROM items WHERE item_id = ?', [$itemId]));

        $this->act($rubric, 'unpin', $itemId);
        $this->assertResponseIsSuccessful();
        $this->assertSame(0, (int) $this->connection()->fetchOne('SELECT pinned FROM items WHERE item_id = ?', [$itemId]));
    }

    #[DataProvider('all')]
    public function testMarkRead(string $rubric): void
    {
        $itemId = $this->createEntry($rubric);
        $this->connection()->delete('reader', ['item_id' => $itemId]);

        $this->act($rubric, 'markread', $itemId);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM reader WHERE item_id = ?', [$itemId]));
    }

    #[DataProvider('markable')]
    public function testMark(string $rubric): void
    {
        $itemId = $this->createEntry($rubric);

        $this->act($rubric, 'mark', $itemId);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThanOrEqual(1, json_decode((string) $this->client->getResponse()->getContent(), true)['payload']['count'] ?? 0);
    }

    #[DataProvider('all')]
    public function testCategorize(string $rubric): void
    {
        $itemId = $this->createEntry($rubric);
        $tagId = (int) static::getContainer()->get(CategoryService::class)->addTag('Kategorie', $this->roomId)->getItemID();

        $this->act($rubric, 'categorize', $itemId, ['choices' => [$tagId]]);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM link_items WHERE deletion_date IS NULL AND ((first_item_id = :tag AND second_item_id = :item) OR (first_item_id = :item AND second_item_id = :tag))',
            ['tag' => $tagId, 'item' => $itemId],
        ));
    }

    #[DataProvider('all')]
    public function testHashtag(string $rubric): void
    {
        $itemId = $this->createEntry($rubric);
        $hashtagId = (int) static::getContainer()->get(LabelService::class)->getNewHashtag('Schlagwort', $this->roomId)->getItemID();

        $this->act($rubric, 'hashtag', $itemId, ['choices' => [$hashtagId]]);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, (int) $this->connection()->fetchOne(
            "SELECT COUNT(*) FROM links WHERE deletion_date IS NULL AND link_type = 'buzzword_for' AND ((from_item_id = :item AND to_item_id = :tag) OR (from_item_id = :tag AND to_item_id = :item))",
            ['tag' => $hashtagId, 'item' => $itemId],
        ));
    }

    public function testTodoStatusFromTheList(): void
    {
        $todoId = $this->createEntry('todo');

        $this->act('todo', 'status', $todoId, ['status' => 'done']);

        $this->assertResponseIsSuccessful();
        $this->assertSame(TodoStatus::Done->value, (int) $this->column('todo', $todoId, 'status'));
    }

    public function testTodoStatusFromTheDetailPage(): void
    {
        $todoId = $this->createEntry('todo');

        $this->post("/room/$this->roomId/todo/xhr/changestatus/$todoId", ['payload' => ['status' => 'inprogress']]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(TodoStatus::InProgress->value, (int) $this->column('todo', $todoId, 'status'));
    }

    public function testTodoStatusFromAStep(): void
    {
        $todoId = $this->createEntry('todo');
        $stepId = $this->createStep($todoId);

        $this->post("/room/$this->roomId/step/xhr/changestatus/$stepId", ['payload' => ['status' => 'done']]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(TodoStatus::Done->value, (int) $this->column('todo', $todoId, 'status'));
    }

    public function testDeleteAStep(): void
    {
        $todoId = $this->createEntry('todo');
        $stepId = $this->createStep($todoId);

        $this->act('step', 'delete', $stepId);

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($this->connection()->fetchOne('SELECT deletion_date FROM step WHERE item_id = ?', [$stepId]));
    }

    public function testMarkAndDeleteFromTheSearch(): void
    {
        $materialId = $this->createEntry('material');

        $this->act('search', 'mark', $materialId);
        $this->assertResponseIsSuccessful();

        $this->act('search', 'delete', $materialId);
        $this->assertResponseIsSuccessful();
        $this->assertNotNull($this->column('material', $materialId, 'deletion_date'));
    }

    public function testTheMarkedListCategorizesTagsRemovesAndInserts(): void
    {
        $materialId = $this->createEntry('material');
        $this->act('material', 'mark', $materialId);
        $tagId = (int) static::getContainer()->get(CategoryService::class)->addTag('Kategorie', $this->roomId)->getItemID();
        $hashtagId = (int) static::getContainer()->get(LabelService::class)->getNewHashtag('Schlagwort', $this->roomId)->getItemID();

        $this->act('mark', 'categorize', $materialId, ['choices' => [$tagId]]);
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM link_items WHERE deletion_date IS NULL AND ((first_item_id = :tag AND second_item_id = :item) OR (first_item_id = :item AND second_item_id = :tag))',
            ['tag' => $tagId, 'item' => $materialId],
        ));

        $this->act('mark', 'hashtag', $materialId, ['choices' => [$hashtagId]]);
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, (int) $this->connection()->fetchOne(
            "SELECT COUNT(*) FROM links WHERE deletion_date IS NULL AND link_type = 'buzzword_for' AND ((from_item_id = :item AND to_item_id = :tag) OR (from_item_id = :tag AND to_item_id = :item))",
            ['tag' => $hashtagId, 'item' => $materialId],
        ));

        $title = (string) $this->column('material', $materialId, 'title');
        $otherRoomId = $this->createRoom((int) AccountStory::get('account')->getPortal()->getId(), 'Anderes Projekt');
        $this->post("/room/$otherRoomId/mark/xhr/insert", ['action' => 'insert', 'positiveItemIds' => [$materialId]]);
        $this->assertResponseIsSuccessful();
        $this->assertSame(1, (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM materials WHERE context_id = ? AND title = ? AND deletion_date IS NULL',
            [$otherRoomId, $title],
        ));

        $this->act('mark', 'remove', $materialId);
        $this->assertResponseIsSuccessful();
    }

    public function testThePersonListPinsAndMarksRead(): void
    {
        $this->client->request('GET', "/room/$this->roomId");
        $userId = (int) $this->connection()->fetchOne(
            'SELECT item_id FROM user WHERE context_id = ? AND user_id = ? AND deletion_date IS NULL',
            [$this->roomId, AccountStory::get('account')->getUsername()],
        );

        $this->act('user', 'pin', $userId);
        $this->assertResponseIsSuccessful();
        $this->assertSame(1, (int) $this->connection()->fetchOne('SELECT pinned FROM items WHERE item_id = ?', [$userId]));

        $this->act('user', 'unpin', $userId);
        $this->assertResponseIsSuccessful();
        $this->assertSame(0, (int) $this->connection()->fetchOne('SELECT pinned FROM items WHERE item_id = ?', [$userId]));

        $this->connection()->delete('reader', ['item_id' => $userId]);
        $this->act('user', 'markread', $userId);
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM reader WHERE item_id = ?', [$userId]));
    }

    private function createStep(int $todoId): int
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $creatorId = (int) $this->connection()->fetchOne('SELECT creator_id FROM todos WHERE item_id = ?', [$todoId]);

        return (int) StepFactory::createOne([
            'todo' => $entityManager->find(Todos::class, $todoId),
            'room' => $entityManager->find(Room::class, $this->roomId),
            'creator' => $entityManager->find(User::class, $creatorId),
        ])->getItemId();
    }

    private function createEntry(string $rubric): int
    {
        // a new date starts from a moment, as the create button of the list passes it
        $this->client->request('GET', "/room/$this->roomId/$rubric/create" . ('date' === $rubric ? '/now' : ''));
        $this->client->followRedirect();
        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');
        $this->assertGreaterThan(0, $itemId);
        $this->connection()->update('items', ['draft' => 0], ['item_id' => $itemId]);

        return $itemId;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function act(string $rubric, string $action, int $itemId, array $payload = []): void
    {
        $parameters = ['action' => $action, 'positiveItemIds' => [$itemId]];
        if ([] !== $payload) {
            $parameters['payload'] = $payload;
        }

        $this->post("/room/$this->roomId/$rubric/xhr/$action", $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function post(string $url, array $parameters): void
    {
        $this->client->request('POST', $url, $parameters, [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
    }

    private function column(string $rubric, int $itemId, string $column): mixed
    {
        return $this->connection()->fetchOne(sprintf('SELECT %s FROM %s WHERE item_id = ?', $column, self::TABLES[$rubric]), [$itemId]);
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
