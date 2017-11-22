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

include_once('classes/cs_reader_manager.php');
include_once('classes/cs_dates_manager.php');
include_once('functions/text_functions.php');
$this->includeClass(VIEW);

/**
 *  class for preferences for rooms: list view
 */
class cs_privateroom_home_portlet_view extends cs_view{

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;

   var $_count_all_shown = NULL;

   var $_used_rubrics_for_room_array = array();

   var $_user_for_room_array = array();

   var $_portlet_views = array();

   var $_list = NULL;

   var $_column_count = 2;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct( $params);
      $current_context = $this->_environment->getCurrentContextItem();
   }

   function setPortletViewArray($portlet_array){
      $this->_portlet_views = $portlet_array;
   }

   function setColumnCount($count){
   	  $this->_column_count = $count;
   }

    function setList ($list) {
       $this->_list = $list;
    }

    function getList () {
       return $this->_list;
    }


   function _getPortletsAsHTML($portlet_array,$columns){
      $html = '<div style="margin-top:10px;">';
      $column_count = 0;
      $html_array = array();
      //for ($i = 0; $i < ($columns+1); $i++){
      //   $width[$i]= 100/$columns;
      //}
      switch ($columns){
         case 2:
            $width[0] = 60;
            $width[1] = 40;
         	break;
         case 3:
            $width[0] = 30;
            $width[1] = 45;
            $width[2] = 25;
         	break;
      }

      //for ($i=0; $i< ($columns + 1); $i++){
      //   if ($i < ($columns -1)){
      //      $html_array[$i] = '<div class="column" style="width:'.$width[$i].'%;">';
      //   }else{
      //      $html_array[$i] = '<div class="column" style="width:'.$width[$i].'%;">';
      //   }
      //}
      // -->
      for ($i=0; $i<$columns; $i++){
         $html_array[$i] = '<div class="column" style="width:'.$width[$i].'%;">';
      }

      $privateroom_item = $this->_environment->getCurrentContextItem();
      $home_config = $privateroom_item->getHomeConfig();
      
      if(!empty($home_config)){
	      for ($i=0; $i < sizeof($home_config); $i++){
	      	if (isset($home_config[$i])){
	      	   $temp_column_config = $home_config[$i];
	      	   foreach($temp_column_config as $portlet_class){
	      		  foreach ($portlet_array as $portlet){
	      			if($portlet['class'] == $portlet_class){
	      				$html_array[$i] .= $this->_getPortletAsHTML($portlet['title'],$portlet['content'],$portlet['preferences'],$portlet['class']);
	      			}
	      		}
	      	  }
	      	}
	     }
      } else {
         foreach ($portlet_array as $portlet){
	         if ($column_count == $columns){
	            $column_count = 0;
	         }
	         $html_array[$column_count] .= $this->_getPortletAsHTML($portlet['title'],$portlet['content'],$portlet['preferences'],$portlet['class']);
#            $html_array[$column_count % $columns] .= $this->_getPortletAsHTML($portlet['title'],$portlet['content']);
	         $column_count++;
	      }
      }

      //for ($i=0; $i< ($columns + 1); $i++){
      //   $html_array[$i] .= '</div>';
      //}
      // -->
      for ($i=0; $i<$columns; $i++){
         $html_array[$i] .= '</div>';
      }
      foreach ($html_array as $html_entry){
         $html .= $html_entry;
      }
      $html .= '</div>';
      return $html;

   }

   function _getPortletAsHTML($title,$content,$preferences,$class){
      $html  = '<div class="portlet" id="'.$class.'">'.LF;
      $html .= '<div class="portlet-header">'.$title.'<div style="float:right;"><a name="portlet_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div></div>'.LF;
      $html .= '<div class="portlet-content">'.$content.'</div>'.LF;
      if(isset($preferences) and !empty($preferences)){
      	$html .= '<div class="portlet-turn portlet-front" style="float:right;">'.LF;
         $html .= '<a class="preferences_flip" name="portlet_preferences" style="cursor:pointer;"><img src="images/config_home.png" /></a>'.LF;
         $html .= '&nbsp;</div>'.LF;
      }
      $html .= '</div>'.LF;

      if(isset($preferences) and !empty($preferences)){
         $html .= '<div class="portlet" style="display:none;" id="'.$class.'_preferences">'.LF;
         $html .= '<div class="portlet-header">'.$title.' - '.$this->_translator->getMessage('COMMON_CONFIGURATION').'</div>'.LF;
         $html .= '<div class="portlet-content">'.$preferences.'</div>'.LF;
         $html .= '<div class="portlet-turn portlet-back" style="float:right;"><a class="preferences_flip" name="portlet_preferences_back_button" style="cursor:pointer;"><img src="images/commsyicons/16x16/room.png" height="18" width="18"/></a>&nbsp;</div>'.LF;
         $html .= '</div>'.LF;
      }
      return $html;
   }


   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;
      $context = $this->_environment->getCurrentContextItem();
      $portlet_array = array();
      foreach($this->_portlet_views as $portlet_view){
      	$tmp_array['class'] = get_class($portlet_view);
         $tmp_array['title'] = $portlet_view->getViewTitle();
         $tmp_array['content'] = $portlet_view->asHTML();
         $tmp_array['preferences'] = $portlet_view->getPreferencesAsHTML();
         $portlet_array[] = $tmp_array;
      }

      $html .= $this->_getPortletsAsHTML($portlet_array,$this->_column_count);
      foreach($this->_portlet_views as $portlet_view){
         $html .= $portlet_view->getPortletJavascriptAsHTML();
      }

#      $html .='</div>'.LF;
      $html .='<div style="clear:both;"></div>'.LF;
      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var ajax_cid = '.$this->_environment->getCurrentContextItem()->getItemID().';'.LF;
      $html .= 'var ajax_function = "privateroom_home";'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;
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
            $retour .= '    table.room_window_border'.$item->getItemID().' {background-color: '.$color_array['boxes_background'].'; width: 150px; margin:2px; border: 1px solid '.$cs_color['tableheader'].';}'.LF;
            $retour .= '    td.detail_view_content_room_window'.$item->getItemID().' { width: 17em; padding: 3px;text-align: left; border-bottom: 1px solid '.$cs_color['tableheader'].';}'.LF;
            $retour .= '    td.detail_view_title_room_window'.$item->getItemID().' {background-color: '.$cs_color['tableheader'].'; color: '.$cs_color['room_title'].'; padding: 0px;text-align: left;}'.LF;
            $retour .= '    td.detail_view_title_room_window'.$item->getItemID().' a {background-color: '.$cs_color['tableheader'].'; color: '.$cs_color['room_title'].'; padding: 0px;text-align: left;}'.LF;
            $retour .= '    td.detail_view_title_room_window'.$item->getItemID().' a:hover {background-color: '.$cs_color['tableheader'].'; color: '.$cs_color['room_title'].'; padding: 0px;text-align: left;}'.LF;
            $retour .= ' .gauge'.$item->getItemID().' { background-color: #FFFFFF; width: 100%; margin: 2px 0px; border: 1px solid #666; }'.LF;
            $retour .= ' .gauge-bar'.$item->getItemID().' { background-color: '.$color_array['tabs_background'].'; text-align: right; font-size: 8pt; color: black; }'.LF;
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
            $retour .= 'background-color: '.$color_array['content_background'].'; }';
            $item = $this->_list->getNext();
         }
         $retour .= '   </style>'."\n";
         $retour .= '   <!-- END Styles -->'."\n".LF;
      }

      $retour .= $this->_initDropDownMenus();

      return $retour;
   }

   function _initDropDownMenus(){
   	$privateroom_item = $this->_environment->getCurrentContextItem();
      $action_array = array();
      $html = '';

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_NEWEST_ENTRIES');
      $temp_array['value'] = "cs_privateroom_home_new_entries_view";
      if($privateroom_item->getPortletShowNewEntryList()){
      	$temp_array['checked']  = "checked";
      } else {
      	$temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_ACTIVE_ROOMS');
      $temp_array['value'] = "cs_privateroom_home_room_view";
      if($privateroom_item->getPortletShowActiveRoomList()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_ROOMWIDE_SEARCH_BOX');
      $temp_array['value'] = "cs_privateroom_home_roomwide_search_view";
      if($privateroom_item->getPortletShowRoomWideSearchBox()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      #$temp_array = array();
      #$temp_array['dropdown_image']  = "new_icon";
      #$temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_DOKUVERSER_BOX');
      #$temp_array['value'] = "cs_privateroom_home_dokuverser_view";
      #if($privateroom_item->getPortletShowDokuverserBox()){
      #   $temp_array['checked']  = "checked";
      #} else {
      #   $temp_array['checked']  = "";
      #}
      #$action_array[] = $temp_array;

      if($privateroom_item->withBuzzwords()){
	      $temp_array = array();
	      $temp_array['dropdown_image']  = "new_icon";
	      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_BUZZWORD_BOX');
	      $temp_array['value'] = "cs_privateroom_home_buzzword_view";
	      if($privateroom_item->getPortletShowBuzzwordBox()){
	         $temp_array['checked']  = "checked";
	      } else {
	         $temp_array['checked']  = "";
	      }
	      $action_array[] = $temp_array;
      }

      if($privateroom_item->withTags()){
	      $temp_array = array();
	      $temp_array['dropdown_image']  = "new_icon";
	      $temp_array['text']  = $this->_translator->getMessage('COMMON_TAGS');
	      $temp_array['value'] = "cs_privateroom_home_tag_view";
	      if($privateroom_item->getPortletShowTagBox()){
	         $temp_array['checked']  = "checked";
	      } else {
	         $temp_array['checked']  = "";
	      }
	      $action_array[] = $temp_array;
      }
      
      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('HOME_EXTRA_TOOLS');
      $temp_array['value'] = "cs_privateroom_home_configuration_view";
      if($privateroom_item->getPortletShowConfigurationBox()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_NEW_ITEM_BOX');
      $temp_array['value'] = "cs_privateroom_home_new_item_view";
      if($privateroom_item->getPortletShowNewItemBox()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_SEARCH_BOX');
      $temp_array['value'] = "cs_privateroom_home_search_view";
      if($privateroom_item->getPortletShowSearchBox()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_WEATHER_BOX');
      $temp_array['value'] = "cs_privateroom_home_weather_view";
      if($privateroom_item->getPortletShowWeatherBox()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_CLOCK_BOX');
      $temp_array['value'] = "cs_privateroom_home_clock_view";
      if($privateroom_item->getPortletShowClockBox()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_YOUTUBE');
      $temp_array['value'] = "cs_privateroom_home_youtube_view";
      if($privateroom_item->getPortletShowYouTube()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_FLICKR');
      $temp_array['value'] = "cs_privateroom_home_flickr_view";
      if($privateroom_item->getPortletShowFlickr()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('COMMON_TWITTER_FRIENDS');
      $temp_array['value'] = "cs_privateroom_home_twitter_view";
      if($privateroom_item->getPortletShowTwitter()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PORTLET_CONFIGURATION_RSS');
      $temp_array['value'] = "cs_privateroom_home_rss_ticker_view";
      if($privateroom_item->getPortletShowRSS()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('COMMON_NOTE_PORTLET');
      $temp_array['value'] = "cs_privateroom_home_note_view";
      if($privateroom_item->getPortletShowNoteBox()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;
      
      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('COMMON_RELEASED_ENTRIES_PORTLET');
      $temp_array['value'] = "cs_privateroom_home_released_entries_view";
      if($privateroom_item->getPortletShowReleasedEntriesBox()){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;
      
      // init drop down menu
      if ( !empty($action_array)
           and count($action_array) >= 1
         ) {
         $html .= '<script type="text/javascript">'.LF;
         $html .= '<!--'.LF;
         $html .= 'var dropDownPortlets = new Array(';
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
         $html .= 'var portletsColumnText = "'.$this->_translator->getMessage('PORTLET_CONFIGURATION_COLUMN_COUNT').'";'.LF;
         $html .= 'var portletsColumnCount = '.$privateroom_item->getPortletColumnCount().';'.LF;
         $html .= 'var portletsSaveButton = "'.$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON').'";'.LF;
         $html .= '-->'.LF;
         $html .= '</script>'.LF;
      }
      return $html;
   }
}
?>