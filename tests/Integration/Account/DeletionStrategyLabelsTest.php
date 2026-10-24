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

namespace Tests\Integration\Account;

use App\Account\AccountSetting;
use App\Account\AccountSettingsManager;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Rubric\RedactionText;
use App\User\UserMembershipDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\LabelFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins that deleting a person's entries keeps the labels and categories of
 * the room — the shared vocabulary and structure — without the person, and
 * replaces only what they wrote about topics and groups.
 */
#[WithStory(RoomWithMemberStory::class)]
final class DeletionStrategyLabelsTest extends KernelTestCase
{
    private Connection $connection;
    private Room $room;
    private User $roomUser;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->account = RoomWithMemberStory::get('account');
    }

    public function testDeletingThePersonsEntriesKeepsTheLabelsAndRedactsTheirDescriptions(): void
    {
        $this->setStrategy(cascade: true);
        $all = $this->createLabel('ALL', 'group', 'GROUP_ALL_DESC', ['SYSTEM_LABEL' => 1]);
        $group = $this->createLabel('Lerngruppe', 'group', 'Wir treffen uns dienstags.');
        $topic = $this->createLabel('Klausur', 'topic', 'Alles zur Klausur.');
        $hashtag = $this->createLabel('Statistik', 'buzzword', '');
        $category = $this->createCategory('Vorlesung');
        $othersMaterial = $this->createMaterialOfAnotherPerson();
        $this->tagWithHashtag($othersMaterial, $hashtag);

        $this->deleteMembership();

        $redacted = self::getContainer()->get(RedactionText::class)->description((int) $this->room->getItemId());
        foreach ([$all, $group, $topic, $hashtag] as $label) {
            self::assertNull($this->labels($label)['deletion_date'], 'labels stay');
            self::assertNull($this->labels($label)['creator_id'], 'without the person');
        }
        self::assertSame('GROUP_ALL_DESC', $this->labels($all)['description'], 'the group of all members is left alone');
        self::assertSame('ALL', $this->labels($all)['name']);
        self::assertSame('Lerngruppe', $this->labels($group)['name']);
        self::assertSame($redacted, $this->labels($group)['description']);
        self::assertSame('Klausur', $this->labels($topic)['name']);
        self::assertSame($redacted, $this->labels($topic)['description']);
        self::assertSame('Statistik', $this->labels($hashtag)['name']);
        self::assertSame(1, (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM links WHERE from_item_id = ? AND to_item_id = ? AND link_type = 'buzzword_for' AND deletion_date IS NULL",
            [$othersMaterial, $hashtag],
        ), 'the hashtag stays on the entries of others');
        self::assertNull($this->connection->fetchOne('SELECT deletion_date FROM tag WHERE item_id = ?', [$category]));
        self::assertNull($this->connection->fetchOne('SELECT creator_id FROM tag WHERE item_id = ?', [$category]));
    }

    public function testKeepingThePersonsEntriesKeepsTheLabelsAsTheyAre(): void
    {
        $this->setStrategy(cascade: false);
        $group = $this->createLabel('Lerngruppe', 'group', 'Wir treffen uns dienstags.');

        $this->deleteMembership();

        self::assertNull($this->labels($group)['deletion_date']);
        self::assertNull($this->labels($group)['creator_id']);
        self::assertSame('Wir treffen uns dienstags.', $this->labels($group)['description']);
    }

    /**
     * @param array<string, int> $extras
     */
    private function createLabel(string $name, string $type, string $description, array $extras = []): int
    {
        $itemId = (int) LabelFactory::createOne(['name' => $name, 'type' => $type, 'room' => $this->room, 'creator' => $this->roomUser])->getItemId();
        $this->connection->update('labels', ['description' => $description, 'extras' => serialize($extras)], ['item_id' => $itemId]);

        return $itemId;
    }

    private function createCategory(string $title): int
    {
        $now = date('Y-m-d H:i:s');
        $this->connection->insert('items', ['context_id' => $this->room->getItemId(), 'modification_date' => $now, 'type' => 'tag']);
        $itemId = (int) $this->connection->lastInsertId();
        $this->connection->insert('tag', [
            'item_id' => $itemId,
            'context_id' => $this->room->getItemId(),
            'creator_id' => $this->roomUser->getItemId(),
            'modifier_id' => $this->roomUser->getItemId(),
            'creation_date' => $now,
            'modification_date' => $now,
            'title' => $title,
        ]);

        return $itemId;
    }

    private function createMaterialOfAnotherPerson(): int
    {
        $other = RoomUserFactory::createOne([
            'account' => AccountFactory::createOne(['portal' => $this->account->getPortal(), 'authSource' => $this->account->getAuthSource()]),
            'room' => $this->room,
            'status' => 2,
        ]);

        return (int) MaterialFactory::createOne(['room' => $this->room, 'creator' => $other])->getItemId();
    }

    private function tagWithHashtag(int $itemId, int $hashtagId): void
    {
        $this->connection->insert('links', [
            'from_item_id' => $itemId,
            'from_version_id' => 0,
            'to_item_id' => $hashtagId,
            'to_version_id' => 0,
            'link_type' => 'buzzword_for',
            'context_id' => $this->room->getItemId(),
        ]);
    }

    private function deleteMembership(): void
    {
        self::getContainer()->get(UserMembershipDeleter::class)->softDeleteMembership((int) $this->roomUser->getItemId(), 1);
    }

    /**
     * Both switches at once: the portal has to allow a personal choice, and
     * the personal choice has to say what the test wants.
     */
    private function setStrategy(bool $cascade): void
    {
        $this->account->getPortal()
            ->setAllowUserDefinedDeletionStrategy(true)
            ->setCascadingUserDeletionStrategy(!$cascade);
        self::getContainer()->get(AccountSettingsManager::class)->storeSetting(
            $this->account,
            AccountSetting::USER_DELETION_CASCADING_ITEMS,
            ['enabled' => $cascade]
        );
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($this->account);
        $entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function labels(int $itemId): array
    {
        return $this->connection->fetchAssociative('SELECT * FROM labels WHERE item_id = ?', [$itemId]) ?: [];
    }
}
