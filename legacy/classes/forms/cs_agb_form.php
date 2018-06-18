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
class cs_agb_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * string - containing the headline of the form
   */
   var $_agb_text = NULL;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      // agb text
      $context = $this->_environment->getCurrentContextItem();
      $text_array = $context->getAGBTextArray();
      $language = $context->getLanguage();
      if ( $language == 'user' ) {
         $language = getSelectedLanguage();
      }
      if ( !empty($text_array[mb_strtoupper($language, 'UTF-8')]) ) {
         $this->_agb_text = $this->_environment->getTextConverter()->cleanDataFromTextArea($text_array[mb_strtoupper($language, 'UTF-8')]);
      } else {
         foreach($text_array as $key => $value){
            if(!empty($value)){
               $this->_agb_text = $this->_environment->getTextConverter()->cleanDataFromTextArea($text_array[mb_strtoupper($key, 'UTF-8')]);
               $this->_agb_text .= '<br/><br/><b>'.$this->_translator->getMessage('AGB_NO_AGS_FOUND_IN_SELECTED_LANGUAGE').'</b>';
               break;
            }
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->setHeadline($this->_translator->getMessage('AGB_CHANGE_TITLE'));
      $this->_form->addText('agb_text','',$this->_agb_text);
      if ( !($this->_environment->getCurrentModule() == 'agb' and
             $this->_environment->getCurrentFunction() == 'index')
         ) {
         $this->_form->addEmptyLine();
         if ( !$this->_environment->inPortal() ) {
            $this->_form->addButtonBar('option',
            $this->_translator->getMessage('AGB_ACCEPTANCE_BUTTON'),
            $this->_translator->getMessage('COMMON_CANCEL_BUTTON'),
            $this->_translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON_ROOM'));
         } else {
            if($this->_environment->getCurrentUserItem()->getCreationDate() > getCurrentDateTimeMinusMinutesInMySQL(1) ) {
               $this->_form->addHidden('is_no_user', '1');
               $this->_form->addButtonBar('option',
               $this->_translator->getMessage('AGB_ACCEPTANCE_BUTTON'),
               $this->_translator->getMessage('COMMON_CANCEL_BUTTON'));

            }
            else {
               $this->_form->addButtonBar('option',
               $this->_translator->getMessage('AGB_ACCEPTANCE_BUTTON'),
               $this->_translator->getMessage('COMMON_CANCEL_BUTTON'),
               $this->_translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON_PORTAL'));
            }
         }
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }
}
?>