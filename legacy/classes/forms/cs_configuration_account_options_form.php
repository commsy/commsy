<?PHP
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

$this->includeClass(RUBRIC_FORM);
include_once('functions/text_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_account_options_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_choice = array();

   var $_languages = array();

   var $_text_area_height = '';

   var $_disable_agb_change_date = false;

   var $_disable_code = true;


   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   function _initForm () {

      /**************Raumteilnahme******/
      $this->_disable_code = true;
      if ( isset($this->_item) ) {
         if ($this->_item->checkNewMembersWithCode()) {
            $this->_disable_code = false;
         }
      } elseif ( !empty($this->_form_post['member_check'])
                 and $this->_form_post['member_check'] == 'withcode'
               ) {
         $this->_disable_code = false;
      }

      /*************AGBs**************/
      $current_context_item = $this->_environment->getCurrentContextItem();
      if ( $current_context_item->getLanguage() == 'user' ) {
         $this->_languages = $this->_environment->getAvailableLanguageArray();
      } else {
         $this->_languages[] = $current_context_item->getLanguage();
      }
      if (isset($this->_form_post['description_text'])) {
         $this->_description_text = $this->_form_post['description_text'];
      } else{
         $this->_description_text = $current_context_item->getLanguage();
         if ( $this->_description_text == 'user' ) {
            $this->_description_text = 'de';
         }
      }
      $this->_choice[0]['text'] = $this->_translator->getMessage('COMMON_YES');
      $this->_choice[0]['value'] = '1';
      $this->_choice[1]['text'] = $this->_translator->getMessage('COMMON_NO');
      $this->_choice[1]['value'] = '2';

   }


   function _createForm () {
      $current_context_item = $this->_environment->getCurrentContextItem();

      /**************Raumteilnahme******/

      $use_javascript = false;
      $session_item = $this->_environment->getSessionItem();
      if($session_item->issetValue('javascript')){
         if($session_item->getValue('javascript') == "1"){
            $use_javascript = true;
         }
      }

      $radio_values = array();
      $radio_values[0]['text'] = $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_NEVER');
      $radio_values[0]['value'] = 'never';
      if($use_javascript){
         $radio_values[0]['extention'] = 'onclick="disable_code()"';
      }
      $radio_values[2]['text'] = $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_ALWAYS');
      $radio_values[2]['value'] = 'always';
      if($use_javascript){
         $radio_values[2]['extention'] = 'onclick="disable_code()"';
      }
      $radio_values[3]['text'] = $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_WITH_CODE');
      $radio_values[3]['value'] = 'withcode';
      if($use_javascript){
         $radio_values[3]['extention'] = 'onclick="enable_code()"';
      }
      $this->_form->addRadioGroup('member_check',
                                  $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS'),
                                  $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_DESC'),
                                  $radio_values,
                                  '',
                                  true,
                                  false
                                 );
      unset($radio_values);
      $this->_form->combine();
      $code_disabled = false;
      if($use_javascript){
          $code_disabled = $this->_disable_code;
      }
      $this->_form->addTextfield('code',$this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_WITH_CODE_VALUE'),'','','',30,'','','','','','','',$code_disabled);

      /********Gastzugang********/
      if ($this->_environment->inCommunityRoom()){
         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('COMMON_ON');
         $radio_values[0]['value'] = 'open';
         $radio_values[1]['text'] = $this->_translator->getMessage('COMMON_OFF');
         $radio_values[1]['value'] = 'closed';
         $this->_form->addRadioGroup('open_for_guests',
                                     $this->_translator->getMessage('PREFERENCES_OPEN_FOR_GUESTS'),
                                     $this->_translator->getMessage('PREFERENCES_OPEN_FOR_GUESTS_DESC'),
                                     $radio_values,
                                     '',
                                     true,
                                     true
                                    );
         unset($radio_values);
      }


      /*********Raum archivieren***************/
      $this->_form->addCheckbox('status',
                                     '2',
                                     false,
                                     $this->_translator->getMessage('ROOM_ARCHIVE_STATUS'),
                                     $this->_translator->getMessage('ROOM_STATUS_DESCRIPTION'),
                                     '',
                                     '',
                                     '',
                                     ''
                                    );
      $this->_form->combine();
      $this->_form->addExplanation('status_desc',$this->_translator->getMessage('ROOM_STATUS_LONG_DESCRIPTION'),$this->_translator->getMessage('ROOM_STATUS_LONG_DESCRIPTION'));



      /*************AGBs**************/
      if ($this->_environment->inCommunityRoom()) {
         $desc = $this->_translator->getMessage('CONFIGURATION_AGB_FORM_WANT_DESC');
      } elseif ($this->_environment->inPortal()) {
         $desc = $this->_translator->getMessage('CONFIGURATION_AGB_FORM_WANT_DESC_PORTAL');
      } else {
         $desc = '';
      }
      $this->_form->addRadioGroup('agb_status',$this->_translator->getMessage('CONFIGURATION_CONFIRMATION_FORM_TITLE'),$desc,$this->_choice,'',true,true,'','','','onclick="cs_toggle()"');
      $this->_form->combine();
      $languageArray = array();
      #$tmpArray = $this->_environment->getAvailableLanguageArray();
      $tmpArray = $this->_languages;
      $zaehler = 0;
      foreach ($tmpArray as $item){
         switch ( mb_strtoupper($item, 'UTF-8') ){
            case 'DE':
               $languageArray[$zaehler]['text']= $this->_translator->getMessage('DE');
               break;
            case 'EN':
               $languageArray[$zaehler]['text']= $this->_translator->getMessage('EN');
               break;
            default:
               break;
         }
         $languageArray[$zaehler]['value']= $item;
         $zaehler++;
      }
      $this->_form->addSelect( 'description_text',
                               $languageArray,
                               '',
                               $this->_translator->getMessage('CONFIGURATION_CHOOSE_LANGUAGE'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               $this->_translator->getMessage('COMMON_LANGUAGE_CHOOSE_BUTTON'),
                               'option','','','16',true);

      $this->_form->combine();
      $context_item = $this->_environment->getCurrentContextItem();
      foreach ($this->_languages as $language) {
         if ($language == $this->_description_text){
            $html_status = $context_item->getHtmlTextAreaStatus();
            if ($html_status =='1'){
               $html_status ='2';
            }
            $this->_form->addTextArea('agb_text_'.cs_strtoupper($language),'','','','60','5','virtual',false,false,true,$html_status);
         } else {
            $this->_form->addHidden('agb_text_'.cs_strtoupper($language),'');
         }
      }
      $this->_form->combine();
      $this->_form->addText($this->_translator->getMessage('COMMON_SUCCESSBOX_TITLE'),
                            $this->_translator->getMessage('COMMON_SUCCESSBOX_TITLE'),
                            $this->_translator->getMessage('PREFERENCES_AGB_NOTE')
                           );

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'',$this->_translator->getMessage('COMMON_DELETE_ROOM'));
   }


   function _prepareValues () {
      $this->_values = array();
      $current_context_item = $this->_environment->getCurrentContextItem();
      if (isset($this->_form_post)) {
          $this->_values = $this->_form_post;
      }else {

         /********Gastzugang********/
         if ($current_context_item->isOpenForGuests()) {
            $this->_values['open_for_guests'] = 'open';
         } else {
            $this->_values['open_for_guests'] = 'closed';
         }

         /**************Raumteilnahme******/
         if ($current_context_item->checkNewMembersNever()) {
            $this->_values['member_check'] = 'never';
         } elseif ($current_context_item->checkNewMembersAlways()) {
            $this->_values['member_check'] = 'always';
         } elseif ($current_context_item->checkNewMembersSometimes()) {
            $this->_values['member_check'] = 'sometimes';
         } elseif ($current_context_item->checkNewMembersWithCode()) {
            $this->_values['member_check'] = 'withcode';
         }

         $code = $this->_item->getCheckNewMemberCode();
         if ( !empty($code) ) {
            $this->_values['code'] = $code;
         }

         /******Raum archivieren*****/
         if ( $current_context_item->isOpen() ) {
            $this->_values['status'] = '';
         } else {
            $this->_values['status'] = '2';
         }


         /*************AGBs**************/
         $agb_text_array = $current_context_item->getAGBTextArray();
         foreach ($this->_languages as $language) {
            if (!empty($agb_text_array[cs_strtoupper($language)])) {
               $this->_values['agb_text_'.cs_strtoupper($language)] = $agb_text_array[cs_strtoupper($language)];
            } else {
               $this->_values['agb_text_'.cs_strtoupper($language)] = '';
            }
         }
         $this->_values['agb_status'] = $current_context_item->getAGBStatus();
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    *
    * @author CommSy Development Group
    */
   function _checkValues () {
      if ($this->_form_post['agb_status'] == 1) {
         $no_language_set = true;
         foreach ($this->_languages as $language) {
            if (!empty($this->_values['agb_text_'.cs_strtoupper($language)])) {
               $no_language_set = false;
               break;
            }
         }
         if($no_language_set){
            $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AGB_TEXT_ERROR');
            $this->_form->setFailure('agb_text_'.cs_strtoupper($language),'');
         }
      }
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '';
      $retour .= '         function disable_code() {'.LF;
      $retour .= '            document.edit.code.disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function enable_code() {'.LF;
      $retour .= '            document.edit.code.disabled = false;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_toggle() {'.LF;
      $retour .= '            if (document.edit.agb_status[0].checked == true) {'.LF;
      $retour .= '               cs_enable();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable() {'.LF;
      $retour .= '            document.edit.description_text.disabled = true;'.LF;
      foreach ($this->_languages as $language) {
         $retour .= '            document.edit.agb_text_'.cs_strtoupper($language).'.disabled = true;'.LF;
      }
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable() {'.LF;
      $retour .= '            document.edit.description_text.disabled = false;'.LF;
      foreach ($this->_languages as $language) {
         $retour .= '            document.edit.agb_text_'.cs_strtoupper($language).'.disabled = false;'.LF;
      }
      $retour .= '         }'.LF;
      return $retour;
   }
}
?>