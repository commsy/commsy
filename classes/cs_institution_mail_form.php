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
class cs_institution_mail_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing an array of institutions in the context
   */
   var $_institution_array = array();

  /**
   * string - name of the current context
   */
   var $_context_name = '';

  /** constructor: cs_institution_form
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function cs_institution_mail_form($environment) {
      $this->cs_rubric_form($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example institutions
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      // headline
      if ( isset($_GET['iid']) ){
         $label_manager =  $this->_environment->getLabelManager();
         $institution_item = $label_manager->getItem($_GET['iid']);
         $this->_headline = getMessage('EMAIL_TO_INSTITUTION_TITLE',$institution_item->getTitle());
      } else {
         $this->_headline = getMessage('EMAIL_TO_INSTITUTION_TITLE_WITHOUT');
      }
      // institutions
      $label_manager =  $this->_environment->getLabelManager();
      $label_manager->resetLimits();
      $label_manager->setContextLimit($this->_environment->getCurrentContextID());
      $label_manager->setTypeLimit(CS_INSTITUTION_TYPE);
      $label_manager->select();
      $label_list = $label_manager->get();
      $label_array = array();
      if ($label_list->getCount() > 0) {
         $label_item =  $label_list->getFirst();
         while ($label_item) {
            $temp_array['text'] = chunkText($label_item->getName(),'50');
            $temp_array['value'] = $label_item->getItemID();
            $label_array[] = $temp_array;
            $label_item =  $label_list->getNext();
         }
      }
      $this->_institution_array = $label_array;

      // context name
      $context = $this->_environment->getCurrentContextItem();

      $this->_context_name = $context->getTitle();
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      $this->_form->addHeadline('headline',$this->_headline);
      $this->_form->addTextField('subject','',getMessage('COMMON_MAIL_SUBJECT'),getMessage('COMMON_MAIL_SUBJECT_DESC'),'','53',true);
      $this->_form->addTextArea('mailcontent','',getMessage('COMMON_MAIL_CONTENT'),getMessage('COMMON_MAIL_CONTENT_DESC'), '58', '', '', true,'',false);
      if (!empty($this->_institution_array)) {
         $this->_form->addCheckBoxGroup('institutions',$this->_institution_array,'',getMessage('COMMON_RELEVANT_FOR'),getMessage('COMMON_RELEVANT_FOR_DESC'), true, false);
      }

      $yesno[][] = array();
      $yesno['0']['text']  = getMessage('COMMON_YES');
      $yesno['0']['value'] = getMessage('COMMON_YES');
      $yesno['1']['text']  = getMessage('COMMON_NO');
      $yesno['1']['value'] = getMessage('COMMON_NO');
      $this->_form->addRadioGroup('copytosender',getMessage('MAILCOPY_TO_SENDER'),getMessage('MAILCOPY_TO_SENDER_DESC'),$yesno,getMessage('COMMON_NO'),true,false);

      // buttons
      $this->_form->addButtonBar('option',getMessage('COMMON_MAIL_SEND_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if (empty($this->_form_post)) {
         global $_GET; // what is that ????? (TBD)
         $this->_values['institutions'] = array($_GET['iid']);
      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post; // no encode here - encode in form-views
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      if ( !empty($this->_form_post['institutions']) ) {
		 $manager = $this->_environment->getInstitutionManager();
         $iids = $this->_form_post['institutions'];
		 $counter = 0;

         foreach ($iids as $iid) {
	        $item = $manager->getItem($iid);
	        $user_list = $item->getMemberItemList();
			$counter = $counter + $user_list->getCount();
         }
 
         if ($counter == 0) {
		    $this->_error_array[] = getMessage('INSTITUTION_MAIL_NO_RECIPIENTS_ERROR');
            $this->_form->setFailure('institutions','');
         }
      }
   }
}
?>