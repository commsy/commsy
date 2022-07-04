<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/** upper class of the group room item
 */
include_once('classes/cs_room_item.php');

use App\Mail\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** group room
 * this class implements a group room item
 */

class cs_grouproom_item extends cs_room_item {

   private $_project_room_item = NULL;

   private $_group_item = NULL;

   /** constructor
    *
    * @param object environment environment of the commsy project
    */
   public function __construct ($environment) {
      parent::__construct($environment);

      $this->_type = CS_GROUPROOM_TYPE;

      $this->_default_rubrics_array[0] = CS_ANNOUNCEMENT_TYPE;
      $this->_default_rubrics_array[1] = CS_TODO_TYPE;
      $this->_default_rubrics_array[2] = CS_DATE_TYPE;
      $this->_default_rubrics_array[3] = CS_MATERIAL_TYPE;
      $this->_default_rubrics_array[4] = CS_DISCUSSION_TYPE;
      $this->_default_rubrics_array[5] = CS_USER_TYPE;
      $this->_default_rubrics_array[6] = CS_TOPIC_TYPE;

      $this->_default_home_conf_array[CS_ANNOUNCEMENT_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_TODO_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_DATE_TYPE] = 'short';
      $this->_default_home_conf_array[CS_MATERIAL_TYPE] = 'short';
      $this->_default_home_conf_array[CS_DISCUSSION_TYPE] = 'short';
      $this->_default_home_conf_array[CS_USER_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_TOPIC_TYPE] = 'tiny';
   }

   public function isGroupRoom () {
      return true;
   }

   public function isOpenForGuests () {
      return false;
   }

   /** get time spread for items on home
    * this method returns the time spread for items on the home of the room
    *
    * @return integer the time spread
    */
   public function getTimeSpread () {
      $retour = '7';
      if ($this->_issetExtra('TIMESPREAD')) {
         $retour = $this->_getExtra('TIMESPREAD');
      }
      return $retour;
   }

   /** set time spread for items on home
    * this method sets the time spread for items on the home of the room
    *
    * @param integer value the time spread
    */
   public function setTimeSpread ($value) {
      $this->_addExtra('TIMESPREAD',(int)$value);
   }

   private function _getTaskList () {
      $task_manager = $this->_environment->getTaskManager();
      return $task_manager->getTaskListForItem($this);
   }

   /** save group room
    * this method save the group room
    */
   public function save ( $save_other = true) {
      $item_id = $this->getItemID();

      $manager = $this->_environment->getGroupRoomManager();
      $current_user = $this->_environment->getCurrentUser();
      if ( empty($item_id) ) {
         $this->setContactPerson($current_user->getFullName());
      }
      $this->_save($manager);

      if ( empty($item_id) ) {
         // create first moderator
         $current_user = $this->_environment->getCurrentUser();
         $new_room_user = $current_user->cloneData();

         // Fixed: Picture of Creator was not copied when creating a group-room
         $picture = $current_user->getPicture();
         if( !empty($picture) ) {
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
         $this->generateLayoutImages();
      }

      else {
         $new_status = $this->getStatus();
         if ( $new_status != $this->_old_status ) {
            if ( $this->_old_status == CS_ROOM_LOCK ) {
               $this->_sendMailRoomUnlock();
            } elseif ( $new_status == CS_ROOM_CLOSED ) {
               $this->_sendMailRoomArchive();
            } elseif ( $new_status == CS_ROOM_OPEN ) {
               $this->_sendMailRoomReOpen();
            } elseif ( $new_status == CS_ROOM_LOCK ) {
               $this->_sendMailRoomLock();
            }
         }
      }
      $this->_old_status = $this->getStatus();
      if ( empty($item_id) ) {
         $this->initTagRootItem();
      }

      $this->updateElastic();
   }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_room');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('App:Room');

        $this->replaceElasticItem($objectPersister, $repository);
    }

   /** save news item
    * this methode save the news item into the database
    */
   function saveOnlyItem () {
      $this->save(false);
   }

   /** delete project
    * this method deletes the group room
    */
   public function delete () {
      parent::delete();

      // delete associated tasks
      $task_list = $this->_getTaskList();
      $current_task = $task_list->getFirst();
      while ($current_task) {
         $current_task->delete();
         $current_task = $task_list->getNext();
      }

      // send mail to moderation
      $this->_sendMailRoomDelete();

      $manager = $this->_environment->getProjectManager();
      $this->_delete($manager);

      // detach grouproom from group
      $group = $this->getLinkedGroupItem();
      $group->unsetGroupRoomActive();
      $group->unsetGroupRoomItemID();
      $group->saveOnlyItem();

      global $symfonyContainer;
      $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_room');
      $em = $symfonyContainer->get('doctrine.orm.entity_manager');
      $repository = $em->getRepository('App:Room');

       // use zzz repository if room is archived
       if ($this->isArchived()) {
           $repository = $em->getRepository('App:ZzzRoom');
       }
       $this->deleteElasticItem($objectPersister, $repository);
   }

   public function undelete () {
      $manager = $this->_environment->getProjectManager();
      $this->_undelete($manager);

      // send mail to moderation
      $this->_sendMailRoomUnDelete();
   }

   public function setRoomContext ($value) {
      $this->_addExtra('ROOM_CONTEXT',(string)$value);
      if ($value == 'uni'){
         $this->setTimeSpread(7);
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_DATE_TYPE.'_short,'.CS_TODO_TYPE.'_tiny,'.CS_MATERIAL_TYPE.'_tiny,'.CS_DISCUSSION_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_tiny,'.CS_GROUP_TYPE.'_short');
         $retour = array();
         $retour['NAME'] = CS_TOPIC_TYPE;
         $retour['DE']['GENUS']= 'N';
         $retour['DE']['NOMS']= 'Thema';
         $retour['DE']['GENS']= 'Themas';
         $retour['DE']['AKKS']= 'Thema';
         $retour['DE']['DATS']= 'Thema';
         $retour['DE']['NOMPL']= 'Themen';
         $retour['DE']['GENPL']= 'Themen';
         $retour['DE']['AKKPL']= 'Themen';
         $retour['DE']['DATPL']= 'Themen';
         $retour['EN']['GENUS']= 'N';
         $retour['EN']['NOMS']= 'topic';
         $retour['EN']['GENS']= 'topic';
         $retour['EN']['AKKS']= 'topic';
         $retour['EN']['DATS']= 'topic';
         $retour['EN']['NOMPL']= 'topics';
         $retour['EN']['GENPL']= 'topics';
         $retour['EN']['AKKPL']= 'topics';
         $retour['EN']['DATPL']= 'topics';
         $retour['RU']['GENUS']= 'F';
         $retour['RU']['NOMS']= 'tema';
         $retour['RU']['GENS']= 'temei';
         $retour['RU']['AKKS']= 'tema';
         $retour['RU']['DATS']= 'temei';
         $retour['RU']['NOMPL']= 'temele';
         $retour['RU']['GENPL']= 'temelor';
         $retour['RU']['AKKPL']= 'temele';
         $retour['RU']['DATPL']= 'temelor';
         $this->setRubricArray (CS_TOPIC_TYPE, $retour);

         $retour = array();
         $retour['NAME'] = CS_PROJECT_TYPE;
         $retour['DE']['GENUS']= 'M';
         $retour['DE']['NOMS']= 'Projektraum';
         $retour['DE']['GENS']= 'Projektraums';
         $retour['DE']['AKKS']= 'Projektraum';
         $retour['DE']['DATS']= 'Projektraum';
         $retour['DE']['NOMPL']= 'Projekträume';
         $retour['DE']['GENPL']= 'Projekträume';
         $retour['DE']['AKKPL']= 'Projekträume';
         $retour['DE']['DATPL']= 'Projekträumen';
         $retour['EN']['GENUS']= 'M';
         $retour['EN']['NOMS']= 'project room';
         $retour['EN']['GENS']= 'project room';
         $retour['EN']['AKKS']= 'project room';
         $retour['EN']['DATS']= 'project room';
         $retour['EN']['NOMPL']= 'project rooms';
         $retour['EN']['GENPL']= 'project rooms';
         $retour['EN']['AKKPL']= 'project rooms';
         $retour['EN']['DATPL']= 'project rooms';
         $retour['RU']['GENUS']= 'F';
         $retour['RU']['NOMS']= 'sala de proiecte';
         $retour['RU']['GENS']= 'salii de proiecte';
         $retour['RU']['AKKS']= 'sala de proiecte';
         $retour['RU']['DATS']= 'salii de proiecte';
         $retour['RU']['NOMPL']= 'salile de proiecte';
         $retour['RU']['GENPL']= 'salilor de proiecte';
         $retour['RU']['AKKPL']= 'salile de proiecte';
         $retour['RU']['DATPL']= 'salilor de proiecte';
         $this->setRubricArray (CS_PROJECT_TYPE, $retour);
      }

      // school
      elseif ($value == 'school') {
         $this->setTimeSpread(7);
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_DATE_TYPE.'_short,'.CS_TODO_TYPE.'_tiny,'.CS_MATERIAL_TYPE.'_tiny,'.CS_DISCUSSION_TYPE.'_tiny,'.CS_USER_TYPE.'_short,'.CS_GROUP_TYPE.'_short,'.CS_TOPIC_TYPE.'_short');
         $retour['NAME'] = CS_TOPIC_TYPE;
         $retour['DE']['GENUS']= 'N';
         $retour['DE']['NOMS']= 'Fach';
         $retour['DE']['GENS']= 'Fachs';
         $retour['DE']['AKKS']= 'Fach';
         $retour['DE']['DATS']= 'Fach';
         $retour['DE']['NOMPL']= 'Fächer';
         $retour['DE']['GENPL']= 'Fächer';
         $retour['DE']['AKKPL']= 'Fächern';
         $retour['DE']['DATPL']= 'Fächer';
         $retour['EN']['GENUS']= 'N';
         $retour['EN']['NOMS']= 'course';
         $retour['EN']['GENS']= 'course';
         $retour['EN']['AKKS']= 'course';
         $retour['EN']['DATS']= 'course';
         $retour['EN']['NOMPL']= 'courses';
         $retour['EN']['GENPL']= 'courses';
         $retour['EN']['AKKPL']= 'courses';
         $retour['EN']['DATPL']= 'courses';
         $retour['RU']['GENUS']= 'N';
         $retour['RU']['NOMS']= 'materia';
         $retour['RU']['GENS']= 'materiei';
         $retour['RU']['AKKS']= 'materia';
         $retour['RU']['DATS']= 'materiei';
         $retour['RU']['NOMPL']= 'materiile';
         $retour['RU']['GENPL']= 'materiilor';
         $retour['RU']['AKKPL']= 'materiile';
         $retour['RU']['DATPL']= 'materiilor';
         $this->setRubricArray (CS_TOPIC_TYPE, $retour);

         $retour = array();
         $retour['NAME'] = CS_PROJECT_TYPE;
         $retour['DE']['GENUS']= 'M';
         $retour['DE']['NOMS']= 'Klassenraum';
         $retour['DE']['GENS']= 'Klassenraums';
         $retour['DE']['AKKS']= 'Klassenraum';
         $retour['DE']['DATS']= 'Klassenraum';
         $retour['DE']['NOMPL']= 'Klassenräume';
         $retour['DE']['GENPL']= 'Klassenräume';
         $retour['DE']['AKKPL']= 'Klassenräume';
         $retour['DE']['DATPL']= 'Klassenräumen';
         $retour['EN']['GENUS']= 'M';
         $retour['EN']['NOMS']= 'class room';
         $retour['EN']['GENS']= 'class room';
         $retour['EN']['AKKS']= 'class room';
         $retour['EN']['DATS']= 'class room';
         $retour['EN']['NOMPL']= 'class rooms';
         $retour['EN']['GENPL']= 'class rooms';
         $retour['EN']['AKKPL']= 'class rooms';
         $retour['EN']['DATPL']= 'class rooms';
         $retour['RU']['GENUS']= 'F';
         $retour['RU']['NOMS']= 'sala de clasa';
         $retour['RU']['GENS']= 'salii de clasa';
         $retour['RU']['AKKS']= 'sala de clasa';
         $retour['RU']['DATS']= 'salii de clasa';
         $retour['RU']['NOMPL']= 'salile de clasa';
         $retour['RU']['GENPL']= 'salilor de clasa';
         $retour['RU']['AKKPL']= 'salile de clasa';
         $retour['RU']['DATPL']= 'salilor de clasa';
         $this->setRubricArray (CS_PROJECT_TYPE, $retour);

         $retour = array();
      }

      // project (business)
      elseif ($value == 'project') {
         $this->setTimeSpread(30);
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_DATE_TYPE.'_short,'.CS_TODO_TYPE.'_tiny,'.CS_MATERIAL_TYPE.'_short,'.CS_DISCUSSION_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_tiny,'.CS_GROUP_TYPE.'_tiny');
         $retour = array();
         $retour['NAME'] = CS_TOPIC_TYPE;
         $retour['DE']['GENUS']= 'N';
         $retour['DE']['NOMS']= 'Thema';
         $retour['DE']['GENS']= 'Themas';
         $retour['DE']['AKKS']= 'Thema';
         $retour['DE']['DATS']= 'Thema';
         $retour['DE']['NOMPL']= 'Themen';
         $retour['DE']['GENPL']= 'Themen';
         $retour['DE']['AKKPL']= 'Themen';
         $retour['DE']['DATPL']= 'Themen';
         $retour['EN']['GENUS']= 'N';
         $retour['EN']['NOMS']= 'topic';
         $retour['EN']['GENS']= 'topic';
         $retour['EN']['AKKS']= 'topic';
         $retour['EN']['DATS']= 'topic';
         $retour['EN']['NOMPL']= 'topics';
         $retour['EN']['GENPL']= 'topics';
         $retour['EN']['AKKPL']= 'topics';
         $retour['EN']['DATPL']= 'topics';
         $retour['RU']['GENUS']= 'F';
         $retour['RU']['NOMS']= 'tema';
         $retour['RU']['GENS']= 'temei';
         $retour['RU']['AKKS']= 'tema';
         $retour['RU']['DATS']= 'temei';
         $retour['RU']['NOMPL']= 'temele';
         $retour['RU']['GENPL']= 'temelor';
         $retour['RU']['AKKPL']= 'temele';
         $retour['RU']['DATPL']= 'temelor';
         $this->setRubricArray (CS_TOPIC_TYPE, $retour);

          $retour = array();
         $retour['NAME'] = CS_PROJECT_TYPE;
         $retour['DE']['GENUS']= 'M';
         $retour['DE']['NOMS']= 'Projektraum';
         $retour['DE']['GENS']= 'Projektraums';
         $retour['DE']['AKKS']= 'Projektraum';
         $retour['DE']['DATS']= 'Projektraum';
         $retour['DE']['NOMPL']= 'Projekträume';
         $retour['DE']['GENPL']= 'Projekträume';
         $retour['DE']['AKKPL']= 'Projekträume';
         $retour['DE']['DATPL']= 'Projekträumen';
         $retour['EN']['GENUS']= 'M';
         $retour['EN']['NOMS']= 'project room';
         $retour['EN']['GENS']= 'project room';
         $retour['EN']['AKKS']= 'project room';
         $retour['EN']['DATS']= 'project room';
         $retour['EN']['NOMPL']= 'project rooms';
         $retour['EN']['GENPL']= 'project rooms';
         $retour['EN']['AKKPL']= 'project rooms';
         $retour['EN']['DATPL']= 'project rooms';
         $retour['RU']['GENUS']= 'F';
         $retour['RU']['NOMS']= 'sala de proiecte';
         $retour['RU']['GENS']= 'salii de proiecte';
         $retour['RU']['AKKS']= 'sala de proiecte';
         $retour['RU']['DATS']= 'salii de proiecte';
         $retour['RU']['NOMPL']= 'salile de proiecte';
         $retour['RU']['GENPL']= 'salilor de proiecte';
         $retour['RU']['AKKPL']= 'salile de proiecte';
         $retour['RU']['DATPL']= 'salilor de proiecte';
         $this->setRubricArray (CS_PROJECT_TYPE, $retour);
      }
   }

   public function isActive ($start,$end) {
      $activity_border = 9;
      $activity = 0;

      $activity = $activity + $this->getCountItems($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      /*
      $activity = $activity + $this->getCountAnnouncements($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      $activity = $activity + $this->getCountDates($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      $activity = $activity + $this->getCountMaterials($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      $activity = $activity + $this->getCountDiscussions($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      $activity = $activity + $this->getCountTopics($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      $activity = $activity + $this->getCountUsers($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      $activity = $activity + $this->getCountTodos($start,$end);
      if ($activity > $activity_border) {
         return true;
      }
      */

      return false;
   }


   public function maySee ($user_item) {
      $project_item = $this->getLinkedProjectItem();
      if ( $user_item->isRoot()
           or ( isset($project_item)
                and !empty($project_item)
                and $user_item->getContextID() == $project_item->getItemID()
                and ( $user_item->isUser()
                      or ( $user_item->isGuest()
                           and $project_item->isOpenForGuests()
                         )
                    )
              )
           or ( $this->_environment->inPrivateRoom()
                and $this->isUser($user_item)
              )
         ) {
         $access = true;
      } else {
         $access = false;
      }
      return $access;
   }

   public function getLinkedProjectItem () {
      $retour = NULL;
      if ( !isset($this->_project_room_item) ) {
         if ( $this->_issetExtra('PROJECT_ROOM_ITEM_ID') ) {
            $item_id = $this->_getExtra('PROJECT_ROOM_ITEM_ID');
            $project_manager = $this->_environment->getProjectManager();
            $project_room_item = $project_manager->getItem($item_id);
            if ( isset($project_room_item) and !$project_room_item->isDeleted() ) {
               $this->_project_room_item = $project_room_item;
            }
            $retour = $this->_project_room_item;
         }
      } else {
         $retour = $this->_project_room_item;
      }

      return $retour;
   }

   public function getLinkedProjectItemID () {
      $retour = NULL;
      if ( $this->_issetExtra('PROJECT_ROOM_ITEM_ID') ) {
         $retour =  $this->_getExtra('PROJECT_ROOM_ITEM_ID');
      }
      return $retour;
   }

   public function setLinkedProjectRoomItemID ( $value ) {
      $this->_setExtra('PROJECT_ROOM_ITEM_ID',(int)$value);
   }

   public function getLinkedGroupItem () {
      $retour = NULL;
      if ( !isset($this->_group_item) ) {
         if ( $this->_issetExtra('GROUP_ITEM_ID') ) {
            $item_id = $this->_getExtra('GROUP_ITEM_ID');
            $manager = $this->_environment->getGroupManager();
            if ( $manager->existsItem($item_id) ) {
               $group_item = $manager->getItem($item_id);
               if ( isset($group_item) and !$group_item->isDeleted() ) {
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

   public function getLinkedGroupItemID () {
      $retour = NULL;
      if ( $this->_issetExtra('GROUP_ITEM_ID') ) {
         $retour =  $this->_getExtra('GROUP_ITEM_ID');
      }
      return $retour;
   }

   public function setLinkedGroupItemID ( $value ) {
      $this->_setExtra('GROUP_ITEM_ID',(int)$value);
   }

   /** get UsageInfos
    * this method returns the usage infos
    *
    * @return array
    */
   public function getUsageInfoArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO')) {
         $retour = $this->_getExtra('USAGE_INFO');
         if ( empty($retour) ) {
            $retour = array();
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
   public function setUsageInfoArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO',$value_array);
      }
   }

   /** set UsageInfos
    * this method sets the usage infos
    *
    * @param array
    */
   public function setUsageInfoFormArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM',$value_array);
      }
   }

   /** get UsageInfos
    * this method returns the usage infos
    *
    * @return array
    */
   public function getUsageInfoFormArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      return $retour;
   }


   public function getUsageInfoHeaderArray () {
      $retour = NULL;
      if ( $this->_issetExtra('USAGE_INFO_HEADER') ) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      return $retour;
   }

   public function setUsageInfoHeaderArray ($value_array) {
      if ( is_array($value_array) ) {
         $this->_addExtra('USAGE_INFO_HEADER',$value_array);
      }
   }

   public function getUsageInfoFormHeaderArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_HEADER');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      return $retour;
   }

   public function setUsageInfoFormHeaderArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM_HEADER',$value_array);
      }
   }


   public function getUsageInfoTextArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      return $retour;
   }

   public function setUsageInfoTextArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_TEXT',$value_array);
      }
   }

   public function getUsageInfoFormTextArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      return $retour;
   }

   public function setUsageInfoFormTextArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM_TEXT',$value_array);
      }
   }

   public function getUsageInfoHeaderForRubric($rubric){
      $translator = $this->_environment->getTranslationObject();
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
         if ( empty($retour) ) {
            $retour = array();
          }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = $translator->getMessage('USAGE_INFO_HEADER');
      }
      return $retour;
   }

   public function setUsageInfoHeaderForRubric($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $value_array = $this->_getExtra('USAGE_INFO_HEADER');
         if ( empty($value_array) ) {
            $value_array = array();
         }
      } else {
         $value_array = array();
      }
      $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      $this->_addExtra('USAGE_INFO_HEADER',$value_array);
   }

   public function getUsageInfoHeaderForRubricForm($rubric){
      $translator = $this->_environment->getTranslationObject();
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = $translator->getMessage('USAGE_INFO_HEADER');
      }
      return $retour;
   }

   public function setUsageInfoHeaderForRubricForm($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
         $value_array = $this->_getExtra('USAGE_INFO_FORM_HEADER');
         if ( empty($value_array) ) {
            $value_array = array();
         }
      } else {
         $value_array = array();
      }
      $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      $this->_addExtra('USAGE_INFO_FORM_HEADER',$value_array);
   }

   public function getUsageInfoTextForRubric($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      if ( isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
      }
      return $retour;
   }

   public function setUsageInfoTextForRubric($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_TEXT');
         if ( empty($value_array) ) {
            $value_array = array();
         }
      } else {
         $value_array = array();
      }
      if(!empty($string)){
         $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      }else{
         if ( isset($value_array[mb_strtoupper($rubric, 'UTF-8')]) ) {
            unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
         }
      }
      $this->_addExtra('USAGE_INFO_TEXT',$value_array);
   }

   public function setUsageInfoTextForRubricForm($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
         if ( empty($value_array) ) {
            $value_array = array();
         }
      } else {
         $value_array = array();
      }
      if(!empty($string)){
         $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      }else{
         if (isset($value_array[mb_strtoupper($rubric, 'UTF-8')])){
            unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
         }
      }
      $this->_addExtra('USAGE_INFO_FORM_TEXT',$value_array);
   }


   public function getUsageInfoTextForRubricForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
      }
      return $retour;
   }

   public function getUsageInfoTextForRubricInForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
      }
      return $retour;
   }

   public function getUsageInfoTextForRubricFormInForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
      }
      return $retour;
   }

   ################################################################
   # mail to moderation, if the group room status changed
   # - delete
   # - undelete
   # - open
   # - archive
   # - reopen
   # - lock
   # - unlock
   ################################################################

   private function _sendMailRoomDelete () {
      $this->_sendMailRoomDeleteToGroupModeration();
      $this->_sendMailRoomDeleteToProjectModeration();
      $this->_sendMailRoomDeleteToPortalModeration();
   }

   private function _sendMailRoomDeleteToGroupModeration () {
      $this->_sendMailToModeration('group','delete');
   }

   private function _sendMailRoomUnDelete () {
      $this->_sendMailRoomUnDeleteToGroupModeration();
      $this->_sendMailRoomUnDeleteToProjectModeration();
      $this->_sendMailRoomUnDeleteToPortalModeration();
   }

   private function _sendMailRoomUnDeleteToGroupModeration () {
      $this->_sendMailToModeration('group','undelete');
   }

   private function _sendMailRoomOpen () {
      $this->_sendMailRoomOpenToGroupModeration();
      $this->_sendMailRoomOpenToProjectModeration();
      $this->_sendMailRoomOpenToPortalModeration();
   }

   private function _sendMailRoomOpenToGroupModeration () {
      $this->_sendMailToModeration('group','open');
   }

   private function _sendMailRoomArchive () {
      $this->_sendMailRoomArchiveToGroupModeration();
      $this->_sendMailRoomArchiveToProjectModeration();
      $this->_sendMailRoomArchiveToPortalModeration();
   }

   private function _sendMailRoomArchiveToGroupModeration () {
      $this->_sendMailToModeration('group','archive');
   }

   private function _sendMailRoomReOpen () {
      $this->_sendMailRoomReOpenToGroupModeration();
      $this->_sendMailRoomReOpenToProjectModeration();
      $this->_sendMailRoomReOpenToPortalModeration();
   }

   private function _sendMailRoomReOpenToGroupModeration () {
      $this->_sendMailToModeration('group','reopen');
   }

   private function _sendMailRoomLock () {
      $this->_sendMailRoomLockToGroupModeration();
      $this->_sendMailRoomLockToProjectModeration();
      $this->_sendMailRoomLockToPortalModeration();
   }

   private function _sendMailRoomLockToGroupModeration () {
      $this->_sendMailToModeration('group','lock');
   }

   private function _sendMailRoomUnlock () {
      $this->_sendMailRoomUnlockToGroupModeration();
      $this->_sendMailRoomUnlockToProjectModeration();
      $this->_sendMailRoomUnlockToPortalModeration();
   }

   private function _sendMailRoomUnlockToGroupModeration () {
      $this->_sendMailToModeration('group','unlock');
   }

   public function _sendMailToModeration ($room_moderation, $room_change) {
      if ( $room_moderation == 'project' ) {
         $project_room_item = $this->getLinkedProjectItem();
         if ( isset($project_room_item) and !empty($project_room_item) ) {
            $this->_sendMailToModeration2($project_room_item,$room_change);
         }
      } elseif ( $room_moderation == 'group' ) {
         $this->_sendMailToModeration2($this,$room_change);
      } elseif ( $room_moderation == 'portal' ) {
         $this->_sendMailToModeration2($this->getContextItem(),$room_change);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('lost room moderation',E_USER_WARNING);
      }
   }

   private function _sendMailToModeration2 ($room_item, $room_change) {
      $translator = $this->_environment->getTranslationObject();
      $default_language = 'de';
      
      // maybe in archive mode
      $toggle_archive = false;
      if ( $this->_environment->isArchiveMode() ) {
      	$toggle_archive = true;
      	$this->_environment->toggleArchiveMode();
      }
      
      $current_portal = $this->_environment->getCurrentPortalItem();
      if ( empty($current_portal)
           or !$current_portal->isPortal()
         ) {
         $current_portal = $this->getContextItem();
         if ( !empty($current_portal)
              and $current_portal->isProjectRoom()
            ) {
            $current_portal = $current_portal->getContextItem();
         }
      }
      $current_user = $this->_environment->getCurrentUserItem();
      
      if ( $toggle_archive ) {
      	$this->_environment->toggleArchiveMode();
      }
      unset($toggle_archive);
      
      $moderator_list = $room_item->getModeratorList();

      // get moderators
      $receiver_array = array();
      $moderator_name_array = array();

      if ( $moderator_list->isNotEmpty() ) {
         $mod_item = $moderator_list->getFirst();
         while ($mod_item) {
            if ($mod_item->getOpenRoomWantMail() == 'yes') {
               $language = $room_item->getLanguage();
               if ($language == 'user') {
                  $language = $mod_item->getLanguage();
                  if ($language == 'browser') {
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
         $save_language = $translator->getSelectedLanguage();
         $translator->setSelectedLanguage($key);
         $project_room = $this->getLinkedProjectItem();

         $title = html_entity_decode($this->getTitle());

         if ( $room_change == 'open' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_OPEN',$title);
         } elseif ( $room_change == 'reopen' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_REOPEN',$title);
         } elseif ( $room_change == 'delete' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_DELETE',$title);
         } elseif ( $room_change == 'undelete' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_UNDELETE',$title);
         } elseif ( $room_change == 'archive' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_ARCHIVE',$title);
         } elseif ( $room_change == 'link' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_LINK',$title);
         } elseif ( $room_change == 'lock' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_LOCK',$title);
         } elseif ( $room_change == 'unlock' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_UNLOCK',$title);
         }
         $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
         $body .= LF.LF;
         if ( $room_change == 'open' ) {
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_OPEN');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_OPEN');
         } elseif ( $room_change == 'reopen' ) {
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_REOPEN');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_REOPEN');
         } elseif ( $room_change == 'delete' ) {
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_DELETE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_DELETE');
         } elseif ( $room_change == 'undelete' ) {
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_UNDELETE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNDELETE');
         } elseif ( $room_change == 'archive' ) {
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_ARCHIVE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_ARCHIVE');
         } elseif ( $room_change == 'lock' ) {
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_LOCK');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_LOCK');
         } elseif ( $room_change == 'unlock' ) {
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_UNLOCK');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNLOCK');
         }
         $body .= LF.LF;
         $body .= $translator->getMessage('PROJECT_MAIL_BODY_INFORMATION',$title,$current_user->getFullname(),$room_change_action);

         global $symfonyContainer;

         if ( $room_change != 'delete' ) {

            $router = $symfonyContainer->get('router');

            $group_item = $this->getLinkedGroupItem();
            if ( isset($project_room) and !empty($project_room) and !$room_item->isPortal() ) {
               if ( isset($group_item) and !empty($group_item) ) {
                  $url = $router->generate(
                     'app_group_detail', [
                        'roomId' => $project_room->getItemID(),
                        'itemId' => $group_item->getItemID()
                     ], UrlGeneratorInterface::ABSOLUTE_URL);
               } else {
                  $url = $router->generate(
                     'app_room_home', [
                        'roomId' => $project_room->getItemID()
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

         if ( isset($project_room) and !empty($project_room) ) {
            $body .= html_entity_decode($project_room->getTitle());
         } else {
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_PROJECT_ROOMS_EMPTY');
         }

         $body .= LF.LF;
         $body .= $translator->getMessage('MAIL_SEND_TO',implode(LF,$moderator_name_array));
         $body .= LF.LF;
         if ( $room_item->isPortal() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_PORTAL', html_entity_decode($room_item->getTitle()));
         } elseif ( $room_item->isCommunityRoom() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY', html_entity_decode($room_item->getTitle()));
         } elseif ( $room_item->isProjectRoom() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT', html_entity_decode($room_item->getTitle()));
         } else {
            $body .= $translator->getMessage('GROUPROOM_MAIL_SEND_WHY_GROUP', html_entity_decode($room_item->getTitle()));
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

   #######################################################
   # linking calls for extras to the parent project room #
   #######################################################
   public function withAds() {
      // point to linked project item
      $linked_project_item = $this->getLinkedProjectItem();
      if( isset($linked_project_item) ) {
         return $linked_project_item->withAds();
      }
   }

   public function withGrouproomFunctions() {
      // grouprooms can not have grouprooms
      return false;
   }

   public function withLogArchive() {
      // point to linked project item
      $linked_project_item = $this->getLinkedProjectItem();
      if( isset($linked_project_item) ) {
         return $linked_project_item->withLogArchive();
      }
   }

   public function withPDAView() {
      // point to linked project item
      $linked_project_item = $this->getLinkedProjectItem();
      if( isset($linked_project_item) ) {
         return $linked_project_item->withPDAView();
      }
   }

   public function withWikiFunctions() {
      // point to linked project item
      $linked_project_item = $this->getLinkedProjectItem();
      if( isset($linked_project_item) ) {
         return $linked_project_item->withWikiFunctions();
      }
   }

   public function withChatLink(){
      // point to linked project item
      $linked_project_item = $this->getLinkedProjectItem();
      if( isset($linked_project_item) ) {
         return $linked_project_item->withChatLink();
      }
   }

   public function withMaterialImportLink() {
      // point to linked project item
      $linked_project_item = $this->getLinkedProjectItem();
      if( isset($linked_project_item) ) {
         return $linked_project_item->withMaterialImportLink();
      }
   }

   public function withActivatingContent() {
      // point to linked project item
      $linked_project_item = $this->getLinkedProjectItem();
      if( isset($linked_project_item) ) {
         return $linked_project_item->withActivatingContent();
      }
   }
}
?>
