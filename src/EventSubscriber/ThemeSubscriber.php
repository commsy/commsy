<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

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
    public function __construct(private ThemeRepositoryInterface $themeRepository, private SettableThemeContext $themeContext, private ParameterBagInterface $parameterBag, private RoomService $roomService)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
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
