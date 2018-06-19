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
class cs_discarticle_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_did = NULL; // ID of the discussion this article belongs to
   var $_ref_position = '1'; // Position of the answered discarticle
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
         $this->_headline = $this->_translator->getMessage('DISCARTICLE_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('DISCARTICLE_EDIT');
         } else {
            $this->_headline = $this->_translator->getMessage('DISCARTICLE_ENTER_NEW');
         }
      } else {
         $this->_headline = $this->_translator->getMessage('DISCARTICLE_ENTER_NEW');
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
      $this->_form->addAnchor('discarticle_form');
      $text_title = $this->_translator->getMessage('COMMON_SUBJECT');
      $text_discription = $this->_translator->getMessage('DISCUSSION_ARTICLE');
      if ( $this->_detail_mode ) {
         if($this->_number == '') {
            $text_title = '';
         } else {
            $text_title = $this->_number.'.';
         }
         $text_discription = '';
      }

      // discussion
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('discussion_id','');
      $this->_form->addHidden('ref_position','');
      $this->_form->addTitleField('subject','',$text_title,'',200,45,true);
      $this->_form->addTextArea('description','',$text_discription,'',59);

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
      $this->_form->addFilefield('upload', $this->_translator->getMessage('MATERIAL_FILES'), '', 12, false, $this->_translator->getMessage('MATERIAL_UPLOADFILE_BUTTON'),'option',$this->_with_multi_upload);
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
            $this->_form->addButtonBar('option',$this->_translator->getMessage('DISCARTICLE_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
         } else {
            $this->_form->addButtonBar('option',$this->_translator->getMessage('DISCARTICLE_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','');
         }
      } else {
         $this->_form->addEmptyLine();

         $discussion_manager = $this->_environment->getDiscussionManager();
		 $discussion = $discussion_manager->getItem($this->_did);
		 $discussion_type = $discussion->getDiscussionType();

		 $this->_form->addButton('option',$this->_translator->getMessage('DISCARTICLE_SAVE_BUTTON'),'');
         if(   $this->_environment->getCurrentModule() == 'discussion' &&
               $this->_environment->getCurrentFunction() == 'detail' &&
               $discussion_type == 'threaded') {
            $this->_form->combine('vertical');
            $this->_form->addButton('option',$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'');

         }
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
         $this->_values['discussion_id'] = $this->_item->getDiscussionID();
         $this->_values['ref_position'] = $this->_ref_position;
         $this->_values['subject'] = $this->_item->getSubject();
         $this->_values['description'] = $this->_item->getDescription();
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
         $this->_values['discussion_id'] = $this->_did;
         if (isset($this->_ref_did)){
            $discarticle_manager = $this->_environment->getDiscussionArticlesManager();
            $discarticle_item = $discarticle_manager->getItem($this->_ref_did);
#            $this->_values['subject'] = 'Re:'.$discarticle_item->getSubject();
         }
         $this->_values['ref_position'] = $this->_ref_position;
      }
   }

   function setDiscussionID($did) {
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
