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
      cs_rubric_form::__construct($params);
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
   	
   	$context_item = $this->_environment->getCurrentContextItem();
   	if($context_item->isServer()){
   		$this->_headline = $this->_translator->getMessage('CONFIGURATION_LOG_DATA');
   	} else {
   		$this->_headline = $this->_translator->getMessage('CONFIGURATION_DATA_SECURITY');
   	}
   	
   	
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

      $this->_form->addTextfield('log_delete_interval','',$translator->getMessage('CONFIGURATION_DELETE_AFTER_DAYS'),'',3,10,true,'','','','','','',false);

      $this->_form->addRadioGroup('log_ip', $translator->getMessage('CONFIGURATION_EXTRA_LOG_IP'),'',$this->_yes_no_array,'','',true,'','',$disabled);

      $this->_form->addTitleText('logdata', $translator->getMessage('CONFIGURATION_LOG_DATA_ROOM_DELETE'));//( 'logdata', $translator->getMessage('CONFIGURATION_LOG_DATA_ROOM_DELETE'), '', '', 4 ,2);
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
      $this->_form->addText('',$translator->getMessage('CONFIGURATION_CHOOSE_LANGUAGE'), $translator->getMessage('COMMON_CONFIGURATION_DELETE_LOG_DESCRIPTION'));
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
      			
      			$log_manager = $this->_environment->getLogManager();
      			$log_archive_manager = $this->_environment->getLogArchiveManager();
      			
      			$log_archive_data = $log_archive_manager->getLogdataByContextID($room->getItemID());
      			$log_data = $log_manager->getLogdataByContextID($room->getItemID());
      			
      			if(!empty($log_data) or !empty($log_archive_data)){
      				// Link für das pseudonymisierte herunterladen
      				$link_export = ahref_curl( $this->_environment->getCurrentContextID(),
      							$this->_environment->getCurrentModule(),
      							'getlogfile',
      							array('id' => $room->getItemID()), '<img src="images/archive_found.png" alt="'.$this->_translator->getMessage('CONFIGURATION_LOG_EXPORT').'">',$this->_translator->getMessage('CONFIGURATION_LOG_EXPORT'));
      				
      				$link_delete = ahref_curl( $this->_environment->getCurrentContextID(),
      							$this->_environment->getCurrentModule(),
      							'datasecurity',
      							array('modus' => 'delete','id' => $room->getItemID()), '<img src="images/delete_restriction.gif" width="14">',$this->_translator->getMessage('CONFIGURATION_LOG_DELETE'));
      			} else {
      				$link_export = '<img src="images/archive.png">'; //.$this->_translator->getMessage('COMMON_CONFIGURATION_EXPORT')
      				$link_delete = '<img src="images/delete_restriction.gif">';
      			}
      			
      			$link_disable_archive = ahref_curl( $this->_environment->getCurrentContextID(),
      							$this->_environment->getCurrentModule(),
      							'datasecurity',
      							array('modus' => 'remove','id' => $room->getItemID()), '<img src="images/less.gif" width="14">',$this->_translator->getMessage('CONFIGURATION_LOG_DEARCHIVE'));
      			
      			
      			
      			$this->_form->addText('Name', 'Label', $room->getTitle().$type.' '.$link_export.' '.$link_delete.' '.$link_disable_archive);
      			#$this->_form->addCheckbox('ROOM_'.$room->getItemID(),$room->getItemID(),'','',$room->getTitle().$type.$link_export.' '.$link_delete,'','',$disabled);
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
        $this->_form->addRadioGroup('hide_accountname',$translator->getMessage('CONFIGURATION_HIDE_USERLOGIN'),'',$this->_yes_no_array,'','',true,'','',$disabled);
        $this->_form->addRadioGroup('default_hide_mail',$translator->getMessage('CONFIGURATION_DATA_SECURITY_HIDE_MAIL_DEFAULT'),'',$this->_yes_no_array,'','',true,'','',$disabled);
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
         	
         	if($current_context->withLogIPCover()){
         		$this->_values['log_ip'] = 1;
         	} else {
         		$this->_values['log_ip'] = 2;
         	}
         	
         	 
         	if( empty($this->_values['log_delete_interval'])){
         		$this->_values['log_delete_interval'] = 50;
         	}
         } else if($current_context->isPortal()){
         	$this->_values['hide_accountname'] = $current_context->getHideAccountname();

         	
         	if( $this->_values['hide_accountname']){
         		$this->_values['hide_accountname'] = 1;
         	} else {
         		$this->_values['hide_accountname'] = 2;
         	}

          $this->_values['default_hide_mail'] = $current_context->getConfigurationHideMailByDefault();

          if($this->_values['default_hide_mail']) {
            $this->_values['default_hide_mail'] = 1;
          } else {
            $this->_values['default_hide_mail'] = 2;
          }
         	
         	
         }
         
   	}
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   	/* sollte automatisch passieren, 16.08.2013 IJ
   	if ( empty($this->_form_post['log_delete_interval']) ) {
   		$this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_FIELD',$translator->getMessage('CONFIGURATION_DELETE_AFTER_DAYS'));
   		$this->_form->setFailure('log_delete_interval');
   	}
   	*/
   	/* _checkValues wird nicht aufgerufen daher greift dies hier nicht, 16.08.2013 IJ
      if ( !empty($this->_form_post['log_delete_interval'])
      	  and !is_numeric($this->_form_post['log_delete_interval'])
      	) {
   		$this->_error_array[] = $this->_translator->getMessage('TBD');
   		$this->_form->setFailure('log_delete_interval');
   	}
      if ( !empty($this->_form_post['log_delete_interval'])
      	  and is_numeric($this->_form_post['log_delete_interval'])
      	  and $this->_form_post['log_delete_interval'] <= 0
      	) {
   		$this->_error_array[] = $this->_translator->getMessage('TBD');
   		$this->_form->setFailure('log_delete_interval');
   	}
   	*/
   }
}
?>
