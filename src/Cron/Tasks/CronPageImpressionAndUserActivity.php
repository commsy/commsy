<?php

namespace App\Cron\Tasks;

use App\Helper\PortalHelper;
use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_room_item;
use DateTimeImmutable;

class CronPageImpressionAndUserActivity implements CronTaskInterface
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
        $logManager = $this->legacyEnvironment->getLogManager();

        $portals = $this->portalRepository->findActivePortals();
        foreach ($portals as $portal) {
            $roomList = $this->portalHelper->getRoomList($portal);
            foreach ($roomList as $room) {
                /** @var cs_room_item $room */

                // get latest timestamp of page impressions and user actitivty
                // from extra field PIUA_LAST
                $piuaLast = $room->getPageImpressionAndUserActivityLast();

                if (!empty($piuaLast)) {
                    $oldestDate = $piuaLast;
                } else {
                    // if there is no entry take creationDate
                    $creationDate = $room->getCreationDate();
                    $oldestDate = getYearFromDateTime($creationDate) .
                        getMonthFromDateTime($creationDate) .
                        getDayFromDateTime($creationDate);
                }

                $currentDate = getCurrentDate();
                $dayDiff = getDifference($oldestDate, $currentDate);
                $piArray = $room->getPageImpressionArray();
                $uaArray = $room->getUserActivityArray();
                $piInput = [];
                $uaInput = [];

                // for each day, get page impressions and user activity
                for ($i = 1; $i < $dayDiff; $i++) {
                    $logManager->resetLimits();
                    $logManager->setContextLimit($room->getItemID());
                    $logManager->setRequestLimit("/room/");
                    $older_limit_stamp = datetime2Timestamp(date("Y-m-d 00:00:00")) - ($i - 1) * 86400;
                    $older_limit = date('Y-m-d', $older_limit_stamp);
                    $logManager->setTimestampOlderLimit($older_limit);
                    $logManager->setTimestampNotOlderLimit($i);

                    $piInput[] = $logManager->getCountAll();
                    $uaInput[] = $logManager->countWithUserDistinction();
                }

                // put actual date in extra field PIUA_LAST
                $room->setPageImpressionAndUserActivityLast($currentDate);
                $room->setPageImpressionArray(array_merge($piInput, $piArray));
                $room->setUserActivityArray(array_merge($uaInput, $uaArray));
                $room->saveWithoutChangingModificationInformation();
            }
        }
    }

    public function getSummary(): string
    {
        return 'Count page impressions and user activity';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}