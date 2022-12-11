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

class CronCheckTimeLabels implements CronTaskInterface
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
        $timeManager->setSortOrder('title');

        $portals = $this->portalRepository->findActivePortals();
        foreach ($portals as $portal) {
            if (!$portal->getShowTimePulses() || 1 != $portal->getStatus()) {
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
                    ++$counter;
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
                if ($lastTimeTitleExplode[1] == (is_countable($time_text_array) ? count($time_text_array) : 0)) {
                    $title = ($lastTimeTitleExplode[0] + 1).'_1';
                } else {
                    $title = $lastTimeTitleExplode[0].'_'.($lastTimeTitleExplode[1] + 1);
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
