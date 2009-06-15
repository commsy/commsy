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
class cs_page_guide_view extends cs_page_view {

   /**
    * string - containing the parameter of the page
    */
   var $_current_parameter = '';

   var $_form_tags =false;

   var $_form_action= '';


   var $_with_room_list = true;

   /**
    * array - containing the hyperlinks for the page
    */
   var $_links = array();

   var $_space_between_views=true;

   var $_blank_page = false;

   var $_blank_page_content ='';

   var $_room_list_view = NULL;

   var $_room_detail_view = NULL;

   var $_configuration_list_view = NULL;

   var $_configuration_preferences_view = NULL;

   var $_mail_to_moderator_view = NULL;

   var $_form_view = NULL;

   var $_show_agbs = false;

   var $_warning = NULL;

   var $_agb_view = NULL;

   var $_with_delete_box = false;

   var $_delete_box_action_url = '';

   var $_delete_box_mode = 'detail';

   var $_delete_box_ids = NULL;
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

   var $_with_agb_link = true;

   var $_with_announcements = false;

   var $_style_image_path = 'images/layout/';


   private $_navigation_bar = NULL;

   public  $_login_redirect = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_page_guide_view ($params) {
      $this->cs_page_view($params);
      if (file_exists('htdocs/'.$this->_environment->getCurrentPortalID().'/commsy.css') ){
         $this->_style_image_path = $this->_environment->getCurrentPortalID().'/images/';
      }
   }

   public function setLoginRedirect () {
      $this->_login_redirect = true;
   }

   public function setNavigationBar ($value) {
      $this->_navigation_bar = $value;
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

   function setShowAGBs () {
      $this->_show_agbs = true;
   }

   function withAnnouncements(){
      $boolean = true;
      if ($this->_with_announcements == false){
         $boolean = false;
      }
      return $boolean;
   }

   /** adds a view on the left
    * this method adds a view to the page on the left hand side
    *
    * @param object cs_view a commsy view
    */
   function addRoomList ($view) {
      $this->_room_list_view = $view;
   }

   function addForm ($view) {
      $this->_form_view = $view;
   }

   function addAGBView ($view) {
      $this->_agb_view = $view;
   }

   function addWarning ($view) {
      $this->_warning = $view;
   }

   function addRoomDetail ($view) {
      $this->_room_detail_view = $view;
   }

   function addConfigurationListView ($view) {
      $this->_configuration_list_view = $view;
   }

   function addConfigurationPreferencesView ($view) {
      $this->_configuration_preferences_view = $view;
   }

   function addMailToModeratorFormView($view) {
      $this->_mail_to_moderator_view = $view;
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

   /** add an action to the page
    * this method adds an action (hyperlink) to the page view
    *
    * @param string  title        title of the action
    * @param string  explanantion explanation of the action
    * @param string  module       module of the action
    * @param string  function     function in module of the action
    * @param string  parameter    get parameter of the action
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

      $html = LF.'<!-- FADE LEFT MENUE -->'.LF;
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ( $this->_without_left_menue or (isset($_GET['mode']) and $_GET['mode']=='print') ) {
   // do nothing
      } elseif ( $left_menue_status == 'disapear' ) {
         $html .=       '<div style="vertical-align:bottom;">';
         $params = $this->_environment->getCurrentParameterArray();
         $params['left_menue'] = 'apear';
         $html .= '<div style=" margin:0px; padding-left:5px;">'.LF;
         $html .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,'<span class="required">'.'> '.'</span>'.'<span style="font-size:8pt; color:black;">'.getMessage('COMMON_FADE_IN').'</span>', '', '', '', '');
         $html .= '</div>'.LF;
         unset($params);
         $html .='</div>'.LF;
      } else {
         #$params = $this->_environment->getCurrentParameterArray();
         #$params['left_menue'] = 'disapear';
         #$html .=       '<div style="width:58.3em; vertical-align:bottom; padding-top:0px;">';
         #$html .= '<div style=" margin:0px; padding-top:0px; padding-left:5px;">'.LF;
         #$html .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,'<span class="required">'.'< '.'</span>'.'<span style="font-size:8pt; color:black;">'.getMessage('COMMON_FADE_OUT').'</span>', '', '', '', '');
         #unset($params);
         #$html .= '</div>'.LF;
         #$html .='</div>'.LF;
      }

      $html .= LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div class="portal_tabs_frame">'.LF;
      $html .= '<div class="portal-tabs">'.LF;
      $html .= '<div style="float:right; margin:0px; padding:0px;">'.LF;

      // language options
      $language_array = $this->_environment->getAvailableLanguageArray();
      foreach ($language_array as $lang) {
         $params = array();
         $params['language'] = $lang;
         if ( $lang == 'en' ) {
            $flag_lang = 'gb';
         } elseif ( $lang == 'ru' ) {
            $flag_lang = 'ro';
         } else {
            $flag_lang = $lang;
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'language','change',$params,'<img src="images/flags/'.$flag_lang.'.gif" style="float: left; padding-top: 3px; padding-right: 2px;" alt="'.$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG').'"/>',$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG')).LF;
         unset($params);
      }

      // Always show context sensitive help
      $params = array();
      $params['module'] = $this->_module;
      $params['function'] = $this->_function;
      $html .= ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                          $params,
                          '?', '', 'help', '', '',
                          'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','class="navlist_help"').LF;
      unset($params);
      $html .= '  '."\n";
      $html .= '</div>'."\n";
      $html .= '<div style="margin:0px; padding:0px;">'."\n";
      $html .= '<span class="navlist">&nbsp;</span>'."\n";
      $html .= '</div>'."\n";
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_portal_oben_links.gif" alt="" border="0"/></div>';
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_portal_oben_rechts.gif" alt="" border="0"/></div>';
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      return $html;
   }

   function _getBlankLinkRowAsHTML () {
      $html  = LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div class="tabs_frame">'.LF;
      $html .= '<div class="tabs">'.LF;
      $html .= '<div style="float:right; margin:0px; padding:0px;">'.LF;

      // Always show context sensitive help
      $params = array();
      $params['module'] = $this->_environment->getCurrentModule();
      $params['function'] = $this->_environment->getCurrentFunction();
      $html .= ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                             $params,
                              '?', '', '', '', '',
                             'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','class="navlist_help"').LF;
      unset($params);
      $html .= '  '.LF;
      $html .= '</div>'.LF;
      $html .= '<div style="margin:0px; padding:0px;">'.LF;
      $html .= '<span class="navlist">&nbsp;</span>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div style="position:absolute; top:-8px; left:-8px;"><img src="'.$this->_style_image_path.'ecke_portal_oben_links.gif" alt="" border="0"/></div>';
      $html .= '<div style="position:absolute; top:-8px; right:-8px;"><img src="'.$this->_style_image_path.'ecke_portal_oben_rechts.gif" alt="" border="0"/></div>';
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<!-- END TABS -->'.LF;
  }

   function _getWelcomeTextAsHTML () {
      $html ='';
      $html .= LF.'<!-- BEGIN TABS -->'."\n";
      $width = 'width:100%;';
      $html .= '<div class="welcome_frame" style="width: 100%; height:268px; margin-bottom:5px;">'.LF;
      $html .= '<div class="content_without_fader" style="height:268px;">';
      $html .= '<div style="margin:0px; padding:0px">'."\n";

      $html .= '<table style="width:100%; margin:0px; padding:0px; border-collapse:collapse;" summary="Layout">'."\n";
      $html .= '<tr>'."\n";
      $html .= '<td style="width:35%; vertical-align:top; margin:0px; padding-top:0px; padding-left:0px; padding-bottom:5px;">'."\n";
      $current_portal = $this->_environment->getCurrentPortalItem();
      $logo_filename = $current_portal->getPictureFilename();
      $disc_manager = $this->_environment->getDiscManager();
      $disc_manager->setContextID($current_portal->getItemID());
      if ( !empty($logo_filename) and $disc_manager->existsFile($logo_filename) ) {
         $params = array();
         $params['picture'] = $current_portal->getPictureFilename();
         $curl = curl($current_portal->getItemID(), 'picture', 'getfile', $params,'');
         unset($params);
         if ($current_portal->isShowAnnouncementsOnHome()){
            $html .= '<img class="logo" style="width:200px;" src="'.$curl.'" alt="'.$this->_translator->getMessage('LOGO').'" border="0"/>';
         }else{
            $html .= '<img class="logo" style="width:300px; height:268px;" src="'.$curl.'" alt="'.$this->_translator->getMessage('LOGO').'" border="0"/>';
         }
      }
      $disc_manager->setContextID($this->_environment->getCurrentContextID());

      $html .= '</td>'."\n";
      if ($current_portal->isShowAnnouncementsOnHome()){
         $html .= '<td style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 5px; font-weight: normal;">'."\n";
      }else{
         $html .= '<td style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 15px; font-weight: normal;">'."\n";
      }
      $text = $current_portal->getDescriptionWellcome1();
      if ( !empty($text) ) {
         $html .= '<div style="width:99%; text-align:left; padding-top:10px; padding-bottom:5px;"><h1 class="portal_title">'.$this->_text_as_html_short($current_portal->getDescriptionWellcome1()).'</h1></div>'.LF;
      }
      $text = $current_portal->getDescriptionWellcome2();
      if ( !empty($text) ) {
         $html .= '<div style="width:99%; text-align:right; padding-bottom:10px;"><h1 class="portal_main_title">'.$this->_text_as_html_short($current_portal->getDescriptionWellcome2()).'</h1></div>'.LF;
      }
      if ($current_portal->isShowAnnouncementsOnHome()){
         $html .= '</td>'."\n";
         $html .= '</tr>'."\n";
         $html .= '<tr>'."\n";
         $html .= '<td colspan="2" style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 5px; font-weight: normal;">'."\n";
      }
      $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($current_portal->getDescription()));
      if ($current_portal->isShowAnnouncementsOnHome()){
         $html .= '</td>'."\n";
      }
      $html .= '</tr>'."\n";
      $html .= '</table>'."\n";

      $html .= '</div>'."\n";
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>';
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>';
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'.LF;
      return $html;
   }

   function _getModeratorMailTextAsHTML(){
      $html ='';
      $html .= LF.'<!-- BEGIN TABS -->'."\n";
      $html .= '<div class="welcome_frame" style="width: 100%;">'.LF;
      $html .= '<div class="content_without_fader">';
      $html .= '<div style="margin:0px; padding:0px">'."\n";

      $html .= '<div style="font-weight:normal; padding:5px;">'."\n";
      if ( isset($this->_mail_to_moderator_view) ){
         $html .= $this->_mail_to_moderator_view->asHTML();
      }
      $html .= '</div>'."\n";

      $html .= '</div>'."\n";
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>';
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>';
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'.LF;
      return $html;
   }

   function _getServerWelcomeTextAsHTML(){
      $html ='';
      $html .= LF.'<!-- BEGIN TABS -->'."\n";
      $html .= '<div class="welcome_frame" style="width: 100%;">'.LF;
      $html .= '<div class="content_without_fader">';
      $html .= '<div style="margin:0px; padding:0px 0px;">'."\n";

      $html .= '<table style="width:100%; margin:0px; padding:0px; border-collapse:collapse;" summary="Layout">'."\n";
      $html .= '<tr>'."\n";
      $current_portal = $this->_environment->getServerItem();
      $html .= '<td style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 5px; font-weight: normal;">'."\n";
      $html .= '</td>'."\n";
      $html .= '</tr>'."\n";
      $html .= '<tr>'."\n";
      $html .= '<td  style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 5px; font-weight: normal;">'."\n";
      $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($current_portal->getDescription()));
      $current_user = $this->_environment->getCurrentUser();
      if ( $current_user->isRoot() ){
         $html .= '<div class="search_link" style="padding-left:0px; padding-top: 5px;">'.LF;
         $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','index','',$this->_translator->getMessage('SERVER_CONFIGURATION_ACTION')).BRLF;
         $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','preferences',array('iid' => 'NEW'),$this->_translator->getMessage('PORTAL_ENTER_NEW')).BRLF;
         $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),'context','logout','',$this->_translator->getMessage('LOGOUT')).BRLF;
         $html .= '</div>'.LF;
      }
      $html .= '</td>'."\n";
      $html .= '</tr>'."\n";
      $html .= '</table>'."\n";



      $html .= '</div>'."\n";
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>';
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>';
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'.LF;
      return $html;
   }

   function _getPortalAnnouncements(){
      $html ='';
      $html .= LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div class="welcome_frame" style="width: 100%; height:268px; margin-bottom:5px;">'.LF;
      $html .= '<div class="content_fader" style="height:268px;">';
      $html .= '<div style="margin:0px; padding:0px 0px;">'."\n";

      $params = array();
      $params['environment'] = $this->_environment;
      $announcement_view = $this->_class_factory->getClass(ANNOUNCEMENT_SHORT_COMMUNITY_GUIDE_VIEW,$params);
      unset($params);
      $community_manager = $this->_environment->getCommunityManager();
      $community_manager->setOpenedLimit();
      $community_manager->setOrder('activity_rev');
      $community_manager->select();
      $community_list = $community_manager->get();
      if (!$community_list->isEmpty()) {
         $announcement_view->setList($community_list);
         $html .= $announcement_view->asHTML();
      } else {
         $html .= $announcement_view->asHTML();
     }
      $html .= '</div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>'.LF;
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

   function _getSearchBoxAsHTML(){
      $html ='';
      $html .= LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div class="welcome_frame" style="width:100%;">'.LF;
      $html .= '<div class="content_fader">'.LF;
      $html .= '<div style="margin:0px; padding-bottom:10px; width:100%;">'.LF;

      if ( isset($this->_room_list_view) ){
         $html .= $this->_room_list_view->getSearchBoxasHTML();
      }

      $html .= '</div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>'.LF;
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

   function _getSystemInfoAsHTML(){
      $html ='';
      $html .='<div style="font-size:8pt; padding-left:10px; padding-top:0px; margin-top:3px;">'.LF;
      $html .= '<div class="footer" style="text-align:left; padding-left:0px; padding-right:0px; padding-top:0px; padding-bottom:10px;">'.LF;
      $html .= '<a href="http://tidy.sourceforge.net/" target="_top" title="HTML Tidy">'.'<img src="images/checked_by_tidy.gif" style="height:14px; vertical-align: bottom;" alt="Tidy"/></a>';
      $html .= '&nbsp;&nbsp;<a href="http://www.commsy.net" target="_top" title="'.$this->_translator->getMessage('COMMON_COMMSY_LINK_TITLE').'">CommSy '.getCommSyVersion().'</a>';
      $html .= '</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }

   function _getContentListAsHTML(){
      $html ='';
      $html .= LF.'<!-- BEGIN TABS -->'."\n";
      $html .= '<div class="welcome_frame" style="width:100%;">'.LF;
      $html .= '<div class="content_fader">';
      $html .= '<div style="margin:0px; padding-top:0px; padding-bottom: 10px;">'."\n";
      if ( isset($this->_room_list_view) ){
         $html .= $this->_room_list_view->asHTML();
      }

      $html .= '</div>'."\n";
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>';
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>';
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'.LF;
      return $html;
   }

   function _getAGBTextAsHTML(){
      $html ='';
      $html .= '<div style="width: 43em; padding-left:10px; font-weight:normal;">'.LF;
      $html .= LF.'<table style="border-collapse:collapse; padding:0px;  margin-top:5px; width:100%;" summary="Layout">'.LF;
      $html .='<tr>'.LF;
      $html .= '<td style="width:100%;">'.LF;
      $html .= $this->_getLogoAsHTML().LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td>'.LF;
      $html .= $this->_getAGBViewAsHTML().LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      $html .= '</table>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div style="padding-left:0px;">'.LF;
      $html .= $this->_getSystemInfoAsHTML();
      $html .= '</div>'.LF;
      return $html;
   }

   function _getAGBViewAsHTML () {
      $html  = LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div class="tabs_frame" >'.LF;
      $html .= '<div class="tabs">'.LF;
      $html .= '<div style="float:right; margin:0px; padding:0px;">'.LF;

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

      if ( isset($this->_agb_view) and $this->_agb_view instanceof cs_form_view_plain ) {
         $title = $this->_agb_view->getTitle();
      }
      if ( empty($title) ) {
         $title = $this->_translator->getMessage('AGB_CONFIRMATION');
      }
      if ( !empty($this->_navigation_bar) ) {
         $title = $this->_navigation_bar;
      }

      $html .= '<div style="margin:0px; padding:0px;">'.LF;
      $html .= '<span class="navlist">&nbsp;'.$title.'</span>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>';
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>';
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<!-- END TABS -->'.LF;
      $html .= '<div style="border-left: 2px solid #C3C3C3; border-right: 2px solid #C3C3C3; padding:0px 0px; margin:0px;">'.LF;
      $html .= '<div class="content">'.LF;
      $html .= '<div class="content_fader">';
      $html .= '<a name="top"></a>'.LF;
      if ( isset($this->_agb_view) ) {
         $html .= $this->_agb_view->asHTML();
      }
      $html .='</div>';
      $html .= '<div class="top_of_page">'.LF;
      $html .= '<div>'.LF;
      $html .= '<a href="#top">'.'<img src="images/browse_left2.gif" alt="&lt;" border="0"/></a>&nbsp;<a href="#top">'.getMessage('COMMON_TOP_OF_PAGE').'</a>';
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>';
      $html .= '<div style="position:absolute; top:-11px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>';
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

   /** get room window as html
    *
    * param cs_project_item project room item
    */
   function _getRoomAccessAsHTML ($item, $mode = 'none') {
      $current_user = $this->_environment->getCurrentUserItem();
      $may_enter = $item->mayEnter($current_user);
      $html ='';
      //Projektraum User
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setUserIDLimit($current_user->getUserID());
      $user_manager->setAuthSourceLimit($current_user->getAuthSource());
      $user_manager->setContextLimit($item->getItemID());
      $user_manager->select();
      $user_list = $user_manager->get();
      if (!empty($user_list)) {
         $room_user = $user_list->getFirst();
      } else {
         $room_user = '';
      }
      $current_user = $this->_environment->getCurrentUserItem();

      //Anzeige außerhalb des Anmeldeprozesses
      if ($mode !='member' and $mode !='info' and $mode !='email'){
         $current_user = $this->_environment->getCurrentUserItem();
         $may_enter = $item->mayEnter($current_user);
         // Eintritt erlaubt
         if ( $may_enter and ( ( !empty($room_user) and $room_user->isUser() ) or $current_user->isRoot() ) ) {
            $actionCurl = curl( $item->getItemID(),
                             'home',
                             'index',
                             '');
            $html .= '<a class="room_window" href="'.$actionCurl.'"><img src="images/door_open_large.gif" alt="door open" /></a>'.BRLF;
            $actionCurl = curl( $item->getItemID(),
                             'home',
                             'index',
                             '');
         $html .= '<div style="padding-top:8px;">&nbsp;</div>'.BRLF;
         //als Gast Zutritt erlaubt, aber kein Mitglied
         } elseif ( $item->isLocked() ) {
            $html .= '<img src="images/door_closed_large.gif" alt="door closed" />'.LF;
         } elseif ( $item->isOpenForGuests()
                    and empty($room_user)
                  ) {
            $actionCurl = curl( $item->getItemID(),
                             'home',
                             'index',
                             '');
            $html .= '<a class="room_window" href="'.$actionCurl.'"><img src="images/door_open_large.gif" alt="door open" /></a>'.BRLF;
            $actionCurl = curl( $item->getItemID(),
                             'home',
                             'index',
                             '');
            $html .= '<div style="padding-top:5px;">'.'> <a href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER_AS_GUEST').'</a></div>'.LF;
            if ( $item->isOpen()
                 and !$this->_current_user->isOnlyReadUser()
               ) {
               $params = array();
               $params = $this->_environment->getCurrentParameterArray();
               $params['account'] = 'member';
               $params['room_id'] = $item->getItemID();
               $actionCurl = curl( $this->_environment->getCurrentContextID(),
                                  'home',
                                  'index',
                                  $params,
                                  '');
               $html .= '<div style="padding-top:3px;">'.'> <a href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_JOIN').'</a></div>'.LF;
              unset($params);
           } else {
              $html .= '<div style="padding-top:3px;">> <span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
           }

         //Um Erlaubnis gefragt
         } elseif ( !empty($room_user) and $room_user->isRequested() ) {
            if ( $item->isOpenForGuests() ) {
               $actionCurl = curl( $item->getItemID(),
                                   'home',
                                   'index',
                                   '');
               $html .= '<a class="room_window" href="'.$actionCurl.'"><img src="images/door_open_large.gif" alt="door open" /></a>'.BRLF;
               $actionCurl = curl( $item->getItemID(),
                                   'home',
                                   'index',
                                   '');
               $html .= '<div style="padding-top:7px; text-align: center;">'.'> <a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER_AS_GUEST').'</a></div>'.LF;
            } else {
               $html .= '<img src="images/door_closed_large.gif" alt="door closed"/>'.LF;
            }
            $html .= '<div style="padding-top:7px;"><p style="margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED_YET').'</p></div>'.LF;
         //Erlaubnis verweigert
         } elseif ( !empty($room_user) and $room_user->isRejected() ) {
            if ( $item->isOpenForGuests() ) {
               $actionCurl = curl( $item->getItemID(),
                                   'home',
                                   'index',
                                   '');
               $html .= '<a class="room_window" href="'.$actionCurl.'"><img src="images/door_open_large.gif" alt="door open"/></a>'.BRLF;
               $actionCurl = curl( $item->getItemID(),
                                   'home',
                                   'index',
                                   '');
                $html .= '<div style="padding-top:7px;">'.'> <a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER_AS_GUEST').'</a></div>'.LF;
         } else {
               $html .= '<img src="images/door_closed_large.gif" alt="door closed"/>'.LF;
         }
            $html .= '<div style="padding-top:7px;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED').'</p></div>'.LF;

         // noch nicht angemeldet als Mitglied im Raum
         } else {
            $html .= '<img src="images/door_closed_large.gif" alt="door closed" style="vertical-align: middle; "/>'.BRLF;
            if ( $item->isOpen()
                 and !$this->_current_user->isOnlyReadUser()
               ) {
               $params = array();
               $params = $this->_environment->getCurrentParameterArray();
               $params['account'] = 'member';
               $params['room_id'] = $item->getItemID();
               $actionCurl = curl( $this->_environment->getCurrentContextID(),
                                  'home',
                                  'index',
                                  $params,
                                  '');
               $session_item = $this->_environment->getSessionItem();
               if ($session_item->issetValue('login_redirect')) {
                  $html .= '<div style="padding-top:7px;"><p style="margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">';
                  if ( !$item->isPrivateRoom() and !$item->isGroupRoom() ) {
                     $html .= $this->_translator->getMessage('CONTEXT_ENTER_LOGIN','<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_JOIN').'</a>');
                  } else {
                     $current_user_item = $this->_environment->getCurrentUserItem();
                     $current_user_item = $current_user_item->getRelatedCommSyUserItem();
                     if ( isset($current_user_item) and $current_user_item->isUser() ) {
                        $html .= $this->_translator->getMessage('CONTEXT_ENTER_LOGIN_NOT_ALLOWED');
                     } else {
                        $html .= $this->_translator->getMessage('CONTEXT_ENTER_LOGIN2');
                     }
                     unset($current_user_item);
                  }
                  $html .= '</p></div>'.LF;
                  $session_item->unsetValue('login_redirect');
                  unset($session_item);
               } elseif ( !$item->isPrivateRoom() and !$item->isGroupRoom() ) {
                  $html .= '<div style="padding-top:5px;">'.'> <a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_JOIN').'</a></div>'.LF;
               }
               unset($params);
            } elseif ( !$item->isPrivateRoom() and !$item->isGroupRoom() ) {
               $html .= '<div style="padding-top:5px;">> <span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
            }
            $html .= '<div style="padding-top:6px;">&nbsp;</div>'.LF;
         }
      }
      return $html;
   }

   function _getRoomFacts($item){
      $html ='';
      // prepare moderator
      $html_temp='';
      $moda = array();
      $moda_list = $item->getContactModeratorList();
      $current_user = $this->_environment->getCurrentUser();
      $moda_item = $moda_list->getFirst();
      while ($moda_item) {
         $html_temp .= '<li style="font-weight:normal; font-size:8pt;">'.$this->_text_as_html_short($moda_item->getFullName()).'</li>';
         $moda_item = $moda_list->getNext();
      }
      $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('ROOM_CONTACT').':</span>'.LF;
      $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">'.LF;
      if (!empty($html_temp) ) {
         $temp_array = array();
         $html .= $html_temp;
         $params = $this->_environment->getCurrentParameterArray();
         $params['account'] = 'email';
         $params['room_id'] = $item->getItemID();
         $actionCurl = curl( $this->_environment->getCurrentContextID(),
                             'home',
                             'index',
                             $params,
                             '');
         unset($params);
         if ( $current_user->isUser() ) {
            $html .= '<li style="font-weight:bold; font-size:8pt;">'.'<a href="'.$actionCurl.'">'.$this->_translator->getMessage('EMAIL_CONTACT_MODERATOR').'</a></li>';
         }else{
            $html .= '<li style="font-weight:bold; font-size:8pt;">'.'<span class="disabled">'.$this->_translator->getMessage('EMAIL_CONTACT_MODERATOR').'</span></li>';
         }
      }else{
         $html .= '<li style="font-weight:bold; font-size:8pt;">'.'<span class="disabled">'.$this->_translator->getMessage('COMMON_NO_CONTACTS').'</span></li>';
      }
      $html .= '</ul>'.LF;
      // prepare time (clock pulses)
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->showTime() and ( $item->isProjectRoom() or $item->isCommunityRoom() ) ) {
         $time_list = $item->getTimeList();
         if ($time_list->isNotEmpty()) {
            $this->translatorChangeToPortal();
            $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_TIME_NAME').':</span>'.LF;
            $this->translatorChangeToCurrentContext();
            if ($item->isContinuous()) {
               $time_item = $time_list->getFirst();
               if ($item->isClosed()) {
                  $time_item_last = $time_list->getLast();
                  if ($time_item_last->getItemID() == $time_item->getItemID()) {
                     $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">'.LF;
          $html .= '   <li style="font-weight:normal; font-size:8pt;">'.LF;
          $html .= $this->_translator->getTimeMessage($time_item->getTitle()).LF;
          $html .= '   </li>'.LF;
          $html .= '</ul>'.LF;
       } else {
          $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">'.LF;
          $html .= '   <li style="font-weight:normal; font-size:8pt;">'.LF;
          $html .= $this->_translator->getMessage('COMMON_FROM2').' '.$this->_translator->getTimeMessage($time_item->getTitle()).LF;
          $html .= '   </li>'.LF;
          $html .= '   <li style="font-weight:normal; font-size:8pt;">'.LF;
          $html .= $this->_translator->getMessage('COMMON_TO').' '.$this->_translator->getTimeMessage($time_item_last->getTitle()).LF;
          $html .= '   </li>'.LF;
          $html .= '</ul>'.LF;
       }
               } else {
                  $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">'.LF;
       $html .= '   <li style="font-weight:normal; font-size:8pt;">'.LF;
       $html .= $this->_translator->getMessage('ROOM_CONTINUOUS_SINCE').' '.BRLF.$this->_translator->getTimeMessage($time_item->getTitle()).LF;
       $html .= '   </li>'.LF;
       $html .= '</ul>'.LF;
               }
            } else {
               $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">'.LF;
               $time_item = $time_list->getFirst();
               while ($time_item) {
                  $html .= '<li style="font-weight:normal; font-size:8pt;">'.$this->_translator->getTimeMessage($time_item->getTitle()).'</li>'.LF;
       $time_item = $time_list->getNext();
               }
               $html .= '</ul>'.LF;
            }
         } else {
           $this->translatorChangeToPortal();
           $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_TIME_NAME').':</span>'.LF;
           $this->translatorChangeToCurrentContext();
           $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">'.LF;
           $html .= '   <li style="font-weight:normal; font-size:8pt;"><span class="disabled">'.LF;
           $html .= $this->_translator->getMessage('ROOM_NOT_LINKED').LF;
           $html .= '   </span></li>'.LF;
           $html .= '</ul>'.LF;
         }
      }

      // community list
      if ($item->isProjectRoom()) {
         $community_list = $item->getCommunityList();
         $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('COMMUNITYS').':</span>'.LF;
         $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">'.LF;
         if ($community_list->isNotEmpty()) {
            $community_item = $community_list->getFirst();
            while ($community_item) {
               $html .= '<li style="font-weight:normal; font-size:8pt;">'.LF;
               $params = $this->_environment->getCurrentParameterArray();
               $params['room_id'] = $community_item->getItemID();
               $link = ahref_curl($this->_environment->getCurrentContextID(),'home','index',$params,$community_item->getTitle());
               $html .= $link.LF;
               $html .= '</li>'.LF;
               $community_item = $community_list->getNext();
            }
            $html .= '</ul>'.LF;
         } else {
            $html .= '<li style="font-weight:normal; font-size:8pt;" ><span class="disabled">'.LF;
            $html .= $this->_translator->getMessage('ROOM_NOT_LINKED').LF;
            $html .= '</span></li>'.LF;
            $html .= '</ul>'.LF;
               }
      }


      // add-ons
      if ( $item->showHomepageDescLink() or
            ( $item->showWikiLink()
              and $item->existWiki()
              and $item->issetWikiPortalLink()
            )
         ) {
         $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_PORTAL_LINKS').':</span>'.LF;
         $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">'.LF;

         if (
               ( $item->showWikiLink()
                 and $item->existWiki()
                 and $item->issetWikiPortalLink()
               )
            ) {
            $html .= '<li style="font-weight:normal; font-size:8pt;">'.LF;
            global $c_pmwiki_path_url;
            $url_session_id = '';
            if ( $item->withWikiUseCommSyLogin() ) {
               $session_item = $this->_environment->getSessionItem();
               $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
               unset($session_item);
            }
            $html .= '<span style="white-space:nowrap;"> <a href="'.$c_pmwiki_path_url.'/wikis/'.$item->getContextID().'/'.$item->getItemID().'/'.$url_session_id.'" target="_blank">'.$item->getWikiTitle().'</a> ('.$this->_translator->getMessage('COMMON_WIKI_LINK').')</span>';
            $html .= '</li>'.LF;
         }

         if ( $item->showHomepageDescLink() ) {
            $html .= '<li style="font-weight:normal; font-size:8pt;">'.LF;
            $link = ahref_curl( $item->getitemID(),
                                'context',
                                'forward',
                                array('tool' => 'homepage'),
                                $this->_translator->getMessage('HOMEPAGE_HOMEPAGE'),'','_blank');
            $html .= '<span style="white-space:nowrap;"> '.$link.'</span>';
            $html .= '</li>'.LF;
         }

         $html .= '</ul>'.LF;
      }
      return $html;
   }

   function _getRoomForm($item, $mode){
     $html ='';
     $current_user = $this->_environment->getCurrentUser();
     // Person ist User und will Mitglied werden
     if ($mode=='member' and $current_user->isUser()) {
        $translator = $this->_environment->getTranslationObject();
        $html .= '<div>'.LF;
        $formal_data = array();
        $get_params = $this->_environment->getCurrentParameterArray();
        if (isset($get_params['sort'])){
           $params['sort'] = $get_params['sort'];
        }elseif (isset($_POST['sort'])){
           $params['sort'] = $get_params['sort'];
        }
        if (isset($get_params['search'])){
           $params['search'] = $get_params['search'];
        }elseif (isset($_POST['search'])){
           $params['search'] = $get_params['search'];
        }
        if (isset($get_params['seltime'])){
           $params['seltime'] = $get_params['seltime'];
        }elseif (isset($_POST['seltime'])){
           $params['seltime'] = $get_params['seltime'];
        }
        if (isset($get_params['selroom'])){
           $params['selroom'] = $get_params['selroom'];
        }elseif (isset($_POST['selroom'])){
           $params['selroom'] = $get_params['selroom'];
        }
        if (isset($get_params['sel_archive_room'])){
           $params['sel_archive_room'] = $get_params['sel_archive_room'];
        }elseif (isset($_POST['sel_archive_room'])){
           $params['sel_archive_room'] = $get_params['sel_archive_room'];
        }
        $params['room_id'] = $item->getItemID();
        $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),'home','index',$params).'" name="member">'.LF;
        if (isset($get_params['sort'])){
           $html .= '   <input type="hidden" name="sort" value="'.$get_params['sort'].'"/>'.LF;
        }elseif (isset($_POST['sort'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['sort'].'"/>'.LF;
        }
        if (isset($get_params['search'])){
           $html .= '   <input type="hidden" name="search" value="'.$get_params['search'].'"/>'.LF;
        }elseif (isset($_POST['search'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['search'].'"/>'.LF;
        }
        if (isset($get_params['seltime'])){
           $html .= '   <input type="hidden" name="seltime" value="'.$get_params['seltime'].'"/>'.LF;
        }elseif (isset($_POST['seltime'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['seltime'].'"/>'.LF;
        }
        if (isset($get_params['selroom'])){
           $html .= '   <input type="hidden" name="selroom" value="'.$get_params['selroom'].'"/>'.LF;
        }elseif (isset($_POST['selroom'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['selroom'].'"/>'.LF;
        }
        if (isset($get_params['sel_archive_room'])){
           $html .= '   <input type="hidden" name="selroom" value="'.$get_params['sel_archive_room'].'"/>'.LF;
        }elseif (isset($_POST['sel_archive_room'])){
           $html .= '   <input type="hidden" name="selroom" value="'.$get_params['sel_archive_room'].'"/>'.LF;
        }

        if ($item->checkNewMembersWithCode()) {
           $html .= $this->_translator->getMessage('ACCOUNT_GET_CODE_TEXT');
           if ( isset($get_params['error']) and !empty($get_params['error']) ) {
              $temp_array[0] = $this->_translator->getMessage('COMMON_ATTENTION').': ';
              $temp_array[1] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE_ERROR');
              $formal_data[] = $temp_array;
           }
           $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE').': ';
           $temp_array[1] = '<input type="text" name="code" tabindex="14" size="30"/>'.LF;
           $formal_data[] = $temp_array;
        } else {
           $html .= $this->_translator->getMessage('ACCOUNT_GET_4_TEXT');
           $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_REASON').': ';
           $value = '';
           if (!empty($get_params['description_user'])) {
              $value = $get_params['description_user'];
              $value = str_replace('%20',' ',$value);
           }
           $temp_array[1] = '<textarea name="description_user" cols="31" rows="10" tabindex="14">'.$value.'</textarea>'.LF;
           $formal_data[] = $temp_array;
        }

        $temp_array = array();
        $temp_array[0] = '&nbsp;';
        $temp_array[1] = '<input type="submit" name="option" tabindex="15" value="'.$this->_translator->getMessage('ACCOUNT_GET_MEMBERSHIP_BUTTON').'"/>'.
                         '&nbsp;&nbsp;'.'<input type="submit" name="option" tabindex="16" value="'.$this->_translator->getMessage('COMMON_BACK_BUTTON').'"/>'.LF;
        $formal_data[] = $temp_array;
        if ( !empty($formal_data) ) {
           $html .= $this->_getFormalDataAsHTML2($formal_data);
           $html .= BRLF;
        }
        unset($params);
        $html .= '</form>'.LF;
        $html .= '</div>'.LF;
     }

     // person is guest und will Mitglied werden
     elseif ($mode=='member' and $current_user->isGuest()) {
        $translator = $this->_environment->getTranslationObject();
        $html .= '<div>'.LF;
        $params = $this->_environment->getCurrentParameterArray();
        $params['cs_modus'] = 'portalmember';
        $link = ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$this->_translator->getMessage('ACCOUNT_GET_GUEST_CHOICE_LINK'));
        $html .= $this->_translator->getMessage('ACCOUNT_GET_GUEST_CHOICE',$link);
        $html .= '</div>'.LF;
     }
     elseif ( $mode=='email') {
        $translator = $this->_environment->getTranslationObject();
        $html .= '<div>'.LF;
        $formal_data = array();

        $get_params = $this->_environment->getCurrentParameterArray();
        if (isset($get_params['sort'])){
           $params['sort'] = $get_params['sort'];
        }elseif (isset($_POST['sort'])){
           $params['sort'] = $get_params['sort'];
        }
        if (isset($get_params['search'])){
           $params['search'] = $get_params['search'];
        }elseif (isset($_POST['search'])){
           $params['search'] = $get_params['search'];
        }
        if (isset($get_params['seltime'])){
           $params['seltime'] = $get_params['seltime'];
        }elseif (isset($_POST['seltime'])){
           $params['seltime'] = $get_params['seltime'];
        }
        if (isset($get_params['selroom'])){
           $params['selroom'] = $get_params['selroom'];
        }elseif (isset($_POST['selroom'])){
           $params['selroom'] = $get_params['selroom'];
        }
        if (isset($get_params['sel_archive_room'])){
           $params['sel_archive_room'] = $get_params['sel_archive_room'];
        }elseif (isset($_POST['sel_archive_room'])){
           $params['sel_archive_room'] = $get_params['sel_archive_room'];
        }
        $params['room_id'] = $item->getItemID();
        $html.= $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT');
        $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),'home','index',$params).'" name="member">'.LF;
        if (isset($get_params['sort'])){
           $html .= '   <input type="hidden" name="sort" value="'.$get_params['sort'].'"/>'.LF;
        }elseif (isset($_POST['sort'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['sort'].'"/>'.LF;
        }
        if (isset($get_params['search'])){
           $html .= '   <input type="hidden" name="search" value="'.$get_params['search'].'"/>'.LF;
        }elseif (isset($_POST['search'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['search'].'"/>'.LF;
        }
        if (isset($get_params['seltime'])){
           $html .= '   <input type="hidden" name="seltime" value="'.$get_params['seltime'].'"/>'.LF;
        }elseif (isset($_POST['seltime'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['seltime'].'"/>'.LF;
        }
        if (isset($get_params['selroom'])){
           $html .= '   <input type="hidden" name="selroom" value="'.$get_params['selroom'].'"/>'.LF;
        }elseif (isset($_POST['selroom'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['selroom'].'"/>'.LF;
        }
        if (isset($get_params['sel_archive_room'])){
           $html .= '   <input type="hidden" name="selroom" value="'.$get_params['sel_archive_room'].'"/>'.LF;
        }elseif (isset($_POST['sel_archive_room'])){
           $html .= '   <input type="hidden" name="selroom" value="'.$get_params['sel_archive_room'].'"/>'.LF;
        }
        $temp_array[0] = $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT_DESC').': ';
        $temp_array[1]= '<textarea name="description_user" cols="31" rows="10" wrap="virtual" tabindex="14" ></textarea>'.LF;
        $formal_data[] = $temp_array;
        $temp_array = array();
        $temp_array[0] = '&nbsp;';
        $temp_array[1]= '<input type="submit" name="option"  value="'.$this->_translator->getMessage('CONTACT_MAIL_SEND_BUTTON').'"/>'.
                      '&nbsp;&nbsp;'.'<input type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_BACK_BUTTON').'"/>'.LF;
        $formal_data[] = $temp_array;
        if ( !empty($formal_data) ) {
           $html .= $this->_getFormalDataAsHTML2($formal_data);
           $html .= BRLF;
        }
        unset($params);
        $html .= '</form>'.LF;
        $html .= '</div>'.LF;
     }
      // Person ist User und hat sich angemeldet; wurde aber nicht automatisch freigschaltet
     elseif ($mode =='info') {
        $translator = $this->_environment->getTranslationObject();
        $html .= '<div>'.LF;
        $formal_data = array();
        $get_params = $this->_environment->getCurrentParameterArray();
        if (isset($get_params['sort'])){
           $params['sort'] = $get_params['sort'];
        }elseif (isset($_POST['sort'])){
           $params['sort'] = $get_params['sort'];
        }
        if (isset($get_params['search'])){
           $params['search'] = $get_params['search'];
        }elseif (isset($_POST['search'])){
           $params['search'] = $get_params['search'];
        }
        if (isset($get_params['seltime'])){
           $params['seltime'] = $get_params['seltime'];
        }elseif (isset($_POST['seltime'])){
           $params['seltime'] = $get_params['seltime'];
        }
        if (isset($get_params['selroom'])){
           $params['selroom'] = $get_params['selroom'];
        }elseif (isset($_POST['selroom'])){
           $params['selroom'] = $get_params['selroom'];
        }
        if (isset($get_params['sel_archive_room'])){
           $params['sel_archive_room'] = $get_params['sel_archive_room'];
        }elseif (isset($_POST['sel_archive_room'])){
           $params['sel_archive_room'] = $get_params['sel_archive_room'];
        }
        $params['room_id'] = $item->getItemID();
        $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),'home','index',$params).'" name="member">'.LF;
        if (isset($get_params['sort'])){
           $html .= '   <input type="hidden" name="sort" value="'.$get_params['sort'].'"/>'.LF;
        }elseif (isset($_POST['sort'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['sort'].'"/>'.LF;
        }
        if (isset($get_params['search'])){
           $html .= '   <input type="hidden" name="search" value="'.$get_params['search'].'"/>'.LF;
        }elseif (isset($_POST['search'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['search'].'"/>'.LF;
        }
        if (isset($get_params['seltime'])){
           $html .= '   <input type="hidden" name="seltime" value="'.$get_params['seltime'].'"/>'.LF;
        }elseif (isset($_POST['seltime'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['seltime'].'"/>'.LF;
        }
        if (isset($get_params['selroom'])){
           $html .= '   <input type="hidden" name="selroom" value="'.$get_params['selroom'].'"/>'.LF;
        }elseif (isset($_POST['selroom'])){
           $html .= '   <input type="hidden" name="sort" value="'.$_POST['selroom'].'"/>'.LF;
        }
        if (isset($get_params['sel_archive_room'])){
           $html .= '   <input type="hidden" name="selroom" value="'.$get_params['sel_archive_room'].'"/>'.LF;
        }elseif (isset($_POST['sel_archive_room'])){
           $html .= '   <input type="hidden" name="selroom" value="'.$get_params['sel_archive_room'].'"/>'.LF;
        }
        $temp_array = array();
        $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_CONFIRMATION').': ';
        $temp_array[1]= $this->_translator->getMessage('ACCOUNT_GET_6_TEXT_2',$item->getTitle());
        $formal_data[] = $temp_array;
        $temp_array = array();
        $temp_array[0] = '&nbsp;';
        $temp_array[1]= '<input type="submit" name="option"  value="'.$this->_translator->getMessage('COMMON_NEXT').'"/>'.LF;
        $formal_data[] = $temp_array;
        if ( !empty($formal_data) ) {
           $html .= $this->_getFormalDataAsHTML2($formal_data);
           $html .= BRLF;
        }
        unset($params);
        $html .= '</form>'.LF;
        $html .= '</div>'.LF;
     }

     return $html;
   }

   function _getFormalDataAsHTML2($data, $spacecount=0, $clear=false) {
      $prefix = str_repeat(' ', $spacecount);
      $html  = $prefix.'<table class="detail" style="width: 100%;" summary="Layout" ';
      if ( $clear ) {
         $html .= 'style="clear:both"';
      }
      $html .= '>'."\n";
      foreach ($data as $value) {
         if ( !empty($value[0]) ) {
            $html .= $prefix.'   <tr>'.LF;
            $html .= $prefix.'      <td style="padding: 10px 2px 10px 0px; color: #666; vertical-align: top; width: 1%;">'.LF;
            $html .= $prefix.'         '.$value[0].'&nbsp;'.LF;
         } else {
            $html .= $prefix.'         &nbsp;';
         }
         $html .= $prefix.'      </td><td style="margin: 0px; padding: 10px 2px 10px 0px;">'.LF;
         if ( !empty($value[1]) ) {
            $html .= $prefix.'         '.$value[1].LF;
         }
         $html .= $prefix.'      </td>'.LF;
         $html .= $prefix.'   </tr>'.LF;
      }
      $html .= $prefix.'</table>'.LF;
      return $html;
   }

   function _getRoomFormAsHTML($item){
      $html ='';
      $html .= LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div class="welcome_frame" style="width:100%;">'.LF;
      $html .= '<div class="content_without_fader" style="padding:0px 0px 5px 0px;">'.LF;
      $html .= '<div style="margin:0px;width:100%; font-weight:normal; font-size:10pt;">'.LF;
      $html .= '<div style="padding-left:5px; padding-right:5px;">'.LF;
      if (isset($this->_warning)) {
         $html .= $this->_warning->asHTML();
      }
      if ( isset($this->_form_view) and !empty($this->_form_view) ) {
         $html .= $this->_form_view->asHTML();
      }
      $html .= '</div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>'.LF;
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

   function _getConfigurationAsHTML () {
      $html ='';
      $html .= LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div class="welcome_frame" style="width:100%;">'.LF;
      $html .= '<div class="content_without_fader">';
      $html .= '<div style="margin:0px;width:100%; font-weight:normal;">'.LF;
      $html .= '<div style="padding-left:5px; padding-right:5px;">'.LF;

      if ( $this->_environment->getCurrentFunction() == 'index'
           and isset($this->_configuration_list_view)
           and !empty($this->_configuration_list_view)
         ) {
         $html .= '<div style="padding-top:15px;">'.LF;
         $html .= $this->_configuration_list_view->asHTML();
         $html .= '</div>'.LF;
      } elseif ( isset($this->_form_view) and !empty($this->_form_view) ) {
         $html .= '<div style="padding-top:15px;">'.LF;
         $html .= $this->_form_view->asHTML();
         $html .= '</div>'.LF;
         if ( $this->_with_delete_box ) {
            $html .= $this->getDeleteBoxAsHTML('portal');
         }
      }

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>'.LF;
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

   function _getLanguageIndexAsHTML () {
      $html ='';
      $html .= LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div class="welcome_frame" style="width:100%;">'.LF;
      $html .= '<div class="content_without_fader">';
      $html .= '<div style="margin:0px;width:100%; font-weight:normal;">'.LF;
      $html .= '<div style="padding-left:5px; padding-right:5px;">'.LF;

      if ( $this->_environment->getCurrentFunction() == 'index'
           and isset($this->_configuration_list_view)
           and !empty($this->_configuration_list_view)
         ) {
         $html .= $this->_configuration_list_view->asHTML();
      } elseif ( isset($this->_form_view) and !empty($this->_form_view) ) {
         $html .= '<div>'.LF;
         $html .= $this->_form_view->asHTML();
         $html .= '</div>'.LF;
      }

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>'.LF;
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }


   function getDeleteBoxAsHTML ($type='room') {
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status != 'disapear' and !$this->_without_left_menue ) {
         $left = '0em';
         $width = '56em';
      }else{
         $left = '0em';
         $width = '73em';
      }
      $html  = '<div style="position: absolute; z-index:100;  top:-3px; left:-3px; width:'.$width.'; height: 300px;">'.LF;
      $html .= '<center>';
      $html .= '<div style="position:fixed; z-index:100; margin-top:0px; margin-left:100px; width:400px; padding:20px; background-color:#FFF; border:2px solid red;">';
      $html .= '<form style="margin-bottom:0px; padding:0px;" method="post" action="'.$this->_delete_box_action_url.'">';
      if ( $type == 'portal' ) {
         $html .= '<h2>'.getMessage('COMMON_DELETE_BOX_TITLE_PORTAL');
      } else {
         $html .= '<h2>'.getMessage('COMMON_DELETE_BOX_TITLE_ROOM');
      }
      $html .= '</h2>';
      if ( $type == 'portal' ) {
         $html .= '<p style="text-align:left; font-weight:normal;">'.getMessage('COMMON_DELETE_BOX_DESCRIPTION_PORTAL');
      } else {
         $html .= '<p style="text-align:left; font-weight:normal;">'.getMessage('COMMON_DELETE_BOX_DESCRIPTION_ROOM');
      }
      $html .= '</p>';
      $html .= '<div style="height:20px;">';
      $html .= '<input style="float:right;" type="submit" name="delete_option" value="'.getMessage('COMMON_DELETE_BUTTON').'" tabindex="2"/>';
      $html .= '<input style="float:left;" type="submit" name="delete_option" value="'.getMessage('COMMON_CANCEL_BUTTON').'" tabindex="2"/>';
      if ( $type != 'portal' ) {
         $html .= '<input style="float:left;" type="submit" name="delete_option" value="'.getMessage('ROOM_ARCHIV_BUTTON').'" tabindex="2"/>';
      }
      $html .= '</div>';
      $html .= '</form>';
      $html .= '</div>';
      $html .= '</center>';
      $html .= '</div>';
      $html .= '<div id="delete" style="position: absolute; z-index:90; top:-3px; left:-3px; width:'.$width.'; height: 300px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
      $html .= '</div>';
      return $html;
   }

   function addDeleteBox($url,$mode='detail',$selected_ids = NULL){
      $this->_with_delete_box = true;
      $this->_delete_box_action_url = $url;
      $this->_delete_box_mode = $mode;
      $this->_delete_box_ids = $selected_ids;
   }

   function _getRoomItemAsHTML($item) {
      $html  = '';
      $html .= LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div class="welcome_frame" style="width:100%; margin-bottom:5px;">'.LF;
      $html .= '<div class="content_without_fader">';
      $html .= '<div style="margin:0px; padding:0px 0px; width:100%;">'."\n";

      $html .= '<table style="border-collapse:collapse; border:0px solid black; margin-left:5px; margin-right:5px;" summary="Layout">'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td colspan="6" style="width:100%; border-bottom:1px solid #B0B0B0;">'.LF;
      $html .= '<div style="float:right; padding-top:10px;">'.LF;
#      $html .= '<td colspan="2" style="width:30%; border-bottom:1px solid #B0B0B0; text-align:right; vertical-align:middle;">'.LF;

      // actions
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUser();
      if ( !$item->isDeleted() and !$item->isPrivateRoom() and !$item->isGroupRoom() ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         if ( ($current_user->isModerator() or $item->mayEdit($current_user)) and $this->_with_modifying_actions) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $html .=  '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','common',$params,$this->_translator->getMessage('PORTAL_EDIT_ROOM'),'','','','','','','class="portal_link"').BRLF;
            unset($params);
            $params = $this->_environment->getCurrentParameterArray();
            $params['iid'] = $item->getItemID();
            $params['room_id'] = $item->getItemID();
            $params['action'] = 'delete';
            $html .= '> '. ahref_curl( $this->_environment->getCurrentContextID(),
                                          $this->_environment->getCurrentModule(),
                                          'index',
                                          $params,
                                          $this->_translator->getMessage('COMMON_DELETE_ROOM'),
                                          '','','','','','','class="portal_link"').LF;
            unset($params);
         } else {
           $html .=  '<span class="disabled">> '.$this->_translator->getMessage('PORTAL_EDIT_ROOM').'</span>'.BRLF;
           $html .=  '<span class="disabled">> '.$this->_translator->getMessage('COMMON_DELETE_ROOM').'</span>'.LF;
         }
         $html .= BRLF;

         if ( $current_user->isModerator()
              and $this->_with_modifying_actions
              and !$item->isLocked()
            ) {
            $params['iid'] = $item->getItemID();
            $params['automatic'] = 'lock';
            $html .=  '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','room',$params,$this->_translator->getMessage('CONTEXT_ROOM_LOCK'),'','','','','','','class="portal_link"').BRLF;
            unset($params);
         } elseif ( $current_user->isModerator()
                    and $this->_with_modifying_actions
                    and $item->isLocked()
                  ) {
            $params = array();
            $params['automatic'] = 'unlock';
            $params['iid'] = $item->getItemID();
            $html .=  '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','room',$params,$this->_translator->getMessage('CONTEXT_ROOM_UNLOCK'),'','','','','','','class="portal_link"').BRLF;
            unset($params);
         }
         if ( $current_user->isModerator()
              and $this->_with_modifying_actions
              and !$item->isClosed()
            ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $params['automatic'] = 'archive';
            $html .=  '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','room',$params,$this->_translator->getMessage('CONTEXT_ROOM_ARCHIVE'),'','','','','','','class="portal_link"').LF;
            unset($params);
         }elseif( $current_user->isModerator()
              and $this->_with_modifying_actions
              and $item->isClosed()
            ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $params['automatic'] = 'open';
            $html .=  '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','room',$params,$this->_translator->getMessage('CONTEXT_ROOM_OPEN'),'','','','','','','class="portal_link"').LF;
            unset($params);
         }
         $server_item = $this->_environment->getServerItem();
         $portal_list = $server_item->getPortalList();
         if ( $portal_list->getCount() > 1 and !$item->isGroupRoom() ) {
            if ( $current_user->isModerator()
                 and $this->_with_modifying_actions
                 and !$item->isLockedForMove() ) {
               $params = array();
               $params['iid'] = $item->getItemID();
               $html .=  BR.'> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','move',$params,$this->_translator->getMessage('PORTAL_MOVE_ROOM'),'','','','','','','class="portal_link"').LF;
               unset($params);
            } elseif ( $current_user->isModerator()
                       and $this->_with_modifying_actions
                       and $item->isLockedForMove() ) {
               $html .= BR.'<span class="disabled">> '.$this->_translator->getMessage('PORTAL_MOVE_ROOM').'</span>'.LF;
            }
         }

         if ( $current_user->isRoot()
              and $this->_with_modifying_actions
            ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $html .=  BR.'> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','export',$params,$this->_translator->getMessage('PORTAL_EXPORT_ROOM'),'','','','','','','class="portal_link"').LF;
            unset($params);
         }
      } elseif ( $current_user->isRoot() ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         $params['automatic'] = 'undelete';
         $html .=  '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','room',$params,$this->_translator->getMessage('CONTEXT_ROOM_UNDELETE'),'','','','','','','class="portal_link"').LF;
         unset($params);
      }
      // end actions

      $html .= '</div>'.LF;
      $html .= '<div style="width:70%;">'.LF;
      $html .= $this->_getRoomHeaderAsHTML($item);
      $html .= '</div>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      $html .= '<tr>'.LF;
      $mode = '';
      if (isset($_GET['account'])){
         $mode = $_GET['account'];
      }
      if (empty($mode)){
         $html .= '<td style="width:1%; vertical-align:middle;">'.LF;
         $html .= '<img src="'.$this->_style_image_path.'portal_key.gif" alt="" border="0"/>';
         $html .= '</td>'.LF;
         $html .= '<td style="width:26%; vertical-align:middle;">'.LF;
         $html .= '<span class="search_title">'.getMessage('COMMON_ACCESS_POINT').':'.'</span>';
         $html .= '</td>'.LF;

         $html .= '<td style="width:1%; vertical-align:middle;">'.LF;
         $html .= '<img src="'.$this->_style_image_path.'portal_info.gif" alt="" border="0"/>'.LF;
         $html .= '</td>'.LF;
         $html .= '<td style="width:42%; vertical-align:middle;">'.LF;
         $html .= '<span class="search_title">'.getMessage('COMMON_DESCRIPTION').':'.'</span>';
         $html .= '</td>'.LF;

         $html .= '<td style="width:1%; vertical-align:middle;">'.LF;
         $html .= '<img src="'.$this->_style_image_path.'portal_info2.gif" alt="" border="0"/>'.LF;
         $html .= '</td>'.LF;
         $html .= '<td style="width:29%; vertical-align:middle;">'.LF;
         $html .= '<span class="search_title">'.getMessage('COMMON_FACTS').':'.'</span>';
         $html .= '</td>'.LF;
      }else{
         $html .= '<td colspan="4" rowspan="2" style="width:71%; vertical-align:top; font-weight:normal;">'.LF;
         $html .= $this->_getRoomForm($item, $mode);
         $html .= '</td>'.LF;

         $html .= '<td style="width:1%; vertical-align:top;">'.LF;
         $html .= '<img src="'.$this->_style_image_path.'portal_info2.gif" alt="" border="0"/>'.LF;
         $html .= '</td>'.LF;
         $html .= '<td style="width:29%; vertical-align:top; padding-top:10px;">'.LF;
         $html .= '<span class="search_title">'.getMessage('COMMON_FACTS').':'.'</span>';
         $html .= '</td>'.LF;
      }


      $html .= '</tr>'.LF;
      $html .= '<tr>'.LF;
      if (empty($mode)){
         $html .= '<td>'.LF;
         $html .= '</td>'.LF;
         $html .= '<td style="vertical-align:top; font-weight:normal;">'.LF;
         $html .= $this->_getRoomAccessAsHTML($item);
         $html .= '</td>'.LF;

         $html .= '<td>'.LF;
         $html .= '</td>'.LF;
         $html .= '<td style="font-weight:normal; font-size:8pt; vertical-align:top;">'.LF;
         $desc = $item->getDescription();
         if (!empty($desc)){
            $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($item->getDescription()));
         }else{
            $html .= '<span class="disabled">'.getMessage('COMMON_NO_DESCRIPTION').'</span>'.LF;
         }
         $html .= '</td>'.LF;
      } else {
#         $html .= '<td colspan="4" style="width:70%; vertical-align:top; font-weight:normal;">'.LF;
#         $html .= '</td>'.LF;
      }

      $html .= '<td>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="vertical-align:top;">'.LF;
      $html .= $this->_getRoomFacts($item);
      $html .= '</td>'.LF;

      $html .= '</tr>'.LF;
      $html .= '</table>'.LF;

      $html .= '</div>'."\n";
      $html .= '<div style="position:absolute; top:-4px; left:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_links.gif" alt="" border="0"/></div>'.LF;
      $html .= '<div style="position:absolute; top:-4px; right:-5px;"><img src="'.$this->_style_image_path.'ecke_oben_rechts.gif" alt="" border="0"/></div>'.LF;

      $html .= '</div>'.LF;
      $html .= '<div class="frame_bottom">'.LF;
      $html .= '<div class="content_bottom">'.LF;
      $html .= '<div style="position:absolute; top:-11px; left:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_links.gif" alt=""/></div>';
      $html .= '<div style="position:absolute; top:-11px; right:-7px;"><img src="'.$this->_style_image_path.'ecke_unten_rechts.gif" alt=""/></div>';
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      if ($this->_with_delete_box) {
         $html .= $this->getDeleteBoxAsHTML();
      }
      $html .= '</div>'.LF;
      return $html;
   }

   /** get the header as HTML
    * this method returns the commsy header as HTML - internal, do not use
    *
    * @return string header as HTML
    */
   function _getRoomHeaderAsHTML($item) {
      $html  = LF.'<!-- BEGIN HEADER -->'.LF;
      // title
      $html .='<table style=" width:100%; padding:0px; margin:0px;" summary="Layout">';
      $html .='<tr>';
      $html .='<td style="width: 1%; vertical-align:bottom;">';
      $logo_filename = $item->getLogoFilename();
      $current_user = $this->_environment->getCurrentUserItem();
      if ( !empty($logo_filename) ) {
         $params = array();
         $params['picture'] = $item->getLogoFilename();
         $curl = curl($item->getItemID(), 'picture', 'getfile', $params,'');
         unset($params);
         $html .= '      <img class="logo" style="height:48px;" src="'.$curl.'" alt="'.$this->_translator->getMessage('LOGO').'" border="0"/>';
      }
      $html .= '</td>';
      // logo
      $html .=       '<td style="width: 99%; vertical-align:middle; padding-left:10px; padding-top:12px; padding-bottom:13px; padding-right:0px; text-align:left;">';
      $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: bold;">';
      if ( !$item->isPrivateRoom() ) {
         $html .= $this->_text_as_html_short($item->getTitle());
      } else {
         $owner = $item->getOwnerUserItem();
         if ( !empty($owner) ) {
            $html .= $this->_text_as_html_short($this->_translator->getMessage('PRIVATE_ROOM_TITLE').' '.$owner->getFullname());
         }
         unset($owner);
      }
      $html .= '</span>'.LF;
      if ($item->isDeleted()) {
         $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: normal;">';
         $html .= ' ('.$this->_translator->getMessage('ROOM_STATUS_DELETED').')';
         $html .= '</span>'.LF;
      } elseif ($item->isLocked()) {
         $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: normal;">';
         $html .= ' ('.$this->_translator->getMessage('PROJECTROOM_LOCKED').')'.LF;
         $html .= '</span>'.LF;
      } elseif ($item->isProjectroom() and $item->isTemplate()) {
         $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: normal;">';
         $html .= ' ('.$this->_translator->getMessage('PROJECTROOM_TEMPLATE').')'.LF;
         $html .= '</span>'.LF;
      } elseif ($item->isClosed()) {
         $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: normal;">';
         $html .= ' ('.$this->_translator->getMessage('PROJECTROOM_CLOSED').')'.LF;
         $html .= '</span>'.LF;
      }
      $html .='</td>';
      $html .='</tr>';
      $html .='</table>';
      $html .= '<!-- END HEADER -->'.LF;
      return $html;
   }


   /** get page view as HTML
    * this method returns the page view in HTML-Code
    *
    * @return string page view as HMTL
    *
    * @author CommSy Development Group
    */
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
         if ( ($this->_focus_onload) or ($this->_with_delete_box) ) {
            $html .= ' onload="';
            if ( $this->_focus_onload){
               $html .=' window.focus();setfocus();';
            }
            if ($this->_with_delete_box){
               $html .= ' initDeleteLayer();';
            }
            $html .= ' "';
         }
         $views = array_merge($this->_views, $this->_views_left, $this->_views_right);
         if ( isset($this->_form_view) ) {
            $views[] = $this->_form_view;
         }
         $view = reset($views);
         while ($view) {
            $html .= $view->getInfoForBodyAsHTML();
            $view = next($views);
         }
         $html .= '>'.LF;

         $html .= $this->_getPluginInfosForBeforeContentAsHTML();
         if ($this->_show_agbs) {
            $html .= $this->_getAGBTextAsHTML();
         } else {
            $html .= '<div style="width: 72em;">'.LF;
            $html .= LF.'<table style="border-collapse:collapse; padding:0px;  margin-top:5px; width:100%;" summary="Layout">'.LF;

            // Page Header
            $session = $this->_environment->getSession();
            $left_menue_status = $session->getValue('left_menue_status');
            $html .='<tr>'.LF;
            if ( ($left_menue_status != 'disapear' and !$this->_without_left_menue) ) {
               $html .='<td style=" width:13.7em; vertical-align:bottom;">'.LF;
               $html .= $this->_getLogoAsHTML().LF;
               $html .='</td>'.LF;
               $html .=       '<td style="width:58.3em; vertical-align:bottom; padding-bottom:0px;">';
            } else {
               $html .=       '<td style="width:72em; vertical-align:bottom; padding-bottom:0px;">';
            }
            $html .='</td>'.LF;
            $html .='</tr>'.LF;

            $html .= '<tr>'.LF;
            $session = $this->_environment->getSession();
            $left_menue_status = $session->getValue('left_menue_status');

            // INSTALLTION: for initializing first portal
            // root can login at server and initialize first portal
            $show_left_menue = false;
            $current_context = $this->_environment->getCurrentContextItem();
            if ( !$current_context->isDeleted() ) {
               if ( !$this->_environment->inServer() ) {
                  $show_left_menue = true;
               } else {
                  $server_item = $this->_environment->getCurrentContextItem();
                  $portal_list = $server_item->getPortalList();
                  if ( !isset($portal_list) or $portal_list->isEmpty() ) {
                     $show_left_menue = true;
                  }
              $user = $this->_environment->getCurrentUser();
              if($user->isRoot())
              {
                 $show_left_menue = true;
              }
               }
            }
            // INSTALLTION: for initializing first portal

            if ($left_menue_status !='disapear' and $show_left_menue ) {
               $html .= '<td style="margin-bottom:0px; padding:0px; vertical-align:top;">'.LF;
               $html .= LF.'<!-- COMMSY_MYAREA: START -->'.LF.LF;
               $html .= $this->getMyAreaAsHTML();
               $html .= LF.'<!-- COMMSY_MYAEREA: END -->'.LF.LF;
               $html .= '</td>'.LF;
               $html .= '<td style="padding-left:5px; padding-top:0px; margin:0px; vertical-align: top; ">'.LF;
            } else {
               $html .= '<td colspan="2" style="padding-left:5px; padding-top:0px; margin:0px; vertical-align: top; ">'.LF;
            }

            // Link Row
            if ($this->_with_navigation_links and !$this->_shown_as_printable) {
               $html .= $this->_getLinkRowAsHTML();
            } else {
               $html .= $this->_getBlankLinkRowAsHTML();
            }
            $html .= '<div class="portal_content">'.LF;

            $html .= '<table style="width:100%" summary="Layout">'.LF;
            $mod = $this->_environment->getCurrentModule();
            $fct = $this->_environment->getCurrentFunction();
            if ( !empty($this->_views) ) {
               foreach ($this->_views as $view) {
                  if ( isset($view->_title) ){
                    $html .= '<tr>'.LF;
                    $html .= '<td colspan="2">'.LF;
                    $html .= $view->asHTML();
                    $html .= '</td>'.LF;
                    $html .= '</tr>'.LF;
                  }
               }
            }
            $session = $this->_environment->getSession();
            $left_menue_status = $session->getValue('left_menue_status');
            if ($left_menue_status !='disapear' and !$this->_environment->inServer() ) {
               $width='width:55.5em;';
               $width_left='width:36em;';
               $width_right='width:18.7em;';
            } else {
               $width='width:68.5em;';
               $width_left='width:48em;';
               $width_right='width:18.7em;';
            }

            // first
            if ( !$current_context->isDeleted()
                 and ( !$this->_environment->inServer()
                       or $this->_environment->getCurrentFunction()=='statistic'
                     )
               ) {
               $html .= '<tr>'.LF;
               if ( isset($this->_agb_view) ) {
                  $html .= '<td colspan="2" class="portal_leftviews" style="'.$width.'">'.LF;
                  $html .= $this->_getAGBViewAsHTML().LF;
                  $html .= '</td>'.LF;
               } elseif (isset($_GET['room_id'])) {
                  $room_manager = $this->_environment->getRoomManager();
                  $room_item = $room_manager->getItem($_GET['room_id']);
                  #if ( isset($room_item) and !$room_item->isPrivateRoom() ) {
                  if ( isset($room_item) ) {
                     $html .= '<td colspan="2" class="portal_leftviews" style="'.$width.'">'.LF;
                     $html .= $this->_getRoomItemAsHTML($room_item);
                     $html .= '</td>'.LF;
                  } else {

                     $with_announcements = $current_context->isShowAnnouncementsOnHome();
                     if ($with_announcements){
                        $html .= '<td class="portal_leftviews" style="'.$width_left.'">'.LF;
                        $html .= $this->_getWelcomeTextAsHTML();
                        $html .= '</td>'.LF;
                        $html .= '<td class="portal_rightviews" style="'.$width_right.'">'.LF;
                        $html .= $this->_getPortalAnnouncements();
                        $html .= '</td>'.LF;
                     }else{

                        $html .= '<td colspan="2" class="portal_leftviews" style="'.$width.'">'.LF;
                        $html .= $this->_getWelcomeTextAsHTML();
                        $html .= '</td>'.LF;
                     }
                  }
               } elseif (isset($_GET['iid']) and $mod == 'configuration' ){
                  $html .= '<td colspan="2" class="portal_leftviews">'.LF;
                  $room_manager = $this->_environment->getRoomManager();
                  $room_item = $room_manager->getItem($_GET['iid']);
                  $html .= $this->_getRoomFormAsHTML($room_item);
                  $html .= '</td>'.LF;
               } elseif ($mod == 'mail' and $this->_environment->getCurrentFunction() == 'to_moderator'){
                  $html .= '<td colspan="2" class="portal_leftviews" style="width:'.$width.'">'.LF;
                  $html .= $this->_getModeratorMailTextAsHTML();
                  $html .= '</td>'.LF;
               } elseif ($mod == 'configuration' or $mod == 'account'){
                  $html .= '<td colspan="2" class="portal_leftviews" style="width:'.$width.'">'.LF;
                  $html .= $this->_getConfigurationAsHTML();
                  $html .= '</td>'.LF;
               } elseif ( $mod == 'language' ) {
                  $html .= '<td colspan="2" class="portal_leftviews" style="width:'.$width.'">'.LF;
                  $html .= $this->_getLanguageIndexAsHTML();
                  $html .= '</td>'.LF;
               } elseif ($mod == 'mail' and $fct == 'process') {
                  $html .= '<td colspan="2" class="portal_leftviews" style="width:'.$width.'">'.LF;
                  $html .= $this->_getConfigurationAsHTML();
                  $html .= '</td>'.LF;
               } elseif ( ( $mod == 'project' and $fct == 'edit' )
                            or ( $mod == 'community' and $fct == 'edit' )
                        ) {
                  $html .= '<td colspan="2" class="portal_leftviews" style="width:'.$width.'">'.LF;
                  $html .= $this->_getConfigurationAsHTML();
                  $html .= '</td>'.LF;
               } else {
                  $with_announcements = $current_context->isShowAnnouncementsOnHome();
                  if ($with_announcements){
                     $html .= '<td class="portal_leftviews" style="'.$width_left.'">'.LF;
                     $html .= $this->_getWelcomeTextAsHTML();
                     $html .= '</td>'.LF;
                     $html .= '<td class="portal_rightviews" style="'.$width_right.'">'.LF;
                     $html .= $this->_getPortalAnnouncements();
                     $html .= '</td>'.LF;
                  } else {
                     $html .= '<td colspan="2" class="portal_leftviews" style="'.$width.'">'.LF;
                     $html .= $this->_getWelcomeTextAsHTML();
                     $html .= '</td>'.LF;
                  }
               }
               $html .=' </tr>'.LF;
            } elseif ( $this->_environment->inServer()
                       and ($mod == 'configuration' or $mod == 'account')
                     ) {
               $html .= '<tr>'.LF;
               $html .= '<td colspan="2" class="portal_leftviews">'.LF;
               $html .= $this->_getConfigurationAsHTML();
               $html .= '</td>'.LF;
               $html .=' </tr>'.LF;
            }

            // second
            if ( !$current_context->isDeleted()
                 and !(isset($_GET['iid']) and ($fct == 'common' or $fct == 'preferences' or $fct == 'move' or $fct == 'export'))
                 and ( !(!isset($_GET['iid']) and $mod == 'configuration') )
                 and ( !($mod == 'configuration' and $fct == 'service') ) // configuration_service: don't show second row
                 and ( !($mod == 'configuration' and $fct == 'plugins') ) // configuration_plugins: don't show second row
                 and ( !($mod == 'account') )
                 and ( !($mod == 'agb') ) // AGB: don't show second row
                 and ( !($mod == 'mail' and $fct == 'process') )
                 and ( !($mod == 'mail' and $fct == 'to_moderator') )
                 and ( !($mod == 'project' and $fct == 'edit') )
                 and ( !($mod == 'community' and $fct == 'edit') )
                 and ( !($mod == 'language') )
                 and !$this->_environment->inServer()
                 and !isset($this->_agb_view)
               ) {
               $html .= '<tr>'.LF;
               $html .= '<td class="portal_leftviews" style="'.$width_left.'">'.LF;
               $html .= $this->_getContentListAsHTML();
               $html .= '</td>'.LF;
               $html .= '<td class="portal_rightviews" style="'.$width_right.'">'.LF;
               $html .= $this->_getSearchBoxAsHTML();
               $html .= '</td>'.LF;
               $html .= '</tr>'.LF;
            } elseif ( $this->_environment->inServer()
                       and !($mod == 'configuration' or $mod == 'account')
            ) {
               $width_left='width:39em;';
               $width_right='width:28.5em;';
               $html .= '<tr>'.LF;
               $html .= '<td class="portal_leftviews" style="'.$width_left.'">'.LF;
               $html .= $this->_getContentListAsHTML();
               $html .= '</td>'.LF;
               $html .= '<td class="portal_rightviews" style="'.$width_right.'">'.LF;
               $html .= $this->_getServerWelcomeTextAsHTML();
               $html .= '</td>'.LF;
               $html .= '</tr>'.LF;
            }
            $html .= '</table>'.LF;

            $html .= '</div>'.LF;
            $html .= '</td></tr>';
            $html .= '<tr>';
            if ( !$this->_environment->inServer() ) {
               $html .= '<td></td>';
               $html .= '<td>';
            } else {
               $html .= '<td colspan="2">';
            }
            $html .= '<div class="footer" style="float:right; text-align:right; padding-left:0px; padding-right:10px; padding-top:0px; padding-bottom:10px;">'.LF;
            $current_user = $this->_environment->getCurrentUserItem();
            $current_context = $this->_environment->getCurrentContextItem();
            $email_to_moderators = '';
            if ( $current_context->showMail2ModeratorLink() ) {

               $email_to_moderators = ahref_curl($this->_environment->getCurrentContextID(),
                                                   'mail',
                                                    'to_moderator',
                                                    '',
                                                    $this->_translator->getMessage('COMMON_MAIL_TO_MODERATOR'));
            }

            // service link
            if ( $current_context->showServiceLink()
                 and $current_user->isUser()
               ) {
               $color = '#D5D5D5';
               $server_item = $this->_environment->getServerItem();
               $link = 'http://www.commsy.net/?n=Software.FAQ&amp;mod=edit';
               $email_to_service = '<form action="'.$link.'" method="post" name="service" style="margin-bottom: 0px;">'.LF;
               $email_to_service .= '<input type="hidden" name="server_name" value="'.$server_item->getTitle().'"/>'.LF;
               if ( isset($_SERVER["SERVER_ADDR"]) and !empty($_SERVER["SERVER_ADDR"])) {
                  $email_to_service .= '<input type="hidden" name="server_ip" value="'.$this->_text_as_html_short($_SERVER["SERVER_ADDR"]).'"/>'.LF;
               } else {
                  $email_to_service .= '<input type="hidden" name="server_ip" value="'.$this->_text_as_html_short($_SERVER["HTTP_HOST"]).'"/>'.LF;
               }

               // Hierarchy of service-email: Set email, test if portal tier has one, then server tier
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
               unset($server_item);

               if ($service_email == '') {
                  $service_email = 'NONE';
               }

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
               if ($email_to_moderators != '' ) {
                  $html .= $email_to_moderators;
               }
               if ( $current_context->withAGB() and !isset($this->_agb_view) and $this->_with_agb_link ) {
                  if ($email_to_moderators != '' ) {
                     $html .= '&nbsp;-&nbsp;';
                  }
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),'agb','index','',getMessage('COMMON_AGB_CONFIRMATION_LINK_INPUT'),'','agb','','',' onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"').'&nbsp;-&nbsp;';
               }
               $html .= '     </td>'.LF;
               $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
               $html .= $email_to_service;
               $html .= '     </td>'.LF;
               $html .= '  </tr>'.LF;
               $html .= '</table>'.LF;
            } else {
               $html .= '<div style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
               if ($email_to_moderators != '' ) {
                  $html .= $email_to_moderators;
               }
               if ( $current_context->withAGB() and !isset($this->_agb_view) and $this->_with_agb_link ) {
                  if ($email_to_moderators != '' ) {
                     $html .= '&nbsp;-&nbsp;';
                  }
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),'agb','index','',getMessage('AGB_CONFIRMATION_LINK_INPUT'),'','agb','','',' onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
               }
               $html .= '</div>'.LF;
            }
            $html .= $this->_getPluginInfosForAfterContentAsHTML();
            $html .= '</div>'.LF;
            $html .= '<div style="padding-left:10px;">'.LF;
            $html .= $this->_getSystemInfoAsHTML();
            $html .= '</div>'.LF;
            $html .= $this->_getFooterAsHTML();
            $html .= '</td></tr>';
            $html .=' </table>'.BRLF;
            $html .= '</div>'.LF;
         }
         if ( isset($_GET['show_profile']) and $_GET['show_profile'] == 'yes'){
            $html .= $this->getProfileBoxAsHTML();
         }
         $html .= '</body>'.LF;
         $html .= '</html>'.LF;
      }
      return $html;
   }

   function getProfileBoxAsHTML(){
      $html = '';
      $environment = $this->_environment;
      $html  = '<div style="position:absolute; left:0px; top:0px; z-index:1000; width:100%; height: 100%;">'.LF;
      $html .= '<div style="z-index:1000; margin-top:40px; margin-bottom:0px; margin-left: 20%; width:60%; text-align:left; background-color:#FFFFFF;">';
      global $profile_view;
      $html .= $profile_view->asHTML();
      $html .= '</div>';
      $html .= '</div>';
      $html .= '<div id="profile" style="position: absolute; left:0px; top:0px; z-index:900; width:100%; height: 850px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
      $html .= '</div>';
      return $html;
   }
}
?>