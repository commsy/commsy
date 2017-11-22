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

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_language_form extends cs_rubric_form {


   /**
   * array - containing the language choices
   */
   var $_language_array = array();

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
       $this->_language_array = array();

       if ( $this->_environment->inServer() ) {
          $lang_array = $this->_translator->getAvailableLanguages();
       } elseif ( $this->_environment->inPortal() ) {
          $server_item = $this->_environment->getServerItem();
          $lang_array = $server_item->getAvailableLanguageArray();
       } else {
          include_once('functions/error_functions.php');
          trigger_error('context not server or portal',E_USER_ERROR);
       }

       foreach ($lang_array as $lang) {
          $temp_array = array();
          $temp_array['text'] = $this->_translator->getLanguageLabelTranslated($lang);
          $temp_array['text'] .= LF.'<img src="images/'.mb_strtolower($lang, 'UTF-8').'.gif" style="vertical-align: middle;" alt="Language '.mb_strtolower($lang, 'UTF-8').'"/>'.LF;
          $temp_array['value'] = $lang;
          $this->_language_array[] = $temp_array;
          unset($temp_array);
       }
   }


   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      $this->_form->addCheckboxGroup('languages',$this->_language_array,'',$this->_translator->getMessage('COMMON_LANGUAGE'),'',true,'','','','','','','',true);
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_item)) {
         $this->_values['languages'] = $this->_item->getAvailableLanguageArray();
      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }
   }
}
?>