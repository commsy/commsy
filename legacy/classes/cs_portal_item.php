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

   private $_community_id_array = NULL;
   private $_active_community_id_array = NULL;
   private $_project_id_array = NULL;
   private $_active_project_id_array = NULL;
   private $_group_id_array = NULL;
   private $_active_group_id_array = NULL;
   private $_community_id_array_archive = NULL;
   private $_project_id_array_archive = NULL;
   private $_group_id_array_archive = NULL;
   private $_private_id_array = NULL;
   private $_private_id_array_active_user = NULL;
   private $_room_list_continuous_nlct = NULL;
   private $_grouproom_list_count = NULL;
   private $_count_archived_grouprooms = NULL;
   private $_count_archived_project_and_community_rooms = NULL;
   private $_count_project_and_community_rooms_without_templates = NULL;

   /** constructor: cs_server_item
    * the only available constructor, initial values for internal variables
    *
    * @param object environment the environment of the commsy
    */
   function __construct($environment) {
      cs_guide_item::__construct($environment);
      $this->_type = CS_PORTAL_TYPE;
      $this->_default_rubrics_array[0] = CS_COMMUNITY_TYPE;
      $this->_default_rubrics_array[1] = CS_PROJECT_TYPE;
      $this->_default_home_conf_array[CS_COMMUNITY_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_PROJECT_TYPE] = 'tiny';
   }

   function isPortal () {
      return true;
   }

   /** get max activity points of rooms
    *
    * @return int max activity points of rooms
    */
   function getMaxRoomActivityPoints () {
      $retour = 0;
      if ($this->_issetExtra('MAX_ROOM_ACTIVITY')) {
         $retour = $this->_getExtra('MAX_ROOM_ACTIVITY');
      }
      return $retour;
   }

   /** set max activity points of rooms
    *
    * @param int max activity points of rooms
    */
   function setMaxRoomActivityPoints ($value) {
      $this->_addExtra('MAX_ROOM_ACTIVITY',(int)$value);
   }

   function saveMaxRoomActivityPoints ($value) {
      $current_value = $this->getMaxRoomActivityPoints();
      if ( $current_value < $value ) {
         $this->setMaxRoomActivityPoints($value);
         $this->saveWithoutChangingModificationInformation();
      }
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

    public function openCommunityRoomOnlyByModeration () {
       $retour = false;
       $status = $this->getCommunityRoomCreationStatus();
       if ( $status == 'moderator' ) {
          $retour = true;
       }
       return $retour;
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

    public function openProjectRoomOnlyInCommunityRoom () {
       $retour = false;
       $status = $this->getProjectRoomCreationStatus();
       if ( $status == 'communityroom' ) {
          $retour = true;
       }
       return $retour;
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

   function getNumberRoomsOnHome(){
      $retour = 10;
      if ($this->_issetExtra('NUMBERROOMSONHOME')) {
         $retour = $this->_getExtra('NUMBERROOMSONHOME');
      }
      return $retour;
   }

   function setNumberRoomsOnHome($value){
      $this->_addExtra('NUMBERROOMSONHOME',$value);
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

   public function getCommunityIDArray () {
      $retour = array();
      $archive = 'this->_community_id_array';
      if ( $this->_environment->isArchiveMode() ) {
         $archive .= '_archive';
      }
      if ( !isset($$archive) ) {
         $manager = $this->_environment->getCommunityManager();
         $manager->resetData();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $id_array = $manager->getIDArray();
         unset($manager);
         if ( is_array($id_array) ) {
            $$archive = $id_array;
         }
      }
      if ( !empty($$archive) ) {
         $retour = $$archive;
      }
      return $retour;
   }

   public function getActiveCommunityIDArray () {
      $retour = array();
      $archive = 'this->_active_community_id_array';
      if ( $this->_environment->isArchiveMode() ) {
         $archive .= '_archive';
      }
      if ( !isset($$archive) ) {
         $manager = $this->_environment->getCommunityManager();
         $manager->resetData();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $manager->setActiveLimit();
         $id_array = $manager->getIDArray();
         unset($manager);
         if ( is_array($id_array) ) {
            $$archive = $id_array;
         }
      }
      if ( !empty($$archive) ) {
         $retour = $$archive;
      }
      return $retour;
   }

   public function getProjectIDArray () {
      $retour = array();
      $archive = 'this->_project_id_array';
      if ( $this->_environment->isArchiveMode() ) {
         $archive .= '_archive';
      }
      if ( !isset($$archive) ) {
         $manager = $this->_environment->getProjectManager();
         $manager->resetData();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $id_array = $manager->getIDArray();
         unset($manager);
         if ( is_array($id_array) ) {
            $$archive = $id_array;
         }
      }
      if ( !empty($$archive) ) {
         $retour = $$archive;
      }
      return $retour;
   }

   public function getActiveProjectIDArray () {
      $retour = array();
      $archive = 'this->_active_project_id_array';
      if ( $this->_environment->isArchiveMode() ) {
         $archive .= '_archive';
      }
      if ( !isset($$archive) ) {
         $manager = $this->_environment->getProjectManager();
         $manager->resetData();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $manager->setActiveLimit();
         $id_array = $manager->getIDArray();
         unset($manager);
         if ( is_array($id_array) ) {
            $$archive = $id_array;
         }
      }
      if ( !empty($$archive) ) {
         $retour = $$archive;
      }
      return $retour;
   }

   public function getGroupIDArray () {
      $retour = array();
      $archive = 'this->_group_id_array';
      if ( $this->_environment->isArchiveMode() ) {
         $archive .= '_archive';
      }
      if ( !isset($$archive) ) {
         $manager = $this->_environment->getGrouproomManager();
         $manager->resetData();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $id_array = $manager->getIDArray();
         unset($manager);
         if ( is_array($id_array) ) {
            $$archive = $id_array;
         }
      }
      if ( !empty($$archive) ) {
         $retour = $$archive;
      }
      return $retour;
   }
   
   public function getActiveGroupIDArray () {
      $retour = array();
      $archive = 'this->_active_group_id_array';
      if ( $this->_environment->isArchiveMode() ) {
         $archive .= '_archive';
      }
      if ( !isset($$archive) ) {
         $manager = $this->_environment->getGrouproomManager();
         $manager->resetData();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $manager->setActiveLimit();
         $id_array = $manager->getIDArray();
         unset($manager);
         if ( is_array($id_array) ) {
            $$archive = $id_array;
         }
      }
      if ( !empty($$archive) ) {
         $retour = $$archive;
      }
      return $retour;
   }
   
   public function getPrivateIDArray () {
      $retour = array();
      if ( !isset($this->_private_id_array) ) {
         $manager = $this->_environment->getPrivateroomManager();
         $manager->resetData();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $id_array = $manager->getIDArray();
         unset($manager);
         if ( is_array($id_array) ) {
            $this->_private_id_array = $id_array;
         }
      }
      if ( !empty($this->_private_id_array) ) {
         $retour = $this->_private_id_array;
      }
      return $retour;
   }

   public function getActiveUserPrivateIDArray () {
      $retour = array();
      if ( !isset($this->_private_id_array_active_user) ) {
         $manager = $this->_environment->getPrivateroomManager();
         $manager->resetData();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $manager->setActiveLimit();
         $id_array = $manager->getIDArray();
         unset($manager);
         if ( is_array($id_array) ) {
            $this->_private_id_array_active_user = $id_array;
         }
      }
      if ( !empty($this->_private_id_array_active_user) ) {
         $retour = $this->_private_id_array_active_user;
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

   function getRoomList () {
      if (!isset($this->_room_list)) {
         $this->_room_list = $this->getCommunityList();
         $this->_room_list->addList($this->getProjectList());
      }
      return $this->_room_list;
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

   function getContinuousRoomListNotLinkedToTime ( $time_obj ) {
      if (!isset($this->_room_list_continuous_nlct)) {
         $manager = $this->_environment->getRoomManager();
         $manager->setContextLimit($this->getItemID());
         $manager->setContinuousLimit();
         $manager->setOpenedLimit();
         $manager->select();
         $id_array1 = $manager->getIdArray();
         $manager->setTimeLimit($time_obj->getItemID());
         $manager->select();
         $id_array2 = $manager->getIdArray();
         if ( is_array($id_array1) and is_array($id_array2) ) {
            $id_array3 = array_diff($id_array1,$id_array2);
            if ( !empty($id_array3) ) {
               $manager->resetLimits();
               $manager->setIDArrayLimit($id_array3);
               $manager->select();
               $this->_room_list_continuous_nlct = $manager->get();
            }
         }
         unset($manager);
      }
      return $this->_room_list_continuous_nlct;
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
        $value2[mb_strtoupper($lang, 'UTF-8')]['NOMPL'] = $name;
     }
     $this->setRubricArray(CS_TIME_TYPE, $value2);
   }

   /** return the current display string for time intervals as specified in
    * the current portal configuration for the currently selected language
    */
   function getCurrentTimeName()
   {
      $timeNamesByLanguage = $this->getTimeNameArray();
      $lang = strtoupper($this->_environment->getSelectedLanguage());

      $timeName = '';
      if ($timeNamesByLanguage && !empty($timeNamesByLanguage)) {
         if (isset($timeNamesByLanguage[$lang])) {
            $timeName = $timeNamesByLanguage[$lang];
         }
      }

      return $timeName;
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
            if ( isset($clock_pulse['BEGIN'][3])
                 and isset($clock_pulse['BEGIN'][4])
               ) {
               $begin_month = $clock_pulse['BEGIN'][3].$clock_pulse['BEGIN'][4];
            } else {
               $begin_month = '';
            }
            if ( isset($clock_pulse['BEGIN'][0])
                 and isset($clock_pulse['BEGIN'][1])
               ) {
               $begin_day = $clock_pulse['BEGIN'][0].$clock_pulse['BEGIN'][1];
            } else {
               $begin_day = '';
            }
            if ( isset($clock_pulse['END'][3])
                 and isset($clock_pulse['END'][4])
               ) {
               $end_month = $clock_pulse['END'][3].$clock_pulse['END'][4];
            } else {
               $end_month = '';
            }
            if ( isset($clock_pulse['END'][0])
                 and isset($clock_pulse['END'][1])
               ) {
               $end_day = $clock_pulse['END'][0].$clock_pulse['END'][1];
            } else {
               $end_day = '';
            }
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
      $item_id = $this->getItemID();
      parent::save();
      $this->_time_list = NULL;

      if ( empty($item_id) ) {
         $this->generateLayoutImages();
      }
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

   function getCountPlugin ($plugin, $start, $end) {
      $retour = 0;

      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $user_manager->setContextLimit($this->getItemID());
      $retour = $user_manager->getCountPlugin($plugin,$start,$end);
      unset($user_manager);

      return $retour;
   }

   function getCountAllTypeRooms ($type, $start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountAllTypeRooms($type,$start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountUsedTypeRooms ($type, $start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountUsedTypeRooms($type,$start,$end);
      unset($room_manager);

      return $retour;
   }

   function getCountActiveTypeRooms ($type, $start, $end) {
      $retour = 0;

      $room_manager = $this->_environment->getRoomManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getItemID());
      $retour = $room_manager->getCountActiveTypeRooms($type,$start,$end);
      unset($room_manager);

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

      $cron_array[] = $this->_cronUnlinkFiles();
      $cron_array[] = $this->_cronPageImpressionAndUserActivity(); // this function must run before server cron
      if ( $this->showTime() and $this->isOpen() ) {
         $cron_array[] = $this->_cronCheckTimeLabels();
         $cron_array[] = $this->_cronRenewContinuousLinks();
      }
      if ( $this->isCountRoomRedundancy() ) {
         $cron_array[] = $this->_cronSyncCountRooms();
      }
         
      // archiving
      if ( $this->isActivatedArchivingUnusedRooms() ) {
      	$cron_array[] = $this->_cronArchiveUnusedRooms();
      	$cron_array[] = $this->_cronArchiveUnusedRoomsSendMailBefore();
         if ( $this->isActivatedDeletingUnusedRooms() ) {
         	$this->_environment->toggleArchiveMode();
      	   $cron_array[] = $this->_cronDeleteUnusedRooms();
      	   $cron_array[] = $this->_cronDeleteUnusedRoomsSendMailBefore();
         	$this->_environment->toggleArchiveMode();
         }
      }
      $cron_array[] = $this->_cronTemporaryLoginAs();
      $cron_array[] = $this->_cronDraftCleanUp();
      return $cron_array;
   }

    function _cronDraftCleanUp()
    {
        $time_start = getmicrotime();
        $cron_array = array();
        $cron_array['title'] = 'Delete draft items';
        $cron_array['description'] = 'Delete all drafts';
        $success = false;

        // clean up all drafts
        $itemManager = $this->_environment->getItemManager();
        $draftItems = $itemManager->getAllDraftItems();

        foreach ($draftItems as $key => $value) {
            $manager = $this->_environment->getManager($value['type']);
            $item = $manager->getItem($value['item_id']);

            if($item) {
                $item->delete();
            }
        }
        $success = true;
        $cron_array['success'] = true;
        $cron_array['success_text'] = 'Drafts deleted';
        $time_end = getmicrotime();
        $time = round($time_end - $time_start,0);
        $cron_array['time'] = $time;

        return $cron_array;

    }
   
   function _cronTemporaryLoginAs() {
   	$time_start = getmicrotime();
   	$cron_array = array();
   	$cron_array['title'] = 'Temporary login as expired';
   	$cron_array['description'] = 'check if a temporary login is expired';
   	$success = false;
   	$translator = $this->_environment->getTranslationObject();
   
   	$user_manager = $this->_environment->getUserManager();
   	$user_list = $user_manager->getUserTempLoginExpired();
   	require_once 'classes/cs_mail.php';
   	if(!empty($user_list)) {
   		foreach ($user_list as $user) {
   			if($user->getTimestampForLoginAs() <= getCurrentDateTimeInMySQL()) {
   				$success = true;
   				// unset login as timestamp
   				$user->unsetDaysForLoginAs();
   				$user->save();
   				// send mail
   				
   				$mail = new cs_mail();
   				
   				$subject = $translator->getMessage('EMAIL_LOGIN_EXPIRATION_SUBJECT', $this->getTitle());
   				$to = $user->getEmail();
   				$to_name = $user->getFullname();
   				if ( !empty($to_name) ) {
   					$to = $to_name." <".$to.">";
   				}
   				$mod_contact_list = $this->getContactModeratorList();
   				$mod_user_first = $mod_contact_list->getFirst();

                global $symfonyContainer;
                $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                $mail->set_from_email($emailFrom);

                $mail->set_from_name($this->_environment->getCurrentPortalItem()->getTitle());
   				
   				// $mail->set_cc_to($mod_user_first->getEmail());
   				$mail->set_cc_to($this->_environment->getRootUserItem()->getEmail());

          // $current_context = $this->_environment->getCurrentContextItem();
          $mod_list = $mod_contact_list;

          if (!$mod_list->isEmpty()) {
            $moderator_item = $mod_list->getFirst();

            while ($moderator_item) {
              $email = $moderator_item->getEmail();
              if (!empty($email)) {
                $mail->set_cc_to($email);
                // $cc_array[] = $email;
              }

              unset($email);
              $moderator_item = $mod_list->getNext();
            }
          }
   				
   				// link
   				$url_to_portal = '';
   				if ( !empty($this) ) {
   					$url_to_portal = $this->getURL();
   				}
   				$c_commsy_cron_path = $this->_environment->getConfiguration('c_commsy_cron_path');
   				if ( isset($c_commsy_cron_path) ) {
   					$link = $c_commsy_cron_path;
   				} elseif ( !empty($url_to_portal) ) {
   					$c_commsy_domain = $this->_environment->getConfiguration('c_commsy_domain');
   					if ( stristr($c_commsy_domain,'https://') ) {
   						$link = 'https://';
   					} else {
   						$link = 'http://';
   					}
   					$link .= $url_to_portal;
   					$file = 'commsy.php';
   					$c_single_entry_point = $this->_environment->getConfiguration('c_single_entry_point');
   					if ( !empty($c_single_entry_point) ) {
   						$file = $c_single_entry_point;
   					}
   					$link .= '/'.$file;
   				} else {
   					$file = $_SERVER['PHP_SELF'];
   					$file = str_replace('cron','commsy',$file);
   					$link = 'http://'.$_SERVER['HTTP_HOST'].$file;
   				}
   				$link .= '?cid='.$this->getItemID().'&mod=home&fct=index';
   				// link
   					
   				//content
   				$email_text_array = $this->getEmailTextArray();
   				$translator->setEmailTextArray($this->getEmailTextArray());
   				
   				$body  = '';
   				$body .= $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
   				$body .= "\n\n";
   				$body .= $translator->getEmailMessage('MAIL_BODY_HELLO', $user->getFullName());
   				$body .= "\n\n";
   				$body .= $translator->getEmailMessage('EMAIL_LOGIN_EXPIRATION_BODY');
   				$body .= "\n\n";
   				$body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $mod_user_first->getFullName(), $this->getTitle());
   				$body .= "\n\n";
   				$body .= $link;
   					
   				$mail->set_subject($subject);
   				$mail->set_message($body);
   				$mail->set_to($to);
   				
   				#$mail->setSendAsHTML();
   				if ( $mail->send() ) {
   					$cron_array['success'] = true;
   					$cron_array['success_text'] = 'send mail to '.$to;
   				} else {
   					$cron_array['success'] = false;
   					$cron_array['success_text'] = 'failed send mail to '.$to;
   				}
   			}
   		}
   	}
   	if($success){
   		$cron_array['success'] = true;
   		$cron_array['success_text'] = 'mails send';
   	} else {
   		$cron_array['success'] = true;
   		$cron_array['success_text'] = 'nothing to do';
   	}
   
   	$time_end = getmicrotime();
   	$time = round($time_end - $time_start,0);
   	$cron_array['time'] = $time;
   
   	unset($user_manager);
   	unset($user_list);
   
   	return $cron_array;
   }
   
   ##############################################################
   # archive unused rooms - BEGIN
   ##############################################################
   
   private function _cronArchiveUnusedRooms () {
   	$cron_array = array();
   	$cron_array['title'] = 'archive unused rooms';
   	$cron_array['description'] = 'if rooms (project and community) are unused for '.$this->getDaysUnusedBeforeArchivingRooms().' days this cron archives it';
   	$cron_array['success'] = false;
   	$cron_array['success_text'] = 'cron failed';
   
   	$days_mail_send_before = $this->getDaysSendMailBeforeArchivingRooms();
   
   	// unused project rooms
   	// group rooms will be archived with project room
   	$count_project = 0;
   	$room_manager = $this->_environment->getProjectManager();
   	include_once('functions/date_functions.php');
   	$datetime_border = getCurrentDateTimeMinusDaysInMySQL($this->getDaysUnusedBeforeArchivingRooms());
   	$room_manager->setLastLoginOlderLimit($datetime_border);
   	$room_manager->setContextLimit($this->getItemID());
   	$room_manager->setNotTemplateLimit();
   	$room_manager->select();
   	$room_list = $room_manager->get();
   	$count_project_all = 0;
   	if ( !empty($room_list)
   	     and $room_list->isNotEmpty()
   	   ) {
   		$count_project_all = $room_list->getCount();
   		$datetime_border_send_mail = getCurrentDateTimeMinusHoursInMySQL(($this->getDaysSendMailBeforeArchivingRooms()-0.5)*24);
   		$datetime_border_send_mail2 = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeArchivingRooms()+21);
   		$room_item = $room_list->getFirst();
   		while ( $room_item ) {
   
   			$archive = true;
   			if ( !empty($days_mail_send_before) ) {
   				$send_mail_datetime = $room_item->getArchiveMailSendDateTime();
   					
   				// room will only archived configured days after sending email
   				if ( empty($send_mail_datetime)
   				     or $send_mail_datetime > $datetime_border_send_mail
   				   ) {
   					$archive = false;
   				}
   					
   				// maybe mail was send and user login into room
   				// after one period room will be archived without sending mail,
   				// because there is a datetime from sending mail a period before
   				// this if clause reset the datetime of sending the email
   				// $datetime_border_send_mail = 3 weeks before border to send mail
   				elseif ( $send_mail_datetime < $datetime_border_send_mail2 ) {
   					$archive = false;
   					$room_item->setArchiveMailSendDateTime('');
   					$room_item->saveWithoutChangingModificationInformation();
   				}
   			}
   
   			if ( $archive ) {
   				$room_item->close();
   				$room_item->save();
   				$room_item->moveToArchive();
   				$count_project++;
   			}
   
   			unset($room_item);
   			$room_item = $room_list->getNext();
   		}
   	}
   	unset($room_list);
   	unset($room_manager);
   
   	// unused community rooms
   	$count_community = 0;
   	$room_manager = $this->_environment->getCommunityManager();
   	include_once('functions/date_functions.php');
   	$datetime_border = getCurrentDateTimeMinusDaysInMySQL($this->getDaysUnusedBeforeArchivingRooms());
   	$room_manager->setLastLoginOlderLimit($datetime_border);
   	$room_manager->setContextLimit($this->getItemID());
   	$room_manager->setNotTemplateLimit();
   	$room_manager->select();
   	$room_list = $room_manager->get();
   	$count_community_all = 0;
   	if ( !empty($room_list)
   	     and $room_list->isNotEmpty()
   	   ) {
   		$count_community_all = $room_list->getCount();
   		$datetime_border_send_mail = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeArchivingRooms());
   		$datetime_border_send_mail2 = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeArchivingRooms()+21);
   		$room_item = $room_list->getFirst();
   		while ( $room_item ) {
   
   			$archive = true;
   			if ( !empty($days_mail_send_before) ) {
   				$send_mail_datetime = $room_item->getArchiveMailSendDateTime();
   					
   				// room will only archived configured days after sending email
   				if ( empty($send_mail_datetime)
   				     or $send_mail_datetime > $datetime_border_send_mail
   				   ) {
   					$archive = false;
   				}
   					
   				// maybe mail was send and user login into room
   				// after one period room will be archived without sending mail,
   				// because there is a datetime from sending mail a period before
   				// this if clause reset the datetime of sending the email
   				// $datetime_border_send_mail = 3 weeks before border to send mail
   				elseif ( $send_mail_datetime < $datetime_border_send_mail2 ) {
   					$archive = false;
   					$room_item->setArchiveMailSendDateTime('');
   					$room_item->saveWithoutChangingModificationInformation();
   				}
   			}
   
   			if ( $archive ) {
   				$room_item->close();
   				$room_item->save();
   				$room_item->moveToArchive();
   				$count_community++;
   			}
   
   			unset($room_item);
   			$room_item = $room_list->getNext();
   		}
   	}
   	unset($room_list);
   	unset($room_manager);
   
   	$cron_array['success'] = true;
   	$cron_array['success_text'] = 'archive project rooms: '.$count_project. ' (possible: '.$count_project_all.') - archive community rooms: '.$count_community.' (possible: '.$count_community_all.')';
   
   	return $cron_array;
   }
    
   private function _cronArchiveUnusedRoomsSendMailBefore () {
   	$cron_array = array();
   	$cron_array['title'] = 'send mail before archive unused rooms';
   	$cron_array['description'] = 'if rooms are unused for '.($this->getDaysUnusedBeforeArchivingRooms()-$this->getDaysSendMailBeforeArchivingRooms()).' days this cron sends a notifications about archiving the room in '.$this->getDaysSendMailBeforeArchivingRooms().' days';
   	$cron_array['success'] = false;
   	$cron_array['success_text'] = 'cron failed';
   
   	$days_mail_send_before = $this->getDaysSendMailBeforeArchivingRooms();
   
   	if ( !empty($days_mail_send_before) ) {
   		// unused project rooms
   		// group rooms will be archived with project room
   		$count_project = 0;
   		$room_manager = $this->_environment->getProjectManager();
   		include_once('functions/date_functions.php');
   		$datetime_border = getCurrentDateTimeMinusDaysInMySQL($this->getDaysUnusedBeforeArchivingRooms()-$this->getDaysSendMailBeforeArchivingRooms());
   		$room_manager->setLastLoginOlderLimit($datetime_border);
   		$room_manager->setContextLimit($this->getItemID());
   	   $room_manager->setNotTemplateLimit();
   		$room_manager->select();
   		$room_list = $room_manager->get();
   		$count_project_all = 0;
   		if ( !empty($room_list)
   		     and $room_list->isNotEmpty()
   		   ) {
   			$count_project_all = $room_list->getCount();
   			$datetime_border_send_mail = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeArchivingRooms());
   			$datetime_border_send_mail2 = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeArchivingRooms()+21);
   			$room_item = $room_list->getFirst();
   			while ( $room_item ) {
   				 
   				$send_mail = true;
   				$send_mail_datetime = $room_item->getArchiveMailSendDateTime();
   
   				if ( !empty($send_mail_datetime)
   				     and !($send_mail_datetime < $datetime_border_send_mail2)
   				   ) {
   					$send_mail = false;
   				}
   
   				if ( $send_mail ) {
   
   					// send mail
   					$success = $room_item->sendMailArchiveInfoToModeration();
   
   					// save room
   					include_once('functions/date_functions.php');
   					$room_item->setArchiveMailSendDateTime(getCurrentDateTimeInMySQL());
   					$room_item->saveWithoutChangingModificationInformation();
   					$count_project++;
   				}
   				 
   				unset($room_item);
   				$room_item = $room_list->getNext();
   			}
   		}
   		unset($room_list);
   		unset($room_manager);
   
   		// unused community rooms
   		$count_community = 0;
   		$room_manager = $this->_environment->getCommunityManager();
   		include_once('functions/date_functions.php');
   		$datetime_border = getCurrentDateTimeMinusDaysInMySQL($this->getDaysUnusedBeforeArchivingRooms()-$this->getDaysSendMailBeforeArchivingRooms());
   		$room_manager->setLastLoginOlderLimit($datetime_border);
   		$room_manager->setContextLimit($this->getItemID());
   	   $room_manager->setNotTemplateLimit();
   		$room_manager->select();
   		$room_list = $room_manager->get();
   		$count_community_all = 0;
   		if ( !empty($room_list)
   		     and $room_list->isNotEmpty()
   		   ) {
   			$count_community_all = $room_list->getCount();
   			$datetime_border_send_mail = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeArchivingRooms());
   			$datetime_border_send_mail2 = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeArchivingRooms()+21);
   			$room_item = $room_list->getFirst();
   			while ( $room_item ) {
   				 
   				$send_mail = true;
   				$send_mail_datetime = $room_item->getArchiveMailSendDateTime();
   
   				if ( !empty($send_mail_datetime)
   				     and !($send_mail_datetime < $datetime_border_send_mail2)
   				   ) {
   					$send_mail = false;
   				}
   
   				if ( $send_mail ) {
   
   					// send mail
   					$success = $room_item->sendMailArchiveInfoToModeration();
   
   					// save room
   					include_once('functions/date_functions.php');
   					$room_item->setArchiveMailSendDateTime(getCurrentDateTimeInMySQL());
   					$room_item->saveWithoutChangingModificationInformation();
   					$count_community++;
   				}
   				 
   				unset($room_item);
   				$room_item = $room_list->getNext();
   			}
   		}
   		unset($room_list);
   		unset($room_manager);
   	}
   
   	$cron_array['success'] = true;
   	$cron_array['success_text'] = 'send archive info project rooms: '.$count_project. ' (possible: '.$count_project_all.') - send archive info community rooms: '.$count_community.' (possible: '.$count_community_all.')';
   	return $cron_array;
   }
    
   ##############################################################
   # archive unused rooms - END
   ##############################################################

   ##############################################################
   # delete unused rooms - BEGIN
   ##############################################################
    
   private function _cronDeleteUnusedRooms () {
   	$cron_array = array();
   	$cron_array['title'] = 'delete unused rooms';
   	$cron_array['description'] = 'if rooms (project and community) are unused for '.$this->getDaysUnusedBeforeDeletingRooms().' days this cron deletes it';
   	$cron_array['success'] = false;
   	$cron_array['success_text'] = 'cron failed';
   	 
   	$days_mail_send_before = $this->getDaysSendMailBeforeDeletingRooms();
   	 
   	// unused project rooms
   	// group rooms will be deleted with project room
   	$count_project = 0;
   	$room_manager = $this->_environment->getProjectManager();
   	include_once('functions/date_functions.php');
   	$datetime_border = getCurrentDateTimeMinusDaysInMySQL($this->getDaysUnusedBeforeDeletingRooms());
   	$room_manager->setLastLoginOlderLimit($datetime_border);
   	$room_manager->setContextLimit($this->getItemID());
   	#$room_manager->setNotTemplateLimit();
   	$room_manager->select();
   	$room_list = $room_manager->get();
   	$count_project_all = 0;
   	if ( !empty($room_list)
   			and $room_list->isNotEmpty()
   	   ) {
   		$count_project_all = $room_list->getCount();
   		$datetime_border_send_mail = getCurrentDateTimeMinusHoursInMySQL(($this->getDaysSendMailBeforeDeletingRooms()-0.5)*24);
   		$datetime_border_send_mail2 = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeDeletingRooms()+21);
   		$room_item = $room_list->getFirst();
   		while ( $room_item ) {
   			 
   			$delete = true;
   			if ( !empty($days_mail_send_before) ) {
   				$send_mail_datetime = $room_item->getDeleteMailSendDateTime();
   
   				// room will only deleted configured days after sending email
   				if ( empty($send_mail_datetime)
   					  or $send_mail_datetime > $datetime_border_send_mail
   				   ) {
   					$delete = false;
   				}
   
   				// maybe mail was send and user login into room
   				// after one period room will be deleted without sending mail,
   				// because there is a datetime from sending mail a period before
   				// this if clause reset the datetime of sending the email
   				// $datetime_border_send_mail = 3 weeks before border to send mail
   				elseif ( $send_mail_datetime < $datetime_border_send_mail2 ) {
   					$delete = false;
   					$room_item->setDeleteMailSendDateTime('');
   					$room_item->saveWithoutChangingModificationInformation();
   				}
   			}
   			 
   			if ( $delete ) {
   				$room_item->delete();
   				$count_project++;
   			}
   			 
   			unset($room_item);
   			$room_item = $room_list->getNext();
   		}
   	}
   	unset($room_list);
   	unset($room_manager);
   	 
   	// unused community rooms
   	$count_community = 0;
   	$room_manager = $this->_environment->getCommunityManager();
   	include_once('functions/date_functions.php');
   	$datetime_border = getCurrentDateTimeMinusDaysInMySQL($this->getDaysUnusedBeforeDeletingRooms());
   	$room_manager->setLastLoginOlderLimit($datetime_border);
   	$room_manager->setContextLimit($this->getItemID());
   	#$room_manager->setNotTemplateLimit();
   	$room_manager->select();
   	$room_list = $room_manager->get();
   	$count_community_all = 0;
   	if ( !empty($room_list)
   			and $room_list->isNotEmpty()
   	   ) {
   		$count_community_all = $room_list->getCount();
   		$datetime_border_send_mail = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeDeletingRooms());
   		$datetime_border_send_mail2 = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeDeletingRooms()+21);
   		$room_item = $room_list->getFirst();
   		while ( $room_item ) {
   			 
   			$delete = true;
   			if ( !empty($days_mail_send_before) ) {
   				$send_mail_datetime = $room_item->getDeleteMailSendDateTime();
   
   				// room will only deleted configured days after sending email
   				if ( empty($send_mail_datetime)
   					  or $send_mail_datetime > $datetime_border_send_mail
   				   ) {
   					$delete = false;
   				}
   
   				// maybe mail was send and user login into room
   				// after one period room will be deleted without sending mail,
   				// because there is a datetime from sending mail a period before
   				// this if clause reset the datetime of sending the email
   				// $datetime_border_send_mail = 3 weeks before border to send mail
   				elseif ( $send_mail_datetime < $datetime_border_send_mail2 ) {
   					$delete = false;
   					$room_item->setDeleteMailSendDateTime('');
   					$room_item->saveWithoutChangingModificationInformation();
   				}
   			}
   			 
   			if ( $delete ) {
   				$room_item->delete();
   				$count_community++;
   			}
   			 
   			unset($room_item);
   			$room_item = $room_list->getNext();
   		}
   	}
   	unset($room_list);
   	unset($room_manager);
   	 
   	$cron_array['success'] = true;
   	$cron_array['success_text'] = 'delete project rooms: '.$count_project. ' (possible: '.$count_project_all.') - delete community rooms: '.$count_community.' (possible: '.$count_community_all.')';
   	 
   	return $cron_array;
   }
   
   private function _cronDeleteUnusedRoomsSendMailBefore () {
   	$cron_array = array();
   	$cron_array['title'] = 'send mail before delete unused rooms';
   	$cron_array['description'] = 'if rooms are unused for '.($this->getDaysUnusedBeforeDeletingRooms()-$this->getDaysSendMailBeforeDeletingRooms()).' days this cron sends a notifications about deleting the room in '.$this->getDaysSendMailBeforeDeletingRooms().' days';
   	$cron_array['success'] = false;
   	$cron_array['success_text'] = 'cron failed';
   	 
   	$days_mail_send_before = $this->getDaysSendMailBeforeDeletingRooms();
   	 
   	if ( !empty($days_mail_send_before) ) {
   		// unused project rooms
   		// group rooms will be archived with project room
   		$count_project = 0;
   		$room_manager = $this->_environment->getProjectManager();
   		include_once('functions/date_functions.php');
   		$datetime_border = getCurrentDateTimeMinusDaysInMySQL($this->getDaysUnusedBeforeDeletingRooms()-$this->getDaysSendMailBeforeDeletingRooms());
   		$room_manager->setLastLoginOlderLimit($datetime_border);
   		$room_manager->setContextLimit($this->getItemID());
   		#$room_manager->setNotTemplateLimit();
   		$room_manager->select();
   		$room_list = $room_manager->get();
   		$count_project_all = 0;
   		if ( !empty($room_list)
   				and $room_list->isNotEmpty()
   		   ) {
   			$count_project_all = $room_list->getCount();
   			$datetime_border_send_mail = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeDeletingRooms());
   			$datetime_border_send_mail2 = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeDeletingRooms()+21);
   			$room_item = $room_list->getFirst();
   			while ( $room_item ) {
   
   				$send_mail = true;
   				$send_mail_datetime = $room_item->getDeleteMailSendDateTime();
   				 
   				if ( !empty($send_mail_datetime)
   					  and !($send_mail_datetime < $datetime_border_send_mail2)
   				   ) {
   					$send_mail = false;
   				}
   				 
   				if ( $send_mail ) {
   					 
   					// send mail
   					$success = $room_item->sendMailDeleteInfoToModeration();
   					 
   					// save room
   					include_once('functions/date_functions.php');
   					$room_item->setDeleteMailSendDateTime(getCurrentDateTimeInMySQL());
   					$room_item->saveWithoutChangingModificationInformation();
   					$count_project++;
   				}
   
   				unset($room_item);
   				$room_item = $room_list->getNext();
   			}
   		}
   		unset($room_list);
   		unset($room_manager);
   		 
   		// unused community rooms
   		$count_community = 0;
   		$room_manager = $this->_environment->getCommunityManager();
   		include_once('functions/date_functions.php');
   		$datetime_border = getCurrentDateTimeMinusDaysInMySQL($this->getDaysUnusedBeforeDeletingRooms()-$this->getDaysSendMailBeforeDeletingRooms());
   		$room_manager->setLastLoginOlderLimit($datetime_border);
   		$room_manager->setContextLimit($this->getItemID());
   		#$room_manager->setNotTemplateLimit();
   		$room_manager->select();
   		$room_list = $room_manager->get();
   		$count_community_all = 0;
   		if ( !empty($room_list)
   				and $room_list->isNotEmpty()
   		   ) {
   			$count_community_all = $room_list->getCount();
   			$datetime_border_send_mail = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeDeletingRooms());
   			$datetime_border_send_mail2 = getCurrentDateTimeMinusDaysInMySQL($this->getDaysSendMailBeforeDeletingRooms()+21);
   			$room_item = $room_list->getFirst();
   			while ( $room_item ) {
   
   				$send_mail = true;
   				$send_mail_datetime = $room_item->getDeleteMailSendDateTime();
   				 
   				if ( !empty($send_mail_datetime)
   					  and !($send_mail_datetime < $datetime_border_send_mail2)
   				   ) {
   					$send_mail = false;
   				}
   				 
   				if ( $send_mail ) {
   					 
   					// send mail
   					$success = $room_item->sendMailDeleteInfoToModeration();
   					 
   					// save room
   					include_once('functions/date_functions.php');
   					$room_item->setDeleteMailSendDateTime(getCurrentDateTimeInMySQL());
   					$room_item->saveWithoutChangingModificationInformation();
   					$count_community++;
   				}
   
   				unset($room_item);
   				$room_item = $room_list->getNext();
   			}
   		}
   		unset($room_list);
   		unset($room_manager);
   	}
   	 
   	$cron_array['success'] = true;
   	$cron_array['success_text'] = 'send delete info project rooms: '.$count_project. ' (possible: '.$count_project_all.') - send delete info community rooms: '.$count_community.' (possible: '.$count_community_all.')';
   	return $cron_array;
   }
   
   ##############################################################
   # delete unused rooms - END
   ##############################################################
    
   /** cron log, INTERNAL
    *  daily cron
    *
    * @return array results of running this cron
    */
   function _cronPageImpressionAndUserActivity () {
      include_once('functions/misc_functions.php');
      $time_start = getmicrotime();

      $cron_array = array();
      $cron_array['title'] = 'page impression and user activity cron';
      $cron_array['description'] = 'count page impressions and user activity';
      $cron_array['success'] = true;
      $cron_array['success_text'] = 'cron failed';

      $log_manager = $this->_environment->getLogManager();

      $room_list = $this->getRoomList();
      $count_rooms = 0;
      
      if ($room_list->isNotEmpty()) {
         $room_item = $room_list->getFirst();
         while ($room_item) {
            // get latest timestamp of page impressions and user actitivty
            // from extra field PIUA_LAST
            $piua_last = $room_item->getPageImpressionAndUserActivityLast();

            if(!empty($piua_last)) {
               $oldest_date = $piua_last;
            } else {
               // if there is no entry take creation_date
               $creation_date = $room_item->getCreationDate();
               $oldest_date = getYearFromDateTime($creation_date) .
                    getMonthFromDateTime($creation_date) .
                    getDayFromDateTime($creation_date);
            }

            $current_date = getCurrentDate();
            $day_diff = getDifference($oldest_date, $current_date);
            $pi_array = $room_item->getPageImpressionArray();
            $ua_array = $room_item->getUserActivityArray();
            $pi_input = array();
            $ua_input = array();

            // for each day, get page impressions and user activity
            for($i=1;$i < $day_diff;$i++) {
               $log_manager->resetLimits();
               $log_manager->setContextLimit($room_item->getItemID());
               $log_manager->setRequestLimit("/room/");
               $older_limit_stamp = datetime2Timestamp(date("Y-m-d 00:00:00"))-($i-1)*86400;
               $older_limit = date('Y-m-d', $older_limit_stamp);
               $log_manager->setTimestampOlderLimit($older_limit);
               $log_manager->setTimestampNotOlderLimit($i);

               $pi_input[] = $log_manager->getCountAll();
               $ua_input[] = $log_manager->countWithUserDistinction();
            }

            // put actual date in extra field PIUA_LAST
            $room_item->setPageImpressionAndUserActivityLast($current_date);
            $room_item->setPageImpressionArray(array_merge($pi_input, $pi_array));
            $room_item->setUserActivityArray(array_merge($ua_input, $ua_array));
            $room_item->saveWithoutChangingModificationInformation();

            $count_rooms++;
            unset($room_item);
            $room_item = $room_list->getNext();
         }
      }
      unset($room_list);
      unset($log_manager);
      
      $cron_array['success_text'] = 'count page impressions and user activity of '.$count_rooms.' rooms';

      $time_end = getmicrotime();
      $time = round($time_end - $time_start,0);
      $cron_array['time'] = $time;

      return $cron_array;
   }
   
   function _cronCheckTimeLabels () {
      include_once('functions/misc_functions.php');
      $time_start = getmicrotime();

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

         unset($time_label);
         unset($time_manager);
         unset($last_time_item);
         $retour['success'] = true;
         $retour['success_text'] = 'insert new time label: '.$title;
      } else {
         $retour['success'] = true;
         $retour['success_text'] = 'nothing to do';
      }
      unset($time_list);

      $time_end = getmicrotime();
      $time = round($time_end - $time_start,0);
      $retour['time'] = $time;

      return $retour;
   }

   function _cronRenewContinuousLinks () {
      include_once('functions/misc_functions.php');
      $time_start = getmicrotime();

      $retour = array();
      $retour['title'] = 'renew links';
      $retour['description'] = 'renew links between continuous rooms and current time label';
      $retour['success'] = false;
      $retour['success_text'] = 'cron failed';

      $count = 0;

      $current_time = $this->getCurrentTimeItem();
      if ( isset($current_time) ) {
         $cont_room_list = $this->getContinuousRoomListNotLinkedToTime($current_time);
         if ( isset($cont_room_list)
              and $cont_room_list->isNotEmpty()
            ) {
            $cont_room_item = $cont_room_list->getFirst();
            while ($cont_room_item) {
               $cont_room_item->setContinuous();
               $cont_room_item->saveWithoutChangingModificationInformation();
               $count++;
               unset($cont_room_item);
               $cont_room_item = $cont_room_list->getNext();
            }
         }
         unset($cont_room_list);
         unset($current_time);
         if ( $count > 0 ) {
            $retour['success'] = true;
            $retour['success_text'] = 'renew links between '.$count.' continuous rooms and current time label';
         } else {
            $retour['success'] = true;
            $retour['success_text'] = 'nothing to do';
         }
      }

      $time_end = getmicrotime();
      $time = round($time_end - $time_start,0);
      $retour['time'] = $time;

      return $retour;
   }

   function _cronSyncCountRooms () {
      include_once('functions/misc_functions.php');
      $time_start = getmicrotime();

      $retour = array();
      $retour['title'] = 'sync count rooms';
      $retour['description'] = 'sync redundancy saved count rooms in portal item';
      $retour['success'] = false;
      $retour['success_text'] = 'cron failed';

      if ( $this->isCountRoomRedundancy() ) {
         $this->syncCountRoomRedundancy();
         $retour['success'] = true;
         $retour['success_text'] = 'sync count rooms successfully';
      } else {
         $retour['success'] = true;
         $retour['success_text'] = 'nothing to do';
      }

      $time_end = getmicrotime();
      $time = round($time_end - $time_start,0);
      $retour['time'] = $time;

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
      $translator = $this->_environment->getTranslationObject();
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
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = $translator->getMessage('USAGE_INFO_HEADER');
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
      $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      $this->_addExtra('USAGE_INFO_HEADER',$value_array);
   }

   function getUsageInfoHeaderForRubricForm($rubric){
      $translator = $this->_environment->getTranslationObject();
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
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = $translator->getMessage('USAGE_INFO_HEADER');
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
      $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
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
      $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
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
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $translator = $this->_environment->getTranslationObject();
         $mod = $this->_environment->getCurrentModule();
         $fct = $this->_environment->getCurrentFunction();
         if ($mod == 'configuration' and $fct == 'time'){ // no link in message tag
            $retour = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_TIME_FORM');
            $temp = 'CONFIGURATION_TIME';
         } else {
            $temp = mb_strtoupper($rubric, 'UTF-8').'_'.mb_strtoupper($funct, 'UTF-8');
            $tempMessage = "";
            // ---> Remark for testing: Login as root, "Configure Portal" <---
            switch( $temp )
            {
               case 'ACCOUNT_ACTION':        // getestet: eine Kennung bearbeiten
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_ACCOUNT_ACTION_FORM');
                  break;
               case 'ACCOUNT_EDIT':          // getestet: eine Kennung bearbeiten
                  $tempMessage = $translator->getMessage('USAGE_INFO_FORM_COMING_SOON');
                  break;
               case 'ACCOUNT_STATUS':        // getestet: Benutzer Status ändern (als Root/Moderator)
                  $tempMessage = $translator->getMessage('USAGE_INFO_FORM_COMING_SOON');
                  break;
               case 'COMMUNITY_EDIT':        // getestet: Gemeinschaftsraum neu eröffnen
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_COMMUNITY_EDIT_FORM');
                  break;
               case 'CONFIGURATION_AGB':     // getestet: Portal / Einstellungen / Nutzungsvereinbarungen und Textareas mit Extra-Tags
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_AGB_FORM');
                  break;
               case 'CONFIGURATION_AUTHENTICATION':  // getestet
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_AUTHENTICATION_FORM');
                  break;
               case 'CONFIGURATION_COMMON':  // getestet: als root irgendeinen Raum anklicken, dann oben rechts "Raum bearbeiten"
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_COMMON_FORM');
                  break;
               case 'CONFIGURATION_DEFAULTS': // getestet
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_DEFAULTS_FORM');
                  break;
               case 'CONFIGURATION_EXPORT':  // getestet: als root irgendeinen Raum anklicken, dann oben rechts
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_EXPORT_FORM');
                  break;
               case 'CONFIGURATION_MAIL':    // getestet und Textareas mit Extra-Tags
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_MAIL_FORM');
                  break;
               case 'CONFIGURATION_MOVE':    // getestet und Textareas mit Extra-Tags
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_MOVE_FORM');
                  break;
               case 'CONFIGURATION_NEWS':    // getestet Portal-Ankündigungen bearbeiten und Textareas mit Extra-Tags
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_NEWS_FORM');
                  break;
               case 'CONFIGURATION_PORTALHOME': // getestet
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PORTALHOME_FORM');
                  break;
               case 'CONFIGURATION_PORTALUPLOAD':
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PORTALUPLOAD_FORM');
                  break;
               case 'CONFIGURATION_PREFERENCES': // getestet
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PREFERENCES_FORM');
                  break;
               case 'CONFIGURATION_ROOM_OPENING': // getestet Voreinst. f. Räume, z. B. Schule, Uni, Business
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_ROOM_OPENING_FORM');
                  break;
               case 'CONFIGURATION_SERVICE': // getestet Handhabungssupport
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_SERVICE_FORM');
                  break;
               case 'CONFIGURATION_WIKI':    // getestet Einstellungen Raum-Wiki
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_WIKI_FORM');
                  break;
               case 'CONFIGURATION_AUTOACCOUNTS':    // getestet Einstellungen Raum-Wiki
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_AUTOACCOUNTS_FORM');
                  break;
               case 'PROJECT_EDIT':          // getestet: Projektraum neu eröffnen
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_PROJECT_EDIT_FORM');
                  break;
               case 'MAIL_TO_MODERATOR':      //
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_MAIL_TO_MODERATOR_FORM');
                  break;
               case 'MAIL_PROCESS':      //
                  $tempMessage = $translator->getMessage('USAGE_INFO_FORM_COMING_SOON');
                  break;
               case 'LANGUAGE_UNUSED':      //
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_LANGUAGE_UNUSED_FORM');
                  break;
               case 'CONFIGURATION_PLUGIN':    // getestet Einstellungen Raum-Wiki
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PLUGIN_FORM');
                  break;
               case 'ACCOUNT_PASSWORD':    // getestet Einstellungen Raum-Wiki
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_ACCOUNT_PASSWORD_FORM');
                  break;
               case 'CONFIGURATION_HTMLTEXTAREA':    // getestet Einstellungen Raum-Wiki
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_HTMLTEXTAREA_FORM');
                  break;
               case 'CONFIGURATION_PLUGINS':    // getestet Einstellungen Plugins
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PLUGINS_FORM');
                  break;
               case 'CONFIGURATION_LANGUAGE':
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_LANGUAGE_FORM');
                  break;
               case 'CONFIGURATION_DATASECURITY':
               	  $tempMessage = $translator->getMessage('USAGE_INFO_COMING_SOON');
               	  break;
               case 'CONFIGURATION_INACTIVE':
               	  $tempMessage = $translator->getMessage('USAGE_INFO_COMING_SOON');
               	  break;
               case 'CONFIGURATION_INACTIVEPROCESS':
               	  $tempMessage = $translator->getMessage('USAGE_INFO_COMING_SOON');
               	  break;
               case 'CONFIGURATION_EXPORT_IMPORT':
                  $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_EXPORT_IMPORT_FORM');
                  break;
               default:
                  $tempMessage = $translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_portal_item(".__LINE__.")";
                  break;
            }
            $retour = $tempMessage;
         }
         if ($retour == 'USAGE_INFO_TEXT_PORTAL_FOR_'.$temp.'_FORM' or $retour =='tbd'){
            $retour = $translator->getMessage('USAGE_INFO_FORM_COMING_SOON');
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
      $retour = NULL;
      if ( !isset($this->_cache_auth_source_list) ) {
         $manager = $this->_environment->getAuthSourceManager();
         $manager->setContextLimit($this->getItemID());
         $manager->select();
         $retour = $manager->get();
         if ( $this->_cache_on ) {
            $this->_cache_auth_source_list = $retour;
         }
      } else {
         $retour = $this->_cache_auth_source_list;
      }
      return $retour;
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
            if ( !$item->show() or mb_strtoupper($item->getSourceType(), 'UTF-8') != 'CAS' ) {
               $list->removeElement($item);
            }
            $item = $list->getNext();
         }
      }
      return $list;
   }

   function getAuthSourceListTypo3WebEnabled () {
      $list = $this->getAuthSourceList();
      if ( !$list->isEmpty() ) {
         $item = $list->getFirst();
         while ( $item ) {
            if ( !$item->show() or mb_strtoupper($item->getSourceType(), 'UTF-8') != 'TYPO3WEB' ) {
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

   function getCountAuthSourceListEnabled () {
      $retour = 0;
      $list = $this->getAuthSourceListEnabled();
      if ( isset($list) ) {
         $retour = $list->getcount();
      }
      return $retour;
   }

   public function setShowAuthAtLogin () {
      $this->_addExtra('AUTH_SHOW_LOGIN',1);
   }

   public function setNotShowAuthAtLogin () {
      $this->_addExtra('AUTH_SHOW_LOGIN',-1);
   }

   private function _getShowAuthAtLogin () {
      $retour = '';
      if ($this->_issetExtra('AUTH_SHOW_LOGIN')) {
         $value = $this->_getExtra('AUTH_SHOW_LOGIN');
         if ( !empty($value) ) {
            $retour = $value;
         }
      }
      return $retour;
   }

   public function showAuthAtLogin () {
      $retour = true;
      $show = $this->_getShowAuthAtLogin();
      if ( !empty($show)
           and $show == -1
         ) {
         $retour = false;
      }
      return $retour;
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
         $retour = $translator->getMessageInLang(mb_strtolower($language, 'UTF-8'),'HOMEPAGE_PAGE_ROOT_TITLE').' '.$translator->getMessageInLang(mb_strtolower($language, 'UTF-8'),'COMMON_IN').' ...';
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
      $desc_array[mb_strtoupper($language, 'UTF-8')] = $value;
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
      $desc_array[mb_strtoupper($language, 'UTF-8')] = $value;
      $this->setDescriptionWellcome2Array($desc_array);
   }

   public function showAllwaysPrivateRoomLink () {
      $retour = true;
      $value = $this->_getShowPrivateRoomLink();
      if ( $value == -1 ) {
         $retour = false;
      }
      return $retour;
   }

   private function _getShowPrivateRoomLink () {
      $retour = 1;
      if ($this->_issetExtra('SHOW_PRIVATE_ROOM_LINK')) {
         $retour = $this->_getExtra('SHOW_PRIVATE_ROOM_LINK');
      }
      return $retour;
   }

   private function _setShowPrivateRoomLink ($value) {
      $this->_setExtra('SHOW_PRIVATE_ROOM_LINK',(int)$value);
   }

   public function setShowAllwaysPrivateRoomLink () {
      $this->_setShowPrivateRoomLink(1);
   }

   public function unsetShowAllwaysPrivateRoomLink () {
      $this->_setShowPrivateRoomLink(-1);
   }

   ######################################################
   # don't show news from server on portal

   public function _setNewsFromServerShow ($value) {
      $this->_setServerNews('show_news_form_server',$value);
   }

   public function setDontShowNewsFromServer () {
      $this->_setNewsFromServerShow(-1);
   }

   public function setShowNewsFromServer () {
      $this->_setNewsFromServerShow(1);
   }

   public function showNewsFromServer () {
      $retour = false;
      $show_news = $this->_getNewsFromServerShow();
      if ($show_news == 1) {
         $retour = true;
      }
      return $retour;
   }

   private function _getNewsFromServerShow () {
      return $this->_getServerNews('show_news_form_server');
   }

   public function isPluginActive ( $plugin ) {
      $retour = false;
      if ( $this->isPluginOn($plugin) ) {
         #$server_item = $this->_environment->getServerItem();
         #if ( $server_item->isPluginActive($plugin) ) {
            $retour = true;
         #}
      }
      return $retour;
   }
   
   // show tempates in room list

   private function _setShowTemplateInRoomList ($value) {
   	$this->_setExtra('SHOW_TEMPLATE_IN_ROOM_LIST',(int)$value);
   }
    
   private function _getShowTemplateInRoomList () {
      $retour = 1;
      if ($this->_issetExtra('SHOW_TEMPLATE_IN_ROOM_LIST')) {
         $retour = $this->_getExtra('SHOW_TEMPLATE_IN_ROOM_LIST');
      }
      return $retour;
   }
   
   public function showTemplatesInRoomList () {
   	$retour = true;
   	$value = $this->_getShowTemplateInRoomList();
   	if ( !empty($value)
   		  and $value == -1
   	   ) {
   		$retour = false;
   	}
   	return $retour;
   }
   
   public function setShowTemplatesInRoomListON () {
   	$this->_setShowTemplateInRoomList(1);
   }

   public function setShowTemplatesInRoomListOFF () {
   	$this->_setShowTemplateInRoomList(-1);
   }
    
   ############################################
   # archiving - BEGIN
   ############################################
   
   public function isActivatedArchivingUnusedRooms () {
   	$retour = false;
   	$status = $this->_getStatusArchivingUnusedRooms();
   	if ( !empty($status)
   	     and $status == 1
   	   ) {
   		$retour = true;
   	}
   	return $retour;
   }
    
   public function turnOnArchivingUnusedRooms () {
   	$this->_setStatusArchivingUnusedRooms(1);
   }
    
   public function turnOffArchivingUnusedRooms () {
   	$this->_setStatusArchivingUnusedRooms(-1);
   }
    
   /** get status of archiving unused rooms
    *
    * @return int status of archiving unused rooms (1 = on, -1 = off)
    */
   private function _getStatusArchivingUnusedRooms () {
   	$retour = -1;
   	if ($this->_issetExtra('ARCHIVING_ROOMS_STATUS')) {
   		$retour = $this->_getExtra('ARCHIVING_ROOMS_STATUS');
   	}
   	return $retour;
   }
   
   /** set status archiving unused rooms
    *
    * @param int status archiving unused rooms (1 = on, -1 = off)
    */
   private function _setStatusArchivingUnusedRooms ($value) {
   	$this->_addExtra('ARCHIVING_ROOMS_STATUS',(int)$value);
   }
    
   /** get days before archiving an unused room
    *
    * @return int days before archiving an unused room
    */
   public function getDaysUnusedBeforeArchivingRooms () {
   	$retour = 365; //default
   	if ($this->_issetExtra('ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_ARCHIVE')) {
   		$retour = $this->_getExtra('ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_ARCHIVE');
   	}
   	return $retour;
   }
   
   /** set days before archiving an unused room
    *
    * @param int days before archiving an unused room
    */
   public function setDaysUnusedBeforeArchivingRooms ($value) {
   	$this->_addExtra('ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_ARCHIVE',(int)$value);
   }
   
   /** get days send an email before archiving an unused room
    *
    * @return int days send email before archiving an unused room
    */
   public function getDaysSendMailBeforeArchivingRooms () {
   	$retour = 0;
   	if ($this->_issetExtra('ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_ARCHIVE')) {
   		$retour = $this->_getExtra('ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_ARCHIVE');
   	}
   	return $retour;
   }
   
   /** set days sed mail before archiving an unused room
    *
    * @param int days send mail before archiving an unused room
    */
   public function setDaysSendMailBeforeArchivingRooms ($value) {
   	$this->_addExtra('ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_ARCHIVE',(int)$value);
   }

   public function isActivatedDeletingUnusedRooms () {
   	$retour = false;
   	$status = $this->_getStatusDeletingUnusedRooms();
   	if ( !empty($status)
   			and $status == 1
   	) {
   		$retour = true;
   	}
   	return $retour;
   }
   
   public function turnOnDeletingUnusedRooms () {
   	$this->_setStatusDeletingUnusedRooms(1);
   }
   
   public function turnOffDeletingUnusedRooms () {
   	$this->_setStatusDeletingUnusedRooms(-1);
   }
   
   /** get status of deleting unused rooms
    *
    * @return int status of deleting unused rooms (1 = on, -1 = off)
    */
   private function _getStatusDeletingUnusedRooms () {
   	$retour = -1;
   	if ($this->_issetExtra('DELETING_ROOMS_STATUS')) {
   		$retour = $this->_getExtra('DELETING_ROOMS_STATUS');
   	}
   	return $retour;
   }
    
   /** set status deleting unused rooms
    *
    * @param int status deleting unused rooms (1 = on, -1 = off)
    */
   private function _setStatusDeletingUnusedRooms ($value) {
   	$this->_addExtra('DELETING_ROOMS_STATUS',(int)$value);
   }
   
   /** get days before deleting an unused archived room
    *
    * @return int days before deleting an unused archived room
    */
   public function getDaysUnusedBeforeDeletingRooms () {
   	$retour = 365; //default
   	if ($this->_issetExtra('ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_DELETE')) {
   		$retour = $this->_getExtra('ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_DELETE');
   	}
   	return $retour;
   }
    
   /** set days before deleting an unused archived room
    *
    * @param int days before deleting an unused archived room
    */
   public function setDaysUnusedBeforeDeletingRooms ($value) {
   	$this->_addExtra('ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_DELETE',(int)$value);
   }
    
   /** get days send an email before deleting an unused archived room
    *
    * @return int days send email before deleting an unused archived room
    */
   public function getDaysSendMailBeforeDeletingRooms () {
   	$retour = 0;
   	if ($this->_issetExtra('ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_DELETE')) {
   		$retour = $this->_getExtra('ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_DELETE');
   	}
   	return $retour;
   }
    
   /** set days sed mail before deleting an unused archived room
    *
    * @param int days send mail before deleting an unused archived room
    */
   public function setDaysSendMailBeforeDeletingRooms ($value) {
   	$this->_addExtra('ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_DELETE',(int)$value);
   }
    
   ############################################
   # archiving - END
   ############################################

   ############################################
   # count rooms
   ############################################

   /** get count project rooms in extras
    *
    * @return int count project rooms
    */
   private function _getCountProjectRoomsExtra () {
      $retour = 0;
      if ($this->_issetExtra('COUNT_ROOM_PROJECT')) {
         $retour = (int)$this->_getExtra('COUNT_ROOM_PROJECT');
      }
      return $retour;
   }

   /** set count project rooms in extras
    *
    * @param int count project rooms
    */
   private function _setCountProjectRoomsExtra ($value) {
      $this->_addExtra('COUNT_ROOM_PROJECT',(int)$value);
   }

   /** increase count project rooms in extras
    *
    * @param boolean save portal item? default = false
    */
   public function increaseCountProjectRoomsExtra ( $save = false ) {
      $this->_setCountProjectRoomsExtra((int)($this->_getCountProjectRoomsExtra()+1));
      if ( $save ) {
         $this->save();
      }
   }

   /** decrease count project rooms in extras
    *
    * @param boolean save portal item? default = false
    */
   public function decreaseCountProjectRoomsExtra ( $save = false ) {
    $this->_setCountProjectRoomsExtra((int)($this->_getCountProjectRoomsExtra()-1));
      if ( $save ) {
        $this->save();
      }
   }

   /** get count community rooms in extras
    *
    * @return int count community rooms
    */
   private function _getCountCommunityRoomsExtra () {
      $retour = 0;
      if ($this->_issetExtra('COUNT_ROOM_COMMUNITY')) {
         $retour = (int)$this->_getExtra('COUNT_ROOM_COMMUNITY');
      }
      return $retour;
   }

   /** set count community rooms in extras
    *
    * @param int count community rooms
    */
   private function _setCountCommunityRoomsExtra ($value) {
      $this->_addExtra('COUNT_ROOM_COMMUNITY',(int)$value);
   }

   /** increase count community rooms in extras
    *
    * @param boolean save portal item? default = false
    */
   public function increaseCountCommunityRoomsExtra ( $save = false ) {
      $this->_setCountCommunityRoomsExtra((int)($this->_getCountCommunityRoomsExtra()+1));
      if ( $save ) {
         $this->save();
      }
   }

   /** decrease count community rooms in extras
    *
    * @param boolean save portal item? default = false
    */
   public function decreaseCountCommunityRoomsExtra ( $save = false ) {
      $this->_setCountCommunityRoomsExtra((int)($this->_getCountCommunityRoomsExtra()-1));
      if ( $save ) {
         $this->save();
      }
   }

   /** get count group rooms in extras
    *
    * @return int count group rooms
    */
   private function _getCountGroupRoomsExtra () {
      $retour = 0;
      if ($this->_issetExtra('COUNT_ROOM_GROUP')) {
         $retour = (int)$this->_getExtra('COUNT_ROOM_GROUP');
      }
      return $retour;
   }

   /** set count group rooms in extras
    *
    * @param int count group rooms
    */
   private function _setCountGroupRoomsExtra ($value) {
      $this->_addExtra('COUNT_ROOM_GROUP',(int)$value);
   }

   /** increase count group rooms in extras
    *
    * @param boolean save portal item? default = false
    */
   public function increaseCountGroupRoomsExtra ( $save = false ) {
      $this->_setCountGroupRoomsExtra((int)($this->_getCountGroupRoomsExtra()+1));
      if ( $save ) {
         $this->save();
      }
   }

   /** decrease count group rooms in extras
    *
    * @param boolean save portal item? default = false
    */
   public function decreaseCountGroupRoomsExtra ( $save = false ) {
      $this->_setCountGroupRoomsExtra((int)($this->_getCountGroupRoomsExtra()-1));
      if ( $save ) {
         $this->save();
      }
   }

   /** get count private rooms in extras
    *
    * @return int count private rooms
    */
   private function _getCountPrivateRoomsExtra () {
      $retour = 0;
      if ($this->_issetExtra('COUNT_ROOM_PRIVATE')) {
         $retour = (int)$this->_getExtra('COUNT_ROOM_PRIVATE');
      }
      return $retour;
   }

   /** set count private rooms
    *
    * @param int count private rooms
    */
   private function _setCountPrivateRoomsExtra ($value) {
      $this->_addExtra('COUNT_ROOM_PRIVATE',(int)$value);
   }

   /** increase count private rooms in extras
    *
    * @param boolean save portal item? default = false
    */
   public function increaseCountPrivateRoomsExtra ( $save = false ) {
      $this->_setCountPrivateRoomsExtra((int)($this->_getCountPrivateRoomsExtra()+1));
      if ( $save ) {
         $this->save();
      }
   }

   /** decrease count private rooms in extras
    *
    * @param boolean save portal item? default = false
    */
   public function decreaseCountPrivateRoomsExtra ( $save = false ) {
      $this->_setCountPrivateRoomsExtra((int)($this->_getCountPrivateRoomsExtra()-1));
      if ( $save ) {
         $this->save();
      }
   }

   /** get count project rooms from manager
    *
    * @return int count project rooms
    */
   private function _getCountProjectRoomsManager () {
      if (!isset($this->_project_list_count)) {
         $manager = $this->_environment->getProjectManager();
         $manager->setContextLimit($this->getItemID());
         $this->_project_list_count = $manager->getCountAll();
         unset($manager);
      }
      return $this->_project_list_count;
   }

   /** get count community rooms from manager
    *
    * @return int count community rooms
    */
   private function _getCountCommunityRoomsManager () {
      if (!isset($this->_community_list_count)) {
         $manager = $this->_environment->getCommunityManager();
         $manager->setContextLimit($this->getItemID());
         $this->_community_list_count = $manager->getCountAll();
         unset($manager);
      }
      return $this->_community_list_count;
   }

   /** get count group rooms from manager
    *
    * @return int count group rooms
    */
   private function _getCountGroupRoomsManager () {
      if (!isset($this->_grouproom_list_count)) {
         $manager = $this->_environment->getGrouproomManager();
         $manager->setContextLimit($this->getItemID());
         $this->_grouproom_list_count = $manager->getCountAll();
         unset($manager);
      }
      return $this->_grouproom_list_count;
   }

   /** get count private rooms from manager
    *
    * @return int count private rooms
    */
   private function _getCountPrivateRoomsManager () {
      if (!isset($this->_private_list_count)) {
         $manager = $this->_environment->getPrivateRoomManager();
         $manager->setContextLimit($this->getItemID());
         $this->_private_list_count = $manager->getCountAll();
         unset($manager);
      }
      return $this->_private_list_count;
   }

   function getCountProjectRooms () {
      $retour = 0;
      if ( $this->isCountRoomRedundancy() ) {
         $retour = $this->_getCountProjectRoomsExtra();
         if ( empty($retour) ) {
            $this->_syncCountProjectRoomRedundancy(true);
            $retour = $this->_getCountProjectRoomsExtra();
         }
      } else {
         $retour = $this->_getCountProjectRoomsManager();
      }
      return $retour;
   }

   function getCountCommunityRooms () {
      $retour = 0;
      if ( $this->isCountRoomRedundancy() ) {
         $retour = $this->_getCountCommunityRoomsExtra();
         if ( empty($retour) ) {
            $this->_syncCountCommunityRoomRedundancy(true);
            $retour = $this->_getCountCommunityRoomsExtra();
         }
      } else {
         $retour = $this->_getCountCommunityRoomsManager();
      }
      return $retour;
   }

   function getCountGroupRooms () {
      $retour = 0;
      if ( $this->isCountRoomRedundancy() ) {
         $retour = $this->_getCountGroupRoomsExtra();
         if ( empty($retour) ) {
            $this->_syncCountGroupRoomRedundancy(true);
            $retour = $this->_getCountGroupRoomsExtra();
         }
      } else {
         $retour = $this->_getCountGroupRoomsManager();
      }
      return $retour;
   }

   function getCountPrivateRooms () {
      $retour = 0;
      if ( $this->isCountRoomRedundancy() ) {
         $retour = $this->_getCountPrivateRoomsExtra();
         if ( empty($retour) ) {
            $this->_syncCountPrivateRoomRedundancy(true);
            $retour = $this->_getCountPrivateRoomsExtra();
         }
      } else {
         $retour = $this->_getCountPrivateRoomsManager();
      }
      return $retour;
   }

   public function getCountRooms ( $type_array = '' ) {
      $retour = 0;
      if ( empty($type_array) ) {
         $retour = $this->getCountCommunityRooms() + $this->getCountProjectRooms() + $this->getCountGroupRooms();
      } else {
         foreach ( $type_array as $type_room ) {
            if ( $type_room == CS_PROJECT_TYPE ) {
               $retour += $this->getCountProjectRooms();
            } elseif ( $type_room == CS_COMMUNITY_TYPE ) {
               $retour += $this->getCountCommunityRooms();
            } elseif ( $type_room == CS_GROUPROOM_TYPE ) {
               $retour += $this->getCountGroupRooms();
            } elseif ( $type_room == CS_PRIVATEROOM_TYPE ) {
               $retour += $this->getCountPrivateRooms();
            }
         }
      }
      return $retour;
   }

   public function getCountProjectAndCommunityRooms () {
      $retour = 0;
      if ( $this->isCountRoomRedundancy() ) {
         $retour = $this->getCountRooms(array(CS_PROJECT_TYPE,CS_COMMUNITY_TYPE));
      } else {
         $manager = $this->_environment->getRoomManager();
         $manager->setContextLimit($this->getItemID());
         $retour = $manager->getCountAll();
         unset($manager);
      }
      return $retour;
   }

   private function _getCountRoomRedundancy () {
      $retour = -1;
      if ($this->_issetExtra('COUNT_ROOM_REDUNDANCY')) {
         $value = (int)$this->_getExtra('COUNT_ROOM_REDUNDANCY');
      }
      return $retour;
   }

   private function _setCountRoomRedundancy ( $value ) {
      $this->_addExtra('COUNT_ROOM_REDUNDANCY',(int)$value);
   }

   public function isCountRoomRedundancy () {
      $retour = false;
      $value = $this->_getCountRoomRedundancy();
      if ( $value == 1
           # with "or" only this switch needed to active this function
           or $this->_environment->getConfiguration('c_room_count_redundancy')
           # with "and" there is an second switch, maybe turned on by cron
           # if count rooms is over a limit
           # this is not implemented yet
           #and $this->_environment->getConfiguration('c_room_count_redundancy')
         ) {
         $retour = true;
      }
      return $retour;
   }

   public function turnOnCountRoomRedundancy ( $save = false ) {
      $this->_setCountRoomRedundancy(1);
      if ( $save ) {
         $this->save();
      }
   }

   public function turnOffCountRoomRedundancy ( $save = false ) {
      $this->_setCountRoomRedundancy(-1);
      if ( $save ) {
         $this->save();
      }
   }

   public function syncCountRoomRedundancy ( $save = false ) {
      $this->_syncCountRoomRedundancy();
      if ( $save ) {
         $this->save();
      }
   }

   private function _syncCountRoomRedundancy ( $save = false ) {
      $this->_syncCountProjectRoomRedundancy();
      $this->_syncCountCommunityRoomRedundancy();
      $this->_syncCountGroupRoomRedundancy();
      $this->_syncCountPrivateRoomRedundancy();
      if ( $save ) {
         $this->save();
      }
   }

   public function syncCountProjectRoomRedundancy ( $save = false ) {
      $this->_syncCountProjectRoomRedundancy();
      if ( $save ) {
         $this->save();
      }
   }

   private function _syncCountProjectRoomRedundancy ( $save = false ) {
      $value1 = $this->_getCountProjectRoomsManager();
      $value2 = $this->_getCountProjectRoomsExtra();
      if ( $value1 != $value2 ) {
         $this->_setCountProjectRoomsExtra($value1);
      }
      if ( $save ) {
         $this->save();
      }
   }

   public function syncCountCommunityRoomRedundancy ( $save = false ) {
      $this->_syncCountCommunityRoomRedundancy();
      if ( $save ) {
         $this->save();
      }
   }

   private function _syncCountCommunityRoomRedundancy ( $save = false ) {
      $value1 = $this->_getCountCommunityRoomsManager();
      $value2 = $this->_getCountCommunityRoomsExtra();
      if ( $value1 != $value2 ) {
         $this->_setCountCommunityRoomsExtra($value1);
      }
      if ( $save ) {
         $this->save();
      }
   }

   public function syncCountGroupRoomRedundancy ( $save = false ) {
      $this->_syncCountGroupRoomRedundancy();
      if ( $save ) {
         $this->save();
      }
   }

   private function _syncCountGroupRoomRedundancy ( $save = false ) {
      $value1 = $this->_getCountGroupRoomsManager();
      $value2 = $this->_getCountGroupRoomsExtra();
      if ( $value1 != $value2 ) {
         $this->_setCountGroupRoomsExtra($value1);
      }
      if ( $save ) {
         $this->save();
      }
   }

   public function syncCountPrivateRoomRedundancy ( $save = false ) {
      $this->_syncCountPrivateRoomRedundancy();
      if ( $save ) {
         $this->save();
      }
   }

   private function _syncCountPrivateRoomRedundancy ( $save = false ) {
      $value1 = $this->_getCountPrivateRoomsManager();
      $value2 = $this->_getCountPrivateRoomsExtra();
      if ( $value1 != $value2 ) {
         $this->_setCountPrivateRoomsExtra($value1);
      }
      if ( $save ) {
         $this->save();
      }
   }
   
   public function getCountArchivedProjectAndCommunityRooms () {
   	if ( !isset($this->_count_archived_project_and_community_rooms) ) {
   	   $manager = $this->_environment->getZzzRoomManager();
   	   $manager->setContextLimit($this->getItemID());
   	   $this->_count_archived_project_and_community_rooms = $manager->getCountAll();
   	   unset($manager);
   	}
   	return $this->_count_archived_project_and_community_rooms;
   }

   public function getCountProjectAndCommunityRoomsWithoutTemplates () {
   	if ( !isset($this->_count_project_and_community_rooms_without_templates) ) {
   		$manager = $this->_environment->getRoomManager();
   		$manager->setContextLimit($this->getItemID());
   		$manager->setNotTemplateLimit();
   		$this->_count_project_and_community_rooms_without_templates = $manager->getCountAll();
   		unset($manager);
   	}
   	return $this->_count_project_and_community_rooms_without_templates;
   }
    
   /** get count group rooms from manager
    *
    * @return int count group rooms
    */
   public function getCountArchivedGroupRooms () {
      if (!isset($this->_count_archived_grouprooms)) {
         $manager = $this->_environment->getZzzGrouproomManager();
         $manager->setContextLimit($this->getItemID());
         $this->_count_archived_grouprooms = $manager->getCountAll();
         unset($manager);
      }
      return $this->_count_archived_grouprooms;
   }
   
   // Datenschutz
   public function getLockTime() {
   	$retour = 0;
   	if($this->_issetExtra('LOCK_TIME')){
   		$retour = $this->_getExtra('LOCK_TIME');
   	}
   	return $retour;
   }
   
   public function setLockTime($time) {
   	$this->_addExtra('LOCK_TIME', $time);
   }
   
   public function getPasswordGeneration () {
   	$retour = 0;
   	if ($this->_issetExtra('PASSWORD_GENERATION')) {
   		$retour = $this->_getExtra('PASSWORD_GENERATION');
   	}
   	return $retour;
   	
   }
   
   public function setLockTimeInterval($seconds){
   	$this->_addExtra('LOCK_INTERVAL', $seconds);
   }
   
   public function getLockTimeInterval(){
   	$retour = 0;
   	if($this->_issetExtra('LOCK_INTERVAL')){
   		$retour = $this->_getExtra('LOCK_INTERVAL');
   	}
   	return $retour;
   }
   
   public function setTryUntilLock($number){
   	$this->_addExtra('TRY_UNTIL_LOCK', $number);
   }
   
   public function getTryUntilLock(){
   	$retour = 0;
   	if($this->_issetExtra('TRY_UNTIL_LOCK')){
   		$retour = $this->_getExtra('TRY_UNTIL_LOCK');
   	}
   	return $retour;
   }
   
   public function isPasswordGenerationActive () {
   	$retour = false;
   	if($this->_issetExtra('PASSWORD_GENERATION')) {
   		if($this->getPasswordGeneration() > 0){
   			$retour = true;
   		}
   	}
   	return $retour;
   }
   
   public function setPasswordGeneration ($value) {
   	$this->_addExtra('PASSWORD_GENERATION', $value);
   }
   
   public function isPasswordExpirationActive() {
   	$retour = false;
   	if($this->_issetExtra('PASSWORD_EXPIRATION')) {
   		if($this->getPasswordExpiration() > 0){
   			$retour = true;
   		}
   	}
   	return $retour;
   }
   
   public function getPasswordExpiration() {
   	$retour = 0;
   	if ($this->_issetExtra('PASSWORD_EXPIRATION')) {
   		$retour = $this->_getExtra('PASSWORD_EXPIRATION');
   	}
   	return $retour;
   }
   
   public function setPasswordExpiration($value) {
   	$this->_addExtra('PASSWORD_EXPIRATION', $value);
   }
   
   public function setInactivityLockDays($days) {
   	$this->_addExtra('INACTIVITY_LOCK', $days);
   }
   
   public function getInactivityLockDays() {
   	$retour = 0;
   	if ($this->_issetExtra('INACTIVITY_LOCK')) {
   		$retour = $this->_getExtra('INACTIVITY_LOCK');
   	}
   	return $retour;
   }
   
   public function setInactivitySendMailBeforeLockDays($days){
   	$this->_addExtra('INACTIVITY_MAIL_BEFORE_LOCK', $days);
   }
   
   public function getInactivitySendMailBeforeLockDays(){
   	$retour = 0;
   	if ($this->_issetExtra('INACTIVITY_MAIL_BEFORE_LOCK')) {
   		$retour = $this->_getExtra('INACTIVITY_MAIL_BEFORE_LOCK');
   	}
   	return $retour;
   }
   
   public function setInactivityDeleteDays($days){
   	$this->_addExtra('INACTIVITY_DELETE', $days);
   }
   
   public function getInactivityDeleteDays(){
   	$retour = 0;
   	if ($this->_issetExtra('INACTIVITY_DELETE')) {
   		$retour = $this->_getExtra('INACTIVITY_DELETE');
   	}
   	return $retour;
   }
   
   public function setInactivitySendMailBeforeDeleteDays($days){
   	$this->_addExtra('INACTIVITY_MAIL_DELETE', $days);
   }
   
   public function getInactivitySendMailBeforeDeleteDays(){
   	$retour = 0;
   	if ($this->_issetExtra('INACTIVITY_MAIL_DELETE')) {
   		$retour = $this->_getExtra('INACTIVITY_MAIL_DELETE');
   	}
   	return $retour;
   }

   public function setInactivityConfigDate()
   {
        // set inactivity configuration date
        $this->_addExtra('INACTIVITY_CONFIGURATION_DATE', getCurrentDateTimeInMySQL());
   }

   public function getInactivityConfigDate()
   {
        // get inactivity configuration date
        $retour = 0;
        if ($this->_issetExtra('INACTIVITY_CONFIGURATION_DATE')) {
            $retour = $this->_getExtra('INACTIVITY_CONFIGURATION_DATE');
        }
        return $retour;
   }
   
   public function setDaysBeforeExpiringPasswordSendMail($days){
   	$this->_addExtra('DAYSBEFORE_EXPIRINGPW_SENDMAIL', $days);
   }
   
   public function getDaysBeforeExpiringPasswordSendMail(){
   	$retour = 0;
   	if ($this->_issetExtra('DAYSBEFORE_EXPIRINGPW_SENDMAIL')) {
   		$retour = $this->_getExtra('DAYSBEFORE_EXPIRINGPW_SENDMAIL');
   	}
   	return $retour;
   }
   
   // Datenschutz
   public function setTemporaryLock($value) {
   	$this->_addExtra('TEMPORARY_LOCK',$value);
   }
   
   
   public function getTemporaryLock() {
   	$retour = '';
   	$value = $this->_getExtra('TEMPORARY_LOCK');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
    
   public function isTemporaryLockActivated(){
   	if(($this->getTemporaryLock() == 1)){
   		return true;
   	} else {
   		return false;
   	}
   }
   
   /** set wordpress url
    *
    * @param string url
    */
   public function setWordpressUrl($value){
   	$this->_addExtra('WP_URL', $value);
   }
   
   /** get wordpress url
    *
    * @param string url
    */
   public function getWordpressUrl(){
   	$retour = '';
   	$value = $this->_getExtra('WP_URL');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
   
   /** set activate wordpress blog
    *
    * @param boolean
    */
   public function setWordpressPortalActive($value){
   	$this->_addExtra('WP_PORTAL_ACTIVE', $value);
   }
    
   /** get activate wordpress blog
    *
    * @param boolean
    */
   public function getWordpressPortalActive(){
   	$retour = false;
   	$value = $this->_getExtra('WP_PORTAL_ACTIVE');
   	if ( $value ) {
   		$retour = true;
   	}
   	return $retour;
   }


   public function setInactivitySettingChangeTime(){
      $this->_addExtra('INACTIVITY_CHANGE_SETTING_TIME', getCurrentDateTimeInMySQL());
   }

   public function getInactivitySettingChangeTime(){
    $retour = 0;
    if ($this->_issetExtra('INACTIVITY_CHANGE_SETTING_TIME')) {
      $retour = $this->_getExtra('INACTIVITY_CHANGE_SETTING_TIME');
    }
    return $retour;
   }

   public function setConfigurationHideMailByDefault($value)
   {
     $this->_addExtra('HIDE_MAIL_BY_DEFAULT', $value);
   }

   public function getConfigurationHideMailByDefault()
   {
     $retour = 0;
     if ($this->_issetExtra('HIDE_MAIL_BY_DEFAULT')) {
      $retour = $this->_getExtra('HIDE_MAIL_BY_DEFAULT');
    } else {
      $retour = '';
    }
    return $retour;
   }
}