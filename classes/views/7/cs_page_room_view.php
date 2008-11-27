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

$this->includeClass(PAGE_VIEW);

/** language_functions are needed for language specific display
 */
include_once('functions/language_functions.php');

/** curl_functions are needed for actions
 */
include_once('functions/curl_functions.php');

/** date_functions are needed for language specific display
 */
include_once('functions/date_functions.php');

/** misc_functions are needed for display the commsy version
 */
include_once('functions/misc_functions.php');
include_once('functions/text_functions.php');

/** class for a page view of commsy
 * this class implements a page view of commsy
 */
class cs_page_room_view extends cs_page_view {

   /**
    * string - containing the parameter of the page
    */
   var $_current_parameter = '';

   var $_form_tags =false;

   var $_form_action= '';

   var $_with_delete_box = false;

   var $_delete_box_action_url = '';

   var $_delete_box_mode = 'detail';

   var $_delete_box_ids = NULL;

   /**
    * array - containing the hyperlinks for the page
    */
   var $_links = array();

   var $_space_between_views=true;

   var $_blank_page = false;

   var $_blank_page_content ='';

   /**
    * boolean - containing the flag for displaying the CommSy header
    * standard = true
    */
   var $_with_commsy_header = true;

   /**
    * boolean - containing the flag for displaying a personal area for root (e.g. page commsy overview)
    * standard = false
    */
   var $_with_root_personal_area = false;

   /**
    * boolean - containing the flag for displaying a navigation bar for root (e.g. page commsy overview)
    * standard = false
    */
   var $_with_root_navigation_links = false;


   var $_bold_rubric = '';

   var $_shown_as_printable = false;

   var $_style_image_path = 'images/layout/';



   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the context
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   public function __CONSTRUCT ($params) {
      $this->cs_page_view($params);
      if (file_exists('htdocs/'.$this->_environment->getCurrentPortalID().'/commsy.css') ){
         $this->_style_image_path = $this->_environment->getCurrentPortalID().'/images/';
      }
   }

   function setBlankPage () {
      $this->_blank_page = true;
   }

   function setBlankPageContent ($content) {
      $this->_blank_page_content = $content;
   }

   function unsetBlankPage () {
      $this->_blank_page = false;
   }

   function setSpace () {
      $this->_space_between_views = true;
   }

   function unsetSpace () {
      $this->_space_between_views = false;
   }

   function setContextID ($value) {
      $this->_context_id = (int)($value);
   }

   function setBoldRubric($value){
      $this->_bold_rubric = $value;
   }

   function setPrintableView(){
      $this->_shown_as_printable = true;
   }
   function isPrintableView(){
      return $this->_shown_as_printable;
   }

   /** so page will be displayed without the CommSy header
    * this method skip a flag, so that the CommSy header will not be shown
    *
    * @author CommSy Development Group
    */
   function withoutCommSyHeader () {
      $this->_with_commsy_header = false;
   }

   /** so page will be displayed without the personal area
    */
   function setWithoutPersonalArea () {
      $this->_with_personal_area = false;
   }

   /** so page will be displayed with the personal area for root user
    */
   function setWithRootPersonalArea () {
      $this->_with_root_personal_area = true;
   }


   /** so page will be displayed with the navigation bar for root user
    */
   function setWithRootNavigationLinks () {
      $this->_with_root_navigation_links = true;
   }

   function addFormTags($action){
      $this->_form_tags = true;
      $this->_form_action = $action;
   }

   /** add an action to the page
    * this method adds an action (hyperlink) to the page view
    *
    * @param string  title        title of the action
    * @param string  explanantion explanation of the action
    * @param string  module       module of the action
    * @param string  function     function in module of the action
    * @param string  parameter    get parameter of the action
    *
    * @author CommSy Development Group
    */
   function addAction ($title, $explanation = '', $module = '', $function = '', $parameter = '') {
      $action['title'] = $title;
      $action['module'] = $module;
      $action['function'] = $function;
      $action['parameter'] = $parameter;
      $action['explanation'] = $explanation;
      $this->_links[] = $action;
   }


   /** get the linkbar as HTML
    * this method returns the linkbar as HTML - internal, do not use
    *
    * @return string linkbar as HTML
    */
   function _getLinkRowAsHTML ($bottom=false) {
      $html  = LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div id="tabs_frame" >'.LF;
      if ($bottom){
         $html .= '<div class="tabs_bottom">'.LF;
      }else{
         $html .= '<div id="tabs">'.LF;
      }
      $html .= '<div style="float:right; margin:0px; padding:0px 20px;">'.LF;

      // rss link
      $current_context_item = $this->_environment->getCurrentContextItem();
      $current_user_item = $this->_environment->getCurrentUserItem();
      $show_rss_link = false;
      if ( $current_context_item->isLocked() ) {
         // do nothing
      } elseif ( $current_context_item->isOpenForGuests() ) {
         $show_rss_link =  true;
      } elseif ( $current_user_item->isUser() ) {
         $show_rss_link =  true;
      }
      $hash_string = '';
      if ( !$current_context_item->isOpenForGuests()
           and $current_user_item->isUser()
         ) {
         $hash_manager = $this->_environment->getHashManager();
         $hash_string = '&amp;hid='.$hash_manager->getRSSHashForUser($current_user_item->getItemID());
      }
      if ( $show_rss_link ) {
         $html .= '<a href="rss.php?cid='.$current_context_item->getItemID().$hash_string.'" target="_blank"><img src="images/rss.png" width="15" height="15" style="vertical-align:bottom;" alt="RSS-Feed dieses Raumes abonnieren"/></a>';
      }

      $html .= '</div>'.LF;
      $html .= '<div id="tablist">'.LF;
      $session = $this->_environment->getSession();
      $history = $session->getValue('history');
      if ( isset($history) and isset($history[1]) and !empty($history[1]) ) {
         $h_module = $history[1]['module'];
      } else {
         $h_module ='';
      }
      $first = true;
      foreach ( $this->_links as $link ) {
         if ($current_context_item->isPrivateRoom()){
            switch ($link['module']) {
               case 'topic':
                  $link_title = getMessage('COMMON_TOPICS');
                  break;
               case 'material':
                  $link_title = getMessage('COMMON_MATERIALS');
                  break;
               case 'user':
                  $link_title = getMessage('COMMON_MY_USER_DESCRIPTION');
                  break;
               case 'myroom':
                  $link_title = getMessage('PRIVATEROOMS');
                  break;
               default:
                  $link_title = $link['title'];
            }
         } else {
            $link_title = '';
            switch ($link['module']) {
               case 'user':
                  $link_title = '<img src="images/commsyicons/16x16/user.png" style="vertical-align:bottom;"/>';
                  break;
               case 'discussion':
                  $link_title = '<img src="images/commsyicons/16x16/discussion.png" style="vertical-align:bottom;"/>';
                  break;
               case 'material':
                  $link_title = '<img src="images/commsyicons/16x16/material.png" style="vertical-align:bottom;"/>';
                  break;
               case 'date':
                  $link_title = '<img src="images/commsyicons/16x16/date.png" style="vertical-align:bottom;"/>';
                  break;
               case 'announcement':
                  $link_title = '<img src="images/commsyicons/16x16/announcement.png" style="vertical-align:bottom;"/>';
                  break;
               case 'group':
                  $link_title = '<img src="images/commsyicons/16x16/group.png" style="vertical-align:bottom;"/>';
                  break;
               case 'todo':
                  $link_title = '<img src="images/commsyicons/16x16/todo.png" style="vertical-align:bottom;"/>';
                  break;
               case 'topic':
                  $link_title = '<img src="images/commsyicons/16x16/topic.png" style="vertical-align:bottom;"/>';
                  break;
               default:
                  $link_title = '';
            }
            $link_title .= $link['title'];
         }
         if ($first){
            $first = false;
         }
         if( $this->_module == 'buzzwords' ){
            // Get linked rubric
            if ( isset($_GET['module']) and !empty($_GET['module']) ) {
               $linked_rubric = $_GET['module'];
               $session->setValue($this->_environment->getCurrentModule().'_linked_rubric',$linked_rubric);
            } elseif ( $session->issetValue($this->_environment->getCurrentModule().'_linked_rubric') ) {
               $linked_rubric = $session->getValue($this->_environment->getCurrentModule().'_linked_rubric');
            } else {
               $linked_rubric = '';
            }
         }
         if( $this->_module == 'tag' ){
            // Get linked rubric
            if ( isset($_GET['module']) and !empty($_GET['module']) ) {
               $linked_rubric = $_GET['module'];
               $session->setValue($this->_environment->getCurrentModule().'_linked_rubric',$linked_rubric);
            } elseif ( $session->issetValue($this->_environment->getCurrentModule().'_linked_rubric') ) {
               $linked_rubric = $session->getValue($this->_environment->getCurrentModule().'_linked_rubric');
            } else {
               $linked_rubric = '';
            }
         }
         if ( $current_context_item->isOpenForGuests() or $this->_current_user->isUser() ) {
            if ( $this->_module == $link['module']
                 or ($this->_module == 'discarticle' and $link['module'] == 'discussion')
                 or ($this->_module == 'material' and $link['module'] == 'search')
                 or ($this->_module == 'section' and $link['module'] == 'material')
                 or ($this->_module == 'version' and $link['module'] == 'material')
                 or ($this->_module == 'buzzwords' and $link['module'] == $linked_rubric)
                 or ($this->_module == 'tag' and $link['module'] == $linked_rubric)
                 or ($this->_module == 'labels' and $link['module'] == 'material')
                 or ($this->_module == 'version_material' and $link['module'] == 'material')
                 or ($this->_module == 'version_archive' and $link['module'] == 'search')
                 or ($this->_module == 'clipboard' and $link['module'] == 'material')
                 or ($this->_module == 'auth' and $link['module'] == 'contact')
                 or ($this->_module == 'user' and $link['module'] == 'contact')
                 or ($this->_module == 'rubric' and $this->_function =='mail' and $link['module'] == $h_module )
            ) {
               $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist_current"');
            } elseif ( $this->_module =='annotation' ) {
               if ( isset($history) and isset($history[1]) and !empty($history[1]) ){
                  $h_module = $history[1]['module'];
               } else {
                  $h_module ='';
               }
               if ( $session->issetValue('annotation_history_module') ){
                  $h_module = $session->getValue('annotation_history_module');
               }
               if ($link['module']== $h_module) {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist_current"');
               } else {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist"');
               }
            } elseif ( $this->_module == 'context' and $this->_function =='info_text_edit' ) {
               if ( isset($history) and isset($history[0]) and !empty($history[0]) ) {
                  $h_module = $history[0]['module'];
               } else {
                  $h_module ='';
               }
               if ( $link['module'] == $h_module ) {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist_current"');
               } else {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist"');
               }
            } else {
               $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist"');
            }
            if (($link['module']!='todo' or $current_context_item->withRubric(CS_TODO_TYPE)) ){
               $html .= $ahref;
            }
         }else {
            $html .= '     <span >'.$link_title.'</span>'.LF;
         }
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<!-- END TABS -->'.LF;
      unset($current_user_item);
      unset($current_context_item);
      return $html;
   }


   function _getBlankLinkRowAsHTML () {
      $html  = LF.'<!-- BEGIN TABS -->'.LF;
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      unset($session);
      $html .= '<div id="tabs_frame" >'.LF;
      $html .= '<div class="tabs">'.LF;
      $html .= '<div style="float:right; margin:0px; padding:0px;">'.LF;


      // rss link
      $current_context_item = $this->_environment->getCurrentContextItem();
      $current_user_item = $this->_environment->getCurrentUserItem();
      $show_rss_link = false;
      if ( $current_context_item->isLocked() ) {
         // do nothing
      } elseif ( $current_context_item->isOpenForGuests() ) {
         $show_rss_link =  true;
      } elseif ( $current_user_item->isUser() ) {
         $show_rss_link =  true;
      }
      $hash_string = '';
      if ( !$current_context_item->isOpenForGuests()
           and $current_user_item->isUser()
         ) {
         $hash_manager = $this->_environment->getHashManager();
         $hash_string = '&amp;hid='.$hash_manager->getRSSHashForUser($current_user_item->getItemID());
      }
      if ( $show_rss_link ) {
         $html .= '<a href="rss.php?cid='.$current_context_item->getItemID().$hash_string.'" target="_blank"><img src="images/rss.png" width="15" height="15" style="vertical-align:bottom;" alt="RSS-Feed dieses Raumes abonnieren"/></a>';
      }
      unset($current_user_item);
      unset($current_context_item);

      // Always show context sensitive help
      $params = array();
      $params['module'] = $this->_module;
      $params['function'] = $this->_function;
      $html .= ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                             $params,
                             '?', '', 'help', '', '',
                             'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','class="navlist_help"').LF;
      unset($params);
      $html .= '</div>'.LF;
      $html .= '<div style="margin:0px; padding:0px;">'.LF;
      $text = '&nbsp;';
      if ( $this->_environment->getCurrentModule() == 'agb'
           and $this->_environment->getCurrentFunction() == 'index'
         ) {
         $text .= $this->_translator->getMessage('AGB_CONFIRMATION');
      }
      $html .= '<span class="navlist">'.$text.'</span>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'/ecke_oben_links.gif" alt="" border="0"/></div>';
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'/ecke_oben_rechts.gif" alt="" border="0"/></div>';
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<!-- END TABS -->'.LF;
      return $html;
   }
   // @segment-end 92557


   function _getLogoAsHTML(){
      $html  = '';
      $logo_filename = '';
      $context_item = $this->_environment->getCurrentContextItem();
      $html .='<table summary="layout">';
      $html .= '<tr>';
      if ( $this->_environment->inCommunityRoom()
           or $this->_environment->inProjectRoom()
           or $this->_environment->inPrivateRoom()
           or $this->_environment->inGroupRoom()
         ) {
         $logo_filename = $context_item->getLogoFilename();
         if ( !empty($logo_filename) ) {
            $params = array();
            $params['picture'] = $context_item->getLogoFilename();
            $curl = curl($this->_environment->getCurrentContextID(), 'picture', 'getfile', $params,'');
            unset($params);
            $html .= '<td>';
            $html .= '<div class="logo" style="vertical-align:top;padding-top:5px;">'.LF;
            $html .= '     <img style="height:4em; padding-top:0px; padding-bottom:0px; padding-left:0px;" src="'.$curl.'" alt="'.$this->_translator->getMessage('COMMON_LOGO').'" border="0"/>';
            $html .= '</div>'.LF;
            $html .= '</td>';
         }
      }
      $html .= '<td style="verticale-align:middle;">';
      $html .= '<h1>'.$context_item->getTitle().'</h1>'.LF;
      $breadcrump = '';
      $portal_item = $this->_environment->getCurrentPortalItem();
      $params = array();
      if ($this->_environment->inProjectRoom() or $this->_environment->inCommunityRoom()){
         $params['room_id'] = $context_item->getItemID();
      }
      $breadcrump.= ahref_curl($portal_item->getItemID(),'home','index',$params,$portal_item->getTitle(),'','','','','','','style="color:#800000"');
      if ($this->_environment->inProjectRoom()){
      	$community_list = $context_item->getCommunityList();
         $community_item = $community_list->getFirst();
         if (!empty($community_item)){
            $breadcrump.= ' > '.ahref_curl($community_item->getItemID(),'home','index','',$community_item->getTitle(),'','','','','','','style="color:#800000"');
         }
         $breadcrump.= ' > '.$context_item->getTitle();
      }elseif($this->_environment->inGroupRoom()){
         $project_item = $context_item->getLinkedProjectItem();
         $community_list = $project_item->getLinkedCommunityListForBreadCrump();
         $community_item = $community_list->getFirst();
         if (!empty($community_item)){
            $breadcrump.= ' > '.ahref_curl($community_item->getItemID(),'home','index','',$community_item->getTitle(),'','','','','','','style="color:#800000"');
         }
         $breadcrump.= ' > '.ahref_curl($project_item->getItemID(),'home','index','',$project_item->getTitle(),'','','','','','','style="color:#800000"');
         $breadcrump.= ' > '.$context_item->getTitle();
      }elseif($this->_environment->inCommunityRoom() or $this->_environment->inPrivateRoom()){
         $breadcrump.= ' > '.$context_item->getTitle();
      }
      $html .= '<span style="font-size:8pt; font-weight:normal;">'.$breadcrump.'</span>'.BRLF;
      $html .= '</td>';
      $html .= '</tr>';
      $html .= '</table>';
      return $html;
   }


   // @segment-begin 92221 asHTMLFirstPart():call_getHTMLHeadAsHTML(): meta+link+header_title
   function asHTMLFirstPart () {
      $html ='';
      $session = $this->_environment->getSession();
      if (!empty($session)) {
         $session_id = $session->getSessionID();
      } else {
         $session_id = '';
      }
      unset($session);
      // Header
      $html .= $this->_getHTMLHeadAsHTML();
      // @segment-end 92221

      // @segment-begin 374 begin<body>,width-from-room-body
      if ( !$this->_blank_page ) {
         $html .= '<body';
         $current_function = $this->_environment->getCurrentFunction();
         if ( ($this->_focus_onload and $current_function != 'index' and $this->_environment->getCurrentModule() != 'help') or ($this->_with_delete_box) ) {
            $html .= ' onload="';
            if ( $this->_focus_onload and $current_function != 'index' and $this->_environment->getCurrentModule() != 'help'){
               $html .=' window.focus();setfocus();';
            }
            if ($this->_with_delete_box){
               $html .= ' initDeleteLayer();';
            }
            if (isset($_GET['show_copies']) or isset($_GET['show_profile'])){
               $html .= ' initLayer("profile");';
            }
            $html .= ' "';
         }elseif(isset($_GET['show_copies']) or isset($_GET['show_profile'])){
            $html .= ' onload="';
            $html .= ' initLayer(\'profile\');';
            $html .= ' "';
         }
         $views = array_merge($this->_views, $this->_views_left, $this->_views_right);
         $view = reset($views);
         while ($view) {
            $html .= $view->getInfoForBodyAsHTML();
            $view = next($views);
         }
         unset($views);
         unset($view);
         $html .= ' class="body">'.LF;
         $html .= '<div style="width: 100%; padding:0px; margin:0px;">'.LF;

      }
      $this->_send_first_html_part = true;
      $html .= '<div id="page_header">';
      $html .= '<div class="page_header_personal_area">';
      $html .= '<div style="float:right;">';
      $html .= $this->getMyAreaAsHTML();
      $html .= '</div>';
      $html .= '<div style="clear:both;">';
      $html .= '</div>';
      $browser = $this->_environment->getCurrentBrowser();
      if ($browser == 'MSIE') {
         $html .='<div style="float:right; padding-top:20px; white-space:nowrap;">';
      }else{
         $html .='<div style="float:right; padding-top:27px; white-space:nowrap;">';
      }
      $html .= '<div style="float:right; vertical-align:bottom;">';
      $html .= '<table style="font-size:10pt; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .= '<tr>';
      $html .= '<td>';
      $html .= $this->_translator->getMessage('MYAREA_CHANGE_MY_ACTUAL_ROOMS').' '.LF;
      $html .= '</td>';
      $html .= '<td>';
      $html .= $this->_getUserPersonalAreaAsHTML().LF;
      $html .= '</td>';
      $html .= '</tr>';
      $html .= '</table>';
      $html .= '</div>';
      $html .= '</div>';
      $html .= '<div style="clear:both;">';
      $html .= '</div>';


#      $html .= 'Angemeldet als: Matthias Finck<br/>Meine aktuellen Räume:<br/>Meine Kopien';
      $html .= '</div>';
      $html .= '<div id="page_header_logo">';
      $html .= $this->_getLogoAsHTML().LF;
      $html .= '</div>';
      $html .= '</div>';
      return $html;
   }



   // @segment-end 87067

   // @segment-begin 86468 sHTMLSecondPart():allways-show-result-from-asHTMLFirstPart()
   function asHTMLSecondPart () {
      $html = '';
      if ( !$this->_send_first_html_part ) {
         $html .= $this->asHTMLFirstPart();
      }
      // @segment-end 86468
      // @segment-begin 66992 if-with-left-menue-display-my_area_box:call-getMyAreaAsHTML()
      $session = $this->_environment->getSession();
      if (!empty($session)) {
         $session_id = $session->getSessionID();
      } else {
         $session_id = '';
      }
      // Body
/*      if ( !$this->_blank_page ) {
         $html .= '<tr>'.LF;
         $left_menue_status = $session->getValue('left_menue_status');
         if ($left_menue_status != 'disapear' and !$this->_without_left_menue ) {
            $html .= '<td style="width:13.7em;  margin-bottom:0px; padding:0px; vertical-align:top;">'.LF;
            $html .= LF.'<!-- COMMSY_MYAREA: START -->'.LF.LF;
            $html .= $this->getMyAreaAsHTML();
            $html .= LF.'<!-- COMMSY_MYAEREA: END -->'.LF.LF;
            $html .= '</td>'.LF;
         }
      }*/
      unset($session);
      return $html;
   }
   // @segment-end 66992

  function getProfileBoxAsHTML(){
     $html = '';
     $environment = $this->_environment;
     $html  = '<div style="position:absolute; left:0px; top:0px; z-index:1000; width:100%; height: 100%;">'.LF;
     $html .= '<div style="z-index:1000; margin-top:20px; margin-bottom:10px; margin-left: 20%; width:60%; text-align:left; background-color:#FFFFFF;">';
     global $profile_view;
     $html .= $profile_view->asHTML();
     $html .= '</div>';
     $html .= '</div>';
     $html .= '<div id="profile" style="position: absolute; left:0px; top:0px; z-index:900; width:100%; height: 850px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
     $html .= '</div>';
     return $html;
  }

  function getCopyBoxAsHTML(){
     $html = '';
     $environment = $this->_environment;
     $html  = '<div style="position:absolute; left:0px; top:0px; z-index:1000; width:100%; height: 100%;">'.LF;
     $html .= '<div style="z-index:1000; margin-top:20px; margin-bottom:10px; margin-left: 15%; width:70%; text-align:left; background-color:#FFFFFF;">';
     global $copy_view;
     $html .= $copy_view->asHTML();
     $html .= '</div>';
     $html .= '</div>';
     $html .= '<div id="profile" style="position: absolute; left:0px; top:0px; z-index:900; width:100%; height: 850px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
     $html .= '</div>';
     return $html;
  }

   function getDeleteBoxAsHTML(){
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      $left = '0em';
      $width = '100%';
      $html  = '<div style="position: absolute; z-index:1000; top:95px; left:'.$left.'; width:'.$width.'; height: 100%;">'.LF;
      $html .= '<center>';
      $html .= '<div style="position:fixed; left:'.$left.'; z-index:1000; margin-top:10px; margin-left: 30%; width:400px; padding:20px; background-color:#FFF; border:2px solid red;">';
      $html .= '<form style="margin-bottom:50px;" method="post" action="'.$this->_delete_box_action_url.'">';

      if ($this->_delete_box_mode == 'index'){
         $html .= '<h2>'.getMessage('COMMON_DELETE_BOX_INDEX_TITLE');
         $html .= '</h2>';
         $count = 0;
         if($this->_delete_box_ids){
            $count = count($this->_delete_box_ids);
         }
         if($count == 1){
            $html .= '<p style="text-align:left;">'.getMessage('COMMON_DELETE_BOX_INDEX_DESCRIPTION_ONE_ENTRY',$count);
            $html .= '</p>';
         }else{
            $html .= '<p style="text-align:left;">'.getMessage('COMMON_DELETE_BOX_INDEX_DESCRIPTION',$count);
            $html .= '</p>';
         }
      } elseif ( $this->_environment->getCurrentFunction() == 'preferences'
                 or
                 ( $this->_environment->getCurrentModule() == 'project'
                   and $this->_environment->getCurrentFunction() == 'detail'
                 )
               ) {
         $html .= '<h2>'.getMessage('COMMON_DELETE_BOX_TITLE_ROOM');
         $html .= '</h2>';
         $html .= '<p style="text-align:left;">'.getMessage('COMMON_DELETE_BOX_DESCRIPTION_ROOM');
         $html .= '</p>';
      } elseif ( $this->_environment->getCurrentModule() == 'material'
                   and $this->_environment->getCurrentFunction() == 'detail'
                   and (isset ($_GET['del_version']) and !empty($_GET['del_version']))
               ) {
         $html .= '<h2>'.getMessage('COMMON_DELETE_VERSION_TITLE_MATERIAL_VERSION');
         $html .= '</h2>';
         $html .= '<p style="text-align:left;">'.getMessage('COMMON_DELETE_BOX_DESCRIPTION_MATERIAL_VERSION');
         $html .= '</p>';
      }elseif ( $this->_environment->getCurrentModule() == 'configuration'
                   and $this->_environment->getCurrentFunction() == 'wiki'
               ) {
         $html .= '<h2>'.getMessage('COMMON_DELETE_WIKI_TITLE');
         $html .= '</h2>';
         $html .= '<p style="text-align:left;">'.getMessage('COMMON_DELETE_BOX_DESCRIPTION_WIKI');
         $html .= '</p>';
      }else{
         $user_item = $this->_environment->getCurrentUserItem();
         $html .= '<h2>'.getMessage('COMMON_DELETE_BOX_TITLE');
         $html .= '</h2>';
         $html .= '<p style="text-align:left;">'.getMessage('COMMON_DELETE_BOX_DESCRIPTION');
         $html .= '</p>';
         if( $user_item->isModerator() ) {
            $html .= '<p style="text-align:left;">'.getMessage('COMMON_DELETE_BOX_DESCRIPTION_MODERATOR');
            $html .= '</p>';
         }
      }
      $html .= '<div>';
      $html .= '<input style="float:right;" type="submit" name="delete_option" value="'.getMessage('COMMON_DELETE_BUTTON').'" tabindex="2"/>';
      $html .= '<input style="float:left;" type="submit" name="delete_option" value="'.getMessage('COMMON_CANCEL_BUTTON').'" tabindex="2"/>';
      if ( ( $this->_environment->getCurrentModule() == 'configuration'
             and $this->_environment->getCurrentFunction() == 'preferences'
           )
           or
           ( $this->_environment->getCurrentModule() == 'project'
             and $this->_environment->getCurrentFunction() == 'detail'
           )
         ) {
         $html .= '<input style="float:left;" type="submit" name="delete_option" value="'.getMessage('ROOM_ARCHIV_BUTTON').'" tabindex="2"/>';
      }
      $html .= '</div>';
      $html .= '</form>';
      $html .= '</div>';
      $html .= '</center>';
      $html .= '</div>';
      $html .= '<div id="delete" style="position: absolute; z-index:900; top:95px; left:'.$left.'; width:'.$width.'; height: 100%; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
      $html .= '</div>';
      return $html;
   }

   function addDeleteBox($url,$mode='detail',$selected_ids = NULL){
        $this->_with_delete_box = true;
      $this->_delete_box_action_url = $url;
      $this->_delete_box_mode = $mode;
      $this->_delete_box_ids = $selected_ids;
   }



   function asHTML () {
      // @segment-begin 47648 asHTML():call-_getLinkRowAsHTML()/_getBlankLinkRowAsHTML():display-tabs
      $html = '';
      $session = $this->_environment->getSession();
      if (!empty($session)) {
         $session_id = $session->getSessionID();
      } else {
         $session_id = '';
      }
      if ( !$this->_blank_page ) {
         // Link Row
         if ($this->_with_navigation_links and !$this->_shown_as_printable) {
            $html .= $this->_getLinkRowAsHTML();
         } else {
            $html .= $this->_getBlankLinkRowAsHTML();
         }
         // @segment-end 47648
         // @segment-begin 65077 asHTML():set-<div>_style/class-for-views-part(under-tabs)
         // Content
         $width = 'width:100%;';//not used
         $html .= '<div style="padding:0px; margin:0px;">'.LF;
         $html .= '<div class="content">'.LF;

         // Full Screen Views
         $first = true;
         $html .= '<div class="content_fader">';

         // @segment-end 65077
         // @segment-begin 97506 asHTML():???agb?
         $show_agb_again = false;
         $current_user = $this->_environment->getCurrentUserItem();
         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_user->isUser()
              and !$current_user->isRoot()
              and $current_context->withAGB()
            ) {
            $user_agb_date = $current_user->getAGBAcceptanceDate();
            $context_agb_date = $current_context->getAGBChangeDate();
            if ($user_agb_date < $context_agb_date) {
               $show_agb_again = true;
            }
         }
         unset($current_user);


         $html .= LF.'<div id="main">'.LF;

         $html .= $this->_getPluginInfosForBeforeContentAsHTML();

         if ($show_agb_again) {
            $html .='&nbsp;';
         }
         // @segment-end 97506
         // @segment-begin 64067 asHTML():display-full_screen_views-added-to-the-$page(e.g.:cs_announcement_view-which-asHTML()-from-upper_class-cs_index_view)
         if ( !empty($this->_views) ) {
            foreach ($this->_views as $view) {
               if ($first){
                  $first = false;
                  $html .= $view->asHTML();
               }else{
                  $html .= $view->asHTML();
               }
            }
         }
         if ($this->_environment->getCurrentModule()!='home' or isset($view->_title) or $show_agb_again){
            $html .='</div>';
         }
         // @segment-end 64067
         // @segment-begin 45532 asHTML():display-left_views/right_views-if-any()e.g.-if-mod=home
         // Left views
         if ( !empty($this->_views_right) ) {
            if ($this->_environment->getCurrentModule()=='home'){
               $context_item = $this->_environment->getCurrentContextItem();
               $title_string = '';
               $desc_string = '';
               $size_string = '';
               $config_text = '';
               $html .='<div style="clear:both;">'.LF;
               $html .='</div>'.LF;
               $html .= '<div id="commsy_panels" style="width:100%;">'.LF;
               $html .='<div style="float:right; width:27%; padding-top:5px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
               $html .='<div style="width:250px;">'.LF;
               if ( $this->_environment->inPrivateRoom() ){
                  $html .= $this->_views_left[0]->_getListInfosAsHTML().LF;
               }
               $first = true;
               $count = 1;


               $home_right_conf = $context_item->getHomeRightConf();
               $right_boxes_config_array = array();
               if (!empty($home_right_conf)){
                  $home_right_conf_array = explode(',',$home_right_conf);
                   foreach ($home_right_conf_array as $box_conf) {
                      $box_conf_array = explode('_',$box_conf);
                      $right_boxes_config_array[$box_conf_array[0]]= $box_conf_array[1];
                   }
                }
                $append = '';
                foreach ($this->_views_right as $view) {
                  $view_name = $view->getViewName();
                  if($view_name != 'actions' and $view_name != 'activity' and $view_name != 'usageinfos' and $view_name != 'preferences' and $view_name != 'search' and $view_name != 'homeextratools'
                     and ($view_name != 'netnavigation' or $context_item->withRubric(CS_GROUP_TYPE) or $context_item->withRubric(CS_TOPIC_TYPE) or $context_item->withRubric(CS_INSTITUTION_TYPE) )
                  ){
                     $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
                     $html .= $view->asHTML();
                     $title_string .= $append.'"'.$view->getViewTitle().'"';
                     $desc_string  .= $append.'"&nbsp;"';
                     $size_string  .= $append.'"10"';
                     if (
                         ($view_name == 'buzzwords' and $context_item->isBuzzwordShowExpanded() )
                         or ($view_name == 'tags' and $context_item->isTagsShowExpanded() )
                         or ($view_name == 'netnavigation' and $context_item->isNetnavigationShowExpanded() )
                     ) {
                        $config_text .= $append.'true';
                     }else{
                        $config_text .= $append.'false';
                     }
                     $html .= '</div>';
                  }elseif($view_name == 'activity'){
                     $html .= '<div style="margin-bottom:1px;">'.LF;
                     $html .= $view->asHTML();
                     $html .= '</div>';
                  }elseif($view_name == 'usageinfos'){
                     $html .= '<div style="margin-bottom:10px;">'.LF;
                     $html .= $view->asHTML();
                     $html .= '</div>';
                  }
                  if ( $view_name == 'buzzwords' or $view_name == 'tags' or $view_name == 'netnavigation') {
                     $append = ',';
                  }
               }
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .='<div class="content_display_width" style="padding-top:5px; vertical-align:bottom;">'.LF;

               $conf = $context_item->getHomeConf();
               if ( !empty($conf) ) {
                  $rubrics = explode(',', $conf);
               } else {
                  $rubrics = array();
               }
               foreach ( $rubrics as $rubric ) {
                  $rubric_array = explode('_', $rubric);
                  if ( $rubric_array[1] != 'none' and $rubric_array[1] != 'nodisplay') {
                     if ($rubric_array[1] == 'short'){
                        if (empty($config_text)){
                           $config_text .='true';
                        }else{
                           $config_text .= ',true';
                        }
                     }else{
                        if (empty($config_text)){
                           $config_text .='false';
                        }else{
                           $config_text .= ',false';
                        }
                     }
                  }
               }
               foreach ($this->_views_left as $view) {
                  if (!$this->_environment->inPrivateRoom()){
                     if ($view->getViewName() != getMessage('COMMON_INFORMATION_INDEX')){
                        $html .= '<div class="commsy_panel" style="margin-bottom:20px; border:0px solid black;">'.LF;
                     }else{
                        $html .= '<div id="commsy_no_panel" style="margin-bottom:20px; border:0px solid black;">'.LF;
                     }$desc = $view->_getDescriptionAsHTML();
                     $noscript_title = $view->getViewTitle();
                     if ($view instanceof cs_user_short_view or $view instanceof cs_user_tiny_view){
                        $image_text = 'user';
                     }elseif ($view instanceof cs_announcement_short_view or $view instanceof cs_announcement_tiny_view){
                        $image_text = 'announcement';
                     }elseif ($view instanceof cs_date_short_view or $view instanceof cs_date_tiny_view){
                        $image_text = 'date';
                     }elseif ($view instanceof cs_group_short_view or $view instanceof cs_group_tiny_view){
                        $image_text = 'group';
                     }elseif ($view instanceof cs_todo_short_view or $view instanceof cs_todo_tiny_view){
                        $image_text = 'todo';
                     }elseif ($view instanceof cs_topic_short_view or $view instanceof cs_topic_tiny_view){
                        $image_text = 'topic';
                     }elseif ($view instanceof cs_institution_short_view or $view instanceof cs_institution_tiny_view){
                        $image_text = 'institution';
                     }elseif ($view instanceof cs_discussion_short_view or $view instanceof cs_discussion_tiny_view){
                        $image_text = 'discussion';
                     }else{
                        $image_text = 'material';
                     }
                     $image = '<img src="images/commsyicons/16x16/'.$image_text.'.png" style="padding-top:2px; float:left;" alt="'.getMessage('COMMON_NEW_MATERIAL').'"/>';
                     $title = addslashes($image.' '.$view->getViewTitle());
                     if ($view->getViewName() != getMessage('COMMON_INFORMATION_INDEX')){
                       $item_list = $view->getList();
                       $size = 10;
                       if ( isset($item_list) ) {
                          $size = $item_list->getCount();
                       }
                       if ( empty($size) ) {
                          $size = 10;
                       }
                       if ( !empty($size) and
                            ( $view instanceof cs_user_short_view
                              or $view instanceof cs_topic_short_view
                              or $view instanceof cs_institution_short_view
                              or $view instanceof cs_group_short_view
                            )
                          ) {
                          $size = round($size/3,0);
                       }
                       if (empty($title_string)){
                          $title_string .= '"'.$title.'"';
                          $desc_string  .= '"'.$desc.'"';
                          $size_string  .= '"'.$size.'"';
                       }else{
                          $title_string .= ',"'.$title.'"';
                          $desc_string  .= ',"'.$desc.'"';
                          $size_string  .= ',"'.$size.'"';
                       }
                     }
                     $html .= '<div>';
                     $html .= '<noscript>';
                     $html .= '<div class="homeheader">'.$noscript_title.' '.$desc.'</div>';
                     $html .= '</noscript>';
                  }
                  $html .= $view->asHTML();
                  if (!$this->_environment->inPrivateRoom()){
                     $html .= '</div>';
                     $html .= '</div>';
                  }
                  $last_view = $view;
               }

               $html .= '</div>';
               $html .= '</div>';
               $html .='<div style="clear:both;">'.LF;
               $html .='</div>'.LF;
#               $html .= '</form>'."\n";
               $html .='</div>'.LF;
               $html .= '<script type="text/javascript">'.LF;
               /*******************************/
               /* TBD: Die Werte des dritten Arrays setzen, falls die Veränderungen*/
               /* gespeichert werden sollen Array("pane1","pane1",...)*/
               /*******************************/
               $title_string = str_replace('</','&COMMSYDHTMLTAG&',$title_string);
               $html .= 'initCommSyPanels(Array('.$title_string.'),Array('.$desc_string.'),Array('.$config_text.'),Array(),Array('.$size_string.'));'.LF;
               $html .= '</script>'.LF;
            }else{
               $html .= '<table style="width:100%; padding-top:0px;" summary="Layout">'.LF;
               $html .= '<tr>'.LF;
               $html .= '<td class="leftviews">'.LF;
               $html .= '<div class="infoborder" style="margin-top:1px; padding-bottom:10px;"></div>'.LF;
               foreach ($this->_views_left as $view) {
                  $html .= $view->asHTML();
                  $last_view = $view;
               }
               $html .= '</td>'.LF;
               $html .= '<td class="rightviews">'.LF;
               $html .= '<div class="infoborder" style="margin-top:1px; padding-bottom:10px;"></div>'.LF;
               $first = true;
               foreach ($this->_views_right as $view) {
               if ($first) {
                  $first = false;
               } else {
                  $html .= '<br/>'.LF;
               }
               $html .= $view->asHTML();
               $last_view = $view;
               }
               unset($first);
               $html .= '</td>'.LF;
               $html .= '</tr>'.LF;
               $html .= '</table>'.LF;
            }
         }
         $html .= '</div>'.LF;
         // @segment-end 45532
         // @segment-begin 13258 asHTML():display-date,month,time-right_bottom_corner-of-the-page
         $html .= '<div class="top_of_page">'.LF;
         $html .= '<div style="float:right; padding-right:0px;">'.LF;
         $date = date("Y-m-d");
         $date_array = explode('-',$date);
         $current_time = localtime();
         $month = getLongMonthName($current_time[4]);
         $year = $current_time[5]+1900;
         $language = $this->_environment->getSelectedLanguage();
         if ($language !='en'){
            $text = $date_array[2].'. '.$month.' '.$date_array[0];
         }else{
            $text = $date_array[2].' '.$month.' '.$date_array[0];
         }
         $text .=', ';
         if (strlen($current_time[1])==1){
            $current_time[1] = '0'.$current_time[1];
         }
         $text .= $current_time[2].':'.$current_time[1];
         $html .= '<span>'.$text.'</span>'.LF;
         $html .= '</div>'.LF;

         // @segment-end 13258
         // @segment-begin 38811 asHTML():image+link-to page_top
         $html .= '<div class="footer" style="padding-left:5px;">'.LF;
         $html .= '<a href="#top">'.'<img src="images/browse_left2.gif" alt="&lt;" border="0"/></a>&nbsp;<a href="#top">'.getMessage('COMMON_TOP_OF_PAGE').'</a>';
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;

/*         if ($this->_with_navigation_links and !$this->_shown_as_printable) {
            $html .= $this->_getBlankLinkRowAsHTML(true);
         } else {
            $html .= $this->_getBlankLinkRowAsHTML();
         }*/
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;


         if ($this->_with_delete_box){
            $html .= $this->getDeleteBoxAsHTML();
         }

         if ( isset($_GET['show_profile']) and $_GET['show_profile'] == 'yes'){
            $html .= $this->getProfileBoxAsHTML();
         }
         if ( isset($_GET['show_copies']) and ($_GET['show_copies'] == 'yes') ) {
            $html .= $this->getCopyBoxAsHTML();
         }

         $html .= $this->_getPluginInfosForAfterContentAsHTML();

         // @segment-end 35577
         // @segment-begin 91880 asHTML(): link"email_to_moderator"&co(right-bottom-corner)
         $html .= '<div style="border-top:1px solid #B0B0B0; background-color:white;">'.LF;
         $html .= '<div class="footer" style="float:right; text-align:right; padding-left:20px; padding-right:20px; padding-top:5px; padding-bottom:10px; background-color:white;">'.LF;
         $email_to_moderators = '';
         $current_user = $this->_environment->getCurrentUserItem();
         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_context->showMail2ModeratorLink() ) {
            $email_to_moderators = ahref_curl($this->_environment->getCurrentContextID(),
                                                'mail',
                                                 'to_moderator',
                                                 '',
                                                 $this->_translator->getMessage('COMMON_MAIL_TO_MODERATOR'));
         }

          // service link

          if ( $current_context->withAGB() and $this->_with_agb_link ) {
             $desc_link = ahref_curl($this->_environment->getCurrentContextID(),'agb','index','',getMessage('COMMON_AGB_CONFIRMATION_LINK_INPUT'),'','agb','','',' onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
          } else {
             $desc_link ='';
          }
          if ( $current_context->showServiceLink()
               and $current_user->isUser()
               and !$this->_environment->inPrivateRoom()
               and !( $this->_environment->getCurrentModule() =='agb' and $this->_environment->getCurrentFunction()=='index' )
             ) {
            $color = '#D5D5D5';
            $server_item = $this->_environment->getServerItem();
            $link = 'http://www.commsy.net/?n=Software.FAQ&amp;mod=edit';

            //Hierarchy of service-email: Set email, test if portal tier has one, then server tier
            $service_email = $current_context->getServiceEmail();

            if ($service_email == '') {
               $portal_item = $this->_environment->getCurrentPortalItem();
               if (isset($portal_item) and !empty($portal_item)) {
                  $service_email = $portal_item->getServiceEmail();
               }
               unset($portal_item);
            }

            if ($service_email == '') {
               $service_email = $server_item->getServiceEmail();
            }

            if ($service_email == '') {
               $service_email = 'NONE';
            }

            $email_to_service = '<form action="'.$link.'" method="post" name="service" style="margin-bottom: 0px;">'.LF;
            $email_to_service .= '<input type="hidden" name="server_name" value="'.$this->_text_as_html_short($server_item->getTitle()).'"/>'.LF;
            $email_to_service .= '<input type="hidden" name="server_ip" value="'.$this->_text_as_html_short($_SERVER["SERVER_ADDR"]).'"/>'.LF;
            $email_to_service .= '<input type="hidden" name="context_id" value="'.$this->_text_as_html_short($current_context->getItemID()).'"/>'.LF;
            $email_to_service .= '<input type="hidden" name="context_name" value="'.$this->_text_as_html_short($current_context->getTitle()).'"/>'.LF;
            $email_to_service .= '<input type="hidden" name="context_type" value="'.$this->_text_as_html_short($current_context->getType()).'"/>'.LF;
            $email_to_service .= '<input type="hidden" name="user_name" value="'.$this->_text_as_html_short($current_user->getFullname()).'"/>'.LF;
            $email_to_service .= '<input type="hidden" name="user_email" value="'.$this->_text_as_html_short($current_user->getEmail()).'"/>'.LF;
            $email_to_service .= '<input type="hidden" name="service_email" value="'.$this->_text_as_html_short($service_email).'"/>'.LF;
            $email_to_service .= $this->_translator->getMessage('COMMON_MAIL_TO_SERVICE').' <input type="image" src="images/servicelink.jpg" alt="Link to CommSyService" style="vertical-align:text-bottom;" />'.LF;
            $email_to_service .= '</form>'.LF;
            $html .= '<table style="margin:0px; padding:0px; border-collapse: collapse; border:0px solid black;" summary="Layout">'.LF;
            $html .= '  <tr>'.LF;
            $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
            if($email_to_moderators != '') {
               $html .= $email_to_moderators.'&nbsp;-&nbsp;';
            }
            $html .= '     </td>'.LF;
            if ( !empty($desc_link) ){
               $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
               $html .= $desc_link.'&nbsp;-&nbsp;';
               $html .= '     </td>'.LF;
            }
            $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
            $html .= $email_to_service;
            $html .= '     </td>'.LF;
            $html .= '  </tr>'.LF;
            $html .= '</table>'.LF;
         } elseif ( !$this->_environment->inPrivateRoom()
                    and !( $this->_environment->getCurrentModule() =='agb' and $this->_environment->getCurrentFunction()=='index' )
         ) {
            $html .= '<table style="margin:0px; padding:0px; border-collapse: collapse; border:0px solid black;" summary="Layout">'.LF;
            $html .= '  <tr>'.LF;
            $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
            $html .= $email_to_moderators;
            $html .= '</td>'.LF;
            if ( !empty($desc_link) ){
               $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
               $html .= '&nbsp;-&nbsp;'.$desc_link;
               $html .= '     </td>'.LF;
            }
            $html .= '  </tr>'.LF;
            $html .= '</table>'.LF;
         }
         $html .= '</div>'.LF;
         $html .= '<div style="padding-top:5px; padding-left:10px;">'.LF;
         $html .= $this->_getSystemInfoAsHTML();
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         unset($current_user);
         unset($current_context);
         unset($server_item);
         $html .= $this->_getFooterAsHTML();
         $html .= '</body>'.LF;
         $html .= '</html>'.LF;
      }
      return $html;
   }

   private function _getFlagsAsHTML () {
      $html = '&nbsp;&nbsp;|&nbsp;&nbsp;';
      if ( !( $this->_environment->getCurrentModule() == 'agb'
              and $this->_environment->getCurrentFunction() == 'index'
            )
         ) {
         // language options
         $selected_language = $this->_environment->getSelectedLanguage();
         $current_context = $this->_environment->getCurrentContextItem();
         $language = $current_context->getLanguage();
         $language_array = $this->_environment->getAvailableLanguageArray();
         unset($current_context);
         foreach ($language_array as $lang) {
            if ( $lang == 'en' ) {
               $flag_lang = 'gb';
            } else {
               $flag_lang = $lang;
            }
            if ( strtolower($selected_language) == $lang ) {
               $img = '<img style="vertical-align:bottom;" src="images/flags/'.$flag_lang.'.gif" alt="'.$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG').'"/>';
               $html .= $img.'&nbsp;&nbsp;';
            } elseif ( $language != 'user' ) {
               $img = '<img style="vertical-align:bottom;" src="images/flags/'.$flag_lang.'_gray.gif" alt="'.$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG_DISABLED',$this->_translator->getMessageInLang($lang,strtoupper($language))).'" title="'.$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG_DISABLED',$this->_translator->getMessageInLang($lang,strtoupper($language))).'"/>';
               $html .= $img.'&nbsp;&nbsp;';
            } else {
               $img = '<img style="vertical-align:bottom;" src="images/flags/'.$flag_lang.'.gif" alt="'.$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG').'"/>';
               $params = array();
               $params['language'] = $lang;
               $html .= ahref_curl($this->_environment->getCurrentContextID(),'language','change',$params,$img,$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG')).'&nbsp;&nbsp;'.LF;
               unset($params);
            }
            unset($img);
         }
      }
      return $html;
   }


   function _getCopyLinkAsHTML(){
      $html = '';
      $context_item = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  array();
      }
      unset($current_room_modules);
      $modules = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none') {
            $modules[] = $link_name[0];
         }
      }
      unset($room_modules);
      $html_array = array();
      $rubric_copy_array = array(CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE, CS_MATERIAL_TYPE,CS_TODO_TYPE);
      $count = 0;
      foreach ($rubric_copy_array as $rubric){
         $id_array = $session->getValue($rubric.'_clipboard');
         $count += count($id_array);
      }
      unset($rubric_copy_array);
      unset($context_item);
      if ( $count > 0 ){
         $params = $this->_environment->getCurrentParameterArray();
         $params['show_copies'] = 'yes';
         unset($params['show_profile']);
         $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $params,$this->_translator->getMessage('MYAREA_MY_COPIES'),'','','','','','','style="color:#800000"').''.LF;
      }else{
         $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.'<span class="disabled">'.getMessage('MYAREA_MY_COPIES').'</span>'.LF;
      }
      return $html;
   }




   function getMyAreaAsHTML() {
      // @segment-begin 47891 read cs_mode-from-GET/POST(values_user_logged_in=password_change/account_change/become_member;values_user_logged_out=portalmember/account_forget/passwort_forget)
      $get_vars  = $this->_environment->getCurrentParameterArray();
      $post_vars = $this->_environment->getCurrentPostParameterArray();
      $current_context = $this->_environment->getCurrentContextItem();
      $current_portal = $this->_environment->getCurrentPortalItem();
      if (!empty($get_vars['cs_modus'])) {
         $cs_mod = $get_vars['cs_modus'];
      } elseif (!empty($post_vars['cs_modus'])) {
         $cs_mod = $post_vars['cs_modus'];
      } else {
         $cs_mod = '';
      }
      unset($get_vars);
      unset($post_vars);
      // @segment-end 47891
      // @segment-begin 65267 titel-of-my_area_box/upper_corner_pictures
      $html  = '<div style="white-space:nowrap;">';
/*      $html .= '<table summary="layout" style="border-collapse:collapse;">';
      $html .= '<tr>';
      $html .= '<td>';
      $html .= $this->_translator->getMessage('MYAREA_LOGGED_ON_AS').' ';
      $html .= '</td>';
      $html .= '<td>';*/
      if ( $this->_with_personal_area) {
         if ( !empty($this->_current_user) and ($this->_current_user->getUserID() == 'guest' and $this->_current_user->isGuest()) and !$this->_environment->inServer() ) {
            $html .= $this->_translator->getMessage('MYAREA_LOGIN_NOT_LOGGED_IN');
            // @segment-end 77327
            // @segment-begin 69973 no-cs_modus/user=guest:if-logged-in-as-guest
         } elseif ( !($this->_environment->inServer() and $this->_current_user->isGuest()) ) {
               $params = array();
               $params['iid'] = $this->_current_user->getItemID();
               $fullname = $this->_current_user->getFullname();
               $html .= $fullname;
               $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), 'context', 'logout', $params,$this->_translator->getMessage('MYAREA_LOGOUT'),'','','','','','','style="color:#800000"').''.LF;
               $params = $this->_environment->getCurrentParameterArray();
               $params['uid'] = $this->_current_user->getItemID();
               $params['show_profile'] = 'yes';
               unset($params['show_copies']);
               $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $params,$this->_translator->getMessage('MYAREA_PROFILE'),'','','','','','','style="color:#800000"').''.LF;
               $html .= $this->_getCopyLinkAsHTML();
               $html .= '&nbsp;'.$this->_getFlagsAsHTML();
         }
      }
      if ( $current_context->isOpenForGuests() and !$this->_current_user->isUser()
                 and !$this->_environment->inServer()
                 and !$this->_environment->inPortal()
      ) {
         $html .= '<span class="bold">'.$this->_translator->getMessage('MYAREA_LOGIN_AS_GUEST').'</span>';
      }
#      $html .= '</td>';
#      $html .= '</tr>';
      // @segment-end 65267
      // personal area
      // @segment-begin 77327 no-cs_modus/user=guest:no-user-logged-in-message
      if ( $this->_with_personal_area and empty($cs_mod)) {
         if ( !($this->_environment->inServer() and $this->_current_user->isGuest()) ) {
            $params = array();

            // @segment-end 70706
            // @segment-begin 23516 no-cs_modus/user-status><0:display-user_name,font-size-depends-on-length
            // @segment-end 23516
            // @segment-begin 67550 no-cs_modus/user-status><0:link-log_out

            if (!$this->_environment->inServer()) {

               unset($current_user_item);
/*               if ((!$user->isRoot() and $user->isUser()) or ($user->isGuest() and $user->getUserID() != 'guest')
               ){
                  $private_room_manager = $this->_environment->getPrivateRoomManager();
                  $own_room = $private_room_manager->getRelatedOwnRoomForUser($user,$this->_environment->getCurrentPortalID());
                  global $c_annonymous_account_array;
                  if ( isset($own_room)
                       and empty($c_annonymous_account_array[strtolower($this->_current_user->getUserID()).'_'.$this->_current_user->getAuthSource()])
                     ) {
                     $html .= '<span> '.ahref_curl($own_room->getItemID(), 'home',
                                      'index',
                                      '',
                                      '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;

                     $html .= ahref_curl($own_room->getItemID(), 'home', 'index', '',$this->_translator->getMessage('MYAREA_LOGIN_TO_OWN_ROOM'),'','','','','','','style="color:#800000"').'</span>'.BRLF;
                  }
                  unset($own_room);
               }
               $html .= '<span> '.ahref_curl($this->_environment->getCurrentPortalID(), 'home',
                                        'index',
                                        '',
                                        '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;

               $html .= ahref_curl($this->_environment->getCurrentPortalID(), 'home', 'index', '',$this->_translator->getMessage('COMMON_PORTAL').' ('.$this->_translator->getMessage('MYAREA_LOGIN_TO_PORTAL_OVERVIEW').')','','','','','','','style="color:#800000"').'</span>'.LF;

               // @segment-end 7294
               // @segment-begin 90042 link-to:portal-overview-if-root-user
               if ( $this->_current_user->isRoot() ) {
                  $html .= BR.'<span> '.ahref_curl($this->_environment->getServerID(), 'home',
                                        'index',
                                        '',
                                        '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;

                  $html .= ahref_curl($this->_environment->getServerID(), 'home', 'index', '',$this->_translator->getMessage('MYAREA_LOGIN_TO_ALL_PORTALS'),'','','','','','','style="color:#800000"').'</span>'.LF;
               }*/
            } else {
               if ( $this->_current_user->isRoot() ) {
                  $html .= '<tr>';
                  $html .= '<td colspan ="2">';
                  $html .= '<span> '.ahref_curl($this->_environment->getServerID(), 'home',
                                        'index',
                                        '',
                                        '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;

                  $html .= ahref_curl($this->_environment->getServerID(), 'home', 'index', '',$this->_translator->getMessage('MYAREA_LOGIN_TO_ALL_PORTALS'),'','','','','','','style="color:#800000"').'</span>'.LF;
                  $html .= '</td>';
                  $html .= '</tr>';
               }
            }
            // @segment-begin 7294 link-to:own-room/room-overview

            unset($current_context);
            unset($current_portal);
         }
      }
      $html .= '</div>';
      return $html;
   }

}
?>