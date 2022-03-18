<?php

namespace App\Controller\Api;

use App\Entity\Portal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetPortalTou extends AbstractController
{
    public function __invoke(Portal $data): array
    {
        $portal = $data;

        return [
            'de' => $portal->getTermsGerman(),
            'en' => $portal->getTermsEnglish(),
        ];
    }
}