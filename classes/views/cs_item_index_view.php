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

$this->includeClass(INDEX_VIEW);

/**
 *  class for CommSy index list view: index
 */
class cs_item_index_view extends cs_index_view {

var $_max_activity = NULL;

var $_first_announcement = true;
var $_first_institution = true;
var $_first_group = true;
var $_first_material = true;
var $_first_topic = true;
var $_first_date = true;
var $_first_todo = true;
var $_first_discussion = true;
var $_first_user = true;
var $_first_project = true;
var $_first_myroom = true;
var $_selected_rubric = NULL;
var $_selected_restriction = NULL;

   /** constructor: cs_item_list_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_item_index_view ($params) {
      $params['viewname'] = 'campus_search_index';
      $this->cs_index_view($params);
      $this->institution = $this->_translator->getMessage('INSTITUTION');
   }

   function _getTableheadAsHTML () {
      $html = '';
#      $html  = '   <tr class="list">'.LF;
#      $html .= '      <td class="head" colspan="5" style="vertical-align:middle;">&nbsp;'.LF;
#      $html .= '      </td>'.LF;
#      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      $html .= '      <td class="head" colspan="5" style="vertical-align:middle;">&nbsp;'.LF;
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function setSelectedRubric ($rubric) {
      $this->_selected_rubric = $rubric;
   }

   function getSelectedRubric () {
      return $this->_selected_rubric;
   }

   function setSelectedFile ($rubric) {
      $this->_selected_file = $rubric;
   }

   function getSelectedFile () {
      return $this->_selected_file;
   }

   function setSelectedRestriction ($restriction) {
      $this->_selected_restriction = $restriction;
   }

   function getSelectedRestriction () {
      return $this->_selected_restriction;
   }

   function setLongTitle(){
      $title = $this->_translator->getMessage('COMMON_SEARCH');
      $this->setTitle($title);
   }

   function _getListInfosAsHTML ($title) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= '<div class="index_forward_links" style="white-space:nowrap;">'.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<div class="right_box_main" >'.LF;
      $html .= $this->_getRestrictionTextAsHTML();
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

     return $html;
   }

   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      if ( isset($params['only_files']) and $params['only_files'] == 1){
         $this->_additional_selects = true;
         $html_text ='<div class="restriction" style="width:100%;">';
         $module = $this->_environment->getCurrentModule();
         $html_text .= '<span class="infocolor">'.getMessage('COMMON_ENTRIES').': </span> ';
         $html_text .= '<span><a title="'.getMessage('COMMON_ONLY_FILES').'">'.getMessage('COMMON_ONLY_FILES').'</a></span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         unset($new_params['only_files']);
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</div>';
         $html .= $html_text;
      }
      if ( isset($params['selrubric']) and $params['selrubric'] != 'all'){
         $this->_additional_selects = true;
         $html_text ='<div class="restriction" style="width:100%;">';
         $module = $this->_environment->getCurrentModule();
         $html_text .= '<span class="infocolor">'.getMessage('COMMON_RUBRIC').': </span> ';
         $tempMessage = '';
         switch ( strtoupper($params['selrubric']) ) {
            case 'ANNOUNCEMENT':
               $tempMessage = getMessage('ANNOUNCEMENT_INDEX');
               break;
            case 'DATE':
               $tempMessage = getMessage('DATE_INDEX');
               break;
            case 'DISCUSSION':
               $tempMessage = getMessage('DISCUSSION_INDEX');
               break;
            case 'INSTITUTION':
               $tempMessage = getMessage('INSTITUTION_INDEX');
               break;
            case 'GROUP':
               $tempMessage = getMessage('GROUP_INDEX');
               break;
            case 'MATERIAL':
               $tempMessage = getMessage('MATERIAL_INDEX');
               break;
            case 'MYROOM':
               $tempMessage = getMessage('MYROOM_INDEX');
               break;
            case 'PROJECT':
               $tempMessage = getMessage('PROJECT_INDEX');
               break;
            case 'TODO':
               $tempMessage = getMessage('TODO_INDEX');
               break;
            case 'TOPIC':
               $tempMessage = getMessage('TOPIC_INDEX');
               break;
            case 'USER':
               $tempMessage = getMessage('USER_INDEX');
               break;
            default:
               $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR'.' cs_index_view(685) ');
               break;
         }
         $html_text .= '<span><a title="'.$tempMessage.'">'.$tempMessage.'</a></span>';

         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         unset($new_params['selrubric']);
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</div>';
         $html .= $html_text;
      }
      return $html;
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    *
    * @author CommSy Development Group
    */
   function _getContentAsHTML() {
      $html = '';
      $list = $this->_list;
      if(isset($_GET['mode']) and $_GET['mode']=='print'){
         $this->_with_checkboxes = false;
      }
      if ( !isset($list) || $list->isEmpty() ) {
         $html .= '   <tr class="list">'.LF;
         $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
         $html .= '&nbsp;';
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
         $html .= '<tr  class="list"><td class="odd" colspan="'.$this->_colspan.'" style="border-bottom: 0px;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      } else {
         $current_item = $list->getFirst();
         $i = 0;
         while ( $current_item ) {
            $html .= $this->_getItemAsHTML($current_item, $i++);
            $current_item = $list->getNext();
         }
      }
      return $html;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method from the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item,$pos) {
      $html = '';
      $shown_entry_number = $pos;
      $shown_entry_number = $pos + $this->_count_headlines;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $item_type = $item->getType();
      if ($item_type == 'label'){
         $item_type = $item->getItemType();
      }
      switch ($item_type){
         case CS_ANNOUNCEMENT_TYPE:
            if ($this->_first_announcement){
              $this->_first_announcement = false;
              $html .= '   <tr class="list">'.LF;
              $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
              $html .= $this->_translator->getMessage('COMMON_ANNOUNCEMENTS');
              $html .= '</td>'.LF;
            }
            $html .= $this->_getAnnouncementItemAsLongHtml($item,$style);
            break;
         case CS_DATE_TYPE:
            if ($this->_first_date){
              $this->_first_date = false;
              $html .= '   <tr class="list">'.LF;
              $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
              $html .= $this->_translator->getMessage('COMMON_DATES_PL');
              $html .= '</td>'.LF;
            }
            $html .= $this->_getDateItemAsLongHtml($item,$style);
            break;
         case CS_DISCUSSION_TYPE:
            if ($this->_first_discussion){
              $this->_first_discussion = false;
              $html .= '   <tr class="list">'.LF;
              $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
              $html .= $this->_translator->getMessage('COMMON_DISCUSSIONS');
              $html .= '</td>'.LF;
            }
            $html .= $this->_getDiscussionItemAsLongHtml($item,$style);
            break;
         case CS_TODO_TYPE:
            if ($this->_first_todo){
              $this->_first_todo = false;
              $html .= '   <tr class="list">'.LF;
              $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
              $html .= $this->_translator->getMessage('COMMON_TODOS');
              $html .= '</td>'.LF;
            }
            $html .= $this->_getToDoItemAsLongHtml($item,$style);
            break;
         case CS_USER_TYPE:
            if ($this->_first_user){
              $this->_first_user = false;
              $html .= '   <tr class="list">'.LF;
              $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
              $html .= $this->_translator->getMessage('COMMON_USERS');
              $html .= '</td>'.LF;
            }
            $html .= $this->_getUserItemAsLongHtml($item,$style);
            break;
         case CS_PROJECT_TYPE:
            if ( $this->_environment->inPrivateRoom() ){
               if ($this->_first_myroom){
                 $this->_first_myroom = false;
                 $html .= '   <tr class="list">'.LF;
                 $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
                 $html .= $this->_translator->getMessage('MYROOM_INDEX');
                 $html .= '</td>'.LF;
               }
               $html .= $this->_getMyRoomItemAsLongHtml($item,$style);
            } else {
               if ($this->_first_project){
                 $this->_first_project = false;
                 $html .= '   <tr class="list">'.LF;
                 $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
                 $html .= $this->_translator->getMessage('PROJECT_INDEX');
                 $html .= '</td>'.LF;
               }
               $html .= $this->_getProjectItemAsLongHtml($item,$style);
            }
            break;
         case CS_COMMUNITY_TYPE:
            if ( $this->_environment->inPrivateRoom() ){
               if ($this->_first_myroom){
                 $this->_first_myroom = false;
                 $html .= '   <tr class="list">'.LF;
                 $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
                 $html .= $this->_translator->getMessage('MYROOM_INDEX');
                 $html .= '</td>'.LF;
               }
               $html .= $this->_getMyRoomItemAsLongHtml($item,$style);
            } else {
               if ($this->_first_project){
                 $this->_first_project = false;
                 $html .= '   <tr class="list">'.LF;
                 $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
                 $html .= $this->_translator->getMessage('PROJECT_INDEX');
                 $html .= '</td>'.LF;
               }
               $html .= $this->_getProjectItemAsLongHtml($item,$style);
            }
            break;
         case CS_TOPIC_TYPE:
            if ($this->_first_topic){
              $this->_first_topic = false;
              $html .= '   <tr class="list">'.LF;
              $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
              $html .= $this->_translator->getMessage('COMMON_TOPICS');
              $html .= '</td>'.LF;
            }
            $html .= $this->_getTopicItemAsLongHtml($item,$style);
            break;
         case CS_GROUP_TYPE:
            if ($this->_first_topic){
              $this->_first_topic = false;
              $html .= '   <tr class="list">'.LF;
              $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
              $html .= $this->_translator->getMessage('COMMON_GROUPS');
              $html .= '</td>'.LF;
            }
            $html .= $this->_getTopicItemAsLongHtml($item,$style);
            break;
         case CS_INSTITUTION_TYPE:
            if ($this->_first_institution){
              $this->_first_institution = false;
              $html .= '   <tr class="list">'.LF;
              $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
              $html .= $this->_translator->getMessage('INSTITUTIONS');
              $html .= '</td>'.LF;
            }
            $html .= $this->_getInstitutionItemAsLongHtml($item,$style);
            break;
         case CS_MATERIAL_TYPE:
            if ($this->_first_material){
              $this->_first_material = false;
              $html .= '   <tr class="list">'.LF;
              $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="4">';
              $html .= $this->_translator->getMessage('COMMON_MATERIALS');
              $html .= '</td>'.LF;
            }
            $html .= $this->_getMaterialItemAsLongHtml($item,$style);
            break;
      }
      return $html;
   }

   function _getAnnouncementItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;" colspan="2">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:25%;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:15%;">'.$this->_getItemModificationDate($item).'</td>'.LF;
      return $html;
   }

   /** get the date of the item
    * this method returns the item date in the right formatted style
    *
    * @return string title
    */
   function _getDateInLang($item){
      $original_date = $item->getDate();
      $date = getDateInLang($original_date);
      $date = $this->_compareWithSearchText($date);
      $status = $item->getStatus();
      $actual_date = date("Y-m-d H:i:s");
      if ($status !=$this->_translator->getMessage('TODO_DONE') and $original_date < $actual_date){
         $date = '<span class="required">'.$date.'</span>';
      }
      return $date;
   }

   /** get the status of the item
    * this method returns the item date in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getStatus($item){
      $status = $item->getStatus();
      $status = $this->_compareWithSearchText($status);
      return $status;
   }

   function _getToDoItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;" colspan="2">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:20%;">'.$this->_getDateInLang($item).LF;
      $html .='</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:20%;">'.$this->_getStatus($item).'</td>'.LF;
      return $html;
   }

   /** get article count of a discussion
    * Returns the total and unread number of articles
    * for a discussion-item in a formatted string.
    *
    * @return string article_count
    *
    * @author CommSy Development Group
    */
   function _getItemArticleCount ($item) {
     $array = $item->getAllAndUnreadArticles();
     return $array['count'].' ('.$array['unread'].' <span class="desc">'.$this->_translator->getMessage('COMMON_UNREAD').'</span>)';
   }

   /** get the date of last added article
    * this method returns the number in the right formatted style
    *
    * @return date last_article_date
    *
    * @author CommSy Development Group
    */
   function _getItemLastArticleDate ($item) {
     $last_article_date = $item->getLatestArticleModificationDate();
     $last_article_date = getDateInLang($last_article_date);
     return $this->_text_as_html_short($last_article_date);
   }

   function _getDiscussionItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;" colspan="2">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:20%;">'.$this->_getItemArticleCount($item).LF;
      $html .='</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:20%;">'.$this->_getItemLastArticleDate($item).'</td>'.LF;
      return $html;
   }


   function _getDateItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;" colspan="2">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:20%;">'.$this->_getItemDate($item).LF;
      $time = $this->_getItemTime($item);
      $starting_time = $item->getStartingTime();
      if (!empty($time) and !empty($starting_time)) {
         $html .= ', '.$time;
      }
      $html .='</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:25%;">'.$this->_getItemPlace($item).'</td>'.LF;
      return $html;
   }

   /** get the place of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemPlace($item){
      $place = $item->getPlace();
      if ($item->issetPrivatDate()){
         $title ='<i>'.$this->_text_as_html_short($place).'</i>';
      }else{
         $place = $this->_compareWithSearchText($place);
      }
      return $this->_text_as_html_short($place);
   }

   /** get the time of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTime($item){
      $parse_time_start = convertTimeFromInput($item->getStartingTime());
      $conforms = $parse_time_start['conforms'];
      if ($conforms == TRUE) {
         $time = getTimeLanguage($parse_time_start['datetime']);
      } else {
         $time = $item->getStartingTime();
      }
      if ($item->issetPrivatDate()){
         $time ='<i>'.$this->_text_as_html_short($time).'</i>';
      }else{
         $time = $this->_text_as_html_short($this->_compareWithSearchText($time));
      }
      return $time;
   }

   /** get the date of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemDate($item){
      $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
      $conforms = $parse_day_start['conforms'];
      if ($conforms == TRUE) {
         $date = $this->_translator->getDateInLang($parse_day_start['datetime']);
      } else {
         $date = $item->getStartingDay();
      }
      $date = $this->_compareWithSearchText($date);
      if ($item->issetPrivatDate()){
         $date ='<i>'.$this->_text_as_html_short($date).'</i>';
         return $date;
      }else{
         return $this->_text_as_html_short($date);
      }
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getProjectItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;" colspan="2">'.$this->_getProjectTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:25%;">'.$this->_getProjectModerator($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:15%;">'.$this->_getProjectActivity($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getMyRoomItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;" colspan="2">'.$this->_getProjectTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:40%;">'.$this->_getProjectModerator($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:15%;"> </td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   /** get the projektroom of the item
    * this method returns the item projektroom in the right formatted style
    *
    * @return string name of projektroom
    *
    * @author CommSy Development Group
    */
   function _getItemProjectRoom($item) {
         $project_list = $item->getProjectList();
         if ($project_list->getCount() == 0) {
            $project_string = $this->_translator->getMessage('CONTEXT_NO_ROOM');
         } else {
            $project_string = '';
            $project = $project_list->getFirst();
            $first = true;
            while ($project) {
               if ($first) {
                  $first = false;
               } else {
                  $project_string .= ', ';
               }
               $projektroom_string = $project->getTitle();
               $projektroom_string = $this->_compareWithSearchText($projektroom_string);
               $user = $this->_environment->getCurrentUser();
               $params = array();
               $params['iid'] = $project->getItemID();
               $current_context = $this->_environment->getCurrentContextItem();
               if ($current_context->withRubric(CS_PROJECT_TYPE)) {
                  $context_id = $this->_environment->getCurrentContextID();
               } else {
                  $context_id = $this->_environment->getCurrentPortalID();
               }
               $projectroom_link = ahref_curl( $context_id,
                           CS_PROJECT_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($projektroom_string));
               unset($params);
               $project_string .= $projectroom_link;
               if ($project->isClosed()) {
                  $project_string .= ' <span class="list_view_description">'.$this->_translator->getMessage('PROJECTROOM_CLOSED').'</span>';
               } elseif ($project->isLocked()) {
                  $project_string .= ' <span class="list_view_description">'.$this->_translator->getMessage('PROJECTROOM_LOCKED').'</span>';
               }
               $project = $project_list->getNext();
            }
         }
         $project_string = $this->_compareWithSearchText($project_string);
         return $project_string;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getMaterialItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $html .= '      <td colspan="2" '.$style.' style="font-size:10pt; width:62%;">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;width:25%;">'.$this->_getItemAuthor($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:13%;">'.$this->_getItemModificationDate($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }


   /** get the author of the item
    * this method returns the item author in the right formatted style
    *
    * @return string author
    *
    * @author CommSy Development Group
    */
   function _getItemAuthor($item){
         $author = $item->getAuthor();
         $author = $this->_compareWithSearchText($author);
         return $this->_text_as_html_short($author);
   }

   /** get the publishing date of the item
    * this method returns the item publishing date in the right formatted style
    *
    * @return string publishing date
    *
    * @author CommSy Development Group
    */
   function _getItemPublishingDate($item){
      $publishing_date = $this->_compareWithSearchText($item->getPublishingDate());
//      $publishing_date = '('.$publishing_date.')';
      return $publishing_date;
   }

   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles ($item) {
      $retour = '';
      $file_list='';
      if ( $item->isA(CS_DISCUSSION_TYPE) ) {
         $files = $item->getFileListWithFilesFromArticles();
      } elseif ( $item->isA(CS_MATERIAL_TYPE) ) {
         $files = $item->getFileListWithFilesFromSections();
      } else {
         $files = $item->getFileList();
      }
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while($file){
         $url = $file->getUrl();
         $displayname = $this->_text_as_html_short($file->getDisplayName());
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser() ))) {
            if ( isset($_GET['mode']) and $_GET['mode']=='print' ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
               if ( stristr(strtolower($file->getFilename()),'png')
                 or stristr(strtolower($file->getFilename()),'jpg')
                 or stristr(strtolower($file->getFilename()),'jpeg')
                 or stristr(strtolower($file->getFilename()),'gif')
               ) {
                   $this->_with_slimbox = true;
                   $file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
               }else{
                  $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
               }
            }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon." ".$displayname.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      return /*$this->_text_as_html_short(*/$retour.$file_list;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getUserItemAsLongHTML($item, $style) {
      $phone = $this->_compareWithSearchText($item->getTelephone());
      $handy = $this->_compareWithSearchText($item->getCellularphone());
      $html  = '   <tr>'.LF;
      $html .= '      <td colspan="2" '.$style.' style="font-size:10pt; width:40%;">'.$this->_getItemFullname($item).'</td>'.LF;
      $html .= '      <td  '.$style.' style="font-size:8pt; width:35%;">'.$this->_getItemEmail($item).'</td>'.LF;
      $html .= '      <td  '.$style.' style="font-size:8pt; width:25%;">'.$this->_text_as_html_short($phone).LF;
      if (!empty($handy)){
         $html .= BRLF.$this->_text_as_html_short($handy).'</td>'.LF;
      }else{
         $html .='</td>'.LF;
      }
      $html .= '   </tr>'.LF;
      return $html;
   }

   /** get the name of the item
    * this method returns the item name in the right formatted style
    *
    * @return string name
    *
    * @author CommSy Development Group
    */
   function _getItemFullName($item){
      $name = $item->getFullname();

      $name_text = $this->_compareWithSearchText($name);
      $params = array();
      $params['iid'] = $item->getItemID();
      $name = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_USER_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($name_text));
      unset($params);
      return $name;
   }

   /** get the email of the item
    * this method returns the item email in the right formatted style
    *
    * @return string email
    *
    * @author CommSy Development Group
    */
   function _getItemEmail($item){
      $email = $item->getEmail();
      $email_text = $this->_compareWithSearchText($email);
      $email = curl_mailto( $item->getEmail(), $this->_text_as_html_short($email_text));
      return $email;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getTopicItemAsLongHTML($item, $style) {
      $html = '   <tr>'.LF;
      $html .= '      <td   '.$style.' style="font-size:10pt; width:70%;" colspan="3">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td   '.$style.' style="font-size:8pt; width:30%;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getInstitutionItemAsLongHTML($item, $style) {
      $html = '   <tr>'.LF;
      $html .= '      <td   '.$style.' style="font-size:10pt; width:70%;" colspan="3">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td   '.$style.' style="font-size:8pt; width:30%;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

//user
   /** get the email of the item
    * this method returns the item email in the right formatted style
    *
    * @return string email
    *
    * @author CommSy Development Group
    */
   function _getEmail($item){
      $email = $item->getEmail();
      $email_text = $this->_compareWithSearchText($email);
      $email = curl_mailto( $item->getEmail(), $email_text);
      return $email;
   }

//topics

   /** get the time of the last change
    * this method returns the item last change time in the right formatted style
    *
    * @return string last change
    *
    * @author CommSy Development Group
    */
   function _getLastChanged($item) {
      $last_item = $item->getLastChangedItem();
      if(!empty($last_item) and is_object($last_item)) {
         $last_modificated = $last_item->getModificationDate();
      }
      $item_modificated = $item->getModificationDate();
      $time_text = '';
      if(!empty($last_modificated) and !empty($item_modificated)) {
         if($last_modificated > $item_modificated) {
            $time = $last_modificated;
         } else {
            $time = $item_modificated;
         }
      } else if(!empty($item_modificated)) {
         $time = $item_modificated;
      } else if(!empty($last_modificated)) {
         $time = $last_modificated;
      }
      if (!empty($time)) {
         $time_text = getDateTimeInLang($time);
         $time_text = $this->_compareWithSearchText($time_text);
         $time_text = '<span class="list_view_description">'.$this->_translator->getMessage('COMMON_LAST_CHANGE').': </span>'.' '.$time_text;
      }
      return $time_text;
   }

   /** get the last change item
    * this method returns the item last change item in the right formatted style
    *
    * @return string last change item
    *
    * @author CommSy Development Group
    */
   function _getLastChangedItem ($item) {
      $newestItem = $item->getLastChangedItem();
      $text = $item_link = '';
      if ( is_null($newestItem) || !is_object($newestItem) ) {
         $text = $this->_translator->getMessage('COMMON_ENTRY_NEW');
      } else {
         $latestItemType = $newestItem->getType();
         switch($latestItemType) {
            case "material":
               if ( $newestItem->getModificationDate() == $newestItem->getCreationDate() ) {
                  if ($newestItem->getVersionID() == '0') {
                     $text = $this->_translator->getMessage('COMMON_NEW_MATERIAL').': ';
                  } else {
                     $text = $this->_translator->getMessage('COMMON_NEW_VERSION_MATERIAL').': ';
                  }
               } else {
                  $text = $this->_translator->getMessage('COMMON_CHANGED_MATERIAL').": ";
               }
               $params = array();
               $params['iid'] = $newestItem->getItemID();
               $item_link = ahref_curl($this->_environment->getCurrentContextID(), 'material', 'detail', $params,$newestItem->getTitle());
               unset($params);
               break;
            case "annotation": // need this case ??? (TBD)
               if ( $newestItem->getModificationDate() == $newestItem->getCreationDate() ) {
                  $text = $this->_translator->getMessage('COMMON_NEW_ANNOTATION').': ';
               } else {
                  $text = $this->_translator->getMessage('COMMON_CHANGED_ANNOTATION').': ';
               }
               $params = array();
               $params['iid'] = $newestItem->getItemID();
               $item_link = ahref_curl($this->_environment->getCurrentContextID(), 'annotation', 'detail', $params,$newestItem->getTitle());
               unset($params);
               break;
            default:
               include_once('functions/error_functions.php');trigger_error("Unknown latest item type: ".$latestItemType, E_USER_ERROR);
         }
      }
      $newest_item = '<span class="list_view_description">'.
                          $text.
                          '</span>'.$item_link;
      return $newest_item;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle ($item) {
      $title = $item->getTitle();
      $title = $this->_compareWithSearchText($title);
      $module = $item->getItemType();
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $module,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title));
      unset($params);
      $title .= $this->_getItemChangeStatus($item);
      $title .= $this->_getItemAnnotationChangeStatus($item);
      $title .= ' '.$this->_getItemFiles($item);
      return $title;
   }

   function _getAdditionalFormFieldsAsHTML () {
      $selfile = $this->getSelectedFile();
      $html  = '<div style="text-align:left; font-size: 10pt;">'.LF;
      $html .= '<input type="checkbox" name="only_files" value="1" tabindex="7" style="margin-left:0px;"';
      if ( !empty($selfile) ) {
         $html .= ' checked="checked"';
      }
      $html .= ' />&nbsp;'.$this->_translator->getMessage('SEARCH_ONLY_FILES_TEXT').LF;
      $html .= '</div>'.LF;

      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear'){
        $width = '120';
      } else {
        $width = '145';
      }
      $selrubric = $this->getSelectedRubric();
      $html .= '   <select name="selrubric" size="1" style="width: '.$width.'px; font-size:8pt; margin-top:5px;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '      <option value="all"';
      if ( !isset($selrubric) or $selrubric == 'all' ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('ALL').'</option>'.LF;

      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  array();
      }
      $first = '';
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
            $html .= '      <option value="'.$link_name[0].'"';
            if ( isset($selrubric) and $selrubric == $link_name[0] ) {
               $html .= ' selected="selected"';
            }
            switch ( strtoupper($link_name[0]) )
            {
               case 'ANNOUNCEMENT':
                  $text = $this->_translator->getMessage('ANNOUNCEMENT_INDEX');
                  break;
               case 'DATE':
                  $text = $this->_translator->getMessage('DATE_INDEX');
                  break;
               case 'DISCUSSION':
                  $text = $this->_translator->getMessage('DISCUSSION_INDEX');
                  break;
               case 'GROUP':
                  $text = $this->_translator->getMessage('GROUP_INDEX');
                  break;
               case 'INSTITUTION':
                  $text = $this->_translator->getMessage('INSTITUTION_INDEX');
                  break;
               case 'MATERIAL':
                  $text = $this->_translator->getMessage('MATERIAL_INDEX');
                  break;
               case 'PROJECT':
                  $text = $this->_translator->getMessage('PROJECT_INDEX');
                  break;
               case 'TODO':
                  $text = $this->_translator->getMessage('TODO_INDEX');
                  break;
               case 'TOPIC':
                  $text = $this->_translator->getMessage('TOPIC_INDEX');
                  break;
               case 'USER':
                  $text = $this->_translator->getMessage('USER_INDEX');
                  break;
               default:
                  $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_item_index_view(895) ' );
                  break;
            }
            $html .= '>'.$text.'</option>'.LF;
         }
      }

      $html .= '   </select>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getProjectTitle ($item) {
      $title = $item->getTitle();
      $title = $this->_compareWithSearchText($title);
      $params = array();
      $params['iid'] = $item->getItemID();

     $current_user = $this->_environment->getCurrentUserItem();
      $may_enter = $item->mayEnter($current_user);
      if ($may_enter) {
         $html = ahref_curl($item->getItemID(), 'home',
                                        'index',
                                        '',
                                        '<img src="images/door_open_small.gif" style="vertical-align: middle">').LF;
      } else {
       $html = '<img src="images/door_closed_small.gif" style="vertical-align: middle">'.LF;
     }
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_PROJECT_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title));
      unset($params);
      return $html.' '.$title;
   }

   /** get the moderator of the item
    * this method returns the item moderator in the right formatted style
    *
    * @return string title
    */
   function _getProjectModerator ($item) {
      $retour = '';
      $list = $item->getModeratorList();
      if ( $list->isNotEmpty() ) {
         $first = true;
         $item = $list->getFirst();
         while ($item) {
            if ($first) {
               $first = false;
            } else {
               $retour .= ', ';
            }
            $retour .= $item->getFullname();
            $item = $list->getNext();
         }
      }
      $retour = $this->_compareWithSearchText($retour);
      return $this->_text_as_html_short($retour);
   }

   /** get the activity of the item
    * this method returns the item activity in the right formatted style
    *
    * @return string title
    */
   function _getProjectActivity ($item) {
      $percentage = $item->getActivityPoints();
      if ( empty($percentage) or $this->_max_activity == 0 ) {
         $percentage = 0;
      } else {
         $percentage  = round(($percentage / $this->_max_activity) * 100,2);
      }
      $display_percentage = $percentage;

      if ($percentage<100){
         $display_percentage = 80;
      }
      if ($percentage<80){
         $display_percentage = 60;
      }
      if ($percentage<60){
         $display_percentage = 40;
      }
      if ($percentage<40){
         $display_percentage = 20;
      }
      if ($percentage<20){
         $display_percentage = 10;
      }

      $html  = '         <div class="project-gauge">'.LF;
      $html .= '            <div class="project-gauge-bar" style="width:'.$display_percentage.'%;">&nbsp;</div>'.LF;
      $html .= '         </div>'.LF;

      return $html;
   }

   function _getIndexPageHeaderAsHTML(){
      $html = '';
      $html .='<div style="width:100%;">'.LF;
      // @segment-end 16772
      // @segment-begin 61726 complete:asHTML():style_cell_1:1
     $html .='<div style="width:71%;">'.LF;
     $html .='<div>'.LF;
      // @segment-end 17331
      // @segment-begin 64852 asHTML():display_rubrik_title/rubrik_clipboard_title
      $tempMessage = getMessage('CAMPUS_SEARCH_INDEX');
      if ($this->_clipboard_mode){
          $html .= '<h2 class="pagetitle">'.getMessage('CLIPBOARD_HEADER').' ('.$tempMessage.')';
      }elseif ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
         $html .= '<h2 class="pagetitle">'.getMessage('COMMON_ASSIGN').' ('.$tempMessage.')';
      }else{
          $html .= '<h2 class="pagetitle">'.$tempMessage;
      }

      $html .= '</h2>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }



   function _getListActionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.getMessage('COMMON_ACTIONS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" >'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),
                              $this->_environment->getCurrentModule(),
                              'index',
                              $params,$this->_translator->getMessage('COMMON_LIST_PRINTVIEW')
                             ).BRLF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      return $html;
   }

  function _getListSelectionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $html  = '';
      // Search / select form
      $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="'.$this->_text_as_form($this->_module).'"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="'.$this->_text_as_form($this->_function).'"/>'.LF;
      $html .= '   <input type="hidden" name="sort" value="'.$this->_text_as_form($this->getSortKey()).'"/>'.LF;
      if ( $this->hasCheckboxes() ) {
         $html .= '   <input type="hidden" name="mode" value="'.$this->_text_as_form($this->_has_checkboxes).'"/>'.LF;
      }
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
         $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
      }
      if ( $this->isAttachedList() ) {
         $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
         $html .= '   <input type="hidden" name="mode" value="attached"/>'.LF;
      }
      $session = $this->_environment->getSession();
      if ( !$session->issetValue('cookie')
           or $session->getValue('cookie') == '0' ) {
         $html .= '   <input type="hidden" name="SID" value="'.$this->_text_as_form($session->getSessionID()).'"/>'.LF;
      }
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.getMessage('CAMPUS_SEARCH_INDEX').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear'){
         $width = '190';
      } else {
        $width = '225';
      }
      $html .= '<input style="width:'.$width.'px; font-size:8pt; margin-bottom:5px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
      $html .= '<div style="margin-top:5px;">'.LF;
      $html .= $this->_getAdditionalFormFieldsAsHTML();
      $html .= '<input style="width:65px; font-size:10pt;" name="option" value="'.getMessage('COMMON_SEARCH_BUTTON').'" type="submit"/></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      return $html;
   }
}
?>