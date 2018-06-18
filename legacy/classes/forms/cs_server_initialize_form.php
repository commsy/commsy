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

/** class for commsy form: server initialize
 * this class implements an interface for the creation of a form in the commsy style: server initialize
 */
class cs_server_initialize_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the options for a choice of languages
   */
   var $_language_options = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      // headline
      $this->_headline = $this->_translator->getMessage('SERVER_INITIALIZE_FORM_TITLE');

      // language options
      $languages = $this->_environment->getAvailableLanguageArray();
      $i=0;
      foreach ($languages as $language) {
         $options[$i]['value'] = $language;
         $options[$i]['text'] = $this->_translator->getLanguageLabelOriginally($language);
         $i++;
      }
      $this->_language_options = $options;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // headline
      $this->_form->addHeadline('headline',$this->_headline);

      // text and options
      $this->_form->addText('hints',$this->_translator->getMessage('COMMON_HINTS'),$this->_translator->getMessage('SERVER_INITIALIZE_FORM_HINTS'));
      $this->_form->addTextField('firstname','',$this->_translator->getMessage('USER_FIRSTNAME'),'','','',true);
      $this->_form->addTextField('lastname','',$this->_translator->getMessage('USER_LASTNAME'),'','','',true);
      $this->_form->addTextField('email','',$this->_translator->getMessage('USER_EMAIL'),$this->_translator->getMessage('USER_EMAIL_DESC'),'','',true);
      $this->_form->addText('user_id',$this->_translator->getMessage('USER_USER_ID'),'root');
      $this->_form->addHidden('user_id','root');
      $this->_form->addPassword('password','',$this->_translator->getMessage('USER_PASSWORD'),'','','',true);
      $this->_form->addPassword('password2','',$this->_translator->getMessage('USER_PASSWORD2'),$this->_translator->getMessage('USER_PASSWORD2_DESC'),'','',true);
      $this->_form->addSelect('language',$this->_language_options,'',$this->_translator->getMessage('USER_LANGUAGE'),$this->_translator->getMessage('USER_LANGUAGE_DESC'),'','',true);

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      if (!empty($this->_form_post)) {
         $this->_values = $this->_form_post;
      } else {
         $language_selected = array();
         $language_selected[] = $this->_environment->getSelectedLanguage();
         $this->_values['language'] = $language_selected;
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    *
    * @author CommSy Development Group
    */
   function _checkValues () {
      // password check
      if ($this->_form_post['password'] != $this->_form_post['password2']) {
         $this->_error_array[] = $this->_translator->getMessage('USER_PASSWORD_ERROR');
         $this->_form->setFailure('password','');
         $this->_form->setFailure('password2','');
      }
   }
}
?>