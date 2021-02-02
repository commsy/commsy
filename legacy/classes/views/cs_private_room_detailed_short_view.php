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
class cs_private_room_detailed_short_view extends cs_view{

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;

   var $_count_all_shown = NULL;

   var $_used_rubrics_for_room_array = array();

   var $_user_for_room_array = array();

   var $_portlet_views = array();

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct( $params);
      $current_context = $this->_environment->getCurrentContextItem();
   }

   function setUsedRubricsForRoomsArray($array){
      $this->_used_rubrics_for_room_array = $array;
   }

   function setUserForRoomsArray($user_array){
      $this->_user_for_room_array = $user_array;
   }

   function _getForwardLinkAsHTML () {
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all_shown = $this->_count_all_shown;
      $params = $this->_environment->getCurrentParameterArray();
      if (!isset($params['mode']) or $params['mode'] == 'browse'){
         $params['mode'] = 'list_actions';
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
      $html = $act_page.' / '.$num_pages.LF;
      return $html;
   }


   function _getListInfosAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box" style="margin-top:0px; margin-bottom:1px;">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= $this->_getBrowsingIconsAsHTML().LF;
      $html .= '<div style="white-space:nowrap;">'.$this->_translator->getMessage('COMMON_PAGE').' '.$this->_getForwardLinkAsHTML().'</div>'.LF;


#      $html .= '<div class="index_forward_links" style="white-space:nowrap;">'.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<div class="right_box_main" >'.LF;
      $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
      $html .= '         <tr>'.LF;
      $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
      $html .= $this->_translator->getMessage('COMMON_ALL_LIST_ENTRIES',$this->_translator->getMessage('COMMON_ROOMS')).': ';
      $html .= '         </td>'.LF;
      $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
      $html .= $this->_count_all;
      $html .= '         </td>'.LF;
      $html .= '         </tr>'.LF;
      $html .= '         <tr>'.LF;
      $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
      $html .= $this->_translator->getMessage('COMMON_LIST_SHOWN_ENTRIES').' ';
      $html .= '         </td>'.LF;
      $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
      $html .= $this->_getListDescriptionAsHTML();
      $html .= '         </td>'.LF;
      $html .= '         </tr>'.LF;
      $html .= '         </table>'.LF;
      $context_user = $this->_environment->getCurrentUserItem();
      if ( !$context_user->isOnlyReadUser()
           or ( $current_context->showWikiLink()
                and $current_context->existWiki()
                and $current_context->issetWikiHomeLink()
              )
         ) {
         $html .='<div class="listinfoborder">'.LF;
         $html .='</div>'.LF;
         $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
         $html .= '         <tr>'.LF;
         $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
         $html .= $this->_translator->getMessage('COMMON_PAGETITLE_CONFIGURATION').': ';
         $html .= '         </td>'.LF;
         $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
         if ( $current_context->showWikiLink() and $current_context->existWiki() and $current_context->issetWikiHomeLink() ) {
            global $c_pmwiki_path_url;
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/pmwiki.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WIKI_LINK').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/pmwiki.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WIKI_LINK').'"/>';
            }
            $title = $this->_translator->getMessage('COMMON_WIKI_LINK').': '.$current_context->getWikiTitle();
            $url_session_id = '';
            if ( $current_context->withWikiUseCommSyLogin() ) {
               $session_item = $this->_environment->getSessionItem();
               $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
               unset($session_item);
            }
            $html .= ' '.'<a title="'.$title.'" href="'.$c_pmwiki_path_url.'/wikis/'.$current_context->getContextID().'/'.$current_context->getItemID().'/'.$url_session_id.'" target="_blank">'.$image.'</a>'.LF;
            unset($session_item);
         }
         if ( !$context_user->isOnlyReadUser() ) {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/config.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/config.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION').'"/>';
            }
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION')).LF;
         }
         $html .= '         </td>'.LF;
         $html .= '         </tr>'.LF;
         $html .= '         </table>'.LF;
      }

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

     return $html;
   }


    function setCountAll ($count_all) {
       $this->_count_all = (int)$count_all;
    }

    function setFrom ($from) {
       $this->_from = (int)$from;
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */
    function getCountAll () {
       return $this->_count_all;
    }
    // @segment-end 49781

   // @segment-begin 17374  setCountAllShown($count_all)/getCountAllShown()-lenght-of-whole-shown-list
   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole shown list
    *
    * @author CommSy Development Group
    */
    function setCountAllShown ($count_all) {
       $this->_count_all_shown = (int)$count_all;
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole shown list
    *
    * @author CommSy Development Group
    */
    function getCountAllShown () {
       return $this->_count_all_shown;
    }
    // @segment-end 17374

   function setInterval ($interval) {
       $this->_interval = (int)$interval;
    }

   /** get interval counter of the list view
    * this method gets the shown interval of the list view
    *
    * @param int  $this->_interval          lenght of the shown list
    */
    function getInterval () {
       return $this->_interval;
    }

  function _getBrowsingIconsAsHTML(){
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all_shown = $this->_count_all_shown;
      $params = $this->_environment->getCurrentParameterArray();
      if (!isset($params['mode']) or $params['mode'] == 'browse'){
         $params['mode'] = 'list_actions';
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
      } else {
         $html .= '         <span style="font-weight:normal;">&lt;&lt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_left > 0 ) {
         $params['from'] = $browse_left;
         $image = '<span class="bold">&lt;</span>';
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
      } else {
         $html .= '         <span style="font-weight:normal;">&lt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_right > 0 ) {
         $params['from'] = $browse_right;
         $image = '<span class="bold">&gt;</span>';
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
      } else {
         $html .= '         <span style="font-weight:normal;">&gt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_end > 0 ) {
         $params['from'] = $browse_end;
         $image = '<span class="bold">&gt;&gt;</span>';
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
      } else {
         $html .= '         <span style="font-weight:normal;">&gt;&gt;</span>'.LF;
      }
      $html .= '</div>';
      return $html;
  }

   function _getListDescriptionAsHTML() {
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all = $this->_count_all;
      $count_all_shown = $this->_count_all_shown;
      if ( $count_all > $count_all_shown ) {
         if ( $count_all_shown == 0 ) {
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_NO_ENTRIES_FROM_ALL', $count_all_shown);
         } elseif ( $count_all_shown == 1 ) {
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_ONE_ENTRY_FROM_ALL', $count_all_shown);
         } elseif ( $interval == 0 || $count_all_shown <= $interval ) {
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_X_ENTRIES_FROM_ALL', $count_all_shown);
         } elseif ( $from == $count_all_shown){
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_X_FROM_Z_FROM_ALL', $count_all_shown);
         } else {
            if ( $from + $interval -1 <= $count_all_shown ) {
               $to = $from + $interval - 1;
            } else {
               $to = $count_all_shown;
            }
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_X_TO_Y_FROM_Z_FROM_ALL', $from, $to, $count_all_shown);
         }
      }
      // @segment-end 39076
      // @segment-begin 96579 _getDescriptionAsHTML():count_all=count_all_shown:5_possible_messages_like"shown..."
      else {
         if ( $count_all_shown == 0 ) {
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_NO_ENTRIES');
         } elseif ( $count_all_shown == 1 ) {
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_ONE_ENTRY');
         } elseif ( $interval == 0 || $count_all_shown <= $interval ) {
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_X_ENTRIES', $count_all_shown);
         } elseif ( $from == $count_all_shown){
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_X_FROM_Z', $count_all_shown);
         } else {
            if ( $from + $interval -1 <= $count_all ) {
               $to = $from + $interval - 1;
            } else {
               $to = $count_all_shown;
            }
            $description = $this->_translator->getMessage('PRIVATEROOM_COMMON_X_TO_Y_FROM_Z', $from, $to, $count_all_shown);
         }
      }
      // @segment-end 96579

      // @segment-begin 24649 _getDescriptionAsHTML():add_description=(numbers_of_displayed_entries+amount_all_entries)_to_return
      $html ='';

      if ( !empty($description) ) {
         $html .= $description;
      }
      // @segment-end 24649
      // @segment-begin 88089 _getDescriptionAsHTML():call_getAttachedItemInfoAsHTML():display_attached_info_under_numbers_of_displayed_entries+amount_all_entries


      return /*$this->_text_as_html_short(*/ $html /*)*/;
   }


  /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {

      $html ='';
      $context = $this->_environment->getCurrentContextItem();
      $time_spread = $context->getTimeSpread();
      if ($time_spread =='1'){
         $description = $this->_translator->getMessage('PRIVATE_ROOM_SHORT_DETAILED_VIEW_DESCRIPTION2',$time_spread);
      }else{
         $description = $this->_translator->getMessage('PRIVATE_ROOM_SHORT_DETAILED_VIEW_DESCRIPTION',$time_spread);
      }
      if ( !empty($description) ) {
         $html .= ' ('.$description.')';
      }

      return /*$this->_text_as_html_short(*/ $html /*)*/;
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
#      $html .= LF.'<div class="head" style="margin-bottom:10px;">'.LF;
#      $context = $this->_environment->getCurrentContextItem();
#      $html .= '<span style="font-weight: bold">'.$this->_translator->getMessage('COMMON_ROOM_OVERVIEW').'</span>';
#      $html .= ' '.$this->_getDescriptionAsHTML().' '.LF;
#      $html .='</div>'.LF;
      $html .= '';
      $list = $this->_list;
      $user = $this->_environment->getCurrentUserItem();
      $temp_item  = $list->getFirst();
      $room_id_array = array();
      while ($temp_item) {
         $room_id_array[] = $temp_item->getItemID();
         $temp_item = $list->getNext();
      }
      $material_manager = $this->_environment->getMaterialManager();
      $material_manager->create_tmp_table_by_id_array($room_id_array);
      $current_item  = $list->getFirst();
      while ($current_item) {
         $html.= '<div style="margin-bottom:20px;">'.LF;
         $item_text = $this->_getRoomWindowAsHTML($current_item);
         $html .= $item_text;
         $html .= '</div>'.LF;
         $current_item = $list->getNext();
      }
      $material_manager->delete_tmp_table();
      $html .= '<!-- END OF LIST VIEW -->'.LF.LF;
      return $html;

   }

   /** set title of the list view
    * this method sets the title of the list view
    *
    * @param string  $this->_title          title of the list view
    *
    * @author CommSy Development Group
    */
    function setTitle ($value) {
       $this->_title = (string)$value;
    }

    /** get title of the list view
    * this method gets the title of the list view
    *
    * @param string  $this->_title          title of the list view
    *
    * @author CommSy Development Group
    */
    function getTitle () {
       return $this->_title;
    }

    /** set description of the list view
    * this method sets the shown description of the list view
    *
    * @param int  $this->_description          description of the shown list
    *
    * @author CommSy Development Group
    */
    function setDescription ($description) {
       $this->_description = (string)$description;
    }

    /** set no description for the list view
    * this method hides the description of the list view
    *
    *
    * @author CommSy Development Group
    */
    function setWithoutDescription () {
       $this->_with_description = FALSE;
    }


  /** get the content of the list view
    * this method gets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function getList (){
       return $this->_list;
    }

    /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function setList ($list){
       $this->_list = $list;
    }

   /** get room window as html
    *
    * param cs_project_item project room item
    */
   function _getRoomWindowAsHTML ($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      $current_context = $this->_environment->getCurrentContextItem();

      $temp_room_id = $item->getItemID();
      if (array_key_exists($temp_room_id, $this->_user_for_room_array)){
         $ref_user = $this->_user_for_room_array[$temp_room_id];
      }else{
         $user_manager = $this->_environment->getUserManager();
         $user_manager->resetLimits();
         $user_manager->setUserIDLimit($current_user->getUserID());
         $user_manager->_room_limit = $item->getItemID();
         $user_manager->select();
         $user_list = $user_manager->get();
         $ref_user = $user_list->getFirst();
      }
      $may_enter = $item->mayEnter($current_user);
      $title = $item->getTitle();
      $color_array = $item->getColorArray();
      $cs_color['room_title'] = '';
       $cs_color['room_background']  = '';
       $cs_color['tableheader']  = '';
      $html  = '';
      $html .= '<table class="room_window'.$item->getItemID().'" summary="Layout" style="width:100%; border-collapse:collapse;">'.LF;
      $html .= '<tr>'.LF;
      $logo = $item->getLogoFilename();
      // Titelzeile
      if (!empty($logo) ) {
         $html .= '<td colspan="2" class="detail_view_title_room_window'.$item->getItemID().'" style="padding:3px;">';
         $params = array();
         $params['picture'] = $item->getLogoFilename();
         $curl = curl($item->getItemID(), 'picture', 'getfile', $params,'');
         unset($params);
         $params['iid']=$item->getItemID();
         $html .='<div style="float:left; padding-right:3px;">'.LF;
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'myroom','detail',$params,'<img style="height:20px;" src="'.$curl.'" alt="'.$this->_translator->getMessage('COMMON_LOGO').'" border="0"/>');
         $html .='</div>'.LF;
         $html .='<div style="font-weight: bold; padding-top: 3px; padding-bottom: 3px; ">'.LF;
         $title = $this->_text_as_html_short($title);
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'myroom','detail',$params,$title);
         unset($params);
         if ($item->isLocked()) {
            $html .= ' ('.$this->_translator->getMessage('PRIVATE_ROOM_PROJECTROOM_LOCKED').')'.LF;
         }elseif ($item->isProjectroom() and $item->isTemplate()) {
            $html .= ' ('.$this->_translator->getMessage('PRIVATE_ROOM_PROJECTROOM_TEMPLATE').')'.LF;
         }elseif ($item->isClosed()) {
            $html .= ' ('.$this->_translator->getMessage('PRIVATE_ROOM_PROJECTROOM_CLOSED').')';
         }
         $html .='</div>'.LF;
         $html .= '</td>'.LF;
      } else {
         $html .= '<td class="detail_view_title_room_window'.$item->getItemID().'" colspan="2" style="font-weight: bold; padding-top: 3px; padding-bottom: 3px; padding-left:3px;">';
         $params['iid']=$item->getItemID();
         $title = $this->_text_as_html_short($title)."\n";
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'myroom','detail',$params,$title);
         if ($item->isLocked()) {
            $html .= ' ('.$this->_translator->getMessage('PRIVATE_ROOM_PROJECTROOM_LOCKED').')';
         } elseif ($item->isClosed()) {
            $html .= ' ('.$this->_translator->getMessage('PRIVATE_ROOM_PROJECTROOM_CLOSED').')';
         }
         $html .= '</td>';
      }
      $html .= '<td class="detail_view_title_room_window'.$item->getItemID().'" style="vertical-align:top; text-align:right;">'.LF;
      $html .= '</td>'.LF;

      $html .= '</tr>'.LF;
      $html .= '<tr><td colspan="2" style="width:70%; vertical-align:top;" class="detail_view_content_room_window'.$item->getItemID().'">'.LF;



      $conf = $item->getHomeConf();
      if ( !empty($conf) ) {
         $rubrics = explode(',', $conf);
      } else {
         $rubrics = array();
      }
      $count = count($rubrics);

      $check_managers = array();
      $check_rubrics = array();
      $display_rubrics = array();
      foreach ( $rubrics as $rubric ) {
         list($rubric_name, $rubric_status) = explode('_', $rubric);
         if ( $rubric_status != 'none' and $rubric_name !='chat'){
            $check_managers[] = $rubric_name;
            $check_rubrics[] = $rubric_name;
            $display_rubrics[] = $rubric_name;
            if ( $rubric_name == 'discussion' ) {
               $check_managers[] = 'discarticle';
               $check_rubrics[] = $rubric_name;
            }
            if ( $rubric_name == 'material' ) {
               $check_managers[] = 'section';
               $check_rubrics[] = $rubric_name;
            }
         }
      }
      $display_count = count($display_rubrics);
      for ( $i =0; $i<$display_count; $i++){
         $rubric_array = explode('_', $display_rubrics[$i]);
         $html .='<table style="width:100%; margin-bottom:5px; border-collapse:collapse; border: 1px solid '.$color_array['tabs_background'].';" summary="Layout">';
         $html .='<tr>'.LF;
         $html .='<td class="detail_view_title_room_window'.$item->getItemID().'" style="padding:2px;">'.LF;
         $count_entries = 0;
         $temp_html ='';
         if (array_key_exists($item->getItemID(), $this->_used_rubrics_for_room_array) and in_array($rubric_array[0], $this->_used_rubrics_for_room_array[$item->getItemID()])){
            $rubric_manager = $this->_environment->getManager($rubric_array[0]);
            $rubric_manager->reset();
            if ($rubric_array[0] == CS_MATERIAL_TYPE){
               $rubric_manager->_handle_tmp_manual = true;
            }
            $rubric_manager->setContextLimit($item->getItemID());
            $rubric_manager->setAgeLimit($current_context->getTimeSpread());
            if ( $rubric_manager instanceof cs_dates_manager ) {
               $rubric_manager->setDateModeLimit(2);
            }
            if ( $rubric_manager instanceof cs_user_manager ) {
               $rubric_manager->setUserLimit(2);
            }
            $rubric_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            $rubric_manager->select();
            $rubric_list = $rubric_manager->get();
            $ids = $rubric_manager->getIDs();
            $noticed_manager = $this->_environment->getNoticedManager();
            $noticed_manager->getLatestNoticedByIDArrayAndUser($ids,$ref_user->getItemID() );
            if ($rubric_array[0] != CS_DISCUSSION_TYPE and
                  $rubric_array[0] != CS_SECTION_TYPE and
                  $rubric_array[0] != CS_DISCARTICLE_TYPE and
                  $rubric_array[0] != CS_USER_TYPE and
                  $rubric_array[0] != CS_GROUP_TYPE){
               $noticed_manager->getLatestNoticedAnnotationsByIDArrayAndUser($ids, $ref_user->getItemID());
            }
            $rubric_item = $rubric_list->getFirst();
            while($rubric_item){
               $noticed = '';
               $noticed = $noticed_manager->getLatestNoticed($rubric_item->getItemID(),$ref_user->getItemID());
               if ( empty($noticed) ) {
                  $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW').']</span>';
               } elseif ( $noticed['read_date'] < $rubric_item->getModificationDate() ) {
                  $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED').']</span>';
               } else {
                  $info_text = '';
               }
               if ($rubric_array[0] != CS_DISCUSSION_TYPE and
                     $rubric_array[0] != CS_SECTION_TYPE and
                     $rubric_array[0] != CS_DISCARTICLE_TYPE and
                     $rubric_array[0] != CS_USER_TYPE and
                     $rubric_array[0] != CS_GROUP_TYPE){
                  $info_text .= $this->_getItemAnnotationChangeStatus($rubric_item, $ref_user);
               }
               if (!empty($info_text)){
                  $count_entries++;
                  $temp_html .='</td>';
                  $temp_html .='</tr>';
                  $temp_html .='<tr>';
                  $style=' style=" padding:2px; background-color:'.$color_array['content_background'].';"';
                  $style=' style=" padding:2px; background-color:#FFFFFF;"';
                  $temp_html .='<td class="detail_view_content_room_window'.$item->getItemID().'" '.$style.'>';
                  $params = array();
                  $params['iid'] = $rubric_item->getItemID();
                  $title ='';
                  if($rubric_item->isA(CS_USER_TYPE)){
                     $title .= $rubric_item->getFullname();
                  }else{
                     $title .= $rubric_item->getTitle();
                  }
                  if ($rubric_item->getType() == 'label'){
                      $rubric_type = $rubric_item->getItemType();
                  }else{
                      $rubric_type = $rubric_item->getType();
                  }
                  $temp_html .= ahref_curl( $item->getItemID(),
                        $rubric_type,
                        'detail',
                        $params,
                        $this->_text_as_html_short($title),
                        '', '', '', '', '', '', '', '','n'.$rubric_item->getItemID()).' '.$info_text;
               }
               $rubric_item = $rubric_list->getNext();
            }

         }

         $tempRubric = mb_strtoupper($rubric_array[0], 'UTF-8');
         switch ( $tempRubric )
         {
            case 'ANNOUNCEMENT':
               $tempMessage = $this->_translator->getMessage('COMMON_ANNOUNCEMENT_INDEX');
               break;
            case 'DATE':
               $tempMessage = $this->_translator->getMessage('COMMON_DATE_INDEX');
               break;
            case 'DISCUSSION':
               $tempMessage = $this->_translator->getMessage('COMMON_DISCUSSION_INDEX');
               break;
            case 'GROUP':
               $tempMessage = $this->_translator->getMessage('COMMON_GROUP_INDEX');
               break;
            case 'MATERIAL':
               $tempMessage = $this->_translator->getMessage('COMMON_MATERIAL_INDEX');
               break;
            case 'USER':
               $tempMessage = $this->_translator->getMessage('COMMON_USER_INDEX');
               break;
            case 'INSTITUTION':
               $tempMessage = $this->_translator->getMessage('COMMON_INSTITUTION_INDEX');
               break;
            case 'TODO':
               $tempMessage = $this->_translator->getMessage('COMMON_TODO_INDEX');
               break;
            case 'PROJECT':
               $tempMessage = $this->_translator->getMessage('COMMON_PROJECT_INDEX');
               break;
            case 'TOPIC':
               $tempMessage = $this->_translator->getMessage('COMMON_TOPIC_INDEX');
               break;
            default:
               $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_private_room_detailed_short_view.php(456) ';
               break;
         }
$html .= ahref_curl( $item->getItemID(),
                     $rubric_array[0],
                     'index',
                     '',
                     $tempMessage);

         if ($count_entries == 0){
            $html .= ' <span style="font-size:8pt;">('.$this->_translator->getMessage('COMMON_NO_NEW_ENTRIES').')</span>';
         }elseif($count_entries == 1){
            $html .= ' <span style="font-size:8pt;">('.$count_entries.' '.$this->_translator->getMessage('NEWSLETTER_NEW_SINGLE_ENTRY').')</span>';
         }else{
            $html .= ' <span style="font-size:8pt;">('.$count_entries.' '.$this->_translator->getMessage('NEWSLETTER_NEW_ENTRIES').')</span>';
         }
         $html .= $temp_html;
         $html .='</td>';
         $html .='</tr>';
         $html .='</table>';
      }

      $html .= '</td><td style="width:30%; vertical-align:top;" class="detail_view_content_room_window'.$item->getItemID().'">'.LF;
      $html .='<table style="width:95%;" class="room_window_border'.$item->getItemID().'" summary="Layout">';

      $html .= '<tr><td class="detail_view_content_room_window'.$item->getItemID().'">'.LF;
      if ($item->isClosed() ) {
            $curl = curl($item->getItemID(), 'home', 'index','','');
            $html .= '<div style="float:left;"><a href="'.$curl.'">';
            $html .= '<img alt="door" src="images/door_open_middle.gif" style="vertical-align: middle; margin-right:3px;"/>'.LF;
            $html .= '</a></div>';
            $html .= ' <div>'.$this->_translator->getMessage('COMMON_CLOSED_SINCE').' '.$this->_translator->getDateInLang($item->getModificationDate()).'</div>'.LF;
      }elseif ($item->isLocked()) {
            $html .= ' ('.$this->_translator->getMessage('COMMON_PROJECTROOM_LOCKED').')'.LF;
            $html .= '<div style="float:left;"><img alt="door" src="images/door_closed_middle.gif" style="vertical-align: middle; margin-right:3px;"/></div>'.LF;
            $html .= ' <div>'.$this->_translator->getMessage('COMMON_LOCKED_SINCE').' '.$this->_translator->getDateInLang($item->getModificationDate()).'</div>'.LF;
      }else{
            $curl = curl($item->getItemID(), 'home', 'index','','');
            $html .= '<div style="float:left;"><a href="'.$curl.'">';
            $html .= '<img alt="door" src="images/door_open_middle.gif" style="vertical-align: middle; margin-right:3px;"/>'.LF;
            $html .= '</a></div>';
            $html .= ' <div>'.$this->_translator->getMessage('COMMON_OPENED_SINCE').' '.$this->_translator->getDateInLang($item->getCreationDate()).'</div>'.LF;
      }

      $html .= '</td></tr>'.LF;

      $html .= '<tr><td class="detail_view_content_room_window'.$item->getItemID().'">'.LF;
      $context = $this->_environment->getCurrentContextItem();
      $count_total = $item->getPageImpressions($context->getTimeSpread());
      if ( $count_total == 1 ) {
         $html .= $count_total.'&nbsp;'.$this->_translator->getMessage('HOME_ACTIVITY_PAGE_IMPRESSIONS_SINGULAR').'';
         $html .= BRLF;
      } else {
         $html .= $count_total.'&nbsp;'.$this->_translator->getMessage('HOME_ACTIVITY_PAGE_IMPRESSIONS').'';
         $html .= BRLF;
      }
      $html .= '</td></tr>'.LF;
      $html .= '<tr><td>'.LF;
      // Get percentage of active members
      $active = $item->getActiveMembers($context->getTimeSpread());
      $all_users = $item->getAllUsers();
      $percentage = round($active / $all_users * 100);
      $html .= $this->_translator->getMessage('HOME_ACTIVITY_ACTIVE_MEMBERS_SHORT').':'.BRLF;
      $html .= '         <div class="gauge'.$item->getItemID().'">'.LF;
      if ( $percentage >= 5 ) {
         $html .= '            <div class="gauge-bar'.$item->getItemID().'" style="width:'.$percentage.'%; color:white;">'.$active.'</div>'.LF;
      } else {
         $html .= '            <div class="gauge-bar'.$item->getItemID().'" style="float:left; width:'.$percentage.'%; color:white;">&nbsp;</div>'.LF;
         $html .= '            <div style="font-size: 8pt; padding-left:3px;">'.$active.'</div>'.LF;
      }
      $html .= '         </div>'.LF;

      $html .= '</td></tr>'.LF;
      $html .= '</table>'.LF.LF;
      $html .= '</td></tr>'.LF;
      $html .= '</table>'.LF.LF;


      return $html;
   }

   /** return a text indicating the modification state of an item
    * this method returns a string like [new] or [modified] depending
    * on the read state of the current user.
    *
    * @param  object item       a CommSy item (cs_item)
    *
    * @return string value
    *
    * @author CommSy Development Group
    */
   function _getItemAnnotationChangeStatus($item, $ref_user) {
      $current_user = $ref_user;
      if ($current_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $annotation_list = $item->getItemAnnotationList();
         $anno_item = $annotation_list->getFirst();
         $new = false;
         $changed = false;
         $date = "0000-00-00 00:00:00";
         while ( $anno_item ) {
            $noticed = $noticed_manager->getLatestNoticed($anno_item->getItemID(),$ref_user->getItemID());
            if ( empty($noticed) ) {
               if ($date < $anno_item->getModificationDate() ) {
                   $new = true;
                   $changed = false;
                   $date = $anno_item->getModificationDate();
               }
            } elseif ( $noticed['read_date'] < $anno_item->getModificationDate() ) {
               if ($date < $anno_item->getModificationDate() ) {
                   $new = false;
                   $changed = true;
                   $date = $anno_item->getModificationDate();
               }
            }
            $anno_item = $annotation_list->getNext();
         }
         if ( $new ) {
            $info_text =' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW_ANNOTATION').']</span>';
         } elseif ( $changed ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED_ANNOTATION').']</span>';
         } else {
            $info_text = '';
         }
      } else {
         $info_text = '';
      }
      return $info_text;
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
         $retour .= '   <!-- END Styles -->'."\n";
      }
      return $retour;
   }



}
?>