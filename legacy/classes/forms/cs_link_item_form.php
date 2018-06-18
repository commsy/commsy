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
include_once('functions/text_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_link_item_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the materials of a link_item
   */
   var $_material_array = array();

  /**
   * array - containing an array of materials form the session
   */
   var $_session_material_array = array();

  /**
   * array - containing an array of existing institution in the context
   */
   var $_institution_array = array();

  /**
   * boolean - true  -> institutions will be displayed
   *           false -> institutions will NOT be displayed
   */
   var $_institution_with = true;

   /**
   * array - containing an array of selected topics for the link_item
   */
   var $_topic_array = array();

  /**
   * boolean - true  -> search possibility will NOT be displayed
   *           false -> search possibility will be displayed
   */
   var $_topic_without_search = false;

  /**
   * array - containing an array of selected topics from the session
   */
   var $_session_topic_array = array();



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

   /** set materials from session
    * set an array with the materials from the session
    *
    * @param array array of materials out of session
    *
    * @author CommSy Development Group
    */
   function setSessionMaterialArray ($value) {
      $this->_session_material_array = (array)$value;
   }

   /** set topics form session
    * set an array with the topics form the session
    *
    * @param array array of topics out of session
    *
    * @author CommSy Development Group
    */
   function setSessionTopicArray ($value) {
      $this->_session_topic_array = (array)$value;
   }

   /** set institutions form session
    * set an array with the institutions form the session
    *
    * @param array array of institutions out of session
    *
    * @author CommSy Development Group
    */
   function setSessionInstitutionArray ($value) {
      $this->_session_institution_array = (array)$value;
   }


   /** set flag: display form without institutions
    * set a flag, so the form will not be display institutions
    *
    * @author CommSy Development Group
    */
   function withoutInstitutions () {
      $this->_institution_with = false;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example institutions
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      // headline
      $this->_headline = $this->_translator->getMessage('LINK_ITEM_NEW');
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // link_item
      $this->_form->addHeadline('headline',$this->_headline);
      $this->_form->addHidden('iid','');

      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      $this->_form->addButtonBar('option',$this->_translator->getMessage('LINK_ITEM_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();

      if (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         // rubric connections
         $this->_setValuesForRubricConnections();

      } elseif (!empty($this->_form_post)) {
         $this->_values = $this->_form_post; // no encode here - encode in form-views
      } else {
         $current_user = $this->_environment->getCurrentUserItem();
      }
   }
}
?>