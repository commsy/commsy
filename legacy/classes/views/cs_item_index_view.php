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
var $_sel_rubric = '';

   /** constructor: cs_item_list_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      $params['viewname'] = 'campus_search_index';
      cs_index_view::__construct($params);
      $this->institution = $this->_translator->getMessage('INSTITUTION');
   }

   function getChoosenRubric(){
      return $this->_sel_rubric;
   }

   function setChoosenRubric($value){
      $this->_sel_rubric = $value;
   }

   function _getTableheadAsHTML () {
      $html = '';
      return $html;
   }

    function getSearchText (){
       return $this->_search_text;
    }

    // @segment-begin 8397  setSearchText($search_tex)-sets:_search_text/_search_array
    function setSearchText ($search_text){
       $this->_search_text = $search_text;
       $literal_array = array();
       $search_array = array();

       //find all occurances of quoted text and store them in an array
       preg_match_all('~("(.+?)")~u',$search_text,$literal_array);
       //delete this occurances from the original string
       $search_text = preg_replace('~("(.+?)")~u','',$search_text);

       $search_text = preg_replace('~-(\w+)~u','',$search_text);

       //clean up the resulting array from quots
       $literal_array = str_replace('"','',$literal_array[2]);
       //clean up rest of $limit and get an array with entrys
       $search_text = str_replace('  ',' ',$search_text);
       $search_text = trim($search_text);
       $split_array = explode(' ',$search_text);

       //check which array contains search limits and act accordingly
       if ($split_array[0] != '' AND count($literal_array) > 0) {
          $search_array = array_merge($split_array,$literal_array);
       } else {
          if ($split_array[0] != '') {
             $search_array = $split_array;
          } else {
             $search_array = $literal_array;
          }
       }
       $this->_search_array = $search_array;
    }



   function _getTablefootAsHTML() {
   	// If a user was found use _getTablefootAsHTML2 (ViewActions)
   	if(!$this->_first_user){
   		$html = $this->_getTablefootAsHTML2();
   	} else {
	      $html  = '   <tr class="list">'.LF;
	      $html .= '      <td class="head" colspan="5" style="vertical-align:middle;">&nbsp;'.LF;
	      $html .= '      </td>'.LF;
	      $html .= '   </tr>'.LF;
   	}
   	return $html;
   }

   function _getTablefootAsHTML2() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="3"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="3" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="foot_right" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
      if ( $this->hasCheckboxes() ) {
         if (count($this->getCheckedIDs())=='1'){
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED_ONE',count($this->getCheckedIDs()));
         }else{
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED',count($this->getCheckedIDs()));
         }
      }
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;

   }

   /** get View-Actions of this index view
    * this method returns the index actions as html
    *
    * @return string index actions
    */
   function _getViewActionsAsHTML () {
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt; font-weight:normal;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      $html .= '   <option value="2">'.$this->_translator->getMessage('USER_LIST_ACTION_EMAIL_SEND').'</option>'.LF;
      $html .= '</select>'.LF;
      $html .= '<input type="submit" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
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
         $temp_item_id_array = array();
         while ( $current_item ) {
            $temp_item_id_array[] = $current_item->getItemId();
            $current_item = $list->getNext();
         }

         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed_manager->getLatestNoticedByIDArray($temp_item_id_array);
         $noticed_manager->getLatestNoticedAnnotationsByIDArray($temp_item_id_array);

         $link_manager = $this->_environment->getLinkManager();
         $link_manager->getAllFileLinksForListByIDs($temp_item_id_array);

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
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();

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
            #$html .= $this->_getUserItemAsLongHtml($item,$style);
            $html .= $this->_getUserItemAsHtml($item,0,$style);
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
      $html .= '      <td '.$style.' style="font-size:8pt; width:15%;">'.$this->_getItemModificationDate($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:25%;">'.$this->_getItemModificator($item).'</td>'.LF;
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
      $user = $this->_environment->getCurrentUser();
      $status = $item->getStatus();
      return $status;
   }

   function _getToDoItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $context = $this->_environment->getCurrentContextItem();

      if ($context->withTodoManagement()){
         $html .= '      <td '.$style.' style="font-size:10pt; width: 35%;">'.$this->_getItemTitle($item).'</td>'.LF;
         $html .= '      <td '.$style.' style="font-size:8pt; width:15%;">'.$this->_getStatus($item).'</td>'.LF;
         $html .= '      <td '.$style.' style="font-size:8pt; width:25%; white-space:nowrap;">'.'<div style="float:right; padding-right:5px;">'.$this->_getProcess($item).'</div>'.'<div>'.$this->_getDateInLang($item).' </div></td>'.LF;
         $html .= '      <td '.$style.' style="font-size:8pt; width:25%;">'.$this->_getProcessors($item).'</td>'.LF;
      }else{
         $html .= '      <td '.$style.' style="font-size:10pt; width: 40%;">'.$this->_getItemTitle($item).'</td>'.LF;
         $html .= '      <td '.$style.' style="font-size:8pt; width:15%;">'.$this->_getStatus($item).'</td>'.LF;
         $html .= '      <td '.$style.' style="font-size:8pt; width:10%;">'.$this->_getDateInLang($item).'</td>'.LF;
         $html .= '      <td '.$style.' style="font-size:8pt; width:35%%;">'.$this->_getProcessors($item).'</td>'.LF;
      }


#      $html .= '      <td '.$style.' style="font-size:10pt; width: 35%;">'.$this->_getItemTitle($item).'</td>'.LF;
#      $html .= '      <td '.$style.' style="font-size:8pt; width: 15%;">'.$this->_getStatus($item).'</td>'.LF;
#      $html .= '      <td '.$style.' style="font-size:8pt; width: 20%;">'.$this->_getDateInLang($item).'</td>'.LF;
#      $html .= '      <td '.$style.' style="font-size:8pt; width: 30%;">'.$this->_getProcessors($item).'</td>'.LF;
      return $html;
   }


  function _getProcess($item){
      $step_html = '';
      $step_minutes = 0;
      $step_item_list = $item->getStepItemList();
      if ( $step_item_list->isEmpty() ) {
         $status = $this->_compareWithSearchText($item->getStatus());
      } else {
         $step = $step_item_list->getFirst();
         $count = $step_item_list->getCount();
         $counter = 0;
         while ($step) {
            $counter++;
            $step_minutes = $step_minutes + $step->getMinutes();
            $step = $step_item_list->getNext();
         }
      }
      $done_time = '';
      $done_percentage = 100;
      if ($item->getPlannedTime() > 0){
         $done_percentage = $step_minutes / $item->getPlannedTime() * 100;
      }

      $tmp_message = $this->_translator->getMessage('COMMON_MINUTES');
      $step_minutes_text = $step_minutes;
      if (($step_minutes/60)>1 and ($step_minutes/60)<=8){
         $step_minutes_text = '';
         $exact_minutes = $step_minutes/60;
         $step_minutes = round($exact_minutes,1);
         if ($step_minutes != $exact_minutes){
            $step_minutes_text .= 'ca. ';
         }
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
         $step_minutes_text .= $step_minutes;
         $tmp_message = $this->_translator->getMessage('COMMON_HOURS');
         if ($step_minutes == 1){
            $tmp_message = $this->_translator->getMessage('COMMON_HOUR');
         }
       }elseif(($step_minutes/60)>8){
         $exact_minutes = ($step_minutes/60)/8;
         $step_minutes = round($exact_minutes,1);
         $step_minutes_text = '';
         if ($step_minutes != $exact_minutes){
            $step_minutes_text .= 'ca. ';
         }
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
         $step_minutes_text .= $step_minutes;
         $tmp_message = $this->_translator->getMessage('COMMON_DAYS');
         if ($step_minutes == 1){
            $tmp_message = $this->_translator->getMessage('COMMON_DAY');
         }
      }else{
         $step_minutes = round($step_minutes,1);
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
      }

      $display_plannend_time = $item->getPlannedTime();
      $shown_time = $step_minutes_text.' '.$tmp_message;
      $display_time_text_addon = $display_plannend_time.' '.$this->_translator->getMessage('COMMON_MINUTES');
      if (($display_plannend_time/60)>1){
         $display_time_text_addon = round($display_plannend_time/60);
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $display_time_text_addon = str_replace('.',',',$display_time_text_addon);
         }
         if ($display_time_text_addon == 1){
            $display_time_text_addon .= ' '.$this->_translator->getMessage('COMMON_HOUR');
         }else{
            $display_time_text_addon .= ' '.$this->_translator->getMessage('COMMON_HOURS');
         }
      }
      if ($display_plannend_time/60>8){
         $display_time_text_addon = round($display_plannend_time/60/8,1);
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $display_time_text_addon = str_replace('.',',',$display_time_text_addon);
         }
         if ($display_time_text_addon == 1){
            $display_time_text_addon .= ' '.$this->_translator->getMessage('COMMON_DAY');
         }else{
            $display_time_text_addon .= ' '.$this->_translator->getMessage('COMMON_DAYS_AKK');
         }
      }
      $display_time_text = $shown_time.' '.$this->_translator->getMessage('COMMON_FROM2').' '.$display_time_text_addon.' - '.round($done_percentage).'% '.$this->_translator->getMessage('TODO_DONE');
      if($done_percentage <= 100){
         $style = ' height: 8px; background-color: #75ab05; ';
         $done_time .= '      <div title="'.$display_time_text.'" style="border: 1px solid #444;  margin-left: 0px; height: 8px; width: 50px;">'.LF;
         if ( $done_percentage >= 30 ) {
            $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
         } else {
            $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
         }
         $done_time .= '      </div>'.LF;
      }elseif($done_percentage <= 120){
         $done_percentage = (100 / $done_percentage) *100;
         $style = ' height: 10px; border: 1px solid #444; background-color: #f2f030; ';
         $done_time .= '         <div title="'.$display_time_text.'" style="width: 60px; font-size: 2pt; '.$style.' color:#000000;">'.LF;
         $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:10px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
         $done_time .= '      </div>'.LF;
         $done_time .= '</div>'.LF;
      }else{
         $done_percentage = (100 / $done_percentage) *100;
         $style = ' height: 8px; border: 1px solid #444; background-color: #f23030; ';
         $done_time .= '         <div title="'.$display_time_text.'" style="width: 60px; font-size: 2pt; '.$style.' color:#000000;">'.LF;
         $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:8px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
         $done_time .= '      </div>'.LF;
         $done_time .= '</div>'.LF;
      }
      if ($item->getPlannedTime() > 0){
         $process = $done_time;
      }else{
         $process = $shown_time;
      }
      return $process;
   }

   function _getProcessors($item){
     $user = $this->_environment->getCurrentUser();
     $html ='';
     $members = $item->getProcessorItemList();
      if ( $members->isEmpty() ) {
         $html .= '   <span class="disabled">'.$this->_translator->getMessage('TODO_NO_PROCESSOR').'</span>'.LF;
      } else {
         $member = $members->getFirst();
         if ( $member->isUser() ){
            $linktext = $member->getFullname();
            $params = array();
            $params['iid'] = $member->getItemID();
            if ( $this->_environment->inProjectRoom() and $member->maySee($user) ) {
               $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             'user',
                             'detail',
                             $params,
                             $linktext);
            } else {
               $html .= '<span class="disabled">'.$linktext.'</span>';
            }
            unset($params);
         }
         $member = $members->getNext();
         while ($member) {
            if ( $member->isUser() ){
               $linktext = ', '.$member->getFullname();
               $member_title = $member->getTitle();
               $params = array();
               $params['iid'] = $member->getItemID();
               if ( $this->_environment->inProjectRoom() and $member->maySee($user) ) {
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'user',
                                'detail',
                                $params,
                                $linktext);
               } else {
                  $html .= '<span class="disabled">'.$linktext.'</span>';
               }
               unset($params);
            }
            $member = $members->getNext();
         }
      }
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
      $html .= '      <td colspan="2" '.$style.' style="font-size:10pt; width:62%;">'.$this->_getMaterialItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:13%;">'.$this->_getItemModificationDate($item).'</td>'.LF;

      ########################
      # EDU HACK - BEGIN
      ########################
      if ( $this->_environment->inConfigArray('c_material_auhtor_array',$this->_environment->getCurrentContextID()) ) {
         $text = $this->_getItemAuthor($item);
         if ( empty($text) ) {
            $text = $this->_getItemModificator($item);
         }
         $html .= '      <td '.$style.' style="font-size:8pt;width:25%;">'.$text.'</td>'.LF;
      } else {
      ########################
      # EDU HACK - END
      ########################

         $html .= '      <td '.$style.' style="font-size:8pt;width:25%;">'.$this->_getItemModificator($item).'</td>'.LF;

      ########################
      # EDU HACK - BEGIN
      ########################
      }
      ########################
      # EDU HACK - END
      ########################

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
               if(in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
                   $this->_with_slimbox = true;
                   // jQuery
                   //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                   $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                   // jQuery
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
      $html .= '      <td  '.$style.' style="font-size:8pt; width:25%;">'.$this->_text_as_html_short($phone).LF;
      if (!empty($handy)){
         $html .= BRLF.$this->_text_as_html_short($handy).'</td>'.LF;
      }else{
         $html .='</td>'.LF;
      }
      $html .= '      <td  '.$style.' style="font-size:8pt; width:35%;">'.$this->_getItemEmail($item).'</td>'.LF;
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
   function _getUserItemAsHTML($item,$pos=0,$style) {
      $shown_entry_number = $pos;
      $phone = $this->_compareWithSearchText($item->getTelephone());
      $handy = $this->_compareWithSearchText($item->getCellularphone());
//      if ($shown_entry_number%2 == 0){
//         $style='class="odd"';
//      }else{
//         $style='class="even"';
//      }
      $html  = '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         #$html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         $html .= '      <td '.$style.' style="font-size:10pt;" colspan="2">'.LF;
         $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
         if ( in_array($key, $checked_ids) ) {
            $html .= ' checked="checked"'.LF;
            if ( in_array($key, $dontedit_ids) ) {
               $html .= ' disabled="disabled"'.LF;
            }
         }
         $html .= '/>'.LF;
         $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         #$html .= '      </td>'.LF;

         // Item Change Status
         if ( !$this->_environment->inPrivateRoom() ) {
         	$name .= $this->_getItemChangeStatus($item);
      	}
         $html .= $this->_getItemFullname($item).$name.LF;
         $html .= '</td>'.LF;
      }else{
         $html .= '      <td colspan="4"'.$style.' style="font-size:10pt;">'.$this->_getItemFullname($item).'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">';
      if ( !empty($phone) ){
         $html .= $this->_text_as_html_short($phone).LF;
      }
      if (!empty($phone) and !empty($handy)) {
         $html .= BRLF;
      }
      if ( !empty($handy) ){
         $html .= $this->_text_as_html_short($handy).LF;
      }
      $html .= '</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemEmail($item).'</td>'.LF;
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
      $params['search_path'] = 'true';
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
      $email = curl_mailto( $item->getEmail(), $this->_text_as_html_short($email_text) );
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
     // check if item is not activated
      $title = $item->getTitle();
	   $title = $this->_compareWithSearchText($title);
      if($item->isNotActivated()) {
         $user = $this->_environment->getCurrentUser();
         if($item->getCreatorID() == $user->getItemID() or $user->isModerator()) {
            $params = array();
		      $params['iid'] = $item->getItemID();
		      $params['search_path'] = 'true';
		      $title = ahref_curl( $this->_environment->getCurrentContextID(),
		                           $item->getItemType(),
		                           'detail',
		                           $params,
		                           $this->_text_as_html_short($title));
		      unset($params);
         }else{
            $title = $this->_text_as_html_short($title);
         }
         $activating_date = $item->getActivatingDate();
         if (strstr($activating_date,'9999-00-00')){
            $title .= BR.$this->_translator->getMessage('COMMON_NOT_ACTIVATED');
         }else{
            $title .= BR.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
         }
         $title = '<span class="disabled">'.$title.'</span>';
      } else {
	      $params = array();
	      $params['iid'] = $item->getItemID();
	      $params['search_path'] = 'true';
	      $title = ahref_curl( $this->_environment->getCurrentContextID(),
	                           $item->getItemType(),
	                           'detail',
	                           $params,
	                           $this->_text_as_html_short($title));
	      unset($params);
	      $title .= $this->_getItemChangeStatus($item);
	      $title .= $this->_getItemAnnotationChangeStatus($item);
	      $title .= ' '.$this->_getItemFiles($item);
      }

      return $title;
   }

   function _getMaterialItemTitle ($item) {
      $title = $item->getTitle();
      $title = $this->_compareWithSearchText($title);
      $module = $item->getItemType();
      $author_text = $this->_text_as_html_short($this->_compareWithSearchText($this->_getItemAuthor($item)));
      $year_text = $this->_text_as_html_short($this->_compareWithSearchText($this->_getItemPublishingDate($item)));
      $bib_kind = $item->getBibKind() ? $item->getBibKind() : 'none';
      $title_text = '';
      $user = $this->_environment->getCurrentUser();
      if ( ( $item->isNotActivated()
             and $item->getCreatorID() != $user->getItemID()
             and !$user->isModerator()
           )
           or ( !$this->_environment->inProjectRoom()
                and !$item->isPublished()
                and !$user->isUser()
              )
         ) {
         $title_text = $this->_text_as_html_short($title);
         $activating_date = $item->getActivatingDate();
         if (strstr($activating_date,'9999-00-00')){
            $title_text .= BR.$this->_translator->getMessage('COMMON_NOT_ACTIVATED');
         }else{
            $title_text .= BR.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
         }
         if (!empty($author_text) and $bib_kind !='none'){
            if (!empty($year_text)){
                $year_text = ', '.$year_text;
            }else{
                $year_text = '';
            }
            $title = '<span class="disabled">'.$title_text.'</span>'.'<span class="disabled" style="font-size:8pt;"> ('.$this->_getItemAuthor($item).$year_text.')'.'</span>';
         }else{
            $title = '<span class="disabled">'.$title_text.'</span>'.LF;
         }
      }else{
         if (!empty($author_text) and $bib_kind !='none'){
            if (!empty($year_text)){
               $year_text = ', '.$year_text;
            }else{
               $year_text = '';
            }
            $title_text = '<span style="font-size:8pt;"> ('.$this->_getItemAuthor($item).$year_text.')'.'</span>';
         }else{
            $title_text = ''.LF;
         }
         $params = array();
         $params['iid'] = $item->getItemID();
         $params['search_path'] = 'true';
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $module,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title));
         unset($params);
         $title .= $title_text;
         $title .= $this->_getItemChangeStatus($item);
         $title .= $this->_getItemAnnotationChangeStatus($item);
         $title .= ' '.$this->_getItemFiles($item);
      }
      return $title;
   }



/*   function _getAdditionalFormFieldsAsHTML () {
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
   }*/

   function _getExpertSearchAsHTML(){
      $html  = '';
      $context_item = $this->_environment->getCurrentContextItem();
      $module = $this->_environment->getCurrentModule();
      $width = '235';
      $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_RESTRICTIONS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;

      $html .= '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">';
      $html .= $this->_translator->getMessage('SEARCH_ONLY_IN_FILES_TEXT').'&nbsp;'.LF;
      $selfile = $this->getSelectedFile();
      // jQuery
      //$html .= '<input type="checkbox" name="only_files" value="1" tabindex="7" style="margin-left:0px;" onClick="javascript:document.indexform.submit()"';
      $html .= '<input type="checkbox" name="only_files" value="1" tabindex="7" style="margin-left:0px;" id="submit_form"';
      // jQuery
      if ( !empty($selfile) ) {
         $html .= ' checked="checked"';
      }
      $html .= '>';
      $html .= '</div>'.LF;

      $html .= '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">';
      $html .= $this->_translator->getMessage('SEARCH_RUBRIC_RESTRICTION').'&nbsp;'.LF;
      $selrubric = $this->getChoosenRubric();
      // jQuery
      //$html .= '   <select name="selrubric" size="1" style="width: '.$width.'px; font-size:10pt;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select name="selrubric" size="1" style="width: '.$width.'px; font-size:10pt;" id="submit_form">'.LF;
      // jQuery
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
         if ( $link_name[1] != 'none'
              and !$this->_environment->isPlugin($link_name[0])
            ) {
            $html .= '      <option value="'.$link_name[0].'"';
            if ( isset($selrubric) and $selrubric == $link_name[0] ) {
               $html .= ' selected="selected"';
            }
            switch ( mb_strtoupper($link_name[0], 'UTF-8') )
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
                  $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_item_index_view('.__LINE__.') ' );
                  break;
            }
            $html .= '>'.$text.'</option>'.LF;
         }
      }

      $html .= '   </select>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">';
      $html .= $this->_translator->getMessage('COMMON_FIELD_RESTRICTIONS').'<br />'.LF;
      $selected_value = $this->getSelectedRestriction();
      if (isset($this->_search_text) and !empty($this->_search_text) ){
         // jQuery
         //$html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selrestriction" size="1" onChange="javascript:document.indexform.submit()">'.LF;
         $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selrestriction" size="1" id="submit_form">'.LF;
         // jQuery
      }else{
         $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selrestriction" size="1">'.LF;
      }
      $html .= '      <option value="0"';
      if ( !isset($selected_value) || $selected_value == 0 ) {
          $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('MATERIAL_FULL_FIELD_SEARCH').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
      $html .= '      <option value="1"';
      if ( isset($selected_value) and $selected_value == 'title' ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('MATERIAL_ONLY_TITLE').'</option>'.LF;
      $html .= '      <option value="2"';
      if ( isset($selected_value) and $selected_value == 'author' ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('MATERIAL_ONLY_AUTHOR').'</option>'.LF;
      $html .= '   </select>'.LF;
      $html .='</div>';

      if ($context_item->withActivatingContent()){
         $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_SHOW_ACTIVATING_ENTRIES').'<br />'.LF;
         // jQuery
         //$html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selactivatingstatus" size="1" onChange="javascript:document.indexform.submit()">'.LF;
         $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selactivatingstatus" size="1" id="submit_form">'.LF;
         // jQuery
         $html .= '      <option value="1"';
         if ( isset($this->_activation_limit) and $this->_activation_limit == 1 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>*'.$this->_translator->getMessage('COMMON_ALL_ENTRIES').'</option>'.LF;
         $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
         $html .= '      <option value="2"';
         if ( !isset($this->_activation_limit) || $this->_activation_limit == 2 ) {
           $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</option>'.LF;
         $html .= '   </select>'.LF;
         $html .='</div>';
      }
      $html .= $this->_getAdditionalRestrictionBoxAsHTML('14.5').LF;
      $html .= $this->_getAdditionalFormFieldsAsHTML().LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
  }





   function _getAdditionalFormFieldsAsHTML ($field_length=14.5) {
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $width = '235';
      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  array();
      }
      $html = '';
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
            if ($context_item->_is_perspective($link_name[0]) and $context_item->withRubric($link_name[0])) {
               $list = $this->getAvailableRubric($link_name[0]);
               $selrubric = $this->getSelectedRubric($link_name[0]);
               $temp_link = mb_strtoupper($link_name[0], 'UTF-8');
               switch ( $temp_link )
               {
                  case 'GROUP':
                     $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_GROUP').'<br />'.LF;
                     break;
                  case 'INSTITUTION':
                     $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_INSTITUTION').'<br />'.LF;
                     break;
                  case 'TOPIC':
                     $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_TOPIC').'<br />'.LF;
                     break;
                  default:
                     $html .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_index_view(1503) ';
                     break;
               }

               if ( isset($list)) {
                  // jQuery
                  //$html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="sel'.$link_name[0].'" size="1" onChange="javascript:document.indexform.submit()">'.LF;
                  $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="sel'.$link_name[0].'" size="1" id="submit_form">'.LF;
                  // jQuery
                  $html .= '      <option value="0"';
                  if ( !isset($selrubric) || $selrubric == 0 ) {
                     $html .= ' selected="selected"';
                  }
                  $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;
                  $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
                  $sel_item = $list->getFirst();
                  while ( $sel_item ) {
                     $html .= '      <option value="'.$sel_item->getItemID().'"';
                     if ( isset($selrubric) and $selrubric == $sel_item->getItemID() ) {
                        $html .= ' selected="selected"';
                     }
                     $text = $this->_Name2SelectOption($sel_item->getTitle());
                     $html .= '>'.$text.'</option>'.LF;
                     $sel_item = $list->getNext();
                 }
                 $html .= '   <option class="disabled" disabled="disabled" value="-1">------------------------------</option>'.LF;
                 $html .= '      <option value="-1"';
                 if ( !isset($selrubric) || $selrubric == -1 ) {
                    $html .= ' selected="selected"';
                 }
                 $html .= '>*'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</option>'.LF;
                 $html .= '   </select>'.LF;
             } else {
                $html.='';
             }
             $html .='</div>';
            }
         }
      }
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


  function _getSearchAsHTML () {
     $html  = '';
     $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'campus_search', 'index','').'" method="get" name="searchform">'.LF;
     $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
     $html .= '   <input type="hidden" name="mod" value="campus_search"/>'.LF;
     $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
     $html .= '<input id="searchtext" onclick="javascript:resetSearchText(\'searchtext\');" style="width:220px; font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
        $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     } else {
        $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     }
     $html .= '</form>';
     return $html;
  }


   function _getIndexPageHeaderAsHTML(){
      $html = '';
      $html .='<div style="width:100%;">'.LF;
      $html .='<div style="height:30px;">'.LF;
      $html .= '<div style="float:right; width:28%; white-space:nowrap; text-align-left; padding-top:5px; margin:0px;">'.LF;
      $html .= $this->_getSearchAsHTML();
      $html .= '</div>'.LF;
      $html .='<div style="width:70%;">'.LF;
      $html .='<div>'.LF;
      $tempMessage = $this->_translator->getMessage('CAMPUS_SEARCH_INDEX');
      if ($this->_clipboard_mode){
          $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('CLIPBOARD_HEADER').' ('.$tempMessage.')';
      }elseif ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
         $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('COMMON_ASSIGN').' ('.$tempMessage.')';
      }else{
          $html .= '<h2 class="pagetitle">'.$tempMessage;
      }
      $html .= '</h2>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }

   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $context_item = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSessionItem();
      if (isset($_GET['back_to_search']) and $session->issetValue('cid'.$this->_environment->getCurrentContextID().'_campus_search_parameter_array')){
         $campus_search_parameter_array = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_campus_search_parameter_array');
         $params['search'] = $campus_search_parameter_array['search'];
         $params['selrestriction'] = $campus_search_parameter_array['selrestriction'];
         $params['selrubric'] = $campus_search_parameter_array['selrubric'];
         $params['selbuzzword'] = $campus_search_parameter_array['selbuzzword'];
         $params['seltag_array'] = $campus_search_parameter_array['seltag_array'];
         $params['only_files'] = $campus_search_parameter_array['selfiles'];
         $params['interval'] = $campus_search_parameter_array['interval'];
         $params['sel_activating_status'] = $campus_search_parameter_array['sel_activating_status'];
      }
      if ( isset($params['only_files']) and !empty($params['only_files']) and $params['only_files'] == 1){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor" style="white-space:nowrap;">'.$this->_translator->getMessage('MATERIAL_FILES').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         $html_text .= $this->_translator->getMessage('COMMON_ONLY_FILES');
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         unset($new_params['only_files']);
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
      if ( (isset($params['selrubric']) and !empty($params['selrubric']) and $params['selrubric'] != 'all' ) ){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_RUBRIC').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         switch ( mb_strtoupper($params['selrubric'], 'UTF-8') ){
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
               $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_item_index_view(1396) ' );
               break;
         }
         $html_text .= '<span>'.$text.'</span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selrubric'] = 'all';
         unset($new_params['selrestriction']);
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
      if ( isset($params['selrestriction'])
           and !empty($params['selrestriction'])
           and $params['selrestriction'] != 'all'
         ) {
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor" style="white-space:nowrap;">'.$this->_translator->getMessage('SEARCH_FIELD_RESTRICTION').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;

         switch ( mb_strtoupper($params['selrestriction'], 'UTF-8') ){
            case '1':
               $text = $this->_translator->getMessage('COMMON_TITLE');
               break;
            case '2':
               $text = $this->_translator->getMessage('COMMON_AUTHOR');
               break;
           default:
               $text = $this->_translator->getMessage('COMMON_TITLE');
               break;
         }
         $html_text .= '<span>'.$text.'</span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selrestriction'] = 'all';
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      } elseif ( !empty($params['selrubric'])
                 and mb_strtoupper($params['selrubric'], 'UTF-8') == mb_strtoupper(type2module(CS_MATERIAL_TYPE), 'UTF-8')
               ) {
         $width = '150';
         $html_text = '<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor" style="white-space:nowrap;">'.$this->_translator->getMessage('SEARCH_FIELD_RESTRICTION').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         // jQuery
         //$html_text .= '   <select style="width: '.$width.'px; font-size:10pt; font-family: \'Trebuchet MS\',\'lucida grande\',tahoma,\'ms sans serif\',verdana,arial,sans-serif;" name="selrestriction" size="1" onChange="javascript:document.indexform.submit()">'.LF;
         $html_text .= '   <select style="width: '.$width.'px; font-size:10pt; font-family: \'Trebuchet MS\',\'lucida grande\',tahoma,\'ms sans serif\',verdana,arial,sans-serif;" name="selrestriction" size="1" id="submit_form">'.LF;
         // jQuery
         $html_text .= '      <option value="0"';
         $html_text .= ' selected="selected"';
         $html_text .= '>*'.$this->_translator->getMessage('COMMON_NO_RESTRICTION').'</option>'.LF;
         $html_text .= '   <option class="disabled" disabled="disabled" value="-2">-------------------</option>'.LF;
         $html_text .= '      <option value="1"';
         $html_text .= '>'.$this->_translator->getMessage('COMMON_TITLE').'</option>'.LF;
         $html_text .= '      <option value="2"';
         $html_text .= '>'.$this->_translator->getMessage('COMMON_AUTHOR').'</option>'.LF;
         $html_text .= '   </select>'.LF;
         $html_text .= '   <input type="hidden" name="selrubric" value="material" />'.LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }

      if ($context_item->withActivatingContent()){
         if ( !isset($params['selactivatingstatus']) or (isset($params['selactivatingstatus']) and $params['selactivatingstatus'] == 2 ) ){
            $this->_additional_selects = true;
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_ACTIVATION_RESTRICTION').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $html_text .= '<span>'.$this->_translator->getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            $new_params['selactivatingstatus'] = 1;
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
      }
      return $html;
   }


 /*  function _getListActionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ACTIONS').'</div>';
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
   }*/

 /* function _getListSelectionsAsHTML () {
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
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('CAMPUS_SEARCH_INDEX').'</div>';
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
      $html .= '<input style="width:65px; font-size:10pt;" name="option" value="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'" type="submit"/></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      return $html;
   }*/
}
?>