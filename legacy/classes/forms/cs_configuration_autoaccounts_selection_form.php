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

/** class for commsy form: group
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_autoaccounts_selection_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;
   var $_array = NULL;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   function setArray($array){
      $this->_array = $array;
      $temp_array = array();
      foreach($this->_array as $key =>  $data){
        $new_array= array();
        $new_array['text']  = trim($key);
        $new_array['value'] = trim($key);
        $temp_array[]= $new_array;
      }
      $this->_array = $temp_array;
   }

   function setAuthSource($autoaccounts_auth_source){
      $this->autoaccounts_auth_source = $autoaccounts_auth_source;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_FORM_HEADLINE');
      $this->setHeadline($this->_headline);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      $this->_form->addSelect('autoaccounts_lastname',$this->_array,$this->_array[0]['value'],$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SELECTION_LASTNAME'),$this->_translator->getMessage('DATE_TITLE_DESC'), 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('autoaccounts_firstname',$this->_array,$this->_array[1]['value'],$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SELECTION_FIRSTNAME'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('autoaccounts_email',$this->_array,$this->_array[2]['value'],$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SELECTION_EMAIL'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('autoaccounts_account',$this->_array,$this->_array[3]['value'],$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SELECTION_ACCOUNT'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('autoaccounts_password',$this->_array,$this->_array[4]['value'],$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SELECTION_PASSWORD'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('autoaccounts_rooms',$this->_array,$this->_array[5]['value'],$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SELECTION_ROOMS'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addEmptyline();

      $this->_form->addCheckbox('autoaccount_no_new_account_when_email_exists',1,'',$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_NEW_ACCOUNT_WHEN_EMAIL'),$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_NEW_ACCOUNT_WHEN_EMAIL_DESCRIPTION'),false,false,false,'','',false,false);

      //$this->_form->addCheckbox('autoaccount_send_email',1,'',$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEND_EMAIL'),$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEND_EMAIL_DESCRIPTION'),false,false,false,'','',false,false);

      $radio_values = array();
      $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEND_EMAIL_NO_EMAIL');
      $radio_values[0]['value'] = 'autoaccount_send_email_no_email';
      $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEND_EMAIL_COMMSY');
      $radio_values[1]['value'] = 'autoaccount_send_email_commsy';
      $radio_values[2]['text'] = $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEND_EMAIL_FORM');
      $radio_values[2]['value'] = 'autoaccount_send_email_form';
      $this->_form->addRadioGroup('autoaccount_send_email',
                                  $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEND_EMAIL'),
                                  $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEND_EMAIL'),
                                  $radio_values,
                                  '',
                                  false,
                                  false
                                 );

      $this->_form->addTextField('autoaccount_email_subject','',$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_EMAIL_SUBJECT'),'',150,50,false,'','','','left','','',false,'','',false,false);

      $this->_form->addTextArea('autoaccount_email_text','',$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_EMAIL_TEXT'),'','60','20','',false);

      $this->_form->addHidden('autoaccounts_auth_source', $this->autoaccounts_auth_source);

      $this->_form->addButtonBar('option',$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_CREATE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      } else {
         $this->_values['autoaccount_send_email'] = 'autoaccount_send_email_no_email';
      }
   }


   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      if ( ($this->_form_post['autoaccount_send_email'] == 'autoaccount_send_email_form')
           and empty($this->_form_post['autoaccount_email_subject'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_NO_SUBJECT_ERROR');
         $this->_form->setFailure('autoaccount_email_subject','');
      }
      if ( ($this->_form_post['autoaccount_send_email'] == 'autoaccount_send_email_form')
           and empty($this->_form_post['autoaccount_email_text'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_NO_TEXT_ERROR');
         $this->_form->setFailure('autoaccount_email_text','');
      }
   }

   function show_account_array($account_array){
      $this->_form->reset();
      $this->_form->addText(null, null, $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_HEADER') . ':');
      foreach($account_array as $account){
         $account_string = '';
         if($account['lastname'] == $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_INFO_MISSING')){
            $account_string .= '<font style="color:red">' . $account['lastname'] . '</font>, ';
         } else {
            $account_string .= $account['lastname'] . ', ';
         }
         if($account['firstname'] == $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_INFO_MISSING')){
            $account_string .= '<font style="color:red">' . $account['firstname'] . '</font><br/>';
         } else {
            $account_string .= $account['firstname'] . '<br/>';
         }
         $account_string .= '&nbsp;&nbsp;&nbsp;' . ' ';
         $account_string .= '<b>' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_EMAIL') . '</b>: ';
         if($account['email'] == $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_INFO_MISSING')){
            $account_string .= '<font style="color:red">' . $account['email'] . '</font><br/>';
         } else {
            $account_string .= $account['email'] . '<br/>';
         }
         $account_string .= '&nbsp;&nbsp;&nbsp;&nbsp;';
         $account_string .= '<b>' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_ACCOUNT') . '</b>: ';
         if($account['found_account_by_email']){
            $account_string .= '<b>' . $account['account'] . '</b> ';
            $account_string .= '(' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_EMAIL_EXISTS') . ')';
         } else {
            if($account['account_changed'] == 'changed'){
               $account_string .= $account['account'];
               $account_string .= ' (' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_ACCOUNT_EXISTS'). ': <i>' . $account['account_csv'] . '</i>' . ')';
            } else if($account['account_changed'] == 'generated'){
               $account_string .= $account['account'];
               $account_string .= ' (' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_ACCOUNT_GENERATED'). ')';
            } else {
               $account_string .= $account['account'];
            }
            $account_string .= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;';
            $account_string .= '<b>' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_PASSWORD') . '</b>: ';
            $account_string .= $account['password'];
            if($account['password_generated']){
               $account_string .= ' (' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_PASSWORD_GENERATED') . ')';
            }
         }
         $account_string .= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;';
         $account_string .= '<b>' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_ROOMS') . '</b>: ';
         if(!empty($account['rooms'])){
            foreach($account['rooms'] as $room){
               if(in_array($room, $account['rooms_added']['added'])){
                  $account_string .= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;';
                  $account_string .= ahref_curl($room, 'account', 'index', null, $room) . ' - ' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_ROOMS_ADDED');
               } else if (in_array($room, $account['rooms_added']['not_existing'])) {
                  $account_string .= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;';
                  $account_string .= $room . ' - ' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_ROOMS_NOT_EXISTING');
               }  else {
                  $account_string .= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;';
                  $account_string .= ahref_curl($room, 'account', 'index', null, $room) . ' - ' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SHOW_LIST_ROOMS_NOT_ADDED');
               }
            }
         }
         if ( isset($account['account_not_created'])
              and $account['account_not_created']
            ) {
            $account_string .= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;';
            $account_string .= '<font style="color:red"><b>' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_ACCOUNT_NOT_CREATED') . '</b></font> ';
            if ( isset($account['has_comment'])
                 and $account['has_comment']
               ) {
               $account_string .= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;';
               $account_string .= '<font style="color:red"><b>' . $account['comment'] . '</b></font> ';
            }
         }
         $this->_form->addText(null, null, $account_string);
      }
   }
}
?>