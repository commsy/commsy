<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Entity\Room;
use App\Entity\User;
use App\Repository\LinksRepository;
use App\Utils\LabelService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\LabelFactory;
use Tests\Factory\LinkFactory;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The `links` table carries an entry's hashtags, among other typed links. These
 * tests pin the reading against what legacy does — same rows in, same ids out —
 * because the export and the interface must not disagree with the pages the
 * hashtags are edited on.
 */
final class LinksRepositoryTest extends KernelTestCase
{
    private const HASHTAG_LINK = 'buzzword_for';

    private Connection $connection;
    private LinksRepository $links;
    private Room $room;
    private User $roomUser;

    #[WithStory(RoomWithMemberStory::class)]
    public function testItFindsAHashtagLinkedToAnEntry(): void
    {
        $material = $this->createMaterial();
        $hashtag = $this->createHashtag();

        $this->link($material, $hashtag);

        $this->assertSame(
            [$hashtag],
            $this->links->findLinkedItemIds($material, self::HASHTAG_LINK, $this->roomId())
        );
    }

    /**
     * Legacy merges links from an item with links to it, so which column an
     * entry sits in is an accident of how the row was written.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheEntryIsFoundOnEitherSideOfTheLink(): void
    {
        $material = $this->createMaterial();
        $hashtag = $this->createHashtag();

        $this->link($hashtag, $material);

        $this->assertSame(
            [$hashtag],
            $this->links->findLinkedItemIds($material, self::HASHTAG_LINK, $this->roomId())
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testItAgreesWithTheLegacyReader(): void
    {
        $material = $this->createMaterial();
        $first = $this->createHashtag();
        $second = $this->createHashtag();

        $this->link($material, $first);
        $this->link($second, $material);

        $mine = $this->links->findLinkedItemIds($material, self::HASHTAG_LINK, $this->roomId());
        $legacy = self::getContainer()->get(LabelService::class)
            ->getLinkedHashtagIds($material, $this->roomId());

        sort($mine);
        sort($legacy);

        $this->assertSame($legacy, $mine);
        $this->assertSame([$first, $second], $mine);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAnotherLinkTypeIsNotReturned(): void
    {
        $material = $this->createMaterial();
        $hashtag = $this->createHashtag();

        $this->link($material, $hashtag, 'relevant_for');

        $this->assertSame(
            [],
            $this->links->findLinkedItemIds($material, self::HASHTAG_LINK, $this->roomId())
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedLinkIsNotReturned(): void
    {
        $material = $this->createMaterial();
        $hashtag = $this->createHashtag();

        $this->link($material, $hashtag);

        // Legacy hides a link once someone is named as its deleter, and looks at
        // nothing else — deletion_date alone leaves it in place.
        $this->connection->executeStatement(
            'UPDATE links SET deleter_id = :deleter WHERE from_item_id = :from AND to_item_id = :to',
            ['deleter' => $this->roomUser->getItemId(), 'from' => $material, 'to' => $hashtag]
        );

        $this->assertSame(
            [],
            $this->links->findLinkedItemIds($material, self::HASHTAG_LINK, $this->roomId())
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testALinkOfAnotherRoomIsNotReturned(): void
    {
        $material = $this->createMaterial();
        $hashtag = $this->createHashtag();

        $this->link($material, $hashtag);

        $this->assertSame(
            [],
            $this->links->findLinkedItemIds($material, self::HASHTAG_LINK, $this->roomId() + 9999)
        );
    }

    /**
     * A material carries its hashtags per version, so a version id must match
     * the version column of the side the entry sits on.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAVersionIdSelectsTheLinksOfThatVersion(): void
    {
        $material = $this->createMaterial();
        $hashtag = $this->createHashtag();

        $this->link($material, $hashtag);

        $version = (int) $this->connection->fetchOne(
            'SELECT from_version_id FROM links WHERE from_item_id = :from AND to_item_id = :to',
            ['from' => $material, 'to' => $hashtag]
        );

        $this->assertSame(
            [$hashtag],
            $this->links->findLinkedItemIds($material, self::HASHTAG_LINK, $this->roomId(), $version)
        );
        $this->assertSame(
            [],
            $this->links->findLinkedItemIds($material, self::HASHTAG_LINK, $this->roomId(), $version + 1)
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAnEntryWithoutLinksComesBackEmpty(): void
    {
        $material = $this->createMaterial();

        $this->assertSame(
            [],
            $this->links->findLinkedItemIds($material, self::HASHTAG_LINK, $this->roomId())
        );
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->links = self::getContainer()->get(LinksRepository::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
    }

    private function roomId(): int
    {
        return $this->room->getItemId();
    }

    private function createMaterial(): int
    {
        return MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ])->getItemId();
    }

    private function createHashtag(): int
    {
        return LabelFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'type' => 'buzzword',
        ])->getItemId();
    }

    private function link(int $fromItemId, int $toItemId, string $linkType = self::HASHTAG_LINK): void
    {
        LinkFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'fromItemId' => $fromItemId,
            'toItemId' => $toItemId,
            'linkType' => $linkType,
        ]);
    }
}
