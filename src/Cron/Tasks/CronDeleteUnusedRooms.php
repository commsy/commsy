<?php

namespace App\Cron\Tasks;

use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;

class CronDeleteUnusedRooms implements CronTaskInterface
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

            // unused project rooms
            // group rooms will be deleted with project room
            $roomManager = $this->legacyEnvironment->getProjectManager();
            $datetime_border = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysUnusedBeforeDeletingRooms());
            $roomManager->setLastLoginOlderLimit($datetime_border);
            $roomManager->setContextLimit($portal->getId());
            $roomManager->select();

            $roomList = $roomManager->get();
            if ($roomList->isNotEmpty()) {
                $dateTimeBorderSendMail = getCurrentDateTimeMinusHoursInMySQL(($portal->getDaysSendMailBeforeDeletingRooms() - 0.5) * 24);

                foreach ($roomList as $roomItem) {
                    $delete = true;
                    if (!empty($daysMailSendBefore)) {
                        $sendMailDateTime = $roomItem->getDeleteMailSendDateTime();

                        // room will only deleted configured days after sending email
                        if (empty($sendMailDateTime) || $sendMailDateTime > $dateTimeBorderSendMail) {
                            $delete = false;
                        }

                        // maybe mail was send and user login into room
                        // after one period room will be deleted without sending mail,
                        // because there is a datetime from sending mail a period before
                        // this if clause reset the datetime of sending the email
                        // $dateTimeBorderSendMail = 3 weeks before border to send mail
                        elseif ($sendMailDateTime < $dateTimeBorderSendMail2) {
                            $delete = false;
                            $roomItem->setDeleteMailSendDateTime('');
                            $roomItem->saveWithoutChangingModificationInformation();
                        }
                    }

                    if ($delete) {
                        $roomItem->delete();
                    }
                }
            }

            // unused community rooms
            $roomManager = $this->legacyEnvironment->getCommunityManager();
            $datetime_border = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysUnusedBeforeDeletingRooms());
            $roomManager->setLastLoginOlderLimit($datetime_border);
            $roomManager->setContextLimit($portal->getId());
            $roomManager->select();

            $roomList = $roomManager->get();
            if ($roomList->isNotEmpty()) {
                $dateTimeBorderSendMail = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysSendMailBeforeDeletingRooms());

                foreach ($roomList as $roomItem) {
                    $delete = true;
                    if (!empty($daysMailSendBefore)) {
                        $sendMailDateTime = $roomItem->getDeleteMailSendDateTime();

                        // room will only deleted configured days after sending email
                        if (empty($sendMailDateTime) || $sendMailDateTime > $dateTimeBorderSendMail) {
                            $delete = false;
                        }

                        // maybe mail was send and user login into room
                        // after one period room will be deleted without sending mail,
                        // because there is a datetime from sending mail a period before
                        // this if clause reset the datetime of sending the email
                        // $dateTimeBorderSendMail = 3 weeks before border to send mail
                        elseif ($sendMailDateTime < $dateTimeBorderSendMail2) {
                            $delete = false;
                            $roomItem->setDeleteMailSendDateTime('');
                            $roomItem->saveWithoutChangingModificationInformation();
                        }
                    }

                    if ($delete) {
                        $roomItem->delete();
                    }
                }
            }
        }

        $this->legacyEnvironment->deactivateArchiveMode();
    }

    public function getSummary(): string
    {
        return 'Delete unused rooms';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}