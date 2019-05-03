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
		if(/*!$current_context->isOpen()*/false) {
		}

		// access granted
		else {
			if(false) {

			}

			// save user
			else {
				$tab = $additional['part'];

				switch($tab) {
					/**** ACCOUNT ****/
					case 'account_merge':
						if ( $this->_popup_controller->checkFormData('merge') )
						{
							
							$authentication = $this->_environment->getAuthenticationObject();
							
							global $c_annonymous_account_array;
							
							$currentUser = $this->_environment->getCurrentUserItem();
							if ( !empty($c_annonymous_account_array[mb_strtolower($currentUser->getUserID(), 'UTF-8') . '_' . $currentUser->getAuthSource()]) && $currentUser->isOnlyReadUser() )
							{
								$this->_popup_controller->setErrorReturn("1014", "anonymous account");
								exit;
							}
							else
							{
								if (
                                    strtolower($currentUser->getUserID()) == strtolower($form_data['merge_user_id']) &&
                                    (
                                        empty($form_data['auth_source']) ||
                                        $currentUser->getAuthSource() == $form_data['auth_source']
                                    )
                                ) {
									$this->_popup_controller->setErrorReturn("1015", "invalid account");
								}
								else
								{
									$user_manager = $this->_environment->getUserManager();
									$user_manager->setUserIDLimitBinary($form_data['merge_user_id']);
									
									$user_manager->select();
									$user = $user_manager->get();
									$first_user = $user->getFirst();
									
									$current_user = $this->_environment->getCurrentUserItem();

									if(!empty($first_user)){
										if(empty($form_data['auth_source'])){
											$authManager = $authentication->getAuthManager($current_user->getAuthSource());
										} else {
											$authManager = $authentication->getAuthManager($form_data['auth_source']);
										}
										if ( !$authManager->checkAccount($form_data['merge_user_id'], $form_data['merge_user_password']) )
										{
											$this->_popup_controller->setErrorReturn("1016", "authentication error");
											exit;
										}
									} else {
										$this->_popup_controller->setErrorReturn("1015", "invalid account");
										exit;
									}
								}
							}
							
							$currentUser = $this->_environment->getCurrentUserItem();
							
							if ( isset($form_data['auth_source']) )
							{
								$authSourceOld = $form_data['auth_source'];
							}
							else
							{
								$authSourceOld = $this->_environment->getCurrentPortalItem()->getAuthDefault();
							}
							
							ini_set('display_errors', 'on');
							error_reporting(E_ALL);

							$authentication->mergeAccount($currentUser->getUserID(), $currentUser->getAuthSource(), $form_data['merge_user_id'], $authSourceOld);
							
							// set return
							$this->_popup_controller->setSuccessfullItemIDReturn($currentUser->getItemID());
						}
						break;

					case "account_lock_room":
						$current_user = $this->_environment->getCurrentUserItem();

						$current_user->reject();
						$current_user->save();
                        // remove link from group room
                        if($current_context->isGroupRoom()) {
                            $group_item = $current_context->getLinkedGroupItem();
                            $group_item->removeMember($current_user->getRelatedUserItemInContext($group_item->getContextID()));
                        }

						// set return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_user->getItemID());
						break;

					case "account_delete_room":
						$current_user = $this->_environment->getCurrentUserItem();

						$current_user->delete();
						// remove link from group room
						if($current_context->isGroupRoom()) {
							$group_item = $current_context->getLinkedGroupItem();
                          	$group_item->removeMember($current_user->getRelatedUserItemInContext($group_item->getContextID()));
						}

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

						// return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_user->getItemID());
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

						// return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_user->getItemID());
						break;

					case 'account':
						if($this->_popup_controller->checkFormData('account')) {
							$authentication = $this->_environment->getAuthenticationObject();

							$currentUser = $this->_environment->getCurrentUserItem();
							
							// get portal user if in room context
							if ( !$this->_environment->inPortal() )
							{
								$portalUser = $this->_environment->getPortalUserItem();
							}
							else
							{
								$portalUser = $this->_environment->getCurrentUserItem();
							}
							
							$translator = $this->_environment->getTranslationObject();
							
							// Datenschutz
							if($current_portal_item->getPasswordGeneration() > 0){

								if(!$portalUser->isPasswordInGeneration(md5($form_data['new_password']))) {
									// password
									if(!empty($form_data['new_password'])) {
										$auth_manager = $authentication->getAuthManager($currentUser->getAuthSource());
										$auth_source = $currentUser->getAuthSource();
										$old_password = $auth_manager->getItem($form_data['user_id'])->getPasswordMD5();
										if($old_password == md5($form_data['old_password'])){
											$change_pw = true;
											// if password options are set, check password
											$auth_source_manager = $this->_environment->getAuthSourceManager();
											$auth_source_item = $auth_source_manager->getItem($currentUser->getAuthSource());
											
											$error_array = array();
											
											if($auth_source_item->getPasswordLength() > 0){
												if(strlen($form_data['new_password']) < $auth_source_item->getPasswordLength()) {
													$error_array[] = $translator->getMessage('PASSWORD_INFO_LENGTH',$auth_source_item->getPasswordLength());
													//$this->_popup_controller->setErrorReturn('1022', 'new password too short');
													$change_pw = false;
												}
											}
											if($auth_source_item->getPasswordSecureBigchar() == 1){
												if(!preg_match('~[A-Z]+~u', $form_data['new_password'])) {
													$error_array[] = $translator->getMessage('PASSWORD_INFO_BIG');
													//$this->_popup_controller->setErrorReturn('1023', 'new password no big character');
													$change_pw = false;
												}
											}
											if($auth_source_item->getPasswordSecureSmallchar() == 1){
												if(!preg_match('~[a-z]+~u', $form_data['new_password'])) {
													$error_array[] = $translator->getMessage('PASSWORD_INFO_SMALL');
													//$this->_popup_controller->setErrorReturn('1026', 'new password no small character');
													$change_pw = false;
												}
											}
											if($auth_source_item->getPasswordSecureNumber() == 1){
												if(!preg_match('~[0-9]+~u', $form_data['new_password'])) {
													$error_array[] = $translator->getMessage('PASSWORD_INFO_NUMBER');
													//$this->_popup_controller->setErrorReturn('1027', 'new password no number');
													$change_pw = false;
												}
											}
											if($auth_source_item->getPasswordSecureSpecialchar() == 1){
												if(!preg_match('~[^a-zA-Z0-9]+~u',$form_data['new_password'])){
													$error_array[] = $translator->getMessage('PASSWORD_INFO_SPECIAL');
													//$this->_popup_controller->setErrorReturn('1024', 'new password no special character');
													$change_pw = false;
												}
											}
								
											unset($auth_source);
											if($change_pw) {
												$portalUser->setPasswordExpireDate($current_portal_item->getPasswordExpiration());
												$portalUser->save();
												$auth_manager->changePassword($form_data['user_id'], $form_data['new_password']);
											} else {
												$this->_popup_controller->setErrorReturn('1022', $error_array);
											}
											
										} else {
											$error_array[] = $translator->getMessage('PASSWORD_OLD_NOT_EQUAL');
											$this->_popup_controller->setErrorReturn('1023', $error_array);
											#$this->_popup_controller->setErrorReturn('1009', 'password change error');
										}
										$error_number = $auth_manager->getErrorNumber();
		
										if(!empty($error_number)) {
											// TODO:$error_string .= $translator->getMessage('COMMON_ERROR_DATABASE').$error_number.'<br />';
										} else {
											$portalUser->setNewGenerationPassword($old_password);
										}
									}
								} else {
									$this->_popup_controller->setErrorReturn('1025', 'password generation error');
								}
							} else {
								if(!empty($form_data['new_password'])) {
									$auth_manager = $authentication->getAuthManager($currentUser->getAuthSource());
									$old_password = $auth_manager->getItem($form_data['user_id'])->getPasswordMD5();
									if($old_password == md5($form_data['old_password'])){
											$change_pw = true;
											// if password options are set, check password
											$auth_source_manager = $this->_environment->getAuthSourceManager();
											$auth_source_item = $auth_source_manager->getItem($currentUser->getAuthSource());
											
											$error_array = array();
											
											if($auth_source_item->getPasswordLength() > 0){
												if(strlen($form_data['new_password']) < $auth_source_item->getPasswordLength()) {
													$error_array[] = $translator->getMessage('PASSWORD_INFO_LENGTH',$auth_source_item->getPasswordLength()).'<br>';
													//$this->_popup_controller->setErrorReturn('1022', 'new password too short');
													$change_pw = false;
												}
											}
											if($auth_source_item->getPasswordSecureBigchar() == 1){
												if(!preg_match('~[A-Z]+~u', $form_data['new_password'])) {
													$error_array[] = $translator->getMessage('PASSWORD_INFO_BIG');
													//$this->_popup_controller->setErrorReturn('1023', 'new password no big character');
													$change_pw = false;
												}
											}
											if($auth_source_item->getPasswordSecureSmallchar() == 1){
												if(!preg_match('~[a-z]+~u', $form_data['new_password'])) {
													$error_array[] = $translator->getMessage('PASSWORD_INFO_SMALL');
													//$this->_popup_controller->setErrorReturn('1026', 'new password no small character');
													$change_pw = false;
												}
											}
											if($auth_source_item->getPasswordSecureNumber() == 1){
												if(!preg_match('~[0-9]+~u', $form_data['new_password'])) {
													$error_array[] = $translator->getMessage('PASSWORD_INFO_NUMBER');
													//$this->_popup_controller->setErrorReturn('1027', 'new password no number');
													$change_pw = false;
												}
											}
											if($auth_source_item->getPasswordSecureSpecialchar() == 1){
												if(!preg_match('~[^a-zA-Z0-9]+~u',$form_data['new_password'])){
													$error_array[] = $translator->getMessage('PASSWORD_INFO_SPECIAL');
													//$this->_popup_controller->setErrorReturn('1024', 'new password no special character');
													$change_pw = false;
												}
											}
											unset($auth_source);
											if($change_pw) {
												$portalUser->setPasswordExpireDate($current_portal_item->getPasswordExpiration());
												$portalUser->save();
												$auth_manager->changePassword($form_data['user_id'], $form_data['new_password']);
											} else {
												$this->_popup_controller->setErrorReturn('1022', $error_array);
											}
											
										} else {
											$error_array[] = $translator->getMessage('PASSWORD_OLD_NOT_EQUAL');
											$this->_popup_controller->setErrorReturn('1023', $error_array);
											#$this->_popup_controller->setErrorReturn('1008', 'password change error');
										}
									$error_number = $auth_manager->getErrorNumber();
								
									if(!empty($error_number)) {
										// TODO:$error_string .= $translator->getMessage('COMMON_ERROR_DATABASE').$error_number.'<br />';
									} else {
										$portalUser->setNewGenerationPassword($old_password);
									}
								}
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
										$currentUser->setUserID($form_data['user_id']);
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
							
							if(isset($form_data['mail_account'])){
								$currentUser->setAccountWantMail('yes');
								$currentUser->save();
								#$save = true;
							} else {
								$currentUser->setAccountWantMail('no');
								$currentUser->save();
								#$save = true;
							}
							
							if(isset($form_data['mail_room'])){
								$currentUser->setOpenRoomWantMail('yes');
								$currentUser->save();
								#$save = true;
							} else {
								$currentUser->setOpenRoomWantMail('no');
								$currentUser->save();
								#$save = true;
							}

							$change_name = false;
							
							$text_converter = $this->_environment->getTextConverter();
							
							$form_data['forname'] = $text_converter->sanitizeHTML($form_data['forname']);
							$form_data['surname'] = $text_converter->sanitizeHTML($form_data['surname']);
							
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
						    if ($c_email_upload && !$portalUser->isRoot()) {
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

					/**** IMPORT ****/
					case 'import':
						if($this->_popup_controller->checkFormData('upload_import_private_room')) {
                     if(!empty($additional["fileInfo"])) {
                        $temp_stamp = time();
                        rename($additional["fileInfo"]["file"], 'var/temp/upload_'.$temp_stamp.'.zip');
                        $zip = new ZipArchive;
                        $res = $zip->open('var/temp/upload_'.$temp_stamp.'.zip');
                        if ($res === TRUE) {
                           $zip->extractTo('var/temp/'.$temp_stamp);
                           $zip->close();
                           
                           $commsy_work_dir = getcwd();
                           chdir('var/temp/'.$temp_stamp);
                           foreach (glob("commsy_xml_export_import_*.xml") as $filename) {
                              $xml = simplexml_load_file($filename, null, LIBXML_NOCDATA);
                              //el($xml);
                              $dom = new DOMDocument('1.0');
                              $dom->preserveWhiteSpace = false;
                              $dom->formatOutput = true;
                              $dom->loadXML($xml->asXML());
                              //el($dom->saveXML());
                              
                              $options = array();
                              chdir($commsy_work_dir);
                              $room_manager = $this->_environment->getRoomManager();
                              $room_manager->import_item($xml, null, $options);
                              chdir('var/temp/'.$temp_stamp);
               
                              $files = scandir('.');
                              foreach($files as $file) {
                                 if (strpos($file, 'files') === 0) {
                                    $directory_name_array = explode('_', $file);
                                    $directory_old_id = $directory_name_array[1];
                                    $disc_manager = $this->_environment->getDiscManager();
                                    $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
                                    $directory_new_id = $options[$directory_old_id];
                                    if ($directory_new_id != '') {
                                       $disc_manager->setContextID($directory_new_id);
                                       $new_file_path = $disc_manager->getFilePath();
                                       chdir($file);
                                       $files_to_copy = glob('./*');
                                       foreach($files_to_copy as $file_to_copy){
                                          if (!(strpos($file, 'default_cs_gradient') === 0)) {
                                             $file_to_copy = str_ireplace('./', '', $file_to_copy);
                                             $file_name_array = explode('.', $file_to_copy);
                                             $file_old_id = $file_name_array[0];
                                             $file_new_id = $options[$file_old_id];
                                             if ($file_new_id != '') {
                                                $file_to_copy_temp = str_ireplace($file_old_id.'.', $file_new_id.'.', $file_to_copy);
                                                $file_to_copy_temp = './'.$file_to_copy_temp;
                                                $file_to_go = str_replace('./',$commsy_work_dir.'/'.$new_file_path, $file_to_copy_temp);
                                                copy($file_to_copy, $file_to_go);
                                             }
                                          }
                                       }
                                       chdir('..');
                                    }
                                 }
                              }
                           }
                           chdir($commsy_work_dir);
                        }
                     }
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
						$text_converter = $this->_environment->getTextConverter();
						$currentContext = $this->_environment->getCurrentContextItem();

						if ( $this->_popup_controller->checkFormData('user') )
						{
						    $tempPortalUser = null;
                            if ($currentContext->isPortal()) {
                                $tempPortalUser = $portalUser;
                            }

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

							setValue($currentUser, $tempPortalUser, 'setTitle', $text_converter->sanitizeHTML($form_data['title']));
							setValue($currentUser, $tempPortalUser, 'setBirthday', $text_converter->sanitizeHTML($form_data['birthday']));

							$email_old = $portalUser->getEmail();
							setValue($currentUser, $tempPortalUser, 'setEmail', $text_converter->sanitizeHTML($form_data['mail']));
							if ( $portalUser->hasToChangeEmail()
								  and $email_old != $form_data['mail']
								) {
								$portalUser->unsetHasToChangeEmail();
								$form_data['mail_all'] = 1;
							}
							unset($email_old);

							if($currentContext->isPortal()){
								if($form_data['mail_hide']){
									$portalUser->setDefaultMailNotVisible();
								} else {
									$portalUser->setDefaultMailVisible();
								}
								if($form_data['mail_hide_all']){
									$user_list = $currentUser->getRelatedUserList();

							        $user_item = $user_list->getFirst();
							        while($user_item) {
							        	if($form_data['mail_hide']){
							        		$user_item->setEmailNotVisible();
							        	} else {
							        		$user_item->setEmailVisible();
							        	}
							            
							            $user_item->save();
							            $user_item = $user_list->getNext();
							        }
							        $currentUser->setDefaultMailNotVisible();
							        $currentUser->save();

								}
							} else {
								if($form_data['mail_hide']){
									$currentUser->setEmailNotVisible();
								} else {
									$currentUser->setEmailVisible();
								}
								if($form_data['mail_hide_all']){
									$user_list = $currentUser->getRelatedUserList();

							        $user_item = $user_list->getFirst();
							        while($user_item) {
							        	if($form_data['mail_hide']){
							        		$user_item->setEmailNotVisible();
							        	} else {
							        		$user_item->setEmailVisible();
							        	}
							            
							            $user_item->save();
							            $user_item = $user_list->getNext();
							        }
							        $currentUser->setDefaultMailNotVisible();
							        $currentUser->save();	
								}
							}

							// im portal nur default wert
							// im raum default wert und raum wert?

							setValue($currentUser, $tempPortalUser, 'setTelephone', $text_converter->sanitizeHTML($form_data['telephone']));
							setValue($currentUser, $tempPortalUser, 'setCellularphone', $text_converter->sanitizeHTML($form_data['cellularphone']));
							setValue($currentUser, $tempPortalUser, 'setStreet', $text_converter->sanitizeHTML($form_data['street']));
							setValue($currentUser, $tempPortalUser, 'setZipcode', $text_converter->sanitizeHTML($form_data['zipcode']));
							setValue($currentUser, $tempPortalUser, 'setCity', $text_converter->sanitizeHTML($form_data['city']));
							setValue($currentUser, $tempPortalUser, 'setRoom', $text_converter->sanitizeHTML($form_data['room']));
							setValue($currentUser, $tempPortalUser, 'setOrganisation', $text_converter->sanitizeHTML($form_data['organisation']));
							setValue($currentUser, $tempPortalUser, 'setPosition', $text_converter->sanitizeHTML($form_data['position']));
							setValue($currentUser, $tempPortalUser, 'setICQ', $text_converter->sanitizeHTML($form_data['icq']));
							setValue($currentUser, $tempPortalUser, 'setMSN', $text_converter->sanitizeHTML($form_data['msn']));
							setValue($currentUser, $tempPortalUser, 'setSkype', $text_converter->sanitizeHTML($form_data['skype']));
							setValue($currentUser, $tempPortalUser, 'setYahoo', $text_converter->sanitizeHTML($form_data['yahoo']));
							setValue($currentUser, $tempPortalUser, 'setJabber', $text_converter->sanitizeHTML($form_data['jabber']));
							setValue($currentUser, $tempPortalUser, 'setHomepage', $text_converter->sanitizeHTML($form_data['homepage']));
							setValue($currentUser, $tempPortalUser, 'setDescription', $form_data['description']);

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
							$currentUser = $this->_environment->getCurrentUserItem();
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
							$currentUser = $this->_environment->getCurrentUserItem();
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

						      // plugins
						      elseif ( substr($additional['action'],0,7) == 'plugin_' ) {
						         $plugin = substr($additional['action'],7);

						         $plugin_class = $this->_environment->getPluginClass($plugin);
						         if ( !empty($plugin_class)
						              and method_exists($plugin_class,'isConfigurableInPortal')
						            ) {
						            if ( ( $this->_environment->inPortal()
						                   and $plugin_class->isConfigurableInPortal()
						                 )
						                 or
						                 ( !$this->_environment->inServer()
						                   and method_exists($plugin_class,'isConfigurableInRoom')
						                   and $plugin_class->isConfigurableInRoom(CS_PRIVATEROOM_TYPE)
						                 )
						                 or 
						                 (
						                 	!$this->_environment->inServer()
						                 	and method_exists($plugin_class,'isConfigurableInRoom')
						                 	and $plugin_class->isConfigurableInRoom()
						                 	and $plugin == 'voyeur'
						                 )
						               ) {
						               if ( !empty($form_data[$plugin.'_on'])
						                    and $form_data[$plugin.'_on'] == 'yes'
						                  ) {
						                  $room_item->setPluginOn($plugin);
						               } else {
						                  $room_item->setPluginOff($plugin);
						               }

						               $values = $form_data;
						               $values['current_context_item'] = $room_item;
						               if ( $this->_environment->inPortal()
						                    and method_exists($plugin_class,'configurationAtPortal')
						                  ) {
						                  $plugin_class->configurationAtPortal('save_config',$values);
						               } elseif ( !$this->_environment->inServer()
						                          and method_exists($plugin_class,'configurationAtRoom')
						                        ) {
						                  $plugin_class->configurationAtRoom('save_config',$values);
						               }
						            }
						         }
						         $room_item->save();
						      }
						      // plugins

                        else if($additional['action'] == 'export_private_room'){
                           $currentUserItem = $this->_environment->getCurrentUserItem();
                           $privateroom_manager = $this->_environment->getPrivateRoomManager();
                           $privateroom_item = $privateroom_manager->getRelatedOwnRoomForUser($currentUserItem, $this->_environment->getCurrentPortalID());
                           
                           $room_manager = $this->_environment->getRoomManager();
                           $xml = $room_manager->export_item($privateroom_item->getItemID());
                           //$xml = $room_manager->export_item(488);
                           $dom = new DOMDocument('1.0');
                           $dom->preserveWhiteSpace = false;
                           $dom->formatOutput = true;
                           $dom->loadXML($xml->asXML());
                           //el($dom->saveXML());
   
                           $filename = 'var/temp/commsy_xml_export_import_'.$privateroom_item->getItemID().'.xml';
                           if ( file_exists($filename) ) {
                              unlink($filename);
                           }
                  
                           $xmlfile = fopen($filename, 'a');   
                           fputs($xmlfile, $dom->saveXML());
                           fclose($xmlfile);
                  
                           //Location where export is saved
                           $zipfile = 'var/temp/commsy_export_import_'.$privateroom_item->getItemID().'.zip';
                           if ( file_exists($zipfile) ) {
                              unlink($zipfile);
                           }
                  
                           //Location that will be backuped
                           $disc_manager = $this->_environment->getDiscManager();
                           $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
                  
                           $backup_paths = array();
                           $room_item = $privateroom_manager->getItem($privateroom_item->getItemID());

                           $disc_manager->setContextID($room_item->getItemId());
                           $backup_paths[$room_item->getItemId()] = $disc_manager->getFilePath();
                  
                           if ( class_exists('ZipArchive') ) {
                              include_once('functions/misc_functions.php');
                              $zip = new ZipArchive();
                              $filename_zip = $zipfile;
                  
                              if ( $zip->open($filename_zip, ZIPARCHIVE::CREATE) !== TRUE ) {
                                 include_once('functions/error_functions.php');
                                 trigger_error('can not open zip-file '.$filename_zip,E_USER_WARNNG);
                              }
                              $temp_dir = getcwd();
                              foreach ($backup_paths as $item_id => $backup_path) {
                                 chdir($backup_path);
                                 $zip = addFolderToZip('.',$zip,'files_'.$item_id);
                                 chdir($temp_dir);
                              }
                  
                              $zip->addFile($filename, basename($filename));
                              $zip->close();
                              unset($zip);
                              
                              #header('Content-disposition: attachment; filename=commsy_export_import_'.$_POST['room'].'.zip');
                              #header('Content-type: application/zip');
                              #readfile($zipfile);
                              
                              //export_privateroom
                              
                              $this->_popup_controller->setSuccessfullDataReturn(array('commsy_export' => '/commsy.php?cid='.$this->_environment->getCurrentPortalID().'&mod=export_privateroom&fct=getfile'));
                           } else {
                              include_once('functions/error_functions.php');
                              trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNNG);
                           }
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
							
							// portal2portal
							if(isset($form_data['show_connection_view']) && !empty($form_data['show_connection_view'])) {
								if($form_data['show_connection_view'] == 'yes'){
									$room_item->setCSBarShowConnection('1');
								} else{
									$room_item->setCSBarShowConnection('-1');
								}
							}else{
								$room_item->setCSBarShowConnection('-1');
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
		$current_context = $this->_environment->getCurrentContextItem();

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

		// if context room show email visbility
		// if context portal show default email hide

		$this->_config['hide_mail_adress'] = false;
		if(!$this->_user->isRoot()) {
			if(!$current_context->isPortal()){
				// room context
				if(!$this->_user->isEmailVisible()){
					$this->_config['hide_mail_adress'] = true;
				}
			} else {
				if(!$this->_user->getRelatedPortalUserItem()->getDefaultIsMailVisible()){
					$this->_config['hide_mail_adress'] = true;
				}
			}
		}
		

		// datenschutz: overwrite or not (04.09.2012 IJ)
		$overwrite = true;
		global $symfonyContainer;
		$disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
		if ( !empty($disable_overwrite) and $disable_overwrite === 'TRUE' ) {
			$overwrite = false;
		}
		$this->_config['datenschutz_overwrite'] = $overwrite;
		
		// has to change email
		$this->_config['has_to_change_email'] = false;
		if ( isset($this->_user)
			  and $this->_user->hasToChangeEmail()
			) {
			$this->_config['has_to_change_email'] = true;
		   $translator = $this->_environment->getTranslationObject();
			$this->_config['has_to_change_email_text'] = $translator->getMessage('COMMON_ERROR_FIELD_CORRECT',$translator->getMessage('USER_EMAIL'));
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
				array('name' => 'merge_user_id', 'type' => 'text', 'mandatory' => true),
				array('name' => 'merge_user_password', 'type' => 'text', 'mandatory' => true)
			),
			'account'	=> array(
				array('name' => 'forname', 'type' => 'text', 'mandatory' => true),
				array('name' => 'surname', 'type' => 'text', 'mandatory' => true),
				array('name' => 'old_password', 'type' => 'text', 'mandatory' => false),
				array('name' => 'new_password', 'type' => 'text', 'mandatory' => false, 'same_as' => 'new_password_confirm'),
				array('name' => 'new_password_confirm', 'type' => 'text', 'mandatory' => false),
				array('name' => 'language', 'type' => 'select', 'mandatory' => true),
				array('name' => 'mail_account', 'type' => 'checkbox', 'mandatory' => false),
				array('name' => 'mail_room', 'type' => 'checkbox', 'mandatory' => false),
				array('name' => 'upload', 'type' => 'radio', 'mandatory' => false/*true*/),
//				array('name' => 'mail_delete_entry', 'type' => 'radio', 'mandatory' => false/*true*/),
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

		// user id is only mandatory, if the user is allowed to changed it
		$userItem = $this->_environment->getCurrentUserItem();
		$portalItem = $this->_environment->getCurrentPortalItem();
		$authSourceItem = $portalItem->getAuthSource($userItem->getAuthSource());

		if (isset($authSourceItem) && $authSourceItem->allowChangeUserID()) {
			$return['account'][] = array('name' => 'user_id', 'type' => 'text', 'mandatory' => true);
		}

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
		$context_information["isPortal"] = $current_context->isPortal();
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
		$return['mail_account'] = ($this->_user->getAccountWantMail() === 'yes') ? true : false;
		//TODO PRINT ENTFERNEN
		#pr($this->_user->getAccountWantMail());
		$return['mail_room'] = ($this->_user->getOpenRoomWantMail() === 'yes') ? true : false;
//		$return['mail_delete_entry'] = ($this->_user->getDeleteEntryWantMail() === 'yes') ? true : false;
//		$return['new_upload'] = ($this->_user->isNewUploadOn()) ? true : false;
		$return['auto_save'] = ($this->_user->isAutoSaveOn()) ? true : false;
		$return['email_to_commsy_on'] = false;

	    global $c_email_upload;
	    if ($c_email_upload && !$this->_user->isRoot()) {
		   $return['email_to_commsy_on'] = true;
	       $own_room = $this->_user->getOwnRoom();
	       $return['email_to_commsy'] = $own_room->getEmailToCommSy();
	       $return['email_to_commsy_secret'] = $own_room->getEmailToCommSySecret();
	       global $c_email_upload_email_account;
	       $return['email_to_commsy_mailadress'] = $c_email_upload_email_account;
	       $mail_address = $this->_environment->getConfiguration('c_email_upload_email_address');
	       if ( !empty($mail_address) ) {
	       	 $return['email_to_commsy_mailadress'] = $mail_address;
	       }
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
	   global $c_pmwiki;

	   $return = array();
	   $translator = $this->_environment->getTranslationObject();

	   $user_item = $this->_environment->getPortalUserItem();
	   $current_user = $this->_environment->getPortalUserItem();
	   $current_context = $user_item->getOwnRoom();
	   $current_portal_item = $this->_environment->getCurrentPortalItem();


	   // Wordpress
	   if($current_portal_item->getWordpressPortalActive() && !$current_user->isRoot()){
   	   $wordpress_manager = $this->_environment->getWordpressManager();
	      $wordpress = array();
	   $wordpress_url = $current_portal_item->getWordpressUrl();
   	   if($current_context->isWordpressActive() || !empty($wordpress_url)){
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

   	   if(!$current_user->isRoot() && $current_context->isWikiActive()){
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

   	   } else if(!$current_user->isRoot()){
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

	   // plugins - TODO
	   $c_plugin_array = $this->_environment->getConfiguration('c_plugin_array');
	   if (isset($c_plugin_array) and !empty($c_plugin_array)) {
	      $current_portal_item = $this->_environment->getCurrentPortalItem();
	      foreach ($c_plugin_array as $plugin) {
	         $plugin_class = $this->_environment->getPluginClass($plugin);
	         if ( (
	                $this->_environment->inPortal()
	                and method_exists($plugin_class,'isConfigurableInPortal')
	                and $plugin_class->isConfigurableInPortal()
	         		and $plugin != 'onyx'
	              )
	              or
	              (
	                !$this->_environment->inServer()
	                and $current_portal_item->isPluginOn($plugin)
	                and method_exists($plugin_class,'isConfigurableInRoom')
	                and $plugin_class->isConfigurableInRoom(CS_PRIVATEROOM_TYPE)
	              	and $plugin != 'onyx'
	              )
	              or 
	              (
	              	!$this->_environment->inServer()
	              	and $current_portal_item->isPluginOn($plugin)
	              	and method_exists($plugin_class,'isConfigurableInRoom')
	              	and $plugin_class->isConfigurableInRoom()
	              	and $plugin != 'onyx'
	              	and $plugin == 'voyeur'
	              )
	            ) {
	            $array_plugins[$plugin_class->getIdentifier()]['title'] = $plugin_class->getTitle();
	            if ( method_exists($plugin_class,'getDescription') ) {
	               $array_plugins[$plugin_class->getIdentifier()]['description'] = $plugin_class->getDescription();
	            }
	            if ( method_exists($plugin_class,'getHomepage') ) {
	               $homepage = $plugin_class->getHomepage();
	               if ( !empty($homepage) ) {
	                  $array_plugins[$plugin_class->getIdentifier()]['homepage'] = '___CONFIGURATION_PLUGIN_HOMEPAGE___: <a href="'.$homepage.'" target="_blank" title="___CONFIGURATION_PLUGIN_HOMEPAGE___: '.$plugin_class->getTitle().'">'.$homepage.'</a>';
	               }
	            }
               if ( !$current_user->isRoot() && $current_context->isPluginOn($plugin) ) {
                  $array_plugins[$plugin_class->getIdentifier()]['on'] = 'yes';
               } else {
                  $array_plugins[$plugin_class->getIdentifier()]['on'] = 'no';
               }

	            /*
	            if ( $this->_environment->inPortal()
	                 and method_exists($plugin_class,'configurationAtPortal')
	               ) {
	               $array_plugins[$plugin_class->getIdentifier()]['change_form'] = $plugin_class->configurationAtPortal('change_form');
	            } elseif ( !$this->_environment->inServer()
	                       and method_exists($plugin_class,'configurationAtRoom')
	               ) {
	               $array_plugins[$plugin_class->getIdentifier()]['change_form'] = $plugin_class->configurationAtRoom('change_form');
	            }
	            */
	         }
	      }
	   }
	   if ( !empty($array_plugins) ) {
	      ksort($array_plugins);
	      $return['plugins'] = true;
	      $return['plugins_array'] = $array_plugins;
	   }
	   // plugins

	   return $return;
	}



	private function getNewsletterInformation() {
		$return = array();
		
		if(!$this->_user->isRoot()){
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

	private function getCSBarInformation() {
		$return = array();
		
		if(!$this->_user->isRoot()){
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
			
			// portal2portal
			$return['show_connection_view'] = 'inactive';
			$server_item = $this->_environment->getServerItem();
			if ( !empty($server_item) ) {
				if ( $server_item->isServerConnectionAvailable() ) {
					if ($room->getCSBarShowConnection() == '1') {
						$return['show_connection_view'] = 'yes';
					} else {
						$return['show_connection_view'] = 'no';
					}
				}
			}
	
			return $return;
		}
	}


}
