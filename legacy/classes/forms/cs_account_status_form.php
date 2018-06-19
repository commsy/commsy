<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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
include_once('functions/text_functions.php');

/** class for commsy form: edit an account
 * this class implements an interface for the creation of a form in the commsy style: edit an account
 */
class cs_account_status_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * string - containing the text of the form
   */
   var $_text = NULL;

  /**
   * boolean - containing the choice, if an delete button will appear in the form or not
   */
   var $_with_delete_button = NULL;

   var $_options = array();

   var $_user_status = NULL;

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data (text and options) for the form
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      // if an item is given - first call of the form
      if ( !empty($this->_item) ) {
         $this->_headline = $this->_translator->getMessage('ADMIN_USER_FORM_TITLE',$this->_item->getFullname());
         $this->_user_id = $this->_item->getUserID();
         $this->_user_fullname = $this->_item->getFullname();
         $this->_user_status = $this->_item->getStatus();
         $this->_user_lastlogin = $this->_item->getLastLogin();
         $this->_admin_comment = $this->_item->getAdminComment();
      }

      // if form posts are given - second call of the form
      else {
         $this->_headline = $this->_translator->getMessage('ADMIN_USER_FORM_TITLE',$this->_form_post['fullname']);

         if ( !empty($this->_form_post['lastlogin'])
              and $this->_form_post['lastlogin'] != '0000-00-00 00:00:00' ) {
            $this->_with_delete_button = false;
         }
         $this->_user_id = $this->_form_post['user_id'];
         $this->_user_fullname = $this->_form_post['fullname'];

         if (!empty($this->_form_post['status'])) {
            $this->_user_status = $this->_form_post['status'];
         } else {
            $this->_user_status = '';
         }
         $this->_user_lastlogin = $this->_form_post['lastlogin'];
      }

      // transform the user status into a text message
      $this->_status_old = '';
      if ( $this->_user_status == 3 ) {
         $this->_status_message = 'USER_STATUS_MODERATOR';
         $this->_selected = 'moderator';
      } elseif ( $this->_user_status == 2 ) {
         $this->_status_message = 'USER_STATUS_USER';
         $this->_selected = 'user';
      } elseif ( $this->_user_status == 1 ) {
         $this->_status_message = 'USER_STATUS_REQUESTED';
         $this->_selected = 'user';
         $this->_status_old = 'request';
      } else {
         if ( !empty($this->_user_lastlogin) ) {
            $this->_status_message = 'USER_STATUS_CLOSED';
         } else {
            $this->_status_message = 'USER_STATUS_REJECT';
         }
         $this->_selected = 'close';
      }

      // prepare status options for the form
      if ( $this->_user_status == 1 ) {
         $this->_options[0]['text']  = $this->_translator->getMessage('USER_STATUS_REJECT');
         $this->_options[0]['value'] = 'reject';
      } else {
         $this->_options[0]['text']  = $this->_translator->getMessage('USER_STATUS_CLOSED');
         $this->_options[0]['value'] = 'close';
      }
      $this->_options[1]['text']  = $this->_translator->getMessage('USER_STATUS_USER');
      $this->_options[1]['value'] = 'user';
      $this->_options[2]['text']  = $this->_translator->getMessage('USER_STATUS_MODERATOR');
      $this->_options[2]['value'] = 'moderator';

      // prepare lastlogin information
      if ( empty($this->_user_lastlogin) or ($this->_user_lastlogin == '0000-00-00 00:00:00') ) {
         $this->_user_lastlogin = $this->_translator->getMessage('USER_NEVER_LOGIN');
      } else {
         $this->_user_lastlogin = getDateTimeInLang($this->_user_lastlogin);
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // headline and hidden fields
      $this->setHeadline($this->_headline);
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('fullname','');
      $this->_form->addHidden('lastlogin','');
      $this->_form->addHidden('user_id','');
      $this->_form->addHidden('status_old',$this->_status_old);
      
      $user = false;
      $moderator = false;

      // content form fields
      $this->_form->addText('fullname_text',
                            $this->_translator->getMessage('USER_FULLNAME'),
                            $this->_user_fullname
                           );
      $this->_form->addText('user_id_text',
                            $this->_translator->getMessage('USER_USER_ID'),
                            $this->_user_id
                           );
      if ( $this->_environment->inPortal() ) {
         $this->_form->addText('lastlogin_text',
                               $this->_translator->getMessage('USER_LASTLOGIN'),
                               $this->_user_lastlogin,
                               $this->_translator->getMessage('USER_LASTLOGIN_ADMIN_DESC')
                              );
      }
      switch ( $this->_status_message ){
         case 'USER_STATUS_MODERATOR':
            $tempMessage = $this->_translator->getMessage('USER_STATUS_MODERATOR');
            $moderator = true;
            break;
         case 'USER_STATUS_USER':
            $tempMessage = $this->_translator->getMessage('USER_STATUS_USER');
            $user = true;
            break;
         case 'USER_STATUS_REQUESTED':
            $tempMessage = $this->_translator->getMessage('USER_STATUS_REQUESTED');
            break;
         case 'USER_STATUS_CLOSED':
            $tempMessage = $this->_translator->getMessage('USER_STATUS_CLOSED');
            break;
         case 'USER_STATUS_REJECT':
            $tempMessage = $this->_translator->getMessage('USER_STATUS_REJECT');
            break;
         default:
            $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_account_status_form(198) ');
            break;
      }
      $this->_form->addText('status_text',
                            $this->_translator->getMessage('USER_STATUS_NOW'),
                            $tempMessage,
                            $this->_translator->getMessage('USER_STATUS_DESC')
                           );
      $this->_form->addRadioGroup('status',
                                  $this->_translator->getMessage('USER_STATUS_NEW'),
                                  $this->_translator->getMessage('USER_STATUS_ADMIN_DESC'),
                                  $this->_options,
                                  $this->_selected,
                                  true,
                                  true
                                 );
      $this->_form->addCheckbox('contact_person',
                                1,
                                '',
                                $this->_translator->getMessage('ROOM_CONTACT'),
                                $this->_translator->getMessage('ROOM_CONTACT'),
                                ''
                               );
      $current_user = $this->_environment->getCurrentUser();
      if($current_user->isRoot()){
      	
      	$this->_yes_no_array[0]['text'] = $this->_translator->getMessage('COMMON_YES');
      	$this->_yes_no_array[0]['value'] = 1;
      	$this->_yes_no_array[1]['text'] = $this->_translator->getMessage('COMMON_NO');
      	$this->_yes_no_array[1]['value'] = 2;
      	
      	$this->_form->addRadioGroup('login_as',$this->_translator->getMessage('USER_LOGIN_AS'),'',$this->_yes_no_array,'','',true,'','','');
      	$this->_form->addTextfield('days_interval','',$this->_translator->getMessage('USER_LOGIN_AS_DAYS'),'',3,10,false,'','','','','','','');
      } else {
      	
      	$this->_yes_no_array[0]['text'] = $this->_translator->getMessage('COMMON_YES');
      	$this->_yes_no_array[0]['value'] = 1;
      	$this->_yes_no_array[1]['text'] = $this->_translator->getMessage('COMMON_NO');
      	$this->_yes_no_array[1]['value'] = 2;
      	
      	$this->_form->addRadioGroup('login_as',$this->_translator->getMessage('USER_LOGIN_AS'),'',$this->_yes_no_array,'','',true,'','',true);
      	$this->_form->addTextfield('days_interval','',$this->_translator->getMessage('USER_LOGIN_AS_DAYS'),'',3,10,false,'','','','','','',true);
      }

      // buttons
      $this->_form->addButtonBar('option',
                                 $this->_translator->getMessage('COMMON_CHANGE_BUTTON'),
                                 $this->_translator->getMessage('ADMIN_CANCEL_BUTTON'),
                                 $this->_translator->getMessage('ACCOUNT_DELETE_BUTTON')
                                );
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      if ( empty($this->_form_post) ) {
         $this->_values['iid']            = $this->_item->getItemID();
         $this->_values['fullname']       = $this->_item->getFullname();
         $this->_values['lastlogin']      = $this->_item->getLastlogin();
         $this->_values['user_id']        = $this->_item->getUserID();
         $this->_values['contact_person'] = $this->_item->isContact();
         // deactivate by default the login as feature
         
         $deactived_login_as = $this->_item->isDeactivatedLoginAsAnotherUser();
         #pr($deactived_login_as);
         if($deactived_login_as == 1){
         	$this->_values['login_as']		  = 1;
         } elseif (empty($deactived_login_as)) {
          global $symfonyContainer;
          $allow_moderator_takeover = $symfonyContainer->getParameter('commsy.security.allow_moderator_takeover');
          
         	if(!$allow_moderator_takeover){
         		$this->_values['login_as']		  = 1;
         	} else {
         		$this->_values['login_as']		  = 2;
         	}
         } else{
         	$this->_values['login_as']		  = 2;
         }
         $this->_values['days_interval'] = 0;
         
      } else {
         $this->_values = $this->_form_post;
      }
   }

   /** specific check the values of the form
     * this methods check the entered values
     */
     function _checkValues () {
     	$return = true;
        //Don't loose last moderator by closing or downgrading to user
        if ( ( isset($this->_form_post['option'])
               and $this->_form_post['option'] == $this->_translator->getMessage('ACCOUNT_DELETE_BUTTON')
             )
             or ( isset($this->_form_post['status'])
                  and $this->_form_post['status'] != 'moderator'
                )
           ) {
           $user_manager = $this->_environment->getUserManager();
           $user_manager->resetLimits();
           $user_manager->setContextLimit($this->_environment->getCurrentContextID());
           $user_manager->setModeratorLimit();
           $moderator_count = $user_manager->getCountAll();
           if ($moderator_count == 1) {
              $user_manager->select();
              $moderator_list = $user_manager->get();
              $moderator_item = $moderator_list->getFirst();
              if ($moderator_item->getItemID() == $this->_form_post['iid']) {
                 $this->_error_array[] = $this->_translator->getMessage('ERROR_LAST_MODERATOR');
                 $this->_form->setFailure('status');
                 $return = false;
              }
           }
        }
        return $return;
     }
}
?>