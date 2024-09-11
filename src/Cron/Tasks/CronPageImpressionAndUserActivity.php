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
use App\Repository\LogRepository;
use App\Repository\PortalRepository;
use cs_room_item;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;

readonly class CronPageImpressionAndUserActivity implements CronTaskInterface
{
    public function __construct(
        private PortalRepository $portalRepository,
        private PortalHelper $portalHelper,
        private LogRepository $logRepository
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    public function run(?DateTimeImmutable $lastRun): void
    {
        $portals = $this->portalRepository->findActivePortals();
        foreach ($portals as $portal) {
            $roomList = $this->portalHelper->getRoomList($portal);
            foreach ($roomList as $room) {
                /** @var cs_room_item $room */

                // get latest timestamp of page impressions and user actitivty
                // from extra field PIUA_LAST or fallback to creation date
                $oldestDate = $room->getPageImpressionAndUserActivityLast() ?:
                    (new DateTimeImmutable($room->getCreationDate()))->format('Ymd');
                $today = (new DateTimeImmutable())->format('Ymd');
                $dayDiff = getDifference($oldestDate, $today);

                $piArray = $room->getPageImpressionArray();
                $uaArray = $room->getUserActivityArray();
                $piInput = [];
                $uaInput = [];

                // for each day, get page impressions and user activity
                for ($i = 0; $i < $dayDiff; $i++) {
                    $upper = (new DateTimeImmutable())
                        ->setTime(0, 0, 0)
                        ->sub(new DateInterval('P' . $i . 'D'));
                    $lower = $upper->sub(new DateInterval('P1D'));

                    $data = $this->logRepository->getCountByContextAndDateSpan($room->getItemID(), $lower, $upper);

                    $piInput[] = $data['count'];
                    $uaArray[] = $data['distinctUserCount'];
                }

                // put actual date in extra field PIUA_LAST
                $room->setPageImpressionAndUserActivityLast($today);
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
}
