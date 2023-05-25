<?php

namespace App\WOPI\REST;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class WOPISrc
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function getUrl(string $wopiFileId): string
    {
        $url = $this->router->generate('_api_wopi/files/{fileId}_get', [
            'fileId' => $wopiFileId,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // dev env
        return str_replace('https://localhost', 'http://caddy', $url);
    }
}
