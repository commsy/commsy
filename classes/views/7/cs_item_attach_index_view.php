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

$this->includeClass(ITEM_INDEX_VIEW);

/**
 *  class for CommSy index list view: index
 */
class cs_item_attach_index_view extends cs_item_index_view {


var $_checked_ids = array();
var $_ref_iid = '';
var $_hidden_field_array = array();

   /** constructor: cs_item_list_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_item_attach_index_view ($params) {
      $params['viewname'] = 'item_attach_index';
      $this->cs_item_index_view($params);
   }

   function setRefItemID($iid){
      $this->_ref_iid = $iid;
   }

   function setLinkedItemIDArray($array){
      $this->_checked_ids = $array;
   }


   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      $html .= '      <td class="head" colspan="4" style="vertical-align:middle;">&nbsp;'.LF;
      $html .= '         <input type="hidden" name="return_attach_item_list" value="true"/>'.LF;
      $html .= '         <input type="hidden" style="font-size:10pt;" name="iid" value="'.$this->_ref_iid.'"/>';
      $html .= '         <input type="submit" style="font-size:10pt;" name="option"';
      $html .= '          value="'.$this->_translator->getMessage('COMMON_ITEM_ATTACH').'"';
      $html .= '         />'.LF;
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }


   function setSelectedRestriction ($restriction) {
      $this->_selected_restriction = $restriction;
   }

   function getSelectedRestriction () {
      return $this->_selected_restriction;
   }

   function setHiddenFields($array){
      $this->_hidden_field_array = $array;
   }

   function setLongTitle(){
      $title = $this->_translator->getMessage('COMMON_SEARCH');
      $this->setTitle($title);
   }

   function _getTopicItemAsLongHTML($item, $style) {
      $html = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $text .= ' disabled="disabled"'.LF;

      }
      $text .= '/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td   '.$style.' style="font-size:10pt; width:70%;" colspan="2">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td   '.$style.' style="font-size:8pt; width:29%;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getGroupItemAsLongHTML($item, $style) {
      $html = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      $tmp_text = '';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $tmp_text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $tmp_text .= ' disabled="disabled"'.LF;
      }
      if($item->isSystemLabel()){
         $tmp_text .= ' checked="checked" disabled="disabled"'.LF;
      }
      $text .= $tmp_text.'/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td   '.$style.' style="font-size:10pt; width:70%;" colspan="2">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td   '.$style.' style="font-size:8pt; width:29%;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getInstitutionItemAsLongHTML($item, $style) {
      $html = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $text .= ' disabled="disabled"'.LF;

      }
      $text .= '/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td   '.$style.' style="font-size:10pt; width:70%;" colspan="2">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td   '.$style.' style="font-size:8pt; width:29%;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }


   function _getDiscussionItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $text .= ' disabled="disabled"'.LF;

      }
      $text .= '/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:20%;">'.$this->_getItemArticleCount($item).LF;
      $html .='</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:19%;">'.$this->_getItemLastArticleDate($item).'</td>'.LF;
      return $html;
   }


   function _getDateItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $text .= ' disabled="disabled"'.LF;

      }
      $text .= '/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;" >'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:20%;">'.$this->_getItemDate($item).LF;
      $time = $this->_getItemTime($item);
      $starting_time = $item->getStartingTime();
      if (!empty($time) and !empty($starting_time)) {
         $html .= ', '.$time;
      }
      $html .='</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:24%;">'.$this->_getItemPlace($item).'</td>'.LF;
      return $html;
   }

   function _getMaterialItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $text .= ' disabled="disabled"'.LF;

      }
      $text .= '/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt; width:62%;">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:25%;">'.$this->_getItemAuthor($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:12%;">'.$this->_getItemModificationDate($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getUserItemAsLongHTML($item, $style) {
      $phone = $this->_compareWithSearchText($item->getTelephone());
      $handy = $this->_compareWithSearchText($item->getCellularphone());
      $html  = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $text .= ' disabled="disabled"'.LF;

      }
      $text .= '/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt; width:40%;">'.$this->_getItemFullname($item).'</td>'.LF;
      $html .= '      <td  '.$style.' style="font-size:8pt; width:35%;">'.$this->_getItemEmail($item).'</td>'.LF;
      $html .= '      <td  '.$style.' style="font-size:8pt; width:24%;">'.$this->_text_as_html_short($phone).LF;
      if (!empty($handy)){
         $html .= BRLF.$this->_text_as_html_short($handy).'</td>'.LF;
      }else{
         $html .='</td>'.LF;
      }
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getToDoItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $text .= ' disabled="disabled"'.LF;

      }
      $text .= '/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:20%;">'.$this->_getDateInLang($item).LF;
      $html .='</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:19%;">'.$this->_getStatus($item).'</td>'.LF;
      return $html;
   }

   function _getAnnouncementItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $text .= ' disabled="disabled"'.LF;

      }
      $text .= '/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:24%;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:15%;">'.$this->_getItemModificationDate($item).'</td>'.LF;
      return $html;
   }

   function _getMyRoomItemAsLongHTML($item,$style) {
      $html  = '   <tr>'.LF;
      $checked_item_array = $this->_checked_ids;
      $key = $item->getItemID();
      $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" onClick="quark(this)" type="checkbox" name="itemlist['.$key.']" value="1"';
      if ( isset($checked_item_array) and !empty($checked_item_array) and in_array($key, $checked_item_array)) {
         $text .= ' checked="checked"'.LF;
      }
      if ($item->getItemID() == $this->_ref_iid){
         $text .= ' disabled="disabled"'.LF;

      }
      $text .= '/>'.LF;
      $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:1%;">'.$text.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getProjectTitle($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:39%;">'.$this->_getProjectModerator($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:15%;"> </td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    */
   function _getContentAsHTML() {
      $html = '';
      $list = $this->_list;
      if(isset($_GET['mode']) and $_GET['mode']=='print'){
         $this->_with_checkboxes = false;
      }
      if ( !isset($list) || $list->isEmpty() ) {
         $html .= '   <tr class="list">'.LF;
         $html .= '      <td class="head" style="font-size:10pt; font-weight:bold" colspan="5">';
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
            $html .= $this->_getGroupItemAsLongHtml($item,$style);
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

   function _getItemTitle ($item) {
      $title = $item->getTitle();
      $title = $this->_compareWithSearchText($title);
      $title .= $this->_getItemChangeStatus($item);
      $title .= $this->_getItemAnnotationChangeStatus($item);
      $title .= ' '.$this->_getItemFiles($item);
      return $title;
   }


   function _getExpertSearchAsHTML(){
      $html  = '';
      $context_item = $this->_environment->getCurrentContextItem();
      $module = $this->_environment->getCurrentModule();
      $width = '168';

      $html .= '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">';
      $html .= $this->_translator->getMessage('SEARCH_RUBRIC_RESTRICTION').'&nbsp;'.LF;
      $selrubric = $this->getChoosenRubric();
      $html .= '   <select name="selrubric" size="1" style="width: '.$width.'px; font-size:10pt;" onChange="javascript:document.item_list_form.submit()">'.LF;
      $html .= '      <option value="all"';
      if ( !isset($selrubric) or $selrubric == 'all' ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('ALL').'</option>'.LF;
      $html .= '      <option value="-1" disabled="disabled">-------------------------</option>'.LF;

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
                  $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_item_index_view(1057) ' );
                  break;
            }
            $html .= '>'.$this->_text_as_form($text).'</option>'.LF;
         }
      }

      $html .= '   </select>'.BRLF;

      # checkbox for only linked items
      $html .= '   <input type="checkbox" name="linked_only" value="1" onChange="javascript:document.item_list_form.submit()"';
      if ( !empty($_POST['linked_only']) and $_POST['linked_only'] == 1 ) {
         $html .= ' checked="checked"';
      }
      $html .= '/>'.$this->_translator->getMessage('SEARCH_LINKED_ENTRIES_ONLY').BRLF;

      # textfield for search term
      $html .= '   <input type="textfield" name="search" style="width: 135px;"';
      if ( !empty($_POST['search']) ) {
         $html .= ' value="'.$this->_text_as_form($_POST['search']).'"';
      }
      $html .= '/>'.LF;
      $html .= '   <input src="images/commsyicons/22x22/search.png" style="vertical-align: top;" alt="Suchen" type="image">'.LF;

      # div end
      $html .= '</div>'.LF;

      if ( $context_item->withActivatingContent() ) {
         $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_SHOW_ACTIVATING_ENTRIES').'<br />'.LF;
         $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selactivatingstatus" size="1" onChange="javascript:document.item_list_form.submit()">'.LF;
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
      return $html;
  }





   function _getAdditionalFormFieldsAsHTML ($field_length=14.5) {
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $width = '150';
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
               $temp_link = strtoupper($link_name[0]);
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
                  $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="sel'.$link_name[0].'" size="1" onChange="javascript:document.item_list_form.submit()">'.LF;
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
      $title = $title;
      unset($params);
      return $html.' '.$title;
   }


   function _getIndexPageHeaderAsHTML(){
      $html = '';
      $html .='<div style="width:100%;">'.LF;
      $html .='<div style="height:30px;">'.LF;
      $html .= '<div style="float:right; width:27%; white-space:nowrap; text-align-left; padding-top:5px; margin:0px;">'.LF;
      $html .= $this->_getSearchAsHTML();
      $html .= '</div>'.LF;
      $html .='<div style="width:71%;">'.LF;
      $html .='<div>'.LF;
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
      $html .='</div>'.LF;
      return $html;
   }

   function _getRestrictionTextAsHTML(){
      $ref_user = $this->getRefUser();
      $ref_iid = $this->getRefIID();
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      if ( isset($params['search']) and !empty($params['search']) ){
            $html_text .='<div class="restriction">';
            $html_text .= '<span class="infocolor">'.getMessage('COMMON_SEARCH_RESTRICTION').':</span> ';
            $html_text .= '<span><a title="'.urldecode($params['search']).'">'.chunkText(urldecode($params['search']),13).'</a></span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            unset($new_params['search']);
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</div>';
            $html .= $html_text;
         }
#         $html .= $this->getAdditionalRestrictionTextAsHTML();
      return $html;
   }

  function _getBrowsingIconsAsHTML(){
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all_shown = $this->_count_all_shown;
      $params = $this->_environment->getCurrentParameterArray();
      if ( $this->getChoosenRubric() != 'all'  ){
         $params['selrubric'] = $this->getChoosenRubric();
      }
      if (!isset($params['mode']) or $params['mode'] == 'browse'){
         $params['mode'] = 'list_actions';
      }
      if ($this->_environment->getCurrentFunction()=='edit'){
         $edit_page = true;
      }else{
         $edit_page = false;
      }
      unset($params['select']);
      if ($interval > 0) {
         if ($count_all_shown != 0) {
            $num_pages = ceil($count_all_shown / $interval);
         } else {
            $num_pages = 1;
         }
         $act_page  = ceil(($from + $interval - 1) / $interval);
      } else {
         $num_pages = 1;
         $act_page  = 1;
      }

      // prepare browsing
      if ( $from > 1 ) {        // can I browse to the left / start?
         $browse_left = $from - $interval;
         if ($browse_left < 1) {
            $browse_left = 1;
         }
         $browse_start = 1;
      } else {
         $browse_left = 0;      // 0 means: do not browse
         $browse_start = 0;     // 0 means: do not browse
      }
      if ( $from + $interval <= $count_all_shown ) {  // can I browse to the right / end?
         $browse_right = $from + $interval;
         $browse_end = $count_all_shown - $interval + 1;
      } else {
         $browse_right = 0;     // 0 means: do not browse
         $browse_end = 0;       // 0 means: do not browse
      }
      // create HTML for browsing icons
      $html = '<div style="float:right;">';
      if ( $browse_start > 0 ) {
         $params['from'] = $browse_start;
         $image = '<span class="bold">&lt;&lt;</span>';
         if ($edit_page){
            $html .= '<input type="hidden" name="from" value="'.$browse_start.'"/>';
            $html .= '<input type="hidden" name="interval" value="'.$interval.'"/>';
            $html .= '<input type="hidden" name="count_all_shown" value="'.$count_all_shown.'"/>';
            $html .= '<a href="javascript:right_box_send(\'item_list_form\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH').'\');"">'.$image.'</a>'.LF;
         }else{
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_module,
                                         $this->_function,
                                         $params, $image,
                                         $this->_translator->getMessage('COMMON_BROWSE_START_DESC'),
                                         '',
                                         '',
                                         '',
                                         '',
                                         '',
                                         'class="index_system_link"'
                                        ).LF;

         }
      } else {
         $html .= '         <span style="font-weight:normal;">&lt;&lt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_left > 0 ) {
         $params['from'] = $browse_left;
         $image = '<span class="bold">&lt;</span>';
         if ($edit_page){
            $html .= '<input type="hidden" name="from" value="'.$browse_left.'"/>';
            $html .= '<input type="hidden" name="interval" value="'.$interval.'"/>';
            $html .= '<input type="hidden" name="count_all_shown" value="'.$count_all_shown.'"/>';
            $html .= '<a href="javascript:right_box_send(\'item_list_form\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH').'\');"">'.$image.'</a>'.LF;
         }else{
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_module, $this->_function,
                                         $params, $image,
                                         $this->_translator->getMessage('COMMON_BROWSE_LEFT_DESC'),
                                         '',
                                         '',
                                         '',
                                         '',
                                         '',
                                         'class="index_system_link"'
                                        ).LF;
         }
      } else {
         $html .= '         <span style="font-weight:normal;">&lt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_right > 0 ) {
         $params['from'] = $browse_right;
         $image = '<span class="bold">&gt;</span>';
         if ($edit_page){
            $html .= '<input type="hidden" name="interval" value="'.$interval.'"/>';
            $html .= '<input type="hidden" name="count_all_shown" value="'.$count_all_shown.'"/>';
            $html .= '<input type="hidden" name="from" value="'.$browse_right.'"/>';
            $html .= '<a href="javascript:right_box_send(\'item_list_form\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH').'\');"">'.$image.'</a>'.LF;
         }else{
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_module,
                                         $this->_function,
                                         $params,
                                         $image,
                                         $this->_translator->getMessage('COMMON_BROWSE_RIGHT_DESC'),
                                         '',
                                         '',
                                         '',
                                         '',
                                         '',
                                         'class="index_system_link"'
                                        ).LF;
         }
      } else {
         $html .= '         <span style="font-weight:normal;">&gt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_end > 0 ) {
         $params['from'] = $browse_end;
         $image = '<span class="bold">&gt;&gt;</span>';
         if ($edit_page){
            $html .= '<input type="hidden" name="from" value="'.$browse_end.'"/>';
            $html .= '<input type="hidden" name="interval" value="'.$interval.'"/>';
            $html .= '<input type="hidden" name="count_all_shown" value="'.$count_all_shown.'"/>';
            $html .= '<a href="javascript:right_box_send(\'item_list_form\',\'right_box_option\',\''.$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH').'\');"">'.$image.'</a>'.LF;
         }else{
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_module, $this->_function,
                                         $params,
                                         $image,
                                         $this->_translator->getMessage('COMMON_BROWSE_END_DESC'),
                                         '',
                                         '',
                                         '',
                                         '',
                                         '',
                                         'class="index_system_link"'
                                        ).LF;
         }
      } else {
         $html .= '         <span style="font-weight:normal;">&gt;&gt;</span>'.LF;
      }
      $html .= '</div>';
      return $html;
  }



   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $context_item = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSessionItem();
      if ( $this->getChoosenRubric() != 'all'  ){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.getMessage('COMMON_RUBRIC').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         switch ( strtoupper($this->getChoosenRubric()) ){
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
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
/*      if ($context_item->withActivatingContent()){
         if ( $this->getActivationLimit() == 2  ){
            $this->_additional_selects = true;
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.getMessage('COMMON_ACTIVATION_RESTRICTION').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $html_text .= '<span>'.getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            $new_params['selactivatingstatus'] = 1;
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
      }*/
      return $html;
   }

   function _getListInfosAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= $this->_getBrowsingIconsAsHTML().LF;
      $html .= '<div style="white-space:nowrap;">'.$this->_translator->getMessage('COMMON_PAGE').' '.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;


      $width = '';
      $current_browser = strtolower($this->_environment->getCurrentBrowser());
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width = 'width:180px;';
      }
      $html .= '<div class="right_box_main" style="'.$width.'">'.LF;

      $html .= '<table style="width:100%; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .='<tr>'.LF;
      $html .='<td>'.LF;
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_LIST_SHOWN_ENTRIES').' </span>';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $html .= '<span class="index_description">'.$this->_getDescriptionAsHTML().'</span>'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .= $this->_getRestrictionTextAsHTML();
      $html .= '</table>'.LF;

      $html .= $this->_getExpertSearchAsHTML();
      $html .= '</div>'.LF;

     return $html;
   }



   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

      $html .='<div id="profile_content" style="width:700px; background-color:#FFFFFF; text-align:left;">'.LF;
      $html .= '<script type="text/javascript"> '.LF;
      $html .= 'function quark(elem) { '.LF;
      $html .= 'var cookie_value = \'\'; '.LF;
      $html .= 'if (elem.checked) '.LF;
      $html .= 'cookie_value = elem.name + \'=1\' '.LF;
      $html .= 'else '.LF;
      $html .= 'cookie_value = elem.name + \'=0\''.LF;
      $html .= 'document.cookie=cookie_value; '.LF;
      $html .= '} '.LF;
      $html .= '</script>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['attach_view']);
      unset($params['attach_type']);
      unset($params['from']);
      unset($params['pos']);
      unset($params['mode']);
      $params['return_attach_item_list']= 'true';
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $this->_environment->getCurrentModule(),
                           $this->_environment->getCurrentFunction(),
                           $params,
                           'X',
                           '','', '', '', '', '', 'class="titlelink"');
      $html .= '<form style="padding:0px; margin:0px;" action="';
      $params = $this->_environment->getCurrentParameterArray();
      $html .= curl($this->_environment->getCurrentContextID(),
                    $this->_environment->getCurrentModule(),
                    $this->_environment->getCurrentFunction(),
                    $params
                   ).'" name="item_list_form" id="item_list_form"';
      $html .= ' method="post">'.LF;

      # post values from real item
      $post_values_orig = array();
      foreach ($this->_hidden_field_array as $field_name => $value) {
         if ( $field_name != 'from'
              and $field_name != 'count_all_shown'
              and $field_name != 'interval'
            ) {
            if ( is_array($value) ) {
               foreach ( $value as $key2 => $value2 ) {
                  $html .= '<input type="hidden" name="'.$field_name.'['.$key2.']" value="'.$this->_text_as_form($value2).'"/>'.LF;
               }
            } else {
               $html .= '<input type="hidden" name="'.$field_name.'" value="'.$this->_text_as_form($value).'"/>'.LF;
            }
            $post_values_orig[] = $field_name;
         }
      }
      if ( !empty($post_values_orig) ) {
         $html .= '<input type="hidden" name="orig_post_keys" value="'.$this->_text_as_form(implode('§',$post_values_orig)).'" />'.LF;
      }

      $html .= '<div>'.LF;
      $html .= '<div class="profile_title" style="float:right">'.$title.'</div>';
      if (count($this->_checked_ids)>0){
         $desc = ' ('.count($this->_checked_ids).' '.$this->_translator->getMessage('COMMON_ACTUAL_ATTACHED').')';
      }else{
         $desc = '';
      }
      $html .= '<h2 id="profile_title">'.$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH').$desc.'</h2>';
      $html .='</div>'.LF;

      $html .='<div style="padding:5px;">'.LF;
      if (($this->_environment->getCurrentModule() != CS_USER_TYPE) ){
         $html .='<div id="right_boxes_area" style="float:right; width:27%; padding-top:5px; vertical-align:top; text-align:left;">'.LF;
         $html .='<div style="width:180px;">'.LF;
         $current_context = $this->_environment->getCurrentContextItem();
         $list_box_conf = $current_context->getListBoxConf();
         $first_box = true;
         $title_string ='';
         $desc_string ='';
         $config_text ='';
         $size_string = '';
         $html .= $this->_getHiddenFieldsAsHTML();
         $html .='<div>'.LF;
         $params = $this->_environment->getCurrentParameterArray();
         $html .= '<div class="commsy_no_panel" style="margin-bottom:1px;">'.LF;
         $tempMessage = getRubricMessageTageName($this->_environment->getCurrentModule(),true);
         $html .= $this->_getListInfosAsHTML();
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $context_item = $this->_environment->getCurrentContextItem();
         /*********Expert Search*******/
         if ( !strstr($list_box_conf,'search_nodisplay')
            and ($context_item->withActivatingContent()
                 or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                 or $this->_environment->getCurrentModule() == CS_USER_TYPE
                 or $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                 or $this->_environment->getCurrentModule() == CS_TODO_TYPE
            )
         ){
            if ( $first_box ){
               $first_box = false;
               $additional_text ='';
            }else{
               $additional_text =',';
            }
            if ($this->_environment->getCurrentModule() != 'campus_search'){
               $title_string .= $additional_text.'"'.$this->_translator->getMessage('COMMON_RESTRICTIONS').'"';
            }else{
               $title_string .= $additional_text.'"'.$this->_translator->getMessage('COMMON_RESTRICTION_SEARCH').'"';
            }$desc_string .= $additional_text.'""';
            $size_string .= $additional_text.'"10"';
            $parameter_array = $this->_environment->getCurrentParameterArray();
            if (
                (isset($parameter_array['attribute_limit']) and $parameter_array['attribute_limit']!='0')
                or (isset($parameter_array['selactivatingstatus']) and $parameter_array['selactivatingstatus']!='0')
                or (isset($parameter_array['selstatus']) and $parameter_array['selstatus']!='0')
                or (isset($parameter_array['selrubric']) and !empty($parameter_array['selrubric']))
                or (isset($parameter_array['selrestriction']) and !empty($parameter_array['selrestriction']))
                or ($this->_environment->getCurrentModule() == 'campus_search')
               ){
                if ($this->_environment->getCurrentModule() != CS_USER_TYPE or (isset($parameter_array['selstatus']) and $parameter_array['selstatus']=='3')){
                   $config_text .= $additional_text.'true';
                }else{
                   $config_text .= $additional_text.'false';
                }
            }else{
                $config_text .= $additional_text.'false';
            }
 #           $html .= $this->_getExpertSearchAsHTML();
         }

         $html .='</div>'.LF;



         $html .='</div>'.LF;
         $current_browser = strtolower($this->_environment->getCurrentBrowser());
         $current_browser_version = $this->_environment->getCurrentBrowserVersion();
         if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
            $width= ' width:100%; padding-right:10px;';
         }else{
            $width= '';
         }

         if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
            $html .='</div>'.LF;
            $html .='<div class="index_content_display_width" style="'.$width.'padding-top:5px; vertical-align:bottom;">'.LF;
         }else{
            $html .='</div>'.LF;
            $html .='<div style="width:100%; padding-top:5px; vertical-align:bottom;">'.LF;
         }
         $params = $this->_environment->getCurrentParameterArray();
         if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
            $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
         }
         $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
      }else{
         $html .='</div>'.LF;
         $html .='<div style="width:100%; vertical-align:bottom; padding:5px;">'.LF;
         $params = $this->_environment->getCurrentParameterArray();
         if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
            $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
         }
         $html .= '<table class="list" style="width: 690px; border-collapse: collapse;" summary="Layout">'.LF;
      }
      $html .= $this->_getTableheadAsHTML();
      if (!$this->_clipboard_mode){
         $html .= $this->_getContentAsHTML();
      }else{
         $html .= $this->_getClipboardContentAsHTML();
      }
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= $this->_getTablefootAsHTML();
      }
      $html .= '</table>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='</form>'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }


}
?>