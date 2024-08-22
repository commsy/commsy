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

use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class CronRoomActivity implements CronTaskInterface
{
    private const QUOTIENT = 4;

    private readonly cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, private readonly EntityManagerInterface $entityManager)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $portalManager = $this->legacyEnvironment->getPortalManager();

        $roomManager->minimizeActivityPoints(self::QUOTIENT);
        $portalManager->minimizeActivityPoints(self::QUOTIENT);

        $portalRepository = $this->entityManager->getRepository(Portal::class);
        $portals = $portalRepository->findActivePortals();
        foreach ($portals as $portal) {
            /* @var Portal $portal */
            $portal->setMaxRoomActivityPoints(round($portal->getMaxRoomActivityPoints() / self::QUOTIENT));

            // TODO ??? This will save the portal with an updated modification time due to ORM lifecycle callbacks
            $this->entityManager->persist($portal);
        }

        $this->entityManager->flush();
    }

    public function getSummary(): string
    {
        return 'Calculate activity points';
    }
}
