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
class cs_privateroom_home_room_view extends cs_view {

   var $_list = NULL;
   var $_page_impressions_for_room_id_array = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct( $params);
      $this->_view_title = $this->_translator->getMessage('COMMON_ACTIV_ROOMS');
      $this->_room_type = 'privateroom_home_room_view';
   }

   /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function setList ($list) {
       $this->_list = $list;
    }

   /** get the content of the list view
    * this method gets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function getList () {
       return $this->_list;
    }

   function asHTML () {
   	  $html = '<div id="'.get_class($this).'">'.LF;
      $html .= LF.'<!-- BEGIN OF LIST VIEW -->'.LF;
      $html .= '<table style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
      $context = $this->_environment->getCurrentContextItem();
      $html .= '<tr>';
      $list = $this->_list;
      $user = $this->_environment->getCurrentUserItem();

      /*Get Page Impressions for all rooms */
      $id_array = $list->getIDArray();
      $log_manager = $this->_environment->getLogManager();
      $this->_page_impressions_for_room_id_array = $log_manager->selectTotalCountsForContextIDArray($id_array);
      unset($log_manager);
      if ( isset($list)  ){
         $current_item = $list->getFirst();
         if (isset($current_item) and !empty($current_item)){
            $count = 0;
            while ( $current_item ) {
               if ( $count == 2 ){
                  $count = 0;
                  $html .= '</tr><tr>'.LF;
               }
               $item_text = $this->_getRoomWindowAsHTML($current_item);
               $html .= $item_text;
               $count++;
               $current_item = $list->getNext();
            }
            while ( $count < 2 ){
               $html .= '<td width="50%" style="vertical-align: top;"></td>';
               $count++;
            }
         }else{
      	    $html .='<td>'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td>'.LF;
         }
      }else{
      	$html .='<td>'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td>'.LF;
      }
      $html .= '</tr></table>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF;
      $html .= '</div>'.LF.LF;
      return $html;
   }



   /** get room window as html
    *
    * param cs_project_item project room item
    */
   function _getRoomWindowAsHTML ($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      $may_enter = $item->mayEnter($current_user);
      $title = $item->getTitle();
      $color_array = $item->getColorArray();
      $cs_color['room_title'] = '';
      $cs_color['room_background']  = '';
      $cs_color['tableheader']  = '';
      $html  = '';
      $html = '<td style="width:25%; padding:3px; vertical-align: top;">'.LF;
      $html .= '<table class="room_window'.$item->getItemID().'" summary="Layout" style="width:100%; border-collapse:collapse;">'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td class="detail_view_title_room_window'.$item->getItemID().'" style="font-weight: bold; padding-top: 3px; padding-bottom: 3px; padding-left:3px;">';
      $params['iid']=$item->getItemID();
      $title = $this->_text_as_html_short($title)."\n";
      $html .= ahref_curl($item->getItemID(),'home','index',array(),$title);
          if ($item->isLocked()) {
         $html .= ' ('.$this->_translator->getMessage('PROJECTROOM_LOCKED').')';
      } elseif ($item->isClosed()) {
         $html .= ' ('.$this->_translator->getMessage('PROJECTROOM_CLOSED').')';
      }
      $html .= '</td>';
      $html .= '</tr>'.LF;
      $html .= '<tr><td class="detail_view_content_room_window'.$item->getItemID().'">'.LF;
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
      $this->_page_impressions_for_room_id_array;
      if (isset($this->_page_impressions_for_room_id_array[$item->getItemID()])){
         $count_total = $item->getPageImpressions($item->getTimeSpread(),$this->_page_impressions_for_room_id_array[$item->getItemID()]);
      }else{
         $count_total = $item->getPageImpressions($item->getTimeSpread());
      }
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

      $count_total = $item->getNewEntries($item->getTimeSpread());
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
      $active = $item->getActiveMembers($item->getTimeSpread());
      $all_users = $item->getAllUsers();
      $percentage = round($active / $all_users * 100);
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
      $html .='</td>';


      return $html;
   }

   function getInfoForHeaderAsHTML () {
      global $cs_color;
      $retour = parent::getInfoForHeaderAsHTML();
      if ( !empty($this->_list) ) {
         $retour .= '   <!-- BEGIN RoomWindow-Styles -->'.LF;
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
         $retour .= '   <!-- END RoomWindow-Styles -->'."\n";
      }
      return $retour;
   }




}
?>