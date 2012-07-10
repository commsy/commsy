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

	public function save($form_data, $additional = array()) {
// 		// function for page edit
// 		// - to check files for virus
// 		if (isset($c_virus_scan) and $c_virus_scan) {
// 			include_once('functions/page_edit_functions.php');
// 		}
// 		$is_saved = false;

// 		if (!empty($_POST['option'])) {
// 			$command = $_POST['option'];
// 		} else {
// 			$command = '';
// 		}

// 		// Coming back from attaching items
// 		if ( !empty($_GET['backfrom']) ) {
// 			$backfrom = $_GET['backfrom'];
// 		} else {
// 			$backfrom = false;
// 		}

// 		if (!empty($_GET['uid'])) {
// 			$iid = $_GET['uid'];
// 		} elseif (!empty($_POST['uid'])) {
// 			$iid = $_POST['uid'];
// 		} else {
// 			include_once('functions/error_functions.php');
// 			trigger_error('No user selected!',E_USER_ERROR);
// 		}

// 		if (!empty($_GET['profile_page'])) {
// 			$profile_page = $_GET['profile_page'];
// 		}  else {
// 			$profile_page = 'account';
// 		}

		$user_item = $this->_environment->getCurrentUserItem();
		$current_context = $this->_environment->getCurrentContextItem();
		$current_portal_item = $this->_environment->getCurrentPortalItem();

// 		// Get the translator object
// 		$translator = $environment->getTranslationObject();

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
			// 				$portal_user_item = $user_item->getRelatedCommSyUserItem();
			// 				$portal_user_item->reject();
			// 				$portal_user_item->save();
			// 				$session = $environment->getSessionItem();
			// 				$session_manager = $environment->getSessionManager();
			// 				$session_manager->delete($session->getSessionID());
			// 				unset($session);
			// 				unset($session_manager);
			// 				unset($portal_user_item);
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
					
					case 'account_delete':
						if($this->_popup_controller->checkFormData('delete')) {
							$authentication = $this->_environment->getAuthenticationObject();
							$current_user = $this->_environment->getCurrentUserItem();
							
							// TODO:...
							
							// set return
							$this->_popup_controller->setSuccessfullItemIDReturn($current_user->getItemID());
						}
						break;
						
					case 'account':
						if($this->_popup_controller->checkFormData('account')) {
							$authentication = $this->_environment->getAuthenticationObject();
							
							// password
							if(!empty($form_data['new_password'])) {
								$auth_manager = $authentication->getAuthManager($current_user->getAuthSource());
								$auth_manager->changePassword($form_data['user_id'], $form_data['new_password']);
								$error_number = $auth_manager->getErrorNumber();
								
								if(!empty($error_number)) {
									// TODO:$error_string .= $translator->getMessage('COMMON_ERROR_DATABASE').$error_number.'<br />';
								}
							}
							
							if(!$this->_environment->inPortal()) $user = $this->_environment->getPortalUserItem();
							else $user = $this->_environment->getCurrentUserItem();
							
							// user id
							if(!empty($form_data['user_id']) && $form_data['user_ID'] != $user->getUserID()) {
								if($authentication->changeUserID($form_data['user_id'], $user)) {
									$session_manager = $this->_environment->getSessionManager();
									$session = $this->_environment->getSessionItem();
									
									$session_id_old = $session->getSessionID();
									$session_manager->delete($session_id_old, true);
									$session->createSessionID($form_data['user_id']);
									
									$cookie = $session->getValue('cookie');
									if($cookie == 1) $session->setValue('cookie', 2);
									
									$session_manager->save($session);
									unset($session_manager);
									
									$user->setUserID($form_data['user_id']);
									require_once('functions/misc_functions.php');
									plugin_hook('user_save', $portal_user);
								}
							} else {
								// $success_1 = true
							}
							
							$save = false;
							
							// language
							if(!empty($form_data['language']) && $form_data['language'] != $user->getLanguage()) {
								$user->setLanguage($form_data['language']);
								$save = true;
								
								if($this->_environment->inPrivateRoom()) {
									$current_user->setLanguage($form_data['language']);
									$current_user->save();
								}
							}
							
							// mail settings
							if(!empty($form_data['mail_account'])) {
								if($user->getAccountWantMail() == 'no') {
									$user->setAccountWantMail('yes');
									$save = true;
								}
							} else {
								if($user->getAccountWantMail() == 'yes') {
									$user->setAccountWantMail('no');
									$save = true;
								}
							}
							
							if(!empty($form_data['mail_room'])) {
								if($user->getOpenRoomWantMail() == 'no') {
									$user->setOpenRoomWantMail('yes');
									$save = true;
								}
							} else {
								if($user->getOpenRoomWantMail() == 'yes') {
									$user->setOpenRoomWantMail('no');
									$save = true;
								}
							}
							
							$change_name = false;
							
							// forname
							if(!empty($form_data['forname']) && $user->getFirstName() != $form_data['forname']) {
								$user->setFirstName($form_data['forname']);
								$change_name = true;
								$save = true;
							}
							
							// surname
							if(!empty($form_data['surname']) && $user->getLastName() != $form_data['surname']) {
								$user->setLastName($form_data['surname']);
								$change_name = true;
								$save = true;
							}
							
							// new upload
							if(isset($form_data['new_upload'])) {
								if($form_data['new_upload'] == 'on') $user->turnNewUploadOn();
								else $user->turnNewUploadOff();
								
								$save = true;
							}
							
							// auto save
							if(!empty($form_data['auto_save'])) {
								if($form_data['auto_save'] == 'on') $user->turnAutoSaveOn();
								else $user->turnAutoSaveOff();
								
								$save = true;
							}
							
						    global $c_email_upload;
						    if ($c_email_upload ) {
						       $own_room = $user->getOwnRoom();
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
								$user->save();
							} else {
								// $success_2 = true;
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
								
								$user->changeRelatedUser($dummy_user);
							}
							
							// set return
                			$this->_popup_controller->setSuccessfullItemIDReturn($user->getItemID());
						}
						break;

					/**** USER PICTURE ****/
					case 'user_picture':
						if($this->_popup_controller->checkFormData('user_picture')) {
							/* handle user picture upload */
							if(!empty($additional["fileInfo"])) {
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
								$filename_info = pathinfo($targetfile);
								$filename = 'cid' . $this->_environment->getCurrentContextID() . '_' . $user_item->getItemID() . '.' . $filename_info['extension'];

								// copy file and set picture
								$disc_manager = $this->_environment->getDiscManager();

								$disc_manager->copyFile($targetfile, $filename, true);
								$user_item->setPicture($filename);

								$portal_user = $user_item->getRelatedCommSyUserItem();
								if(isset($portal_user)) {
									if($disc_manager->copyImageFromRoomToRoom($filename, $portal_user->getContextID())) {
										$value_array = explode('_', $filename);

										$old_room_id = $value_array[0];
										$old_room_id = str_replace('cid', '', $old_room_id);
										$valu_array[0] = 'cid' . $portal_user->getContextID();
										$new_picture_name = implode('_', $value_array);

										$portal_user->setPicture($new_picture_name);

										$portal_user->save();
									}
								}

								// save
								$user_item->save();
							}

							// set return
							$this->_popup_controller->setSuccessfullDataReturn($filename);
						}
						break;

					/**** USER ****/
					case 'user':
						if($this->_popup_controller->checkFormData('user')) {

							// 						if ( $correct
							// 								and empty($_FILES['upload']['tmp_name'])
							// 								and !empty($_POST['hidden_upload_name'])
							// 						) {
							// 							$session_item = $environment->getSessionItem();
							// 							if ( isset($session_item) ) {
							// 								$_FILES['upload']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_temp_name');
							// 								$_FILES['upload']['name']     = $session_item->getValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_name');
							// 								$session_item->unsetValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_temp_name');
							// 								$session_item->unsetValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_name');
							// 							}
							// 						}

							global $c_virus_scan;
							if(!isset($c_virus_scan) || !c_virus_scan /*or empty($_FILES['upload']['tmp_name'])
							// 										or empty($_FILES['upload']['name'])
							// 										or page_edit_virusscan_isClean($_FILES['upload']['tmp_name'],$_FILES['upload']['name'])*/) {
								$portal_user = $user_item->getRelatedCommSyUserItem();

								function setValue($user_item, $portal_user_item, $method, $value) {
									if(isset($value) && !empty($value)) {
										// set for user
										call_user_func_array(array($user_item, $method), array($value));

										// set for portal user
										call_user_func_array(array($portal_user_item, $method), array($value));
									}
								}

								setValue($user_item, $portal_user, 'setTitle', $form_data['title']);
								setValue($user_item, $portal_user, 'setBirthday', $form_data['birthday']);

								setValue($user_item, $portal_user, 'setEmail', $form_data['mail']);
								if($portal_user->hasToChangeEmail()) {
									$portal_user_item->unsetHasToChangeEmail();
									$form_data['mail_all'] = 1;
								}

								setValue($user_item, $portal_user, 'setTelephone', $form_data['telephone']);
								setValue($user_item, $portal_user, 'setCellularphone', $form_data['cellularphone']);
								setValue($user_item, $portal_user, 'setStreet', $form_data['street']);
								setValue($user_item, $portal_user, 'setZipcode', $form_data['zipcode']);
								setValue($user_item, $portal_user, 'setCity', $form_data['city']);
								setValue($user_item, $portal_user, 'setRoom', $form_data['room']);
								setValue($user_item, $portal_user, 'setOrganisation', $form_data['organisation']);
								setValue($user_item, $portal_user, 'setPosition', $form_data['position']);
								setValue($user_item, $portal_user, 'setICQ', $form_data['icq']);
								setValue($user_item, $portal_user, 'setMSN', $form_data['msn']);
								setValue($user_item, $portal_user, 'setSkype', $form_data['skype']);
								setValue($user_item, $portal_user, 'setYahoo', $form_data['yahoo']);
								setValue($user_item, $portal_user, 'setJabber', $form_data['jabber']);
								setValue($user_item, $portal_user, 'setHomepage', $form_data['homepage']);
								setValue($user_item, $portal_user, 'setDescription', $form_data['description']);

								// delete picture handling
								if(isset($form_data['delete_picture']) && $user_item->getPicture()) {
									$disc_manager = $this->_environment->getDiscManager();

									// unlink file
									if($disc_manager->existsFile($user_item->getPicture())) $disc_manager->unlinkFile($user_item->getPicture());

									// set non picture
									$user_item->setPicture('');
									if(isset($portal_user)) $portal_user->setPicture('');
								}

								// set modificator and modification date
								$user_item->setModificationDate(getCurrentDateTimeInMySQL());
								$portal_user->setModificationDate(getCurrentDateTimeInMySQL());

								// save
								$user_item->save();
								$portal_user->save();

								// 							if (isset($_POST['want_mail_get_account'])) {
								// 								$user_item->setAccountWantMail($_POST['want_mail_get_account']);
								// 							}



								// 							// email visibility
								// 							if (isset($_POST['email_visibility']) and !empty($_POST['email_visibility'])) {
								// 								$user_item->setEmailNotVisible();
								// 							} else {
								// 								$user_item->setEmailVisible();
								// 							}

								/* change all option */
								// get a dummy user
								$user_manager = $this->_environment->getUserManager();
								$dummy_user = $user_manager->getNewItem();

								function setChangeAllValue($user_item, $dummy_user_item, $method_set, $method_get, $checked) {
									if(isset($checked)) {
										$value = call_user_func_array(array($user_item, $method_get), array());

										if(empty($value)) $value = -1;

										call_user_func_array(array($dummy_user, $method_set), array($value));
									}
								}

								setChangeAllValue($user_item, $dummy_user, 'setTitle', 'getTitle', $form_data['title_all']);
								setChangeAllValue($user_item, $dummy_user, 'setBirthday', 'getBirthday', $form_data['birthday_all']);

								setChangeAllValue($user_item, $dummy_user, 'setEmail', 'getEmail', $form_data['mail_all']);
								if(isset($form_data['mail_all'])) {
									if(!$user_item->isEmailVisible()) $dummy_user->setEmailNotVisible();
									else $dummy_user->setEmailVisible();
								}

								setChangeAllValue($user_item, $dummy_user, 'setTelephone', 'getTelephone', $form_data['telephone_all']);
								setChangeAllValue($user_item, $dummy_user, 'setCellularphone', 'getCellularphone', $form_data['cellularphone_all']);
								setChangeAllValue($user_item, $dummy_user, 'setStreet', 'getStreet', $form_data['street_all']);
								setChangeAllValue($user_item, $dummy_user, 'setZipcode', 'getZipcode', $form_data['zipcode_all']);
								setChangeAllValue($user_item, $dummy_user, 'setCity', 'getCity', $form_data['city_all']);
								setChangeAllValue($user_item, $dummy_user, 'setRoom', 'getRoom', $form_data['room_all']);
								setChangeAllValue($user_item, $dummy_user, 'setOrganisation', 'getOrganisation', $form_data['organisation_all']);
								setChangeAllValue($user_item, $dummy_user, 'setPosition', 'getPosition', $form_data['position_all']);
								setChangeAllValue($user_item, $dummy_user, 'setICQ', 'getICQ', $form_data['messenger_all']);
								setChangeAllValue($user_item, $dummy_user, 'setMSN', 'getMSN', $form_data['messenger_all']);
								setChangeAllValue($user_item, $dummy_user, 'setSkype', 'getSkype', $form_data['messenger_all']);
								setChangeAllValue($user_item, $dummy_user, 'setYahoo', 'getYahoo', $form_data['messenger_all']);
								setChangeAllValue($user_item, $dummy_user, 'setJabber', 'getJabber', $form_data['messenger_all']);
								setChangeAllValue($user_item, $dummy_user, 'setHomepage', 'getHomepage', $form_data['homepage_all']);
								setChangeAllValue($user_item, $dummy_user, 'setDescription', 'getDescription', $form_data['description_all']);


								// 								if (isset($_POST['picture_change_all'])) {
								// 									$value = $user_item->getPicture();
								// 									if (empty($value)) {
								// 										$value = -1;
								// 									}
								// 									$dummy_user->setPicture($value);
								// 								}
								$user_item->changeRelatedUser($dummy_user);


								// 							//Add modifier to all users who ever edited this item
								// 							$manager = $environment->getLinkModifierItemManager();
								// 							$manager->markEdited($user->getItemID());

								// 							// redirect
								// 							$params = $environment->getCurrentParameterArray();
								// 							if ($is_saved){
								// 								$params['is_saved'] = true;
								// 							}
								// 							redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
							}


							// set return
                			$this->_popup_controller->setSuccessfullItemIDReturn($user_item->getItemID());
						}
						// init data display
						// 					if (!empty($_POST)) {
						// 						if ( !empty($_FILES) ) {
						// 							if ( !empty($_FILES['upload']['tmp_name']) ) {
						// 								$new_temp_name = $_FILES['upload']['tmp_name'].'_TEMP_'.$_FILES['upload']['name'];
						// 								move_uploaded_file($_FILES['upload']['tmp_name'],$new_temp_name);
						// 								$_FILES['upload']['tmp_name'] = $new_temp_name;
						// 								$session_item = $environment->getSessionItem();
						// 								if ( isset($session_item) ) {
						// 									$current_iid = $environment->getCurrentContextID();
						// 									$session_item->setValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_temp_name',$new_temp_name);
						// 									$session_item->setValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_name',$_FILES['upload']['name']);
						// 								}
						// 								//resizing the userimage to a maximum width of 150px
						// 								// + keeping a set ratio
						// 								$srcfile = $_FILES['upload']['tmp_name'];
						// 								$target = $_FILES['upload']['tmp_name'];
						// 								$size = getimagesize($srcfile);
						// 								$x_orig= $size[0];
						// 								$y_orig= $size[1];
						// 								//$verhaeltnis = $x_orig/$y_orig;
						// 								$verhaeltnis = $y_orig/$x_orig;
						// 								$max_width = 150;
						// 								//$ratio = 1.618; // Goldener Schnitt
						// 								//$ratio = 1.5; // 2:3
						// 								$ratio = 1.334; // 3:4
						// 								//$ratio = 1; // 1:1
						// 								if($verhaeltnis < $ratio){
						// 									// Breiter als 1:$ratio
						// 									$source_width = ($size[1] * $max_width) / ($max_width * $ratio);
						// 									$source_height = $size[1];
						// 									$source_x = ($size[0] - $source_width) / 2;
						// 									$source_y = 0;
						// 								} else {
						// 									// Höher als 1:$ratio
						// 									$source_width = $size[0];
						// 									$source_height = ($size[0] * ($max_width * $ratio)) / ($max_width);
						// 									$source_x = 0;
						// 									$source_y = ($size[1] - $source_height) / 2;
						// 								}
						// 								switch ($size[2]) {
						// 									case '1':
						// 										$im = imagecreatefromgif($srcfile);
						// 										break;
						// 									case '2':
						// 										$im = imagecreatefromjpeg($srcfile);
						// 										break;
						// 									case '3':
						// 										$im = imagecreatefrompng($srcfile);
						// 										break;
						// 								}
						// 								//$newimg = imagecreatetruecolor($show_width,$show_height);
						// 								//imagecopyresampled($newimg, $im, 0, 0, 0, 0, $show_width, $show_height, $size[0], $size[1]);
						// 								$newimg = imagecreatetruecolor($max_width,($max_width * $ratio));
						// 								imagecopyresampled($newimg, $im, 0, 0, $source_x, $source_y, $max_width, ceil($max_width * $ratio), $source_width, $source_height);
						// 								imagepng($newimg,$target);
						// 								imagedestroy($im);
						// 								imagedestroy($newimg);
						// 							}
						// 							$values = array_merge($_POST,$_FILES);
						// 						} else {
						// 							$values = $_POST;
						// 						}
						// 						$form->setFormPost($values);
						// 						if (!empty($_POST['is_moderator'])) {
						// 							$form->setIsModerator(true);
						// 						} else {
						// 							$form->setIsModerator(false);
						// 						}
						// 						if (!empty($_POST['with_picture'])) {
						// 							$form->setWithPicture(true);
						// 						} else {
						// 							$form->setWithPicture(false);
						// 						}
						// 					}

						// 					// Back from attaching groups
						// 					// ??? IJ 22.05.2009
						// 					elseif ( $backfrom == CS_GROUP_TYPE ) {
						// 						$session_post_vars = $session->getValue($iid.'_post_vars'); // Must be called before attach_return(...)
						// 						$attach_ids = attach_return(CS_GROUP_TYPE, $iid);
						// 						$with_anchor = true;
						// 						$session_post_vars[CS_GROUP_TYPE] = $attach_ids;
						// 						$form->setFormPost($session_post_vars);
						// 					}

						// 					// first call
						// 					elseif (!empty($iid) and $iid != 'NEW') { // change existing user
						// 						$user_manager = $environment->getUserManager();
						// 						$user_item = $user_manager->getItem($iid);

						// 						if(isset($_GET['show_profile']) && $_GET['show_profile'] == 'yes') {
						// 							$user_manager->setContextLimit($environment->getCurrentPortalID());
						// 							$user_manager->setUserIDLimit($user_item->getUserID());
						// 							$user_manager->select();
						// 							$list = $user_manager->get();
						// 							$user_manager->resetLimits();
						// 							if($list->isNotEmpty() && $list->getCount() == 1) {
						// 								$user_item = $list->getFirst();
						// 							}
						// 						}

						// 						$form->setItem($user_item);
						// 						$form->setIsModerator($current_user->isModerator());
						// 						$picture = $user_item->getPicture();
						// 						if (!empty($picture)) {
						// 							$form->setWithPicture(true);
						// 						}
						// 					}
						// 					$form->prepareForm();
						// 					$form->loadValues();
						break;

					/**** NEWSLETTER ****/
					case 'newsletter':
						if($this->_popup_controller->checkFormData('newsletter')) {
							$room_item = $user_item->getOwnRoom();

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
			// 				if (!$user_item->mayEditRegular($current_user)) {
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

		// set user item
		if($this->_environment->inCommunityRoom() || $this->_environment->inProjectRoom()) {
			$this->_user = $this->_environment->getPortalUserItem();
		} else {
			$this->_user = $this->_environment->getCurrentUserItem();
		}

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
				array('name' => 'upload', 'type' => 'radio', 'mandatory' => true),
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
		$current_user = $this->_environment->getCurrentUserItem();
		$current_context = $this->_environment->getCurrentContextItem();
		$portal_user = $this->_environment->getPortalUserItem();

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

		// form information
		$form_information = array();
		$form_information['account'] = $this->getAccountInformation();
		$form_information['user'] = $this->getUserInformation();
		$form_information['newsletter'] = $this->getNewsletterInformation();
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
		$return['new_upload'] = ($this->_user->isNewUploadOn()) ? true : false;
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

		return $return;
	}

	private function getUserInformation() {
		$return = array();

		// get data from database
		$return['title'] = $this->_user->getTitle();
		$return['birthday'] = $this->_user->getBirthday();
		$return['picture'] = $this->_environment->getCurrentUserItem()->getPicture();
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
}