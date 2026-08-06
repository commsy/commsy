<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Application;

use App\Entity\Account;
use App\Entity\Portal;
use App\Entity\User;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;

/**
 * A community room is a room, not a portal directory: whose person entries its
 * members may open is decided by the entry's own context, exactly as in every
 * other room type.
 *
 * The four cases below are the whole rule. The first two are the ones that used
 * to differ — a community room granted on the actor's location alone, which
 * reached entries of unrelated rooms and of other portals. The last two are the
 * control: what legitimately worked must keep working, or the fix would be a
 * denial of service rather than a boundary.
 */
final class CommunityRoomUserVisibilityTest extends AbstractApplicationTestCase
{
    private Portal $portal;
    private int $communityRoomId;
    private Account $member;
    private string $memberPassword;

    private int $entryInUnrelatedRoom;
    private int $entryInAnotherPortal;
    private int $entryOfFellowMember;
    private int $entryOfMemberThemselves;

    public function setUp(): void
    {
        parent::setUp();

        $this->portal = $this->createPortal();

        // the actor and the community room they belong to
        [$this->member, $this->memberPassword] = $this->createAccount($this->portal, 'gemeinschaft.mitglied');
        $community = RoomFactory::createOne([
            'type' => 'community',
            'contextId' => $this->portal->getId(),
            'portal' => $this->portal,
        ]);
        $this->communityRoomId = $community->getItemId();

        $memberEntry = RoomUserFactory::createOne(['account' => $this->member, 'room' => $community]);
        $this->entryOfMemberThemselves = $memberEntry->getItemId();

        // someone else in the same community room
        [$fellow] = $this->createAccount($this->portal, 'gemeinschaft.kollegin');
        $this->entryOfFellowMember = RoomUserFactory::createOne([
            'account' => $fellow,
            'room' => $community,
        ])->getItemId();

        // a project room of the same portal that the actor is no member of,
        // and which is not linked to the community room either
        [$stranger] = $this->createAccount($this->portal, 'fremder.raum.person');
        $unrelatedRoom = RoomFactory::createOne([
            'type' => 'project',
            'contextId' => $this->portal->getId(),
            'portal' => $this->portal,
        ]);
        $this->entryInUnrelatedRoom = RoomUserFactory::createOne([
            'account' => $stranger,
            'room' => $unrelatedRoom,
        ])->getItemId();

        // a person of a wholly different portal
        $otherPortal = $this->createPortal();
        [$foreigner] = $this->createAccount($otherPortal, 'anderes.portal.person');
        $foreignRoom = RoomFactory::createOne([
            'type' => 'project',
            'contextId' => $otherPortal->getId(),
            'portal' => $otherPortal,
        ]);
        $this->entryInAnotherPortal = RoomUserFactory::createOne([
            'account' => $foreigner,
            'room' => $foreignRoom,
        ])->getItemId();
    }

    public function testMemberCannotOpenAPersonOfAnUnrelatedRoomOfTheSamePortal(): void
    {
        $this->loginAsMember();

        $this->client->request(
            'GET',
            "/room/$this->communityRoomId/user/$this->entryInUnrelatedRoom"
        );

        $this->assertAccessDenied(
            'a community room must not reach into a room the caller is no member of'
        );
    }

    public function testMemberCannotOpenAPersonOfAnotherPortal(): void
    {
        $this->loginAsMember();

        $this->client->request(
            'GET',
            "/room/$this->communityRoomId/user/$this->entryInAnotherPortal"
        );

        $this->assertAccessDenied('a community room must not reach across the portal boundary');
    }

    public function testMemberStillSeesFellowMembersOfTheCommunityRoom(): void
    {
        $this->loginAsMember();

        $this->client->request(
            'GET',
            "/room/$this->communityRoomId/user/$this->entryOfFellowMember"
        );

        $this->assertResponseIsSuccessful();
    }

    public function testMemberStillSeesTheirOwnEntry(): void
    {
        $this->loginAsMember();

        $this->client->request(
            'GET',
            "/room/$this->communityRoomId/user/$this->entryOfMemberThemselves"
        );

        $this->assertResponseIsSuccessful();
    }

    /**
     * The print route mirrors the detail view and inherits the same rule, so the
     * boundary has to hold there too — it is the second way into the same data.
     */
    public function testThePrintRouteInheritsTheBoundary(): void
    {
        $this->loginAsMember();

        $this->client->request(
            'GET',
            "/room/$this->communityRoomId/user/$this->entryInAnotherPortal/print"
        );

        $this->assertAccessDenied('the print route must not reach across the portal boundary either');
    }

    private function createPortal(): Portal
    {
        $authSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);

        return PortalFactory::createOne(['authSources' => [$authSource]]);
    }

    /**
     * @return array{0: Account, 1: string} the account and its plain password —
     *                                      the latter does not survive a reload
     *                                      of the Foundry object, so it is taken
     *                                      here while it still exists
     */
    private function createAccount(Portal $portal, string $username): array
    {
        $account = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal->getAuthSources()->first(),
            'username' => $username,
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        return [$account, (string) $account->getPlainPassword()];
    }

    private function loginAsMember(): void
    {
        $this->loginAsUser(
            $this->portal->getId(),
            $this->member->getUsername(),
            $this->memberPassword
        );
    }

    /**
     * Denial shows up as the redirect App\Security\AccessDeniedHandler produces,
     * not as 403 — this firewall does not answer 403 for a browser request. A 5xx
     * is explicitly not accepted, or a crashing template would count as
     * protection and hide a missing check.
     */
    private function assertAccessDenied(string $message): void
    {
        $status = $this->client->getResponse()->getStatusCode();

        self::assertContains(
            $status,
            [301, 302, 401, 403],
            $message." (got HTTP $status)"
        );
    }
}
