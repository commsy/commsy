<?php

namespace App\Cron\Tasks;

use App\Account\AccountManager;
use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_mail;
use cs_user_item;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CronInactiveUsers implements CronTaskInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var AccountManager
     */
    private AccountManager $accountManager;

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager,
        AccountManager $accountManager,
        RouterInterface $router,
        ParameterBagInterface $parameterBag)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->entityManager = $entityManager;
        $this->accountManager = $accountManager;
        $this->router = $router;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @throws Exception
     */
    public function run(?DateTimeImmutable $lastRun): void
    {
        require_once 'classes/cs_mail.php';

        $userManager = $this->legacyEnvironment->getUserManager();

        $portalRepository = $this->entityManager->getRepository(Portal::class);
        $portals = $portalRepository->findActivePortals();

        foreach ($portals as $portal) {
            /** @var Portal $portal */
            if ($portal->getInactivityLockDays() != 0
                || $portal->getInactivitySendMailBeforeLockDays() != 0
                || $portal->getInactivityDeleteDays() != 0
                || $portal->getInactivitySendMailBeforeDeleteDays() != 0
            ) {
                // get inactivity configuration
                $inactivitySendMailDeleteDays = $portal->getInactivitySendMailBeforeDeleteDays();
                $inactivityDeleteDays = $portal->getInactivityDeleteDays();
                $inactivitySendMailLockDays = $portal->getInactivitySendMailBeforeLockDays();
                $inactivityLockDays = $portal->getInactivityLockDays();

                // calc date to find user which last login is later as the calculated date
                if (isset($inactivitySendMailLockDays) && !empty($inactivitySendMailLockDays)) {
                    // inactivity lock notification is set
                    $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($inactivitySendMailLockDays);
                } else {
                    // inactivity lock notification is not set
                    $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($inactivitySendMailDeleteDays);
                }

                $projectManager = $this->legacyEnvironment->getProjectManager();
                $communityManager = $this->legacyEnvironment->getCommunityManager();
                $roomManager = $this->legacyEnvironment->getRoomManager();

                // get array of users
                $user_array = $userManager->getUserLastLoginLaterAs($date_lastlogin_do, $portal->getId(), 0);
                if (!empty($user_array)) {
                    foreach ($user_array as $user) {
                        /** @var cs_user_item $user */

                        // check if user is last moderator of a room
                        $roomList = new \cs_list();

                        $roomList->addList($projectManager->getRelatedProjectRooms($user, $portal->getId()));
                        $roomList->addList($communityManager->getRelatedCommunityRooms($user, $portal->getId()));

                        $isLastModerator = false;

                        if (!$roomList->isEmpty()) {
                            $room = $roomList->getFirst();
                            while ($room) {
                                $roomUser = $user->getRelatedUserItemInContext($room->getItemID());

                                if ($roomUser && $roomUser->isModerator()) {
                                    if ($roomManager->getNumberOfModerators($room->getItemID()) === 1) {
                                        $isLastModerator = true;
                                        break;
                                    }
                                }

                                $room = $roomList->getNext();
                            }
                        }

                        if ($isLastModerator) {
                            $user->resetInactivity();
                            continue;
                        }

                        if ($user->getStatus() == 0
                            && !$user->getNotifyLockDate()
                            && !$user->getMailSendBeforeLock()
                            && !$user->getMailSendLocked()) {
                            $user->setNotifyLockDate();
                            $user->setMailSendBeforeLock();
                            $user->setMailSendLocked();
                            $user->setLockSendMailDate();
                            $user->save();
                        }

                        // set user mail for log
                        $to = $user->getEmail();
                        // calc days from lastlogin till now
                        $start_date = new DateTime(getCurrentDateTimeInMySQL());
                        $since_start = $start_date->diff(new DateTime($user->getLastLogin()));
                        $days = $since_start->days;
                        if ($days == 0) {
                            $days = 1;
                        }

                        $daysTillLock = 0;

                        // notify lock date
                        $notifyLockDate = $user->getNotifyLockDate();
                        if (!empty($notifyLockDate)) {
                            $start_date_lock = new DateTime($notifyLockDate);
                            $since_start_lock = $start_date_lock->diff(new DateTime(getCurrentDateTimeInMySQL()));
                            $days = $since_start_lock->days;
                            if ($days == 0) {
                                $days = 1;
                            }
                        }
                        // lock date
                        $lockSendMailDate = $user->getLockSendMailDate();
                        if (!empty($lockSendMailDate)) {
                            $start_date_lock = new DateTime($user->getLockSendMailDate());
                            $since_start_lock = $start_date_lock->diff(new DateTime(getCurrentDateTimeInMySQL()));
                            $daysTillLock = $since_start_lock->days;
                            if ($daysTillLock == 0) {
                                $daysTillLock = 1;
                            }
                        }
                        // notify delete date
                        $notifyDeleteDate = $user->getNotifyDeleteDate();
                        if (!empty($notifyDeleteDate)) {
                            $start_date_lock = new DateTime($user->getNotifyDeleteDate());
                            $since_start_lock = $start_date_lock->diff(new DateTime(getCurrentDateTimeInMySQL()));
                            $daysTillLock = $since_start_lock->days;
                            if ($daysTillLock == 0) {
                                $daysTillLock = 1;
                            }
                        }

                        // if lock is not set
                        if (empty($inactivityLockDays)) {
                            $daysTillLock = $days;
                        }

                        // delete user
                        if ($daysTillLock >= $inactivitySendMailDeleteDays &&
                            $user->getMailSendBeforeDelete() && !empty($inactivityDeleteDays)) {
                            // mail locked send or locked configuration is not set
                            if (($user->getMailSendLocked() || (empty($inactivitySendMailLockDays) && empty($inactivityLockDays)))) {
                                $mail = $this->sendMailForUserInactivity("deleted", $user, $portal, $days);
                                if ($mail && $mail->send()) {
                                    // handle deletion
                                    $user->deleteUserCausedByInactivity();
                                }
                            }
                        }

                        // inform about next day deletion
                        $userNotifyDeleteDate = $user->getNotifyDeleteDate();
                        if ($daysTillLock >= $inactivitySendMailDeleteDays - 1 &&
                            (!empty($userNotifyDeleteDate) && $user->getMailSendLocked()
                                || (empty($inactivitySendMailLockDays) && empty($inactivityLockDays))
                            ) && !empty($inactivityDeleteDays)) {
                            if (!$user->getMailSendBeforeDelete()) {
                                // send mail next day delete

                                $mail = $this->sendMailForUserInactivity("deleteNext", $user, $portal, $days);
                                if ($mail && $mail->send()) {
                                    $user->setMailSendNextDelete();
                                    $user->setMailSendBeforeDelete();
                                    $user->save();
                                }
                            }
                        }

                        // inform about future deletion
                        if (($inactivityDeleteDays - $daysTillLock) <= $inactivitySendMailDeleteDays and
                            ($user->getMailSendLocked() or empty($inactivitySendMailLockDays) and
                                empty($inactivityLockDays)) and !empty($inactivitySendMailDeleteDays)) {
                            // send mail delete in the next y days
                            if (!$user->getMailSendBeforeDelete()) {

                                if (!$user->getMailSendNextDelete()) {

                                    if (!$user->getNotifyDeleteDate()) {

                                        $mail = $this->sendMailForUserInactivity("deleteNotify", $user, $portal, $daysTillLock);
                                        if ($mail && $mail->send()) {
                                            $user->setNotifyDeleteDate();
                                            $user->save();
                                        }

                                        // step over
                                        continue;

                                    }
                                }
                            }
                        }

                        // lock now
                        if ($days >= $inactivitySendMailLockDays - 1 and !empty($inactivityLockDays) and $user->getNotifyLockDate()) {
                            if ($user->getMailSendBeforeLock() and !$user->getMailSendLocked()) {
                                // lock user and set lock date till deletion date
                                $user->setLock($portal->getInactivityDeleteDays() + 365); // days till delete
                                $user->reject();
                                $account = $this->accountManager->getAccount($user, $portal->getId());
                                if ($account) {
                                    $this->accountManager->lock($account);
                                }

                                $user->save();
                                // lock user if not locked already
                                $mail = $this->sendMailForUserInactivity("locked", $user, $portal, $days);

                                if ($mail && $mail->send()) {
                                    $user->setMailSendLocked();
                                    $user->setLockSendMailDate();
                                    $user->save();
                                }
                            } else if (!$user->getMailSendBeforeLock()) {
                                // send mail to user that the user will be locked in one day
                                $mail = $this->sendMailForUserInactivity("lockNext", $user, $portal, $days);
                                if ($mail && $mail->send()) {
                                    $user->setMailSendBeforeLock();
                                    $user->save();
                                }

                                // step over
                                continue;
                            }
                        }
                        // lock in x days
                        if ($days >= $portal->getInactivitySendMailBeforeLockDays() and !empty($inactivitySendMailLockDays)) {
                            // send mail lock in x days

                            if (!$user->getMailSendBeforeLock() && !$user->getNotifyLockDate()) {
                                if (($portal->getInactivityLockDays() - $days) <= $portal->getInactivitySendMailBeforeLockDays()) {
                                    $mail = $this->sendMailForUserInactivity("lockNotify", $user, $portal, $inactivitySendMailLockDays);
                                    if ($mail && $mail->send()) {
                                        $user->setNotifyLockDate();
                                        $user->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function getSummary(): string
    {
        return 'Lock and delete inactive users';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }

    private function sendMailForUserInactivity($state, $user, Portal $portal, $days)
    {
        // deleted deleteNext deleteNotify locked lockNext lockNotify
        $translator = $this->legacyEnvironment->getTranslationObject();

        $mail = new cs_mail();

        $to = $user->getEmail();
        $mod_contact_list = $portal->getContactModeratorList($this->legacyEnvironment);
        $mod_user_first = $mod_contact_list->getFirst();

        $urlToPortal = $this->router->generate('app_helper_portalenter', [
            'context' => $portal->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        //content
        $translator->setEmailTextArray($portal->getEmailTextArray());

        $account = $this->accountManager->getAccount($user, $portal->getId());
        if (!$account) {
            return false;
        }

        $authSource = $account->getAuthSource();

        if ($mod_user_first) {
            $fullnameFirstModUser = $mod_user_first->getFullName();
        } else {
            $fullnameFirstModUser = '';
        }

        $emailFrom = $this->parameterBag->get('commsy.email.from');
        $mail->set_from_email($emailFrom);

        $mail->set_from_name($portal->getTitle());


        // set message body for every inactivity state
        switch ($state) {
            case 'lockNotify':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_LOCK_NEXT_SUBJECT', $portal->getInactivitySendMailBeforeLockDays(), $portal->getTitle());
                // lock in x days
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_LOCK_NEXT_BODY', $user->getUserID(), $authSource->getTitle(), $portal->getInactivitySendMailBeforeLockDays(), $urlToPortal, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'lockNext':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_LOCK_TOMORROW_SUBJECT', $portal->getTitle());
                // lock tomorrow
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_LOCK_TOMORROW_BODY', $user->getUserID(), $authSource->getTitle(), $urlToPortal, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'locked':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_LOCK_NOW_SUBJECT', $portal->getTitle());
                // locked
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_LOCK_NOW_BODY', $user->getUserID(), $authSource->getTitle(), $urlToPortal, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'deleteNotify':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_DELETE_NEXT_SUBJECT', $portal->getInactivitySendMailBeforeDeleteDays(), $portal->getTitle());
                // delete in x days
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_DELETE_NEXT_BODY', $user->getUserID(), $authSource->getTitle(), $portal->getInactivitySendMailBeforeDeleteDays(), $urlToPortal, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'deleteNext':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_DELETE_TOMORROW_SUBJECT', $portal->getTitle());
                // delete tomorrow
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_DELETE_TOMORROW_BODY', $user->getUserID(), $authSource->getTitle(), $urlToPortal, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'deleted':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_DELETE_NOW_SUBJECT', '', $portal->getTitle());
                // deleted
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_DELETE_NOW_BODY', $user->getUserID(), $authSource->getTitle(), $urlToPortal, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('EMAIL_COMMSY_PORTAL_MODERATION');
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            default:
                // Should not be used
                break;
        }

        $mail->set_subject($subject);
        $mail->set_message($body);
        $mail->set_to($to);

        return $mail;
    }
}