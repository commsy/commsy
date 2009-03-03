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

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_configuration_rubric_extras_form($environment) {
      $this->cs_rubric_form($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
       $this->_headline = getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE');
       $this->setHeadline($this->_headline);
       $current_user = $this->_environment->getCurrentUser();
       $fullname = $current_user->getFullname();
   }


   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

     //Terminoptionen
	  $radio_values = array();
     $desc = getMessage('CONFIGURATION_DATES_DESC');
     $radio_values[0]['text'] = '<img src="images/dates_presentation_normal.gif" width="150px;" style=" border:1px solid black; vertical-align: middle;"/>';
	  $radio_values[0]['value'] = 'normal';
	  $radio_values[1]['text'] = '<img src="images/dates_presentation_calendar.gif" width="150px;" style=" border:1px solid black; vertical-align: middle;"/>';
	  $radio_values[1]['value'] = 'calendar';
	  $this->_form->addRadioGroup('dates_status',getMessage('DATES_INDEX'),$desc,$radio_values,'',true,true,'','',false,' style="vertical-align:top;"');
     $this->_form->combine();
     $this->_form->addExplanation('dates',getMessage('CONFIGURATION_DATES_DESC'));
     $this->_form->addEmptyline();

     //Diskussionsoptionen
     $radio_values = array();
     $radio_values[0]['text'] = '<img src="images/configuration_discussion_not_threaded.gif" alt="picture_simple" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
     $radio_values[0]['value'] = '1';
     $radio_values[1]['text'] = '<img src="images/configuration_discussion_threaded.gif" alt="picture_threaded" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
     $radio_values[1]['value'] = '2';
     $radio_values[2]['text'] = '<span style="vertical-align: top;">'.getMessage('CONFIGURATION_DISCUSSION_DESC_3').'</span>';
     $radio_values[2]['value'] = '3';
     $this->_form->addRadioGroup('discussion_status',getMessage('DISCUSSION_INDEX'),'',$radio_values,'',true,true,'','',false,' style="vertical-align:top;"');
     $this->_form->combine();
     $this->_form->addExplanation('discussions',getMessage('CONFIGURATION_DISCUSSION_WARNING'));


     if ($this->_environment->inProjectRoom()){
        $this->_form->addEmptyline();
        //Todooption
        $radio_values = array();
        $radio_values[0]['text'] = '<img src="images/configuration_todo_no_management.gif" alt="picture_todo_no_management" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
        $radio_values[0]['value'] = '1';
        $radio_values[1]['text'] = '<img src="images/configuration_todo_management.gif" alt="picture_todo_management" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
        $radio_values[1]['value'] = '2';
        $this->_form->addRadioGroup('todo_management',getMessage('TODO_INDEX'),'',$radio_values,'',true,true,'','',false,' style="vertical-align:top;"');
        $this->_form->combine();
        $this->_form->addExplanation('todos',getMessage('CONFIGURATION_TDOD_MANAGEMENT_DESC'));

        $this->_form->addEmptyline();
        //Gruppenoptionen
        $picture = '<img src="images/configuration_grouproom.gif" alt="picture_threaded" style=" width:150px; border:1px solid black; vertical-align: middle;"/>';
        $this->_form->addCheckbox('grouproom',1,'',getMessage('GROUP_INDEX'),getMessage('GROUPROOM_CONFIGURATION_CHOICE_VALUE'),'');
        $this->_form->combine();
        $this->_form->addExplanation('groups',getMessage('GROUPROOM_EXPLANATION_VALUE'));
     }

      // buttons
      $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'),'');
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