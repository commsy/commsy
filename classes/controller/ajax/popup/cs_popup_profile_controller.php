<?php
require_once('classes/controller/ajax/popup/cs_popup_controller.php');

class cs_popup_profile_controller implements cs_popup_controller {
	private $_environment = null;
	private $_popup_controller = null;
	private $_user = null;
	private $_config = array();
	private $_data = array();

	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}

	public function save($form_data, $additional = array())
	{
		$current_context = $this->_environment->getCurrentContextItem();
		$current_portal_item = $this->_environment->getCurrentPortalItem();
		
		// check context
		if(!$current_context->isOpen()) {
			// TODO:
			// 			$params = array();
			// 			$params['environment'] = $environment;
			// 			$params['with_modifying_actions'] = true;
			// 			$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
			// 			unset($params);
			// 			$error_string = $translator->getMessage('PROJECT_ROOM_IS_CLOSED',$context_item->getTitle());
			// 			$errorbox->setText($error_string);
			// 			$page->add($errorbox);
			// 			$command = 'error';
		}

		// access granted
		else {
			// 			// include form
			// 			$class_params= array();
			// 			$class_params['environment'] = $environment;
			// 			$form = $class_factory->getClass(PROFILE_FORM,$class_params);
			// 			unset($class_params);

			// 			if ( isset($error_message_for_profile_form)
			// 					and !empty($error_message_for_profile_form)
			// 			) {
			// 				$form->setFailure('email','',$error_message_for_profile_form);
			// 			}

			// 			$form->setProfilePageName($profile_page);

			// 			$current_portal_item = $environment->getCurrentPortalItem();

			// 			// cancel edit process
			// 			if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
			// 				$params = $environment->getCurrentParameterArray();
			// 				redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
			// 			}

			// 			// lock user (room)
			// 			elseif ( isOption($command, $translator->getMessage('PREFERENCES_LOCK_BUTTON_ROOM')) ) {
			// 				$user_item->reject();
			// 				$user_item->save();
			// 				unset($user_item);
			// 				redirect($current_portal_item->getItemID(), 'home','index', array('room_id' => $environment->getCurrentContextID()));
			// 			}

			// 			// delte user (room)
			// 			elseif ( isOption($command, $translator->getMessage('PREFERENCES_REALLY_DELETE_BUTTON_ROOM')) ) {
			// 				$user_item->delete();
			// 				unset($user_item);
			// 				redirect($current_portal_item->getItemID(), 'home','index', array('room_id' => $environment->getCurrentContextID()));
			// 			}

			// 			// lock user (portal)
			// 			elseif ( isOption($command, $translator->getMessage('PREFERENCES_LOCK_BUTTON',$current_portal_item->getTitle())) ) {
			// 				$portalUser_item = $user_item->getRelatedCommSyUserItem();
			// 				$portalUser_item->reject();
			// 				$portalUser_item->save();
			// 				$session = $environment->getSessionItem();
			// 				$session_manager = $environment->getSessionManager();
			// 				$session_manager->delete($session->getSessionID());
			// 				unset($session);
			// 				unset($session_manager);
			// 				unset($portalUser_item);
			// 				unset($user_item);
			// 				$environment->setSessionItem(NULL);
			// 				redirect($environment->getCurrentPortalID(), 'home','index', array());
			// 			}

			// 			// delete user (portal)
			// 			elseif ( isOption($command, $translator->getMessage('PREFERENCES_REALLY_DELETE_BUTTON',$current_portal_item->getTitle())) ) {
			// 				$authentication = $environment->getAuthenticationObject();
			// 				$authentication->delete($user_item->getItemID());
			// 				unset($authentication);
			// 				$session = $environment->getSessionItem();
			// 				$session_manager = $environment->getSessionManager();
			// 				$session_manager->delete($session->getSessionID());
			// 				unset($session);
			// 				unset($session_manager);
			// 				$environment->setSessionItem(NULL);
			// 				redirect($environment->getCurrentPortalID(), 'home','index', array());
			// 			}
			if(false) {

			}

			// save user
			else {
				$tab = $additional['part'];

				switch($tab) {
					/**** ACCOUNT ****/
					case 'account_merge':
						if($this->_popup_controller->checkFormData('merge')) {
							$authentication = $this->_environment->getAuthenticationObject();
							$current_user = $this->_environment->getCurrentUserItem();

							if(isset($form_data['auth_source'])) $auth_source_old = $form_data['auth_source'];
							else $auth_source_old = $current_portal_item->getAuthDefault();

							$authentication->mergeAccount($current_user->getUserID(), $current_user->getAuthSource(), $form_data['merge_user_id'], $auth_source_old);

							// set return
							$this->_popup_controller->setSuccessfullItemIDReturn($current_user->getItemID());
						}
						break;

					case "account_lock_room":
						$current_user = $this->_environment->getCurrentUserItem();

						$current_user->reject();
						$current_user->save();

						// set return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_user->getItemID());
						break;

					case "account_delete_room":
						$current_user = $this->_environment->getCurrentUserItem();

						$current_user->delete();

						// set return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_user->getItemID());
						break;

					case "account_lock_portal":
						$current_user = $this->_environment->getCurrentUserItem();
						$portalUser_item = $current_user->getRelatedCommSyUserItem();

						$portalUser_item->reject();
						$portalUser_item->save();

						// delete session
						$session_manager = $this->_environment->getSessionManager();
						$session = $this->_environment->getSessionItem();
						$session_manager->delete($session->getSessionID());
						$this->_environment->setSessionItem(null);
						break;

					case "account_delete_portal":
						$current_user = $this->_environment->getCurrentUserItem();
						$portalUser_item = $current_user->getRelatedCommSyUserItem();
						$authentication = $this->_environment->getAuthenticationObject();
						$authentication->delete($portalUser_item->getItemID());

						// delete session
						$session_manager = $this->_environment->getSessionManager();
						$session = $this->_environment->getSessionItem();
						$session_manager->delete($session->getSessionID());
						$this->_environment->setSessionItem(null);
						break;

					case 'account':
						if($this->_popup_controller->checkFormData('account')) {
							$authentication = $this->_environment->getAuthenticationObject();
							
							$currentUser = $this->_environment->getCurrentUserItem();
							
							// password
							if(!empty($form_data['new_password'])) {
								$auth_manager = $authentication->getAuthManager($currentUser->getAuthSource());
								$auth_manager->changePassword($form_data['user_id'], $form_data['new_password']);
								$error_number = $auth_manager->getErrorNumber();

								if(!empty($error_number)) {
									// TODO:$error_string .= $translator->getMessage('COMMON_ERROR_DATABASE').$error_number.'<br />';
								}
							}
							
							// get portal user if in room context
							if ( !$this->_environment->inPortal() )
							{
								$portalUser = $this->_environment->getPortalUserItem();
							}
							else
							{
								$portalUser = $this->_environment->getCurrentUserItem();
							}
							
							// user id
							if(!empty($form_data['user_id']) && $form_data['user_id'] != $portalUser->getUserID()) {
								
								$check = true;
								$auth_source = $portalUser->getAuthSource();
								if ( !empty($auth_source) ) {
									$authentication = $this->_environment->getAuthenticationObject();
									
									if ( !$authentication->is_free($form_data['user_id'], $auth_source) ) {
										$this->_popup_controller->setErrorReturn("1011", "user id error(duplicated)", array());
										$check = false;
									} elseif ( withUmlaut($form_data['user_id']) ) {
										$this->_popup_controller->setErrorReturn("1012", "user id error(umlaut)", array());
										$check = false;
									}
								} else {
									$this->_popup_controller->setErrorReturn("1013", "user id error(auth source error)", array());
									$check = false;
								}
								
								if ($check === true) {
									if($authentication->changeUserID($form_data['user_id'], $portalUser)) {
										$session_manager = $this->_environment->getSessionManager();
										$session = $this->_environment->getSessionItem();
									
										$session_id_old = $session->getSessionID();
										$session_manager->delete($session_id_old, true);
										$session->createSessionID($form_data['user_id']);
									
										$cookie = $session->getValue('cookie');
										if($cookie == 1) $session->setValue('cookie', 2);
									
										$session_manager->save($session);
										unset($session_manager);
									
										$portalUser->setUserID($form_data['user_id']);
										require_once('functions/misc_functions.php');
										plugin_hook('user_save', $portalUser);
									}
								} else {
									$this->_popup_controller->setErrorReturn("117", "user id error(duplicated, umlaut, etc)", array());
								}
							} else {
								// $success_1 = true
							}

							$save = false;

							// language
							if(!empty($form_data['language']) && $form_data['language'] != $portalUser->getLanguage()) {
								$portalUser->setLanguage($form_data['language']);
								$save = true;

								if($this->_environment->inPrivateRoom()) {
									$currentUser->setLanguage($form_data['language']);
									$currentUser->save();
								}
							}

							// mail settings
							if(!empty($form_data['mail_account'])) {
								if($portalUser->getAccountWantMail() == 'no') {
									$portalUser->setAccountWantMail('yes');
									$save = true;
								}
							} else {
								if($portalUser->getAccountWantMail() == 'yes') {
									$portalUser->setAccountWantMail('no');
									$save = true;
								}
							}

							if(!empty($form_data['mail_room'])) {
								if($portalUser->getOpenRoomWantMail() == 'no') {
									$portalUser->setOpenRoomWantMail('yes');
									$save = true;
								}
							} else {
								if($portalUser->getOpenRoomWantMail() == 'yes') {
									$portalUser->setOpenRoomWantMail('no');
									$save = true;
								}
							}

							$change_name = false;

							// forname
							if(!empty($form_data['forname']) && $portalUser->getFirstName() != $form_data['forname']) {
								$portalUser->setFirstName($form_data['forname']);
								$change_name = true;
								$save = true;
							}

							// surname
							if(!empty($form_data['surname']) && $portalUser->getLastName() != $form_data['surname']) {
								$portalUser->setLastName($form_data['surname']);
								$change_name = true;
								$save = true;
							}
							
							// auto save
							if(!empty($form_data['auto_save'])) {
								if($form_data['auto_save'] == 'on') $portalUser->turnAutoSaveOn();
								else $portalUser->turnAutoSaveOff();

								$save = true;
							}else{
								$portalUser->turnAutoSaveOff();
								$save = true;
							}

						    global $c_email_upload;
						    if ($c_email_upload ) {
						       $own_room = $currentUser->getOwnRoom();
						       if ( (isset($form_data['email_to_commsy']) and !empty($form_data['email_to_commsy'])) ) {
						          $own_room->setEmailToCommSy();
						       } else {
						          $own_room->unsetEmailToCommSy();
						       }
					           if ( (isset($form_data['email_to_commsy_secret']) and !empty($form_data['email_to_commsy_secret'])) ) {
						          $own_room->setEmailToCommSySecret($form_data['email_to_commsy_secret']);
						       } else {
						          $own_room->setEmailToCommSySecret('');
						       }
						       $own_room->save();
						       $save = true;
						    }

							if($save === true) {
								$portalUser->save();
							}

							// change firstname and lastname in all other user_items of this user
							if($change_name === true) {
								$user_manager = $this->_environment->getUserManager();
								$dummy_user = $user_manager->getNewItem();

								// forname
								$value = $form_data['forname'];
								if(empty($value)) $value = -1;
								$dummy_user->setFirstName($value);

								// surname
								$value = $form_data['surname'];
								if(empty($value)) $value = -1;
								$dummy_user->setLastName($value);

								$portalUser->changeRelatedUser($dummy_user);
							}

							// set return
                			$this->_popup_controller->setSuccessfullItemIDReturn($portalUser->getItemID());
						}
						break;

					/**** USER PICTURE ****/
					case 'user_picture':
						if($this->_popup_controller->checkFormData('user_picture')) {
							/* handle user picture upload */
							if(!empty($additional["fileInfo"])) {
								$currentUser = $this->_environment->getCurrentUserItem();
								$portalUser = $currentUser->getRelatedCommSyUserItem();
								
								$srcfile = $additional["fileInfo"]["file"];
								$targetfile = $srcfile . "_converted";

								$session = $this->_environment->getSessionItem();
								$session->unsetValue("add_files");

								// resize image to a maximum width of 150px and keep ratio
								$size = getimagesize($srcfile);
								list($x_orig, $y_orig, $type) = $size;

								$verhaeltnis = $y_orig / $x_orig;
								$max_width = 150;
								$ratio = 1.334; // 3:4

								if($verhaeltnis < $ratio) {
									// wider than 1:$ratio
									$source_width = ($y_orig * $max_width) / ($max_width * $ratio);
									$source_height = $y_orig;
									$source_x = ($x_orig - $source_width) / 2;
									$source_y = 0;
								} else {
									// higher than 1:$ratio
									$source_width = $x_orig;
									$source_height = ($x_orig * ($max_width * $ratio)) / $max_width;
									$source_x = 0;
									$source_y = ($y_orig - $source_height) / 2;
								}

								// create image
								switch($type) {
									case '1':
										$im = imagecreatefromgif($srcfile);
										break;
									case '2':
										$im = imagecreatefromjpeg($srcfile);
										break;
									case '3':
										$im = imagecreatefrompng($srcfile);
										break;
								}

								$newimg = imagecreatetruecolor($max_width, ($max_width * $ratio));
								imagecopyresampled($newimg, $im, 0, 0, $source_x, $source_y, $max_width, ceil($max_width * $ratio), $source_width, $source_height);
								imagepng($newimg, $targetfile);

								// clean up
								imagedestroy($im);
								imagedestroy($newimg);

								// determ new file name
								$filename_info = pathinfo($additional["fileInfo"]["name"]);
								$filename = 'cid' . $this->_environment->getCurrentContextID() . '_' . $currentUser->getItemID() . '.' . $filename_info['extension'];

								// copy file and set picture
								$disc_manager = $this->_environment->getDiscManager();

								$disc_manager->copyFile($targetfile, $filename, true);
								$currentUser->setPicture($filename);
								
								$modifier = $this->_environment->getCurrentUserItem();
								
								if ( isset($portalUser) ) {
									if($disc_manager->copyImageFromRoomToRoom($filename, $portalUser->getContextID())) {
										$value_array = explode('_', $filename);

										$old_room_id = $value_array[0];
										$old_room_id = str_replace('cid', '', $old_room_id);
										$valu_array[0] = 'cid' . $portalUser->getContextID();
										$new_picture_name = implode('_', $value_array);

										$portalUser->setPicture($new_picture_name);
										
										$portalUser->setModificatorItem($modifier);
										$portalUser->setModificationDate(getCurrentDateTimeInMySQL());
										$portalUser->save();
									}
								}
								
								// save
								$currentUser->setModificatorItem($modifier);
								$currentUser->setModificationDate(getCurrentDateTimeInMySQL());
								$currentUser->save();
							}

							// set return
							$this->_popup_controller->setSuccessfullDataReturn($filename);
						}
						break;

					/**** USER ****/
					case 'user':
						$currentUser = $this->_environment->getCurrentUserItem();
						$portalUser = $currentUser->getRelatedCommSyUserItem();
						
						if ( $this->_popup_controller->checkFormData('user') )
						{

							function setValue($currentUser, $portalUser_item, $method, $value) {
								if(isset($value)) {
									// set for user
									call_user_func_array(array($currentUser, $method), array($value));
									
									if ( isset($portalUser_item) )
									{
										// set for portal user
										call_user_func_array(array($portalUser_item, $method), array($value));
									}
								}
							}

							setValue($currentUser, $portalUser, 'setTitle', $form_data['title']);
							setValue($currentUser, $portalUser, 'setBirthday', $form_data['birthday']);

							setValue($currentUser, $portalUser, 'setEmail', $form_data['mail']);
							if($portalUser->hasToChangeEmail()) {
								$portalUser_item->unsetHasToChangeEmail();
								$form_data['mail_all'] = 1;
							}

							setValue($currentUser, $portalUser, 'setTelephone', $form_data['telephone']);
							setValue($currentUser, $portalUser, 'setCellularphone', $form_data['cellularphone']);
							setValue($currentUser, $portalUser, 'setStreet', $form_data['street']);
							setValue($currentUser, $portalUser, 'setZipcode', $form_data['zipcode']);
							setValue($currentUser, $portalUser, 'setCity', $form_data['city']);
							setValue($currentUser, $portalUser, 'setRoom', $form_data['room']);
							setValue($currentUser, $portalUser, 'setOrganisation', $form_data['organisation']);
							setValue($currentUser, $portalUser, 'setPosition', $form_data['position']);
							setValue($currentUser, $portalUser, 'setICQ', $form_data['icq']);
							setValue($currentUser, $portalUser, 'setMSN', $form_data['msn']);
							setValue($currentUser, $portalUser, 'setSkype', $form_data['skype']);
							setValue($currentUser, $portalUser, 'setYahoo', $form_data['yahoo']);
							setValue($currentUser, $portalUser, 'setJabber', $form_data['jabber']);
							setValue($currentUser, $portalUser, 'setHomepage', $form_data['homepage']);
							setValue($currentUser, $portalUser, 'setDescription', $form_data['description']);

							// delete picture handling
							if(isset($form_data['delete_picture']) && $currentUser->getPicture()) {
								$disc_manager = $this->_environment->getDiscManager();

								// unlink file
								if($disc_manager->existsFile($currentUser->getPicture())) $disc_manager->unlinkFile($currentUser->getPicture());

								// set non picture
								$currentUser->setPicture('');
								if(isset($portalUser)) $portalUser->setPicture('');
							}

							// set modificator and modification date and save
							$modifier = $this->_environment->getCurrentUserItem();
							
							$currentUser->setModificatorItem($modifier);
							$currentUser->setModificationDate(getCurrentDateTimeInMySQL());
							$currentUser->save();
							
							if ( isset($portalUser) )
							{
								$portalUser->setModificatorItem($modifier);
								$portalUser->setModificationDate(getCurrentDateTimeInMySQL());
								$portalUser->save();
							}

							/* change all option */
							// get a dummy user
							$user_manager = $this->_environment->getUserManager();
							$dummy_user = $user_manager->getNewItem();
							
							$changeAll = false;
							function setChangeAllValue($currentUser, $dummy_user_item, $method_set, $method_get, $checked) {
								if( isset($checked) )
								{
									$value = call_user_func_array(array($currentUser, $method_get), array());
									if(empty($value)) $value = -1;

									call_user_func_array(array($dummy_user_item, $method_set), array($value));
								}
								
								return $checked;
							}

							$changeAll = $changeAll || setChangeAllValue($currentUser, $dummy_user, 'setTitle', 'getTitle', $form_data['title_all']);
							$changeAll = $changeAll || setChangeAllValue($currentUser, $dummy_user, 'setBirthday', 'getBirthday', $form_data['birthday_all']);

							$changeAll = $changeAll || setChangeAllValue($currentUser, $dummy_user, 'setEmail', 'getEmail', $form_data['mail_all']);
							if(isset($form_data['mail_all'])) {
								$changeAll = true;
								
								if(!$currentUser->isEmailVisible()) $dummy_user->setEmailNotVisible();
								else $dummy_user->setEmailVisible();
							}

							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setTelephone', 'getTelephone', $form_data['telephone_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setCellularphone', 'getCellularphone', $form_data['cellularphone_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setStreet', 'getStreet', $form_data['street_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setZipcode', 'getZipcode', $form_data['zipcode_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setCity', 'getCity', $form_data['city_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setRoom', 'getRoom', $form_data['room_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setOrganisation', 'getOrganisation', $form_data['organisation_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setPosition', 'getPosition', $form_data['position_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setICQ', 'getICQ', $form_data['messenger_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setMSN', 'getMSN', $form_data['messenger_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setSkype', 'getSkype', $form_data['messenger_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setYahoo', 'getYahoo', $form_data['messenger_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setJabber', 'getJabber', $form_data['messenger_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setHomepage', 'getHomepage', $form_data['homepage_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setDescription', 'getDescription', $form_data['description_all']) || $changeAll;
							$changeAll = setChangeAllValue($currentUser, $dummy_user, 'setPicture', 'getPicture', $form_data['picture_all']) || $changeAll;
							
							if ( $changeAll === true )
							{
								$currentUser->changeRelatedUser($dummy_user);
							}
							
							$manager = $this->_environment->getLinkModifierItemManager();
							$manager->markEdited($currentUser->getItemID());
							
							// set return
                			$this->_popup_controller->setSuccessfullItemIDReturn($currentUser->getItemID());
						}
						break;

					/**** NEWSLETTER ****/
					case 'newsletter':
						if($this->_popup_controller->checkFormData('newsletter')) {
							$room_item = $currentUser->getOwnRoom();

							$set_to = 'none';
							if(isset($form_data['newsletter']) && !empty($form_data['newsletter'])) {
								if($form_data['newsletter'] == 2) $set_to = 'weekly';
								elseif($form_data['newsletter'] == 3) $set_to = 'daily';
							}

							// set
							$room_item->setPrivateRoomNewsletterActivity($set_to);

							// save
							$room_item->save();

							// set return
                			$this->_popup_controller->setSuccessfullItemIDReturn($room_item->getItemID());
						}
						break;
					case 'cs_bar':
						if($this->_popup_controller->checkFormData('cs_bar')) {
							$room_item = $currentUser->getOwnRoom();

							//---
							
							$wordpress_manager = $this->_environment->getWordpressManager();
		               $wiki_manager = $this->_environment->getWikiManager();

						      if($additional['action'] == 'create_wordpress'){
		                          if ( isset($form_data['use_comments']) and !empty($form_data['use_comments']) and $form_data['use_comments'] == 'yes') {
		      				         $room_item->setWordpressUseComments();
		      				      } else {
		      				         $room_item->unsetWordpressUseComments();
		      				      }

		      				      if ( isset($form_data['use_comments_moderation']) and !empty($form_data['use_comments_moderation']) and $form_data['use_comments_moderation'] == 'yes') {
		      				         $room_item->setWordpressUseCommentsModeration();
		      				      } else {
		      				         $room_item->unsetWordpressUseCommentsModeration();
		      				      }

		      				      if ( isset($form_data['wordpresslink']) and !empty($form_data['wordpresslink']) and $form_data['wordpresslink'] == 'yes') {
		      				         $room_item->setWordpressHomeLink();
		      				      } else {
		      				         $room_item->unsetWordpressHomeLink();
		      				      }

		      				      if ( isset($form_data['skin_choice']) and !empty($form_data['skin_choice']) ) {
		      				         $room_item->setWordpressSkin($form_data['skin_choice']);
		      				      }

		      				      if ( isset($form_data['wordpresstitle']) and !empty($form_data['wordpresstitle']) ) {
		      				         $room_item->setWordpressTitle($form_data['wordpresstitle']);
		      				      } else {
		      				         $room_item->setWordpressTitle($room_item->getTitle());
		      				      }

		      				      if ( isset($form_data['wordpressdescription']) and !empty($form_data['wordpressdescription']) ) {
		      				         $room_item->setWordpressDescription($form_data['wordpressdescription']);
		      				      } else {
		      				         $room_item->setWordpressDescription('');
		      				      }

		      				      if ( isset($form_data['member_role']) and !empty($form_data['member_role']) ) {
		      				         $room_item->setWordpressMemberRole($form_data['member_role']);
		      				      } else {
		      				         $room_item->setWordpressMemberRole();
		      				      }

		      				      $room_item->setWithWordpressFunctions();
		      				      $room_item->setWordpressExists();
		      				      $room_item->setWordpressActive();
		      				      // save
		      				      $room_item->save();
		      				      // create or change new wordpress
		      				      $success = $wordpress_manager->createWordpress($room_item);
						      } else if ($additional['action'] == 'delete_wordpress') {
						         if($wordpress_manager->deleteWordpress($room_item->getWordpressId())){
		      				         $current_user = $this->_environment->getCurrentUserItem();
		      				         $room_item->setModificatorItem($current_user);
		      				         $room_item->setModificationDate(getCurrentDateTimeInMySQL());
		      				         $room_item->unsetWordpressExists();
		      				         $room_item->setWordpressInActive();
		      				         $room_item->setWordpressSkin('twentyten');
		      				         $room_item->setWordpressTitle($room_item->getTitle());
		      				         $room_item->setWordpressDescription('');
		      				         $room_item->setWordpressId(0);
		      				         // Save item
		      				         $room_item->save();
						         }
						      } else if($additional['action'] == 'create_wiki'){
						         // Set modificator and modification date
		                     #if ( isset($form_data['wikilink']) and !empty($form_data['wikilink']) and $form_data['wikilink'] == 'yes') {
		                        $room_item->setWikiHomeLink();
		                     #} else {
		                     #   $room_item->unsetWikiHomeLink();
		                     #}
		                     if ( isset($form_data['wikilink2']) and !empty($form_data['wikilink2']) and $form_data['wikilink2'] == 'yes') {
		                        $room_item->setWikiPortalLink();
		                     } else {
		                        $room_item->unsetWikiPortalLink();
		                     }
		                     if ( isset($form_data['wiki_skin_choice']) and !empty($form_data['wiki_skin_choice']) ) {
		                        $room_item->setWikiSkin($form_data['wiki_skin_choice']);
		                     }
		                     if ( isset($form_data['wikititle']) and !empty($form_data['wikititle']) ) {
		                        $room_item->setWikiTitle($form_data['wikititle']);
		                     } else {
		                        $room_item->setWikiTitle($room_item->getTitle());
		                     }

		                     if ( isset($form_data['admin']) and !empty($form_data['admin']) ) {
		                        $room_item->setWikiAdminPW($form_data['admin']);
		                     }

		                     if ( isset($form_data['edit']) and !empty($form_data['edit']) ) {
		                        $room_item->setWikiEditPW($form_data['edit']);
		                     } else {
		                        $room_item->setWikiEditPW('');
		                     }

		                     if ( isset($form_data['read']) and !empty($form_data['read']) ) {
		                        $room_item->setWikiReadPW($form_data['read']);
		                     } else {
		                        $room_item->setWikiReadPW('');
		                     }

		                     #if ( isset($form_data['use_commsy_login']) ) {
		                        $room_item->setWikiUseCommSyLogin();
		                     #} else {
		                     #   $room_item->unsetWikiUseCommSyLogin();
		                     #}

		                     if ( isset($form_data['community_read_access']) ) {
		                        $room_item->setWikiCommunityReadAccess();
		                     } else {
		                        $room_item->unsetWikiCommunityReadAccess();
		                     }

		                     if ( isset($form_data['community_write_access']) ) {
		                        $room_item->setWikiCommunityWriteAccess();
		                     } else {
		                        $room_item->unsetWikiCommunityWriteAccess();
		                     }

		                     if ( isset($form_data['portal_read_access']) ) {
		                        $room_item->setWikiPortalReadAccess();
		                     } else {
		                        $room_item->unsetWikiPortalReadAccess();
		                     }

		                     if ( isset($form_data['room_mod_write_access']) ) {
		                        $room_item->setWikiRoomModWriteAccess();
		                     } else {
		                        $room_item->unsetWikiRoomModWriteAccess();
		                     }

		                     if ( isset($form_data['show_login_box']) ) {
		                        $room_item->setWikiShowCommSyLogin();
		                     } else {
		                        $room_item->unsetWikiShowCommSyLogin();
		                     }

		                     #if ( isset($form_data['enable_fckeditor']) ) {
		                        $room_item->setWikiEnableFCKEditor();
		                     #} else {
		                     #   $room_item->unsetWikiEnableFCKEditor();
		                     #}

		                     #if ( isset($form_data['enable_sitemap']) ) {
		                        $room_item->setWikiEnableSitemap();
		                     #} else {
		                     #   $room_item->unsetWikiEnableSitemap();
		                     #}

		                     #if ( isset($form_data['enable_statistic']) ) {
		                        $room_item->setWikiEnableStatistic();
		                     #} else {
		                     #   $room_item->unsetWikiEnableStatistic();
		                     #}

		                     #if ( isset($form_data['enable_search']) ) {
		                        $room_item->setWikiEnableSearch();
		                     #} else {
		                     #   $room_item->unsetWikiEnableSearch();
		                     #}

		                     #if ( isset($form_data['enable_rss']) ) {
		                        $room_item->setWikiEnableRss();
		                     #} else {
		                     #   $room_item->unsetWikiEnableRss();
		                     #}

		                     if ( isset($form_data['enable_calendar']) ) {
		                        $room_item->setWikiEnableCalendar();
		                     } else {
		                        $room_item->unsetWikiEnableCalendar();
		                     }

		                     if ( isset($form_data['enable_gallery']) ) {
		                        $room_item->setWikiEnableGallery();
		                     } else {
		                        $room_item->unsetWikiEnableGallery();
		                     }

		                     if ( isset($form_data['enable_notice']) ) {
		                        $room_item->setWikiEnableNotice();
		                     } else {
		                        $room_item->unsetWikiEnableNotice();
		                     }

		                     #if ( isset($form_data['enable_pdf']) ) {
		                        $room_item->setWikiEnablePdf();
		                     #} else {
		                     #   $room_item->unsetWikiEnablePdf();
		                     #}

		                     if ( isset($form_data['enable_rater']) ) {
		                        $room_item->setWikiEnableRater();
		                     } else {
		                        $room_item->unsetWikiEnableRater();
		                     }

		                     #if ( isset($form_data['enable_listcategories']) ) {
		                        $room_item->setWikiEnableListCategories();
		                     #} else {
		                     #   $room_item->unsetWikiEnableListCategories();
		                     #}

		                     if ((isset($form_data['new_page_template'])) &&  ($_POST['new_page_template'] != '')) {
		                        $room_item->setWikiNewPageTemplate($_POST['new_page_template']);
		                     } else {
		                        $room_item->unsetWikiNewPageTemplate();
		                     }

		                     if ( isset($form_data['enable_swf']) ) {
		                        $room_item->setWikiEnableSwf();
		                     } else {
		                        $room_item->unsetWikiEnableSwf();
		                     }

		                     if ( isset($form_data['enable_wmplayer']) ) {
		                        $room_item->setWikiEnableWmplayer();
		                     } else {
		                        $room_item->unsetWikiEnableWmplayer();
		                     }

		                     if ( isset($form_data['enable_quicktime']) ) {
		                        $room_item->setWikiEnableQuicktime();
		                     } else {
		                        $room_item->unsetWikiEnableQuicktime();
		                     }

		                     if ( isset($form_data['enable_youtube_google_vimeo']) ) {
		                        $room_item->setWikiEnableYoutubeGoogleVimeo();
		                     } else {
		                        $room_item->unsetWikiEnableYoutubeGoogleVimeo();
		                     }

		                     include_once('functions/development_functions.php');

		                     // Discussion
		                     #if ( isset($form_data['enable_discussion']) ) {
		                        $room_item->setWikiEnableDiscussion();
		                        if ( isset($form_data['new_discussion']) ) {
		                           $_POST['new_discussion'] = $form_data['new_discussion'];
		                           $room_item->WikiSetNewDiscussion($form_data['new_discussion']);
		                        }
		                     #} else {
		                     #   $room_item->unsetWikiEnableDiscussion();
		                     #}

		                     $enable_discussion_discussions = array();
		                     $form_data_keys = array_keys($form_data);
		                     foreach($form_data_keys as $form_data_key){
		                        if(stristr($form_data_key, 'enable_discussion_discussions_')){;
		                           $enable_discussion_discussions[] = $form_data[$form_data_key];
		                        }
		                     }
		                     $_POST['enable_discussion_discussions'] = $enable_discussion_discussions;

		                     if ( isset($form_data['enable_discussion_notification']) ) {
		                        $room_item->setWikiEnableDiscussionNotification();
		                     } else {
		                        $room_item->unsetWikiEnableDiscussionNotification();
		                     }

		                    if ( isset($form_data['enable_discussion_notification_groups']) ) {
		                        $room_item->setWikiEnableDiscussionNotificationGroups();
		                    } else {
		                        $room_item->unsetWikiEnableDiscussionNotificationGroups();
		                    }

		                    if ( isset($form_data['wiki_section_edit']) ) {
		                        $room_item->setWikiWithSectionEdit();
		                    } else {
		                        $room_item->setWikiWithoutSectionEdit();
		                    }

		                    if ( isset($form_data['wiki_section_edit_header']) ) {
		                        $room_item->setWikiWithHeaderForSectionEdit();
		                    } else {
		                        $room_item->setWikiWithoutHeaderForSectionEdit();
		                    }

		                     $room_item->setWikiExists();
		                     $room_item->setWikiActive();

		                     $wiki_manager->createWiki($room_item);

		                     // Save item - after createWiki() -> old discussions might be deleted
		                     $room_item->save();

		                     $enable_wiki_groups = array();
		                     $form_data_keys = array_keys($form_data);
		                     foreach($form_data_keys as $form_data_key){
		                        if(stristr($form_data_key, 'enable_wiki_groups_')){;
		                           $enable_wiki_groups[] = $form_data[$form_data_key];
		                        }
		                     }

		                     // WSDL-xml hier noch nicht zugreifbar, daher weiterhin die alte Variante
		                     if ( !empty($enable_wiki_groups)){
		                        //global $c_use_soap_for_wiki;
		                        //if(!$c_use_soap_for_wiki){
		                           $wiki_manager->setWikiGroupsAsPublic($enable_wiki_groups);
		                        //} else {
		                        //   $wiki_manager->setWikiGroupsAsPublic_soap($_POST['enable_wiki_groups']);
		                        //}
		                     } else {
		                        //global $c_use_soap_for_wiki;
		                        //if(!$c_use_soap_for_wiki){
		                           $wiki_manager->setWikiGroupsAsPublic(array());
		                        //} else {
		                        //   $wiki_manager->setWikiGroupsAsPublic_soap(array());
		                        //}
		                     }
						      } else if ($additional['action'] == 'delete_wiki'){
		                     $room_item->setModificatorItem($currentUser);
		                     $room_item->setModificationDate(getCurrentDateTimeInMySQL());
		                     $room_item->unsetWikiExists();
		                     $room_item->setWikiInActive();
		                     $room_item->setWikiSkin('pmwiki');
		                     $room_item->setWikiTitle($room_item->getTitle());
		                     $room_item->unsetWikiEnableDiscussion();
		                     $room_item->unsetWikiEnableDiscussionNotification();
		                     $room_item->unsetWikiEnableDiscussionNotificationGroups();
		                     $room_item->unsetWikiDiscussionArray();
		                     // Save item
		                     $room_item->save();
		                     // delete wiki
		                     $wiki_manager->deleteWiki($room_item);
						      } else if ($additional['action'] == 'chat'){
						         if ( isset($form_data['chatlink']) and !empty($form_data['chatlink']) and $form_data['chatlink'] == 'yes') {
		                            $room_item->setChatLinkActive();
		                         } else {
		                            $room_item->setChatLinkInactive();
		                         }
		                         $room_item->save();
						      }
							
							
							//---
							
							
							if(isset($form_data['show_widget_view']) && !empty($form_data['show_widget_view'])) {
								if($form_data['show_widget_view'] == 'yes'){
								   $room_item->setCSBarShowWidgets('1');
								} else{
									$room_item->setCSBarShowWidgets('-1');
								}
							}else{
								$room_item->setCSBarShowWidgets('-1');
							}
							if(isset($form_data['show_roomwide_search']) && !empty($form_data['show_roomwide_search'])) {
								if($form_data['show_roomwide_search'] == 'yes'){
								   $room_item->setPortletShowRoomWideSearchBox('1');
								} else{
									$room_item->setPortletShowRoomWideSearchBox('-1');
								}
							}else{
								$room_item->setPortletShowRoomWideSearchBox('-1');
							}

							if(isset($form_data['show_newest_entries']) && !empty($form_data['show_newest_entries'])) {
								if($form_data['show_newest_entries'] == 'yes'){
								   $room_item->setPortletShowNewEntryList('1');
								} else{
									$room_item->setPortletShowNewEntryList('-1');
								}
							}else{
								$room_item->setPortletShowNewEntryList('-1');
							}

							if(isset($form_data['show_active_rooms']) && !empty($form_data['show_active_rooms'])) {
								if($form_data['show_active_rooms'] == 'yes'){
								   $room_item->setPortletShowActiveRoomList('1');
								} else{
									$room_item->setPortletShowActiveRoomList('-1');
								}
							}else{
								$room_item->setPortletShowActiveRoomList('-1');
							}


							if(isset($form_data['show_calendar_view']) && !empty($form_data['show_calendar_view'])) {
								if($form_data['show_calendar_view'] == 'yes'){
								   $room_item->setCSBarShowCalendar('1');
								} else{
									$room_item->setCSBarShowCalendar('-1');
								}
							}else{
								$room_item->setCSBarShowCalendar('-1');
							}

							if(isset($form_data['show_stack_view']) && !empty($form_data['show_stack_view'])) {
								if($form_data['show_stack_view'] == 'yes'){
								   $room_item->setCSBarShowStack('1');
								} else{
									$room_item->setCSBarShowStack('-1');
								}
							}else{
								$room_item->setCSBarShowStack('-1');
							}

							if(isset($form_data['show_portfolio_view']) && !empty($form_data['show_portfolio_view'])) {
								if($form_data['show_portfolio_view'] == 'yes'){
									$room_item->setCSBarShowPortfolio('1');
								} else{
									$room_item->setCSBarShowPortfolio('-1');
								}
							}else{
								$room_item->setCSBarShowPortfolio('-1');
							}

							if(isset($form_data['show_old_room_switcher']) && !empty($form_data['show_old_room_switcher'])) {
								if($form_data['show_old_room_switcher'] == 'yes'){
								   $room_item->setCSBarShowOldRoomSwitcher('1');
								} else{
									$room_item->setCSBarShowOldRoomSwitcher('-1');
								}
							}else{
								$room_item->setCSBarShowOldRoomSwitcher('-1');
							}

							// save
							$room_item->save();

							// set return
                			$this->_popup_controller->setSuccessfullItemIDReturn($room_item->getItemID());
						}
						break;
				}
			}


			// 			// save user
			// 			else {





			// 				$room_item = $environment->getCurrentContextItem();
			// 				// Define rubric connections
			// 				$rubric_connection = array();
			// 				$current_rubrics = $room_item->getAvailableRubrics();
			// 				foreach ( $current_rubrics as $rubric ) {
			// 					switch ( $rubric ) {
			// 						case CS_GROUP_TYPE:
			// 							$rubric_connection[] = CS_GROUP_TYPE;
			// 							break;
			// 						case CS_INSTITUTION_TYPE:
			// 							$rubric_connection[] = CS_INSTITUTION_TYPE;
			// 							break;
			// 					}
			// 				}
			// 				$profile_view->setRubricConnections($rubric_connection);
			// 				$params = $environment->getCurrentParameterArray();
			// 				unset($params['is_saved']);
			// 				$profile_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params));
			// 				if (!$currentUser->mayEditRegular($current_user)) {
			// 					$profile_view->warnChanger();
			// 					$params = array();
			// 					$params['environment'] = $environment;
			// 					$params['with_modifying_actions'] = true;
			// 					$params['width'] = 500;
			// 					$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
			// 					unset($params);
			// 					$errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
			// 				}

			// 				$profile_view->setForm($form);
			// 			}
		}
	}

	public function initPopup($data) {
		$current_portal_item = $this->_environment->getCurrentPortalItem();

		// set configuration
		$account = array();
		
		$this->_user = $this->_environment->getCurrentUserItem();

		// disable merge form only for root
		$this->_config['show_merge_form'] = true;
		if(isset($this->_user) && $this->_user->isRoot()) {
			$this->_config['show_merge_form'] = false;
		}

		// auth source
		if(!isset($current_portal_item)) $current_portal_item = $this->_environment->getServerItem();

		#$this->_show_auth_source = $current_portal_item->showAuthAtLogin();
		# muss angezeigt werden, sonst koennen mit der aktuellen Programmierung
		# keine Acounts mit gleichen Kennungen aber unterschiedlichen Quellen
		# zusammengelegt werden
		$this->_config['show_auth_source'] = true;

		$auth_source_list = $current_portal_item->getAuthSourceListEnabled();
		if(isset($auth_source_list) && !$auth_source_list->isEmpty()) {
			$auth_source_item = $auth_source_list->getFirst();

			while($auth_source_item) {
				$this->_data['auth_source_array'][] = array(
					'value'		=> $auth_source_item->getItemID(),
					'text'		=> $auth_source_item->getTitle());

				$auth_source_item = $auth_source_list->getNext();
			}
		}
		$this->_data['default_auth_source'] = $current_portal_item->getAuthDefault();

		// password change form
		$this->_config['show_password_change_form'] = false;
		$current_auth_source_item = $current_portal_item->getAuthSource($this->_user->getAuthSource());
		if(	(isset($current_auth_source_item) && $current_auth_source_item->allowChangePassword()) ||
			$this->_user->isRoot()) {

			$this->_config['show_password_change_form'] = true;
		}

		// account change form
		$this->_config['show_account_change_form'] = false;
		if(	(isset($current_auth_source_item) && $current_auth_source_item->allowChangeUserID()) ||
			$this->_user->isRoot()) {

			$this->_config['show_account_change_form'] = true;
		}

		// mail form
		$this->_config['show_mail_change_form'] = false;
		if($this->_user->isModerator()) {
			$this->_config['show_mail_change_form'] = true;
		}

		// assign template vars
		$this->assignTemplateVars();
	}

	public function getFieldInformation($sub) {
		$return = array(
			'newsletter'	=> array(
				array('name' => 'newsletter', 'type' => 'radio', 'mandatory' => true)
			),
			'merge'	=> array(
				array('name' => 'merge_user_id', 'type' => 'text', 'mandatory' => false),
				array('name' => 'merge_user_password', 'type' => 'text', 'mandatory' => false)
			),
			'account'	=> array(
				array('name' => 'forname', 'type' => 'text', 'mandatory' => true),
				array('name' => 'surname', 'type' => 'text', 'mandatory' => true),
				array('name' => 'user_id', 'type' => 'text', 'mandatory' => true),
				array('name' => 'old_password', 'type' => 'text', 'mandatory' => false),
				array('name' => 'new_password', 'type' => 'text', 'mandatory' => false, 'same_as' => 'new_password_confirm'),
				array('name' => 'new_password_confirm', 'type' => 'text', 'mandatory' => false),
				array('name' => 'language', 'type' => 'select', 'mandatory' => true),
				array('name' => 'mail_account', 'type' => 'checkbox', 'mandatory' => false),
				array('name' => 'mail_room', 'type' => 'checkbox', 'mandatory' => false),
				array('name' => 'upload', 'type' => 'radio', 'mandatory' => false/*true*/),
				array('name' => 'auto_save', 'type' => 'checkbox', 'mandatory' => true),
			),
			'user'			=> array(
				array('name' => 'title','type' => 'text', 'mandatory' => false), array('name' => 'title_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'birthday','type' => 'text', 'mandatory' => false), array('name' => 'birthday_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'picture','type' => 'file', 'mandatory' => false), array('name' => 'picture_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'mail','type' => 'mail', 'mandatory' => true), array('name' => 'mail_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'telephone','type' => 'text', 'mandatory' => false), array('name' => 'telephone_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'cellularphone','type' => 'text', 'mandatory' => false), array('name' => 'cellularphone_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'street','type' => 'text', 'mandatory' => false), array('name' => 'street_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'zipcode','type' => 'numeric', 'mandatory' => false), array('name' => 'zipcode_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'city','type' => 'text', 'mandatory' => false), array('name' => 'city_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'room','type' => 'text', 'mandatory' => false), array('name' => 'room_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'organisation','type' => 'text', 'mandatory' => false), array('name' => 'organisation_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'position','type' => 'text', 'mandatory' => false), array('name' => 'position_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'icq','type' => 'numeric', 'mandatory' => false),
				array('name' => 'msn','type' => 'text', 'mandatory' => false),
				array('name' => 'skype','type' => 'text', 'mandatory' => false),
				array('name' => 'yahoo','type' => 'text', 'mandatory' => false),
				array('name' => 'jabber','type' => 'text', 'mandatory' => false), array('name' => 'messenger_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'homepage','type' => 'text', 'mandatory' => false), array('name' => 'homepage_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'description','type' => 'text', 'mandatory' => false), array('name' => 'description_all','type' => 'checkbox', 'mandatory' => false),
			),
			'user_picture'	=> array(
			),
		);

		return $return[$sub];
	}

	private function assignTemplateVars() {
		$translator = $this->_environment->getTranslationObject();
		$current_context = $this->_environment->getCurrentContextItem();

		// general information
		$general_information = array();

		// max upload size
		$val = $current_context->getMaxUploadSizeInBytes();
		$meg_val = round($val / 1048576);
		$general_information['max_upload_size'] = $meg_val;

		$this->_popup_controller->assign('popup', 'general', $general_information);

		// portal information
		$portal_information = array();
		$portal_information['portal_name'] = $this->_environment->getCurrentPortalItem()->getTitle();
		$this->_popup_controller->assign('popup', 'portal', $portal_information);

		// context
		$context_information = array();
		$context_information["context_name"] = $current_context->getTitle();
		$this->_popup_controller->assign("popup", "context", $context_information);

		// form information
		$form_information = array();
		$form_information['account'] = $this->getAccountInformation();
		$form_information['user'] = $this->getUserInformation();
		$form_information['newsletter'] = $this->getNewsletterInformation();
		$form_information['cs_bar'] = $this->getCSBarInformation();
		$form_information['config'] = $this->_config;
		$form_information['data'] = $this->_data;

		// languages
		$languages = array();
		$languages[] = array(
			'value'		=>	'browser',
			'text'		=>	$translator->getMessage('USER_BROWSER_LANGUAGE')
		);
		$languages[] = array(
			'value'		=>	'disabled',
			'text'		=>	'------------------'
		);

		$available_languages = $this->_environment->getAvailableLanguageArray();
		foreach($available_languages as $language) {
			$languages[] = array(
				'value'		=>	$language,
				'text'		=>	$translator->getLanguageLabelOriginally($language)
			);
		}

		$form_information['languages'] = $languages;

		$this->_popup_controller->assign('popup', 'form', $form_information);
	}

	private function getAccountInformation() {
		$return = array();

		// get data from database
		$return['firstname'] = $this->_user->getFirstname();
		$return['lastname'] = $this->_user->getLastname();
		$return['user_id'] = $this->_user->getUserID();
		$return['language'] = $this->_user->getLanguage();
		$return['email_account'] = ($this->_user->getAccountWantMail() === 'yes') ? true : false;
		$return['email_room'] = ($this->_user->getOpenRoomWantMail() === 'yes') ? true : false;
//		$return['new_upload'] = ($this->_user->isNewUploadOn()) ? true : false;
		$return['auto_save'] = ($this->_user->isAutoSaveOn()) ? true : false;
		$return['email_to_commsy_on'] = false;

	    global $c_email_upload;
	    if ($c_email_upload ) {
		   $return['email_to_commsy_on'] = true;
	       $own_room = $this->_user->getOwnRoom();
	       $return['email_to_commsy'] = $own_room->getEmailToCommSy();
	       $return['email_to_commsy_secret'] = $own_room->getEmailToCommSySecret();
	       global $c_email_upload_email_account;
	       $return['email_to_commsy_mailadress'] = $c_email_upload_email_account;
	    }

      $this->_popup_controller->assign('popup', 'external', $this->getExternalInformation());
      
		return $return;
	}

	private function getUserInformation() {
		$return = array();

		// get data from database
		$return['title'] = $this->_user->getTitle();
		$return['birthday'] = $this->_user->getBirthday();
		$return['picture'] = $this->_user->getPicture();
		$return['mail'] = $this->_user->getEmail();
		$return['telephone'] = $this->_user->getTelephone();
		$return['cellularphone'] = $this->_user->getCellularphone();
		$return['street'] = $this->_user->getStreet();
		$return['zipcode'] = $this->_user->getZipcode();
		$return['city'] = $this->_user->getCity();
		$return['room'] = $this->_user->getRoom();
		$return['organisation'] = $this->_user->getOrganisation();
		$return['position'] = $this->_user->getPosition();
		$return['icq'] = $this->_user->getICQ();
		$return['msn'] = $this->_user->getMSN();
		$return['skype'] = $this->_user->getSkype();
		$return['yahoo'] = $this->_user->getYahoo();
		$return['jabber'] = $this->_user->getJabber();
		$return['homepage'] = $this->_user->getHomepage();
		$return['description'] = $this->_user->getDescription();

		return $return;

		/*

            if ($this->_item->isModerator()) {
               $this->_values['want_mail_get_account'] = $this->_item->getAccountWantMail();
               $this->_values['is_moderator'] = true;
            } else {
               $this->_values['is_moderator'] = false;
            }
            $picture = $this->_item->getPicture();
            $this->_values['upload'] = $picture;
            if (!empty($picture)) {
               $this->_values['with_picture'] = true;
            } else {
               $this->_values['with_picture'] = false;
            }

            if (!$this->_item->isEmailVisible()) {
               $this->_values['email_visibility'] = 'checked';
            }
		 */
	}

	private function getExternalInformation() {
	   global $c_wordpress;
	   global $c_pmwiki;

	   $return = array();
	   $translator = $this->_environment->getTranslationObject();

	   $user_item = $this->_environment->getPortalUserItem();
	   $current_user = $this->_environment->getPortalUserItem();
	   $current_context = $user_item->getOwnRoom();
	   $current_portal_item = $this->_environment->getCurrentPortalItem();


	   // Wordpress
	   if(isset($c_wordpress) and $c_wordpress){
   	   $wordpress_manager = $this->_environment->getWordpressManager();
	      $wordpress = array();
   	   if($current_context->isWordpressActive()){
            $wordpress['wordpress_active'] = 'yes';
   	   }
	      $wordpress['wordpresstitle'] = $current_context->getWordpressTitle();
	      $wordpress['wordpressdescription'] = $current_context->getWordpressDescription();

   	   $wordpress_skins = array();
   	   foreach($wordpress_manager->getSkins() as $key => $value){
   	      $temp_array['text']  = $key;
   	      $temp_array['value'] = $value;
   	      $wordpress_skins[] = $temp_array;
   	   }
   	   $wordpress['skin_array'] = $wordpress_skins;
   	   $wordpress['skin_choice'] = $current_context->getWordpressSkin();

   	   $wordpress_member_roles = array();
   	   $wordpress_member_roles[] = array('text' => $translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_SUBSCRIBER'), 'value' => 'subscriber');
   	   $wordpress_member_roles[] = array('text' => $translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_AUTHOR'), 'value' => 'author');
   	   $wordpress_member_roles[] = array('text' => $translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_EDITOR'), 'value' => 'editor');
   	   $wordpress_member_roles[] = array('text' => $translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_ADMINISTRATOR'), 'value' => 'administrator');
   	   $wordpress['member_role_array'] = $wordpress_member_roles;
   	   $wordpress['member_role'] = $current_context->getWordpressMemberRole();

   	   if($current_context->getWordpressUseComments() == '1'){
   	      $wordpress['use_comments'] = 'yes';
   	   }
   	   if($current_context->getWordpressUseCommentsModeration() == '1'){
   	      $wordpress['use_comments_moderation'] = 'yes';
   	   }

   	   if($current_context->getWordpressHomeLink() == '1'){
            $wordpress['wordpresslink'] = 'yes';
   	   }
      	$return['wordpress'] = $wordpress;
	   } else {
	      $return['wordpress'] = false;
	   }

	   // Wiki
	   if(!empty($c_pmwiki) && $c_pmwiki) {
	      $wiki_manager = $this->_environment->getWikiManager();
	      $wiki = array();

	      $wiki_skins = array();
         global $c_pmwiki_path_file;
         $directory_handle = @opendir($c_pmwiki_path_file.'/pub/skins');
         if ($directory_handle) {
            while (false !== ($dir = readdir($directory_handle))) {
               if ( $dir != 'home'
                    and $dir != '...'
                    and $dir != '..'
                    and $dir != '.'
                    and $dir != 'print'
                    and $dir != 'jsMath'
                    and $dir != 'CVS'
                  ) {
                  $wiki_skins[] = $dir;
               }
            }
         }
         $directory_handle = @opendir($c_pmwiki_path_file.'/wikis/'.$this->_environment->getCurrentPortalID().'/'.$this->_environment->getCurrentContextID().'/pub/skins');
         if ($directory_handle) {
            while (false !== ($dir = readdir($directory_handle))) {
               if ( $dir != 'home'
                    and $dir != '...'
                    and $dir != '..'
                    and $dir != '.'
                    and $dir != 'print'
                    and $dir != 'jsMath'
                    and $dir != 'CVS'
                  ) {
                  $wiki_skins[] = $dir;
               }
            }
         }
   	   $wiki['wiki_skin_array'] = $wiki_skins;

   	   if($current_context->isWikiActive()){
            $wiki['wiki_active'] = 'yes';

            $wiki['wikititle'] = $current_context->getWikiTitle();
            if ($current_context->getWikiHomeLink() == '1'){
               $wiki['wikilink'] = 'yes';
            }
            if ($current_context->getWikiPortalLink() == '1'){
               $wiki['wikilink2'] = 'yes';
            }
            if ($current_context->WikiShowCommSyLogin() == "1"){
               $wiki['show_login_box'] = 'yes';
            }
            if ($current_context->WikiEnableFCKEditor() == "1"){
               $wiki['enable_fckeditor'] = 'yes';
            }
            if ($current_context->WikiEnableSitemap() == "1"){
               $wiki['enable_sitemap'] = 'yes';
            }
            if ($current_context->WikiEnableStatistic() == "1"){
               $wiki['enable_statistic'] = 'yes';
            }
            if ($current_context->WikiEnableSearch() == "1"){
               $wiki['enable_search'] = 'yes';
            }
            if ($current_context->WikiEnableRss() == "1"){
               $wiki['enable_rss'] = 'yes';
            }
            if ($current_context->WikiEnableCalendar() == "1"){
               $wiki['enable_calendar'] = 'yes';
            }
            if ($current_context->WikiEnableGallery() == "1"){
               $wiki['enable_gallery'] = 'yes';
            }
            if ($current_context->WikiEnableNotice() == "1"){
               $wiki['enable_notice'] = 'yes';
            }
            if ($current_context->WikiEnablePdf() == "1"){
               $wiki['enable_pdf'] = 'yes';
            }
            if ($current_context->WikiEnableRater() == "1"){
               $wiki['enable_rater'] = 'yes';
            }
            if ($current_context->WikiEnableListCategories() == "1"){
               $wiki['enable_listcategories'] = 'yes';
            }
            if ($current_context->WikiNewPageTemplate() != "-1"){
               $wiki['new_page_template'] = $this->_item->WikiNewPageTemplate();
            }
            if ($current_context->WikiEnableSwf() == "1"){
               $wiki['enable_swf'] = 'yes';
            }
            if ($current_context->WikiEnableWmplayer() == "1"){
               $wiki['enable_wmplayer'] = 'yes';
            }
            if ($current_context->WikiEnableQuicktime() == "1"){
               $wiki['enable_quicktime'] = 'yes';
            }
            if ($current_context->WikiEnableYoutubeGoogleVimeo() == "1"){
               $wiki['enable_youtube_google_vimeo'] = 'yes';
            }
            if ($current_context->WikiEnableDiscussion() == "1"){
               $wiki['enable_discussion'] = 'yes';
            }
            $wiki['enable_discussion_discussions'] = $current_context->getWikiDiscussionArray();
            if ($current_context->WikiEnableDiscussionNotification() == "1"){
               $wiki['enable_discussion_notification'] = 'yes';
            }
            if ($current_context->WikiEnableDiscussionNotificationGroups() == "1"){
               $wiki['enable_discussion_notification_groups'] = 'yes';
            }
            if ($current_context->WikiUseCommSyLogin() == "1"){
               $wiki['use_commsy_login'] = 'yes';
            }
            if ($current_context->WikiCommunityReadAccess() == "1"){
               $wiki['community_read_access'] = 'yes';
            }
            if ($current_context->WikiCommunityWriteAccess() == "1"){
               $wiki['community_write_access'] = 'yes';
            }
            if ($current_context->WikiPortalReadAccess() == "1"){
               $wiki['portal_read_access'] = 'yes';
            }
            if ($current_context->isWikiRoomModWriteAccess() ) {
               $wiki['room_mod_write_access'] = 'yes';
            }
            $wiki['new_discussion'] = '';
            if ($current_context->wikiWithSectionEdit() ) {
               $wiki['wiki_section_edit'] = 'yes';
            }
            if ($current_context->wikiWithHeaderForSectionEdit() ) {
               $wiki['wiki_section_edit_header'] = 'yes';
            }

            $wiki['wiki_skin_choice'] = $current_context->getWikiSkin();
            $wiki['admin'] = $current_context->getWikiAdminPW();
            $wiki['edit'] = $current_context->getWikiEditPW();
            $wiki['read'] = $current_context->getWikiReadPW();

            $wiki_groups_array = $wiki_manager->getGroupsForWiki(false);
            $temp_wiki_groups_array = array();
            for ($index = 0; $index < sizeof($wiki_groups_array['groups']); $index++) {
               $temp_array = array();
               $temp_array['group'] = $wiki_groups_array['groups'][$index];
               $temp_array['public'] = $wiki_groups_array['public'][$index];
               $temp_wiki_groups_array[] = $temp_array;
            }

            $wiki['enable_wiki_groups'] = $temp_wiki_groups_array;

   	   } else {
      	   $wiki['wikititle'] = $current_context->getWikiTitle();
            $wiki['wiki_skin_choice'] = 'pmwiki';
            $wiki['admin'] = 'admin';
            $wiki['edit'] = 'edit';
            $wiki['read'] = 'read';
            $wiki['show_login_box'] = 'yes';
            $wiki['wikilink'] = 'yes';
            $wiki['use_commsy_login'] = 'yes';
   	   }
	      $return['wiki'] = $wiki;
	   } else {
	      $return['wiki'] = false;
	   }

	   return $return;
	}

	
	
	private function getNewsletterInformation() {
		$return = array();

		// get data from database
		$room = $this->_environment->getCurrentUserItem()->getOwnRoom();
		$newsletter = $room->getPrivateRoomNewsletterActivity();

		switch($newsletter) {
			case 'weekly':
				$return['newsletter'] = '2';
				break;
			case 'daily':
				$return['newsletter'] = '3';
				break;
			default:
				$return['newsletter'] = '1';
				break;
		}

		return $return;
	}

	private function getCSBarInformation() {
		$return = array();

		// get data from database
		$room = $this->_environment->getCurrentUserItem()->getOwnRoom();
		if ($room->getCSBarShowWidgets() == '1'){
			$return['show_widget_view'] = 'yes';
		}
		if ($room->getPortletShowRoomWideSearchBox()){
			$return['show_roomwide_search'] = 'yes';
		}
		if ($room->getPortletShowNewEntryList()){
			$return['show_newest_entries'] = 'yes';
		}
		if ($room->getPortletShowActiveRoomList()){
			$return['show_active_rooms'] = 'yes';
		}

		if ($room->getCSBarShowCalendar() == '1'){
			$return['show_calendar_view'] = 'yes';
		}

		if ($room->getCSBarShowStack() == '1'){
			$return['show_stack_view'] = 'yes';
		}

		if ($room->getCSBarShowPortfolio() == '1'){
			$return['show_portfolio_view'] = 'yes';
		}

		if ($room->getCSBarShowOldRoomSwitcher() == '1'){
			$return['show_old_room_switcher'] = 'yes';
		}

		return $return;
	}


}