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
class cs_configuration_mediaintegration_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function cs_configuration_plugins_form ($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form
    */
   function _initForm () {

      // headline
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_MEDIA_INTEGRATION');
	  $image = '<img src="images/commsyicons/48x48/config/video.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_HTMLTEXTAREA_FORM_TITLE').'"/>';
	  
      if ( !empty($image) ) {
         $this->_headline = $image.' '.$this->_headline;
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->setHeadline($this->_headline);
      $this->_form->addText('text','',$this->_translator->getMessage('CONFIGURATION_MEDIA_INTEGRATION_DESC'),'');
      // media integration
      $this->_form->addEmptyLine();
      
      if($this->_environment->inCommunityRoom()) {
        // mediendistribution online      
        if($this->_environment->inCommunityRoom()) {
          $this->_form->addRadioGroup(  'mdo_active', $this->_translator->getMessage('CONFIGURATION_MEDIA_MEDIENINTEGRATIONONLINE'), '',
                                        array(
                                                array(  'text'  => $this->_translator->getMessage('COMMON_ON'),
                                                        'value' => 1),
                                                array(  'text'  => $this->_translator->getMessage('COMMON_OFF'),
                                                        'value' => -1)), '', true, true);
          $this->_form->combine();
          $this->_form->addText('description', '', $this->_translator->getMessage('CONFIGURATION_MEDIA_MEDIENINTEGRATIONONLINE_DESC'));
          
          $this->_form->addTextfield('mdo_key', '', $this->_translator->getMessage('CONFIGURATION_MEDIA_MEDIENINTEGRATIONONLINE_KEY'), '', 40, 40, true);
        }
      }
      
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'','','','','','');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
      } else {
         // mediendistribution online
         if($this->_environment->inCommunityRoom()) {
           // get information from community room extra
           $community_room = $this->_environment->getCurrentContextItem();
           
           $mdo_active = $community_room->getMDOActive();
           if(empty($mdo_active) || $mdo_active === -1) {
             $this->_values['mdo_active'] = -1;
           } else {
             $this->_values['mdo_active'] = 1;
           }
           
           $mdo_key = $community_room->getMDOKey();
           if(empty($mdo_key) || $mdo_key === -1) {
             $this->_values['mdo_key'] = '';
           } else {
             $this->_values['mdo_key'] = $mdo_key;
           }
         }
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }

   /** reset rubric form
    *  reset this rubric form (item, values, postvars and the form [elements])
    */
   function reset () {
      parent::reset();
      unset($this->_array_plugins);
   }
}
?>