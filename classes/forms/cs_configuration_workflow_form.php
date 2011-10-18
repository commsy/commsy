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
   function cs_configuration_workflow_form ($params) {
      $this->cs_rubric_form($params);
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
      $radio_values[0]['value'] = '0';
      $radio_values[1]['text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_GREEN');
      $radio_values[1]['value'] = '1';
      $radio_values[2]['text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_YELLOW');
      $radio_values[2]['value'] = '2';
      $radio_values[3]['text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_RED');
      $radio_values[3]['value'] = '3';
      $this->_form->addRadioGroup('workflow_trafic_light_default',
                                  $this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_TRAFFIC_LIGHT_DEFAULT'),
                                  $this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW'),
                                  $radio_values,
                                  '',
                                  false,
                                  false
                                  );
      
      $this->_form->addTextfield('workflow_trafic_light_green_text','',$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),200,28,false,'','','','left','<div style="float:left; width:30px;">'.$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_GREEN').':</div>');
      $this->_form->combine();
      $this->_form->addTextfield('workflow_trafic_light_yellow_text','',$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),200,28,false,'','','','left','<div style="float:left; width:30px;">'.$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_YELLOW').':</div>');
      $this->_form->combine();
      $this->_form->addTextfield('workflow_trafic_light_red_text','',$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT'),200,28,false,'','','','left','<div style="float:left; width:30px;">'.$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_RED').':</div>');

      $this->_form->addCheckbox('workflow_resubmission','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_VALUE'));
      $this->_form->combine();
      $this->_form->addCheckbox('buzzword_reader','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_READER_VALUE'));
      
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   function _prepareValues () {
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }else{
         $room = $this->_environment->getCurrentContextItem();
         if ($room->withWorkflowTrafficLight()){
            $this->_values['workflow_trafic_light'] = 'yes';
            $this->_values['workflow_trafic_light_default'] = '0';
            $this->_values['workflow_trafic_light_green_text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT');
            $this->_values['workflow_trafic_light_yellow_text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT');
            $this->_values['workflow_trafic_light_red_text'] = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT');
         }
         
         if ($room->withWorkflowResubmission()){
            $this->_values['workflow_resubmission'] = 'yes';
         }
         if ($room->withWorkflowReader()){
            $this->_values['buzzword_reader'] = 'yes';
         }
      }
   }
}
?>