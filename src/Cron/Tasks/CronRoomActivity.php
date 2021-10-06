<?php

namespace App\Cron\Tasks;

use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class CronRoomActivity implements CronTaskInterface
{
    private const QUOTIENT = 4;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(LegacyEnvironment $legacyEnvironment, EntityManagerInterface $entityManager)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->entityManager = $entityManager;
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
            /** @var Portal $portal */
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

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}