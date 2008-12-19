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

/** upper class of the community item
 */
include_once('classes/cs_room_item.php');

/** class for a community
 * this class implements a community item
 */
class cs_community_item extends cs_room_item {

   /**
    * Constructor
    */
   function cs_community_item ($environment) {
      $this->cs_context_item($environment);
      $this->_type = CS_COMMUNITY_TYPE;
      $this->_default_rubrics_array[0] = CS_ANNOUNCEMENT_TYPE;
      $this->_default_rubrics_array[1] = CS_PROJECT_TYPE;
      $this->_default_rubrics_array[3] = CS_DATE_TYPE;
      $this->_default_rubrics_array[4] = CS_MATERIAL_TYPE;
      $this->_default_rubrics_array[5] = CS_DISCUSSION_TYPE;
      $this->_default_rubrics_array[6] = CS_USER_TYPE;
      $this->_default_rubrics_array[7] = CS_TOPIC_TYPE;
      $this->_default_rubrics_array[8] = CS_INSTITUTION_TYPE;
      $this->_default_home_conf_array[CS_ANNOUNCEMENT_TYPE] = 'short';
      $this->_default_home_conf_array[CS_PROJECT_TYPE] = 'short';
      $this->_default_home_conf_array[CS_DATE_TYPE] = 'none';
      $this->_default_home_conf_array[CS_MATERIAL_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_USER_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_TOPIC_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_INSTITUTION_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_DISCUSSION_TYPE] = 'none';
   }

   function isCommunityRoom () {
      return true;
   }

   /** get projects of a project
    * this method returns a list of projects which are linked to the project
    *
    * @return object cs_list a list of projects (cs_project_item)
    *
    * @author CommSy Development Group
    */
   function getProjectList () {
      return $this->getLinkedItemList(CS_PROJECT_TYPE);
   }

   /** get communitys of a project
    * this method returns a list of communitys which are linked to the project
    *
    * @return object cs_list a list of communitys (cs_community_item)
    */
   function getCommunityList () {
      return $this->getLinkedItemList(CS_COMMUNITY_TYPE);
   }


  /** set projects of a project item by item id and version id
   * this method sets a list of project item_ids and version_ids which are linked to the project
   *
   * @param array of project ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   *
   * @author CommSy Development Group
   */
   function setProjectListByID ($value) {
      $project_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $project_array[] = $tmp_data;
      }
      $this->_setValue(CS_PROJECT_TYPE, $project_array, FALSE);
   }

   /** set projects of a project
    * this method sets a list of projects which are linked to the project
    *
    * @param string value title of the project
    *
    * @author CommSy Development Group
    */
   function setProjectList ($value) {
      $this->_setObject(CS_PROJECT_TYPE, $value, FALSE);
   }

   /** save community
    * this method save the community
    */
   function save() {
      $item_id = $this->getItemID();
      $manager = $this->_environment->getCommunityManager();
      if ( empty($item_id) ) {
         $this->setContinuous();
         $this->setServiceLinkActive();
      }

      $this->_save($manager);
      unset($manager);

      if ( empty($item_id) ) {
         // create first moderator
         $current_user = $this->_environment->getCurrentUser();
         $new_room_user = $current_user->cloneData();
         $new_room_user->setContextID($this->getItemID());
         $new_room_user->makeModerator();
         $new_room_user->makeContactPerson();
         $new_room_user->setVisibleToLoggedIn();
         $new_room_user->save();
         $new_room_user->setCreatorID2ItemID();
         $picture = $current_user->getPicture();
         if (!empty($picture)) {
            $value_array = explode('_',$picture);
            $value_array[0] = 'cid'.$new_room_user->getContextID();
            $new_picture_name = implode('_',$value_array);

            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->copyImageFromRoomToRoom($picture,$new_room_user->getContextID());
            unset($disc_manager);

            $new_room_user->setPicture($new_picture_name);
            $new_room_user->save();
         }
         unset($new_room_user);
         unset($current_user);

         // send mail to moderation
         $this->_sendMailRoomOpen();
         $this->generateLayoutImages();
      }

      else {
         $new_status = $this->getStatus();
         if ( !empty($this->_old_status)
              and !empty($new_status)
              and $new_status != $this->_old_status ) {
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
      if ( empty($item_id) ) {
         $this->initTagRootItem();
      }
   }

   /** delete community
    * this method deletes the community
    */
   function delete() {
      parent::delete();

      // send mail to moderation
      $this->_sendMailRoomDelete();

      $manager = $this->_environment->getCommunityManager();
      $this->_delete($manager);
   }

   function undelete () {
      $manager = $this->_environment->getCommunityManager();
      $this->_undelete($manager);

      // send mail to moderation
      $this->_sendMailRoomUnDelete();
   }

   function getTimeSpread () {
      $retour = '90';
      return $retour;
   }

   function setRoomContext ($value) {
      $this->_addExtra('ROOM_CONTEXT',(string)$value);
      if ($value == 'uni'){
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_PROJECT_TYPE.'_short,'.CS_MATERIAL_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_short,'.CS_INSTITUTION_TYPE.'_short,'.CS_DISCUSSION_TYPE.'_none');
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

         $retour['NAME'] = CS_COMMUNITY_TYPE;
        $retour['DE']['GENUS']= 'M';
        $retour['DE']['NOMS']= 'Gemeinschaftsraum';
        $retour['DE']['GENS']= 'Gemeinschaftsraums';
        $retour['DE']['AKKS']= 'Gemeinschaftsraum';
        $retour['DE']['DATS']= 'Gemeinschaftsraum';
        $retour['DE']['NOMPL']= 'Gemeinschaftsr�ume';
        $retour['DE']['GENPL']= 'Gemeinschaftsr�ume';
        $retour['DE']['AKKPL']= 'Gemeinschaftsr�ume';
        $retour['DE']['DATPL']= 'Gemeinschaftsr�umen';
        $retour['EN']['GENUS']= 'M';
        $retour['EN']['NOMS']= 'community workspace';
        $retour['EN']['GENS']= 'community workspace';
        $retour['EN']['AKKS']= 'community workspace';
        $retour['EN']['DATS']= 'community workspace';
        $retour['EN']['NOMPL']= 'community workspaces';
        $retour['EN']['GENPL']= 'community workspaces';
        $retour['EN']['AKKPL']= 'community workspaces';
        $retour['EN']['DATPL']= 'community workspaces';
         $this->setRubricArray (CS_COMMUNITY_TYPE, $retour);
      } elseif ($value == 'school') {
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_PROJECT_TYPE.'_short,'.CS_MATERIAL_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_short,'.CS_INSTITUTION_TYPE.'_none,'.CS_DISCUSSION_TYPE.'_none');
         $retour['NAME'] = CS_TOPIC_TYPE;
        $retour['DE']['GENUS']= 'N';
         $retour['DE']['NOMS']= 'Gremium';
        $retour['DE']['GENS']= 'Gremiums';
        $retour['DE']['AKKS']= 'Gremium';
        $retour['DE']['DATS']= 'Gremium';
        $retour['DE']['NOMPL']= 'Gremien';
        $retour['DE']['GENPL']= 'Gremien';
        $retour['DE']['AKKPL']= 'Gremien';
        $retour['DE']['DATPL']= 'Gremien';
        $retour['EN']['GENUS']= 'N';
        $retour['EN']['NOMS']= 'committee';
        $retour['EN']['GENS']= 'committee';
        $retour['EN']['AKKS']= 'committee';
        $retour['EN']['DATS']= 'committee';
        $retour['EN']['NOMPL']= 'committees';
        $retour['EN']['GENPL']= 'committees';
        $retour['EN']['AKKPL']= 'committees';
        $retour['EN']['DATPL']= 'committees';
        $retour['RU']['GENUS']= 'N';
        $retour['RU']['NOMS']= 'comitetul';
        $retour['RU']['GENS']= 'comitetului';
        $retour['RU']['AKKS']= 'comitetul';
        $retour['RU']['DATS']= 'comitetului';
        $retour['RU']['NOMPL']= 'comitetele';
        $retour['RU']['GENPL']= 'comitetelor';
        $retour['RU']['AKKPL']= 'comitetele';
        $retour['RU']['DATPL']= 'comitetelor';
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

         $retour['NAME'] = CS_COMMUNITY_TYPE;
        $retour['DE']['GENUS']= 'M';
        $retour['DE']['NOMS']= 'Schulraum';
        $retour['DE']['GENS']= 'Schulraums';
        $retour['DE']['AKKS']= 'Schulraum';
        $retour['DE']['DATS']= 'Schulraum';
        $retour['DE']['NOMPL']= 'Schulr�ume';
        $retour['DE']['GENPL']= 'Schulr�ume';
        $retour['DE']['AKKPL']= 'Schulr�ume';
        $retour['DE']['DATPL']= 'Schulr�umen';
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
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_PROJECT_TYPE.'_short,'.CS_MATERIAL_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_short,'.CS_INSTITUTION_TYPE.'_none,'.CS_DISCUSSION_TYPE.'_none');
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

         $retour['NAME'] = CS_COMMUNITY_TYPE;
        $retour['DE']['GENUS']= 'M';
        $retour['DE']['NOMS']= 'Gemeinschaftsraum';
        $retour['DE']['GENS']= 'Gemeinschaftsraums';
        $retour['DE']['AKKS']= 'Gemeinschaftsraum';
        $retour['DE']['DATS']= 'Gemeinschaftsraum';
        $retour['DE']['NOMPL']= 'Gemeinschaftsr�ume';
        $retour['DE']['GENPL']= 'Gemeinschaftsr�ume';
        $retour['DE']['AKKPL']= 'Gemeinschaftsr�ume';
        $retour['DE']['DATPL']= 'Gemeinschaftsr�umen';
        $retour['EN']['GENUS']= 'M';
        $retour['EN']['NOMS']= 'community workspace';
        $retour['EN']['GENS']= 'community workspace';
        $retour['EN']['AKKS']= 'community workspace';
        $retour['EN']['DATS']= 'community workspace';
        $retour['EN']['NOMPL']= 'community workspaces';
        $retour['EN']['GENPL']= 'community workspaces';
        $retour['EN']['AKKPL']= 'community workspaces';
        $retour['EN']['DATPL']= 'community workspaces';
         $this->setRubricArray (CS_COMMUNITY_TYPE, $retour);
      }
   }

   ###################################################
   # time methods
   ###################################################

   function _getShowTime () {
      $retour = '';
      if ($this->_issetExtra('TIME_SHOW')) {
         $retour = $this->_getExtra('TIME_SHOW');
      }
      return $retour;
   }

   function showTime () {
      $retour = true;
      $value = $this->_getShowTime();
      if ($value == -1) {
        $retour = false;
      }
      return $retour;
   }

   function setShowTime () {
      $this->_addExtra('TIME_SHOW',1);
   }

   function setNotShowTime () {
      $this->_addExtra('TIME_SHOW',-1);
   }

   ###########################################################
   # some function to get lists of items in one community room
   ###########################################################

   function getUsedProjectRoomList ($start, $end) {
      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getContextID());
      $room_manager->setCommunityRoomLimit($this->getItemID());
      $room_list = $room_manager->getUsedProjectRooms($start,$end);

      return $room_list;
   }

   function getActiveProjectRoomList ($start, $end) {
      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getContextID());
      $room_manager->setCommunityRoomLimit($this->getItemID());
      $room_list = $room_manager->getActiveProjectRooms($start,$end);

      return $room_list;
   }

   function getProjectRoomList () {
      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getContextID());
      $room_manager->setCommunityRoomLimit($this->getItemID());
      $room_manager->select();
      $room_list = $room_manager->get();

      return $room_list;
   }

   function isActive ($start,$end) {
      $activity_border = 9;
      $activity = 0;

      $activity = $activity + $this->getCountAnnouncements($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      $activity = $activity + $this->getCountUsers($start,$end);
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

      $activity = $activity + $this->getCountInstitutions($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      $activity = $activity + $this->getCountTopics($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      $activity = $activity + $this->getCountProjects($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      return false;
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
      // index Seiten
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
                            'help', 'context',
                            array('module'=>$this->_environment->getCurrentModule(),
                                  'function'=>$this->_environment->getCurrentFunction(),
                                  'context'=>'HELP_COMMON_FORMAT'),
                            getMessage('COMMON_HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
         $temp_rub_func = strtoupper($rubric) . '_' . strtoupper($funct);
         $tempMessage = "";
         switch( $temp_rub_func )
         {
            case 'ACCOUNT_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ACCOUNT_INDEX',$link);
               break;
            case 'ACCOUNT_STATUS':      //
               $tempMessage      = getMessage('USAGE_INFO_FORM_COMING_SOON');
               break;
            case 'ANNOUNCEMENT_CLIPBOARD_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ANNOUNCEMENT_CLIPBOARD_INDEX',$link);
               break;
            case 'ANNOUNCEMENT_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ANNOUNCEMENT_INDEX',$link);
               break;
            case 'DATE_CLIPBOARD_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DATE_CLIPBOARD_INDEX',$link);
               break;
            case 'DATE_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DATE_INDEX',$link);
               break;
            case 'DISCUSSION_CLIPBOARD_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DISCUSSION_CLIPBOARD_INDEX',$link);
               break;
            case 'DISCUSSION_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DISCUSSION_INDEX',$link);
               break;
            case 'HOME_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_HOME_INDEX',$link);
               break;
            case 'HOME_USAGEINFO':        // getestet - Nutzungshinweise bearbeiten als Raum-Moderator
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_HOME_USAGEINFO',$link);
               break;
            case 'INSTITUTION_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_INSTITUTION_INDEX',$link);
               break;
            case 'MATERIAL_CLIPBOARD_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_MATERIAL_CLIPBOARD_INDEX',$link);
               break;
            case 'MATERIAL_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_MATERIAL_INDEX',$link);
               break;
            case 'PROJECT_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_PROJECT_INDEX',$link);
               break;
            case 'TODO_CLIPBOARD_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TODO_CLIPBOARD_INDEX',$link);
               break;
            case 'TODO_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TODO_INDEX',$link);
               break;
            case 'TOPIC_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TOPIC_INDEX',$link);
               break;
            case 'USER_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_USER_INDEX',$link);
               break;
            default:
               $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR'.' cs_community_item(679) ',$link);
               break;
         }
         $retour = $tempMessage;

         if ($retour =='USAGE_INFO_TEXT_COMMUNITYROOM_FOR_'.strtoupper($rubric).'_'.strtoupper($funct) or $retour =='tbd'){
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
      // formular
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
         $temp_rub_func = strtoupper($rubric).'_'.strtoupper($funct);
         $tempMessage = "";
         switch( $temp_rub_func )
         {
            case 'ACCOUNT_EDIT':          // Raumeinstellungen, Kennungen, <ein Benutzer>, Bearbeiten
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ACCOUNT_EDIT_FORM', $link);
               break;
            case 'ACCOUNT_PREFERENCES':   // Raumeinstellungen, Kennungen, <ein Benutzer>, Einstellungen �ndern
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ACCOUNT_PREFERENCES_FORM', $link);
               break;
            case 'ACCOUNT_STATUS':        // Raumeinstellungen, Kennungen, <ein Benutzer>, Status �ndern
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ACCOUNT_STATUS_FORM', $link);
               break;
            case 'ANNOTATION_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ANNOTATION_EDIT_FORM', $link);
               break;
            case 'ANNOUNCEMENT_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ANNOUNCEMENT_EDIT_FORM', $link);
               break;
            case 'BUZZWORDS_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_BUZZWORDS_EDIT_FORM', $link);
               break;
            case 'CONFIGURATION_AGB':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_AGB_FORM', $link);
               break;
            case 'CONFIGURATION_CHAT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_CHAT_FORM', $link);
               break;
            case 'CONFIGURATION_COLOR':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_COLOR_FORM', $link);
               break;
            case 'CONFIGURATION_DATES':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_DATES_FORM', $link);
               break;
            case 'CONFIGURATION_DEFAULTS':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_DEFAULTS_FORM', $link);
               break;
            case 'CONFIGURATION_DISCUSSION':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_DISCUSSION_FORM', $link);
               break;
            case 'CONFIGURATION':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_FORM', $link);
               break;
            case 'CONFIGURATION_HOMEPAGE':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_HOMEPAGE_FORM', $link);
               break;
            case 'CONFIGURATION_HOME':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_HOME_FORM', $link);
               break;
            case 'CONFIGURATION_HTMLTEXTAREA':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_HTMLTEXTAREA_FORM', $link);
               break;
            case 'CONFIGURATION_MAIL':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_MAIL_FORM', $link);
               break;
            case 'CONFIGURATION_NEWSLETTER':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_NEWSLETTER_FORM', $link);
               break;
            case 'CONFIGURATION_PREFERENCES':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_PREFERENCES_FORM', $link);
               break;
            case 'CONFIGURATION_RUBRIC':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_RUBRIC_FORM', $link);
               break;
            case 'CONFIGURATION_SERVICE':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_SERVICE_FORM', $link);
               break;
            case 'CONFIGURATION_USAGEINFO':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_USAGEINFO_FORM', $link);
               break;
             case 'CONFIGURATION_TAGS':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_CONFIGURATION_TAGS_FORM',$link);
               break;
             case 'CONFIGURATION_LISTVIEWS':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_LISTVIEWS_FORM',$link);
               break;
            case 'CONFIGURATION_WIKI':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_WIKI_FORM',$link);
               break;
            case 'CONFIGURATION_PATH':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_PATH_FORM',$link);
               break;
            case 'CONFIGURATION_INFORMATIONBOX':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_INFORMATIOBOX_FORM',$link);
               break;
            case 'DATE_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DATE_EDIT_FORM', $link);
               break;
            case 'DISCARTICLE_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DISCARTICLE_EDIT_FORM', $link);
               break;
            case 'DISCUSSION_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DISCUSSION_EDIT_FORM', $link);
               break;
            case 'DISCUSSION_INDEX':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DISCUSSION_INDEX', $link);
               break;
            case 'DISCUSSION_DETAIL':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DISCUSSION_DETAIL_FORM', $link);
               break;
            case 'INSTITUTION_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_INSTITUTION_EDIT_FORM', $link);
               break;
            case 'LABELS_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_LABELS_EDIT_FORM', $link);
               break;
            case 'MATERIAL_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_MATERIAL_EDIT_FORM', $link);
               break;
            case 'PROJECT_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_PROJECT_EDIT_FORM', $link);
               break;
            case 'RUBRIC_MAIL':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_RUBRIC_MAIL_FORM', $link);
               break;
            case 'SECTION_EDIT':   // Nutzungshinweise unter Materialien, <ein Material>, Abschnitt hinzuf�gen
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_SECTION_EDIT_FORM', $link);
               break;
            case 'TODO_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TODO_EDIT_FORM', $link);
               break;
            case 'TOPIC_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TOPIC_EDIT_FORM', $link);
               break;
            case 'USER_CLOSE':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_USER_CLOSE_FORM', $link);
               break;
            case 'ACCOUNT_CLOSE':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_USER_CLOSE_FORM', $link);
               break;
            case 'USER_EDIT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_USER_EDIT_FORM', $link);
               break;
            case 'USER_PREFERENCES':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_USER_PREFERENCES_FORM', $link);
               break;
            case 'MAIL_TO_MODERATOR':      //
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_MAIL_TO_MODERATOR_FORM',$link);
               break;
            case 'TAG_EDIT':      //
               $tempMessage = getMessage('USAGE_INFO_TEXT_ROOM_TAG_EDIT_FORM');
               break;
            case 'ACCOUNT_ACTION':             //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_ROOM_ACCOUNT_EDIT_FORM');
               break;
            case 'MAIL_PROCESS':             //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_ROOM_MAIL_PROCESS_FORM');
               break;
            case 'LANGUAGE_UNUSED':      //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_LANGUAGE_UNUSED_FORM');
               break;
            case 'CONFIGURATION_ARCHIVE':      //
               $tempMessage      = getMessage('USAGE_INFO_FORM_COMING_SOON');
               break;
            case 'INSTITUTION_MAIL':      //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_INSTITUTION_MAIL_FORM');
               break;
            default:
               $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR'.' cs_community_item('.__LINE__.') ',$link);
               break;
         }
         $retour = $tempMessage;
         if ( $retour == 'USAGE_INFO_TEXT_COMMUNITYROOM_FOR_'.strtoupper($rubric).'_'.strtoupper($funct).'_FORM'
              or $retour == 'tbd'
              or $retour == 'USAGE_INFO_TEXT_PROJECTROOM_FOR_'.strtoupper($rubric).'_'.strtoupper($funct).'_FORM'
            ) {
            $retour = getMessage('USAGE_INFO_FORM_COMING_SOON');
         }
      }
      return $retour;
   }

   function getUsageInfoTextForRubricFormInForm($rubric){
      // Konfiguration: Einstellung (Formular)
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
         $temp_rub = strtoupper($rubric);
         $tempMessage = "";
         switch( $temp_rub )
         {
            case 'ANNOUNCEMENT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ANNOUNCEMENT_EDIT_FORM',$link);
               break;
            case 'BUZZWORDS':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_BUZZWORDS_EDIT_FORM',$link);
               break;
            case 'DATE':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DATE_EDIT_FORM',$link);
               break;
            case 'DISCUSSION':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DISCUSSION_EDIT_FORM',$link);
               break;
            case 'INSTITUTION':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_INSTITUTION_EDIT_FORM',$link);
               break;
            case 'LABELS':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_LABELS_EDIT_FORM',$link);
               break;
            case 'MATERIAL':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_MATERIAL_EDIT_FORM',$link);
               break;
            case 'PROJECT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_PROJECT_EDIT_FORM',$link);
               break;
            case 'TODO':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TODO_EDIT_FORM',$link);
               break;
            case 'TOPIC':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TOPIC_EDIT_FORM',$link);
               break;
            case 'USER':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_USER_EDIT_FORM',$link);
               break;
            default:
               $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR'.' cs_community_item(988) ',$link);
               break;
         }
         $retour = $tempMessage;

         if ($retour =='USAGE_INFO_TEXT_COMMUNITYROOM_FOR_'.strtoupper($rubric).'_EDIT_FORM' or $retour =='tbd'){
            $retour = getMessage('USAGE_INFO_FORM_COMMING_SOON');
         }
      }
      return $retour;
   }

   function getUsageInfoTextForRubricInForm($rubric){
      // Konfigurationsoption: Einstellen (Index)
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
         $temp_rub = strtoupper($rubric);
         $tempMessage = "";
         switch( $temp_rub )
         {
            case 'ACCOUNT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ACCOUNT_INDEX',$link);
               break;
            case 'ANNOUNCEMENT_CLIPBOARD':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ANNOUNCEMENT_CLIPBOARD_INDEX',$link);
               break;
            case 'ANNOUNCEMENT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_ANNOUNCEMENT_INDEX',$link);
               break;
            case 'DATE_CLIPBOARD':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DATE_CLIPBOARD_INDEX',$link);
               break;
            case 'DATE':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DATE_INDEX',$link);
               break;
            case 'DISCUSSION_CLIPBOARD':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DISCUSSION_CLIPBOARD_INDEX',$link);
               break;
            case 'DISCUSSION':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_DISCUSSION_INDEX',$link);
               break;
            case 'HOME':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_HOME_INDEX',$link);
               break;
            case 'INSTITUTION':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_INSTITUTION_INDEX',$link);
               break;
            case 'MATERIAL_CLIPBOARD':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_MATERIAL_CLIPBOARD_INDEX',$link);
               break;
            case 'MATERIAL':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_MATERIAL_INDEX',$link);
               break;
            case 'PROJECT':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_PROJECT_INDEX',$link);
               break;
            case 'TODO_CLIPBOARD':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TODO_CLIPBOARD_INDEX',$link);
               break;
            case 'TODO':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TODO_INDEX',$link);
               break;
            case 'TOPIC':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_TOPIC_INDEX',$link);
               break;
            case 'USER':
               $tempMessage = getMessage('USAGE_INFO_TEXT_COMMUNITYROOM_FOR_USER_INDEX',$link);
               break;
            default:
               $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR'.' cs_community_item(1099) ',$link);
               break;
         }
         $retour = $tempMessage;

         if ($retour =='USAGE_INFO_TEXT_COMMUNITYROOM_FOR_'.strtoupper($rubric).'_INDEX' or $retour =='tbd'){
            $retour = getMessage('USAGE_INFO_COMMING_SOON');
         }
      }
      return $retour;
   }

   ################################################################
   # mail to moderation, if the community room status changed
   # - delete
   # - undelete
   # - open
   # - archive
   # - template (not implemented yet because flagged function)
   # - untemplate (not implemented yet because flagged function)
   # - reopen
   ################################################################

   function _sendMailRoomDelete () {
      $this->_sendMailRoomDeleteToCommunityModeration();
      $this->_sendMailRoomDeleteToPortalModeration();
   }

   function _sendMailRoomUnDelete () {
      $this->_sendMailRoomUnDeleteToCommunityModeration();
      $this->_sendMailRoomUnDeleteToPortalModeration();
   }

   function _sendMailRoomOpen () {
      $this->_sendMailRoomOpenToCommunityModeration();
      $this->_sendMailRoomOpenToPortalModeration();
   }

   function _sendMailRoomArchive () {
      $this->_sendMailRoomArchiveToCommunityModeration();
      $this->_sendMailRoomArchiveToPortalModeration();
   }

   function _sendMailRoomReOpen () {
      $this->_sendMailRoomReOpenToCommunityModeration();
      $this->_sendMailRoomReOpenToPortalModeration();
   }

   function _sendMailRoomLock () {
      $this->_sendMailRoomLockToCommunityModeration();
      $this->_sendMailRoomLockToPortalModeration();
   }

   function _sendMailRoomUnlock () {
      $this->_sendMailRoomUnlockToCommunityModeration();
      $this->_sendMailRoomUnlockToPortalModeration();
   }

   function _sendMailToModeration ($room_moderation, $room_change) {
      if ( $room_moderation == 'portal' ) {
                   $this->_sendMailToModeration2($this->getContextItem(),$room_change);
                } elseif ( $room_moderation == 'community' ) {
                   $this->_sendMailToModeration2($this,$room_change);
                } else {
                   include_once('functions/error_functions.php');trigger_error('lost room moderation',E_USER_WARNING);
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
         $subject = '';
         if ( $room_item->isPortal() ){
            $subject .= $room_item->getTitle().': ';
         }
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
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_OPEN');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_OPEN');
         } elseif ( $room_change == 'reopen' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_REOPEN');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_REOPEN');
         } elseif ( $room_change == 'delete' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_DELETE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_DELETE');
         } elseif ( $room_change == 'undelete' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_UNDELETE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNDELETE');
         } elseif ( $room_change == 'archive' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_ARCHIVE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_ARCHIVE');
         } elseif ( $room_change == 'lock' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_LOCK');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_LOCK');
         } elseif ( $room_change == 'unlock' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_UNLOCK');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNLOCK');
         }
         $body .= LF.LF;
         $body .= $translator->getMessage('PROJECT_MAIL_BODY_INFORMATION',$this->getTitle(),$current_user->getFullname(),$room_change_action);
         if ( $room_change != 'delete' ) {
            $body .= LF.'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->getContextID().'&room_id='.$this->getItemID();
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
         if ( isset($current_portal) ) {
            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_portal->getTitle()));
         } else {
            $server_item = $this->_environment->getServerItem();
            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$server_item->getTitle()));
            unset($server_item);
         }
         $mail->set_reply_to_name($current_user->getFullname());
         $mail->set_reply_to_email($current_user->getEmail());
         $mail->set_subject($subject);
         $mail->set_message($body);
         $mail->send();
         $translator->setSelectedLanguage($save_language);
         unset($save_language);
         unset($current_portal);
         unset($current_user);
         unset($mail);
      }
   }
}
?>