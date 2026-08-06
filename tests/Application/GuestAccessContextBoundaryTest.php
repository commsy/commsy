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
use App\Entity\Room;
use App\Entity\User;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Factory\SectionFactory;

/**
 * Guest access is bounded by the room that granted it.
 *
 * The branch has no membership to lean on, so without comparing the entry's
 * context to the room being browsed, a guest-open room would hand out every
 * activated entry of every other room and every other portal.
 *
 * The actor here is someone whose join request was REJECTED — status 0 with a
 * real user_id, the same status the dedicated guest identity carries. That is
 * deliberate: the room stands open to any passer-by, so having asked to join
 * and been turned down cannot leave someone with less than a stranger who
 * never asked. What the branch grants is reading; writing runs through
 * attributes that require membership.
 *
 * Most assertions ask the voter rather than the response status on purpose. The
 * rubric controllers redirect on their own when an item does not belong to the
 * room in the path, so an HTTP-level assertion would stay green even with the
 * authorization wide open — it would test the redirect, not the rule. Person
 * routes carry no such redirect, so those are exercised end to end.
 */
final class GuestAccessContextBoundaryTest extends AbstractApplicationTestCase
{
    private Portal $portal;
    private Account $guest;
    private string $guestPassword;
    private int $guestRoomId;

    private int $personOfGuestRoom;
    private int $personOfUnrelatedRoom;
    private int $personOfAnotherPortal;

    private int $materialOfGuestRoom;
    private int $sectionOfGuestRoom;
    private int $materialOfUnrelatedRoom;
    private int $materialOfAnotherPortal;

    public function setUp(): void
    {
        parent::setUp();

        $this->portal = $this->createPortal();

        // the guest-open community room, its guest and one real member
        $guestRoom = RoomFactory::new()->openForGuests()->create([
            'type' => 'community',
            'contextId' => $this->portal->getId(),
            'portal' => $this->portal,
        ]);
        $this->guestRoomId = $guestRoom->getItemId();

        // status 0 with a real user_id: someone whose join request was turned
        // down. Not the guest identity, though it shares the status.
        [$this->guest, $this->guestPassword] = $this->createAccount($this->portal, 'abgelehnte.kennung');
        RoomUserFactory::new()->asRejected()->create([
            'account' => $this->guest,
            'room' => $guestRoom,
        ]);

        [$memberAccount] = $this->createAccount($this->portal, 'mitglied.kennung');
        $member = RoomUserFactory::createOne(['account' => $memberAccount, 'room' => $guestRoom]);
        $this->personOfGuestRoom = $member->getItemId();

        $material = MaterialFactory::createOne([
            'room' => $guestRoom,
            'creator' => $member,
            'title' => 'Aushang im Gemeinschaftsraum',
        ]);
        $this->materialOfGuestRoom = $material->getItemId();

        // a sub-entry: it carries the room as its context, so the boundary
        // must not lock it away
        $this->sectionOfGuestRoom = SectionFactory::createOne([
            'room' => $guestRoom,
            'creator' => $member,
            'material' => $material,
        ])->getItemId();

        // an unrelated project room of the same portal
        [$strangerRoom, $stranger] = $this->createRoomWithPerson($this->portal, 'fremde.person');
        $this->personOfUnrelatedRoom = $stranger->getItemId();
        $this->materialOfUnrelatedRoom = MaterialFactory::createOne([
            'room' => $strangerRoom,
            'creator' => $stranger,
            'title' => 'Fremdes Material',
        ])->getItemId();

        // and a wholly different portal
        $otherPortal = $this->createPortal();
        [$foreignRoom, $foreigner] = $this->createRoomWithPerson($otherPortal, 'portalfremde.person');
        $this->personOfAnotherPortal = $foreigner->getItemId();
        $this->materialOfAnotherPortal = MaterialFactory::createOne([
            'room' => $foreignRoom,
            'creator' => $foreigner,
            'title' => 'Material aus anderem Portal',
        ])->getItemId();
    }

    // ---- what the guest-open room hands out, rejected applicant included

    public function testRejectedApplicantSeesTheRoomLikeAnyGuest(): void
    {
        $this->enterAsRejectedApplicant();

        self::assertTrue($this->maySee($this->materialOfGuestRoom), 'material of that room');
        self::assertTrue($this->maySee($this->sectionOfGuestRoom), 'sub-entry of that material');
        self::assertTrue($this->maySee($this->personOfGuestRoom), 'person entry of that room');
    }

    public function testRejectedApplicantReachesAPersonOfThatRoomEndToEnd(): void
    {
        $this->enterAsRejectedApplicant();

        $this->client->request('GET', "/room/$this->guestRoomId/user/$this->personOfGuestRoom");

        $this->assertResponseIsSuccessful();
    }

    // ---- and the context bound, which no actor of this branch may cross

    public function testNoEntriesOfAnUnrelatedRoomOfTheSamePortal(): void
    {
        $this->enterAsRejectedApplicant();

        self::assertFalse($this->maySee($this->materialOfUnrelatedRoom), 'material of an unrelated room');
        self::assertFalse($this->maySee($this->personOfUnrelatedRoom), 'person of an unrelated room');
    }

    public function testNoEntriesOfAnotherPortal(): void
    {
        $this->enterAsRejectedApplicant();

        self::assertFalse($this->maySee($this->materialOfAnotherPortal), 'material of another portal');
        self::assertFalse($this->maySee($this->personOfAnotherPortal), 'person of another portal');
    }

    public function testTurnedAwayFromAForeignPersonEndToEnd(): void
    {
        $this->enterAsRejectedApplicant();

        $this->client->request('GET', "/room/$this->guestRoomId/user/$this->personOfAnotherPortal");

        $status = $this->client->getResponse()->getStatusCode();
        self::assertContains(
            $status,
            [301, 302, 401, 403],
            "a person of another portal must not be reachable (got HTTP $status)"
        );
    }

    /**
     * Primes the legacy context the way a real request does — the voter reads
     * the current room and the current user from there, so asking it without a
     * request first would judge on an empty context.
     */
    private function enterAsRejectedApplicant(): void
    {
        $this->loginAsUser(
            $this->portal->getId(),
            $this->guest->getUsername(),
            $this->guestPassword
        );

        $this->client->request('GET', "/room/$this->guestRoomId/user");
        $this->assertResponseIsSuccessful();
    }

    private function maySee(int $itemId): bool
    {
        return static::getContainer()
            ->get('security.authorization_checker')
            ->isGranted('ITEM_SEE', $itemId);
    }

    private function createPortal(): Portal
    {
        $authSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);

        return PortalFactory::createOne(['authSources' => [$authSource]]);
    }

    /** @return array{0: Account, 1: string} account plus its plain password */
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

    /** @return array{0: Room, 1: User} */
    private function createRoomWithPerson(Portal $portal, string $username): array
    {
        $room = RoomFactory::createOne([
            'type' => 'project',
            'contextId' => $portal->getId(),
            'portal' => $portal,
        ]);
        [$account] = $this->createAccount($portal, $username);

        return [$room, RoomUserFactory::createOne(['account' => $account, 'room' => $room])];
    }
}
