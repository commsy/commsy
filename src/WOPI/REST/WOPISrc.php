<?php

namespace App\WOPI\REST;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class WOPISrc
{
    public function __construct(
        private RouterInterface $router,
        #[Autowire('%commsy.settings.internal_base_url%')]
        private string $internalBaseUrl
    ) {
    }

    public function getUrl(string $wopiFileId): string
    {
        $url = $this->router->generate('_api_wopi/files/{fileId}_get', [
            'fileId' => $wopiFileId,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // The office server sits beside us and cannot resolve the public host,
        // so the origin is swapped for the one that works inside the network.
        return str_replace('https://localhost', $this->internalBaseUrl, $url);
    }
}
