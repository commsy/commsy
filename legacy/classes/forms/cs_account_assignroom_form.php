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
class cs_account_assignroom_form extends cs_rubric_form {

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
         $this->_headline = 'Kennung einem Raum zuordnen'; ############### TODO: MESSAGE TAG
         $this->_user_id = $this->_item->getUserID();
         $this->_user_fullname = $this->_item->getFullname();
//          $this->_admin_comment = $this->_item->getAdminComment();
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
      $this->_form->addHidden('user_id','');
      $this->_form->addHidden('auth_source', '');
      
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
      
      
      if(!empty($_POST['room_search'])) {
      	$room_search = $_POST['room_search'];
      } else {
      	$room_search = '';
      }
      
      $this->_form->addTextfield(	'room_search',
      		$room_search,
      		'Suche nach Raum',
      		'',
      		255,
      		20,
      		false,
      		'Suchen',
      		'submit',
      		'',
      		'',
      		'',
      		'',
      		false,
      		'');
      $disabled = true;
      $this->room_array = array();
      if(!empty($_POST['room_search'])) {
      	// search for room
      	$project_manager = $this->_environment->getProjectManager();
      	$room_search = $_POST['room_search'];
      	$items = $project_manager->getRoomsByTitle($room_search, $this->_environment->getCurrentPortalID());
      	#pr($items);
      	 
      	$item = $items->getFirst();
      	while($item) {
      		$this->room_array[] = array(   'text'   =>   $item->getTitle(),
      				'value'	=>   $item->getItemId());
      
      		$item = $items->getNext();
      	}
      	$room_array = $this->room_array;
      	 
      	$this->_form->addSelect(   'room_id',
      			$room_array,
      			'',
      			$this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_ROOM_SELECT'),
      			'',
      			'',
      			'',
      			'',
      			true);
      	
      	$disabled = false;
      }
      
//       $this->_form->addTextfield('room_id',
//                           		 '',
//                           		 'Raum ID',
//                           		 '',
//                           		 '',
//                           		 '20',
//                           		 false,
//                           		 '',
//                           		 ''
//    								);
      
      
      
      $this->_form->addTextarea('description',
                              	'',
                              	'Teilnahmebeschreibung',
                              	'',
                              	'40',
                              	'',
                              	'',
                              	false,
                              	$disabled,
                              	'',
                              	'',
                              	'',
                              	''
   								);
      

      $current_user = $this->_environment->getCurrentUser();

      // buttons
      $this->_form->addButtonBar('option',
                                 $this->_translator->getMessage('COMMON_CHANGE_BUTTON'),
                                 $this->_translator->getMessage('ADMIN_CANCEL_BUTTON')
//                                  $this->_translator->getMessage('ACCOUNT_DELETE_BUTTON')
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
         $this->_values['user_id']        = $this->_item->getUserID();
         $this->_values['auth_source']	  = $this->_item->getAuthSource();
//          $this->_values['description']       = $this->_item->getFullname();
         
         
         
      } else {
         $this->_values = $this->_form_post;
      }
   }

   /** specific check the values of the form
     * this methods check the entered values
     */
     function _checkValues () {
     	$return = true;
     	if (empty($this->_form_post['room_id'])){
     		$this->_error_array[] = '';
     		$this->_form->setFailure('status');
     		$return = false;
     	}
           
        return $return;
     }
}
?>