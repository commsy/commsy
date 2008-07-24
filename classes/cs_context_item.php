<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Josï¿½ Manuel Gonzï¿½lez Vï¿½zquez, Johannes Schultze
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
include_once('classes/cs_item.php');

/** class for a context
 * this class implements a context item
 */
class cs_context_item extends cs_item {
   var $_default_colors = array();

   var $_colors = array();

   /**
    * a list of the moderators
    */
   var $_moderator_list = NULL;

   /**
    * a list of the users
    */
   private $_user_list = NULL;

   var $_default_rubrics_array = array();

   var $_default_home_conf_array = array();


   var $_current_rubrics_array = array();

   var $_current_home_conf_array = array();

   var $_default_listbox_array = array();
   var $_current_listbox_array = array();
   var $_current_lisbox_conf_array = array();
   var $_default_detailbox_array = array();
   var $_current_detailbox_array = array();
   var $_current_detailbox_conf_array = array();
   /**
    * list of rubrics, that can be turned on or off on the server
    */
   var $_configurable_rubrics = array();

   var $_rubric_support = array();

   var $_cache_may_enter = array();

   /** constructor: cs_context_item
    * the only available constructor, initial values for internal variables
    *
    * @param object environment the environment of the commsy
    */
   function cs_context_item ($environment) {
      $this->cs_item($environment);
      $this->_type = 'context';

      $this->_default_listbox_array[0] = 'actions';
      $this->_default_listbox_array[1] = 'search';
      $this->_default_listbox_array[2] = 'buzzwords';
      $this->_default_listbox_array[3] = 'tags';
      $this->_default_listbox_array[4] = 'usage';
      $this->_default_listbox_conf_array['actions'] = 'short';
      $this->_default_listbox_conf_array['search'] = 'tiny';
      $this->_default_listbox_conf_array['buzzwords'] = 'tiny';
      $this->_default_listbox_conf_array['tags'] = 'tiny';
      $this->_default_listbox_conf_array['usage'] = 'tiny';

      $this->_default_detailbox_array[0] = 'detailactions';
      $this->_default_detailbox_array[1] = 'detailbuzzwords';
      $this->_default_detailbox_array[2] = 'detailtags';
      $this->_default_detailbox_array[3] = 'detailnetnavigation';
      $this->_default_detailbox_conf_array['detailactions'] = 'short';
      $this->_default_detailbox_conf_array['detailbuzzwords'] = 'tiny';
      $this->_default_detailbox_conf_array['detailtags'] = 'tiny';
      $this->_default_detailbox_conf_array['detailnetnavigation'] = 'short';

      global $cs_color;
      $this->_default_colors = $cs_color['DEFAULT'];
   }

/* zum debuggen
   function __destruct() {
       echo ("Zerstoere ".$this->getTitle().BRLF);
   }
*/

   function isOpenForGuests () {
      if ($this->_getValue('is_open_for_guests') == 1) {
         return true;
      } else {
         return false;
      }
   }

   function setOpenForGuests () {
      $this->_setValue('is_open_for_guests', 1, TRUE);
   }

   function setClosedForGuests () {
      $this->_setValue('is_open_for_guests', 0, TRUE);
   }

   function isCommunityRoom () {
      return false;
   }

   function isPrivateRoom () {
      return false;
   }

   function isGroupRoom () {
      return false;
   }

   function isProjectRoom () {
      return false;
   }

   function isPortal () {
      return false;
   }

   function isServer () {
      return false;
   }

   function setShowNoAnnouncementsOnHome(){
       $this->_addExtra('SHOWANNOUNCEMENTSONHOME','no');
   }

   function setShowAnnouncementsOnHome(){
       $this->_addExtra('SHOWANNOUNCEMENTSONHOME','yes');
   }

   function isShowAnnouncementsOnHome(){
      $retour = true;
      if ($this->_issetExtra('SHOWANNOUNCEMENTSONHOME') and $this->_getExtra('SHOWANNOUNCEMENTSONHOME')=='no') {
         $retour = false;
      }
      return $retour;
   }





   /** Checks and sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
    */
   function _setItemData($data_array) {
      // not yet implemented
      $this->_data = $data_array;
   }

   /** get title of a context
    * this method returns the title of the context
    *
    * @return string title of a context
    */
   function getTitle () {
      return $this->_getValue('title');
   }

   /** set title of a context
    * this method sets the title of the context
    *
    * @param string value title of the context
    */
   function setTitle ($value) {
      $this->_setValue('title', $value, TRUE);
   }

   /** get room type of a context
    * this method returns the room type of the context
    *
    * @return string room type of a context
    */
   function getRoomType () {
      return $this->_getValue('type');
   }

   /** set room type of a context
    * this method sets the room type of the context
    *
    * @param string value room type of the context
    */
   function setRoomType ($value) {
      $this->_setValue('type', $value, TRUE);
   }

   /** det description array
    *
    * @return array description text in different languages
    */
   function getDescriptionArray () {
      $retour = array();
      if ($this->_issetExtra('DESCRIPTION')) {
         $retour = $this->_getExtra('DESCRIPTION');
      }
      return $retour;
   }

   function setNotShownInPrivateRoomHome ($user_id) {
      $current_user = $this->_environment->getCurrentUserItem();
      $tag = $current_user->getItemID();
      $this->_addExtra('IS_SHOW_ON_HOME_'.$tag,'NO');
   }

   function setShownInPrivateRoomHome ($user_id) {
      $current_user = $this->_environment->getCurrentUserItem();
      $tag = $current_user->getItemID();
      $this->_unsetExtra('IS_SHOW_ON_HOME_'.$tag);
   }

   /** get shown option
    *
    * @return boolean if room is shown on home
    */
   function isShownInPrivateRoomHome ($user_id) {
      $retour = 'true';
      $current_user = $this->_environment->getCurrentUserItem();
      $tag = $current_user->getItemID();
      if ($this->_issetExtra('IS_SHOW_ON_HOME_'.$tag)) {
         if ( $this->_getExtra('IS_SHOW_ON_HOME_'.$tag) == 'NO'){
            $retour = false;
         }
      }
      unset($current_user);
      return $retour;
   }

   /** get shown option
    *
    * @return boolean if room is shown on home
    */
   function isShownInPrivateRoomHomeByItemID ($item_id) {
      $retour = 'true';
      if ($this->_issetExtra('IS_SHOW_ON_HOME_'.$item_id)) {
         if ( $this->_getExtra('IS_SHOW_ON_HOME_'.$item_id) == 'NO'){
            $retour = false;
         }
      }
      return $retour;
   }

   /** set description array
    *
    * @param array value description text in different languages
    */
   function setDescriptionArray ($value) {
      $this->_addExtra('DESCRIPTION',(array)$value);
   }

   /** get contact moderators of a room
    * this method returns a list of contact moderators which are linked to the room
    *
    * @return object cs_list a list of contact moderators (cs_label_item)
    */
   function getContactModeratorList() {
      if ( !isset($this->_contact_moderator_list) ) {
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($this->getItemID());
         $user_manager->setContactModeratorLimit();
         $user_manager->select();
         $this->_contact_moderator_list = $user_manager->get();
         if ($this->_contact_moderator_list->isEmpty()) {
            $this->_contact_moderator_list = $this->getModeratorList();
         }
      }
      return $this->_contact_moderator_list;
   }

   /** get description of a context
    * this method returns the description of the context
    *
    * @return string description of a context
    */
   function getDescriptionByLanguage ($language) {
      $retour = '';
      if ($language == 'browser') {
      }
      $desc_array = $this->getDescriptionArray();
      if ( !empty($desc_array[cs_strtoupper($language)]) ) {
         $retour = $desc_array[cs_strtoupper($language)];
      }
      return $retour;
   }

   function getDescription () {
      $retour = '';
      $retour = $this->getDescriptionByLanguage($this->_environment->getSelectedLanguage());
      if ( empty($retour) ) {
         $current_user = $this->_environment->getCurrentUserItem();
         $retour = $this->getDescriptionByLanguage($this->_environment->getUserLanguage());
      }
      if ( empty($retour) ) {
         $retour = $this->getDescriptionByLanguage($this->getLanguage());
      }
      if ( empty($retour) and ($this->isProjectRoom() or $this->isCommunityRoom()) ) {
         $current_portal = $this->_environment->getCurrentPortalItem();
         $retour = $this->getDescriptionByLanguage($current_portal->getLanguage());
      }
      if ( empty($retour) ) {
         $server = $this->_environment->getServerItem();
         $retour = $this->getDescriptionByLanguage($server->getLanguage());
      }
      if ( empty($retour) ) {
         $desc_array = $this->getDescriptionArray();
         foreach ($desc_array as $desc) {
            if (!empty($desc)) {
               $retour = $desc;
               break;
            }
         }
      }
      return $retour;
   }

   /** get language
    * this method returns the language
    *
    * @return string language
    */
   function getLanguage () {
      if ( $this->isServer() ) {
         $retour = 'user';
      } else {
         $server = $this->_environment->getServerItem();
         $retour = $server->getLanguage();
      }
      if ($this->_issetExtra('LANGUAGE')) {
         $retour = $this->_getExtra('LANGUAGE');
      }
      return $retour;
   }

   function isLanguageFix () {
      $retour = false;
      $lang = $this->getLanguage();
      if ( strtoupper($lang) == 'USER' ) {
         $retour = true;
      }
      return $retour;
   }

   /** set language
    * this method sets the language
    *
    * @param string value language
    */
   function setLanguage ($value) {
      $this->_addExtra('LANGUAGE',(string)$value);
   }

   /** set description of a context
    * this method sets the description of the context
    *
    * @param string value description of the context
    * @param string value lanugage of the description
    */
   function setDescriptionByLanguage ($value, $language) {
      $desc_array = $this->getDescriptionArray();
      $desc_array[strtoupper($language)] = $value;
      $this->setDescriptionArray($desc_array);
   }

   /** get agb text
    *
    * @return array agb text in different languages
    */
   function getAGBTextArray () {
      $retour = array();
      if ($this->_issetExtra('AGBTEXTARRAY')) {
         $retour = $this->_getExtra('AGBTEXTARRAY');
      }
      return $retour;
   }

   /** set agb text
    *
    * @param array value agb in different languages
    */
   function setAGBTextArray ($value) {
      $this->_addExtra('AGBTEXTARRAY',(array)$value);
   }

   /** set agb change date
    */
   function setAGBChangeDate () {
      include_once('functions/date_functions.php');
      $this->_addExtra('AGB_CHANGE_DATE',getCurrentDateTimeInMySQL());
   }

   /** get agb status
    *
    * @return integer agb status 1 = yes, 2 = no
    */
   function getAGBStatus () {
      $retour = '2';
      if ($this->_issetExtra('AGBSTATUS')) {
         $retour = $this->_getExtra('AGBSTATUS');
      }
      return $retour;
   }

   /** set agb status
    *
    * @param array value agb status
    */
   function setAGBStatus ($value) {
      $this->_addExtra('AGBSTATUS',(int)$value);
   }

   // @return boolean true = with AGB, false = without AGB
   function withAGB () {
      $agb_status = $this->getAGBStatus();
      if ($agb_status == 1) {
         $retour = true;
      } else {
         $retour = false;
      }
      return $retour;
   }

   function getAGBChangeDate () {
      $retour = '';
      if ($this->_issetExtra('AGB_CHANGE_DATE')) {
         $retour = $this->_getExtra('AGB_CHANGE_DATE');
      }
      return $retour;
   }

   function setWithBuzzwords(){
      $this->_addExtra('WITHBUZZWORDS',2);
   }

   function setWithoutBuzzwords(){
      $this->_addExtra('WITHBUZZWORDS',1);
   }

   function withBuzzwords(){
      $retour = false;
      if ($this->_issetExtra('WITHBUZZWORDS') ){
         $re = $this->_getExtra('WITHBUZZWORDS');
         if ($re == 2){
            $retour = true;
         }
      }else{
         $retour = true;
      }
      return $retour;
   }

   function isBuzzwordMandatory () {
      $retour = false;
      if ( $this->_issetExtra('BUZZWORDMANDATORY') ) {
         $value = $this->_getExtra('BUZZWORDMANDATORY');
         if ($value == 1) {
            $retour = true;
         }
      }
      return $retour;
   }

   function setBuzzwordMandatory () {
      $this->_addExtra('BUZZWORDMANDATORY',1);
   }

   function unsetBuzzwordMandatory () {
      $this->_addExtra('BUZZWORDMANDATORY',0);
   }


    /** get htmltextarea status
    *
    * @return integer discussion status 1 = simple, 2 = threaded,  3 = both
    */
   function getDiscussionStatus () {
      $retour = 1;
      if ($this->_issetExtra('DISCUSSIONSTATUS')) {
         $retour = $this->_getExtra('DISCUSSIONSTATUS');
      }
      return $retour;
   }

   /** set agb status
    *
    * @param array value discussion status
    */
   function setDiscussionStatus ($value) {
      $this->_addExtra('DISCUSSIONSTATUS',(int)$value);
   }


   // @return boolean true = with threaded discussions, false = without threaded discussions
   function withOnlyThreadedDiscussionType () {
      $discussionmodus = $this->getDiscussionStatus();
      if ($discussionmodus == 2) {
         $retour = true;
      } else {
         $retour = false;
      }
      return $retour;
   }

   // @return boolean true = with simple discussions, false = without simple discussions
   function withOnlySimpleDiscussionType () {
      $discussionmodus = $this->getDiscussionStatus();
      if ($discussionmodus == 1) {
         $retour = true;
      } else {
         $retour = false;
      }
      return $retour;
   }

   // @return boolean true = with both discussion types, false = not both discussion types
   function withBothDiscussionTypes () {
      $discussionmodus = $this->getDiscussionStatus();
      if ($discussionmodus == 3) {
         $retour = true;
      } else {
         $retour = false;
      }
      return $retour;
   }

    /** get htmltextarea status
    *
    * @return integer htmltextarea status 1 = yes, 2 = yes, but minimum, 3 = no
    */
   function getHtmlTextAreaStatus () {
      $retour = 3;
      if ($this->_issetExtra('HTMLTEXTAREASTATUS')) {
         $retour = $this->_getExtra('HTMLTEXTAREASTATUS');
      }
      return $retour;
   }

   /** set agb status
    *
    * @param array value HTMLTextArea status
    */
   function setHtmlTextAreaStatus ($value) {
      $this->_addExtra('HTMLTEXTAREASTATUS',(int)$value);
   }


   // @return boolean true = with HTMLTextArea, false = without HTMLTextArea
   function withHtmlTextArea () {
      $htmltextarea = $this->getHtmlTextAreaStatus();
      if ($htmltextarea != 3) {
         $retour = true;
      } else {
         $retour = false;
      }
      return $retour;
   }

    /** get dates status
    *
    * @return integer dates status "normal" or "calendar"
    */
   function getDatesPresentationStatus () {
      $retour = 'normal';
      if ($this->_issetExtra('DATEPRESENTATIONSTATUS')) {
         $retour = $this->_getExtra('DATEPRESENTATIONSTATUS');
      }
      return $retour;
   }

   /** set agb status
    *
    * @param array value dates status
    */
   function setDatesPresentationStatus ($value) {
      $this->_addExtra('DATEPRESENTATIONSTATUS',(string)$value);
   }



  /** returns a boolean, if the the user can enter the context
    * true: user can enter project
    * false: user can not enter project
    *
    * @param object user item this user wants to enter the project
    */
   function mayEnter ($user_item) {
      return $this->mayEnterByUserID($user_item->getUserID(),$user_item->getAuthSource());
   }

  /** returns a boolean, if the the user can enter the context
    * true: user can enter project
    * false: user can not enter project
    *
    * @param string user_id of user wants to enter the project
    */
   function mayEnterByUserID ($user_id, $auth_source) {
      $retour = false;
      if ( isset($this->_cache_may_enter[$user_id.'_'.$auth_source]) ) {
         $retour = $this->_cache_may_enter[$user_id.'_'.$auth_source];
      } elseif ($user_id == 'root') {
         $retour = true;
      } elseif ($this->isLocked()) {
         $retour = false;
      } elseif ($this->isOpenForGuests()) {
         $retour = true;
      } else {
         $user_manager = $this->_environment->getUserManager();
         $user_manager->resetLimits();
         $user_manager->setContextLimit($this->getItemID());
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ($user_list->getCount() == 1) {
            $user_in_room = $user_list->getFirst();
            if ($user_in_room->isUser()) {
               $retour = true;
               $this->_cache_may_enter[$user_id.'_'.$auth_source] = true;
            } else {
               $this->_cache_may_enter[$user_id.'_'.$auth_source] = false;
            }
            unset($user_in_room);
         } elseif ($user_list->getCount() > 1) {
            include_once('functions/error_functions.php');
            trigger_error('ambiguous user data in database table "user" for user-id "'.$user_id.'"',E_USER_WARNING);
         }
         unset($user_in_room);
         unset($user_list);
         unset($user_manager);
      }
      return $retour;
   }

   function mayEnterByUserItemID ( $user_item_id ) {
      $retour = false;
      if ( $this->isLocked() ) {
         $retour = false;
      } elseif ( isset($this->_cache_may_enter[$user_item_id]) ) {
         $retour = $this->_cache_may_enter[$user_item_id];
      } elseif ( $this->isOpenForGuests() ) {
         $retour = true;
      } else {
         $user_manager = $this->_environment->getUserManager();
         $user_in_room = $user_manager->getItem($user_item_id);
         if ($user_in_room->isUser()) {
            $retour = true;
            $this->_cache_may_enter[$user_item_id] = true;
         } else {
            $this->_cache_may_enter[$user_item_id] = false;
         }
         unset($user_in_room);
         unset($user_manager);
      }
      return $retour;
   }

   function getColorArray(){
      $retour = $this->_default_colors;
      if ($this->_issetExtra('COLOR')) {
         $retour = $this->_getExtra('COLOR');
         $retour_temp = array();
       if ( is_array($retour) ) {
            foreach ($retour as $key => $entry){
                $retour_temp[strtolower($key)]= $entry;
            }
       }
        $retour = $retour_temp;
      }
      return $retour;
   }

   function setColorArray($array){
      if (is_array($array)){
         $this->_addExtra('COLOR',$array);
      }
   }



   /** get modifier
    * this method returns the modifier of the item
    *
    * @return cs_user_item modifier of the item
    */
   function getModifier () {
      return $this->_getUserItem('modifier');
   }

   /** set modifier
    * this method sets the modifier of the item
    *
    * @param object cs_item modifier of the item
    */
   function setModifier ($value) {
      $this->_setUserItem('modifier',$value);
   }

   /** get flag for checking always new members
    * this method returns a boolean for checking always new members
    *
    * @return integer the flag
    */
   function checkNewMembersAlways () {
      $retour = true;
      if ( $this->checkNewMembersSometimes()
           or $this->checkNewMembersNever()
           or $this->checkNewMembersWithCode()
         ) {
         $retour = false;
      }
      return $retour;
   }

   /** get flag for checking always new members
    * this method returns a boolean for checking always new members
    *
    * @return integer the flag
    */
   function checkNewMembersSometimes () {
      $retour = false;
      if ($this->_getCheckNewMembers() == 2) {
         $retour = true;
      }
      return $retour;
   }

   /** get flag for checking always new members
    * this method returns a boolean for checking always new members
    *
    * @return integer the flag
    */
   function checkNewMembersWithCode () {
      $retour = false;
      if ($this->_getCheckNewMembers() == 3) {
         $retour = true;
      }
      return $retour;
   }

   public function setCheckNewMemberCode ( $value ) {
      $this->_addExtra('CHECKNEWMEMBERS_CODE',$value);
   }

   function getCheckNewMemberCode () {
      $retour = '';
      if ($this->_issetExtra('CHECKNEWMEMBERS_CODE')) {
         $retour = $this->_getExtra('CHECKNEWMEMBERS_CODE');
      }
      return $retour;
   }

   /** get flag for checking never new members
    * this method returns a boolean for checking never new members
    *
    * @return integer the flag
    */
   function checkNewMembersNever () {
      $retour = false;
      if ($this->_getCheckNewMembers() == -1) {
         $retour = true;
      }
      return $retour;
   }

   /** get flag for checking new members, INTERNAL -> use checkNewMember()
    * this method returns a flag for checking new members
    *
    * @return integer the flag: -1, new members can enter instantly
    *                            1, moderator must activate new members
    *                            2, moderator must activate new members,
    *                               - room: if account is new
    *                               - portal: if account with room membership
    */
   function _getCheckNewMembers () {
      $retour = false;
      if ($this->_issetExtra('CHECKNEWMEMBERS')) {
         $retour = $this->_getExtra('CHECKNEWMEMBERS');
      }
      return $retour;
   }

   /** set flag for check new members
    * this method sets the flag for checking new members
    *
    * @param boolean value flag for checking new members
    */
   function _setCheckNewMember ($value) {
      $this->_addExtra('CHECKNEWMEMBERS',$value);
   }

   /** set flag for check new members
    * this method sets the flag for checking new members
    *
    * @param boolean value flag for checking new members
    */
   function setCheckNewMemberAlways () {
      $this->_setCheckNewMember(1);
   }

   /** set flag for check new members
    * this method sets the flag for checking new members
    *
    * @param boolean value flag for checking new members
    */
   function setCheckNewMemberSometimes () {
      $this->_setCheckNewMember(2);
   }

   /** set flag for check new members
    * this method sets the flag for checking new members
    *
    * @param boolean value flag for checking new members
    */
   function setCheckNewMemberWithCode () {
      $this->_setCheckNewMember(3);
   }

   /** set flag for check new members
    * this method sets the flag for checking new members
    *
    * @param boolean value flag for checking new members
    */
   function setCheckNewMemberNever () {
      $this->_setCheckNewMember(-1);
   }

   /** get filename of logo
    *
    * @return string filename of logo
    */
   function getLogoFilename () {
      $retour = '';
      if ($this->_issetExtra('LOGOFILENAME')) {
         $retour = $this->_getExtra('LOGOFILENAME');
      }
      return $retour;
   }

   /** set filename of logo
    *
    * @param string filename of logo
    */
   function setLogoFilename ($value) {
      $this->_addExtra('LOGOFILENAME',(string)$value);
   }

   /** get context of room
    *
    * @return string
    */
   function getRoomContext () {
      $retour = '';
      if ($this->_issetExtra('ROOM_CONTEXT')) {
         $retour = $this->_getExtra('ROOM_CONTEXT');
      } else {
         $retour = 'uni'; // not university
      }
      return $retour;
   }

   /** set context of room
    *
    * @param string
    */
   function setRoomContext ($value) {
      $this->_addExtra('ROOM_CONTEXT',(string)$value);
   }


   ###################################################
   # email text translation methods
   ###################################################

   function getEmailTextArray () {
      $retour = array();
      if ($this->_issetExtra('MAIL_TEXT_ARRAY')) {
         $retour = $this->_getExtra('MAIL_TEXT_ARRAY');
      }
      return $retour;
   }

   function setEmailText ($message_tag, $array) {
      $mail_text_array = array();
      if ($this->_issetExtra('MAIL_TEXT_ARRAY')) {
         $mail_text_array = $this->_getExtra('MAIL_TEXT_ARRAY');
      }
      if (!empty($array)) {
         $mail_text_array[$message_tag] = $array;
      } elseif (!empty($mail_text_array[$message_tag])) {
         unset($mail_text_array[$message_tag]);
      }
      $this->_addExtra('MAIL_TEXT_ARRAY',$mail_text_array);
   }


   ###################################################
   # rubric translation methods
   ###################################################

   function getRubricTranslationArray () {
      $retour = array();
      $rubric_array = array();
      $rubric_array[] = CS_PROJECT_TYPE;
      $rubric_array[] = CS_COMMUNITY_TYPE;
      $rubric_array[] = CS_TOPIC_TYPE;
      $rubric_array[] = CS_INSTITUTION_TYPE;
      $rubric_array[] = CS_TIME_TYPE;

      foreach ($rubric_array as $rubric) {
         $retour[cs_strtoupper($rubric)] = $this->_getRubricArray($rubric);
      }
      return $retour;
   }

   /** set RubricArray
    * this method sets the Rubric Name
    *
    * @param array value name cases
    */
   function setRubricArray ($rubric, $array) {
      $rubric_translation_array = $this->_getExtra('RUBRIC_TRANSLATION_ARRAY');
      $rubric_translation_array[cs_strtoupper($rubric)] = $array;
      $this->_addExtra('RUBRIC_TRANSLATION_ARRAY',$rubric_translation_array);
   }

   /** get RubricArray
    * this method gets the Rubric Name
    *
    * @return array value name cases
    */
   function _getRubricArray ($rubric) {
      $commsy_context = $this->getRoomContext();
      $retour = array();
      if ($this->_issetExtra('RUBRIC_TRANSLATION_ARRAY')) {
         $rubric_translation_array = $this->_getExtra('RUBRIC_TRANSLATION_ARRAY');
         if (!empty($rubric_translation_array[cs_strtoupper($rubric)])) {
            $retour = $rubric_translation_array[cs_strtoupper($rubric)];
         }
      }
      if (empty($retour)) {
         if ($rubric == CS_PROJECT_TYPE){
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
         }elseif ($rubric == CS_COMMUNITY_TYPE){
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
               $retour['RU']['GENUS']= 'F';
               $retour['RU']['NOMS']= 'sala comunitara';
               $retour['RU']['GENS']= 'salii comunitare';
               $retour['RU']['AKKS']= 'sala comunitara';
               $retour['RU']['DATS']= 'salii comunitare';
               $retour['RU']['NOMPL']= 'salile comunitare';
               $retour['RU']['GENPL']= 'salilor comunitare';
               $retour['RU']['AKKPL']= 'salile comunitare';
               $retour['RU']['DATPL']= 'salilor comunitare';
         } elseif ($rubric == CS_TOPIC_TYPE) {
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
         } elseif ($rubric == CS_INSTITUTION_TYPE) {
            $retour['NAME'] = CS_INSTITUTION_TYPE;
            $retour['DE']['GENUS']= 'F';
            $retour['DE']['NOMS']= 'Institution';
            $retour['DE']['GENS']= 'Institution';
            $retour['DE']['AKKS']= 'Institution';
            $retour['DE']['DATS']= 'Institution';
            $retour['DE']['NOMPL']= 'Institutionen';
            $retour['DE']['GENPL']= 'Institutionen';
            $retour['DE']['AKKPL']= 'Institutionen';
            $retour['DE']['DATPL']= 'Institutionen';
            $retour['EN']['GENUS']= 'F';
            $retour['EN']['NOMS']= 'institution';
            $retour['EN']['GENS']= 'institution';
            $retour['EN']['AKKS']= 'institution';
            $retour['EN']['DATS']= 'institution';
            $retour['EN']['NOMPL']= 'institutions';
            $retour['EN']['GENPL']= 'institutions';
            $retour['EN']['AKKPL']= 'institutions';
            $retour['EN']['DATPL']= 'institutions';
             $retour['RU']['GENUS']= 'F';
            $retour['RU']['NOMS']= 'institutia';
            $retour['RU']['GENS']= 'institutiei';
            $retour['RU']['AKKS']= 'institutia';
            $retour['RU']['DATS']= 'institutiei';
            $retour['RU']['NOMPL']= 'institutiile';
            $retour['RU']['GENPL']= 'institutiilor';
            $retour['RU']['AKKPL']= 'institutiile';
            $retour['RU']['DATPL']= 'institutiilor';
         } else {
            $retour['NAME'] = 'rubrics';
            $retour['DE']['GENUS']= 'F';
            $retour['DE']['NOMS']= 'Rubrik';
            $retour['DE']['GENS']= 'Rubrik';
            $retour['DE']['AKKS']= 'Rubrik';
            $retour['DE']['DATS']= 'Rubrik';
            $retour['DE']['NOMPL']= 'Rubriken';
            $retour['DE']['GENPL']= 'Rubriken';
            $retour['DE']['AKKPL']= 'Rubriken';
            $retour['DE']['DATPL']= 'Rubriken';
            $retour['EN']['GENUS']= 'F';
            $retour['EN']['NOMS']= 'rubric';
            $retour['EN']['GENS']= 'rubric';
            $retour['EN']['AKKS']= 'rubric';
            $retour['EN']['DATS']= 'rubric';
            $retour['EN']['NOMPL']= 'rubrics';
            $retour['EN']['GENPL']= 'rubrics';
            $retour['EN']['AKKPL']= 'rubrics';
            $retour['EN']['DATPL']= 'rubrics';
            $retour['RU']['GENUS']= 'F';
            $retour['RU']['NOMS']= 'rubrica';
            $retour['RU']['GENS']= 'rubricii';
            $retour['RU']['AKKS']= 'rubrica';
            $retour['RU']['DATS']= 'rubricii';
            $retour['RU']['NOMPL']= 'rubricile';
            $retour['RU']['GENPL']= 'rubricilor';
            $retour['RU']['AKKPL']= 'rubricile';
            $retour['RU']['DATPL']= 'rubricilor';
         }
      }
      return $retour;
   }

   /** get show title, INTERNAL
    *
    * @return integer show title: -1 = not
    *                              1 = yes
    */
   function _getShowTitle () {
      $retour = '';
      if ($this->_issetExtra('SHOWTITLE')) {
         $retour = $this->_getExtra('SHOWTITLE');
      }
      return $retour;
   }

   /** set show title, INTERNAL
    *
    * @param integer show title: -1 = not
    *                             1 = yes
    */
   function _setShowTitle ($value) {
      $this->_addExtra('SHOWTITLE',(int)$value);
   }

   /** set show title
    */
   function setShowTitle () {
      $this->_setShowTitle(1);
   }

   /** set not show title
    */
   function setNotShowTitle () {
      $this->_setShowTitle(-1);
   }

   /** show title ?
    * true = show title, default
    * false = show title not
    *
    * @return boolean
    */
   function showTitle () {
      $retour = true;
      $show_int = $this->_getShowTitle();
      if ( isset($show_int) and !empty($show_int) ) {
         if ($show_int == -1) {
            $retour = false;
         }
      }
      return $retour;
   }

   /** get moderators of the context
    * this method returns a list of moderators of the context
    *
    * @return object cs_list a list of moderator (cs_user_item)
    */
   function getModeratorList () {
      if ( empty($this->_moderator_list) ) {
         $userManager = $this->_environment->getUserManager();
         $userManager->resetLimits();
         $userManager->setContextLimit($this->getItemID());
         $userManager->setModeratorLimit();
         $userManager->select();
         $this->_moderator_list = $userManager->get();
      }
      return $this->_moderator_list;
   }

   /** get rubric configuration of the context
    * this method returns the configuration of the homepage of the context
    *
    * @return string configuration of the homepage
    */
   function getHomeRightConf () {
      $retour = '';
      if ($this->_issetExtra('HOMERIGHTCONF')) {
         $retour = $this->_getExtra('HOMERIGHTCONF');
      }
      return $retour;
   }

   function setHomeRightConf ($text) {
      $this->_addExtra('HOMERIGHTCONF',(string)$text);
   }

   function getHomeConf () {
      $retour = '';
      if ($this->_issetExtra('HOMECONF')) {
         $retour = $this->clearUnallowedRubrics($this->_getExtra('HOMECONF'));
      }
      $retour = $this->_changeContactInUser($retour);
      if ( empty($retour) ) {
         $retour = $this->getDefaultHomeConf();
         $this->setHomeConf($retour);
      }
      return $retour;
   }


   function _changeContactInUser($rubricsString){
      $change_needed = false;
      if (stristr($rubricsString, 'contact_tiny')) {
         $rubricsString = str_replace('contact_tiny','user_tiny', $rubricsString);
         $change_needed = true;
      }
      if (stristr($rubricsString, 'contact_short')) {
         $rubricsString = str_replace('contact_short','user_short', $rubricsString);
         $change_needed = true;
      }
      if (stristr($rubricsString, 'contact_none')) {
         $rubricsString = str_replace('contact_none','user_none',  $rubricsString);
         $change_needed = true;
      }
      if ($change_needed){
         $this->setHomeConf($rubricsString);
         $this->save();
      }
      return $rubricsString;
   }

   /**
    * Method takes the configuration string from the database and removes
    * the rubrics that are not allowed on the server.
    *
    */
   function clearUnallowedRubrics($rubricsString) {
      foreach ($this->_configurable_rubrics as $rubric) {
         if (stristr($rubricsString, $rubric)) {
           if (!$this->showExtraRubric($rubric)) {
              if (stristr($rubricsString, $rubric.'_tiny')) {
                $rubricsString = str_replace($rubric.'_tiny', '', $rubricsString);
              }
              if (stristr($rubricsString, $rubric.'_short')) {
                $rubricsString = str_replace($rubric.'_short', '', $rubricsString);
              }
              if (stristr($rubricsString, $rubric.'_none')) {
                $rubricsString = str_replace($rubric.'_none', '', $rubricsString);
              }

            // clear string from ","
             if ($rubricsString[0] == ',') {
                  $rubricsString = substr($rubricsString,1);
              }
              if ($rubricsString[strlen($rubricsString)-1] == ',') {
                $rubricsString = substr($rubricsString,0,strlen($rubricsString)-1);
              }
            $rubricsString = str_replace(',,',',',$rubricsString);
           }
        }
      }
      return $rubricsString;
   }


   function getAvailableDetailBoxes() {
      $current_list_boxes = $this->getDetailBoxConf();
      if (!empty($current_list_boxes)) {
        $tokens = explode(',', $current_list_boxes);
        $pointer = 0;
        foreach ($tokens as $module) {
           if (!empty($module)){
             list($box, $view) = explode('_', $module);
             if ($box =='detailbuzzwords' and $this->withBuzzwords() ) {
               $this->_current_detailbox_array[$pointer++] = $box;
             }elseif ($box =='detailtags' and $this->withTags() ) {
               $this->_current_detailbox_array[$pointer++] = $box;
             }elseif ($box !='detailtags' and $box !='detailbuzzwords') {
               $this->_current_detailbox_array[$pointer++] = $box;
             }
             $this->_current_detailbox_conf_array[$box] = $view;
           }
        }
        if ($this->withTags()){
           $in_array = false;
           foreach($this->_current_detailbox_array as $entry){
              if ($entry == 'detailtags'){
                $in_array = true;
              }
           }
           if ( !$in_array ){
              $this->_current_detailbox_array[] = 'detailtags';
              $this->_current_detailbox_conf_array['detailtags'] = 'tiny';
           }
        }
        if ($this->withBuzzwords()){
           $in_array = false;
           foreach($this->_current_detailbox_array as $entry){
              if ($entry == 'detailbuzzwords'){
                $in_array = true;
              }
           }
           if ( !$in_array ){
              $this->_current_detailbox_array[] = 'detailbuzzwords';
              $this->_current_detailbox_conf_array['detailbuzzwords'] = 'tiny';
           }
        }
      }
      return $this->_current_detailbox_array;
   }

   function withIMSContentConnection(){
      global $with_ims_content_connection;
      if (isset($with_ims_content_connection)){
         return $with_ims_content_connection;
      }else{
         return false;
      }
   }


   function getAvailableListBoxes() {
      $current_list_boxes = $this->getListBoxConf();
      if (!empty($current_list_boxes)) {
        $tokens = explode(',', $current_list_boxes);
        $pointer = 0;
        foreach ($tokens as $module) {
           if (!empty($module)){
             list($box, $view) = explode('_', $module);
             if ($box =='buzzwords' and $this->withBuzzwords() ) {
               $this->_current_listbox_array[$pointer++] = $box;
             }elseif ($box =='tags' and $this->withTags() ) {
               $this->_current_listbox_array[$pointer++] = $box;
             }elseif ($box !='tags' and $box !='buzzwords') {
               $this->_current_listbox_array[$pointer++] = $box;
             }
             $this->_current_listbox_conf_array[$box] = $view;
           }
        }
        if ($this->withTags()){
           $in_array = false;
           foreach($this->_current_listbox_array as $entry){
              if ($entry == 'tags'){
                $in_array = true;
              }
           }
           if ( !$in_array ){
              $this->_current_listbox_array[] = 'tags';
              $this->_current_listbox_conf_array['tags'] = 'tiny';
           }
        }
        if ($this->withBuzzwords()){
           $in_array = false;
           foreach($this->_current_listbox_array as $entry){
              if ($entry == 'buzzwords'){
                $in_array = true;
              }
           }
           if ( !$in_array ){
              $this->_current_listbox_array[] = 'buzzwords';
              $this->_current_listbox_conf_array['buzzwords'] = 'tiny';
           }
        }
      }
      return $this->_current_listbox_array;
   }

      function getDefaultListBoxConf () {
         $retour = '';
         $first = true;
         foreach ($this->_default_listbox_array as $box) {
            if ($first) {
               $first = false;
            } else {
               $retour .= ',';
            }
            if (isset($this->_default_listbox_conf_array[$box])){
               $retour .= $box.'_'.$this->_default_listbox_conf_array[$box];
            }
         }
         return $this->clearUnallowedBoxes($retour);
      }

      function getDefaultDetailBoxConf () {
         $retour = '';
         $first = true;
         foreach ($this->_default_detailbox_array as $box) {
            if ($first) {
               $first = false;
            } else {
               $retour .= ',';
            }
            if (isset($this->_default_detailbox_conf_array[$box])){
               $retour .= $box.'_'.$this->_default_detailbox_conf_array[$box];
            }
         }
         return $this->clearUnallowedDetailBoxes($retour);
      }

      function setListBoxConf ($value) {
         $this->_addExtra('LISTCONF', (string)$value);
      }

      function getListBoxConf () {
         $retour = '';
         if ($this->_issetExtra('LISTCONF')) {
            $retour = $this->clearUnallowedBoxes($this->_getExtra('LISTCONF'));
         }
         if ( empty($retour) ) {
            $retour = $this->getDefaultListBoxConf();
            $this->setListBoxConf($retour);
         }
         return $retour;
      }

      function setDetailBoxConf ($value) {
         $this->_addExtra('DETAILCONF', (string)$value);
      }

      function getDetailBoxConf () {

         $retour = '';
         if ($this->_issetExtra('DETAILCONF')) {
            $retour = $this->clearUnallowedDetailBoxes($this->_getExtra('DETAILCONF'));
         }
         if ( empty($retour) ) {
            $retour = $this->getDefaultDetailBoxConf();
            $this->setDetailBoxConf($retour);
         }
         return $retour;
      }

      function clearUnallowedBoxes($boxesString){
         if( !$this->withTags() ){
            if (stristr($boxesString, 'tags_tiny')) {
               $boxesString = str_replace('tags_tiny', '', $boxesString);
            }
            if (stristr($boxesString, 'tags_short')) {
               $boxesString = str_replace('tags_short', '', $boxesString);
            }
            if (stristr($boxesString, 'tags_none')) {
               $boxesString = str_replace('tags_none', '', $boxesString);
            }
         }
         if( !$this->withBuzzwords() ){
            if (stristr($boxesString, 'buzzwords_tiny')) {
               $boxesString = str_replace('buzzwords_tiny', '', $boxesString);
            }
            if (stristr($boxesString, 'buzzwords_short')) {
               $boxesString = str_replace('buzzwords_short', '', $boxesString);
            }
            if (stristr($boxesString, 'buzzwords_none')) {
               $boxesString = str_replace('buzzwords_none', '', $boxesString);
            }
         }
         // clear string from ","
         if ($boxesString[0] == ',') {
            $boxesString = substr($boxesString,1);
         }
         if ($boxesString[strlen($boxesString)-1] == ',') {
            $boxesString = substr($boxesString,0,strlen($boxesString)-1);
         }
         $boxesString = str_replace(',,',',',$boxesString);
         return $boxesString;
      }

      function clearUnallowedDetailBoxes($boxesString){
         if( !$this->withTags() ){
            if (stristr($boxesString, 'detailtags_tiny')) {
               $boxesString = str_replace('detailtags_tiny', '', $boxesString);
            }
            if (stristr($boxesString, 'detailtags_short')) {
               $boxesString = str_replace('detailtags_short', '', $boxesString);
            }
            if (stristr($boxesString, 'detailtags_none')) {
               $boxesString = str_replace('detailtags_none', '', $boxesString);
            }
         }
         if( !$this->withBuzzwords() ){
            if (stristr($boxesString, 'detailbuzzwords_tiny')) {
               $boxesString = str_replace('detailbuzzwords_tiny', '', $boxesString);
            }
            if (stristr($boxesString, 'detailbuzzwords_short')) {
               $boxesString = str_replace('detailbuzzwords_short', '', $boxesString);
            }
            if (stristr($boxesString, 'detailbuzzwords_none')) {
               $boxesString = str_replace('detailbuzzwords_none', '', $boxesString);
            }
         }
         // clear string from ","
         if ($boxesString[0] == ',') {
            $boxesString = substr($boxesString,1);
         }
         if ($boxesString[strlen($boxesString)-1] == ',') {
            $boxesString = substr($boxesString,0,strlen($boxesString)-1);
         }
         $boxesString = str_replace(',,',',',$boxesString);
         return $boxesString;
      }

      function getListLength(){
         $retour = CS_LIST_INTERVAL;
         if ( $this->_issetExtra('LISTLENGTH') ){
            $retour = $this->_getExtra('LISTLENGTH');
         }
         return $retour;
      }

      function setListLength($value){
         $this->_addExtra('LISTLENGTH',$value);
      }


   /** get configuration of the homepage
    * this method configuration of the homepage
    *
    * @return array configuration of the homepage
    */
   function getDefaultHomeConf () {
      $retour = '';
      $first = true;
      foreach ($this->_default_rubrics_array as $rubric) {
         if ($first) {
            $first = false;
         } else {
            $retour .= ',';
         }
         if (isset($this->_default_home_conf_array[$rubric])){
            $retour .= $rubric.'_'.$this->_default_home_conf_array[$rubric];
         }
      }
      return $this->clearUnallowedRubrics($retour);
   }


   /** set home conf
    * this method sets the home conf
    *
    * @param string value home conf
    */
   function setHomeConf ($value) {
      $this->_addExtra('HOMECONF', (string)$value);
   }



   ##########################################
   # extras (add-ons) configuration
   ############## BEGIN #####################

   /** get part of the extra config array, INTERNAL
    *
    * @param string type: ads for sponsoring / ads
    *                     whole for the whole array
    *
    * @return int 1 = true / 0 = false
    */
   function _getExtraConfig ($type) {
      if ( $type == 'whole' ) {
         $retour = array();
      } else {
         $retour = '';
      }
      if ( $this->_issetExtra('EXTRA_CONFIG') ) {
         $extra_config_array = $this->_getExtra('EXTRA_CONFIG');
         if ( $type == 'whole' ) {
            $retour = $extra_config_array;
         } elseif ( isset($extra_config_array[strtoupper($type)]) ) {
            $retour = $extra_config_array[strtoupper($type)];
         }
      }
      return $retour;
   }

   /** set part of the extra config array, INTERNAL
    *
    * @param string part: ads for sponsoring / ads
    *                     whole for the whole array
    * @param array
    */
   function _setExtraConfig ($type, $value) {
      if ($type == 'whole') {
         $this->_addExtra('EXTRA_CONFIG',$value);
      } else {
         $extra_config_array = $this->_getExtraConfig('whole');
         $extra_config_array[strtoupper($type)] = $value;
         $this->_setExtraConfig('whole',$extra_config_array);
      }
   }

   function getExtraConfig () {
      return $this->_getExtraConfig('whole');
   }

   function setExtraConfig ($value) {
      $this->_setExtraConfig('whole',$value);
   }


   ##########################################
   # grouproom flag
   ##########################################

   function withGrouproomFunctions() {
      $retour = false;
      $value = $this->_getExtraConfig('GROUPROOM');
      if ($value == 1) {
         $retour = true;
      }
      return $retour;
   }

   function setWithGrouproomFunctions () {
      $this->_setExtraConfig('GROUPROOM',1);
   }

   function setWithoutGrouproomFunctions () {
      $this->_setExtraConfig('GROUPROOM',-1);
   }

   function showGrouproomConfig () {
      $retour = false;
      if ($this->withGrouproomFunctions()) {
         $retour = true;
      } elseif ( $this->isProjectRoom()
                 or $this->isCommunityRoom()
               ) {
         $portal = $this->getContextItem();
         $retour = $portal->withGrouproomFunctions();
      }
      return $retour;
   }

   function showGrouproomFunctions () {
      $retour = false;
      if ( $this->showGrouproomConfig() and $this->isGrouproomActive() ) {
         $retour = true;
      }
      return $retour;
   }

   /** is group room active ?
    * can be switched at room configuration
    *
    * true = group room is active
    * false = group room is not active, default
    *
    * @return boolean
    */
   function isGrouproomActive () {
      $retour = false;
      if ( $this->_issetExtra('GROUPROOM') ) {
         $active = $this->_getExtra('GROUPROOM');
         if ( $active == 1 ) {
            $retour = true;
         }
      }
      return $retour;
   }

   /** set activity of the group room, INTERNAL
    *
    * @param integer value: -1 = not
    *                        1 = yes
    */
   function _setGrouproomActivity ($value) {
      $this->_addExtra('GROUPROOM',(int)$value);
   }

   /** set group room active
    */
   function setGrouproomActive () {
      $this->_setGrouproomActivity(1);
   }

   /** set group room inactive
    */
   function setGrouproomInactive () {
      $this->_setGrouproomActivity(-1);
   }


   ##########################################
   # service link
   ##########################################

   function showServiceLink () {
      $retour = false;
      if ($this->isServiceLinkActive()) {
         $retour = true;
      }
      return $retour;
   }

    /**
    *  set service email adress
    */

    function setServiceEmail($email) {
          $this->_addExtra('SERVICEEMAIL',(string)$email);
    }

    /**
    *  get service email adress
    */

    function getServiceEmail() {
       return $this->_getExtra('SERVICEEMAIL');
    }

   /** is service link active ?
    * can be switched at room configuration
    *
    * true = service link is active
    * false = service link is not active, default
    *
    * @return boolean
    */
   function isServiceLinkActive () {
      $retour = false;
      if ( $this->_issetExtra('SERVICELINK') ) {
         $active = $this->_getExtra('SERVICELINK');
         if ($active == 1) {
            $retour = true;
         }
      }
      return $retour;
   }

   /** set activity of the service link, INTERNAL
    *
    * @param integer value: -1 = not
    *                        1 = yes
    */
   function _setServiceLinkActivity ($value) {
      if ( $this->_issetExtra('SERVICELINK') ) {
         $this->_setExtra('SERVICELINK',(int)$value);
      } else {
         $this->_addExtra('SERVICELINK',(int)$value);
      }
   }

   /** set service link active
    */
   function setServiceLinkActive () {
      $this->_setServiceLinkActivity(1);
   }

   /** set service link inactive
    */
   function setServiceLinkInactive () {
      $this->_setServiceLinkActivity(0);
   }

   /** is mail to moderator link active ?
    * can be switched at room configuration
    *
    * true = mail to moderator link is active
    * false = mail to moderator link is not active, default
    *
    * @return boolean
    */
   function isModeratorLinkActive () {
      $retour = true;
      if ( $this->_issetExtra('MODERATOR_MAIL_LINK') ) {
         $active = $this->_getExtra('MODERATOR_MAIL_LINK');
         if ($active == -1) {
            $retour = false;
         }
      }
      return $retour;
   }

   function showMail2ModeratorLink () {
      return $this->isModeratorLinkActive();
   }

   /** set activity of the mail to moderator link, INTERNAL
    *
    * @param integer value: -1 = not
    *                        1 = yes
    */
   function _setModeratorLinkActivity ($value) {
      if ( $this->_issetExtra('MODERATOR_MAIL_LINK') ) {
         $this->_setExtra('MODERATOR_MAIL_LINK',(int)$value);
      } else {
         $this->_addExtra('MODERATOR_MAIL_LINK',(int)$value);
      }
   }

   /** set mail to moderator link active
    */
   function setModeratorLinkActive () {
      $this->_setModeratorLinkActivity(1);
   }

   /** set mail to moderator link inactive
    */
   function setModeratorLinkInactive () {
      $this->_setModeratorLinkActivity(-1);
   }

   function setTemplateAvailability($value) {
         if(!$this->_issetExtra('TEMPLATE_AVAILABILITY'))
         {
            $this->_addExtra('TEMPLATE_AVAILABILITY',(int)$value);
         } else {
            $this->_setExtra('TEMPLATE_AVAILABILITY',(int)$value);
         }
   }

   function getTemplateAvailability () {
      $retour = '1';
      if ( $this->_issetExtra('TEMPLATE_AVAILABILITY') ) {
         $retour = $this->_getExtra('TEMPLATE_AVAILABILITY');
      }
      return $retour;
   }

   function setCommunityTemplateAvailability($value) {
         if(!$this->_issetExtra('TEMPLATE_COMMUNITY_AVAILABILITY'))
         {
            $this->_addExtra('TEMPLATE_COMMUNITY_AVAILABILITY',(int)$value);
         } else {
            $this->_setExtra('TEMPLATE_COMMUNITY_AVAILABILITY',(int)$value);
         }
   }

   function getCommunityTemplateAvailability () {
      $retour = '1';
      if ( $this->_issetExtra('TEMPLATE_COMMUNITY_AVAILABILITY') ) {
         $retour = $this->_getExtra('TEMPLATE_COMMUNITY_AVAILABILITY');
      }
      return $retour;
   }



   ##########################################
   # homepage - Raum-Webseite
   ##########################################

   function withHomepageLink () {
      $retour = false;
      $value = $this->_getExtraConfig('HOMEPAGELINK');
      if ($value == 1) {
         $retour = true;
      } elseif ($this->isProjectRoom() or $this->isCommunityRoom()) {
         $portal_room = $this->getContextItem();
         if ( $portal_room->withHomepageLink() ) {
            $retour = true;
         }
      }
      return $retour;
   }

   function setWithHomepageLink () {
      $this->_setExtraConfig('HOMEPAGELINK',1);
   }

   function setWithoutHomepageLink () {
      $this->_setExtraConfig('HOMEPAGELINK',0);
   }

   function showHomepageLink () {
      $retour = false;
      if ($this->withHomepageLink() and $this->isHomepageLinkActive()) {
         $retour = true;
      }
      return $retour;
   }

   /** is homepage link active ?
    * can be switched at room configuration
    *
    * true = homepage link is active
    * false = homepage link is not active, default
    *
    * @return boolean
    */
   function isHomepageLinkActive () {
      $retour = false;
      if ( $this->_issetExtra('HOMEPAGELINK') ) {
         $active = $this->_getExtra('HOMEPAGELINK');
         if ($active == 1) {
            $retour = true;
            $retour = $retour and $this->withHomepageLink();
         }
      }
      return $retour;
   }

   /** set activity of the homepage link, INTERNAL
    *
    * @param integer value: -1 = not
    *                        1 = yes
    */
   function _setHomepageLinkActivity ($value) {
      $this->_addExtra('HOMEPAGELINK',(int)$value);
   }

   /** set homepage link active
    */
   function setHomepageLinkActive () {
      $this->_setHomepageLinkActivity(1);
   }

   /** set homepage link inactive
    */
   function setHomepageLinkInactive () {
      $this->_setHomepageLinkActivity(-1);
   }

   function activateHomepageDescLink () {
      $this->_addExtra('HOMEPAGE_DESC_LINK','1');
   }

   function deactivateHomepageDescLink () {
      $this->_addExtra('HOMEPAGE_DESC_LINK','-1');
   }

   function showHomepageDescLink () {
      if ( $this->_issetExtra('HOMEPAGE_DESC_LINK') ) {
         $retour = $this->_getExtra('HOMEPAGE_DESC_LINK');
         if ( $retour == '1' ) {
            $retour = true;
         } else {
            $retour = false;
         }
      } else {
         $retour = false;
      }
      return $retour and $this->isHomepageLinkActive();
   }

   ##########################################
   # Wiki - Raum-Wiki
   ##########################################

   function withWikiFunctions () {
      global $c_pmwiki;
      if ( !isset($c_pmwiki) or !$c_pmwiki ) {
         return false;
      }
      $retour = false;
      $value = $this->_getExtraConfig('WIKI');
      if ($value == 1) {
         $retour = true;
      } elseif ( $this->isProjectRoom()
                 or $this->isCommunityRoom()
                 or $this->isGroupRoom()
               ) {
         $portal_room = $this->getContextItem();
         if ( $portal_room->withWikiFunctions() ) {
            $retour = true;
         }
      }
      return $retour;
   }

   function setWithWikiFunctions () {
      $this->_setExtraConfig('WIKI',1);
   }

   function setWithoutWikiFunctions () {
      $this->_setExtraConfig('WIKI',0);
   }

   function showWikiLink () {
      $retour = false;
      if ($this->withWikiFunctions() and $this->isWikiActive()) {
         $retour = true;
      }
      return $retour;
   }

   /** is wiki link active ?
    * can be switched at room configuration
    *
    * true = wiki link is active
    * false = wiki link is not active, default
    *
    * @return boolean
    */
   function isWikiActive () {
      $retour = false;
      $active = $this->_getExtra('WIKILINK');
      if ($active == 1) {
         $retour = true;
         $retour = $retour and $this->withWikiFunctions();
      }
      return $retour;
   }

   /** set activity of the wiki link, INTERNAL
    *
    * @param integer value: -1 = not
    *                        1 = yes
    */
   function _setWikiActivity ($value) {
      $this->_addExtra('WIKILINK',(int)$value);
   }

   /** set wiki link active
    */
   function setWikiActive () {
      $this->_setWikiActivity(1);
   }

   /** set wiki link inactive
    */
   function setWikiInactive () {
      $this->_setWikiActivity(-1);
   }

   function setWikiHomeLink(){
      $this->_addExtra('WIKIHOMELINK','1');
   }

   function getWikiHomeLink(){
      if ( $this->_issetExtra('WIKIHOMELINK') ) {
         $retour = $this->_getExtra('WIKIHOMELINK');
      } else {
         $retour = '-1';
      }
      return $retour;
   }

   function unsetWikiHomeLink(){
      $this->_addExtra('WIKIHOMELINK','-1');
   }

   function issetWikiHomeLink(){
      if ( $this->_issetExtra('WIKIHOMELINK') ) {
         $retour = $this->_getExtra('WIKIHOMELINK');
         if ($retour == '1'){
            $retour = true;
         }else{
            $retour = false;
         }
      } else {
         $retour =false;
      }
      return $retour;
   }

   function setWikiPortalLink(){
      $this->_addExtra('WIKIPORTALLINK','1');
   }

   function getWikiPortalLink(){
      if ( $this->_issetExtra('WIKIPORTALLINK') ) {
         $retour = $this->_getExtra('WIKIPORTALLINK');
      }else{
         $retour = '-1';
      }
      return $retour;
   }
   function unsetWikiPortalLink(){
      $this->_addExtra('WIKIPORTALLINK','-1');
   }

   function issetWikiPortalLink(){
      if ( $this->_issetExtra('WIKIPORTALLINK') ) {
         $retour = $this->_getExtra('WIKIPORTALLINK');
         if ($retour == '1'){
            $retour = true;
         }else{
            $retour = false;
         }
      } else {
         $retour =false;
      }
      return $retour;
   }

   function setWikiExists () {
      $this->_addExtra('WIKIEXISTS','1');
   }

   function unsetWikiExists () {
      $this->_addExtra('WIKIEXISTS','-1');
   }

   function existWiki () {
      if ( $this->_issetExtra('WIKIEXISTS') ) {
         $retour = $this->_getExtra('WIKIEXISTS');
         if ($retour == '1'){
            $retour = true;
         }else{
            $retour = false;
         }
      } else {
         $retour =false;
      }
      return $retour;
   }

   function setWikiSkin($skin){
      $this->_addExtra('WIKISKIN',$skin);
   }

   function getWikiSkin(){
      if ( $this->_issetExtra('WIKISKIN') ) {
         $retour = $this->_getExtra('WIKISKIN');
      } else {
         $retour ='pmwiki';
      }
      return $retour;
   }

   function setWikiTitle($title){
      $this->_addExtra('WIKITITLE',$title);
   }

   function getWikiTitle(){
      if ( $this->_issetExtra('WIKITITLE') ) {
         $retour = $this->_getExtra('WIKITITLE');
      } else {
         $retour = $this->getTitle();
      }
      return $retour;
   }


   function setWikiAdminPW($pw){
      $this->_addExtra('WIKIADMINPW',$pw);
   }

   function getWikiAdminPW(){
      if ( $this->_issetExtra('WIKIADMINPW') ) {
         $retour = $this->_getExtra('WIKIADMINPW');
      } else {
         $retour = 'admin';
      }
      return $retour;
   }


   function setWikiShowCommSyLogin(){
      $this->_addExtra('WIKISHOWLOGIN','1');
   }

   function unsetWikiShowCommSyLogin(){
      $this->_addExtra('WIKISHOWLOGIN','-1');
   }

   function WikiShowCommSyLogin(){
      if ( $this->_issetExtra('WIKISHOWLOGIN') ) {
         $retour = $this->_getExtra('WIKISHOWLOGIN');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   //  new features
   function setWikiEnableFCKEditor(){
      $this->_addExtra('WIKIENABLEFCKEDITOR','1');
   }

   function unsetWikiEnableFCKEditor(){
      $this->_addExtra('WIKIENABLEFCKEDITOR','-1');
   }

   function WikiEnableFCKEditor(){
      if ( $this->_issetExtra('WIKIENABLEFCKEDITOR') ) {
         $retour = $this->_getExtra('WIKIENABLEFCKEDITOR');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableSitemap(){
      $this->_addExtra('WIKIENABLESITEMAP','1');
   }

   function unsetWikiEnableSitemap(){
      $this->_addExtra('WIKIENABLESITEMAP','-1');
   }

   function WikiEnableSitemap(){
      if ( $this->_issetExtra('WIKIENABLESITEMAP') ) {
         $retour = $this->_getExtra('WIKIENABLESITEMAP');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableStatistic(){
      $this->_addExtra('WIKIENABLESTATISTIC','1');
   }

   function unsetWikiEnableStatistic(){
      $this->_addExtra('WIKIENABLESTATISTIC','-1');
   }

   function WikiEnableStatistic(){
      if ( $this->_issetExtra('WIKIENABLESTATISTIC') ) {
         $retour = $this->_getExtra('WIKIENABLESTATISTIC');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableSearch(){
      $this->_addExtra('WIKIENABLESEARCH','1');
   }

   function unsetWikiEnableSearch(){
      $this->_addExtra('WIKIENABLESEARCH','-1');
   }

   function WikiEnableSearch(){
      if ( $this->_issetExtra('WIKIENABLESEARCH') ) {
         $retour = $this->_getExtra('WIKIENABLESEARCH');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableRss(){
      $this->_addExtra('WIKIENABLERSS','1');
   }

   function unsetWikiEnableRss(){
      $this->_addExtra('WIKIENABLERSS','-1');
   }

   function WikiEnableRss(){
      if ( $this->_issetExtra('WIKIENABLERSS') ) {
         $retour = $this->_getExtra('WIKIENABLERSS');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableCalendar(){
      $this->_addExtra('WIKIENABLECALENDAR','1');
   }

   function unsetWikiEnableCalendar(){
      $this->_addExtra('WIKIENABLECALENDAR','-1');
   }

   function WikiEnableCalendar(){
      if ( $this->_issetExtra('WIKIENABLECALENDAR') ) {
         $retour = $this->_getExtra('WIKIENABLECALENDAR');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableGallery(){
      $this->_addExtra('WIKIENABLEGALLERY','1');
   }

   function unsetWikiEnableGallery(){
      $this->_addExtra('WIKIENABLEGALLERY','-1');
   }

   function WikiEnableGallery(){
      if ( $this->_issetExtra('WIKIENABLEGALLERY') ) {
         $retour = $this->_getExtra('WIKIENABLEGALLERY');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableNotice(){
      $this->_addExtra('WIKIENABLENOTICE','1');
   }

   function unsetWikiEnableNotice(){
      $this->_addExtra('WIKIENABLENOTICE','-1');
   }

   function WikiEnableNotice(){
      if ( $this->_issetExtra('WIKIENABLENOTICE') ) {
         $retour = $this->_getExtra('WIKIENABLENOTICE');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnablePdf(){
      $this->_addExtra('WIKIENABLEPDF','1');
   }

   function unsetWikiEnablePdf(){
      $this->_addExtra('WIKIENABLEPDF','-1');
   }

   function WikiEnablePdf(){
      if ( $this->_issetExtra('WIKIENABLEPDF') ) {
         $retour = $this->_getExtra('WIKIENABLEPDF');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableSwf(){
      $this->_addExtra('WIKIENABLESWF','1');
   }

   function unsetWikiEnableSwf(){
      $this->_addExtra('WIKIENABLESWF','-1');
   }

   function WikiEnableSwf(){
      if ( $this->_issetExtra('WIKIENABLESWF') ) {
         $retour = $this->_getExtra('WIKIENABLESWF');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableWmplayer(){
      $this->_addExtra('WIKIENABLEWMPLAYER','1');
   }

   function unsetWikiEnableWmplayer(){
      $this->_addExtra('WIKIENABLEWMPLAYER','-1');
   }

   function WikiEnableWmplayer(){
      if ( $this->_issetExtra('WIKIENABLEWMPLAYER') ) {
         $retour = $this->_getExtra('WIKIENABLEWMPLAYER');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableQuicktime(){
      $this->_addExtra('WIKIENABLEQUICKTIME','1');
   }

   function unsetWikiEnableQuicktime(){
      $this->_addExtra('WIKIENABLEQUICKTIME','-1');
   }

   function WikiEnableQuicktime(){
      if ( $this->_issetExtra('WIKIENABLEQUICKTIME') ) {
         $retour = $this->_getExtra('WIKIENABLEQUICKTIME');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   function setWikiEnableYoutubeGoogleVimeo(){
      $this->_addExtra('WIKIENABLEYOUTUBEGOOGLEVIMOEO','1');
   }

   function unsetWikiEnableYoutubeGoogleVimeo(){
      $this->_addExtra('WIKIENABLEYOUTUBEGOOGLEVIMOEO','-1');
   }

   function WikiEnableYoutubeGoogleVimeo(){
      if ( $this->_issetExtra('WIKIENABLEYOUTUBEGOOGLEVIMOEO') ) {
         $retour = $this->_getExtra('WIKIENABLEYOUTUBEGOOGLEVIMOEO');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

   // /new features

   function setWikiEditPW($pw){
      $this->_addExtra('WIKIEDITPW',$pw);
   }

   function getWikiEditPW(){
      if ( $this->_issetExtra('WIKIEDITPW') ) {
         $retour = $this->_getExtra('WIKIEDITPW');
      } else {
         $retour ='edit';
      }
      return $retour;
   }


   function setWikiReadPW($pw){
      $this->_addExtra('WIKIREADPW',$pw);
   }

   function getWikiReadPW(){
      if ( $this->_issetExtra('WIKIREADPW') ) {
         $retour = $this->_getExtra('WIKIREADPW');
      } else {
         $retour = '';
      }
      return $retour;
   }

   function setWikiWithSectionEdit () {
      $this->_addExtra('WIKI_SECTIONEDIT','1');
   }

   function setWikiWithoutSectionEdit () {
      $this->_addExtra('WIKI_SECTIONEDIT','-1');
   }

   function setWikiWithHeaderForSectionEdit () {
      $this->_addExtra('WIKI_SECTIONEDIT_HEADER','1');
   }

   function setWikiWithoutHeaderForSectionEdit () {
      $this->_addExtra('WIKI_SECTIONEDIT_HEADER','-1');
   }

   function wikiWithSectionEdit () {
      $retour = false;
      if ( $this->_issetExtra('WIKI_SECTIONEDIT') ) {
         $value = $this->_getExtra('WIKI_SECTIONEDIT');
         if ( $value == 1 ) {
            $retour = true;
         }
      }
      return $retour;
   }

   function wikiWithHeaderForSectionEdit () {
      $retour = false;
      if ( $this->_issetExtra('WIKI_SECTIONEDIT_HEADER') ) {
         $value = $this->_getExtra('WIKI_SECTIONEDIT_HEADER');
         if ( $value == 1 ) {
            $retour = true;
         }
      }
      return $retour;
   }

	// Wiki Discussion
	
	function setWikiEnableDiscussion(){
      $this->_addExtra('WIKIENABLEDISCUSSION','1');
   }

   function unsetWikiEnableDiscussion(){
      $this->_addExtra('WIKIENABLEDISCUSSION','-1');
   }

   function WikiEnableDiscussion(){
      if ( $this->_issetExtra('WIKIENABLEDISCUSSION') ) {
         $retour = $this->_getExtra('WIKIENABLEDISCUSSION');
      } else {
         $retour ='-1';
      }
      return $retour;
   }

	function WikiSetNewDiscussion($new_discussion){
		  if(!empty($new_discussion)){
		  	  if(!$this->_issetExtra('WIKIDISCUSSIONARRAY')){
		  	  	$this->_addExtra('WIKIDISCUSSIONARRAY', $new_discussion);
		  	  } else {
				  if ( $this->_issetExtra('WIKIDISCUSSIONARRAY') && !stristr($this->_getExtra('WIKIDISCUSSIONARRAY'), $new_discussion)) {
			         $discussion_string = $this->_getExtra('WIKIDISCUSSIONARRAY');
			         if(!empty($discussion_string)){
			         	$discussion_array = explode("$CSDW$", $discussion_string);
			         } else {
				         $discussion_array = array();
			         }
			         $discussion_array[] = $new_discussion;
			         $discussion_string = implode("$CSDW$", $discussion_array);
			         $this->_addExtra('WIKIDISCUSSIONARRAY',$discussion_string);
			      }
		      }
	      }
	}
	
	function getWikiDiscussionArray(){
		  if ( $this->_issetExtra('WIKIDISCUSSIONARRAY') ) {
	         $discussion_string = $this->_getExtra('WIKIDISCUSSIONARRAY');
	      } else {
	         $discussion_string ='';
	      }
	      $discussion_array = explode("$CSDW$", $discussion_string);
	      if($discussion_array[0] == ''){
	      	return false;
	      } else {
	      	return $discussion_array;
	      }
	}
	
	function unsetWikiDiscussionArray(){
		$this->_addExtra('WIKIDISCUSSIONARRAY','');
	}

   ##########################################
   # Pfad
   ##########################################

   function withPath () {
      $retour = false;
      if ( $this->_issetExtra('PATH') ) {
         $value = $this->_getExtra('PATH');
         if ($value == 1) {
            $retour = true;
         }
      } else {
         $value = $this->_getExtraConfig('PATH');
         if ($value == 1) {
            $retour = true;
         }
      }
      return $retour;
   }

   function setWithPath () {
      $this->_addExtra('PATH',1);
   }

   function setWithoutPath () {
      $this->_addExtra('PATH',0);
   }

   function InformationBoxWithExistingObject(){
      $retour = false;
      $id = $this->getInformationBoxEntryID();
      $manager = $this->_environment->getItemManager();
      $item = $manager->getItem($id);
      if (is_object($item) and !$item->isDeleted()){
         $entry_manager = $this->_environment->getManager($item->getItemType());
         $entry = $entry_manager->getItem($id);
         if (is_object($entry) and !$entry->isDeleted()){
            $retour = true;
         }
      }
      return $retour;
   }

   function withInformationBox(){
      $retour = false;
      if ($this->_issetExtra('WITHINFORMATIONBOX')) {
         if( $this->_getExtra('WITHINFORMATIONBOX') == 'yes' and $this->InformationBoxWithExistingObject()){
            $retour = true;
         }
      }
      return $retour;
   }

   function setwithInformationBox($value){
      $this->_addExtra('WITHINFORMATIONBOX',(string)$value);
   }


  function getDefaultProjectTemplateID(){
      $retour = '-1';
      if ($this->_issetExtra('DEFAULTPROJECTTEMPLATEID')) {
         $retour = $this->_getExtra('DEFAULTPROJECTTEMPLATEID');
      }
      return $retour;
   }

   function setDefaultProjectTemplateID($value){
      $this->_addExtra('DEFAULTPROJECTTEMPLATEID',(string)$value);
   }


  function getDefaultCommunityTemplateID(){
      $retour = '-1';
      if ($this->_issetExtra('DEFAULTCOMMUNITYTEMPLATEID')) {
         $retour = $this->_getExtra('DEFAULTCOMMUNITYTEMPLATEID');
      }
      return $retour;
   }

   function setDefaultCommunityTemplateID($value){
      $this->_addExtra('DEFAULTCOMMUNITYTEMPLATEID',(string)$value);
   }

   function getTemplateDescription(){
      $retour = '';
      if ($this->_issetExtra('TEMPLATEDESCRIPTION')) {
         $retour = $this->_getExtra('TEMPLATEDESCRIPTION');
      }
      return $retour;
   }

   function setTemplateDescription($value){
      $this->_addExtra('TEMPLATEDESCRIPTION',(string)$value);
   }


   function getInformationBoxEntryID(){
      $retour = getMessage('COMMON_INFORMATION_INDEX');
      if ($this->_issetExtra('INFORMATIONBOXENTRYID')) {
         $retour = $this->_getExtra('INFORMATIONBOXENTRYID');
      }
      return $retour;
   }
   function setInformationBoxEntryID($value){
      $this->_addExtra('INFORMATIONBOXENTRYID',(string)$value);
   }




   ##########################################
   # Tags
   ##########################################

   function isTagMandatory () {
      $retour = false;
      if ( $this->_issetExtra('TAGMANDATORY') ) {
         $value = $this->_getExtra('TAGMANDATORY');
         if ($value == 1) {
            $retour = true;
         }
      }
      return $retour;
   }

   function setTagMandatory () {
      $this->_addExtra('TAGMANDATORY',1);
   }

   function unsetTagMandatory () {
      $this->_addExtra('TAGMANDATORY',0);
   }


   function isTagEditedByAll () {
      $retour = true;
      if ( $this->_issetExtra('TAGEDITEDBY') ) {
         $value = $this->_getExtra('TAGEDITEDBY');
         if ($value == 2) {
            $retour = false;
         }
      }
      return $retour;
   }

   function setTagEditedByModerator () {
      $this->_addExtra('TAGEDITEDBY',2);
   }

   function setTagEditedByAll () {
      $this->_addExtra('TAGEDITEDBY',1);
   }

   function setWithTags(){
      $this->_addExtra('WITHTAGS',2);
   }

   function setWithoutTags(){
      $this->_addExtra('WITHTAGS',1);
   }

   function withTags(){
      $retour = false;
      if ( $this->_issetExtra('WITHTAGS') ){
         $re = $this->_getExtra('WITHTAGS');
         if ($re == 2){
            $retour = true;
         }
      }
      return $retour;
   }

   ##########################################
   # Chat
   ##########################################

   function withChatLink () {
      $retour = false;
      $value = $this->_getExtraConfig('CHATLINK');
      if ($value == 1) {
         $retour = true;
      } elseif ($this->isProjectRoom() or $this->isCommunityRoom()) {
         $portal_room = $this->getContextItem();
         if ( $portal_room->withChatLink() ) {
            $retour = true;
         }
      }
      return $retour;
   }

   function setWithChatLink () {
      $this->_setExtraConfig('CHATLINK',1);
   }

   function setWithoutChatLink () {
      $this->_setExtraConfig('CHATLINK',0);
   }

   function showChatLink () {
      $retour = false;
      if ($this->withChatLink() and $this->isChatLinkActive()) {
         $retour = true;
      }
      return $retour;
   }

   /** is chat link active ?
    * can be switched at room configuration
    *
    * true = chat link is active
    * false = chat link is not active, default
    *
    * @return boolean
    */
   function isChatLinkActive () {
      $retour = false;
      if ( $this->_issetExtra('CHATLINK') ) {
         $active = $this->_getExtra('CHATLINK');
         if ($active == 1) {
            $retour = true;
            $retour = $retour and $this->withChatLink();
         }
      }
      return $retour;
   }

   /** set activity of the chat link, INTERNAL
    *
    * @param integer value: -1 = not
    *                        1 = yes
    */
   function _setChatLinkActivity ($value) {
      $this->_addExtra('CHATLINK',(int)$value);
   }

   /** set chat link active
    */
   function setChatLinkActive () {
      $this->_setChatLinkActivity(1);
   }

   /** set chat link inactive
    */
   function setChatLinkInactive () {
      $this->_setChatLinkActivity(-1);
   }

   #########################################
   # rubrics
   #########################################

   /** returns a boolean, if the project HomeConf support rubric = true
    * else false
    */
   function withRubric($rubric_type) {
      if (!isset($this->_rubric_support[$rubric_type])) {
         $current_room_modules = $this->getHomeConf();
        //rubric is mentioned? if not -> false
         if ( !empty ($rubric_type) and stristr($current_room_modules,$rubric_type) ) {
           //if rubric is mentioned as <rubric>_none ->false
           if (stristr($current_room_modules,$rubric_type.'_none') ) {
               $this->_rubric_support[$rubric_type] = false;
           } else {
               if ($this->isExtraRubric($rubric_type) and !$this->showExtraRubric($rubric_type)) {
                 $this->_rubric_support[$rubric_type] = false;
               } else {
                 $this->_rubric_support[$rubric_type] = true;
               }
           }
         } else {
            $this->_rubric_support[$rubric_type] = false;
         }
      }
      return $this->_rubric_support[$rubric_type];
   }

   function getAvailableRubrics() {
      $current_room_modules = $this->getHomeConf();
      if (!empty($current_room_modules)) {
        $tokens = explode(',', $current_room_modules);
        $pointer = 0;
        foreach ($tokens as $module) {
           list($rubric, $view) = explode('_', $module);
            if ($rubric=='contact'){
               $rubric = 'user';
            }
            if ($this->withRubric($rubric)) {
              $this->_current_rubrics_array[$pointer++] = Module2Type($rubric);
            }
            $this->_current_home_conf_array[Module2Type($rubric)] = $view;
        }
      }
      return $this->_current_rubrics_array;
   }

   function getAvailableDefaultRubricArray () {
     $retour = array();
     $temp = $this->_default_rubrics_array;
     foreach ($temp as $rubric) {
        if ($rubric=='contact'){
           $rubric = 'user';
        }
        if ( !$this->isExtraRubric($rubric) ) {
           $retour[] = $rubric;
        } elseif ( $this->isExtraRubric($rubric) and $this->showExtraRubric($rubric) ) {
           $retour[] = $rubric;
        }
     }
     return $retour;
   }

   /**
    * turn rubrics on or off unsing the defined
    * keyword for rubrics
    *
    */
   function withExtraRubric($rubric) {
      $retour = false;
      $value = $this->_getExtraConfig($rubric);
     if ($value == 1) {
        $retour = true;
     }
      return $retour;
   }

   function isExtraRubric ($rubric) {
      $retour = false;
      if (in_array($rubric, $this->_configurable_rubrics) ) {
         $retour = true;
      }
     return $retour;
   }

   function showExtraRubric($rubric) {

      $retour = false;

      // check leave
      if (in_array($rubric, $this->_default_rubrics_array)) {
        $value = $this->_getExtraConfig($rubric);
        if ($value == 1) {
           $retour = true;
        }
         // check if there is a parent node
         if ( !$retour and ($this->isProjectRoom() or $this->isCommunityRoom()) ) {
            $context = $this->getContextItem();
           $retour = $context->withExtraRubric($rubric);
         }
      }
      return $retour;
   }

   function setWithExtraRubric ($rubric) {
         $this->_setExtraConfig($rubric,1);
   }

   function setWithoutExtraRubric ($rubric) {
       $this->_setExtraConfig($rubric,0);
   }


   ##########################################
   # ads
   ##########################################

   /** with ads ?
    * true = ads are possible
    * false = ads are not possible, default
    *
    * server always true
    *
    * @return boolean
    */
   function withAds () {
      $retour = false;
      if ($this->isServer()) {
         $retour = true;
      } else {
         $value = $this->_getExtraConfig('ADS');
         if ($value == 1) {
            $retour = true;
         }
      }
      return $retour;
   }

   function setWithAds () {
      $this->_setExtraConfig('ADS',1);
   }

   function setWithoutAds () {
      $this->_setExtraConfig('ADS',0);
   }

   /** show ads ?
    * can be switched at room configuration
    *
    * true = show ads
    * false = not show ads, default
    *
    * @return boolean
    */
   function showAds () {
      $retour = false;
      if ( $this->_issetExtra('SHOWADS') ) {
         $showads = $this->_getExtra('SHOWADS');
         if ($showads == 1) {
            $retour = true;
            if (!$this->isServer()) {
               $retour = $retour and $this->withAds();
            }
         }
      }
      return $retour;
   }

   /** set show ads, INTERNAL
    *
    * @param integer show ads: -1 = not
    *                           1 = yes
    */
   function _setShowAds ($value) {
      $this->_addExtra('SHOWADS',(int)$value);
   }

   /** set show ads
    */
   function setShowAds () {
      $this->_setShowAds(1);
   }

   /** set not show ads
    */
   function setNotShowAds () {
      $this->_setShowAds(-1);
   }

   /** set show ads, INTERNAL
    *
    * @param integer show ads: -1 = not
    *                           1 = yes
    */
   function _setShowGoogleAds ($value) {
      $this->_addExtra('SHOWGOOGLEADS',(int)$value);
   }

   /** set show ads
    */
   function setShowGoogleAds () {
      $this->_setShowGoogleAds(1);
   }

   /** set not show ads
    */
   function setNotShowGoogleAds () {
      $this->_setShowGoogleAds(-1);
   }

   /** show Google ads ?
    * can be switched at room configuration
    *
    * true = show Google ads
    * false = not show Google ads, default
    *
    * @return boolean
    */
   function showGoogleAds () {
      $retour = false;
      if ( $this->_issetExtra('SHOWGOOGLEADS') ) {
         $showgoogleads = $this->_getExtra('SHOWGOOGLEADS');
         if ($showgoogleads == 1) {
            $retour = true;
            if (!$this->isServer()) {
               $retour = $retour and $this->withAds();
            }
         }
      }
      return $retour;
   }

   /** set show ads, INTERNAL
    *
    * @param integer show ads: -1 = not
    *                           1 = yes
    */
   function _setShowAmazonAds ($value) {
      $this->_addExtra('SHOWAMAZONADS',(int)$value);
   }

   /** set show ads
    */
   function setShowAmazonAds () {
      $this->_setShowAmazonAds(1);
   }

   /** set not show ads
    */
   function setNotShowAmazonAds () {
      $this->_setShowAmazonAds(-1);
   }

   /** show Amazon ads ?
    * can be switched at room configuration
    *
    * true = show Amazon ads
    * false = not show Amazon ads, default
    *
    * @return boolean
    */
   function showAmazonAds () {
      $retour = false;
      if ( $this->_issetExtra('SHOWAMAZONADS') ) {
         $showads = $this->_getExtra('SHOWAMAZONADS');
         if ($showads == 1) {
            $retour = true;
            if (!$this->isServer()) {
               $retour = $retour and $this->withAds();
            }
         }
      }
      return $retour;
   }

   /** get part of sponsor array, INTERNAL
    *
    * @param string part: main for main sponsors
    *                     normal for normal sponsors
    *                     little for little sponsors
    *                     whole for the whole array
    *
    * @return array
    */
   function _getSponsorArray ($part) {
      $retour = array();
      if ( $this->_issetExtra('SPONSORS') ) {
         $sponsor_array = $this->_getExtra('SPONSORS');
         if ( $part == 'whole' ) {
            $retour = $sponsor_array;
         } elseif ( isset($sponsor_array[$part]) ) {
            $retour = $sponsor_array[$part];
         }
      }
      return $retour;
   }

   /** get main sponsor array
    *
    * @return array main sponsors
    */
   function getMainSponsorArray () {
      return $this->_getSponsorArray('MAIN');
   }

   /** get normal sponsor array
    *
    * @return array normal sponsors
    */
   function getNormalSponsorArray () {
      return $this->_getSponsorArray('NORMAL');
   }

   /** get little sponsor array
    *
    * @return array little sponsors
    */
   function getLittleSponsorArray () {
      return $this->_getSponsorArray('LITTLE');
   }

   /** get whole sponsor array
    *
    * @return array whole sponsors
    */
   function getWholeSponsorArray () {
      return $this->_getSponsorArray('whole');
   }

   /** set part of sponsor array, INTERNAL
    *
    * @param string part: main for main sponsors
    *                     normal for normal sponsors
    *                     little for little sponsors
    *                     whole for the whole array
    * @param array
    */
   function _setSponsorArray ($part, $array) {
      if ($part == 'whole') {
         $this->_addExtra('SPONSORS',$array);
      } else {
         $sponsor_array = $this->getWholeSponsorArray();
         $sponsor_array[$part] = $array;
         $this->setWholeSponsorArray($sponsor_array);
      }
   }

   /** set main sponsor array
    *
    * @param array main sponsors
    */
   function setMainSponsorArray ($array) {
      $this->_setSponsorArray('MAIN',$array);
   }

   /** set normal sponsor array
    *
    * @param array normal sponsors
    */
   function setNormalSponsorArray ($array) {
      $this->_setSponsorArray('NORMAL',$array);
   }

   /** set little sponsor array
    *
    * @param array little sponsors
    */
   function setLittleSponsorArray ($array) {
      $this->_setSponsorArray('LITTLE',$array);
   }

   /** set whole sponsor array
    *
    * @param array whole sponsors
    */
   function setWholeSponsorArray ($array) {
      $this->_setSponsorArray('whole',$array);
   }

   /** answer to question: has this room main sponsors?
    *
    * @return boolean
    */
   function hasMainSponsors () {
      $retour = false;
      $sponsor_array = $this->getMainSponsorArray();
      if ( !empty($sponsor_array) ) {
         $retour = true;
      }
      return $retour;
   }

   /** answer to question: has this room normal sponsors?
    *
    * @return boolean
    */
   function hasNormalSponsors () {
      $retour = false;
      $sponsor_array = $this->getNormalSponsorArray();
      if ( !empty($sponsor_array) ) {
         $retour = true;
      }
      return $retour;
   }

   /** answer to question: has this room little sponsors?
    *
    * @return boolean
    */
   function hasLittleSponsors () {
      $retour = false;
      $sponsor_array = $this->getLittleSponsorArray();
      if ( !empty($sponsor_array) ) {
         $retour = true;
      }
      return $retour;
   }

   /** count main sponsors of this room
    *
    * @return integer number of main sponsors
    */
   function getCountMainSponsors () {
      $retour = 0;
      if ( $this->hasMainSponsors() ) {
         $array = $this->getMainSponsorArray();
         $retour = count($array);
      }
      return $retour;
   }

   /** count normal sponsors of this room
    *
    * @return integer number of normal sponsors
    */
   function getCountNormalSponsors () {
      $retour = 0;
      if ( $this->hasNormalSponsors() ) {
         $array = $this->getNormalSponsorArray();
         $retour = count($array);
      }
      return $retour;
   }

   /** count little sponsors of this room
    *
    * @return integer number of little sponsors
    */
   function getCountLittleSponsors () {
      $retour = 0;
      if ( $this->hasLittleSponsors() ) {
         $array = $this->getLittleSponsorArray();
         $retour = count($array);
      }
      return $retour;
   }

   /** set title for sponsors, INTERNAL, do not use
    *
    * @param string part [MAIN|NORMAL|LITTLE]
    * @param string value title of sponsors
    */
   function _setSponsorTitle ($part, $value) {
      if ($part == 'whole') {
         $this->_addExtra('SPONSORTITLE',$value);
      } else {
         $title_array = $this->getWholeSponsorTitle();
         $title_array[$part] = $value;
         $this->setWholeSponsorTitle($title_array);
      }
   }

   /** set title of main sponsors
    *
    * @param string value title of main sponsors
    */
   function setMainSponsorTitle ($value) {
      $this->_setSponsorTitle('MAIN',$value);
   }

   /** set title of normal sponsors
    *
    * @param string value title of normal sponsors
    */
   function setNormalSponsorTitle ($value) {
      $this->_setSponsorTitle('NORMAL',$value);
   }

   /** set title of little sponsors
    *
    * @param string value title of little sponsors
    */
   function setLittleSponsorTitle ($value) {
      $this->_setSponsorTitle('LITTLE',$value);
   }

   /** set title of main sponsors to extra field
    *
    * @param array value array of titles
    */
   function setWholeSponsorTitle ($value) {
      $this->_setSponsorTitle('whole',$value);
   }

   /** get title for sponsors, INTERNAL, do not use
    *
    * @param string part [MAIN|NORMAL|LITTLE|whole]
    *
    * @return string or array title of sponsors
    */
   function _getSponsorTitle ($part) {
      $retour = '';
      if ( $this->_issetExtra('SPONSORTITLE') ) {
         $title_array = $this->_getExtra('SPONSORTITLE');
         if ( $part == 'whole' ) {
            $retour = $title_array;
         } elseif ( isset($title_array[$part]) ) {
            $retour = $title_array[$part];
         }
      }
      return $retour;
   }

   /** get title for main sponsors
    *
    * @return string value title of main sponsors
    */
   function getMainSponsorTitle () {
      return $this->_getSponsorTitle('MAIN');
   }

   /** get title for normal sponsors
    *
    * @return string value title of normal sponsors
    */
   function getNormalSponsorTitle () {
      return $this->_getSponsorTitle('NORMAL');
   }

   /** get title for little sponsors
    *
    * @return string value title of little sponsors
    */
   function getLittleSponsorTitle () {
      return $this->_getSponsorTitle('LITTLE');
   }

   /** get title array for sponsors
    *
    * @return array title of sponsors
    */
   function getWholeSponsorTitle () {
      return $this->_getSponsorTitle('whole');
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
      // $cron_array[] = $this->_CRON_METHOD();
      $cron_array = array();
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

   /** run crons of this room item
    *
    * @return array results of running crons
    */
   function runCron () {
      $this->setCacheOff();
      $cron_array = array();
      $cron_array['daily']  = $this->_runCronDaily();
      $cron_array['weekly'] = $this->_runCronWeekly();
      $this->saveWithoutChangingModificationInformation();
      return $cron_array;
   }

   /** run daily crons or get next running date, INTERNAL
    *
    * @return array results of running crons
    */
   function _runCronDaily () {
      $result = '';
      if (!$this->_runCronDailyAlready()) {
         $result = $this->_cronDaily();
         $this->_saveCronDailyTimestamp();
      } else {
         $cron_run_timestamp = $this->_getTimestampCronDaily();
         if ( empty($cron_run_timestamp) ) {
            include_once('functions/date_functions.php');
            $cron_run_timestamp = date('YmdHis');
         }
         $year = $cron_run_timestamp['0'].$cron_run_timestamp['1'].$cron_run_timestamp['2'].$cron_run_timestamp['3'];
         $month = $cron_run_timestamp['4'].$cron_run_timestamp['5'];
         $day = $cron_run_timestamp['6'].$cron_run_timestamp['7'];
         $hour = $cron_run_timestamp['8'].$cron_run_timestamp['9'];
         $minute = $cron_run_timestamp['10'].$cron_run_timestamp['11'];
         $second = $cron_run_timestamp['12'].$cron_run_timestamp['13'];
         $result[]['title'] = 'next run at '.date('d.m.Y H:i:s', mktime($hour+24, $minute, $second, $month, $day, $year));
      }
      return $result;
   }

   /** run weekly crons or get next running date, INTERNAL
    *
    * @return array results of running crons
    */
   function _runCronWeekly () {
      $result = '';
      if (!$this->_runCronWeeklyAlready()) {
         $result = $this->_cronWeekly();
         $this->_saveCronWeeklyTimestamp();
      } else {
         $cron_run_timestamp = $this->_getTimestampCronWeekly();
         if ( empty($cron_run_timestamp) ) {
            include_once('functions/date_functions.php');
            $cron_run_timestamp = date('YmdHis');
         }
         $year = $cron_run_timestamp['0'].$cron_run_timestamp['1'].$cron_run_timestamp['2'].$cron_run_timestamp['3'];
         $month = $cron_run_timestamp['4'].$cron_run_timestamp['5'];
         $day = $cron_run_timestamp['6'].$cron_run_timestamp['7'];
         $hour = $cron_run_timestamp['8'].$cron_run_timestamp['9'];
         $minute = $cron_run_timestamp['10'].$cron_run_timestamp['11'];
         $second = $cron_run_timestamp['12'].$cron_run_timestamp['13'];
         $result[]['title'] = 'next run at '.date('d.m.Y H:i:s', mktime($hour, $minute, $second, $month, $day+7, $year));
      }
      return $result;
   }

   /** run daily already?, INTERNAL
    *  run daily crons only once a day
    *
    * @return boolean: true  = cron run already
    *                  false = cron don't run today
    */
   function _runCronDailyAlready () {
      $hours = 20;
      $retour = true;
      $timestamp_last_cron_daily = $this->_getTimestampCronDaily();
      $timestamp_current_minus = date('YmdHis', mktime((date('H')-$hours), date('i'), date('s'), date('m'), date('d'), date('Y')));
      if ( $timestamp_current_minus > $timestamp_last_cron_daily ) {
         $retour = false;
      }
      unset($timestamp_current_minus);
      unset($timestamp_last_cron_daily);
      return $retour;
   }

   /** run weekly already?, INTERNAL
    *  run weekly crons only once a week
    *
    * @return boolean: true  = cron run already
    *                  false = cron don't run this week
    */
   function _runCronWeeklyAlready () {
      $hours = 20;
      $days = 6;
      $retour = true;
      $timestamp_last_cron_weekly = $this->_getTimestampCronWeekly();
      if ( !empty($timestamp_last_cron_weekly) ) {
         $timestamp_current_minus = date('YmdHis', mktime((date('H')-$hours), date('i'), date('s'), date('m'), (date('d')-$days), date('Y')));
         if ($timestamp_current_minus > $timestamp_last_cron_weekly) {
            $retour = false;
         }
         unset($timestamp_current_minus);
      } else {
         // init weekly cron to monday 00:00:00
         $day_array = array();
         for ($i=0; $i<7; $i++) {
            $day_array[date('D', mktime(date('H'), date('i'), date('s'), date('m'), (date('d')+$i), date('Y')))] = date('d')+$i;
         }
         $init_timestamp = date('YmdHis', mktime('00', '00', '00', date('m'), ($day_array['Mon']-7), date('Y')));
         $this->_setTimestampCronWeekly($init_timestamp);
         $this->save();
         unset($init_timestamp);
      }
      unset($timestamp_last_cron_weekly);
      return $retour;
   }

   /** save current timestamp for cron daily, INTERNAL
    *  ... to check running, see _runCronDailyAlready()
    */
   function _saveCronDailyTimestamp () {
      $this->_setTimestampCronDaily(date('YmdHis'));
   }

   /** set timestamp for cron daily, INTERNAL
    *
    * @param sting timestamp YYYYMMDDHHmmSS
    */
   function _setTimestampCronDaily ($value) {
      return $this->_addExtra('CRON_DAILY',$value);
   }

   /** get timestamp for cron daily, INTERNAL
    *
    * @return sting timestamp YYYYMMDDHHmmSS
    */
   function _getTimestampCronDaily () {
      return $this->_getExtra('CRON_DAILY');
   }

   /** save current timestamp for cron weekly, INTERNAL
    *  ... to check running, see _runCronWeeklyAlready()
    */
   function _saveCronWeeklyTimestamp () {
      $this->_setTimestampCronWeekly(date('YmdHis'));
   }

   /** set timestamp for cron weekly, INTERNAL
    *
    * @param sting timestamp YYYYMMDDHHmmSS
    */
   function _setTimestampCronWeekly ($value) {
      return $this->_addExtra('CRON_WEEKLY',$value);
   }

   /** get timestamp for cron weekly, INTERNAL
    *
    * @return sting timestamp YYYYMMDDHHmmSS
    */
   function _getTimestampCronWeekly () {
      return $this->_getExtra('CRON_WEEKLY');
   }

   public function _cronUnlinkFiles () {
      $cron_array = array();
      $cron_array['title'] = 'unlink files';
      $cron_array['description'] = 'unlink files not needed anymore';
      $cron_array['success'] = false;
      $cron_array['success_text'] = 'cron failed';

      $file_manager = $this->_environment->getFileManager();
      if ( $file_manager->deleteUnneededFiles($this->getItemID()) ) {
         $cron_array['success'] = true;
         $cron_array['success_text'] = 'cron done';
      }
      return $cron_array;
   }

   ############### BEGIN ####################
   # activity points
   ##########################################

   /** get title of a context
    * this method returns the title of the context
    *
    * @return string title of a context
    */
   function getActivityPoints () {
      return $this->_getValue('activity');
   }

   /** set title of a context
    * this method sets the title of the context
    *
    * @param string value title of the context
    */
   function setActivityPoints ($value) {
      $this->_setValue('activity', $value, TRUE);
   }

   function saveActivityPoints ($points) {
      $this->setActivityPoints($points + $this->getActivityPoints());
      if ( $this->isProjectRoom() ) {
         $manager = $this->_environment->getProjectManager();
      } elseif ( $this->isCommunityRoom() ) {
         $manager = $this->_environment->getCommunityManager();
      } elseif ( $this->isPortal() ) {
         $manager = $this->_environment->getPortalManager();
      } elseif ( $this->isServer() ) {
         $manager = $this->_environment->getServerManager();
      }
      if ( isset($manager) ) {
         $manager->saveActivityPoints($this);
      }
   }

   ##########################################
   # activity points
   ################# END ####################

   ############### BEGIN ####################
   # status of the room
   ##########################################

   /** get last status
    * this method returns the last status before blocking the room
    *
    * @return integer the status of the room before it was blocked
    */
   function getLastStatus () {
      $retour = false;
      if ($this->_issetExtra('LASTSTATUS')) {
         $retour = $this->_getExtra('LASTSTATUS');
      }
      return $retour;
   }

   /** set last status
    * this method sets the last status
    *
    * @param integer value status of the room
    */
   function setLastStatus ($value) {
      $this->_addExtra('LASTSTATUS',(int)$value);
   }

   /** set status of a room
    * this method returns the status of the room
    *
    * @param integer value status of a room
    */
   function setStatus ($value) {
       $this->_setValue('status',(int)$value,TRUE);
   }

   /** get status of a room
    * this method returns the status of the room
    *
    * @return integer status of a room
    */
   function getStatus () {
      return $this->_getValue('status');
   }

   /** open the room for usage
    * this method sets the status of the room to open
    */
   function open () {
      $this->_data['status'] = CS_ROOM_OPEN;
   }

   /** close a room
    * this method sets the status of the room to closed
    */
   function close () {
       $this->_data['status'] = CS_ROOM_CLOSED;
   }

   /** lock a room
    * this method sets the status of the room to locked
    */
   function lock () {
      $this->setLastStatus($this->getStatus());
      $this->_data['status'] = CS_ROOM_LOCK;
   }

   /** lock a room
    * this method sets the status of the room to locked
    */
   function unlock () {
       $temp = $this->getLastStatus();
       $this->setLastStatus($this->getStatus());
     $this->_data['status'] = $temp;
     unset($temp);
   }

    /** is room a normal open ?
    * this method returns a boolean explaining if a room is open
    *
    * @return boolean true, if a room is open
    *                 false, if a room is not open
    */
   function isOpen () {
      return $this->_data['status'] == CS_ROOM_OPEN;
   }

   /** is a room closed ?
    * this method returns a boolean explaining if a room is closed or not
    *
    * @return boolean true, if a room is closed
    *                 false, if a room is not closed
    */
   function isClosed () {
      return $this->_data['status'] == CS_ROOM_CLOSED;
   }

   /** is a room locked?
    * this method returns a boolean explaining if a room is locked
    *
    * @return boolean true, if a room is locked
    *                 false, if a room is not locked
    */
   function isLocked () {
      return $this->_data['status'] == CS_ROOM_LOCK;
   }

   function lockForMoveWithLinkedRooms () {
      $this->_addExtra('MOVE','2');
   }

   function lockForMove () {
      $this->_addExtra('MOVE','1');
   }

   function moveWithLinkedRooms () {
     $retour = false;
     if ($this->_issetExtra('MOVE')) {
       if ($this->_getExtra('MOVE') == 2) {
         $retour = true;
       }
     }
     return $retour;
   }

   function unlockForMove () {
      $this->_unsetExtra('MOVE');
   }

   /** is a room locked for movement between portals?
    * this method returns a boolean explaining if a room is locked for movement between portals
    *
    * @return boolean true, if a room is locked
    *                 false, if a room is not locked
    */
   function isLockedForMove () {
     $retour = false;
     if ($this->_issetExtra('MOVE')) {
       if ($this->_getExtra('MOVE') == 1 or $this->_getExtra('MOVE') == 2) {
         $retour = true;
       }
     }
     return $retour;
   }

   ##########################################
   # status of the room
   ################# END ####################


   /** save context
    * this method save the context
    */
   function save() {
      $manager = $this->_environment->getManager($this->_type);
      $this->_save($manager);
      $this->_changes = array();
   }

   function saveWithoutChangingModificationInformation () {
      $manager = $this->_environment->getManager($this->_type);
      $manager->saveWithoutChangingModificationInformation();
      $this->_save($manager);
      $this->_changes = array();
   }

   function mayEdit ($user) {
      $value = false;
      if ( !empty($user) ) {
         if ( $user->isRoot()
              or ( $user->isUser()
                   and ( $user->getItemID() == $this->getCreatorID()
                         or $this->isPublic()
                         or $this->isModeratorByUserID($user->getUserID(),$user->getAuthSource())
                         or ( $this->_environment->inCommunityRoom()
                              and $this->isProjectRoom()
                              and $user->isModerator()
                            )
                       )
                 )
            ) {
            $value = true;
         }
      }
      return $value;
   }

   function mayEditRegular ($user) {
      $value = false;
      if ( !empty($user) ) {
         if ( $user->isUser()
              and ( $user->getItemID() == $this->getCreatorID()
                    or $this->isPublic()
                    or $this->isModeratorByUserID($user->getUserID(),$user->getAuthSource())
                  )
            ) {
            $value = true;
         }
      }
      return $value;
   }

   function isModeratorByUserID ($user_id,$auth_source) {
      $retour = false;
      $mod_list = $this->getModeratorList();
      if ($mod_list->isNotEmpty()) {
         $mod = $mod_list->getFirst();
         while ($mod) {
            if ($mod->getUserID() == $user_id and $mod->getAuthSource() == $auth_source) {
               $retour = true;
               break;
            }
            $mod = $mod_list->getNext();
         }
      }
      return $retour;
   }

   function isLastModeratorByUserID ($user_id,$auth_source) {
      $retour = false;
      $mod_list = $this->getModeratorList();
      if ( $mod_list->getCount() == 1 ) {
         $mod = $mod_list->getFirst();
         if ( $mod->getUserID() == $user_id
              and $mod->getAuthSource() == $auth_source
            ) {
            $retour = true;
         }
      }
      return $retour;
   }

   /** get users of the context
    * this method returns a list of users of the context
    *
    * @return object cs_list a list of user (cs_user_item)
    */
   function getUserList () {
      if ( empty($this->_user_list) ) {
         $userManager = $this->_environment->getUserManager();
         $userManager->resetLimits();
         $userManager->setContextLimit($this->getItemID());
         $userManager->setUserLimit();
         $userManager->select();
         $this->_user_list = $userManager->get();
      }
      return $this->_user_list;
   }


   function isUser ($user) {
      $retour = false;
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setContextLimit($this->getItemID());
      $user_manager->setUserIDLimit($user->getUserID());
      $user_manager->setAuthSourceLimit($user->getAuthSource());
      $user_manager->setUserLimit();
      $user_manager->select();
      $user_list = $user_manager->get();
      if ( $user_list->isNotEmpty() ) {
         $retour = true;
      }
      return $retour;
   }

   function getUserByUserID ($user_id,$auth_source) {
      $retour = NULL;
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setContextLimit($this->getItemID());
      $user_manager->setUserIDLimit($user_id);
      $user_manager->setAuthSourceLimit($auth_source);
      $user_manager->select();
      $user_list = $user_manager->get();
      if ( $user_list->isNotEmpty() and $user_list->getCount() == 1 ) {
         $retour = $user_list->getFirst();
      }
      return $retour;
   }

   function _is_perspective ($rubric) {
      $in_array = in_array($rubric, array(CS_GROUP_TYPE, CS_TOPIC_TYPE, CS_INSTITUTION_TYPE)) ;
      if ($rubric == CS_INSTITUTION_TYPE) {
         $in_array = $this->withRubric(CS_INSTITUTION_TYPE);
      }
      return $in_array;
   }


      /** asks if item is editable by everybody or just creator
    *
    * @param value
    *
    * @author CommSy Development Group
    */
   function isPublic() {
      if ($this->_getValue('public')== 1) {
         return true;
      } else {
        return false;
      }
   }

   /** sets if announcement is editable by everybody or just creator
    *
    * @param value
    *
    * @author CommSy Development Group
    */
   function setPublic ($value) {
      $this->_setValue('public', $value);
   }

   /** sets if announcement is editable by everybody or just creator
    *
    * @param value
    *
    * @author CommSy Development Group
    */
   function getPublic () {
      return $this->_getValue('public');
   }


   ######################################################################
   # statistic functions
   ######################################################################

   function getCountAnnouncements ($start,$end) {
      if (!isset($this->_count_announcements)) {
         $manager = $this->_environment->getAnnouncementManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_announcements = $manager->getCountAnnouncements($start,$end);
      }
      $retour = $this->_count_announcements;
      return $retour;
   }

   function getCountNewAnnouncements ($start, $end) {
      if ( !isset($this->_count_new_announcements )) {
         $manager = $this->_environment->getAnnouncementManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_announcements = $manager->getCountNewAnnouncements($start,$end);
      }
      $retour = $this->_count_new_announcements;
      return $retour;
   }

   function getCountModAnnouncements ($start, $end) {
      if ( !isset($this->_count_mod_announcements )) {
         $manager = $this->_environment->getAnnouncementManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_announcements = $manager->getCountModAnnouncements($start,$end);
      }
      $retour = $this->_count_mod_announcements;
      return $retour;
   }

   function getCountDates ($start,$end) {
      if (!isset($this->_count_dates)) {
         $manager = $this->_environment->getDateManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_dates = $manager->getCountDates($start,$end);
      }
      $retour = $this->_count_dates;
      return $retour;
   }

   function getCountNewDates ($start,$end) {
      if (!isset($this->_count_new_dates)) {
         $manager = $this->_environment->getDateManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_dates = $manager->getCountNewDates($start,$end);
      }
      $retour = $this->_count_new_dates;
      return $retour;
   }

   function getCountModDates ($start,$end) {
      if (!isset($this->_count_mod_dates)) {
         $manager = $this->_environment->getDateManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_dates = $manager->getCountModDates($start,$end);
      }
      $retour = $this->_count_mod_dates;
      return $retour;
   }

   function getCountMaterials ($start,$end) {
      if (!isset($this->_count_materials)) {
         $manager = $this->_environment->getMaterialManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_materials = $manager->getCountMaterials($start,$end);
      }
      $retour = $this->_count_materials;
      return $retour;
   }

   function getCountNewMaterials ($start,$end) {
      if (!isset($this->_count_new_materials)) {
         $manager = $this->_environment->getMaterialManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_materials = $manager->getCountNewMaterials($start,$end);
      }
      $retour = $this->_count_new_materials;
      return $retour;
   }

   function getCountModMaterials ($start,$end) {
      if (!isset($this->_count_mod_materials)) {
         $manager = $this->_environment->getMaterialManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_materials = $manager->getCountModMaterials($start,$end);
      }
      $retour = $this->_count_mod_materials;
      return $retour;
   }

   function getCountDiscussions ($start,$end) {
      if (!isset($this->_count_discussions)) {
         $manager = $this->_environment->getDiscussionManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_discussions = $manager->getCountDiscussions($start,$end);
      }
      $retour = $this->_count_discussions;
      return $retour;
   }

   function getCountNewDiscussions ($start,$end) {
      if (!isset($this->_count_new_discussions)) {
         $manager = $this->_environment->getDiscussionManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_discussions = $manager->getCountNewDiscussions($start,$end);
      }
      $retour = $this->_count_new_discussions;
      return $retour;
   }

   function getCountModDiscussions ($start,$end) {
      if (!isset($this->_count_mod_discussions)) {
         $manager = $this->_environment->getDiscussionManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_discussions = $manager->getCountModDiscussions($start,$end);
      }
      $retour = $this->_count_mod_discussions;
      return $retour;
   }

   function getCountDiscArticles ($start,$end) {
      if (!isset($this->_count_discarticles)) {
         $manager = $this->_environment->getDiscussionArticleManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_discarticles = $manager->getCountDiscArticles($start,$end);
      }
      $retour = $this->_count_discarticles;
      return $retour;
   }

   function getCountNewDiscArticles ($start,$end) {
      if (!isset($this->_count_new_discarticles)) {
         $manager = $this->_environment->getDiscussionArticleManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_discarticles = $manager->getCountNewDiscArticles($start,$end);
      }
      $retour = $this->_count_new_discarticles;
      return $retour;
   }

   function getCountModDiscArticles ($start,$end) {
      if (!isset($this->_count_mod_disarticles)) {
         $manager = $this->_environment->getDiscussionArticleManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_disarticles = $manager->getCountModDiscArticles($start,$end);
      }
      $retour = $this->_count_mod_disarticles;
      return $retour;
   }

   function getCountUsers ($start,$end) {
      if (!isset($this->_count_users)) {
         $manager = $this->_environment->getUserManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_users = $manager->getCountUsers($start,$end);
      }
      $retour = $this->_count_users;
      return $retour;
   }

   function getCountNewUsers ($start,$end) {
      if (!isset($this->_count_new_users)) {
         $manager = $this->_environment->getUserManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_users = $manager->getCountNewUsers($start,$end);
      }
      $retour = $this->_count_new_users;
      return $retour;
   }

   function getCountModUsers ($start,$end) {
      if (!isset($this->_count_mod_users)) {
         $manager = $this->_environment->getUserManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_users = $manager->getCountModUsers($start,$end);
      }
      $retour = $this->_count_mod_users;
      return $retour;
   }

   function getCountGroups ($start,$end) {
      if (!isset($this->_count_groups)) {
         $manager = $this->_environment->getGroupManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_groups = $manager->getCountGroups($start,$end);
      }
      $retour = $this->_count_groups;
      return $retour;
   }

   function getCountNewGroups ($start,$end) {
      if (!isset($this->_count_new_groups)) {
         $manager = $this->_environment->getGroupManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_groups = $manager->getCountNewGroups($start,$end);
      }
      $retour = $this->_count_new_groups;
      return $retour;
   }

   function getCountModGroups ($start,$end) {
      if (!isset($this->_count_mod_groups)) {
         $manager = $this->_environment->getGroupManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_groups = $manager->getCountModGroups($start,$end);
      }
      $retour = $this->_count_mod_groups;
      return $retour;
   }

   function getCountTopics ($start,$end) {
      if (!isset($this->_count_topics)) {
         $manager = $this->_environment->getTopicManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_topics = $manager->getCountTopics($start,$end);
      }
      $retour = $this->_count_topics;
      return $retour;
   }

   function getCountNewTopics ($start,$end) {
      if (!isset($this->_count_new_topics)) {
         $manager = $this->_environment->getTopicManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_topics = $manager->getCountNewTopics($start,$end);
      }
      $retour = $this->_count_new_topics;
      return $retour;
   }

   function getCountModTopics ($start,$end) {
      if (!isset($this->_count_mod_topics)) {
         $manager = $this->_environment->getTopicManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_topics = $manager->getCountModTopics($start,$end);
      }
      $retour = $this->_count_mod_topics;
      return $retour;
   }

   function getCountInstitutions ($start,$end) {
      if (!isset($this->_count_institutions)) {
         $manager = $this->_environment->getInstitutionManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_institutions = $manager->getCountInstitutions($start,$end);
      }
      $retour = $this->_count_institutions;
      return $retour;
   }

   function getCountNewInstitutions ($start,$end) {
      if (!isset($this->_count_new_institutions)) {
         $manager = $this->_environment->getInstitutionManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_institutions = $manager->getCountNewInstitutions($start,$end);
      }
      $retour = $this->_count_new_institutions;
      return $retour;
   }

   function getCountModInstitutions ($start,$end) {
      if (!isset($this->_count_mod_institutions)) {
         $manager = $this->_environment->getInstitutionManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_institutions = $manager->getCountModInstitutions($start,$end);
      }
      $retour = $this->_count_mod_institutions;
      return $retour;
   }

   function getCountToDos ($start,$end) {
      if (!isset($this->_count_todos)) {
         $manager = $this->_environment->getToDosManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_todos = $manager->getCountToDos($start,$end);
      }
      $retour = $this->_count_todos;
      return $retour;
   }

   function getCountNewToDos ($start, $end) {
      if ( !isset($this->_count_new_todos )) {
         $manager = $this->_environment->getToDosManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_new_todos = $manager->getCountNewToDos($start,$end);
      }
      $retour = $this->_count_new_todos;
      return $retour;
   }

   function getCountModToDos ($start, $end) {
      if ( !isset($this->_count_mod_todos )) {
         $manager = $this->_environment->getToDosManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->getItemID());
         $this->_count_mod_todos = $manager->getCountModToDos($start,$end);
      }
      $retour = $this->_count_mod_todos;
      return $retour;
   }

   function getCountProjects ($start,$end) {
      if (!isset($this->_count_projects)) {
         $manager = $this->_environment->getProjectManager();
         $manager->resetLimits();
       if ($this->isCommunityRoom()) {
            $manager->setContextLimit($this->getContextID());
            $manager->setCommunityRoomLimit($this->getItemID());
       } else {
            $manager->setContextLimit($this->getItemID());
       }
         $this->_count_projects = $manager->getCountProjects($start,$end);
      }
      $retour = $this->_count_projects;
      return $retour;
   }

   function getCountNewProjects ($start,$end) {
      if (!isset($this->_count_new_projects)) {
         $manager = $this->_environment->getProjectManager();
         $manager->resetLimits();
       if ($this->isCommunityRoom()) {
            $manager->setContextLimit($this->getContextID());
            $manager->setCommunityRoomLimit($this->getItemID());
       } else {
            $manager->setContextLimit($this->getItemID());
       }
         $this->_count_new_projects = $manager->getCountNewProjects($start,$end);
      }
      $retour = $this->_count_new_projects;
      return $retour;
   }

   function getCountModProjects ($start,$end) {
      if (!isset($this->_count_mod_projects)) {
         $manager = $this->_environment->getProjectManager();
         $manager->resetLimits();
       if ($this->isCommunityRoom()) {
            $manager->setContextLimit($this->getContextID());
            $manager->setCommunityRoomLimit($this->getItemID());
       } else {
            $manager->setContextLimit($this->getItemID());
       }
         $this->_count_mod_projects = $manager->getCountModProjects($start,$end);
      }
      $retour = $this->_count_mod_projects;
      return $retour;
   }

   /** get time spread for items on home
    * this method returns the time spread for items on the home of the context
    *
    * @return integer the time spread
    *
    * @author CommSy Development Group
    */
   function getTimeSpread () {
      $retour = '30';
      if ($this->_issetExtra('TIMESPREAD')) {
         $retour = $this->_getExtra('TIMESPREAD');
      }
      return $retour;
   }


   /** set page impression array
    *
    * @param array value page impression
    */
   function setPageImpressionArray ($value) {
      $this->_addExtra('PAGE_IMPRESSION',(array)$value);
   }

   /** get page impression array
    */
   function getPageImpressionArray () {
      $retour = $this->_getExtra('PAGE_IMPRESSION');
      if (empty($retour)) {
         $retour = array();
      }
      return $retour;
   }

   function getPageImpressions ($external_timespread = 0) {
      if($external_timespread != 0){
         $timespread = $external_timespread;
      }else{
         $timespread = $this->getTimeSpread();
      }
      $count = 0;
      $pi_array = $this->getPageImpressionArray();
      for ($i=0; $i<$timespread; $i++) {
         if (!empty($pi_array[$i])) {
            $count = $count + $pi_array[$i];
         }
      }
      $log_manager = $this->_environment->getLogManager();
      $log_manager->resetLimits();
      $log_manager->setContextLimit($this->getItemID());
      $page_impressions = $log_manager->getCountAll();
      unset($log_manager);
      return $count + $page_impressions;
   }


   function getNewEntries($external_timespread = 0) {
      if($external_timespread != 0){
         $timespread = $external_timespread;
      }else{
         $timespread = $this->getTimeSpread();
      }
      $new_entries = 0;
      $conf = $this->getHomeConf();
      $rubrics = array();
      if ( !empty($conf) ) {
          $rubrics = explode(',', $conf);
      }
      $check_managers = array();
      foreach ( $rubrics as $rubric ) {
         list($rubric_name, $rubric_status) = explode('_', $rubric);
         if ( $rubric_status != 'none' ){
            $check_managers[] = $rubric_name;
            if ( $rubric_name == CS_DISCUSSION_TYPE ) {
               $check_managers[] = 'discarticle';
            }
            if ( $rubric_name == CS_MATERIAL_TYPE ) {
               $check_managers[] = CS_SECTION_TYPE;
            }
         }
      }
      $check_managers[] = CS_ANNOTATION_TYPE;
      $item_manager =  $this->_environment->getItemManager();
      $item_manager->setContextLimit($this->getItemID());
      $item_manager->setExistenceLimit($timespread);
      $item_manager->setTypeArrayLimit($check_managers);
      $item_manager->select();
      $new_entries = $item_manager->getIDArray();
      $count_total = count($new_entries);
      unset($item_manager);
      return $count_total;
  }

  function getActiveMembers($external_timespread = 0) {
      if($external_timespread != 0){
         $timespread = $external_timespread;
      }else{
         $timespread = $this->getTimeSpread();
      }
      $user_manager = $this->_environment->getUserManager();
      $user_manager->reset();
      $user_manager->setContextLimit($this->getItemID());
      $user_manager->setUserLimit();
      $user_manager->setLastLoginLimit($timespread);
      $ids = $user_manager->getIDArray();
      $active = count($ids);
      unset($user_manager);
      return $active;
   }

  function getActiveAndAllMembersAsArray($external_timespread = 0) {
      if($external_timespread != 0){
         $timespread = $external_timespread;
      }else{
         $timespread = $this->getTimeSpread();
      }
      $user_manager = $this->_environment->getUserManager();
      $user_manager->reset();
      $user_manager->setContextLimit($this->getItemID());
      $user_manager->setUserLimit();
      $retour['all_users'] = $user_manager->getCountAll();
      $context = $this->_environment->getCurrentContextItem();
      $user_manager->setLastLoginLimit($timespread);
      $ids = $user_manager->getIDArray();
      $retour['active'] = count($ids);
      return $retour;
   }

   function getAllUsers() {
      $user_manager = $this->_environment->getUserManager();
      $user_manager->reset();
      $user_manager->setContextLimit($this->getItemID());
      $user_manager->setUserLimit();
      return $user_manager->getCountAll();
   }

   function delete () {
      if ( $this->existWiki() ) {
         $wiki_manager = $this->_environment->getWikiManager();
         $wiki_manager->deleteWiki($this);
      }
   }
}
?>