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
class cs_configuration_date_form extends cs_rubric_form {


   /**
   * array - containing the 2 choices of the public field
   */
   var $_public_array = array();

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
       $this->_headline = $this->_translator->getMessage('CONFIGURATION_DATES_CHANGE');
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

     $radio_values = array();
           $desc =$this->_translator->getMessage('CONFIGURATION_DATES_DESC');
           $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_DATES_DESC_0').'<br/>'.
              '<img src="images/dates_presentation_normal.gif" width="350px;" style=" border:1px solid black; vertical-align: middle;"/>'.BRLF.BRLF;
     $radio_values[0]['value'] = 'normal';
     $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_DATES_DESC_1').'<br/>'.
             '<img src="images/dates_presentation_calendar.gif" width="350px;" style=" border:1px solid black; vertical-align: middle;"/>'.BRLF.BRLF;
     $radio_values[1]['value'] = 'calendar';
     $this->_form->addRadioGroup('dates_status',$this->_translator->getMessage('CONFIGURATION_DATES'),$desc,$radio_values,'',true,false);
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
    } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
         if ( !isset($this->_values['dates_status']) ) {
            $this->_values['dates_status'] = 'normal';
         }

     } else {
         $this->_values['dates_status'] ='normal';
      }
   }

}
?>