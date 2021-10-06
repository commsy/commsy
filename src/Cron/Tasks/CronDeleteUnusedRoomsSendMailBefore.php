<?php

namespace App\Cron\Tasks;

use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;

class CronDeleteUnusedRoomsSendMailBefore implements CronTaskInterface
{
    /**
     * @var PortalRepository
     */
    private PortalRepository $portalRepository;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(PortalRepository $portalRepository, LegacyEnvironment $legacyEnvironment)
    {
        $this->portalRepository = $portalRepository;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        include_once('functions/date_functions.php');

        $this->legacyEnvironment->activateArchiveMode();

        $portals = $this->portalRepository->findActivePortals();
        foreach ($portals as $portal) {
            if (!$portal->isActivatedArchivingUnusedRooms() || !$portal->isActivatedDeletingUnusedRooms()) {
                continue;
            }

            $daysMailSendBefore = $portal->getDaysSendMailBeforeDeletingRooms();
            $dateTimeBorderSendMail2 = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysSendMailBeforeDeletingRooms() + 21);

            if (!empty($daysMailSendBefore)) {
                // unused project rooms
                // group rooms will be archived with project room
                $roomManager = $this->legacyEnvironment->getProjectManager();
                $datetime_border = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysUnusedBeforeDeletingRooms() - $portal->getDaysSendMailBeforeDeletingRooms());
                $roomManager->setLastLoginOlderLimit($datetime_border);
                $roomManager->setContextLimit($portal->getId());
                $roomManager->select();

                $roomList = $roomManager->get();
                foreach ($roomList as $roomItem) {
                    $sendMail = true;
                    $sendMailDateTime = $roomItem->getDeleteMailSendDateTime();

                    if (!empty($sendMailDateTime) && !($sendMailDateTime < $dateTimeBorderSendMail2)) {
                        $sendMail = false;
                    }

                    if ($sendMail) {
                        $roomItem->sendMailDeleteInfoToModeration();
                        $roomItem->setDeleteMailSendDateTime(getCurrentDateTimeInMySQL());
                        $roomItem->saveWithoutChangingModificationInformation();
                    }
                }

                // unused community rooms
                $roomManager = $this->legacyEnvironment->getCommunityManager();
                $datetime_border = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysUnusedBeforeDeletingRooms() - $portal->getDaysSendMailBeforeDeletingRooms());
                $roomManager->setLastLoginOlderLimit($datetime_border);
                $roomManager->setContextLimit($portal->getId());
                $roomManager->select();

                $roomList = $roomManager->get();
                foreach ($roomList as $roomItem) {
                    $sendMail = true;
                    $sendMailDateTime = $roomItem->getDeleteMailSendDateTime();

                    if (!empty($sendMailDateTime) && !($sendMailDateTime < $dateTimeBorderSendMail2)) {
                        $sendMail = false;
                    }

                    if ($sendMail) {
                        $roomItem->sendMailDeleteInfoToModeration();
                        $roomItem->setDeleteMailSendDateTime(getCurrentDateTimeInMySQL());
                        $roomItem->saveWithoutChangingModificationInformation();
                    }
                }
            }
        }

        $this->legacyEnvironment->deactivateArchiveMode();
    }

    public function getSummary(): string
    {
        return 'Send mail before deleting unused rooms';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}