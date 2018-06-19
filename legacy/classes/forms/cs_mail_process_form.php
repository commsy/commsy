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

include_once('classes/cs_mail_obj.php');

class cs_mail_process_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_hints = NULL;

   var $_receiver_array = NULL;
   /** constructor:
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }


   /**
    * Set the cs_mail_obj to init the form
    */
   function setMailObject($mailObj) {
      $this->_mailObj = $mailObj;
      $tmp_array = $this->_mailObj->getReceivers();
      $receiver_array = array();
      if (sizeof($tmp_array) > 0 ) {
         foreach ( $tmp_array as $name => $address ) {
            $tmp2_array = array();
            $tmp2_array['text'] = $name." [".$address."]";
            $tmp2_array['value'] = $address;
            $receiver_array[] = $tmp2_array;
         }
      }
      $this->_receiver_array = $receiver_array;
      $this->_headline = $mailObj->getMailFormHeadLine();
      $this->_hints = $mailObj->getMailFormHints();
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // annotation
      $this->setHeadline($this->_headline);
      $this->_form->addHidden('hints_text','');
      $this->_form->addHidden('senderName','');
      $this->_form->addHidden('senderAddress','');
      $this->_form->addTitleField('subject','',$this->_translator->getMessage('COMMON_MAIL_SUBJECT'),'',200,'46',true);
      if ( $this->_hints != NULL ) {
         $this->_form->addText('hints',$this->_translator->getMessage('COMMON_HINTS'),'');
      }

      if ( sizeof($this->_receiver_array) > 1 ) {
         $this->_form->addCheckBoxGroup('receivers',$this->_receiver_array,'',$this->_translator->getMessage('COMMON_MAIL_RECEIVER'),$this->_translator->getMessage('COMMON_MAIL_RECEIVER_DESC'), true, false);
      } else {
         $this->_form->addText('receiver',$this->_translator->getMessage('COMMON_MAIL_RECEIVER'),'');
         $this->_form->addHidden('receivers',$this->_receiver_array);
      }
      $this->_form->addTextArea('content','',$this->_translator->getMessage('COMMON_CONTENT'),'','60', '15', '', true,false,false);

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('MAIL_SEND_BUTTON'),$this->_translator->getMessage('MAIL_NOT_SEND_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the annotation item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();

      if (isset($this->_mailObj)) {
         $sender = $this->_mailObj->getSender();
         $senderName = "";
         $senderAddress = "";
         foreach ( $sender as $name => $address ) {
            $senderName = $name;
            $senderAddress = $address;
         }
         $this->_values['senderName'] = $senderName; // no encode here - encode in form-views
         $this->_values['senderAddress'] = $senderAddress;

         $this->_values['subject'] = $this->_mailObj->getSubject();
         $this->_values['content'] = $this->_mailObj->getContent();

         $receiver_array = $this->_mailObj->getReceivers();
         if ( sizeof($receiver_array) > 1 ) {
            $receiver_marked = array();
            foreach ( $receiver_array as $name => $address ) {
               if ( $senderAddress != $address) {
                  $receiver_marked[] = $address;
               }
            }
            $this->_values['receivers'] = $receiver_marked;
         } elseif ( sizeof($receiver_array) > 0 ) {
            $this->_values['receivers'] = $this->_receiver_array[0]['value'];
            $this->_values['receiver'] = $this->_receiver_array[0]['text'];
         } else {
         ///// No Receivers
         }

         if ( $this->_hints != NULL ) {
            $this->_values['hints'] = $this->_hints;
            $this->_values['hints_text'] = $this->_hints;
         }
      }
      if (!empty($this->_form_post)) {
         $this->_values = $this->_form_post;
         $this->_values['hints'] = $this->_form_post['hints_text'];
      }
   }
}
?>