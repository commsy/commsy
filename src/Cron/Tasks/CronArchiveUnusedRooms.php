<?php

namespace App\Cron\Tasks;

use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;

class CronArchiveUnusedRooms implements CronTaskInterface
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

            $daysMailSendBefore = $portal->getDaysSendMailBeforeArchivingRooms();
            $datetimeBorderSendMail2 = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysSendMailBeforeArchivingRooms() + 21);

            // unused project rooms
            // group rooms will be archived with project room
            $countProject = 0;
            $roomManager = $this->legacyEnvironment->getProjectManager();

            $datetime_border = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysUnusedBeforeArchivingRooms());
            $roomManager->setLastLoginOlderLimit($datetime_border);
            $roomManager->setContextLimit($portal->getId());
            $roomManager->setNotTemplateLimit();
            $roomManager->select();

            $roomList = $roomManager->get();
            if ($roomList->isNotEmpty()) {
                $datetimeBorderSendMail = getCurrentDateTimeMinusHoursInMySQL(($portal->getDaysSendMailBeforeArchivingRooms() - 0.5) * 24);

                foreach ($roomList as $roomItem) {
                    $archive = true;
                    if (!empty($daysMailSendBefore)) {
                        $sendMailDatetime = $roomItem->getArchiveMailSendDateTime();

                        // room will only archived configured days after sending email
                        if (empty($sendMailDatetime) || $sendMailDatetime > $datetimeBorderSendMail) {
                            $archive = false;
                        }

                        // maybe mail was send and user login into room
                        // after one period room will be archived without sending mail,
                        // because there is a datetime from sending mail a period before
                        // this if clause reset the datetime of sending the email
                        // $datetimeBorderSendMail = 3 weeks before border to send mail
                        elseif ($sendMailDatetime < $datetimeBorderSendMail2) {
                            $archive = false;
                            $roomItem->setArchiveMailSendDateTime('');
                            $roomItem->saveWithoutChangingModificationInformation();
                        }
                    }

                    if ($archive) {
                        $roomItem->close();
                        $roomItem->save();
                        $roomItem->moveToArchive();
                        $countProject++;
                    }
                }
            }

            // unused community rooms
            $countCommunity = 0;
            $roomManager = $this->legacyEnvironment->getCommunityManager();
            $datetime_border = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysUnusedBeforeArchivingRooms());
            $roomManager->setLastLoginOlderLimit($datetime_border);
            $roomManager->setContextLimit($portal->getId());
            $roomManager->setNotTemplateLimit();
            $roomManager->select();

            $roomList = $roomManager->get();
            if ($roomList->isNotEmpty()) {
                $datetimeBorderSendMail = getCurrentDateTimeMinusDaysInMySQL($portal->getDaysSendMailBeforeArchivingRooms());

                foreach ($roomList as $roomItem) {
                    $archive = true;
                    if (!empty($daysMailSendBefore)) {
                        $sendMailDatetime = $roomItem->getArchiveMailSendDateTime();

                        // room will only archived configured days after sending email
                        if (empty($sendMailDatetime) || $sendMailDatetime > $datetimeBorderSendMail) {
                            $archive = false;
                        }

                        // maybe mail was send and user login into room
                        // after one period room will be archived without sending mail,
                        // because there is a datetime from sending mail a period before
                        // this if clause reset the datetime of sending the email
                        // $datetimeBorderSendMail = 3 weeks before border to send mail
                        elseif ($sendMailDatetime < $datetimeBorderSendMail2) {
                            $archive = false;
                            $roomItem->setArchiveMailSendDateTime('');
                            $roomItem->saveWithoutChangingModificationInformation();
                        }
                    }

                    if ($archive) {
                        $roomItem->close();
                        $roomItem->save();
                        $roomItem->moveToArchive();
                        $countCommunity++;
                    }
                }
            }
        }
    }

    public function getSummary(): string
    {
        return 'Archive unused rooms';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}