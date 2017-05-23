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

// Get the translator object
$translator = $environment->getTranslationObject();

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();

// Check access rights
if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $room_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( !$room_item->isOpen() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif (!$current_user->isModerator() || !$environment->inPortal()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
}
// Access granted
else {

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } elseif (isset($_POST['mail_text']) ) {
      $command = $translator->getMessage('COMMON_CHOOSE_BUTTON');
   } elseif (isset($_POST['option_additional_server']) ) {
      $command = $_POST['option_additional_server'];
   } else {
      $command = '';
   }
    
   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      redirect( $environment->getCurrentContextID(),
                $environment->getCurrentModule(),
                $environment->getCurrentFunction(),
                '' );
   }
   
   // Show form and/or save item
   else {
      // Initialize the form
      $form = $class_factory->getClass(CONFIGURATION_AUTHENTICATION_FORM,array('environment' => $environment));

      // additional server
      if ( isOption($command, $translator->getMessage('CONFIGURATION_AUTHENTICATION_ADDITIONAL_SERVER')) ) {
         $form->setAddOneAdditionalServer();
      }
      
      // Display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
      unset($params);

      if ( ( isOption($command, $translator->getMessage('COMMON_CHOOSE_BUTTON'))
             and $_POST['auth_source'] != 'disabled'
             and $_POST['auth_source'] != 'new'
             and $_POST['auth_source'] > 100
           )
           or
           ( isset($_POST['auth_source'])
             and $_POST['auth_source'] != 'new'
             and $_POST['auth_source'] != 'disabled'
             and $_POST['auth_source'] > 100
             and !isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
             and !isOption($command, $translator->getMessage('CONFIGURATION_AUTHENTICATION_ADDITIONAL_SERVER'))
           )
         ) {
         $auth_source_item = $room_item->getAuthSource($_POST['auth_source']);
         $form->setItem($auth_source_item);
      }

      // Load form data from postvars
      elseif ( isset($_POST['auth_source'])
               and $_POST['auth_source'] == 'new'
               and ( !isset($_POST['auth_type'])
                     or $_POST['auth_type'] == -1
                   )
             ) {
      	$temp_values = array();
         $temp_values['auth_source'] = $_POST['auth_source'];
         $form->setFormPost($temp_values);
      } elseif ( !empty($_POST) and !empty($values) and $_POST['auth_source'] != 'new') {
      	$temp_values = $_POST;
         $temp_values['text'] = $values;
         $form->setFormPost($temp_values);
      } elseif ( !empty($_POST['auth_source']) and $_POST['auth_source'] == -1) {
      	if ( !empty($command)
              and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
      		) {
      		$form->setFormPost($_POST);
      	}
      } elseif ( !empty($_POST) ) {
      	$form->setFormPost($_POST);
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command)
           and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
         ) {

         $correct = $form->check();
         if ( $correct and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {

            if (isset( $_POST['auth_source']) and $_POST['auth_source'] == 'new' ) {
               // TBD
            } elseif ( isset($_POST['auth_source']) and $_POST['auth_source'] > 100 ) {
               $authItem = $room_item->getAuthSource($_POST['auth_source']);
            } elseif ($_POST['auth_source'] == -1) {
            	
            } else {
               include_once('functions/error_functions.php');
               trigger_error('id of auth source lost',E_USER_WARNING);
            }
            if ( !isset($authItem) and $_POST['auth_source'] != -1) {
               $auth_source_manager = $environment->getAuthSourceManager();
               $authItem = $auth_source_manager->getNewItem();
               $authItem->setContextID($environment->getCurrentContextID());
            }
            
			   if($_POST['auth_source'] != -1){
				   $authItem->setTitle($_POST['title']);
			
	            if ( $_POST['changeUserID'] == 1 ) {
	               $authItem->setAllowChangeUserID();
	            } elseif ( $_POST['changeUserID'] == 2 ) {
	               $authItem->unsetAllowChangeUserID();
	            }
	            /*
	            if ( $_POST['changeUserData'] == 1 ) {
	               $authItem->setAllowChangeUserData();
	            } elseif ( $_POST['changeUserData'] == 2 ) {
	               $authItem->unsetAllowChangeUserData();
	            }
	            */
	            if ( $_POST['changePassword'] == 1 ) {
	               $authItem->setAllowChangePassword();
	            } elseif ( $_POST['changePassword'] == 2 ) {
	               $authItem->unsetAllowChangePassword();
	            }
	            if ( $_POST['addAccount'] == 1 ) {
	               $authItem->setAllowAddAccount();
                   $authItem->unsetAllowAddAccountInvitation();
	            } elseif ( $_POST['addAccount'] == 2 ) {
	               $authItem->unsetAllowAddAccount();
                   $authItem->unsetAllowAddAccountInvitation();
	            } elseif ( $_POST['addAccount'] == 3 ) {
                   $authItem->unsetAllowAddAccount();
                   $authItem->setAllowAddAccountInvitation();
                }
	            if ( $_POST['deleteAccount'] == 1 ) {
	               $authItem->setAllowDeleteAccount();
	            } elseif ( $_POST['deleteAccount'] == 2 ) {
	               $authItem->unsetAllowDeleteAccount();
	            }
	            if ( $_POST['show'] == 1 ) {
	               $authItem->setShow();
	            } elseif ( $_POST['show'] == 2 ) {
	               $authItem->unsetShow();
	            }
	            if ( isset($_POST['auth_type'])
	                 and !empty($_POST['auth_type']) ) {
	               $authItem->setSourceType($_POST['auth_type']);
	            }
	            if ( isset($_POST['change_password_url']) ) {
	               $authItem->setPasswordChangeLink($_POST['change_password_url']);
	            }
	            if ( isset($_POST['contact_mail']) ) {
	               $authItem->setContactEMail($_POST['contact_mail']);
	            }
	            if ( isset($_POST['contact_fon']) ) {
	               $authItem->setContactFon($_POST['contact_fon']);
	            }
	            if ( isset($_POST['password_secure_check']) ) {
	               $authItem->setPasswordSecureCheck($_POST['password_secure_check']);
	            }
	            if ( isset($_POST['password_bigchar']) ) {
	               $authItem->setPasswordSecureBigchar($_POST['password_bigchar']);
	            }
	            if ( isset($_POST['password_specialchar']) ) {
	               $authItem->setPasswordSecureSpecialchar($_POST['password_specialchar']);
	            }
	            if ( isset($_POST['password_length'])) {
	            	if($_POST['password_length'] >= 0){
	            		$password_length = preg_replace('/[^0-9]+/', '', $_POST['password_length']);
	            		if(empty($password_length) and $password_length != 0){
	            			$params = array();
	            			$params['environment'] = $environment;
	            			$params['with_modifying_actions'] = true;
	            			$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
	            			$errorbox->setText($translator->getMessage('ERROR_VALUE_PASSWORD_LENGTH'));
	            			$page->add($errorbox);
	            			$password_length = 0;
	            		}
	            		$authItem->setPasswordLength($password_length);
	            	}
	            }
	            
	            if ( isset($_POST['password_smallchar']) ) {
	            	$authItem->setPasswordSecureSmallchar($_POST['password_smallchar']);
	            }
	            if ( isset($_POST['password_number']) ) {
	            	$authItem->setPasswordSecureNumber($_POST['password_number']);
	            }
                if ( isset($_POST['email_regex']) ) {
                    $authItem->setEmailRegex($_POST['email_regex']);
                }
                if ( isset($_POST['user_is_allowed_to_create_context']) ) {
	            	if ($_POST['user_is_allowed_to_create_context'] == 1) {
                        $authItem->setUserIsAllowedToCreateContext(1);
					} else {
                        $authItem->setUserIsAllowedToCreateContext(-1);
					}
				}
			   }
			   
			   if ( $_POST['auth_source'] != -1
			   	  and isset($authItem)
			      ) {
			   	$authItem->save();
			   }
			   
            //Datenschutz
            $portal_item = $environment->getCurrentPortalItem();
            
         	if ( isset($_POST['temporary_lock']) ) {
         		$temporary_lock = $_POST['temporary_lock'];
         		$temporary_lock = preg_replace('/[^0-9]+/', '', $temporary_lock);
         		 
         		if($temporary_lock >= 0 and !empty($temporary_lock)){
         			$portal_item->setTemporaryLock($temporary_lock);
         		} else {
         			$params = array();
         			$params['environment'] = $environment;
         			$params['with_modifying_actions'] = true;
         			$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         			$errorbox->setText('TEMPORARY LOCK');
         			$page->add($errorbox);
         			$password_length = 0;
         		}
               #$portal_item->setTemporaryLock($_POST['temporary_lock']);
            }
            
            if ( isset($_POST['try_until_lock'])) {
            	$empty_flag = false;
            	if(empty($_POST['try_until_lock'])){
            		$empty_flag = true;
            	}
            	$try_until_lock = $_POST['try_until_lock'];
            	$try_until_lock = preg_replace('/[^0-9]+/', '', $try_until_lock);
            	
            	if($try_until_lock >= 0 or !empty($try_until_lock)){
            		$portal_item->setTryUntilLock($_POST['try_until_lock']);
            	} else {
            		$params = array();
            		$params['environment'] = $environment;
            		$params['with_modifying_actions'] = true;
            		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            		$errorbox->setText($translator->getMessage('ERROR_VALUE_TRY_UNTIL_LOCK'));
            		$page->add($errorbox);
            		$password_length = 0;
            	}
            	#$portal_item->setTryUntilLock($_POST['try_until_lock']);
            }
            
            if( isset($_POST['seconds_interval'])) {
            	$empty_flag = false;
            	if(empty($_POST['seconds_interval'])){
            		$empty_flag = true;
            	}
            	$seconds_interval = $_POST['seconds_interval'];
            	$seconds_interval = preg_replace('/[^0-9]+/', '', $seconds_interval);
            	
            	if($seconds_interval >= 0 or $empty_flag){
            		$portal_item->setLockTimeInterval($_POST['seconds_interval']);
            	} else {
            		$params = array();
            		$params['environment'] = $environment;
            		$params['with_modifying_actions'] = true;
            		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            		$errorbox->setText($translator->getMessage('ERROR_VALUE_SECONDS_INTERVAL'));
            		$page->add($errorbox);
            		$password_length = 0;
            	}
            	#$portal_item->setLockTimeInterval($_POST['seconds_interval']);
            }
            
            if( isset($_POST['temporary_minutes'])) {
            	$empty_flag = false;
            	if(empty($_POST['temporary_minutes'])){
            		$empty_flag = true;
            	}
            	$temporary_minutes = $_POST['temporary_minutes'];
            	$temporary_minutes = preg_replace('/[^0-9]+/', '', $temporary_minutes);
            	
            	if($temporary_minutes >= 0 or $empty_flag){
            		$portal_item->setLockTime($_POST['temporary_minutes']);
            	} else {
            		$params = array();
            		$params['environment'] = $environment;
            		$params['with_modifying_actions'] = true;
            		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            		$errorbox->setText($translator->getMessage('ERROR_VALUE_TEMPORARY_MINUTES'));
            		$page->add($errorbox);
            		$password_length = 0;
            	}
            }
            
            if( isset($_POST['password_generation'])) {
            	$generation = $_POST['password_generation'];
            	$empty_flag = false;
            	if(empty($_POST['password_generation'])){
            		$empty_flag = true;
            	}
            	
            	$generation = preg_replace('/[^0-9]+/', '', $_POST['password_generation']);
            	if(empty($generation) and !$empty_flag){
            		$params = array();
            		$params['environment'] = $environment;
            		$params['with_modifying_actions'] = true;
            		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            		$errorbox->setText($translator->getMessage('ERROR_VALUE_PASSWORD_GENERATION'));
            		$page->add($errorbox);
            	} else {
            		$portal_item->setPasswordGeneration($generation);
            		
            	}
            }
            if( isset($_POST['password_expiration'])) {
            	if(true)
            	{
            		$empty_flag = false;
            		if(empty($_POST['password_expiration'])){
            			$empty_flag = true;
            		}
            		$password_expiration = preg_replace('/[^0-9]+/', '', $_POST['password_expiration']);
            		#pr($password_expiration);breaK;
            		if(empty($password_expiration) and !$empty_flag){
            			$params = array();
            			$params['environment'] = $environment;
            			$params['with_modifying_actions'] = true;
            			$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            			$errorbox->setText($translator->getMessage('ERROR_VALUE_PASSWORD_EXPIRE'));
            			$page->add($errorbox);
            		} else {
            			if($password_expiration == 0 or empty($password_expiration)){
            				$portal_item->setPasswordExpiration(0);
            				 
            				$portal_users = $portal_item->getUserList();
            				$portal_user = $portal_users->getFirst();
            				while ($portal_user){
            					$portal_user->setPasswordExpireDate('NULL');
            					$portal_user->save();
            					 
            					$portal_user = $portal_users->getNext();
            				}
            			} else {
            				$portal_item->setPasswordExpiration($password_expiration);
            				$portal_item->save();
            				 
            				// set a new expire date for all portal users
            				// Datenschutz
            				$portal_users = $portal_item->getUserList();
            				$portal_user = $portal_users->getFirst();
            				while ($portal_user){
            					if ($_POST['password_expiration'] > 0){
            						$auth_source_manager = $environment->getAuthSourceManager();
            						$authItem = $auth_source_manager->getItem($portal_user->getAuthSource());
            						if($authItem->getSourceType() == 'MYSQL'){
            							$portal_user->setPasswordExpireDate($portal_item->getPasswordExpiration());
            						}
            					} else {
            						$portal_user->unsetPasswordExpireDate();
            					}
            					$portal_user->save();
            					 
            					$portal_user = $portal_users->getNext();
            				}
            			}
            		}
            	}

            }
            if( isset($_POST['days_before_expiring_sendmail'])){
            	if(true){
            		$empty_flag = false;
            		if(empty($_POST['days_before_expiring_sendmail'])){
            			$empty_flag = true;
            		}
            		$days_before_expiring_sendmail = preg_replace('/[^0-9]+/', '', $_POST['days_before_expiring_sendmail']);
            		if(empty($days_before_expiring_sendmail) and !$empty_flag){
            			$params = array();
            			$params['environment'] = $environment;
            			$params['with_modifying_actions'] = true;
            			$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            			$errorbox->setText($translator->getMessage('ERROR_VALUE_PASSWORD_EXPIRE_MAIL'));
            			$page->add($errorbox);
            		} else {
            			$portal_item->setDaysBeforeExpiringPasswordSendMail($days_before_expiring_sendmail);
            		}
            	}
            }
            
            // Shibboleth
            if ( isset($_POST['direct_login']) ) {
            	$authItem->setShibbolethDirectLogin($_POST['direct_login']);
            }
            
            if ( isset($_POST['session_initiator_url']) ) {
            	$authItem->setShibbolethSessionInitiator($_POST['session_initiator_url']);
            }
            
            if ( isset($_POST['session_logout_url']) ) {
            	$authItem->setShibbolethSessionLogout($_POST['session_logout_url']);
            }
            
            if ( isset($_POST['password_change_url']) ) {
            	$authItem->setShibbolethPasswordChange($_POST['password_change_url']);
            }
            
            if ( isset($_POST['username']) ) {
            	$authItem->setShibbolethUsername($_POST['username']);
            }
            
            if ( isset($_POST['firstname']) ) {
            	$authItem->setShibbolethFirstname($_POST['firstname']);
            }
            
            if ( isset($_POST['lastname']) ) {
            	$authItem->setShibbolethLastname($_POST['lastname']);
            }
            
            if ( isset($_POST['email']) ) {
            	$authItem->setShibbolethEmail($_POST['email']);
            }
            
            if ( isset($_POST['update_user_data']) ) {
            	$authItem->setShibbolethUpdateData($_POST['update_user_data']);
            }
            
            
            $portal_item->save();
            unset($portal_item);

            // special data
            $auth_data_array = array();
            if ( isset($_POST['host'])
                 and !empty($_POST['host']) ) {
               $auth_data_array['HOST'] = $_POST['host'];
            }
            if ( isset($_POST['path'])
                 and !empty($_POST['path']) ) {
               $auth_data_array['PATH'] = $_POST['path'];
            }
            if ( isset($_POST['dbname'])
                 and !empty($_POST['dbname']) ) {
               $auth_data_array['DBNAME'] = $_POST['dbname'];
            }
            if ( isset($_POST['dbtable'])
                 and !empty($_POST['dbtable']) ) {
                $auth_data_array['DBTABLE'] = $_POST['dbtable'];
            }
            if ( isset($_POST['dbcolumnuserid'])
                 and !empty($_POST['dbcolumnuserid']) ) {
               $auth_data_array['DBCOLUMNUSERID'] = $_POST['dbcolumnuserid'];
            }
            if ( isset($_POST['dbsearchuserid'])
                 and !empty($_POST['dbsearchuserid']) ) {
               $auth_data_array['DBSEARCHUSERID'] = $_POST['dbsearchuserid'];
            }
            if ( isset($_POST['dbcolumnpasswd'])
                 and !empty($_POST['dbcolumnpasswd']) ) {
                $auth_data_array['DBCOLUMNPASSWD'] = $_POST['dbcolumnpasswd'];
            }
            if ( isset($_POST['port'])
                 and !empty($_POST['port']) ) {
               $auth_data_array['PORT'] = $_POST['port'];
            }
            if ( isset($_POST['userid'])
                 and !empty($_POST['userid']) ) {
               $auth_data_array['USER'] = $_POST['userid'];
            }
            if ( isset($_POST['password'])
                 and !empty($_POST['password']) ) {
               $auth_data_array['PASSWORD'] = $_POST['password'];
            }

            if ( isset($_POST['encryption'])
                 and !empty($_POST['encryption']) ) {
               $auth_data_array['ENCRYPTION'] = $_POST['encryption'];
            }

            if( isset($_POST['base']) and !empty($_POST['base'])) {
               $auth_data_array['BASE'] = $_POST['base'];
            }
            
            // additional server
            if ( !empty($_POST['additional_server_count']) ) {
            	if ( !isset($auth_data_array['additional_server']) ) {
            		$auth_data_array['additional_server'] = array();
            	}
            	for ( $count = 1; $count <=  $_POST['additional_server_count']; $count++ ) {
            		if ( !empty($_POST['host'.$count])
            		     and !empty($_POST['port'.$count])
            			) {
            			$temp_array = array();
            			$temp_array['host'] = $_POST['host'.$count];
            			$temp_array['port'] = $_POST['port'.$count];
            			$auth_data_array['additional_server'][] = $temp_array;
            			unset($temp_array);
            		}
            	}
            	if ( $_POST['additional_server_count'] != count($auth_data_array['additional_server']) ) {
            		$_POST['additional_server_count'] = count($auth_data_array['additional_server']);
            	   
            		// form re-new
            	   $form->reset();
            	   $form->setFormPost($_POST);
            	   $form->prepareForm();
            	   $form->loadValues();
            	}
            }
            
            if ( empty($auth_data_array['additional_server']) ) {
            	unset($auth_data_array['additional_server']);
            }
            
            if ( !empty($_POST['host_new'])
            		and !empty($_POST['port_new'])
               ) {
            	$temp_array = array();
            	$temp_array['host'] = $_POST['host_new'];
            	$temp_array['port'] = $_POST['port_new'];
            	if ( !isset($auth_data_array['additional_server']) ) {
            	   $auth_data_array['additional_server'] = array();
            	}
            	$auth_data_array['additional_server'][] = $temp_array;
            	unset($temp_array);
            	
            	if ( !empty($_POST['additional_server_count']) ) {
            		$_POST['additional_server_count']++;
            	} else {
            		$_POST['additional_server_count'] = 1;
            	}
            	$_POST['host'.$_POST['additional_server_count']] = $_POST['host_new'];
            	$_POST['port'.$_POST['additional_server_count']] = $_POST['port_new'];
            	
            	// form re-new
            	$form->reset();
            	$form->setFormPost($_POST);
            	$form->prepareForm();
            	$form->loadValues();
            }
            
            if ( !empty($_POST['select_server'])
            	  and !empty($auth_data_array['additional_server'])
            	  and count($auth_data_array['additional_server']) > 0
            	) {
            	$auth_data_array['select_server'] = $_POST['select_server'];
            }
            // additional server - END

            if ( !empty($auth_data_array) ) {
               $authItem->setAuthData($auth_data_array);
            }
            
            if ( $_POST['auth_source'] != -1
                 and isset($authItem)
               ) {
            	$authItem->save();
            } 

            if ( isset($_POST['default']) and $_POST['default'] == 1 ) {
               if ( $room_item->getAuthDefault() != $_POST['auth_source']
                    and $_POST['auth_source'] != 'new'
                    and $_POST['auth_source'] != 'disabled'
                    and $_POST['auth_source'] > 100
                  ) {
                  $room_item->setAuthDefault($_POST['auth_source']);
                  $room_item->save();
               }
            }

            if ( isset($_POST['ims']) and $_POST['ims'] == 1 ) {
               if ( $room_item->getAuthIMS() != $_POST['auth_source']
                    and $_POST['auth_source'] != 'new'
                    and $_POST['auth_source'] != 'disabled'
                    and $_POST['auth_source'] > 100
                  ) {
                  $room_item->setAuthIMS($_POST['auth_source']);
                  $room_item->save();
               }
            }
            $form_view->setItemIsSaved();
         }
      }

      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
      $form_view->setForm($form);
      if ( $environment->inPortal() or $environment->inServer() ){
         $page->addForm($form_view);
      } else {
         $page->add($form_view);
      }
   }
}
?>
