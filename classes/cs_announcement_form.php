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
  /**
   * array - containing an array of shown buzzwords in the context
   */
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
   function cs_announcement_form($environment) {
      $this->cs_rubric_form($environment);
   }




/***buzzwords and tags ***/

   /** set buzzwords from session
    * set an array with the buzzwords from the session
    *
    * @param array array of buzzwords out of session
    */
   function setSessionBuzzwordArray ($value) {
      $this->_session_buzzword_array = (array)$value;
   }

   /** set tags from session
    * set an array with the tags from the session
    *
    * @param array array of tags out of session
    */
   function setSessionTagArray ($value) {
      $this->_session_tag_array = (array)$value;
   }

   function _initTagArray($item = NULL, $ebene = 0) {
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            $current_item = $list->getFirst();
            while ( $current_item ) {
               $temp_array = array();
               $text = '';
               $i = 0;
               while($i < $ebene){
                  $text .='>  ';
                  $i++;
               }
               $text .= $current_item->getTitle();
               $temp_array['text']  = $text;
               $temp_array['value'] = $current_item->getItemID();
               $this->_tag_array[] = $temp_array;
               $this->_initTagArray($current_item, $ebene+1);
               $current_item = $list->getNext();
            }
         }
      }
   }
/***buzzwords and tags ***/





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
      } elseif (!empty($this->_form_post['iid'])) {
         $manager = $this->_environment->getManager(CS_ANNOUNCEMENT_TYPE);
         $item = $manager->getItem($this->_form_post['iid']);
         $creator_item = $item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } else {
         $current_user = $this->_environment->getCurrentUser();
         $fullname = $current_user->getFullname();
      }
      $public_array = array();
      $temp_array['text']  = getMessage('RUBRIC_PUBLIC_YES');
      $temp_array['value'] = 1;
      $public_array[] = $temp_array;
      $temp_array['text']  = getMessage('RUBRIC_PUBLIC_NO', $fullname);
      $temp_array['value'] = 0;
      $public_array[] = $temp_array;
      $this->_public_array = $public_array;

      // headline
      if (!empty($this->_item)) {
         $this->_headline = getMessage('ANNOUNCEMENT_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = getMessage('ANNOUNCEMENT_EDIT');
         } else {
            $this->_headline = getMessage('ANNOUNCEMENT_ENTER_NEW');
         }
      } else {
         $this->_headline = getMessage('ANNOUNCEMENT_ENTER_NEW');
      }
      $this->setHeadline($this->_headline);

      // materials
      $material_array = array();
      if (isset($this->_session_material_array)) {
         $material_manager = $this->_environment->getMaterialManager();
         foreach ( $this->_session_material_array as $material ) {
            $material_item = $material_manager->getItem($material['iid']);
            $temp_array['text'] = $material_item->getTitle();
            $temp_array['value'] = '<VALUE><ID>'.$material['iid'].'</ID><VERSION>'.$material['vid'].'</VERSION></VALUE>';
            $material_array[] = $temp_array;
         }
      } elseif (isset($this->_item)) {
         $material_list = $this->_item->getMaterialList();
         $material_array_for_session = array();
         if ($material_list->getCount() > 0) {
            $material_item = $material_list->getFirst();
            while ($material_item) {
               $temp_array['text'] = $material_item->getTitle();
               $temp_array['value'] = '<VALUE><ID>'.$material_item->getItemID().'</ID><VERSION>'.$material_item->getVersionID().'</VERSION></VALUE>';
               $material_array[] = $temp_array;
               $material_item = $material_list->getNext();
            }
         }
      }
      $this->_material_array = $material_array;




/***buzzwords and tags ***/
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->resetLimits();
      $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_manager->select();
      $buzzword_list = $buzzword_manager->get();
      $buzzword_array = array();
      $temp_array['text'] = '*'.getMessage('COMMON_NO_BUZZWORDS');
      $temp_array['value'] = '-2';
      $buzzword_array[] = $temp_array;
      if ($buzzword_list->getCount() > 0) {
         $buzzword_item =  $buzzword_list->getFirst();
         while ($buzzword_item) {
            $temp_array['text'] = $buzzword_item->getName();
            $temp_array['value'] = $buzzword_item->getItemID();
            $buzzword_array[] = $temp_array;
            $buzzword_item =  $buzzword_list->getNext();
         }
      }
      $this->_buzzword_array = $buzzword_array;
      $buzzword_array = array();
      if (!empty($this->_session_buzzword_array)) {
         foreach ( $this->_session_buzzword_array as $buzzword ) {
            $temp_array['text'] = $buzzword['name'];
            $temp_array['value'] = $buzzword['id'];
            $buzzword_array[] = $temp_array;
         }
      } elseif (isset($this->_item)) {
         $buzzword_list = $this->_item->getBuzzwordList();
         $buzzword_list->sortby('name');
         if ($buzzword_list->getCount() > 0) {
            $buzzword_item = $buzzword_list->getFirst();
            while ($buzzword_item) {
               $temp_array['text'] = $buzzword_item->getTitle();
               $temp_array['value'] = $buzzword_item->getItemID();
               $buzzword_array[] = $temp_array;
               $buzzword_item = $buzzword_list->getNext();
            }
         }
      }
      $this->_shown_buzzword_array = $buzzword_array;

      // tags
      $current_context = $this->_environment->getCurrentContextItem();
      $temp_array['text'] = '*'.getMessage('COMMON_NO_TAGS');
      $temp_array['value'] = '-2';
      $this->_tag_array[] = $temp_array;
      $tag_manager = $this->_environment->getTagManager();
      $root_item = $tag_manager->getRootTagItem();
      $this->_initTagArray($root_item,0);

      $tag_array = array();
      $tag2tag_manager = $this->_environment->getTag2TagManager();
      if (!empty($this->_session_tag_array)) {
         foreach ( $this->_session_tag_array as $tag ) {
            $shown_tag_array = $tag2tag_manager->getFatherItemIDArray($tag['id']);
            $text = '';
            if( !empty($shown_tag_array) ) {
               foreach( $shown_tag_array as $shown_tag ){
                  $father_tag_item = $tag_manager->getItem($shown_tag);
                  $text .= $father_tag_item->getTitle().' > ';
               }
            }
            $temp_array['text'] = $text.$tag['name'];
            $temp_array['value'] = $tag['id'];
            $tag_array[] = $temp_array;
         }
      } elseif (isset($this->_item)) {
         $tag_list = $this->_item->getTagList();
         if ($tag_list->getCount() > 0) {
            $tag_item = $tag_list->getFirst();
            while ($tag_item) {
               $temp_array['text'] = $tag_item->getTitle();
               $temp_array['value'] = $tag_item->getItemID();
               $tag_array[] = $temp_array;
               $tag_item = $tag_list->getNext();
            }
         }
      }
      $this->_shown_tag_array = $tag_array;
/***buzzwords and tags ***/


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

      // announcement
      $this->_form->addHidden('iid','');
      $this->_form->addTitleField('title','',getMessage('COMMON_TITLE'),'',200,46,true);
      $link = ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  array('module'=>$this->_environment->getCurrentModule(),'function'=>$this->_environment->getCurrentFunction(),'context'=>'HELP_COMMON_FORMAT'),
                  getMessage('HELP_COMMON_FORMAT_TITLE'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
      $this->_form->addDateTimeField('end_date_time','',
                              'dayEnd','timeEnd',10,10,
                              getMessage('ANNOUNCEMENT_TIME_DAY_END'),
                              getMessage('DATES_END_DAY'),
                              getMessage('DATES_END_TIME'),
                              getMessage('ANNOUNCEMENT_TIME_DAY_END_DESC'),true,false);
      $this->_form->addTextArea('description','',getMessage('COMMON_CONTENT'),getMessage('COMMON_CONTENT_DESC',$link),'',25);


      // rubric connections
      $this->_setFormElementsForConnectedRubrics();
      $this->_form->addEmptyline();

/***buzzwords and tags ***/
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->withBuzzwords() ){
         // buzzwords
         if ( !empty ($this->_shown_buzzword_array) ) {
            if ( $current_context->isBuzzwordMandatory() ){
               $this->_form->addCheckBoxGroup('buzzwordlist',$this->_shown_buzzword_array,'',getMessage('COMMON_BUZZWORDS'),getMessage('COMMON_BUZZWORD_DESC'),false,false,0,'','','','',false,false,false,8);
               $this->_form->combine();
            }else{
               $this->_form->addCheckBoxGroup('buzzwordlist',$this->_shown_buzzword_array,'',getMessage('COMMON_BUZZWORDS'),getMessage('COMMON_BUZZWORD_DESC'),false,false,0,'','','','',false,false,false,8);
               $this->_form->combine();
            }
         }
         if ( $current_context->isBuzzwordMandatory() ){
            $this->_form->addSelect('buzzword',$this->_buzzword_array,'',getMessage('COMMON_BUZZWORDS'),getMessage('COMMON_BUZZWORD_DESC'), 1, false,true,false,'','','','',16,false,false,8);
         }else{
            $this->_form->addSelect('buzzword',$this->_buzzword_array,'',getMessage('COMMON_BUZZWORDS'),getMessage('COMMON_BUZZWORD_DESC'), 1, false,false,false,'','','','',16,false,false,8);
         }
         $this->_form->combine('horizontal');
         $this->_form->addButton('option',getMessage('COMMON_ADD_BUZZWORD_BUTTON'),'','',210,false,'','',8);
         $this->_form->combine('vertical');
         $this->_form->addTextField('new_buzzword',"","","","", 29, false,'','','','left','','',false,'',8);
         $this->_form->combine('horizontal');
         $this->_form->addButton('option',getMessage('COMMON_NEW_BUZZWORD_BUTTON'),'','',210,false,'','',8);
      }
      if ( $current_context->withTags() ){
         // tags
         if ( !empty ($this->_shown_tag_array) ) {
            $this->_form->addCheckBoxGroup('taglist',$this->_shown_tag_array,'',getMessage('COMMON_TAGS'),getMessage('COMMON_TAG_DESC'),false,false,0,'','','','',false,false,false,8);
            $this->_form->combine();
         }
         if ( $current_context->isTagMandatory() ){
            $this->_form->addSelect('tag',$this->_tag_array,'',getMessage('COMMON_TAGS'),getMessage('COMMON_TAG_DESC'), 1, false,true,false,'','','','',16,false,false,8);
         }else{
            $this->_form->addSelect('tag',$this->_tag_array,'',getMessage('COMMON_TAGS'),getMessage('COMMON_TAG_DESC'), 1, false,false,false,'','','','',16,false,false,8);
         }
         $this->_form->combine('horizontal');
         $this->_form->addButton('option',getMessage('COMMON_ADD_TAG_BUTTON'),'','',210,false,'','',8);
      }
/***buzzwords and tags ***/



      // files
      $this->_form->addAnchor('fileupload');
      $val = ini_get('upload_max_filesize');
      $val = trim($val);
      $last = $val[strlen($val)-1];
      switch($last) {
         case 'k':
         case 'K':
            $val = $val * 1024;
            break;
         case 'm':
         case 'M':
            $val = $val * 1048576;
            break;
      }
      $meg_val = round($val/1048576);
      if ( !empty($this->_file_array) ) {
         $this->_form->addCheckBoxGroup('filelist',$this->_file_array,'',getMessage('MATERIAL_FILES'),getMessage('MATERIAL_FILES_DESC', $meg_val),false,false);
         $this->_form->combine('vertical');
      }
      $this->_form->addHidden('MAX_FILE_SIZE', $val);
      $this->_form->addFilefield('upload', getMessage('MATERIAL_FILES'), getMessage('MATERIAL_UPLOAD_DESC',$meg_val), 12, false, getMessage('MATERIAL_UPLOADFILE_BUTTON'),'option',$this->_with_multi_upload);
      $this->_form->combine('vertical');
      if ($this->_with_multi_upload) {
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
            if (strtoupper($operation_system) == 'LINUX') {
               $px = '360';
            } elseif (strtoupper($operation_system) == 'MAC OS') {
               $px = '352';
            }
         } elseif ($browser == 'MOZILLA') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (strtoupper($operation_system) == 'MAC OS') {
               $px = '336'; // camino
            }
         }
         $this->_form->addButton('option',getMessage('MATERIAL_BUTTON_MULTI_UPLOAD_YES'),'','',$px.'px');
      }
      $this->_form->combine('vertical');
      $this->_form->addText('max_size',$val,getMessage('MATERIAL_MAX_FILE_SIZE',$meg_val));

      // public radio-buttons
      if ( !isset($this->_item) ) {
         $this->_form->addRadioGroup('public',getMessage('RUBRIC_PUBLIC'),getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
      } else {
         $current_user = $this->_environment->getCurrentUser();
         $creator = $this->_item->getCreatorItem();
         if ($current_user->getItemID() == $creator->getItemID() or $current_user->isModerator()) {
            $this->_form->addRadioGroup('public',getMessage('RUBRIC_PUBLIC'),getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
         } else {
            $this->_form->addHidden('public','');
         }
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
         $this->_form->addButtonBar('option',getMessage('ANNOUNCEMENT_SAVE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',getMessage('ANNOUNCEMENT_CHANGE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','');
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_form_post['end_date_time'] = array();
         $this->_form_post['end_date_time'][] = !empty($this->_form_post['dayEnd']) ? $this->_form_post['dayEnd'] : NULL;
         $this->_form_post['end_date_time'][] = !empty($this->_form_post['timeEnd']) ? $this->_form_post['timeEnd'] : NULL;
         unset($this->_form_post['dayEnd']);
         unset($this->_form_post['timeEnd']);
         $this->_values = $this->_form_post;
         if ( !isset($this->_values['public']) ) {
            $this->_values['public'] = ($this->_environment->inProjectRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
         }
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


/***buzzwords and tags ***/
         $buzzword_array = array();
         $buzzword_list = $this->_item->getBuzzwordList();
         if ($buzzword_list->getCount() > 0) {
            $buzzword_item = $buzzword_list->getFirst();
            while ($buzzword_item) {
               $buzzword_array[] = $buzzword_item->getItemID();
               $buzzword_item = $buzzword_list->getNext();
            }
         }
         if(isset($this->_form_post['buzzwordlist'])){
            $this->_values['buzzwordlist'] = $this->_form_post['buzzwordlist'];
         }else{
            $this->_values['buzzwordlist'] = $buzzword_array;
         }

         // tag
         $tag_array = array();
         $tag_list = $this->_item->getTagList();
         if ($tag_list->getCount() > 0) {
            $tag_item = $tag_list->getFirst();
            while ($tag_item) {
               $tag_array[] = $tag_item->getItemID();
               $tag_item = $tag_list->getNext();
            }
         }
         if(isset($this->_form_post['taglist'])){
            $this->_values['taglist'] = $this->_form_post['taglist'];
         }else{
            $this->_values['taglist'] = $tag_array;
         }
/***buzzwords and tags ***/


      } else {
         $this->_values['public'] = ($this->_environment->inProjectRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
         if ( $this->_environment->inProjectRoom() ) {
            $context_item = $this->_environment->getCurrentContextItem();
            $time = $context_item->getTimeSpread();
            $this->_values['end_date_time'][] = getDateInLang(DateAdd($time,date("Y-m-d"),"Y-m-d"));
            $this->_values['end_date_time'][] = date("H:m");
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
            $this->_error_array[] = getMessage('COMMON_ERROR_FIELD_DATE_INVALID');
         }
      }


/***buzzwords and tags ***/
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->isTagMandatory() ){
         if ($this->_form_post['tag'] == -2 and empty($this->_form_post['taglist'])) {
            $this->_form_post['tag'] = -2;
            $this->_form->setFailure('tag','mandatory');
            $this->_error_array[] = getMessage('COMMON_ERROR_TAG_ENTRY',getMessage('COMMON_TAGS'));
         }
      }
      if ( $current_context->isBuzzwordMandatory() ){
         if ($this->_form_post['buzzword'] == -2 and empty($this->_form_post['buzzwordlist']) and empty($this->_form_post['new_buzzword'])) {
            $this->_form_post['buzzword'] = -2;
            $this->_form->setFailure('buzzword','mandatory');
            $this->_error_array[] = getMessage('COMMON_ERROR_BUZZWORD_ENTRY',getMessage('COMMON_BUZZWORDS'));
         }
      }
/***buzzwords and tags ***/


   }
}
?>
