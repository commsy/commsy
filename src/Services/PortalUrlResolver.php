<?php

declare(strict_types=1);

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

namespace App\Services;

use App\Entity\Portal;
use App\Proxy\PortalProxy;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The address a portal advertises in outgoing mail.
 *
 * Routing can only ever name one host per installation (the current request's, or
 * DEFAULT_URI outside a request), so portals fronted by a vanity host of their own
 * cannot be linked to via url(). Such a portal stores its front door in base_url and
 * this resolver hands it out; portals without one fall back to the routed entry URL,
 * which is portal-specific by path and works as a bookmark either way.
 *
 * For OUTGOING links only. In-app redirects (authenticators, breadcrumbs) must keep
 * using the router: sending a running session to a foreign origin would break it.
 */
readonly class PortalUrlResolver
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function resolve(Portal|PortalProxy $portal): string
    {
        return $portal->getBaseUrl() ?? $this->routedUrl($portal->getId());
    }

    private function routedUrl(int $portalId): string
    {
        return $this->urlGenerator->generate('app_helper_portalenter', [
            'context' => $portalId,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
