<?php

namespace App\Cron\Tasks;

use App\Helper\PortalHelper;
use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use cs_environment;
use cs_room_item;
use DateTimeImmutable;

class CronDeleteFiles implements CronTaskInterface
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
                /** @var cs_room_item $activeRoom */
                $fileManager->deleteUnneededFiles($activeRoom->getItemID(), $portal->getId());
            }
        }
    }

    public function getSummary(): string
    {
        return 'Delete old server files';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}