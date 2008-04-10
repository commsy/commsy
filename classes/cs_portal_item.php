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

/** upper class of the context item
 */
include_once('classes/cs_guide_item.php');

/** class for a context
 * this class implements a context item
 */
class cs_portal_item extends cs_guide_item {

   var $_community_list = NULL;

   var $_project_list = NULL;

   var $_privateroom_list = NULL;

   var $_room_list = NULL;

   var $_room_list_continuous = NULL;

   var $_cache_auth_source_list = NULL;

   /** constructor: cs_server_item
    * the only available constructor, initial values for internal variables
    *
    * @param object environment the environment of the commsy
    */
   function cs_portal_item ($environment) {
      $this->cs_guide_item($environment);
      $this->_type = CS_PORTAL_TYPE;
      $this->_default_rubrics_array[0] = CS_COMMUNITY_TYPE;
      $this->_default_rubrics_array[1] = CS_PROJECT_TYPE;
      $this->_default_home_conf_array[CS_COMMUNITY_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_PROJECT_TYPE] = 'tiny';
   }

   function isPortal () {
      return true;
   }

   /** get filename of picture
    *
    * @return string filename of picture
    */
   function getPictureFilename () {
      $retour = '';
      if ($this->_issetExtra('PICTUREFILENAME')) {
         $retour = $this->_getExtra('PICTUREFILENAME');
      }
      return $retour;
   }

   /** set filename of picture
    *
    * @param string filename of picture
    */
   function setPictureFilename ($value) {
      $this->_addExtra('PICTUREFILENAME',(string)$value);
   }

    /** get project room link status
    *
    * @return room link status status "optional" = project rooms can be opened without a link to a community room, "mandatory" = link is needed
    */
    function getProjectRoomLinkStatus () {
       $retour = 'optional';
       if ($this->_issetExtra('PROJECTROOMLINKSTATUS')) {
          $retour = $this->_getExtra('PROJECTROOMLINKSTATUS');
       }
       return $retour;
    }

    /** set project room link status
    *
    * @param array value room link status
    */
    function setProjectRoomLinkStatus ($value) {
       $this->_addExtra('PROJECTROOMLINKSTATUS',$value);
    }

    /** get community room creation status
    *
    * @return room creation status status "all"= all users, "moderator "= only portal moderators
    */
    function getCommunityRoomCreationStatus () {
       $retour = 'all';
       if ($this->_issetExtra('COMMUNITYROOMCREATIONSTATUS')) {
          $retour = $this->_getExtra('COMMUNITYROOMCREATIONSTATUS');
       }
       return $retour;
    }

    /** set community room creation status
    *
    * @param array value room creation status
    */
    function setCommunityRoomCreationStatus ($value) {
       $this->_addExtra('COMMUNITYROOMCREATIONSTATUS',$value);
    }

    /** get community room creation status
    *
    * @return room creation status status "portal"= on portal, too, "communityroom"= only in communityrooms
    */
    function getProjectRoomCreationStatus () {
       $retour = 'portal';
       if ($this->_issetExtra('PROJECTCREATIONSTATUS')) {
          $retour = $this->_getExtra('PROJECTCREATIONSTATUS');
       }
       return $retour;
    }

    /** set community room creation status
    *
    * @param array value room creation status
    */
    function setProjectRoomCreationStatus ($value) {
       $this->_addExtra('PROJECTCREATIONSTATUS',$value);
    }

   /** set authentication connection information
    * this method sets the authentication connection information of the CommSy
    *
    * @param string value authentication connection information
    */
   function setAuthInfo ($value) {
      $this->_addExtra('AUTHINFO',(array)$value);
   }



   function getShowRoomsOnHome(){
     $retour = 'normal';
     if ($this->_issetExtra('SHOWROOMSONHOME')) {
        $retour = $this->_getExtra('SHOWROOMSONHOME');
     }
     return $retour;
   }

   function setShowRoomsOnHome($value){
       $this->_addExtra('SHOWROOMSONHOME',$value);
   }




   function setSortRoomsByActivityOnHome(){
       $this->_addExtra('SORTROOMSONHOME','activity');
   }

   function setSortRoomsByTitleOnHome(){
       $this->_addExtra('SORTROOMSONHOME','title');
   }

   function isSortRoomsByTitleOnHome(){
      $retour = false;
      if ($this->_issetExtra('SORTROOMSONHOME') and $this->_getExtra('SORTROOMSONHOME')=='title') {
         $retour = true;
      }
      return $retour;
   }




   /** get community list
    * this function returns a list of all community rooms
    * existing at this portal
    *
    * @return list of community rooms
    */
   function getCommunityList () {
      if (!isset($this->_community_list)) {
         $manager = $this->_environment->getCommunityManager();
         $manager->setContextLimit($this->getItemID());
         $manager->select();
         $this->_community_list = $manager->get();
         unset($manager);
      }
      return $this->_community_list;
   }

   function getCountCommunityRooms () {
      if (!isset($this->_community_list_count)) {
         $manager = $this->_environment->getCommunityManager();
         $manager->setContextLimit($this->getItemID());
         $this->_community_list_count = $manager->getCountAll();
         unset($manager);
      }
      return $this->_community_list_count;
   }

   function getProjectList () {
      if (!isset($this->_project_list)) {
         $manager = $this->_environment->getProjectManager();
         $manager->setContextLimit($this->getItemID());
         $manager->select();
         $this->_project_list = $manager->get();
         unset($manager);
      }
      return $this->_project_list;
   }

   function getPrivateRoomList () {
      if ( !isset($this->_privateroom_list) ) {
         $manager = $this->_environment->getPrivateRoomManager();
         $manager->setContextLimit($this->getItemID());
         $manager->select();
         $this->_privateroom_list = $manager->get();
         unset($manager);
      }
      return $this->_privateroom_list;
   }

   function getCountProjectRooms () {
      if (!isset($this->_project_list_count)) {
         $manager = $this->_environment->getProjectManager();
         $manager->setContextLimit($this->getItemID());
         $this->_project_list_count = $manager->getCountAll();
         unset($manager);
      }
      return $this->_project_list_count;
   }

   function getRoomList () {
      if (!isset($this->_room_list)) {
         $this->_room_list = $this->getCommunityList();
         $this->_room_list->addList($this->getProjectList());
      }
      return $this->_room_list;
   }

   function getCountRooms () {
      return $this->getCountCommunityRooms() + $this->getCountProjectRooms();
   }

   function getContinuousRoomList () {
      if (!isset($this->_room_list_continuous)) {
         $manager = $this->_environment->getRoomManager();
         $manager->setContextLimit($this->getItemID());
         $manager->setContinuousLimit();
         $manager->select();
         $this->_room_list_continuous = $manager->get();
         unset($manager);
     }
     return $this->_room_list_continuous;
   }

   ###########################################################
   # some function to get lists of items in one portal
   ###########################################################

   function getUsedRoomList ($start, $end) {
      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $room_list = $room_manager->getUsedRooms($start,$end);
      unset($room_manager);
      return $room_list;
   }

   function getActiveRoomList ($start, $end) {
      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $room_list = $room_manager->getActiveRooms($start,$end);
      unset($room_manager);
      return $room_list;
   }

   function getCountMembers () {
      if (!isset($this->_member_count)) {
         $manager = $this->_environment->getUserManager();
         $manager->setContextLimit($this->getItemID());
         $this->_member_count = $manager->getCountAll();
         unset($manager);
      }
      return $this->_member_count;
   }

   function setRoomContext ($value) {
      $this->_addExtra('ROOM_CONTEXT',(string)$value);
      if ($value == 'uni') {
         $retour['NAME'] = CS_COMMUNITY_TYPE;
         $retour['DE']['GENUS']= 'M';
         $retour['DE']['NOMS']= 'Gemeinschaftsraum';
         $retour['DE']['GENS']= 'Gemeinschaftsraums';
         $retour['DE']['AKKS']= 'Gemeinschaftsraum';
         $retour['DE']['DATS']= 'Gemeinschaftsraum';
         $retour['DE']['NOMPL']= 'Gemeinschaftsräume';
         $retour['DE']['GENPL']= 'Gemeinschaftsräume';
         $retour['DE']['AKKPL']= 'Gemeinschaftsräume';
         $retour['DE']['DATPL']= 'Gemeinschaftsräumen';
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
      } elseif($value == 'school') {
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
      } elseif($value == 'project') {
         $retour['NAME'] = CS_COMMUNITY_TYPE;
         $retour['DE']['GENUS']= 'M';
         $retour['DE']['NOMS']= 'Gemeinschaftsraum';
         $retour['DE']['GENS']= 'Gemeinschaftsraums';
         $retour['DE']['AKKS']= 'Gemeinschaftsraum';
         $retour['DE']['DATS']= 'Gemeinschaftsraum';
         $retour['DE']['NOMPL']= 'Gemeinschaftsräume';
         $retour['DE']['GENPL']= 'Gemeinschaftsräume';
         $retour['DE']['AKKPL']= 'Gemeinschaftsräume';
         $retour['DE']['DATPL']= 'Gemeinschaftsräumen';
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

   ###################################################
   # time text translation methods
   ###################################################

   function getTimeTextArray () {
      $retour = array();
      if ($this->_issetExtra('TIME_TEXT_ARRAY')) {
         $retour = $this->_getExtra('TIME_TEXT_ARRAY');
      }
      return $retour;
   }

   function setTimeTextArray ($value) {
      $this->_addExtra('TIME_TEXT_ARRAY',$value);
   }

   function getTimeNameArray () {
      $retour = array();
      if ($this->_issetExtra('TIME_NAME_ARRAY')) {
         $retour = $this->_getExtra('TIME_NAME_ARRAY');
      }
      return $retour;
   }

   function setTimeNameArray ($value) {
      $this->_addExtra('TIME_NAME_ARRAY',$value);

      $value2 = array();
      $value2['NAME'] = CS_TIME_TYPE;

     foreach ($value as $lang => $name) {
        $value2[strtoupper($lang)]['NOMPL'] = $name;
     }
     $this->setRubricArray(CS_TIME_TYPE, $value2);
   }

   function _getShowTime () {
      $retour = '';
      if ($this->_issetExtra('TIME_SHOW')) {
         $retour = $this->_getExtra('TIME_SHOW');
      }
      return $retour;
   }

   function showTime () {
      $retour = false;
      $value = $this->_getShowTime();
      if ($value == 1) {
        $retour = true;
      }
      return $retour;
   }

   function setShowTime () {
      $this->_addExtra('TIME_SHOW',1);
   }

   function setNotShowTime () {
      $this->_addExtra('TIME_SHOW',-1);
   }

   function getTimeInFuture () {
      $retour = 0;
      if ($this->_issetExtra('TIME_IN_FUTURE')) {
         $retour = $this->_getExtra('TIME_IN_FUTURE');
      }
      return $retour;
   }

   function setTimeInFuture ($value) {
      $this->_addExtra('TIME_IN_FUTURE',$value);
   }

   function getTimeList () {
     $retour = NULL;
     $time_manager = $this->_environment->getTimeManager();
     $time_manager->setContextLimit($this->getItemID());
     $time_manager->setSortOrder('title');
     $time_manager->select();
     $retour = $time_manager->get();
     unset($time_manager);
     return $retour;
   }

   function getTimeListRev () {
     $retour = NULL;
     $time_manager = $this->_environment->getTimeManager();
     $time_manager->setContextLimit($this->getItemID());
     $time_manager->setSortOrder('title_rev');
     $time_manager->select();
     $retour = $time_manager->get();
     unset($time_manager);
     return $retour;
   }

   function getTitleOfCurrentTime () {
      $retour = '';
      $current_year = date('Y');
      $year = $current_year-1;
      $current_date = getCurrentDate();
      $clock_pulse_array = $this->getTimeTextArray();
      $found = false;
      while (!$found and $year < $current_year+1) {
         foreach ($clock_pulse_array as $key => $clock_pulse) {
            $begin_month = $clock_pulse['BEGIN'][3].$clock_pulse['BEGIN'][4];
            $begin_day = $clock_pulse['BEGIN'][0].$clock_pulse['BEGIN'][1];
            $end_month = $clock_pulse['END'][3].$clock_pulse['END'][4];
            $end_day = $clock_pulse['END'][0].$clock_pulse['END'][1];
            $begin = $begin_month.$begin_day;
            $end = $end_month.$end_day;
            if ($begin > $end) {
               $begin = $year.$begin;
               $end = ($year+1).$end;
            } else {
               $begin = $year.$begin;
               $end = $year.$end;
            }
            if ( $begin <= $current_date
                 and $current_date <= $end
               ) {
               $found = true;
               $retour = $year.'_'.$key;
            }
         }
         $year++;
      }
      return $retour;
   }

   function getCurrentTimeItem () {
      $retour = NULL;
      $time_manager = $this->_environment->getTimeManager();
      $time_manager->setContextLimit($this->getItemID());
      $time_manager->setTypeLimit('time');
      $retour = $time_manager->getItemByName($this->getTitleOfCurrentTime());
      unset($time_manager);
      return $retour;
   }

   function save () {
      parent::save();
      $this->_time_list = NULL;
      $manager = $this->_environment->getPortalManager();
      $this->setServiceLinkActive();
      $this->_save($manager);
      unset($manager);
   }

   /** delete portal
    * this method portal the community
    */
   function delete() {
      parent::delete();

      $manager = $this->_environment->getPortalManager();
      $this->_delete($manager);
      unset($manager);
   }


   ##########################################################
   # statistic functions
   ##########################################################

   function getCountUsedRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountUsedRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountUsedClosedRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountUsedClosedRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountOpenRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountOpenRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountActiveRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountActiveRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountClosedRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountClosedRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountAllRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountAllRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountUsedProjectRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountUsedProjectRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountUsedClosedProjectRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountUsedClosedProjectRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountOpenProjectRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountOpenProjectRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountActiveProjectRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountActiveProjectRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountClosedProjectRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountClosedProjectRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountAllProjectRooms ($start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountAllProjectRooms($start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountUsedAccounts ($start, $end) {
      $retour = 0;

      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $user_manager->setContextLimit($this->getItemID());
      $retour = $user_manager->getCountUsedAccounts($start,$end);
      unset($user_manager);

      return $retour;
   }

   function getCountOpenAccounts ($start, $end) {
      $retour = 0;

      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $user_manager->setContextLimit($this->getItemID());
      $retour = $user_manager->getCountOpenAccounts($start,$end);
      unset($user_manager);

      return $retour;
   }

   function getCountAllAccounts ($start, $end) {
      $retour = 0;

      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $user_manager->setContextLimit($this->getItemID());
      $retour = $user_manager->getCountAllAccounts($start,$end);
      unset($user_manager);

      return $retour;
   }

   #########################################################
   # COMMSY CRON JOBS
   #
   # this cron jobs only works if a daily cron job is
   # configured to run cron.php in /htdocs
   #########################################################

   /** cron daily, INTERNAL
    * here you can link daily cron jobs
    *
    * @return array results of running crons
    */
   function _cronDaily () {
      // you can link daily cron jobs here like this
      // $cron_array[] = $this->_sendEmailNewsLetter();
      $cron_array = array();

      if ( $this->showTime() and $this->isOpen() ) {
         $cron_array[] = $this->_cronCheckTimeLabels();
      }

      return $cron_array;
   }

   function _cronCheckTimeLabels () {
      $retour = array();
      $retour['title'] = 'check time labels';
      $retour['description'] = 'checks switching between two time lables';
      $retour['success'] = false;
      $retour['success_text'] = 'cron failed';

      $time_list = $this->getTimeList();
      $counter = 0;
      $count = false;
      if ($time_list->isNotEmpty()) {
         $current_time_label_title = $this->getTitleOfCurrentTime();
         $time_item = $time_list->getFirst();
         while ($time_item) {
            if ($count) {
               $counter++;
            }
            if ($current_time_label_title == $time_item->getTitle()) {
               $count = true;
            }
            unset($time_item);
            $time_item = $time_list->getNext();
         }
      }
      if ($counter < $this->getTimeInFuture()) {
         $last_time_item = $time_list->getLast();
         $title_last_item = $last_time_item->getTitle();
         $title_array_last_item = explode('_',$title_last_item);
         $time_text_array = $this->getTimeTextArray();
         if ($title_array_last_item[1] == count($time_text_array)) {
            $title = ($title_array_last_item[0]+1).'_1';
         } else {
            $title = $title_array_last_item[0].'_'.($title_array_last_item[1]+1);
         }

         $time_manager = $this->_environment->getTimeManager();
         $time_label = $time_manager->getNewItem();
         $time_label->setContextID($this->getItemID());
         $time_label->setCreatorItem($this->_environment->getRootUserItem());
         $time_label->setModificatorItem($this->_environment->getRootUserItem());
         $time_label->setTitle($title);
         $time_label->save();

         $cont_room_list = $this->getContinuousRoomList();
         if ($cont_room_list->isNotEmpty()) {
            $cont_room_item = $cont_room_list->getFirst();
            while ($cont_room_item) {
              $cont_room_item->setContinuous();
              $cont_room_item->saveWithoutChangingModificationInformation();
              unset($cont_room_item);
              $cont_room_item = $cont_room_list->getNext();
            }
         }
         unset($time_label);
         unset($time_manager);
         unset($last_time_item);
         $retour['success'] = true;
         $retour['success_text'] = 'insert new time label: '.$title.BR;
         $retour['success_text'] .= 'renew links between continuous rooms and time labels';
      } else {
         $retour['success'] = true;
         $retour['success_text'] = 'nothing to do';
      }
      unset($time_list);
      return $retour;
   }


   /** get UsageInfos
    * this method returns the usage infos
    *
    * @return array
    */
   function getUsageInfoArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO')) {
       $retour = $this->_getExtra('USAGE_INFO');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
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
   function setUsageInfoArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO',$value_array);
      }
   }

   /** set UsageInfos
    * this method sets the usage infos
    *
    * @param array
    */
   function setUsageInfoFormArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM',$value_array);
      }
   }

   /** get UsageInfos
    * this method returns the usage infos
    *
    * @return array
    */
   function getUsageInfoFormArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM')) {
       $retour = $this->_getExtra('USAGE_INFO_FORM');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }


   function getUsageInfoHeaderArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoHeaderArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_HEADER',$value_array);
      }
   }

   function getUsageInfoFormHeaderArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_HEADER');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoFormHeaderArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM_HEADER',$value_array);
      }
   }


   function getUsageInfoTextArray () {
      $retour = NULL;
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
      return $retour;
   }

   function setUsageInfoTextArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_TEXT',$value_array);
      }
   }

   function getUsageInfoFormTextArray () {
      $retour = NULL;
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
      return $retour;
   }

   function setUsageInfoFormTextArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM_TEXT',$value_array);
      }
   }

   function getUsageInfoHeaderForRubric($rubric){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
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
         $retour = getMessage('USAGE_INFO_HEADER');
      }
      return $retour;
   }

   function setUsageInfoHeaderForRubric($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $value_array = $this->_getExtra('USAGE_INFO_HEADER');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_HEADER',$value_array);
   }

   function getUsageInfoHeaderForRubricForm($rubric){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
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
         $retour = getMessage('USAGE_INFO_HEADER');
      }
      return $retour;
   }

   function setUsageInfoHeaderForRubricForm($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
         $value_array = $this->_getExtra('USAGE_INFO_FORM_HEADER');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_FORM_HEADER',$value_array);
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
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
         $mod = $this->_environment->getCurrentModule();
         $fct = $this->_environment->getCurrentFunction();
         if ($mod == 'configuration' and $fct == 'time'){ // no link in message tag
            $retour = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_TIME_FORM');
            $temp = 'CONFIGURATION_TIME';
         } else {
            $temp = strtoupper($rubric).'_'.strtoupper($funct);
            $tempMessage = "";
            // ---> Remark for testing: Login as root, "Configure Portal" <---
            switch( $temp )
            {
               case 'ACCOUNT_ACTION':        // getestet: eine Kennung bearbeiten
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_ACCOUNT_ACTION_FORM',$link);
                  break;
               case 'ACCOUNT_EDIT':          // getestet: eine Kennung bearbeiten
                  $tempMessage = getMessage('USAGE_INFO_FORM_COMING_SOON',$link);
                  break;
               case 'ACCOUNT_STATUS':        // getestet: Benutzer Status ändern (als Root/Moderator)
                  $tempMessage = getMessage('USAGE_INFO_FORM_COMING_SOON',$link);
                  break;
               case 'COMMUNITY_EDIT':        // getestet: Gemeinschaftsraum neu eröffnen
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_COMMUNITY_EDIT_FORM',$link);
                  break;
               case 'CONFIGURATION_AGB':     // getestet: Portal / Einstellungen / Nutzungsvereinbarungen und Textareas mit Extra-Tags
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_AGB_FORM',$link);
                  break;
               case 'CONFIGURATION_AUTHENTICATION':  // getestet
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_AUTHENTICATION_FORM',$link);
                  break;
               case 'CONFIGURATION_COLOR':   // getestet
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_COLOR_FORM',$link);
                  break;
               case 'CONFIGURATION_COMMON':  // getestet: als root irgendeinen Raum anklicken, dann oben rechts "Raum bearbeiten"
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_COMMON_FORM',$link);
                  break;
               case 'CONFIGURATION_DEFAULTS': // getestet
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_DEFAULTS_FORM',$link);
                  break;
               case 'CONFIGURATION_EXPORT':  // getestet: als root irgendeinen Raum anklicken, dann oben rechts
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_EXPORT_FORM',$link);
                  break;
               case 'CONFIGURATION_MAIL':    // getestet und Textareas mit Extra-Tags
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_MAIL_FORM',$link);
                  break;
               case 'CONFIGURATION_MOVE':    // getestet und Textareas mit Extra-Tags
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_MOVE_FORM',$link);
                  break;
               case 'CONFIGURATION_NEWS':    // getestet Portal-Ankündigungen bearbeiten und Textareas mit Extra-Tags
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_NEWS_FORM',$link);
                  break;
               case 'CONFIGURATION_PORTALHOME': // getestet
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PORTALHOME_FORM',$link);
                  break;
               case 'CONFIGURATION_PREFERENCES': // getestet
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PREFERENCES_FORM',$link);
                  break;
               case 'CONFIGURATION_ROOM_OPENING': // getestet Voreinst. f. Räume, z. B. Schule, Uni, Business
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_ROOM_OPENING_FORM',$link);
                  break;
               case 'CONFIGURATION_SERVICE': // getestet Handhabungssupport
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_SERVICE_FORM',$link);
                  break;
               case 'CONFIGURATION_WIKI':    // getestet Einstellungen Raum-Wiki
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_WIKI_FORM',$link);
                  break;
               case 'PROJECT_EDIT':          // getestet: Projektraum neu eröffnen
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_PROJECT_EDIT_FORM',$link);
                  break;
               case 'MAIL_TO_MODERATOR':      //
                  $tempMessage = getMessage('USAGE_INFO_TEXT_PORTAL_FOR_MAIL_TO_MODERATOR_FORM',$link);
                  break;
               case 'LANGUAGE_UNUSED':      //
                  $tempMessage      = getMessage('USAGE_INFO_TEXT_LANGUAGE_UNUSED_FORM');
                  break;
               default:
                  $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR')." cs_portal_item(1208)";
                  break;
            }
            $retour = $tempMessage;
         }
         if ($retour == 'USAGE_INFO_TEXT_PORTAL_FOR_'.$temp.'_FORM' or $retour =='tbd'){
            $retour = getMessage('USAGE_INFO_FORM_COMING_SOON');
         }
      }
      return $retour;
   }

   ################################################################
   # Authentication
   ################################################################

   function setAuthDefault ($value) {
      $this->_addExtra('DEFAULT_AUTH',$value);
   }

   function setAuthIMS ($value) {
      $this->_addExtra('IMS_AUTH',$value);
   }

   function getAuthDefault () {
      $retour = '';
      if ($this->_issetExtra('DEFAULT_AUTH')) {
         $value = $this->_getExtra('DEFAULT_AUTH');
         if ( !empty($value) ) {
            $retour = $value;
         }
      }
      return $retour;
   }

   function getAuthIMS () {
      $retour = '';
      if ($this->_issetExtra('IMS_AUTH')) {
         $value = $this->_getExtra('IMS_AUTH');
         if ( !empty($value) ) {
            $retour = $value;
         }
      }
      return $retour;
   }

   function getDefaultAuthSourceItem () {
      $retour = NULL;
      $default_auth_item_id = $this->getAuthDefault();
      if ( !empty($default_auth_item_id) ) {
         $manager = $this->_environment->getAuthSourceManager();
         $item = $manager->getItem($default_auth_item_id);
         if ( isset($item) ) {
            $retour = $item;
         }
         unset($item);
      }
      return $retour;
   }

   function getAuthSourceList () {
      if ( !isset($this->_cache_auth_source_list) ) {
         $manager = $this->_environment->getAuthSourceManager();
         $manager->setContextLimit($this->getItemID());
         $manager->select();
         $this->_cache_auth_source_list = $manager->get();
      }
      return $this->_cache_auth_source_list;
   }

   function getAuthSourceListEnabled () {
      $list = $this->getAuthSourceList();
      if ( !$list->isEmpty() ) {
         $item = $list->getFirst();
         while ( $item ) {
            if ( !$item->show() ) {
               $list->removeElement($item);
            }
            $item = $list->getNext();
         }
      }
      return $list;
   }

   function getAuthSourceListCASEnabled () {
      $list = $this->getAuthSourceList();
      if ( !$list->isEmpty() ) {
         $item = $list->getFirst();
         while ( $item ) {
            if ( !$item->show() or strtoupper($item->getSourceType()) != 'CAS' ) {
               $list->removeElement($item);
            }
            $item = $list->getNext();
         }
      }
      return $list;
   }

   function getAuthSource ($item_id) {
      $manager = $this->_environment->getAuthSourceManager();
      return $manager->getItem($item_id);
   }

   ###########################################
   # portal description wellcome text
   ###########################################

   /** get description array
    *
    * @return array description text in different languages
    */
   function getDescriptionWellcome1Array () {
      $retour = array();
      if ($this->_issetExtra('DESCRIPTION_WELLCOME_1')) {
         $retour = $this->_getExtra('DESCRIPTION_WELLCOME_1');
      }
      return $retour;
   }

   /** set description array
    *
    * @param array value description text in different languages
    */
   function setDescriptionWellcome1Array ($value) {
      $this->_addExtra('DESCRIPTION_WELLCOME_1',(array)$value);
   }

   /** get description of a context
    * this method returns the description of the context
    *
    * @return string description of a context
    */
   function getDescriptionWellcome1ByLanguage ($language) {
      $retour = NULL;
      if ( $language == 'browser' ) {
         $language = $this->_environment->getSelectedLanguage();
      }
      $desc_array = $this->getDescriptionWellcome1Array();
      if ( isset($desc_array[cs_strtoupper($language)]) ) {
         $retour = $desc_array[cs_strtoupper($language)];
      } else {
         $translator = $this->_environment->getTranslationObject();
         $retour = $translator->getMessageInLang(strtolower($language),'HOMEPAGE_PAGE_ROOT_TITLE').' '.$translator->getMessageInLang(strtolower($language),'COMMON_IN').' ...';
      }
      return $retour;
   }

   function getDescriptionWellcome1 () {
      $retour = '';
      $retour = $this->getDescriptionWellcome1ByLanguage($this->_environment->getSelectedLanguage());
      if ( !isset($retour) ) {
         $current_user = $this->_environment->getCurrentUserItem();
         $retour = $this->getDescriptionWellcome1ByLanguage($this->_environment->getUserLanguage());
      }
      if ( !isset($retour) ) {
         $translator = $this->_environment->getTranslationObject();
         $retour = $translator->getMessage('HOMEPAGE_PAGE_ROOT_TITLE').' '.$translator->getMessage('COMMON_IN').' ...';
      }
      return $retour;
   }

   /** set description of a context
    * this method sets the description of the context
    *
    * @param string value description of the context
    * @param string value lanugage of the description
    */
   function setDescriptionWellcome1ByLanguage ($value, $language) {
      $desc_array = $this->getDescriptionWellcome1Array();
      $desc_array[strtoupper($language)] = $value;
      $this->setDescriptionWellcome1Array($desc_array);
   }

   /** get description array
    *
    * @return array description text in different languages
    */
   function getDescriptionWellcome2Array () {
      $retour = array();
      if ($this->_issetExtra('DESCRIPTION_WELLCOME_2')) {
         $retour = $this->_getExtra('DESCRIPTION_WELLCOME_2');
      }
      return $retour;
   }

   /** set description array
    *
    * @param array value description text in different languages
    */
   function setDescriptionWellcome2Array ($value) {
      $this->_addExtra('DESCRIPTION_WELLCOME_2',(array)$value);
   }

   /** get description of a context
    * this method returns the description of the context
    *
    * @return string description of a context
    */
   function getDescriptionWellcome2ByLanguage ($language) {
      $retour = NULL;
      if ( $language == 'browser' ) {
         $language = $this->_environment->getSelectedLanguage();
      }
      $desc_array = $this->getDescriptionWellcome2Array();
      if ( isset($desc_array[cs_strtoupper($language)]) ) {
         $retour = $desc_array[cs_strtoupper($language)];
      } else {
         $retour = '... '.$this->getTitle();
      }
      return $retour;
   }

   function getDescriptionWellcome2 () {
      $retour = '';
      $retour = $this->getDescriptionWellcome2ByLanguage($this->_environment->getSelectedLanguage());
      if ( !isset($retour) ) {
         $current_user = $this->_environment->getCurrentUserItem();
         $retour = $this->getDescriptionWellcome2ByLanguage($this->_environment->getUserLanguage());
      }
      if ( !isset($retour) ) {
         $retour = '... '.$this->getTitle();
      }
      return $retour;
   }

   /** set description of a context
    * this method sets the description of the context
    *
    * @param string value description of the context
    * @param string value lanugage of the description
    */
   function setDescriptionWellcome2ByLanguage ($value, $language) {
      $desc_array = $this->getDescriptionWellcome2Array();
      $desc_array[strtoupper($language)] = $value;
      $this->setDescriptionWellcome2Array($desc_array);
   }
}
?>