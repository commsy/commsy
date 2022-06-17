<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
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
   * @var \cs_list $_moderator_list
   */
  var $_moderator_list = NULL;

  /**
   * a list of the users
   */
  private $_user_list = NULL;

  var $_default_rubrics_array = array();
  var $_plugin_rubrics_array = array();

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

  var $_count_items = NULL;

  /** constructor: cs_context_item
   * the only available constructor, initial values for internal variables
   *
   * @param object environment the environment of the commsy
   */
  function __construct($environment) {
    cs_item::__construct($environment);
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

    $colors = [];
    $colors['schema']                     = 'DEFAULT';
    $colors['tabs_background']            = '#3B658E';
    $colors['tabs_focus']                 = '#EC930D';
    $colors['table_background']           = '#EFEFEF';
    $colors['tabs_title']                 = 'white';
    $colors['tabs_separators']            = 'white';
    $colors['tabs_dash']                  = 'white';
    $colors['headline_text']              = 'white';
    $colors['hyperlink']                  = '#01458A';
    $colors['help_background']            = '#2079D3';
    $colors['boxes_background']           = 'white';
    $colors['content_background']         = '#EFECE2';
    $colors['list_entry_odd']             = '#EFECE2';
    $colors['list_entry_even']            = '#F7F7F7';
    $colors['myarea_headline_background'] = '#CDCBC2';
    $colors['myarea_headline_title']      = 'white';
    $colors['myarea_title_background']    = '#F7F7F7';
    $colors['myarea_content_background']  = '#EFECE2';
    $colors['myarea_section_title']       = '#666666';
    $colors['portal_tabs_background']     = '#666666';
    $colors['portal_tabs_title']          = 'white';
    $colors['portal_tabs_focus']          = '#EC930D';
    $colors['portal_td_head_background']  = '#F7F7F7';
    $colors['index_td_head_title']        = 'white';
    $colors['date_title']                 = '#EC930D';
    $colors['info_color']                 = '#827F76';
    $colors['disabled']                   = '#B0B0B0';
    $colors['warning']                    = '#FC1D12';
    $colors['welcome_text']               = '#3B658E';
    $colors['head_background']            = '#2A4E72';
    $colors['page_title']                 = '#000000';

    $this->_default_colors = $colors;
  }

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
  
  function isMaterialOpenForGuests () {
  if ($this->_issetExtra('MATERIAL_GUESTS') and $this->_getExtra('MATERIAL_GUESTS') == 1) {
      return true;
    } else {
      return false;
    }
  }
  
  function setMaterialOpenForGuests () {
  	$this->_addExtra('MATERIAL_GUESTS', 1, TRUE);
  }
  
  function setMaterialClosedForGuests () {
  	$this->_addExtra('MATERIAL_GUESTS', 0, TRUE);
  }

  function isAssignmentOnlyOpenForRoomMembers () {
    $retour = false;
    if ($this->_issetExtra('ROOMASSOCIATION') and $this->_getExtra('ROOMASSOCIATION')=='onlymembers') {
      $retour = true;
    }
    return $retour;
  }

  function setAssignmentOpenForAnybody () {
    $this->_addExtra('ROOMASSOCIATION','forall');
  }

  function setAssignmentOnlyOpenForRoomMembers () {
    $this->_addExtra('ROOMASSOCIATION','onlymembers');
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

  function isUserroom () {
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

  function setShowNoAnnouncementsOnHome() {
    $this->_addExtra('SHOWANNOUNCEMENTSONHOME','no');
  }

  function setShowAnnouncementsOnHome() {
    $this->_addExtra('SHOWANNOUNCEMENTSONHOME','yes');
  }

  function isShowAnnouncementsOnHome() {
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
  	// sanitize title
  	$converter = $this->_environment->getTextConverter();
    $value = htmlentities($value);
  	$value = $converter->sanitizeHTML($value);
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

  function getMaxUploadSizeInBytes() {
    $val = ini_get('upload_max_filesize');
    $val = trim($val);

    // if this is in portal context
    if(   $this->_environment->inPortal()) {
      // check for portal limit
      if($this->_issetExtra('MAX_UPLOAD_SIZE')) {
        $val = $this->_getExtra('MAX_UPLOAD_SIZE');
      }
    }

    // if this is in room context
    if(   $this->_environment->inGroupRoom() ||
            $this->_environment->inPrivateRoom() ||
            $this->_environment->inProjectRoom() ||
            $this->_environment->inCommunityRoom()) {
      // check for portal limit
      $portal_item = $this->_environment->getCurrentPortalItem();
      $portal_limit = $portal_item->getMaxUploadSizeExtraOnly();
      if($portal_limit != '') {
        $val = $portal_limit;
      }

      // check for room limit
      if($this->_issetExtra('MAX_UPLOAD_SIZE')) {
        $val = $this->_getExtra('MAX_UPLOAD_SIZE');
      }
    }

    $last = $val[mb_strlen($val)-1];
    $numericVal = (int) substr($val, 0, -1);
    switch($last) {
      case 'k':
      case 'K':
        $numericVal *= 1024;
        break;
      case 'm':
      case 'M':
        $numericVal *= 1048576;
        break;
    }

    // check if limit is beyond server maximum
    $server_limit = ini_get('upload_max_filesize');
    $server_last = $server_limit[mb_strlen($server_limit)-1];
    $numericServerVal = (int) substr($server_limit, 0, -1);
    switch($server_last) {
      case 'k':
      case 'K':
        $numericServerVal *= 1024;
        break;
      case 'm':
      case 'M':
        $numericServerVal *= 1048576;
        break;
    }

    if($numericServerVal < $numericVal) {
      return $numericServerVal;
    }

    return $numericVal;
  }

  function setMaxUploadSizeInBytes($val) {
    if($val != '') {
      $this->_addExtra('MAX_UPLOAD_SIZE',$val);
    } else {
      $this->_unsetExtra('MAX_UPLOAD_SIZE');
    }
  }

  function getMaxUploadSizeExtraOnly() {
    $retour = '';
    if($this->_issetExtra('MAX_UPLOAD_SIZE')) {
      $retour = $this->_getExtra('MAX_UPLOAD_SIZE');
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
      if ( $this->_getExtra('IS_SHOW_ON_HOME_'.$tag) == 'NO') {
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
      if ( $this->_getExtra('IS_SHOW_ON_HOME_'.$item_id) == 'NO') {
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
      unset($user_manager);
      if ( $this->_contact_moderator_list->isEmpty() ) {
        if ( $this->isClosed()
                and !$this->_environment->isArchiveMode()
        ) {
          $user_manager = $this->_environment->getZzzUserManager();
          $user_manager->setContextLimit($this->getItemID());
          $user_manager->setContactModeratorLimit();
          $user_manager->select();
          $this->_contact_moderator_list = $user_manager->get();
          unset($user_manager);
          if ( $this->_contact_moderator_list->isEmpty() ) {
            $this->_contact_moderator_list = $this->getModeratorList();
          }
        } else {
          $this->_contact_moderator_list = $this->getModeratorList();
        }
      }
    }
    return $this->_contact_moderator_list;
  }
  
  function getContactModeratorListString(){
    $list = new cs_list();
    $counter = 1;
    $return = '';
    $list = $this->getContactModeratorList();      
    $length = $list->getCount();
    if (!$list->isEmpty()){
        $contact = $list->getFirst();
        while ($contact){
            $return .= $contact->getFullname();
            if ($counter < $length){
                $return .=', ';
                $counter++;  
            }
            $contact = $list->getNext();  
        } 
     }  
     return $return;
  }

  function getModeratorListString(){
    $list = new cs_list();
    $counter = 1;
    $return = '';
    $list = $this->getModeratorList();      
    $length = $list->getCount();
    if (!$list->isEmpty()){
        $contact = $list->getFirst();
        while ($contact){
            $return .= $contact->getFullname();
            if ($counter < $length){
                $return .=', ';
                $counter++;  
            }
            $contact = $list->getNext();  
        } 
     }  
     return $return;
  }


  /** get description of a context
   * this method returns the description of the context
   *
   * @return string description of a context
   */
  function getDescriptionByLanguage ($language) {
    $retour = '';
    if ($language == 'browser') {
      // ???
    }
    $desc_array = $this->getDescriptionArray();
    if ( !empty($desc_array[cs_strtoupper($language)]) ) {
      $retour = $desc_array[cs_strtoupper($language)];
    }
    return $retour;
  }

  function getDescription () {
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
      if(!empty($desc_array)) {
        foreach ($desc_array as $desc) {
          if (!empty($desc)) {
            $retour = $desc;
            break;
          }
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
    if ( mb_strtoupper($lang, 'UTF-8') == 'USER' ) {
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
    $desc_array[mb_strtoupper($language, 'UTF-8')] = $value;
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

    public function getAGBChangeDate(): ?DateTimeImmutable
    {
        if ($this->_issetExtra('AGB_CHANGE_DATE')) {
            $agbChangeDate = $this->_getExtra('AGB_CHANGE_DATE') ?? '';
            return !empty($agbChangeDate) ?
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $agbChangeDate) :
                null;
        }

        return null;
    }

    public function setAGBChangeDate(?DateTimeImmutable $agbChangeDate): self
    {
      $this->_addExtra(
          'AGB_CHANGE_DATE',
          $agbChangeDate ? $agbChangeDate->format('Y-m-d H:i:s') : ''
      );

      return $this;
    }

  function setActionBarVisibilityDefault($value) {
    $this->_addExtra('ACTIONBARVISIBILITY',$value);
  }

  function isActionBarVisibleAsDefault() {
    $retour = true;
    if ($this->_issetExtra('ACTIONBARVISIBILITY') and $this->_getExtra('ACTIONBARVISIBILITY') == -1) {
      $retour = false;
    }
    return $retour;
  }

  function setReferenceBarVisibilityDefault($value) {
    $this->_addExtra('REFERENCEBARVISIBILITY',$value);
  }

  function isReferenceBarVisibleAsDefault() {
    $retour = false;
    if ($this->_issetExtra('REFERENCEBARVISIBILITY') and $this->_getExtra('REFERENCEBARVISIBILITY') == 1) {
      $retour = true;
    }
    return $retour;
  }

  function setDetailsBarVisibilityDefault($value) {
    $this->_addExtra('DETAILSBARVISIBILITY',$value);
  }

  function isDetailsBarVisibleAsDefault() {
    $retour = false;
    if ($this->_issetExtra('DETAILSBARVISIBILITY') and $this->_getExtra('DETAILSBARVISIBILITY') == 1) {
      $retour = true;
    }
    return $retour;
  }

  function setAnnotationsBarVisibilityDefault($value) {
    $this->_addExtra('ANNOTATIONSBARVISIBILITY',$value);
  }

  function isAnnotationsBarVisibleAsDefault() {
    $retour = false;
    if ($this->_issetExtra('ANNOTATIONSBARVISIBILITY') and $this->_getExtra('ANNOTATIONSBARVISIBILITY') == 1) {
      $retour = true;
    }
    return $retour;
  }

  function setWithAssociations() {
    $this->_addExtra('WITHASSOCIATIONS',2);
  }

  function setWithoutAssociations() {
    $this->_addExtra('WITHASSOCIATIONS',1);
  }

  function withAssociations() {
    $retour = false;
    if ($this->_issetExtra('WITHASSOCIATIONS') ) {
      $re = $this->_getExtra('WITHASSOCIATIONS');
      if ($re == 2) {
        $retour = true;
      }
    }else {
      $retour = true;
    }
    return $retour;
  }

  function setWithBuzzwords() {
    $this->_addExtra('WITHBUZZWORDS',2);
  }

  function setWithoutBuzzwords() {
    $this->_addExtra('WITHBUZZWORDS',1);
  }

  function withBuzzwords() {
    $retour = false;
    if ($this->_issetExtra('WITHBUZZWORDS') ) {
      $re = $this->_getExtra('WITHBUZZWORDS');
      if ($re == 2) {
        $retour = true;
      }
    }else {
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


    public function isAssociationShowExpanded()
    {
        if ($this->_issetExtra('ASSOCIATIONSHOWEXPANDED')) {
            $value = $this->_getExtra('ASSOCIATIONSHOWEXPANDED');
            if ($value == 1) {
                return true;
            }
        }

        return false;
    }

    public function setAssociationShowExpanded()
    {
        $this->_addExtra('ASSOCIATIONSHOWEXPANDED', 1);
    }

    public function unsetAssociationShowExpanded()
    {
        $this->_addExtra('ASSOCIATIONSHOWEXPANDED', 0);
    }


  function isBuzzwordShowExpanded () {
    $retour = true;
    if ( $this->_issetExtra('BUZZWORDSHOWEXPANDED') ) {
      $value = $this->_getExtra('BUZZWORDSHOWEXPANDED');
      if ($value == 0) {
        $retour = false;
      }
    }
    return $retour;
  }

  function setBuzzwordShowExpanded () {
    $this->_addExtra('BUZZWORDSHOWEXPANDED',1);
  }

  function unsetBuzzwordShowExpanded () {
    $this->_addExtra('BUZZWORDSHOWEXPANDED',0);
  }



  function setWithWorkflow() {
    $this->_addExtra('WITHWORKFLOW',2);
  }

  function setWithoutWorkflow() {
    $this->_addExtra('WITHWORKFLOW',1);
  }

  function withWorkflow() {
    $retour = false;
    if ($this->_issetExtra('WITHWORKFLOW') ) {
      $re = $this->_getExtra('WITHWORKFLOW');
      if ($re == 2) {
        $retour = true;
      }
    }
    #else {
    #  $retour = true;
    #}
    return $retour;
  }


  function setWithWorkflowTrafficLight() {
    $this->_addExtra('WITHWORKFLOWTRAFFICLIGHT',2);
  }

  function setWithoutWorkflowTrafficLight() {
    $this->_addExtra('WITHWORKFLOWTRAFFICLIGHT',1);
  }

  function withWorkflowTrafficLight() {
    $retour = false;
    if ($this->_issetExtra('WITHWORKFLOWTRAFFICLIGHT') ) {
      $re = $this->_getExtra('WITHWORKFLOWTRAFFICLIGHT');
      if ($re == 2) {
        $retour = true;
      }
    }
    return $retour;
  }

  function setWorkflowTrafficLightDefault($value) {
    $this->_addExtra('WORKFLOWTRAFFICLIGHTDEFAULT',$value);
  }

  function getWorkflowTrafficLightDefault() {
    $retour = '3_none';
    if ($this->_issetExtra('WORKFLOWTRAFFICLIGHTDEFAULT') ) {
      $retour = $this->_getExtra('WORKFLOWTRAFFICLIGHTDEFAULT');
    }
    return $retour;
  }

  function setWorkflowTrafficLightTextGreen($value) {
    $this->_addExtra('WORKFLOWTRAFFICLIGHTTEXTGREEN',$value);
  }

  function getWorkflowTrafficLightTextGreen() {
    $retour = '';
    if ($this->_issetExtra('WORKFLOWTRAFFICLIGHTTEXTGREEN') ) {
      $retour = $this->_getExtra('WORKFLOWTRAFFICLIGHTTEXTGREEN');
    }
    return $retour;
  }

  function setWorkflowTrafficLightTextYellow($value) {
    $this->_addExtra('WORKFLOWTRAFFICLIGHTTEXTYELLOW',$value);
  }

  function getWorkflowTrafficLightTextYellow() {
    $retour = '';
    if ($this->_issetExtra('WORKFLOWTRAFFICLIGHTTEXTYELLOW') ) {
      $retour = $this->_getExtra('WORKFLOWTRAFFICLIGHTTEXTYELLOW');
    }
    return $retour;
  }

  function setWorkflowTrafficLightTextRed($value) {
    $this->_addExtra('WORKFLOWTRAFFICLIGHTTEXTRED',$value);
  }

  function getWorkflowTrafficLightTextRed() {
    $retour = '';
    if ($this->_issetExtra('WORKFLOWTRAFFICLIGHTTEXTRED') ) {
      $retour = $this->_getExtra('WORKFLOWTRAFFICLIGHTTEXTRED');
    }
    return $retour;
  }


  function setWithWorkflowResubmission() {
    $this->_addExtra('WITHWORKFLOWRESUBMISSION',2);
  }

  function setWithoutWorkflowResubmission() {
    $this->_addExtra('WITHWORKFLOWRESUBMISSION',1);
  }

  function withWorkflowResubmission() {
    $retour = false;
    if ($this->_issetExtra('WITHWORKFLOWRESUBMISSION') ) {
      $re = $this->_getExtra('WITHWORKFLOWRESUBMISSION');
      if ($re == 2) {
        $retour = true;
      }
    }
    #else {
    #  $retour = true;
    #}
    return $retour;
  }


  function setWithWorkflowReader() {
    $this->_addExtra('WITHWORKFLOWREADER',2);
  }

  function setWithoutWorkflowReader() {
    $this->_addExtra('WITHWORKFLOWREADER',1);
  }

  function withWorkflowReader() {
    $retour = false;
    if ($this->_issetExtra('WITHWORKFLOWREADER') ) {
      $re = $this->_getExtra('WITHWORKFLOWREADER');
      if ($re == 2) {
        $retour = true;
      }
    }
    #else {
    #  $retour = true;
    #}
    return $retour;
  }

  function setWithWorkflowReaderGroup() {
    $this->_addExtra('WORKFLOWREADERGROUP','1');
  }

  function setWithoutWorkflowReaderGroup() {
    $this->_addExtra('WORKFLOWREADERGROUP','0');
  }

  function getWorkflowReaderGroup() {
    $retour = '0';
    if ($this->_issetExtra('WORKFLOWREADERGROUP') ) {
      $retour = $this->_getExtra('WORKFLOWREADERGROUP');
    }
    return $retour;
  }

  function setWithWorkflowReaderPerson() {
    $this->_addExtra('WORKFLOWREADERPERSON','1');
  }

  function setWithoutWorkflowReaderPerson() {
    $this->_addExtra('WORKFLOWREADERPERSON','0');
  }

  function getWorkflowReaderPerson() {
    $retour = '0';
    if ($this->_issetExtra('WORKFLOWREADERPERSON') ) {
      $retour = $this->_getExtra('WORKFLOWREADERPERSON');
    }
    return $retour;
  }

  function setWorkflowReaderShowTo($value) {
    $this->_addExtra('WORKFLOWREADERSHOWTO',$value);
  }

  function getWorkflowReaderShowTo() {
    $retour = 'moderator';
    if ($this->_issetExtra('WORKFLOWREADERSHOWTO') ) {
      $retour = $this->_getExtra('WORKFLOWREADERSHOWTO');
    }
    return $retour;
  }

  function setWithWorkflowValidity() {
    $this->_addExtra('WITHWORKFLOWVALIDITY',2);
  }

  function setWithoutWorkflowValidity() {
    $this->_addExtra('WITHWORKFLOWVALIDITY',1);
  }

  function withWorkflowValidity() {
    $retour = false;
    if ($this->_issetExtra('WITHWORKFLOWVALIDITY') ) {
      $re = $this->_getExtra('WITHWORKFLOWVALIDITY');
      if ($re == 2) {
        $retour = true;
      }
    }
    #else {
    #  $retour = true;
    #}
    return $retour;
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
    public function getHtmlTextAreaStatus()
    {
        return 3;
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

    // new private room
    if ( $this->isPrivateRoom()
            and $retour == 'normal'
    ) {
      $retour = 'calendar_month';
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
/*DB-Optimierung vom 23.10.2010*/
       $retour = $user_manager->isUserInContext($user_id, $this->getItemID(), $auth_source);

       // archive
       if ( !$retour
            and ( $this->isProjectRoom()
                  or $this->isCommunityRoom()
                  or $this->isGroupRoom()
                  or $this->isUserroom()
                )
            and $this->isClosed()
            and !$this->_environment->isArchiveMode()
          ) {
       	 $zzz_user_manager = $this->_environment->getZzzUserManager();
          $retour = $zzz_user_manager->isUserInContext($user_id, $this->getItemID(), $auth_source);
       }
       // archive
       
       if ($retour) {
          $this->_cache_may_enter[$user_id.'_'.$auth_source] = true;
       } else {
          $this->_cache_may_enter[$user_id.'_'.$auth_source] = false;
       }
/*     $user_manager->resetLimits();
       $user_manager->setContextLimit($this->getItemID());
       $user_manager->setUserIDLimit($user_id);
       $user_manager->setAuthSourceLimit($auth_source);
       $user_manager->select();
       $user_list = $user_manager->get();
       $user_manager = $this->_environment->getUserManager();
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
       unset($user_manager);*/
/*DB-Optimierung vom 23.10.2010*/

    }
    return $retour;
  }


   function isSystemLabel () {
      $retour = false;
      if ( $this->_issetExtra('SYSTEM_LABEL')) {
         $value = $this->_getExtra('SYSTEM_LABEL');
         if ( $value == 1 ) {
            $retour = true;
         }
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
      if ( $user_in_room->isUser()
           and $user_in_room->getContextID() == $this->getItemID()
         ) {
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

  function getColorArray() {
    $retour = $this->_default_colors;
    if ($this->_issetExtra('COLOR')) {
      $retour = $this->_getExtra('COLOR');
      $retour_temp = array();
      if ( is_array($retour) ) {
        foreach ($retour as $key => $entry) {
          $retour_temp[mb_strtolower($key, 'UTF-8')]= $entry;
        }
      }
      $retour = $retour_temp;
    }

    return $retour;
  }

  function setColorArray($array) {
    if (is_array($array)) {
      $this->_addExtra('COLOR',$array);
    }
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
  /**
   * Return value for Room asociation
   * @return mixed|string|void
   */
  function _getRoomAssociation () {
    $retour = '';
    if ($this->_issetExtra('ROOMASSOCIATION')) {
      $retour = $this->_getExtra('ROOMASSOCIATION');
    }
    return $retour;
  }

  /*
   * set value to room asociation
   */
  public function _setRoomAssociation ( $value ) {
    $this->_addExtra('ROOMASSOCIATION',$value);
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

  function setEmailTextArray ($array) {
    if ( !empty($array) ) {
      $this->_addExtra('MAIL_TEXT_ARRAY',$array);
    }
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
      if ($rubric == CS_PROJECT_TYPE) {
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
      }elseif ($rubric == CS_COMMUNITY_TYPE) {
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
   * @return \cs_list a list of moderator (cs_user_item)
   */
  public function getModeratorList()
  {
    if (empty($this->_moderator_list)) {
      $userManager = $this->_environment->getUserManager();
      $userManager->resetLimits();
      $userManager->setContextLimit($this->getItemID());
      $userManager->setModeratorLimit();
      $userManager->select();
      $this->_moderator_list = $userManager->get();
      unset($userManager);

      if ($this->_moderator_list->isEmpty()) {
        if ($this->isClosed() && !$this->_environment->isArchiveMode()) {
          $userManager = $this->_environment->getZzzUserManager();
          $userManager->resetLimits();
          $userManager->setContextLimit($this->getItemID());
          $userManager->setModeratorLimit();
          $userManager->select();
          $this->_moderator_list = $userManager->get();
          unset($userManager);
        }
      }
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
    $retour = $this->_disablePlugins($retour);
    if ( empty($retour) ) {
      $retour = $this->getDefaultHomeConf();
      $this->setHomeConf($retour);
    }
    return $retour;
  }

  function _disablePlugins ( $home_conf ) {
    $home_conf_array = explode(',',$home_conf);
    $current_portal_item = $this->_environment->getCurrentPortalItem();
    if ( !empty($current_portal_item) ) {
      global $c_plugin_array;
      $unset_key_array = array();
      foreach ( $home_conf_array as $key => $rubric_conf ) {
        $plugin = substr($rubric_conf,0,strpos($rubric_conf,'_'));
        if (in_array($plugin,$this->_plugin_rubrics_array)) {
          if ( !in_array($plugin,$c_plugin_array)
                  or !$current_portal_item->isPluginOn($plugin)
          ) {
            $unset_key_array[] = $key;
          }
        }
      }
      if ( !empty($unset_key_array) ) {
        foreach ( $unset_key_array as $key ) {
          unset($home_conf_array[$key]);
        }
        $home_conf = implode(',',$home_conf_array);
      }
    }
    return $home_conf;
  }

  function _changeContactInUser($rubricsString) {
    $change_needed = false;
    if (mb_stristr($rubricsString, 'contact_tiny')) {
      $rubricsString = str_replace('contact_tiny','user_tiny', $rubricsString);
      $change_needed = true;
    }
    if (mb_stristr($rubricsString, 'contact_short')) {
      $rubricsString = str_replace('contact_short','user_short', $rubricsString);
      $change_needed = true;
    }
    if (mb_stristr($rubricsString, 'contact_none')) {
      $rubricsString = str_replace('contact_none','user_none',  $rubricsString);
      $change_needed = true;
    }
    if ($change_needed) {
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
      if (mb_stristr($rubricsString, $rubric)) {
        if (!$this->showExtraRubric($rubric)) {
          if (mb_stristr($rubricsString, $rubric.'_tiny')) {
            $rubricsString = str_replace($rubric.'_tiny', '', $rubricsString);
          }
          if (mb_stristr($rubricsString, $rubric.'_short')) {
            $rubricsString = str_replace($rubric.'_short', '', $rubricsString);
          }
          if (mb_stristr($rubricsString, $rubric.'_none')) {
            $rubricsString = str_replace($rubric.'_none', '', $rubricsString);
          }

          // clear string from ","
          if ($rubricsString[0] == ',') {
            $rubricsString = mb_substr($rubricsString,1);
          }
          if ($rubricsString[mb_strlen($rubricsString)-1] == ',') {
            $rubricsString = mb_substr($rubricsString,0,mb_strlen($rubricsString)-1);
          }
          $rubricsString = str_replace(',,',',',$rubricsString);
        }
      }
    }

    // if a plugin is deleted, or a rubric configuration is faulty, remove it from HomeConf
      if ( !empty($rubricsString) ) {
        $retour = array();
        $rubric_array = explode(',', $rubricsString);
        foreach ($rubric_array as $rubric) {
          if (strpos($rubric, '_') === false) {
            continue;
          }
          list($rubricType, $rubricConf) = explode('_', $rubric);
          if (!empty($rubricType) && !empty($rubricConf) &&
            in_array($rubricType, $this->_default_rubrics_array)) {
            $retour[] = $rubricType . '_' . $rubricConf;
          }
        }

        $rubricsString = "";
        if (!empty($retour)) {
            $rubricsString = implode(',', $retour);
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
        if (!empty($module)) {
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
      if ($this->withTags()) {
        $in_array = false;
        foreach($this->_current_detailbox_array as $entry) {
          if ($entry == 'detailtags') {
            $in_array = true;
          }
        }
        if ( !$in_array ) {
          $this->_current_detailbox_array[] = 'detailtags';
          $this->_current_detailbox_conf_array['detailtags'] = 'tiny';
        }
      }
      if ($this->withBuzzwords()) {
        $in_array = false;
        foreach($this->_current_detailbox_array as $entry) {
          if ($entry == 'detailbuzzwords') {
            $in_array = true;
          }
        }
        if ( !$in_array ) {
          $this->_current_detailbox_array[] = 'detailbuzzwords';
          $this->_current_detailbox_conf_array['detailbuzzwords'] = 'tiny';
        }
      }
    }
    return $this->_current_detailbox_array;
  }

  function withIMSContentConnection() {
    global $with_ims_content_connection;
    if (isset($with_ims_content_connection)) {
      return $with_ims_content_connection;
    }else {
      return false;
    }
  }


  function getAvailableListBoxes() {
    $current_list_boxes = $this->getListBoxConf();
    if (!empty($current_list_boxes)) {
      $tokens = explode(',', $current_list_boxes);
      $pointer = 0;
      foreach ($tokens as $module) {
        if (!empty($module)) {
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
      if ($this->withTags()) {
        $in_array = false;
        foreach($this->_current_listbox_array as $entry) {
          if ($entry == 'tags') {
            $in_array = true;
          }
        }
        if ( !$in_array ) {
          $this->_current_listbox_array[] = 'tags';
          $this->_current_listbox_conf_array['tags'] = 'tiny';
        }
      }
      if ($this->withBuzzwords()) {
        $in_array = false;
        foreach($this->_current_listbox_array as $entry) {
          if ($entry == 'buzzwords') {
            $in_array = true;
          }
        }
        if ( !$in_array ) {
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
      if (isset($this->_default_listbox_conf_array[$box])) {
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
      if (isset($this->_default_detailbox_conf_array[$box])) {
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

  function clearUnallowedBoxes($boxesString) {
    if( !$this->withTags() ) {
      if (mb_stristr($boxesString, 'tags_tiny')) {
        $boxesString = str_replace('tags_tiny', '', $boxesString);
      }
      if (mb_stristr($boxesString, 'tags_short')) {
        $boxesString = str_replace('tags_short', '', $boxesString);
      }
      if (mb_stristr($boxesString, 'tags_none')) {
        $boxesString = str_replace('tags_none', '', $boxesString);
      }
    }
    if( !$this->withBuzzwords() ) {
      if (mb_stristr($boxesString, 'buzzwords_tiny')) {
        $boxesString = str_replace('buzzwords_tiny', '', $boxesString);
      }
      if (mb_stristr($boxesString, 'buzzwords_short')) {
        $boxesString = str_replace('buzzwords_short', '', $boxesString);
      }
      if (mb_stristr($boxesString, 'buzzwords_none')) {
        $boxesString = str_replace('buzzwords_none', '', $boxesString);
      }
    }
    // clear string from ","
    if ($boxesString[0] == ',') {
      $boxesString = mb_substr($boxesString,1);
    }
    if ($boxesString[mb_strlen($boxesString)-1] == ',') {
      $boxesString = mb_substr($boxesString,0,mb_strlen($boxesString)-1);
    }
    $boxesString = str_replace(',,',',',$boxesString);
    return $boxesString;
  }

  function clearUnallowedDetailBoxes($boxesString) {
    if( !$this->withTags() ) {
      if (mb_stristr($boxesString, 'detailtags_tiny')) {
        $boxesString = str_replace('detailtags_tiny', '', $boxesString);
      }
      if (mb_stristr($boxesString, 'detailtags_short')) {
        $boxesString = str_replace('detailtags_short', '', $boxesString);
      }
      if (mb_stristr($boxesString, 'detailtags_none')) {
        $boxesString = str_replace('detailtags_none', '', $boxesString);
      }
    }
    if( !$this->withBuzzwords() ) {
      if (mb_stristr($boxesString, 'detailbuzzwords_tiny')) {
        $boxesString = str_replace('detailbuzzwords_tiny', '', $boxesString);
      }
      if (mb_stristr($boxesString, 'detailbuzzwords_short')) {
        $boxesString = str_replace('detailbuzzwords_short', '', $boxesString);
      }
      if (mb_stristr($boxesString, 'detailbuzzwords_none')) {
        $boxesString = str_replace('detailbuzzwords_none', '', $boxesString);
      }
    }
    // clear string from ","
    if ($boxesString[0] == ',') {
      $boxesString = mb_substr($boxesString,1);
    }
    if ($boxesString[mb_strlen($boxesString)-1] == ',') {
      $boxesString = mb_substr($boxesString,0,mb_strlen($boxesString)-1);
    }
    $boxesString = str_replace(',,',',',$boxesString);
    return $boxesString;
  }

  function getListLength() {
    $retour = CS_LIST_INTERVAL;
    if ( $this->_issetExtra('LISTLENGTH') ) {
      $retour = $this->_getExtra('LISTLENGTH');
    }
    if(empty($retour)){
    	$retour = CS_LIST_INTERVAL;
    }
    return $retour;
  }

  function setListLength($value) {
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
      if (isset($this->_default_home_conf_array[$rubric])) {
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
  # plugin configuration
  ############## BEGIN #####################

  /** get part of the plugin config array, INTERNAL
   *
   * @param string part: identifier of the plugin
   *                     whole for the whole array
   *
   * @return int 1 = true / -1 = false
   */
  function _getPluginConfig ($identifier) {
    if ( $identifier == 'whole' ) {
      $retour = array();
    } else {
      $retour = '';
    }
    if ( $this->_issetExtra('PLUGIN_CONFIG') ) {
      $plugin_config_array = $this->_getExtra('PLUGIN_CONFIG');
      if ( $identifier == 'whole' ) {
        $retour = $plugin_config_array;
      } elseif ( isset($plugin_config_array[mb_strtoupper($identifier, 'UTF-8')]) ) {
        $retour = $plugin_config_array[mb_strtoupper($identifier, 'UTF-8')];
      }
    }
    return $retour;
  }

  /** set part of the plugin config array, INTERNAL
   *
   * @param string part: identifier of the plugin
   *                     whole for the whole array
   * @param array
   */
  function _setPluginConfig ($identifier, $value) {
    if ($identifier == 'whole') {
      $this->_addExtra('PLUGIN_CONFIG',$value);
    } else {
      $plugin_config_array = $this->_getPluginConfig('whole');
      $plugin_config_array[mb_strtoupper($identifier, 'UTF-8')] = (int)$value;
      $this->_setPluginConfig('whole',$plugin_config_array);
    }
  }

  function getPluginConfig () {
    return $this->_getPluginConfig('whole');
  }

  function setPluginConfig ($value) {
    $this->_setPluginConfig('whole',$value);
  }

  /** is Plugin on / active
   *
   * @param string identifier of the plugin
   *
   * @return boolean true or false
   */
  function isPluginOn ($identifier) {
    $retour = false;
    if ( is_object($identifier) ) {
      $identifier = $identifier->getIdentifier();
    }
    $plugin_config = $this->_getPluginConfig($identifier);
    if ($plugin_config == 1) {
      $retour = true;
      global $c_plugin_array;
      if ( !in_array($identifier,$c_plugin_array) ) {
        $retour = false;
      }
    }
    return $retour;
  }

  /** set Plugin on
   *
   * @param string identifier of the plugin
   */
  function setPluginOn ($identifier) {
    $this->_setPluginConfig($identifier,1);
  }

  /** set Plugin off
   *
   * @param string identifier of the plugin
   */
  function setPluginOff ($identifier) {
    $this->_setPluginConfig($identifier,-1);
  }

  /** get part of the plugin config array, INTERNAL
   *
   * @param string type: PLUGIN for the plugin
   *                     whole for the whole array
   *
   * @return string the configuration
   */
  public function getPluginConfigForPlugin ($type) {
    if ( $type == 'whole' ) {
      $retour = array();
    } else {
      $retour = '';
    }
    if ( $this->_issetExtra('PLUGIN_CONFIG_DATA') ) {
      $config_array = $this->_getExtra('PLUGIN_CONFIG_DATA');
      if ( $type == 'whole' ) {
        $retour = $config_array;
      } elseif ( isset($config_array[mb_strtoupper($type, 'UTF-8')]) ) {
        $retour = $config_array[mb_strtoupper($type, 'UTF-8')];
      }
    }
    return $retour;
  }

  /** set part of the plugin config array, INTERNAL
   *
   * @param string part: PLUGIN for the plugin
   *                     whole for the whole array
   * @param array or string value the configuration
   */
  public function setPluginConfigForPlugin ($type, $value) {
    if ($type == 'whole') {
      $this->_addExtra('PLUGIN_CONFIG_DATA',$value);
    } else {
      $config_array = $this->getPluginConfigForPlugin('whole');
      $config_array[mb_strtoupper($type, 'UTF-8')] = $value;
      $this->setPluginConfigForPlugin('whole',$config_array);
    }
  }

  public function getPluginConfigData () {
    return $this->getPluginConfigForPlugin('whole');
  }

  public function setPluginConfigData ($value) {
    $this->setPluginConfigForPlugin('whole',$value);
  }

  ############### END ######################
  # plugin configuration
  ##########################################

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
      } elseif ( isset($extra_config_array[mb_strtoupper($type, 'UTF-8')]) ) {
        $retour = $extra_config_array[mb_strtoupper($type, 'UTF-8')];
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
      $extra_config_array[mb_strtoupper($type, 'UTF-8')] = $value;
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
  # log-archive flag
  ##########################################

  function withLogArchive () {
    $retour = false;
    $value = $this->_getExtraConfig('LOGARCHIVE');
    if ($value == 1) {
      $retour = true;
    }
    return $retour;
  }
  
  ##########################################
  # log-ip flag
  ##########################################
  
  function withLogIPCover () {
  	$retour = false;
  	$value = $this->_getExtraConfig('LOGIPCOVER');
  	if ($value == 1) {
  		$retour = true;
  	}
  	return $retour;
  	
  }
  
  function setWithLogIPCover () {
  	$this->_setExtraConfig('LOGIPCOVER', 1);
  }
  
  function setWithoutLogIPCover () {
  	$this->_setExtraConfig('LOGIPCOVER', -1);
  }

  ##########################################
  # assessment flag
  ##########################################

  function setAssessmentActive() {
  	$this->_addExtra('ASSESSMENT', (int) 1);
  }

  function setAssessmentInactive() {
  	$this->_addExtra('ASSESSMENT', (int) -1);
  }

  function isAssessmentActive() {
  	$retour = false;
    if ( $this->_issetExtra('ASSESSMENT') ) {
      $active = $this->_getExtra('ASSESSMENT');
      if ( $active == 1 ) {
        $retour = true;
      }
    }
    return $retour;
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
//    return $retour;
	return true;
  }

  function setWithGrouproomFunctions () {
    $this->_setExtraConfig('GROUPROOM',1);
  }

  function setWithoutGrouproomFunctions () {
    $this->_setExtraConfig('GROUPROOM',-1);
  }

  function showGrouproomConfig () {
    $retour = false;
    if ( $this->withGrouproomFunctions() ) {
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
    /*
    $retour = false;
    if ( $this->_issetExtra('GROUPROOM') ) {
      $active = $this->_getExtra('GROUPROOM');
      if ( $active == 1 ) {
        $retour = true;
      }
    }
    */
    $retour = true;
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

  /**
   *  set external service link
   */

  function setServiceLinkExternal($email) {
    $this->_addExtra('SERVICELINKEXTERNAL',(string)$email);
  }

  /**
   *  get external service link
   */

  function getServiceLinkExternal() {
    return $this->_getExtra('SERVICELINKEXTERNAL');
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



  function getExtraToDoStatusArray() {
    $retour = array();
    if ( $this->_issetExtra('TODOEXTRASTATUSARRAY') ) {
      $retour = $this->_getExtra('TODOEXTRASTATUSARRAY');
    }
    return $retour;
  }

  function setExtraToDoStatusArray($array) {
    if(!$this->_issetExtra('TODOEXTRASTATUSARRAY')) {
      $this->_addExtra('TODOEXTRASTATUSARRAY',$array);
    } else {
      $this->_setExtra('TODOEXTRASTATUSARRAY',$array);
    }
    return ;
  }

  function setTemplateAvailability($value) {
    if(!$this->_issetExtra('TEMPLATE_AVAILABILITY')) {
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
    if(!$this->_issetExtra('TEMPLATE_COMMUNITY_AVAILABILITY')) {
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

  # Wordpress

  function setWordpressId($id) {
    $this->_setExtra('WORDPRESSID',$id);
  }

  function getWordpressId(){
    return ( $this->_issetExtra('WORDPRESSID') ) ? $this->_getExtra('WORDPRESSID') : 0;
  }

  function withWordpressFunctions () {
    $portal_item = $this->_environment->getCurrentPortalItem();
    if(!empty($portal_item)) {
    	$wordpress = $portal_item->getWordpressPortalActive();
    }
    if ( !isset($wordpress) or !$wordpress ) {
      return false;
    }
    $retour = false;
    $value = $this->_getExtraConfig('WORDPRESS');
    if ($value == 1) {
      $retour = true;
    } elseif ( $this->isProjectRoom()
    or $this->isCommunityRoom()
    or $this->isGroupRoom()
    or $this->isPrivateRoom()
    ) {
      $portal_room = $this->getContextItem();
      if ( $portal_room->withWordpressFunctions() ) {
        $retour = true;
      }
    }
    return $retour;
  }

  function setWithWordpressFunctions () {
    $this->_setExtraConfig('WORDPRESS',1);
  }

  function setWithoutWordpressFunctions () {
    $this->_setExtraConfig('WORDPRESS',0);
  }

  function showWordpressLink () {
    $retour = false;
    if ($this->withWordpressFunctions() and $this->isWordpressActive()) {
      $retour = true;
    }
    return $retour;
  }

  /** is wordpress link active ?
   * can be switched at room configuration
   *
   * true = wordpress link is active
   * false = wordpress link is not active, default
   *
   * @return boolean
   */
  function isWordpressActive () {
    $retour = false;
    $active = $this->_getExtra('WORDPRESSLINK');
    if ($active == 1) {
      $retour = true;
      $retour = $retour and $this->withWordpressFunctions();
    }
    return $retour;
  }

  /** set activity of the wordpress link, INTERNAL
   *
   * @param integer value: -1 = not
   *                        1 = yes
   */
  function _setWordpressActivity ($value) {
    $this->_addExtra('WORDPRESSLINK',(int)$value);
  }

  /** set wordpress link active
   */
  function setWordpressActive () {
    $this->_setWordpressActivity(1);
  }

  /** set wordpress link inactive
   */
  function setWordpressInactive () {
    $this->_setWordpressActivity(-1);
  }

  function setWordpressUseComments(){
    $this->_addExtra('WORDPRESSUSECOMMENTS','1');
  }

  function getWordpressUseComments(){
    if ( $this->_issetExtra('WORDPRESSUSECOMMENTS') ) {
      $retour = $this->_getExtra('WORDPRESSUSECOMMENTS');
    } else {
      $retour = '1';
    }
    return $retour;
  }

  function unsetWordpressUseComments(){
    $this->_addExtra('WORDPRESSUSECOMMENTS','-1');
  }

  function setWordpressUseCommentsModeration(){
    $this->_addExtra('WORDPRESSUSECOMMENTSMODERATION','1');
  }

  function getWordpressUseCommentsModeration(){
    if ( $this->_issetExtra('WORDPRESSUSECOMMENTSMODERATION') ) {
      $retour = $this->_getExtra('WORDPRESSUSECOMMENTSMODERATION');
    } else {
      $retour = '1';
    }
    return $retour;
  }

  function unsetWordpressUseCommentsModeration(){
    $this->_addExtra('WORDPRESSUSECOMMENTSMODERATION','-1');
  }

  function setWordpressUseCalendar(){
    $this->_addExtra('WORDPRESSUSECALENDAR','1');
  }

  function getWordpressUseCalendar(){
    if ( $this->_issetExtra('WORDPRESSUSECALENDAR') ) {
      $retour = $this->_getExtra('WORDPRESSUSECALENDAR');
    } else {
      $retour = '1';
    }
    return $retour;
  }

  function unsetWordpressUseCalendar(){
    $this->_addExtra('WORDPRESSUSECALENDAR','-1');
  }

  function setWordpressUseTagCloud(){
    $this->_addExtra('WORDPRESSUSETAGCLOUD','1');
  }

  function getWordpressUseTagCloud(){
    if ( $this->_issetExtra('WORDPRESSUSETAGCLOUD') ) {
      $retour = $this->_getExtra('WORDPRESSUSETAGCLOUD');
    } else {
      $retour = '1';
    }
    return $retour;
  }

  function unsetWordpressUseTagCloud(){
    $this->_addExtra('WORDPRESSUSETAGCLOUD','-1');
  }

  function setWordpressMemberRole($role='subscriber'){
    $this->_addExtra('WORDPRESSMEMBERROLE',$role);
  }

  function getWordpressMemberRole(){
    if ( $this->_issetExtra('WORDPRESSMEMBERROLE') ) {
      $retour = $this->_getExtra('WORDPRESSMEMBERROLE');
    } else {
      $retour = 'subscriber';
    }
    return $retour;
  }

  function setWordpressHomeLink(){
    $this->_addExtra('WORDPRESSHOMELINK','1');
  }

  function getWordpressHomeLink(){
    if ( $this->_issetExtra('WORDPRESSHOMELINK') ) {
      $retour = $this->_getExtra('WORDPRESSHOMELINK');
    } else {
      $retour = '1';
    }
    return $retour;
  }

  function unsetWordpressHomeLink(){
    $this->_addExtra('WORDPRESSHOMELINK','-1');
  }

  function issetWordpressHomeLink(){
    if ( $this->_issetExtra('WORDPRESSHOMELINK') ) {
      $retour = $this->_getExtra('WORDPRESSHOMELINK');
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

  function setWordpressPortalLink(){
    $this->_addExtra('WORDPRESSPORTALLINK','1');
  }

  function getWordpressPortalLink(){
    if ( $this->_issetExtra('WORDPRESSPORTALLINK') ) {
      $retour = $this->_getExtra('WORDPRESSPORTALLINK');
    }else{
      $retour = '-1';
    }
    return $retour;
  }
  function unsetWordpressPortalLink(){
    $this->_addExtra('WORDPRESSPORTALLINK','-1');
  }

  function issetWordpressPortalLink(){
    if ( $this->_issetExtra('WORDPRESSPORTALLINK') ) {
      $retour = $this->_getExtra('WORDPRESSPORTALLINK');
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

  function setWordpressExists () {
    $this->_addExtra('WORDPRESSEXISTS','1');
  }

  function unsetWordpressExists () {
    $this->_addExtra('WORDPRESSEXISTS','-1');
  }

  function existWordpress () {
    if ( $this->_issetExtra('WORDPRESSEXISTS') ) {
      $retour = $this->_getExtra('WORDPRESSEXISTS');
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

  function setWordpressSkin($skin){
    $this->_addExtra('WORDPRESSSKIN',$skin);
  }

  function getWordpressSkin(){
    if ( $this->_issetExtra('WORDPRESSSKIN') ) {
      $retour = $this->_getExtra('WORDPRESSSKIN');
    } else {
      $retour ='twentyten';
    }
    return $retour;
  }

  function setWordpressTitle($title){
    $this->_addExtra('WORDPRESSTITLE',$title);
  }

  function getWordpressTitle(){
    if ( $this->_issetExtra('WORDPRESSTITLE') ) {
      $retour = $this->_getExtra('WORDPRESSTITLE');
    } else {
      if ($this->isPrivateRoom()){
        $translator = $this->_environment->getTranslationObject();
        $retour = $translator->getMessage('COMMON_PRIVATE_ROOM');
      }else{
        $retour = $this->getTitle();
      }
    }
    return $retour;
  }

  function setWordpressDescription($title){
    $this->_addExtra('WORDPRESSDESCRIPTION',$title);
  }

  function getWordpressDescription(){
    if ( $this->_issetExtra('WORDPRESSDESCRIPTION') ) {
      $retour = $this->_getExtra('WORDPRESSDESCRIPTION');
    } else {
      $retour = '';
    }
    return $retour;
  }

//  function setWordpressAdminPW($pw){
//    $this->_addExtra('WORDPRESSADMINPW',$pw);
//  }
//
//  function getWordpressAdminPW(){
//    if ( $this->_issetExtra('WORDPRESSADMINPW') ) {
//      $retour = $this->_getExtra('WORDPRESSADMINPW');
//    } else {
//      $retour = 'admin';
//    }
//    return $retour;
//  }
//
//
//  function setWordpressShowCommSyLogin(){
//    $this->_addExtra('WORDPRESSSHOWLOGIN','1');
//  }
//
//  function unsetWordpressShowCommSyLogin(){
//    $this->_addExtra('WORDPRESSSHOWLOGIN','-1');
//  }
//
//  function WordpressShowCommSyLogin(){
//    if ( $this->_issetExtra('WORDPRESSSHOWLOGIN') ) {
//      $retour = $this->_getExtra('WORDPRESSSHOWLOGIN');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  //  new features
//  function setWordpressEnableFCKEditor(){
//    $this->_addExtra('WORDPRESSENABLEFCKEDITOR','1');
//  }
//
//  function unsetWordpressEnableFCKEditor(){
//    $this->_addExtra('WORDPRESSENABLEFCKEDITOR','-1');
//  }
//
//  function WordpressEnableFCKEditor(){
//    if ( $this->_issetExtra('WORDPRESSENABLEFCKEDITOR') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLEFCKEDITOR');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableSitemap(){
//    $this->_addExtra('WORDPRESSENABLESITEMAP','1');
//  }
//
//  function unsetWordpressEnableSitemap(){
//    $this->_addExtra('WORDPRESSENABLESITEMAP','-1');
//  }
//
//  function WordpressEnableSitemap(){
//    if ( $this->_issetExtra('WORDPRESSENABLESITEMAP') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLESITEMAP');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableStatistic(){
//    $this->_addExtra('WORDPRESSENABLESTATISTIC','1');
//  }
//
//  function unsetWordpressEnableStatistic(){
//    $this->_addExtra('WORDPRESSENABLESTATISTIC','-1');
//  }
//
//  function WordpressEnableStatistic(){
//    if ( $this->_issetExtra('WORDPRESSENABLESTATISTIC') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLESTATISTIC');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableSearch(){
//    $this->_addExtra('WORDPRESSENABLESEARCH','1');
//  }
//
//  function unsetWordpressEnableSearch(){
//    $this->_addExtra('WORDPRESSENABLESEARCH','-1');
//  }
//
//  function WordpressEnableSearch(){
//    if ( $this->_issetExtra('WORDPRESSENABLESEARCH') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLESEARCH');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableRss(){
//    $this->_addExtra('WORDPRESSENABLERSS','1');
//  }
//
//  function unsetWordpressEnableRss(){
//    $this->_addExtra('WORDPRESSENABLERSS','-1');
//  }
//
//  function WordpressEnableRss(){
//    if ( $this->_issetExtra('WORDPRESSENABLERSS') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLERSS');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableCalendar(){
//    $this->_addExtra('WORDPRESSENABLECALENDAR','1');
//  }
//
//  function unsetWordpressEnableCalendar(){
//    $this->_addExtra('WORDPRESSENABLECALENDAR','-1');
//  }
//
//  function WordpressEnableCalendar(){
//    if ( $this->_issetExtra('WORDPRESSENABLECALENDAR') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLECALENDAR');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableGallery(){
//    $this->_addExtra('WORDPRESSENABLEGALLERY','1');
//  }
//
//  function unsetWordpressEnableGallery(){
//    $this->_addExtra('WORDPRESSENABLEGALLERY','-1');
//  }
//
//  function WordpressEnableGallery(){
//    if ( $this->_issetExtra('WORDPRESSENABLEGALLERY') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLEGALLERY');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableNotice(){
//    $this->_addExtra('WORDPRESSENABLENOTICE','1');
//  }
//
//  function unsetWordpressEnableNotice(){
//    $this->_addExtra('WORDPRESSENABLENOTICE','-1');
//  }
//
//  function WordpressEnableNotice(){
//    if ( $this->_issetExtra('WORDPRESSENABLENOTICE') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLENOTICE');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnablePdf(){
//    $this->_addExtra('WORDPRESSENABLEPDF','1');
//  }
//
//  function unsetWordpressEnablePdf(){
//    $this->_addExtra('WORDPRESSENABLEPDF','-1');
//  }
//
//
//  function WordpressEnablePdf(){
//    if ( $this->_issetExtra('WORDPRESSENABLEPDF') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLEPDF');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableRater(){
//    $this->_addExtra('WORDPRESSENABLERATER','1');
//  }
//
//  function unsetWordpressEnableRater(){
//    $this->_addExtra('WORDPRESSENABLERATER','-1');
//  }
//
//  function WordpressEnableRater(){
//    if ( $this->_issetExtra('WORDPRESSENABLERATER') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLERATER');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableListCategories(){
//    $this->_addExtra('WORDPRESSENABLELISTCATEGORIES','1');
//  }
//
//  function unsetWordpressEnableListCategories(){
//    $this->_addExtra('WORDPRESSENABLELISTCATEGORIES','-1');
//  }
//
//  function WordpressEnableListCategories(){
//    if ( $this->_issetExtra('WORDPRESSENABLELISTCATEGORIES') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLELISTCATEGORIES');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressNewPageTemplate($template){
//    $this->_addExtra('WORDPRESSNEWPAGETEMPLATE',$template);
//  }
//
//  function unsetWordpressNewPageTemplate(){
//    $this->_addExtra('WORDPRESSNEWPAGETEMPLATE','-1');
//  }
//
//  function WordpressNewPageTemplate(){
//    if (($this->_issetExtra('WORDPRESSNEWPAGETEMPLATE')) &&  ($this->_getExtra('WORDPRESSNEWPAGETEMPLATE') != '-1')) {
//      $retour = $this->_getExtra('WORDPRESSNEWPAGETEMPLATE');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableSwf(){
//    $this->_addExtra('WORDPRESSENABLESWF','1');
//  }
//
//  function unsetWordpressEnableSwf(){
//    $this->_addExtra('WORDPRESSENABLESWF','-1');
//  }
//
//  function WordpressEnableSwf(){
//    if ( $this->_issetExtra('WORDPRESSENABLESWF') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLESWF');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableWmplayer(){
//    $this->_addExtra('WORDPRESSENABLEWMPLAYER','1');
//  }
//
//  function unsetWordpressEnableWmplayer(){
//    $this->_addExtra('WORDPRESSENABLEWMPLAYER','-1');
//  }
//
//  function WordpressEnableWmplayer(){
//    if ( $this->_issetExtra('WORDPRESSENABLEWMPLAYER') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLEWMPLAYER');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableQuicktime(){
//    $this->_addExtra('WORDPRESSENABLEQUICKTIME','1');
//  }
//
//  function unsetWordpressEnableQuicktime(){
//    $this->_addExtra('WORDPRESSENABLEQUICKTIME','-1');
//  }
//
//  function WordpressEnableQuicktime(){
//    if ( $this->_issetExtra('WORDPRESSENABLEQUICKTIME') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLEQUICKTIME');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableYoutubeGoogleVimeo(){
//    $this->_addExtra('WORDPRESSENABLEYOUTUBEGOOGLEVIMOEO','1');
//  }
//
//  function unsetWordpressEnableYoutubeGoogleVimeo(){
//    $this->_addExtra('WORDPRESSENABLEYOUTUBEGOOGLEVIMOEO','-1');
//  }
//
//  function WordpressEnableYoutubeGoogleVimeo(){
//    if ( $this->_issetExtra('WORDPRESSENABLEYOUTUBEGOOGLEVIMOEO') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLEYOUTUBEGOOGLEVIMOEO');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  // /new features
//
//  function setWordpressEditPW($pw){
//    $this->_addExtra('WORDPRESSEDITPW',$pw);
//  }
//
//  function getWordpressEditPW(){
//    if ( $this->_issetExtra('WORDPRESSEDITPW') ) {
//      $retour = $this->_getExtra('WORDPRESSEDITPW');
//    } else {
//      $retour ='edit';
//    }
//    return $retour;
//  }
//
//
//  function setWordpressReadPW($pw){
//    $this->_addExtra('WORDPRESSREADPW',$pw);
//  }
//
//  function getWordpressReadPW(){
//    if ( $this->_issetExtra('WORDPRESSREADPW') ) {
//      $retour = $this->_getExtra('WORDPRESSREADPW');
//    } else {
//      $retour = '';
//    }
//    return $retour;
//  }
//
//  function setWordpressWithSectionEdit () {
//    $this->_addExtra('WORDPRESS_SECTIONEDIT','1');
//  }
//
//  function setWordpressWithoutSectionEdit () {
//    $this->_addExtra('WORDPRESS_SECTIONEDIT','-1');
//  }
//
//  function setWordpressWithHeaderForSectionEdit () {
//    $this->_addExtra('WORDPRESS_SECTIONEDIT_HEADER','1');
//  }
//
//  function setWordpressWithoutHeaderForSectionEdit () {
//    $this->_addExtra('WORDPRESS_SECTIONEDIT_HEADER','-1');
//  }
//
//  function wordpressWithSectionEdit () {
//    $retour = false;
//    if ( $this->_issetExtra('WORDPRESS_SECTIONEDIT') ) {
//      $value = $this->_getExtra('WORDPRESS_SECTIONEDIT');
//      if ( $value == 1 ) {
//        $retour = true;
//      }
//    }
//    return $retour;
//  }
//
//  function wordpressWithHeaderForSectionEdit () {
//    $retour = false;
//    if ( $this->_issetExtra('WORDPRESS_SECTIONEDIT_HEADER') ) {
//      $value = $this->_getExtra('WORDPRESS_SECTIONEDIT_HEADER');
//      if ( $value == 1 ) {
//        $retour = true;
//      }
//    }
//    return $retour;
//  }
//
//  // Wordpress Discussion
//
//  function setWordpressEnableDiscussion(){
//    $this->_addExtra('WORDPRESSENABLEDISCUSSION','1');
//  }
//
//  function unsetWordpressEnableDiscussion(){
//    $this->_addExtra('WORDPRESSENABLEDISCUSSION','-1');
//  }
//
//  function WordpressEnableDiscussion(){
//    if ( $this->_issetExtra('WORDPRESSENABLEDISCUSSION') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLEDISCUSSION');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableDiscussionNotification(){
//    $this->_addExtra('WORDPRESSENABLEDISCUSSIONNOTIFICATION','1');
//  }
//
//  function unsetWordpressEnableDiscussionNotification(){
//    $this->_addExtra('WORDPRESSENABLEDISCUSSIONNOTIFICATION','-1');
//  }
//
//  function WordpressEnableDiscussionNotification(){
//    if ( $this->_issetExtra('WORDPRESSENABLEDISCUSSIONNOTIFICATION') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLEDISCUSSIONNOTIFICATION');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressEnableDiscussionNotificationGroups(){
//    $this->_addExtra('WORDPRESSENABLEDISCUSSIONNOTIFICATIONGROUPS','1');
//  }
//
//  function unsetWordpressEnableDiscussionNotificationGroups(){
//    $this->_addExtra('WORDPRESSENABLEDISCUSSIONNOTIFICATIONGROUPS','-1');
//  }
//
//  function WordpressEnableDiscussionNotificationGroups(){
//    if ( $this->_issetExtra('WORDPRESSENABLEDISCUSSIONNOTIFICATIONGROUPS') ) {
//      $retour = $this->_getExtra('WORDPRESSENABLEDISCUSSIONNOTIFICATIONGROUPS');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function WordpressSetNewDiscussion($new_discussion){
//    if(!empty($new_discussion)){
//      if(!$this->_issetExtra('WORDPRESSDISCUSSIONARRAY')){
//        $this->_addExtra('WORDPRESSDISCUSSIONARRAY', $new_discussion);
//      } else {
//        if ( $this->_issetExtra('WORDPRESSDISCUSSIONARRAY') && !mb_stristr($this->_getExtra('WORDPRESSDISCUSSIONARRAY'), $new_discussion)) {
//          $discussion_string = $this->_getExtra('WORDPRESSDISCUSSIONARRAY');
//          if(!empty($discussion_string)){
//            $discussion_array = explode('$CSDW$', $discussion_string);
//          } else {
//            $discussion_array = array();
//          }
//          $discussion_array[] = $new_discussion;
//          $discussion_string = implode('$CSDW$', $discussion_array);
//          $this->_addExtra('WORDPRESSDISCUSSIONARRAY',$discussion_string);
//        }
//      }
//    }
//  }
//
//  function WordpressRemoveDiscussion($old_discussion){
//    if ( $this->_issetExtra('WORDPRESSDISCUSSIONARRAY') && mb_stristr($this->_getExtra('WORDPRESSDISCUSSIONARRAY'), $old_discussion)) {
//      $discussion_string = $this->_getExtra('WORDPRESSDISCUSSIONARRAY');
//      if(!empty($discussion_string)){
//        $discussion_array = explode('$CSDW$', $discussion_string);
//        $new_discussion_array = array();
//        foreach($discussion_array as $discussion){
//          if($discussion != $old_discussion){
//            $new_discussion_array[] = $discussion;
//          }
//        }
//        $discussion_string = implode('$CSDW$', $new_discussion_array);
//      }
//      $this->_addExtra('WORDPRESSDISCUSSIONARRAY',$discussion_string);
//    }
//  }
//
//  function getWordpressDiscussionArray(){
//    if ( $this->_issetExtra('WORDPRESSDISCUSSIONARRAY') ) {
//      $discussion_string = $this->_getExtra('WORDPRESSDISCUSSIONARRAY');
//    } else {
//      $discussion_string ='';
//    }
//    $discussion_array = explode('$CSDW$', $discussion_string);
//    if($discussion_array[0] == ''){
//      return false;
//    } else {
//      return $discussion_array;
//    }
//  }
//
//  function unsetWordpressDiscussionArray(){
//    $this->_addExtra('WORDPRESSDISCUSSIONARRAY','');
//  }
//
//  function setWordpressUseCommSyLogin(){
//    $this->_addExtra('WORDPRESSUSECOMMSYLOGIN','1');
//  }
//
  function unsetWordpressUseCommSyLogin(){
    $this->_addExtra('WORDPRESSUSECOMMSYLOGIN','-1');
  }

  function WordpressUseCommSyLogin(){
    if ( $this->_issetExtra('WORDPRESSUSECOMMSYLOGIN') ) {
      $retour = $this->_getExtra('WORDPRESSUSECOMMSYLOGIN');
    } else {
      $retour ='1';
    }
    return $retour;
  }

  public function withWordpressUseCommSyLogin () {
    $retour = false;
    if ( $this->WordpressUseCommSyLogin() == 1 ) {
      $retour = true;
    }
    return $retour;
  }
//
//  function setWordpressCommunityReadAccess(){
//    $this->_addExtra('WORDPRESSCOMMUNITYREADACCESS','1');
//  }
//
//  function unsetWordpressCommunityReadAccess(){
//    $this->_addExtra('WORDPRESSCOMMUNITYREADACCESS','-1');
//  }
//
//  function WordpressCommunityReadAccess(){
//    if ( $this->_issetExtra('WORDPRESSCOMMUNITYREADACCESS') ) {
//      $retour = $this->_getExtra('WORDPRESSCOMMUNITYREADACCESS');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressCommunityWriteAccess(){
//    $this->_addExtra('WORDPRESSCOMMUNITYWRITEACCESS','1');
//  }
//
//  function unsetWordpressCommunityWriteAccess(){
//    $this->_addExtra('WORDPRESSCOMMUNITYWRITEACCESS','-1');
//  }
//
//  function WordpressCommunityWriteAccess(){
//    if ( $this->_issetExtra('WORDPRESSCOMMUNITYWRITEACCESS') ) {
//      $retour = $this->_getExtra('WORDPRESSCOMMUNITYWRITEACCESS');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function setWordpressPortalReadAccess(){
//    $this->_addExtra('WORDPRESSPORTALREADACCESS','1');
//  }
//
//  function unsetWordpressPortalReadAccess(){
//    $this->_addExtra('WORDPRESSPORTALREADACCESS','-1');
//  }
//
//  function WordpressPortalReadAccess(){
//    if ( $this->_issetExtra('WORDPRESSPORTALREADACCESS') ) {
//      $retour = $this->_getExtra('WORDPRESSPORTALREADACCESS');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function isWordpressPortalReadAccess () {
//    $retour = false;
//    if ( $this->WordpressPortalReadAccess() == 1 ) {
//      $retour = true;
//    }
//    return $retour;
//  }
//
//  function setWordpressRoomModWriteAccess(){
//    $this->_addExtra('WORDPRESSROOMWRITEMODACCESS','1');
//  }
//
//  function unsetWordpressRoomModWriteAccess(){
//    $this->_addExtra('WORDPRESSROOMWRITEMODACCESS','-1');
//  }
//
//  function WordpressRoomModWriteAccess(){
//    if ( $this->_issetExtra('WORDPRESSROOMWRITEMODACCESS') ) {
//      $retour = $this->_getExtra('WORDPRESSROOMWRITEMODACCESS');
//    } else {
//      $retour ='-1';
//    }
//    return $retour;
//  }
//
//  function isWordpressRoomModWriteAccess () {
//    $retour = false;
//    if ( $this->WordpressRoomModWriteAccess() == 1 ) {
//      $retour = true;
//    }
//    return $retour;
//  }

  
  function withLimeSurveyFunctions()
  {
    global $symfonyContainer;
    return $symfonyContainer->getParameter('commsy.limesurvey.enabled');
  }
  
  function setLimeSurveyActive()
  {
  	$this->_addExtra('LIMESURVEY', 1);
  }
  
  function setLimeSurveyInactive()
  {
  	$this->_addExtra('LIMESURVEY', -1);
  }
  
  function isLimeSurveyActive()
  {
  	if ( $this->_issetExtra('LIMESURVEY') && $this->_getExtra('LIMESURVEY') === 1 )
  	{
  		return true;
  	}
  	
  	return false;
  }
  
  function setLimeSurveyJsonRpcUrl($url)
  {
  	$this->_addExtra('LIMESURVEYJSONRPCURL', $url);
  }
  
  function getLimeSurveyJsonRpcUrl()
  {
  	if ( $this->_issetExtra('LIMESURVEYJSONRPCURL') )
  	{
  		return $this->_getExtra('LIMESURVEYJSONRPCURL');
  	}
  	
  	return '';
  }
  
  function setLimeSurveySurveyIDs($ids)
  {
  	$this->_addExtra('LIMESURVEYSURVEYIDS', $ids);
  }
  
  function getLimeSurveySurveyIDs()
  {
  	if ( $this->_issetExtra('LIMESURVEYSURVEYIDS') )
  	{
  		return $this->_getExtra('LIMESURVEYSURVEYIDS');
  	}
  	
  	return array();
  }
  
  function setLimeSurveyAdminUser($username)
  {
  	$this->_addExtra('LIMESURVEYADMINUSER', $username);
  }
  
  function getLimeSurveyAdminUser()
  {
  	if ( $this->_issetExtra('LIMESURVEYADMINUSER') )
  	{
  		return $this->_getExtra('LIMESURVEYADMINUSER');
  	}
  	
  	return '';
  }
  
  function setLimeSurveyAdminPassword($password)
  {
  	$this->_addExtra('LIMESURVEYADMINPASSWORD', $password);
  }
  
  function getLimeSurveyAdminPassword()
  {
  	if ( $this->_issetExtra('LIMESURVEYADMINPASSWORD') )
  	{
  		return $this->_getExtra('LIMESURVEYADMINPASSWORD');
  	}
  	
  	return '';
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
//    return $retour;
	return true;
  }

  function setWithPath () {
    $this->_addExtra('PATH',1);
  }

  function setWithoutPath () {
    $this->_addExtra('PATH',0);
  }

  function InformationBoxWithExistingObject() {
    $retour = false;
    $id = $this->getInformationBoxEntryID();
    $manager = $this->_environment->getItemManager();
    $item = $manager->getItem($id);
    if (is_object($item) and !$item->isDeleted()) {
      $entry_manager = $this->_environment->getManager($item->getItemType());
      $entry = $entry_manager->getItem($id);
      if (is_object($entry) and !$entry->isDeleted()) {
        $retour = true;
      }
    }
    return $retour;
  }

  function withInformationBox() {
    $retour = false;
    if ($this->_issetExtra('WITHINFORMATIONBOX')) {
      if( $this->_getExtra('WITHINFORMATIONBOX') == 'yes' and $this->InformationBoxWithExistingObject()) {
        $retour = true;
      }
    }
    return $retour;
  }

  function setwithInformationBox($value) {
    $this->_addExtra('WITHINFORMATIONBOX',(string)$value);
  }


  function getDefaultProjectTemplateID() {
    $retour = '-1';
    if ($this->_issetExtra('DEFAULTPROJECTTEMPLATEID')) {
      $retour = $this->_getExtra('DEFAULTPROJECTTEMPLATEID');
    }
    return $retour;
  }

  function setDefaultProjectTemplateID($value) {
    $this->_addExtra('DEFAULTPROJECTTEMPLATEID',(string)$value);
  }


  function getDefaultCommunityTemplateID() {
    $retour = '-1';
    if ($this->_issetExtra('DEFAULTCOMMUNITYTEMPLATEID')) {
      $retour = $this->_getExtra('DEFAULTCOMMUNITYTEMPLATEID');
    }
    return $retour;
  }

  function setDefaultCommunityTemplateID($value) {
    $this->_addExtra('DEFAULTCOMMUNITYTEMPLATEID',(string)$value);
  }

  function getTemplateDescription() {
    $retour = '';
    if ($this->_issetExtra('TEMPLATEDESCRIPTION')) {
      $retour = $this->_getExtra('TEMPLATEDESCRIPTION');
    }
    return $retour;
  }

  function setTemplateDescription($value) {
    $this->_addExtra('TEMPLATEDESCRIPTION',(string)$value);
  }


  function getInformationBoxEntryID() {
    $translator = $this->_environment->getTranslationObject();
      $retour = '';
    if ($this->_issetExtra('INFORMATIONBOXENTRYID')) {
      $retour = $this->_getExtra('INFORMATIONBOXENTRYID');
    }
    return $retour;
  }
  function setInformationBoxEntryID($value) {
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

  function setBGImageFilename ($name) {
    $this->_addExtra('BGIMAGEFILENAME',$name);
  }

  function getBGImageFilename () {
    $retour = '';
    if ($this->_issetExtra('BGIMAGEFILENAME')) {
      $retour = $this->_getExtra('BGIMAGEFILENAME');
    }
    return $retour;
  }

  function setBGImageRepeat () {
    $this->_addExtra('BGIMAGEREPEAT',1);
  }

  function unsetBGImageRepeat () {
    $this->_addExtra('BGIMAGEREPEAT',0);
  }

  function setBGImageFixed () {
    $this->_addExtra('BGIMAGEFIXED',1);
  }

  function unsetBGImageFixed () {
    $this->_addExtra('BGIMAGEFIXED',0);
  }

  public function issetBGImageRepeat () {
    $retour = false;
    if ($this->_issetExtra('BGIMAGEREPEAT')) {
      $retour = $this->_getExtra('BGIMAGEREPEAT');
      if ( $retour == 1 ) {
        $retour = true;
      } else {
        $retour = false;
      }
    }
    return $retour;
  }

  public function issetBGImageFixed () {
    $retour = false;
    if ($this->_issetExtra('BGIMAGEFIXED')) {
      $retour = $this->_getExtra('BGIMAGEFIXED');
      if ( $retour == 1 ) {
        $retour = true;
      } else {
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

  function setWithTags() {
    $this->_addExtra('WITHTAGS',2);
  }

  function setWithoutTags() {
    $this->_addExtra('WITHTAGS',1);
  }

  function withTags() {
    $retour = false;
    if ( $this->_issetExtra('WITHTAGS') ) {
      $re = $this->_getExtra('WITHTAGS');
      if ($re == 2) {
        $retour = true;
      }
    } else {
       if($this->_environment->inPrivateRoom()){
         $retour = true;
       }

       if (is_a($this, "cs_privateroom_item")) $retour = true;
    }
    return $retour;
  }

  function setTagsShowExpanded () {
    $this->_addExtra('TAGSSHOWEXPANDED',1);
  }

  function unsetTagsShowExpanded () {
    $this->_addExtra('TAGSSHOWEXPANDED',0);
  }

  function isTagsShowExpanded () {
    $retour = true;
    if ( $this->_issetExtra('TAGSSHOWEXPANDED') ) {
      $value = $this->_getExtra('TAGSSHOWEXPANDED');
      if ($value == 0) {
        $retour = false;
      }
    }
   return $retour;
  }

  function isNetnavigationShowExpanded () {
    $retour = false;
    if ( $this->_issetExtra('NAVIGATIONSHOWEXPANDED') ) {
      $value = $this->_getExtra('NAVIGATIONSHOWEXPANDED');
      if ($value == 1) {
        $retour = true;
      }
    }
//    return $retour;
	return true;
  }

  function setNetnavigationShowExpanded () {
    $this->_addExtra('NAVIGATIONSHOWEXPANDED',1);
  }

  function unsetNetnavigationShowExpanded () {
    $this->_addExtra('NAVIGATIONSHOWEXPANDED',0);
  }


  function setWithNetnavigation() {
    $this->_addExtra('WITHNETNAVIGATION',2);
  }

  function setWithoutNetnavigation() {
    $this->_addExtra('WITHNETNAVIGATION',1);
  }

  function withNetnavigation() {
    $retour = true;
    if ( $this->_issetExtra('WITHNETNAVIGATION') ) {
      $retour = false;
      $re = $this->_getExtra('WITHNETNAVIGATION');
      if ($re == 2) {
        $retour = true;
      }
    }
    return $retour;
  }


  function withMaterialImportLink () {
    $retour = false;
    $value = $this->_getExtraConfig('MATERIALIMPORT');
    if ($value == 1) {
      $retour = true;
    } elseif ($this->isProjectRoom() or $this->isCommunityRoom()) {
      $portal_room = $this->getContextItem();
      if ( $portal_room->withMaterialImportLink() ) {
        $retour = true;
      }
    }
    return $retour;
  }

  function setWithMaterialImport () {
    $this->_setExtraConfig('MATERIALIMPORT',1);
  }

  function setWithoutMaterialImport () {
    $this->_setExtraConfig('MATERIALIMPORT',0);
  }

  function withActivatingContent () {
    $retour = false;
    $value = $this->_getExtraConfig('ACTIVATINGCONTENT');
    if ($value == 1) {
      $retour = true;
    } elseif ($this->isProjectRoom() or $this->isCommunityRoom()) {
      $portal_room = $this->getContextItem();
      if ( $portal_room->withActivatingContent() ) {
        $retour = true;
      }
    }
    return $retour;
  }

  function setWithActivatingContent () {
    $this->_setExtraConfig('ACTIVATINGCONTENT',1);
  }

  function setWithoutActivatingContent () {
    $this->_setExtraConfig('ACTIVATINGCONTENT',0);
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
      if ( !empty ($rubric_type) and mb_stristr($current_room_modules,$rubric_type) ) {
        // for <rubric>_none, _rubric_support[<rubric>] previously was set to false; however,
        // it now contains true since rubrics with <rubric>_none are activated in CS9 (while
        // they were deactivated in CS8)
        if ($this->isExtraRubric($rubric_type) and !$this->showExtraRubric($rubric_type)) {
          $this->_rubric_support[$rubric_type] = false;
        } else {
          $this->_rubric_support[$rubric_type] = true;
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
        if ($rubric=='contact') {
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
    if ($this->isPrivateRoom()) {
      unset($temp[4]);
    }
    foreach ($temp as $rubric) {
      if ($rubric=='contact') {
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


   public function isRSSOn () {
      $retour = true;
      $value = $this->getRSSStatus();
      if ( !empty($value) and $value == -1 ) {
         $retour = false;
      }
      return $retour;
   }

   public function getRSSStatus () {
      $retour = '';
      if ($this->_issetExtra('RSS_STATUS')) {
         $retour = $this->_getExtra('RSS_STATUS');
      }
      return $retour;
   }

   public function _setRSSStatus ($value) {
      $this->_addExtra('RSS_STATUS',$value);
   }

   public function turnRSSOn () {
      $this->_setRSSStatus(1);
   }

   public function turnRSSOff () {
      $this->_setRSSStatus(-1);
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

    public function saveActivityPoints($points)
    {
        $this->setActivityPoints($points + $this->getActivityPoints());
        if ($this->isProjectRoom()) {
            $manager = $this->_environment->getProjectManager();
        } elseif ($this->isGroupRoom()) {
            $manager = $this->_environment->getGrouproomManager();
        } elseif ($this->isUserroom() ) {
      $manager = $this->_environment->getUserRoomManager();
    } elseif ( $this->isCommunityRoom()) {
            $manager = $this->_environment->getCommunityManager();
        } elseif ($this->isPortal()) {
            $manager = $this->_environment->getPortalManager();
        } elseif ($this->isServer()) {
            $manager = $this->_environment->getServerManager();
        }
        if (isset($manager)) {
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


  }

  /** is room a normal open ?
   * this method returns a boolean explaining if a room is open
   *
   * @return boolean true, if a room is open
   *                 false, if a room is not open
   */
  function isOpen () {
    $retour = false;
    if ( !empty($this->_data['status'])
            and $this->_data['status'] == CS_ROOM_OPEN
    ) {
      $retour = true;
    }
    return $retour;
  }

  /** is a room closed ?
   * this method returns a boolean explaining if a room is closed or not
   *
   * @return boolean true, if a room is closed
   *                 false, if a room is not closed
   */
  function isClosed () {
    $retour = false;
    if ( !empty($this->_data['status'])
            and $this->_data['status'] == CS_ROOM_CLOSED
    ) {
      $retour = true;
    }
    return $retour;
  }

  /** is a room locked?
   * this method returns a boolean explaining if a room is locked
   *
   * @return boolean true, if a room is locked
   *                 false, if a room is not locked
   */
  function isLocked () {
    $retour = false;
    if ( !empty($this->_data['status'])
            and $this->_data['status'] == CS_ROOM_LOCK
    ) {
      $retour = true;
    }
    return $retour;
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

  function mayEdit (cs_user_item $user) {
    $value = false;
    if ( !empty($user) ) {
      if ( !$user->isOnlyReadUser() ) {
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
    }
    return $value;
  }

  function mayEditRegular ($user) {
    $value = false;
    if ( !empty($user) ) {
      if ( !$user->isOnlyReadUser() ) {
        if ( $user->isUser()
                and ( $user->getItemID() == $this->getCreatorID()
                        or $this->isPublic()
                        or $this->isModeratorByUserID($user->getUserID(),$user->getAuthSource())
        )
        ) {
          $value = true;
        }
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

  function resetUserList () {
      $userManager = $this->_environment->getUserManager();
      $userManager->setCacheOff();
      unset($this->_user_list);
  }

  function isUser ($user) {
    $retour = false;
    $user_manager = $this->_environment->getUserManager();
/*DB-Optimierung vom 23.10.2010*/
    $retour = $user_manager->isUserInContext($user->getUserID(), $this->getItemID(), $user->getAuthSource());
#    $user_manager->setContextLimit($this->getItemID());
#    $user_manager->setUserIDLimit($user->getUserID());
#    $user_manager->setAuthSourceLimit($user->getAuthSource());
#    $user_manager->setUserLimit();
#    $user_manager->select();
#    $user_list = $user_manager->get();
#    if ( $user_list->isNotEmpty() ) {
#      $retour = true;
#    }
/*DB-Optimierung vom 23.10.2010*/
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
    $in_array = in_array($rubric, array(CS_GROUP_TYPE, CS_TOPIC_TYPE)) ;
    return $in_array;
  }


  /** asks if item is editable by everybody or just creator
   *
   * @param value
   */
  function isPublic () {
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

  function getCountItems ($start,$end) {
    if ( !isset($this->_count_items) ) {
      $manager = $this->_environment->getItemManager();
      $manager->resetLimits();
      $manager->setContextLimit($this->getItemID());
      $this->_count_items = $manager->getCountItems($start,$end);
    }
    $retour = $this->_count_items;
    return $retour;
  }

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
        global $c_cache_cr_pr;
        if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
          $manager->setCommunityRoomLimit($this->getItemID());
        } else {
          /**
           * use redundant infos in community room
           */
          $manager->setIDArrayLimit($this->getInternalProjectIDArray());
        }
      } else {
        $manager->setContextLimit($this->getItemID());
      }
      $this->_count_projects = $manager->getCountProjects($start,$end);
    }
    $retour = $this->_count_projects;
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
    // only save for 365 days
    if ( is_array($value) ) {
      while ( count($value) > 365 ) {
        array_pop($value);
      }
    }
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

  /*
    * set user activity array
  */
  function setUserActivityArray($value) {
    if(is_array($value)) {
      while(count($value) > 365) {
        array_pop($value);
      }
    }
    $this->_addExtra('USER_ACTIVITY',(array)$value);
  }

  /*
    * get user activity array
  */
  function getUserActivityArray() {
    $retour = $this->_getExtra('USER_ACTIVITY');
    if(empty($retour)) {
      $retour = array();
    }
    return $retour;
  }

  function getPageImpressions ($external_timespread = 0,$db_page_impressions = 0) {
    $retour = 0;
    if ( isset($this->_page_impression_array[$external_timespread]) ) {
      $retour = $this->_page_impression_array[$external_timespread];
    } else {
      if ( $external_timespread != 0 ) {
        $timespread = $external_timespread;
      } else {
        $timespread = $this->getTimeSpread();
      }
      $count = 0;
      $pi_array = $this->getPageImpressionArray();
      for ($i=0; $i<$timespread; $i++) {
        if (!empty($pi_array[$i])) {
          $count = $count + $pi_array[$i];
        }
      }
      if ($db_page_impressions == 0) {
        $log_manager = $this->_environment->getLogManager();
        $log_manager->resetLimits();
        $log_manager->setContextLimit($this->getItemID());
        $page_impressions = $log_manager->getCountAll();
        unset($log_manager);
      }else {
        $page_impressions = $db_page_impressions;
      }
      $this->_page_impression_array[$external_timespread] = $count + $page_impressions;
      $retour = $this->_page_impression_array[$external_timespread];
    }
    return $retour;
  }

  function isActiveDuringLast99Days () {
    return $this->getPageImpressions() > 0;
  }

  function getNewEntries($external_timespread = 0) {
    if($external_timespread != 0) {
      $timespread = $external_timespread;
    }else {
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
      if ( $rubric_status != 'none' ) {
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
#      $item_manager->setAgeLimit(7);
    $item_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    $item_manager->setTypeArrayLimit($check_managers);
    $item_manager->select();
    $new_entries = $item_manager->getIDArray();
    $count_total = $new_entries ? count($new_entries) : 0;
    unset($item_manager);
    return $count_total;
  }

  function getNotReadEntries($external_timespread = 0, $user_id) {
    if($external_timespread != 0) {
      $timespread = $external_timespread;
    }else {
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
      if ( $rubric_status != 'none' ) {
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
    $item_manager->setReadLimit($user_id);
    $item_manager->setAgeLimit($timespread);
    $item_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    $item_manager->setTypeArrayLimit($check_managers);
    $item_manager->select();
    $new_entries = $item_manager->getIDArray();
    $count_total = count($new_entries);
    unset($item_manager);
    return $count_total;
  }

  function getActiveMembers($external_timespread = 0) {
    if($external_timespread != 0) {
      $timespread = $external_timespread;
    }else {
      $timespread = $this->getTimeSpread();
    }
    $user_manager = $this->_environment->getUserManager();
    $user_manager->reset();
    $user_manager->setContextLimit($this->getItemID());
    $user_manager->setUserLimit();
    $user_manager->setLastLoginLimit($timespread);
    $ids = $user_manager->getIDArray();
    $active = !empty($ids) ? count($ids) : 0;
    unset($user_manager);
    return $active;
  }

  function getActiveMembersForNewsletter($external_timespread = 0) {
    // take it from UserActivity extras field
    $retour = 0;
    if(isset($this->_user_activity_array[$external_timespread])) {
      $retour = $this->_user_activity_array[$external_timespread];
    } else {
      if($external_timespread != 0) {
        $timespread = $external_timespread;
      } else {
        $timespread = $this->getTimeSpread();
      }

      $count = 0;
      $ua_array = $this->getUserActivityArray();

      for($i=0; $i<$timespread;$i++) {
        if(!empty($ua_array[$i])) {
          $count += $ua_array[$i];
        }
      }
      $retour = $count;
    }
    return $retour;
  }

  function getPageImpressionsForNewsletter($external_timespread = 0) {
    $retour = 0;
    if ( isset($this->_page_impression_array[$external_timespread]) ) {
      $retour = $this->_page_impression_array[$external_timespread];
    } else {
      if ( $external_timespread != 0 ) {
        $timespread = $external_timespread;
      } else {
        $timespread = $this->getTimeSpread();
      }
      $count = 0;
      $pi_array = $this->getPageImpressionArray();

      for ($i=0; $i<$timespread; $i++) {
        if (!empty($pi_array[$i])) {
          $count += $pi_array[$i];
        }
      }
      $retour = $count;
    }
    return $retour;
  }

  function getAllUsers() {
    $user_manager = $this->_environment->getUserManager();
    $user_manager->reset();
    $user_manager->setContextLimit($this->getItemID());
    $user_manager->setUserLimit();
    $retour = $user_manager->getCountAll();
    if ( empty($retour)
         and $this->isClosed()
         and !$this->_environment->isArchiveMode()
       ) {
       $this->_environment->activateArchiveMode();
       $user_manager2 = $this->_environment->getUserManager();
       $user_manager2->reset();
       $user_manager2->setContextLimit($this->getItemID());
       $user_manager2->setUserLimit();
       $retour = $user_manager2->getCountAll();
       unset($user_manager2);
       $this->_environment->deactivateArchiveMode();
    }
    unset($user_manager);
    return $retour;
  }

  function delete () {
  }

  function generateLayoutImages() {
    global $c_commsy_path_file;
    $color_array = $this->getColorArray();
    $disc_manager = $this->_environment->getDiscManager();
    if ( $this->isPortal() or $this->isServer() ) {
      $disc_manager->setPortalID($this->getItemID());
      $disc_manager->setContextID($this->getItemID());
      $disc_manager->makeFolder($this->getItemID(),$this->getItemID());
    }
    
    $disc_manager->setContextID($this->_environment->getCurrentContextItem()->getItemID());
  }

  function generateColourGradient($height, $rgb) {
    $image = imagecreate(1, $height);

    $rgb = str_replace('#', '', $rgb);

    $r = hexdec(mb_substr($rgb, 0, 2));
    $g = hexdec(mb_substr($rgb, 2, 2));
    $b = hexdec(mb_substr($rgb, 4, 2));

    $border = ImageColorAllocate($image,$r,$g,$b);

    for ($i=0; $i<($height/2); $i++) {
      $line = ImageColorAllocate($image,$r-(($r/255)*($i*3)),$g-(($g/255)*($i*3)),$b-(($b/255)*($i*3)));
      imageline($image, 0, $i, 0, $i, $line);
      imageline($image, 0, (($height-1)-$i), 500, (($height-1)-$i), $line);
    }
    return $image;
  }

  function getPageImpressionAndUserActivityLast() {
    $retour = $this->_getExtra('PIUA_LAST');
    if (empty($retour)) {
      $retour = "";
    }
    return $retour;
  }

  function setPageImpressionAndUserActivityLast($value) {
    $this->_addExtra('PIUA_LAST',$value);
  }

  ##################################
  # Workflow
  ##################################

  function withWorkflowFunctions () {
    $retour = false;
    $value = $this->_getExtraConfig('WORKFLOW');
    if ($value == 1) {
      $retour = true;
    } elseif ( $this->isProjectRoom()
    or $this->isCommunityRoom()
    or $this->isGroupRoom()
    or $this->isPrivateRoom()
    ) {
      $portal_room = $this->getContextItem();
      if ( $portal_room->withWorkflowFunctions() ) {
        $retour = true;
      }
    }
    return $retour;
  }

  function setWithWorkflowFunctions () {
    $this->_setExtraConfig('WORKFLOW',1);
  }

  function setWithoutWorkflowFunctions () {
    $this->_setExtraConfig('WORKFLOW',0);
  }
  
  function setHideAccountname(){
  	$this->_setExtraConfig('HIDE_ACCOUNTNAME', '1');
  }
  
  function unsetHideAccountname(){
  	$this->_setExtraConfig('HIDE_ACCOUNTNAME', '2');
  }
  
  function getHideAccountname(){
  	$retour = false;
  	$value = $this->_getExtraConfig('HIDE_ACCOUNTNAME');
  	if($value == 2){
  		$retour = false;
  	} else if($value == 1){
  		$retour = true;
  	}
  	return $retour;
  }

  function setWithAnnouncementDates() {
  	$this->_addExtra('HIDE_ANNOUNCEMENT_DATE',2);
  }
  
  function setWithoutAnnouncementDates() {
  	$this->_addExtra('HIDE_ANNOUNCEMENT_DATE',1);
  }
  
  function withAnnouncementDates() {
  	$retour = false;
  	if ($this->_issetExtra('HIDE_ANNOUNCEMENT_DATE') ) {
  		$re = $this->_getExtra('HIDE_ANNOUNCEMENT_DATE');
  		if ($re == 2) {
  			$retour = true;
  		}
  	}else {
  		$retour = false;
  	}
  	return $retour;
  }
 
 
  // MediaWiki
  function setWikiEnabled ($value) {
      $this->_addExtra('WIKI_ENABLED',$value);
  }
  
  function isWikiEnabled () {
  	if ($this->_issetExtra('WIKI_ENABLED')) {
  		if ($this->_getExtra('WIKI_ENABLED')) {
      		return true;
  		}
  	}
  	return false;
  }


  public function getDefaultCalendarId () {
      global $symfonyContainer;
      $calendarsService = $symfonyContainer->get('commsy.calendars_service');
      if (!isset($calendarsService->getDefaultCalendar($this->getItemId())[0])) {
          $calendarsService->createCalendar($this, null, null, true);
      }
      return $calendarsService->getDefaultCalendar($this->getItemId())[0]->getId();
  }

    function setUsersCanEditCalendars() {
        $this->_addExtra('USERSCANEDITCALENDARS',1);
    }

    function unsetUsersCanEditCalendars() {
        $this->_addExtra('USERSCANEDITCALENDARS',0);
    }

    function usersCanEditCalendars() {
        $retour = false;
        if ($this->_issetExtra('USERSCANEDITCALENDARS') ) {
            $re = $this->_getExtra('USERSCANEDITCALENDARS');
            if ($re == 1) {
                $retour = true;
            }
        }
        return $retour;
    }

    function setUsersCanSetExternalCalendarsUrl() {
        $this->_addExtra('USERSCANSETEXTERNALCALENDARSURL',1);
    }

    function unsetUsersCanSetExternalCalendarsUrl() {
        $this->_addExtra('USERSCANSETEXTERNALCALENDARSURL',0);
    }

    function usersCanSetExternalCalendarsUrl() {
        $retour = false;
        if ($this->_issetExtra('USERSCANSETEXTERNALCALENDARSURL') ) {
            $re = $this->_getExtra('USERSCANSETEXTERNALCALENDARSURL');
            if ($re == 1) {
                $retour = true;
            }
        }
        return $retour;
    }
}
?>
