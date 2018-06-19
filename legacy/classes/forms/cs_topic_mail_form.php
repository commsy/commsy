<?PHP
// $Id:
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
class cs_topic_mail_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;
   var $_description = NULL;

  /**
   * array - containing an array of topics in the context
   */
   var $_topic_array = array();

  /**
   * string - name of the current context
   */
   var $_context_name = '';

  /** constructor: cs_topic_form
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Topic
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example topics
    *
    * @author CommSy Development Topic
    */
   function _initForm () {
      // headline
      if ( isset($_GET['iid']) ){
         $label_manager =  $this->_environment->getLabelManager();
         $topic_item = $label_manager->getItem($_GET['iid']);
         $this->_headline = $this->_translator->getMessage('EMAIL_TO_TOPIC_TITLE',$topic_item->getTitle());
      } else {
         $this->_headline = $this->_translator->getMessage('EMAIL_TO_TOPIC_TITLE');
      }

      // context name
      $context = $this->_environment->getCurrentContextItem();
      $this->_context_name = $context->getTitle();

      // topics
      $label_manager =  $this->_environment->getLabelManager();
      $label_manager->resetLimits();
      $label_manager->setContextLimit($this->_environment->getCurrentContextID());
      $label_manager->setTypeLimit('topic');
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
      $this->_topic_array = $label_array;

   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Topic
    */
   function _createForm () {

      $this->_form->addHeadline('headline',$this->_headline);
      // topics
      $this->_form->addTextField('subject','',$this->_translator->getMessage('COMMON_MAIL_SUBJECT'),$this->_translator->getMessage('COMMON_MAIL_SUBJECT_DESC'),'','53',true);
      if ($this->_environment->inProjectRoom()){
         $this->_form->addTextArea('mailcontent','',$this->_translator->getMessage('COMMON_MAIL_CONTENT'),$this->_translator->getMessage('COMMON_MAIL_CONTENT_DESC'), '58', '', '', true,'',false);
      } elseif ($this->_environment->inCommunityRoom()) {
         $this->_form->addTextArea('mailcontent','',$this->_translator->getMessage('COMMON_MAIL_CONTENT'),$this->_translator->getMessage('COMMON_MAIL_CONTENT_DESC'), '58', '', '', true,'',false);
      }
      if (!empty($this->_topic_array)) {
         $this->_form->addCheckBoxGroup(CS_TOPIC_TYPE,$this->_topic_array,'',$this->_translator->getMessage('COMMON_RELEVANT_FOR'),$this->_translator->getMessage('COMMON_RELEVANT_FOR_DESC'), true, false);
      }

      $yesno[][] = array();
      $yesno['0']['text']  = $this->_translator->getMessage('COMMON_YES');
      $yesno['0']['value'] = $this->_translator->getMessage('COMMON_YES');
      $yesno['1']['text']  = $this->_translator->getMessage('COMMON_NO');
      $yesno['1']['value'] = $this->_translator->getMessage('COMMON_NO');
      $this->_form->addRadioGroup('copytosender',$this->_translator->getMessage('MAILCOPY_TO_SENDER'),$this->_translator->getMessage('MAILCOPY_TO_SENDER_DESC'),$yesno,$this->_translator->getMessage('COMMON_NO'),true,false);

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_MAIL_SEND_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the item or the form_post data
    *
    * @author CommSy Development Topic
    */
   function _prepareValues () {
      $this->_values = array();
      if (empty($this->_form_post)) {
         global $_GET;
         $this->_values[CS_TOPIC_TYPE] = array($_GET['iid']);
      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post; // no encode here - encode in form-views
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      if ( !empty($this->_form_post[CS_TOPIC_TYPE]) ) {
		 $manager = $this->_environment->getTopicManager();
         $topic_iids = $this->_form_post[CS_TOPIC_TYPE];
		 $counter = 0;

         foreach ($topic_iids as $iid) {
	        $item = $manager->getItem($iid);
	        $user_list = $item->getMemberItemList();
			$counter = $counter + $user_list->getCount();
         }

         if ($counter == 0) {
		    $this->_error_array[] = $this->_translator->getMessage('TOPIC_MAIL_NO_RECIPIENTS_ERROR');
            $this->_form->setFailure(CS_TOPIC_TYPE,'');
         }
      }
   }
}
?>