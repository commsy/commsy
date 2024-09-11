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

namespace App\Cron\Tasks;

use App\Helper\PortalHelper;
use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_room_item;
use DateTimeImmutable;

class CronDeleteFiles implements CronTaskInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly PortalRepository $portalRepository,
        private readonly PortalHelper $portalHelper
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $fileManager = $this->legacyEnvironment->getFileManager();

        // server
        $fileManager->deleteUnneededFiles(99, 99);

        // portals
        $portals = $this->portalRepository->findAll();
        foreach ($portals as $portal) {
            $fileManager->deleteUnneededFiles($portal->getId(), $portal->getId());

            // rooms
            $activeRooms = $this->portalHelper->getActiveRoomsInPortal($portal);
            foreach ($activeRooms as $activeRoom) {
                /* @var cs_room_item $activeRoom */
                $fileManager->deleteUnneededFiles($activeRoom->getItemID(), $portal->getId());
            }
        }
    }

    public function getSummary(): string
    {
        return 'Delete old server files';
    }
}
