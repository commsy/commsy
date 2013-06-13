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
class cs_configuration_datasecurity_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_yes_no_array = array();


  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function cs_configuration_authentication_form ($params) {
      $this->cs_rubric_form($params);
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
   	
   	$this->_headline = 'Datenschutz';
   	
   	// portal option choice
   	$this->_array_portal[0]['text']  = '*'.$this->_translator->getMessage('CONFIGURATION_EXTRA_CHOOSE_NO_PORTAL');
   	$this->_array_portal[0]['value'] = -1;
   	
   	$server_item = $this->_environment->getServerItem();
   	$portal_list = $server_item->getPortalList();
   	if ( $portal_list->isNotEmpty() ) {
   		$this->_array_portal[1]['text']  = '----------------------';
   		$this->_array_portal[1]['value'] = 'disabled';
   		$portal_item = $portal_list->getFirst();
   		while ( $portal_item ) {
   			$temp_array = array();
   			$temp_array['text']  = $portal_item->getTitle();
   			$temp_array['value'] = $portal_item->getItemID();
   			$this->_array_portal[] = $temp_array;
   	
   			$portal_item = $portal_list->getNext();
   		}
   	}

    // auth text choice
    $this->_array_auth_source[0]['text']  = '*'.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_CHOOSE_TEXT');
    $this->_array_auth_source[0]['value'] = -1;

    // yes no array
    $this->_yes_no_array[0]['text'] = $this->_translator->getMessage('COMMON_YES');
    $this->_yes_no_array[0]['value'] = 1;
    $this->_yes_no_array[1]['text'] = $this->_translator->getMessage('COMMON_NO');
    $this->_yes_no_array[1]['value'] = 2;
    
    // show checkboxes
    if ( !empty($this->_form_post['portal'])
    and $this->_form_post['portal'] > 99
    ) {
    	$this->_show_checkboxes = $this->_form_post['portal'];
    }


   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $translator = $this->_environment->getTranslationObject();
//       if (isset($this->_form_post['extra']) and $this->_form_post['extra'] != -1) {
      	$disabled = false;
//       } else {
//       	$disabled = true;
//       }
    

      $this->setHeadline($this->_headline);
      
      $context_item = $this->_environment->getCurrentContextItem();
      if($context_item->isServer()){
      $this->_form->addText('Test', 'Log-Daten', '');
      // Zeitraum zur Löschung alter Log Daten
      $this->_form->addTextfield('log_delete_interval','','Löschung nach X Tagen','',3,10,false,'','','','','','',false);
      // Räume für langfristige Archivierung
      // Log-Daten runterladen für bestimmte Räume (pseudonymisiert)
      // Log-Daten für bestimmte Räume löschen
      
// 	  $this->_form->addRadioGroup('password_specialchar',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_SPECIALCHAR'),'',$this->_yes_no_array,'','',true,'','',$disabled);
// 	  $this->_form->addTextfield('password_length','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_LENGTH'),'',1,10,false,'','','','','','',$disabled);
// 	  $this->_form->addEmptyLine();
// 	  //Datenschutz
// 	  $this->_form->addTextfield('password_expiration','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_EXPIRATION'),'',1,10,false,'','','','','','',$disabled);
	  #$this->_form->addRadioGroup('expired_password', 'Intervall Passwortänderung','',$this->_yes_no_array,'','',true,'','',$disabled);
	  $this->_form->addText('logdata', 'Raum Log-Daten löschen', '');
	  
	  $this->_form->addSelect( 'portal',
                               $this->_array_portal,
                               '',
                               $translator->getMessage('COMMON_PORTAL'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               $translator->getMessage('COMMON_CHOOSE_BUTTON'),
                               'option',
                               '',
                               '',
                               '',
                               true);

      // description text
      #$this->_form->addText('description',$translator->getMessage('COMMON_DESCRIPTION'),'');
      $this->_form->addText('','Beschreibung', $translator->getMessage('COMMON_CONFIGURATION_DELETE_LOG_DESCRIPTION'));
      $this->_form->addHidden('description_hidden','');

      // generate checkboxes for rooms
      if ( $this->_show_checkboxes ) {
      	$portal_manager = $this->_environment->getPortalManager();
      	$portal = $portal_manager->getItem($this->_show_checkboxes);
      	unset($portal_manager);
      	$this->_form->addSubHeadline('headline',$portal->getTitle());
      	#$this->_form->addCheckbox('ROOM_'.$portal->getItemID(),$portal->getItemID(),'','',$portal->getTitle().' ('.$translator->getMessage('ROOM_TYPE_PORTAL').')','','',$disabled);
      	$room_list = $portal->getRoomList();
      	if ( !$room_list->isEmpty() ) {
      		$room = $room_list->getFirst();
      		while ($room) {
      			// skip entry if room is grouproom
      			if( $room->isGroupRoom() or !$room->withLogArchive()) {
      				$room = $room_list->getNext();
      				continue;
      			}
      			 
      			$type = '';
      			if ( $room->isProjectRoom() ) {
      				$type = ' ('.$translator->getMessage('ROOM_TYPE_PROJECT').')';
      			} elseif ( $room->isCommunityRoom() ) {
      				$type = ' ('.$translator->getMessage('ROOM_TYPE_COMMUNITY').')';
      			}
      			$this->_form->combine();

      			// Link für das pseudonymisierte herunterladen
      			$link = ahref_curl( $this->_environment->getCurrentContextID(),
      								 $this->_environment->getCurrentModule(), 
      								'getlogfile',
      								array('id' => $room->getItemID()), ' [.csv]');
      			
      			$this->_form->addCheckbox('ROOM_'.$room->getItemID(),$room->getItemID(),'','',$room->getTitle().$type.$link,'','',$disabled);
      			unset($type);
      			unset($room);
      			$room = $room_list->getNext();
      		}
      		unset($room_list);
      	}
      	unset($portal);
      }
      } else if($context_item->isPortal()){
      	#############################
        // Datenschutz Portal Ebene
        #############################
        $this->_form->addRadioGroup('hide_accountname','Verstecke Kennungen','',$this->_yes_no_array,'','',true,'','',$disabled);
      }

      #$this->_form->addEmptyLine();
      ###############
      # Log Daten
      ###############
      

      // buttons
      $this->_form->addButtonBar('option',$translator->getMessage('PREFERENCES_SAVE_BUTTON'),'','','','','','');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;

      } elseif ( !empty($this->_item) ) {
         
         
         $current_context = $this->_environment->getCurrentContextItem();
         
         if($current_context->isServer()){
         	$this->_values['log_delete_interval'] = $current_context->getLogDeleteInterval();
         	 
         	if( empty($this->_values['log_delete_interval'])){
         		$this->_values['log_delete_interval'] = 1;
         	}
         } else if($current_context->isPortal()){
         	$this->_values['hide_accountname'] = $current_context->getHideAccountname();
         	
         	if( empty($this->_values['hide_accountname'])){
         		$this->_values['hide_accountname'] = 2;
         	}
         }
         
   	}
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      // check choosen auth source
      
  
   }
}
?>