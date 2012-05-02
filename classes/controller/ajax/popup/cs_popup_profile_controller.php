<?php
class cs_popup_profile_controller {
	private $_environment = null;
	private $_popup_controller = null;
	private $_return = '';
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
						break;
					
					/**** USER ****/
					case 'user':
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
							
							$this->_return = 'success';
						}
						break;
				}
			}
			
			
			// 			// save user
			// 			else {

			
			// 				/**************User********/
			// 				if ($profile_page =='user'){
			// 					// init data display
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
			
			
			// 					if ( !empty($command) AND isOption($command,$translator->getMessage('COMMON_CHANGE_BUTTON')) ) {
			
			// 						$correct = $form->check();
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
			// 						if ( $correct
					// 								and ( !isset($c_virus_scan)
							// 										or !$c_virus_scan
							// 										or empty($_FILES['upload']['tmp_name'])
							// 										or empty($_FILES['upload']['name'])
							// 										or page_edit_virusscan_isClean($_FILES['upload']['tmp_name'],$_FILES['upload']['name'])
							// 								)
					// 						) {
			// 							$user_manager = $environment->getUserManager();
			// 							if (!empty($iid)) { // change user
			// 								$user_item = $user_manager->getItem($iid);
			// 								$portal_user_item = $user_item->getRelatedCommSyUserItem();
			// 							}
			// 							if (isset($_POST['title'])) {
			// 								$user_item->setTitle($_POST['title']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setTitle($_POST['title']);
			// 								}
			// 							}
			// 							if (isset($_POST['telephone'])) {
			// 								$user_item->setTelephone($_POST['telephone']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setTelephone($_POST['telephone']);
			// 								}
			// 							}
			// 							if (isset($_POST['birthday'])) {
			// 								$user_item->setBirthday($_POST['birthday']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setBirthday($_POST['birthday']);
			// 								}
			// 							}
			// 							if (isset($_POST['cellularphone'])) {
			// 								$user_item->setCellularphone($_POST['cellularphone']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setCellularphone($_POST['cellularphone']);
			// 								}
			// 							}
			// 							if (isset($_POST['homepage'])) {
			// 								$user_item->setHomepage($_POST['homepage']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setHomepage($_POST['homepage']);
			// 								}
			// 							}
			// 							if (isset($_POST['organisation'])) {
			// 								$user_item->setOrganisation($_POST['organisation']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setOrganisation($_POST['organisation']);
			// 								}
			// 							}
			// 							if (isset($_POST['position'])) {
			// 								$user_item->setPosition($_POST['position']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setPosition($_POST['position']);
			// 								}
			// 							}
			// 							if (isset($_POST['icq'])) {
			// 								$user_item->setICQ($_POST['icq']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setICQ($_POST['icq']);
			// 								}
			// 							}
			// 							if (isset($_POST['skype'])) {
			// 								$user_item->setSkype($_POST['skype']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setSkype($_POST['skype']);
			// 								}
			// 							}
			// 							if (isset($_POST['yahoo'])) {
			// 								$user_item->setYahoo($_POST['yahoo']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setYahoo($_POST['yahoo']);
			// 								}
			// 							}
			// 							if (isset($_POST['msn'])) {
			// 								$user_item->setMSN($_POST['msn']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setMSN($_POST['msn']);
			// 								}
			// 							}
			// 							if (isset($_POST['jabber'])) {
			// 								$user_item->setJabber($_POST['jabber']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setJabber($_POST['jabber']);
			// 								}
			// 							}
			// 							if (isset($_POST['email'])) {
			// 								$user_item->setEmail($_POST['email']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setEmail($_POST['email']);
			// 									if ( $portal_user_item->hasToChangeEmail() ) {
			// 										$portal_user_item->unsetHasToChangeEmail();
			// 										$_POST['email_change_all'] = 1;
			// 									}
			// 								}
			// 							}
			// 							if (isset($_POST['street'])) {
			// 								$user_item->setStreet($_POST['street']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setStreet($_POST['street']);
			// 								}
			// 							}
			// 							if (isset($_POST['zipcode'])) {
			// 								$user_item->setZipcode($_POST['zipcode']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setZipcode($_POST['zipcode']);
			// 								}
			// 							}
			// 							if (isset($_POST['city'])) {
			// 								$user_item->setCity($_POST['city']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setCity($_POST['city']);
			// 								}
			// 							}
			// 							if (isset($_POST['room'])) {
			// 								$user_item->setRoom($_POST['room']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setRoom($_POST['room']);
			// 								}
			// 							}
			// 							if (isset($_POST['description'])) {
			// 								$user_item->setDescription($_POST['description']);
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setDescription($_POST['description']);
			// 								}
			// 							}
			
			// 							if ( ( isset($_POST['deletePicture'])
					// 									or ( !empty($_FILES['upload']['name'])
							// 											and !empty($_FILES['upload']['tmp_name'])
							// 									)
					// 							)
					// 									and $user_item->getPicture()
					// 							) {
			// 								$disc_manager = $environment->getDiscManager();
			// 								if ( $disc_manager->existsFile($user_item->getPicture()) ) {
			// 									$disc_manager->unlinkFile($user_item->getPicture());
			// 								}
			// 								$user_item->setPicture('');
			// 								if ( isset($portal_user_item) ) {
			// 									$portal_user_item->setPicture('');
			// 								}
			// 							}
			// 							if ( !empty($_FILES['upload']['name']) and !empty($_FILES['upload']['tmp_name']) ) {
			// 								//$filename = 'cid'.$environment->getCurrentContextID().'_'.$user_item->getUserID().'_'.$_FILES['upload']['name'];
			// 								$filename_info = pathinfo($_FILES['upload']['name']);
			// 								$filename = 'cid' . $environment->getCurrentContextID() . '_' . $user_item->getItemID() . '.' . $filename_info['extension'];
			// 								$disc_manager = $environment->getDiscManager();
			// 								$disc_manager->copyFile($_FILES['upload']['tmp_name'],$filename,true);
			// 								$user_item->setPicture($filename);
			// 								if ( isset($portal_user_item) ) {
			// 									if ( $disc_manager->copyImageFromRoomToRoom($filename,$portal_user_item->getContextID()) ) {
			// 										$value_array = explode('_',$filename);
			// 										$old_room_id = $value_array[0];
			// 										$old_room_id = str_replace('cid','',$old_room_id);
			// 										$value_array[0] = 'cid'.$portal_user_item->getContextID();
			// 										$new_picture_name = implode('_',$value_array);
			// 										$portal_user_item->setPicture($new_picture_name);
			// 									}
			// 								}
			// 							}
			
			// 							if (isset($_POST['want_mail_get_account'])) {
			// 								$user_item->setAccountWantMail($_POST['want_mail_get_account']);
			// 							}
			
			// 							// Set modificator and modification date
			// 							$user = $environment->getCurrentUserItem();
			// 							$user_item->setModificatorItem($user);
			// 							$user_item->setModificationDate(getCurrentDateTimeInMySQL());
			// 							if ( isset($portal_user_item) ) {
			// 								$portal_user_item->setModificatorItem($user);
			// 								$portal_user_item->setModificationDate(getCurrentDateTimeInMySQL());
			// 							}
			
			// 							// email visibility
			// 							if (isset($_POST['email_visibility']) and !empty($_POST['email_visibility'])) {
			// 								$user_item->setEmailNotVisible();
			// 							} else {
			// 								$user_item->setEmailVisible();
			// 							}
			
			
			
			// 							// save user
			// 							$user_item->save();
			// 							$is_saved = true;
			// 							if ( isset($portal_user_item) ) {
			// 								$portal_user_item->save();
			// 							}
			
			
			// 							if ( isset($_POST['title_change_all'])
					// 									or isset($_POST['street_change_all'])
					// 									or isset($_POST['zipcode_change_all'])
					// 									or isset($_POST['city_change_all'])
					// 									or isset($_POST['room_change_all'])
					// 									or isset($_POST['telephone_change_all'])
					// 									or isset($_POST['birthday_change_all'])
					// 									or isset($_POST['cellularphone_change_all'])
					// 									or isset($_POST['homepage_change_all'])
					// 									or isset($_POST['organisation_change_all'])
					// 									or isset($_POST['position_change_all'])
					// 									or isset($_POST['email_change_all'])
					// 									or isset($_POST['messenger_change_all'])
					// 									or isset($_POST['description_change_all'])
					// 									or isset($_POST['picture_change_all'])) {
			// 								// change firstname and lastname in all other user_items of this user
			// 								$user_manager = $environment->getUserManager();
			// 								$dummy_user = $user_manager->getNewItem();
			// 								if (isset($_POST['title_change_all'])) {
			// 									$value = $user_item->getTitle();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setTitle($value);
			// 								}
			// 								if (isset($_POST['street_change_all'])) {
			// 									$value = $user_item->getStreet();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setStreet($value);
			// 								}
			// 								if (isset($_POST['zipcode_change_all'])) {
			// 									$value = $user_item->getZipCode();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setZipCode($value);
			// 								}
			// 								if (isset($_POST['city_change_all'])) {
			// 									$value = $user_item->getCity();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setCity($value);
			// 								}
			// 								if (isset($_POST['room_change_all'])) {
			// 									$value = $user_item->getRoom();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setRoom($value);
			// 								}
			// 								if (isset($_POST['telephone_change_all'])) {
			// 									$value = $user_item->getTelephone();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setTelephone($value);
			// 								}
			// 								if (isset($_POST['birthday_change_all'])) {
			// 									$value = $user_item->getBirthday();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setBirthday($value);
			// 								}
			// 								if (isset($_POST['cellularphone_change_all'])) {
			// 									$value = $user_item->getCellularphone();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setCellularphone($value);
			// 								}
			// 								if (isset($_POST['homepage_change_all'])) {
			// 									$value = $user_item->getHomepage();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setHomepage($value);
			// 								}
			// 								if (isset($_POST['organisation_change_all'])) {
			// 									$value = $user_item->getOrganisation();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setOrganisation($value);
			// 								}
			// 								if (isset($_POST['position_change_all'])) {
			// 									$value = $user_item->getPosition();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setPosition($value);
			// 								}
			// 								if (isset($_POST['messenger_change_all'])) {
			// 									$value = $user_item->getICQ();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setICQ($value);
			// 									$value = $user_item->getSkype();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setSkype($value);
			// 									$value = $user_item->getYahoo();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setYahoo($value);
			// 									$value = $user_item->getMSN();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setMSN($value);
			// 									$value = $user_item->getJabber();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setJabber($value);
			// 								}
			// 								if (isset($_POST['email_change_all'])) {
			// 									$value = $user_item->getEmail();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setEmail($value);
			
			// 									if (!$user->isEmailVisible()) {
			// 										$dummy_user->setEmailNotVisible();
			// 									} else {
			// 										$dummy_user->setEmailVisible();
			// 									}
			// 								}
			// 								if (isset($_POST['description_change_all'])) {
			// 									$value = $user_item->getDescription();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setDescription($value);
			// 								}
			// 								if (isset($_POST['picture_change_all'])) {
			// 									$value = $user_item->getPicture();
			// 									if (empty($value)) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setPicture($value);
			// 								}
			// 								$user_item->changeRelatedUser($dummy_user);
			// 							}
			
			// 							//Add modifier to all users who ever edited this item
			// 							$manager = $environment->getLinkModifierItemManager();
			// 							$manager->markEdited($user->getItemID());
			
			// 							// redirect
			// 							$params = $environment->getCurrentParameterArray();
			// 							if ($is_saved){
			// 								$params['is_saved'] = true;
			// 							}
			// 							redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
			// 						}
			// 					}
			// 				}
			
			
			// 				/**************Roomlist********/
			// 				elseif ($profile_page =='room_list'){
			// 					$form->prepareForm();
			// 					$form->loadValues();
			
			// 					if ( !empty($command) AND isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
			
			// 						$correct = $form->check();
			// 						if ( $correct ){
			// 							if ( isset($_POST['sorting'])
					// 									or !empty($_POST['delete'])
					// 							) {
			// 								if ( isset($_POST['sorting'][0])
					// 										or !empty($_POST['delete'])
					// 								) {
			// 									$current_user = $environment->getCurrentUserItem();
			// 									$own_room_item = $current_user->getOwnRoom();
			// 									if ( !empty($_POST['delete']) and $_POST['delete'] == 1 ) {
			// 										$own_room_item->setCustomizedRoomIDArray(array());
			// 									} else {
			// 										$own_room_item->setCustomizedRoomIDArray($_POST['sorting']);
			// 									}
			// 									$own_room_item->save();
			// 									$is_saved = true;
			// 								}
			// 							}
			// 						}
			// 						// redirect
			// 						$params = $environment->getCurrentParameterArray();
			// 						if ($is_saved){
			// 							$params['is_saved'] = true;
			// 						}
			// 						redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
			// 					}
			
			// 				}else{
			// 					if ( isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
			// 						$authentication = $environment->getAuthenticationObject();
			// 						$error_string = '';
			// 						$form->setFormPost($_POST);
			// 						$form->prepareForm();
			// 						$form->loadValues();
			// 						$params = $environment->getCurrentParameterArray();
			// 						if ( $form->check() ) {
			// 							// change password
			// 							if (empty($error_string)) {
			// 								if (!empty($_POST['password'])){
			// 									$auth_manager = $authentication->getAuthManager($user->getAuthSource());
			// 									$auth_manager->changePassword($_POST['user_id'],$_POST['password']);
			// 									$params['is_saved'] = true;
			// 									$error_number = $auth_manager->getErrorNumber();
			// 									if (!empty($error_number)) {
			// 										$error_string .= $translator->getMessage('COMMON_ERROR_DATABASE').$error_number.'<br />';
			// 									}
			// 								}
			// 								if ( !$environment->inPortal() ) {
			// 									$portal_user = $environment->getPortalUserItem();
			// 								} else {
			// 									$portal_user = $environment->getCurrentUserItem();
			// 								}
			
			// 								$success_1 = false;
			// 								$success_2 = false;
			// 								$success_3 = false;
			
			// 								if ( !empty($_POST['user_id'])
					// 										and $_POST['user_id'] != $portal_user->getUserID()
					// 								) {
			// 									if ($authentication->changeUserID($_POST['user_id'],$portal_user)) {
			// 										$session = $environment->getSessionItem();
			// 										$session_id_old = $session->getSessionID();
			// 										$session_manager = $environment->getSessionManager();
			// 										$session_manager->delete($session_id_old,true);
			// 										$session->createSessionID($_POST['user_id']);
			// 										$cookie = $session->getValue('cookie');
			// 										if ( $cookie == 1 ) {
			// 											$session->setValue('cookie',2);
			// 										}
			// 										$session_manager->save($session);
			// 										unset($session_manager);
			// 										$success_1 = true;
			// 										$portal_user->setUserID($_POST['user_id']);
			// 										include_once('functions/misc_functions.php');
			// 										plugin_hook('user_save', $portal_user);
			// 									}
			// 								} else {
			// 									$success_1 = true;
			// 								}
			// 								$save = false;
			// 								if (!empty($_POST['language']) and $_POST['language'] != $portal_user->getLanguage()) {
			// 									$portal_user->setLanguage($_POST['language']);
			// 									$save = true;
			// 									if ( $environment->inPrivateRoom() ) {
			// 										$current_user_item = $environment->getCurrentUserItem();
			// 										$current_user_item->setLanguage($_POST['language']);
			// 										$current_user_item->save();
			// 										unset($current_user_item);
			// 									}
			// 								}
			// 								if (!empty($_POST['email_account_want'])) {
			// 									if ($portal_user->getAccountWantMail() == 'no') {
			// 										$portal_user->setAccountWantMail('yes');
			// 										$save = true;
			// 									}
			// 								} else {
			// 									if ($portal_user->getAccountWantMail() == 'yes') {
			// 										$portal_user->setAccountWantMail('no');
			// 										$save = true;
			// 									}
			// 								}
			// 								if (!empty($_POST['email_room_want'])) {
			// 									if ($portal_user->getOpenRoomWantMail() == 'no') {
			// 										$portal_user->setOpenRoomWantMail('yes');
			// 										$save = true;
			// 									}
			// 								} else {
			// 									if ($portal_user->getOpenRoomWantMail() == 'yes') {
			// 										$portal_user->setOpenRoomWantMail('no');
			// 										$save = true;
			// 									}
			// 								}
			// 								$change_name = false;
			// 								if ( !empty($_POST['firstname'])
					// 										and $portal_user->getFirstName() != $_POST['firstname']
					// 								) {
			// 									$portal_user->setFirstName($_POST['firstname']);
			// 									$change_name = true;
			// 									$save = true;
			// 								}
			// 								if ( !empty($_POST['lastname'])
					// 										and $portal_user->getLastName() != $_POST['lastname']
					// 								) {
			// 									$portal_user->setLastName($_POST['lastname']);
			// 									$change_name = true;
			// 									$save = true;
			// 								}
			// 								if ( !empty($_POST['auto_save'])) {
			// 									if ($_POST['auto_save'] == 'yes') {
			// 										$portal_user->turnAutoSaveOn();
			// 									}elseif ($_POST['auto_save'] == 'no') {
			// 										$portal_user->turnAutoSaveOff();
			// 									}
			// 									$save = true;
			// 								}
			
			// 								if (isset($_POST['new_upload'])) {
			// 									if ($_POST['new_upload'] == 'yes') {
			// 										$portal_user->turnNewUploadOn();
			// 									}elseif ($_POST['new_upload'] == 'no') {
			// 										$portal_user->turnNewUploadOff();
			// 									}
			// 									$save = true;
			// 								}
			
			// 								if ($save) {
			// 									$portal_user->save();
			// 									$params['is_saved'] = true;
			// 								} else {
			// 									$success_2 = true;
			// 								}
			// 								$success = $success_1 and $success_2;
			
			// 								// change firstname and lastname in all other user_items of this user
			// 								if ( $change_name ) {
			// 									$user_manager = $environment->getUserManager();
			// 									$dummy_user = $user_manager->getNewItem();
			// 									$value = $_POST['firstname'];
			// 									if ( empty($value) ) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setFirstName($value);
			// 									$value = $_POST['lastname'];
			// 									if ( empty($value) ) {
			// 										$value = -1;
			// 									}
			// 									$dummy_user->setLastName($value);
			// 									$portal_user->changeRelatedUser($dummy_user);
			// 								}
			// 							}
			// 							if ( !$success or !empty($error_number) ) {
			// 								unset($params['is_saved']);
			// 							}
			// 							redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
			// 						}
			// 					}
			
			// 					// merge accounts
			// 					elseif ( isOption($command,$translator->getMessage('ACCOUNT_MERGE_BUTTON')) ) {
			// 						$form->setFormPost($_POST);
			// 						$form->prepareForm();
			// 						$form->loadValues();
			// 						if ( $form->check() ) {
			// 							$authentication = $environment->getAuthenticationObject();
			// 							$current_user = $environment->getCurrentUserItem();
			// 							if ( isset($_POST['auth_source']) and !empty($_POST['auth_source']) ) {
			// 								$auth_source_old = $_POST['auth_source'];
			// 							} else {
			// 								$current_context = $environment->getCurrentContextItem();
			// 								$auth_source_old = $current_context->getAuthDefault();
			// 							}
			// 							$authentication->mergeAccount($current_user->getUserID(),$current_user->getAuthSource(),$_POST['user_id_merge'],$auth_source_old);
			// 							$params = $environment->getCurrentParameterArray();
			// 							$params['is_saved'] = true;
			// 							redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
			// 						}
			// 					}
			
			// 					// change existing user
			// 					elseif (!empty($iid) ) {
			// 						$user_manager = $environment->getUserManager();
			// 						$user_item = $user_manager->getItem($iid);
			// 						$form->setItem($user_item);
			// 						$form->setIsModerator($current_user->isModerator());
			// 						$form->prepareForm();
			// 						$form->loadValues();
			// 					}
			
			// 				}
			
			
			
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
	
	public function getReturn() {
		return $this->_return;
	}
	
	public function getFieldInformation($sub) {
		$return = array(
			'newsletter'	=> array(
					array('name' => 'newsletter', 'type' => 'radio', 'mandatory' => true)
			),
			'user'			=>
				array('name' => 'mail','type' => 'mail', 'mandatory' => true),
				array('name' => 'title','type' => 'mail', 'mandatory' => true)
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
		
		// portal information
		$portal_information = array();
		$portal_information['portal_name'] = $this->_environment->getCurrentPortalItem()->getTitle();
		$this->_popup_controller->assign('popup', 'portal', $portal_information);
		
		// form information
		$form_information = array();
		$form_information['account'] = $this->getAccountInformation();
		$form_information['user'] = $this->getUserInformation();
		$form_information['newsletter'] = $this->getNewsletterInformation();
		$form_information['room_list'] = $this->getRoomListInformation();
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
		
		return $return;
	}
	
	private function getUserInformation() {
		$retrun = array();
		
		// get data from database
		$return['title'] = $this->_user->getTitle();
		$return['birthday'] = $this->_user->getBirthday();
		//$return['picture']
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
	
	private function getRoomListInformation() {
		
	}
	
	private function cleanup_session($current_iid) {
		$environment = $this->_environment;
		$session = $this->_environment->getSessionItem();

		$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
		$session->unsetValue($environment->getCurrentModule().'_add_tags');
		$session->unsetValue($environment->getCurrentModule().'_add_files');
		$session->unsetValue($current_iid.'_post_vars');
	}
}