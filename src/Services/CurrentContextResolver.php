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

use App\Entity\Portal;
use App\Entity\Room;
use App\Utils\RequestContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The Doctrine-native answer to "which context/portal is this request
 * in" — the Schloss 2 counterpart of {@see CurrentUserResolver}.
 *
 * Context resolution mirrors {@see \App\EventSubscriber\LegacySubscriber}'s
 * setupContext(): request attributes win (via {@see RequestContext},
 * which already covers context/roomId/portalId/fileId and the
 * room-/item-to-portal derivation), and when none is present we fall
 * back to the logged-in account's portal — exactly the legacy behaviour
 * the Schloss 2 Phase 0 characterization pinned.
 *
 * Implements {@see ResetInterface} from the start (the Schloss 1
 * lesson): this is a shared singleton, so the per-request memo MUST be
 * cleared between requests via the autoconfigured `kernel.reset` tag —
 * otherwise the first request's context leaks into later requests in
 * any multi-request-per-process runtime (test client, worker, messenger).
 *
 * Collision note (pinned in Phase 0): legacy hard-codes the server id
 * as 99 and resolves it via a Portal lookup before the server path, so
 * a portal with id 99 collides with "server context". This resolver
 * delegates that lookup to RequestContext, inheriting the same (quirky
 * but consistent) behaviour deliberately.
 */
final class CurrentContextResolver implements ResetInterface
{
    private bool $contextIdResolved = false;
    private ?int $contextIdCache = null;
    private bool $roomResolved = false;
    private ?Room $roomCache = null;
    private bool $portalResolved = false;
    private ?Portal $portalCache = null;

    public function __construct(
        private readonly RequestContext $requestContext,
        private readonly RequestStack $requestStack,
        private readonly CurrentUserResolver $currentUserResolver,
    ) {
    }

    /**
     * Current context id: request attributes first, then the logged-in
     * account's portal — 1:1 with LegacySubscriber::setupContext().
     */
    public function getContextId(): ?int
    {
        if ($this->contextIdResolved) {
            return $this->contextIdCache;
        }

        $this->contextIdResolved = true;

        $request = $this->requestStack->getCurrentRequest();
        $contextId = null !== $request
            ? $this->requestContext->fetchContextId($request)
            : null;

        return $this->contextIdCache = $contextId
            ?? $this->currentUserResolver->getAccount()?->getPortal()?->getId();
    }

    /**
     * The Room entity for the current context, or null when the context
     * is a portal/server or no room can be resolved.
     */
    public function getRoom(): ?Room
    {
        if ($this->roomResolved) {
            return $this->roomCache;
        }

        $this->roomResolved = true;

        $request = $this->requestStack->getCurrentRequest();

        return $this->roomCache = null !== $request
            ? $this->requestContext->fetchRoom($request)
            : null;
    }

    /**
     * The current portal entity (portal context, the room's portal, or
     * the item's portal — handled by RequestContext), falling back to
     * the logged-in account's portal when no request context applies.
     */
    public function getPortal(): ?Portal
    {
        if ($this->portalResolved) {
            return $this->portalCache;
        }

        $this->portalResolved = true;

        $request = $this->requestStack->getCurrentRequest();
        $portal = null !== $request
            ? $this->requestContext->fetchPortal($request)
            : null;

        return $this->portalCache = $portal
            ?? $this->currentUserResolver->getAccount()?->getPortal();
    }

    /**
     * Clears the per-request memo. Invoked between requests via the
     * `kernel.reset` tag — without it the singleton would serve a stale
     * context across requests in multi-request runtimes.
     */
    public function reset(): void
    {
        $this->contextIdResolved = false;
        $this->contextIdCache = null;
        $this->roomResolved = false;
        $this->roomCache = null;
        $this->portalResolved = false;
        $this->portalCache = null;
    }
}
