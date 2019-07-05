<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blösl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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
   function __construct($environment) {
      cs_context_item::__construct($environment);
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
      $this->_default_home_conf_array[CS_TODO_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_DATE_TYPE] = 'short';
      $this->_default_home_conf_array[CS_MATERIAL_TYPE] = 'short';
      $this->_default_home_conf_array[CS_DISCUSSION_TYPE] = 'short';
      $this->_default_home_conf_array[CS_USER_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_GROUP_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_TOPIC_TYPE] = 'tiny';
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

      $current_user = $this->_environment->getCurrentUser();
      $manager = $this->_environment->getProjectManager();
      if ( empty($item_id) ) {
         $this->setServiceLinkActive();
         $this->setContactPerson($current_user->getFullName());
      }
      $this->_save($manager);

      if ( empty($item_id) ) {
         // create first moderator
         $new_room_user = $current_user->cloneData();
         $new_room_user->setContextID($this->getItemID());
         $new_room_user->makeModerator();
         $new_room_user->makeContactPerson();
         $new_room_user->setAccountWantMail('yes');
         $new_room_user->setOpenRoomWantMail('yes');
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
         $group->makeSystemLabel();
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
         $this->generateLayoutImages();

         // sync count room redundancy
         $current_portal_item = $this->getContextItem();
         if ( $current_portal_item->isCountRoomRedundancy() ) {
            $current_portal_item->syncCountProjectRoomRedundancy(true);
         }
         unset($current_portal_item);
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

      $this->updateElastic();
   }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('fos_elastica.object_persister.commsy.room');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('App:Room');

        $this->replaceElasticItem($objectPersister, $repository);
    }

    /**
     * Deletes the project room
     */
    public function delete()
    {
        parent::delete();

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

        if ($this->_environment->inPortal()) {
            $id_manager = $this->_environment->getExternalIdManager();
            $id_manager->deleteByCommSyID($this->getItemID());
            unset($id_manager);
        }

        // sync count room redundancy
        $current_portal_item = $this->getContextItem();
        if ($current_portal_item->isCountRoomRedundancy()) {
            $current_portal_item->syncCountProjectRoomRedundancy(true);
        }
        unset($current_portal_item);

        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('fos_elastica.object_persister.commsy.room');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('App:Room');


        // use zzz repository if room is archived
        if ($this->isArchived()) {
            $repository = $em->getRepository('App:ZzzRoom');
        }

        $this->deleteElasticItem($objectPersister, $repository);
    }

   function undelete () {
      $manager = $this->_environment->getProjectManager();
      $this->_undelete($manager);

      // send mail to moderation
      $this->_sendMailRoomUnDelete();

      // re-insert internal community room links
      $com_list = $this->getCommunityList();
      if ( isset($com_list)
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
      unset($com_list);

      // sync count room redundancy
      $current_portal_item = $this->getContextItem();
      if ( $current_portal_item->isCountRoomRedundancy() ) {
         $current_portal_item->syncCountProjectRoomRedundancy(true);
      }
      unset($current_portal_item);
   }

   function setRoomContext ($value) {
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
        $this->setRubricArray (CS_PROJECT_TYPE, $retour);
      }
      elseif ($value == 'school'){
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
         $this->setRubricArray (CS_PROJECT_TYPE, $retour);

         $retour = array();
         $retour['NAME'] = CS_COMMUNITY_TYPE;
         $retour['DE']['GENUS']= 'M';
         $retour['DE']['NOMS']= 'Schulraum';
         $retour['DE']['GENS']= 'Schulraums';
         $retour['DE']['AKKS']= 'Schulraum';
         $retour['DE']['DATS']= 'Schulraum';
         $retour['DE']['NOMPL']= 'Schulräume';
         $retour['DE']['GENPL']= 'Schulräume';
         $retour['DE']['AKKPL']= 'Schulräume';
         $retour['DE']['DATPL']= 'Schulräumen';
         $retour['EN']['GENUS']= 'M';
         $retour['EN']['NOMS']= 'school workspace';
         $retour['EN']['GENS']= 'school workspace';
         $retour['EN']['AKKS']= 'school workspace';
         $retour['EN']['DATS']= 'school workspace';
         $retour['EN']['NOMPL']= 'school workspaces';
         $retour['EN']['GENPL']= 'school workspaces';
         $retour['EN']['AKKPL']= 'school workspaces';
         $retour['EN']['DATPL']= 'school workspaces';
         $this->setRubricArray (CS_COMMUNITY_TYPE, $retour);

      }
      elseif ($value == 'project'){
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
         $this->setRubricArray (CS_PROJECT_TYPE, $retour);
      }
   }

   function isActive ($start,$end) {
      $activity_border = 9;
      $activity = 0;

      $activity = $activity + $this->getCountItems($start,$end);
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

   function _cronDaily () {
      // you can link daily cron jobs here like this
      // $cron_array[] = $this->_sendEmailNewsLetter();
      $cron_array   = array();
      #$cron_array[] = $this->_cleanLinksToGroupAll();

      $father_cron_array = parent::_cronDaily();
      $cron_array = array_merge($father_cron_array,$cron_array);

      return $cron_array;
   }

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

   #########################################################
   # COMMSY CRON JOBS - END
   #########################################################

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
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      }else{
         $retour = '';
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
      }if(!empty($string)){
         $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      }else{
         if (isset($value_array[mb_strtoupper($rubric, 'UTF-8')])){
            unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
         }
      }
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
      if(!empty($string)){
         $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      }else{
         if (isset($value_array[mb_strtoupper($rubric, 'UTF-8')])){
            unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
         }
      }
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
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour =  $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
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
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
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
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
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
      
      // maybe in archive mode
      $toggle_archive = false;
      if ( $this->_environment->isArchiveMode() ) {
      	$toggle_archive = true;
      	$this->_environment->toggleArchiveMode();
      }

       global $symfonyContainer;
       $default_sender_address = $symfonyContainer->getParameter('commsy.email.from');

      $current_portal = $this->_environment->getCurrentPortalItem();
      if ( empty($current_portal)
           or !$current_portal->isPortal()
         ) {
         $current_portal = $this->getContextItem();
      }
      $current_user = $this->_environment->getCurrentUserItem();
      $fullname = $current_user->getFullname();
      if ( empty($fullname) ) {
         $current_user = $this->_environment->getRootUserItem();
         $email = $current_user->getEmail();
         if ( empty($email)
              and !empty($default_sender_address)
              and $default_sender_address != '@'
            ) {
         	$current_user->setEmail($default_sender_address);
         }	
      }
      
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
         $subject = '';
         if ( $room_item->isCommunityRoom() or $room_item->isPortal() ){
          $subject .= $room_item->getTitle().': ';
         }
         $title = html_entity_decode($this->getTitle());
         if ( $room_change == 'open' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_OPEN',$title);
         } elseif ( $room_change == 'reopen' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_REOPEN',$title);
         } elseif ( $room_change == 'delete' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_DELETE',$title);
         } elseif ( $room_change == 'undelete' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_UNDELETE',$title);
         } elseif ( $room_change == 'archive' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_ARCHIVE',$title);
         } elseif ( $room_change == 'link' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_LINK',$title);
         } elseif ( $room_change == 'lock' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_LOCK',$title);
         } elseif ( $room_change == 'unlock' ) {
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_UNLOCK',$title);
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
         $body .= $translator->getMessage('PROJECT_MAIL_BODY_INFORMATION',$title,$current_user->getFullname(),$room_change_action);
         if ( $room_change != 'delete' ) {

             global $symfonyContainer;

             $url = $symfonyContainer->get('router')->generate(
                 'app_room_home',
                 array('roomId' => $this->getItemID()),
                 true
             );
             $requestStack = $symfonyContainer->get('request_stack');
             $currentRequest = $requestStack->getCurrentRequest();
             if ($currentRequest) {
                 $url = $currentRequest->getSchemeAndHttpHost() . $url;
             }

             $body .= LF.$url;
         	
  			   #$body .= LF.'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->getContextID().'&room_id='.$this->getItemID();
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
            $body .= $translator->getMessage('MAIL_SEND_WHY_PORTAL',html_entity_decode($room_item->getTitle()));
         } elseif ( $room_item->isCommunityRoom() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY',html_entity_decode($room_item->getTitle()));
         } else {
            $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT',html_entity_decode($room_item->getTitle()));
         }

         // send email
         include_once('classes/cs_mail.php');
         $mail = new cs_mail();
         $mail->set_to(implode(',',$value));
         $mail->set_from_email($default_sender_address);
         if (isset($current_portal)){
            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_portal->getTitle()));
         }else{
            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
         }
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

    /**
     * Returns a list of related grouprooms.
     *
     * @return cs_list
     */
   public function getGroupRoomList(): \cs_list
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

   function _setObjectLinkItems($changed_key) {
      if ( $changed_key == CS_COMMUNITY_TYPE ) {
         if ( !empty($this->_data[$changed_key])
              and is_object($this->_data[$changed_key])
            ) {
            $item = $this->_data[$changed_key]->getFirst();
            $community_save_array = array();
            while ( $item ) {
               $item->addProjectID2InternalProjectIDArray($this->getItemID());
               $item->saveWithoutChangingModificationInformation();
               $community_save_array[] = $item->getItemID();
               unset($item);
               $item = $this->_data[$changed_key]->getNext();
            }
            if ( !empty($this->_old_community_id_array) ) {
               $community_manager = $this->_environment->getCommunityManager();
               foreach ( $this->_old_community_id_array as $id ) {
                  if ( !in_array($id,$community_save_array) ) {
                     $item = $community_manager->getItem($id);
                     if ( !empty($item) ) {
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

   function _setIDLinkItems($changed_key) {
      if ( $changed_key == CS_COMMUNITY_TYPE ) {
         if ( isset($this->_data[$changed_key])
              and is_array($this->_data[$changed_key])
            ) {
            $community_save_array = array();
            $community_manager = $this->_environment->getCommunityManager();
            foreach ($this->_data[$changed_key] as $key => $id) {
               if ( !empty($id['iid']) ) {
                  $id = $id['iid'];
               }
               $item = $community_manager->getItem($id);
               if ( !empty($item) ) {
                  $item->addProjectID2InternalProjectIDArray($this->getItemID());
                  $community_save_array[] = $id;
                  $item->saveWithoutChangingModificationInformation();
               }
               unset($item);
            }
            if ( !empty($this->_old_community_id_array) ) {
               foreach ( $this->_old_community_id_array as $id ) {
                  if ( !in_array($id,$community_save_array) ) {
                     $item = $community_manager->getItem($id);
                     if ( !empty($item) ) {
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

   public function moveGrouproomsToArchive () {
      $retour = true;
      $group_room_manager = $this->_environment->getGroupRoomManager();
      $group_room_manager->setContextLimit($this->getContextID());
      $group_room_manager->setProjectroomLimit($this->getItemID());
      $group_room_manager->select();
      $group_room_list = $group_room_manager->get();
      $group_room_item = $group_room_list->getFirst();
      while($group_room_item){
         $retour = $retour and $group_room_item->moveToArchive();
         $group_room_item = $group_room_list->getNext();
      }
      unset($group_room_manager);
      return $retour;
   }

    public function backGrouproomsFromArchive()
    {
        $zzz_group_room_manager = $this->_environment->getZzzGroupRoomManager();
        $zzz_group_room_manager->setContextLimit($this->getContextID());
        $zzz_group_room_manager->setProjectroomLimit($this->getItemID());
        $zzz_group_room_manager->select();
        $group_room_list = $zzz_group_room_manager->get();
        $group_room_item = $group_room_list->getFirst();
        while ($group_room_item) {
            $group_room_item->backFromArchive();
            $group_room_item = $group_room_list->getNext();
        }
        unset($zzz_group_room_manager);
    }
}
?>