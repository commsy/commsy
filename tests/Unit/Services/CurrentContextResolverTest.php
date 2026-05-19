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

namespace Tests\Unit\Services;

use App\Entity\Account;
use App\Entity\Portal;
use App\Entity\Room;
use App\Proxy\PortalProxy;
use App\Services\CurrentContextResolver;
use App\Services\CurrentUserResolver;
use App\Services\LegacyEnvironment;
use App\Utils\RequestContext;
use cs_context_item;
use cs_environment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Mirrors the Schloss 2 Phase 0 characterization for the Doctrine path.
 *
 * getContextId() reads the legacy attribute precedence directly
 * (roomId, portalId, fileId) and deliberately ignores the generic
 * `context` attribute (legacy setupContext never honoured it; the
 * `/portal/{context}/enter` route would otherwise feed a non-id into
 * cs_environment). getRoom()/getPortal() still delegate to
 * RequestContext.
 */
final class CurrentContextResolverTest extends TestCase
{
    private RequestContext&MockObject $requestContext;
    private RequestStack&MockObject $requestStack;
    private CurrentUserResolver&MockObject $currentUserResolver;
    private LegacyEnvironment&MockObject $legacyEnvironment;
    private CurrentContextResolver $resolver;

    protected function setUp(): void
    {
        $this->requestContext = $this->createMock(RequestContext::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->currentUserResolver = $this->createMock(CurrentUserResolver::class);
        $this->legacyEnvironment = $this->createMock(LegacyEnvironment::class);
        $this->resolver = new CurrentContextResolver(
            $this->requestContext,
            $this->requestStack,
            $this->currentUserResolver,
            $this->legacyEnvironment,
        );
    }

    public function testRoomContextResolvesIdRoomAndPortal(): void
    {
        $request = new Request();
        $request->attributes->set('roomId', 42);
        $room = $this->createMock(Room::class);
        $portal = $this->createMock(Portal::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->method('fetchRoom')->with($request)->willReturn($room);
        $this->requestContext->method('fetchPortal')->with($request)->willReturn($portal);

        self::assertSame(42, $this->resolver->getContextId());
        self::assertSame($room, $this->resolver->getRoom());
        self::assertSame($portal, $this->resolver->getPortal());
    }

    public function testPortalContextHasNoRoom(): void
    {
        $request = new Request();
        $request->attributes->set('portalId', 7);
        $portal = $this->createMock(Portal::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->method('fetchRoom')->willReturn(null);
        $this->requestContext->method('fetchPortal')->willReturn($portal);

        self::assertSame(7, $this->resolver->getContextId());
        self::assertNull($this->resolver->getRoom());
        self::assertSame($portal, $this->resolver->getPortal());
    }

    public function testGenericContextAttributeIsIgnored(): void
    {
        // Regression guard: `/portal/{context}/enter` binds {context}.
        // Legacy setupContext ignored it; so must we (else cs_environment
        // gets a non-resolvable id -> E_USER_ERROR).
        $request = new Request();
        $request->attributes->set('context', '1');
        // A normal portal id — NOT 99 (that is the legacy server-context
        // sentinel, getServerID(); reusing it as a portal id would
        // conflate the two, see the Phase 0 collision note).
        $portal = $this->createMock(Portal::class);
        $portal->method('getId')->willReturn(55);
        $account = $this->createMock(Account::class);
        $account->method('getPortal')->willReturn($portal);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->method('fetchRoom')->willReturn(null);
        $this->requestContext->method('fetchPortal')->willReturn(null);
        $this->currentUserResolver->method('getAccount')->willReturn($account);

        // not 1 (the `context` value) — falls back to the account portal
        self::assertSame(55, $this->resolver->getContextId());
    }

    public function testFallsBackToAccountPortalWhenRequestHasNoContext(): void
    {
        $request = new Request();
        $portal = $this->createMock(Portal::class);
        $portal->method('getId')->willReturn(9);
        $account = $this->createMock(Account::class);
        $account->method('getPortal')->willReturn($portal);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->method('fetchRoom')->willReturn(null);
        $this->requestContext->method('fetchPortal')->willReturn(null);
        $this->currentUserResolver->method('getAccount')->willReturn($account);

        self::assertSame(9, $this->resolver->getContextId());
        self::assertNull($this->resolver->getRoom());
        self::assertSame($portal, $this->resolver->getPortal());
    }

    public function testNoRequestUsesAccountPortalWithoutTouchingRequestContext(): void
    {
        $portal = $this->createMock(Portal::class);
        $portal->method('getId')->willReturn(5);
        $account = $this->createMock(Account::class);
        $account->method('getPortal')->willReturn($portal);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $this->requestContext->expects($this->never())->method('fetchContextId');
        $this->requestContext->expects($this->never())->method('fetchRoom');
        $this->requestContext->expects($this->never())->method('fetchPortal');
        $this->currentUserResolver->method('getAccount')->willReturn($account);

        self::assertSame(5, $this->resolver->getContextId());
        self::assertNull($this->resolver->getRoom());
        self::assertSame($portal, $this->resolver->getPortal());
    }

    public function testNoRequestNoAccountYieldsAllNull(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $this->currentUserResolver->method('getAccount')->willReturn(null);

        self::assertNull($this->resolver->getContextId());
        self::assertNull($this->resolver->getRoom());
        self::assertNull($this->resolver->getPortal());
    }

    public function testEachResolutionIsMemoisedAndResetClearsIt(): void
    {
        $request = new Request();
        $room = $this->createMock(Room::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->expects($this->exactly(2))
            ->method('fetchRoom')
            ->with($request)
            ->willReturn($room);

        // memoised within a request: two calls, one underlying fetch
        self::assertSame($room, $this->resolver->getRoom());
        self::assertSame($room, $this->resolver->getRoom());

        // reset() clears the memo -> next call resolves again (2nd fetch)
        $this->resolver->reset();
        self::assertSame($room, $this->resolver->getRoom());
    }

    public function testGetContextItemDelegatesToLegacyEnvironmentSeam(): void
    {
        $contextItem = $this->createMock(cs_context_item::class);
        $legacy = $this->createMock(cs_environment::class);
        $legacy->expects($this->once())
            ->method('getCurrentContextItem')
            ->willReturn($contextItem);
        $this->legacyEnvironment->method('getEnvironment')->willReturn($legacy);

        self::assertSame($contextItem, $this->resolver->getContextItem());
    }

    public function testGetPortalItemDelegatesToLegacyEnvironmentSeam(): void
    {
        $portalItem = $this->createMock(PortalProxy::class);
        $legacy = $this->createMock(cs_environment::class);
        $legacy->expects($this->once())
            ->method('getCurrentPortalItem')
            ->willReturn($portalItem);
        $this->legacyEnvironment->method('getEnvironment')->willReturn($legacy);

        self::assertSame($portalItem, $this->resolver->getPortalItem());
    }

    public function testGetPortalItemPropagatesNull(): void
    {
        $legacy = $this->createMock(cs_environment::class);
        $legacy->method('getCurrentPortalItem')->willReturn(null);
        $this->legacyEnvironment->method('getEnvironment')->willReturn($legacy);

        self::assertNull($this->resolver->getPortalItem());
    }
}
