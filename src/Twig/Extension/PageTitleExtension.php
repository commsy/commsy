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

namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use cs_environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageTitleExtension extends AbstractExtension
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly RoomService $roomService,
        private readonly TranslatorInterface $translator
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pageTitle', $this->pageTitle(...), ['is_safe' => ['html']]),
            new TwigFunction('shortPageTitle', $this->shortPageTitle(...), ['is_safe' => ['html']]),
        ];
    }

    public function pageTitle($roomId): string
    {
        $pageTitleElements = [];

        // room title
        $room = $this->roomService->getRoomItem(intval($roomId));
        if ($room) {
            if ($room->isPrivateRoom()) {
                $pageTitleElements[] = $this->translator->trans('dashboard', [], 'menu');
            } else {
                $pageTitleElements[] = $room->getTitle();
            }
        }

        // portal name
        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portal) {
            $pageTitleElements[] = $portal->getTitle();
        }

        if (!empty($pageTitleElements)) {
            $pageTitle = implode(' - ', $pageTitleElements);

            return htmlentities(
                $pageTitle,
                ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
                'UTF-8',
                false
            );
        }

        return 'CommSy';
    }

    public function shortPageTitle(): string
    {
        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portal) {
            return htmlentities(
                $portal->getTitle(),
                ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
                'UTF-8',
                false
            );
        }

        return 'CommSy';
    }
}
