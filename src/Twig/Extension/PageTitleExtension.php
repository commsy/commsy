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
use cs_environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageTitleExtension extends AbstractExtension
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var RoomService
     */
    private RoomService $roomService;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @param LegacyEnvironment $legacyEnvironment
     * @param RoomService $roomService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        RoomService $roomService,
        TranslatorInterface $translator
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->translator = $translator;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pageTitle', [$this, 'pageTitle'], ['is_safe' => ['html']]),
            new TwigFunction('shortPageTitle', [$this, 'shortPageTitle'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param $roomId
     * @return string
     */
    public function pageTitle($roomId): string
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

    /**
     * @return string
     */
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