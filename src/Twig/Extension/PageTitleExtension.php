<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 22.08.18
 * Time: 08:01
 */

namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageTitleExtension extends AbstractExtension
{
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;
    private $roomService;
    private $translator;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        RoomService $roomService,
        TranslatorInterface $translator
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->translator = $translator;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('pageTitle', [$this, 'pageTitle']),
            new TwigFunction('shortPageTitle', [$this, 'shortPageTitle']),
        ];
    }

    public function pageTitle($roomId)
    {
        $pageTitleElements = [];

        // room title
        $room = $this->roomService->getRoomItem($roomId);
        if (!$room) {
            $this->legacyEnvironment->toggleArchiveMode();
            $room = $this->roomService->getRoomItem($roomId);
            $this->legacyEnvironment->toggleArchiveMode();
        }

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

        return (!empty($pageTitleElements)) ? implode(' - ', $pageTitleElements) : 'CommSy';
    }

    public function shortPageTitle()
    {
        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portal) {
            return $portal->getTitle();
        }

        return 'CommSy';
    }

    private function roomTitle($roomId)
    {

    }
}