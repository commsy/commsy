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
use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\User;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The contact form writes to profiles of a room one may enter, and to the
 * contact persons and moderators of any room of the own portal. Members of
 * other rooms and profiles of other portals are out of reach, and nothing
 * goes to an address typed in freely.
 */
#[WithStory(RoomWithMemberStory::class)]
final class ContactFormRecipientsTest extends AbstractApplicationTestCase
{
    private Account $account;
    private Room $room;
    private User $neighbour;
    private Room $otherRoom;
    private User $otherModerator;
    private User $otherMember;
    private User $foreignModerator;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $portal = $this->account->getPortal();
        $this->neighbour = $this->profile($portal, $this->room, 2);

        $this->otherRoom = $this->room($portal);
        $this->otherModerator = $this->profile($portal, $this->otherRoom, 3);
        $this->otherMember = $this->profile($portal, $this->otherRoom, 2);

        $foreignPortal = PortalFactory::createOne();
        $this->foreignModerator = $this->profile($foreignPortal, $this->room($foreignPortal), 3);

        $this->loginAsUser((int) $portal?->getId(), $this->account->getUsername(), $this->account->getPlainPassword());
    }

    public function testAProfileOfTheOwnRoomCanBeWritten(): void
    {
        $this->open($this->room, $this->neighbour);

        self::assertResponseIsSuccessful();
    }

    public function testTheModerationOfAnotherRoomOfThePortalCanBeWritten(): void
    {
        $this->open($this->room, $this->otherModerator);

        self::assertResponseIsSuccessful();
    }

    public function testAMemberOfAnotherRoomCannotBeWritten(): void
    {
        $this->open($this->room, $this->otherMember);
        $this->assertDenied();

        // naming the member's room in the address does not help without access to it
        $this->open($this->otherRoom, $this->otherMember);
        $this->assertDenied();
    }

    public function testAProfileOfAnotherPortalCannotBeWritten(): void
    {
        $this->open($this->room, $this->foreignModerator);

        $this->assertDenied();
    }

    public function testFurtherRecipientsAreKeptToThoseThatMayBeWritten(): void
    {
        $crawler = $this->open($this->room, $this->neighbour, [$this->otherModerator, $this->otherMember, $this->foreignModerator]);
        self::assertResponseIsSuccessful();

        $message = (string) $crawler->filter('textarea')->first()->text('');
        self::assertStringContainsString($this->otherModerator->getFullName(), $message);
        self::assertStringNotContainsString($this->otherMember->getFullName(), $message);
        self::assertStringNotContainsString($this->foreignModerator->getFullName(), $message);
    }

    public function testNoAddressCanBeTypedInFreely(): void
    {
        $crawler = $this->open($this->room, $this->neighbour);

        self::assertCount(0, $crawler->filter('[name*="additional_recipient"]'));
    }

    /**
     * A denied access bounces to the description of the room.
     */
    private function assertDenied(): void
    {
        self::assertResponseRedirects();
        self::assertStringContainsString('/room/', (string) $this->client->getResponse()->headers->get('Location'));
        self::assertStringNotContainsString('contactForm', (string) $this->client->getResponse()->headers->get('Location'));
    }

    /**
     * @param list<User> $furtherRecipients
     */
    private function open(Room $room, User $recipient, array $furtherRecipients = []): \Symfony\Component\DomCrawler\Crawler
    {
        $path = "/room/{$room->getItemId()}/user/{$recipient->getItemId()}/contactForm/app_room_home";
        if ([] !== $furtherRecipients) {
            $path .= '/'.implode(',', array_map(static fn (User $user): int => $user->getItemId(), $furtherRecipients));
        }

        return $this->client->request('GET', $path);
    }

    private function room(?Portal $portal): Room
    {
        return RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
    }

    private function profile(?Portal $portal, Room $room, int $status): User
    {
        $account = AccountFactory::createOne(['portal' => $portal, 'authSource' => $portal?->getAuthSources()->first()]);

        return RoomUserFactory::createOne(['account' => $account, 'room' => $room, 'status' => $status]);
    }
}
