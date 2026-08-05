<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use Knp\Snappy\Pdf;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins that the person print route enforces the same access rules as the detail
 * view it mirrors. The detail assertions are the control: they show the voter
 * working, so a passing detail check next to a failing print check isolates the
 * print route as the gap.
 *
 * Reduced against the 10.5 original, which walks every rubric: the rubric
 * factories (Announcement, Dates, Discussion, Label, Material, Todo) do not
 * exist on this branch. Personal data is the part that mattered here anyway —
 * the print route was the only one of the two without any guard.
 */
#[WithStory(RoomWithMemberStory::class)]
class PrintRoutesAuthorizationTest extends AbstractApplicationTestCase
{
    private Account $member;
    private Account $outsider;
    private Room $room;
    private User $roomUser;
    private int $roomId;

    private int $foreignUserItemId;

    public function setUp(): void
    {
        parent::setUp();

        $this->member = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->roomId = $this->room->getItemId();

        $pdf = $this->createMock(Pdf::class);
        $pdf->method('getOutputFromHtml')->willReturn('%PDF-1.4 stub');
        static::getContainer()->set('knp_snappy.pdf', $pdf);

        // A second account in the same portal that is not a member of the room.
        // The portal is not passed on purpose: AccountFactory only wires the
        // auth source when it resolves the portal itself.
        $this->outsider = AccountFactory::createOne([
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $this->assertSame(
            $this->member->getPortal()->getId(),
            $this->outsider->getPortal()->getId(),
            'outsider must live in the same portal, otherwise this tests the wrong boundary'
        );

        // Must run after the outsider exists: the foreign room holds their
        // person entry, not the member's, or the member would legitimately be
        // allowed to see it and the check below would prove nothing.
        $this->createForeignRoom();
    }

    /**
     * Control: the guarded detail route must reject a non-member.
     */
    public function testOutsiderIsDeniedOnUserDetailView(): void
    {
        $this->loginAsOutsider();

        $this->client->request('GET', "/room/$this->roomId/user/{$this->roomUser->getItemId()}");

        $this->assertAccessDenied('non-member must not see the person detail view');
    }

    public function testOutsiderIsDeniedOnUserPrint(): void
    {
        $this->loginAsOutsider();

        $this->client->request('GET', "/room/$this->roomId/user/{$this->roomUser->getItemId()}/print");

        $this->assertAccessDenied('non-member must not print personal data');
    }

    public function testAnonymousGetsNoContentFromUserPrint(): void
    {
        $itemId = $this->roomUser->getItemId();

        $this->client->request('GET', "/room/$this->roomId/user/$itemId/print");

        $this->assertAccessDenied('anonymous request must not receive personal data');
    }

    public function testOutsiderIsDeniedOnUserPrintList(): void
    {
        $this->loginAsOutsider();

        $this->client->request('GET', "/room/$this->roomId/user/print/none");

        $this->assertAccessDenied('non-member must not print the person list');
    }

    /**
     * The room id in the URL must belong to the requested item. Otherwise any
     * room the caller may enter can be used as a passe-partout for entries
     * living somewhere else.
     *
     * Browsed through a room created here rather than through the story room,
     * whose type RoomFactory picks at random: the rule only holds outside
     * community rooms, and the next test pins the other half.
     */
    public function testRoomIdMustMatchTheRequestedItem(): void
    {
        $projectRoomId = $this->createRoomWithMember('project');

        $this->loginAsMember();

        $this->client->request('GET', "/room/$projectRoomId/user/$this->foreignUserItemId/print");

        $this->assertAccessDenied(
            'an item from another room must not be printable through a room the caller may enter'
        );
    }

    /**
     * Characterization, not an endorsement: in a community room a logged-in
     * member sees every person of the portal, including one whose entry lives
     * in a room they are no member of. `cs_user_item::maySee()` grants on
     * `isVisibleForLoggedIn()`, which is hard-true. The print route inherits
     * that, exactly like the detail view it mirrors.
     *
     * Pinned so the ITEM_SEE guard added alongside cannot be mistaken for a
     * tighter rule than it is, and so a later change to that visibility rule
     * shows up here rather than silently.
     */
    public function testCommunityRoomLetsAMemberPrintAnyPersonOfThePortal(): void
    {
        $communityRoomId = $this->createRoomWithMember('community');

        $this->loginAsMember();

        $this->client->request('GET', "/room/$communityRoomId/user/$this->foreignUserItemId/print");

        $this->assertResponseIsSuccessful();
    }

    private function loginAsMember(): void
    {
        $this->loginAsUser(
            $this->member->getPortal()->getId(),
            $this->member->getUsername(),
            $this->member->getPlainPassword()
        );
    }

    private function loginAsOutsider(): void
    {
        $this->loginAsUser(
            $this->outsider->getPortal()->getId(),
            $this->outsider->getUsername(),
            $this->outsider->getPlainPassword()
        );
    }

    /**
     * A non-member is turned away with a redirect to the room request page, not
     * with 403 — only the server context answers 403. What matters is that no
     * content comes back.
     *
     * A 5xx is explicitly not a denial: a template crashing before it finishes
     * would otherwise count as "protected" and hide a missing check.
     */
    private function assertAccessDenied(string $message): void
    {
        $status = $this->client->getResponse()->getStatusCode();

        $this->assertContains(
            $status,
            [301, 302, 401, 403],
            $message." (got HTTP $status)"
        );
    }

    /**
     * A room of the given type in the member's portal, with the member joined,
     * so it can serve as the browsing context.
     */
    private function createRoomWithMember(string $type): int
    {
        $room = RoomFactory::createOne([
            'type' => $type,
            'contextId' => $this->member->getPortal()->getId(),
            'portal' => $this->member->getPortal(),
        ]);

        RoomUserFactory::createOne([
            'account' => $this->member,
            'room' => $room,
        ]);

        return $room->getItemId();
    }

    /**
     * A second room holding a person, used to check that the room id in the URL
     * is validated against the item.
     */
    private function createForeignRoom(): void
    {
        $foreignRoom = RoomFactory::createOne([
            'contextId' => $this->member->getPortal()->getId(),
            'portal' => $this->member->getPortal(),
        ]);

        $foreignUser = RoomUserFactory::createOne([
            'account' => $this->outsider,
            'room' => $foreignRoom,
        ]);

        $this->foreignUserItemId = $foreignUser->getItemId();
    }
}
