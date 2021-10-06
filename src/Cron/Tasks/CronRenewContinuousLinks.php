<?php

namespace App\Cron\Tasks;

use App\Helper\PortalHelper;
use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_room_item;
use cs_time_item;
use DateTimeImmutable;

class CronRenewContinuousLinks implements CronTaskInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var PortalRepository
     */
    private PortalRepository $portalRepository;

    /**
     * @var PortalHelper
     */
    private PortalHelper $portalHelper;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        PortalRepository $portalRepository,
        PortalHelper $portalHelper
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->portalRepository = $portalRepository;
        $this->portalHelper = $portalHelper;
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $timeManager = $this->legacyEnvironment->getTimeManager();
        $timeManager->setTypeLimit('time');

        $portals = $this->portalRepository->findActivePortals();
        foreach ($portals as $portal) {
            if (!$portal->getShowTimePulses() || $portal->getStatus() != 1) {
                continue;
            }

            $timeManager->setContextLimit($portal->getId());

            /** @var cs_time_item $currentTime */
            $currentTime = $timeManager->getItemByName($this->portalHelper->getTitleOfCurrentTime($portal));

            if ($currentTime) {
                $unlinkedContinuousRoomList = $this->portalHelper->getContinuousRoomListNotLinkedToTime($portal, $currentTime);
                foreach ($unlinkedContinuousRoomList as $unlinkedContinuousRoom) {
                    /** @var cs_room_item $unlinkedContinuousRoom */
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