<?PHP
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
class cs_configuration_rubric_extras_form extends cs_rubric_form {


   /**
   * array - containing the 2 choices of the public field
   */
   var $_public_array = array();
   var $_status_array = array();

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($environment) {
      cs_rubric_form::__construct($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE');
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/32x32/config/rubric_extras.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE').'"/>';
      } else {
         $image = '<img src="images/commsyicons/32x32/config/rubric_extras.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE').'"/>';
      }
      if ( !empty($image) ) {
         $this->_headline = $image.' '.$this->_headline;
      }
      $this->setHeadline($this->_headline);

      // Get available extra todo status
      $context_item = $this->_environment->getCurrentContextItem();
      $todo_status_array = $context_item->getExtraToDoStatusArray();

      $status_array = array();
      foreach ($todo_status_array as $key=>$value){
         $temp_array['text']  = $value;
         $temp_array['value'] = $key;
         $status_array[] = $temp_array;
      }
      $this->_status_array = $status_array;


   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
     $output = false;
     $current_context = $this->_environment->getCurrentContextItem();

     if ( $current_context->withRubric(CS_DATE_TYPE) ) {
        // new private room -> no date list view
        $show_list_option = true;
        if ( $this->_environment->inPrivateRoom() ) {
           $show_list_option = false;
        }

        if ( $show_list_option ) {
           // Terminoptionen
           $radio_values = array();
           $desc = $this->_translator->getMessage('CONFIGURATION_DATES_DESC');
           if ( $show_list_option ) {
              $radio_values[0]['text'] = '<div style="height:62px; width:150px; border:1px solid black; display:inline-block; background-image:url(images/dates_presentation_normal_150.gif); text-align:center; padding-top:30px;">'.$this->_translator->getMessage('CONFIGURATION_DATES_PRESENTATION_NORMAL').'</div>';
              $radio_values[0]['value'] = 'normal';
           }
           $radio_values[1]['text'] = '<div style="height:62px; width:150px; border:1px solid black; display:inline-block; background-image:url(images/dates_presentation_calendar_week_150.gif); text-align:center; padding-top:30px;">'.$this->_translator->getMessage('CONFIGURATION_DATES_PRESENTATION_CALENDAR_WEEK').'</div>';
           $radio_values[1]['value'] = 'calendar';
           $radio_values[2]['text'] = '<div style="height:62px; width:150px; border:1px solid black; display:inline-block; background-image:url(images/dates_presentation_calendar_month_150.gif); text-align:center; padding-top:30px;">'.$this->_translator->getMessage('CONFIGURATION_DATES_PRESENTATION_CALENDAR').'</div>';
           $radio_values[2]['value'] = 'calendar_month';
           $this->_form->addRadioGroup('dates_status',$this->_translator->getMessage('DATES_INDEX'),$desc,$radio_values,'',true,true,'','',false,' style="vertical-align:top;"');
           $this->_form->combine();
           $this->_form->addExplanation('dates',$this->_translator->getMessage('CONFIGURATION_DATES_DESC'));
           $output = true;
        } else {
           #$this->_form->addExplanation('dates',$this->_translator->getMessage('CONFIGURATION_DATES_DESC2'));
        }
     }

     if ( $current_context->withRubric(CS_DISCUSSION_TYPE) ) {
        if ( $output ) {
           $this->_form->addEmptyline();
        } else {
           $output = true;
        }
        // discussion otions
        $radio_values = array();
        $radio_values[0]['text'] = '<img src="images/configuration_discussion_not_threaded.gif" alt="picture_simple" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
        $radio_values[0]['value'] = '1';
        $radio_values[1]['text'] = '<img src="images/configuration_discussion_threaded.gif" alt="picture_threaded" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
        $radio_values[1]['value'] = '2';
        $radio_values[2]['text'] = '<span style="vertical-align: top;">'.$this->_translator->getMessage('CONFIGURATION_DISCUSSION_DESC_3').'</span>';
        $radio_values[2]['value'] = '3';
        $this->_form->addRadioGroup('discussion_status',$this->_translator->getMessage('DISCUSSION_INDEX'),'',$radio_values,'',true,true,'','',false,' style="vertical-align:top;"');
        $this->_form->combine();
        $this->_form->addExplanation('discussions',$this->_translator->getMessage('CONFIGURATION_DISCUSSION_WARNING'));
     }


     if ( $current_context->withRubric(CS_TODO_TYPE) ) {
        if ( $output ) {
           $this->_form->addEmptyline();
        } else {
           $output = true;
        }
        //Todo option
        $radio_values = array();
        $radio_values[0]['text'] = '<img src="images/configuration_todo_no_management.gif" alt="picture_todo_no_management" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
        $radio_values[0]['value'] = '1';
        $radio_values[1]['text'] = '<img src="images/configuration_todo_management.gif" alt="picture_todo_management" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
        $radio_values[1]['value'] = '2';
        $this->_form->addRadioGroup('todo_management',$this->_translator->getMessage('TODO_INDEX'),'',$radio_values,'',true,true,'','',false,' style="vertical-align:top;"');
        $this->_form->combine();
        $this->_form->addExplanation('todos',$this->_translator->getMessage('CONFIGURATION_TDOD_MANAGEMENT_DESC'));

        $this->_form->combine();
        $this->_form->addExplanation('todos_status',$this->_translator->getMessage('CONFIGURATION_TODO_STATUS_MANAGEMENT_DESC'));
        $this->_form->addTextField('new_status','','','','',32,'');
        $this->_form->combine('horizontal');
        $this->_form->addButton('option',$this->_translator->getMessage('CONFIGURATION_TODO_NEW_STATUS_BUTTON'),'','','200');
        $i = 0;
        foreach ($this->_status_array as $status){
           $i++;
           $this->_form->addTextField('status'.'#'.$status['value'],$status['text'],'','','',32);
           $this->_form->combine('horizontal');
           $this->_form->addButton('option'.'#'.$status['value'],$this->_translator->getMessage('CONFIGURATION_TODO_STATUS_CHANGE_BUTTON'),'','','200');
           $this->_form->combine('horizontal');
           $this->_form->addButton('option'.'#'.$status['value'],$this->_translator->getMessage('COMMON_DELETE_BUTTON'),'','','100');
        }

     }

     if ( $current_context->withRubric(CS_GROUP_TYPE)
          and $current_context->showGrouproomConfig()
        ) {
        if ( $output ) {
           $this->_form->addEmptyline();
        } else {
           $output = true;
        }
        //Gruppenoptionen
        $picture = '<img src="images/configuration_grouproom.gif" alt="picture_threaded" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
        $this->_form->addCheckbox('grouproom',1,'',$this->_translator->getMessage('GROUP_INDEX'),$this->_translator->getMessage('GROUPROOM_CONFIGURATION_CHOICE_VALUE'),'');
        $this->_form->combine();
        $this->_form->addExplanation('groups',$this->_translator->getMessage('GROUPROOM_EXPLANATION_VALUE'));
     }
	
	// Bewertungsfunktion
	$this->_form->addEmptyline();
	$this->_form->addCheckbox('assessment',1,'',$this->_translator->getMessage('COMMON_ASSESSMENT_INDEX'),$this->_translator->getMessage('COMMON_ASSESSMENT_CONFIGURATION_CHOICE_VALUE'),'');
	$this->_form->combine();
	$this->_form->addExplanation('assessments',$this->_translator->getMessage('COMMON_ASSESSMENT_EXPLANATION_VALUE'));

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_item)) {
         $this->_values['dates_status'] = $this->_item->getDatesPresentationStatus();
         $this->_values['discussion_status'] = $this->_item->getDiscussionStatus();
         $this->_values['todo_management'] = $this->_item->getTodoManagmentStatus();
         $this->_values['grouproom'] = $this->_item->isGrouproomActive();
		 $this->_values['assessment'] = $this->_item->isAssessmentActive();
        } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
         if ( !isset($this->_values['dates_status']) ) {
            $this->_values['dates_status'] = 'normal';
         }
         if ( !isset($this->_values['discussion_status']) ) {
            $this->_values['discussion_status'] = '1';
         }
         if ( !isset($this->_values['todo_management']) ) {
            $this->_values['todo_management'] = '1';
         }
      } else {
         $this->_values['dates_status'] ='normal';
         $this->_values['discussion_status'] ='1';
         $this->_values['todo_management'] ='1';
      }
   }

}
?>