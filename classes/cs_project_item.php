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

/** upper class of the project item
 */
include_once('classes/cs_room_item.php');

/** father class for a rooms (project or community)
 * this class implements an abstract room item
 */

class cs_project_item extends cs_room_item {

  /**
   * boolean - are groups available in this project?
   */
   var $_group_support = NULL;

  /**
   * boolean - are topics available in this project?
   */
   var $_topic_support = NULL;

  /**
   * boolean - are materials available in this project?
   */
   var $_material_support = NULL;

   var $_new_community_id_array = NULL;
   var $_old_community_id_array = NULL;

   var $_changed_room_link = false;

   /** constructor
    *
    * @param object environment environment of the commsy project
    */
   function cs_project_item ($environment) {
      $this->cs_context_item($environment);
      $this->_type = CS_PROJECT_TYPE;

      $this->_default_rubrics_array[0] = CS_ANNOUNCEMENT_TYPE;
      $this->_default_rubrics_array[1] = CS_TODO_TYPE;
      $this->_default_rubrics_array[2] = CS_DATE_TYPE;
      $this->_default_rubrics_array[3] = CS_MATERIAL_TYPE;
      $this->_default_rubrics_array[4] = CS_DISCUSSION_TYPE;
      $this->_default_rubrics_array[5] = CS_USER_TYPE;
      $this->_default_rubrics_array[6] = CS_GROUP_TYPE;
      $this->_default_rubrics_array[7] = CS_TOPIC_TYPE;

      $this->_default_home_conf_array[CS_ANNOUNCEMENT_TYPE] = 'short';
      $this->_default_home_conf_array[CS_TODO_TYPE] = 'none';
      $this->_default_home_conf_array[CS_DATE_TYPE] = 'short';
      $this->_default_home_conf_array[CS_MATERIAL_TYPE] = 'short';
      $this->_default_home_conf_array[CS_DISCUSSION_TYPE] = 'short';
      $this->_default_home_conf_array[CS_USER_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_GROUP_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_TOPIC_TYPE] = 'none';
   }

   function isProjectRoom () {
      return true;
   }

   function isOpenForGuests () {
      return false;
   }

   /** get user comment
    * this method returns the users comment: why he or she wants an account
    *
    * @return string user comment
    *
    * @author CommSy Development Group
    */
   function getUserComment () {
      $retour = false;
      if ($this->_issetExtra('USERCOMMENT')) {
         $retour = $this->_getExtra('USERCOMMENT');
      }
      return $retour;
   }

   /** set user comment
    * this method sets the users comment why he or she wants an account
    *
    * @param string value user comment
    *
    * @author CommSy Development Group
    */
   function setUserComment ($value) {
      $this->_addExtra('USERCOMMENT',(string)$value);
   }

   /** get time spread for items on home
    * this method returns the time spread for items on the home of the project project
    *
    * @return integer the time spread
    *
    * @author CommSy Development Group
    */
   function getTimeSpread () {
      $retour = '7';
      if ($this->_issetExtra('TIMESPREAD')) {
         $retour = $this->_getExtra('TIMESPREAD');
      }
      return $retour;
   }

   /** set time spread for items on home
    * this method sets the time spread for items on the home of the project project
    *
    * @param integer value the time spread
    *
    * @author CommSy Development Group
    */
   function setTimeSpread ($value) {
      $this->_addExtra('TIMESPREAD',(int)$value);
   }

   function _getTaskList () {
      $task_manager = $this->_environment->getTaskManager();
      return $task_manager->getTaskListForItem($this);
   }

   /** get communitys of a project
    * this method returns a list of communitys which are linked to the project
    *
    * @return object cs_list a list of communitys (cs_community_item)
    */
   function getCommunityList () {
      return $this->getLinkedItemList(CS_COMMUNITY_TYPE);
   }

  /** set communitys of a project item by item id and version id
   * this method sets a list of community item_ids and version_ids which are linked to the project
   *
   * @param array of community ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   */
   function setCommunityListByID ($value) {
      $community_list_old = $this->getCommunityList();
      $community_array_old = array();
      if ( $community_list_old->isNotEmpty() ) {
         $community_item = $community_list_old->getFirst();
         while ( $community_item ) {
            $community_array_old[] = $community_item->getItemID();
            $community_item = $community_list_old->getNext();
         }
      }
      $this->setLinkedItemsByID(CS_COMMUNITY_TYPE, $value);
      $this->_new_community_id_array = $value;

      # send mail to moderation
      $diff_array1 = array_diff($this->_new_community_id_array,$community_array_old);
      $diff_array2 = array_diff($community_array_old,$this->_new_community_id_array);
      if ( !empty($diff_array1)
           or !empty($diff_array2)
         ) {
         $this->_old_community_id_array = $community_array_old;
         $item_id = $this->getItemID();
         if ( !empty($item_id) ) {
            $this->_changed_room_link = true;
         }
      }
   }

   /** set communitys of a project
    * this method sets a list of communitys which are linked to the project
    *
    * @param string value title of the project
    */
   function setCommunityList ($value) {
      $community_list_old = $this->getCommunityList();
      $community_array_old = array();
      if ( $community_list_old->isNotEmpty() ) {
         $community_item = $community_list_old->getFirst();
         while ( $community_item ) {
            $community_array_old[] = $community_item->getItemID();
            $community_item = $community_list_old->getNext();
         }
      }

      $this->_setObject(CS_COMMUNITY_TYPE, $value, FALSE);
      if ( $value->isNotEmpty() ) {
         $this->_new_community_id_array = array();
         $item = $value->getFirst();
         while ($item) {
            $this->_new_community_id_array[] = $item->getItemID();
            $item = $value->getNext();
         }
      }

      # send mail to moderation
      $diff_array1 = array_diff($this->_new_community_id_array,$community_array_old);
      $diff_array2 = array_diff($community_array_old,$this->_new_community_id_array);
      if ( !empty($diff_array1)
           or !empty($diff_array2)
         ) {
         $this->_old_community_id_array = $community_array_old;
         $item_id = $this->getItemID();
         if ( !empty($item_id) ) {
            $this->_changed_room_link = true;
         }
      }
   }

   /** save project
    * this method save the project
    */
   function save() {
      $item_id = $this->getItemID();

      $manager = $this->_environment->getProjectManager();
      if ( empty($item_id) ) {
         $this->setServiceLinkActive();
      }
      $this->_save($manager);

      if ( empty($item_id) ) {
         // create first moderator
         $current_user = $this->_environment->getCurrentUser();
         $new_room_user = $current_user->cloneData();
         $new_room_user->setContextID($this->getItemID());
         $new_room_user->makeModerator();
         $new_room_user->makeContactPerson();
         $new_room_user->save();
         $new_room_user->setCreatorID2ItemID();
         // save picture in new room
         $picture = $current_user->getPicture();
         if (!empty($picture)) {
            $value_array = explode('_',$picture);
            $value_array[0] = 'cid'.$new_room_user->getContextID();
            $new_picture_name = implode('_',$value_array);

            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->copyImageFromRoomToRoom($picture,$new_room_user->getContextID());
            $new_room_user->setPicture($new_picture_name);
         }

         // save group all
         $group_manager = $this->_environment->getLabelManager();
         $group = $group_manager->getNewItem('group');
         $group->setName('ALL');
         $group->setDescription('GROUP_ALL_DESC');
         $group->setContextID($this->getItemID());
         $group->setCreatorID($new_room_user->getItemID());
         $group->save();

         // link moderator 2 group all
         $new_room_user->setGroupByID($group->getItemID());
         $new_room_user->setChangeModificationOnSave(false);
         $new_room_user->save();

         // send mail to moderation
         $this->_sendMailRoomOpen();
         if ( $this->_changed_room_link ){
            $this->_sendMailRoomLink();
            $this->_changed_room_link = false;
         }
      }

      else {
         $new_status = $this->getStatus();
         $creation_date = $this->getCreationDate();
         $timestamp  = strtotime($creation_date);
         $show_time = true;
         if( ($timestamp+60) <= time() ){
            $show_time = false;
         }
         if ( $new_status != $this->_old_status ) {
            if ( $this->_old_status == CS_ROOM_LOCK ) {
               $this->_sendMailRoomUnlock();
            } elseif ( $new_status == CS_ROOM_CLOSED ) {
               $this->_sendMailRoomArchive();
            } elseif ( $new_status == CS_ROOM_OPEN and !$show_time) {
               $this->_sendMailRoomReOpen();
            } elseif ( $new_status == CS_ROOM_LOCK ) {
               $this->_sendMailRoomLock();
            }
         }
         if ( $this->_changed_room_link ){
            $this->_sendMailRoomLink();
            $this->_changed_room_link = false;
         }
      }
      unset($new_room_user);
      if ( empty($item_id) ) {
         $this->initTagRootItem();
      }
   }

   /** delete project
    * this method deletes the project
    */
   function delete() {
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

      if ( $this->_environment->inPortal() ) {
         $id_manager = $this->_environment->getExternalIdManager();
         $id_manager->deleteByCommSyID($this->getItemID());
         unset($id_manager);
      }
   }

   function undelete () {
      $manager = $this->_environment->getProjectManager();
      $this->_undelete($manager);

      // send mail to moderation
      $this->_sendMailRoomUnDelete();
   }

   function setRoomContext ($value) {
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
         $retour['DE']['NOMPL']= 'Projekträume';
         $retour['DE']['GENPL']= 'Projekträume';
         $retour['DE']['AKKPL']= 'Projekträume';
         $retour['DE']['DATPL']= 'Projekträumen';
         $retour['EN']['GENUS']= 'M';
         $retour['EN']['NOMS']= 'project workspace';
         $retour['EN']['GENS']= 'project workspace';
         $retour['EN']['AKKS']= 'project workspace';
         $retour['EN']['DATS']= 'project workspace';
         $retour['EN']['NOMPL']= 'project workspaces';
         $retour['EN']['GENPL']= 'project workspaces';
         $retour['EN']['AKKPL']= 'project workspaces';
         $retour['EN']['DATPL']= 'project workspaces';
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
      elseif ($value == 'school'){
         $this->setTimeSpread(7);
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_DATE_TYPE.'_short,'.CS_TODO_TYPE.'_none,'.CS_MATERIAL_TYPE.'_tiny,'.CS_DISCUSSION_TYPE.'_tiny,'.CS_USER_TYPE.'_short,'.CS_GROUP_TYPE.'_short,'.CS_TOPIC_TYPE.'_short');
         $retour['NAME'] = CS_TOPIC_TYPE;
          $retour['DE']['GENUS']= 'N';
         $retour['DE']['NOMS']= 'Fach';
          $retour['DE']['GENS']= 'Fachs';
          $retour['DE']['AKKS']= 'Fach';
          $retour['DE']['DATS']= 'Fach';
          $retour['DE']['NOMPL']= 'Fächer';
          $retour['DE']['GENPL']= 'Fächer';
          $retour['DE']['AKKPL']= 'Fächer';
          $retour['DE']['DATPL']= 'Fächern';
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
         $retour['EN']['NOMS']= 'class workspace';
         $retour['EN']['GENS']= 'class workspace';
         $retour['EN']['AKKS']= 'class workspace';
         $retour['EN']['DATS']= 'class workspace';
         $retour['EN']['NOMPL']= 'class workspaces';
         $retour['EN']['GENPL']= 'class workspaces';
         $retour['EN']['AKKPL']= 'class workspaces';
         $retour['EN']['DATPL']= 'class workspaces';
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
      elseif ($value == 'project'){
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
         $retour['DE']['NOMPL']= 'Projekträume';
         $retour['DE']['GENPL']= 'Projekträume';
         $retour['DE']['AKKPL']= 'Projekträume';
         $retour['DE']['DATPL']= 'Projekträumen';
         $retour['EN']['GENUS']= 'M';
         $retour['EN']['NOMS']= 'project workspace';
         $retour['EN']['GENS']= 'project workspace';
         $retour['EN']['AKKS']= 'project workspace';
         $retour['EN']['DATS']= 'project workspace';
         $retour['EN']['NOMPL']= 'project workspaces';
         $retour['EN']['GENPL']= 'project workspaces';
         $retour['EN']['AKKPL']= 'project workspaces';
         $retour['EN']['DATPL']= 'project workspaces';
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

   function isActive ($start,$end) {
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

      $activity = $activity + $this->getCountGroups($start,$end);
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


   function maySee ($user_item) {
      $context_item = $this->_environment->getCurrentContextItem();
      if ( $user_item->isRoot() or
           ( $user_item->getContextID() == $this->_environment->getCurrentContextID()
             and ( $user_item->isGuest() or $user_item->isUser() )
           ) or $context_item->isOpenForGuests()
         ) {
         $access = true;
      } else {
         $access = false;
      }
      return $access;
   }

   #########################################################
   # COMMSY CRON JOBS
   #
   # this cron jobs only works if a daily cron job is
   # configured to run cron.php in /htdocs
   #########################################################

   /** cron weekly, INTERNAL
    * here you can link weekly cron jobs
    *
    * @return array results of running crons
    */
   function _cronWeekly () {
      // you can link daily cron jobs here like this
      // $cron_array[] = $this->_CRON_METHOD();
      $cron_array = array();

      return $cron_array;
   }

   function getUsageInfoTextForRubric($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
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
                            getMessage('COMMON_HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
         $temp = strtoupper($rubric) . '_' . strtoupper($funct);
         $tempMessage      = "";
         switch( $temp )
         {
            case 'ACCOUNT_INDEX':              // getestet, Einstellungen -> Kennungen
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ACCOUNT_INDEX',$link);
               break;
            case 'ANNOUNCEMENT_INDEX':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOUNCEMENT_INDEX',$link);
               break;
            case 'ANNOUNCEMENT_CLIPBOARD_INDEX':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOUNCEMENT_CLIPBOARD_INDEX',$link);
               break;
            case 'DATE_INDEX':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DATE_INDEX',$link);
               break;
            case 'DATE_CLIPBOARD_INDEX':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DATE_CLIPBOARD_INDEX',$link);
               break;
            case 'DISCUSSION_INDEX':           // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_INDEX',$link);
               break;
            case 'DISCUSSION_CLIPBOARD_INDEX':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_CLIPBOARD_INDEX',$link);
               break;
            case 'HOME_INDEX':                 // getestet, Home
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_HOME_INDEX',$link);
               break;
            case 'HOME_USAGEINFO':             // getestet Projektraum: Nutzungshinweise bearbeiten
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_HOME_USAGEINFO',$link);
               break;
            case 'GROUP_INDEX':                // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_GROUP_INDEX',$link);
               break;
            case 'MATERIAL_INDEX':             // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MATERIAL_INDEX',$link);
               break;
            case 'MATERIAL_CLIPBOARD_INDEX':             // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MATERIAL_CLIPBOARD_INDEX',$link);
               break;
            case 'TODO_INDEX':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_INDEX',$link);
               break;
            case 'TODO_CLIPBOARD_INDEX':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_CLIPBOARD_INDEX',$link);
               break;
            case 'TOPIC_INDEX':                // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TOPIC_INDEX',$link);
               break;
            case 'USER_INDEX':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_INDEX',$link);
               break;
            case 'CAMPUS_SEARCH_INDEX':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_COMING_SOON');
               break;
            default:
               $tempMessage      = getMessage('COMMON_MESSAGETAG_ERROR')." cs_project_item(705) ";
               break;
         }
         $retour = $tempMessage;
         if ($retour == 'USAGE_INFO_TEXT_PROJECTROOM_FOR_'.$temp or $retour == 'tbd') {
            $retour = getMessage('USAGE_INFO_COMING_SOON');
         }
      }
      return $retour;
   }

   function setUsageInfoTextForRubric($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_TEXT');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_TEXT',$value_array);
   }

   function setUsageInfoTextForRubricForm($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_FORM_TEXT',$value_array);
   }


   function getUsageInfoTextForRubricForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour =  $retour[strtoupper($rubric)];
      } else {
         $link = ahref_curl($this->_environment->getCurrentContextID(),
                            'help',
                            'context',
                            array('module'=>$this->_environment->getCurrentModule(),
                                  'function'=>$this->_environment->getCurrentFunction(),
                                  'context'=>'HELP_COMMON_FORMAT'),
                            getMessage('COMMON_HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
         $temp = strtoupper($rubric) . '_' . strtoupper($funct);
         $tempMessage      = "";
         // ---> Anmerkung zum Testen: Projektraumeinstellungen und auch alle Projektraum-Rubriken<---
         switch( $temp )
         {
            case 'ACCOUNT_EDIT':               // getestet - Eine Kennung bearbeiten
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ACCOUNT_EDIT_FORM',$link);
               break;
            case 'ANNOUNCEMENT_EDIT':          // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOUNCEMENT_EDIT_FORM',$link);
               break;
            case 'ANNOTATION_EDIT':            // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOTATION_EDIT_FORM',$link);
               break;
            case 'BUZZWORDS_EDIT':             // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_BUZZWORDS_EDIT_FORM',$link);
               break;
            case 'CONFIGURATION_AGB':          // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_AGB_FORM',$link);
               break;
            case 'CONFIGURATION_CHAT':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_CHAT_FORM',$link);
               break;
            case 'CONFIGURATION_COLOR':        // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_COLOR_FORM',$link);
               break;
            case 'CONFIGURATION_DATES':        // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_DATES_FORM',$link);
               break;
            case 'CONFIGURATION_DEFAULTS':     // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_DEFAULTS_FORM',$link);
               break;
            case 'CONFIGURATION_DISCUSSION':   // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_DISCUSSION_FORM',$link);
               break;
            case 'CONFIGURATION_GROUPROOM':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_GROUPROOM_FORM',$link);
               break;
            case 'CONFIGURATION_HOME':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_HOME_FORM',$link);
               break;
            case 'CONFIGURATION_HOMEPAGE':     // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_HOMEPAGE_FORM',$link);
               break;
            case 'CONFIGURATION_MAIL':         // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_MAIL_FORM',$link);
               break;
            case 'CONFIGURATION_PREFERENCES':  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_PREFERENCES_FORM',$link);
               break;
            case 'CONFIGURATION_HTMLTEXTAREA':       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_HTMLTEXTAREA_FORM',$link);
               break;
            case 'CONFIGURATION_RUBRIC':       // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_RUBRIC_FORM',$link);
               break;
            case 'CONFIGURATION_SERVICE':      // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_SERVICE_FORM',$link);
               break;
            case 'CONFIGURATION_USAGEINFO':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_RUBRIC_FORM',$link);
               break;
            case 'CONFIGURATION_WIKI':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_WIKI_FORM',$link);
               break;
            case 'CONFIGURATION_PATH':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_PATH_FORM',$link);
               break;
             case 'CONFIGURATION_TAGS':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_TAGS_FORM',$link);
               break;
             case 'CONFIGURATION_LISTVIEWS':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_LISTVIEWS_FORM',$link);
               break;
            case 'CONFIGURATION_INFORMATIONBOX':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_INFORMATIOBOX_FORM',$link);
               break;
            case 'DATE_EDIT':                  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DATE_EDIT_FORM',$link);
               break;
            case 'DISCUSSION_CLOSE':           // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_CLOSE_FORM',$link);
               break;
            case 'DISCUSSION_EDIT':            // getestet: Projektraum / Diskussionen / Neue Diskussion erstellen
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_EDIT_FORM',$link);
               break;
            case 'DISCUSSION_DETAIL':          // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_DETAIL_FORM',$link);
               break;
            case 'GROUP_EDIT':                 // getestet, Projektraum, Gruppe, Neuen Eintrag erstellen
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_GROUP_EDIT_FORM',$link);
               break;
            case 'GROUP_MAIL':                 // getestet, Projektraum, Gruppe, E-Mail schreiben
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_GROUP_MAIL_FORM',$link);
               break;
            case 'LABELS_EDIT':                // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_LABELS_EDIT_FORM',$link);
               break;
            case 'DISCARTICLE_EDIT':           // getestet, G.-raum, Projektraum, Diskussionsbeitrag bearbeiten
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCARTICLE_EDIT_FORM',$link);
               break;
            case 'MATERIAL_EDIT':              // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MATERIAL_EDIT_FORM',$link);
               break;
            case 'RUBRIC_MAIL':                // getestet: Projektraum / Diskussionen / versenden
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_RUBRIC_MAIL_FORM',$link);
               break;
            case 'SECTION_EDIT':               // getestet: Projektraum / Materialien / <ein Material> / Abschnitt hinzufügen
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_SECTION_EDIT_FORM',$link);
               break;
            case 'TODO_EDIT':                  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_EDIT_FORM',$link);
               break;
            case 'TODO_EDIT':                  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_EDIT_FORM',$link);
               break;
            case 'TOPIC_EDIT':                 // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TOPIC_EDIT_FORM',$link);
               break;
            case 'USER_ACTION':                  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_ACTION_FORM',$link);
               break;
            case 'USER_EDIT':                  // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_EDIT_FORM',$link);
               break;
            case 'USER_CLOSE':                 // getestet, (Teilnahme beenden, ganz unten)
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_CLOSE_FORM',$link);
               break;
            case 'ACCOUNT_CLOSE':                 // getestet, (Teilnahme beenden, ganz unten)
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_CLOSE_FORM',$link);
               break;
            case 'USER_PREFERENCES':           // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_PREFERENCES_FORM',$link);
               break;
            case 'MAIL_TO_MODERATOR':          //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MAIL_TO_MODERATOR_FORM',$link);
               break;
            case 'TAG_EDIT':                   //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_ROOM_TAG_EDIT_FORM');
               break;
            case 'ACCOUNT_ACTION':             //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_ROOM_ACCOUNT_EDIT_FORM');
               break;
            case 'LANGUAGE_UNUSED':      //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_LANGUAGE_UNUSED_FORM');
               break;
            case 'ACCOUNT_STATUS':      //
               $tempMessage      = getMessage('USAGE_INFO_FORM_COMING_SOON');
               break;
            case 'CONFIGURATION_ARCHIVE':      //
               $tempMessage      = getMessage('USAGE_INFO_FORM_COMING_SOON');
               break;
            case 'MAIL_PROCESS':      //
               $tempMessage      = getMessage('USAGE_INFO_FORM_COMING_SOON');
               break;
            default:
               $tempMessage      = getMessage('COMMON_MESSAGETAG_ERROR')." cs_project_item _FORM(".__LINE__.") ";
               break;
         }
         $retour = $tempMessage;
         if ( $retour == 'USAGE_INFO_TEXT_PROJECTROOM_FOR_'.$temp.'_FORM'
              or $retour == 'tbd'
            ) {
            $retour = getMessage('USAGE_INFO_FORM_COMING_SOON');
         }
      }
      return $retour;
   }

   function getUsageInfoTextForRubricInForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
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
                            getMessage('COMMON_HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
         $tempMessage = "";
         switch( strtoupper($rubric) )
         {
            case 'ACCOUNT':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ACCOUNT_INDEX',$link);
               break;
            case 'ANNOUNCEMENT':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOUNCEMENT_INDEX',$link);
               break;
            case 'DATE':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DATE_INDEX',$link);
               break;
            case 'DISCUSSION':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_INDEX',$link);
               break;
            case 'GROUP':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_GROUP_INDEX',$link);
               break;
            case 'HOME':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_HOME_INDEX',$link);
               break;
            case 'MATERIAL':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MATERIAL_INDEX',$link);
               break;
            case 'TODO':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_INDEX',$link);
               break;
            case 'TOPIC':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TOPIC_INDEX',$link);
               break;
            case 'USER':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_INDEX',$link);
               break;
            default:
               $tempMessage      = getMessage('COMMON_MESSAGETAG_ERROR')." cs_project_item.php _INDEX(1002) ";
               break;
         }
         $retour = $tempMessage;
         if ($retour =='USAGE_INFO_TEXT_PROJECTROOM_FOR_'.strtoupper($rubric).'_INDEX' or $retour =='tbd'){
            $retour = getMessage('USAGE_INFO_COMING_SOON');
         }
      }
      return $retour;
   }

   function getUsageInfoTextForRubricFormInForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
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
                            getMessage('COMMON_HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
         $tempMessage = "";
         switch( strtoupper($rubric) )
         {
            case 'ANNOUNCEMENT':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_ANNOUNCEMENT_EDIT_FORM',$link);
               break;
            case 'BUZZWORDS':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_BUZZWORDS_EDIT_FORM',$link);
               break;
            case 'DATE':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DATE_EDIT_FORM',$link);
               break;
            case 'DISCUSSION':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_DISCUSSION_EDIT_FORM',$link);
               break;
            case 'GROUP':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_GROUP_EDIT_FORM',$link);
               break;
            case 'LABELS':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_LABELS_EDIT_FORM',$link);
               break;
            case 'MATERIAL':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_MATERIAL_EDIT_FORM',$link);
               break;
            case 'TODO':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TODO_EDIT_FORM',$link);
               break;
            case 'TOPIC':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_TOPIC_EDIT_FORM',$link);
               break;
            case 'USER':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_USER_EDIT_FORM',$link);
               break;
            default:
               $tempMessage      = getMessage('COMMON_MESSAGETAG_ERROR')." cs_project_item _EDIT_FORM(1095) ";
               break;
         }
         $retour = $tempMessage;
         if ( $retour =='USAGE_INFO_TEXT_PROJECTROOM_FOR_'.strtoupper($rubric).'_EDIT_FORM' or $retour =='tbd' ) {
            $retour = getMessage('USAGE_INFO_FORM_COMING_SOON');
         }
      }
      return $retour;
   }

   ################################################################
   # mail to moderation, if the project room status changed
   # - delete
   # - undelete
   # - open
   # - archive
   # - template (not implemented yet because flagged function)
   # - untemplate (not implemented yet because flagged function)
   # - reopen
   # - link to and unlink from community room
   ################################################################

   function _sendMailRoomDelete () {
      $this->_sendMailRoomDeleteToProjectModeration();
      $this->_sendMailRoomDeleteToCommunityModeration();
      $this->_sendMailRoomDeleteToPortalModeration();
   }

   function _sendMailRoomUnDelete () {
      $this->_sendMailRoomUnDeleteToProjectModeration();
      $this->_sendMailRoomUnDeleteToCommunityModeration();
      $this->_sendMailRoomUnDeleteToPortalModeration();
   }

   function _sendMailRoomOpen () {
      $this->_sendMailRoomOpenToProjectModeration();
      $this->_sendMailRoomOpenToCommunityModeration();
      $this->_sendMailRoomOpenToPortalModeration();
   }

   function _sendMailRoomArchive () {
      $this->_sendMailRoomArchiveToProjectModeration();
      $this->_sendMailRoomArchiveToCommunityModeration();
      $this->_sendMailRoomArchiveToPortalModeration();
   }

   function _sendMailRoomReOpen () {
      $this->_sendMailRoomReOpenToProjectModeration();
      $this->_sendMailRoomReOpenToCommunityModeration();
      $this->_sendMailRoomReOpenToPortalModeration();
   }

   function _sendMailRoomLink () {
      $this->_sendMailRoomLinkToProjectModeration();
      $this->_sendMailRoomLinkToCommunityModeration();
      $this->_sendMailRoomLinkToPortalModeration();
   }

   function _sendMailRoomLock () {
      $this->_sendMailRoomLockToProjectModeration();
      $this->_sendMailRoomLockToCommunityModeration();
      $this->_sendMailRoomLockToPortalModeration();
   }

   function _sendMailRoomUnlock () {
      $this->_sendMailRoomUnlockToProjectModeration();
      $this->_sendMailRoomUnlockToCommunityModeration();
      $this->_sendMailRoomUnlockToPortalModeration();
   }

   function _sendMailToModeration ($room_moderation, $room_change) {
      if ( $room_moderation == 'portal' ) {
         $this->_sendMailToModeration2($this->getContextItem(),$room_change);
      } elseif ( $room_moderation == 'project' ) {
         $this->_sendMailToModeration2($this,$room_change);
      } elseif ( $room_moderation == 'community' ) {
         $community_item_array = array();
         $community_itemid_array = array();
         $community_list = $this->getCommunityList();
         if ( $room_change == 'link' ) {
            if ( $community_list->isNotEmpty() ) {
               $community_item = $community_list->getFirst();
               while ($community_item) {
                  $community_item_array[$community_item->getItemID()] = $community_item;
                  $community_itemid_array[] = $community_item->getItemID();
                  $community_item = $community_list->getNext();
               }
            }
            $add_roomid_array = array();
            if ( !empty($this->_new_community_id_array) ) {
               foreach ($this->_new_community_id_array as $item_id) {
                  if ( !in_array($item_id,$community_itemid_array) ) {
                     $add_roomid_array[] = $item_id;
                  }
               }
            }
            $room_manager = $this->_environment->getCommunityManager();
            foreach ($add_roomid_array as $room_id) {
               $community_room_item = $room_manager->getItem($room_id);
               if ( !empty($community_room_item) ) {
                  $community_list->add($community_room_item);
               }
            }
         }
         if ( $community_list->isNotEmpty() ) {
            $community_item = $community_list->getFirst();
            while ($community_item) {
               $this->_sendMailToModeration2($community_item,$room_change);
               $community_item = $community_list->getNext();
            }
         }
      } else {
         include_once('functions/error_functions.php');
         trigger_error('lost room moderation',E_USER_WARNING);
      }
   }

   function _sendMailToModeration2 ($room_item, $room_change) {
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
         $subject = '';
         if ( $room_item->isCommunityRoom() or $room_item->isPortal() ){
          $subject .= $room_item->getTitle().': ';
         }
         if ( $room_change == 'open' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_OPEN',$this->getTitle());
         } elseif ( $room_change == 'reopen' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_REOPEN',$this->getTitle());
         } elseif ( $room_change == 'delete' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_DELETE',$this->getTitle());
         } elseif ( $room_change == 'undelete' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_UNDELETE',$this->getTitle());
         } elseif ( $room_change == 'archive' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_ARCHIVE',$this->getTitle());
         } elseif ( $room_change == 'link' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_LINK',$this->getTitle());
         } elseif ( $room_change == 'lock' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_LOCK',$this->getTitle());
         } elseif ( $room_change == 'unlock' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_UNLOCK',$this->getTitle());
         }
         $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
         $body .= LF.LF;
         if ( $room_change == 'open' ) {
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_OPEN');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_OPEN');
         } elseif ( $room_change == 'reopen' ) {
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_REOPEN');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_REOPEN');
         } elseif ( $room_change == 'delete' ) {
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_DELETE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_DELETE');
         } elseif ( $room_change == 'undelete' ) {
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_UNDELETE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNDELETE');
         } elseif ( $room_change == 'archive' ) {
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_ARCHIVE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_ARCHIVE');
         } elseif ( $room_change == 'link' ) {
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_LINK');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_LINK');
         } elseif ( $room_change == 'lock' ) {
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_LOCK');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_LOCK');
         } elseif ( $room_change == 'unlock' ) {
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_UNLOCK');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNLOCK');
         }
         $body .= LF.LF;
         $body .= $translator->getMessage('PROJECT_MAIL_BODY_INFORMATION',$this->getTitle(),$current_user->getFullname(),$room_change_action);
         if ( $room_change != 'delete' ) {
            $body .= LF.'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->getContextID().'&room_id='.$this->getItemID();
         }

         $body .= LF.LF;
         $body .= $translator->getMessage('PROJECT_MAIL_BODY_COMMUNITIY_ROOMS').LF;

         $community_name_array = array();
         if ( $room_change != 'link' ) {
             $community_list = $this->getCommunityList();
            if ( $community_list->isNotEmpty() ) {
               $community_item = $community_list->getFirst();
               while ($community_item) {
                  $community_name_array[] = $community_item->getTitle();
                  $community_item = $community_list->getNext();
               }
            }
         } else {
            $room_manager = $this->_environment->getCommunityManager();
            foreach ($this->_new_community_id_array as $room_id) {
               $community_room_item = $room_manager->getItem($room_id);
               if ( !empty($community_room_item) ) {
                  $temp_title = $community_room_item->getTitle();
                  if ( !in_array($community_room_item->getItemID(),$this->_old_community_id_array) ) {
                     $temp_title .= ' ['.$translator->getMessage('COMMON_NEW').']';
                  }
                  $community_name_array[] = $temp_title;
                  unset($temp_title);
               }
            }
         }
         if ( !empty($community_name_array) ) {
            $body .= implode(LF,$community_name_array);
         } else {
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_COMMUNITIY_ROOMS_EMPTY');
         }

         if ( $room_change == 'link' ) {
            $community_old_name_array = array();
            foreach ($this->_old_community_id_array as $room_id) {
               if ( !in_array($room_id,$this->_new_community_id_array) ) {
                  $community_room_item = $room_manager->getItem($room_id);
                  if ( !empty($community_room_item) ) {
                     $temp_title = $community_room_item->getTitle();
                     $community_old_name_array[] = $temp_title;
                     unset($temp_title);
                  }
               }
            }
            if ( !empty($community_old_name_array) ) {
               $body .= LF.LF;
               $body .= $translator->getMessage('PROJECT_MAIL_BODY_COMMUNITIY_ROOMS_UNLINKED').LF;
               $body .= implode(LF,$community_old_name_array);
            }
         }

         $body .= LF.LF;
         $body .= $translator->getMessage('MAIL_SEND_TO',implode(LF,$moderator_name_array));
         $body .= LF.LF;
         if ( $room_item->isPortal() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_PORTAL',$room_item->getTitle());
         } elseif ( $room_item->isCommunityRoom() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY',$room_item->getTitle());
         } else {
            $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT',$room_item->getTitle());
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

   ######################################################
   # FLAG: group room
   ######################################################

   /** set clock pulses of a room item by id
   * this method sets a list of clock pulses item_ids which are linked to the room
   *
   * @param array of time ids
   */
   function setTimeListByID ($value) {
      parent::setTimeListByID($value);
      if ( $this->showGrouproomFunctions() ) {
         $grouproom_list = $this->getGroupRoomList();
         if ( $grouproom_list->isNotEmpty() ) {
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
   * this method sets a list of clock pulses which are linked to the room
   *
   * @param object cs_list value list of clock pulses (cs_label_item)
   */
   function setTimeList($value) {
      parent::setTimeList($value);
      if ( $this->showGrouproomFunctions() ) {
         $grouproom_list = $this->getGroupRoomList();
         if ( $grouproom_list->isNotEmpty() ) {
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

   public function getGroupRoomList () {
      $grouproom_manager = $this->_environment->getGroupRoomManager();
      $grouproom_manager->setContextLimit($this->getContextID());
      $grouproom_manager->setProjectRoomLimit($this->getItemID());
      $grouproom_manager->select();
      return $grouproom_manager->get();
   }
}
?>