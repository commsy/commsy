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

namespace App\Services;

use App\Entity\Account;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\RequestContext;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The Doctrine-native answer to "who is acting in the current request,
 * and what is their User row in the current context".
 *
 * This is the Security→Doctrine direction of the same seam that
 * {@see \App\Security\Permission\Legacy\LegacyPermissionBridge::userFromLegacy()}
 * crosses from the legacy side: both end up calling
 * {@see UserRepository::findByAccountIdAndContext()}.
 *
 * Context resolution mirrors {@see \App\EventSubscriber\LegacySubscriber}'s
 * setupContext(): the request attributes win (handled by
 * {@see RequestContext::fetchContextId()}, which already covers
 * context/roomId/portalId/fileId), and when none is present we fall back
 * to the logged-in account's portal — exactly the legacy behaviour the
 * Phase 0 characterization pinned.
 *
 * Not `readonly`: the per-request user lookup is memoised. The resolver
 * is request-scoped, so the cache lives exactly as long as it should.
 */
final class CurrentUserResolver
{
    private ?User $userCache = null;
    private bool $userResolved = false;

    public function __construct(
        private readonly Security $security,
        private readonly RequestContext $requestContext,
        private readonly RequestStack $requestStack,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * The logged-in principal, or null for a guest request.
     */
    public function getAccount(): ?Account
    {
        $account = $this->security->getUser();

        return $account instanceof Account ? $account : null;
    }

    public function isGuest(): bool
    {
        return null === $this->getAccount();
    }

    /**
     * The User row for the current account in the current context, or
     * null when the request is a guest request, no context can be
     * resolved, or the account has no (non-deleted) membership there.
     *
     * The "account present but no User row" case returns null WITHOUT
     * being a guest — callers must distinguish via {@see isGuest()}.
     */
    public function getUser(): ?User
    {
        if ($this->userResolved) {
            return $this->userCache;
        }

        $this->userResolved = true;

        $account = $this->getAccount();
        if (null === $account) {
            return $this->userCache = null;
        }

        $contextId = $this->resolveContextId($account);
        if (null === $contextId) {
            return $this->userCache = null;
        }

        return $this->userCache = $this->userRepository
            ->findByAccountIdAndContext($account->getId(), $contextId);
    }

    /**
     * Request attributes first (RequestContext already understands
     * context/roomId/portalId/fileId), then the account portal fallback —
     * 1:1 with LegacySubscriber::setupContext().
     */
    private function resolveContextId(Account $account): ?int
    {
        $request = $this->requestStack->getCurrentRequest();
        $contextId = null !== $request
            ? $this->requestContext->fetchContextId($request)
            : null;

        return $contextId ?? $account->getPortal()?->getId();
    }
}
