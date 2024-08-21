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

use App\Account\AccountManager;
use App\Account\AccountSetting;
use App\Account\AccountSettingsManager;
use App\Entity\Room;
use App\Event\Workspace\WorkspaceArchivedEvent;
use App\Event\Workspace\WorkspaceLockedEvent;
use App\Event\Workspace\WorkspaceOpenedEvent;
use App\Event\Workspace\WorkspaceUnarchivedEvent;
use App\Event\Workspace\WorkspaceUnlockedEvent;
use App\Mail\Factories\ModerationMessageFactory;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Proxy\PortalProxy;
use Symfony\Component\EventDispatcher\EventDispatcher;

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

        $symfonyContainer = $this->_environment->getSymfonyContainer();

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $symfonyContainer->get('event_dispatcher');

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

            // dispatch event (sending mail to moderation is handled by an event subscriber)
            $eventDispatcher->dispatch(new WorkspaceOpenedEvent($this));
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
                    $eventDispatcher->dispatch(new WorkspaceUnlockedEvent($this));
                } elseif (CS_ROOM_CLOSED == $new_status) {
                    $eventDispatcher->dispatch(new WorkspaceArchivedEvent($this));
                } elseif (CS_ROOM_OPEN == $new_status) {
                    $eventDispatcher->dispatch(new WorkspaceUnarchivedEvent($this));
                } elseif (CS_ROOM_LOCK == $new_status) {
                    $eventDispatcher->dispatch(new WorkspaceLockedEvent($this));
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
        if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_HEADER');
            if (!empty($retour)) {
                return $retour;
            }
        }

        return [];
    }

    public function setUsageInfoFormHeaderArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM_HEADER', $value_array);
        }
    }

    public function getUsageInfoTextArray()
    {
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_TEXT');
            if (!empty($retour)) {
                return $retour;
            }
        }

        return [];
    }

    public function setUsageInfoTextArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_TEXT', $value_array);
        }
    }

    public function getUsageInfoFormTextArray()
    {
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (!empty($retour)) {
                return $retour;
            }
        }

        return [];
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

    public function _sendMailRoomOpenToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'open');
    }

    public function _sendMailRoomArchiveToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'archive');
    }

    public function _sendMailRoomReOpenToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'reopen');
    }

    public function _sendMailRoomLockToGroupModeration(): void
    {
        $this->_sendMailToModeration('group', 'lock');
    }

    public function _sendMailRoomUnlockToGroupModeration()
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
        $symfonyContainer = $this->_environment->getSymfonyContainer();

        if ($room_item instanceof PortalProxy) {
            /** @var AccountManager $accountManager */
            $accountManager = $symfonyContainer->get(AccountManager::class);

            /** @var AccountSettingsManager $settingsManager */
            $settingsManager = $symfonyContainer->get(AccountSettingsManager::class);

            $portalModeratorAccounts = $accountManager->getAccounts($room_item->getId(), ...$room_item->getModeratorList());
            $filteredModeratorAccounts = array_filter(
                iterator_to_array($portalModeratorAccounts),
                function ($account) use ($settingsManager): bool {
                    $setting = $settingsManager->getSetting(
                        $account,
                        AccountSetting::NOTIFY_PORTAL_MOD_ON_WORKSPACE_CHANGE);

                    return $setting['enabled'] === true;
                }
            );

            $recipients = iterator_to_array(RecipientFactory::createFromAccounts(...$filteredModeratorAccounts));
        } else {
            $recipients = RecipientFactory::createModerationRecipients($room_item, fn (cs_user_item $user) =>
                $user->getOpenRoomWantMail()
            );
        }

        /** @var Mailer $mailer */
        $mailer = $symfonyContainer->get(Mailer::class);

        /** @var ModerationMessageFactory $moderationMessageFactory */
        $moderationMessageFactory = $symfonyContainer->get(ModerationMessageFactory::class);
        $message = $moderationMessageFactory->createRoomModerationMessage(
            $this,
            $room_item,
            $room_change
        );
        $mailer->sendMultiple($message, $recipients);
    }

    public function withGrouproomFunctions(): bool
    {
        // grouprooms can not have grouprooms
        return false;
    }
}
