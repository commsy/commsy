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

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\LabelFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The categories and hashtags of a room are managed from that room only:
 * ids of another room's categories, hashtags, groups or topics are not
 * found, and a category is deleted by a post carrying its own token.
 */
#[WithStory(RoomWithMemberStory::class)]
final class CategoryHashtagScopeTest extends AbstractApplicationTestCase
{
    private Connection $connection;
    private Room $room;
    private int $ownCategory;
    private int $foreignCategory;
    private int $foreignHashtag;
    private int $foreignGroup;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = self::getContainer()->get(Connection::class);
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection->update('user', ['status' => 3], ['item_id' => $roomUser->getItemId()]);
        $this->connection->update('room', ['extras' => serialize(['WITHTAGS' => 2, 'WITHBUZZWORDS' => 2])], ['item_id' => $this->room->getItemId()]);

        $portal = $account->getPortal();
        $foreignRoom = RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
        $owner = RoomUserFactory::createOne([
            'account' => AccountFactory::createOne(['portal' => $portal, 'authSource' => $account->getAuthSource()]),
            'room' => $foreignRoom,
            'status' => 3,
        ]);

        $this->ownCategory = $this->category($this->room, $roomUser, 'Eigene Kategorie');
        $this->foreignCategory = $this->category($foreignRoom, $owner, 'Fremde Kategorie');
        $this->foreignHashtag = LabelFactory::createOne(['room' => $foreignRoom, 'creator' => $owner, 'name' => 'Fremder Hashtag', 'type' => 'buzzword'])->getItemId();
        $this->foreignGroup = LabelFactory::createOne(['room' => $foreignRoom, 'creator' => $owner, 'name' => 'Fremde Gruppe', 'type' => 'group'])->getItemId();

        $this->loginAsUser((int) $portal?->getId(), $account->getUsername(), $account->getPlainPassword());
    }

    public function testACategoryIsNotDeletedByAPlainLink(): void
    {
        $this->client->request('GET', $this->deletePath($this->ownCategory));

        self::assertResponseStatusCodeSame(405);
        self::assertFalse($this->isDeleted('tag', $this->ownCategory));
    }

    public function testACategoryIsNotDeletedWithoutItsToken(): void
    {
        $this->client->request('POST', $this->deletePath($this->ownCategory), ['_token' => 'forged']);

        self::assertFalse($this->isDeleted('tag', $this->ownCategory));
    }

    public function testTheDeleteButtonOfTheRoomDeletesItsCategory(): void
    {
        $crawler = $this->client->request('GET', "/room/{$this->room->getItemId()}/category/edit");
        $button = $crawler->filter(sprintf('button[formaction="%s"]', $this->deletePath($this->ownCategory)));
        self::assertCount(1, $button);

        $this->client->request('POST', $this->deletePath($this->ownCategory), ['_token' => $button->attr('value')]);

        self::assertTrue($this->isDeleted('tag', $this->ownCategory));
    }

    public function testACategoryOfAnotherRoomIsNotFound(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/category/edit/{$this->foreignCategory}");
        self::assertResponseStatusCodeSame(404);

        self::assertFalse(self::getContainer()->get(CategoryService::class)->isTagOfRoom($this->foreignCategory, $this->room->getItemId()));
        self::assertTrue(self::getContainer()->get(CategoryService::class)->isTagOfRoom($this->ownCategory, $this->room->getItemId()));
    }

    public function testTheTreeOfTheRoomMovesOnlyItsOwnCategories(): void
    {
        $parentsBefore = $this->parentsOf($this->foreignCategory);

        $crawler = $this->client->request('GET', "/room/{$this->room->getItemId()}/category/edit");
        $form = $crawler->selectButton('category_edit[update]')->form();
        $form['category_edit[structure]'] = json_encode([['itemId' => $this->ownCategory], ['itemId' => $this->foreignCategory]]);
        $this->client->submit($form);

        self::assertSame($parentsBefore, $this->parentsOf($this->foreignCategory));
    }

    public function testAHashtagOrGroupOfAnotherRoomIsNotFound(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/hashtag/edit/{$this->foreignHashtag}");
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', "/room/{$this->room->getItemId()}/hashtag/edit/{$this->foreignGroup}");
        self::assertResponseStatusCodeSame(404);

        self::assertFalse($this->isDeleted('labels', $this->foreignHashtag));
        self::assertFalse($this->isDeleted('labels', $this->foreignGroup));
    }

    /**
     * @return list<int> the live parents of the category in the tree
     */
    private function parentsOf(int $categoryId): array
    {
        return array_map(intval(...), $this->connection->fetchFirstColumn(
            'SELECT from_item_id FROM tag2tag WHERE to_item_id = ? AND deletion_date IS NULL ORDER BY from_item_id',
            [$categoryId],
        ));
    }

    private function deletePath(int $categoryId): string
    {
        return "/room/{$this->room->getItemId()}/category/delete/{$categoryId}";
    }

    private function category(Room $room, User $creator, string $title): int
    {
        $environment = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment();
        $environment->setCurrentPortalID((int) $room->getPortal()?->getId());
        $environment->setCurrentUserItem($environment->getUserManager()->getItem($creator->getItemId()));

        return (int) self::getContainer()->get(CategoryService::class)->addTag($title, $room->getItemId())->getItemID();
    }

    private function isDeleted(string $table, int $itemId): bool
    {
        return null !== $this->connection->fetchOne("SELECT deletion_date FROM {$table} WHERE item_id = ?", [$itemId]);
    }
}
