<?php

namespace App\EventSubscriber;

use App\Utils\RoomService;
use Sylius\Bundle\ThemeBundle\Context\SettableThemeContext;
use Sylius\Bundle\ThemeBundle\Repository\ThemeRepositoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ThemeSubscriber implements EventSubscriberInterface
{
    /**
     * @var ThemeRepositoryInterface
     */
    private ThemeRepositoryInterface $themeRepository;

    /**
     * @var SettableThemeContext
     */
    private SettableThemeContext $themeContext;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * @var RoomService
     */
    private RoomService $roomService;

    public function __construct(
        ThemeRepositoryInterface $themeRepository,
        SettableThemeContext $themeContext,
        ParameterBagInterface $parameterBag,
        RoomService $roomService
    ) {
        $this->themeRepository = $themeRepository;
        $this->themeContext = $themeContext;
        $this->parameterBag = $parameterBag;
        $this->roomService = $roomService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        // Check if a specific theme is forced in the env vars
        $forceTheme = $this->parameterBag->get('commsy.force_theme');
        if ($forceTheme) {
            $theme = $this->themeRepository->findOneByName("commsy/{$forceTheme}");
            if ($theme) {
                $this->themeContext->setTheme($theme);
                return;
            }
        }

        // Decide based on room configuration
        $room = $this->roomService->getCurrentRoomItem();
        if ($room) {
            $colorArray = $room->getColorArray();
            if ($colorArray && isset($colorArray['schema'])) {
                $schema = $colorArray['schema'];

                $theme = $this->themeRepository->findOneByName("commsy/{$schema}");
                if ($theme) {
                    $this->themeContext->setTheme($theme);
                }
            }
        }
    }
}