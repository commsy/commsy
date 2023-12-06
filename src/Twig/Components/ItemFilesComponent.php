<?php

namespace App\Twig\Components;

use App\WOPI\Discovery\DiscoveryService;
use App\WOPI\Permission\WOPIPermission;
use cs_file_item;
use cs_item;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('item_files')]
final class ItemFilesComponent
{
    public cs_item $item;
    public bool $draft = false;
    public bool $simple = false;

    public function __construct(
        private readonly DiscoveryService $discoveryService,
    ) {
    }

    public function supportsOnlineOffice(cs_file_item $file): bool
    {
        $discovery = $this->discoveryService->getWOPIDiscovery();
        if (!$discovery) {
            return false;
        }

        $app = $this->discoveryService->findApp($discovery, $file->getExtension(), WOPIPermission::VIEW->value);
        if (!$app) {
            return false;
        }

        return true;
    }
}
