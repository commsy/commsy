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

$this->includeClass(RUBRIC_FORM);
include_once('functions/text_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_agb_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_choice = array();

   var $_languages = array();

   var $_text_area_height = '';

   var $_disable_agb_change_date = false;

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // headline
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_AGB_FORM_HEADLINE');

      // choice
      $this->_choice[0]['text'] = $this->_translator->getMessage('COMMON_YES');
      $this->_choice[0]['value'] = '1';
      $this->_choice[1]['text'] = $this->_translator->getMessage('COMMON_NO');
      $this->_choice[1]['value'] = '2';

      $current_context = $this->_environment->getCurrentContextItem();
      if ($current_context->getLanguage() != 'user') {
        $this->_languages[] = $current_context->getLanguage();
        $this->_text_area_height = 20;
      } else {
         $this->_languages = $this->_environment->getAvailableLanguageArray();
        $this->_text_area_height = 12;
      }

      if ( isset($this->_item) ) {
         if ( !$this->_item->withAGB() ) {
            $this->_disable_agb_change_date = true;
         }
      } elseif ( !empty($this->_form_post['agb_status']) ) {
         if ( $this->_form_post['agb_status'] == 2 ) {
            $this->_disable_agb_change_date = true;
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // form fields
      $this->setHeadline($this->_headline);

      // choice
      if ($this->_environment->inCommunityRoom()) {
        $desc = $this->_translator->getMessage('CONFIGURATION_AGB_FORM_WANT_DESC');
      } elseif ($this->_environment->inPortal()) {
         $desc = $this->_translator->getMessage('CONFIGURATION_AGB_FORM_WANT_DESC_PORTAL');
      } else {
         $desc = '';
      }
      $this->_form->addRadioGroup('agb_status',$this->_translator->getMessage('CONFIGURATION_AGB_FORM_WANT'),$desc,$this->_choice,'',true,true,'','','','onclick="cs_toggle()"');

      // text fields
      $languages = $this->_environment->getAvailableLanguageArray();
      foreach ($this->_languages as $language) {
         $this->_form->addTextArea('agb_text_'.cs_strtoupper($language),
                                   '',
                                   $this->_translator->getMessage('CONFIGURATION_AGB_FORM_TEXT').'&nbsp;'.'('.$this->_translator->getLanguageLabelTranslated($language).')',
                                   '',
                                   '60',
                                   $this->_text_area_height,
                                   '',
                                   false
                                  );
      }

      $this->_form->addText($this->_translator->getMessage('COMMON_SUCCESSBOX_TITLE'),
                            $this->_translator->getMessage('COMMON_SUCCESSBOX_TITLE'),
                            $this->_translator->getMessage('PREFERENCES_AGB_NOTE')
                           );
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_item)) {
         $agb_text_array = $this->_item->getAGBTextArray();
         foreach ($this->_languages as $language) {
            if (!empty($agb_text_array[cs_strtoupper($language)])) {
               $this->_values['agb_text_'.cs_strtoupper($language)] = $agb_text_array[cs_strtoupper($language)];
            } else {
               $this->_values['agb_text_'.cs_strtoupper($language)] = '';
            }
         }
         $this->_values['agb_status'] = $this->_item->getAGBStatus();
      } elseif (isset($this->_form_post)) {
          $this->_values = $this->_form_post;
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
            foreach ($this->_languages as $language) {
               $this->_form->setFailure('agb_text_'.cs_strtoupper($language),'');
            }
         }
      }
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '';
      $retour .= '         function cs_toggle() {'.LF;
      $retour .= '            if (document.f.agb_status[0].checked == true) {'.LF;
      $retour .= '               cs_enable();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable() {'.LF;
      $retour .= '            document.f.agb_change_date.checked = 0;'.LF;
      $retour .= '            document.f.agb_change_date.disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable() {'.LF;
      $retour .= '            document.f.agb_change_date.disabled = false;'.LF;
      $retour .= '         }'.LF;
      return $retour;
   }
}
?>