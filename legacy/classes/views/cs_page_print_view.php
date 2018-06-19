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

/** upper class of the detail view
 */
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
class cs_page_print_view extends cs_page_view {

   /**
    * string - containing the parameter of the page
    */
   var $_current_parameter = '';

   var $_form_tags =false;

   var $_form_action= '';

   /**
    * array - containing the hyperlinks for the page
    */
   var $_links = array();

   var $_space_between_views=true;

   var $_blank_page = false;

   var $_blank_page_content ='';

   var $_configuration_list_view = NULL;
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

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_page_view::__construct($params);
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

   /** so page will be displayed without the navigation links
    * this method skip a flag, so that the navigation links will not be shown
    */
   function setWithoutNavigationLinks () {
      $this->_with_navigation_links = false;
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

   function addConfigurationListView ($view) {
      $this->_configuration_list_view = $view;
   }

   function addConfigurationPreferencesView ($view) {
      $this->_configuration_preferences_view = $view;
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
    *
    * @author CommSy Development Group
    */
   function _getLinkRowAsHTML () {

      $html  = LF.'<!-- BEGIN TABS -->'."\n";
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status =='disapear'){
         $width = 'width:940px;';
      }else{
         $width = 'width:754px;';
      }
      $html .= '<div class="tabs_frame" style="'.$width.'">'.LF;
      $html .= '<div class="tabs">'.LF;
      $html .= '<div style="margin:0px; padding:0px;">'."\n";
      $session = $this->_environment->getSession();
      $history = $session->getValue('history');
      if ( isset($history) and isset($history[1]) and !empty($history[1]) ) {
         $h_module = $history[1]['module'];
      } else {
         $h_module ='';
      }      // construct tabs
      $context = $this->_environment->getCurrentContextItem();
        $first = true;
        foreach ( $this->_links as $link ) {
            if ($context->isPrivateRoom()){
               switch ($link['module']) {
                  case 'topic':
                     $link_title = $this->_translator->getMessage('COMMON_TOPICS');
                     break;
                  case 'material':
                     $link_title = $this->_translator->getMessage('COMMON_MATERIALS');
                     break;
                  case 'user':
                     $link_title = $this->_translator->getMessage('COMMON_MY_USER_DESCRIPTION');
                     break;
                  case 'myroom':
                     $link_title = $this->_translator->getMessage('PRIVATEROOMS');
                     break;
                  default:
                     $link_title = $link['title'];
               }
            }else{
               $link_title = $link['title'];
            }
            if ($first){
               $first = false;
            }
            if ( $context->isOpenForGuests() or $this->_current_user->isUser() ) {
               if ( $this->_module == $link['module']
                 or ($this->_module == 'discarticle' and $link['module'] == 'discussion')
                 or ($this->_module == 'material' and $link['module'] == 'search')
                 or ($this->_module == 'section' and $link['module'] == 'material')
                 or ($this->_module == 'version' and $link['module'] == 'material')
                 or ($this->_module == 'labels' and $link['module'] == 'material')
                 or ($this->_module == 'buzzwords' and $link['module'] == 'material')
                 or ($this->_module == 'version_material' and $link['module'] == 'material')
                 or ($this->_module == 'version_archive' and $link['module'] == 'search')
                 or ($this->_module == 'clipboard' and $link['module'] == 'material')
                 or ($this->_module == 'auth' and $link['module'] == 'contact')
                 or ($this->_module == 'user' and $link['module'] == 'contact')
                 or ($this->_module == 'rubric' and $this->_function =='mail' and $link['module'] == $h_module )
               ) {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist_current"');
               } elseif ($this->_module =='annotation'){
                  $history = $session->getValue('history');
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
               } elseif ($this->_module == 'context' and $this->_function =='info_text_edit' ){
                  $history = $session->getValue('history');
                  if ( isset($history) and isset($history[0]) and !empty($history[0]) ){
                     $h_module = $history[0]['module'];
                  } else {
                     $h_module ='';
                  }
                  if ($link['module']==$h_module) {
                     $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist_current"');
                  } else {
                     $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist"');
                  }
               } else {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist"');
               }
               $context = $this->_environment->getCurrentContextItem();
               if (($link['module']!='todo' or $context->withRubric(CS_TODO_TYPE)) ){
                  $html .= $ahref;
               }
            } else {
              $html .= '     <span >'.$link_title.'</span>'.LF;
            }
         }
      // admin area (this should be in the commsy.php)
      if ( !empty($this->_current_user)) {
         if ( $this->_current_user->isModerator() or $context->isPrivateRoom()) {
            if ( $this->_module == 'configuration'
                 or $this->_module == 'account'
                 or $this->_module == 'material_admin'
                 or $this->_module == 'language'
               ) {
               $ahref = ahref_curl($this->_environment->getCurrentContextID(), 'configuration','index','',$this->_translator->getMessage('ADMIN_INDEX'),'','','','','','','class="navlist_current"');
            } else {
               $ahref = ahref_curl($this->_environment->getCurrentContextID(), 'configuration','index','',$this->_translator->getMessage('ADMIN_INDEX'),'','','','','','','class="navlist"');
            }
            #$html .= $ahref;
         }
      }
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '<!-- END TABS -->'."\n";

      return $html;
   }


   function _getBlankLinkRowAsHTML () {
      $html  = LF.'<!-- BEGIN TABS -->'."\n";
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status =='disapear'){
         $width = 'width:940px;';
      }else{
         $width = 'width:754px;';
      }
      $html .= '<div class="tabs_frame" style="'.$width.'">'.LF;
      $html .= '<div class="tabs">'.LF;
      $html .= '<div style="margin:0px; padding:0px;">'."\n";
      $html .= '<span class="navlist">&nbsp;</span>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '<!-- END TABS -->'."\n";
      return $html;
   }



/*    function _getAllOpenContexts () {
          $retour = array();
          $portal_item = $this->_environment->getCurrentPortalItem();
          $temp_array = array();
          $temp_array['item_id'] = -1;
          $temp_array['title'] = $this->_translator->getMessage('COMMUNITY_INDEX').'';
          $retour[] = $temp_array;
          unset($temp_array);
          if (isset($portal_item)){
             $community_list = $portal_item->getCommunityList();
             if ( $community_list->isNotEmpty() ) {
                $community_item = $community_list->getFirst();
                while ($community_item) {
                   $temp_array = array();
                   $temp_array['item_id'] = $community_item->getItemID();
                   $title = $community_item->getShortTitle();
                   if(empty($title)){
                       $title = $community_item->getTitle();
                   }
                   $temp_array['title'] = $title;
                   if ($community_item->getItemID() == $this->_environment->getCurrentContextID()) {
                      $temp_array['selected'] = true;
                   }
                   $retour[] = $temp_array;
                   unset($temp_array);
                   $community_item = $community_list->getNext();
                }
             }
         }
       return $retour;
    }  */






   function asHTML () {
      $html = '';
     $session = $this->_environment->getSession();
      if (!empty($session)) {
         $session_id = $session->getSessionID();
      } else {
         $session_id = '';
      }
      // Header
      $html .= $this->_getHTMLHeadAsHTML();
     // Body
     if ( !$this->_blank_page ) {
         $html .= '<body';
         if ($this->_focus_onload) {
            $html .= ' onload="window.focus();setfocus();';
            $html .= ' "';
         }
         $views = array_merge($this->_views, $this->_views_left, $this->_views_right);
         $view = reset($views);

         while ($view) {
            $html .= $view->getInfoForBodyAsHTML();
            $view = next($views);
         }
         $html .= '>'.LF;
         $html .= LF.'<table style="border-collapse:collapse; padding:0px; margin:0px;" summary="Layout">'.LF;

         // Page Header
         $session = $this->_environment->getSession();
         $left_menue_status = $session->getValue('left_menue_status');
         $html .= '<tr>'.LF;
         $html .= '<td style="margin-bottom:0px; padding:0px; vertical-align:top;">'.LF;
         $html .= '</td>'.LF;
         $width = 'width:600px;';
         $html .= '<td colspan="2" style="'.$width.' padding-left:5px; padding-top:0px; margin:0px; vertical-align: top; ">'.LF;
         // Content
         $session = $this->_environment->getSession();
         $left_menue_status = $session->getValue('left_menue_status');
         if ($left_menue_status =='disapear'){
            $width = 'width:800px;';
         }else{
            $width = 'width:650px;';
         }
         $html .= '<div style="'.$width.' padding:0px 3px; margin:0px;">'.LF;
         $html .= '<div class="content">'.LF;

         // Full Screen Views
         $first = true;
         $html .= '<div class="content_fader">';
         $html .= LF.'<div class="main">'.LF;

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
         if ($this->_environment->getCurrentModule()!='home'){
            $html .='</div>';
         }
         // Leftviews
         if ( !empty($this->_views_right) ) {
            $html .= '<table style="width:100%; padding-top;0px;" summary="Layout">'.LF;
            $html .= '<tr>'.LF;
            $html .= '<td class="leftviews">'.LF;
            $html .= '<div class="infoborder" style="margin-top:1px; padding-bottom:10px;"></div>'.LF;
            foreach ($this->_views_left as $view) {
               $html .= $view->asHTML();
               $last_view = $view;
            }
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
            $html .= '</table>'.LF;
         }
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;

         $html .= '</div>'."\n";

         $html .= '</td></tr></table>';
         $html .= '</body>'."\n";
         $html .= '</html>'."\n";
      }
      return $html;
   }

}
?>