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
      $this->_form->addCheckbox('workflow_trafic_light','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_WORKFLOW_TRAFFIC_LIGHT_VALUE'));
      $this->_form->combine();
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