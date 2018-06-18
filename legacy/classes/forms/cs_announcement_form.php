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
include_once('functions/date_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_announcement_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the materials of an annotation
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
   * array - containing an array of selected topics for the annotation
   */
   var $_topic_array = array();

  /**
   * array - containing an array of selected topics from the session
   */
   var $_session_topic_array = array();

   /**
   * array - containing the 2 choices of the public field
   */
   var $_public_array = array();

/***buzzwords and tags ***/
   var $_buzzword_array = array();

   var $_tag_array = array();

   var $_shown_buzzword_array = array();

   var $_shown_tag_array = array();

   var $_session_tag_array = array();
/***buzzwords and tags ***/


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

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // public
      if ( isset($this->_item) ) {
         $creator_item = $this->_item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } elseif ( !empty($this->_form_post['iid'])
                 and mb_strtolower($this->_form_post['iid'], 'UTF-8') != 'new'
               ) {
         $manager = $this->_environment->getManager(CS_ANNOUNCEMENT_TYPE);
         $item = $manager->getItem($this->_form_post['iid']);
         $creator_item = $item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } else {
         $current_user = $this->_environment->getCurrentUser();
         $fullname = $current_user->getFullname();
      }
      $public_array = array();
      $temp_array['text']  = $this->_translator->getMessage('RUBRIC_PUBLIC_YES');
      $temp_array['value'] = 1;
      $public_array[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('RUBRIC_PUBLIC_NO', $fullname);
      $temp_array['value'] = 0;
      $public_array[] = $temp_array;
      $this->_public_array = $public_array;

      // headline
      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('ANNOUNCEMENT_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('ANNOUNCEMENT_EDIT');
         } else {
            $this->_headline = $this->_translator->getMessage('ANNOUNCEMENT_ENTER_NEW');
         }
      } else {
         $this->_headline = $this->_translator->getMessage('ANNOUNCEMENT_ENTER_NEW');
      }
      $this->setHeadline($this->_headline);

      // files
      $file_array = array();
      if (!empty($this->_session_file_array)) {
         foreach ( $this->_session_file_array as $file ) {
            $temp_array['text'] = $file['name'];
            $temp_array['value'] = $file['file_id'];
            $file_array[] = $temp_array;
         }
      } elseif (isset($this->_item)) {
         $file_list = $this->_item->getFileList();
         if ($file_list->getCount() > 0) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $temp_array['text'] = $file_item->getDisplayname();
               $temp_array['value'] = $file_item->getFileID();
               $file_array[] = $temp_array;
               $file_item = $file_list->getNext();
            }
         }
      }
      $this->_file_array = $file_array;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      $current_context = $this->_environment->getCurrentContextItem();

      // announcement
      $this->_form->addHidden('iid','');
      $this->_form->addTitleField('title','',$this->_translator->getMessage('COMMON_TITLE'),'',200,58,true);
      $this->_form->addDateTimeField('end_date_time','',
                              'dayEnd','timeEnd',10,10,
                              $this->_translator->getMessage('ANNOUNCEMENT_SHOW_HOME_DATE'),
                              $this->_translator->getMessage('DATES_END_DAY'),
                              $this->_translator->getMessage('DATES_END_TIME'),
                              $this->_translator->getMessage('ANNOUNCEMENT_TIME_DAY_END_DESC'),true,false,100,100);
      $this->_form->addTextArea('description','',$this->_translator->getMessage('COMMON_CONTENT'),'','',20);

      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      // files
      $this->_form->addAnchor('fileupload');
      $val = $this->_environment->getCurrentContextItem()->getMaxUploadSizeInBytes();
      $meg_val = round($val/1048576);
      if ( !empty($this->_file_array) ) {
         $this->_form->addCheckBoxGroup('filelist',$this->_file_array,'',$this->_translator->getMessage('MATERIAL_FILES'),$this->_translator->getMessage('MATERIAL_FILES_DESC', $meg_val),false,false);
         $this->_form->combine('vertical');
      }
      $this->_form->addHidden('MAX_FILE_SIZE', $val);
      $this->_form->addFilefield('upload', $this->_translator->getMessage('MATERIAL_FILES'), $this->_translator->getMessage('MATERIAL_UPLOAD_DESC',$meg_val), 12, false, $this->_translator->getMessage('MATERIAL_UPLOADFILE_BUTTON'),'option',$this->_with_multi_upload);
      $this->_form->combine('vertical');
      //global $c_new_upload;
      $use_new_upload = false;
      $session = $this->_environment->getSession();
      if($session->issetValue('javascript') and $session->issetValue('flash')){
         if(($session->getValue('javascript') == '1') and ($session->getValue('flash') == '1')){
            $use_new_upload = true;
         }
      }
      if ($this->_with_multi_upload or $use_new_upload) {
         // do nothing
      } else {
         #$px = '245';
         $px = '331';
         $browser = $this->_environment->getCurrentBrowser();
         if ($browser == 'MSIE') {
            $px = '351';
         } elseif ($browser == 'OPERA') {
            $px = '321';
         } elseif ($browser == 'KONQUEROR') {
            $px = '361';
         } elseif ($browser == 'SAFARI') {
            $px = '380';
         } elseif ($browser == 'FIREFOX') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (mb_strtoupper($operation_system, 'UTF-8') == 'LINUX') {
               $px = '360';
            } elseif (mb_strtoupper($operation_system, 'UTF-8') == 'MAC OS') {
               $px = '352';
            }
         } elseif ($browser == 'MOZILLA') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (mb_strtoupper($operation_system, 'UTF-8') == 'MAC OS') {
               $px = '336'; // camino
            }
         }
         $this->_form->addButton('option',$this->_translator->getMessage('MATERIAL_BUTTON_MULTI_UPLOAD_YES'),'','',$px.'px');
      }
      $this->_form->combine('vertical');
      $this->_form->addText('max_size',$val,$this->_translator->getMessage('MATERIAL_MAX_FILE_SIZE',$meg_val));
      
      $session = $this->_environment->getSession();
      $new_upload = false;
      if($session->issetValue('javascript') and $session->issetValue('flash')) {
      	if(($session->getValue('javascript') == '1') and ($session->getValue('flash') == '1')) {
      	   $new_upload = true;
      	}
      }
      if(!$new_upload) $this->_form->addText('old_upload', '', $this->_translator->getMessage('COMMON_UPLOAD_OLD'));

      if ( !$this->_environment->inPrivateRoom() ){
         // public radio-buttons
         if ($current_context->withActivatingContent()){
            $this->_form->addCheckbox('private_editing',1,'',$this->_translator->getMessage('COMMON_RIGHTS'),$this->_public_array[1]['text'],$this->_translator->getMessage('COMMON_RIGHTS_DESCRIPTION'),false,false,'','',true,false);
            $this->_form->combine();
            $this->_form->addCheckbox('hide',1,'',$this->_translator->getMessage('COMMON_HIDE'),$this->_translator->getMessage('COMMON_HIDE'),'');
            $this->_form->combine('horizontal');
            $this->_form->addDateTimeField('start_date_time','','dayStart','timeStart',9,4,$this->_translator->getMessage('DATES_HIDING_DAY'),'('.$this->_translator->getMessage('DATES_HIDING_DAY'),$this->_translator->getMessage('DATES_HIDING_TIME'),$this->_translator->getMessage('DATES_TIME_DAY_START_DESC'),FALSE,FALSE,100,100,true,'left','',FALSE);
            $this->_form->combine('horizontal');
            $this->_form->addText('hide_end2','',')');
         }else{
             // public radio-buttons
             if ( !isset($this->_item) ) {
                $this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
             } else {
                $current_user = $this->_environment->getCurrentUser();
                $creator = $this->_item->getCreatorItem();
                if ($current_user->getItemID() == $creator->getItemID() or $current_user->isModerator()) {
                   $this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
                } else {
                   $this->_form->addHidden('public','');
                }
             }
         }
      } else {
         $this->_form->addHidden('public','');
      }

      // buttons
      $id = 0;
      if (isset($this->_item)) {
         $id = $this->_item->getItemID();
      } elseif (isset($this->_form_post)) {
         if (isset($this->_form_post['iid'])) {
            $id = $this->_form_post['iid'];
         }
      }
      if ( $id == 0 )  {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('ANNOUNCEMENT_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('ANNOUNCEMENT_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','');
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $current_context = $this->_environment->getCurrentContextItem();
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_form_post['end_date_time'] = array();
         $this->_form_post['end_date_time'][] = !empty($this->_form_post['dayEnd']) ? $this->_form_post['dayEnd'] : NULL;
         $this->_form_post['end_date_time'][] = !empty($this->_form_post['timeEnd']) ? $this->_form_post['timeEnd'] : NULL;
         unset($this->_form_post['dayEnd']);
         unset($this->_form_post['timeEnd']);
         $this->_values = $this->_form_post;
         $tmp_array = array();
         if (isset($this->_form_post['dayStart'])){
            $tmp_array['dayStart'] = $this->_form_post['dayStart'];
         }else{
            $tmp_array['dayStart'] = '';
         }
         if (isset($this->_form_post['timeStart'])){
            $tmp_array['timeStart'] = $this->_form_post['timeStart'];
         }else{
            $tmp_array['timeStart'] = '';
         }
         $this->_values['start_date_time'] = $tmp_array;
      } elseif ( isset($this->_item) ) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['title'] = $this->_item->getTitle();
         $this->_values['description'] = $this->_item->getDescription();
         $this->_values['public'] = $this->_item->isPublic();
         if (!$this->_item->getSeconddateTime() == '') {
            $this->_values['end_date_time'][] = getDateInLang($this->_item->getSeconddateTime());
         } else {
            $this->_values['end_date_time'][]= '';
         }
         if (!$this->_item->getSeconddateTime()== '') {
            $this->_values['end_date_time'][] = getTimeInLang($this->_item->getSeconddateTime());
         } else {
            $this->_values['end_date_time'][]= '';
         }
         $this->_setValuesForRubricConnections();

         // file
         $file_array = array();
         $file_list = $this->_item->getFileList();
         if ($file_list->getCount() > 0) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $file_array[] = $file_item->getFileID();
               $file_item = $file_list->getNext();
            }
         }
         if (isset($this->_form_post['filelist'])) {
            $this->_values['filelist'] = $this->_form_post['filelist'];
         } else {
            $this->_values['filelist'] = $file_array;
         }
         if ($current_context->withActivatingContent()){
            if ($this->_item->isPrivateEditing()){
               $this->_values['private_editing'] = 1;
            }else{
               $this->_values['private_editing'] = $this->_item->isPrivateEditing();
            }
         }else{
            $this->_values['public'] = $this->_item->isPublic();
         }
         $this->_values['hide'] = $this->_item->isNotActivated()?'1':'0';
         if ($this->_item->isNotActivated()){
            $activating_date = $this->_item->getActivatingDate();
            if (!strstr($activating_date,'9999-00-00')){
               $array = array();
               $array['dayStart'] = getDateInLang($activating_date);
               $array['timeStart'] = getTimeInLang($activating_date);
               $this->_values['start_date_time'] = $array;
            }
         }


      } else {
         if ( $this->_environment->inProjectRoom() ) {
            $context_item = $this->_environment->getCurrentContextItem();
            $time = $context_item->getTimeSpread();
            $this->_values['end_date_time'][] = getDateInLang(DateAdd($time,date("Y-m-d"),"Y-m-d"));
            $this->_values['end_date_time'][] = date("H:m");
         }
         if ($current_context->withActivatingContent()){
            if ( !isset($this->_values['private_editing']) ) {
               $this->_values['private_editing'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'0':'1'; //In projectrooms everybody can edit the item by default, else default is creator only
            }
         }else{
            if ( !isset($this->_values['public']) ) {
               $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
            }
         }
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    *
    * @author CommSy Development Group
    */
   function _checkValues () {
      if (!empty($this->_values['end_date_time'][0])) {
         $dayEnd = convertDateFromInput($this->_values['end_date_time'][0],$this->_environment->getSelectedLanguage());
         if(!$dayEnd['conforms']) {
            $this->_form->setFailure('end_date_time','',1);
            $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_FIELD_DATE_INVALID');
         }
         include_once('functions/date_functions.php');
         if ( !isDatetimeCorrect($this->_environment->getSelectedLanguage(),$this->_form_post['end_date_time'][0],$this->_form_post['end_date_time'][1]) ) {
            $this->_error_array[] = $this->_translator->getMessage('DATES_DATE_NOT_VALID');
            $this->_form->setFailure('end_date_time','');
         }
      }
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->isTagMandatory() ){
         $session = $this->_environment->getSessionItem();
         $tag_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_tag_ids');
         if (count($tag_ids) == 0){
            $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_TAG_ENTRY',$this->_translator->getMessage('MATERIAL_TAGS'));
         }
      }
      if ( $current_context->isBuzzwordMandatory() ){
         $session = $this->_environment->getSessionItem();
         $buzzword_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_buzzword_ids');
         if (count($buzzword_ids) == 0){
            $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_BUZZWORD_ENTRY',$this->_translator->getMessage('MATERIAL_BUZZWORDS'));
         }
      }
      if ($current_context->withActivatingContent() and !empty($this->_form_post['dayStart']) and !empty($this->_form_post['hide'])){
         include_once('functions/date_functions.php');
         if ( !isDatetimeCorrect($this->_environment->getSelectedLanguage(),$this->_form_post['dayStart'],$this->_form_post['timeStart']) ) {
            $this->_error_array[] = $this->_translator->getMessage('DATES_DATE_NOT_VALID');
            $this->_form->setFailure('start_date_time','');
         }
      }
   }
}
?>
