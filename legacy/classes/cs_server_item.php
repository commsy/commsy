<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

/** upper class of the context item
 */
include_once 'classes/cs_guide_item.php';

/** class for a context
 * this class implements a context item
 */
class cs_server_item extends cs_guide_item
{
    /** constructor: cs_server_item
     * the only available constructor, initial values for internal variables
     *
     * @param object environment the environment of the commsy
     */
    public function __construct($environment)
    {
        cs_guide_item::__construct($environment);
        $this->_type = CS_SERVER_TYPE;
    }

    public function isServer()
    {
        return true;
    }

    /** get default portal item id
     *
     * @return string portal item id
     */
    public function getDefaultPortalItemID()
    {
        $retour = '';
        if ($this->_issetExtra('DEFAULT_PORTAL_ID')) {
            $retour = $this->_getExtra('DEFAULT_PORTAL_ID');
        }

        return $retour;
    }

    /** set default portal item id
     *
     * @param default portal item id
     */
    public function setDefaultPortalItemID($value)
    {
        $this->_addExtra('DEFAULT_PORTAL_ID', $value);
    }

    /** get default email sender address
     *
     * @return string default email sender address
     */
    public function getDefaultSenderAddress()
    {
        $retour = '@';
        if ($this->_issetExtra('DEFAULT_SENDER_ADDRESS')) {
            $retour = $this->_getExtra('DEFAULT_SENDER_ADDRESS');
        }

        return $retour;
    }

    /** set default email sender address
     *
     * @param default email sender address
     */
    public function setDefaultSenderAddress($value)
    {
        $this->_addExtra('DEFAULT_SENDER_ADDRESS', $value);
    }

    public function getPortalIDArray()
    {
        $retour = array();
        $portal_manager = $this->_environment->getPortalManager();
        $portal_manager->setContextLimit($this->getItemID());
        $portal_manager->select();
        $portal_id_array = $portal_manager->getIDArray();
        unset($portal_manager);
        if (is_array($portal_id_array)) {
            $retour = $portal_id_array;
        }

        return $retour;
    }

    /** get portal list
     * this function returns a list of all portals
     * existing on this commsy server
     *
     * @return list of portals
     */
    public function getPortalList()
    {
        $portal_manager = $this->_environment->getPortalManager();
        $portal_manager->setContextLimit($this->getItemID());
        $portal_manager->select();
        $portal_list = $portal_manager->get();
        unset($portal_manager);

        return $portal_list;
    }

    /** get portal list
     * this function returns a list of all portals
     * existing on this commsy server
     *
     * @return list of portals
     */
    public function getPortalListByActivity()
    {
        $portal_manager = $this->_environment->getPortalManager();
        $portal_manager->setContextLimit($this->getItemID());
        $portal_manager->setOrder('activity_rev');
        $portal_manager->select();
        $portal_list = $portal_manager->get();

        return $portal_list;
    }

    /** get contact moderator of a room
     * this method returns a list of contact moderator which are linked to the room
     *
     * @return object cs_list a list of contact moderator (cs_label_item)
     */
    public function getContactModeratorList()
    {
        $user_manager = $this->_environment->getUserManager();
        $mod_list = new cs_list();
        $mod_list->add($user_manager->getRootUser());

        return $mod_list;
    }

    #########################################################
    # COMMSY CRON JOBS
    #
    # this cron jobs only works if a daily cron job is
    # configured to run cron.php in /htdocs
    #########################################################

    /** cron daily, INTERNAL
     * here you can link daily cron jobs
     *
     * @return array results of running crons
     */
    public function _cronDaily()
    {
        $cron_array = array();

        # move to portal item
        #$cron_array[] = $this->_cronPageImpressionAndUserActivity();

        $cron_array[] = $this->_cronLog(); // this function must run AFTER all other portal crons
        $cron_array[] = $this->_cronLogArchive();
        $cron_array[] = $this->_cronRoomActivity();
        $cron_array[] = $this->_cronReallyDelete();
        $cron_array[] = $this->_cronReallyDeleteArchive();
        $cron_array[] = $this->_cronCleanTempDirectory();
        $cron_array[] = $this->_cronUnlinkFiles();
        $cron_array[] = $this->_cronItemBackup();
        $cron_array[] = $this->_cronInactiveUserDelete();
        $cron_array[] = $this->_cronCheckPasswordExpiredSoon();
        $cron_array[] = $this->_cronCheckPasswordExpired();

        return $cron_array;
    }

    public function _cronInactiveUserDelete()
    {
        $time_start = getmicrotime();
        $cron_array = array();
        $cron_array['title'] = 'Temporary login as expired';
        $cron_array['description'] = 'check if a temporary login is expired';
        $success = false;
        $translator = $this->_environment->getTranslationObject();
        $server_item = $this->_environment->getServerItem();

        require_once 'classes/cs_mail.php';

        $user_manager = $this->_environment->getUserManager();
        //$current_portal = $this->_environment->getCurrentContextItem();

        $portal_list = $this->getPortalList();
        if ($portal_list->isNotEmpty()) {
            $portal_item = $portal_list->getFirst();
            while ($portal_item) {
                if ($portal_item->getInactivityLockDays() != 0
                    or $portal_item->getInactivitySendMailBeforeLockDays() != 0
                    or $portal_item->getInactivityDeleteDays() != 0
                    or $portal_item->getInactivitySendMailBeforeDeleteDays() != 0
                ) {
                    // get inactivity configuration
                    $inactivitySendMailDeleteDays = $portal_item->getInactivitySendMailBeforeDeleteDays();
                    $inactivityDeleteDays = $portal_item->getInactivityDeleteDays();
                    $inactivitySendMailLockDays = $portal_item->getInactivitySendMailBeforeLockDays();
                    $inactivityLockDays = $portal_item->getInactivityLockDays();

                    // calc date to find user which last login is later as the calculated date
                    if (isset($inactivitySendMailLockDays) and !empty($inactivitySendMailLockDays)) {
                        // inactivity lock notification is set
                        $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($inactivitySendMailLockDays);
                    } else {
                        // inactivity lock notification is not set
                        $date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($inactivitySendMailDeleteDays);
                    }

                    $projectManager = $this->_environment->getProjectManager();
                    $communityManager = $this->_environment->getCommunityManager();
                    $roomManager = $this->_environment->getRoomManager();

                    // get array of users
                    $user_array = $user_manager->getUserLastLoginLaterAs($date_lastlogin_do, $portal_item->getItemID(), 0);
                    if (!empty($user_array)) {
                        foreach ($user_array as $user) {
                            // check if user is last moderator of a room
                            $roomList = new \cs_list();

                            $roomList->addList($projectManager->getRelatedProjectRooms($user, $portal_item->getItemID()));
                            $roomList->addList($communityManager->getRelatedCommunityRooms($user, $portal_item->getItemID()));

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
                                    $mail = $this->sendMailForUserInactivity("deleted", $user, $portal_item, $days);
                                    if ($mail->send()) {
                                        // handle deletion
                                        $user->deleteUserCausedByInactivity();

                                        $cron_array['success'] = true;
                                        $cron_array['success_text'] = 'send delete mail to ' . $to;
                                    } else {
                                        $cron_array['success'] = false;
                                        $cron_array['success_text'] = 'failed send mail to ' . $to;
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

                                    $mail = $this->sendMailForUserInactivity("deleteNext", $user, $portal_item, $days);
                                    if ($mail->send()) {
                                        $user->setMailSendNextDelete();
                                        $user->setMailSendBeforeDelete();
                                        $user->save();

                                        $cron_array['success'] = true;
                                        $cron_array['success_text'] = 'send mail to ' . $to;
                                    } else {
                                        $cron_array['success'] = false;
                                        $cron_array['success_text'] = 'failed send mail to ' . $to;
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

                                            $mail = $this->sendMailForUserInactivity("deleteNotify", $user, $portal_item, $daysTillLock);
                                            if ($mail->send()) {
                                                $user->setNotifyDeleteDate();
                                                $user->save();

                                                $cron_array['success'] = true;
                                                $cron_array['success_text'] = 'send mail to ' . $to;
                                            } else {
                                                $cron_array['success'] = false;
                                                $cron_array['success_text'] = 'failed send mail to ' . $to;
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
                                    $user->setLock($portal_item->getInactivityDeleteDays() + 365); // days till delete
                                    $user->reject();
                                    $user->save();
                                    // lock user if not locked already
                                    $mail = $this->sendMailForUserInactivity("locked", $user, $portal_item, $days);

                                    if ($mail->send()) {
                                        $user->setMailSendLocked();
                                        $user->setLockSendMailDate();
                                        $user->save();

                                        $cron_array['success'] = true;
                                        $cron_array['success_text'] = 'send mail to ' . $to;
                                    } else {
                                        $cron_array['success'] = false;
                                        $cron_array['success_text'] = 'failed send mail to ' . $to;
                                    }
                                } else if (!$user->getMailSendBeforeLock()) {
                                    // send mail to user that the user will be locked in one day
                                    $mail = $this->sendMailForUserInactivity("lockNext", $user, $portal_item, $days);
                                    if ($mail->send()) {
                                        $user->setMailSendBeforeLock();
                                        $user->save();

                                        $cron_array['success'] = true;
                                        $cron_array['success_text'] = 'send mail to ' . $to;
                                    } else {
                                        $cron_array['success'] = false;
                                        $cron_array['success_text'] = 'failed send mail to ' . $to;
                                    }

                                    // step over
                                    continue;
                                }
                            }
                            // lock in x days
                            if ($days >= $portal_item->getInactivitySendMailBeforeLockDays() and !empty($inactivitySendMailLockDays)) {
                                // send mail lock in x days

                                if (!$user->getMailSendBeforeLock() && !$user->getNotifyLockDate()) {
                                    if (($portal_item->getInactivityLockDays() - $days) <= $portal_item->getInactivitySendMailBeforeLockDays()) {
                                        $mail = $this->sendMailForUserInactivity("lockNotify", $user, $portal_item, $inactivitySendMailLockDays);
                                        if ($mail->send()) {
                                            $user->setNotifyLockDate();
                                            $user->save();

                                            $cron_array['success'] = true;
                                            $cron_array['success_text'] = 'send mail to ' . $to;
                                        } else {
                                            $cron_array['success'] = false;
                                            $cron_array['success_text'] = 'failed send mail to ' . $to;
                                        }

                                        // step over
                                        continue;
                                    }
                                }
                            }
                        }
                    }
                }
                $portal_item = $portal_list->getNext();
            }
        }
        if ($success) {
            $cron_array['success'] = true;
            $cron_array['success_text'] = 'mails send';
        } else {
            $cron_array['success'] = true;
            $cron_array['success_text'] = 'nothing to do';
        }

        $time_end = getmicrotime();
        $time = round($time_end - $time_start, 0);
        $cron_array['time'] = $time;
        unset($user_manager);

        return $cron_array;
    }

    public function sendMailModerationForUserInactivity($subject, $body, $to)
    {
        // Hide mail replace user id
    }

    public function sendMailForUserInactivity($state, $user, $portal_item, $days)
    {
        // deleted deleteNext deleteNotify locked lockNext lockNotify
        $translator = $this->_environment->getTranslationObject();

        $mail = new cs_mail();

        $to = $user->getEmail();
        $mod_contact_list = $portal_item->getContactModeratorList();
        $mod_user_first = $mod_contact_list->getFirst();

        // link
        $url_to_portal = '';
        if (!empty($portal_item)) {
            $url_to_portal = $portal_item->getURL();
        }
        $c_commsy_cron_path = $this->_environment->getConfiguration('c_commsy_cron_path');
        if (isset($c_commsy_cron_path)) {
            $link = $c_commsy_cron_path;
        } elseif (!empty($url_to_portal)) {
            $c_commsy_domain = $this->_environment->getConfiguration('c_commsy_domain');
            if (stristr($c_commsy_domain, 'https://')) {
                $link = 'https://';
            } else {
                $link = 'http://';
            }
            $link .= $url_to_portal;
            $file = 'commsy.php';
            $c_single_entry_point = $this->_environment->getConfiguration('c_single_entry_point');
            if (!empty($c_single_entry_point)) {
                $file = $c_single_entry_point;
            }
            $link .= '/' . $file;
        } else {
            $file = $_SERVER['PHP_SELF'];
            $file = str_replace('cron', 'commsy', $file);
            $link = 'http://' . $_SERVER['HTTP_HOST'] . $file;
        }
        $link .= '?cid=' . $portal_item->getItemID() . '&mod=home&fct=index';
        // link

        //content
        $email_text_array = $portal_item->getEmailTextArray();
        $translator->setEmailTextArray($portal_item->getEmailTextArray());

        $auth_source_manager = $this->_environment->getAuthSourceManager();
        $auth_source_id = $user->getAuthSource();
        $auth_source_item = $auth_source_manager->getItem($auth_source_id);

        if ($mod_user_first) {
            $fullnameFirstModUser = $mod_user_first->getFullName();
        } else {
            $fullnameFirstModUser = '';
        }

        global $symfonyContainer;
        $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
        $mail->set_from_email($emailFrom);

        $mail->set_from_name($portal_item->getTitle());


        // set message body for every inactivity state
        switch ($state) {
            case 'lockNotify':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_LOCK_NEXT_SUBJECT', $portal_item->getInactivitySendMailBeforeLockDays(), $portal_item->getTitle());
                // lock in x days
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_LOCK_NEXT_BODY', $user->getUserID(), $auth_source_item->getTitle(), $portal_item->getInactivitySendMailBeforeLockDays(), $link, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'lockNext':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_LOCK_TOMORROW_SUBJECT', $portal_item->getTitle());
                // lock tomorrow
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_LOCK_TOMORROW_BODY', $user->getUserID(), $auth_source_item->getTitle(), $link, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'locked':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_LOCK_NOW_SUBJECT', $portal_item->getTitle());
                // locked
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_LOCK_NOW_BODY', $user->getUserID(), $auth_source_item->getTitle(), $link, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'deleteNotify':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_DELETE_NEXT_SUBJECT', $portal_item->getInactivitySendMailBeforeDeleteDays(), $portal_item->getTitle());
                // delete in x days
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_DELETE_NEXT_BODY', $user->getUserID(), $auth_source_item->getTitle(), $portal_item->getInactivitySendMailBeforeDeleteDays(), $link, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'deleteNext':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_DELETE_TOMORROW_SUBJECT', $portal_item->getTitle());
                // delete tomorrow
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_DELETE_TOMORROW_BODY', $user->getUserID(), $auth_source_item->getTitle(), $link, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                break;
            case 'deleted':
                $subject = $translator->getMessage('EMAIL_INACTIVITY_DELETE_NOW_SUBJECT', '', $portal_item->getTitle());
                // deleted
                $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullname());
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('EMAIL_INACTIVITY_DELETE_NOW_BODY', $user->getUserID(), $auth_source_item->getTitle(), $link, $portal_item->getTitle());
                $body .= "\n\n";
                $body .= $translator->getMessage('EMAIL_COMMSY_PORTAL_MODERATION');
                $body .= "\n\n";
                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $fullnameFirstModUser, $portal_item->getTitle());
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

    public function _cronCheckPasswordExpired()
    {
        // Datenschutz
        $time_start = getmicrotime();
        $cron_array = array();
        $cron_array['title'] = 'Password expire';
        $cron_array['description'] = 'check if a password is expired';

        $user_manager = $this->_environment->getUserManager();
        $authentication = $this->_environment->getAuthenticationObject();
        $translator = $this->_environment->getTranslationObject();
        $portal_list = $this->getPortalList();
        // send mail to user if password expires soon
        // if password is expired set new random password
        if ($portal_list->isNotEmpty()) {
            $portal_item = $portal_list->getFirst();
            while ($portal_item) {
                if ($portal_item->isPasswordExpirationActive()) {
                    if ($user_manager->getCountUserPasswordExpiredByContextID($portal_item->getItemID()) > 0) {
                        $expired_user_array = $user_manager->getUserPasswordExpiredByContextID($portal_item->getItemID());
                        require_once 'classes/cs_mail.php';
                        global $c_password_expiration_user_ids_ignore;
                        foreach ($expired_user_array as $user) {
                            $auth_manager = $this->_environment->getAuthSourceManager();
                            $auth_item = $auth_manager->getItem($user->getAuthSource());
                            if ($auth_item->getSourceType() == 'MYSQL') {
                                if (!$user->isPasswordExpiredEmailSend() && !in_array($user->getUserId(), $c_password_expiration_user_ids_ignore)) {
                                    $auth_manager = $authentication->getAuthManager($user->getAuthSource());
                                    $auth_manager->changePassword($user->getUserID(), uniqid('', true));

                                    $mail = new cs_mail();

                                    $subject = $translator->getMessage('EMAIL_PASSWORD_EXPIRATION_SUBJECT', $portal_item->getTitle());
                                    $to = $user->getEmail();
                                    $to_name = $user->getFullname();
                                    if (!empty($to_name)) {
                                        $to = $to_name . " <" . $to . ">";
                                    }
                                    $mod_contact_list = $portal_item->getContactModeratorList();
                                    $mod_user_first = $mod_contact_list->getFirst();

                                    global $symfonyContainer;
                                    $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                                    $mail->set_from_email($emailFrom);

                                    $mail->set_from_name($portal_item->getTitle());

                                    // link
                                    $url_to_portal = '';
                                    if (!empty($portal_item)) {
                                        $url_to_portal = $portal_item->getURL();
                                    }
                                    $c_commsy_cron_path = $this->_environment->getConfiguration('c_commsy_cron_path');
                                    if (isset($c_commsy_cron_path)) {
                                        $link = $c_commsy_cron_path;
                                    } elseif (!empty($url_to_portal)) {
                                        $c_commsy_domain = $this->_environment->getConfiguration('c_commsy_domain');
                                        if (stristr($c_commsy_domain, 'https://')) {
                                            $link = 'https://';
                                        } else {
                                            $link = 'http://';
                                        }
                                        $link .= $url_to_portal;
                                        $file = 'commsy.php';
                                        $c_single_entry_point = $this->_environment->getConfiguration('c_single_entry_point');
                                        if (!empty($c_single_entry_point)) {
                                            $file = $c_single_entry_point;
                                        }
                                        $link .= '/' . $file;
                                    } else {
                                        $file = $_SERVER['PHP_SELF'];
                                        $file = str_replace('cron', 'commsy', $file);
                                        $link = 'http://' . $_SERVER['HTTP_HOST'] . $file;
                                    }
                                    $link .= '?cid=' . $portal_item->getItemID() . '&mod=home&fct=index&cs_modus=password_forget';
                                    // link

                                    //content
                                    $email_text_array = $portal_item->getEmailTextArray();
                                    $translator->setEmailTextArray($portal_item->getEmailTextArray());

                                    $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullName());
                                    $body .= "\n\n";
                                    $body .= $translator->getEmailMessage('EMAIL_BODY_PASSWORD_EXPIRATION', $link);
                                    $body .= "\n\n";
                                    if ($mod_user_first) {
                                        $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $mod_user_first->getFullName(), $portal_item->getTitle());
                                    } else {
                                        $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', '', $portal_item->getTitle());
                                    }
                                    $body .= "\n\n";
                                    $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));

                                    $mail->set_subject($subject);
                                    $mail->set_message($body);
                                    $mail->set_to($to);

                                    if ($mail->send()) {
                                        $user->setPasswordExpireDate($portal_item->getPasswordExpiration());
                                        $user->save();
                                        $cron_array['success' . '_' . $portal_item->getItemId() . '_' . $user->getItemId()] = true;
                                        $cron_array['success_text' . '_' . $portal_item->getItemId() . '_' . $user->getItemId()] = 'send mail to ' . $to;
                                    } else {
                                        $cron_array['success' . '_' . $portal_item->getItemId() . '_' . $user->getItemId()] = false;
                                        $cron_array['success_text' . '_' . $portal_item->getItemId() . '_' . $user->getItemId()] = 'failed send mail to ' . $to;
                                    }
                                }
                            }
                        }

                        $time_end = getmicrotime();
                        $time = round($time_end - $time_start, 0);
                        $cron_array['time'] = $time;
                    } else {
                        $cron_array['success' . '_' . $portal_item->getItemId()] = true;
                        $cron_array['success_text' . '_' . $portal_item->getItemId()] = 'nothing to do';
                    }
                }

                unset($portal_item);
                $portal_item = $portal_list->getNext();
            }
        }

        return $cron_array;
    }

    public function _cronCheckPasswordExpiredSoon()
    {
        require_once 'functions/curl_functions.php';
        // Datenschutz
        $time_start = getmicrotime();
        $cron_array = array();
        $cron_array['title'] = 'Password expire soon';
        $cron_array['description'] = 'check if a password is expired soon';

        $user_manager = $this->_environment->getUserManager();
        $translator = $this->_environment->getTranslationObject();
        $portal_list = $this->getPortalList();
        // send mail to user if password expires soon
        // if password is expired set new random password
        if ($portal_list->isNotEmpty()) {
            $portal_item = $portal_list->getFirst();
            while ($portal_item) {
                if ($portal_item->isPasswordExpirationActive()) {
                    if ($user_manager->getCountUserPasswordExpiredSoonByContextID($portal_item->getItemID(), $portal_item) > 0) {
                        $expired_user_array = $user_manager->getUserPasswordExpiredSoonByContextID($portal_item->getItemID(), $portal_item);
                        require_once 'classes/cs_mail.php';
                        global $c_password_expiration_user_ids_ignore;
                        foreach ($expired_user_array as $user) {
                            if (!in_array($user->getUserId(), $c_password_expiration_user_ids_ignore)) {
                                $auth_manager = $this->_environment->getAuthSourceManager();
                                $auth_item = $auth_manager->getItem($user->getAuthSource());
                                if ($auth_item->getSourceType() == 'MYSQL') {
                                    //                            if (!$user->isPasswordExpiredEmailSend()){
                                    $mail = new cs_mail();

                                    $mod_contact_list = $portal_item->getContactModeratorList();
                                    $mod_user_first = $mod_contact_list->getFirst();

                                    global $symfonyContainer;
                                    $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                                    $mail->set_from_email($emailFrom);

                                    $mail->set_from_name($portal_item->getTitle());

                                    if ($user->getPasswordExpireDate() > getCurrentDateTimeInMySQL()) {
                                        $start_date = new DateTime(getCurrentDateTimeInMySQL());
                                        $since_start = $start_date->diff(new DateTime($user->getPasswordExpireDate()));
                                        $days = $since_start->days + 1;
                                        if ($days == 0) {
                                            $days = 1;
                                        }
                                    }

                                    $subject = $translator->getMessage('EMAIL_PASSWORD_EXPIRATION_SOON_SUBJECT', $portal_item->getTitle(), $days);
                                    $to = $user->getEmail();
                                    $to_name = $user->getFullname();
                                    if (!empty($to_name)) {
                                        $to = $to_name . " <" . $to . ">";
                                    }

                                    // link
                                    $url_to_portal = '';
                                    if (!empty($portal_item)) {
                                        $url_to_portal = $portal_item->getURL();
                                    }
                                    $c_commsy_cron_path = $this->_environment->getConfiguration('c_commsy_cron_path');
                                    if (isset($c_commsy_cron_path)) {
                                        $link = $c_commsy_cron_path;
                                    } elseif (!empty($url_to_portal)) {
                                        $c_commsy_domain = $this->_environment->getConfiguration('c_commsy_domain');
                                        if (stristr($c_commsy_domain, 'https://')) {
                                            $link = 'https://';
                                        } else {
                                            $link = 'http://';
                                        }
                                        $link .= $url_to_portal;
                                        $file = 'commsy.php';
                                        $c_single_entry_point = $this->_environment->getConfiguration('c_single_entry_point');
                                        if (!empty($c_single_entry_point)) {
                                            $file = $c_single_entry_point;
                                        }
                                        $link .= '/' . $file;
                                    } else {
                                        $file = $_SERVER['PHP_SELF'];
                                        $file = str_replace('cron', 'commsy', $file);
                                        $link = 'http://' . $_SERVER['HTTP_HOST'] . $file;
                                    }
                                    $link .= '?cid=' . $portal_item->getItemID() . '&mod=home&fct=index';
                                    // link

                                    //content
                                    $email_text_array = $portal_item->getEmailTextArray();
                                    $translator->setEmailTextArray($portal_item->getEmailTextArray());

                                    $body = $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullName());
                                    $body .= "\n\n";
                                    $body .= $translator->getEmailMessage('EMAIL_BODY_PASSWORD_EXPIRATION_SOON', $days, $link);
                                    $body .= "\n\n";
                                    if ($mod_user_first) {
                                        $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $mod_user_first->getFullName(), $portal_item->getTitle());
                                    } else {
                                        $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', '', $portal_item->getTitle());
                                    }
                                    $body .= "\n\n";
                                    $body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));

                                    $context_item = $this->_environment->getServerItem();
                                    $translator->setEmailTextArray($context_item->getEmailTextArray());

                                    $mail->set_subject(html_entity_decode($subject));
                                    $mail->set_message($body);
                                    $mail->set_to($to);

                                    if ($mail->send()) {
                                        $cron_array['success' . '_' . $portal_item->getItemId() . '_' . $user->getItemId()] = true;
                                        $cron_array['success_text' . '_' . $portal_item->getItemId() . '_' . $user->getItemId()] = 'send mail to ' . $to;
                                    } else {
                                        $cron_array['success' . '_' . $portal_item->getItemId() . '_' . $user->getItemId()] = false;
                                        $cron_array['success_text' . '_' . $portal_item->getItemId() . '_' . $user->getItemId()] = 'failed send mail to ' . $to;
                                    }
                                }
                            }
                        }

                        $time_end = getmicrotime();
                        $time = round($time_end - $time_start, 0);
                        $cron_array['time'] = $time;
                    } else {
                        $cron_array['success' . '_' . $portal_item->getItemId()] = true;
                        $cron_array['success_text' . '_' . $portal_item->getItemId()] = 'nothing to do';
                    }
                } else {
                    $cron_array['success' . '_' . $portal_item->getItemId()] = true;
                    $cron_array['success_text' . '_' . $portal_item->getItemId()] = 'nothing to do';
                }

                unset($portal_item);
                $portal_item = $portal_list->getNext();
            }
        }

        return $cron_array;
    }

    public function _cronCleanTempDirectory()
    {
        include_once 'functions/misc_functions.php';
        $time_start = getmicrotime();

        $temp_folder = 'var/temp';
        $cron_array = array();
        $cron_array['title'] = 'clean temporary directory "' . $temp_folder . '"';
        $cron_array['description'] = 'free space on hard disk';

        $disc_manager = $this->_environment->getDiscManager();
        $success = $disc_manager->removeDirectory($temp_folder);
        if ($success) {
            $success = $disc_manager->makeDirectory($temp_folder);
            if ($success) {
                global $c_commsy_cron_var_temp_user;
                global $c_commsy_cron_var_temp_group;
                if (isset($c_commsy_cron_var_temp_user) && isset($c_commsy_cron_var_temp_group)) {
                    chown($temp_folder, $c_commsy_cron_var_temp_user);
                    chgrp($temp_folder, $c_commsy_cron_var_temp_group);
                }
            }
        }
        unset($disc_manager);

        if ($success) {
            $cron_array['success'] = true;
            $cron_array['success_text'] = 'cron done';
        } else {
            $cron_array['success'] = false;
            $cron_array['success_text'] = 'failed to clean dir: ' . $temp_folder;
        }

        $time_end = getmicrotime();
        $time = round($time_end - $time_start, 0);
        $cron_array['time'] = $time;

        return $cron_array;
    }

    /** cron log, INTERNAL
     *  daily cron, move old log entries to table log_archive
     *
     * @return array results of running this cron
     */
    /*
    function _cronPageImpressionAndUserActivity () {
       include_once('functions/misc_functions.php');
       $time_start = getmicrotime();

       $cron_array = array();
       $cron_array['title'] = 'page impression and user activity cron';
       $cron_array['description'] = 'count page impressions and user activity';
       $cron_array['success'] = true;
       $cron_array['success_text'] = 'cron failed';

       $log_manager = $this->_environment->getLogManager();

       $portal_list = $this->getPortalList();
       $count_rooms = 0;

       if ( $portal_list->isNotEmpty() ) {
          $portal_item = $portal_list->getFirst();
          while ($portal_item) {
             $room_list = $portal_item->getRoomList();

             if ($room_list->isNotEmpty()) {
                $room_item = $room_list->getFirst();
                while ($room_item) {
                   // get latest timestamp of page impressions and user actitivty
                   // from extra field PIUA_LAST
                   $piua_last = $room_item->getPageImpressionAndUserActivityLast();

                   if(!empty($piua_last)) {
                      $oldest_date = $piua_last;
                   } else {
                      // if there is no entry take creation_date
                      $creation_date = $room_item->getCreationDate();
                      $oldest_date = getYearFromDateTime($creation_date) .
                           getMonthFromDateTime($creation_date) .
                           getDayFromDateTime($creation_date);
                   }

                   $current_date = getCurrentDate();
                   $day_diff = getDifference($oldest_date, $current_date);
                   $pi_array = $room_item->getPageImpressionArray();
                   $ua_array = $room_item->getUserActivityArray();
                   $pi_input = array();
                   $ua_input = array();

                   // for each day, get page impressions and user activity
                   for($i=1;$i < $day_diff;$i++) {
                      $log_manager->resetLimits();
                      $log_manager->setContextLimit($room_item->getItemID());
                      $log_manager->setRequestLimit("commsy.php");
                      $older_limit_stamp = datetime2Timestamp(date("Y-m-d 00:00:00"))-($i-1)*86400;
                      $older_limit = date('Y-m-d', $older_limit_stamp);
                      $log_manager->setTimestampOlderLimit($older_limit);
                      $log_manager->setTimestampNotOlderLimit($i);

                      $pi_input[] = $log_manager->getCountAll();
                      $ua_input[] = $log_manager->countWithUserDistinction();
                   }

                   // put actual date in extra field PIUA_LAST
                   $room_item->setPageImpressionAndUserActivityLast($current_date);
                   $room_item->setPageImpressionArray(array_merge($pi_input, $pi_array));
                   $room_item->setUserActivityArray(array_merge($ua_input, $ua_array));
                   $room_item->saveWithoutChangingModificationInformation();

                   $count_rooms++;
                   unset($room_item);
                   $room_item = $room_list->getNext();
                }
             }
             unset($portal_item);
             $portal_item = $portal_list->getNext();
          }
       }

       $cron_array['success_text'] = 'count page impressions and user activity of '.$count_rooms.' rooms';
       unset($log_manager);
       unset($portal_list);

       $time_end = getmicrotime();
       $time = round($time_end - $time_start,0);
       $cron_array['time'] = $time;

       return $cron_array;
    }
    */

    /**
     * cron log, INTERNAL
     * daily cron, delete old entries in item_backup
     *
     * @return array results of running this cron
     */
    private function _cronItemBackup()
    {
        include_once 'functions/misc_functions.php';
        $time_start = getmicrotime();

        $cron_array = array();
        $cron_array['title'] = 'item backup cron';
        $cron_array['description'] = 'delete old entries in item_backup';
        $cron_array['success'] = false;
        $cron_array['success_text'] = 'cron failed';

        $backupItem_manager = $this->_environment->getBackupItemManager();
        if ($backupItem_manager->deleteOlderThan(14)) {
            $cron_array['success'] = true;
            $cron_array['success_text'] = 'table cleaned up';
        }
        unset($backupItem_manager);

        $time_end = getmicrotime();
        $time = round($time_end - $time_start, 0);
        $cron_array['time'] = $time;

        return $cron_array;
    }

    /** cron log, INTERNAL
     *  daily cron, move old log entries to table log_archive
     *
     * @return array results of running this cron
     */
    public function _cronLog()
    {
        include_once 'functions/misc_functions.php';
        include_once 'functions/date_functions.php';

        $time_start = getmicrotime();

        $cron_array = array();
        $cron_array['title'] = 'log cron';
        $cron_array['description'] = 'move old logs to log archive';
        $cron_array['success'] = false;
        $cron_array['success_text'] = 'cron failed';

        $context_item = $this->_environment->getCurrentContextItem();

        $log_DB = $this->_environment->getLogManager();
        $log_DB->resetlimits();
        $log_DB->setContextLimit(0);

        $from = 0;
        $range = 500;
        $log_DB->setRangeLimit($from, $range);
//       // only archive logs that are older then the beginning of the actual day
//       // getCurrentDate() returns date("Ymd");
//             // Datenschutz : Logdaten nach bestimmtem Zeitraum löschen
//       // Wenn im context_item das Extra eingestellt ist, dann
//       if($context_item->getLogDeleteInterval() <= 1){
//          $log_DB->setTimestampOlderLimit(getCurrentDate());
//       } else {
//          $log_DB->setTimestampOlderLimit(getCurrentDateTimeMinusDaysInMySQL($context_item->getLogDeleteInterval()));
//       }
        $log_DB->setTimestampOlderLimit(getCurrentDate());
        $data_array = $log_DB->select();
        $count = count($data_array);
        if ($count == 0) {
            $cron_array['success'] = true;
            $cron_array['success_text'] = 'nothing to do';
        } else {
            $count_all = 0;
            $log_archive_manager = $this->_environment->getLogArchiveManager();
            while (count($data_array) > 0) {
                // save old logs in log archive
                $success = $log_archive_manager->save($data_array);
                if ($success) {
                    // delete old logs
                    $success = $log_DB->deleteByArray($data_array);
                    if ($success) {
                        $cron_array['success'] = true;
                        $count_all = $count_all + count($data_array);
                        $cron_array['success_text'] = 'move ' . $count_all . ' log entries';
                    }
                }
                unset($data_array);
                $data_array = $log_DB->select();
            }
            unset($log_archive_manager);
        }
        unset($log_DB);

        $time_end = getmicrotime();
        $time = round($time_end - $time_start, 0);
        $cron_array['time'] = $time;

        return $cron_array;
    }

    /** cron log, INTERNAL
     *  daily cron, move old log entries to table log_archive
     *
     * @return array results of running this cron
     */
    public function _cronLogArchive()
    {
        include_once 'functions/misc_functions.php';
        $time_start = getmicrotime();

        $cron_array = array();
        $cron_array['title'] = 'log archive cron';
        $cron_array['description'] = 'delete old logs in log_archive';
        $cron_array['success'] = false;
        $cron_array['success_text'] = 'cron failed';

        $log_DB = $this->_environment->getLogArchiveManager();
        $log_DB->resetlimits();

        $room_manager = $this->_environment->getRoomManager();
        $room_manager->setContextLimit('');
        $room_manager->setLogArchiveLimit();
        $room_ids = $room_manager->getIDs();
        unset($room_manager);

        if ($log_DB->deleteByContextArray($room_ids)) {
            $cron_array['success'] = true;
            $cron_array['success_text'] = 'success';
        }

        unset($log_DB);

        $time_end = getmicrotime();
        $time = round($time_end - $time_start, 0);
        $cron_array['time'] = $time;

        return $cron_array;
    }

    /** cron room activity, INTERNAL
     *  daily cron, minimize activity points
     *
     * @return array results of running this cron
     */
    public function _cronRoomActivity()
    {
        include_once 'functions/misc_functions.php';
        $time_start = getmicrotime();

        $quotient = 4;
        $cron_array = array();
        $cron_array['title'] = 'activity points cron';
        $cron_array['description'] = 'minimize activity points';
        $cron_array['success'] = false;
        $cron_array['success_text'] = 'cron failed';

        $room_manager = $this->_environment->getRoomManager();
        $success1 = $room_manager->minimizeActivityPoints($quotient);

        $portal_manager = $this->_environment->getPortalManager();
        $success2 = $portal_manager->minimizeActivityPoints($quotient);

        $portal_list = $this->getPortalList();
        if (!empty($portal_list)
            and $portal_list->isNotEmpty()
        ) {
            $portal_item = $portal_list->getFirst();
            while ($portal_item) {
                $portal_item->setMaxRoomActivityPoints(round(($portal_item->getMaxRoomActivityPoints() / $quotient), 0));
                $portal_item->saveWithoutChangingModificationInformation();
                unset($portal_item);
                $portal_item = $portal_list->getNext();
            }
        }
        unset($portal_list);

        if ($success1 and $success2) {
            $cron_array['success'] = true;
            $cron_array['success_text'] = '';
            if ($success1) {
                $cron_array['success_text'] .= ' in rooms ';
            }
            if ($success2) {
                $cron_array['success_text'] .= ' in portals ';
            }
        }
        unset($portal_manager);
        unset($room_manager);

        $time_end = getmicrotime();
        $time = round($time_end - $time_start, 0);
        $cron_array['time'] = $time;

        return $cron_array;
    }

    /** cron room activity, INTERNAL
     *  daily cron, minimize activity points
     *
     * @return array results of running this cron
     */
    public function _cronReallyDelete()
    {
        include_once 'functions/misc_functions.php';
        $time_start = getmicrotime();

        $cron_array = array();
        $cron_array['title'] = 'delete items';
        $cron_array['description'] = 'delete items older than x days';
        $cron_array['success'] = true;
        $cron_array['success_text'] = '';

        $item_type_array = array();
        $item_type_array[] = CS_ANNOTATION_TYPE;
        $item_type_array[] = CS_ANNOUNCEMENT_TYPE;
        $item_type_array[] = CS_DATE_TYPE;
        $item_type_array[] = CS_DISCUSSION_TYPE;
        #$item_type_array[] = CS_DISCARTICLE_TYPE; // NO NO NO -> because of closed discussions
        $item_type_array[] = CS_LINKITEMFILE_TYPE;
        $item_type_array[] = CS_FILE_TYPE;
        $item_type_array[] = CS_ITEM_TYPE;
        $item_type_array[] = CS_LABEL_TYPE;
        $item_type_array[] = CS_LINK_TYPE;
        $item_type_array[] = CS_LINKITEM_TYPE;
        $item_type_array[] = CS_MATERIAL_TYPE;
        #$item_type_array[] = CS_PORTAL_TYPE; // not implemented yet because than all data (rooms, data in rooms) should be deleted too
        $item_type_array[] = CS_ROOM_TYPE;
        $item_type_array[] = CS_SECTION_TYPE;
        $item_type_array[] = CS_TAG_TYPE;
        $item_type_array[] = CS_TAG2TAG_TYPE;
        $item_type_array[] = CS_TASK_TYPE;
        $item_type_array[] = CS_TODO_TYPE;
        #$item_type_array[] = CS_USER_TYPE; // NO NO NO -> because of old entries of user

        foreach ($item_type_array as $item_type) {
            $manager = $this->_environment->getManager($item_type);

            global $symfonyContainer;
            $c_delete_days = $symfonyContainer->getParameter('commsy.settings.delete_days');

            if (!empty($c_delete_days) and is_numeric($c_delete_days)) {
                $success = $manager->deleteReallyOlderThan($c_delete_days);
                $cron_array['success'] = $success and $cron_array['success'];
                $cron_array['success_text'] = 'delete entries in database marked as deleted older than ' . $c_delete_days . ' days';
            } else {
                $cron_array['success_text'] = 'nothing to do - please activate etc/commsy/settings.php -> c_delete_days if needed';
            }
            unset($manager);
        }
        unset($item_type_array);

        $time_end = getmicrotime();
        $time = round($time_end - $time_start, 0);
        $cron_array['time'] = $time;

        return $cron_array;
    }

    /** cron delete archived items, INTERNAL
     *  daily cron, delete archived items
     *
     * @return array results of running this cron
     */
    public function _cronReallyDeleteArchive()
    {
        // toggle archive mode
        $toggle_archive_mode = false;
        if (!$this->_environment->isArchiveMode()) {
            $toggle_archive_mode = true;
            $this->_environment->toggleArchiveMode();
        }

        include_once 'functions/misc_functions.php';
        $time_start = getmicrotime();

        $cron_array = array();
        $cron_array['title'] = 'delete archived items';
        $cron_array['description'] = 'delete archived items older than x days';
        $cron_array['success'] = true;
        $cron_array['success_text'] = '';

        $item_type_array = array();
        $item_type_array[] = CS_ANNOTATION_TYPE;
        $item_type_array[] = CS_ANNOUNCEMENT_TYPE;
        $item_type_array[] = CS_DATE_TYPE;
        $item_type_array[] = CS_DISCUSSION_TYPE;
        $item_type_array[] = CS_DISCARTICLE_TYPE;
        $item_type_array[] = CS_LINKITEMFILE_TYPE;
        $item_type_array[] = CS_FILE_TYPE;
        $item_type_array[] = CS_ITEM_TYPE;
        $item_type_array[] = CS_LABEL_TYPE;
        $item_type_array[] = CS_LINK_TYPE;
        $item_type_array[] = CS_LINKITEM_TYPE;
        $item_type_array[] = CS_MATERIAL_TYPE;
        $item_type_array[] = CS_ROOM_TYPE;
        $item_type_array[] = CS_SECTION_TYPE;
        $item_type_array[] = CS_TAG_TYPE;
        $item_type_array[] = CS_TAG2TAG_TYPE;
        $item_type_array[] = CS_TASK_TYPE;
        $item_type_array[] = CS_TODO_TYPE;
        $item_type_array[] = CS_USER_TYPE;

        foreach ($item_type_array as $item_type) {
            $manager = $this->_environment->getManager($item_type);

            global $symfonyContainer;
            $c_delete_days = $symfonyContainer->getParameter('commsy.settings.delete_days');

            if (!empty($c_delete_days) and is_numeric($c_delete_days)) {
                $success = $manager->deleteReallyOlderThan($c_delete_days);

                $cron_array['success'] = $success and $cron_array['success'];
                $cron_array['success_text'] = 'delete entries in database marked as deleted older than ' . $c_delete_days . ' days';
            } else {
                $cron_array['success_text'] = 'nothing to do - please activate etc/commsy/settings.php -> c_delete_days if needed';
            }
            unset($manager);
        }
        unset($item_type_array);

        $time_end = getmicrotime();
        $time = round($time_end - $time_start, 0);
        $cron_array['time'] = $time;

        // toggle archive mode
        if ($toggle_archive_mode) {
            $this->_environment->toggleArchiveMode();
        }
        unset($toggle_archive_mode);

        return $cron_array;
    }

    ####################################################################
    # CRON END
    ####################################################################

    /** get UsageInfos
     * this method returns the usage infos
     *
     * @return array
     */
    public function getUsageInfoArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO')) {
            $retour = $this->_getExtra('USAGE_INFO');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    /** set UsageInfos
     * this method sets the usage infos
     *
     * @param array
     */
    public function setUsageInfoArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO', $value_array);
        }
    }

    /** set UsageInfos
     * this method sets the usage infos
     *
     * @param array
     */
    public function setUsageInfoFormArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM', $value_array);
        }
    }

    /** get UsageInfos
     * this method returns the usage infos
     *
     * @return array
     */
    public function getUsageInfoFormArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_FORM')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function getUsageInfoHeaderArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function setUsageInfoHeaderArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_HEADER', $value_array);
        }
    }

    public function getUsageInfoFormHeaderArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_HEADER');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function setUsageInfoFormHeaderArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM_HEADER', $value_array);
        }
    }

    public function getUsageInfoTextArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function setUsageInfoTextArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_TEXT', $value_array);
        }
    }

    public function getUsageInfoFormTextArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function setUsageInfoFormTextArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM_TEXT', $value_array);
        }
    }

    public function getUsageInfoHeaderForRubric($rubric)
    {
        $translator = $this->_environment->getTranslationObject();
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = $translator->getMessage('USAGE_INFO_HEADER');
        }

        return $retour;
    }

    public function setUsageInfoHeaderForRubric($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $value_array = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
        }
        $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_HEADER', $value_array);
    }

    public function getUsageInfoHeaderForRubricForm($rubric)
    {
        $translator = $this->_environment->getTranslationObject();
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = $translator->getMessage('USAGE_INFO_HEADER');
        }

        return $retour;
    }

    public function setUsageInfoHeaderForRubricForm($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
            $value_array = $this->_getExtra('USAGE_INFO_FORM_HEADER');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
        }
        $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_FORM_HEADER', $value_array);
    }

    public function setUsageInfoTextForRubric($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $value_array = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
        }
        $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_TEXT', $value_array);
    }

    public function setUsageInfoTextForRubricForm($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
        }
        $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_FORM_TEXT', $value_array);
    }

    public function getUsageInfoTextForRubricForm($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $translator = $this->_environment->getTranslationObject();
            $temp = mb_strtoupper($rubric, 'UTF-8') . '_' . mb_strtoupper($funct, 'UTF-8');
            $tempMessage = "";
            switch ($temp) {
                case 'CONFIGURATION_BACKUP':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_BACKUP_FORM');
                    break;

                case 'CONFIGURATION_COLOR':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_COLOR_FORM');
                    break;

                case 'CONFIGURATION_EXTRA':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_EXTRA_FORM');
                    break;

                case 'CONFIGURATION_IMS':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_IMS_FORM');
                    break;

                case 'CONFIGURATION_LANGUAGE':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_LANGUAGE_FORM');
                    break;

                case 'CONFIGURATION_NEWS':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_NEWS_FORM');
                    break;

                case 'CONFIGURATION_PREFERENCES':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_PREFERENCES_FORM');
                    break;

                case 'CONFIGURATION_SERVICE':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_SERVICE_FORM');
                    break;

                case 'CONFIGURATION_OUTOFSERVICE':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_OUTOFSERVICE_FORM');
                    break;

                case 'CONFIGURATION_SCRIBD':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_SCRIBD_FORM');
                    break;

                case 'CONFIGURATION_UPDATE':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_UPDATE_FORM');
                    break;

                case 'CONFIGURATION_HTMLTEXTAREA':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_HTMLTEXTAREA_FORM');
                    break;

                case 'CONFIGURATION_CONNECTION':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_CONNECTION_FORM');
                    break;

                case 'CONFIGURATION_DATASECURITY':
                    $tempMessage = $translator->getMessage('USAGE_INFO_COMING_SOON');
                    break;

                case 'CONFIGURATION_PLUGINS':
                    $tempMessage = $translator->getMessage('USAGE_INFO_COMING_SOON');
                    break;

                default:
                    $tempMessage = $translator->getMessage('COMMON_MESSAGETAG_ERROR') . " cs_server_item (" . __LINE__ . ")";
                    break;

            }

            $retour = $tempMessage;
            if ($retour == 'USAGE_INFO_TEXT_SERVER_FOR_' . $temp . '_FORM' or $retour == 'tbd') {
                $retour = $translator->getMessage('USAGE_INFO_FORM_COMING_SOON');
            }
        }

        return $retour;
    }

    ################################################################
    # Authentication
    ################################################################

    public function setAuthDefault($value)
    {
        $this->_addExtra('DEFAULT_AUTH', $value);
    }

    public function getAuthDefault()
    {
        $retour = '';
        if ($this->_issetExtra('DEFAULT_AUTH')) {
            $value = $this->_getExtra('DEFAULT_AUTH');
            if (!empty($value)) {
                $retour = $value;
            }
        }

        return $retour;
    }

    public function getDefaultAuthSourceItem()
    {
        $retour = null;
        $default_auth_item_id = $this->getAuthDefault();
        if (!empty($default_auth_item_id)) {
            $manager = $this->_environment->getAuthSourceManager();
            $item = $manager->getItem($default_auth_item_id);
            if (isset($item)) {
                $retour = $item;
            }
            unset($item);
            unset($manager);
        }

        return $retour;
    }

    public function getAuthSourceList()
    {
        $manager = $this->_environment->getAuthSourceManager();
        $manager->setContextLimit($this->getItemID());
        $manager->select();
        $retour = $manager->get();
        unset($manager);

        return $retour;
    }

    public function getAuthSource($item_id)
    {
        $manager = $this->_environment->getAuthSourceManager();
        $retour = $manager->getItem($item_id);
        unset($manager);

        return $retour;
    }

    public function getCurrentCommSyVersion()
    {
        $retour = '';
        $version = trim(file_get_contents('version'));
        if (!empty($version)) {
            $retour = $version;
        }

        return $retour;
    }

    /** get out of service text
     *
     * @return array out of service text in different languages
     */
    public function getOutOfServiceArray()
    {
        $retour = array();
        if ($this->_issetExtra('OUTOFSERVICE')) {
            $retour = $this->_getExtra('OUTOFSERVICE');
        }

        return $retour;
    }

    /** set out of service array
     *
     * @param array value out of service text in different languages
     */
    public function setOutOfServiceArray($value)
    {
        $this->_addExtra('OUTOFSERVICE', (array)$value);
    }

    /** get out of service of a context
     * this method returns the out of service of the context
     *
     * @return string out of service of a context
     */
    public function getOutOfServiceByLanguage($language)
    {
        $retour = '';
        if ($language == 'browser') {
            $language = $this->_environment->getSelectedLanguage();
        }
        $desc_array = $this->getOutOfServiceArray();
        if (!empty($desc_array[cs_strtoupper($language)])) {
            $retour = $desc_array[cs_strtoupper($language)];
        }

        return $retour;
    }

    public function getOutOfService()
    {
        $retour = '';
        $retour = $this->getOutOfServiceByLanguage($this->_environment->getSelectedLanguage());
        if (empty($retour)) {
            $retour = $this->getOutOfServiceByLanguage($this->_environment->getUserLanguage());
        }
        if (empty($retour)) {
            $retour = $this->getOutOfServiceByLanguage($this->getLanguage());
        }
        if (empty($retour)) {
            $desc_array = $this->getOutOfServiceArray();
            foreach ($desc_array as $desc) {
                if (!empty($desc)) {
                    $retour = $desc;
                    break;
                }
            }
        }

        return $retour;
    }

    /** set OutOfService of a context
     * this method sets the OutOfService of the context
     *
     * @param string value OutOfService of the context
     * @param string value lanugage of the OutOfService
     */
    public function setOutOfServiceByLanguage($value, $language)
    {
        $desc_array = $this->getOutOfServiceArray();
        $desc_array[mb_strtoupper($language, 'UTF-8')] = $value;
        $this->setOutOfServiceArray($desc_array);
    }

    public function _getOutOfServiceShow()
    {
        return $this->_getExtra('OUTOFSERVICE_SHOW');
    }

    public function showOutOfService()
    {
        $retour = false;
        $show_oos = $this->_getOutOfServiceShow();
        if ($show_oos == 1) {
            $retour = true;
        }

        return $retour;
    }

    public function _setOutOfServiceShow($value)
    {
        $this->_setExtra('OUTOFSERVICE_SHOW', $value);
    }

    public function setDontShowOutOfService()
    {
        $this->_setOutOfServiceShow(-1);
    }

    public function setShowOutOfService()
    {
        $this->_setOutOfServiceShow(1);
    }

    public function getDBVersion()
    {
        $retour = '';
        if ($this->_issetExtra('VERSION')) {
            $retour = $this->_getExtra('VERSION');
        }

        return $retour;
    }

    public function setDBVersion($value)
    {
        $this->_addExtra('VERSION', $value);
    }

    public function getScribdApiKey()
    {
        $retour = '';
        if ($this->_issetExtra('SCRIBD_API_KEY')) {
            $retour = $this->_getExtra('SCRIBD_API_KEY');
        }

        return $retour;
    }

    public function setScribdApiKey($value)
    {
        $this->_addExtra('SCRIBD_API_KEY', $value);
    }

    public function getScribdSecret()
    {
        $retour = '';
        if ($this->_issetExtra('SCRIBD_SECRET')) {
            $retour = $this->_getExtra('SCRIBD_SECRET');
        }

        return $retour;
    }

    public function setScribdSecret($value)
    {
        $this->_addExtra('SCRIBD_SECRET', $value);
    }

    public function isPluginActive($plugin)
    {
        $retour = false;
        #if ( $this->isPluginOn($plugin) ) {
        #   $retour = true;
        #}
        return $retour;
    }

    public function getStatistics($date_start, $date_end)
    {
        $manager = $this->_environment->getServerManager();

        return $manager->getStatistics($this, $date_start, $date_end);
    }

    public function withLogIPCover()
    {
        $retour = false;
        $value = $this->_getExtraConfig('LOGIPCOVER');
        if ($value == 1) {
            $retour = true;
        }

        return $retour;
    }

    public function setWithLogIPCover()
    {
        $this->_setExtraConfig('LOGIPCOVER', 1);
    }

    public function setWithoutLogIPCover()
    {
        $this->_setExtraConfig('LOGIPCOVER', -1);
    }

    ## commsy server connections: portal2portal
    public function getOwnConnectionKey()
    {
        $retour = '';
        $value = $this->_getExtraConfig('CONNECTION_OWNKEY');
        if (!empty($value)) {
            $retour = $value;
        }

        return $retour;
    }

    public function setOwnConnectionKey($value)
    {
        $this->_setExtraConfig('CONNECTION_OWNKEY', $value);
    }

    public function setNewServerConnection($title, $url, $key, $proxy = CS_NO)
    {
        if (!empty($title)
            and !empty($url)
            and !empty($key)
            and !empty($proxy)
        ) {
            $connection_array = $this->getServerConnectionArray();
            $temp_array = array();
            $temp_array['title'] = $title;
            $temp_array['url'] = $url;
            $temp_array['key'] = $key;
            $temp_array['proxy'] = $proxy;

            $key = '';
            $key .= $title;
            $key .= rand(0, 9);
            $key .= $url;
            $key .= rand(0, 9);
            $key .= $key;
            $key .= rand(0, 9);
            include_once 'functions/date_functions.php';
            $key .= getCurrentDateTimeInMySQL();
            $key = md5($key);
            $temp_array['id'] = $key;

            $connection_array[(count($connection_array) + 1)] = $temp_array;
            $this->setServerConnectionArray($connection_array);
        }
    }

    public function setOldServerConnection($id, $title, $url, $key, $proxy = CS_NO)
    {
        if (!empty($title)
            and !empty($url)
            and !empty($key)
            and !empty($proxy)
            and !empty($id)
        ) {
            $connection_array = $this->getServerConnectionArray();
            $temp_array = array();
            $temp_array['title'] = $title;
            $temp_array['url'] = $url;
            $temp_array['key'] = $key;
            $temp_array['proxy'] = $proxy;
            if (!empty($connection_array[$id]['id'])) {
                $temp_array['id'] = $connection_array[$id]['id'];
            } else {
                $key = '';
                $key .= $title;
                $key .= rand(0, 9);
                $key .= $url;
                $key .= rand(0, 9);
                $key .= $key;
                $key .= rand(0, 9);
                include_once 'functions/date_functions.php';
                $key .= getCurrentDateTimeInMySQL();
                $key = md5($key);
                $temp_array['id'] = $key;
            }
            $connection_array[$id] = $temp_array;
            $this->setServerConnectionArray($connection_array);
        }
    }

    public function getServerConnectionArray()
    {
        $retour = array();
        $value = $this->_getExtraConfig('CONNECTION_ARRAY');
        if (!empty($value)) {
            $retour = $value;
        }

        return $retour;
    }

    public function getServerConnectionInfo($id)
    {
        $retour = array();
        $connection_array = $this->getServerConnectionArray();
        if (!empty($connection_array)) {
            foreach ($connection_array as $connection_info) {
                if ($connection_info['id'] == $id) {
                    $retour = $connection_info;
                    break;
                }
            }
        }

        return $retour;
    }

    public function getServerConnectionInfoByKey($key)
    {
        $retour = array();
        $connection_array = $this->getServerConnectionArray();
        if (!empty($connection_array)) {
            foreach ($connection_array as $connection_info) {
                if ($connection_info['key'] == $key) {
                    $retour = $connection_info;
                    break;
                }
            }
        }

        return $retour;
    }

    public function setServerConnectionArray($value)
    {
        $this->_setExtraConfig('CONNECTION_ARRAY', $value);
    }

    public function deleteServerConnection($key)
    {
        if (!empty($key)
            or $key == 0
        ) {
            $connection_array = $this->getServerConnectionArray();
            if (!empty($connection_array[$key])) {
                // delete all tabs on this server
                $server_to_delete = $connection_array[$key];
                $portal_id_array = $this->getPortalIDArray();

                if (!empty($server_to_delete['id'])
                    and !empty($portal_id_array)
                ) {
                    $portal_id_array = $this->getPortalIDArray();

                    $user_manager = $this->_environment->getUserManager();
                    $user_manager->setContextArrayLimit($portal_id_array);
                    $user_manager->setExternalConnectionServerKeyLimit($server_to_delete['id']);
                    $user_manager->select();
                    $user_list = $user_manager->get();
                    if (!empty($user_list)
                        and $user_list->isNotEmpty()
                    ) {
                        $user_item = $user_list->getFirst();
                        while ($user_item) {
                            // delete tabs from server
                            $user_item->deletePortalConnectionFromServer($server_to_delete['id']);
                            $user_item->save();
                            $user_item = $user_list->getNext();
                        }
                    }
                }

                // delete server
                unset($connection_array[$key]);

                // reset keys
                if (!empty($connection_array)) {
                    $key_array = array_keys($connection_array);
                    $temp_array = array();
                    $i = 0;
                    foreach ($key_array as $key) {
                        $i++;
                        $temp_array[$i] = $connection_array[$key];
                    }
                    $connection_array = $temp_array;
                    unset($i);
                    unset($temp_array);
                    unset($key_array);
                    unset($key);
                }

                $this->setServerConnectionArray($connection_array);
            }
        }
    }

    public function isServerConnectionAvailable()
    {
        $retour = false;
        $server_array = $this->getServerConnectionArray();
        if (!empty($server_array)
            and is_array($server_array)
            and count($server_array) > 0
        ) {
            $retour = true;
        }

        return $retour;
    }
}