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

   #var $_color = '#FFFF80';

   var $_recurring_select = 'weekly';

  /** constructor: cs_date_form
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
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
      } elseif ( !empty($this->_form_post['iid'])
                 and mb_strtolower($this->_form_post['iid'], 'UTF-8') != 'new'
               ) {
         $manager = $this->_environment->getManager(CS_DATE_TYPE);
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

      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('DATES_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('DATES_EDIT');
         } else {
            $this->_headline = $this->_translator->getMessage('DATES_ENTER_NEW');
         }
      } else {
         $this->_headline = $this->_translator->getMessage('DATES_ENTER_NEW');
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
      $current_context = $this->_environment->getCurrentContextItem();
      // dates
      $this->_form->addHidden('iid','');
      if ($this->getCalendarDateStatus()){
         $this->_form->addHidden('seldisplay_mode','calendar');
      }
      $this->_form->addHidden('start_point',$this->_start_point);
      $this->_form->addTitlefield('title','',$this->_translator->getMessage('COMMON_TITLE'),$this->_translator->getMessage('DATES_TITLE_DESC'),200,45,true);
      $this->_form->addDateTimeField('start_date_time','','dayStart','timeStart',13,13,$this->_translator->getMessage('DATES_TIME_DAY_START'),$this->_translator->getMessage('DATES_START_DAY'),$this->_translator->getMessage('DATES_START_TIME'),$this->_translator->getMessage('DATES_TIME_DAY_START_DESC'),TRUE,FALSE,100,100);
      $this->_form->addDateTimeField('end_date_time','','dayEnd','timeEnd',13,13,$this->_translator->getMessage('DATES_TIME_DAY_END'),$this->_translator->getMessage('DATES_END_DAY'),$this->_translator->getMessage('DATES_END_TIME'),$this->_translator->getMessage('DATES_TIME_DAY_END_DESC'),FALSE,FALSE,100,100);
      $this->_form->addTextfield('place','',$this->_translator->getMessage('DATES_PLACE'),$this->_translator->getMessage('DATES_PLACE_DESC'),100,50);

      $radio_values = array();
      $radio_values[0]['text'] = '<img src="images/spacer.gif" style="background-color:#999999; border:1px solid #cccccc;"/>';
      $radio_values[0]['value'] = '#999999';
      $radio_values[1]['text'] = '<img src="images/spacer.gif" style="background-color:#cc0000; border:1px solid #cccccc;"/>';
      $radio_values[1]['value'] = '#CC0000';
      $radio_values[2]['text'] = '<img src="images/spacer.gif" style="background-color:#ff6600; border:1px solid #cccccc;"/>';
      $radio_values[2]['value'] = '#FF6600';
      $radio_values[3]['text'] = '<img src="images/spacer.gif" style="background-color:#ffcc00; border:1px solid #cccccc;"/>';
      $radio_values[3]['value'] = '#FFCC00';
      $radio_values[4]['text'] = '<img src="images/spacer.gif" style="background-color:#ffff66; border:1px solid #cccccc;"/>';
      $radio_values[4]['value'] = '#FFFF66';
      $radio_values[5]['text'] = '<img src="images/spacer.gif" style="background-color:#33cc00; border:1px solid #cccccc;"/>';
      $radio_values[5]['value'] = '#33CC00';
      $radio_values[6]['text'] = '<img src="images/spacer.gif" style="background-color:#00cccc; border:1px solid #cccccc;"/>';
      $radio_values[6]['value'] = '#00CCCC';
      $radio_values[7]['text'] = '<img src="images/spacer.gif" style="background-color:#3366ff; border:1px solid #cccccc;"/>';
      $radio_values[7]['value'] = '#3366FF';
      $radio_values[8]['text'] = '<img src="images/spacer.gif" style="background-color:#6633ff; border:1px solid #cccccc;"/>';
      $radio_values[8]['value'] = '#6633FF';
      $radio_values[9]['text'] = '<img src="images/spacer.gif" style="background-color:#cc33cc; border:1px solid #cccccc;"/>';
      $radio_values[9]['value'] = '#CC33CC';
      $show_drop_down = false;
      $recurring_pattern_array = array();
      if(isset($this->_item)){
         $recurring_pattern_array = $this->_item->getRecurrencePattern();
      }
      if (isset($this->_form_post['recurring']) or isset($recurring_pattern_array['recurring_select'])){
         $show_drop_down = true;
      }
      $recurring_selected = false;
      $show_disabled = false;
      if(isset($recurring_pattern_array['recurring_select'])){
         $recurring_select = $recurring_pattern_array['recurring_select'];
         $recurring_selected = true;
         $show_disabled = true;
         $this->_form->addHidden('recurring_select',$recurring_pattern_array['recurring_select']);
      } else {
         $recurring_select = $this->_recurring_select;
      }
      $this->_form->addRadioGroup('date_addon_color',$this->_translator->getMessage('DATES_ADDON'),$this->_translator->getMessage('DATES_ADDON_DESC'),$radio_values,'',false,true,'','',false,' style="vertical-align:top;"',true,$show_drop_down);
      $this->_form->combine();
      //$this->_form->addText('','','<br/>'.$this->_translator->getMessage('DATES_RECURRING').':');
      $this->_form->addText('','','<br/>');
      $this->_form->combine();
      if(isset($recurring_pattern_array['recurring_select'])){
         $this->_form->addHidden('recurring_date','1');
      }
      $this->_form->addCheckbox('recurring','recurring',$recurring_selected,'',$this->_translator->getMessage('DATES_RECURRING_DESC').':','',false,$show_disabled);
      $this->_form->combine('horizontal');
      $select_values = array();
      $select_values[0]['text'] = $this->_translator->getMessage('DATES_RECURRING_DAILY');
      $select_values[0]['value'] = 'daily';
      $select_values[1]['text'] = $this->_translator->getMessage('DATES_RECURRING_WEEKLY');
      $select_values[1]['value'] = 'weekly';
      $select_values[2]['text'] = $this->_translator->getMessage('DATES_RECURRING_MONTHLY');
      $select_values[2]['value'] = 'monthly';
      $select_values[3]['text'] = $this->_translator->getMessage('DATES_RECURRING_YEARLY');
      $select_values[3]['value'] = 'yearly';
      $this->_form->addSelect('recurring_select',
                               $select_values,
                               $recurring_select,
                               $this->_translator->getMessage('DATES_RECURRING'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               $this->_translator->getMessage('DATES_RECURRING'),
                               'option',
                               '',
                               '',
                               '',
                               true,
                               $show_disabled);
      $this->_form->combine('horizontal');
      $this->_form->addText('','',$this->_translator->getMessage('DATES_RECURRING_DATE'));
      $this->_form->combine();
      if ((isset($this->_form_post['recurring_select']) and $this->_form_post['recurring_select'] == 'daily') or (isset($recurring_pattern_array['recurring_select']) and $recurring_pattern_array['recurring_select'] == 'daily')){
         $value_day = '';
         if(isset($recurring_pattern_array['recurring_select'])){
            $value_day = $recurring_pattern_array['recurring_day'];
            $this->_form->addHidden('recurring_day',$recurring_pattern_array['recurring_day']);
         }
         $this->_form->addTextfield('recurring_day',$value_day,'','',4,1,false,'','','','',$this->_translator->getMessage('DATES_RECURRING_EVERY_DAY'),'',$show_disabled,'. '.$this->_translator->getMessage('DATES_RECURRING_DAY'));
      } elseif ((isset($this->_form_post['recurring_select']) and $this->_form_post['recurring_select'] == 'monthly') or (isset($recurring_pattern_array['recurring_select']) and $recurring_pattern_array['recurring_select'] == 'monthly')){
         $value_month = '';
         $value_recurring_month_every = $this->_translator->getMessage('DATES_RECURRING_FIRST');
         $value_recurring_month_day_every = $this->_translator->getMessage('COMMON_DATE_MONDAY');
         if(isset($recurring_pattern_array['recurring_select'])){
            $value_month = $recurring_pattern_array['recurring_month'];
            $this->_form->addHidden('recurring_month',$recurring_pattern_array['recurring_month']);
            $value_recurring_month_every = $recurring_pattern_array['recurring_month_every'];
            $this->_form->addHidden('recurring_month_every',$recurring_pattern_array['recurring_month_every']);
            $value_recurring_month_day_every = $recurring_pattern_array['recurring_month_day_every'];
            $this->_form->addHidden('recurring_month_day_every',$recurring_pattern_array['recurring_month_day_every']);
         }
         $this->_form->addTextfield('recurring_month',$value_month,'','',4,1,false,'','','','',$this->_translator->getMessage('DATES_RECURRING_EVERY_MONTH'),'',$show_disabled,'. '.$this->_translator->getMessage('DATES_RECURRING_EVERY_MONTH_ON'));
         $month_every_values = array();
         $month_every_values[0]['text'] = $this->_translator->getMessage('DATES_RECURRING_FIRST');
         $month_every_values[0]['value'] = '1';
         $month_every_values[1]['text'] = $this->_translator->getMessage('DATES_RECURRING_SECOND');
         $month_every_values[1]['value'] = '2';
         $month_every_values[2]['text'] = $this->_translator->getMessage('DATES_RECURRING_THIRD');
         $month_every_values[2]['value'] = '3';
         $month_every_values[3]['text'] = $this->_translator->getMessage('DATES_RECURRING_FOURTH');
         $month_every_values[3]['value'] = '4';
         $month_every_values[4]['text'] = $this->_translator->getMessage('DATES_RECURRING_FIFTH');
         $month_every_values[4]['value'] = '5';
         $month_every_values[5]['text'] = $this->_translator->getMessage('DATES_RECURRING_LAST');
         $month_every_values[5]['value'] = 'last';
         $this->_form->combine('horizontal');
         $this->_form->addSelect('recurring_month_every',
                               $month_every_values,
                               $value_recurring_month_every,
                               $this->_translator->getMessage('DATES_RECURRING'),
                               '',
                               0,
                               false,
                               false,
                               false,
                               '',
                               '',
                               '',
                               '',
                               '',
                               false,
                               $show_disabled);
         $month_day_values = array();
         $month_day_values[0]['text'] = $this->_translator->getMessage('COMMON_DATE_MONDAY');
         $month_day_values[0]['value'] = '1';
         $month_day_values[1]['text'] = $this->_translator->getMessage('COMMON_DATE_TUESDAY');
         $month_day_values[1]['value'] = '2';
         $month_day_values[2]['text'] = $this->_translator->getMessage('COMMON_DATE_WEDNESDAY');
         $month_day_values[2]['value'] = '3';
         $month_day_values[3]['text'] = $this->_translator->getMessage('COMMON_DATE_THURSDAY');
         $month_day_values[3]['value'] = '4';
         $month_day_values[4]['text'] = $this->_translator->getMessage('COMMON_DATE_FRIDAY');
         $month_day_values[4]['value'] = '5';
         $month_day_values[5]['text'] = $this->_translator->getMessage('COMMON_DATE_SATURDAY');
         $month_day_values[5]['value'] = '6';
         $month_day_values[6]['text'] = $this->_translator->getMessage('COMMON_DATE_SUNDAY');
         $month_day_values[6]['value'] = '0';
         $this->_form->combine('horizontal');
         $this->_form->addSelect('recurring_month_day_every',
                               $month_day_values,
                               $value_recurring_month_day_every,
                               $this->_translator->getMessage('DATES_RECURRING'),
                               '',
                               0,
                               false,
                               false,
                               false,
                               '',
                               '',
                               '',
                               '',
                               '',
                               false,
                               $show_disabled);
      } elseif ((isset($this->_form_post['recurring_select']) and $this->_form_post['recurring_select'] == 'yearly') or (isset($recurring_pattern_array['recurring_select']) and $recurring_pattern_array['recurring_select'] == 'yearly')){
         $value_year = '';
         $value_recurring_year_every = $this->_translator->getMessage('DATES_JANUARY_LONG');
         if(isset($recurring_pattern_array['recurring_select'])){
            $value_year = $recurring_pattern_array['recurring_year'];
            $this->_form->addHidden('recurring_year',$recurring_pattern_array['recurring_year']);
            $value_recurring_year_every = $recurring_pattern_array['recurring_year_every'];
            $this->_form->addHidden('recurring_year_every',$recurring_pattern_array['recurring_year_every']);
         }
         $this->_form->addTextfield('recurring_year',$value_year,'','',4,1,false,'','','','',$this->_translator->getMessage('DATES_RECURRING_EVERY_YEAR'),'',$show_disabled,'. ');
         $month_values = array();
         $month_values[0]['text'] = $this->_translator->getMessage('DATES_JANUARY_LONG');
         $month_values[0]['value'] = '1';
         $month_values[1]['text'] = $this->_translator->getMessage('DATES_FEBRUARY_LONG');
         $month_values[1]['value'] = '2';
         $month_values[2]['text'] = $this->_translator->getMessage('DATES_MARCH_LONG');
         $month_values[2]['value'] = '3';
         $month_values[3]['text'] = $this->_translator->getMessage('DATES_APRIL_LONG');
         $month_values[3]['value'] = '4';
         $month_values[4]['text'] = $this->_translator->getMessage('DATES_MAY_LONG');
         $month_values[4]['value'] = '5';
         $month_values[5]['text'] = $this->_translator->getMessage('DATES_JUNE_LONG');
         $month_values[5]['value'] = '6';
         $month_values[6]['text'] = $this->_translator->getMessage('DATES_JULY_LONG');
         $month_values[6]['value'] = '7';
         $month_values[7]['text'] = $this->_translator->getMessage('DATES_AUGUST_LONG');
         $month_values[7]['value'] = '8';
         $month_values[8]['text'] = $this->_translator->getMessage('DATES_SEPTEMBER_LONG');
         $month_values[8]['value'] = '9';
         $month_values[9]['text'] = $this->_translator->getMessage('DATES_OCTOBER_LONG');
         $month_values[9]['value'] = '10';
         $month_values[10]['text'] = $this->_translator->getMessage('DATES_NOVEMBER_LONG');
         $month_values[10]['value'] = '11';
         $month_values[11]['text'] = $this->_translator->getMessage('DATES_DECEMBER_LONG');
         $month_values[11]['value'] = '12';
         $this->_form->combine('horizontal');
         $this->_form->addSelect('recurring_year_every',
                               $month_values,
                               $value_recurring_year_every,
                               $this->_translator->getMessage('DATES_RECURRING'),
                               '',
                               0,
                               false,
                               false,
                               false,
                               '',
                               '',
                               '',
                               '',
                               '',
                               false,
                               $show_disabled);
      } else {
         $value_week = '';
         $value_selected = array();
         if(isset($recurring_pattern_array['recurring_select'])){
            $value_week = $recurring_pattern_array['recurring_week'];
            $this->_form->addHidden('recurring_week',$recurring_pattern_array['recurring_week']);
            $value_selected = $recurring_pattern_array['recurring_week_days'];
            $this->_form->addHidden('recurring_week_days',$recurring_pattern_array['recurring_week_days']);
         }
         $this->_form->addTextfield('recurring_week',$value_week,'','',4,1,false,'','','','',$this->_translator->getMessage('DATES_RECURRING_EVERY_WEEK'),'',$show_disabled,'. '.$this->_translator->getMessage('DATES_RECURRING_WEEK'));
         $week_values = array();
         $week_values[0]['text'] = $this->_translator->getMessage('COMMON_DATE_MONDAY');
         $week_values[0]['value'] = 'monday';
         $week_values[1]['text'] = $this->_translator->getMessage('COMMON_DATE_TUESDAY');
         $week_values[1]['value'] = 'tuesday';
         $week_values[2]['text'] = $this->_translator->getMessage('COMMON_DATE_WEDNESDAY');
         $week_values[2]['value'] = 'wednesday';
         $week_values[3]['text'] = $this->_translator->getMessage('COMMON_DATE_THURSDAY');
         $week_values[3]['value'] = 'thursday';
         $week_values[4]['text'] = $this->_translator->getMessage('COMMON_DATE_FRIDAY');
         $week_values[4]['value'] = 'friday';
         $week_values[5]['text'] = $this->_translator->getMessage('COMMON_DATE_SATURDAY');
         $week_values[5]['value'] = 'saturday';
         $week_values[6]['text'] = $this->_translator->getMessage('COMMON_DATE_SUNDAY');
         $week_values[6]['value'] = 'sunday';
         $this->_form->combine();
         $this->_form->addCheckboxGroup('recurring_week_days',$week_values,$value_selected,$this->_translator->getMessage('DATES_RECURRING_WEEK_DAYS'),'',false,true,0,'','','','',false,false,false,10,$show_disabled);
      }
      $this->_form->combine();
      $value_end_date = '';
      if(isset($recurring_pattern_array['recurring_select'])){
         $value_end_date = mb_substr($recurring_pattern_array['recurring_end_date'],8,2) . '.' . mb_substr($recurring_pattern_array['recurring_end_date'],5,2) . '.' . mb_substr($recurring_pattern_array['recurring_end_date'],0,4);
      }
      $this->_form->addTextfield('recurring_end_date',$value_end_date,'','',13,13,false,'','','','',$this->_translator->getMessage('DATES_RECURRING_END_DATE').':<span class="required">*</span>','',$show_disabled);
      if(isset($recurring_pattern_array['recurring_select'])){
         $this->_form->addHidden('recurring_ignore', '1');
      }

      $this->_form->addTextArea('description','',$this->_translator->getMessage('DATES_DESCRIPTION'),'','',10);

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

      #$this->_form->addTextfield('colour','',$this->_translator->getMessage('DATES_COLOUR'),$this->_translator->getMessage('DATES_COLOUR_DESC'),'',10,false,'','','','left','','',false,'',10,true,true);
      #$this->_form->combine();
      #pr('--->'.$this->_color.'<---');
      #$this->_form->addText('colorpicker',$this->_translator->getMessage('DATES_COLOUR'),'<br/><br/><INPUT class="color" value="' . $this->_color . '" name="colorpicker">',$this->_translator->getMessage('DATES_COLOUR_DESC'),false,'','','left','','',true,false);

      #$this->_form->addText('colorpicker',$this->_translator->getMessage('DATES_COLOUR'),'<br/><br/><INPUT class="color" value="' . $this->_color . '" name="colorpicker">',$this->_translator->getMessage('DATES_COLOUR_DESC'),false,'','','left','','',true,false);

      if ($current_context->withActivatingContent() and !$current_context->isPrivateRoom()){
         $this->_form->addCheckbox('private_editing',1,'',$this->_translator->getMessage('COMMON_RIGHTS'),$this->_public_array[1]['text'],$this->_translator->getMessage('COMMON_RIGHTS_DESCRIPTION'),false,false,'','',true,false);
         $this->_form->combine();
         $this->_form->addCheckbox('mode','','','',$this->_translator->getMessage('COMMON_NOT_ACCESSIBLE'),'');
         $this->_form->combine();
         $this->_form->addCheckbox('hide',1,'',$this->_translator->getMessage('COMMON_HIDE'),$this->_translator->getMessage('COMMON_HIDE'),'');
         $this->_form->combine('horizontal');
         $this->_form->addDateTimeField('start_activate_date_time','','dayActivateStart','timeActivateStart',9,4,$this->_translator->getMessage('DATES_HIDING_DAY'),'('.$this->_translator->getMessage('DATES_HIDING_DAY'),$this->_translator->getMessage('DATES_HIDING_TIME'),$this->_translator->getMessage('DATES_TIME_DAY_START_DESC'),FALSE,FALSE,100,100,true,'left','',FALSE);
         $this->_form->combine('horizontal');
         $this->_form->addText('hide_end2','',')');
      } else {
         // public radio-buttons
         $this->_form->addCheckbox('mode','','',$this->_translator->getMessage('COMMON_DATE_STATUS'),$this->_translator->getMessage('DATES_NON_PUBLIC_FORM'),'');
         if ( !$current_context->isPrivateRoom() ) {
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
         } else {
            $this->_form->addHidden('public',0);
            $this->_form->addCheckbox('external_viewer',1,'',$this->_translator->getMessage('COMMON_RIGHTS'),$this->_translator->getMessage('EXTERNAL_VIEWER_DESCRIPTION'),$this->_translator->getMessage('COMMON_RIGHTS_DESCRIPTION'),false,false,'','',true,false);
            $this->_form->combine();
            $this->_form->addTextField('external_viewer_accounts','',$this->_translator->getMessage('EXTERNAL_VIEWER'),$this->_translator->getMessage('EXTERNAL_VIEWER_DESC'),200,35,false);
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
         $this->_form->addButtonBar('option',$this->_translator->getMessage('DATES_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         if(!isset($recurring_pattern_array['recurring_select'])){
            $this->_form->addButtonBar('option',$this->_translator->getMessage('DATES_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','');
         } else {
            $this->_form->addButtonBar('option',$this->_translator->getMessage('DATES_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','',$this->_translator->getMessage('DATES_CHANGE_RECURRING_BUTTON'));
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
         $temp_array = array();

         if (isset($this->_form_post['dayActivateStart'])){
            $temp_array['dayActivateStart'] = $this->_form_post['dayActivateStart'];
         }else{
            $temp_array['dayActivateStart'] = '';
         }
         if (isset($this->_form_post['timeActivateStart'])){
            $temp_array['timeActivateStart'] = $this->_form_post['timeActivateStart'];
         }else{
            $temp_array['timeActivateStart'] = '';
         }
         $this->_form_post['start_activate_date_time'] = $temp_array;
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
         #if ( !empty($this->_form_post['colorpicker']) ) {
         #   $this->_values['colorpicker'] = '<br/><br/><INPUT class="color" value="' . $_POST['colorpicker'] . '" name="colorpicker">';
         #   $this->_color = $_POST['colorpicker'];
         #}
         if (isset($this->_form_post['date_addon_color'])){
            $this->_values['date_addon_color'] = $this->_form_post['date_addon_color'];
         }
         if (isset($this->_form_post['recurring'])){
            $this->_values['recurring'] = $this->_form_post['recurring'];
            // avoid "manipulation of data"-error
            if($this->_form_post['recurring_select'] == 'daily'){
               unset($this->_form_post['recurring_month']);
               unset($this->_form_post['recurring_month_every']);
               unset($this->_form_post['recurring_month_day_every']);
               unset($this->_form_post['recurring_year_every']);
               unset($this->_form_post['recurring_week']);
               unset($this->_form_post['recurring_week_days']);
            } elseif($this->_form_post['recurring_select'] == 'weekly'){
               unset($this->_form_post['recurring_month']);
               unset($this->_form_post['recurring_month_every']);
               unset($this->_form_post['recurring_month_day_every']);
               unset($this->_form_post['recurring_year_every']);
               unset($this->_form_post['recurring_day']);
            } elseif($this->_form_post['recurring_select'] == 'monthly'){
               unset($this->_form_post['recurring_week']);
               unset($this->_form_post['recurring_week_days']);
               unset($this->_form_post['recurring_year_every']);
               unset($this->_form_post['recurring_day']);
            } elseif($this->_form_post['recurring_select'] == 'yearly'){
               unset($this->_form_post['recurring_month']);
               unset($this->_form_post['recurring_month_every']);
               unset($this->_form_post['recurring_month_day_every']);
               unset($this->_form_post['recurring_day']);
               unset($this->_form_post['recurring_week']);
               unset($this->_form_post['recurring_week_days']);
            }
         }
      } elseif ( isset($this->_item) ) {
         $this->_values['external_viewer'] = $this->_item->issetExternalViewerStatus();
         $this->_values['external_viewer_accounts'] = $this->_item->getExternalViewerString();
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
         $current_context = $this->_environment->getCurrentContextItem();
         if ($current_context->withActivatingContent()){
            if ($this->_item->isPrivateEditing()){
               $this->_values['private_editing'] = 1;
            }else{
               $this->_values['private_editing'] = $this->_item->isPrivateEditing();
            }
         }else{
            $this->_values['public'] = $this->_item->isPublic();
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


         $this->_values['hide'] = $this->_item->isNotActivated()?'1':'0';
         if ($this->_item->isNotActivated()){
            $activating_date = $this->_item->getActivatingDate();
            if (!strstr($activating_date,'9999-00-00')){
               $array = array();
               $array['dayActivateStart'] = getDateInLang($activating_date);
               $array['timeActivateStart'] = getTimeInLang($activating_date);
               $this->_values['start_activate_date_time'] = $array;
            }
         }

         if ( $this->_item->getColor() != '' ) {
            //$this->_values['colorpicker'] = '<br/><br/><INPUT class="color" value="' . $this->_item->getColor() . '" name="colorpicker">';
            $this->_values['date_addon_color'] = $this->_item->getColor();
            #$this->_color = $this->_item->getColor();
         }

      } else {
         $temp_array['dayStart'] = $this->_private_date_starting_date;
         $temp_array['timeStart'] = $this->_private_date_starting_time;
         $this->_values['start_date_time'] = $temp_array;
         $temp_array = array();
         $temp_array['dayEnd'] = $this->_private_date_ending_date;
         $temp_array['timeEnd'] = $this->_private_date_ending_time;
         $this->_values['end_date_time'] = $temp_array;
         $current_context = $this->_environment->getCurrentContextItem();
         if ($current_context->withActivatingContent()){
            if ( !isset($this->_values['private_editing']) ) {
               $this->_values['private_editing'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'0':'1'; //In projectrooms everybody can edit the item by default, else default is creator only
            }
         }else{
            if ( !isset($this->_values['public']) ) {
               $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
            }
         }
         #$this->_values['date_addon_color'] = '#FFFF66';
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
      include_once('functions/date_functions.php');
      if ( !empty($this->_form_post['start_date_time'][0])
           and !isDatetimeCorrect($this->_environment->getSelectedLanguage(),$this->_form_post['start_date_time'][0],$this->_form_post['start_date_time'][1]) ) {
         $this->_error_array[] = $this->_translator->getMessage('DATES_TIME_NOT_VALID');
         $this->_form->setFailure('start_date_time','');
      }
      if ( !empty($this->_form_post['end_date_time'][0])
           and !isDatetimeCorrect($this->_environment->getSelectedLanguage(),$this->_form_post['end_date_time']['dayEnd'],$this->_form_post['end_date_time']['timeEnd']) ) {
         $this->_error_array[] = $this->_translator->getMessage('DATES_DATE_NOT_VALID');
         $this->_form->setFailure('end_date_time','');
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
      if ( isset($this->_form_post['external_viewer']) and !empty($this->_form_post['external_viewer']) and !isset($this->_form_post['external_viewer_accounts'])){
         $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_EXTERNAL_VIEWER_ACCOUNT_MISSED');
         $this->_form->setFailure('external_viewer_accounts','');
      }
      if ( isset($this->_form_post['external_viewer']) and isset($this->_form_post['external_viewer_accounts'])){
          $user_id_array = explode(' ',$this->_form_post['external_viewer_accounts']);
          $user_manager = $this->_environment->getUserManager();
          foreach($user_id_array as $user_id){
             $user_manager->setUserIDLimit($user_id);
             $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
             $user_manager->select();
             $user_list = $user_manager->get();
             $user_item = $user_list->getFirst();
             if (!is_object($user_item)){
                $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_EXTERNAL_VIEWER_ACCOUNT_NOT_EXISTS',$user_id);
                $this->_form->setFailure('external_viewer_accounts','');
             }
          }
      }
      if ($current_context->withActivatingContent() and !empty($this->_form_post['dayActivateStart']) and !empty($this->_form_post['hide'])){
         if ( !isDatetimeCorrect($this->_environment->getSelectedLanguage(),$this->_form_post['dayActivateStart'],$this->_form_post['timeActivateStart']) ) {
            $this->_error_array[] = $this->_translator->getMessage('DATES_DATE_NOT_VALID');
            $this->_form->setFailure('start_activate_date_time','');
         }
      }
      if ( !empty($this->_form_post['recurring'])){
         if($this->_form_post['recurring_select'] == 'daily'){
            if(empty($this->_form_post['recurring_day'])){
                  $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_DAY_ERROR');
                  $this->_form->setFailure('recurring_day','');
            } else {
               if(!is_numeric($this->_form_post['recurring_day'])){
                  $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_NUMERIC_ERROR');
                  $this->_form->setFailure('recurring_day','');
               }
            }
         } elseif($this->_form_post['recurring_select'] == 'weekly'){
            if(empty($this->_form_post['recurring_week'])){
                  $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_WEEK_ERROR');
                  $this->_form->setFailure('recurring_week','');
            } else {
               if(!is_numeric($this->_form_post['recurring_week'])){
                  $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_NUMERIC_ERROR');
                  $this->_form->setFailure('recurring_week','');
               }
            }
            if(empty($this->_form_post['recurring_week_days'])){
                  $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_WEEKDAYS_ERROR');
                  $this->_form->setFailure('recurring_week_days','');
            }
         } elseif($this->_form_post['recurring_select'] == 'monthly'){
            if(empty($this->_form_post['recurring_month'])){
                  $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_MONTH_ERROR');
                  $this->_form->setFailure('recurring_month','');
            } else {
               if(!is_numeric($this->_form_post['recurring_month'])){
                  $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_NUMERIC_ERROR');
                  $this->_form->setFailure('recurring_month','');
               }
            }
         } elseif($this->_form_post['recurring_select'] == 'yearly'){
            if(empty($this->_form_post['recurring_year'])){
                  $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_YEAR_ERROR');
                  $this->_form->setFailure('recurring_year','');
            } else {
               if(!is_numeric($this->_form_post['recurring_year'])){
                  $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_NUMERIC_ERROR');
                  $this->_form->setFailure('recurring_year','');
               } else {
                  if(($this->_form_post['recurring_year_every'] == '1'
                      or $this->_form_post['recurring_year_every'] == '3'
                      or $this->_form_post['recurring_year_every'] == '5'
                      or $this->_form_post['recurring_year_every'] == '7'
                      or $this->_form_post['recurring_year_every'] == '8'
                      or $this->_form_post['recurring_year_every'] == '10'
                      or $this->_form_post['recurring_year_every'] == '12') and ($this->_form_post['recurring_year'] > 31)){
                     $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_YEAR_TO_MANY_DAYS_ERROR');
                     $this->_form->setFailure('recurring_year','');
                  }
                  if(($this->_form_post['recurring_year_every'] == '4'
                      or $this->_form_post['recurring_year_every'] == '6'
                      or $this->_form_post['recurring_year_every'] == '9'
                      or $this->_form_post['recurring_year_every'] == '11') and ($this->_form_post['recurring_year'] > 30)){
                     $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_YEAR_TO_MANY_DAYS_ERROR');
                     $this->_form->setFailure('recurring_year','');
                  }
                  if(($this->_form_post['recurring_year_every'] == '2') and ($this->_form_post['recurring_year'] > 29)){
                     $this->_error_array[] = $this->_translator->getMessage('DATES_RECURRING_YEAR_TO_MANY_DAYS_ERROR');
                     $this->_form->setFailure('recurring_year','');
                  }
               }
            }
            // Ist das Datum OK? -> z.B. kein 31. Februar
         }
         if(empty($this->_form_post['recurring_end_date'])){
               $this->_error_array[] = $this->_translator->getMessage('DATES_DATE_NOT_VALID');
               $this->_form->setFailure('recurring_end_date','');
         } else {
            if ( !isDatetimeCorrect($this->_environment->getSelectedLanguage(),$this->_form_post['recurring_end_date'],'00:00') ) {
               $this->_error_array[] = $this->_translator->getMessage('DATES_DATE_NOT_VALID');
               $this->_form->setFailure('recurring_end_date','');
            }
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
         $this->_error_array[] = $this->_translator->getMessage('DATES_WRONG_DATE_FORMAT');
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
            $this->_error_array[] = $this->_translator->getMessage('DATES_END_DATE_BEFORE_START_DATE');
         }
      }
   }

   function setRecurringSelect($recurring_select){
      $this->_recurring_select = $recurring_select;
   }
}
?>