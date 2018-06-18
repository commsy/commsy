<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2013 Dr. Iver Jackewitz
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

/** class for CommSy forms
 * this class implements an interface for the creation of forms in the CommSy style
 */
class cs_configuration_connection_form extends cs_rubric_form {

	private $_yesno_array = array();
	private $_iid = NULL;
	private $_headline = '';
	private $_own_key;
	private $_connection_array = array();
	private $_modus = '';
	
   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   public function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
   	
   	$this->_headline = $this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_TITLE_FORM');
   	
      if ( isset($this->_item) ) {
         $this->_iid = $this->_item->getItemID();
      } elseif (isset($this->_form_post['iid'])) {
         $this->_iid = $this->_form_post['iid'];
      } else {
         $this->_iid = 'NEW';
      }

      $yesno_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_YES');
      $temp_array['value'] = CS_YES;
      $yesno_array[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('COMMON_NO');
      $temp_array['value'] = CS_NO;
      $yesno_array[] = $temp_array;
      $this->_yesno_array = $yesno_array;
      
      // saved connections
      if ( !empty($this->_item) ) {
      	$connection_array = $this->_item->getServerConnectionArray();
      	if ( !empty($connection_array) ) {
      		$this->_connection_array = $connection_array;
      	}
      }
      
      // edit
      if ( !empty($_GET['modus'])
      		and $_GET['modus'] == 'edit'
      		and !empty($_GET['id'])
         ) {
      	$this->_modus = 'edit';
      }

  }  // End of function _initForm

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
   	
   	// headline
   	$this->setHeadline($this->_headline);

      // hidden
      $this->_form->addHidden('iid',$this->_iid);

      // own server connection key
      $this->_form->addTextfield('own_key','',$this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_OWN_KEY'),'');
      
      // saved connections
      if ( !empty($this->_connection_array) ) {
         $this->_form->addEmptyline();
         $first = true;
      	foreach ( $this->_connection_array as $key => $value_array ) {
      		if ( $first ) {
      			$first = false;
      		} else {
      			$this->_form->combine();
      		}
      	   $link_edit = ahref_curl( $this->_environment->getCurrentContextID(),
      							          $this->_environment->getCurrentModule(),
      							          $this->_environment->getCurrentFunction(),
      							          array('modus' => 'edit','id' => $key), '<img src="images/commsyicons/12x12/edit.png" width="14">',$this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_EDIT_CONNECTION'));
      		
      		$link_delete = ahref_curl( $this->_environment->getCurrentContextID(),
      							            $this->_environment->getCurrentModule(),
      							            $this->_environment->getCurrentFunction(),
      							            array('modus' => 'delete','id' => $key), '<img src="images/delete_restriction.gif" width="14">',$this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_DELETE_CONNECTION'));
      		
      	   $text = $link_edit.' '.$value_array['url'].' '.$link_delete;
      	   $this->_form->addText('saved_connection', $this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_SAVED_CONNECTION'), $text);
      	}
      }
      
      // new server connection
      $this->_form->addEmptyline();
      $this->_form->addText('new_connection',$this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_NEW_CONNECTION'),'');
      if ( !empty($this->_modus)
      	  and $this->_modus == 'edit'
         ) {
      	$this->_form->addHidden('id',$_GET['id']);
      }
      $this->_form->addTextfield('new_title','',$this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_NEW_TITLE'),'');
      $this->_form->addTextfield('new_url','',$this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_NEW_URL'),'',255,50,false,'','','','left','','',false,'/commsy.php');
      $this->_form->addTextfield('new_key','',$this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_NEW_KEY'),'');

      global $symfonyContainer;
      $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
      $c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');

      if ($c_proxy_ip && $c_proxy_port) {
         $this->_form->addRadioGroup('new_proxy',$this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_NEW_PROXY'),'',$this->_yesno_array,'',false,true);
      } else {
      	$this->_form->addHidden('new_proxy',-1); // no proxy
      }
      
      // buttons
      $this->_form->addButtonBar( 'option',
                                  $this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),
                                  $this->_translator->getMessage('COMMON_CANCEL_BUTTON')
                                 );
   } // End of function _createForm

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();

      if ( isset($this->_form_post) ) {
         $this->_values = $this->_form_post;
      } elseif (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['own_key'] = $this->_item->getOwnConnectionKey();
      }
      
      // edit
      if ( !empty($_GET['modus'])
      	  and $_GET['modus'] == 'edit'
      	  and !empty($_GET['id'])
      	  and !empty($this->_item)
      	) {
      	$connection_array = $this->_item->getServerConnectionArray();
      	if ( !empty($connection_array[$_GET['id']]) ) {
      		$this->_values['id'] = $_GET['id'];
      		$this->_values['new_title'] = $connection_array[$_GET['id']]['title'];
      		$this->_values['new_url'] = $connection_array[$_GET['id']]['url'];
      		$this->_values['new_key'] = $connection_array[$_GET['id']]['key'];
      		$this->_values['new_proxy'] = $connection_array[$_GET['id']]['proxy'];
      	}
      }
      
   } // End of function _prepareValues

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   	if ( empty($this->_values['new_title'])
   		  and empty($this->_values['new_url'])
   		  and empty($this->_values['new_key'])
   		  and empty($this->_values['new_proxy'])
   		) {
   		// do nothing
   	} elseif ( !empty($this->_values['new_title'])
   			     and !empty($this->_values['new_url'])
   			     and !empty($this->_values['new_key'])
   			     and !empty($this->_values['new_proxy'])
   	   ) {
   		// test connection
   		$connection_obj = $this->_environment->getCommSyConnectionObject();
   		if ( !$connection_obj->testConnection($this->_values['new_url'],$this->_values['new_key'],$this->_values['new_proxy']) ) {
   			$this->_error_array = array_merge($this->_error_array,$connection_obj->getErrorArray());
   			$this->_form->setFailure('new_url','');
   		}
   	} else {
   		if ( empty($this->_values['new_title']) ) {
   			$this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_SELECT', $this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_NEW_TITLE'));
   		   $this->_form->setFailure('new_title','');
   		}
   		if ( empty($this->_values['new_url']) ) {
   			$this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_SELECT', $this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_NEW_URL'));
   			$this->_form->setFailure('new_url','');
   		}
   	   if ( empty($this->_values['new_key']) ) {
   			$this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_SELECT', $this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_NEW_KEY'));
   	   	$this->_form->setFailure('new_key','');
   		}
   	   if ( empty($this->_values['new_proxy']) ) {
   			$this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_SELECT', $this->_translator->getMessage('SERVER_CONFIGURATION_CONNECTION_NEW_PROXY'));
   	   	$this->_form->setFailure('new_proxy','');
   		}
   	}
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '';
      return $retour;
   }
}
?>