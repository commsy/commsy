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
use cs_room_item;

class CronRenewContinuousLinks implements CronTaskInterface
{
    private \cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private PortalRepository $portalRepository,
        private PortalHelper $portalHelper
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?\DateTimeImmutable $lastRun): void
    {
        $timeManager = $this->legacyEnvironment->getTimeManager();
        $timeManager->setTypeLimit('time');

        $portals = $this->portalRepository->findActivePortals();
        foreach ($portals as $portal) {
            if (!$portal->getShowTimePulses() || 1 != $portal->getStatus()) {
                continue;
            }

            $timeManager->setContextLimit($portal->getId());

            /** @var \cs_time_item $currentTime */
            $currentTime = $timeManager->getItemByName($this->portalHelper->getTitleOfCurrentTime($portal));

            if ($currentTime) {
                $unlinkedContinuousRoomList = $this->portalHelper->getContinuousRoomListNotLinkedToTime($portal, $currentTime);
                foreach ($unlinkedContinuousRoomList as $unlinkedContinuousRoom) {
                    /* @var cs_room_item $unlinkedContinuousRoom */
                    $unlinkedContinuousRoom->setContinuous();
                    $unlinkedContinuousRoom->saveWithoutChangingModificationInformation();
                }
            }
        }
    }

    public function getSummary(): string
    {
        return 'Renew links between continuous rooms and current time label';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}
