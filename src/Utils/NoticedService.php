<?php

namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;

class NoticedService
{
    private $legacyEnvironment;

    private $noticedManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->noticedManager = $this->legacyEnvironment->getEnvironment()->getNoticedManager();
    }

    public function getLatestNoticedByIDArrayAndUser($id_array, $user_id)
    {
        return $this->noticedManager->getLatestNoticedByIDArrayAndUser($id_array, $user_id);
    }
}