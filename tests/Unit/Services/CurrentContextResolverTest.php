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
use App\Services\CurrentContextResolver;
use App\Services\CurrentUserResolver;
use App\Utils\RequestContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Mirrors the Schloss 2 Phase 0 characterization for the Doctrine path:
 * request-attribute context vs account-portal fallback, room vs portal
 * context, request-scoped memoisation, and reset().
 */
final class CurrentContextResolverTest extends TestCase
{
    private RequestContext&MockObject $requestContext;
    private RequestStack&MockObject $requestStack;
    private CurrentUserResolver&MockObject $currentUserResolver;
    private CurrentContextResolver $resolver;

    protected function setUp(): void
    {
        $this->requestContext = $this->createMock(RequestContext::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->currentUserResolver = $this->createMock(CurrentUserResolver::class);
        $this->resolver = new CurrentContextResolver(
            $this->requestContext,
            $this->requestStack,
            $this->currentUserResolver,
        );
    }

    public function testRoomContextResolvesIdRoomAndPortal(): void
    {
        $request = $this->createMock(Request::class);
        $room = $this->createMock(Room::class);
        $portal = $this->createMock(Portal::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->method('fetchContextId')->with($request)->willReturn(42);
        $this->requestContext->method('fetchRoom')->with($request)->willReturn($room);
        $this->requestContext->method('fetchPortal')->with($request)->willReturn($portal);

        self::assertSame(42, $this->resolver->getContextId());
        self::assertSame($room, $this->resolver->getRoom());
        self::assertSame($portal, $this->resolver->getPortal());
    }

    public function testPortalContextHasNoRoom(): void
    {
        $request = $this->createMock(Request::class);
        $portal = $this->createMock(Portal::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->method('fetchContextId')->willReturn(7);
        $this->requestContext->method('fetchRoom')->willReturn(null);
        $this->requestContext->method('fetchPortal')->willReturn($portal);

        self::assertSame(7, $this->resolver->getContextId());
        self::assertNull($this->resolver->getRoom());
        self::assertSame($portal, $this->resolver->getPortal());
    }

    public function testFallsBackToAccountPortalWhenRequestHasNoContext(): void
    {
        $request = $this->createMock(Request::class);
        $portal = $this->createMock(Portal::class);
        $portal->method('getId')->willReturn(9);
        $account = $this->createMock(Account::class);
        $account->method('getPortal')->willReturn($portal);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->method('fetchContextId')->willReturn(null);
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
        $request = $this->createMock(Request::class);
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
}
