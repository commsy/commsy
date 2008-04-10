<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

include_once('classes/cs_rubric_form.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_chat_form extends cs_rubric_form {

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_configuration_chat_form ($environment) {
      $this->cs_rubric_form($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      // form fields
      $this->_form->addHidden('iid','');
      $desc = '<img src="images/chat_screenshot.gif" alt="Chat Screenshot" style=" border:1px solid black; vertical-align: middle;"/>';
      $this->_form->addCheckbox('chatlink',1,'',getMessage('CHAT_CONFIGURATION_CHAT'),getMessage('CHAT_CONFIGURATION_CHAT_VALUE'),'');
      $desc = getMessage('CHAT_CONFIGURATION_DESCRIPTION');
      $desc .= '<br/><img src="images/chat_screenshot.gif" alt="'.getMessage('COMMON_COLOR_DEFAULT').'" style=" border:1px solid black; vertical-align: middle;"/>';
      $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);

      // buttons
      $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['chatlink'] = $this->_item->isChatLinkActive();
      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }
   }
}
?>