<?php

namespace App\Controller\Api;

use App\Entity\Portal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetPortalAnnouncement extends AbstractController
{
    public function __invoke(Portal $data): array
    {
        $portal = $data;

        return [
            'enabled' => $portal->hasAnnouncementEnabled(),
            'title' => $portal->getAnnouncementTitle(),
            'severity' => $portal->getAnnouncementSeverity(),
            'text' => $portal->getAnnouncementText(),
        ];
    }
}