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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Schloss 2 — Phase 0 characterization of what the per-request legacy
 * bootstrap writes into {@see cs_environment} for the *context/portal*
 * seam (getCurrentContextID / getCurrentContextItem /
 * getCurrentPortalItem / getCurrentPortalID), driven through the real
 * {@see LegacySubscriber::setupContext()} via a real ControllerEvent.
 *
 * Safety net for the planned CurrentContextResolver flow-inversion:
 * every assertion here must stay green when setupContext() is rebuilt
 * to source context from RequestContext/Doctrine instead of the legacy
 * item-manager dispatch. Robust integer/itemId invariants are pinned,
 * not brittle internal predicate names (Schloss 1 lesson).
 */
#[WithStory(AccountStory::class)]
final class LegacySubscriberCurrentContextCharacterizationTest extends KernelTestCase
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

    public function testRoomContextResolvesRoomAndItsPortal(): void
    {
        $portalId = $this->account->getPortal()?->getId();
        self::assertNotNull($portalId);

        $room = $this->createRoom();
        RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $room,
            'status' => 2,
        ]);

        $this->dispatch($this->account, ['roomId' => $room->getItemId()]);

        self::assertSame($room->getItemId(), $this->legacyEnvironment->getCurrentContextID());
        self::assertSame(
            $room->getItemId(),
            $this->legacyEnvironment->getCurrentContextItem()->getItemID(),
        );

        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        self::assertNotNull($portalItem);
        self::assertSame($portalId, $portalItem->getItemID());
        self::assertSame($portalId, $this->legacyEnvironment->getCurrentPortalID());
    }

    public function testPortalContextResolvesThePortalAsContextAndPortal(): void
    {
        $portalId = $this->account->getPortal()?->getId();
        self::assertNotNull($portalId);

        $this->dispatch($this->account, ['portalId' => $portalId]);

        self::assertSame($portalId, $this->legacyEnvironment->getCurrentContextID());
        self::assertSame(
            $portalId,
            $this->legacyEnvironment->getCurrentContextItem()->getItemID(),
        );
        self::assertSame(
            $portalId,
            $this->legacyEnvironment->getCurrentPortalItem()?->getItemID(),
        );
        self::assertSame($portalId, $this->legacyEnvironment->getCurrentPortalID());
    }

    public function testNoContextAttributeFallsBackToAccountPortal(): void
    {
        $portalId = $this->account->getPortal()?->getId();
        self::assertNotNull($portalId);

        // setupContext(): no roomId/portalId/fileId => account portal.
        $this->dispatch($this->account, []);

        self::assertSame($portalId, $this->legacyEnvironment->getCurrentContextID());
        self::assertSame(
            $portalId,
            $this->legacyEnvironment->getCurrentPortalItem()?->getItemID(),
        );
    }

    public function testGuestWithoutContextResolvesToServerAndNoPortal(): void
    {
        // Guest + no attribute: setupContext() never calls
        // setCurrentContextID() (the account-null branch). The context id
        // therefore stays at the LegacyEnvironment ctor default — 99, the
        // server context (guessContextId() fallback). That is the stable
        // invariant pinned here.
        //
        // NOTE: the portal outcome is deliberately NOT asserted. Legacy
        // hard-codes the server id as 99 (getServerID()), and
        // getCurrentContextItem() does a Doctrine Portal lookup by that
        // id before the server path — so when the test DB happens to have
        // a Portal with id 99 the "server" context collides with a real
        // portal and getCurrentPortalItem() returns that PortalProxy
        // instead of null. Environment-dependent, not a faithful
        // invariant; the CurrentContextResolver design must be aware of
        // this 99/portal-id collision.
        $this->dispatch(null, []);

        self::assertSame(99, $this->legacyEnvironment->getCurrentContextID());
    }

    // ------------------------------------------------------------------
    // helpers — same faithful harness as the Schloss 1 Phase 0 test:
    // prime the token, push the request onto the RequestStack (as
    // HttpKernel does before the CONTROLLER event), fire onKernelController.
    // ------------------------------------------------------------------

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

        self::getContainer()->get(RequestStack::class)->push($request);

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
}
