<?php

namespace CommsyBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Liip\ThemeBundle\ActiveTheme;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;

use Psr\Log\LoggerInterface;

class CommsyRequestListener
{
    private $roomService;

    private $activeTheme;

    private $themeArray;

    private $preDefinedTheme;

    private $logger;

    private $legacyEnvironment;

    public function __construct(RoomService $roomService, ActiveTheme $activeTheme, $themeArray, $preDefinedTheme, LoggerInterface $logger)
    {
        $this->roomService = $roomService;
        $this->activeTheme = $activeTheme;
        $this->themeArray = $themeArray;
        $this->preDefinedTheme = $preDefinedTheme;
        $this->logger = $logger;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // get the current room theme and set it as active for LiipThemeBundle
        $roomItem = $this->roomService->getCurrentRoomItem();
        $preDefinedTheme = $this->preDefinedTheme;

        // is theme pre-defined in config?

        if($preDefinedTheme){
            if (in_array($preDefinedTheme, $this->themeArray)) {
                $this->activeTheme->setName($preDefinedTheme);
            }else{
                $this->logger->error('The string for a pre-defined theme is not valid.');
            }
        }elseif($roomItem) {
            $colorArray = $roomItem->getColorArray();
            if ($colorArray) {
                if (isset($colorArray['schema'])) {
                    $schema = $colorArray['schema'];

                    if (in_array($schema, $this->themeArray)) {
                        $this->activeTheme->setName($schema);
                    }
                }
            }
        }
    }
}