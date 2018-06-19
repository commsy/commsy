<?php
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
 *  generic upper class for CommSy list-views
 */
class cs_copy_index_view extends cs_index_view {

   var $_clipboard_id_array=array();
   var $_selected_institution = NULL;
   var $_available_institutions = NULL;
   var $_selected_topic = NULL;
   var $_available_topics = NULL;
   var $_selected_group = NULL;
   var $_available_groups = NULL;
   var $_selected_tag_array = array();
   var $_selected_buzzword = NULL;
   var $_available_buzzwords = NULL;
   var $_include_mootools = false;
   var $_show_netnavigation_box = true;

   /**
    * int - begin of list
    */
   var $_from = NULL;

   /**
    * int - length of shown list
    */
   var $_interval = NULL;

   /**
    * string - with search_text as keys
    */
   var $_search_text = NULL;

   var $_show_buzzwords_box = false;
   var $_show_tag_box = false;

   /*
    * array containing all search expressions to be highlighted
    */
   var $_search_array = array();

   /**
    * string - the current sort key
    */
   var $_sort_key = NULL;

   var $_with_checkboxes = true;
   /**
    * array - array of possible sort keys
    */
   var $_sort_keys = NULL;

   /**
    * int - id of item, all shown entries are linked to
    */
   var $_linked_to = NULL;

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;
   var $_count_all_shown = NULL;

   /**
    * string - containing the title of the list view
    */
   var $_title = NULL;

   /**
    * array - containing the actions of the list view
    */
   var $_actions = NULL;

   var $_action_title = '';
   /**
    * list - containing the content of the list view
    */
   var $_list = NULL;
   var $_list_of_read_entry_ids = NULL;

   /**
    * string - containing a ahref mark i.e. "http://www.commsy.net/index.html#fragment"
    */
   var $_fragment = NULL;

   var $_checked_ids = array();
   var $_dontedit_ids = array();
   var $_has_checkboxes = false;
   var $_ref_iid = 0;
   var $_ref_user = 0;
   var $_ref_item = 0;
   var $_is_attached_list = false;
   var $_display_title = true;
   var $_with_form_fields = true;
   var $_clipboard_mode = false;
   var $_last_sort_criteria = -1;
   var $_count_headlines = 0;
   var $_additional_selects = false;
   var $_attribute_limit = Null;
   var $_activation_limit = 2;

   var $_colspan = 4;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function __construct($params) {
      $this->_with_form_fields = true;
      if ( !empty($params['with_form_fields']) ) {
         $this->_with_form_fields = $params['with_form_fields'];
      }
      cs_view::__construct($params);
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->withTags() ){
         $this->_show_tag_box = true;
      }
      if ( $current_context->withBuzzwords() ){
         $this->_show_buzzwords_box = true;
      }
   }




   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

      $html .='<div id="copy_content">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['show_copies']);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $this->_environment->getCurrentModule(),
                           $this->_environment->getCurrentFunction(),
                           $params,
                           'X',
                           '','', '', '', '', '', 'class="titlelink"');
      $html .='<div>'.LF;
      $html .= '<div class="copy_title" style="float:right">'.$title.'</div>';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $tempMessage = '<img src="images/commsyicons_msie6/22x22/copy.gif" style="vertical-align:bottom;"/>';
      } else {
         $tempMessage = '<img src="images/commsyicons/22x22/copy.png" style="vertical-align:bottom;"/>';
      }
      $html .= '<h2 id="copy_title">'.$tempMessage.'&nbsp;'.$this->_translator->getMessage('MYAREA_MY_COPIES').'</h2>';
      $html .='</div>'.LF;
      $html .='<div style="padding:10px;">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      $html .= '<form style="padding:0px; margin:0px;" action="';
      $html .= curl($this->_environment->getCurrentContextID(),
                    $this->_environment->getCurrentModule(),
                    $this->_environment->getCurrentFunction(),
                    $params
                   ).'" method="post">'.LF;
       $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
      $html .= $this->_getTableheadAsHTML();
      $html .= $this->_getContentAsHTML();
      $html .= $this->_getTablefootAsHTML();
      $html .= '</table>'.LF;
      $html .='</form>'.LF;

      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }

   // @segment-begin 68626 _getViewActionsAsHTML()-actions-for-action-box-under-annoucement-index
   /** get View-Actions of this index view
    * this method returns the index actions as html
    *
    * @return string index actions
    */
   function _getViewActionsAsHTML () {
      $user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt; font-weight:normal;">'.LF;
      $html .= '   <option value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      $html .= '   <option selected="selected" value="1">'.$this->_translator->getMessage('CLIPBOARD_PASTE_BUTTON').'</option>'.LF;
      $html .= '   <option value="2">'.$this->_translator->getMessage('CLIPBOARD_DELETE_BUTTON').'</option>'.LF;
      $html .= '</select>'.LF;
      $html .= '<input type="submit" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_COPY_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
   }

   function _getItemAsHTML($item,$pos=0,$with_links=TRUE) {
      $html = '';
      $shown_entry_number = $pos;
      $shown_entry_number = $pos + $this->_count_headlines;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      if ($this->_clipboard_mode){
         $sort_criteria = $item->getContextID();
         if ( $sort_criteria != $this->_last_sort_criteria ) {
            $this->_last_sort_criteria = $sort_criteria;
            $this->_count_headlines ++;
            $room_manager = $this->_environment->getProjectManager();
            $sort_room = $room_manager->getItem($sort_criteria);
            $html .= '                     <tr class="list"><td '.$style.' width="100%" style="font-weight:bold;" colspan="5">'."\n";
            if ( empty($sort_room) ) {
               $community_manager = $this->_environment->getCommunityManager();
               $sort_community = $community_manager->getItem($sort_criteria);
               if ( !empty($sort_community) ) {
                  $html .= '                        '.$this->_translator->getMessage('COPY_FROM').'&nbsp;'.$this->_translator->getMessage('COMMON_COMMUNITY_ROOM_TITLE').'&nbsp;"'.$sort_community->getTitle().'"'."\n";
               }
            } elseif( $sort_room->isPrivateRoom() ){
               $user = $this->_environment->getCurrentUserItem();
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PRIVATEROOM').'&nbsp;"'.$user->getFullname().'"'.LF;
            }elseif( $sort_room->isGroupRoom() ){
              $html .= '                        '.$this->_translator->getMessage('COPY_FROM_GROUPROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }else {
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PROJECTROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }
            $html .= '                     </td></tr>'."\n";
            if ( $style=='class="odd"' ){
               $style='class="even"';
            }else{
               $style='class="odd"';
            }
         }
      }
      $html  .= '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      $fileicons = $this->_getItemFiles($item, $with_links);
      if ( !empty($fileicons) ) {
         $fileicons = ' '.$fileicons;
      }
      if ( !(isset($_GET['mode']) and $_GET['mode']=='print') ) {
         $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
         $user = $this->_environment->getCurrentUser();
         if($item->isNotActivated() and !($item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
            $html .= ' disabled="disabled"'.LF;
         }elseif ( isset($checked_ids)
              and !empty($checked_ids)
              and in_array($key, $checked_ids)
            ) {
            $html .= ' checked="checked"'.LF;
            if ( in_array($key, $dontedit_ids) ) {
               $html .= ' disabled="disabled"'.LF;
            }
         }
         $html .= '/>'.LF;
         $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         $html .= '      </td>'.LF;
         if ($item->isNotActivated()){
            $title = $item->getTitle();
            $title = $this->_compareWithSearchText($this->_text_as_html_short($title));
            $user = $this->_environment->getCurrentUser();
            if($item->getCreatorID() == $user->getItemID() or $user->isModerator()){
               $params = array();
               $params['iid'] = $item->getItemID();
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
                                  CS_ANNOUNCEMENT_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_ANNOUNCEMENT_TYPE.$item->getItemID());
               unset($params);
               if ( !$this->_environment->inPrivateRoom() ) {
                  $title .= $this->_getItemChangeStatus($item);
                  $title .= $this->_getItemAnnotationChangeStatus($item);
               }
            }
            $activating_date = $item->getActivatingDate();
            if (strstr($activating_date,'9999-00-00')){
               $title .= BR.$this->_translator->getMessage('COMMON_NOT_ACTIVATED');
            }else{
               $title .= BR.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
            }
            $title = '<span class="disabled">'.$title.'</span>';
            $html .= '      <td '.$style.'>'.$title.LF;
         }else{
             if($with_links) {
                $html .= '      <td '.$style.'>'.$this->_getItemTitle($item).LF;
             } else {
                $title = $this->_text_as_html_short($item->getTitle());
                $html .= '      <td '.$style.'>'.$title.LF;
             }
         }
      } else {
         $html .= '      <td colspan="2" '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      }
      $text = '';
      switch ( mb_strtoupper($item->getItemType(), 'UTF-8') ){
         case 'ANNOUNCEMENT':
            $text .= $this->_translator->getMessage('ANNOUNCEMENTS');
            break;
         case 'DATE':
            $text .= $this->_translator->getMessage('DATES');
            break;
         case 'DISCUSSION':
            $text .= $this->_translator->getMessage('DISCUSSIONS');
            break;
         case 'GROUP':
            $text .= $this->_translator->getMessage('GROUPS');
            break;
         case 'INSTITUTION':
            $text .= $this->_translator->getMessage('INSTITUTIONS');
            break;
         case 'MATERIAL':
            $text .= $this->_translator->getMessage('MATERIALS');
            break;
         case 'MYROOM':
            $text .= $this->_translator->getMessage('COMMON_ROOMS');
            break;
         case 'PROJECT':
            $text .= $this->_translator->getMessage('PROJECTS');
            break;
         case 'TODO':
            $text .= $this->_translator->getMessage('TODOS');
            break;
         case 'TOPIC':
            $text .= $this->_translator->getMessage('TOPICS');
            break;
         case 'USER':
            $text .= $this->_translator->getMessage('COMMON_USER_INDEX');
            break;
         case 'ACCOUNT':
            $text .= $this->_translator->getMessage('COMMON_ACCOUNTS');
            break;
         default:
            $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_index_view('.__LINE__.') ';
            break;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$text.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;" colspan="2">'.$this->_getItemModificationDate($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   function _getItemTitle($item){
      $title = $this->_text_as_html_short($item->getTitle());
      return $title;
   }

   function _getTableheadAsHTML () {
      $html = '   <tr class="head">'.LF;
      $html .= '      <td class="head" style="width:48%;" colspan="2">';
      $html .= $this->_translator->getMessage('COMMON_TITLE');
      $html .= '</td>'.LF;

      $html .= '      <td style="width:20%; font-size:8pt;" class="head">';
      $html .= $this->_translator->getMessage('COMMON_RUBRIC');
      $html .= '</td>'.LF;

      $html .= '      <td style="width:20%; font-size:8pt;" class="head">';
      $html .= $this->_translator->getMessage('COMMON_MODIFIED_BY');
      $html .= '</td>'.LF;

      $html .= '      <td style="width:12%; font-size:8pt;" class="head" colspan="2">';
      $html .= $this->_translator->getMessage('COMMON_MODIFIED_AT');
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }


   function _getTablefootAsHTML() {
      $html  = '   <tr id="index_table_foot" class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="2"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="2" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="foot_right" colspan="4" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
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



}
?>