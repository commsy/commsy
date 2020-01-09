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

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_rubric_mail_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;
   var $_description = NULL;
   var $_group_names = NULL;
   var $_institution_names = NULL;
   var $_topics = array();
   var $_groups = array();
   var $_institutions = array();
   var $_test = array();

  /**
   * array - containing an array of rubrics in the context
   */
   var $_rubric_array = array();

  /**
   * string - name of the current context
   */
   var $_context_name = '';

  /** constructor: cs_rubric_form
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Topic
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   function setGroupNames($array){
      if( !empty($array['string']) ){
         $this->_group_names = $array;
      }
   }

   function setInstitutionNames($array){
      if( !empty($array['string']) ){
         $this->_institution_names = $array;
      }
   }

   function setGroups($array){
      if( !empty($array) ){
         $this->_groups = $array;
      }
   }

   function setInstitutions($array){
      if( !empty($array) ){
         $this->_institutions = $array;
      }
   }
   /** init data for form, INTERNAL
    * this methods init the data for the form, for example rubrics
    *
    * @author CommSy Development Topic
    */
   function _initForm () {
      // headline
      $this->_headline = $this->_translator->getMessage('COMMON_EMAIL_TO_TITLE');

      // context name
      $context = $this->_environment->getCurrentContextItem();
      $this->_context_name = $context->getTitle();

      //get all groups in context
      $this->_group_array = $this->_getAllLabelsByType('group');

      //get all institutions in context
      $this->_institution_array = $this->_getAllLabelsByType('institution');
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Topic
    */
   function _createForm () {
      // rubrics
      $iid = $_GET['iid'];
      $manager = $this->_environment->getItemManager();
      $item = $manager->getItem($iid);
      $module = $item->getItemType();
      $link_module = $module;
      if ($module== 'label' or $module== 'labels') {
          $label_manager = $this->_environment->getLabelManager();
          $label = $label_manager->getItem($iid);
          $link_module= $label->getLabelType();
      }
      $item_manager = $this->_environment->getManager($module);
      $item = $item_manager->getItem($iid);
      $item_name = $item->getTitle();
      $context_name = $this->_context_name;
      $article = '';
      // Wenn man mit HTTPS auf Commsy surft und eine Email generiert
      // sollte diese Mail auch https links erstellen.
      if ( !empty($_SERVER["HTTPS"])
           and $_SERVER["HTTPS"]
         ) {
         $url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']
                .'?cid='.$this->_environment->getCurrentContextID()
                .'&mod='.$link_module
                .'&fct=detail'
                .'&iid='.$item->getItemID();
      } else {
         $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']
                .'?cid='.$this->_environment->getCurrentContextID()
                .'&mod='.$link_module
                .'&fct=detail'
                .'&iid='.$item->getItemID();
      }

      $link = $url;
      $rubric_type_pretty = '';
      $content = '';
      //generate module name for the interface- a pretty version of module...
       if ($module== 'new' or $module== 'news') {
          $news_content = $this->_translator->getMessage('COMMON_NEWS').': '.$item->getTitle().LF;
          $content = $news_content;

       } elseif ($module== CS_DATE_TYPE) {
          $rubric_type_pretty = $this->_translator->getMessage('COMMON_DATES');
          // set up style of days and times
          $parse_time_start = convertTimeFromInput($item->getStartingTime());
          $conforms = $parse_time_start['conforms'];
          if ($conforms == TRUE) {
             $start_time_print = getTimeLanguage($parse_time_start['datetime']);
          } else {
             $start_time_print = $item->getStartingTime();
          }

          $parse_time_end = convertTimeFromInput($item->getEndingTime());
          $conforms = $parse_time_end['conforms'];
          if ($conforms == TRUE) {
             $end_time_print = getTimeLanguage($parse_time_end['datetime']);
          } else {
             $end_time_print = $item->getEndingTime();
          }

          $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
          $conforms = $parse_day_start['conforms'];
          if ($conforms == TRUE) {
             $start_day_print = getDateInLang($parse_day_start['datetime']);
          } else {
             $start_day_print = $item->getStartingDay();
          }

          $parse_day_end = convertDateFromInput($item->getEndingDay(),$this->_environment->getSelectedLanguage());
          $conforms = $parse_day_end['conforms'];
          if ($conforms == TRUE) {
             $end_day_print =getDateLanguage($parse_day_end['datetime']);
          } else {
             $end_day_print =$item->getEndingDay();
          }
          //formating dates and times for displaying
          $date_print ="";
          $time_print ="";

          if ($end_day_print != "") { //with ending day
             $date_print = $this->_translator->getMessage('DATES_AS_OF').' '.$start_day_print.' '.$this->_translator->getMessage('DATES_TILL').' '.$end_day_print;
             if ($parse_day_start['conforms']
                 and $parse_day_end['conforms']) { //start and end are dates, not strings
               $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$this->_translator->getMessage('DATES_DAYS').')';
             }
             if ($start_time_print != "" and $end_time_print =="") { //starting time given
                $time_print = $this->_translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
                if ($parse_time_start['conforms'] == true) {
                   $time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
                }
             } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
                $time_print = $this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
                if ($parse_time_end['conforms'] == true) {
                   $time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
                }
             } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
                if ($parse_time_end['conforms'] == true) {
                   $end_time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
                }
                if ($parse_time_start['conforms'] == true) {
                    $start_time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
                }
                $date_print = $this->_translator->getMessage('DATES_AS_OF').' '.$start_day_print.', '.$start_time_print.'<br />'.
                          $this->_translator->getMessage('DATES_TILL').' '.$end_day_print.', '.$end_time_print;
                if ($parse_day_start['conforms']
                     and $parse_day_end['conforms']) {
                   $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$this->_translator->getMessage('DATES_DAYS').')';
                }
             }

          } else { //without ending day
             $date_print = $start_day_print;
             if ($start_time_print != "" and $end_time_print =="") { //starting time given
                 $time_print = $this->_translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
                 if ($parse_time_start['conforms'] == true) {
                    $time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
                 }
              } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
                 $time_print = $this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
                 if ($parse_time_end['conforms'] == true) {
                     $time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
                 }
              } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
                 if ($parse_time_end['conforms'] == true) {
                    $end_time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
                 }
                 if ($parse_time_start['conforms'] == true) {
                    $start_time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
                 }
                 $time_print = $this->_translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
              }
           }

           if ($parse_day_start['timestamp'] == $parse_day_end['timestamp'] and $parse_day_start['conforms'] and $parse_day_end['conforms']) {
              $date_print = $this->_translator->getMessage('DATES_ON_DAY').' '.$start_day_print;
              if ($start_time_print != "" and $end_time_print =="") { //starting time given
                  $time_print = $this->_translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
              } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
                 $time_print = $this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
              } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
                 $time_print = $this->_translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
              }
           }
          // Date and time
          $dates_content = '';
          $dates_content = $this->_translator->getMessage('DATES_DATETIME').': '.$item_name.LF;
          if ($time_print != '') {
          $dates_content .= $this->_translator->getMessage('COMMON_TIME').': '.$date_print.','.$time_print.LF;
          } else {
          $dates_content .= $this->_translator->getMessage('COMMON_TIME').': '.$date_print.LF;
          }
          // Place
          $place = $item->getPlace();
          if (!empty($place)) {
             $dates_content .= $this->_translator->getMessage('DATES_PLACE').': ';
             $dates_content .= $place.LF;
          }
          $content = $dates_content;
       } elseif ($module== 'discussion' or $module== 'discussions') {
          $discussion_content = $this->_translator->getMessage('COMMON_DISCUSSION').': '.$item->getTitle().LF;
          $article_count = $item->getAllArticlesCount();
          $discussion_content .= $this->_translator->getMessage('DISCUSSION_DISCARTICLE_COUNT').': '.$article_count.LF;
          $time = $item->getLatestArticleModificationDate();
          $discussion_content .= $this->_translator->getMessage('DISCUSSION_LAST_ENTRY').': '.getDateTimeInLang($time).LF;
          $content = $discussion_content;
       } elseif ($module== 'material' or $module== 'materials') {
          $material_content = $this->_translator->getMessage('COMMON_MATERIAL').': '.$item->getTitle().LF;
          $content = $material_content;
       } elseif ($module== 'announcement' or $module== CS_ANNOUNCEMENT_TYPE) {
          $announcement_content = $this->_translator->getMessage('COMMON_ANNOUNCEMENT').': '.$item->getTitle().LF;
          $content = $announcement_content;
       }  elseif ($module== 'label' or $module== 'labels') {
          $label_manager = $this->_environment->getLabelManager();
          $label = $label_manager->getItem($iid);
          $module= $label->getLabelType();
          if ($module== 'group' or $module== 'groups') {
             $group_content = $this->_translator->getMessage('COMMON_GROUP').': '.$item->getTitle().LF;
             $content = $group_content;
          } elseif ($module== 'institution' or $module== 'institutions') {
             $institution_content = $this->_translator->getMessage('INSTITUTION').': '.$item->getTitle().LF;
             $content = $institution_content;
          }
       }
       if ( $this->_environment->inProjectRoom() ){
          $emailtext = $this->_translator->getMessage('RUBRIC_EMAIL_DEFAULT_PROJECT',$context_name).LF;
       } elseif ( $this->_environment->inGroupRoom() ){
          $emailtext = $this->_translator->getMessage('RUBRIC_EMAIL_DEFAULT_GROUPROOM',$context_name).LF;
       } else {
          $emailtext = $this->_translator->getMessage('RUBRIC_EMAIL_DEFAULT_COMMUNITY', $context_name).LF;
       }
       if ( empty($content) ){
          $emailtext .= LF.LF;
       } else {
          $emailtext .= $content;
       }
       $emailtext .= $this->_translator->getMessage('RUBRIC_EMAIL_DEFAULT_PROJECT_END',$link);
      $this->_form->addTextField('subject','',$this->_translator->getMessage('COMMON_MAIL_SUBJECT'),'','','57',true);

      $this->_form->addTextArea('mailcontent',$emailtext,$this->_translator->getMessage('COMMON_MAIL_CONTENT'),$this->_translator->getMessage('COMMON_MAIL_CONTENT_DESC'), '60', '15', '', true,false,false);
      if ($module== 'date'){
         $this->_form->addCheckBox('attendees','2',false,$this->_translator->getMessage('COMMON_MAIL_SEND_TO_ASIGNED_PEOPLE'),$this->_translator->getMessage('COMMON_MAIL_SEND_TO_ATTENDEES'));
      }elseif($module== 'todo') {
         $this->_form->addCheckBox('processors','2',false,$this->_translator->getMessage('COMMON_MAIL_SEND_TO_ASIGNED_PEOPLE'),$this->_translator->getMessage('COMMON_MAIL_SEND_TO_PROCESSORS'));
      }

      if ( $this->_environment->inProjectRoom() and !empty($this->_group_array) ) {
         $context_item = $this->_environment->getCurrentContextItem();
         if ( $context_item->withRubric(CS_GROUP_TYPE) ) {
            $this-> _initCheckBoxGroup();
         }
      } else {
         $context_item = $this->_environment->getCurrentContextItem();
      }
      $projekt_room_show_mail_to_all = false;

      //Projectroom and no groups enabled -> send mails to group all
      if ( $context_item->isProjectRoom()
           and !$context_item->withRubric(CS_GROUP_TYPE)
         ) {
         $cid = $this->_environment->getCurrentContextId();
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setUserLimit();
         $user_manager->setContextLimit($cid);
         $count = $user_manager->getCountAll();
         $all_iid = $this->_group_array[0]['value'];
         if ( $item->getItemType() != CS_DATE_TYPE
              and $item->getItemType() != CS_TODO_TYPE
            ) {
            $this->_form->addText('receiver_text', $this->_translator->getMessage('COMMON_MAIL_RECEIVER'), $this->_translator->getMessage('COMMON_MAIL_ALL_IN_ROOM',$count));
            $this->_form->addHidden('groups[0]',$all_iid);
         } else {
            $this->_form->combine();
            $this->_form->addCheckbox('groups[0]',$all_iid,false,$this->_translator->getMessage('COMMON_MAIL_RECEIVER'), $this->_translator->getMessage('COMMON_MAIL_ALL_IN_ROOM',$count));
         }
      }

      if ($context_item->isCommunityRoom()) {
         $cid = $this->_environment->getCurrentContextId();
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setUserLimit();
         $user_manager->setContextLimit($cid);
         $count = $user_manager->getCountAll();
         if ( $item->getItemType() != CS_DATE_TYPE
              and $item->getItemType() != CS_TODO_TYPE
            ) {
            $this->_form->addText('receiver_text', $this->_translator->getMessage('COMMON_MAIL_RECEIVER'), $this->_translator->getMessage('COMMON_MAIL_ALL_IN_ROOM',$count));
            $this->_form->addHidden('send_to_all',true);
         } else {
            $this->_form->combine();
            $this->_form->addCheckbox('send_to_all',1,false,$this->_translator->getMessage('COMMON_MAIL_RECEIVER'), $this->_translator->getMessage('COMMON_MAIL_ALL_IN_ROOM',$count));
         }
      }

      // send to all members in group rooms
      if ( $context_item->isGroupRoom() ) {
         $cid = $this->_environment->getCurrentContextID();
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setUserLimit();
         $user_manager->setContextLimit($cid);
         $count = $user_manager->getCountAll();
         if ( $item->getItemType() != CS_DATE_TYPE
              and $item->getItemType() != CS_TODO_TYPE
            ) {
            $this->_form->addText('receiver_text', $this->_translator->getMessage('COMMON_MAIL_RECEIVER'), $this->_translator->getMessage('COMMON_MAIL_ALL_IN_ROOM',$count));
            $this->_form->addHidden('send_to_all',true);
         } else {
            $this->_form->combine();
            $this->_form->addCheckbox('send_to_all',1,false,$this->_translator->getMessage('COMMON_MAIL_RECEIVER'), $this->_translator->getMessage('COMMON_MAIL_ALL_IN_ROOM',$count));
         }
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
         // group all will be marked at entering the form
         $current_context = $this->_environment->getCurrentContextItem();
         if ( empty($this->_groups) and $current_context->withRubric(CS_GROUP_TYPE)) {
            $group_manager = $this->_environment->getGroupManager();
            $group_manager->select();
            $group_list = $group_manager->get();
            if ( $group_list->isNotEmpty() ) {
               $group_item = $group_list->getFirst();
               while ( $group_item ) {
                  if ( $group_item->isSystemLabel() ) {
                     $this->_groups[] = $group_item->getItemID();
                  }
                  $group_item = $group_list->getNext();
               }
            }
            unset($group_list);
            unset($group_manager);
         }
         unset($current_context);
         $this->_values['groups'] = $this->_groups;
         $this->_values['institutions'] = $this->_institutions;
         $this->_values['attendees'] = true;
         $this->_values['processors'] = true;
      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post; // no encode here - encode in form-views
         if ( !empty($this->_values['groups'][0]) ) {
            $this->_values['groups[0]'] = $this->_values['groups'][0];
         }
      }
   }

   /** initializes a check box for selecting the relevant groups
    *  this method is called in the child classes, where this row is needed
    */
   function _initCheckBoxGroup () {
      if (isset($this->_group_array)) {
         $this->_form->addCheckBoxGroup('groups',$this->_group_array,'',$this->_translator->getMessage('COMMON_MAILTO_GROUPS'),$this->_translator->getMessage('COMMON_RELEVANT_FOR_DESC'), false, false);
      }
   }

   /** Retrieves all labels of a type in the current context
   *   @param $type: typ of label, e.g. 'topic', 'group' or 'institution'
   *   @return list of names and id's of desired labels
   */
   function _getAllLabelsByType($type) {
      $label_manager = $this->_environment->getLabelManager();
      $label_manager->resetLimits();
      $label_manager->setContextLimit($this->_environment->getCurrentContextID());
      $label_manager->setTypeLimit($type);
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
      return $label_array;
   }
}
?>