<?PHP
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
include_once('functions/text_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_defaults_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_context = NULL;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_configuration_defaults_form ($environment) {
      $this->cs_rubric_form($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      // headline
      $this->setHeadline(getMessage('CONFIGURATION_DEFAULTS'));
	  $room = $this->_environment->getCurrentContextItem();

	  if ( isset($this->_item) ) {
	     $this->_context = $this->_item->getRoomContext();
	  } elseif (isset($this->_form_post['context'])) {
	     $this->_context = $this->_form_post['context'];
	  } else{
	     $this->_context = $room>getRoomContext();
	  }
  }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

	  $room = $this->_environment->getCurrentContextItem();
	  if ( $room->isProjectRoom() ){
	   	  $radio_values = array();
		  $radio_values[0]['text'] = getMessage('COMMON_UNIVERSITY_CONTEXT').BR.
	      '<table summary="Layout"><tr style="border:0px;"><td style="border:0px;"><img src="images/default_uni.gif" alt="'.getMessage('COMMON_UNIVERSITY_CONTEXT').'" style=" width: 290px; border:1px solid black; vertical-align: middle;"/>'.
		  '</td><td style="border:0px;">'.getMessage('ROOM_UNIVERSITY_CONTEXT_DESCRIPTION').'</td></tr></table>';
		  $radio_values[0]['value'] = 'uni';
		  $radio_values[1]['text'] = getMessage('COMMON_SCHOOL_CONTEXT').BR.
	      '<table summary="Layout"><tr style="border:0px;"><td style="border:0px;"><img src="images/default_school.gif" alt="'.getMessage('COMMON_SCHOOL_CONTEXT').'" style=" width: 290px; border:1px solid black; vertical-align: middle;"/>'.
	      '</td><td style="border:0px;">'.getMessage('ROOM_SCHOOL_CONTEXT_DESCRIPTION').'</td></tr></table>';
		  $radio_values[1]['value'] = 'school';
		  $radio_values[2]['text'] = getMessage('ROOM_TYPE_BUSINESS').BR.
	      '<table summary="Layout"><tr style="border:0px;"><td style="border:0px;"><img src="images/default_business.gif" alt="'.getMessage('ROOM_TYPE_BUSINESS').'" style=" width: 290px; border:1px solid black; vertical-align: middle;"/>'.
	      '</td><td style="border:0px;">'.getMessage('ROOM_BUSINESS_CONTEXT_DESCRIPTION').'</td></tr></table>';
		  $radio_values[2]['value'] = 'project';
		  $this->_form->addRadioGroup('context','','',$radio_values,'',false,true);
		  unset($radio_values);
	  } elseif ( $room->isGroupRoom() ) {
	   	  $radio_values = array();
		  $radio_values[0]['text'] = getMessage('COMMON_UNIVERSITY_CONTEXT').BR.
	      '<table summary="Layout"><tr style="border:0px;"><td style="border:0px;"><img src="images/default_uni.gif" alt="'.getMessage('COMMON_UNIVERSITY_CONTEXT').'" style=" width: 290px; border:1px solid black; vertical-align: middle;"/>'.
		  '</td><td style="border:0px;">'.getMessage('ROOM_GR_UNIVERSITY_CONTEXT_DESCRIPTION').'</td></tr></table>';
		  $radio_values[0]['value'] = 'uni';
		  $radio_values[1]['text'] = getMessage('COMMON_SCHOOL_CONTEXT').BR.
	      '<table summary="Layout"><tr style="border:0px;"><td style="border:0px;"><img src="images/default_school.gif" alt="'.getMessage('COMMON_SCHOOL_CONTEXT').'" style=" width: 290px; border:1px solid black; vertical-align: middle;"/>'.
	      '</td><td style="border:0px;">'.getMessage('ROOM_GR_SCHOOL_CONTEXT_DESCRIPTION').'</td></tr></table>';
		  $radio_values[1]['value'] = 'school';
		  $radio_values[2]['text'] = getMessage('ROOM_TYPE_BUSINESS').BR.
	      '<table summary="Layout"><tr style="border:0px;"><td style="border:0px;"><img src="images/default_business.gif" alt="'.getMessage('ROOM_TYPE_BUSINESS').'" style=" width: 290px; border:1px solid black; vertical-align: middle;"/>'.
	      '</td><td style="border:0px;">'.getMessage('ROOM_GR_BUSINESS_CONTEXT_DESCRIPTION').'</td></tr></table>';
		  $radio_values[2]['value'] = 'project';
		  $this->_form->addRadioGroup('context','','',$radio_values,'',false,true);
		  unset($radio_values);
	  }else{
	   	  $radio_values = array();
		  $radio_values[0]['text'] = getMessage('COMMON_UNIVERSITY_CONTEXT').BR.
	      '<table summary="Layout"><tr style="border:0px;"><td style="border:0px;"><img src="images/default_uni_cr.gif"  alt="'.getMessage('COMMON_UNIVERSITY_CONTEXT').'" style=" width: 290px; border:1px solid black; vertical-align: middle;"/>'.
		  '</td><td style="border:0px;">'.getMessage('ROOM_CR_UNIVERSITY_CONTEXT_DESCRIPTION').'</td></tr></table>';
		  $radio_values[0]['value'] = 'uni';
		  $radio_values[1]['text'] = getMessage('COMMON_SCHOOL_CONTEXT').BR.
	      '<table summary="Layout"><tr style="border:0px;"><td style="border:0px;"><img src="images/default_school_cr.gif"  alt="'.getMessage('COMMON_SCHOOL_CONTEXT').'" style=" width: 290px; border:1px solid black; vertical-align: middle;"/>'.
	      '</td><td style="border:0px;">'.getMessage('ROOM_CR_SCHOOL_CONTEXT_DESCRIPTION').'</td></tr></table>';
		  $radio_values[1]['value'] = 'school';
		  $radio_values[2]['text'] = getMessage('ROOM_TYPE_BUSINESS').BR.
	      '<table summary="Layout"><tr style="border:0px;"><td style="border:0px;"><img src="images/default_business_cr.gif"  alt="'.getMessage('ROOM_TYPE_BUSINESS').'" style=" width: 290px; border:1px solid black; vertical-align: middle;"/>'.
	      '</td><td style="border:0px;">'.getMessage('ROOM_CR_BUSINESS_CONTEXT_DESCRIPTION').'</td></tr></table>';
		  $radio_values[2]['value'] = 'project';
		  $this->_form->addRadioGroup('context','','',$radio_values,'',false,true);
		  unset($radio_values);
	  }
      // buttons
      $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }



   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
     if (isset($this->_form_post)) {
          $this->_values = $this->_form_post;
      } else{
	     $room = $this->_environment->getCurrentContextItem();
		 $this->_values['context'] = $room->getRoomContext();
      }
   }

 }

?>