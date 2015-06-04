<?php

namespace Commsy\LegacyBundle\Services;

class LegacyEnvironment
{
    private $environment;

    /**
     * Path to the legacy application "commsy_legacy.php front controller"
     * @var String
     */
    private $legacyAppPath;

    /**
     * @param String             $legacyAppPath
     */
    public function __construct($legacyAppPath)
    {
        $this->legacyAppPath = $legacyAppPath;
    }

    public function getEnvironment()
    {
        if ($this->environment === null) {
            $legacyDir = dirname($this->legacyAppPath) . '/../legacy';
            chdir($legacyDir);

            global $cs_color;
            global $db;
            include_once('etc/cs_constants.php');
            include_once('etc/cs_config.php');
            include_once('functions/misc_functions.php');

            include_once('classes/cs_environment.php');
            global $environment;
            $environment = new \cs_environment();
            $this->environment = $environment;
        }

        return $this->environment;
    }
}