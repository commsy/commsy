<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bl�ssl, Matthias Finck, Dirk Fust, Franz Gr�nig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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
      $this->cs_context_item($environment);
      $this->_type = CS_GROUPROOM_TYPE;

      $this->_default_rubrics_array[0] = CS_ANNOUNCEMENT_TYPE;
      $this->_default_rubrics_array[1] = CS_TODO_TYPE;
      $this->_default_rubrics_array[2] = CS_DATE_TYPE;
      $this->_default_rubrics_array[3] = CS_MATERIAL_TYPE;
      $this->_default_rubrics_array[4] = CS_DISCUSSION_TYPE;
      $this->_default_rubrics_array[5] = CS_USER_TYPE;
      $this->_default_rubrics_array[6] = CS_TOPIC_TYPE;

      $this->_default_home_conf_array[CS_ANNOUNCEMENT_TYPE] = 'none';
      $this->_default_home_conf_array[CS_TODO_TYPE] = 'none';
      $this->_default_home_conf_array[CS_DATE_TYPE] = 'short';
      $this->_default_home_conf_array[CS_MATERIAL_TYPE] = 'none';
      $this->_default_home_conf_array[CS_DISCUSSION_TYPE] = 'short';
      $this->_default_home_conf_array[CS_USER_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_TOPIC_TYPE] = 'none';
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
      $this->_save($manager);

      // sync group item
      if ( $save_other ) {
         $group_item = $this->getLinkedGroupItem();
         if ( isset($group_item) and !empty($group_item) ) {
            $group_item->setTitle($this->getTitle());
            $group_item->setDescription($this->getDescription());
            $logo = $this->getLogoFileName();
            if ( isset($logo) and !empty($logo) ) {
               $disc_manager = $this->_environment->getDiscManager();
               $disc_manager->copyImageFromRoomToRoom($logo,$group_item->getContextID());
               $group_item->setPicture($disc_manager->getLastSavedFileName());
            }
            $group_item->saveOnlyItem();
         }
      }

      if ( empty($item_id) ) {
         // create first moderator
         $current_user = $this->_environment->getCurrentUser();
         $new_room_user = $current_user->cloneData();
         $new_room_user->setContextID($this->getItemID());
         $new_room_user->makeModerator();
         $new_room_user->makeContactPerson();
         $new_room_user->save();
         $new_room_user->setCreatorID2ItemID();
         $this->setServiceLinkActive();
         $this->_save($manager);

         // send mail to moderation
         $this->_sendMailRoomOpen();
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
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_DATE_TYPE.'_short,'.CS_TODO_TYPE.'_none,'.CS_MATERIAL_TYPE.'_tiny,'.CS_DISCUSSION_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_none,'.CS_GROUP_TYPE.'_short');
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
         $retour['DE']['NOMPL']= 'Projektr�ume';
         $retour['DE']['GENPL']= 'Projektr�ume';
         $retour['DE']['AKKPL']= 'Projektr�ume';
         $retour['DE']['DATPL']= 'Projektr�umen';
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
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_DATE_TYPE.'_short,'.CS_TODO_TYPE.'_none,'.CS_MATERIAL_TYPE.'_tiny,'.CS_DISCUSSION_TYPE.'_tiny,'.CS_USER_TYPE.'_short,'.CS_GROUP_TYPE.'_short,'.CS_TOPIC_TYPE.'_short');
         $retour['NAME'] = CS_TOPIC_TYPE;
         $retour['DE']['GENUS']= 'N';
         $retour['DE']['NOMS']= 'Fach';
         $retour['DE']['GENS']= 'Fachs';
         $retour['DE']['AKKS']= 'Fach';
         $retour['DE']['DATS']= 'Fach';
         $retour['DE']['NOMPL']= 'F�cher';
         $retour['DE']['GENPL']= 'F�cher';
         $retour['DE']['AKKPL']= 'F�chern';
         $retour['DE']['DATPL']= 'F�cher';
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
         $retour['DE']['NOMPL']= 'Klassenr�ume';
         $retour['DE']['GENPL']= 'Klassenr�ume';
         $retour['DE']['AKKPL']= 'Klassenr�ume';
         $retour['DE']['DATPL']= 'Klassenr�umen';
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
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_DATE_TYPE.'_short,'.CS_TODO_TYPE.'_none,'.CS_MATERIAL_TYPE.'_short,'.CS_DISCUSSION_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_tiny,'.CS_GROUP_TYPE.'_tiny');
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
         $retour['DE']['NOMPL']= 'Projektr�ume';
         $retour['DE']['GENPL']= 'Projektr�ume';
         $retour['DE']['AKKPL']= 'Projektr�ume';
         $retour['DE']['DATPL']= 'Projektr�umen';
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
            $group_item = $manager->getItem($item_id);
            if ( isset($group_item) and !$group_item->isDeleted() ) {
               $this->_group_item = $group_item;
            }
            $retour = $this->_group_item;
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
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
         if ( empty($retour) ) {
            $retour = array();
          }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
         $retour = getMessage('USAGE_INFO_HEADER');
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
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_HEADER',$value_array);
   }

   public function getUsageInfoHeaderForRubricForm($rubric){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
         if ( empty($retour) ) {
            $retour = array();
         }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
         $retour = getMessage('USAGE_INFO_HEADER');
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
      $value_array[strtoupper($rubric)]=$string;
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
      if ( isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])) {
         $retour = $retour[strtoupper($rubric)];
      } else {
         $link = ahref_curl($this->_environment->getCurrentContextID(),
                            'help',
                            'context',
                            array('module'=>$this->_environment->getCurrentModule(),
                                  'function'=>$this->_environment->getCurrentFunction(),
                                  'context'=>'HELP_COMMON_FORMAT'),
                            getMessage('HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target,
                                                  \'toolbar=no,
                                                    location=no,
                                                    directories=no,
                                                    status=no,
                                                    menubar=no,
                                                    scrollbars=yes,
                                                    resizable=yes,
                                                    copyhistory=yes,
                                                    width=600,
                                                    height=400
                                                  \'
                                                 );
                                     "'
                           );

         $temp = strtoupper($rubric) . '_' . strtoupper($funct);
         $tempMessage      = "";
         switch( $temp )
         {
            case 'ANNOUNCEMENT_INDEX':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOUNCEMENT_INDEX',$link);
               break;
            case 'DATE_INDEX':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DATE_INDEX',$link);
               break;
            case 'DISCUSSION_INDEX':           // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_INDEX',$link);
               break;
            case 'HOME_INDEX':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_HOME_INDEX',$link);
               break;
            case 'HOME_USAGEINFO':             // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_HOME_USAGEINFO_FORM',$link);
               break;
            case 'MATERIAL_INDEX':             // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MATERIAL_INDEX',$link);
               break;
            case 'TODO_INDEX':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_INDEX',$link);
               break;
            case 'TOPIC_INDEX':                // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TOPIC_INDEX',$link);
               break;
            case 'USER_INDEX':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_INDEX',$link);
               break;
            default:
               $tempMessage      = getMessage('COMMON_MESSAGETAG_ERROR')." cs_grouproom_item(804) ";
               break;
         }
         $retour = $tempMessage;
         if ($retour == 'USAGE_INFO_TEXT_PROJECTROOM_FOR_'.$temp or $retour == 'tbd') {
            $retour = getMessage('USAGE_INFO_COMING_SOON');
         }
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
      $value_array[strtoupper($rubric)]=$string;
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
      $value_array[strtoupper($rubric)]=$string;
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
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
    $link = ahref_curl( $this->_environment->getCurrentContextID(),
                        'help',
                        'context',
                        array('module'=>$this->_environment->getCurrentModule(),
                              'function'=>$this->_environment->getCurrentFunction(),
                              'context'=>'HELP_COMMON_FORMAT'),
                        getMessage('HELP_COMMON_FORMAT_TITLE'),
                        '',
                        'help',
                        '',
                        '',
                        'onclick="window.open(href,
                                              target,
                                              \'toolbar=no,
                                                location=no,
                                                directories=no,
                                                status=no,
                                                menubar=no,
                                                scrollbars=yes,
                                                resizable=yes,
                                                copyhistory=yes,
                                                width=600,
                                                height=400
                                              \'
                                             );
                                 "'
                      );

         $temp = strtoupper($rubric) . '_' . strtoupper($funct);
         $tempMessage      = "";
         switch( $temp )
         {
            case 'ANNOUNCEMENT_EDIT':          // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOUNCEMENT_EDIT_FORM',$link);
               break;
            case 'CONFIGURATION_AGB':          // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_AGB_FORM',$link);
               break;
            case 'CONFIGURATION_COLOR':        // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_COLOR_FORM',$link);
               break;
            case 'CONFIGURATION_DISCUSSION':   // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_DISCUSSION_FORM',$link);
               break;
            case 'CONFIGURATION_DATES':        // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_DATES_FORM',$link);
               break;
            case 'CONFIGURATION_DEFAULTS':     // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_DEFAULTS_FORM',$link);
               break;
            case 'CONFIGURATION_HOME':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_HOME_FORM',$link);
               break;
            case 'CONFIGURATION_PREFERENCES':  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_PREFERENCES_FORM',$link);
               break;
            case 'CONFIGURATION_MAIL':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_PREFERENCES_FORM',$link);
               break;
            case 'CONFIGURATION_RUBRIC':       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_RUBRIC_FORM',$link);
               break;
            case 'CONFIGURATION_SERVICE':      // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_SERVICE_FORM',$link);
               break;
            case 'CONFIGURATION_USAGEINFO':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_USAGEINFO_FORM',$link);
               break;
            case 'CONFIGURATION_WIKI':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_WIKI_FORM',$link);
               break;
            case 'DATE_EDIT':                  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DATE_EDIT_FORM',$link);
               break;
            case 'DISCUSSION_EDIT':            // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_EDIT_FORM',$link);
               break;
            case 'MATERIAL_EDIT':              // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MATERIAL_EDIT_FORM',$link);
               break;
            case 'TODO_EDIT':                  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_EDIT_FORM',$link);
               break;
            case 'TOPIC_EDIT':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TOPIC_EDIT_FORM',$link);
               break;
            case 'USER_EDIT':                  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_EDIT_FORM',$link);
               break;
            case 'USER_PREFERENCES':           // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_PREFERENCES_FORM',$link);
               break;
            case 'MAIL_TO_MODERATOR':      //
               $tempMessage = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MAIL_TO_MODERATOR_FORM',$link);
               break;
            case 'TAG_EDIT':      //
               $tempMessage = getMessage('USAGE_INFO_TEXT_ROOM_TAG_EDIT_FORM');
               break;
            case 'CONFIGURATION_TAGS':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_TAGS_FORM',$link);
               break;
            case 'BUZZWORDS_EDIT':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_BUZZWORDS_EDIT_FORM',$link);
               break;
            case 'ACCOUNT_ACTION':             //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_ROOM_ACCOUNT_EDIT_FORM');
               break;
            case 'LANGUAGE_UNUSED':      //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_LANGUAGE_UNUSED_FORM');
               break;
            default:
               $tempMessage      = getMessage('COMMON_MESSAGETAG_ERROR') . " cs_grouproom_item.php(959) ";
               break;
         }
         $retour = $tempMessage;
         if ($retour == 'USAGE_INFO_TEXT_PROJECTROOM_FOR_'.$temp.'_FORM' or $retour == 'tbd') {
            $retour = getMessage('USAGE_INFO_FORM_COMING_SOON');
         }
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
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
    $link = ahref_curl($this->_environment->getCurrentContextID(),
                       'help',
                       'context',
                       array('module'=>$this->_environment->getCurrentModule(),
                             'function'=>$this->_environment->getCurrentFunction(),
                             'context'=>'HELP_COMMON_FORMAT'),
                       getMessage('HELP_COMMON_FORMAT_TITLE'),
                       '',
                       'help',
                       '',
                       '',
                            'onclick="window.open(href,
                                                  target,
                                                  \'toolbar=no,
                                                    location=no,
                                                    directories=no,
                                                    status=no,
                                                    menubar=no,scrollbars=yes,
                                                    resizable=yes,
                                                    copyhistory=yes,
                                                    width=600,
                                                    height=400
                                                  \'
                                                 );
                                     "'
                           );
         $temp = strtoupper($rubric);
         $tempMessage      = "";
         switch( $temp )
         {
            case 'ANNOUNCEMENT':               // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOUNCEMENT_INDEX',$link);
               break;
            case 'DATE':                       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DATE_INDEX',$link);
               break;
            case 'DISCUSSION':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_INDEX',$link);
               break;
            case 'HOME':                       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_HOME_INDEX',$link);
               break;
            case 'MATERIAL':                   // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MATERIAL_INDEX',$link);
               break;
            case 'TODO':                       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_INDEX',$link);
               break;
            case 'TOPIC':                      // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TOPIC_INDEX',$link);
               break;
            case 'USER':                       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_INDEX',$link);
               break;
            default:
               $tempMessage      = getMessage('COMMON_MESSAGETAG_ERROR')." cs_grouproom_item(1043) ";
               break;
         }
         $retour = $tempMessage;
         if ($retour == 'USAGE_INFO_TEXT_PROJECTROOM_FOR_'.$temp.'_INDEX' or $retour == 'tbd'){
            $retour = getMessage('USAGE_INFO_COMING_SOON');
         }
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
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
    $link = ahref_curl($this->_environment->getCurrentContextID(),
                       'help',
                       'context',
                       array('module'=>$this->_environment->getCurrentModule(),
                             'function'=>$this->_environment->getCurrentFunction(),
                             'context'=>'HELP_COMMON_FORMAT'
                            ),
                       getMessage('HELP_COMMON_FORMAT_TITLE'),
                       '',
                       'help',
                       '',
                       '',
                       'onclick="window.open(href,
                                             target,
                                             \'toolbar=no,
                                               location=no,
                                               directories=no,
                                               status=no,
                                               menubar=no,
                                               scrollbars=yes,
                                               resizable=yes,
                                               copyhistory=yes,
                                               width=600,
                                               height=400
                                             \'
                                            );
                                "'
                      );

         $temp = strtoupper($rubric);
         $tempMessage = "";
         switch( $temp )
         {
            case 'ANNOUNCEMENT':               // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOUNCEMENT_EDIT_FORM',$link);
               break;
            case 'DATE':                       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DATE_EDIT_FORM',$link);
               break;
            case 'DISCUSSION':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_EDIT_FORM',$link);
               break;
            case 'MATERIAL':                   // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MATERIAL_EDIT_FORM',$link);
               break;
            case 'TODO':                       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_EDIT_FORM',$link);
               break;
            case 'TOPIC':                      // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TOPIC_EDIT_FORM',$link);
               break;
            case 'USER':                       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_EDIT_FORM',$link);
               break;
            default:
               $tempMessage      = getMessage('COMMON_MESSAGETAG_ERROR')." cs_grouproom_item(1129) ";
               break;
         }
         $retour = $tempMessage;
         if ($retour == 'USAGE_INFO_TEXT_PROJECTROOM_FOR_'.$temp.'_EDIT_FORM' or $retour =='tbd'){
            $retour = getMessage('USAGE_INFO_FORM_COMING_SOON');
         }
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

   protected function _sendMailToModeration ($room_moderation, $room_change) {
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
      $server_item = $this->_environment->getServerItem();
      $default_sender_address = $server_item->getDefaultSenderAddress();
      if ( empty($default_sender_address) ) {
         $default_sender_address = '@';
      }
      $current_portal = $this->_environment->getCurrentPortalItem();
      $current_user = $this->_environment->getCurrentUserItem();
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
         if ( $room_change == 'open' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_OPEN',$this->getTitle());
         } elseif ( $room_change == 'reopen' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_REOPEN',$this->getTitle());
         } elseif ( $room_change == 'delete' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_DELETE',$this->getTitle());
         } elseif ( $room_change == 'undelete' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_UNDELETE',$this->getTitle());
         } elseif ( $room_change == 'archive' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_ARCHIVE',$this->getTitle());
         } elseif ( $room_change == 'link' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_LINK',$this->getTitle());
         } elseif ( $room_change == 'lock' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_LOCK',$this->getTitle());
         } elseif ( $room_change == 'unlock' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_UNLOCK',$this->getTitle());
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
         $body .= $translator->getMessage('PROJECT_MAIL_BODY_INFORMATION',$this->getTitle(),$current_user->getFullname(),$room_change_action);
         if ( $room_change != 'delete' ) {
            $project_room = $this->getLinkedProjectItem();
            $group_item = $this->getLinkedGroupItem();
            if ( isset($project_room) and !empty($project_room) and !$room_item->isPortal() ) {
               if ( isset($group_item) and !empty($group_item) ) {
                  $body .= LF.'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$project_room->getItemID().'&mod=group&fct=detail&iid='.$group_item->getItemID();
               } else {
                  $body .= LF.'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$project_room->getItemID();
               }
            } else {
               $body .= LF.'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->getContextID().'&room_id='.$this->getItemID();
            }
         }

         $body .= LF.LF;
         $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_PROJECT_ROOM').LF;

         $project_room = $this->getLinkedProjectItem();
         if ( isset($project_room) and !empty($project_room) ) {
            $body .= $project_room->getTitle();
         } else {
            $body .= $translator->getMessage('GROUPROOM_MAIL_BODY_PROJECT_ROOMS_EMPTY');
         }

         $body .= LF.LF;
         $body .= $translator->getMessage('MAIL_SEND_TO',implode(LF,$moderator_name_array));
         $body .= LF.LF;
         if ( $room_item->isPortal() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_PORTAL',$room_item->getTitle());
         } elseif ( $room_item->isCommunityRoom() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY',$room_item->getTitle());
         } elseif ( $room_item->isProjectRoom() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT',$room_item->getTitle());
         } else {
            $body .= $translator->getMessage('GROUPROOM_MAIL_SEND_WHY_GROUP',$room_item->getTitle());
         }

         // send email
         include_once('classes/cs_mail.php');
         $mail = new cs_mail();
         $mail->set_to(implode(',',$value));
         $mail->set_from_email($default_sender_address);
         $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_portal->getTitle()));
         $mail->set_reply_to_name($current_user->getFullname());
         $mail->set_reply_to_email($current_user->getEmail());
         $mail->set_subject($subject);
         $mail->set_message($body);
         $mail->send();
         $translator->setSelectedLanguage($save_language);
         unset($save_language);
      }
   }
}
?>
