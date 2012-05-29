<?php
class cs_popup_configuration_controller {
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
	
	public function save($form_data, $additional) {
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
				$tab = $additional['tab'];
				
				switch($tab) {
					/**** ACCOUNT ****/
					case 'account':
						if(isset($form_data['merge'])) {
							if($this->_popup_controller->checkFormData('merge')) {
								$authentication = $this->_environment->getAuthenticationObject();
								$current_user = $this->_environment->getCurrentUserItem();
								
								if(isset($form_data['auth_source'])) $auth_source_old = $form_data['auth_source'];
								else $auth_source_old = $current_context->getAuthDefault();
								
								$authentication->mergeAccount($current_user->getUserID(), $current_user->getAuthSource(), $form_data['merge_user_id'], $auth_source_old);
								
								// set return
                				$this->_popup_controller->setSuccessfullItemIDReturn($current_user->getItemID());
							}
						} else {
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
									if($form_data['new_upload'] == 'yes') $user->turnNewUploadOn();
									elseif($form_data['new_upload'] == 'no') $user->turnNewUploadOff();
									
									$save = true;
								}
								
								if(!empty($form_data['auto_save'])) {
									if($form_data['auto_save'] == 'yes') $user->turnAutoSaveOn();
									elseif($form_data['auto_save'] == 'no') $user->turnAutoSaveOff();
									
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
						}
						break;
					
					/**** USER PICTURE ****/
					case 'user_picture':
						if($this->_popup_controller->checkFormData('user_picture')) {
							/* handle user picture upload */
							if(!empty($_FILES['form_data']['tmp_name'])) {
								// rename temp file
								$new_temp_name = $_FILES['form_data']['tmp_name']['picture'] . '_TEMP_' . $_FILES['form_data']['name']['picture'];
								move_uploaded_file($_FILES['form_data']['tmp_name']['picture'], $new_temp_name);
								$_FILES['form_data']['tmp_name']['picture'] = $new_temp_name;
								
								$session_item = $this->_environment->getSessionItem();
								if(isset($session_item)) {
									$current_iid = $this->_environment->getCurrentContextID();
									//$session_item->setValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_temp_name',$new_temp_name);
									//$session_item->setValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_name',$_FILES['upload']['name']);
								}
								
								// resize image to a maximum width of 150px and keep ratio
								$srcfile = $_FILES['form_data']['tmp_name']['picture'];
								$target = $_FILES['form_data']['tmp_name']['picture'];
								
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
								imagepng($newimg, $target);
								
								// clean up
								imagedestroy($im);
								imagedestroy($newimg);
								
								// determ new file name
								$filename_info = pathinfo($_FILES['form_data']['name']['picture']);
								$filename = 'cid' . $this->_environment->getCurrentContextID() . '_' . $user_item->getItemID() . '.' . $filename_info['extension'];
								
								// copy file and set picture
								$disc_manager = $this->_environment->getDiscManager();
								
								$disc_manager->copyFile($_FILES['form_data']['tmp_name']['picture'], $filename, true);
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
               				$this->_popup_controller->setSuccessfullItemIDReturn($user_item->getItemID());
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
									else $dummy_user->setEMailVisibile();
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
	
	public function initPopup() {
		
		/*
		 * $current_context_item = $this->_environment->getCurrentContextItem();

      /********Zuordnung********//*
      $community_room_array = array();
      // links to community room
      $current_portal = $this->_environment->getCurrentPortalItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $community_list = $current_portal->getCommunityList();
      $community_room_array = array();
      $temp_array['text'] = '*'.$this->_translator->getMessage('PREFERENCES_NO_COMMUNITY_ROOM');
      $temp_array['value'] = '-1';
      $community_room_array[] = $temp_array;
      $temp_array['text'] = '--------------------';
      $temp_array['value'] = 'disabled';
      $community_room_array[] = $temp_array;
      unset($temp_array);
      if ($community_list->isNotEmpty()) {
         $community_item = $community_list->getFirst();
         while ($community_item) {
            $temp_array = array();
            if ($community_item->isAssignmentOnlyOpenForRoomMembers() ){
               if ( !$community_item->isUser($current_user)) {
                  $temp_array['text'] = $community_item->getTitle();
                  $temp_array['value'] = 'disabled';
               }else{
                  $temp_array['text'] = $community_item->getTitle();
                  $temp_array['value'] = $community_item->getItemID();
               }
            }else{
               $temp_array['text'] = $community_item->getTitle();
               $temp_array['value'] = $community_item->getItemID();
            }
            $community_room_array[] = $temp_array;
            unset($temp_array);
            $community_item = $community_list->getNext();
         }
      }
      $this->_community_room_array = $community_room_array;
      $community_room_array = array();

      if ($this->_environment->inProjectRoom()){
         if (!empty($this->_session_community_room_array)) {
            foreach ( $this->_session_community_room_array as $community_room ) {
               $temp_array['text'] = $community_room['name'];
               $temp_array['value'] = $community_room['id'];
               $community_room_array[] = $temp_array;
            }
         } else{
            $community_room_list = $current_context_item->getCommunityList();
            if ($community_room_list->getCount() > 0) {
               $community_room_item = $community_room_list->getFirst();
               while ($community_room_item) {
                  $temp_array['text'] = $community_room_item->getTitle();
                  $temp_array['value'] = $community_room_item->getItemID();
                  $community_room_array[] = $temp_array;
                  $community_room_item = $community_room_list->getNext();
               }
            }
         }
         $this->_shown_community_room_array = $community_room_array;
      }


      


      /****Zeittakte*****/ /*
      // time pulses
      $current_context = $this->_environment->getCurrentContextItem();
      $current_portal  = $this->_environment->getCurrentPortalItem();
      if (
            ( $current_context->isProjectRoom() and $this->_environment->inProjectRoom() )
            or ( $current_context->isProjectRoom()
                 and $this->_environment->inCommunityRoom()
                 and $current_context->showTime()
               )
            or ( $this->_environment->getCurrentModule() == CS_PROJECT_TYPE
                 and ( $this->_environment->inCommunityRoom() or $this->_environment->inPortal() )
                 and $current_context->showTime()
               )
            or ( $this->_environment->inGroupRoom()
                 and $current_portal->showTime()
               )
         ) {
         if ( $this->_environment->inPortal() ) {
            $portal_item = $current_context;
         } else {
            $portal_item = $current_context->getContextItem();
         }
         if ($portal_item->showTime()) {
                     $current_time_title = $portal_item->getTitleOfCurrentTime();
                     if (isset($this->_item)) {
                            $time_list = $this->_item->getTimeList();
                            if ($time_list->isNotEmpty()) {
                               $time_item = $time_list->getFirst();
                               $linked_time_title = $time_item->getTitle();
                            }
                     }
                     if ( !empty($linked_time_title)
                          and $linked_time_title < $current_time_title
                            ) {
                             $start_time_title = $linked_time_title;
                     } else {
                             $start_time_title = $current_time_title;
                     }
                     $time_list = $portal_item->getTimeList();
                     if ($time_list->isNotEmpty()) {
                             $time_item = $time_list->getFirst();
                             while ($time_item) {
                                     if ($time_item->getTitle() >= $start_time_title) {
                                             $temp_array = array();
                                             $temp_array['text'] = $this->_translator->getTimeMessage($time_item->getTitle());
                                             $temp_array['value'] = $time_item->getItemID();
                                             $this->_time_array2[] = $temp_array;
                                     }
                                     $time_item = $time_list->getNext();
                             }
                     }

                         // continuous
                     $temp_array = array();
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_CONTINUOUS');
                     $temp_array['value'] = 'cont';
                     $this->_time_array2[] = $temp_array;

                     $this->_with_time_array2 = true;
                  }
          }

      /*******Farben********/ /*
      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_DEFAULT');
      $temp_array['value'] = 'COMMON_COLOR_DEFAULT';
      $this->_array_info_text[] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = '-----';
      $temp_array['value'] = '-1';
      $this->_array_info_text[] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_1');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_1';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_1')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_2');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_2';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_2')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_3');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_3';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_3')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_4');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_4';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_4')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_5');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_5';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_5')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_6');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_6';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_6')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_7');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_7';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_7')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_8');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_8';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_8')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_9');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_9';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_9')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_10');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_10';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_10')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_11');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_11';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_11')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_12');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_12';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_12')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_13');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_13';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_13')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_14');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_14';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_14')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_15');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_15';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_15')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_16');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_16';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_16')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_17');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_17';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_17')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_18');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_18';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_18')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_19');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_19';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_19')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_20');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_20';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_20')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_21');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_21';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_21')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_22');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_22';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_22')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_23');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_23';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_23')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_24');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_24';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_24')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_25');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_25';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_25')] = $temp_array;
      
      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_26');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_26';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_26')] = $temp_array;

      ksort($array_info_text_temp);
      foreach($array_info_text_temp as $entry){
         $this->_array_info_text[] = $entry;
      }
      $temp_array = array();
      $temp_array['text']  = '-----';
      $temp_array['value'] = '-1';
      $this->_array_info_text[] = $temp_array;
      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_OWN');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_OWN';
      $this->_array_info_text[] = $temp_array;
		 */
		
		
		
		
		
		
		/*
		
		
		
		$current_portal_item = $this->_environment->getCurrentPortalItem();
		
		/*
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
		
		*/
		
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
				array('name' => 'forename', 'type' => 'text', 'mandatory' => true),
				array('name' => 'surname', 'type' => 'text', 'mandatory' => true),
				array('name' => 'user_id', 'type' => 'text', 'mandatory' => true),
				array('name' => 'old_password', 'type' => 'text', 'mandatory' => false),
				array('name' => 'new_password', 'type' => 'text', 'mandatory' => false, 'same_as' => 'new_password_confirm'),
				array('name' => 'new_password_confirm', 'type' => 'text', 'mandatory' => true),
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
		$portal_user = $this->_environment->getPortalUserItem();
		
		// general information
		$general_information = array();
		
		// max upload size
		$val = ini_get('upload_max_filesize');
		$val = trim($val);
		$last = $val[mb_strlen($val) - 1];
		switch($last) {
			case 'k':
			case 'K':
				$val *= 1024;
				break;
			case 'm':
			case 'M':
				$val *= 1048576;
				break;
		}
		$meg_val = round($val / 1048576);
		$general_information['max_upload_size'] = $meg_val;
		
		$this->_popup_controller->assign('popup', 'general', $general_information);
		
		// room information
		$this->_popup_controller->assign('popup', 'room', $this->getRoomInformation());
	}
	
	private function getRoomInformation() {
		$return = array();
		
		$current_context = $this->_environment->getCurrentContextItem();
		$translator = $this->_environment->getTranslationObject();
		
		$return['room_name'] = $current_context->getTitle();
		$return['room_show_name'] = $current_context->showTitle();
		
		// language
		$languages = array();
		
		$languages[] = array(
			'text'		=> $translator->getMessage('CONTEXT_LANGUAGE_USER'),
			'value'		=> 'user'
		);
		
		$languages[] = array(
			'text'		=> '-------',
			'value'		=> 'disabled',
			'disabled'	=> true
		);
		
		$language_array = $this->_environment->getAvailableLanguageArray();
		foreach($language_array as $entry) {
			switch ( mb_strtoupper($entry, 'UTF-8') ){
				case 'DE':
					$languages[] = array(
						'text'		=> $translator->getMessage('DE'),
						'value'		=> $entry
					);
					break;
				case 'EN':
					$languages[] = array(
						'text'		=> $translator->getMessage('EN'),
						'value'		=> $entry
					);
					break;
				default:
					break;
			}
		}
		$return['languages'] = $languages;
		$return['language'] = $current_context->getLanguage();
		
		// logo
		if($current_context->getLogoFilename()) {
			$return['logo'] = $current_context->getLogoFilename();
		}
		
		
		/**********Logo**********/ /*
		$this->_with_bg_image = $current_context_item->getBGImageFilename();
		
		/*
		 * $context_item = $this->_environment->getCurrentContextItem();

      $this->_values = array();
      $color = $context_item->getColorArray();
      $temp_array = array();
      $temp_array['color_1'] = $color['tabs_background'];
      $temp_array['color_2'] = $color['tabs_focus'];
      $temp_array['color_3'] = $color['tabs_title'];
      $temp_array['color_31'] = $color['tabs_separators'];
      $temp_array['color_32'] = $color['tabs_dash'];
      $temp_array['color_4'] = $color['content_background'];
      $temp_array['color_5'] = $color['boxes_background'];
      $temp_array['color_6'] = $color['hyperlink'];
      $temp_array['color_7'] = $color['list_entry_even'];
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if (empty($this->_values['color_choice'])){
            $this->_values['color_choice'] = 'COMMON_COLOR_'.mb_strtoupper($color['schema'], 'UTF-8');
         }
         if ($this->_values['color_choice']=='COMMON_COLOR_SCHEMA_OWN'){
            for ($i=1; $i<8; $i++){
               if ( !empty($this->_form_post['color_'.$i]) ){
                  $this->_values['color_'.$i] = $this->_form_post['color_'.$i];
               }else{
                  $this->_values['color_'.$i] = $temp_array['color_'.$i];
               }
            }
            if(!empty($this->_form_post['color_31'])) {
               $this->_values['color_31'] = $ths->_form_post['color_31'];
            } else {
               $this->_values['color_31'] = $temp_array['color_31'];
            }
            if(!empty($this->_form_post['color_32'])) {
               $this->_values['color_32'] = $ths->_form_post['color_32'];
            } else {
               $this->_values['color_32'] = $temp_array['color_32'];
            }
         }
      } else {
         $color_array = $context_item->getColorArray();
         $this->_values['color_choice'] = 'COMMON_COLOR_'.mb_strtoupper($color['schema'], 'UTF-8');
         $this->_values['color_1'] = $color['tabs_background'];
         $this->_values['color_2'] = $color['tabs_focus'];
         $this->_values['color_3'] = $color['tabs_title'];
         $this->_values['color_31'] = $color['tabs_separators'];
         $this->_values['color_32'] = $color['tabs_dash'];
         $this->_values['color_5'] = $color['boxes_background'];
         $this->_values['color_7'] = $color['list_entry_even'];
         $this->_values['color_6'] = $color['hyperlink'];
         $this->_values['color_4'] = $color['content_background'];
         if ( $context_item->isPrivateRoom() ) {
            if ( $context_item->getTitle() == 'PRIVATEROOM' ) {
               $this->_values['title'] = $this->_translator->getMessage('COMMON_PRIVATEROOM');
            } elseif ( $context_item->isTemplate() ) {
               $this->_values['title'] = $context_item->getTitlePure();
            }
         }
         if ($context_item->isAssignmentOnlyOpenForRoomMembers()) {
            $this->_values['room_assignment'] = 'closed';
         } else {
            $this->_values['room_assignment'] = 'open';
         }
      }
      
      if ($context_item->isRSSOn()) {
         $this->_values['rss'] = 'yes';
      } else {
         $this->_values['rss'] = 'no';
      }
      if ($context_item->getBGImageFilename()){
         $this->_values['bgimage'] = $context_item->getBGImageFilename();
      }
      if ($context_item->issetBGImageRepeat()){
         $this->_values['bg_image_repeat'] = '1';
      }

      if (
            ( $context_item->isA(CS_PROJECT_TYPE) and $this->_environment->inProjectRoom() )
            or ( $context_item->isA(CS_PROJECT_TYPE) and $this->_environment->inCommunityRoom() )
            or ( $context_item->isA(CS_GROUPROOM_TYPE) and $this->_environment->inGroupRoom() )
         ) {
         $portal_item = $this->_environment->getCurrentPortalItem();
         if ( $portal_item->showTime() ) {
            $time_list = $context_item->getTimeList();
            $mark_array = array();
            if ( $time_list->isNotEmpty() ) {
               $time_item = $time_list->getFirst();
               while ($time_item) {
                  $mark_array[] = $time_item->getItemID();
                  $time_item = $time_list->getNext();
               }
               if ($context_item->isContinuous()) {
                  $mark_array[] = 'cont';
               }
               $this->_values['time2'] = $mark_array;
               unset($mark_array);
            }
         }
      }

      if ($this->_environment->inProjectRoom()){
         $community_room_array = array();
         if (!empty($this->_session_community_room_array)) {
            foreach ( $this->_session_community_room_array as $community_room ) {
               $community_room_array[] = $community_room['id'];
            }
         }
         $community_room_list = $context_item->getCommunityList();
         if ($community_room_list->getCount() > 0) {
            $community_room_item = $community_room_list->getFirst();
            while ($community_room_item) {
               $community_room_array[] = $community_room_item->getItemID();
               $community_room_item = $community_room_list->getNext();
            }
         }
         if ( isset($this->_form_post['communityroomlist']) ) {
            $this->_values['communityroomlist'] = $this->_form_post['communityroomlist'];
         } else {
            $this->_values['communityroomlist'] = $community_room_array;
         }
      }

      $this->_values['description'] = $context_item->getDescription();
      
      global $c_email_upload;
      if ($c_email_upload && $this->_environment->inPrivateRoom()) {
         if ( isset($this->_form_post['email_to_commsy']) ) {
            $this->_values['email_to_commsy'] = $this->_form_post['email_to_commsy'];
         } else {
            $this->_values['email_to_commsy'] = $context_item->getEmailToCommSy();
         }
         
         if ( isset($this->_form_post['email_to_commsy_secret']) ) {
            $this->_values['email_to_commsy_secret'] = $this->_form_post['email_to_commsy_secret'];
         } else {
            $this->_values['email_to_commsy_secret'] = $context_item->getEmailToCommSySecret();
         }
      }
		 */
		
		return $return;
	}
}