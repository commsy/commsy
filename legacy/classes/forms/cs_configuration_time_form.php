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

$this->includeClass(RUBRIC_FORM);

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_time_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * int - counter for main sponsors
   */
   var $_counter = 0;

  /**
   * array - containing choices for showing time
   */
   var $_show_choice = array();

  /**
   * array - containing choices for showing clock pulses in future
   */
   var $_future_choice = array();

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
    */
   function _initForm () {
      $translator = $this->_environment->getTranslationObject();

      // headlines
      $this->_headline = $translator->getMessage('CONFIGURATION_TIME_FORM_HEADLINE');

      // choice show
      $this->_show_choice[0]['text'] = $translator->getMessage('COMMON_YES');
      $this->_show_choice[0]['value'] = 1;
      $this->_show_choice[1]['text'] = $translator->getMessage('COMMON_NO');
      $this->_show_choice[1]['value'] = -1;

     // choice show clock pulse in future
      $this->_future_choice[0]['text'] = '0';
      $this->_future_choice[0]['value'] = -1;
     for ($i=1; $i<9; $i++) {
         $this->_future_choice[$i]['text'] = $i;
         $this->_future_choice[$i]['value'] = $i;
     }

      // counter
      if ( $this->_counter == 0) {
         if ( isset($this->_item) ) {
            $this->_counter = count($this->_item->getTimeTextArray());
         } else {
         $this->_counter = count($this->_form_post['clock_pulse']);
       }
       if ($this->_counter == 0) {
            $this->_counter = 1;
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $translator = $this->_environment->getTranslationObject();

      $this->setHeadline($this->_headline);
      $this->_form->addRadioGroup('show_time',$this->_translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_CHOICE_TITLE'),'',$this->_show_choice,'-1',true,true);

      $avaiable_languages = $this->_environment->getAvailableLanguageArray();
      $first = true;
      foreach ($avaiable_languages as $language) {
         if ($first) {
      $first = false;
   } else {
      $this->_form->combine('vertical');
   }
         $this->_form->addTextField('name['.mb_strtoupper($language, 'UTF-8').']','',$this->_translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_NAME_TITLE'),'','','45',false,'','','','',$language);
      }

      $this->_form->addSelect('future',$this->_future_choice,'',$this->_translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_FUTURE_TITLE'),'',1,false,true);
      $this->_form->addEmptyLine('empty');

      for ($i=1; $i<=$this->_counter; $i++) {
         $this->_form->addButton('delete_'.$i,$this->_translator->getMessage('COMMON_DELETE_BUTTON'),$this->_translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_TEXT_TITLE',$i),$this->_translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_TEXT_DESC'));
         foreach ($avaiable_languages as $language) {
            $this->_form->combine('vertical');
            $this->_form->addTextField('clock_pulse['.$i.']['.mb_strtoupper($language, 'UTF-8').']','','','','','45',false,'','','','',$language);
         }
         $this->_form->combine('vertical');
         $this->_form->addEmptyLine('empty');
         $this->_form->combine('vertical');
         $this->_form->addTextField('clock_pulse['.$i.'][BEGIN]','','','','5','10',false,'','','','',$this->_translator->getMessage('COMMON_FROM2'));
         $this->_form->combine('horizontal');
         $this->_form->addTextField('clock_pulse['.$i.'][END]','','','','5','10',false,'','','','',$this->_translator->getMessage('COMMON_TO'));
      }
      $this->_form->addButton('option',$this->_translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_AD_TITLE'));

      $this->_form->addEmptyLine('empty');
      $this->_form->addButtonBar('option',$translator->getMessage('PREFERENCES_SAVE_BUTTON'),'','','','','','');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;

         if ( !empty($this->_form_post['clock_pulse']) ) {
            foreach ($this->_form_post['clock_pulse'] as $key => $name) {
            foreach ($name as $lang => $value) {
                  $this->_values['clock_pulse['.$key.']['.$lang.']'] = $value;
            }
            }
         }

         if ( !empty($this->_form_post['name']) ) {
            foreach ($this->_form_post['name'] as $key => $name) {
               $this->_values['name['.$key.']'] = $name;
            }
         }

      } elseif ( !empty($this->_item) ) {
         if ( $this->_item->showTime() ) {
            $this->_values['show_time'] = 1;
         } else {
            $this->_values['show_time'] = -1;
         }

       $time_text_array = $this->_item->getTimeTextArray();
         if ( !empty($time_text_array) ) {
            foreach ($time_text_array as $key => $name) {
            foreach ($name as $lang => $value) {
                  $this->_values['clock_pulse['.$key.']['.$lang.']'] = $value;
            }
            }
         }

       $time_name_array = $this->_item->getTimeNameArray();
       if ( !empty($time_name_array) ) {
            foreach ($time_name_array as $key => $name) {
               $this->_values['name['.$key.']'] = $name;
            }
         }

       $this->_values['future'] = $this->_item->getTimeInFuture();
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }

   function setCounter ($value) {
      $this->_counter = (int)$value;
   }
}
?>