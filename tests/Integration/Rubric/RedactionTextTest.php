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

use App\Entity\Room;
use App\Rubric\RedactionText;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The placeholder has to resolve to real wording in the room's language.
 * A missing translation would silently write the key into the column, and
 * nothing downstream would notice.
 */
final class RedactionTextTest extends KernelTestCase
{
    private RedactionText $redactionText;
    private Room $room;

    #[WithStory(RoomWithMemberStory::class)]
    public function testGermanRoomGetsGermanWording(): void
    {
        $this->setRoomLanguage('de');

        self::assertSame('Gelöschter Eintrag', $this->redactionText->title($this->room->getItemId()));
        self::assertStringContainsString(
            'um den Zusammenhang zu anderen Einträgen zu wahren',
            $this->redactionText->description($this->room->getItemId())
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testEnglishRoomGetsEnglishWording(): void
    {
        $this->setRoomLanguage('en');

        self::assertSame('Deleted entry', $this->redactionText->title($this->room->getItemId()));
        self::assertStringContainsString(
            'keep its context to other entries intact',
            $this->redactionText->description($this->room->getItemId())
        );
    }

    /**
     * A room set to follow the reader's language has no language of its
     * own, and there is no reader during a background account deletion.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testRoomFollowingTheReaderFallsBackToTheDefault(): void
    {
        $this->setRoomLanguage('user');

        self::assertSame('Gelöschter Eintrag', $this->redactionText->title($this->room->getItemId()));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testUnknownContextFallsBackInsteadOfFailing(): void
    {
        self::assertSame('Gelöschter Eintrag', $this->redactionText->title(999999));
    }

    /**
     * The wording must not name the cause: "removed at this person's
     * request, along with all their other entries in this room" is itself
     * information about that person.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testWordingDoesNotNameTheCause(): void
    {
        $this->setRoomLanguage('de');
        $description = $this->redactionText->description($this->room->getItemId());

        self::assertStringNotContainsStringIgnoringCase('datenschutz', $description);
        self::assertStringNotContainsStringIgnoringCase('erstellerin', $description);
        self::assertStringNotContainsStringIgnoringCase('person', $description);
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $this->redactionText = self::getContainer()->get(RedactionText::class);
        $this->room = RoomWithMemberStory::get('room');
    }

    private function setRoomLanguage(string $language): void
    {
        $this->room->setLanguage($language);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($this->room);
        $em->flush();
    }
}
