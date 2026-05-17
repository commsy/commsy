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

namespace Tests\Integration\EventSubscriber;

use App\Entity\Account;
use App\Entity\Room;
use App\EventSubscriber\LegacySubscriber;
use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Phase 0 — characterization of {@see LegacySubscriber::onKernelController}.
 *
 * Pins exactly what the per-request legacy bootstrap writes into
 * {@see cs_environment} today: the current context id and the
 * `getCurrentUserItem()` (item id, status, user id) for the relevant
 * (account, context) shapes.
 *
 * This is the safety net for the planned flow-inversion (Schritt 3):
 * when `setupUser()` is rebuilt to source its data from the new
 * `CurrentUserResolver` instead of the legacy `cs_user_manager` query,
 * every assertion here must stay green — that is the definition of
 * "no behavioural drift".
 *
 * The real subscriber is driven through a real `ControllerEvent` (not a
 * hand-primed env like {@see \Tests\Integration\Security\Voter\Concerns\BootsVoter}),
 * so `setupContext()` + `setupUser()` are exercised end to end.
 */
#[WithStory(AccountStory::class)]
final class LegacySubscriberCurrentUserCharacterizationTest extends KernelTestCase
{
    private LegacySubscriber $subscriber;
    private cs_environment $legacyEnvironment;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->subscriber = self::getContainer()->get(LegacySubscriber::class);
        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->account = AccountStory::get('account');
    }

    // ------------------------------------------------------------------
    // Account + room context — the dominant case (status 2 member)
    // ------------------------------------------------------------------

    public function testLoggedInMemberInRoomContextGetsRoomMembershipAsCurrentUser(): void
    {
        $room = $this->createRoom();
        $member = $this->createMember($this->account, $room, status: 2);

        $this->dispatch($this->account, ['roomId' => $room->getItemId()]);

        self::assertSame($room->getItemId(), $this->legacyEnvironment->getCurrentContextID());

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        self::assertSame($member->getItemId(), $currentUser->getItemID());
        self::assertSame(2, $currentUser->getStatus());
    }

    // ------------------------------------------------------------------
    // Account, no room attribute — falls back to the account's portal
    // (this is what the plan called the "portal context" / server-page
    //  case: setupContext() uses account->getPortal()->getId())
    // ------------------------------------------------------------------

    public function testLoggedInUserWithoutRoomAttributeFallsBackToPortalContext(): void
    {
        $portalId = $this->account->getPortal()?->getId();
        self::assertNotNull($portalId, 'AccountStory account must have a portal');

        $this->dispatch($this->account, []);

        self::assertSame(
            $portalId,
            $this->legacyEnvironment->getCurrentContextID(),
            'no room/portal/file attribute => context is the account portal',
        );

        // Account creation auto-provisions a portal-level membership, so a
        // current user IS resolved here. We pin the structural invariant
        // (a real, non-empty user item) rather than a brittle status const.
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        self::assertGreaterThan(
            0,
            $currentUser->getItemID(),
            'portal-level membership is adopted as currentUserItem',
        );
    }

    // ------------------------------------------------------------------
    // Guest — no security token
    // ------------------------------------------------------------------

    public function testGuestRequestYieldsSyntheticGuestUserItem(): void
    {
        $room = $this->createRoom();

        $this->dispatch(null, ['roomId' => $room->getItemId()]);

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        self::assertSame(0, $currentUser->getStatus());
        self::assertSame('guest', $currentUser->getUserID());
    }

    // ------------------------------------------------------------------
    // Status variants in a room context — setupUser() does NOT filter on
    // status, so the membership is adopted whatever its status.
    // ------------------------------------------------------------------

    public function testReadonlyMemberStatusIsPreserved(): void
    {
        $room = $this->createRoom();
        $member = $this->createMember($this->account, $room, status: 4);

        $this->dispatch($this->account, ['roomId' => $room->getItemId()]);

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        self::assertSame($member->getItemId(), $currentUser->getItemID());
        self::assertSame(4, $currentUser->getStatus());
    }

    public function testRequestedMembershipStatusIsPreserved(): void
    {
        $room = $this->createRoom();
        $member = $this->createMember($this->account, $room, status: 1);

        $this->dispatch($this->account, ['roomId' => $room->getItemId()]);

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        self::assertSame($member->getItemId(), $currentUser->getItemID());
        self::assertSame(1, $currentUser->getStatus());
    }

    // ------------------------------------------------------------------
    // Multi-portal: account in portal A, room in a different portal where
    // the account has no membership. setupUser() finds 0 matches and the
    // "cannot throw" branch leaves currentUserItem at its empty default.
    // ------------------------------------------------------------------

    public function testForeignPortalRoomWithoutMembershipLeavesEmptyCurrentUser(): void
    {
        $foreignPortal = PortalFactory::createOne([
            'authSources' => [AuthSourceLocalFactory::createOne()],
        ]);
        $foreignRoom = RoomFactory::new()->project()->create([
            'contextId' => $foreignPortal->getId(),
            'portal' => $foreignPortal,
        ]);

        $this->dispatch($this->account, ['roomId' => $foreignRoom->getItemId()]);

        self::assertSame(
            $foreignRoom->getItemId(),
            $this->legacyEnvironment->getCurrentContextID(),
        );

        // No unique (account, context) user row => setCurrentUser() is never
        // called => the lazily constructed empty cs_user_item remains.
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        self::assertSame(
            0,
            $currentUser->getItemID(),
            'no membership in foreign portal room => empty currentUserItem',
        );
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------

    /**
     * Drives the real subscriber exactly like the kernel would on a main
     * request: prime the security token (or leave it empty for a guest),
     * then fire onKernelController with a ControllerEvent carrying the
     * given request attributes.
     */
    private function dispatch(?Account $account, array $attributes): void
    {
        $tokenStorage = self::getContainer()->get('security.token_storage');
        if ($account instanceof Account) {
            $tokenStorage->setToken(
                new UsernamePasswordToken($account, 'main', $account->getRoles()),
            );
        } else {
            $tokenStorage->setToken(null);
        }

        $request = new Request();
        foreach ($attributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        $event = new ControllerEvent(
            self::$kernel,
            static fn (): null => null,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $this->subscriber->onKernelController($event);
    }

    private function createRoom(): Room
    {
        return RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
    }

    private function createMember(Account $account, Room $room, int $status): \App\Entity\User
    {
        return RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
            'status' => $status,
        ]);
    }
}
