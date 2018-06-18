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

/** the form class: discussion close
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_discussion_close_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_summary = '';

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

   function setSummary ($text) {
      $this->_summary = $text;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // headline
      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('DISCUSSION_CLOSE_TITLE');
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // discussion
      $this->_form->addHeadline('headline',$this->_headline);
      $this->_form->addHidden('iid','');
      if (isset($this->_item)) {
         $title = $this->_item->getTitle();
      }
      else {
         $title = '';
      }
      $this->_form->addTitleField('subject',$this->_translator->getMessage('DISCUSSION_CLOSE_TITLE'),$this->_translator->getMessage('COMMON_SUBJECT'),$this->_translator->getMessage('COMMON_TITLE_DESC'),200,40,true);
      $this->_form->addTextArea('summary','',$this->_translator->getMessage('DISCUSSION_CLOSE'),'',51);

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('DISCUSSION_CLOSE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();

      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post; // no encode here - encode in form-views
      } elseif ( isset($this->_item) ) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['summary'] = $this->_summary;
      }
   }
}
?>