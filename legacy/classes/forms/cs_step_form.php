<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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
class cs_step_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_did = NULL; // ID of the todo this article belongs to
   var $_ref_position = '1'; // Position of the answered step
   var $_ref_did = NULL; // ID of the article this article answers
   private $_detail_mode = false;
   private $_number = 0;

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

   public function setDetailMode ( $value ) {
      $this->_detail_mode = true;
      $this->_number = $value;
   }

   /**
    * Set the refId for this annotation
    *
    * @param int an unique refId of the item
    */
   function setRefId($value) {
      $this->_ref_iid = $value;
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

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // headline
      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('STEP_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('STEP_EDIT');
         } else {
            $this->_headline = $this->_translator->getMessage('STEP_ENTER_NEW');
         }
      } else {
         $this->_headline = $this->_translator->getMessage('STEP_ENTER_NEW');
      }

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

      $text_title = $this->_translator->getMessage('COMMON_TITLE');
      $text_discription = $this->_translator->getMessage('COMMON_DESCRIPTION');
      $text_time = $this->_translator->getMessage('STEP_MINUTES');
      if ( $this->_detail_mode ) {
         $text_title = $this->_number.'.';
         $text_discription = '';
         $text_time .= ':';
      }

      // todo
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('todo_id','');
      $this->_form->addHidden('ref_position','');
      $this->_form->addTitleField('subject','',$text_title,'',200,45,true);
      $this->_form->addTextField('minutes','',$text_time,'',200,4,true);
      $time_type = array();
      $time_type[] = array('text'  => $this->_translator->getMessage('TODO_TIME_MINUTES'),
                           'value' => '1');
      $time_type[] = array('text'  => $this->_translator->getMessage('TODO_TIME_HOURS'),
                           'value' => '2');
      $time_type[] = array('text'  => $this->_translator->getMessage('TODO_TIME_DAYS'),
                           'value' => '3');
      $this->_form->combine('horizontal');
      $this->_form->addSelect('time_type',$time_type,'',$this->_translator->getMessage('TODO_TIME_TYPE'),'', 1, false,false,false,'','','','',12,true);
      $this->_form->addTextArea('description','',$text_discription,'',59);

      // files
      $this->_form->addAnchor('fileupload');
      $val = $this->_environment->getCurrentContextItem()->getMaxUploadSizeInBytes();
      $meg_val = round($val/1048576);
      if ( !empty($this->_file_array) ) {
         $this->_form->addCheckBoxGroup('filelist',$this->_file_array,'',$this->_translator->getMessage('MATERIAL_FILES'),$this->_translator->getMessage('MATERIAL_FILES_DESC', $meg_val),false,false);
         $this->_form->combine('vertical');
      }
      $this->_form->addHidden('MAX_FILE_SIZE', $val);
      $this->_form->addFilefield('upload', ''/*$this->_translator->getMessage('MATERIAL_FILES')*/, '', 12, false, $this->_translator->getMessage('MATERIAL_UPLOADFILE_BUTTON'),'option',$this->_with_multi_upload);
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
      if ( !$this->_detail_mode ) {
         $this->_form->combine('vertical');
      }
      $this->_form->addText('max_size','',$this->_translator->getMessage('MATERIAL_MAX_FILE_SIZE',$meg_val));

      $session = $this->_environment->getSession();
      $new_upload = false;
      if($session->issetValue('javascript') and $session->issetValue('flash')) {
      	if(($session->getValue('javascript') == '1') and ($session->getValue('flash') == '1')) {
      	   $new_upload = true;
      	}
      }
      if(!$new_upload) $this->_form->addText('old_upload', '', $this->_translator->getMessage('COMMON_UPLOAD_OLD'));

      // buttons
      if ( !$this->_detail_mode ) {
         $id = 0;
         if (isset($this->_item)) {
            $id = $this->_item->getItemID();
         } elseif (isset($this->_form_post)) {
            if (isset($this->_form_post['iid'])) {
               $id = $this->_form_post['iid'];
            }
         }
         if ( $id == 0 )  {
            $this->_form->addButtonBar('option',$this->_translator->getMessage('STEP_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
         } else {
            $this->_form->addButtonBar('option',$this->_translator->getMessage('STEP_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','');
         }
      } else {
         $this->_form->addEmptyLine();
         $this->_form->addButton('option',$this->_translator->getMessage('STEP_CHANGE_BUTTON'),'');
      }
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
         $this->_values['todo_id'] = $this->_item->getTodoID();
         $this->_values['ref_position'] = $this->_ref_position;
         $this->_values['subject'] = $this->_item->getTitle();
         $this->_values['description'] = $this->_item->getDescription();
         $minutes = $this->_item->getMinutes();
         switch ($this->_item->getTimeType()){
            case 2: $minutes = $minutes/60;break;
            case 3: $minutes = ($minutes/60)/8;break;
         }
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $minutes = str_replace('.',',',$minutes);
         }
         $this->_values['minutes'] = $minutes;
         $this->_values['time_type'] = $this->_item->getTimeType();
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

      } elseif ( isset($this->_did) ) {
         $this->_values['todo_id'] = $this->_did;
         $this->_values['ref_position'] = $this->_ref_position;
      }
      if ( empty($this->_values['todo_id'])
           and !empty($this->_ref_iid) ) {
         $this->_values['todo_id'] = $this->_ref_iid;
      }
   }

   function _checkValues () {
      if ( isset($this->_form_post['minutes']) ){
         $minutes = str_replace(',','.',$this->_form_post['minutes']);
         if(!is_numeric($minutes)){
            $this->_form->setFailure('minutes','mandatory');
            $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_MINUTES_INT',$this->_translator->getMessage('COMMON_ERROR_MINUTES_INT'));
         }
      }
   }


   function setTodoID($did) {
      $this->_did = $did;
   }
   function setRefPosition($did) {
      $this->_ref_position = $did;
   }
   function setRefDid($did) {
      $this->_ref_did = $did;
   }
}
?>
