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
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** group room
 * this class implements a group room item.
 */
class cs_grouproom_item extends cs_room_item
{
    private ?\cs_context_item $_project_room_item = null;

    private ?object $_group_item = null;

    /** constructor.
     *
     * @param object environment environment of the commsy project
     */
    public function __construct($environment)
    {
        parent::__construct($environment);

        $this->_type = CS_GROUPROOM_TYPE;

        $this->_default_rubrics_array[0] = CS_ANNOUNCEMENT_TYPE;
        $this->_default_rubrics_array[1] = CS_TODO_TYPE;
        $this->_default_rubrics_array[2] = CS_DATE_TYPE;
        $this->_default_rubrics_array[3] = CS_MATERIAL_TYPE;
        $this->_default_rubrics_array[4] = CS_DISCUSSION_TYPE;
        $this->_default_rubrics_array[5] = CS_USER_TYPE;
        $this->_default_rubrics_array[6] = CS_TOPIC_TYPE;

        $this->defaultHomeConf[CS_ANNOUNCEMENT_TYPE] = 'show';
        $this->defaultHomeConf[CS_TODO_TYPE] = 'show';
        $this->defaultHomeConf[CS_DATE_TYPE] = 'show';
        $this->defaultHomeConf[CS_MATERIAL_TYPE] = 'show';
        $this->defaultHomeConf[CS_DISCUSSION_TYPE] = 'show';
        $this->defaultHomeConf[CS_USER_TYPE] = 'show';
        $this->defaultHomeConf[CS_TOPIC_TYPE] = 'show';
    }

    public function isGroupRoom(): bool
    {
        return true;
    }

    public function isOpenForGuests(): bool
    {
        return false;
    }

    /** get time spread for items on home
     * this method returns the time spread for items on the home of the room.
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
     * this method sets the time spread for items on the home of the room.
     *
     * @param int value the time spread
     */
    public function setTimeSpread($value)
    {
        $this->_addExtra('TIMESPREAD', (int) $value);
    }

    private function _getTaskList()
    {
        $task_manager = $this->_environment->getTaskManager();

        return $task_manager->getTaskListForItem($this);
    }

    /** save group room
     * this method save the group room.
     */
    public function save($save_other = true): void
    {
        $item_id = $this->getItemID();

        $manager = $this->_environment->getGroupRoomManager();
        $current_user = $this->_environment->getCurrentUser();
        if (empty($item_id)) {
            $this->setContactPerson($current_user->getFullName());
        }
        $this->_save($manager);

        if (empty($item_id)) {
            // create first moderator
            $current_user = $this->_environment->getCurrentUser();
            $new_room_user = $current_user->cloneData();

            // Fixed: Picture of Creator was not copied when creating a group-room
            $picture = $current_user->getPicture();
            if (!empty($picture)) {
                $value_array = explode('_', $picture);                 // extracting
                $value_array[0] = 'cid'.$this->getItemID();            // replacing cid
                $new_picture_name = implode('_', $value_array);        // rebuild
                $disc_manager = $this->_environment->getDiscManager();
                $disc_manager->copyImageFromRoomToRoom(                // copy image
                    $picture,
                    $this->getItemID());
                $new_room_user->setPicture($new_picture_name);
            }
            // ~Fixed

            $new_room_user->setContextID($this->getItemID());
            $new_room_user->makeModerator();
            $new_room_user->makeContactPerson();
            if ($this->_environment->getCurrentPortalItem()->getConfigurationHideMailByDefault()) {
                $new_room_user->setEmailNotVisible();
            }
            $new_room_user->save();
            $new_room_user->setCreatorID2ItemID();

            $this->setServiceLinkActive();
            $this->_save($manager);

            // send mail to moderation
            $this->_sendMailRoomOpen();
        } else {
            // keep grouproom & group title in sync
            $group = $this->getLinkedGroupItem();
            if ($group && $group->getTitle() !== $this->getTitle()) {
                $group->setTitle($this->getTitle());
                $group->save(false);
            }

            $new_status = $this->getStatus();
            if ($new_status != $this->_old_status) {
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
        $this->_old_status = $this->getStatus();
        if (empty($item_id)) {
            $this->initTagRootItem();
        }

        $this->updateElastic();
    }

    /** delete project
     * this method deletes the group room.
     */
    public function delete()
    {
        parent::delete();

        // delete associated tasks
        foreach ($this->_getTaskList() as $task) {
            /** @var cs_task_item $task */
            $task->delete();
        }

        // send mail to moderation
        $this->_sendMailRoomDelete();

        $manager = $this->_environment->getProjectManager();
        $this->_delete($manager);

        // delete linked group
        $group = $this->getLinkedGroupItem();
        $group->delete(false);

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
        $project_item = $this->getLinkedProjectItem();
        if ($user_item->isRoot()
             or (isset($project_item)
                  and !empty($project_item)
                  and $user_item->getContextID() == $project_item->getItemID()
                  and ($user_item->isUser()
                        or ($user_item->isGuest()
                             and $project_item->isOpenForGuests()
                        )
                  )
             )
             or ($this->_environment->inPrivateRoom()
               and $this->isUser($user_item)
             )
        ) {
            $access = true;
        } else {
            $access = false;
        }

        return $access;
    }

    public function getLinkedProjectItem()
    {
        $retour = null;
        if (!isset($this->_project_room_item)) {
            if ($this->_issetExtra('PROJECT_ROOM_ITEM_ID')) {
                $item_id = $this->_getExtra('PROJECT_ROOM_ITEM_ID');
                $project_manager = $this->_environment->getProjectManager();
                $project_room_item = $project_manager->getItem($item_id);
                if (isset($project_room_item) and !$project_room_item->isDeleted()) {
                    $this->_project_room_item = $project_room_item;
                }
                $retour = $this->_project_room_item;
            }
        } else {
            $retour = $this->_project_room_item;
        }

        return $retour;
    }

    public function getLinkedProjectItemID()
    {
        $retour = null;
        if ($this->_issetExtra('PROJECT_ROOM_ITEM_ID')) {
            $retour = $this->_getExtra('PROJECT_ROOM_ITEM_ID');
        }

        return $retour;
    }

    public function setLinkedProjectRoomItemID($value)
    {
        $this->_setExtra('PROJECT_ROOM_ITEM_ID', (int) $value);
    }

    public function getLinkedGroupItem(): ?cs_group_item
    {
        $retour = null;
        if (!isset($this->_group_item)) {
            if ($this->_issetExtra('GROUP_ITEM_ID')) {
                $item_id = $this->_getExtra('GROUP_ITEM_ID');
                $manager = $this->_environment->getGroupManager();
                if ($manager->existsItem($item_id)) {
                    $group_item = $manager->getItem($item_id);
                    if (isset($group_item) and !$group_item->isDeleted()) {
                        $this->_group_item = $group_item;
                    }
                    $retour = $this->_group_item;
                } else {
                    $this->_unsetExtra('GROUP_ITEM_ID');
                    $this->saveWithoutChangingModificationInformation();
                    $this->save();
                }
            }
        } else {
            $retour = $this->_group_item;
        }

        return $retour;
    }

    public function getLinkedGroupItemID()
    {
        $retour = null;
        if ($this->_issetExtra('GROUP_ITEM_ID')) {
            $retour = $this->_getExtra('GROUP_ITEM_ID');
        }

        return $retour;
    }

    public function setLinkedGroupItemID($value)
    {
        $this->_setExtra('GROUP_ITEM_ID', (int) $value);
    }

    /** get UsageInfos
     * this method returns the usage infos.
     *
     * @return array
     */
    public function getUsageInfoArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO')) {
            $retour = $this->_getExtra('USAGE_INFO');
            if (empty($retour)) {
                $retour = [];
            }
        } else {
            $retour = [];
        }

        return $retour;
    }

    /** set UsageInfos
     * this method sets the usage infos.
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
     * this method sets the usage infos.
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
     * this method returns the usage infos.
     *
     * @return array
     */
    public function getUsageInfoFormArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_FORM')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM');
            if (empty($retour)) {
                $retour = [];
            }
        } else {
            $retour = [];
        }

        return $retour;
    }

    public function getUsageInfoHeaderArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = [];
            }
        } else {
            $retour = [];
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
                $retour = [];
            }
        } else {
            $retour = [];
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
                $retour = [];
            }
        } else {
            $retour = [];
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
                $retour = [];
            }
        } else {
            $retour = [];
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
                $retour = [];
            }
        } else {
            $retour = [];
        }
        if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
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
                $value_array = [];
            }
        } else {
            $value_array = [];
        }
        $value_array[mb_strtoupper((string) $rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_HEADER', $value_array);
    }

    public function getUsageInfoHeaderForRubricForm($rubric)
    {
        $translator = $this->_environment->getTranslationObject();
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = [];
            }
        } else {
            $retour = [];
        }
        if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
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
                $value_array = [];
            }
        } else {
            $value_array = [];
        }
        $value_array[mb_strtoupper((string) $rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_FORM_HEADER', $value_array);
    }

    public function getUsageInfoTextForRubric($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($retour)) {
                $retour = [];
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
        $this->_addExtra('USAGE_INFO_TEXT', $value_array);
    }

    public function setUsageInfoTextForRubricForm($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($value_array)) {
                $value_array = [];
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

    // ###############################################################
    // mail to moderation, if the group room status changed
    // - delete
    // - undelete
    // - open
    // - archive
    // - reopen
    // - lock
    // - unlock
    // ###############################################################

    private function _sendMailRoomDelete(): void
    {
        $this->_sendMailRoomDeleteToGroupModeration();
        $this->_sendMailRoomDeleteToProjectModeration();
        $this->_sendMailRoomDeleteToPortalModeration();
    }

    private function _sendMailRoomDeleteToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'delete');
    }

    private function _sendMailRoomUnDelete(): void
    {
        $this->_sendMailRoomUnDeleteToGroupModeration();
        $this->_sendMailRoomUnDeleteToProjectModeration();
        $this->_sendMailRoomUnDeleteToPortalModeration();
    }

    private function _sendMailRoomUnDeleteToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'undelete');
    }

    private function _sendMailRoomOpen(): void
    {
        $this->_sendMailRoomOpenToGroupModeration();
        $this->_sendMailRoomOpenToProjectModeration();
        $this->_sendMailRoomOpenToPortalModeration();
    }

    private function _sendMailRoomOpenToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'open');
    }

    private function _sendMailRoomArchive(): void
    {
        $this->_sendMailRoomArchiveToGroupModeration();
        $this->_sendMailRoomArchiveToProjectModeration();
        $this->_sendMailRoomArchiveToPortalModeration();
    }

    private function _sendMailRoomArchiveToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'archive');
    }

    private function _sendMailRoomReOpen(): void
    {
        $this->_sendMailRoomReOpenToGroupModeration();
        $this->_sendMailRoomReOpenToProjectModeration();
        $this->_sendMailRoomReOpenToPortalModeration();
    }

    private function _sendMailRoomReOpenToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'reopen');
    }

    private function _sendMailRoomLock(): void
    {
        $this->_sendMailRoomLockToGroupModeration();
        $this->_sendMailRoomLockToProjectModeration();
        $this->_sendMailRoomLockToPortalModeration();
    }

    private function _sendMailRoomLockToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'lock');
    }

    private function _sendMailRoomUnlock(): void
    {
        $this->_sendMailRoomUnlockToGroupModeration();
        $this->_sendMailRoomUnlockToProjectModeration();
        $this->_sendMailRoomUnlockToPortalModeration();
    }

    private function _sendMailRoomUnlockToGroupModeration()
    {
        $this->_sendMailToModeration('group', 'unlock');
    }

    public function _sendMailToModeration($room_moderation, $room_change): void
    {
        switch ($room_moderation) {
            case 'project':
                $project_room_item = $this->getLinkedProjectItem();
                if ($project_room_item) {
                    $this->_sendMailToModeration2($project_room_item, $room_change);
                }
                break;
            case 'group':
                $this->_sendMailToModeration2($this, $room_change);
                break;
            case 'portal':
                $this->_sendMailToModeration2($this->getContextItem(), $room_change);
                break;
            default:
                throw new UnexpectedValueException('$room_moderation value not handled');
        }
    }

    private function _sendMailToModeration2($room_item, $room_change): void
    {
        $translator = $this->_environment->getTranslationObject();
        $default_language = 'de';

        $current_portal = $this->_environment->getCurrentPortalItem();
        if (!$current_portal) {
            $current_portal = $this->getContextItem();
            if (!empty($current_portal) && $current_portal->isProjectRoom()) {
                $current_portal = $current_portal->getContextItem();
            }
        }

        $current_user = $this->_environment->getCurrentUserItem();
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
                $receiver_array[$language][] = $mod_item->getEmail();
                $moderator_name_array[] = $mod_item->getFullname();
            }
        }

        // now email information
        foreach ($receiver_array as $key => $value) {
            $save_language = $translator->getSelectedLanguage();
            $translator->setSelectedLanguage($key);
            $project_room = $this->getLinkedProjectItem();

            $title = html_entity_decode($this->getTitle());

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
                $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_OPEN');
                $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_OPEN');
            } elseif ('reopen' == $room_change) {
                $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_REOPEN');
                $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_REOPEN');
            } elseif ('delete' == $room_change) {
                $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_DELETE');
                $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_DELETE');
            } elseif ('undelete' == $room_change) {
                $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_UNDELETE');
                $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNDELETE');
            } elseif ('archive' == $room_change) {
                $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_ARCHIVE');
                $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_ARCHIVE');
            } elseif ('lock' == $room_change) {
                $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_LOCK');
                $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_LOCK');
            } elseif ('unlock' == $room_change) {
                $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_UNLOCK');
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

            global $symfonyContainer;

            if ('delete' != $room_change) {
                $router = $symfonyContainer->get('router');

                $group_item = $this->getLinkedGroupItem();
                if (isset($project_room) and !empty($project_room) and !$room_item->isPortal()) {
                    if (isset($group_item) and !empty($group_item)) {
                        $url = $router->generate(
                            'app_group_detail', [
                               'roomId' => $project_room->getItemID(),
                               'itemId' => $group_item->getItemID(),
                            ], UrlGeneratorInterface::ABSOLUTE_URL);
                    } else {
                        $url = $router->generate(
                            'app_room_home', [
                               'roomId' => $project_room->getItemID(),
                            ], UrlGeneratorInterface::ABSOLUTE_URL);
                    }
                } else {
                    $url = $router->generate(
                        'app_room_home', [
                           'roomId' => $this->getItemID(),
                        ], UrlGeneratorInterface::ABSOLUTE_URL);
                }

                $body .= LF.$url;
            }

            $body .= LF.LF;
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_PROJECT_ROOM').LF;

            if (isset($project_room) and !empty($project_room)) {
                $body .= html_entity_decode((string) $project_room->getTitle());
            } else {
                $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_PROJECT_ROOMS_EMPTY');
            }

            $body .= LF.LF;
            $body .= $translator->getMessage('MAIL_SEND_TO', implode(LF, $moderator_name_array));
            $body .= LF.LF;
            if ($room_item->isPortal()) {
                $body .= $translator->getMessage('MAIL_SEND_WHY_PORTAL', html_entity_decode((string) $room_item->getTitle()));
            } elseif ($room_item->isCommunityRoom()) {
                $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY', html_entity_decode((string) $room_item->getTitle()));
            } elseif ($room_item->isProjectRoom()) {
                $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT', html_entity_decode((string) $room_item->getTitle()));
            } else {
                $body .= $translator->getMessage('GROUPROOM_MAIL_SEND_WHY_GROUP', html_entity_decode((string) $room_item->getTitle()));
            }

            // send email
            $fromName = $translator->getMessage('SYSTEM_MAIL_MESSAGE', $current_portal->getTitle());

            $message = (new Email())
                ->subject($subject)
                ->html(nl2br($body))
                ->to(...$value);

            if ($current_user) {
                $email = $current_user->getEmail();
                if (!empty($email)) {
                    $message->replyTo(new Address($email, $current_user->getFullName()));
                }
            }

            /** @var Mailer $mailer */
            $mailer = $symfonyContainer->get(Mailer::class);
            $mailer->sendEmailObject($message, $fromName);

            $translator->setSelectedLanguage($save_language);
            unset($save_language);
        }
    }

    // ######################################################
    // linking calls for extras to the parent project room #
    // ######################################################
    public function withAds(): bool
    {
        // point to linked project item
        $linked_project_item = $this->getLinkedProjectItem();
        if (isset($linked_project_item)) {
            return $linked_project_item->withAds();
        }
    }

    public function withGrouproomFunctions(): bool
    {
        // grouprooms can not have grouprooms
        return false;
    }

    public function withLogArchive(): bool
    {
        // point to linked project item
        $linked_project_item = $this->getLinkedProjectItem();
        if (isset($linked_project_item)) {
            return $linked_project_item->withLogArchive();
        }
    }

    public function withPDAView()
    {
        // point to linked project item
        $linked_project_item = $this->getLinkedProjectItem();
        if (isset($linked_project_item)) {
            return $linked_project_item->withPDAView();
        }
    }

    public function withWikiFunctions()
    {
        // point to linked project item
        $linked_project_item = $this->getLinkedProjectItem();
        if (isset($linked_project_item)) {
            return $linked_project_item->withWikiFunctions();
        }
    }
}
