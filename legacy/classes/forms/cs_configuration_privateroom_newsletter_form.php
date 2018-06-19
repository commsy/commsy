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
class cs_configuration_privateroom_newsletter_form extends cs_rubric_form {

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
    */
   function _initForm () {
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_TITLE');
      $this->setHeadline($this->_headline);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // form fields
#	  $this->_form->addHidden('iid','');
         $radio_values = array();
    $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_NONE');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_WEEKLY');
         $radio_values[1]['value'] = '2';
         $radio_values[2]['text'] = $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_DAILY');
         $radio_values[2]['value'] = '3';
         $this->_form->addRadioGroup('newsletter',$this->_translator->getMessage('CONFIGURATION_NEWSLETTER'),'',$radio_values,'',true,false);

      // 2007-04-11 Warnhinweis dass Nachricht nur in HTML-Format gesendet wirde
      // Warning for Message is sent in HTML format only
      $this->_form->addText('newsletter_note', $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_NOTE_LABEL'), $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_NOTE'));

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');



   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }else{
         $room = $this->_environment->getCurrentContextItem();
         $newlsetter = $room->getPrivateRoomNewsletterActivity();
         if ($newlsetter == 'weekly'){
            $this->_values['newsletter'] ='2';
         }elseif ($newlsetter == 'daily'){
            $this->_values['newsletter'] ='3';
         }else{
            $this->_values['newsletter'] ='1';

         }
      }
   }

}
?>