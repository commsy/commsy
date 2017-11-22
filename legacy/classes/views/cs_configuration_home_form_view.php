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
$this->includeClass(CONFIGURATION_FORM_VIEW);
include_once('classes/cs_link.php');
include_once('classes/cs_list.php');

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_configuration_home_form_view extends cs_configuration_form_view {

   var $_item_saved = false;

   /** constructor: cs_configuration_form_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_configuration_form_view::__construct($params);
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

      $html .= '<form style="font-size:10pt; margin:0px; padding:0px;" action="'.$this->_action.'" method="'.$this->_action_type.'" enctype="multipart/form-data" name="f">'."\n";
      $html .='<table style="width:100%; border-collapse:collapse;" summary="Layout">'.LF;
      $html .='<tr>'.LF;
      $html .='<td style="width:71%; padding-top:0px; vertical-align:bottom;">'.LF;
      if (count($this->_error_array) > 0) {
         $html .= $this->_getErrorBoxAsHTML();
      }

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


      // Darstellung des Titelfelds
      $temp_array = array();
      $show_title_field = false;
      $first = true;
      foreach ($form_element_array as $form_element) {
#         pr($form_element['name']);
         if ( isset($form_element['type']) and $form_element['type'] == 'titlefield' and $form_element['display']) {
            $html .= '<div style="padding-bottom:10px; white-space:nowrap;">';
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
         }else{
            $temp_array[] = $form_element;
         }
      }
      if (!$show_title_field){
         $html .= '<div style="padding-bottom:5px; ">';
         $temp_mod_func = mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') . '_' . mb_strtoupper($this->_environment->getCurrentFunction(), 'UTF-8');
         $tempMessage = "";
         switch( $temp_mod_func  )
         {
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
            case 'ACCOUNT_ACTION':
               $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNT_ACTION_FORM_TITLE');	// Nutzungshinweise bearbeiten OK
               break;
            case 'CONFIGURATION_EXPORT_IMPORT':
               $tempMessage = $this->_translator->getMessage('COMMON_CONFIGURATION_EXPORT_IMPORT_FORM_TITLE');	// Konfiguration des Uploads(Portal)
               break;
            default:
               $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_configuration_form_view";	// "Bitte Messagetag-Fehler melden"
               break;
         }
         $html .= '<h2 class="pagetitle">' . $this->_translator->getMessage($tempMessage) . '</h2>';

         $html .= '</div>';
      }

      //Berechnung der Buttonleiste
      $form_element_array = $temp_array;
      $temp_array=array();
      foreach ($form_element_array as $form_element) {
         if ( isset($form_element['type']) and $form_element['type'] == 'buttonbar' ) {
            $buttonbartext = $this->_getButtonBarAsHTML($form_element);
         }else{
            $temp_array[] = $form_element;
         }
      }
      $form_element_array = $temp_array;
      $html .='<td colspan="2" style="width:28%; padding-top:5px; padding-left:px; padding-right:0px; vertical-align:bottom; text-align:left;">'.LF;
      if ($this->_item_saved){
         $html .='&nbsp;&nbsp;&nbsp;<span class="required" style="font-weight:bold; font-size:11pt;">'.$this->_translator->getMessage('COMMON_ITEM_SAVED').'</span>'.LF;
      }else{
         $html .='&nbsp;'.LF;
      }
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='<tr>'.LF;
      $html .='<td class="infoborder" style="padding-top:10px; vertical-align:top;">'.LF;





      $html .= '<!-- BEGIN OF FORM-VIEW -->'.LF;
      $html .= LF.'<div>'.LF;


      $html .= LF.'<div style="float:left; width:270px;">'.LF;

      $html .= '<table style="font-size:10pt; border-collapse:collapse; margin-bottom:10px;" summary="Layout">'.LF;
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
      $i=0;
      $second_row = false;
      $length = count($form_element_array);
      while (!$second_row and $i < $length){
         $form_element = $form_element_array[$i];
         if (!isset($form_element[0]['type']) and $form_element['type'] == 'headline') {
            $headline_right = $this->_getHeadLineAsHTML($form_element,$form_element['size']);
         } else {
            if ( !(isset($form_element['type']) and $form_element['type'] == 'netnavigation')) {
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
            if ( !(isset($form_element['type']) and $form_element['type'] == 'netnavigation') ) {
               $html .= $this->_getFormElementAsHTML($form_element);
            }
            if ( !(isset($form_element['type']) and $form_element['type'] == 'netnavigation')) {
               $html .= '   </tr>'.LF;
            }
         }
         $i++;
         if ( isset($form_element_array[$i]) ){
            $next_form_element = $form_element_array[$i];
            $context_item = $this->_environment->getCurrentContextItem();
            if ( !$context_item->withRubric($next_form_element['name']) and $next_form_element['name']!='time_spread' ){
               $second_row = true;
            }
         }
      }
      $html .= '</table>'.LF;

      $html .='</div><div style="width:200px;">'.LF;


      $html .= '<table style="font-size:10pt; border-collapse:collapse; margin-bottom:10px;" summary="Layout">'.LF;
      for ($j=$i;$j<$length;$j++) {
         $form_element = $form_element_array[$j];
         $html .= '<tr>'.LF;
         $html .= $this->_getFormElementAsHTML($form_element);
         $html .= '</tr>'.LF;
      }
      $html .= '</table>'.LF;


      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<!-- END OF FORM-VIEW -->'.LF;


      $html .='</td>'.LF;
      $html .='<td>&nbsp;'.LF;
      $html .='</td>'.LF;
      $funct = $this->_environment->getCurrentFunction();
      if ($funct !='info_text_form_edit' and $funct !='info_text_edit'){
         $html .='<td style="width:27%; vertical-align:top; padding-top:0px;">'.LF;
      }else{
         $html .='<td style="width:27%; vertical-align:top; padding-top:0px;">'.LF;
      }
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $html .= '<div style="margin-bottom:10px; ">'.LF;
      $html .= $this->_getRubricFormInfoAsHTML($this->_environment->getCurrentModule());
      if ($this->_environment->getCurrentModule() != 'mail' and $this->_environment->getCurrentFunction() != 'process'){
         $html .= $this->_getConfigurationOptionsAsHTML();
      }
      $html .= '</div>'.LF;
      $html .= '      </td>'.LF;
      $html .= '</tr>'.LF;

      if (isset($buttonbartext) and !empty($buttonbartext) and $this->_environment->getCurrentModule() !='buzzwords' and $this->_environment->getCurrentModule() !='labels'){
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
      }
      $html .= '</table>'.LF;
      $html .= '</form>'.BRLF;
      return $html;
   }

}
?>