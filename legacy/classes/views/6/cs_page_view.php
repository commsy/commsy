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
$this->includeClass(VIEW);

/** class for a page view of commsy
 * this class implements a page view of commsy
 */
class cs_page_view extends cs_view {

   /**
    * array - containing the views over the page above
    */
   var $_views = array();

   /**
    * array - containing the views on the left hand side
    */
   var $_views_left = array();

   /**
    * array - containing the views on the right hand side
    */
   var $_views_right = array();

   /**
    * array - containing the views overlay
    */
   var $_views_overlay = array();

   /**
    * string - containing the name of the page
    */
   var $_name_page = NULL;

   /**
    * string - containing the name of the context
    */
   var $_name_room = NULL;

   /**
    * object cs_item - containing the current user
    */
   var $_current_user = NULL;

   /**
    * boolean - containing the flag for displaying the personal area
    * standard = true
    */
   var $_with_personal_area = true;

   var $_without_commsy_column = false;

   /**
    * boolean - containing the flag for displaying the CommSy footer
    * standard = true
    */
   var $_with_commsy_footer = true;

   var $_without_left_menue = false;

   /**
    * boolean - containing the flag for setting focus to the page on load
    * standard = false
    */
   var $_focus_onload = false;

   var $_is_help_page = false;

   var $_is_print_page = false;

   /**
    * boolean - containing the flag for displaying the navigation links
    * standard = true
    */
   var $_with_navigation_links = true;

   var $_send_first_html_part = false;

   var $_send_second_html_part = false;

   var $_errorbox_left = NULL;

   var $_with_agb_link = true;

   var $_style_image_path = 'images/layout/';

   var $_flush_mode = false;
   
   protected $_toggle_archive_mode = false;
    
   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      if (file_exists('htdocs/'.$this->_environment->getCurrentPortalID().'/commsy.css') ){
         $this->_style_image_path = $this->_environment->getCurrentPortalID().'/images/';
      }
   }

   public function setFlushModeOn () {
      $this->_flush_mode = true;
   }

   public function flushHTML () {
      return $this->_flush_mode;
   }

   function setMyAreaErrorBox($value){
      $this->_errorbox_left = $value;
   }

   function getMyAreaErrorBox(){
      return $this->_errorbox_left;
   }

   function setWithoutAGBLink () {
      $this->_with_agb_link = false;
   }

   /** set current user
    * this method sets the current user
    *
    * @param object cs_item the current user
    */
   function setCurrentUser ($value) {
      $this->_current_user = $value;
   }

   /** set name of the context
    * this method sets the name of the context
    *
    * @param string value name of the context
    *
    * @author CommSy Development Group
    */
   function setRoomName ($value) {
      $this->_name_room = (string)$value;
   }

   /** set name of the page
    * this method sets the name of the page
    *
    * @param string value name of the page
    *
    * @author CommSy Development Group
    */
   function setPageName ($value) {
      $this->_name_page = (string)$value;
   }

   function withoutCommSyColumn () {
     $this->_without_commsy_column = true;
   }

   function setWithoutLeftMenue () {
     $this->_without_left_menue = true;
   }

   /** so page will be displayed without the navigation links
    * this method skip a flag, so that the navigation links will not be shown
    */
   function setWithoutNavigationLinks () {
      $this->_with_navigation_links = false;
   }

   /** so page will be displayed without the CommSy footer
    * this method skip a flag, so that the CommSy footer will not be shown
    *
    * @author CommSy Development Group
    */
   function withoutCommSyFooter () {
      $this->_with_commsy_footer = false;
   }

   /** the page will get focus on load or reload
    * this method toggles a flag, so that the parameter 'onload="window.focus()"' is
    * added to the html body tag
    *
    * @author CommSy Development Group
    */
   function setFocusOnload () {
      $this->_focus_onload = true;
   }

   function setHelpPageStatus(){
      $this->_is_help_page = true;
      $this->_with_navigation_links = false;
   }

   /** adds a view
    * this method adds a view to the page
    *
    * @param object cs_view a commsy view
    *
    * @author CommSy Development Group
    */
   function add ($view) {
      $this->_views[] = $view;
   }

   /** adds a view on the left
    * this method adds a view to the page on the left hand side
    *
    * @param object cs_view a commsy view
    *
    * @author CommSy Development Group
    */
   function addLeft ($view) {
      $this->_views_left[] = $view;
   }

   /** adds a view in the right
    * this method adds a view to the page on the right hand side
    *
    * @param object cs_view a commsy view
    *
    * @author CommSy Development Group
    */
   function addRight ($view) {
      $this->_views_right[] = $view;
   }

   function _getIncludedCSSAsHTML(){
      global $c_commsy_url_path;
      $module   = $this->_environment->getCurrentModule();
      $current_user   = $this->_environment->getCurrentUserItem();
      $function = $this->_environment->getCurrentFunction();
      $url_addon = '';
      if ( isset($_GET['mode']) and $_GET['mode']=='print' ) {
         $this->_is_print_page = true;
      }
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      $retour  = '';
      $show_agb_again = false;
      if ( $current_user->isUser() and !$current_user->isRoot() ) {
         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_context->withAGB() ) {
            $user_agb_date = $current_user->getAGBAcceptanceDate();
            $context_agb_date = $current_context->getAGBChangeDate();
            if ($user_agb_date < $context_agb_date) {
               $show_agb_again = true;
            }
         }
      }

      $retour .= '   <link rel="stylesheet" type="text/css" href="css/portal.css"/>'.LF;

      if ($this->_environment->getCurrentFunction() == 'detail' or $this->_environment->getCurrentModule() == 'help' and !$this->_is_print_page){
         // for tex in commsy
         // see http://www.math.union.edu/~dpvc/jsMath/
         global $c_jsmath_enable;
         if ( isset($c_jsmath_enable)
              and $c_jsmath_enable
            ) {
            $retour .= '   <style type="text/css"> #jsMath_Warning {display: none} </style>'.LF;
            $retour .= '   <style type="text/css"> #jsMath_button  {display: none} </style>'.LF;
         }
      }
      
      return $retour;
   }

   function _includedJavascriptAsHTML(){
      global $c_commsy_url_path;
      $module   = $this->_environment->getCurrentModule();
      $current_user   = $this->_environment->getCurrentUserItem();
      $function = $this->_environment->getCurrentFunction();
      $url_addon = '';
      if ( isset($_GET['mode']) and $_GET['mode']=='print' ) {
         $this->_is_print_page = true;
      }
      $views = array_merge($this->_views, $this->_views_left, $this->_views_right, $this->_views_overlay);
      if ( isset($this->_form_view) ) {
            $views[] = $this->_form_view;
      }
      $view = reset($views);
      $needed_javascript_array = array();
      while ($view) {
         $needed_javascript_array = $view->getJavaScriptInfoArrayForHeaderAsHTML($needed_javascript_array);
         $view = next($views);
      }
      unset($views);
      unset($view);

      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      $retour  = '';

      if (!$this->_environment->inServer() and (!$this->_environment->inPortal() or $this->_environment->getCurrentModule() == 'account')){
         $retour .= '   <script type="text/javascript" src="javascript/CommSyPanels.js"></script>'.LF;
      }
      if($this->_environment->inPortal() or
             ( $this->_environment->getCurrentModule() == CS_PROJECT_TYPE and
               $this->_environment->getCurrentFunction() == 'edit' )
         ){
         // jQuery
         //$retour .= '   <script type="text/javascript" src="javascript/CommSyTemplateInformation.js"></script>'.LF;
         // jQuery
      }

      if (!$this->_environment->inServer() and !$this->_environment->inPortal() and $this->_environment->getCurrentModule() == 'home'){
         $retour .= '   <script type="text/javascript">'.LF;
         $retour .= '      <!--'.LF;
         $retour .= '   var message = \''.$this->_translator->getMessage('COMMON_PICTURE_DOWNLOADFILE').'\';'.LF;
         $retour .= '      -->'.LF;
         $retour .= '   </script>'.LF;
         $retour .= '   <script type="text/javascript" src="javascript/swfobject.js"></script>'.LF;
      }elseif ($this->_environment->getCurrentFunction() == 'detail'){
         // jQuery
         $retour .= '   <script type="text/javascript" src="javascript/CommSyCreatorInformation.js"></script>'.LF;
         $retour .= '   <script type="text/javascript">'.LF;
         $retour .= '      <!--'.LF;
         $retour .= '   var message = \''.$this->_translator->getMessage('COMMON_PICTURE_DOWNLOADFILE').'\';'.LF;
         $retour .= '      -->'.LF;
         $retour .= '   </script>'.LF;
         $retour .= '   <script type="text/javascript" src="javascript/swfobject.js"></script>'.LF;
        
         // for tex in commsy
         // see http://www.math.union.edu/~dpvc/jsMath/
         global $c_jsmath_enable;
         if ( isset($c_jsmath_enable)
              and $c_jsmath_enable
            ) {
            $retour .= '   <script type="text/javascript"> jsMath = {Controls: {cookie: {scale: 120}}} </script>'.LF;
            global $c_jsmath_url;
            $retour .= '   <script type="text/javascript" src="'.$c_jsmath_url.'/plugins/autoload.js"></script>'.LF;
            $retour .= '   <script type="text/javascript">'.LF;
            $retour .= '      window.addEvent(\'domready\', function () {'.LF;
            $retour .= '         if (jsMath.Autoload) {'.LF;
            $retour .= '            jsMath.Autoload.Check();'.LF;
            $retour .= '            jsMath.Process(document);'.LF;
            $retour .= '         }'.LF;
            $retour .= '      });'.LF;
            $retour .= '   </script>'.LF;
         }
         if ($current_user->isAutoSaveOn() and $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE ){
            $current_context = $this->_environment->getCurrentContextItem();
            if ( $current_context->withOnlySimpleDiscussionType() or $current_context->withBothDiscussionTypes() ) {
               // and discussion_item->is_linear()
               $retour .= '   <script type="text/javascript" src="javascript/CommSyAutoSave.js"></script>'.LF;
               $retour .= '   <script type="text/javascript">'.LF;
               $retour .= '      <!--'.LF;
               $retour .= '         var timerID = null;'.LF;
               $retour .= '         var timerRunning = false;'.LF;
               $retour .= '         var startDate;'.LF;
               $retour .= '         var startSecs;'.LF;

               global $symfonyContainer;

               $c_autosave_mode = $symfonyContainer->getParameter('commsy.autosave.mode');
               $c_autosave_limit = $symfonyContainer->getParameter('commsy.autosave.limit');

               $retour .= '         var dispMode = '.$c_autosave_mode.';'.LF;
               $retour .= '         var sessLimit = '.($c_autosave_limit*60).';'.LF;
               $retour .= '      -->'.LF;
               $retour .= '   </script>'.LF;
            }
         }
      }elseif ($this->_environment->getCurrentFunction() == 'index'){
         $retour .= '   <script type="text/javascript">'.LF;
         $retour .= '      <!--'.LF;
         $retour .= '   var message = \''.$this->_translator->getMessage('COMMON_PICTURE_DOWNLOADFILE').'\';'.LF;
         $retour .= '      -->'.LF;
         $retour .= '   </script>'.LF;
         if ( !$this->_environment->getCurrentModule() == 'configuration' ) {
         }
      }elseif ( $this->_environment->getCurrentFunction() == 'edit'
               or $this->_environment->getCurrentModule() == 'mail'
               or $this->_environment->getCurrentFunction() == 'mail'
               or $this->_environment->getCurrentFunction() == 'info_text_edit'
               or $this->_environment->getCurrentFunction() == 'info_text_form_edit'
               or $this->_environment->getCurrentFunction() =='close'
               or $this->_environment->getCurrentFunction() =='import'
               or $this->_environment->getCurrentFunction() =='preferences'
               or ($this->_environment->getCurrentModule() =='user' and $this->_environment->getCurrentFunction() =='preferences')
               or ($this->_environment->getCurrentModule() =='user' and $this->_environment->getCurrentFunction() =='action')
               or $this->_environment->getCurrentModule() == 'configuration'
               or $this->_environment->getCurrentModule() == 'account'
               or $this->_environment->getCurrentModule() == 'material_admin'
      ){
         if (!$this->_environment->inServer()){
            $retour .= '   <script type="text/javascript">'.LF;
            $retour .= '      <!--'.LF;
            $retour .= '   var message = \''.$this->_translator->getMessage('COMMON_PICTURE_DOWNLOADFILE').'\';'.LF;
            $retour .= '      -->'.LF;
            $retour .= '   </script>'.LF;
         }
      }
      
      // jQuery
      global $c_jsmath_enable;
      if ( isset($c_jsmath_enable)
           and $c_jsmath_enable
         ) {
         $retour .= '   <script type="text/javascript"> jsMath = {Controls: {cookie: {scale: 120}}} </script>'.LF;
         global $c_jsmath_url;
         $retour .= '   <script type="text/javascript" src="'.$c_jsmath_url.'/jsMath.js"></script>'.LF;
      }
      return $retour;
   }
   
   private function _includeDojoAsHTML() {
   	$html = "";
   	
   	$current_user = $this->_environment->getCurrentUser();
   	$ownRoomItem = $current_user->getOwnRoom();
   	$templateEngine = $this->_environment->getTemplateEngine();
   	$translator = $this->_environment->getTranslationObject();

    $tpl_path = 'templates/';
   	
   	global $c_js_mode;
   	$mode = (isset($c_js_mode) && ($c_js_mode === "build" || $c_js_mode === "layer")) ? $c_js_mode : "source";
   	
   	$to_javascript = array();
   	
   	$to_javascript['template']['tpl_path'] = $tpl_path;
   	$to_javascript['environment']['lang'] = $this->_environment->getSelectedLanguage();
   	$to_javascript['environment']['single_entry_point'] = $this->_environment->getConfiguration('c_single_entry_point');
   	$to_javascript['environment']['max_upload_size'] = $this->_environment->getCurrentContextItem()->getMaxUploadSizeInBytes();
    $to_javascript['environment']['isPortal'] = $this->_environment->getCurrentContextItem()->isPortal();

   	$to_javascript['i18n']['COMMON_NEW_BLOCK'] = $translator->getMessage('COMMON_NEW_BLOCK');
   	$to_javascript['i18n']['COMMON_SAVE_BUTTON'] = $translator->getMessage('COMMON_SAVE_BUTTON');
   	$to_javascript['security']['token'] = getToken();
   	$to_javascript['autosave']['mode'] = 0;
   	$to_javascript['autosave']['limit'] = 0;
   	
   	if ($ownRoomItem) {
   		$to_javascript['ownRoom']['id'] = $ownRoomItem->getItemId();
   		$to_javascript['own']['id'] = $ownRoomItem->getItemId();
   		$to_javascript['ownRoom']['withPortfolio'] = $ownRoomItem->getCSBarShowPortfolio();
   	}
   	
   	// translations - should be managed elsewhere soon
   	$to_javascript["translations"]["common_hide"] = $translator->getMessage("COMMON_HIDE");
   	$to_javascript["translations"]["common_show"] = $translator->getMessage("COMMON_SHOW");
   	
   	$portal_item = $this->_environment->getCurrentPortalItem();
   	$current_portal_user = $this->_environment->getPortalUserItem();
   	// password expires soon alert
   	if(!empty($current_portal_user) AND $current_portal_user->getPasswordExpireDate() > getCurrentDateTimeInMySQL()) {
   		$start_date = new DateTime(getCurrentDateTimeInMySQL());
   		$since_start = $start_date->diff(new DateTime($current_portal_user->getPasswordExpireDate()));
   		$days = $since_start->days;
   		if($days == 0){
   			$days = 1;
   		}
   	
        $days_before_expiring_sendmail = $portal_item->getDaysBeforeExpiringPasswordSendMail();
        
        $checkMinutesAndHours = true;
   	    if(isset($days_before_expiring_sendmail) AND $days == $days_before_expiring_sendmail){
       	    if ($since_start->h > 0 || $since_start->m > 0) {
           	    $checkMinutesAndHours = false;
       	    }
   	    } else if(!isset($days_before_expiring_sendmail) AND $days == 14){
       	    if ($since_start->h > 0 || $since_start->m > 0) {
           	    $checkMinutesAndHours = false;
       	    }
   	    }
   	
   		if(isset($days_before_expiring_sendmail) AND $days <= $days_before_expiring_sendmail AND $checkMinutesAndHours){
   			$to_javascript["translations"]["password_expire_soon_alert"] = $translator->getMessage("COMMON_PASSWORD_EXPIRE_ALERT", $days);
   			$to_javascript['environment']['password_expire_soon'] = true;
   		} else if(!isset($days_before_expiring_sendmail) AND $days <= 14 AND $checkMinutesAndHours){
   			$to_javascript["translations"]["password_expire_soon_alert"] = $translator->getMessage("COMMON_PASSWORD_EXPIRE_ALERT", $days);
   			$to_javascript['environment']['password_expire_soon'] = true;
   		}
   	} else {
   		$to_javascript['environment']['password_expire_soon'] = false;
   	}
   	
   	$current_user = $this->_environment->getCurrentUserItem();
   		
   	$auth_source_manager = $this->_environment->getAuthSourceManager();
   	$auth_source_item = $auth_source_manager->getItem($current_user->getAuthSource());
   	
   	if(isset($auth_source_item)){
   		$show_tooltip = true;
	   	// password
	   	if($auth_source_item->getPasswordLength() > 0){
	   		$to_javascript["password"]["length"] = $translator->getMessage('PASSWORD_INFO2_LENGTH', $auth_source_item->getPasswordLength());
	   	} else {
	   		$show_tooltip = false;
	   	}
	   	if($auth_source_item->getPasswordSecureBigchar() == 1){
	   		$to_javascript["password"]["big"] = $translator->getMessage('PASSWORD_INFO2_BIG');
	   	} else {
	   		$show_tooltip = false;
	   	}
	   	if($auth_source_item->getPasswordSecureSmallchar() == 1){
	   		$to_javascript["password"]["small"] = $translator->getMessage('PASSWORD_INFO2_SMALL');
	   	} else {
	   		$show_tooltip = false;
	   	}
	   	if($auth_source_item->getPasswordSecureNumber() == 1){
	   		$to_javascript["password"]["special"] = $translator->getMessage('PASSWORD_INFO2_SPECIAL');
	   	} else {
	   		$show_tooltip = false;
	   	}
	   	if($auth_source_item->getPasswordSecureSpecialchar() == 1){
	   		$to_javascript["password"]["number"] = $translator->getMessage('PASSWORD_INFO2_NUMBER');
	   	} else {
	   		$show_tooltip = false;
	   	}
   	} else {
   		$show_tooltip = false;
   	}
   	if($show_tooltip){
   		$to_javascript["password"]["tooltip"] = 1;
   	} else {
   		$to_javascript["password"]["tooltip"] = 0;
   	}
   	
   	// dev
   	global $c_xhr_error_reporting;
   	$to_javascript['dev']['indexed_search'] = false;
   	$to_javascript['dev']['xhr_error_reporting'] = (isset($c_xhr_error_reporting) && !empty($c_xhr_error_reporting)) ? true : false;
   	
   	if(isset($portal_user) && $portal_user->isAutoSaveOn()) {
      global $symfonyContainer;

      $c_autosave_mode = $symfonyContainer->getParameter('commsy.autosave.mode');
      $c_autosave_limit = $symfonyContainer->getParameter('commsy.autosave.limit');
   	
   		if(isset($c_autosave_mode) && isset($c_autosave_limit)) {
   			$to_javascript['autosave']['mode'] = $c_autosave_mode;
   			$to_javascript['autosave']['limit'] = $c_autosave_limit;
   		}
   	}
   	
      // has to change email (new) at portal
   	if ( isset($this->_has_to_change_email) and $this->_has_to_change_email ) {
   	   $to_javascript['autoOpenPopup']['popup'] = 'tm_user';
   	   $to_javascript['autoOpenPopup']['tab'] = 'user';
   	   $to_javascript['autoOpenPopup']['parameters'] = array();
   	}
   	
   	switch ($mode) {
   		
   		default:
   			$html .= '<script src="js/src/sourceConfig.js"></script>';
   			
   			$html .= "
   				<script>
   					var from_php  = '" . json_encode($to_javascript) . "';
   					dojoConfig.locale = '" . $this->_environment->getSelectedLanguage() . "';
   				</script>
   			";
   			
   			$html .= '<script src="js/src/dojo/dojo.js"></script>';
   			$html .= '<script src="js/src/commsy/main.js"></script>';
   	}
   	
   	$html .= '
   		<link rel="stylesheet" type="text/css" media="screen" href="js/src/dijit/themes/tundra/tundra.css" />
   		<link rel="stylesheet" type="text/css" media="screen" href="js/src/cbtree/themes/tundra/tundra.css" />
   		<link rel="stylesheet" type="text/css" media="screen" href="js/src/dojox/form/resources/UploaderFileList.css" />
   		<link rel="stylesheet" type="text/css" media="screen" href="js/src/dojox/image/resources/Lightbox.css" />
   		<link rel="stylesheet" type="text/css" media="screen" href="js/src/dojox/widget/ColorPicker/ColorPicker.css" />
   		<link rel="stylesheet" type="text/css" media="screen" href="js/src/dojox/calendar/themes/tundra/Calendar.css" />
   		<link rel="stylesheet" type="text/css" media="screen" href="templates/themes/default/styles.css" />
   		<link rel="stylesheet" type="text/css" media="screen" href="templates/themes/default/styles_addon.css" />
   	';
   	
   	return $html;
   }

   function _getHTMLHeadAsHTML () {
      global $c_commsy_url_path;
      $module   = $this->_environment->getCurrentModule();
      $function = $this->_environment->getCurrentFunction();
      $url_addon = '';
      if ( isset($_GET['mode']) and $_GET['mode']=='print' ) {
         $this->_is_print_page = true;
      }
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      $retour  = '';
#      $retour .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.LF;
#      $retour .= '<html xmlns="http://www.w3.org/1999/xhtml">'.LF;
      $retour .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'.LF;
      $retour .= '<html>'.LF;
      $retour .= '<head>'.LF;
      //$retour .= '   <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>'.LF;
      // ------------
      // --->UTF8<---
      $retour .= '   <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'.LF;
      // --->UTF8<---
      // ------------
      $retour .= '   <meta http-equiv="expires" content="-1"/>'.LF;
      $retour .= '   <meta http-equiv="cache-control" content="no-cache"/>'.LF;
      $retour .= '   <meta http-equiv="pragma" content="no-cache"/>'.LF;
      $retour .= '   <meta name="MSSmartTagsPreventParsing" content="TRUE"/>'.LF;
      $retour .= '   <meta name="CommsyBaseURL" content="'.$c_commsy_url_path.'"/>'.LF;
      
      /* CommSy Bar */
      $currentUser = $this->_environment->getCurrentUserItem();
      if ($this->_environment->InPortal() && !$currentUser->isGuest()) {
      	$retour .= $this->_includeDojoAsHTML();
      }
      
      $retour .= $this->_getIncludedCSSAsHTML();
      $retour .= $this->_includedJavascriptAsHTML();

        $auth_source_manager = $this->_environment->getAuthSourceManager();
        $auth_source = $auth_source_manager->_performQuery();

        if (!empty($auth_source)) {
            $auth_source_item = $auth_source_manager->getItem($auth_source[0]['item_id']);

            if(!empty($auth_source_item) AND $auth_source_item->isPasswordSecureActivated()){
                $retour .= '<script type="text/javascript">
                            $.fn.passwordStrength = function( options ){
                                return this.each(function(){
                                    var that = this;that.opts = {};
                                    that.opts = $.extend({}, $.fn.passwordStrength.defaults, options);

                                    that.div = $(that.opts.targetDiv);
                                    that.defaultClass = that.div.attr(\'class\');

                                    that.percents = (that.opts.classes.length) ? 100 / that.opts.classes.length : 100;

                                     v = $(this)
                                    .keyup(function(){
                                        if( typeof el == "undefined" )
                                            this.el = $(this);
                                        var s = getPasswordStrength (this.value);
                                        var p = this.percents;
                                        var t = Math.floor( s / p );
                                        if( 100 <= s )
                                            t = this.opts.classes.length - 1;

                                        this.div
                                            .removeAttr(\'class\')
                                            .addClass( this.defaultClass )
                                            .addClass( this.opts.classes[ t ] );

                                    })
                                });

                                function getPasswordStrength(H){
                                    var L=(H.length);
                                    var D=(H.length);
                                    if (D<4) { D=0 }
                                    if(D>6){
                                        D=5
                                    }';
                                    // Zahlen
                                    $retour .= '
                                    var F=H.replace(/[0-9]/g,"");
                                    var G=(H.length-F.length);
                                    if(G>3){G=3}
                                    var A=H.replace(/\W/g,"");
                                    var C=(H.length-A.length);
                                    if(C>3){C=3}
                                    var B=H.replace(/[A-Z]/g,"");
                                    var I=(H.length-B.length);
                                    if(I>3){I=3}
                                    var Z=H.replace(/[a-z]/g,"");
                                    var S=(H.length-Z.length);
                                    var E=((D*10)-20)+(G*10)+(C*15)+(I*10);';

                                    if($auth_source_item->isPasswordSecureActivated()){
                                        $retour .= 'if(1 ';
                                        if($auth_source_item->getPasswordSecureSpecialchar() == 1){
                                            $retour .= '&& (C >= 1) ';
                                        }
                                        if($auth_source_item->getPasswordSecureBigchar() == 1){
                                            $retour .= '&& (I >= 1) ';
                                        }
                                        if($auth_source_item->getPasswordSecureSmallchar() == 1){
                                            $retour .= '&& (S >= 1) ';
                                        }
                                        if($auth_source_item->getPasswordSecureNumber() == 1){
                                            $retour .= '&& (G >= 1) ';
                                        }
                                        if($auth_source_item->getPasswordLength() > 0){
                                            $retour .= '&& (L >= '.$auth_source_item->getPasswordLength().')';
                                        }
                                        $retour .= '){;if(E >= 100){E = 100}else{E=50}}else{E=0}';
                                    }
                                    $retour .= '
                                    if(E<0){E=0}
                                    if(E>100){E=100}
                                    return E
                                }

                            };

                            $(document)
                            .ready(function(){
                                $(\'input[name="password"]\').passwordStrength({targetDiv: \'#iSM\',classes : Array(\'weak\',\'medium\',\'strong\')});

                            });
                            </script>
                            ';

            }
            unset($auth_source_manager);
            unset($auth_source_item);
        }


      $current_context_item = $this->_environment->getCurrentContextItem();
      $current_user_item = $this->_environment->getCurrentUserItem();
      $show_rss_link = false;
      if ( $current_context_item->isLocked()
           or $current_context_item->isServer()
           or $current_context_item->isPortal()
         ) {
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
         $retour .= '   <link rel="alternate" type="application/rss+xml" title="RSS" href="rss.php?cid='.$current_context_item->getItemID().$hash_string.'" />'.LF;
      }
      unset($current_user_item);
      unset($current_context_item);

      $retour .= '   <link rel="SHORTCUT ICON" href="images/favicon.ico"/>'.LF;

      $between = '';
      if ( !empty($this->_name_room) and !empty($this->_name_page)) {
         $between .= ' - ';
      }
      $retour .= '   <title>'.$this->_text_as_html_short($this->_name_room).$between.$this->_text_as_html_short($this->_name_page).'</title>'.LF;
      if ( !empty($this->_current_user) and ($this->_current_user->getUserID() == 'guest' and $this->_current_user->isGuest()) ) {
         $views = array_merge($this->_views, $this->_views_left, $this->_views_right, $this->_views_overlay);
         if ( isset($this->_form_view) ) {
            $views[] = $this->_form_view;
         }
         $view = reset($views);
         while ($view) {
            $retour .= $view->getInfoForHeaderAsHTML();
            $view = next($views);
         }
         unset($views);
         unset($view);
         $session = $this->_environment->getSession();
         $left_menue_status = $session->getValue('left_menue_status');
         $cs_modus = $this->_environment->getValueOfParameter('cs_modus');
         if ( $left_menue_status != 'disapear'
              and $this->_environment->getCurrentModule() != 'help'
              and !$this->_environment->inServer()
              and empty($cs_modus)
            ) {

            //Set Focus to login field
            $retour .= '   <script type="text/javascript">'.LF;
            $retour .= '      <!--'.LF;
            $retour .= '         function setfocus() {';
            // jQuery
            //$retour .= 'document.login.user_id.focus(); ';
            $retour .= 'jQuery("input[name=\'user_id\'], login").focus();';
            // jQuery
            $retour .= '}'.LF;
            $retour .= '      -->'.LF;
            $retour .= '   </script>'.LF;
            $this->_focus_onload = true;
         }
      } else {
         $views = array_merge($this->_views, $this->_views_left, $this->_views_right, $this->_views_overlay);
         if ( isset($this->_form_view) ) {
            $views[] = $this->_form_view;
         }
         $view = reset($views);
         while ($view) {
            $retour .= $view->getInfoForHeaderAsHTML();
            $view = next($views);
         }
         unset($views);
         unset($view);
      }
      
      // plugins
      $retour .= LF.'   <!-- PLUGINS BEGIN -->'.LF;
      include_once('functions/misc_functions.php');
      $retour .= plugin_hook_output_all('getInfosForHeaderAsHTML',array(),LF).LF;
      $retour .= '   <!-- PLUGINS END -->'.LF.LF;
      
      // plugins -END

      $retour .= '</head>'.LF;
      return $retour;
   }

   function _getFooterAsHTML () {
      $retour  = '';
      $retour .= LF.'<!-- BEGIN COMMSY FOOTER -->'.LF;

      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         $plugin_text = '';
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if (method_exists($plugin_class,'getFooterAsHTML')) {
               $plugin_text .= $plugin_class->getFooterAsHTML();
            }
            unset($plugin_class);
         }
         if ( !empty($plugin_text) ) {
            if ( $this->_environment->inPortal() ) {
               $retour .= '<div style="padding-left: 20px;">'.LF;
            } elseif ( $this->_environment->inServer() ) {
               $retour .= '<div style="padding-left: 200px;">'.LF;
            }
            $retour .= $plugin_text;
            if ( $this->_environment->inPortal()
                 or $this->_environment->inServer()
               ) {
               $retour .= '</div>'.LF;
            }
         }
      }
      $retour .= LF.'<!-- END COMMSY FOOTER -->'.LF;

      // bug in IE7
      // script must be directly before body end tag
      $views = array_merge($this->_views, $this->_views_left, $this->_views_right, $this->_views_overlay);
      if ( isset($this->_form_view) ) {
         $views[] = $this->_form_view;
      }
      $view = reset($views);
      while ($view) {
         if ( method_exists($view,'getContentForBeforeBodyEndAsHTML') ) {
            $retour .= $view->getContentForBeforeBodyEndAsHTML();
         }
         $view = next($views);
      }
      unset($views);
      unset($view);
      
      // plugins
      $retour .= LF.'   <!-- PLUGINS BEGIN -->'.LF;
      include_once('functions/misc_functions.php');
      $retour .= plugin_hook_output_all('getInfosForBeforeBodyEndAsHTML',array(),LF,false).LF;
      $retour .= '   <!-- PLUGINS END -->'.LF.LF;
      
      return $retour;
   }
   // @segment-end 20236

   function asHTMLFirstPart () {
      $html = '';
      return $html;
   }
   function asHTMLSecondPart () {
      $html = '';
      return $html;
   }

   function _getCustomizedRoomListForCurrentUser(){
      $retour = array();
      $current_user = $this->_environment->getCurrentUserItem();
      $current_context_id = $this->_environment->getCurrentContextID();
      $own_room_item = $current_user->getOwnRoom();
      $temp_array = array();
      $temp_array['title'] = '----------------------------';
      $temp_array['item_id'] = '-1';
      $retour[] = $temp_array;
      $customized_room_list = $own_room_item->getCustomizedRoomList();
      if ( isset($customized_room_list) ) {
         $room_item = $customized_room_list->getFirst();
         while ($room_item) {
            $temp_array = array();
            if ( $room_item->isGrouproom() ) {
               $temp_array['title'] = '- '.$room_item->getTitle();
            } else {
               $temp_array['title'] = $room_item->getTitle();
            }
            $temp_array['item_id'] = $room_item->getItemID();
            if ($current_context_id == $temp_array['item_id']){
               $temp_array['selected'] = true;
            }
            $retour[] = $temp_array;
            $room_item = $customized_room_list->getNext();
         }
      }
      return $retour;
   }


   function _getAllOpenContextsForCurrentUser () {
      $current_user = $this->_environment->getCurrentUserItem();
      $own_room_item = $current_user->getOwnRoom();
      if ( isset($own_room_item) ) {
         $customized_room_array = $own_room_item->getCustomizedRoomIDArray();
      }
      if (isset($customized_room_array[0])){
        return $this->_getCustomizedRoomListForCurrentUser();
      }else{
      $this->translatorChangeToPortal();
      $selected = false;
      $selected_future = 0;
      $selected_future_pos = -1;
      $retour = array();
      $temp_array = array();
      $temp_array['item_id'] = -1;
      $temp_array['title'] = '';
      $retour[] = $temp_array;
      unset($temp_array);
      
      // archive
      if ( $this->_environment->isArchiveMode() ) {
         $temp_array = array();
         $temp_array['item_id'] = -1;
         $temp_array['title'] = $this->_translator->getMessage('PORTAL_ARCHIVED_ROOMS');
         $retour[] = $temp_array;
         unset($temp_array);
      }
      // archive
      
      $temp_array = array();
      $community_list = $current_user->getRelatedCommunityList();
      if ( $community_list->isNotEmpty() ) {
         $temp_array['item_id'] = -1;
         $temp_array['title'] = $this->_translator->getMessage('MYAREA_COMMUNITY_INDEX').'';
         $retour[] = $temp_array;
         unset($temp_array);
         $community_item = $community_list->getFirst();
         while ($community_item) {
            $temp_array = array();
            $temp_array['item_id'] = $community_item->getItemID();
            $title = $community_item->getTitle();
            $temp_array['title'] = $title;
            if ( $community_item->getItemID() == $this->_environment->getCurrentContextID()
                 and !$selected
               ) {
               $temp_array['selected'] = true;
               $selected = true;
            }
            $retour[] = $temp_array;
            unset($temp_array);
            unset($community_item);
            $community_item = $community_list->getNext();
         }
         $temp_array = array();
         $temp_array['item_id'] = -1;
         $temp_array['title'] = '';
         $retour[] = $temp_array;
         unset($community_list);
      }
      $portal_item = $this->_environment->getCurrentPortalItem();
      if ($portal_item->showTime()) {
         $project_list = $current_user->getRelatedProjectListSortByTimeForMyArea();
#         if ( $portal_item->showGrouproomConfig() ) {
            include_once('classes/cs_list.php');
            $new_project_list = new cs_list();
            $grouproom_array = array();
            $project_grouproom_array = array();
            if ( $project_list->isNotEmpty() ) {
               $room_item = $project_list->getFirst();
               while ($room_item) {
                  if ( $room_item->isA(CS_GROUPROOM_TYPE) ) {
                     $grouproom_array[$room_item->getItemID()] = $room_item->getTitle();
                     $linked_project_item_id = $room_item->getLinkedProjectItemID();
                     $project_grouproom_array[$linked_project_item_id][] = $room_item->getItemID();
                  } else {
                     $new_project_list->add($room_item);
                  }
                  unset($room_item);
                  $room_item = $project_list->getNext();
               }
               unset($project_list);
               $project_list = $new_project_list;
               unset($new_project_list);
            }
#         }
         $future = true;
         $future_array = array();
         $no_time = false;
         $no_time_array = array();
         $current_time = $portal_item->getTitleOfCurrentTime();
         $with_title = false;
      } else {
         $project_list = $current_user->getRelatedProjectListForMyArea();
#         if ( $portal_item->showGrouproomConfig() ) {
            include_once('classes/cs_list.php');
            $new_project_list = new cs_list();
            $grouproom_array = array();
            $project_grouproom_array = array();
            if ( $project_list->isNotEmpty() ) {
               $room_item = $project_list->getFirst();
               while ($room_item) {
                  if ( $room_item->isA(CS_GROUPROOM_TYPE) ) {
                     $grouproom_array[$room_item->getItemID()] = $room_item->getTitle();
                     $linked_project_item_id = $room_item->getLinkedProjectItemID();
                     $project_grouproom_array[$linked_project_item_id][] = $room_item->getItemID();
                  } else {
                     $new_project_list->add($room_item);
                  }
                  unset($room_item);
                  $room_item = $project_list->getNext();
               }
               unset($project_list);
               $project_list = $new_project_list;
               unset($new_project_list);
            }
#         }
      }
      unset($current_user);
      if ( $project_list->isNotEmpty() ) {
         $temp_array['item_id'] = -1;
         $temp_array['title'] = $this->_translator->getMessage('MYAREA_PROJECT_INDEX').'';
         $retour[] = $temp_array;
         unset($temp_array);
         $project_item = $project_list->getFirst();
         while ($project_item) {
            $temp_array = array();
            if ( $project_item->isA(CS_PROJECT_TYPE)
               ) {
               $temp_array['item_id'] = $project_item->getItemID();
               $title = $project_item->getTitle();
               $temp_array['title'] = $title;
               if ( $project_item->getItemID() == $this->_environment->getCurrentContextID()
                    and ( !$selected
                          or $selected_future == $project_item->getItemID()
                        )
                  ) {
                  $temp_array['selected'] = true;
                  if ( !empty($selected_future)
                       and $selected_future != 0
                       and $selected_future_pos != -1
                     ) {
                     $selected_future = 0;
                     unset($future_array[$selected_future_pos]['selected']);
                  }
                  $selected = true;
               }

               // grouprooms
#               if ( $portal_item->showGrouproomConfig() ) {
                  if ( isset($project_grouproom_array[$project_item->getItemID()]) and !empty($project_grouproom_array[$project_item->getItemID()]) and $project_item->isGrouproomActive()) {
                     $group_result_array = array();
                     $project_grouproom_array[$project_item->getItemID()]= array_unique($project_grouproom_array[$project_item->getItemID()]);
                     foreach ($project_grouproom_array[$project_item->getItemID()] as $value) {
                        $group_temp_array = array();
                        $group_temp_array['item_id'] = $value;
                        $group_temp_array['title'] = '- '.$grouproom_array[$value];
                        if ( $value == $this->_environment->getCurrentContextID()
                             and ( !$selected
                                   or $selected_future == $value
                                 )
                           ) {
                           $group_temp_array['selected'] = true;
                           $selected = true;
                           if ( !empty($selected_future)
                                and $selected_future != 0
                                and $selected_future_pos != -1
                              ) {
                              $selected_future = 0;
                              unset($future_array[$selected_future_pos]['selected']);
                           }
                        }
                        $group_result_array[] = $group_temp_array;
                        unset($group_temp_array);
                     }
                  }
#               }
            } else {
               $with_title = true;
               $temp_array['item_id'] = -2;
               $title = $project_item->getTitle();
               if (!empty($title) and $title != 'COMMON_NOT_LINKED') {
                  $temp_array['title'] = $this->_translator->getTimeMessage($title);
               } else {
                  $temp_array['title'] = $this->_translator->getMessage('COMMON_NOT_LINKED');
                  $no_time = true;
               }
               if (!empty($title) and $title == $current_time) {
               // if (!empty($title) and !empty($current_time) and $title == $current_time) {
                  $future = false;
               }
            }
            if ($portal_item->showTime()) {
               if ($no_time) {
                  $no_time_array[] = $temp_array;
                  if ( isset($group_result_array) and !empty($group_result_array) ) {
                     $no_time_array = array_merge($no_time_array,$group_result_array);
                     unset($group_result_array);
                  }
               } elseif ($future) {
                  if ($temp_array['item_id'] != -2) {
                     $future_array[] = $temp_array;
                     if ( !empty($temp_array['selected']) and $temp_array['selected'] ) {
                        $selected_future = $temp_array['item_id'];
                        $selected_future_pos = count($future_array)-1;
                     }
                     if ( isset($group_result_array) and !empty($group_result_array) ) {
                         $future_array = array_merge($future_array,$group_result_array);
                         unset($group_result_array);
                     }
                  }
               } else {
                  $retour[] = $temp_array;
                  if ( isset($group_result_array) and !empty($group_result_array) ) {
                      $retour = array_merge($retour,$group_result_array);
                      unset($group_result_array);
                  }
               }
            } else {
               $retour[] = $temp_array;
               if ( isset($group_result_array) and !empty($group_result_array) ) {
                    $retour = array_merge($retour,$group_result_array);
                  unset($group_result_array);
               }
            }
            unset($temp_array);
            unset($project_item);
            $project_item = $project_list->getNext();
         }
         unset($project_list);
   if ($portal_item->showTime()) {

      // special case, if no room is linked to a time pulse
      if (isset($with_title) and !$with_title) {
         $temp_array = array();
         $temp_array['item_id'] = -2;
         $temp_array['title'] = $this->_translator->getMessage('COMMON_NOT_LINKED');
         $retour[] = $temp_array;
         unset($temp_array);
         $retour = array_merge($retour,$future_array);
         $future_array = array();
      }

      if (!empty($future_array)) {
         $future_array2 = array();
         $future_array3 = array();
         foreach ($future_array as $element) {
            if ( !in_array($element['item_id'],$future_array3) ) {
                     $future_array3[] = $element['item_id'];
                     $future_array2[] = $element;
            }
         }
         $future_array = $future_array2;
         unset($future_array2);
         unset($future_array3);
         $temp_array = array();
         $temp_array['title'] = $this->_translator->getMessage('COMMON_IN_FUTURE');
         $temp_array['item_id'] = -2;
         $future_array_begin = array();
         $future_array_begin[] = $temp_array;
         $future_array = array_merge($future_array_begin,$future_array);
         unset($temp_array);
         $retour = array_merge($retour,$future_array);
      }

      if (!empty($no_time_array)) {
         $retour = array_merge($retour,$no_time_array);
      }
         }
      }
      unset($portal_item);
      $this->translatorChangeToCurrentContext();

      // archive - BEGIN
      if ( !$this->_toggle_archive_mode ) {
         $this->_toggle_archive_mode = true;
         if ( $this->_environment->isArchiveMode() ) {
            $this->_environment->deactivateArchiveMode();
            $retour2 = $this->_getAllOpenContextsForCurrentUser();
            if ( !empty($retour2) ) {
               $retour = array_merge($retour2,$retour);
            }
            $this->_environment->activateArchiveMode();
         } else {
            $this->_environment->activateArchiveMode();
            $retour2 = $this->_getAllOpenContextsForCurrentUser();
            if ( !empty($retour2) ) {
               $retour = array_merge($retour,$retour2);
            }
            $this->_environment->deactivateArchiveMode();
         }
      }
      // archive - END
      
      return $retour;
      }
   }

   function _getUserPersonalAreaAsHTML () {
      $first_time = '';
      $retour  = '';
      $retour .= '   <form style="margin:0px; padding:0px;" method="post" action="'.curl($this->_environment->getCurrentContextID(),'room','change','').'" name="room_change">'.LF;
      $retour .= '         <select size="1" style="font-size:10pt; width:12.6em;" name="room_id" onChange="javascript:document.room_change.submit()">'.LF;
      $context_array = array();
      $context_array = $this->_getAllOpenContextsForCurrentUser();
      $current_portal = $this->_environment->getCurrentPortalItem();
      if ( !$this->_environment->inServer() ) {
         $title = $this->_environment->getCurrentPortalItem()->getTitle();
         $additional = '';
         if ($this->_environment->inPortal()){
            $additional = 'selected="selected"';
         }
         $retour .= '            <option value="'.$this->_environment->getCurrentPortalID().'" '.$additional.'>'.$this->_environment->getCurrentPortalItem()->getTitle().'</option>'.LF;

         $current_portal_item = $this->_environment->getCurrentPortalItem();
         if ( $current_portal_item->showAllwaysPrivateRoomLink() ) {
            $link_active = true;
         } else {
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( $current_user_item->isRoomMember() ) {
               $link_active = true;
            } else {
               $link_active = false;
            }
            unset($current_user_item);
         }
         unset($current_portal_item);

         if ( $link_active ) {
            $retour .= '            <option value="-1" class="disabled" disabled="disabled">------------------------------------</option>'.LF;
            $additional = '';
            $user = $this->_environment->getCurrentUser();
            $private_room_manager = $this->_environment->getPrivateRoomManager();
            $own_room = $private_room_manager->getRelatedOwnRoomForUser($user,$this->_environment->getCurrentPortalID());
            if ( isset($own_room) ) {
               $own_cid = $own_room->getItemID();
               $additional = '';
               if ($own_room->getItemID() == $this->_environment->getCurrentContextID()) {
                  $additional = ' selected="selected"';
               }
               $retour .= '            <option value="'.$own_cid.'"'.$additional.'>'.$this->_translator->getMessage('COMMON_PRIVATEROOM').'</option>'.LF;
            }
            unset($own_room);
            unset($private_room_manager);
         }
      }

      foreach ($context_array as $con) {
         $title = $this->_text_as_html_short($con['title']);
         $additional = '';
         if (isset($con['selected']) and $con['selected']) {
            $additional = ' selected="selected"';
         }
         if ($con['item_id'] == -1) {
            $additional = ' class="disabled" disabled="disabled"';
            if (!empty($con['title'])) {
               $title = '----'.$this->_text_as_html_short($con['title']).'----';
            } else {
               $title = '&nbsp;';
            }
         }
         if ($con['item_id'] == -2) {
            $additional = ' class="disabled" disabled="disabled" style="font-style:italic;"';
            if (!empty($con['title'])) {
               $title = $this->_text_as_html_short($con['title']);
            } else {
               $title = '&nbsp;';
            }
            $con['item_id'] = -1;
            if ($first_time) {
               $first_time = false;
            } else {
               $retour .= '            <option value="'.$con['item_id'].'"'.$additional.'>&nbsp;</option>'.LF;
            }
         }
         $retour .= '            <option value="'.$con['item_id'].'"'.$additional.'>'.$title.'</option>'.LF;
      }

      if (!$this->_current_user->isUser() and $this->_current_user->getUserID() != "guest") {
         $context = $this->_environment->getCurrentContextItem();
         if (!empty($context_array)) {
            $retour .= '            <option value="-1" class="disabled" disabled="disabled">&nbsp;</option>'.LF;
         }
         $retour .= '            <option value="-1" class="disabled" disabled="disabled">----'.$this->_translator->getMessage('MYAREA_CONTEXT_GUEST_IN').'----</option>'.LF;
         $retour .= '            <option value="'.$context->getItemID().'" selected="selected">'.$context->getTitle().'</option>'."\n";
      }
      $retour .= '         </select>'.LF;
      $retour .= '         <noscript><input type="submit" style="margin-top:3px; font-size:10pt; width:12.6em;" name="room_change" value="'.$this->_translator->getMessage('COMMON_GO_BUTTON').'"/></noscript>'.LF;
      $retour .= '   </form>'.LF;
      unset($context_array);
      return $retour;
   }

   // @segment-begin 78866  _getHeaderAsHTML_()
   /** get the header as HTML
    * this method returns the commsy header as HTML - internal, do not use
    *
    * @return string header as HTML
    */
   function _getHeaderAsHTML () {
      // new header
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      unset($session);
      $context_item = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = LF.'<!-- BEGIN HEADER -->'.LF;
      $html .= '<a name="top"></a>'.LF;
      $html .='<table style="width:100%; padding-top:0px; margin:0px;" summary="Layout">'.LF;
      $html .='<tr>'.LF;
      $length = 0;
      // title
      if ($context_item->showTitle() and !$this->_environment->inPortal()) {
         if (!$context_item->isPrivateRoom()){
            if ( !$this->_environment->inPortal() ){
               $length = mb_strlen($this->_text_as_html_short($this->_name_room));
               $html_text = $this->_text_as_html_short($this->_name_room);
            } else {
               $current_portal = $this->_environment->getCurrentPortalItem();
               $html_text = $this->_text_as_html_short($current_portal->getTitle());
               unset($current_portal);
            }
         } elseif ( $context_item->isPrivateRoom() and !$current_user->isGuest() ) {
            $html_text = $this->_text_as_html_short($context_item->getTitle());
            $length = mb_strlen($html_text);
         }
      } else {
         $html_text = '&nbsp;';
      }
      $font_size = '28';
      if ($length > 20){
         $font_size = '26';
      }
      if ($length > 25){
         $font_size = '24';
      }
      if ($length > 30){
         $font_size = '22';
      }
      if ($length > 40){
         $font_size = '20';
      }
      $html .='<td style="width: 85%; vertical-align:bottom; padding-left:5px; padding-bottom:0px; padding-bottom:0px; font-family: verdana, arial, Nimbus Sans L, sans-serif; font-size: '.$font_size.'px; font-weight: bold; ">';
      $html .= '<span class="room_title">'.$html_text.'</span>';
      $html .= '</td>';

      // logo
      $html .=       '<td rowspan="2" style="width: 15%; vertical-align:bottom; padding-top:5px; padding-bottom:0px; padding-right:5px; text-align:right;">';
      if ( !(isset($_GET['mode']) and $_GET['mode']=='print') ) {
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
               $html .= '     <img class="logo" style="height:48px;" src="'.$curl.'" alt="'.$this->_translator->getMessage('COMMON_LOGO').'" border="0"/>';
            } elseif( $context_item->isPrivateRoom() and !$current_user->isGuest() ){
               $picture = $current_user->getPicture();
               if ( !empty($picture) ) {
                  $params = array();
                  $params['picture'] = $picture;
                  $curl = curl($this->_environment->getCurrentContextID(),
                         'picture', 'getfile', $params,'');
                  unset($params);
                  $html .= '<img class="logo" style="height:48px;" src="'.$curl.'" alt="'.$this->_translator->getMessage('COMMON_LOGO').'" border="0"/>'.LF;
               }
            }
         }
      }
      unset($current_user);
      unset($context_item);
      $html .='</td>';
      $html .='</tr>';
      $html .='<tr>'.LF;
      if ( $this->_without_left_menue or (isset($_GET['mode']) and $_GET['mode']=='print') ) {
         $html .=       '<td style="vertical-align:bottom;">&nbsp;';
         $html .='</td>'.LF;
   // do nothing
      } elseif ( $left_menue_status == 'disapear' ) {
         $html .=       '<td style="vertical-align:bottom;">';
         $params = $this->_environment->getCurrentParameterArray();
         $params['left_menue'] = 'apear';
         $html .= '<div style=" margin:0px; padding-left:5px;">'.LF;
         $html .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,'<span class="required">'.'> '.'</span>'.'<span class="fade-out-link" >'.$this->_translator->getMessage('COMMON_FADE_IN').'</span>', '', '', '', '');
         $html .= '</div>'.LF;
         unset($params);
         $html .='</td>'.LF;
      } else {
         $params = $this->_environment->getCurrentParameterArray();
         $params['left_menue'] = 'disapear';
         $html .=       '<td style="width:58.3em; vertical-align:bottom; padding-top:0px;">';
         $html .= '<div style="margin:0px; padding-top:0px; padding-left:5px;">'.LF;
         $html .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,'<span class="required">'.'< '.'</span>'.'<span class="fade-out-link">'.$this->_translator->getMessage('COMMON_FADE_OUT').'</span>', '', '', '', '');
         unset($params);
         $html .= '</div>'.LF;
         $html .='</td>'.LF;
      }
      $html .='</tr>'.LF;
      $html .='</table>';
      $html .= '<!-- END HEADER -->'.LF;
      return $html;
   }

   // @segment-end 78866



   // @segment-begin 52559  _getLogoAsHTML()
   function _getLogoAsHTML () {
      $html  = '';
      $html .= '<div class="logo" style="vertical-align:top; padding-top:5px;">'.LF;
      if ( !$this->_environment->inServer() ) {
         $current_portal = $this->_environment->getCurrentPortalItem();
         // logo
         $logo_filename = $current_portal->getLogoFilename();
         $disc_manager = $this->_environment->getDiscManager();
         $disc_manager->setContextID($current_portal->getItemID());
         if ( !empty($logo_filename) and $disc_manager->existsFile($logo_filename)) {
            $params = array();
            $params['picture'] = $current_portal->getLogoFilename();
            $curl = curl($current_portal->getItemID(), 'picture', 'getfile', $params,'');
            unset($params);
            $image = '<img style="width:12.8em; height:4em; padding-top:0px; padding-bottom:0px; padding-left:10px;" src="'.$curl.'" alt="'.$this->_translator->getMessage('COMMON_LOGO').'" border="0"/>';
            $html .= ahref_curl($current_portal->getItemID(),'home','index','',$image,'','','','','','','style="color:#000000"').LF;
         } else {
         // title
            $html .= '<h1 style="padding-bottom:15px; font-size:24px; padding-top:0px; margin-top:0px;">'.LF;
            $link_text = $current_portal->getTitle();
            $html .= ahref_curl($current_portal->getItemID(),'home','index','',$link_text,'','','','','','','style="color:#000000; text-decoration:none; line-height: 22px;"').''.LF;
            unset($link_text);
            $html .= '</h1>'.LF;
         }
         unset($current_portal);
         unset($disc_manager);
      } else {
         $html .= '<a href="http://www.commsy.net"><img style="width:12.8em; height:4em; padding-top:0px; padding-bottom:0px; padding-left:10px;" class="portal_logo" src="images/commsy-logo-171x53.gif" alt="commsy logo"/></a>'.LF;
      }
      $html .= '</div>'.LF;
      return $html;
   }
   // @segment-end 52559


   function _getUserCopiesAsHTML(){
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
      $rubric_copy_array = array(CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE,
                                 CS_MATERIAL_TYPE,     CS_TODO_TYPE                      );
      foreach ($rubric_copy_array as $rubric){
         $id_array = $session->getValue($rubric.'_clipboard');
         $count = count($id_array);
         if ($count > 0){
             $temp = mb_strtoupper($rubric, 'UTF-8');
             $theRubricMessage = "";
             switch( $temp )
             {
                case 'ANNOUNCEMENT':
                   $theRubricMessage = $this->_translator->getMessage('COMMON_ANNOUNCEMENT_INDEX');  // Ankündigungen
                   break;
                case 'DATE':
                   $theRubricMessage = $this->_translator->getMessage('COMMON_DATE_INDEX');          // Termine
                   break;
                case 'DISCUSSION':
                   $theRubricMessage = $this->_translator->getMessage('COMMON_DISCUSSION_INDEX');    // Diskussionen
                   break;
                case 'MATERIAL':
                   $theRubricMessage = $this->_translator->getMessage('COMMON_MATERIAL_INDEX');      // Materialien
                   break;
                case 'TODO':
                   $theRubricMessage = $this->_translator->getMessage('COMMON_TODO_INDEX');          // Aufgaben
                   break;
                default:
                   $theRubricMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR' . ' cs_page_view(941) ');
                   break;
             }
             if (in_array($rubric,$modules) and $context_item->isOpen() ){
                if ($count== 1){
                   $link_text = $theRubricMessage.' ('.$count.' '.$this->_translator->getMessage('MYAREA_CLIPBOARD_HEADER_1').')';
                }else{
                   $link_text = $theRubricMessage.' ('.$count.' '.$this->_translator->getMessage('MYAREA_CLIPBOARD_HEADER').')';
                }
                $params = array();
                $html_array[$theRubricMessage] ='> '.ahref_curl($context_item->getItemID(),$rubric,'clipboard_index',$params,$link_text,'','','','','','','style="color:#800000"').BRLF;
             } else {
                if ($count== 1){
                   $html_array[$theRubricMessage] = '<span class="disabled">> '.$theRubricMessage.' ('.$count.' '.$this->_translator->getMessage('MYAREA_CLIPBOARD_HEADER_1').')</span>'.BRLF;
                } else {
                   $html_array[$theRubricMessage] = '<span class="disabled">> '.$theRubricMessage.' ('.$count.' '.$this->_translator->getMessage('MYAREA_CLIPBOARD_HEADER').')</span>'.BRLF;
                }
             }
         }
      }
      unset($rubric_copy_array);
      unset($context_item);
      if ( empty($html_array) ){
         $html .= '<span class="disabled">> '.$this->_translator->getMessage('COMMON_NO_COPIES').'</span>'.BRLF;
      }else{
         ksort($html_array);
         foreach($html_array as $html_text){
            $html .= $html_text;
         }
      }
      unset($html_array);
      return $html;
   }

   function getMyAreaAsHTML() {
   	$html  = LF;
   	if ( !$this->_environment->inPortal() || $this->_current_user->isGuest() ) {
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
		
		      
		      $html .= '<div class="myarea_frame">'.LF;
		      $html .= '<div class="myarea_headline">'.LF;
		      $html .= '<div class="myarea_headline_title">'.LF;
		      if ( $this->_with_personal_area) {
		         if ( !empty($this->_current_user) and ($this->_current_user->getUserID() == 'guest' and $this->_current_user->isGuest()) and !$this->_environment->inServer() ) {
		            $html .= $this->_translator->getMessage('MYAREA_LOGIN_NOT_LOGGED_IN');
		            // @segment-end 77327
		            // @segment-begin 69973 no-cs_modus/user=guest:if-logged-in-as-guest
		         } elseif ( !($this->_environment->inServer() and $this->_current_user->isGuest()) ) {
		               $params = array();
		               $params['iid'] = $this->_current_user->getItemID();
		               $fullname = $this->_current_user->getFullname();
		
		          // @segment-end 70706
		          // @segment-begin 23516 no-cs_modus/user-status><0:display-user_name,font-size-depends-on-length
		               $length = mb_strlen($fullname);
		               if ($length < 20) {
		                  $html .= $fullname;
		               } else {
		                $html .= $fullname;
		               }
		         }
		      }
		      $html .= '</div>'.LF;
		      $html .= '</div>'.LF;
		      if ( $current_context->isOpenForGuests() and !$this->_current_user->isUser()
		           and !$this->_environment->inServer()
		           and !$this->_environment->inPortal()
		         ) {
		         $html .= '<div class="myarea_content" style="padding-bottom:5px; margin-bottom:0px; font-weight:bold;">'.LF;
		         $html .= $this->_translator->getMessage('MYAREA_LOGIN_AS_GUEST');
		         $html .= '</div >'.LF;
		      }
		
		      if ( $this->_with_personal_area and empty($cs_mod)) {
		         if ( !empty($this->_current_user) and ($this->_current_user->getUserID() == 'guest' and $this->_current_user->isGuest()) and !$this->_environment->inServer() ) {
		            if ( $current_context->isOpenForGuests() and !$this->_current_user->isUser()
		                 and !$this->_environment->inServer()
		                 and !$this->_environment->inPortal()
		               ) {
		            }
		            $html .= '<div class="myarea_content">'.LF;
		            if ($this->_environment->inPortal() or $this->_environment->inServer()) {
		               $context_id = $this->_environment->getCurrentContextID();
		            } else {
		               $context_id = $this->_environment->getCurrentPortalID();
		            }
		            $html .= '<form style="margin:0px; padding:0px;" method="post" action="'.curl($context_id,'context','login','').'" name="login">'.LF;
		            $error_box = $this->getMyAreaErrorBox();
		            if ( isset($error_box) ){
		               $error_box->setWidth('100%');
		               $html .= $error_box->asHTML();
		            }
		            unset($context_id);
		            unset($error_box);
		
		            // auth source
		            $insert_auth_source_selectbox = false;
		            if ( $current_portal->showAuthAtLogin() ) {
		               $auth_source_list = $current_portal->getAuthSourceListEnabled();
		               if ( isset($auth_source_list) and !$auth_source_list->isEmpty() ) {
		                  if ($auth_source_list->getCount() == 1) {
		                     $auth_source_item = $auth_source_list->getFirst();
		                     $html .= '<input type="hidden" name="auth_source" value="'.$auth_source_item->getItemID().'"/>'.LF;
		                  } else {
		                     $insert_auth_source_selectbox = true;
		                  }
		               }
		            }
		
		            // login redirect
		            $session_item = $this->_environment->getSessionItem();
		            if ($session_item->issetValue('login_redirect')) {
		                $redirectUrl = $session_item->getValue('login_redirect');
		                $html .= '<input type="hidden" name="login_redirect" value="' . $redirectUrl . '"/>' . LF;
		                $session_item->unsetValue('login_redirect');
		            }
		
		            // login form
		            $html .= '<table summary="Layout">'.LF;
		            $html .= '<tr><td style="padding:0px;margin:0px;">'.LF;
		            $html .= $this->_translator->getMessage('MYAREA_ACCOUNT').':'.LF.'</td><td>';
		            $html .= '<input type="text" name="user_id" size="100" style="font-size:10pt; width:6.2em;" tabindex="1"/>'.LF;
		            $html .= '</td></tr>'.LF.'<tr><td>'.LF;
		            $html .= $this->_translator->getMessage('MYAREA_PASSWORD').':'.'</td>'.LF.'<td>';
		            $html .= '<input type="password" name="password" size="10" style="font-size:10pt; width:6.2em;" tabindex="2"/>'.'</td></tr>'.LF;
		            if ( $insert_auth_source_selectbox ) {
		               $html .= '<tr><td style="padding:0px;margin:0px;">'.LF;
		               $html .= $this->_translator->getMessage('MYAREA_USER_AUTH_SOURCE_SHORT').':'.LF.'</td><td>';//Quelle?
		               // selectbox
		               $width_auth_selectbox = 6.5;
		               if ( mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8') == 'msie' ) {
		                  $width_auth_selectbox = 6.7;
		               }
		               $html .= '<select size="1" style="font-size:10pt; width:'.$width_auth_selectbox.'em;" name="auth_source" tabindex="3">'.LF;
		               $auth_source_item = $auth_source_list->getFirst();
		               $auth_source_selected = false;
		               //Shibboleth
		               $auth_shibboleth_default = false;
		               while ( $auth_source_item ) {
		                  //Shibboleth
// 		                  if($auth_source_item->getItemID() == $current_portal->getAuthDefault()){
		                  	if($auth_source_item->getSourceType() == 'Shibboleth'){
		                  		$sessionInitatorUrl = $auth_source_item->getShibbolethSessionInitiator();
		                  		$auth_shibboleth_default = true;
		                  		$auth_source_item = $auth_source_list->getNext();
		                  		continue;
		                  	}
// 		                  }
		               	  $html .= '   <option value="'.$auth_source_item->getItemID().'"';
		                  if ( !$auth_source_selected ) {
		                     if ( isset($_GET['auth_source'])
		                          and !empty($_GET['auth_source'])
		                          and $auth_source_item->getItemID() == $_GET['auth_source']) {
		                        $html .= ' selected="selected"';
		                        $auth_source_selected = true;
		                     } elseif ( $auth_source_item->getItemID() == $current_portal->getAuthDefault() ) {
		                        $html .= ' selected="selected"';
		                     }
		                  }
		                  $html .= '>'.$auth_source_item->getTitle().'</option>'.LF;
		                  $auth_source_item = $auth_source_list->getNext();
		               }
		               $html .= '</select>'.LF;
		               //Shibboleth
		               if($auth_shibboleth_default){
		               	 $html .= '<a href="'.$sessionInitatorUrl.'">Login Shibboleth</a>';
		               }
		               $html .= '</td></tr>'.LF;
		            }
		            unset($auth_source_list);
		            $html .= '<tr>'.LF.'<td></td>'.LF.'<td>'.LF;
		            $html .= '<input type="submit" name="option" style="width: 6.6em;" value="'.$this->_translator->getMessage('MYAREA_LOGIN_BUTTON').'" tabindex="4"/>'.LF;
		            $html .= '</td></tr>'.LF;
		            $html .= '</table>'.LF;
		
		            if ( !$this->_environment->inServer() ) {
		               $params = array();
		               $params = $this->_environment->getCurrentParameterArray();
		
		               // auth source
		               $auth_source_list = $current_portal->getAuthSourceListEnabled();
		               $count_auth_source_list_add_account = 0;
		               if ( isset($auth_source_list) and !$auth_source_list->isEmpty() ) {
		                  $auth_source_item = $auth_source_list->getFirst();
		                  while ($auth_source_item) {
		                     $temp_array = array();
		                     if ( $auth_source_item->allowAddAccount() ) {
		                        $count_auth_source_list_add_account++;
		                     }
		                     $auth_source_item = $auth_source_list->getNext();
		                  }
		               }
		               unset($auth_source_list);
		
		               if ( $count_auth_source_list_add_account != 0 ) {
		                  $params['cs_modus'] = 'portalmember';
		                  $html .= '<span style="font-size:8pt;">> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$this->_translator->getMessage('MYAREA_LOGIN_ACCOUNT_WANT_LINK'),'','','','','','','style="color:#800000"').'</span>'.BRLF;
		               } else {
		                  $html .= '<span style="font-size:8pt;" class="disabled">> '.$this->_translator->getMessage('MYAREA_LOGIN_ACCOUNT_WANT_LINK').'</span>'.BRLF;
		               }
		               $params['cs_modus'] = 'account_forget';
		               $html .= '<span style="font-size:8pt;">> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$this->_translator->getMessage('MYAREA_LOGIN_ACCOUNT_FORGET_LINK'),'','','','','','','style="color:#800000"').'</span>'.BRLF;
		               $params['cs_modus'] = 'password_forget';
		               $html .= '<span style="font-size:8pt;">> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$this->_translator->getMessage('MYAREA_LOGIN_PASSWORD_FORGET_LINK'),'','','','','','','style="color:#800000"').'</span>'.BRLF;
		               unset($params);
		            }
		            $html .= LF;
		            $html .= '</form>'.LF;
		            $html .= '</div>'.LF;
		
		         } elseif ( !($this->_environment->inServer() and $this->_current_user->isGuest()) ) {
		            $params = array();
		
		            if (!$this->_environment->inServer()) {
		               $title = $this->_translator->getMessage('MYAREA_LOGIN_TO_OWN_ROOM');
		               $user = $this->_environment->getCurrentUser();
		               $current_user_item = $this->_environment->getCurrentUserItem();
		               if ( !$current_user_item->isRoot() ) {
		                  $html .= '<div class="myarea_section_title">'.$this->_translator->getMessage('MYAREA_MY_ACTUAL_ROOMS').'</div>'.LF;
		                  $html .= '<div class="myarea_content">'.LF;
		                  $html .= '<div style="padding-bottom:5px;">'.$this->_getUserPersonalAreaAsHTML().'</div>'.LF;
		               }else{
		                  $html .= '<div class="myarea_content">'.LF;
		               }
		               unset($current_user_item);
		               if ((!$user->isRoot() and $user->isUser()) or ($user->isGuest() and $user->getUserID() != 'guest')
		               ){
		                  $private_room_manager = $this->_environment->getPrivateRoomManager();
		                  $own_room = $private_room_manager->getRelatedOwnRoomForUser($user,$this->_environment->getCurrentPortalID());
		                  global $c_annonymous_account_array;
		                  if ( isset($own_room)
		                       and empty($c_annonymous_account_array[mb_strtolower($this->_current_user->getUserID(), 'UTF-8').'_'.$this->_current_user->getAuthSource()])
		                       and !$this->_current_user->isOnlyReadUser()
		                     ) {
		                     $current_portal_item = $this->_environment->getCurrentPortalItem();
		                     if ( $current_portal_item->showAllwaysPrivateRoomLink() ) {
		                        $link_active = true;
		                     } else {
		                        $current_user_item = $this->_environment->getCurrentUserItem();
		                        if ( $current_user_item->isRoomMember() ) {
		                           $link_active = true;
		                        } else {
		                           $link_active = false;
		                        }
		                        unset($current_user_item);
		                     }
		                     unset($current_portal_item);
		                     if ($link_active) {
		                        $html .= '<span> '.ahref_curl($own_room->getItemID(), 'home',
		                                         'index',
		                                         '',
		                                         '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;
		
		                        $html .= ahref_curl($own_room->getItemID(), 'home', 'index', '',$this->_translator->getMessage('MYAREA_LOGIN_TO_OWN_ROOM'),'','','','','','','style="color:#800000"').'</span>'.BRLF;
		                     } else {
		                        // disable private room
		                        $html .= '<span class="disabled"><img src="images/door_closed_small.gif" style="vertical-align: middle" alt="door close"/>'.LF;
		                        $html .= $this->_translator->getMessage('MYAREA_LOGIN_TO_OWN_ROOM').'</span>'.BRLF;
		                     }
		                  }
		                  unset($own_room);
		               }
		               $html .= '<span> '.ahref_curl($this->_environment->getCurrentPortalID(), 'home',
		                                        'index',
		                                        '',
		                                        '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;
		
		               $html .= ahref_curl($this->_environment->getCurrentPortalID(), 'home', 'index', '',$this->_translator->getMessage('COMMON_PORTAL').' ('.$this->_translator->getMessage('MYAREA_LOGIN_TO_PORTAL_OVERVIEW').')','','','','','','','style="color:#800000"').'</span>'.LF;
		
		               if ( $this->_current_user->isRoot() ) {
		                  $html .= BR.'<span> '.ahref_curl($this->_environment->getServerID(), 'home',
		                                        'index',
		                                        '',
		                                        '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;
		
		                  $html .= ahref_curl($this->_environment->getServerID(), 'home', 'index', '',$this->_translator->getMessage('MYAREA_LOGIN_TO_ALL_PORTALS'),'','','','','','','style="color:#800000"').'</span>'.LF;
		               }
		            } else {
		               if ( $this->_current_user->isRoot() ) {
		                  $html .= '<div class="myarea_content">'.LF;
		                  $html .= '<span> '.ahref_curl($this->_environment->getServerID(), 'home',
		                                        'index',
		                                        '',
		                                        '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;
		
		                  $html .= ahref_curl($this->_environment->getServerID(), 'home', 'index', '',$this->_translator->getMessage('MYAREA_LOGIN_TO_ALL_PORTALS'),'','','','','','','style="color:#800000"').'</span>'.LF;
		                  $html .= '</div>'.LF;
		               }
		            }
		
		            unset($current_context);
		            unset($current_portal);
		            if (!$this->_current_user->isRoot() and !$this->_environment->inServer()) {
		               $html .= '</div>'.LF;
		            }
		            if (!$this->_environment->inServer() and !$this->_current_user->isRoot() ) {
		               $html .= '<div class="myarea_section_title">'.$this->_translator->getMessage('MYAREA_MY_COPIES').'</div>'.LF;
		               $html .= '<div class="myarea_content">'.LF;
		               $html .= $this->_getUserCopiesAsHTML();
		               $html .= '</div>'.LF;
		            }
		
		            if (!$this->_environment->inServer() ) {
		               global $c_annonymous_account_array;
		               if ( !$this->_current_user->isRoot()
		                    and empty($c_annonymous_account_array[mb_strtolower($this->_current_user->getUserID(), 'UTF-8').'_'.$this->_current_user->getAuthSource()])
		                    and !$this->_current_user->isOnlyReadUser()
		                  ) {
		                  $html .= '<div class="myarea_section_title">'.$this->_translator->getMessage('MYAREA_MY_PROFILE').'</div>'.LF;
		                  $html .= '<div class="myarea_content" style="padding-bottom:5px;">'.LF;
		
		                  // new: commsy 7 profile on portal
		                  $params = array();
		                  $params = $this->_environment->getCurrentParameterArray();
		                  $params['uid'] = $this->_current_user->getItemID();
		                  $params['show_profile'] = 'yes';
		                  unset($params['is_saved']);
		                  unset($params['show_copies']);
		                  unset($params['profile_page']);
		                  global $c_annonymous_account_array;
		                  if ( empty($c_annonymous_account_array[mb_strtolower($this->_current_user->getUserID(), 'UTF-8').'_'.$this->_current_user->getAuthSource()])
		                       and !$this->_current_user->isOnlyReadUser()
		                     ) {
		                     $html .= '<span>> '.ahref_curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $params,$this->_translator->getMessage('MYAREA_ACCOUNT_PROFIL'),'','','','','','','style="color:#800000"').'</span>'.BRLF;
		                  } else {
		                     $html .= '<span class="disabled">> '.$this->_translator->getMessage('MYAREA_ACCOUNT_PROFIL').'</span>'.BRLF;
		                  }
		               } elseif ( !$this->_current_user->isRoot() ) {
		                  $html .= '<div>'.LF;
		               }
		            }
		            if ( !$this->_current_user->isRoot() ) {
		               global $c_annonymous_account_array;
		               if ( empty($c_annonymous_account_array[mb_strtolower($this->_current_user->getUserID(), 'UTF-8').'_'.$this->_current_user->getAuthSource()])
		                    and !$this->_current_user->isOnlyReadUser()
		                  ) {
		                  if ($this->_environment->inCommunityRoom() and !$this->_current_user->isUser()){
		                     $params['cs_modus'] = 'become_member';
		                     $html .= '<span>> '.ahref_curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $params,$this->_translator->getMessage('MYAREA_CONTEXT_JOIN'),'','','','','','','style="color:#800000"').'</span>'.BRLF;
		                  }
		                  if ($this->_environment->inProjectRoom() and !$this->_current_user->isUser()){
		                     $params['cs_modus'] = 'become_member';
		                     $html .= '<span>> '.ahref_curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $params,$this->_translator->getMessage('MYAREA_CONTEXT_JOIN'),'','','','','','','style="color:#800000"').'</span>'.BRLF;
		                  }
		                  // auth source
		                  $current_portal_item = $this->_environment->getCurrentPortalItem();
		                  if ( !isset($current_portal_item) ) {
		                     $current_portal_item = $this->_environment->getServerItem();
		                  }
		                  $current_auth_source_item = $current_portal_item->getAuthSource($this->_current_user->getAuthSource());
		                  unset($current_portal_item);
		               }
		            } else {
		               // auth source
		               if (!$this->_environment->inServer() ) {
		                  $html .= '</div>'.LF;
		               }
		               $html .= '<div class="myarea_section_title">'.$this->_translator->getMessage('MYAREA_MY_PROFILE').'</div>'.LF;
		               $html .= '<div class="myarea_content">'.LF;
		               $current_portal_item = $this->_environment->getCurrentPortalItem();
		               if ( !isset($current_portal_item) ) {
		                  $current_portal_item = $this->_environment->getServerItem();
		               }
		               $current_auth_source_item = $current_portal_item->getAuthSource($this->_current_user->getAuthSource());
		               unset($current_portal_item);
		               if ((isset($current_auth_source_item) and $current_auth_source_item->allowChangePassword()) or $this->_current_user->isRoot()) {
		                      $params = array();
		                      $params = $this->_environment->getCurrentParameterArray();
		                      $params['cs_modus'] = 'password_change';
		                      $html .= '<span>> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(), $params,$this->_translator->getMessage('MYAREA_PROFILE_EDIT'),'','','','','','','style="color:#800000"').'</span>'.BRLF;
		
		               } else {
		                   $html .= '<span class="disabled">> '.$this->_translator->getMessage('MYAREA_PROFILE_EDIT').'</span>'.BRLF;
		               }
		               unset($params['cs_modus']);
		               if ($this->_environment->inServer() ) {
		                  $html .= '</div>'.LF;
		               }
		            }
		            if (!$this->_environment->inServer() ) {
		               $html .= '</div>'.LF;
		            }
		            $params = $this->_environment->getCurrentParameterArray();
		            $html .= '<div class="myarea_content" style="padding-bottom:5px; padding-top:7px; text-align:right;">'.LF;
		            $html .= '<div style="float:right; text-align:right;">'.ahref_curl($this->_environment->getCurrentContextID(), 'context', 'logout', $params,$this->_translator->getMessage('MYAREA_LOGOUT'),'','','','','','','style="color:#800000"').'</div>'.LF;
		            $html .= '<div style="text-align:left;"> &nbsp;'.LF;
		            $html .= '</div>'.LF;
		            $html .= '</div>'.LF;
		
		         } elseif ($this->_environment->inServer()) {
		            $server_item = $this->_environment->getServerItem();
		            $portal_list = $server_item->getPortalList();
		            if ($portal_list->isEmpty()) {
		                  $html .= '<div class="myarea_title">'.$this->_translator->getMessage('MYAREA_LOGIN_NOT_LOGGED_IN').'</div>'.LF;
		                     $html .= '<div class="myarea_content">'.LF;
		                     $html .= '<form style="margin:0px; padding:0px;" method="post" action="'.curl($server_item->getItemID(),'context','login','').'" name="login">'.LF;
		                     $error_box = $this->getMyAreaErrorBox();
		                  if ( isset($error_box) ){
		                      $error_box->setWidth('100%');
		                    $html .= $error_box->asHTML();
		                  }
		
		
		                 unset($portal_list);
		                 unset($server_item);
		                 unset($error_box);
		                  $html .= '<table summary="Layout"><tr><td>'.LF;
		                  $html .=  $this->_translator->getMessage('COMMON_ACCOUNT').':'.LF.'</td><td>';
		                  $html .= '<input type="text" name="user_id" style="font-size: 10pt; width:6.2em;" tabindex="1"/>'.LF;
		                  $html .= '</td></tr>'.LF.'<tr><td>'.LF;
		                  $html .= $this->_translator->getMessage('COMMON_PASSWORD').':'.'</td>'.LF.'<td>';
		                  $html .= '<input type="password" name="password" style="font-size: 10pt; width:6.2em;" tabindex="2"/>'.'</td></tr>'.LF;
		                  $html .= '<tr>'.LF.'<td></td>'.LF.'<td>'.LF;
		                  $html .= '<input type="submit" name="option" style="font-size: 10pt; width:6.2em;" value="'.$this->_translator->getMessage('MYAREA_LOGIN_BUTTON').'"/>'.LF;
		                  $html .= '</td></tr>'.LF;
		                  $html .= '</table>'.LF;
		                  $html .= '</form>'.LF;
		                  $html .= '</div>'.LF;
		               }
		           }
		
		      // new account
		      }elseif ( !empty($cs_mod)
		         and ( $cs_mod == 'portalmember'
		         or $cs_mod == 'portalmember2'
		         )
		         ) {
		         if ( !empty($this->_current_user) and ($this->_current_user->getUserID() == 'guest' and $this->_current_user->isGuest()) ) {
		   } else {
		              $params = array();
		            $params['iid'] = $this->_current_user->getItemID();
		      if ( $this->_environment->inProjectRoom() or $this->_environment->inCommunityRoom()) {
		         $portal_user = $this->_environment->getPortalUserItem();
		         $fullname = $portal_user->getFullname();
		         unset($portal_user);
		      } else {
		         $fullname = $this->_current_user->getFullname();
		      }
		
		        }
		        $html .= '<div class="myarea_content" style="font-size:8pt;">'.LF;
		        if ( $cs_mod == 'portalmember' ) {
		             include_once('classes/cs_home_member_page.php');
		     $left_page = new cs_home_member_page($this->_environment);
		        } else {
		             include_once('classes/cs_home_member2_page.php');
		     $left_page = new cs_home_member2_page($this->_environment);
		        }
		        $html .= $left_page->execute();
		        unset($left_page);
		        $html .= '</div>'.LF;
		      }
		
		     // forget account
		      elseif (!empty($cs_mod) and $cs_mod == 'account_forget') {
		         if ( !empty($this->_current_user) and ($this->_current_user->getUserID() == 'guest' and $this->_current_user->isGuest()) ) {
		   } else {
		              $params = array();
		            $params['iid'] = $this->_current_user->getItemID();
		      if ( $this->_environment->inProjectRoom() or $this->_environment->inCommunityRoom()) {
		         $portal_user = $this->_environment->getPortalUserItem();
		         $fullname = $portal_user->getFullname();
		               unset($portal_user);
		      } else {
		         $fullname = $this->_current_user->getFullname();
		      }
		        }
		        $html .= '<div class="myarea_content" style="font-size:8pt;">'.LF;
		        include_once('classes/cs_account_forget_page.php');
		        $left_page = new cs_account_forget_page($this->_environment);
		        $html .= $left_page->execute();
		        unset($left_page);
		        $html .= '</div>'.LF;
		      }
		
		     // forget password
		      elseif (!empty($cs_mod) and $cs_mod == 'password_forget') {
		         if ( !empty($this->_current_user) and ($this->_current_user->getUserID() == 'guest' and $this->_current_user->isGuest()) ) {
		         } else {
		            $params = array();
		            $params['iid'] = $this->_current_user->getItemID();
		            if ( $this->_environment->inProjectRoom() or $this->_environment->inCommunityRoom()) {
		               $portal_user = $this->_environment->getPortalUserItem();
		               $fullname = $portal_user->getFullname();
		               unset($portal_user);
		            } else {
		               $fullname = $this->_current_user->getFullname();
		            }
		         }
		         $html .= '<div class="myarea_content" style="font-size:8pt;">'.LF;
		         include_once('classes/cs_password_forget_page.php');
		         $left_page = new cs_password_forget_page($this->_environment);
		         $html .= $left_page->execute();
		         unset($left_page);
		         $html .= '</div>'.LF;
		      }
		
		      // become member
		      elseif ( !empty($cs_mod) and $cs_mod == 'become_member' ) {
		         if ( !empty($this->_current_user) and ($this->_current_user->getUserID() == 'guest' and $this->_current_user->isGuest()) ) {
		         } else {
		            $params = array();
		            $params['iid'] = $this->_current_user->getItemID();
		            if ( $this->_environment->inProjectRoom() or $this->_environment->inCommunityRoom()) {
		               $portal_user = $this->_environment->getPortalUserItem();
		               $fullname = $portal_user->getFullname();
		               unset($portal_user);
		            } else {
		               $fullname = $this->_current_user->getFullname();
		            }
		        }
		        $html .= '<div class="myarea_content" style="font-size:8pt;">'.LF;
		        include_once('classes/cs_become_member_page.php');
		        $left_page = new cs_become_member_page($this->_environment);
		        $html .= $left_page->execute();
		        unset($left_page);
		        $html .= '</div>'.LF;
		      }
		      // @segment-end 90042
		      // @segment-begin 89418 end-of-my_area_box/down-corner-pictures
		      $html .= '</div>'.LF;
		      // @segment-end 89418
   		}
      
      $html .= $this->_getServerNewsAsHTML();

      $html .= $this->_getPluginInfosAsHTML();
      unset($current_user);
      return $html;
   }


   // @segment-begin 53077  _getSystemInfoAsHTML()
   function _getSystemInfoAsHTML(){
      $html ='';
      $html .='<div style="font-size:8pt; padding-left:5px; margin-top:3px;">'.LF;
      $html .= '<div class="footer" style="text-align:left; padding-left:0px; padding-right:0px; padding-top:0px; padding-bottom:10px;">'.LF;
      $html .= '<a href="http://tidy.sourceforge.net/" target="_top" title="HTML Tidy">'.'<img src="images/checked_by_tidy.gif" style="height:14px; vertical-align: bottom;" alt="Tidy"/></a>';
      $html .= '&nbsp;&nbsp;<a href="http://www.commsy.net" target="_top" title="'.$this->_translator->getMessage('COMMON_COMMSY_LINK_TITLE').'">CommSy '.getCommSyVersion().'</a>';
      $html .= '</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }

   // @segment-end 53077

   /***************CommSy 5.0 certified*****************/
   function _getServerNewsAsHTML() {
      $server_item = $this->_environment->getServerItem();
      $portal_item = $this->_environment->getCurrentPortalItem();
      $get_vars  = $this->_environment->getCurrentParameterArray();
      $post_vars = $this->_environment->getCurrentPostParameterArray();
      if (!empty($get_vars['cs_modus'])) {
         $cs_mod = $get_vars['cs_modus'];
      } elseif (!empty($post_vars['cs_modus'])) {
         $cs_mod = $post_vars['cs_modus'];
      } else {
         $cs_mod = '';
      }
      $html  = LF;
      if ( $server_item->showServerNews()
           and ( !isset($portal_item)
                 or ( isset($portal_item)
                      and $portal_item->isPortal()
                      and $portal_item->showNewsFromServer()
                    )
               )
         ) {
         $html .= BR.'<div class="myarea_frame">'.LF;
         $html .= '<div class="myarea_headline">'.LF;
         $html .= '<div class="myarea_headline_title">'.LF;
         $html .= $this->_translator->getMessage('COMMON_SERVER_NEWS');
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $link = $server_item->getServerNewsLink();
         if (!empty($link)) {
            $title = '<span style="font-weight: bold;"><a href="'.$this->_text_as_html_short($link).'" style="color:#800000" target="_blank">'.$this->_text_as_html_short($server_item->getServerNewsTitle()).'</a></span>'.LF;
         } else {
            $title = '<span style="font-weight: bold;">'.$this->_text_as_html_short($server_item->getServerNewsTitle()).'</span>'.LF;
         }
         $html .= '<div class="myarea_section_title">'.$title.'</div>';
         $html .= '<div class="myarea_content" style="position:relative; padding-bottom:0em;">'.LF;

         $text = $server_item->getServerNewsText();
         if (!empty($text)) {
            $html .= '<span style="font-size: 8pt;">'.$this->_cleanDataFromTextArea($text).'</span>'.LF;
         }
         if (!empty($link)) {
            $html .= '<span style="font-size: 8pt;"> [<a href="'.$this->_text_as_html_short($link).'" style="color:#800000" target="_blank">'.'mehr ...'.'</a>]</span>'.LF;
         }

         $html .= BRLF;
         $html .= BRLF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }

      if ( isset($portal_item)
           and $portal_item->isPortal()
           and $portal_item->showServerNews()
         ) {
         $html .= BR.'<div class="myarea_frame">'.LF;
         $html .= '<div class="myarea_headline">'.LF;
         $html .= '<div class="myarea_headline_title" style="font-size:10pt;">'.LF;
         $html .= $this->_translator->getMessage('COMMON_PORTAL_NEWS');
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $link = $portal_item->getServerNewsLink();
         if (!empty($link)) {
            $title = '<span style="font-weight: bold;"><a href="'.$this->_text_as_html_short($link).'" style="color:#800000" target="_blank">'.$this->_text_as_html_short($portal_item->getServerNewsTitle()).'</a></span>'.LF;
         } else {
            $title = '<span style="font-weight: bold;">'.$this->_text_as_html_short($portal_item->getServerNewsTitle()).'</span>'.LF;
         }
         $html .= '<div class="myarea_section_title">'.$title.'</div>';
         $html .= '<div class="myarea_content" style="position:relative; padding-bottom:0em;">'.LF;

         $text = $portal_item->getServerNewsText();
         if (!empty($text)) {
            $html .= '<span style="font-size: 8pt;">'.$this->_cleanDataFromTextArea($text).'</span>'.LF;
         }
         if (!empty($link)) {
            $html .= '<span style="font-size: 8pt;"> [<a href="'.$this->_text_as_html_short($link).'" style="color:#800000" target="_blank">'.'mehr ...'.'</a>]</span>'.LF;
         }

         $html .= BRLF;
         $html .= BRLF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }
      unset($portal_item);
      unset($server_item);
      return $html;
   }

   function _getPluginInfosAsHTML () {
      $retour = '';
      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if (method_exists($plugin_class,'getLeftMenuAsHTML')) {
               $html = $plugin_class->getLeftMenuAsHTML();
               if (isset($html)) {
                  $retour .= $html;
               }
            }
         }
      }
      return $retour;
   }

   function _getPluginInfosForBeforeContentAsHTML () {
      $retour = '';
      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if (method_exists($plugin_class,'getBeforeContentAsHTML')) {
               $html = $plugin_class->getBeforeContentAsHTML();
               if (isset($html)) {
                  $retour .= $html;
               }
            }
         }
      }
      return $retour;
   }

   function _getPluginInfosForAfterContentAsHTML () {
      $retour = '';
      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if (method_exists($plugin_class,'getAfterContentAsHTML')) {
               $html = $plugin_class->getAfterContentAsHTML();
               if (isset($html)) {
                  $retour .= $html;
               }
            }
         }
      }
      return $retour;
   }

   function _getLanguageConfigAsHTML () {

      $html  = LF;
      $html .= BR.'<div class="myarea_frame">'.LF;
      $html .= '<div class="myarea_headline">'.LF;
      $html .= '<div class="myarea_headline_title" style="font-size:10pt;">'.LF;
      $html .= $this->_translator->getMessage('MESSAGE_BOX_TITLE');
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div class="myarea_section_title">'.$this->_translator->getMessage('MESSAGE_TITLE_LINK').'</div>';
      $html .= '<div class="myarea_content" style="position:relative; padding-bottom:0em;">';
      $html .= '<span>> '.ahref_curl( $this->_environment->getCurrentContextID(),
                                      'language',
                                      'index',
                                      '',
                                      $this->_translator->getMessage('MESSAGE_INDEX_LINK'),
                                      '','','','','','','style="color:#800000"'
                                    ).'</span>'.BRLF;
      $html .= '<span>> '.ahref_curl( $this->_environment->getCurrentContextID(),
                                      'language',
                                      'edit',
                                      '',
                                      $this->_translator->getMessage('MESSAGE_EDIT_LINK'),
                                      '','','','','','','style="color:#800000"'
                                    ).'</span>'.BRLF;
      $html .= '<span>> '.ahref_curl( $this->_environment->getCurrentContextID(),
                                      'language',
                                      'unused',
                                      '',
                                      $this->_translator->getMessage('MESSAGE_UNUSED_LINK'),
                                      '','','','','','','style="color:#800000"'
                                    ).'</span>'.BRLF;
      $html .= BRLF;
      $html .= '</div>'.LF;
      $html .= '<div class="myarea_section_title">'.$this->_translator->getMessage('MESSAGE_LANGUAGE_TITLE_LINK').'</div>';
      $html .= '<div class="myarea_content" style="position:relative; padding-bottom:0em;">'.LF;

      $session_language = '';
      $session_item = $this->_environment->getSessionItem();
      if ( $session_item->issetValue('message_language_select_dev') ) {
         $session_language = $session_item->getValue('message_language_select_dev');
      }
      unset($session_item);
      $languageArray = $this->_translator->getAvailableLanguages();
      $url = curl($this->_environment->getCurrentContextID(),'language','change',array());
      $html .= '<form style="margin:0px; padding:0px;" method="post" action="'.$url.'" name="language_change">'.LF;
      $html .= '<select name="message_language_select" size="1" onChange="javascript:document.language_change.submit()">'.LF;
      $html .= '<option value="reset"';
      if ( empty($session_language) ) {
         $html .= ' selected="selected"';
      }
      $html .= '>';
      $html .= '*'.$this->_translator->getMessage('MESSAGE_LANGUAGE_DEFAULT_LINK');
      $html .= '</option>'.LF;
      $html .= '<option value="-1" class="disabled" disabled="disabled">';
      $html .= '----------';
      $html .= '</option>'.LF;
      foreach($languageArray as $languageItem) {
         $html .= '<option value="'.$languageItem.'"';
         if ( !empty($session_language)
              and $session_language == $languageItem
            ) {
            $html .= ' selected="selected"';
         }
         $html .= '>';
         $html .= $this->_translator->getLanguageLabelOriginally($languageItem);
         $html .= '</option>'.LF;
      }
      unset($languageArray);
      $html .= '<option value="-1" class="disabled" disabled="disabled">';
      $html .= '----------';
      $html .= '</option>'.LF;
      $html .= '<option value="no_trans"'.LF;
      if ( !empty($session_language)
           and $session_language == 'no_trans'
         ) {
         $html .= ' selected="selected"';
      }
      unset($session_language);
      $html .= '>'.$this->_translator->getMessage('MESSAGE_TITLE_LINK').'</option>'.LF;
      $html .= '</select>'.LF;
      $html .= '</form>'.LF;
      $html .= BRLF;
      $html .= BRLF;
      $html .= '</div>'.LF;

      $html .= '</div>'.LF;
      return $html;
   }

   /** adds a view in the right
    * this method adds a view to the page on the right hand side
    *
    * @param object cs_view a commsy view
    */
   public function addOverlay ($view) {
      $this->_views_overlay[] = $view;
   }

   public function _getOverlayBoxAsHTML ( $view ) {
      $left = '0em';
      $width = '100%';
      $html  = '<div style="position: absolute; z-index:1000; top:100px; left:'.$left.'; width:'.$width.'; height: 100%;">'.LF;
      $html .= '<center>';
      //$html .= '<div style="position:fixed; left:'.$left.'; z-index:1000; margin-top:10px; margin-left:25%; background-color:#FFF;">';
      $html .= '<div style="left:'.$left.'; z-index:1000; margin-top:10px; margin-left:25%; background-color:#FFF;">';

      $html .= $view->asHTML();

      $html .= '</div>'.LF;
      $html .= '</center>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div id="delete" style="position: absolute; z-index:900; top:105px; left:'.$left.'; width:'.$width.'; height: 100%; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">';
      $html .= '</div>'.LF;

      return $html;
   }
}
?>
