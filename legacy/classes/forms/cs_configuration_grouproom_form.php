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
class cs_configuration_grouproom_form extends cs_rubric_form {

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
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      // form fields
      $picture = '<br/><br/><img src="images/configuration_grouproom.gif" alt="picture_threaded" style=" width:290px; border:1px solid black; vertical-align: middle;"/>';
      $this->_form->addHidden('iid','');
      $this->_form->addCheckbox('grouproom',1,'',$this->_translator->getMessage('GROUPROOM_CONFIGURATION_CHOICE_TITLE'),$this->_translator->getMessage('GROUPROOM_CONFIGURATION_CHOICE_VALUE'),'');
      $this->_form->addText('grouproom_text',$this->_translator->getMessage('GROUPROOM_EXPLANATION_TITLE'),$this->_translator->getMessage('GROUPROOM_EXPLANATION_VALUE').$picture);

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( isset($this->_item) ) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['grouproom'] = $this->_item->isGrouproomActive();
      } elseif ( isset($this->_form_post) ) {
         $this->_values = $this->_form_post;
      }
   }
}
?>