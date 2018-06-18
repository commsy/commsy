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

class cs_configuration_workflow_form extends cs_rubric_form {

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   function _initForm () {
      $current_context= $this->_environment->getCurrentContextItem();
   }


   function _createForm () {
      $current_context= $this->_environment->getCurrentContextItem();
      // trafic light
      $this->_form->addCheckbox('workflow_trafic_light','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_TRAFFIC_LIGHT_VALUE'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_TRAFFIC_LIGHT_ENABLE'));

      $radio_values = array();
      $radio_values[0]['text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_NONE');
      $radio_values[0]['value'] = '3_none';
      $radio_values[1]['text'] = '<img src="images/commsyicons/workflow_traffic_light_green.png" style="height:10px;">';
      $radio_values[1]['value'] = '0_green';
      $radio_values[2]['text'] = '<img src="images/commsyicons/workflow_traffic_light_yellow.png" style="height:10px;">';
      $radio_values[2]['value'] = '1_yellow';
      $radio_values[3]['text'] = '<img src="images/commsyicons/workflow_traffic_light_red.png" style="height:10px;">';
      $radio_values[3]['value'] = '2_red';
      $this->_form->addRadioGroup('workflow_trafic_light_default',
                                  $this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_TRAFFIC_LIGHT_DEFAULT'),
                                  $this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW'),
                                  $radio_values,
                                  '',
                                  false,
                                  false
                                  );

      $this->_form->addTextfield('workflow_trafic_light_green_text','',$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),200,28,false,'','','','left','<img src="images/commsyicons/workflow_traffic_light_green.png" style="height:10px;">');
      $this->_form->combine();
      $this->_form->addTextfield('workflow_trafic_light_yellow_text','',$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),200,28,false,'','','','left','<img src="images/commsyicons/workflow_traffic_light_yellow.png" style="height:10px;">');
      $this->_form->combine();
      $this->_form->addTextfield('workflow_trafic_light_red_text','',$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),200,28,false,'','','','left','<img src="images/commsyicons/workflow_traffic_light_red.png" style="height:10px;">');

      $this->_form->addText('','','&nbsp;');

      $this->_form->addCheckbox('workflow_resubmission','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_VALUE'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_ENABLE'));
      
      $this->_form->addText('','','&nbsp;');
      
      $this->_form->addCheckbox('workflow_reader','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_READER_VALUE'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_READER_ENABLE'));
      
      $this->_form->addCheckbox('workflow_reader_group','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_READER_GROUP_PERSON_VALUE'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_READER_GROUP_VALUE'));
      $this->_form->combine();
      $this->_form->addCheckbox('workflow_reader_person','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_READER_GROUP_PERSON_VALUE'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_READER_PERSON_VALUE'));
      
      $radio_values = array();
      $radio_values[0]['text'] = $this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_SHOW_TO_MODERATOR_VALUE');
      $radio_values[0]['value'] = 'moderator';
      $radio_values[1]['text'] = $this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_SHOW_TO_ALL_VALUE');
      $radio_values[1]['value'] = 'all';
      $this->_form->addRadioGroup('workflow_resubmission_show_to',
                                  $this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_SHOW_TO_DEFAULT'),
                                  $this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW'),
                                  $radio_values,
                                  '',
                                  false,
                                  false
                                  );
      
      $this->_form->addText('','','&nbsp;');

      $this->_form->addCheckbox('workflow_validity','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_VALUE'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_ENABLE'));
                                 
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   function _prepareValues () {
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }else{
         $room = $this->_environment->getCurrentContextItem();
         
         // traffic light
         if ($room->withWorkflowTrafficLight()){
            $this->_values['workflow_trafic_light'] = 'yes';
         }
         $this->_values['workflow_trafic_light_default'] = $room->getWorkflowTrafficLightDefault();
         if($room->getWorkflowTrafficLightTextGreen() != ''){
            $this->_values['workflow_trafic_light_green_text'] = $room->getWorkflowTrafficLightTextGreen();
         } else {
            $this->_values['workflow_trafic_light_green_text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT');
         }
         if($room->getWorkflowTrafficLightTextYellow() != ''){
            $this->_values['workflow_trafic_light_yellow_text'] = $room->getWorkflowTrafficLightTextYellow();
         } else {
            $this->_values['workflow_trafic_light_yellow_text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT');
         }
         if($room->getWorkflowTrafficLightTextRed() != ''){
            $this->_values['workflow_trafic_light_red_text'] = $room->getWorkflowTrafficLightTextRed();
         } else {
            $this->_values['workflow_trafic_light_red_text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT');
         }
         
         // resubmission
         if ($room->withWorkflowResubmission()){
            $this->_values['workflow_resubmission'] = 'yes';
         }
         
         // reader
         if ($room->withWorkflowReader()){
            $this->_values['workflow_reader'] = 'yes';
         }
         $this->_values['workflow_reader_group'] = $room->getWorkflowReaderGroup();
         $this->_values['workflow_reader_person'] = $room->getWorkflowReaderPerson();
         
         $this->_values['workflow_resubmission_show_to'] = $room->getWorkflowReaderShowTo();
      }
   }
   
   function _checkValues () {
      $context_item = $this->_environment->getCurrentContextItem();
      #pr($this->_form_post);
      if (empty($this->_form_post['workflow_trafic_light_green_text'])) {
         $this->_error_array[] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_EMPTY_ERROR');
         $this->_form->setFailure('workflow_trafic_light_green_text','');
      }
      if (empty($this->_form_post['workflow_trafic_light_yellow_text'])) {
         $this->_error_array[] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_EMPTY_ERROR');
         $this->_form->setFailure('workflow_trafic_light_yellow_text','');
      }
      if (empty($this->_form_post['workflow_trafic_light_red_text'])) {
         $this->_error_array[] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_EMPTY_ERROR');
         $this->_form->setFailure('workflow_trafic_light_red_text','');
      }
  }
}
?>