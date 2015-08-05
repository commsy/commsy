<?php

namespace Commsy\LegacyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class DiscService
{
    private $legacyEnvironment;
    private $discManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->discManager = $this->legacyEnvironment->getEnvironment()->getDiscManager();
    }

    public function copyFile ($source_file, $dest_filename, $delete_source)
    {
        $this->discManager->_file_path_basic = '/var/www/commsy/app/../files/';
        return $this->discManager->copyFile($source_file, $dest_filename, $delete_source);;
    }
}