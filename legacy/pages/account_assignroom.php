<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, JosÃ© Manuel GonzÃ¡lez VÃ¡zquez
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

// include all classes and functions needed for this script
include_once('classes/cs_mail.php');
include_once('functions/date_functions.php');
include_once('functions/text_functions.php');

// translation - object
$translator = $environment->getTranslationObject();

// options und  parameters
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}
if (!empty($_POST['iid'])) {
   $iid = $_POST['iid']; // item id of the user
   $_GET['iid'] = $iid;
} elseif (!empty($_GET['iid'])) {
   $iid = $_GET['iid']; // item id of the user
}


$user_manager = $environment->getUserManager();
$user = $user_manager->getItem($iid);
if ( isset($user) ) {
   $last_status = $user->getStatus();
}
$current_user = $environment->getCurrentUserItem();

//check if context is open
$context_item = $environment->getCurrentContextItem();
if (!$context_item->isOpen()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $error_string = $translator->getMessage('PROJECT_ROOM_IS_CLOSED',$context_item->getTitle());
   $errorbox->setText($error_string);
   $page->add($errorbox);
   $command = 'error';
}

// Check access rights
$room_item = $environment->getCurrentContextItem();

if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $room_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $command = 'error';
}

// first step administration form
if ( empty($command)) {
   $form = $class_factory->getClass(ACCOUNT_ASSIGNROOM_FORM,array('environment' => $environment));
   $form->setItem($user);
   $form->prepareForm();
   $form->loadValues();

   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);
   $form_view->setAction(curl($environment->getCurrentContextID(),'account','assignroom',array( 'iid' => $iid)));
   $form_view->setForm($form);
   if ( $environment->inServer() or $environment->inPortal() ) {
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
} elseif (isOption($command,$translator->getMessage('ADMIN_CANCEL_BUTTON')) or isOption($command,$translator->getMessage('MAIL_NOT_SEND_BUTTON'))) {
   $history = $session->getValue('history');
   $back_hop = 1;
   while ($history[$back_hop]['function'] == 'status' or $history[$back_hop]['module'] == 'mail') {
      $back_hop++;
   }
   redirect($history[$back_hop]['context'],$history[$back_hop]['module'],$history[$back_hop]['function'],$history[$back_hop]['parameter']);
} elseif ( isOption($command,$translator->getMessage('COMMON_CHANGE_BUTTON'))
           or isOption($command,$translator->getMessage('COMMON_DELETE_BUTTON'))
           or isOption($command,$translator->getMessage('COMMON_USER_REJECT_BUTTON'))
         ) {
   if (isOption($command,$translator->getMessage('COMMON_CHANGE_BUTTON'))) {
   	$form = $class_factory->getClass(ACCOUNT_ASSIGNROOM_FORM,array('environment' => $environment));
   	$form->setItem($user);
   	$form->prepareForm();
   	$form->loadValues();
   	
   	$params = array();
   	$params['environment'] = $environment;
   	$params['with_modifying_actions'] = true;
   	$form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   	unset($params);
   	$form_view->setAction(curl($environment->getCurrentContextID(),'account','assignroom',array( 'iid' => $iid)));
   	$form_view->setForm($form);
   	if ( $environment->inServer() or $environment->inPortal() ) {
   		$page->addForm($form_view);
   	} else {
   		$page->add($form_view);
   	}
   	
   	
   	#pr($_POST);
   	if( !empty($_POST['iid']) and !empty($_POST['room_id'])) {
   		$room_id = $_POST['room_id'];
   		$iid = $_POST['iid'];
   		$user_id = $_POST['user_id'];
   		$auth_source = $_POST['auth_source'];
   		
   		$room_manager = $environment->getRoomManager();
   		$room_item = $room_manager->getItem($room_id);
   		if($room_item) {
   			$room_user_item = $room_item->getUserByUserID($user_id, $auth_source);
   			
   			if($room_user_item) {
   				// user is already existing - set errorbox
   				$params = array();
   				$params['environment'] = $environment;
   				$params['with_modifying_actions'] = true;
   				$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   				unset($params);
   				$errorbox->setText($translator->getMessage('MEMBER_EDIT_ERROR_EXISTS'));
//    				if ( $environment->inProjectRoom() or $environment->inCommunityRoom() ) {
//    					$errorbox->setText($translator->getMessage('MEMBER_EDIT_ERROR_JUST_DELETED',$user->getFullname()));
//    				} else {
//    					$errorbox->setText($translator->getMessage('ACCOUNT_EDIT_ERROR_JUST_DELETED',$user->getFullname(),$user->getUserID()));
//    				}
   				$page->add($errorbox);
   				$command = 'error';
   			} else {
   				// user does not exist
   				// create user item for room
   				$user_manager = $environment->getUserManager();
   				$selected_user_item = $user_manager->getItem($iid);
   				$user_item = $selected_user_item->cloneData();
   				
   				$user_item->setContextID($room_item->getItemID());
   				$user_item->setUserComment($_POST['description']);
   				$user_item->request();
   				$check_message = 'YES';
   				
   				$user_item->save();
   				
   				$current_item_id = $room_item->getItemID();
   				
   				// send mail to user + moderator

   				// send email to moderators if necessary
   				$user_manager = $environment->getUserManager();
   				$user_manager->resetLimits();
   				$user_manager->setModeratorLimit();
   				$user_manager->setContextLimit($room_item->getItemID());
   				$user_manager->select();
   				$user_list = $user_manager->get();
   				$email_addresses = array();
   				$moderator_item = $user_list->getFirst();
   				$recipients = '';
   				$language = $room_item->getLanguage();
   				while ($moderator_item) {
   					$want_mail = $moderator_item->getAccountWantMail();
   					if (!empty($want_mail) and $want_mail == 'yes') {
   						if ($language == 'user' and $moderator_item->getLanguage() == 'browser') {
   							$email_addresses[$environment->getSelectedLanguage()][] = $moderator_item->getEmail();
   						} elseif ($language == 'user' and $moderator_item->getLanguage() != 'browser') {
   							$email_addresses[$moderator_item->getLanguage()][] = $moderator_item->getEmail();
   						} else {
   							$email_addresses[$room_item->getLanguage()][] = $moderator_item->getEmail();
   						}
   						$recipients .= $moderator_item->getFullname().LF;
   					}
   					$moderator_item = $user_list->getNext();
   				}
   				foreach ($email_addresses as $language => $email_array) {
   					if (count($email_array) > 0) {
   						$old_lang = $translator->getSelectedLanguage();
   						$translator->setSelectedLanguage($language);
   						$subject = $translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$user_item->getFullname(),$room_item->getTitle());
   						$body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),getTimeInLang(getCurrentDateTimeInMySQL()));
   						$body .= LF.LF;
   						if ( $room_item->isCommunityRoom() ) {
   							$portal = $environment->getCurrentContextItem();
   							if($portal->getHideAccountname()){
   								// Hide useraccountname
   								$user_id = $translator->getMessage('USER_ACCOUNT_NOT_VISIBLE');
   								$body .= $translator->getMessage('USER_JOIN_COMMUNITY_MAIL_BODY_BY_MODERATOR',$user_item->getFullname(),$user_id,$user_item->getEmail(),$room_item->getTitle(),$current_user->getFullname());
   							} else {
   								$body .= $translator->getMessage('USER_JOIN_COMMUNITY_MAIL_BODY_BY_MODERATOR',$user_item->getFullname(),$user_item->getUserID(),$user_item->getEmail(),$room_item->getTitle(),$current_user->getFullname());
   							}
   						} else {
   							$portal = $environment->getCurrentContextItem();
   							if($portal->getHideAccountname()){
   								// Hide useraccountname
   								$user_id = $translator->getMessage('USER_ACCOUNT_NOT_VISIBLE');
   								$body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY_BY_MODERATOR',$user_item->getFullname(),$user_id,$user_item->getEmail(),$room_item->getTitle(),$current_user->getFullname());
   							} else {
   								$body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY_BY_MODERATOR',$user_item->getFullname(),$user_item->getUserID(),$user_item->getEmail(),$room_item->getTitle(),$current_user->getFullname());
   							}
   						}
   						$body .= LF.LF;
   						if ($check_message == 'YES') {
   							$body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
   						} else {
   							$body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
   						}
   						$body .= LF.LF;
   						if (!empty($_POST['description_user'])) {
   							$body .= $translator->getMessage('MAIL_COMMENT_BY',$user_item->getFullname(),$_POST['description_user']);
   							$body .= LF.LF;
   						}
   						$body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
   						$body .= LF;

   						global $symfonyContainer;
   						$router = $symfonyContainer->get('router');

   						if ($check_message == 'YES') {
   							$url = $router->generate(
   								'commsy_user_list', [
   									'roomId' => $current_item_id,
   									'user_filter' => [
   										'user_status' => 1,
   									],
   								]
   							);

   							$body .= $translator->getMessage('MAIL_USER_FREE_LINK').LF;
   						} else {
   							$url = $router->generate(
   								'commsy_room_home', [
   									'roomId' => $current_item_id,
   								]
   							);
   						}

   						$requestStack = $symfonyContainer->get('request_stack');
   						$currentRequest = $requestStack->getCurrentRequest();
   						if ($currentRequest) {
   							$url = $currentRequest->getSchemeAndHttpHost() . $url;
   						}

   						$body .= $url;

   						$emailFrom = $symfonyContainer->getParameter('commsy.email.from');

   						$message = (new \Swift_Message())
   							->setSubject($subject)
   							->setBody($body, 'text/plain')
   							->setFrom([$emailFrom => $environment->getCurrentPortalItem()->getTitle()])
   							->setReplyTo([$user_item->getEmail() => $user_item->getFullname()])
   							->setTo($email_array);

   						$symfonyContainer->get('mailer')->send($message);

   						$translator->setSelectedLanguage($old_lang);
   					}
   				}
   			}
   			
   		} else {
   			// room id existiert nicht
   			$params = array();
   			$params['environment'] = $environment;
   			$params['with_modifying_actions'] = true;
   			$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   			unset($params);
   			$errorbox->setText($translator->getMessage('MEMBER_EDIT_ERROR_ROOM_NOT_EXISTS'));
   			$page->add($errorbox);
   			$command = 'error';
   		}
   	} else {
   		// room id existiert nicht
   		$params = array();
   		$params['environment'] = $environment;
   		$params['with_modifying_actions'] = true;
   		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   		unset($params);
   		$errorbox->setText($translator->getMessage('MEMBER_EDIT_ERROR_ID'));
   		$page->add($errorbox);
   		$command = 'error';
   	}
   	
      
   } 

   
   else {
      if ( isOption($command_delete,$translator->getMessage('COMMON_USER_REJECT_BUTTON')) ) {
         $_POST['status'] = 'close';
      
      } else {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
         unset($params);
         $form_view->setAction(curl($environment->getCurrentContextID(),'account','assignroom',''));
         $form_view->setForm($form);
         if ( $environment->inServer() or $environment->inPortal() ) {
            $page->addForm($form_view);
         } else {
            $page->add($form_view);
         }
      }
   }
}
?>