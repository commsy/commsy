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

namespace Tests\Integration\Rubric;

use App\Entity\Account;
use App\Entity\Discussions;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use cs_user_item;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Factory\DiscussionArticleFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins what pasting a discussion from the clipboard into another room writes,
 * the way InsertAction runs it: a new discussion of the current user in the
 * current room, its answers copied along (deleted ones as deleted, no drafts)
 * with their files.
 *
 * The paste runs in a fresh kernel, as its request would: the legacy managers
 * take the current user once, when they are built.
 */
#[WithStory(AccountStory::class)]
final class DiscussionCopyCharacterizationTest extends KernelTestCase
{
    private Connection $connection;
    private cs_environment $legacyEnvironment;
    private Account $account;
    private Room $sourceRoom;
    private Room $targetRoom;
    private User $sourceUser;
    private User $targetUser;
    private int $portalId;

    /** @var array<int, Discussions> */
    private array $discussions = [];

    /** @var string[] */
    private array $createdPaths = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->account = AccountStory::get('account');
        $this->portalId = (int) $this->account->getPortal()?->getId();
        $portal = $this->account->getPortal();
        $this->sourceRoom = RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
        $this->targetRoom = RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
        $this->sourceUser = RoomUserFactory::createOne(['account' => $this->account, 'room' => $this->sourceRoom, 'status' => 2]);
        $this->targetUser = RoomUserFactory::createOne(['account' => $this->account, 'room' => $this->targetRoom, 'status' => 2]);
        $this->actIn($this->sourceRoom, $this->sourceUser);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->createdPaths);
        $this->createdPaths = [];

        parent::tearDown();
    }

    public function testTheAnswersAreCopiedWithTheDiscussion(): void
    {
        $sourceId = $this->createDiscussion();
        $first = $this->createAnswer($sourceId, '0001', '<p>Eins</p>');
        $this->connection->update('discussionarticles', ['modification_date' => '2026-01-07 10:00:00'], ['item_id' => $first]);
        $this->createAnswer($sourceId, '0001.0001', '<p>Eins-Eins</p>');
        $deleted = $this->createAnswer($sourceId, '0002', '<p>Zwei</p>');
        $this->connection->update('discussionarticles', ['deleter_id' => $this->sourceUser->getItemId(), 'deletion_date' => '2026-01-01 00:00:00'], ['item_id' => $deleted]);
        $draft = $this->createAnswer($sourceId, '0003', '<p>Entwurf</p>');
        $this->connection->update('items', ['draft' => 1], ['item_id' => $draft]);

        $copyId = $this->paste($sourceId);

        $copy = $this->connection->fetchAssociative('SELECT * FROM discussions WHERE item_id = ?', [$copyId]);
        self::assertSame($this->targetRoom->getItemId(), (int) $copy['context_id']);
        self::assertSame($this->targetUser->getItemId(), (int) $copy['creator_id']);
        self::assertSame('Quelle', $copy['title']);

        $answers = $this->connection->fetchAllAssociative('SELECT * FROM discussionarticles WHERE discussion_id = ? ORDER BY position', [$copyId]);
        self::assertSame(['0001', '0001.0001', '0002'], array_column($answers, 'position'));
        self::assertSame(['<p>Eins</p>', '<p>Eins-Eins</p>', '<p>Zwei</p>'], array_column($answers, 'description'));
        self::assertNotContains($first, array_map('intval', array_column($answers, 'item_id')));
        foreach ($answers as $answer) {
            self::assertSame($this->targetRoom->getItemId(), (int) $answer['context_id']);
            self::assertSame($this->targetUser->getItemId(), (int) $answer['creator_id']);
            self::assertSame($this->targetUser->getItemId(), (int) $answer['modifier_id']);
            self::assertSame(0, (int) $this->connection->fetchOne('SELECT draft FROM items WHERE item_id = ?', [$answer['item_id']]));
        }
        self::assertNull($answers[0]['deletion_date']);
        self::assertNull($answers[1]['deletion_date']);
        self::assertNotNull($answers[2]['deletion_date'], 'a deleted answer is copied as deleted');

        // the answers are written latest position first, so the first one is written last
        $copy = $this->connection->fetchAssociative('SELECT * FROM discussions WHERE item_id = ?', [$copyId]);
        self::assertSame((int) $answers[0]['item_id'], (int) $copy['latest_article_item_id']);
        self::assertSame('2026-01-07 10:00:00', $copy['latest_article_modification_date']);
    }

    public function testTheFilesOfTheAnswersAreCopied(): void
    {
        $sourceId = $this->createDiscussion();
        $answerId = $this->createAnswer($sourceId, '0001', '<p>Mit Datei</p>');
        $this->attachFile($answerId, 'notiz.txt', 'Notiz');

        $copyId = $this->paste($sourceId);

        $answerCopyId = (int) $this->connection->fetchOne('SELECT item_id FROM discussionarticles WHERE discussion_id = ?', [$copyId]);
        self::assertSame(['notiz.txt'], $this->fileNamesOf($answerCopyId));
    }

    private function createDiscussion(): int
    {
        $discussion = DiscussionFactory::createOne(['title' => 'Quelle', 'room' => $this->sourceRoom, 'creator' => $this->sourceUser]);
        $this->discussions[(int) $discussion->getItemId()] = $discussion;

        return (int) $discussion->getItemId();
    }

    private function createAnswer(int $discussionId, string $position, string $description): int
    {
        return (int) DiscussionArticleFactory::createOne([
            'room' => $this->sourceRoom,
            'creator' => $this->sourceUser,
            'discussion' => $this->discussions[$discussionId],
            'position' => $position,
            'description' => $description,
        ])->getItemId();
    }

    /**
     * As InsertAction: the typed item, without external viewers, copied in the
     * target room as its member.
     */
    private function paste(int $sourceId): int
    {
        $this->actIn($this->targetRoom, $this->targetUser);

        $item = self::getContainer()->get(ItemService::class)->getTypedItem($sourceId);
        $item->setExternalViewerAccounts([]);
        $copy = $item->copy();
        self::assertSame([], $copy->getErrorArray());

        return (int) $copy->getItemID();
    }

    /**
     * @return list<string>
     */
    private function fileNamesOf(int $itemId): array
    {
        $files = $this->connection->fetchAllAssociative(
            'SELECT f.files_id, f.context_id, f.filename FROM item_link_file lf JOIN files f ON f.files_id = lf.file_id
              WHERE lf.item_iid = ? AND lf.deletion_date IS NULL',
            [$itemId],
        );
        foreach ($files as $file) {
            self::assertSame($this->targetRoom->getItemId(), (int) $file['context_id']);
            $this->createdPaths[] = $this->diskPath($this->targetRoom->getItemId(), (int) $file['files_id'], $file['filename']);
        }

        return array_column($files, 'filename');
    }

    /**
     * A fresh kernel acting as $user in $room, as a request to it would.
     */
    private function actIn(Room $room, User $user): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();
        $container = self::getContainer();
        $this->connection = $container->get(EntityManagerInterface::class)->getConnection();
        $this->legacyEnvironment = $container->get(LegacyEnvironment::class)->getEnvironment();

        $container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($this->account, 'main', $this->account->getRoles()),
        );
        $request = new Request();
        $request->attributes->set('roomId', $room->getItemId());
        $container->get(RequestStack::class)->push($request);

        $this->legacyEnvironment->setCurrentPortalID($this->portalId);
        $this->legacyEnvironment->setCurrentContextID($room->getItemId());

        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextLimit($room->getItemId());
        $userManager->setUserIDLimit($user->getUserId());
        $userManager->select();
        /** @var cs_user_item $userItem */
        $userItem = $userManager->get()->getFirst();
        $this->legacyEnvironment->setCurrentUserItem($userItem);
    }

    private function attachFile(int $itemId, string $filename, string $contents): void
    {
        $now = date('Y-m-d H:i:s');
        $this->connection->insert('files', [
            'context_id' => $this->sourceRoom->getItemId(),
            'creator_id' => $this->sourceUser->getItemId(),
            'creation_date' => $now,
            'modification_date' => $now,
            'filename' => $filename,
            'filepath' => '',
            'portal_id' => $this->portalId,
        ]);
        $fileId = (int) $this->connection->lastInsertId();

        $absolutePath = $this->diskPath($this->sourceRoom->getItemId(), $fileId, $filename);
        (new Filesystem())->dumpFile($absolutePath, $contents);
        $this->createdPaths[] = $absolutePath;

        $projectDir = (string) self::getContainer()->getParameter('kernel.project_dir');
        $this->connection->update('files', ['filepath' => ltrim(str_replace($projectDir, '', $absolutePath), '/')], ['files_id' => $fileId]);
        $this->connection->insert('item_link_file', ['item_iid' => $itemId, 'item_vid' => 0, 'file_id' => $fileId]);
    }

    private function diskPath(int $roomId, int $fileId, string $filename): string
    {
        $discManager = $this->legacyEnvironment->getDiscManager();
        $discManager->setPortalID($this->portalId);

        return $discManager->getAbsoluteFilePath($this->portalId, $roomId, $fileId.'.'.strtolower(pathinfo($filename, PATHINFO_EXTENSION)));
    }
}
