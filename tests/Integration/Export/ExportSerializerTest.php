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

use App\Entity\User;
use App\Export\ExportSerializer;
use App\Repository\AnnouncementRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Tests\Factory\AnnouncementFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins that the serializer actually reaches UserNormalizer. One that is never
 * chosen would hand out the whole user row while the unit tests stay green.
 */
final class ExportSerializerTest extends KernelTestCase
{
    #[WithStory(RoomWithMemberStory::class)]
    public function testItRendersAPersonTheExportWay(): void
    {
        self::bootKernel();

        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');

        $normalized = self::getContainer()->get(ExportSerializer::class)->toArray($roomUser);

        $this->assertSame(
            ['id', 'name', 'email', 'roomStatus'],
            array_keys($normalized),
            'exactly these four, in this order'
        );
        $this->assertSame($roomUser->getItemId(), $normalized['id']);
        $this->assertSame($roomUser->getFullname(), $normalized['name']);
        $this->assertSame('user', $normalized['roomStatus']);
    }

    /**
     * The rest of the user row must not reach the document.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testNothingElseOfThePersonComesAlong(): void
    {
        self::bootKernel();

        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');

        $normalized = self::getContainer()->get(ExportSerializer::class)->toArray($roomUser);

        foreach (['userId', 'account', 'extras', 'city', 'lastlogin', 'status', 'portal', 'room'] as $field) {
            $this->assertArrayNotHasKey($field, $normalized);
        }
    }

    /**
     * Why this serializer exists: the application's one drops relations to
     * non-resources, and a person is not one.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testItIsNotTheApplicationSerializer(): void
    {
        self::bootKernel();

        $this->assertNotSame(
            self::getContainer()->get(NormalizerInterface::class),
            self::getContainer()->get(ExportSerializer::class),
            'the export must not be routed through the interface serializer'
        );
    }

    /**
     * The decorating normalizers stand in the application's chain as well, and a
     * Serializer points the normalizers it is given back at itself. Without a
     * chain of their own, whichever serializer was built last would own them and
     * the export would run through the interface's chain — sub-entries added
     * twice, and which fields come out decided by the resource's own group.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testItKeepsItsOwnChainOnceTheApplicationSerializerIsBuilt(): void
    {
        self::bootKernel();

        $exportSerializer = self::getContainer()->get(ExportSerializer::class);
        self::getContainer()->get('serializer');

        $itemId = AnnouncementFactory::createOne([
            'room' => RoomWithMemberStory::get('room'),
            'creator' => RoomWithMemberStory::get('roomUser'),
        ])->getItemId();
        $announcement = self::getContainer()->get(AnnouncementRepository::class)->findOneForExport($itemId);

        $normalized = $exportSerializer->toArray($announcement);

        $this->assertArrayHasKey('categories', $normalized, 'its own decorator still ran');
        $this->assertArrayNotHasKey('annotations', $normalized, 'sub-entries are the builder\'s business here');
    }
}
