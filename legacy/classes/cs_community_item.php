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

use App\Entity\Room;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** class for a community
 * this class implements a community item.
 */
class cs_community_item extends cs_room_item
{
    /**
     * Constructor.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);

        $this->_type = CS_COMMUNITY_TYPE;
        $this->_default_rubrics_array[0] = CS_ANNOUNCEMENT_TYPE;
        $this->_default_rubrics_array[1] = CS_PROJECT_TYPE;
        $this->_default_rubrics_array[2] = CS_TODO_TYPE;
        $this->_default_rubrics_array[3] = CS_DATE_TYPE;
        $this->_default_rubrics_array[4] = CS_MATERIAL_TYPE;
        $this->_default_rubrics_array[5] = CS_DISCUSSION_TYPE;
        $this->_default_rubrics_array[6] = CS_USER_TYPE;
        $this->_default_rubrics_array[7] = CS_TOPIC_TYPE;

        $this->defaultHomeConf[CS_ANNOUNCEMENT_TYPE] = 'show';
        $this->defaultHomeConf[CS_PROJECT_TYPE] = 'show';
        $this->defaultHomeConf[CS_DATE_TYPE] = 'show';
        $this->defaultHomeConf[CS_MATERIAL_TYPE] = 'show';
        $this->defaultHomeConf[CS_USER_TYPE] = 'show';
        $this->defaultHomeConf[CS_TOPIC_TYPE] = 'show';
        $this->defaultHomeConf[CS_DISCUSSION_TYPE] = 'show';
        $this->defaultHomeConf[CS_TODO_TYPE] = 'show';
    }

   public function isCommunityRoom()
   {
       return true;
   }

    /** get projects of a project
     * this method returns a list of projects which are linked to the project.
     *
     * @return object cs_list a list of projects (cs_project_item)
     */
    public function getProjectList(): cs_list
    {
        return $this->getLinkedItemList(CS_PROJECT_TYPE);
    }

   /** get project ids of a community
    * this method returns an array of projects ids which are linked to the community.
    *
    * @return array an array of projects ids
    */
   public function getProjectIDArray()
   {
       return $this->getLinkedItemIDArray(CS_PROJECT_TYPE);
   }

   public function getInternalProjectIDArray()
   {
       $retour = [];
       if ($this->_issetExtra('PROJECT_ID_ARRAY')) {
           $array = $this->_getExtra('PROJECT_ID_ARRAY');
           if (is_array($array)) {
               $retour = $array;
           }
       }

       return $retour;
   }

   public function setInternalProjectIDArray($array)
   {
       if (is_array($array)) {
           $this->_setExtra('PROJECT_ID_ARRAY', $array);
       }
   }

   public function addProjectID2InternalProjectIDArray($id)
   {
       if (is_numeric($id)) {
           $array = $this->getInternalProjectIDArray();
           if (!in_array($id, $array)) {
               $array[] = $id;
           }
           $this->setInternalProjectIDArray($array);
       }
   }

   public function removeProjectID2InternalProjectIDArray($id)
   {
       if (is_numeric($id)) {
           $array = $this->getInternalProjectIDArray();
           if (in_array($id, $array)) {
               unset($array[array_search($id, $array)]);
           }
           $this->setInternalProjectIDArray($array);
       }
   }

   public function unsetInternalProjectIDArray()
   {
       $this->setInternalProjectIDArray([]);
   }

   public function initInternalProjectIDArray()
   {
       $this->setInternalProjectIDArray($this->getProjectIDArray());
   }

   /** get communitys of a project
    * this method returns a list of communitys which are linked to the project.
    *
    * @return object cs_list a list of communitys (cs_community_item)
    */
   public function getCommunityList()
   {
       return $this->getLinkedItemList(CS_COMMUNITY_TYPE);
   }

   /** set projects of a project item by item id and version id
    * this method sets a list of project item_ids and version_ids which are linked to the project.
    *
    * @param array of project ids, index of id must be 'iid', index of version must be 'vid'
    * Example:
    * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
    *
    * @author CommSy Development Group
    */
   public function setProjectListByID($value)
   {
       $project_array = [];
       foreach ($value as $iid) {
           $tmp_data = [];
           $tmp_data['iid'] = $iid;
           $project_array[] = $tmp_data;
       }
       $this->_setValue(CS_PROJECT_TYPE, $project_array, false);
   }

   /** set projects of a project
    * this method sets a list of projects which are linked to the project.
    *
    * @param string value title of the project
    *
    * @author CommSy Development Group
    */
   public function setProjectList($value)
   {
       $this->_setObject(CS_PROJECT_TYPE, $value, false);
   }

   /** save community
    * this method save the community.
    */
   public function save()
   {
       $item_id = $this->getItemID();
       $manager = $this->_environment->getCommunityManager();
       $current_user = $this->_environment->getCurrentUser();
       if (empty($item_id)) {
           $this->setContinuous();
           $this->setServiceLinkActive();
           $this->setContactPerson($current_user->getFullName());
       }

       $this->_save($manager);
       unset($manager);

       if (empty($item_id)) {
           // create first moderator
           $new_room_user = $current_user->cloneData();
           $new_room_user->setContextID($this->getItemID());
           $new_room_user->makeModerator();
           $new_room_user->makeContactPerson();
           $new_room_user->setVisibleToLoggedIn();
           $new_room_user->setAccountWantMail('yes');
           $new_room_user->setOpenRoomWantMail('yes');
           $new_room_user->setPublishMaterialWantMail('yes');
           $new_room_user->save();
           $new_room_user->setCreatorID2ItemID();
           $picture = $current_user->getPicture();
           if (!empty($picture)) {
               $value_array = explode('_', $picture);
               $value_array[0] = 'cid'.$new_room_user->getContextID();
               $new_picture_name = implode('_', $value_array);

               $disc_manager = $this->_environment->getDiscManager();
               $disc_manager->copyImageFromRoomToRoom($picture, $new_room_user->getContextID());
               unset($disc_manager);

               $new_room_user->setPicture($new_picture_name);
               $new_room_user->save();
           }
           unset($new_room_user);
           unset($current_user);

           // send mail to moderation
           $this->_sendMailRoomOpen();
       } else {
           $new_status = $this->getStatus();
           if (!empty($this->_old_status)
                and !empty($new_status)
                and $new_status != $this->_old_status) {
               if (CS_ROOM_LOCK == $this->_old_status) {
                   $this->_sendMailRoomUnlock();
               } elseif (CS_ROOM_CLOSED == $new_status) {
                   $this->_sendMailRoomArchive();
               } elseif (CS_ROOM_OPEN == $new_status) {
                   $this->_sendMailRoomReOpen();
               } elseif (CS_ROOM_LOCK == $new_status) {
                   $this->_sendMailRoomLock();
               }
           }
       }
       if (empty($item_id)) {
           $this->initTagRootItem();
       }

       $this->updateElastic();
   }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_room');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Room::class);

        $this->replaceElasticItem($objectPersister, $repository);
    }

   /** delete community
    * this method deletes the community.
    */
   public function delete()
   {
       parent::delete();

       // send mail to moderation
       $this->_sendMailRoomDelete();

       $manager = $this->_environment->getCommunityManager();
       $this->_delete($manager);

       global $symfonyContainer;
       $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_room');
       $em = $symfonyContainer->get('doctrine.orm.entity_manager');
       $repository = $em->getRepository(Room::class);

       $this->deleteElasticItem($objectPersister, $repository);
   }

   public function undelete()
   {
       $manager = $this->_environment->getCommunityManager();
       $this->_undelete($manager);

       // send mail to moderation
       $this->_sendMailRoomUnDelete();
   }

   public function getTimeSpread()
   {
       $retour = '90';
       if ($this->_issetExtra('TIMESPREAD')) {
           $retour = $this->_getExtra('TIMESPREAD');
       }

       return $retour;
   }

   /** set time spread for items on home
    * this method sets the time spread for items on the home of the project project.
    *
    * @param int value the time spread
    */
   public function setTimeSpread($value)
   {
       $this->_addExtra('TIMESPREAD', (int) $value);
   }

   // ##################################################
   // time methods
   // ##################################################

   public function _getShowTime()
   {
       $retour = '';
       if ($this->_issetExtra('TIME_SHOW')) {
           $retour = $this->_getExtra('TIME_SHOW');
       }

       return $retour;
   }

   public function showTime()
   {
       $retour = true;
       $value = $this->_getShowTime();
       if (-1 == $value) {
           $retour = false;
       }

       return $retour;
   }

   public function setShowTime()
   {
       $this->_addExtra('TIME_SHOW', 1);
   }

   public function setNotShowTime()
   {
       $this->_addExtra('TIME_SHOW', -1);
   }

   // ##########################################################
   // some function to get lists of items in one community room
   // ##########################################################

   public function getProjectRoomList()
   {
       $room_manager = $this->_environment->getProjectManager();
       $room_manager->resetLimits();
       $room_manager->setContextLimit($this->getContextID());
       global $c_cache_cr_pr;
       if (!isset($c_cache_cr_pr) or !$c_cache_cr_pr) {
           $room_manager->setCommunityRoomLimit($this->getItemID());
       } else {
           /*
            * use redundant infos in community room
            */
           $room_manager->setIDArrayLimit($this->getInternalProjectIDArray());
       }
       $room_manager->select();
       $room_list = $room_manager->get();

       return $room_list;
   }

   public function isActive($start, $end)
   {
       $activity_border = 9;
       $activity = 0;

       $activity = $activity + $this->getCountItems($start, $end);
       if ($activity > $activity_border) {
           return true;
       }

       // count project items additionaly because item manager can count them
       $activity = $activity + $this->getCountProjects($start, $end);
       if ($activity > $activity_border) {
           return true;
       }

       return false;
   }

   public function getUsageInfoTextForRubric($rubric)
   {
       $translator = $this->_environment->getTranslationObject();

       // index Seiten
       $funct = $this->_environment->getCurrentFunction();
       if ($this->_issetExtra('USAGE_INFO_TEXT')) {
           $retour = $this->_getExtra('USAGE_INFO_TEXT');
           if (empty($retour)) {
               $retour = [];
           } elseif (!is_array($retour)) {
               $retour = XML2Array($retour);
           }
       } else {
           $retour = [];
       }
       if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
           $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
       } else {
           $retour = '';
       }

       return $retour;
   }

   public function setUsageInfoTextForRubric($rubric, $string)
   {
       if ($this->_issetExtra('USAGE_INFO_TEXT')) {
           $value_array = $this->_getExtra('USAGE_INFO_TEXT');
           if (empty($value_array)) {
               $value_array = [];
           } elseif (!is_array($value_array)) {
               $value_array = XML2Array($value_array);
           }
       } else {
           $value_array = [];
       }
       if (!empty($string)) {
           $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
       } else {
           if (isset($value_array[mb_strtoupper($rubric, 'UTF-8')])) {
               unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
           }
       }
       $this->_addExtra('USAGE_INFO_TEXT', $value_array);
   }

   public function setUsageInfoTextForRubricForm($rubric, $string)
   {
       if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
           $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
           if (empty($value_array)) {
               $value_array = [];
           } elseif (!is_array($value_array)) {
               $value_array = XML2Array($value_array);
           }
       } else {
           $value_array = [];
       }
       if (!empty($string)) {
           $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
       } else {
           if (isset($value_array[mb_strtoupper($rubric, 'UTF-8')])) {
               unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
           }
       }
       $this->_addExtra('USAGE_INFO_FORM_TEXT', $value_array);
   }

   public function getUsageInfoTextForRubricForm($rubric)
   {
       // formular
       $funct = $this->_environment->getCurrentFunction();
       if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
           $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
           if (empty($retour)) {
               $retour = [];
           } elseif (!is_array($retour)) {
               $retour = XML2Array($retour);
           }
       } else {
           $retour = [];
       }
       if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
           $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
       } else {
           $retour = '';
       }

       return $retour;
   }

   public function getUsageInfoTextForRubricFormInForm($rubric)
   {
       // Konfiguration: Einstellung (Formular)
       $funct = $this->_environment->getCurrentFunction();
       if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
           $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
           if (empty($retour)) {
               $retour = [];
           } elseif (!is_array($retour)) {
               $retour = XML2Array($retour);
           }
       } else {
           $retour = [];
       }
       if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
           $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
       } else {
           $retour = '';
       }

       return $retour;
   }

   public function getUsageInfoTextForRubricInForm($rubric)
   {
       // Konfigurationsoption: Einstellen (Index)
       $funct = $this->_environment->getCurrentFunction();
       if ($this->_issetExtra('USAGE_INFO_TEXT')) {
           $retour = $this->_getExtra('USAGE_INFO_TEXT');
           if (empty($retour)) {
               $retour = [];
           } elseif (!is_array($retour)) {
               $retour = XML2Array($retour);
           }
       } else {
           $retour = [];
       }
       if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
           $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
       } else {
           $retour = '';
       }

       return $retour;
   }

    public function getMDOActive()
    {
        // Konfigurationsoption: Medieninhalte(Mediendistribution-Online)
        $retour = '';
        if ($this->_issetExtra('MEDIA_MDO_ACTIVE')) {
            $retour = $this->_getExtra('MEDIA_MDO_ACTIVE');
        }

        return $retour;
    }

    public function setMDOActive($active)
    {
        if ($active) {
            $this->_addExtra('MEDIA_MDO_ACTIVE', 1);
        } else {
            $this->_addExtra('MEDIA_MDO_ACTIVE', -1);
        }
    }

    public function getMDOKey()
    {
        // Konfigurationsoption: Medieninhalte(Mediendistribution-Online)
        $retour = '';
        if ($this->_issetExtra('MEDIA_MDO_KEY')) {
            $retour = $this->_getExtra('MEDIA_MDO_KEY');
        }

        return $retour;
    }

    public function setMDOKey($key)
    {
        if (!empty($key)) {
            $this->_addExtra('MEDIA_MDO_KEY', $key);
        } else {
            $this->_addExtra('MEDIA_MDO_KEY', -1);
        }
    }

   // ###############################################################
   // mail to moderation, if the community room status changed
   // - delete
   // - undelete
   // - open
   // - archive
   // - template (not implemented yet because flagged function)
   // - untemplate (not implemented yet because flagged function)
   // - reopen
   // ###############################################################

   public function _sendMailRoomDelete()
   {
       $this->_sendMailRoomDeleteToCommunityModeration();
       $this->_sendMailRoomDeleteToPortalModeration();
   }

   public function _sendMailRoomUnDelete()
   {
       $this->_sendMailRoomUnDeleteToCommunityModeration();
       $this->_sendMailRoomUnDeleteToPortalModeration();
   }

   public function _sendMailRoomOpen()
   {
       $this->_sendMailRoomOpenToCommunityModeration();
       $this->_sendMailRoomOpenToPortalModeration();
   }

   public function _sendMailRoomArchive()
   {
       $this->_sendMailRoomArchiveToCommunityModeration();
       $this->_sendMailRoomArchiveToPortalModeration();
   }

   public function _sendMailRoomReOpen()
   {
       $this->_sendMailRoomReOpenToCommunityModeration();
       $this->_sendMailRoomReOpenToPortalModeration();
   }

   public function _sendMailRoomLock()
   {
       $this->_sendMailRoomLockToCommunityModeration();
       $this->_sendMailRoomLockToPortalModeration();
   }

   public function _sendMailRoomUnlock()
   {
       $this->_sendMailRoomUnlockToCommunityModeration();
       $this->_sendMailRoomUnlockToPortalModeration();
   }

   public function _sendMailToModeration($room_moderation, $room_change)
   {
       if ('portal' == $room_moderation) {
           $this->_sendMailToModeration2($this->getContextItem(), $room_change);
       } elseif ('community' == $room_moderation) {
           $this->_sendMailToModeration2($this, $room_change);
       } else {
           trigger_error('lost room moderation', E_USER_WARNING);
       }
   }

   public function _sendMailToModeration2($room_item, $room_change)
   {
       $translator = $this->_environment->getTranslationObject();
       $default_language = 'de';

       global $symfonyContainer;
       $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
       $default_sender_address = $emailFrom;

       $current_portal = $this->_environment->getCurrentPortalItem();
       if (empty($current_portal)
            or !$current_portal->isPortal()
       ) {
           $current_portal = $this->getContextItem();
       }
       $current_user = $this->_environment->getCurrentUserItem();
       $fullname = $current_user->getFullname();
       if (empty($fullname)) {
           $current_user = $this->_environment->getRootUserItem();
           $email = $current_user->getEmail();
           if (empty($email)
                and !empty($default_sender_address)
                and '@' != $default_sender_address
           ) {
               $current_user->setEmail($default_sender_address);
           }
       }

       $moderator_list = $room_item->getModeratorList();

       // get moderators
       $receiver_array = [];
       $moderator_name_array = [];

       if ($moderator_list->isNotEmpty()) {
           $mod_item = $moderator_list->getFirst();
           while ($mod_item) {
               if ('yes' == $mod_item->getOpenRoomWantMail()) {
                   $language = $room_item->getLanguage();
                   if ('user' == $language) {
                       $language = $mod_item->getLanguage();
                       if ('browser' == $language) {
                           $language = $default_language;
                       }
                   }
                   $receiver_array[$language][] = $mod_item->getEmail();
                   $moderator_name_array[] = $mod_item->getFullname();
               }
               $mod_item = $moderator_list->getNext();
           }
       }

       // now email information
       foreach ($receiver_array as $key => $value) {
           $subject = '';
           if ($room_item->isPortal()) {
               $subject .= $room_item->getTitle().': ';
           }
           $save_language = $translator->getSelectedLanguage();
           $translator->setSelectedLanguage($key);
           $title = str_ireplace('&amp;', '&', $this->getTitle());
           if ('open' == $room_change) {
               $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_OPEN', $title);
           } elseif ('reopen' == $room_change) {
               $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_REOPEN', $title);
           } elseif ('delete' == $room_change) {
               $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_DELETE', $title);
           } elseif ('undelete' == $room_change) {
               $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_UNDELETE', $title);
           } elseif ('archive' == $room_change) {
               $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_ARCHIVE', $title);
           } elseif ('link' == $room_change) {
               $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_LINK', $title);
           } elseif ('lock' == $room_change) {
               $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_LOCK', $title);
           } elseif ('unlock' == $room_change) {
               $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_UNLOCK', $title);
           }
           $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
           $body .= LF.LF;
           if ('open' == $room_change) {
               $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_OPEN');
               $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_OPEN');
           } elseif ('reopen' == $room_change) {
               $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_REOPEN');
               $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_REOPEN');
           } elseif ('delete' == $room_change) {
               $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_DELETE');
               $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_DELETE');
           } elseif ('undelete' == $room_change) {
               $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_UNDELETE');
               $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNDELETE');
           } elseif ('archive' == $room_change) {
               $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_ARCHIVE');
               $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_ARCHIVE');
           } elseif ('lock' == $room_change) {
               $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_LOCK');
               $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_LOCK');
           } elseif ('unlock' == $room_change) {
               $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_UNLOCK');
               $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNLOCK');
           }
           $body .= LF.LF;

           $editorFullName = !empty($current_user->getFullname()) ? $current_user->getFullname() : '-';
           $body .= $translator->getMessage(
               'PROJECT_MAIL_BODY_INFORMATION',
               str_ireplace('&amp;', '&', $this->getTitle()),
               $editorFullName,
               $room_change_action
           );

           if ('delete' != $room_change) {
               global $symfonyContainer;
               $url = $symfonyContainer->get('router')->generate('app_room_home', [
                   'roomId' => $this->getItemID(),
               ], UrlGeneratorInterface::ABSOLUTE_URL);

               $body .= LF.$url;
           }

           $body .= LF.LF;
           $body .= $translator->getMessage('MAIL_SEND_TO', implode(LF, $moderator_name_array));
           $body .= LF.LF;
           if ($room_item->isPortal()) {
               $body .= $translator->getMessage('MAIL_SEND_WHY_PORTAL', $room_item->getTitle());
           } elseif ($room_item->isCommunityRoom()) {
               $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY', $room_item->getTitle());
           } else {
               $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT', $room_item->getTitle());
           }

           // send email
           $mail = new cs_mail();
           $mail->set_to(implode(',', $value));
           $mail->set_from_email($default_sender_address);
           if (isset($current_portal)) {
               $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $current_portal->getTitle()));
           } else {
               $server_item = $this->_environment->getServerItem();
               $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $server_item->getTitle()));
               unset($server_item);
           }
           $mail->set_reply_to_name($current_user->getFullname());
           $mail->set_reply_to_email($current_user->getEmail());
           $mail->set_subject($subject);
           $mail->set_message($body);
           $mail->send();
           $translator->setSelectedLanguage($save_language);
           unset($save_language);
           unset($mail);
       }
       unset($current_portal);
       unset($current_user);
   }

   public function getCountUsedAccounts($start, $end)
   {
       $retour = 0;

       $user_manager = $this->_environment->getUserManager();
       $user_manager->resetLimits();
       $project_id_array = $this->getProjectIDArray();
       $project_id_array[] = $this->getItemID();
       $user_manager->setContextArrayLimit($project_id_array);
       $retour = $user_manager->getCountUsedAccounts($start, $end);
       unset($user_manager);

       return $retour;
   }

   public function getCountOpenAccounts($start, $end)
   {
       $retour = 0;

       $user_manager = $this->_environment->getUserManager();
       $user_manager->resetLimits();
       $project_id_array = $this->getProjectIDArray();
       $project_id_array[] = $this->getItemID();
       $user_manager->setContextArrayLimit($project_id_array);
       $retour = $user_manager->getCountOpenAccounts($start, $end);
       unset($user_manager);

       return $retour;
   }

   public function getCountAllAccounts($start, $end)
   {
       $retour = 0;

       $user_manager = $this->_environment->getUserManager();
       $user_manager->resetLimits();
       $project_id_array = $this->getProjectIDArray();
       $project_id_array[] = $this->getItemID();
       $user_manager->setContextArrayLimit($project_id_array);
       $retour = $user_manager->getCountAllAccounts($start, $end);
       unset($user_manager);

       return $retour;
   }

   public function getCountPluginWithLinkedRooms($plugin, $start, $end)
   {
       $retour = 0;

       $user_manager = $this->_environment->getUserManager();
       $user_manager->resetLimits();
       $project_id_array = $this->getProjectIDArray();
       $project_id_array[] = $this->getItemID();
       $user_manager->setContextArrayLimit($project_id_array);
       $retour = $user_manager->getCountPlugin($plugin, $start, $end);
       unset($user_manager);

       return $retour;
   }

   public function _setObjectLinkItems($changed_key)
   {
       if (CS_PROJECT_TYPE == $changed_key) {
           $array = [];
           if (!empty($this->_data[$changed_key])
                and is_object($this->_data[$changed_key])
           ) {
               $item = $this->_data[$changed_key]->getFirst();
               while ($item) {
                   $array[] = $item->getItemID();
                   $item = $this->_data[$changed_key]->getNext();
               }
           }
           $this->setInternalProjectIDArray($array);
       }
   }

   public function _setIDLinkItems($changed_key)
   {
       if (CS_PROJECT_TYPE == $changed_key) {
           if (!empty($this->_data[$changed_key])
                and is_array($this->_data[$changed_key])
           ) {
               $this->setInternalProjectIDArray($this->_data[$changed_key]);
           }
       }
   }
}
