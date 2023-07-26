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

namespace App\Security;

use App\Utils\RequestContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private RequestContext $requestContext,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        // Do not respond with a redirect if this is a xml request. This way we can handle
        // 403 responses in js .ajax() calls.
        if ($request->isXmlHttpRequest()) {
            return;
        }

        $portal = $this->requestContext->fetchPortal($request);
        $contextId = $this->requestContext->fetchContextId($request);

        if ($portal && $contextId) {
            return new RedirectResponse($this->urlGenerator->generate('app_roomall_detail', [
                'portalId' => $portal->getId(),
                'itemId' => $contextId,
            ]));
        }
    }
}
