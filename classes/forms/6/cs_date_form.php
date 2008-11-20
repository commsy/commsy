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

include_once('classes/cs_rubric_form.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_date_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
  var $_headline = NULL;

  /**
   * array - containing the materials of a dates
   */
   var $_material_array = array();

  /**
   * array - containing an array of groups in the context
   */
   var $_group_array = array();

  /**
   * array - containing an array of materials form the session
   */
   var $_session_material_array = array();

   /**
   * array - containing the values for the edit status for the item (everybody or creator)
   */
   var $_public_array = array();

   var $_mode_array = array();

   var $_calendar_date = false;

   var $_private_date_starting_date = '';

   var $_private_date_starting_time = '';

   var $_private_date_ending_date = '';

   var $_private_date_ending_time = '';

   var $_start_point = '';

   var $_buzzword_array = array();

   var $_tag_array = array();
  /**
   * array - containing an array of shown buzzwords in the context
   */
   var $_shown_buzzword_array = array();

   var $_shown_tag_array = array();

   var $_session_tag_array = array();

  /** constructor: cs_date_form
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_date_form($params) {
      $this->cs_rubric_form($params);
   }

   function setCalendarDateStatus(){
      $this->_calendar_date = true;
   }
   function setPrivateDateStartingDate($date){
      $this->_private_date_starting_date = $date;
   }

   function setPrivateDateStartingTime($time){
      $this->_private_date_starting_time = $time;
   }

   function setPrivateDateEndingDate($date){
      $this->_private_date_ending_date = $date;
   }

   function setPrivateDateEndingTime($time){
      $this->_private_date_ending_time = $time;
   }

   function getCalendarDateStatus(){
      return $this->_calendar_date;
   }

   function unsetCalendarDateStatus(){
      $this->_calendar_date = false;
   }

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
      // public
      if ( isset($this->_item) ) {
         $creator_item = $this->_item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } elseif (!empty($this->_form_post['iid'])) {
         $manager = $this->_environment->getManager(CS_DATE_TYPE);
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

      if (!empty($this->_item)) {
         $this->_headline = getMessage('DATES_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = getMessage('DATES_EDIT');
         } else {
            $this->_headline = getMessage('DATES_ENTER_NEW');
         }
      } else {
         $this->_headline = getMessage('DATES_ENTER_NEW');
      }

      // groups
      $label_manager =  $this->_environment->getLabelManager();
      $label_manager->resetLimits();
      $label_manager->setContextLimit($this->_environment->getCurrentContextID());
      $label_manager->setTypeLimit('group');
      $label_manager->select();
      $label_list = $label_manager->get();
      $label_array = array();
      if ($label_list->getCount() > 0) {
         $label_item =  $label_list->getFirst();
         while ($label_item) {
            $temp_array['text'] = $label_item->getName();
            $temp_array['value'] = $label_item->getItemID();
            $label_array[] = $temp_array;
            $label_item =  $label_list->getNext();
         }
      }
      $this->_group_array = $label_array;

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
      $this->setHeadline($this->_headline);

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

      if ( empty($this->_form_post['start_point']) ) {
         $session_item = $this->_environment->getSessionItem();
         if ( isset($session_item) ) {
            $history = $session_item->getValue('history');
            if ( !empty($history) ) {
               $this->_start_point = $history[0]['module'];
            }
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // dates
      $this->_form->addHidden('iid','');
      if ($this->getCalendarDateStatus()){
         $this->_form->addHidden('seldisplay_mode','calendar');
      }
      $this->_form->addHidden('start_point',$this->_start_point);
      $this->_form->addTitlefield('title','',getMessage('COMMON_TITLE'),getMessage('DATES_TITLE_DESC'),200,45,true);
      $this->_form->combine();
      $this->_form->addCheckbox('mode','','','',getMessage('DATES_NON_PUBLIC_FORM'),'');
      $this->_form->addDateTimeField('start_date_time','','dayStart','timeStart',13,13,getMessage('DATES_TIME_DAY_START'),getMessage('DATES_START_DAY'),getMessage('DATES_START_TIME'),getMessage('DATES_TIME_DAY_START_DESC'),TRUE,FALSE,100,100);
      $this->_form->addDateTimeField('end_date_time','','dayEnd','timeEnd',13,13,getMessage('DATES_TIME_DAY_END'),getMessage('DATES_END_DAY'),getMessage('DATES_END_TIME'),getMessage('DATES_TIME_DAY_END_DESC'),FALSE,FALSE,100,100);
      $this->_form->addTextfield('place','',getMessage('DATES_PLACE'),getMessage('DATES_PLACE_DESC'),100,50);
           $link = ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  array('module'=>$this->_environment->getCurrentModule(),'function'=>$this->_environment->getCurrentFunction(),'context'=>'HELP_COMMON_FORMAT'),
                  getMessage('HELP_COMMON_FORMAT_TITLE'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
      $this->_form->addTextArea('description','',getMessage('DATES_DESCRIPTION'),getMessage('COMMON_CONTENT_DESC',$link),'',15);

      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      if ( !$this->_environment->inPrivateRoom() ) {
         $this->_form->addEmptyline();
      }

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
      if ( !$this->_environment->inPrivateRoom() ) {
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
         $this->_form->addButtonBar('option',getMessage('DATES_SAVE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',getMessage('DATES_CHANGE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','');
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
         $temp_array = array();
         if ( !empty($this->_form_post['dayStart']) ) {
            $temp_array[] = $this->_form_post['dayStart'];
         } else {
            $temp_array[] = '';
         }
         if ( !empty($this->_form_post['timeStart']) ) {
            $temp_array[] = $this->_form_post['timeStart'];
         } else {
            $temp_array[] = '';
         }
         $this->_form_post['start_date_time'] = $temp_array;
         $temp_time_array = array();
         if ( !empty($this->_form_post['dayEnd']) ) {
            $temp_time_array['dayEnd'] = $this->_form_post['dayEnd'];
         } else {
            $temp_time_array['dayEnd'] = '';
         }
         if ( !empty($this->_form_post['timeEnd']) ) {
            $temp_time_array['timeEnd'] = $this->_form_post['timeEnd'];
         } else {
            $temp_time_array['timeEnd'] = '';
         }
         $this->_form_post['end_date_time'] = $temp_time_array;
         $this->_values = $this->_form_post;
         if ( !isset($this->_values['public']) ) {
            $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
         }
      } elseif ( isset($this->_item) ) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['title'] = $this->_item->getTitle();
         $this->_values['description'] = $this->_item->getDescription();
         $this->_values['mode'] = $this->_item->getDateMode();

         // DATE AND TIME
         $temp_array = array();

         $temp = convertDateFromInput($this->_item->getStartingDay(),$this->_environment->getSelectedLanguage());
         if ($temp['conforms']) {
            $temp_array['dayStart'] = getDateInLang($this->_item->getStartingDay());
         } else {
            $temp_array['dayStart'] =  $this->_item->getStartingDay();
         }

         $temp = convertTimeFromInput($this->_item->getStartingTime());
            if ($temp['conforms'] == TRUE) {
               $temp_array['timeStart'] = getTimeLanguage($this->_item->getStartingTime());
            } else {
               $temp_array['timeStart'] = $this->_item->getStartingTime();
            }

         $this->_values['start_date_time'] = $temp_array;
         $temp_array = array();

         $temp = convertDateFromInput($this->_item->getEndingDay(),$this->_environment->getSelectedLanguage());
         if ($temp['conforms']) {
            $temp_array['dayEnd'] =  getDateInLang($this->_item->getEndingDay());
         } else {
            $temp_array['dayEnd'] =  $this->_item->getEndingDay();
         }

         $temp = convertTimeFromInput($this->_item->getEndingTime());
         if ($temp['conforms'] == TRUE) {
            $temp_array['timeEnd'] = getTimeLanguage($this->_item->getEndingTime());
         } else {
            $temp_array['timeEnd'] = $this->_item->getEndingTime();
         }

         $this->_values['end_date_time'] = $temp_array;
         $this->_values['place'] = $this->_item->getPlace();
         $this->_values['public'] = $this->_item->isPublic();
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

      } else {
         $temp_array['dayStart'] = $this->_private_date_starting_date;
         $temp_array['timeStart'] = $this->_private_date_starting_time;
         $this->_values['start_date_time'] = $temp_array;
         $temp_array = array();
         $temp_array['dayEnd'] = $this->_private_date_ending_date;
         $temp_array['timeEnd'] = $this->_private_date_ending_time;
         $this->_values['end_date_time'] = $temp_array;
         $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
      }
   }

    /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      $error = $this->_check_language_date_time_format();
      if (!$error) {
         $this->_check_start_end_time();
      }
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

   }

   function _check_language_date_time_format() {
      $error = false;
      $environment = $this->_environment;
      $lang = $environment->getSelectedLanguage();
      $date_start = convertDateFromInput($this->_form_post['dayStart'],$lang);
      $date_end = convertDateFromInput($this->_form_post['dayEnd'],$lang);
      if ($date_start['error'] == true OR $date_end['error'] == true) {
         $this->_error_array[] = getMessage('DATES_WRONG_DATE_FORMAT');
         $error = true;
      }
      return $error;

   }

   function _check_start_end_time() {
   //check start date and time
      $environment = $this->_environment;
      $lang = $environment->getUserLanguage();
      $date_start = convertDateFromInput($this->_form_post['dayStart'],$lang);
      $time_start = convertTimeFromInput($this->_form_post['timeStart']);
      if($date_start['conforms'] != '') {
         $start_timestamp = $date_start['timestamp'];
         if($time_start['conforms'] != '') {
            $start_timestamp .= $time_start['timestamp'];
         } else {
            $start_timestamp .= '000000';
         }
      }
      $date_end = convertDateFromInput($this->_form_post['dayEnd'],$lang);
      $time_end = convertTimeFromInput($this->_form_post['timeEnd']);
      if($date_end['conforms'] != '') {
         $end_timestamp = $date_end['timestamp'];
      } else {
         $end_timestamp = $date_start['timestamp'];
      }

      if($time_end['conforms'] != '') {
         $end_timestamp .= $time_end['timestamp'];
      } else {
         $end_timestamp .= '000000';
      }

      if($date_start['conforms'] != '') {
         if ($date_end['conforms'] != '' and (($end_timestamp - $start_timestamp) < 0)) {
            $this->_error_array[] = getMessage('DATES_END_DATE_BEFORE_START_DATE');
         }
      }
   }
}
?>