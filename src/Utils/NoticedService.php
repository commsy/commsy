<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_noticed_manager;

class NoticedService
{
    /**
     * @var cs_noticed_manager
     */
    private cs_noticed_manager $noticedManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->noticedManager = $legacyEnvironment->getEnvironment()->getNoticedManager();
    }

    public function getLatestNoticedByIDArrayAndUser($id_array, $user_id)
    {
        return $this->noticedManager->getLatestNoticedByIDArrayAndUser($id_array, $user_id);
    }
}