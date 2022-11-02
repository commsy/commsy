<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
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
include_once('classes/cs_context_item.php');
include_once('functions/text_functions.php');

/** father class for a rooms (project or community)
 * this class implements an abstract room item
 */

class cs_room_item extends cs_context_item {

   var $_old_status = NULL;

   /** constructor
   *
   * @param object environment environment of the commsy project
   */
   public function __construct($environment) {
       parent::__construct($environment);
   }

   ######################################################
   # methods for linking times (clock pulses) and rooms #
   ######################################################

   function _getContinuousStatus () {
      $retour = '';
      $retour = $this->_getValue('continuous');
      if (empty($retour)) {
         $retour = -1;
      }
      return $retour;
   }

   function isContinuous () {
      $retour = false;
      $value = $this->_getContinuousStatus();
      if ($value == 1) {
         $retour = true;
      }
      return $retour;
   }

   function setContinuous () {
      $this->_setValue('continuous',1,true);
      $this->_setLinksToTimeLabels();
   }

   function setNotContinuous () {
      $this->_setValue('continuous',-1,true);
   }

   function _setLinksToTimeLabels () {
      $portal_item = $this->getContextItem();
      $start_date = $this->_getDateFromDateTime($this->getCreationDate());
      if ($this->isClosed()) {
         $end_date = $this->_getDateFromDateTime($this->getClosureDate());
      }

      $current_date = getCurrentDate();

      $get_time_item_ids = false;
      $first = true;

      if ( $portal_item->showTime() ) {
         $time_item_id_array = array();
         $time_list = $portal_item->getTimeList();
         if ($time_list) {
            $time_item = $time_list->getFirst();
            while ($time_item) {
               if (!$time_item->isDeleted()) {
                  $date_label_start = $this->_getBeginDateFromTimeLabel($time_item->getTitle());
                  $date_label_end = $this->_getEndDateFromTimeLabel($time_item->getTitle());
                  if ($date_label_end < $date_label_start) {
                     $date_label_end = $date_label_end + 10000;
                  }
                  if ( $date_label_start <= $start_date
                       and $start_date <= $date_label_end
                     ) {
                     $get_time_item_ids = true;
                  }
                  if ($first) {
                     if ($date_label_start > $start_date) {
                        $get_time_item_ids = true;
                     }
                     $first = false;
                  }
                  if ($current_date < $date_label_start) {
                     $get_time_item_ids = false;
                  }
                  if ( isset($end_date)
                       and $date_label_start <= $end_date
                       and $end_date <= $date_label_end) {
                     $get_time_item_ids = false;
                  }
                  if ($get_time_item_ids) {
                     $time_item_id_array[] = $time_item->getItemID();
                  }
               }
               $time_item = $time_list->getNext();
            }
         }
         $this->setTimeListByID($time_item_id_array);
      }
   }

   function _getDateFromDateTime ($datetime) {
      $retour = '';
      if ( !empty($datetime) ) {
         $retour = $datetime[0].$datetime[1].$datetime[2].$datetime[3].$datetime[5].$datetime[6].$datetime[8].$datetime[9];
      }
      return $retour;
   }

   function _getBeginDateFromTimeLabel ($title) {
      $retour = '';
      $title_array = explode('_',$title);
      $day_month = $this->_getBeginDayMonthFromTimeLabel($title);
      if ( isset($title_array[0])
           and isset($day_month[0])
           and isset($day_month[1])
           and isset($day_month[3])
           and isset($day_month[4])
         ) {
         $retour = $title_array[0].$day_month[3].$day_month[4].$day_month[0].$day_month[1];
      }
      return $retour;
   }

   function _getEndDateFromTimeLabel ($title) {
      $retour = '';
      $title_array = explode('_',$title);
      $day_month = $this->_getEndDayMonthFromTimeLabel($title);
      if ( isset($title_array[0])
           and isset($day_month[0])
           and isset($day_month[1])
           and isset($day_month[3])
           and isset($day_month[4])
         ) {
         $retour = $title_array[0].$day_month[3].$day_month[4].$day_month[0].$day_month[1];
      }
      return $retour;
   }


   function _getDayMonthFromTimeLabel ($title, $key) {
      $retour = '';
      $portal_item = $this->getContextItem();
                 if ( !$portal_item->isPortal() ) {
                    $portal_item = $this->_environment->getCurrentPortalItem();
                 }
      $time_text_array = $portal_item->getTimeTextArray();
      $title_array = explode('_',$title);
      $retour = $time_text_array[$title_array[1]][$key];
      return $retour;

   }

   function _getBeginDayMonthFromTimeLabel ($title) {
      return $this->_getDayMonthFromTimeLabel($title,'BEGIN');
   }

   function _getEndDayMonthFromTimeLabel ($title) {
      return $this->_getDayMonthFromTimeLabel($title,'END');
   }

   function setClosureDate ($value) {
      $this->_addExtra('CLOSURE_DATE',$value);
   }

   function getClosureDate () {
      $retour = '';
      if ($this->_issetExtra('CLOSURE_DATE')) {
         $retour = $this->_getExtra('CLOSURE_DATE');
      }
      return $retour;
   }

   function setContactPerson ($fullname) {
      if ( !empty($fullname) ) {
         $value = '';
         $value = $this->_getValue('contact_persons');
         if(!mb_stristr($value,$fullname)){
            $value .= $fullname.', ';
            $this->_setValue('contact_persons',$value);
         }
      }
   }

   function getContactPersonString () {
      $return = '';
      $return = trim($this->_getValue('contact_persons'));
      if ( !empty($return)
           and mb_strstr($return,',')
           and mb_substr($return,mb_strlen($return)-1) == ','
         ) {
         $return = mb_substr($return,0,(mb_strlen($return)-1));
      }
      return $return;
   }

   public function emptyContactPersonString () {
      $this->_unsetValue('contact_persons');
   }

   public function renewContactPersonString () {
      $this->emptyContactPersonString();
      $moderator_list = $this->getContactModeratorList();
      $current_moderator = $moderator_list->getFirst();
      while ( $current_moderator ) {
         $contact_name = $current_moderator->getFullname();
         if ( !empty($contact_name)
              and mb_strtoupper($contact_name) != 'GUEST'
            ) {
            $this->setContactPerson($contact_name);
         }
         $current_moderator = $moderator_list->getNext();
      }
      $this->setChangeModificationOnSave(false);
      $this->save();
   }

   public function renewDescription () {
      if ($this->_issetExtra('DESCRIPTION')) {
         $this->setDescriptionArray($this->_getExtra('DESCRIPTION'));
         $this->_unsetExtra('DESCRIPTION');
      } else {
         $description_array = $this->getDescriptionArray();
         if(empty($description_array)){
            $this->setDescriptionArray(array());
         }
      }
      $this->setChangeModificationOnSave(false);
      $this->save();
   }

   /** close a room
   * this method sets the status of the room to closed
   */
   function close () {
      include_once('functions/date_functions.php');
      $this->setClosureDate(getCurrentDateTimeInMySQL());
      parent::close();
   }

   public function delete()
   {
        // delete associated annotations
        $this->deleteAssociatedAnnotations();
   }

   /** get time of a room
   * this method returns a list of clock pulses which are linked to the room
   *
   * @return object cs_list a list of clock pulses (cs_label_item)
   */
   function getTimeList() {
      $time_list = $this->_getLinkedTimeItems($this->_environment->getTimeManager(), 'in_time');
      $time_list->sortBy('sorting');
      return $time_list;
   }

 /** get list of linked items
   * this method returns a list of items which are linked to the news item
   *
   * @return object cs_list a list of cs_items
   * @access private
   * @author CommSy Development Group
   */
   function _getLinkedTimeItems ($item_manager, $link_type, $order='') {
      if (!isset($this->_data[$link_type]) or !is_object($this->_data[$link_type])) {

         global $environment;
         $link_manager = $environment->getLinkManager();
         $link_manager->setItemIDLimit($this->getItemID());
         // preliminary version: there should be something like 'getIDArray() in the link_manager'

         $id_array = array();
         $link_array = $link_manager->getLinks($link_type, $this, $this->getVersionID(), 'eq');
         $id_array = array();
         foreach($link_array as $link) {
            if ($link['to_item_id'] == $this->getItemID()) {
               $id_array[] = $link['from_item_id'];
            } elseif ($link['from_item_id'] == $this->getItemID()) {
               $id_array[] = $link['to_item_id'];
            }
         }
         $this->_data[$link_type] = $item_manager->getItemList($id_array);
      }
      return $this->_data[$link_type];
   }

   /** set clock pulses of a room item by id
   * this method sets a list of clock pulses item_ids which are linked to the room
   *
   * @param array of time ids
   */
   function setTimeListByID ($value) {
      $time_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $time_array[] = $tmp_data;
      }
      $this->_setValue('in_time', $time_array, FALSE);
   }
   
   /******************************************
    *  diese Funktion wird in der configuration_preferences verwendet,
    *  weil die obige aufgrund eines PHP-Bugs mehrmals aufgerufen wird
    *  und dies zu einer zeitlichen Verzögerung von 30 Sekunden kommt
    *  
    *  Datum:  20.09.2013
    *  Autor:  Iver Jackewitz
    *  Kernel: Linux RZ-CS-WEB01 3.2.0-53-virtual #81-Ubuntu SMP Thu Aug 22 21:21:26 UTC 2013 x86_64
    *  PHP:    PHP Version 5.3.10-1ubuntu3.8
    *  
    *  völlig unerklärlich
    */
   function setTimeListByID2 ($value) {
   	$time_array = array();
   	foreach ( $value as $iid ) {
   		$tmp_data = array();
   		$tmp_data['iid'] = $iid;
   		$time_array[] = $tmp_data;
   	}
   	$this->_setValue('in_time', $time_array, FALSE);
   }
    
   /** set clock pulses of a room
   * this method sets a list of clock pulses which are linked to the room
   *
   * @param object cs_list value list of clock pulses (cs_label_item)
   */
   function setTimeList($value) {
      $this->_setObject('in_time', $value, FALSE);
   }

   ######################################################
   # methods for template technique                     #
   ######################################################

   function _getTemplateStatus () {
      $retour = '-1';
      $value = $this->_getValue('template');
      if (!empty($value) and $value == 1) {
         $retour = 1;
      }
      return $retour;
   }

   function isTemplate () {
      $retour = false;
      $value = $this->_getTemplateStatus();
      if ($value == 1) {
         $retour = true;
      }
      return $retour;
   }

   function setTemplate () {
      $this->_setValue('template',1,true);
   }

   function setNotTemplate () {
      $this->_setValue('template',-1,true);
   }

   /** get topics of a project
    * this method returns a list of topics which are linked to the project
    *
    * @return object cs_list a list of topics (cs_label_item)
    */
   function getTopicList() {
      $topic_list = $this->_getLinkedItems($this->_environment->getLabelManager(), CS_TOPIC_TYPE);
      $topic_list->sortBy('name');
      return $topic_list;
   }

  /** set topics of a project item by id
   * this method sets a list of topic item_ids which are linked to the project
   *
   * @param array of topic ids
   *
   * @author CommSy Development Group
   */
   function setTopicListByID ($value) {
      $topic_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $topic_array[] = $tmp_data;
      }
      $this->_setValue(CS_TOPIC_TYPE, $topic_array, FALSE);
   }

   /** set topics of a project
    * this method sets a list of topics which are linked to the project
    *
    * @param object cs_list value list of topics (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function setTopicList($value) {
      $this->_setObject(CS_TOPIC_TYPE, $value, FALSE);
   }

   /** get materials of a project
    * this method returns a list of materials which are linked to the project
    *
    * @return object cs_list a list of materials (cs_material_item)
    *
    * @author CommSy Development Group
    */
   function getMaterialList () {
      return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
   }

  /** set materials of a project item by item id and version id
   * this method sets a list of material item_ids and version_ids which are linked to the project
   *
   * @param array of material ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   *
   * @author CommSy Development Group
   */
   function setMaterialListByID ($value) {
      $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
   }

   /** set materials of a project
    * this method sets a list of materials which are linked to the project
    *
    * @param string value title of the project
    *
    * @author CommSy Development Group
    */
   function setMaterialList ($value) {
      $this->_setObject(CS_MATERIAL_TYPE, $value, FALSE);
   }

   /** Sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItem($db_array)"
    * @return boolean TRUE if data is valid FALSE otherwise
    */
   function _setItemData($data_array) {
      $this->_data = $data_array;
      $retour = $this->isValid();
      if ($retour) {
         $this->_old_status = $this->getStatus();
      }
      return $retour;
   }

   ################################################################
   # mail to moderation, if the room status changed
   # - delete
   # - undelete
   # - open
   # - archive
   # - template (not implemented yet because flagged function)
   # - untemplate (not implemented yet because flagged function)
   # - reopen
   # - link to and unlink from community room
   ################################################################

   function _sendMailRoomDeleteToProjectModeration () {
      $this->_sendMailToModeration('project','delete');
   }

   function _sendMailRoomDeleteToCommunityModeration () {
      $this->_sendMailToModeration('community','delete');
   }

   function _sendMailRoomDeleteToPortalModeration () {
      $this->_sendMailToModeration('portal','delete');
   }

   function _sendMailRoomUnDeleteToProjectModeration () {
      $this->_sendMailToModeration('project','undelete');
   }

   function _sendMailRoomUnDeleteToCommunityModeration () {
      $this->_sendMailToModeration('community','undelete');
   }

   function _sendMailRoomUnDeleteToPortalModeration () {
      $this->_sendMailToModeration('portal','undelete');
   }

   function _sendMailRoomOpenToProjectModeration () {
      $this->_sendMailToModeration('project','open');
   }

   function _sendMailRoomOpenToCommunityModeration () {
      $this->_sendMailToModeration('community','open');
   }

   function _sendMailRoomOpenToPortalModeration () {
      $this->_sendMailToModeration('portal','open');
   }

   function _sendMailRoomArchiveToProjectModeration () {
      $this->_sendMailToModeration('project','archive');
   }

   function _sendMailRoomArchiveToCommunityModeration () {
      $this->_sendMailToModeration('community','archive');
   }

   function _sendMailRoomArchiveToPortalModeration () {
      $this->_sendMailToModeration('portal','archive');
   }

   function _sendMailRoomReOpenToProjectModeration () {
      $this->_sendMailToModeration('project','reopen');
   }

   function _sendMailRoomReOpenToCommunityModeration () {
      $this->_sendMailToModeration('community','reopen');
   }

   function _sendMailRoomReOpenToPortalModeration () {
      $this->_sendMailToModeration('portal','reopen');
   }

   function _sendMailRoomLinkToProjectModeration () {
      $this->_sendMailToModeration('project','link');
   }

   function _sendMailRoomLinkToCommunityModeration () {
      $this->_sendMailToModeration('community','link');
   }

   function _sendMailRoomLinkToPortalModeration () {
      $this->_sendMailToModeration('portal','link');
   }

   function _sendMailRoomLockToProjectModeration () {
      $this->_sendMailToModeration('project','lock');
   }

   function _sendMailRoomLockToCommunityModeration () {
      $this->_sendMailToModeration('community','lock');
   }

   function _sendMailRoomLockToPortalModeration () {
      $this->_sendMailToModeration('portal','lock');
   }

   function _sendMailRoomUnlockToProjectModeration () {
      $this->_sendMailToModeration('project','unlock');
   }

   function _sendMailRoomUnlockToCommunityModeration () {
      $this->_sendMailToModeration('community','unlock');
   }

   function _sendMailRoomUnlockToPortalModeration () {
      $this->_sendMailToModeration('portal','unlock');
   }

   /** get UsageInfos
   *   this method returns the usage infos
   *
   *   @return array
   */
   function getUsageInfoArray () {
      $retour = NULL;

        if(( $this->_getExtra('USAGE_INFO_GLOBAL') == 'false')  or (!$this->_issetExtra('USAGE_INFO_GLOBAL')))
        {
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
        } else {
           $retour = array();
           $array = $this->_default_rubrics_array;
           foreach($array as $current)
           {
            $retour[] = $current.'_no';
           }
           $retour[] = 'home_no';
        }

      return $retour;
   }

   /** set UsageInfos
   *   this method sets the usage infos
   *
   *   @param array
   */
   function setUsageInfoArray ($value_array) {
     if (is_array($value_array)){
       $this->_addExtra('USAGE_INFO',$value_array);
     }
   }
   function setUsageInfoGlobal ($value) {
      $this->_addExtra('USAGE_INFO_GLOBAL',$value);
   }


   /** get UsageInfos
   *   this method returns the usage infos
   *
   *   @return array
   */
   function getUsageInfoFormArray () {
      $retour = NULL;
      if(( $this->_getExtra('USAGE_INFO_GLOBAL') == 'false')  or (!$this->_issetExtra('USAGE_INFO_GLOBAL'))) {

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

      } else {
        $retour = array();
        $array = $this->_default_rubrics_array;
        foreach($array as $current)
        {
          $retour[] = $current.'_no';
        }
           $retour[] = 'home_no';
        }
      return $retour;
   }
   /** set UsageInfos
    *  this method sets the usage infos
    *
    * @param array
    */
   function setUsageInfoFormArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM',$value_array);
      }
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
   
   public function renewFileIndex () {
   	$retour = true;
      $indexing_manager = $this->_environment->getFTSearchManager();
      $indexing_manager->setRoomID($this->getItemID());
      $indexing_manager->setPortalID($this->getContextID());
      $indexing_manager->rebuildFTIndex();
      unset($indexing_manager);
   	return $retour;
   }

   ###################
   # RSS HASH
   ###################

   public function getUserByRSSHash ($rss_hash) {
      $retour = NULL;
      $hash_manager = $this->_environment->getHashManager();
      $retour = $hash_manager->getUserByRSSHash($rss_hash);
      unset($hash_manager);
      return $retour;
   }

   public function initTagRootItem () {
      $tag_manager = $this->_environment->getTagManager();
      $tag_root_item = $tag_manager->getRootTagItemFor($this->getItemID());
      if ( isset($tag_root_item) ) {
         $tag_root_item_id = $tag_root_item->getItemID();
      }
      if ( !isset($tag_root_item)
           or empty($tag_root_item_id)
         ) {
         $tag_manager->createRootTagItemFor($this->getItemID());
      }
      unset($tag_root_item);
      unset($tag_manager);
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

   public function isPluginActive ( $plugin ) {
      $retour = false;
      if ( $this->isPluginOn($plugin) ) {
         $portal_item = $this->_environment->getCurrentPortalItem();
         if ( $portal_item->isPluginActive($plugin) ) {
            $retour = true;
         }
      }
      return $retour;
   }

  /** det description array
    *
    * @return array description text in different languages
    */
   function getDescriptionArray () {
      $retour = $this->_getValue('description');
      if(empty($retour)){
         $retour = array();
      }
      return $retour;
   }

   function getDescription () {
      $retour = $this->_getValue('room_description');
      if(empty($retour)){
         $retour = '';
      }
      return $retour;
   }
   function setDescription ($value) {
      $this->_setValue('room_description',$value);
   }

  /** set description array
    *
    * @param array value description text in different languages
    */
   function setDescriptionArray ($value) {
      $this->_setValue('description',(array)$value);
   }

    /**
     * Get the room's slug (a unique textual identifier for this room)
     *
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->_getValue('slug') ?: null;
    }

    /**
     * Set the room's slug (a unique textual identifier for this room)
     *
     * @param string|null $slug
     */
    public function setSlug(?string $slug): void
    {
        $slug = !empty($slug) ? strtolower($slug) : null;

        $this->_setValue('slug', $slug);
    }

   public function isUsed ($start_date, $end_date) {
      $retour = false;

      $user_manager = $this->_environment->getUserManager();
      $user_manager->setContextLimit($this->getItemID());
      $count = $user_manager->getCountUsedAccounts($start_date,$end_date);
      unset($user_manager);
      if ( !empty($count)
           and is_numeric($count)
           and $count > 0
         ) {
         $retour = true;
      } else {
         $item_manager = $this->_environment->getItemManager();
         $item_manager->setContextLimit($this->getItemID());
         $count = $item_manager->getCountItems($start_date,$end_date);
         if ( !empty($count)
              and is_numeric($count)
              and $count > 0
            ) {
            $retour = true;
         }
         unset($item_manager);
      }
      return $retour;
   }

    public function moveToArchive()
    {
        // group rooms in project room
        $type = $this->getRoomType();
        if ($type == CS_PROJECT_TYPE) {
            $this->moveGrouproomsToArchive();
        }
        unset($type);

        // set lastlogin to now
        // cause than the deleting prozess will start now
        // and not at the original last login date
        $this->saveLastlogin();

        $this->close();
        $this->saveWithoutChangingModificationInformation();

        // remove room from elastic index
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_room');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('App:Room');

        $this->deleteElasticItem($objectPersister, $repository);

        $environment = $this->_environment;
        // Managers that need data from other tables
        $hash_manager = $environment->getHashManager();
        $hash_manager->moveFromDbToBackup($this->getItemID());
        unset($hash_manager);

        $link_modifier_item_manager = $environment->getLinkModifierItemManager();
        $link_modifier_item_manager->moveFromDbToBackup($this->getItemID());
        unset($link_modifier_item_manager);

        $link_item_file_manager = $environment->getLinkItemFileManager();
        $link_item_file_manager->moveFromDbToBackup($this->getItemID());
        unset($link_item_file_manager);

        $noticed_manager = $environment->getNoticedManager();
        $noticed_manager->moveFromDbToBackup($this->getItemID());
        unset($noticed_manager);

        $reader_manager = $environment->getReaderManager();
        $reader_manager->moveFromDbToBackup($this->getItemID());
        unset($reader_manager);

        $item_manager = $environment->getItemManager();
        $item_manager->moveFromDbToBackupWorkflow($this->getItemID());
        unset($item_manager);

        // Plain copy of the rest
        $assessment_manager = $environment->getAssessmentManager();
        $assessment_manager->moveFromDbToBackup($this->getItemID());
        unset($assessment_manager);

        $annotation_manager = $environment->getAnnotationManager();
        $annotation_manager->moveFromDbToBackup($this->getItemID());
        unset($annotation_manager);

        $announcement_manager = $environment->getAnnouncementManager();
        $announcement_manager->moveFromDbToBackup($this->getItemID());
        unset($announcement_manager);

        $dates_manager = $environment->getDatesManager();
        $dates_manager->moveFromDbToBackup($this->getItemID());
        unset($dates_manager);

        $discussion_manager = $environment->getDiscussionManager();
        $discussion_manager->moveFromDbToBackup($this->getItemID());
        unset($discussion_manager);

        $discussionarticles_manager = $environment->getDiscussionarticleManager();
        $discussionarticles_manager->moveFromDbToBackup($this->getItemID());
        unset($discussionarticles_manager);

        $file_manager = $environment->getFileManager();
        $file_manager->moveFromDbToBackup($this->getItemID());
        unset($file_manager);

        $item_manager = $environment->getItemManager();
        $item_manager->moveFromDbToBackup($this->getItemID());
        unset($item_manager);

        $labels_manager = $environment->getLabelManager();
        $labels_manager->moveFromDbToBackup($this->getItemID());
        unset($labels_manager);

        $links_manager = $environment->getLinkManager();
        $links_manager->moveFromDbToBackup($this->getItemID());
        unset($links_manager);

        $link_item_manager = $environment->getLinkItemManager();
        $link_item_manager->moveFromDbToBackup($this->getItemID());
        unset($link_item_manager);

        $material_manager = $environment->getMaterialManager();
        $material_manager->moveFromDbToBackup($this->getItemID());
        unset($material_manager);

        $section_manager = $environment->getSectionManager();
        $section_manager->moveFromDbToBackup($this->getItemID());
        unset($section_manager);

        $step_manager = $environment->getStepManager();
        $step_manager->moveFromDbToBackup($this->getItemID());
        unset($step_manager);

        $tag_manager = $environment->getTagManager();
        $tag_manager->moveFromDbToBackup($this->getItemID());
        unset($tag_manager);

        $tag2tag_manager = $environment->getTag2TagManager();
        $tag2tag_manager->moveFromDbToBackup($this->getItemID());
        unset($tag2tag_manager);

        $task_manager = $environment->getTaskManager();
        $task_manager->moveFromDbToBackup($this->getItemID());
        unset($task_manager);

        $todo_manager = $environment->getTodoManager();
        $todo_manager->moveFromDbToBackup($this->getItemID());
        unset($todo_manager);

        $user_manager = $environment->getUserManager();
        $user_manager->moveFromDbToBackup($this->getItemID());
        unset($user_manager);

        $room_manager = $environment->getRoomManager();
        $room_manager->moveFromDbToBackup($this->getItemID());
        unset($room_manager);

        unset($environment);
    }

    public function backFromArchive()
    {
        $environment = $this->_environment;

        // Managers that need data from other tables
        $hash_manager = $environment->getHashManager();
        $hash_manager->moveFromBackupToDb($this->getItemID());
        unset($hash_manager);

        $link_modifier_item_manager = $environment->getLinkModifierItemManager();
        $link_modifier_item_manager->moveFromBackupToDb($this->getItemID());
        unset($link_modifier_item_manager);

        $link_item_file_manager = $environment->getLinkItemFileManager();
        $link_item_file_manager->moveFromBackupToDb($this->getItemID());
        unset($link_item_file_manager);

        $noticed_manager = $environment->getNoticedManager();
        $noticed_manager->moveFromBackupToDb($this->getItemID());
        unset($noticed_manager);

        $reader_manager = $environment->getReaderManager();
        $reader_manager->moveFromBackupToDb($this->getItemID());
        unset($reader_manager);

        $item_manager = $environment->getItemManager();
        $item_manager->moveFromBackupToDbWorkflow($this->getItemID());
        unset($item_manager);

        // Plain copy of the rest
        $assessment_manager = $environment->getAssessmentManager();
        $assessment_manager->moveFromBackupToDb($this->getItemID());
        unset($assessment_manager);

        $annotation_manager = $environment->getAnnotationManager();
        $annotation_manager->moveFromBackupToDb($this->getItemID());
        unset($annotation_manager);

        $announcement_manager = $environment->getAnnouncementManager();
        $announcement_manager->moveFromBackupToDb($this->getItemID());
        unset($announcement_manager);

        $dates_manager = $environment->getDatesManager();
        $dates_manager->moveFromBackupToDb($this->getItemID());
        unset($dates_manager);

        $discussion_manager = $environment->getDiscussionManager();
        $discussion_manager->moveFromBackupToDb($this->getItemID());
        unset($discussion_manager);

        $discussionarticles_manager = $environment->getDiscussionarticleManager();
        $discussionarticles_manager->moveFromBackupToDb($this->getItemID());
        unset($discussionarticles_manager);

        $file_manager = $environment->getFileManager();
        $file_manager->moveFromBackupToDb($this->getItemID());
        unset($file_manager);

        $item_manager = $environment->getItemManager();
        $item_manager->moveFromBackupToDb($this->getItemID());
        unset($item_manager);

        $labels_manager = $environment->getLabelManager();
        $labels_manager->moveFromBackupToDb($this->getItemID());
        unset($labels_manager);

        $links_manager = $environment->getLinkManager();
        $links_manager->moveFromBackupToDb($this->getItemID());
        unset($links_manager);

        $link_item_manager = $environment->getLinkItemManager();
        $link_item_manager->moveFromBackupToDb($this->getItemID());
        unset($link_item_manager);

        $material_manager = $environment->getMaterialManager();
        $material_manager->moveFromBackupToDb($this->getItemID());
        unset($material_manager);

        $section_manager = $environment->getSectionManager();
        $section_manager->moveFromBackupToDb($this->getItemID());
        unset($section_manager);

        $step_manager = $environment->getStepManager();
        $step_manager->moveFromBackupToDb($this->getItemID());
        unset($step_manager);

        $tag_manager = $environment->getTagManager();
        $tag_manager->moveFromBackupToDb($this->getItemID());
        unset($tag_manager);

        $tag2tag_manager = $environment->getTag2TagManager();
        $tag2tag_manager->moveFromBackupToDb($this->getItemID());
        unset($tag2tag_manager);

        $task_manager = $environment->getTaskManager();
        $task_manager->moveFromBackupToDb($this->getItemID());
        unset($task_manager);

        $todo_manager = $environment->getTodoManager();
        $todo_manager->moveFromBackupToDb($this->getItemID());
        unset($todo_manager);

        $user_manager = $environment->getUserManager();
        $user_manager->moveFromBackupToDb($this->getItemID());
        unset($user_manager);

        $room_manager = $environment->getRoomManager();
        $room_manager->moveFromBackupToDb($this->getItemID());
        unset($room_manager);

        // move a project room's group rooms last, as these require users to be moved already
        $type = $this->getRoomType();
        if ($type == CS_PROJECT_TYPE) {
            $this->backGrouproomsFromArchive();
        }
        unset($type);

        unset($environment);

        $this->open();
        $this->saveWithoutChangingModificationInformation();

        // set lastlogin to now
        // cause than the archiving prozess will start now
        // and not at the original last login date
        $this->saveLastlogin();

        // add room to elastic index
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
   
   // archiving
   public function saveLastlogin ( $datetime = '' ) {
   	$retour = false;
      if ( $this->isProjectRoom() ) {
         $manager = $this->_environment->getProjectManager();
      } elseif ( $this->isGroupRoom() ) {
         $manager = $this->_environment->getGrouproomManager();
      } elseif ( $this->isUserroom() ) {
          $manager = $this->_environment->getUserRoomManager();
      } elseif ( $this->isCommunityRoom() ) {
         $manager = $this->_environment->getCommunityManager();
      } elseif ( $this->isPrivateRoom() ) {
         $manager = $this->_environment->getPrivateRoomManager();
      }
      if ( isset($manager) ) {
         $retour = $manager->saveLastLogin($this,$datetime);
      }
      return $retour;
   }   

   public function getArchiveMailSendDateTime () {
      $retour = '';
      if ( $this->_issetExtra('ARCHIVE_SEND_MAIL_DATETIME') ) {
         $retour = $this->_getExtra('ARCHIVE_SEND_MAIL_DATETIME');
      }
      return $retour;
   }

   public function setArchiveMailSendDateTime ($value) {
      $this->_addExtra('ARCHIVE_SEND_MAIL_DATETIME',$value);
   }

    public function sendMailArchiveInfoToModeration()
    {
        $translator = $this->_environment->getTranslationObject();
        $default_language = 'de';

        global $symfonyContainer;
        $default_sender_address = $symfonyContainer->getParameter('commsy.email.from');

        /** @var \cs_portal_item $current_portal */
        $current_portal = $this->getContextItem();
        $current_user = $this->_environment->getCurrentUserItem();
        $fullname = $current_user->getFullname();
        if (empty($fullname)) {
            $mod_list = $current_portal->getContactModeratorList();
            if (empty($mod_list)
                or $mod_list->isNotEmpty()
            ) {
                $mod_list = $current_portal->getContactModeratorList();
            }
            if (!empty($mod_list)
                and $mod_list->isNotEmpty()
            ) {
                $current_user = $mod_list->getFirst();
            }
            unset($mod_list);
        }

        $moderator_list = $this->getModeratorList();

        // get moderators
        $receiver_array = array();
        $moderator_name_array = array();

        if ($moderator_list->isNotEmpty()) {
            $mod_item = $moderator_list->getFirst();
            while ($mod_item) {
                if ($mod_item->getOpenRoomWantMail() == 'yes') {
                    $language = $this->getLanguage();
                    if ($language == 'user') {
                        $language = $mod_item->getLanguage();
                        if ($language == 'browser') {
                            $language = $default_language;
                        }
                    }

                    $modEmail = $mod_item->getEmail();
                    $validator = new \Egulias\EmailValidator\EmailValidator();

                    if ($validator->isValid($modEmail, new \Egulias\EmailValidator\Validation\RFCValidation())) {
                        $receiver_array[$language][] = $modEmail;
                        $moderator_name_array[] = $mod_item->getFullname();
                    }
                }
                $mod_item = $moderator_list->getNext();
            }
        }

        // now email information
        foreach ($receiver_array as $language => $emailArray) {
            $save_language = $translator->getSelectedLanguage();
            $translator->setSelectedLanguage($language);
            $subject = '';
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_ARCHIVE_INFO', str_ireplace('&amp;', '&', $this->getTitle()), $current_portal->getDaysSendMailBeforeArchivingRooms());

            $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
            $body .= LF . LF;
            if ($this->isCommunityRoom()) {
                $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_ARCHIVE_INFO', $this->getTitle(), $current_portal->getDaysSendMailBeforeArchivingRooms(), ($current_portal->getDaysUnusedBeforeArchivingRooms() - $current_portal->getDaysSendMailBeforeArchivingRooms()));
            } else {
                $body .= $translator->getEmailMessage('PROJECT_MAIL_BODY_ARCHIVE_INFO', $this->getTitle(), $current_portal->getDaysSendMailBeforeArchivingRooms(), ($current_portal->getDaysUnusedBeforeArchivingRooms() - $current_portal->getDaysSendMailBeforeArchivingRooms()));
            }
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_ARCHIVE_INFO');

            $body .= LF . LF;
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_INFORMATION', str_ireplace('&amp;', '&', $this->getTitle()), $current_user->getFullname(), $room_change_action);

            // set new commsy url
            global $symfonyContainer;

            /** @var \Symfony\Component\Routing\RouterInterface $router */
            $router = $symfonyContainer->get('router');
            $url = $router->generate('app_room_home', [
                'roomId' => $this->getItemID(),
            ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

            $body .= LF . $url;

            if ($this->isProjectRoom()) {
                $community_name_array = array();
                $community_list = $this->getCommunityList();
                if ($community_list->isNotEmpty()) {
                    $community_item = $community_list->getFirst();
                    while ($community_item) {
                        $community_name_array[] = $community_item->getTitle();
                        unset($community_item);
                        $community_item = $community_list->getNext();
                    }
                }
                unset($community_list);
                if (!empty($community_name_array)) {
                    $body .= LF . LF;
                    $body .= $translator->getMessage('PROJECT_MAIL_BODY_COMMUNITIY_ROOMS') . LF;
                    $body .= implode(LF, $community_name_array);
                }
            }

            $body .= LF . LF;
            $body .= $translator->getMessage('MAIL_SEND_TO', implode(LF, $moderator_name_array));
            $body .= LF . LF;
            if ($this->isCommunityRoom()) {
                $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY', $this->getTitle());
            } else {
                $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT', $this->getTitle());
            }

            // send email
            include_once('classes/cs_mail.php');
            $mail = new cs_mail();
            $mail->set_to(implode(',', $emailArray));
            $mail->set_from_email($default_sender_address);
            if (isset($current_portal)) {
                $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $current_portal->getTitle()));
            } else {
                $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $this->getTitle()));
            }
            $mail->set_reply_to_name($current_user->getFullname());
            $mail->set_reply_to_email($current_user->getEmail());
            $mail->set_subject($subject);
            $mail->set_message($body);

            $translator->setSelectedLanguage($save_language);
            return $mail->send();
        }

        return false;
    }

   public function getDeleteMailSendDateTime () {
   	$retour = '';
   	if ( $this->_issetExtra('DELETE_SEND_MAIL_DATETIME') ) {
   		$retour = $this->_getExtra('DELETE_SEND_MAIL_DATETIME');
   	}
   	return $retour;
   }
   
   public function setDeleteMailSendDateTime ($value) {
   	$this->_addExtra('DELETE_SEND_MAIL_DATETIME',$value);
   }

    public function sendMailDeleteInfoToModeration()
    {
        $translator = $this->_environment->getTranslationObject();
        $default_language = 'de';

        $toggle_archive = false;
        if ($this->_environment->isArchiveMode()) {
            $toggle_archive = true;
            $this->_environment->toggleArchiveMode();
        }

        global $symfonyContainer;
        $default_sender_address = $symfonyContainer->getParameter('commsy.email.from');

        $current_portal = $this->getContextItem();
        $current_user = $this->_environment->getCurrentUserItem();
        $fullname = $current_user->getFullname();
        if (empty($fullname)) {
            $mod_list = $current_portal->getContactModeratorList();
            if (empty($mod_list)
                or $mod_list->isNotEmpty()
            ) {
                $mod_list = $current_portal->getContactModeratorList();
            }
            if (!empty($mod_list)
                and $mod_list->isNotEmpty()
            ) {
                $current_user = $mod_list->getFirst();
            }
            unset($mod_list);
        }

        if ($toggle_archive) {
            $this->_environment->toggleArchiveMode();
        }
        unset($toggle_archive);

        $moderator_list = $this->getModeratorList();

        // get moderators
        $receiver_array = array();
        $moderator_name_array = array();

        if ($moderator_list->isNotEmpty()) {
            $mod_item = $moderator_list->getFirst();
            while ($mod_item) {
                if ($mod_item->getOpenRoomWantMail() == 'yes') {
                    $language = $this->getLanguage();
                    if ($language == 'user') {
                        $language = $mod_item->getLanguage();
                        if ($language == 'browser') {
                            $language = $default_language;
                        }
                    }

                    $modEmail = $mod_item->getEmail();
                    $validator = new \Egulias\EmailValidator\EmailValidator();

                    if ($validator->isValid($modEmail, new \Egulias\EmailValidator\Validation\RFCValidation())) {
                        $receiver_array[$language][] = $modEmail;
                        $moderator_name_array[] = $mod_item->getFullname();
                    }
                }
                $mod_item = $moderator_list->getNext();
            }
        }

        // now email information
        foreach ($receiver_array as $language => $emailArray) {
            $save_language = $translator->getSelectedLanguage();
            $translator->setSelectedLanguage($language);
            $subject = '';
            $subject .= $translator->getMessage('PROJECT_MAIL_SUBJECT_DELETE_INFO', str_ireplace('&amp;', '&', $this->getTitle()), $current_portal->getDaysSendMailBeforeDeletingRooms());

            $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
            $body .= LF . LF;
            if ($this->isCommunityRoom()) {
                $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_DELETE_INFO', $this->getTitle(), $current_portal->getDaysSendMailBeforeDeletingRooms(), ($current_portal->getDaysUnusedBeforeDeletingRooms() - $current_portal->getDaysSendMailBeforeDeletingRooms()));
            } else {
                $body .= $translator->getEmailMessage('PROJECT_MAIL_BODY_DELETE_INFO', $this->getTitle(), $current_portal->getDaysSendMailBeforeDeletingRooms(), ($current_portal->getDaysUnusedBeforeDeletingRooms() - $current_portal->getDaysSendMailBeforeDeletingRooms()));
            }
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_DELETE_INFO');

            $body .= LF . LF;
            $body .= $translator->getMessage('PROJECT_MAIL_BODY_INFORMATION', str_ireplace('&amp;', '&', $this->getTitle()), $current_user->getFullname(), $room_change_action);

            // set new commsy url
            global $symfonyContainer;

            /** @var \Symfony\Component\Routing\RouterInterface $router */
            $router = $symfonyContainer->get('router');
            $url = $router->generate('app_room_home', [
                'roomId' => $this->getItemID(),
            ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

            $body .= LF . $url;

            if ($this->isProjectRoom()) {
                $community_name_array = array();
                $community_list = $this->getCommunityList();
                if ($community_list->isNotEmpty()) {
                    $community_item = $community_list->getFirst();
                    while ($community_item) {
                        $community_name_array[] = $community_item->getTitle();
                        unset($community_item);
                        $community_item = $community_list->getNext();
                    }
                }
                unset($community_list);
                if (!empty($community_name_array)) {
                    $body .= LF . LF;
                    $body .= $translator->getMessage('PROJECT_MAIL_BODY_COMMUNITIY_ROOMS') . LF;
                    $body .= implode(LF, $community_name_array);
                }
            }

            $body .= LF . LF;
            $body .= $translator->getMessage('MAIL_SEND_TO', implode(LF, $moderator_name_array));
            $body .= LF . LF;
            if ($this->isCommunityRoom()) {
                $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY', $this->getTitle());
            } else {
                $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT', $this->getTitle());
            }

            // send email
            include_once('classes/cs_mail.php');
            $mail = new cs_mail();
            $mail->set_to(implode(',', $emailArray));
            $mail->set_from_email($default_sender_address);
            if (isset($current_portal)) {
                $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $current_portal->getTitle()));
            } else {
                $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $this->getTitle()));
            }
            $mail->set_reply_to_name($current_user->getFullname());
            $mail->set_reply_to_email($current_user->getEmail());
            $mail->set_subject($subject);
            $mail->set_message($body);

            $translator->setSelectedLanguage($save_language);
            return $mail->send();
        }

        return false;
    }
    
  /** get lastlogin of a context
   * this method returns the last login date of the context
   *
   * @return string lastlogin of a context
   */
   public function getLastLogin () {
      return $this->_getValue('lastlogin');
   }
   
   public function isActiveDuringLast99Days () {
      include_once('functions/date_functions.php');
      return $this->getLastLogin() >= getCurrentDateTimeMinusDaysInMySQL(99);
   }
}