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
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** father class for a rooms (project or community)
 * this class implements an abstract room item.
 */
class cs_project_item extends cs_room_item
{
    /**
     * boolean - are groups available in this project?
     */
    public $_group_support = null;

    /**
     * boolean - are topics available in this project?
     */
    public $_topic_support = null;

    /**
     * boolean - are materials available in this project?
     */
    public $_material_support = null;

    public $_new_community_id_array = null;
    public $_old_community_id_array = null;

    public $_changed_room_link = false;

    /**
     * the room item to be used as a template when creating user rooms.
     */
    private ?cs_context_item $_userroomTemplateItem = null;

    private $environment = null;

    /** constructor.
     *
     * @param object environment environment of the commsy project
     */
    public function __construct($environment)
    {
        cs_context_item::__construct($environment);
        $this->_type = CS_PROJECT_TYPE;

        $this->environment = $environment;
        $this->_default_rubrics_array[0] = CS_ANNOUNCEMENT_TYPE;
        $this->_default_rubrics_array[1] = CS_TODO_TYPE;
        $this->_default_rubrics_array[2] = CS_DATE_TYPE;
        $this->_default_rubrics_array[3] = CS_MATERIAL_TYPE;
        $this->_default_rubrics_array[4] = CS_DISCUSSION_TYPE;
        $this->_default_rubrics_array[5] = CS_USER_TYPE;
        $this->_default_rubrics_array[6] = CS_GROUP_TYPE;
        $this->_default_rubrics_array[7] = CS_TOPIC_TYPE;

        $this->defaultHomeConf[CS_ANNOUNCEMENT_TYPE] = 'show';
        $this->defaultHomeConf[CS_TODO_TYPE] = 'show';
        $this->defaultHomeConf[CS_DATE_TYPE] = 'show';
        $this->defaultHomeConf[CS_MATERIAL_TYPE] = 'show';
        $this->defaultHomeConf[CS_DISCUSSION_TYPE] = 'show';
        $this->defaultHomeConf[CS_USER_TYPE] = 'show';
        $this->defaultHomeConf[CS_GROUP_TYPE] = 'show';
        $this->defaultHomeConf[CS_TOPIC_TYPE] = 'show';
    }

    public function isProjectRoom()
    {
        return true;
    }

    public function isOpenForGuests()
    {
        return false;
    }

    /** get user comment
     * this method returns the users comment: why he or she wants an account.
     *
     * @return string user comment
     *
     * @author CommSy Development Group
     */
    public function getUserComment()
    {
        $retour = false;
        if ($this->_issetExtra('USERCOMMENT')) {
            $retour = $this->_getExtra('USERCOMMENT');
        }

        return $retour;
    }

    /** set user comment
     * this method sets the users comment why he or she wants an account.
     *
     * @param string value user comment
     *
     * @author CommSy Development Group
     */
    public function setUserComment($value)
    {
        $this->_addExtra('USERCOMMENT', (string) $value);
    }

    /** get time spread for items on home
     * this method returns the time spread for items on the home of the project project.
     *
     * @return int the time spread
     */
    public function getTimeSpread()
    {
        $retour = '7';
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

    public function _getTaskList()
    {
        $task_manager = $this->_environment->getTaskManager();

        return $task_manager->getTaskListForItem($this);
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

    /** set communitys of a project item by item id and version id
     * this method sets a list of community item_ids and version_ids which are linked to the project.
     *
     * @param array of community ids, index of id must be 'iid', index of version must be 'vid'
     * Example:
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     */
    public function setCommunityListByID($value)
    {
        $community_list_old = $this->getCommunityList();
        $community_array_old = [];
        if ($community_list_old->isNotEmpty()) {
            $community_item = $community_list_old->getFirst();
            while ($community_item) {
                $community_array_old[] = $community_item->getItemID();
                $community_item = $community_list_old->getNext();
            }
        }
        $this->setLinkedItemsByID(CS_COMMUNITY_TYPE, $value);
        $this->_new_community_id_array = $value;

        // send mail to moderation
        $diff_array1 = array_diff($this->_new_community_id_array, $community_array_old);
        $diff_array2 = array_diff($community_array_old, $this->_new_community_id_array);
        if (!empty($diff_array1)
             or !empty($diff_array2)
        ) {
            $this->_old_community_id_array = $community_array_old;
            $item_id = $this->getItemID();
            if (!empty($item_id)) {
                $this->_changed_room_link = true;
            }
        }
    }

    /** set communitys of a project
     * this method sets a list of communitys which are linked to the project.
     *
     * @param string value title of the project
     */
    public function setCommunityList($value)
    {
        $community_list_old = $this->getCommunityList();
        $community_array_old = [];
        if ($community_list_old->isNotEmpty()) {
            $community_item = $community_list_old->getFirst();
            while ($community_item) {
                $community_array_old[] = $community_item->getItemID();
                $community_item = $community_list_old->getNext();
            }
        }

        $this->_setObject(CS_COMMUNITY_TYPE, $value, false);
        if ($value->isNotEmpty()) {
            $this->_new_community_id_array = [];
            $item = $value->getFirst();
            while ($item) {
                $this->_new_community_id_array[] = $item->getItemID();
                $item = $value->getNext();
            }
        }

        // send mail to moderation
        $diff_array1 = array_diff($this->_new_community_id_array, $community_array_old);
        $diff_array2 = array_diff($community_array_old, $this->_new_community_id_array);
        if (!empty($diff_array1)
             or !empty($diff_array2)
        ) {
            $this->_old_community_id_array = $community_array_old;
            $item_id = $this->getItemID();
            if (!empty($item_id)) {
                $this->_changed_room_link = true;
            }
        }
    }

    /** save project
     * this method save the project.
     */
    public function save()
    {
        $item_id = $this->getItemID();

        $current_user = $this->_environment->getCurrentUser();
        $manager = $this->_environment->getProjectManager();
        if (empty($item_id)) {
            $this->setServiceLinkActive();
            $this->setContactPerson($current_user->getFullName());
        }
        $this->_save($manager);

        if (empty($item_id)) {
            // create first moderator
            $new_room_user = $current_user->cloneData();
            $new_room_user->setContextID($this->getItemID());
            $new_room_user->makeModerator();
            $new_room_user->makeContactPerson();
            $new_room_user->setAccountWantMail('yes');
            $new_room_user->setOpenRoomWantMail('yes');
            $new_room_user->save();
            $new_room_user->setCreatorID2ItemID();

            if ($this->_environment->getCurrentPortalItem()->getConfigurationHideMailByDefault()) {
                $new_room_user->setEmailNotVisible();
            }

            // save picture in new room
            $picture = $current_user->getPicture();
            if (!empty($picture)) {
                $value_array = explode('_', $picture);
                $value_array[0] = 'cid'.$new_room_user->getContextID();
                $new_picture_name = implode('_', $value_array);

                $disc_manager = $this->_environment->getDiscManager();
                $disc_manager->copyImageFromRoomToRoom($picture, $new_room_user->getContextID());
                $new_room_user->setPicture($new_picture_name);
            }

            // save group all
            $group_manager = $this->_environment->getLabelManager();
            $group = $group_manager->getNewItem('group');
            $group->setName('ALL');
            $group->setDescription('GROUP_ALL_DESC');
            $group->setContextID($this->getItemID());
            $group->setCreatorID($new_room_user->getItemID());
            $group->makeSystemLabel();
            $group->save();

            // link moderator 2 group all
            $new_room_user->setGroupByID($group->getItemID());
            $new_room_user->setChangeModificationOnSave(false);
            $new_room_user->save();

            // send mail to moderation
            $this->_sendMailRoomOpen();
            if ($this->_changed_room_link) {
                $this->_sendMailRoomLink();
                $this->_changed_room_link = false;
            }
        } else {
            $new_status = $this->getStatus();
            $creation_date = $this->getCreationDate();
            $timestamp = strtotime($creation_date);
            $show_time = true;
            if (($timestamp + 60) <= time()) {
                $show_time = false;
            }
            if ($new_status != $this->_old_status) {
                if (CS_ROOM_LOCK == $this->_old_status) {
                    $this->_sendMailRoomUnlock();
                } elseif (CS_ROOM_CLOSED == $new_status) {
                    $this->_sendMailRoomArchive();
                } elseif (CS_ROOM_OPEN == $new_status and !$show_time) {
                    $this->_sendMailRoomReOpen();
                } elseif (CS_ROOM_LOCK == $new_status) {
                    $this->_sendMailRoomLock();
                }
            }
            if ($this->_changed_room_link) {
                $this->_sendMailRoomLink();
                $this->_changed_room_link = false;
            }
        }
        unset($new_room_user);
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

     /**
      * Locks the project room as well as any group rooms belonging to groups of this room.
      */
     public function lock()
     {
         parent::lock();

         // lock related group rooms
         foreach ($this->getGroupRoomList() as $groupRoom) {
             /* @var \cs_grouproom_item $groupRoom */
             $groupRoom->lock();
             $groupRoom->save();
         }
     }

     /**
      * Unlocks the project room as well as any group rooms belonging to groups of this room.
      */
     public function unlock()
     {
         parent::unlock();

         // unlock related group rooms
         foreach ($this->getGroupRoomList() as $groupRoom) {
             /* @var \cs_grouproom_item $groupRoom */
             $groupRoom->unlock();
             $groupRoom->save();
         }
     }

     /**
      * Deletes the project room.
      */
     public function delete()
     {
         parent::delete();

         // delete related group rooms
         foreach ($this->getGroupRoomList() as $groupRoom) {
             /* @var \cs_grouproom_item $groupRoom */
             $groupRoom->delete();
             $groupRoom->save();
         }

         // delete all project room users which will also delete any associated user rooms
         foreach ($this->getUserList() as $user) {
             /* @var \cs_user_item $user */
             $user->delete();
             $user->save();
         }

         // delete in community rooms
         $com_list = $this->getCommunityList();
         if (isset($com_list)
             and is_object($com_list)
             and $com_list->isNotEmpty()
         ) {
             $com_item = $com_list->getFirst();
             while ($com_item) {
                 $com_item->removeProjectID2InternalProjectIDArray($this->getItemID());
                 $com_item->saveWithoutChangingModificationInformation();
                 unset($com_item);
                 $com_item = $com_list->getNext();
             }
         }
         unset($com_list);

         // delete associated tasks
         $task_list = $this->_getTaskList();
         $current_task = $task_list->getFirst();
         while ($current_task) {
             $current_task->delete();
             unset($current_task);
             $current_task = $task_list->getNext();
         }
         unset($task_list);

         // send mail to moderation
         $this->_sendMailRoomDelete();

         $manager = $this->_environment->getProjectManager();
         $this->_delete($manager);
         unset($manager);

         global $symfonyContainer;
         $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_room');
         $em = $symfonyContainer->get('doctrine.orm.entity_manager');
         $repository = $em->getRepository(Room::class);
         $this->deleteElasticItem($objectPersister, $repository);
     }

    public function undelete()
    {
        $manager = $this->_environment->getProjectManager();
        $this->_undelete($manager);

        // send mail to moderation
        $this->_sendMailRoomUnDelete();

        // re-insert internal community room links
        $com_list = $this->getCommunityList();
        if (isset($com_list)
             and is_object($com_list)
             and $com_list->isNotEmpty()
        ) {
            $com_item = $com_list->getFirst();
            while ($com_item) {
                $com_item->addProjectID2InternalProjectIDArray($this->getItemID());
                $com_item->saveWithoutChangingModificationInformation();
                unset($com_item);
                $com_item = $com_list->getNext();
            }
        }
    }

    public function isActive($start, $end)
    {
        $activity_border = 9;
        $activity = 0;

        $activity = $activity + $this->getCountItems($start, $end);
        if ($activity > $activity_border) {
            return true;
        }

        return false;
    }

    public function maySee($user_item)
    {
        $context_item = $this->_environment->getCurrentContextItem();
        if ($user_item->isRoot() or
             ($user_item->getContextID() == $this->_environment->getCurrentContextID()
               and ($user_item->isGuest() or $user_item->isUser())
             ) or $context_item->isOpenForGuests()
        ) {
            $access = true;
        } else {
            $access = false;
        }

        return $access;
    }

    public function getUsageInfoTextForRubric($rubric)
    {
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
        if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
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
        }if (!empty($string)) {
            $value_array[mb_strtoupper((string) $rubric, 'UTF-8')] = $string;
        } else {
            if (isset($value_array[mb_strtoupper((string) $rubric, 'UTF-8')])) {
                unset($value_array[mb_strtoupper((string) $rubric, 'UTF-8')]);
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
            $value_array[mb_strtoupper((string) $rubric, 'UTF-8')] = $string;
        } else {
            if (isset($value_array[mb_strtoupper((string) $rubric, 'UTF-8')])) {
                unset($value_array[mb_strtoupper((string) $rubric, 'UTF-8')]);
            }
        }
        $this->_addExtra('USAGE_INFO_FORM_TEXT', $value_array);
    }

    public function getUsageInfoTextForRubricForm($rubric)
    {
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
        if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
        } else {
            $retour = '';
        }

        return $retour;
    }

    public function getUsageInfoTextForRubricInForm($rubric)
    {
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
        if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
        } else {
            $retour = '';
        }

        return $retour;
    }

    public function getUsageInfoTextForRubricFormInForm($rubric)
    {
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
        if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
        } else {
            $retour = '';
        }

        return $retour;
    }

     // #####################################################
     // user rooms
     // #####################################################

     public function getShouldCreateUserRooms(): bool
     {
         if ($this->_issetExtra('CREATE_USER_ROOMS')) {
             return $this->_getExtra('CREATE_USER_ROOMS');
         }

         return false;
     }

     public function setShouldCreateUserRooms(bool $shouldCreate)
     {
         $this->_addExtra('CREATE_USER_ROOMS', $shouldCreate);
     }

     public function getUserRoomTemplateItem(): ?cs_room_item
     {
         if (isset($this->_userroomTemplateItem)) {
             return $this->_userroomTemplateItem;
         }

         $templateItemId = $this->getUserRoomTemplateID();
         if (isset($templateItemId)) {
             $roomManager = $this->_environment->getRoomManager();
             $templateItem = $roomManager->getItem($templateItemId);
             if (isset($templateItem) and !$templateItem->isDeleted()) {
                 $this->_userroomTemplateItem = $templateItem;
             }

             return $this->_userroomTemplateItem;
         }

         return null;
     }

     public function getUserRoomTemplateID(): ?int
     {
         if ($this->_issetExtra('USERROOM_TEMPLATE_ITEM_ID')) {
             return $this->_getExtra('USERROOM_TEMPLATE_ITEM_ID');
         }

         return null;
     }

     public function setUserRoomTemplateID($roomId)
     {
         $this->_setExtra('USERROOM_TEMPLATE_ITEM_ID', (int) $roomId);
     }

    // ###############################################################
    // mail to moderation, if the project room status changed
    // - delete
    // - undelete
    // - open
    // - archive
    // - template (not implemented yet because flagged function)
    // - untemplate (not implemented yet because flagged function)
    // - reopen
    // - link to and unlink from community room
    // ###############################################################

    public function _sendMailRoomDelete()
    {
        $this->_sendMailRoomDeleteToProjectModeration();
        $this->_sendMailRoomDeleteToCommunityModeration();
        $this->_sendMailRoomDeleteToPortalModeration();
    }

    public function _sendMailRoomUnDelete()
    {
        $this->_sendMailRoomUnDeleteToProjectModeration();
        $this->_sendMailRoomUnDeleteToCommunityModeration();
        $this->_sendMailRoomUnDeleteToPortalModeration();
    }

    public function _sendMailRoomOpen()
    {
        $this->_sendMailRoomOpenToProjectModeration();
        $this->_sendMailRoomOpenToCommunityModeration();
        $this->_sendMailRoomOpenToPortalModeration();
    }

    public function _sendMailRoomArchive()
    {
        $this->_sendMailRoomArchiveToProjectModeration();
        $this->_sendMailRoomArchiveToCommunityModeration();
        $this->_sendMailRoomArchiveToPortalModeration();
    }

    public function _sendMailRoomReOpen()
    {
        $this->_sendMailRoomReOpenToProjectModeration();
        $this->_sendMailRoomReOpenToCommunityModeration();
        $this->_sendMailRoomReOpenToPortalModeration();
    }

    public function _sendMailRoomLink()
    {
        $this->_sendMailRoomLinkToProjectModeration();
        $this->_sendMailRoomLinkToCommunityModeration();
        $this->_sendMailRoomLinkToPortalModeration();
    }

    public function _sendMailRoomLock()
    {
        $this->_sendMailRoomLockToProjectModeration();
        $this->_sendMailRoomLockToCommunityModeration();
        $this->_sendMailRoomLockToPortalModeration();
    }

    public function _sendMailRoomUnlock()
    {
        $this->_sendMailRoomUnlockToProjectModeration();
        $this->_sendMailRoomUnlockToCommunityModeration();
        $this->_sendMailRoomUnlockToPortalModeration();
    }

    public function _sendMailToModeration($room_moderation, $room_change)
    {
        if ('portal' == $room_moderation) {
            $this->_sendMailToModeration2($this->getContextItem(), $room_change);
        } elseif ('project' == $room_moderation) {
            $this->_sendMailToModeration2($this, $room_change);
        } elseif ('community' == $room_moderation) {
            $community_item_array = [];
            $community_itemid_array = [];
            $community_list = $this->getCommunityList();
            if ('link' == $room_change) {
                if ($community_list->isNotEmpty()) {
                    $community_item = $community_list->getFirst();
                    while ($community_item) {
                        $community_item_array[$community_item->getItemID()] = $community_item;
                        $community_itemid_array[] = $community_item->getItemID();
                        $community_item = $community_list->getNext();
                    }
                }
                $add_roomid_array = [];
                if (!empty($this->_new_community_id_array)) {
                    foreach ($this->_new_community_id_array as $item_id) {
                        if (!in_array($item_id, $community_itemid_array)) {
                            $add_roomid_array[] = $item_id;
                        }
                    }
                }
                $room_manager = $this->_environment->getCommunityManager();
                foreach ($add_roomid_array as $room_id) {
                    $community_room_item = $room_manager->getItem($room_id);
                    if (!empty($community_room_item)) {
                        $community_list->add($community_room_item);
                    }
                }
            }
            if ($community_list->isNotEmpty()) {
                $community_item = $community_list->getFirst();
                while ($community_item) {
                    $this->_sendMailToModeration2($community_item, $room_change);
                    $community_item = $community_list->getNext();
                }
            }
        } else {
            trigger_error('lost room moderation', E_USER_WARNING);
        }
    }

     public function _sendMailToModeration2($room_item, $room_change)
     {
         $translator = $this->_environment->getTranslationObject();
         $default_language = 'de';

         /** @var ContainerInterface $symfonyContainer */
         global $symfonyContainer;
         $default_sender_address = $symfonyContainer->getParameter('commsy.email.from');

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

         foreach ($moderator_list as $mod_item) {
             if ('yes' == $mod_item->getOpenRoomWantMail()) {
                 $language = $room_item->getLanguage();
                 if ('user' == $language) {
                     $language = $mod_item->getLanguage();
                     if ('browser' == $language) {
                         $language = $default_language;
                     }
                 }
                 $receiver_array[$language] = $mod_item->getEmail();
                 $moderator_name_array[] = $mod_item->getFullname();
             }
         }

         // now email information
         foreach ($receiver_array as $lang => $email) {
             $save_language = $translator->getSelectedLanguage();
             $translator->setSelectedLanguage($lang);
             $subject = '';
             if ($room_item->isCommunityRoom() or $room_item->isPortal()) {
                 $subject .= html_entity_decode($room_item->getTitle().': ');
             }
             $title = html_entity_decode($this->getTitle());
             if ('open' == $room_change) {
                 $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_OPEN', $title);
             } elseif ('reopen' == $room_change) {
                 $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_REOPEN', $title);
             } elseif ('delete' == $room_change) {
                 $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_DELETE', $title);
             } elseif ('undelete' == $room_change) {
                 $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_UNDELETE', $title);
             } elseif ('archive' == $room_change) {
                 $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_ARCHIVE', $title);
             } elseif ('link' == $room_change) {
                 $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_LINK', $title);
             } elseif ('lock' == $room_change) {
                 $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_LOCK', $title);
             } elseif ('unlock' == $room_change) {
                 $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_UNLOCK', $title);
             }
             $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()),
                 $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
             $body .= LF.LF;
             if ('open' == $room_change) {
                 $body .= $translator->getMessage('PROJECT_MAIL_BODY_OPEN');
                 $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_OPEN');
             } elseif ('reopen' == $room_change) {
                 $body .= $translator->getMessage('PROJECT_MAIL_BODY_REOPEN');
                 $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_REOPEN');
             } elseif ('delete' == $room_change) {
                 $body .= $translator->getMessage('PROJECT_MAIL_BODY_DELETE');
                 $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_DELETE');
             } elseif ('undelete' == $room_change) {
                 $body .= $translator->getMessage('PROJECT_MAIL_BODY_UNDELETE');
                 $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNDELETE');
             } elseif ('archive' == $room_change) {
                 $body .= $translator->getMessage('PROJECT_MAIL_BODY_ARCHIVE');
                 $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_ARCHIVE');
             } elseif ('link' == $room_change) {
                 $body .= $translator->getMessage('PROJECT_MAIL_BODY_LINK');
                 $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_LINK');
             } elseif ('lock' == $room_change) {
                 $body .= $translator->getMessage('PROJECT_MAIL_BODY_LOCK');
                 $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_LOCK');
             } elseif ('unlock' == $room_change) {
                 $body .= $translator->getMessage('PROJECT_MAIL_BODY_UNLOCK');
                 $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNLOCK');
             }
             $body .= LF.LF;

             $editorFullName = !empty($current_user->getFullname()) ? $current_user->getFullname() : '-';
             $body .= $translator->getMessage(
                 'PROJECT_MAIL_BODY_INFORMATION',
                 $title,
                 $editorFullName,
                 $room_change_action
             );

             if ('delete' != $room_change) {
                 $url = $symfonyContainer->get('router')->generate('app_room_home', [
                     'roomId' => $this->getItemID(),
                 ], UrlGeneratorInterface::ABSOLUTE_URL);

                 $body .= LF.$url;
             }

             $body .= LF.LF;
             $body .= $translator->getMessage('PROJECT_MAIL_BODY_COMMUNITIY_ROOMS').LF;

             $community_name_array = [];
             if ('link' != $room_change) {
                 $communityList = $this->getCommunityList();
                 foreach ($communityList as $communityWorkspace) {
                     $community_name_array[] = html_entity_decode((string) $communityWorkspace->getTitle());
                 }
             } else {
                 $room_manager = $this->_environment->getCommunityManager();
                 foreach ($this->_new_community_id_array as $room_id) {
                     $community_room_item = $room_manager->getItem($room_id);
                     if (!empty($community_room_item)) {
                         $temp_title = html_entity_decode($community_room_item->getTitle());
                         if (!in_array($community_room_item->getItemID(), $this->_old_community_id_array)) {
                             $temp_title .= ' ['.$translator->getMessage('COMMON_NEW').']';
                         }
                         $community_name_array[] = $temp_title;
                     }
                 }
             }
             if (!empty($community_name_array)) {
                 $body .= implode(LF, $community_name_array);
             } else {
                 $body .= $translator->getMessage('PROJECT_MAIL_BODY_COMMUNITIY_ROOMS_EMPTY');
             }

             if ('link' == $room_change) {
                 $community_old_name_array = [];
                 foreach ($this->_old_community_id_array as $room_id) {
                     if (!in_array($room_id, $this->_new_community_id_array)) {
                         $community_room_item = $room_manager->getItem($room_id);
                         if (!empty($community_room_item)) {
                             $community_old_name_array[] = html_entity_decode($community_room_item->getTitle());
                         }
                     }
                 }
                 if (!empty($community_old_name_array)) {
                     $body .= LF.LF;
                     $body .= $translator->getMessage('PROJECT_MAIL_BODY_COMMUNITIY_ROOMS_UNLINKED').LF;
                     $body .= implode(LF, $community_old_name_array);
                 }
             }

             $body .= LF.LF;
             $body .= $translator->getMessage('MAIL_SEND_TO', implode(LF, $moderator_name_array));
             $body .= LF.LF;
             if ($room_item->isPortal()) {
                 $body .= $translator->getMessage('MAIL_SEND_WHY_PORTAL', html_entity_decode((string) $room_item->getTitle()));
             } elseif ($room_item->isCommunityRoom()) {
                 $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY', html_entity_decode((string) $room_item->getTitle()));
             } else {
                 $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT', html_entity_decode((string) $room_item->getTitle()));
             }

             // send email
             $from = $translator->getMessage(
                 'SYSTEM_MAIL_MESSAGE',
                 isset($current_portal) ? $current_portal->getTitle() : $room_item->getTitle()
             );
             $replyTo = new Address($current_user->getEmail(), $current_user->getFullName());

             /** @var Mailer $mailer */
             $mailer = $symfonyContainer->get(Mailer::class);
             $mailer->sendRaw($subject, $body, RecipientFactory::createFromRaw($email), $from, [$replyTo]);

             $translator->setSelectedLanguage($save_language);
         }
     }

    // #####################################################
    // FLAG: group room
    // #####################################################

    /** set clock pulses of a room item by id
     * this method sets a list of clock pulses item_ids which are linked to the room.
     *
     * @param array of time ids
     */
    public function setTimeListByID($value)
    {
        parent::setTimeListByID($value);
        if ($this->showGrouproomFunctions()) {
            $grouproom_list = $this->getGroupRoomList();
            if ($grouproom_list->isNotEmpty()) {
                $grouproom_item = $grouproom_list->getFirst();
                while ($grouproom_item) {
                    $grouproom_item->setTimeListByID($value);
                    $grouproom_item->save();
                    unset($grouproom_item);
                    $grouproom_item = $grouproom_list->getNext();
                }
            }
        }
        unset($grouproom_list);
    }

    /** set clock pulses of a room
     * this method sets a list of clock pulses which are linked to the room.
     *
     * @param object cs_list value list of clock pulses (cs_label_item)
     */
    public function setTimeList($value)
    {
        parent::setTimeList($value);
        if ($this->showGrouproomFunctions()) {
            $grouproom_list = $this->getGroupRoomList();
            if ($grouproom_list->isNotEmpty()) {
                $grouproom_item = $grouproom_list->getFirst();
                while ($grouproom_item) {
                    $grouproom_item->setTimeList($value);
                    $grouproom_item->save();
                    unset($grouproom_item);
                    $grouproom_item = $grouproom_list->getNext();
                }
            }
        }
        unset($grouproom_list);
    }

    /**
     * Returns a list of related grouprooms.
     */
    public function getGroupRoomList(): cs_list
    {
        if ($this->getItemID()) {
            $grouproom_manager = $this->_environment->getGroupRoomManager();
            $grouproom_manager->setContextLimit($this->getContextID());
            $grouproom_manager->setProjectRoomLimit($this->getItemID());
            $grouproom_manager->select();

            return $grouproom_manager->get();
        } else {
            return new cs_list();
        }
    }

    public function _setObjectLinkItems($changed_key)
    {
        if (CS_COMMUNITY_TYPE == $changed_key) {
            if (!empty($this->_data[$changed_key])
                 and is_object($this->_data[$changed_key])
            ) {
                $item = $this->_data[$changed_key]->getFirst();
                $community_save_array = [];
                while ($item) {
                    $item->addProjectID2InternalProjectIDArray($this->getItemID());
                    $item->saveWithoutChangingModificationInformation();
                    $community_save_array[] = $item->getItemID();
                    unset($item);
                    $item = $this->_data[$changed_key]->getNext();
                }
                if (!empty($this->_old_community_id_array)) {
                    $community_manager = $this->_environment->getCommunityManager();
                    foreach ($this->_old_community_id_array as $id) {
                        if (!in_array($id, $community_save_array)) {
                            $item = $community_manager->getItem($id);
                            if (!empty($item)) {
                                $item->removeProjectID2InternalProjectIDArray($this->getItemID());
                                $item->saveWithoutChangingModificationInformation();
                            }
                            unset($item);
                        }
                    }
                    unset($community_manager);
                }
            }
        }
        parent::_setObjectLinkItems($changed_key);
    }

    public function _setIDLinkItems($changed_key)
    {
        if (CS_COMMUNITY_TYPE == $changed_key) {
            if (isset($this->_data[$changed_key])
                 and is_array($this->_data[$changed_key])
            ) {
                $community_save_array = [];
                $community_manager = $this->_environment->getCommunityManager();
                foreach ($this->_data[$changed_key] as $key => $id) {
                    if (!empty($id['iid'])) {
                        $id = $id['iid'];
                    }
                    $item = $community_manager->getItem($id);
                    if (!empty($item)) {
                        $item->addProjectID2InternalProjectIDArray($this->getItemID());
                        $community_save_array[] = $id;
                        $item->saveWithoutChangingModificationInformation();
                    }
                    unset($item);
                }
                if (!empty($this->_old_community_id_array)) {
                    foreach ($this->_old_community_id_array as $id) {
                        if (!in_array($id, $community_save_array)) {
                            $item = $community_manager->getItem($id);
                            if (!empty($item)) {
                                $item->removeProjectID2InternalProjectIDArray($this->getItemID());
                                $item->saveWithoutChangingModificationInformation();
                            }
                            unset($item);
                        }
                    }
                }
                unset($community_manager);
            }
        }
        parent::_setIDLinkItems($changed_key);
    }

    public function moveGrouproomsToArchive()
    {
        $retour = true;
        $group_room_manager = $this->_environment->getGroupRoomManager();
        $group_room_manager->setContextLimit($this->getContextID());
        $group_room_manager->setProjectroomLimit($this->getItemID());
        $group_room_manager->select();
        $group_room_list = $group_room_manager->get();
        $group_room_item = $group_room_list->getFirst();
        while ($group_room_item) {
            $retour = $retour and $group_room_item->moveToArchive();
            $group_room_item = $group_room_list->getNext();
        }
        unset($group_room_manager);

        return $retour;
    }
}
