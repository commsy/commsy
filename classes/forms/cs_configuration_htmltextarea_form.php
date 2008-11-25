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

include_once('classes/cs_rubric_form.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_htmltextarea_form extends cs_rubric_form {


   /**
   * array - containing the 2 choices of the public field
   */
   var $_public_array = array();

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function cs_configuration_htmltextarea_form($params) {
      $this->cs_rubric_form($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
       $this->_headline = getMessage('CONFIGURATION_HTMLTEXTAREA_CHANGE');
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
      $radio_values[0]['text'] = getMessage('CONFIGURATION_HTMLTEXTAREA_DESC_0').
              '<img src="images/without_html_text_area.gif" width="290px;" style=" border:1px solid black; vertical-align: middle;"/>'.BRLF.BRLF;
     $radio_values[0]['value'] = '3';
     $radio_values[1]['text'] = getMessage('CONFIGURATION_HTMLTEXTAREA_DESC_2').
             '<img src="images/html_text_area_min.gif" width="290px;" style=" border:1px solid black; vertical-align: middle;"/>'.BRLF.BRLF;
     $radio_values[1]['value'] = '2';
     $radio_values[2]['text'] = getMessage('CONFIGURATION_HTMLTEXTAREA_DESC_1').
             '<img src="images/html_text_area.gif" width="290px;" style=" border:1px solid black; vertical-align: middle;"/>';
     $radio_values[2]['value'] = '1';

     $this->_form->addRadioGroup('html_status',getMessage('CONFIGURATION_HTMLTEXTAREA'),'',$radio_values,'',true,false);
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
         $this->_values['html_status'] = $this->_item->getHtmlTextAreaStatus();
    } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
         if ( !isset($this->_values['html_status']) ) {
            $this->_values['html_status'] = '3';
         }

     } else {
         $this->_values['html_status'] ='2';
      }
   }

}
?>