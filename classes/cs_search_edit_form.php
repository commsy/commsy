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

include_once('classes/cs_rubric_form.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_search_edit_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_value_rubric =array();
   var $_value_attribute = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_search_edit_form($environment) {
      $this->cs_rubric_form($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      // headline
      $context = $this->_environment->getCurrentContextItem();
      $this->_headline = getMessage('SEARCH_EDIT_TITLE');

      $this->_value_rubric[0]['value'] = CS_ANNOUNCEMENT_TYPE;
      $this->_value_rubric[0]['text']  = getMessage('SEARCH_ANNOUNCEMENTS');
      $this->_value_rubric[2]['value'] = 'materials';
      $this->_value_rubric[2]['text']  = getMessage('SEARCH_MATERIALS');
      $this->_value_rubric[3]['value'] = 'user';
      $this->_value_rubric[3]['text']  = getMessage('SEARCH_USERS');
      $this->_value_rubric[4]['value'] = CS_TOPIC_TYPE;
      $this->_value_rubric[4]['text']  = getMessage('SEARCH_TOPICS');
      if ($context->withRubric(CS_INSTITUTION_TYPE)) {
         $this->_value_rubric[5]['value'] = 'institution';
         $this->_value_rubric[5]['text']  = getMessage('INSTITUTIONS');
      }
      $this->_value_attribute[0]['value'] = 'title';
      $this->_value_attribute[0]['text']  = getMessage('COMMON_TITLE_OR_NAME');
      $this->_value_attribute[1]['value'] = 'description';
      $this->_value_attribute[1]['text']  = getMessage('COMMON_DESCRIPTION');
      $this->_value_attribute[2]['value'] = 'modificator';
      $this->_value_attribute[2]['text']  = getMessage('COMMON_MODIFICATOR');
      $this->_value_attribute[3]['value'] = 'all_attributes';
      $this->_value_attribute[3]['text']  = getMessage('COMMON_ALL_ATTRIBUTES');
   }


   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $params = array();
      $params['modus'] = 'short';
      $action = ahref_curl($this->_environment->getCurrentContextID(),'campus_search','edit',$params,getMessage('COMMON_SEARCH_EASY'));
      unset($params);
      $this->_form->addHeadline('headline',$this->_headline,'',$action);
      $this->_form->addHidden('modus','normal');
      $this->_form->addTextfield('search_text','',getMessage('SEARCH_TEXT'),getMessage('SEARCH_TEXT_DESC'),'',25,true);
      $this->_form->addCheckBoxGroup('rubric_choice', $this->_value_rubric,'',getMessage('SEARCH_RUBRIC_CHOICE'),getMessage('SEARCH_RUBRIC_CHOICE_DESC'),true,'',2);
      $this->_form->addCheckBoxGroup('item_attribute', $this->_value_attribute,'',getMessage('SEARCH_ITEM_ATTRIBUTE'),getMessage('SEARCH_ITEM_ATTRIBUTE_DESC'),true);
      $this->_form->addButtonBar('option',getMessage('SEARCH_GO_BUTTON'),'');
      $this->_form->addHidden('form_view','campus_search_edit');

   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post; // no encode here - encode in form-views
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }
}
?>