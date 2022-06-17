<?php

namespace App\Security;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Utils\RequestContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    /**
     * @var RequestContext
     */
    private RequestContext $requestContext;

    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        RequestContext $requestContext,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->requestContext = $requestContext;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
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