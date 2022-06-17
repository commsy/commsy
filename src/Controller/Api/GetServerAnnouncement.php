<?php

namespace App\Controller\Api;

use App\Entity\Portal;
use App\Entity\Server;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetServerAnnouncement extends AbstractController
{
    public function __invoke(Server $data): array
    {
        $server = $data;

        return [
            'enabled' => $server->hasAnnouncementEnabled(),
            'title' => $server->getAnnouncementTitle(),
            'severity' => $server->getAnnouncementSeverity(),
            'text' => $server->getAnnouncementText(),
        ];
    }
}