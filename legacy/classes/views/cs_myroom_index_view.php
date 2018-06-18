<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
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

$this->includeClass(CONTEXT_INDEX_VIEW);
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: news
 */
class cs_myroom_index_view extends cs_context_index_view {

   var $_selected_community_room_limit = NULL;

   var $_selected_time = 0;

   var $_selected_room_status = 0;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_context_index_view::__construct($params);
      $this->_room_type = CS_MYROOM_TYPE;
   }

   function getSelectedTime () {
      return $this->_selected_time;
   }

   function setSelectedTime ($value) {
      $this->_selected_time = $value;
   }

   function getSelectedRoomStatus () {
      return $this->_selected_room_status;
   }

   function setSelectedRoomStatus ($value) {
      $this->_selected_room_status = $value;
   }

   function _getIndexPageHeaderAsHTML(){
     $current_context_id = $this->_environment->getCurrentContextID();
     $current_portal_id = $this->_environment->getCurrentPortalID();

      $html = '';
      $html .='<div style="width:100%;">'.LF;
      $html .='<div style="height:30px;">'.LF;
      $html .='<div style="width:100%;">'.LF;
      $html .='<div style="vertical-align:bottom;">'.LF;
      if ( $this->_environment->inPrivateRoom() ) {
         $image = '';
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/32x32/room.gif" style="vertical-align:bottom;"/>';
         } else {
            $image = '<img src="images/commsyicons/32x32/room.png" style="vertical-align:bottom;"/>';
         }
      }
      $html .= '<div style="float:left;"><h2 class="pagetitle">'.$image.' '.$this->_translator->getMessage('MYROOM_INDEX');
      $html .= '</h2></div>'.LF;
      #$html .= '<div style="float:right;"><a href="#"><img id="new_icon" src="images/commsyicons/48x48/config/privateroom_home_options.png" height=24></a></div>';

      $html .= '<div style="float:right;">'.LF;
      $html .= '<div class="portlet-configuration">'.LF;
      $html .= '<div class="portlet-header-configuration ui-widget-header" style="width:200px;">'.LF;
      $html .= $this->_translator->getMessage('HOME_MYROOM_CONFIGURATION').LF;
      $html .= '<div style="float:right;">'.LF;
      $html .= '<a href="#"><img id="new_icon" src="images/commsyicons/48x48/config/privateroom_home_options.png"  style="height:0px;" /></a>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="width:100%; clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }


   function _getListInfosAsHTML ($title) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= $this->_getBrowsingIconsAsHTML().LF;
      $html .= '<div style="white-space:nowrap;">'.$this->_translator->getMessage('COMMON_PAGE').' '.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;


      $width = '';
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width = 'width:250px;';
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


      $html .= '<div class="listinfoborder"></div>'.LF;

      $html .= '<table style="width:100%; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .='<tr>'.LF;
      $html .='<td>'.LF;
      $connection = $this->_environment->getCurrentModule();
      $text = '';
      switch ( mb_strtoupper($connection, 'UTF-8') ){
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
         case 'CAMPUS_SEARCH':
            $text .= $this->_translator->getMessage('COMMON_ENTRIES');
            break;
         default:
            $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_index_view(1913) ';
            break;
      }
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_ALL_LIST_ENTRIES',$text).':</span> ';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $html .= $this->_count_all.''.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='<tr>'.LF;
      $html .= '<td class="infocolor">';
      $html .= $this->_translator->getMessage('COMMON_PAGE_ENTRIES').':';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">10'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='</table>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

     return $html;
   }


   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;
      $html .= $this->_getIndexPageHeaderAsHTML();
      #$html .= '<div style="width:100%; float:right;"><div style="float:right;"><a href="#"><img id="new_icon" src="images/commsyicons/22x22/new.png"></a></div></div>';

      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='<div class="index_content_display_width" style="width:100%; padding-top:5px; vertical-align:bottom;">'.LF;
      }else{
         $html .='<div style="width:100%; padding-top:5px; vertical-align:bottom;">'.LF;
      }


      for ($i=0; $i< 4; $i++){
         $html_array[$i] = '<div class="column" style="width:25%;">';
      }

      $privateroom_item = $this->_environment->getCurrentContextItem();
      $column_array = $privateroom_item->getMyroomConfig();

      if(!empty($column_array)){
         $list = $this->_list;
         $user = $this->_environment->getCurrentUserItem();
         $column_count = 0;
         foreach($column_array as $column){
            foreach($column as $room_id){
               $current_item = $list->getFirst();
               while ( $current_item ) {
                  if($current_item->getItemID() == $room_id){
                     $html_text = '<div class="portlet">'.LF;
                     $html_text .= $this->_getRoomWindowAsHTML($current_item);
                     $html_text .= '</div>'.LF;
                     $html_array[$column_count] .= $html_text;
                  }
                  $current_item = $list->getNext();
               }
            }
            $column_count++;
         }
      } else {
         $list = $this->_list;
         $user = $this->_environment->getCurrentUserItem();
         $column_count = 0;
         if ( isset($list)) {
            $current_item = $list->getFirst();
            while ( $current_item ) {
               if ($column_count == 4){
                  $column_count = 0;
               }
               $html_text = '<div class="portlet">'.LF;
               $html_text .= $this->_getRoomWindowAsHTML($current_item);
               $html_text .= '</div>'.LF;
               $html_array[$column_count] .= $html_text;
               $column_count++;
               $current_item = $list->getNext();
            }
         }
      }
      for ($i=0; $i< 4; $i++){
         $html_array[$i] .= '</div>';
      }
      foreach ($html_array as $html_entry){
         $html .= $html_entry;
      }
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var ajax_cid = '.$this->_environment->getCurrentContextItem()->getItemID().';'.LF;
      $html .= 'var ajax_function = "privateroom_myroom";'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;
      return $html;
   }

  function _getExpertSearchAsHTML(){
     $html  = '';
     $context_item = $this->_environment->getCurrentContextItem();
     $module = $this->_environment->getCurrentModule();
     $width = '235';
     $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
     $html .= '<div class="right_box">'.LF;
     #$html .= '         <noscript>';
     $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_RESTRICTIONS').'</div>';
     #$html .= '         </noscript>';
     $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;
     if ( $this->_environment->inPrivateRoom() ) {
        $html .= $this->_getAdditionalFormFieldsAsHTML2().LF;
     } else {
        $html .= $this->_getAdditionalFormFieldsAsHTML().LF;
     }
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
      $selroomtype = $this->getSelectedRoomType();

      $html = '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_STATUS').BRLF;
      // STATUS SELECTION FIELD
      // jQuery
      //$html .= '   <select name="selroomtype" size="1" style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select name="selroomtype" size="1" style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" id="submit_form">'.LF;
      // jQuery
      $html .= '      <option value="2"';
      if ( empty($selroomtype) || $selroomtype == 2 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;

      $html .= '      <option value="3"';
      if ( !empty($selroomtype) and $selroomtype == 3 ) {
         $html .= ' selected="selected"';
      }
      $text = $this->_translator->getMessage('USER_MODERATORS');
      $html .= '>'.$text.'</option>'.LF;

      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->isCommunityRoom()) {
         $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
         $html .= '      <option value="11"';
         if ( !empty($selroomtype) and $selroomtype == 11 ) {
            $html .= ' selected="selected"';
         }
         $text = $this->_translator->getMessage('USER_PROJECT_USER');
         $html .= '>'.$text.'</option>'.LF;
         $html .= '      <option value="12"';
         if ( !empty($selroomtype) and $selroomtype == 12 ) {
            $html .= ' selected="selected"';
         }
         $text = $this->_translator->getMessage('USER_PROJECT_CONTACT_MODERATOR');
         $html .= '>'.$text.'</option>'.LF;
      }

      $html .= '   </select>'.LF;
      $html .='</div>';
      return $html;
   }

   // in private room - OLD style
   function _getAdditionalFormFieldsAsHTML2 ($field_length=14.5) {
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $width = '235';
      $context_item = $this->_environment->getCurrentContextItem();
      $selroomstatus = $this->getSelectedRoomStatus();

      $html = '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_STATUS').BRLF;
      // STATUS SELECTION FIELD
      // jQuery
      $html .= '   <select name="selroomstatus" size="1" style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" id="submit_form">'.LF;
      // jQuery
      $html .= '      <option value="1"';
      if ( empty($selroomstatus) || $selroomstatus == 1 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('ROOM_ONGOING').'</option>'.LF;

      $html .= '      <option value="2"';
      if ( !empty($selroomstatus) and $selroomstatus == 2 ) {
         $html .= ' selected="selected"';
      }
      $text = $this->_translator->getMessage('ROOM_CLOSED');
      $html .= '>'.$text.'</option>'.LF;

      $html .= '   </select>'.LF;
      $html .='</div>';
      return $html;
   }

   function _getEditAction ( $item, $user, $module = '' ) {
      $html  = '';
      if ( $item->mayEdit($user) and $this->_with_modifying_actions ) {
         if ( empty($module) ) {
            $module = $this->_environment->getCurrentModule();
         }
         $params = array();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= ahref_curl( $item->getItemID(),
                                          'configuration',
                                          'room_options',
                                          $params,
                                          $image,
                                          $this->_translator->getMessage('COMMON_EDIT_ITEM')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_EDIT_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }


   function _getRoomActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html = $this->_getEditAction($item,$current_user);
      return $html;
   }



   /** get room window as html
    *
    * param cs_project_item project room item
    */
   function _getRoomWindowAsHTML ($item) {
     global $c_single_entry_point;
      $current_user = $this->_environment->getCurrentUserItem();
      $may_enter = $item->mayEnter($current_user);
      $title = $item->getTitle();
      $cs_color['room_title'] = '';
      $cs_color['room_background']  = '';
      $cs_color['tableheader']  = '';

      $style = '    style="border:1px solid  '.$cs_color['tableheader'].'; margin:0px; padding:5px 10px 5px 10px; ';
      $style .= 'background: url(../'.$c_single_entry_point.'?cid='.$item->getItemID().'&mod=picture&fct=getfile&picture=' . $color_array['schema'] . '_cs_gradient_24.png) repeat-x;';
      $style .= 'background-color: '.$cs_color['tableheader'].';';

      if (isset($cs_color['room_title'])){
         $style .= ' color:'.$cs_color['room_title'].' "';
      }else{
         $style .= ' color:#000000; "';
      }


      $html  = '';
#      $html = '<td style="width:25%; padding:3px; vertical-align: middle;">'.LF;
      $html  = '<div id="'.$item->getItemID().'" class="portlet-header" '.$style.'><div style="float:right;"><a name="myroom_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div>';
         $params['iid']=$item->getItemID();
         $display_title = chunkText($this->_text_as_html_short($title),20)."\n";
         if ($item->isLocked()) {
            $title = $this->_text_as_html_short($title).' ('.$this->_translator->getMessage('PROJECTROOM_LOCKED').')';
         } elseif ($item->isClosed()) {
            $title = $this->_text_as_html_short($title).' ('.$this->_translator->getMessage('PROJECTROOM_CLOSED').')';
         }
            if (isset($cs_color['room_title'])){
               $style = ' color:'.$cs_color['room_title'].' "';
            }else{
               $style = ' color:#000000; "';
            }
         $html .= ahref_curl($item->getItemID(),'home','index',array(),$display_title,$title,'','','','','','style="'.$style.'"');
      $html .= '</div>'.LF;
      $html .= '<table class="room_window'.$item->getItemID().'" summary="Layout" style="width:100%; border-collapse:collapse;">'.LF;
      $html .= '<tr><td colspan="4" class="detail_view_content_room_window'.$item->getItemID().'">'.LF;

      $html .='<table style="width: 100%;" summary="Layout">';


      $html .= '<tr><td class="detail_view_content_room_window'.$item->getItemID().'">'.LF;
         if ($item->isClosed() ) {
            $curl = curl($item->getItemID(), 'home', 'index','','');
            $html .= '<a href="'.$curl.'">';
            $html .= '<img alt="door" src="images/door_open_small.gif" style="vertical-align: middle; "/>'.LF;
            $html .= '</a>';
            $html .= ' '.$this->_translator->getMessage('COMMON_CLOSED_SINCE').' '.$this->_translator->getDateInLang($item->getModificationDate()).LF;
         }elseif ($item->isLocked()) {
            $html .= ' ('.$this->_translator->getMessage('PROJECTROOM_LOCKED').')'.LF;
            $html .= '<img alt="door" src="images/door_closed_small.gif" style="vertical-align: middle; "/>'.LF;
            $html .= ' '.$this->_translator->getMessage('COMMON_LOCKED_SINCE').' '.$this->_translator->getDateInLang($item->getModificationDate()).LF;
         }else{
            $curl = curl($item->getItemID(), 'home', 'index','','');
            $html .= '<a href="'.$curl.'">';
            $html .= '<img alt="door" src="images/door_open_small.gif" style="vertical-align: middle; "/>'.LF;
            $html .= '</a>';
            $html .= ' '.$this->_translator->getMessage('COMMON_OPENED_SINCE').' '.$this->_translator->getDateInLang($item->getCreationDate()).LF;
         }
      $html .= '</td></tr>'.LF;


      $html .= '<tr><td class="detail_view_content_room_window'.$item->getItemID().'">'.LF;
      $context = $this->_environment->getCurrentContextItem();
      $count_total = $item->getPageImpressions($context->getTimeSpread());
      if ( $count_total == 1 ) {
         $html .= $count_total.'&nbsp;'.$this->_translator->getMessage('ACTIVITY_PAGE_IMPRESSIONS_SINGULAR').'';
         $html .= BRLF;
      } else {
         $html .= $count_total.'&nbsp;'.$this->_translator->getMessage('ACTIVITY_PAGE_IMPRESSIONS').'';
         $html .= BRLF;
      }
      $html .= '</td></tr>'.LF;


      $html .= '<tr><td class="detail_view_content_room_window'.$item->getItemID().'">'.LF;
      // Get number of new entries

      $count_total = $item->getNewEntries($context->getTimeSpread());
      if ( $count_total == 1 ) {
         $html .= $count_total.'&nbsp;'.$this->_translator->getMessage('ACTIVITY_NEW_ENTRIES_SINGULAR');
         $html .= BRLF;
      } else {
            $html .= $count_total.'&nbsp;'.$this->_translator->getMessage('ACTIVITY_NEW_ENTRIES');
         $html .= BRLF;
      }

      $html .= '</td></tr>'.LF;

      $html .= '<tr><td class="detail_view_content_room_window'.$item->getItemID().'">'.LF;
      // Get percentage of active members
      $active = $item->getActiveMembers($context->getTimeSpread());
      $all_users = $item->getAllUsers();
      $percentage = 0;
      if ( !empty($all_users) ) {
         $percentage = round($active / $all_users * 100);
      }
      $html .= $this->_translator->getMessage('ACTIVITY_ACTIVE_MEMBERS').':'.BRLF;
      $html .= '         <div class="gauge'.$item->getItemID().'">'.LF;
      if ( $percentage >= 5 ) {
         $html .= '            <div class="gauge-bar'.$item->getItemID().'" style="width:'.$percentage.'%; color: white;">'.$active.'</div>'.LF;
      } else {
         $html .= '            <div class="gauge-bar'.$item->getItemID().'" style="float:left; width:'.$percentage.'%;">&nbsp;</div>'.LF;
         $html .= '            <div style="font-size: 8pt; padding-left:3px; font-color: white;">'.$active.'</div>'.LF;
      }
      $html .= '         </div>'.LF;
      $html .= '</td></tr>'.LF;
      $html .= '</table>'.LF.LF;
      $html .= '</td></tr>'.LF;
      $html .= '</table>'.LF.LF;
#      $html .='</td>';
      return $html;
   }

   function getInfoForHeaderAsHTML () {
      global $cs_color;
      $retour = parent::getInfoForHeaderAsHTML();
      if ( !empty($this->_list) ) {
         $retour .= '   <!-- BEGIN Styles -->'.LF;
         $retour .= '   <style type="text/css">'.LF;
         $session = $this->_environment->getSession();
         $session_id = $session->getSessionID();
         $retour .= '    img { border: 0px; }'.LF;
         $retour .= '    img.logo_small { width: 40px; }'.LF;
         $retour .= '    td.header_left_no_logo { text-align: left; width:1%; vertical-align: middle; font-size: x-large; font-weight: bold; height: 50px; padding-top: 3px;padding-bottom: 3px;padding-right: 3px; padding-left: 15px; }'.LF;
         $item = $this->_list->getFirst();
         while(!empty($item)){
            $color_array = $item->getColorArray();
            $cs_color['room_title'] = '';
            $cs_color['room_background']  = '';
            $cs_color['tableheader']  = '';
            $retour .= '    table.room_window'.$item->getItemID().' {width: 17em; border:1px solid  '.$cs_color['tableheader'].'; margin:0px; padding:5px 10px 5px 10px; ';
            if ($color_array['schema']=='SCHEMA_OWN'){
               if ($item->getBGImageFilename()){
                  global $c_single_entry_point;
                  if ($item->issetBGImageRepeat()){
                     $retour .= 'background: url('.$c_single_entry_point.'?cid='.$item->getItemID().'&mod=picture&fct=getfile&picture='.$item->getBGImageFilename().') repeat; ';
                  }else{
                     $retour .= 'background: url('.$c_single_entry_point.'?cid='.$item->getItemID().'&mod=picture&fct=getfile&picture='.$item->getBGImageFilename().') no-repeat; ';
                  }
               }
            }else{
               if (isset($color_array['repeat_background']) and $color_array['repeat_background'] == 'xy'){
                  $retour .= 'background: url(css/images/bg-'.$color_array['schema'].'.jpg) repeat; ';
               }elseif (isset($color_array['repeat_background']) and $color_array['repeat_background'] == 'x'){
                  $retour .= 'background: url(css/images/bg-'.$color_array['schema'].'.jpg) repeat-x; ';
               }elseif (isset($color_array['repeat_background']) and $color_array['repeat_background'] == 'y'){
                  $retour .= 'background: url(css/images/bg-'.$color_array['schema'].'.jpg) repeat-y; ';
               }else{
                  $retour .= 'background: url(css/images/bg-'.$color_array['schema'].'.jpg) no-repeat; ';
               }
            }
            $retour .= 'background-color: '.$color_array['content_background'].';';
            if (isset($color_array['page_title'])){
               $retour .= 'color:'.$color_array['page_title'].' }';
            }else{
               $retour .= 'color:#000000; }';
            }
            $retour .= '    td.detail_view_content_room_window'.$item->getItemID().' { width: 17em; padding: 3px;text-align: left; border-bottom: 1px solid '.$cs_color['tableheader'].';}'.LF;
            $retour .= '    td.detail_view_title_room_window'.$item->getItemID().' {background-color: '.$cs_color['tableheader'].'; color: '.$cs_color['room_title'].'; padding: 0px;text-align: left;}'.LF;
            $retour .= '    td.detail_view_title_room_window'.$item->getItemID().' a {background-color: '.$cs_color['tableheader'].'; color: '.$cs_color['room_title'].'; padding: 0px;text-align: left;}'.LF;
            $retour .= '    td.detail_view_title_room_window'.$item->getItemID().' a:hover {background-color: '.$cs_color['tableheader'].'; color: '.$cs_color['room_title'].'; padding: 0px;text-align: left;}'.LF;
            $retour .= ' .gauge'.$item->getItemID().' { background-color: #FFFFFF; width: 100%; margin: 2px 0px; border: 1px solid #666; }'.LF;
            $retour .= ' .gauge-bar'.$item->getItemID().' { background-color: '.$cs_color['tableheader'].'; text-align: right; font-size: 8pt; color: black; }'.LF;


            $item = $this->_list->getNext();
         }
         $retour .= '   </style>'."\n";
         $retour .= '   <!-- END Styles -->'."\n";
      }
      $retour .= $this->_initDropDownMenus();
      return $retour;
   }

   function _initDropDownMenus(){
      $privateroom_item = $this->_environment->getCurrentContextItem();
      $action_array = array();
      $html = '';

      $isset_display_config = $privateroom_item->issetMyroomDisplayConfig();
      $myroom_array = $privateroom_item->getMyroomDisplayConfig();

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['checked']  = "scroll_start";
      $temp_array['text']  = "";
      $temp_array['value']  = "";
      $action_array[] = $temp_array;

      $first_char = 'a';
      $list = $this->_list;
      $room_item = $list->getFirst();
      while($room_item){
         
         // archive
         $room_title = $room_item->getTitle();
         if ( strtolower($room_title[0]) < $first_char
              and $room_item->isClosed()
            ) {           
            $temp_array = array();
            $temp_array['dropdown_image']  = "new_icon";
            $temp_array['checked']  = "headline";
            $temp_array['text']  = $this->_translator->getMessage('PORTAL_ARCHIVED_ROOMS');
            $temp_array['value']  = "";
            $action_array[] = $temp_array;
         }
         $first_char = strtolower($room_title[0]);

         $temp_array = array();
         $temp_array['dropdown_image']  = "new_icon";
         $temp_array['text']  = str_replace('"','&quot;',$this->_text_as_html_short($room_item->getTitle()));
         $temp_array['value'] = $room_item->getItemID();
         if(in_array($room_item->getItemID(), $myroom_array) or !$isset_display_config){
            $temp_array['checked']  = "checked";
         } else {
            $temp_array['checked']  = "";
         }
         $action_array[] = $temp_array;
         $room_item = $list->getNext();
      }

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['checked']  = "scroll_end";
      $temp_array['text']  = "";
      $temp_array['value']  = "";
      $action_array[] = $temp_array;

      // init drop down menu
      if ( !empty($action_array)
           and count($action_array) >= 1
         ) {
         $html .= '<script type="text/javascript">'.LF;
         $html .= '<!--'.LF;
         $html .= 'var dropDownMyRooms = new Array(';
         $first = true;
         foreach ($action_array as $action) {
            if ( $first ) {
               $first = false;
            } else {
               $html .= ',';
            }
            $html .= 'new Array("'.$action['dropdown_image'].'","'.$action['checked'].'","'.$action['text'].'","'.$action['value'].'")';
         }
         $html .= ');'.LF;
         $html .= 'var myroomSaveButton = "'.$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON').'";'.LF;
         $html .= '-->'.LF;
         $html .= '</script>'.LF;
      }
      return $html;
   }


   function _getTableheadAsHTML () {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
      $html .= '      <td class="head" style="width:40%;" colspan="2">';
      if ( $this->getSortKey() == 'title' ) {
         $params['sort'] = 'title_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'title_rev' ) {
         $params['sort'] = 'title';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'title';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_TITLE'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:35%; font-size:8pt;" class="head">';
      $html .= $this->_translator->getMessage('ROOM_CONTACT');
      $html .= '</td>'.LF;

      $html .= '      <td style="width:25%; font-size:8pt;" class="head" colspan="2">';
      $html .= $this->_translator->getMessage('CONTEXT_ACTUALITY');
      $html .= $picture;
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
      $user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      if (!$this->_clipboard_mode){
         $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('CONTEXT_SHOW_ON_HOME').'</option>'.LF;
         $html .= '   <option value="3">'.$this->_translator->getMessage('CONTEXT_NOT_SHOW_ON_HOME').'</option>'.LF;
      }else{
         $html .= '   <option value="1">'.$this->_translator->getMessage('CLIPBOARD_PASTE_BUTTON').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('CLIPBOARD_DELETE_BUTTON').'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
   }

   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="3"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="head" colspan="3" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="head" colspan="3" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
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

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML ($item, $pos) {
     $shown_entry_number = $pos;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $html  = '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
         if ( in_array($key, $checked_ids) ) {
            $html .= ' checked="checked"'.LF;
            if ( in_array($key, $dontedit_ids) ) {
               $html .= ' disabled="disabled"'.LF;
            }
         }
         $html .= '/>'.LF;
         $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         $html .= '      </td>'.LF;
         $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
      }else{
         $html .= '      <td colspan="2" '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModerator($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;" colspan="2">'.$this->_getItemShowOnHome($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }




  /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle ($item) {
      $title = $item->getTitle();
      $title = $this->_compareWithSearchText($title);
      $params = array();
      $params['iid'] = $item->getItemID();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setUserIDLimit($current_user->getUserID());
      $user_manager->setAuthSourceLimit($current_user->getAuthSource());
      $user_manager->setContextLimit($item->getItemID());
      $user_manager->select();
      $user_list = $user_manager->get();
      if (!empty($user_list)){
         $room_user = $user_list->getFirst();
      } else {
         $room_user = '';
      }
      if ($current_user->isRoot()) {
         $may_enter = true;
      } elseif (!empty($room_user)) {
         $may_enter = $item->mayEnter($room_user);
      } else {
         $may_enter = false;
      }
      if ($may_enter) {
         $html = ahref_curl($item->getItemID(), 'home',
                                           'index',
                            '',
                            '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;
      } else {
       $html = '<img src="images/door_closed_small.gif" style="vertical-align: middle" alt="door closed"/>'.LF;
     }
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           'myroom',
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
   function _getItemShowOnHome($item) {
      $retour = '';
      $user = $this->_environment->getCurrentUserItem();
      $user_id = $user->getUserID();
      if ( $item->isShownInPrivateRoomHome($user_id) ){
         $title = $this->_translator->getMessage('CONTEXT_SHOWN_ON_HOME');
      }else{
         $title = $this->_translator->getMessage('CONTEXT_NOT_SHOWN_ON_HOME');
      }
      return $title;
   }



   function getSelectedCommunityRoom () {
      return $this->_selected_community_room_limit;
   }

   function setSelectedCommunityRoom ($value) {
      $this->_selected_community_room_limit = (int)$value;
   }
}
?>