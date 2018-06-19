<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

/** upper class of the form view
 */
$this->includeClass(FORM_VIEW);
include_once('classes/cs_link.php');
include_once('classes/cs_list.php');

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_configuration_form_view extends cs_form_view {

   var $_item_saved = false;

   /** constructor: cs_configuration_form_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_form_view::__construct($params);
   }

   function setItemIsSaved(){
      $this->_item_saved = true;
   }

   /** get form view as HTML
    * this method returns the form view in HTML-Code
    *
    * @return string form view as HMTL
    */
   function asHTML () {
      $html  = '';
      $netnavigation_array = array();
      // prepare form elements, especially combine form fields
      $form_element_array = array();
      $form_element = $this->_form_elements->getFirst();
      $temp_array = array();
      $failure = false;
      $mandatory = false;
      $this->_count_form_elements = 0;
      while ($form_element) {
         if ($form_element['type'] != 'hidden') {
            if (!empty($form_element['combine']) and $form_element['combine']) {
               $temp_array[] = $form_element;
               if (!empty($form_element['failure']) and $form_element['failure']) {
                  $failure = true;
               }
               if (!empty($form_element['mandatory']) and $form_element['mandatory']) {
                  $mandatory = true;
               }
            } else {
               $temp_array[] = $form_element;
               if (count($temp_array) == 1) {
                  $form_element_array[] = $temp_array[0];
               } else {
                  if (!empty($form_element['failure']) and $form_element['failure']) {
                     $failure = true;
                  }
                  if (!empty($form_element['mandatory']) and $form_element['mandatory']) {
                     $mandatory = true;
                  }
                  if ($failure) {
                     $temp_array[0]['failure'] = true;
                     $failure = false;
                  }
                  if ($mandatory) {
                     $temp_array[0]['mandatory'] = true;
                     $mandatory = false;
                  }
                  $form_element_array[] = $temp_array;
               }
               $temp_array = array();
            }
         }
         $this->_count_form_elements++;
         $form_element = $this->_form_elements->getNext();
      }

      //Berechnung der Buttonleiste
      $temp_array=array();
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type']) and $form_element['type'] == 'buttonbar' ) {
            $buttonbartext = $this->_getButtonBarAsHTML($form_element);
         }else{
            $temp_array[] = $form_element;
         }
      }
      $form_element_array = $temp_array;



      $html .= '<form style="font-size:10pt; margin:0px; padding:0px;" action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'."\n";
      $html .='<div style="width:100%;">'.LF;
      if ($this->_item_saved){
       $html .='<div style="width:27%; padding-top:5px; float:right;">'.LF;
         $html .='<span class="required" style="font-weight:bold; font-size:11pt;">'.$this->_translator->getMessage('COMMON_ITEM_SAVED').'</span>'.LF;
       $html .='</div>'.LF;
      }
      $html .='<div style="width:70%;">'.LF;
      if (count($this->_error_array) > 0) {
         $html .= $this->_getErrorBoxAsHTML();
      }

      // Darstellung des Titelfelds
      $temp_array = array();
      $show_title_field = false;
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type']) and $form_element['type'] == 'titlefield' and $form_element['display']) {
            $html .= '<div style="padding-bottom:0px; white-space:nowrap;">';
            if (isset($form_element_array[0]['label'])) {
               if (isset($form_element_array[0]['failure'])) {
                  $label = '<span class="required">'.$form_element_array[0]['label'].'</span>';
               } else {
                  $label = $form_element_array[0]['label'];
               }
               $html .= '<span class="key">'.$label.'</span>';
               if ( !empty($label) ) {
                  $html .= ':';
               }
               if (!empty($form_element_array[0]['mandatory'])) {
                  $html .= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
               }
            }
            $html .= '&nbsp;'.$this->_getTitleFieldAsHTML($form_element);
            $show_title_field = true;
            $html .= '</div>';
         }elseif ( isset($form_element[0]['type']) and $form_element[0]['type'] == 'titlefield' and $form_element[0]['display']) {
            $html .= '<div style="padding-bottom:0px; ">';
            $html .= '<table summary="Layout">';
            $html .= '<tr>';
            $html .= '<td style="padding:0px;">';
            if (isset($form_element_array[0][0]['label'])) {
               if (isset($form_element_array[0][0]['failure'])) {
                  $label = '<span class="required">'.$form_element_array[0][0]['label'].'</span>';
               } else {
                  $label = $form_element_array[0][0]['label'];
               }
               $html .= '<span class="key">'.$label.'</span>';
               if ( !empty($label) ) {
                  $html .= ':';
               }
               if (!empty($form_element_array[0][0]['mandatory'])) {
                  $html .= '<span class="required">'.$this->_translator->getMessage('MARK').'</span>';
               }
            }
            $html .= '</td>';
            $html .= '<td style="padding:0px;">';
            $html .= '&nbsp;'.$this->_getTitleFieldAsHTML($form_element[0]);
            $show_title_field = true;
            $html .= '</td>';
            $html .= '</tr>';
            if ($form_element[1]['type'] == 'checkbox') {
               $html .= '<tr>';
               $html .= '<td style="padding:0px;">';
               $html .= '</td>';
               $html .= '<td style="padding:0px;">';
               $html .= '         '.$this->_getCheckboxAsHTML($form_element[1])."\n";
               $html .= '</td>';
               $html .= '</tr>';
            }
            $html .= '</table>';
            $html .= '</div>';
         }elseif ( isset($form_element['type']) and $form_element['type'] == 'titlefield' and !$form_element['display']) {
            $html .= $this->_getTitleFieldAsHTML($form_element);
            $show_title_field = true;
         }else{
            $temp_array[] = $form_element;
         }
      }
      if ( !$show_title_field ) {
         $html .= '<div style="padding-bottom:0px; ">';
         $temp_mod_func = mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') . '_' . mb_strtoupper($this->_environment->getCurrentFunction(), 'UTF-8');
         $tempMessage = "";

         ############################################
         # dann muss man nicht immer auch diese Datei
         # anfassen, wenn man an einem Formular
         # arbeitet (ij 26.05.2009)
         ############################################
         if ( !empty($this->_form) ) {
            $headline = $this->_form->getHeadline();
            if ( !empty($headline) ) {
               $tempMessage = $headline;
            }
         }
         ############################################

         if ( empty($tempMessage) ) {
            switch( $temp_mod_func ) {
               case 'ACCOUNT_STATUS':
                  $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNT_STATUS_FORM_TITLE');		// Status ändern (Portal)
                  break;
               case 'CONFIGURATION_AGB':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_AGB_FORM_TITLE');	// Nutzungsvereinbarungen OK
                  break;
               case 'CONFIGURATION_AUTHENTICATION':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_AUTHENTICATION_FORM_TITLE');	// Authentifizierung einstellen (Portal)
                  break;
               case 'CONFIGURATION_BACKUP':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_BACKUP_FORM_TITLE');	// Backup eines Raumes einspielen (Server)
                  break;
               case 'CONFIGURATION_CHAT':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_CHAT_FORM_TITLE');	// Raum-Chat einstellen
                  break;
               case 'CONFIGURATION_DATES':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_DATES_FORM_TITLE');	// Termindarstellung OK
                  break;
               case 'CONFIGURATION_DEFAULTS':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_DEFAULTS_FORM_TITLE');	// Voreinstellungen für Räume OK
                  break;
               case 'CONFIGURATION_DISCUSSION':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_DISCUSSION_FORM_TITLE');	// Art der Diskussion OK
                  break;
               case 'CONFIGURATION_EXTRA':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_EXTRA_FORM_TITLE');	// Extras einstellen (Server)
                  break;
               case 'CONFIGURATION_GROUPROOM':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_GROUPROOM_FORM_TITLE');	//
                  break;
               case 'CONFIGURATION_HOMEPAGE':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_HOMEPAGE_FORM_TITLE');	// Raum-Webseite einstellen
                  break;
               case 'CONFIGURATION_HOME':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_HOME_FORM_TITLE');	// Konfiguration der Home OK
                  break;
               case 'CONFIGURATION_PATH':
                  $tempMessage = $this->_translator->getMessage('CONFIGURATION_PATH_FORM_TITLE');	// Konfiguration der Pfade OK
                  break;
               case 'CONFIGURATION_TAGS':
                  $tempMessage = $this->_translator->getMessage('CONFIGURATION_TAGS_FORM_TITLE');	// Konfiguration der Tags OK
                  break;
               case 'CONFIGURATION_LISTVIEWS':
                  $tempMessage = $this->_translator->getMessage('CONFIGURATION_LISTVIEWS_FORM_TITLE');	// Konfiguration der Tags OK
                  break;
               case 'CONFIGURATION_HTMLTEXTAREA':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_HTMLTEXTAREA_FORM_TITLE');	// FCK-Editor-Konfiguration ??
                   break;
               case 'CONFIGURATION_IMS':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_IMS_FORM_TITLE');	// IMS-Account Einstellungen (Server)
                  break;
               case 'CONFIGURATION_LANGUAGE':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_LANGUAGE_FORM_TITLE');	// Verfügbare Sprachen (Server)
                  break;
               case 'CONFIGURATION_MAIL':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE');	// E-Mail-Texte OK
                  break;
               case 'CONFIGURATION_NEWS':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_NEWS_FORM_TITLE');      	// Ankündigungen bearbeiten (Portal)
                  break;
               case 'CONFIGURATION_PLUGIN':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PLUGIN_FORM_TITLE');	// Sponsoren und Werbung
                  break;
               case 'CONFIGURATION_PORTALHOME':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PORTALHOME_FORM_TITLE');	// Gestaltung der Raumübersicht (Portal)
                  break;
               case 'CONFIGURATION_PORTALUPLOAD':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PORTALUPLOAD_FORM_TITLE');	// Konfiguration des Uploads(Portal)
                  break;
               case 'CONFIGURATION_PREFERENCES':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PREFERENCES_FORM_TITLE');	// Allgemeine Einstellungen bearbeiten (pers. Raum)
                  break;
               case 'CONFIGURATION_PRIVATEROOM_NEWSLETTER':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_PRIVATEROOM_NEWSLETTER_FORM_TITLE');	// E-Mail-Newsletter (priv.)
                  break;
               case 'CONFIGURATION_ROOM_OPENING':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPENING_FORM_TITLE');	// Raumeröffnungen (Portal)
                  break;
               case 'CONFIGURATION_RUBRIC':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_FORM_TITLE');	// Auswahl der Rubriken OK
                  break;
               case 'CONFIGURATION_SERVICE':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_SERVICE_FORM_TITLE');	// Handhabungssupport einstellen
                  break;
               case 'CONFIGURATION_TIME':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_TIME_FORM_TITLE');	// Zeittakte bearbeiten (Portal)
                  break;
               case 'CONFIGURATION_USAGEINFO':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE');	// Nutzungshinweise bearbeiten OK
                  break;
               case 'CONFIGURATION_WIKI':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_WIKI_FORM_TITLE');	// Nutzungshinweise bearbeiten OK
                  break;
               case 'CONFIGURATION_LIMESURVEY':
               	  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_LIMESURVEY_FORM_TITLE');
               	  break;
               case 'CONFIGURATION_OUTOFSERVICE':
                  $tempMessage = $this->_translator->getMessage('CONFIGURATION_OUTOFSERVICE_FORM_TITLE');	// Wartungsseite OK
                  break;
               case 'ACCOUNT_ACTION':
                  $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNT_ACTION_FORM_TITLE');   // Nutzungshinweise bearbeiten OK
                  break;
               case 'CONFIGURATION_INFORMATIONBOX':
                  $tempMessage = $this->_translator->getMessage('COMMON_INFORMATION_BOX');   // Hinweistext auf der Home OK
                  break;
               case 'CONFIGURATION_ARCHIVE':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFINGURATION_ARCHIVE');
                  break;
               case 'CONFIGURATION_SCRIBD':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_SCRIBD_FORM_TITLE');
                  break;
               case 'CONFIGURATION_EXPORT_IMPORT':
                  $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_EXPORT_IMPORT_FORM_TITLE');	// Konfiguration des Uploads(Portal)
                  break;
               default:
                  $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_configuration_form_view (".__LINE__.")";	// "Bitte Messagetag-Fehler melden"
                  break;
            }
         }
         $html .= '<h2 class="pagetitle">'.$tempMessage.'</h2>';
      }
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;

      $html .='<div style="width:100%;">'.LF;

      $html .='<div style="float:right; width:27%; margin-top:0px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $html .= '<div style="margin-bottom:1px;">'.LF;
      $html .= $this->_getRubricFormInfoAsHTML($this->_environment->getCurrentModule());
      $html .='</div>'.LF;
      if ($this->_environment->getCurrentModule() != 'mail' and $this->_environment->getCurrentFunction() != 'process'){
         $html .= $this->_getConfigurationOptionsAsHTML();
      }
      $html .='</div>'.LF;



      $html .='<div class="infoborder" style="width:70%; font-size:10pt; margin-top:5px; padding-top:0px; vertical-align:bottom;">'.LF;
      $html .= '<!-- BEGIN OF FORM-VIEW -->'.LF;
      $html .= '<table class="form" style="font-size:10pt; border-collapse:collapse; margin-bottom:10px;" summary="Layout">'.LF;
      $form_element = $this->_form_elements->getFirst();
      $html .= '<tr>'.LF;
      $html .= '<td style="border:0px; padding:0px;" colspan="4">'.LF;
      while ($form_element) {
         if ($form_element['type'] == 'hidden') {
            $html .= $this->_getHiddenfieldAsHTML($form_element);
         }
         $form_element = $this->_form_elements->getNext();
      }
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      $temp_array = array();


      // now get the html code
      $first = true;
      $second = false;
      $temp_array = $form_element_array;
      $i=0;
      $without_description=0;
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type']) and $form_element['type'] == 'netnavigation' ) {
            $netnavigation_array[] = $form_element;
         }
      }
      foreach ($form_element_array as $form_element) {
         if (!isset($form_element[0]['type']) and $form_element['type'] == 'headline') {
            $headline_right = $this->_getHeadLineAsHTML($form_element,$form_element['size']);
         } else {
            if ( !(isset($form_element['type']) and $form_element['type'] == 'netnavigation')  and!(isset($form_element[0]['type']) and $form_element[0]['type'] == 'titlefield') ) {
               if ( isset($form_element['type']) and $form_element['type'] == 'textarea' ) {
                  $html .= '   <tr class="textarea">'.LF;
               } elseif ( isset($form_element['type']) and $form_element['type'] == 'radio' ) {
                  $html .= '   <tr class="radio">'.LF;
               } elseif ( isset($form_element['type']) and $form_element['type'] == 'checkboxgroup' ) {
                  $html .= '   <tr class="checkboxgroup">'.LF;
               } else {
                  $html .= '   <tr>'."\n";
               }
            }
            if ( !(isset($form_element['type']) and $form_element['type'] == 'netnavigation') and!(isset($form_element[0]['type']) and $form_element[0]['type'] == 'titlefield') ) {
               if (!isset($form_element['type']) or $form_element['type'] != 'titlefield'){
                  $html .= $this->_getFormElementAsHTML($form_element);
                  $html .= '   </tr>'.LF;
               }
           }
         }
         $i++;
      }

      $html .= '</table>'.LF;
      $html .='</div>'.LF;

      $html .= '<!-- END OF FORM-VIEW -->'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;


      $funct = $this->_environment->getCurrentFunction();
      if (isset($buttonbartext) and !empty($buttonbartext) and $this->_environment->getCurrentModule() !='buzzwords' and $this->_environment->getCurrentModule() !='labels'){
         $html .= '<div style="width: 100%; clear:both;">'.BRLF;
#         $html .= $buttonbartext;
         $html .= '<table style="width: 100%; border-collapse:collapse;">'.LF;
         $html .= '<tr>'.LF;
         if (!$this->_display_plain) {
            if ($this->_special_color) {
               $html .='      <td colspan="2" style="border-bottom: none; white-space:nowrap;">';
            } else {
               if ($this->_warn_changer) {
                  $html .='      <td colspan="2" style="background-color:#FF0000; white-space:nowrap;">';
               } else {
                  $html .='      <td colspan="2" class="buttonbar">';
               }
            }
         } else {
            if ($this->_special_color) {
               $html .='      <td colspan="2" style="border-bottom: none; white-space:nowrap;">';
            } else {
               $html .='      <td colspan="2" style="border-bottom: none; white-space:nowrap;">';
            }
         }
         $html .= '<span class="required" style="font-size:16pt;">*</span> <span class="key" style="font-weight:normal;">'.$this->_translator->getMessage('COMMON_MANDATORY_FIELDS').'</span> '.$buttonbartext;
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
         $html .= '</table>'.LF;
         $html .= '</div>'.LF;
      }
      $html .='</div>'.LF;
      $function = $this->_environment->getCurrentFunction();
      if( $function != 'preferences' ){
         $html .='</div>'.LF;
      }
      $html .= '</form>'.BRLF;
      return $html;
   }


     function _getConfigurationOptionsAsHTML(){
         $html = '<div id="netnavigation1">'.LF;
         $html .= '<div class="netnavigation" >'.LF;
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION').'</div>';
         $html .= $this->_getConfigurationBoxAsHTML($this->_environment->getCurrentFunction());

         $title_string ='"'.$this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').'"';
         $title_string .=',"'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'"';
         if ( !$this->_environment->inPortal() and !$this->_environment->inServer() ){
            $title_string .=',"'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'"';
         }
         $show_entry ='-1';
         if ($this->_environment->getCurrentFunction() == 'mail' or
             $this->_environment->getCurrentFunction() == 'agb' or
             $this->_environment->getCurrentFunction() == 'usageinfo' or
             $this->_environment->getCurrentFunction() == 'news' or
             $this->_environment->getCurrentFunction() == 'extra' or
             $this->_environment->getCurrentModule() == 'account' or
             $this->_environment->getCurrentFunction() == 'statistic' or
             $this->_environment->getCurrentFunction() == 'informationbox' or
             $this->_environment->getCurrentFunction() == 'update' or
             $this->_environment->getCurrentFunction() == 'archive' or
             $this->_environment->getCurrentFunction() == 'outofservice' or
             $this->_environment->getCurrentFunction() == 'autoaccounts'
            ){
            $show_entry = '0';
         }elseif ($this->_environment->getCurrentFunction() == 'preferences' or
             $this->_environment->getCurrentFunction() == 'portalhome' or
             $this->_environment->getCurrentFunction() == 'portalupload' or
             $this->_environment->getCurrentFunction() == 'rubric' or
             $this->_environment->getCurrentFunction() == 'defaults' or
             $this->_environment->getCurrentFunction() == 'home' or
             $this->_environment->getCurrentFunction() == 'color' or
             $this->_environment->getCurrentFunction() == 'listviews' or
             $this->_environment->getCurrentFunction() == 'tags' or
             $this->_environment->getCurrentFunction() == 'time' or
             $this->_environment->getCurrentFunction() == 'room_opening' or
             $this->_environment->getCurrentFunction() == 'ims' or
             $this->_environment->getCurrentFunction() == 'privateroom_newsletter' or
             $this->_environment->getCurrentFunction() == 'authentication' or
             $this->_environment->getCurrentFunction() == 'language' or
             $this->_environment->getCurrentFunction() == 'backup'
            ){
            $show_entry = '1';
         }elseif ($this->_environment->getCurrentFunction() == 'dates' or
             $this->_environment->getCurrentFunction() == 'discussion' or
             $this->_environment->getCurrentFunction() == 'path' or
             $this->_environment->getCurrentFunction() == 'tags' or
             $this->_environment->getCurrentFunction() == 'grouproom'
             or ( $this->_environment->inPortal() and $this->_environment->getCurrentFunction() == 'plugins')
             or ( $this->_environment->inPortal() and $this->_environment->getCurrentFunction() == 'service')
             or ( $this->_environment->inPortal() and $this->_environment->getCurrentFunction() == 'wiki')
             or ( $this->_environment->inPortal() and $this->_environment->getCurrentFunction() == 'htmltextarea')
             or ( $this->_environment->inServer() and $this->_environment->getCurrentFunction() == 'datasecurity')
         	 or ( $this->_environment->inServer() and $this->_environment->getCurrentFunction() == 'connection')
         ){
            $show_entry = '2';
         }else{
            $show_entry = '3';
         }
         $title_string .=',"'.$this->_translator->getMessage('COMMON_ADDITIONAL_CONFIGURATION_TITLE').'"';
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .= '<script type="text/javascript">'.LF;
         $html .= 'initDhtmlNetnavigation("netnavigation",Array('.$title_string.'),'.$show_entry.',"1");'.LF;
         $html .= '</script>'.LF;
         return $html;
     }

     function _getConfigurationBoxAsHTML($act_fct){
      $html = '';
      $room = $this->_environment->getCurrentContextItem();
      $link_item = new cs_link();
      $link_item->setDescription($this->_translator->getMessage('HOME_ROOM_MEMBER_ADMIN_DESC'));
      $link_item->setIconPath('images/cs_config/CONFIGURATION_OVERVIEW.gif');
      $link_item->setTitle($this->_translator->getMessage('COMMON_COMMSY_CONFIGURE_HOME'));
      $link_item->setContextID($this->_environment->getCurrentContextID());
      $link_item->setModule('configuration');
      $link_item->setFunction('index');
      $params = array();
      $link_item->setParameter($params);
      unset($params);
      $html .= '<div class="netnavigation_panel_top">     '.LF;
      $html .= '<div style="padding-top:3px; padding-bottom:3px; padding-left:0px; padding-right:0px;"><ul style="list-style-type: none; font-size:8pt; padding-top:0px; margin-bottom:0px; padding-left:0px;">'.LF;
      $html .= '<li>'.LF;
      $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
      $html .= $link_item->getLinkIcon(25).LF;
      $html .= '</div><div style="padding-top:5px; text-align:left;">'.LF;
      $html .= $link_item->getShortLink(30).LF;
      $html .= '</div></div>'.LF;
      $html .='</li>'.LF;
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div class="netnavigation_panel">     '.LF;
      $html .= '<noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').'</div>';
      $html .= '</noscript>';
      $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;
      $list = $this->getAdminConfigurationList();
      $element = $list->getFirst();
      while ($element){
         $html .= '<li>'.LF;
         $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
         if ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions or ($room->isClosed() and $element->getFunction() != 'archive') ) {
            $html .= $element->getIcon(25).LF;
         } else {
            $html .= $element->getLinkIcon(25).LF;
         }
         $html .= '</div><div style="padding-top:5px;">'.LF;
         if($element->getFunction() != $this->_environment->getCurrentFunction() and $element->getFunction() == 'archive') {
            $html .= $element->getShortLink().LF;
         }elseif ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions  or ($room->isClosed() and $element->getFunction() != 'archive') ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } else {
            $html .= $element->getShortLink().LF;
         }
         $html .= '</div></div>'.LF;
         $html .='</li>'.LF;
         $element = $list->getNext();
      }
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      $html .= '<div class="netnavigation_panel">     '.LF;
      $html .= '<noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'</div>';
      $html .= '</noscript>';
      $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;
      $list = $this->getRoomConfigurationList();
      $element = $list->getFirst();
      while ($element){
         $html .= '<li>'.LF;
         $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
         if ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions  or ($room->isClosed() and $element->getFunction() != 'preferences') ) {
            $html .= $element->getIcon(25).LF;
         } else {
            $html .= $element->getLinkIcon(25).LF;
         }
         $html .= '</div><div style="padding-top:5px;">'.LF;
         if ( !$this->_with_modifying_actions  or ($room->isClosed() and $element->getFunction() != 'preferences') ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } elseif ( $element->getModule() == $this->_environment->getCurrentModule()
                 and $element->getFunction() == 'index'
                 and $this->_environment->getCurrentFunction() == 'status'
                ) {
            $html .= $element->getShortLink().LF;
         } elseif ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } else {
            $html .= $element->getShortLink().LF;
         }
         $html .= '</div></div>'.LF;
         $html .='</li>'.LF;
         $element = $list->getNext();
      }
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;


      if ( !$this->_environment->inPortal() and !$this->_environment->inServer() ){
      $html .= '<div class="netnavigation_panel">     '.LF;
      $html .= '<noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'</div>';
      $html .= '</noscript>';
      $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;
      $list = $this->getRubricConfigurationList();
      $element = $list->getFirst();
      while ($element){
         $html .= '<li>'.LF;
         $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
         if ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions  or $room->isClosed() ) {
            $html .= $element->getIcon(25).LF;
         } else {
            $html .= $element->getLinkIcon(25).LF;
         }
         $html .= '</div><div style="padding-top:5px;">'.LF;
         if ( !$this->_with_modifying_actions  or $room->isClosed() ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } elseif ( $element->getModule() == $this->_environment->getCurrentModule()
                 and $element->getFunction() == 'index'
                 and $this->_environment->getCurrentFunction() == 'status'
                ) {
            $html .= $element->getShortLink().LF;
         } elseif ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } else {
            $html .= $element->getShortLink().LF;
         }
         $html .= '</div></div>'.LF;
         $html .='</li>'.LF;
         $element = $list->getNext();
      }
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      }

      $addonlist = $this->getAddOnConfigurationList();
      $element = $addonlist->getFirst();
      if ($element){
         $html .= '<div class="netnavigation_panel">     '.LF;
         $html .= '<noscript>';
         $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_ADDITIONAL_CONFIGURATION_TITLE').'</div>';
         $html .= '</noscript>';
         $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;

         while ($element){
            $html .= '<li>'.LF;
            $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
            if ( $element->getFunction() == $this->_environment->getCurrentFunction() or !$this->_with_modifying_actions  or $room->isClosed() ){
               $html .= $element->getIcon(25).LF;
            } else {
               $html .= $element->getLinkIcon(25).LF;
            }
            $html .= '</div><div style="padding-top:5px;">'.LF;
            if ( $element->getFunction() == $this->_environment->getCurrentFunction()  or $room->isClosed() ) {
               $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
            } elseif ( !$this->_with_modifying_actions ){
               $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
            } else {
               $html .= $element->getShortLink().LF;
            }
            $html .= '</div></div>'.LF;
            $html .='</li>'.LF;
            $element = $addonlist->getNext();
         }
         $html .= '</ul>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '</div>'.LF;
      return $html;
   }

   function getRoomConfigurationList () {
      $room_link_list = '';
      include_once('include/inc_configuration_room_links.php');
      return $room_link_list;
   }

   function getAdminConfigurationList () {
      $admin_link_list = '';
      include_once('include/inc_configuration_admin_links.php');
      return $admin_link_list;
   }

   function getRubricConfigurationList () {
      $rubric_link_list = '';
      include_once('include/inc_configuration_rubric_links.php');
      return $rubric_link_list;
   }

   function getAddOnConfigurationList () {
        $addon_link_list = '';
      // addon configuration options
      include_once('include/inc_configuration_links_addon.php');
      return $addon_link_list;
   }
}
?>