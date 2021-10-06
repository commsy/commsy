<?php

namespace App\Cron\Tasks;

use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;

class CronArchiveUnusedRoomsSendMailBefore implements CronTaskInterface
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

        $portals = $this->portalRepository->findActivePortals();
        foreach ($portals as $portal) {
            if (!$portal->isActivatedArchivingUnusedRooms()) {
                continue;
            }

            $daysSendMailBefore = $portal->getDaysSendMailBeforeArchivingRooms();
            $dateTimeBorderSendMail2 = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysSendMailBeforeArchivingRooms() + 21);

            if (!empty($daysSendMailBefore)) {
                // unused project rooms
                // group rooms will be archived with project room
                $roomManager = $this->legacyEnvironment->getProjectManager();

                $dateTimeBorder = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysUnusedBeforeArchivingRooms() - $portal->getDaysSendMailBeforeArchivingRooms());
                $roomManager->setLastLoginOlderLimit($dateTimeBorder);
                $roomManager->setContextLimit($portal->getId());
                $roomManager->setNotTemplateLimit();
                $roomManager->select();
                $roomList = $roomManager->get();

                foreach ($roomList as $roomItem) {
                    $sendMail = true;
                    $sendMailDateTime = $roomItem->getArchiveMailSendDateTime();

                    if (!empty($sendMailDateTime) && !($sendMailDateTime < $dateTimeBorderSendMail2)) {
                        $sendMail = false;
                    }

                    if ($sendMail) {
                        $roomItem->sendMailArchiveInfoToModeration();
                        $roomItem->setArchiveMailSendDateTime(getCurrentDateTimeInMySQL());
                        $roomItem->saveWithoutChangingModificationInformation();
                    }
                }

                // unused community rooms
                $roomManager = $this->legacyEnvironment->getCommunityManager();

                $dateTimeBorder = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysUnusedBeforeArchivingRooms() - $portal->getDaysSendMailBeforeArchivingRooms());
                $roomManager->setLastLoginOlderLimit($dateTimeBorder);
                $roomManager->setContextLimit($portal->getId());
                $roomManager->setNotTemplateLimit();
                $roomManager->select();

                $roomList = $roomManager->get();
                foreach ($roomList as $roomItem) {
                    $sendMail = true;
                    $sendMailDateTime = $roomItem->getArchiveMailSendDateTime();

                    if (!empty($sendMailDateTime) && !($sendMailDateTime < $dateTimeBorderSendMail2)) {
                        $sendMail = false;
                    }

                    if ($sendMail) {
                        $roomItem->sendMailArchiveInfoToModeration();
                        $roomItem->setArchiveMailSendDateTime(getCurrentDateTimeInMySQL());
                        $roomItem->saveWithoutChangingModificationInformation();
                    }
                }
            }


        }
    }

    public function getSummary(): string
    {
        return 'Send mail before archiving unused rooms';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}