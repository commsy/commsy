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
use App\Event\Workspace\WorkspaceDeletedEvent;
use App\Event\Workspace\WorkspaceLockedEvent;
use App\Event\Workspace\WorkspaceOpenedEvent;
use App\Event\Workspace\WorkspaceUnarchivedEvent;
use App\Event\Workspace\WorkspaceUndeletedEvent;
use App\Event\Workspace\WorkspaceUnlockedEvent;
use App\Mail\Factories\ModerationMessageFactory;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Proxy\PortalProxy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use App\Room\RoomStatus;

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

   public function isCommunityRoom(): bool
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
   public function save(): void
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

       $symfonyContainer = $this->_environment->getSymfonyContainer();

       /** @var EventDispatcher $eventDispatcher */
       $eventDispatcher = $symfonyContainer->get('event_dispatcher');

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

           // dispatch event (sending mail to moderation is handled by an event subscriber)
           $eventDispatcher->dispatch(new WorkspaceOpenedEvent($this));
       } else {
           $new_status = $this->getStatus();
           if (!empty($this->_old_status)
                and !empty($new_status)
                and $new_status != $this->_old_status) {
               if (RoomStatus::LOCKED->value == $this->_old_status) {
                   $eventDispatcher->dispatch(new WorkspaceUnlockedEvent($this));
               } elseif (RoomStatus::CLOSED->value == $new_status) {
                   $eventDispatcher->dispatch(new WorkspaceArchivedEvent($this));
               } elseif (RoomStatus::OPEN->value == $new_status) {
                   $eventDispatcher->dispatch(new WorkspaceUnarchivedEvent($this));
               } elseif (RoomStatus::LOCKED->value == $new_status) {
                   $eventDispatcher->dispatch(new WorkspaceLockedEvent($this));
               }
           }
       }
       if (empty($item_id)) {
           $this->initTagRootItem();
       }

       $this->updateElastic();
   }

   /** delete community
    * this method deletes the community.
    */
   public function delete()
   {
       parent::delete();

       // dispatch delete event (sending mail to moderation is handled by an event subscriber)
       $symfonyContainer = $this->_environment->getSymfonyContainer();

       /** @var EventDispatcher $eventDispatcher */
       $eventDispatcher = $symfonyContainer->get('event_dispatcher');
       $eventDispatcher->dispatch(new WorkspaceDeletedEvent($this));

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

       // dispatch undelete event (sending mail to moderation is handled by an event subscriber)
       $symfonyContainer = $this->_environment->getSymfonyContainer();

       /** @var EventDispatcher $eventDispatcher */
       $eventDispatcher = $symfonyContainer->get('event_dispatcher');
       $eventDispatcher->dispatch(new WorkspaceUndeletedEvent($this));
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
       // index Seiten
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
       if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
           $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
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
       if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
           $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
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
       if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
           $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
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

   public function _sendMailToModeration($room_moderation, $room_change): void
   {
       if ('portal' == $room_moderation) {
           $this->_sendMailToModeration2($this->getContextItem(), $room_change);
       } elseif ('community' == $room_moderation) {
           $this->_sendMailToModeration2($this, $room_change);
       } else {
           trigger_error('lost room moderation', E_USER_WARNING);
       }
   }

   public function _sendMailToModeration2($room_item, $room_change): void
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
