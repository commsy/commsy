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
use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\CurrentUserResolver;
use App\Utils\RequestContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Mirrors the Phase 0 characterization of LegacySubscriber, but for the
 * new Doctrine-native path: guest vs account, request-attribute context
 * vs account-portal fallback, the "account but no membership" branch,
 * and request-scoped memoisation.
 */
final class CurrentUserResolverTest extends TestCase
{
    private Security&MockObject $security;
    private RequestContext&MockObject $requestContext;
    private RequestStack&MockObject $requestStack;
    private UserRepository&MockObject $userRepository;
    private CurrentUserResolver $resolver;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->requestContext = $this->createMock(RequestContext::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->resolver = new CurrentUserResolver(
            $this->security,
            $this->requestContext,
            $this->requestStack,
            $this->userRepository,
        );
    }

    // ---- guest

    public function testGuestRequestHasNoAccountIsGuestAndNoUser(): void
    {
        $this->security->method('getUser')->willReturn(null);
        $this->userRepository->expects($this->never())
            ->method('findByAccountIdAndContext');

        self::assertNull($this->resolver->getAccount());
        self::assertTrue($this->resolver->isGuest());
        self::assertNull($this->resolver->getUser());
    }

    public function testNonAccountPrincipalIsTreatedAsGuest(): void
    {
        $this->security->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));

        self::assertNull($this->resolver->getAccount());
        self::assertTrue($this->resolver->isGuest());
    }

    // ---- account + context resolution

    public function testResolvesUserFromRequestContextWhenContextPresent(): void
    {
        $account = $this->account(id: 123, portalId: 7);
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);

        $this->security->method('getUser')->willReturn($account);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->method('fetchContextId')->with($request)->willReturn(42);
        $this->userRepository->expects($this->once())
            ->method('findByAccountIdAndContext')
            ->with(123, 42)
            ->willReturn($user);

        self::assertSame($account, $this->resolver->getAccount());
        self::assertFalse($this->resolver->isGuest());
        self::assertSame($user, $this->resolver->getUser());
    }

    public function testFallsBackToAccountPortalWhenRequestContextHasNoContext(): void
    {
        $account = $this->account(id: 123, portalId: 7);
        $request = $this->createMock(Request::class);

        $this->security->method('getUser')->willReturn($account);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->requestContext->method('fetchContextId')->willReturn(null);
        $this->userRepository->expects($this->once())
            ->method('findByAccountIdAndContext')
            ->with(123, 7)
            ->willReturn($this->createMock(User::class));

        $this->resolver->getUser();
    }

    public function testNoCurrentRequestUsesPortalFallbackWithoutTouchingRequestContext(): void
    {
        $account = $this->account(id: 5, portalId: 9);

        $this->security->method('getUser')->willReturn($account);
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $this->requestContext->expects($this->never())->method('fetchContextId');
        $this->userRepository->expects($this->once())
            ->method('findByAccountIdAndContext')
            ->with(5, 9)
            ->willReturn(null);

        self::assertNull($this->resolver->getUser());
    }

    public function testNoContextResolvableYieldsNullUserWithoutRepositoryCall(): void
    {
        $account = $this->account(id: 5, portalId: null);

        $this->security->method('getUser')->willReturn($account);
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $this->userRepository->expects($this->never())
            ->method('findByAccountIdAndContext');

        self::assertNull($this->resolver->getUser());
        self::assertFalse($this->resolver->isGuest(), 'account present => not a guest');
    }

    // ---- memoisation

    public function testUserLookupIsMemoisedEvenWhenResultIsNull(): void
    {
        $account = $this->account(id: 1, portalId: 2);

        $this->security->method('getUser')->willReturn($account);
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $this->userRepository->expects($this->once())
            ->method('findByAccountIdAndContext')
            ->with(1, 2)
            ->willReturn(null);

        self::assertNull($this->resolver->getUser());
        self::assertNull($this->resolver->getUser());
    }

    // ---- helpers

    private function account(int $id, ?int $portalId): Account&MockObject
    {
        $portal = null;
        if (null !== $portalId) {
            $portal = $this->createMock(Portal::class);
            $portal->method('getId')->willReturn($portalId);
        }

        $account = $this->createMock(Account::class);
        $account->method('getId')->willReturn($id);
        $account->method('getPortal')->willReturn($portal);

        return $account;
    }
}
