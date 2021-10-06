<?php

namespace App\Cron\Tasks;

use App\Helper\PortalHelper;
use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;

class CronCheckTimeLabels implements CronTaskInterface
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
        $timeManager->setSortOrder('title');

        $portals = $this->portalRepository->findActivePortals();
        foreach ($portals as $portal) {
            if (!$portal->getShowTimePulses() || $portal->getStatus() != 1) {
                continue;
            }

            $timeManager->setContextLimit($portal->getId());
            $timeManager->select();

            $timeList = $timeManager->get();
            $counter = 0;
            $count = false;

            $currentTimeLabelTitle = $this->portalHelper->getTitleOfCurrentTime($portal);
            foreach ($timeList as $time) {
                if ($count) {
                    $counter++;
                }
                if ($currentTimeLabelTitle == $time->getTitle()) {
                    $count = true;
                }
            }

            if ($counter < $portal->getNumberOfFutureTimePulses()) {
                $lastTime = $timeList->getLast();
                $lastTimeTitle = $lastTime->getTitle();

                $lastTimeTitleExplode = explode('_', $lastTimeTitle);
                $time_text_array = $portal->getTimeTextArray();
                if ($lastTimeTitleExplode[1] == count($time_text_array)) {
                    $title = ($lastTimeTitleExplode[0] + 1) . '_1';
                } else {
                    $title = $lastTimeTitleExplode[0] . '_' . ($lastTimeTitleExplode[1] + 1);
                }

                $timeLabel = $timeManager->getNewItem();
                $timeLabel->setContextID($portal->getId());
                $timeLabel->setCreatorItem($this->legacyEnvironment->getRootUserItem());
                $timeLabel->setModificatorItem($this->legacyEnvironment->getRootUserItem());
                $timeLabel->setTitle($title);
                $timeLabel->save();
            }
        }
    }

    public function getSummary(): string
    {
        return 'Check switching between two time labels';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}