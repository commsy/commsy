<?PHP
//
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

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

// Get the translator object
$translator = $environment->getTranslationObject();

if (!$current_user->isModerator() || !$environment->inPortal()) {
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
   } else {
      $command = '';
   }

   // Initialize the form
   $form = $class_factory->getClass(CONFIGURATION_ROOM_OPENING_FORM,array('environment' => $environment));
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Save item
   if ( !empty($command)
        and ( isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON'))
              or isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
             )
      ) {
     	
   	if ( !empty($_POST) ) {
   		$form->setFormPost($_POST);
   	}
   	
   	if ( $form->check() ) {

         if ( isset($_POST['community_room_opening']) and !empty($_POST['community_room_opening']) and $_POST['community_room_opening'] == 2 ) {
            $room_item->setCommunityRoomCreationStatus('moderator');
         } else {
            $room_item->setCommunityRoomCreationStatus('all');
         }

         if ( isset($_POST['project_room_link']) and !empty($_POST['project_room_link']) and $_POST['project_room_link'] == 2 ) {
            $room_item->setProjectRoomLinkStatus('mandatory');
         } else {
            $room_item->setProjectRoomLinkStatus('optional');
         }

         if ( isset($_POST['project_room_opening']) and !empty($_POST['project_room_opening']) and $_POST['project_room_opening'] == 2 ) {
            $room_item->setProjectRoomCreationStatus('communityroom');
         } else {
            $room_item->setProjectRoomCreationStatus('portal');
         }

         if ( !empty($_POST['template_select']) ) {
            $room_item->setDefaultProjectTemplateID($_POST['template_select']);
         }
         if ( !empty($_POST['template_select_community']) ) {
            $room_item->setDefaultCommunityTemplateID($_POST['template_select_community']);
         }

         //if ( !empty($_POST['private_room_link']) ) {
         //   if ($_POST['private_room_link'] == 1) {
               $room_item->setShowAllwaysPrivateRoomLink();
         //   } else {
         //      $room_item->unsetShowAllwaysPrivateRoomLink();
         //   }
         //}

   	   // archiving
         if ( !empty($_POST['room_archiving'])
              and $_POST['room_archiving'] == 1
            ) {
            $room_item->turnOnArchivingUnusedRooms();
         } else {
         	$room_item->turnOffArchivingUnusedRooms();
         }
         if ( !empty($_POST['room_archiving_days_unused']) ) {
         	$room_item->setDaysUnusedBeforeArchivingRooms($_POST['room_archiving_days_unused']);
         } else {
         	$room_item->setDaysUnusedBeforeArchivingRooms(0);
         }
         if ( !empty($_POST['room_archiving_days_unused_mail']) ) {
         	$room_item->setDaysSendMailBeforeArchivingRooms($_POST['room_archiving_days_unused_mail']);
         } else {
         	$room_item->setDaysSendMailBeforeArchivingRooms(0);
         }
         
   	   // deleting
         if ( !empty($_POST['room_deleting'])
              and $_POST['room_deleting'] == 1
            ) {
            $room_item->turnOnDeletingUnusedRooms();
         } else {
         	$room_item->turnOffDeletingUnusedRooms();
         }
         if ( !empty($_POST['room_deleting_days_unused']) ) {
         	$room_item->setDaysUnusedBeforeDeletingRooms($_POST['room_deleting_days_unused']);
         } else {
         	$room_item->setDaysUnusedBeforeDeletingRooms(0);
         }
         if ( !empty($_POST['room_deleting_days_unused_mail']) ) {
         	$room_item->setDaysSendMailBeforeDeletingRooms($_POST['room_deleting_days_unused_mail']);
         } else {
         	$room_item->setDaysSendMailBeforeDeletingRooms(0);
         }
          
         // Save item
         $room_item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;
         
         // show info if archiving or deleting rooms
         $message_array = array();
         if ( $room_item->isActivatedArchivingUnusedRooms()
         	  and !empty($_POST['room_archiving_days_unused'])
         	) {
         	$room_manager = $environment->getProjectManager();
         	include_once('functions/date_functions.php');
         	if ( !empty($_POST['room_archiving_days_unused_mail']) ) {
         		$datetime_border = getCurrentDateTimeMinusDaysInMySQL($room_item->getDaysUnusedBeforeArchivingRooms()-$room_item->getDaysSendMailBeforeArchivingRooms());
         	} else {
         		$datetime_border = getCurrentDateTimeMinusDaysInMySQL($room_item->getDaysUnusedBeforeArchivingRooms());
         	}
         	$room_manager->setLastLoginOlderLimit($datetime_border);
         	$room_manager->setContextLimit($room_item->getItemID());
         	$room_manager->setNotTemplateLimit();
         	$number1 = $room_manager->getCountAll();
         	$room_manager = $environment->getCommunityManager();
         	$room_manager->setLastLoginOlderLimit($datetime_border);
         	$room_manager->setContextLimit($room_item->getItemID());
         	$room_manager->setNotTemplateLimit();
         	$number2 = $room_manager->getCountAll();
         	$number = $number1 + $number2;
         	if ( !empty($number) ) {
         	   if ( !empty($number1) and $number1 == 1 ) {
	         	   $project = $translator->getMessage('COMMON_PROJECT_NUMBER_SI',$number1);
	         	} else {
	         		$project = $translator->getMessage('COMMON_PROJECT_NUMBER_PL',$number1);
	         	}
         		if ( !empty($number2) and $number2 == 1 ) {
	         	   $community = $translator->getMessage('COMMON_COMMUNITY_NUMBER_SI',$number2);
         		} else {
         			$community = $translator->getMessage('COMMON_COMMUNITY_NUMBER_PL',$number2);
         		}
         		if ( !empty($_POST['room_archiving_days_unused_mail']) ) {
         			if ( $number == 1 ) {
         				$message_array[] = $translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_MAIL_INFO_SI',$number,$project,$community);
         			} else {
	         		   $message_array[] = $translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_MAIL_INFO',$number,$project,$community);
         			}
	         	} else {
         			if ( $number == 1 ) {
         				$message_array[] = $translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_INFO_SI',$number,$project,$community);
         			} else {
	         		   $message_array[] = $translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_INFO',$number,$project,$community);
         			}
	         	}
         	}
         	unset($room_manager);
         	unset($number);
         	unset($number1);
         	unset($number2);
         }
         
   	   if ( $room_item->isActivatedDeletingUnusedRooms()
         	  and !empty($_POST['room_deleting_days_unused'])
         	) {
         	$room_manager = $environment->getZzzProjectManager();
         	include_once('functions/date_functions.php');
         	if ( !empty($_POST['room_deleting_days_unused_mail']) ) {
         		$datetime_border = getCurrentDateTimeMinusDaysInMySQL($room_item->getDaysUnusedBeforeDeletingRooms()-$room_item->getDaysSendMailBeforeDeletingRooms());
         	} else {
         		$datetime_border = getCurrentDateTimeMinusDaysInMySQL($room_item->getDaysUnusedBeforeDeletingRooms());
         	}
         	$room_manager->setLastLoginOlderLimit($datetime_border);
         	$room_manager->setContextLimit($room_item->getItemID());
         	$room_manager->setNotTemplateLimit();
         	$number1 = $room_manager->getCountAll();
         	$room_manager = $environment->getZzzCommunityManager();
         	$room_manager->setLastLoginOlderLimit($datetime_border);
         	$room_manager->setContextLimit($room_item->getItemID());
         	$room_manager->setNotTemplateLimit();
         	$number2 = $room_manager->getCountAll();
         	$number = $number1 + $number2;
         	if ( !empty($number) ) {
         		if ( !empty($number1) and $number1 == 1 ) {
	         	   $project = $translator->getMessage('COMMON_PROJECT_NUMBER_SI',$number1);
	         	} else {
	         		$project = $translator->getMessage('COMMON_PROJECT_NUMBER_PL',$number1);
	         	}
         		if ( !empty($number2) and $number2 == 1 ) {
	         	   $community = $translator->getMessage('COMMON_COMMUNITY_NUMBER_SI',$number2);
         		} else {
         			$community = $translator->getMessage('COMMON_COMMUNITY_NUMBER_PL',$number2);
         		}
	         	if ( !empty($_POST['room_deleting_days_unused_mail']) ) {
         			if ( $number == 1 ) {
         				$message_array[] = $translator->getMessage('CONFIGURATION_ROOM_DELETING_MAIL_INFO_SI',$number,$project,$community);
         			} else {
         				$message_array[] = $translator->getMessage('CONFIGURATION_ROOM_DELETING_MAIL_INFO',$number,$project,$community);        				 
         			}
	         	} else {
         			if ( $number == 1 ) {
         				$message_array[] = $translator->getMessage('CONFIGURATION_ROOM_DELETING_INFO_SI',$number,$project,$community);
         			} else {
         				$message_array[] = $translator->getMessage('CONFIGURATION_ROOM_DELETING_INFO',$number,$project,$community);        				 
         			}
	         	}
         	}
         	unset($room_manager);
         	unset($number);
         	unset($number1);
         	unset($number2);
         	unset($project);
         	unset($community);
   	   }
         
         if ( !empty($message_array) ) {
         	$params = array();
         	$params['environment'] = $environment;
         	$params['with_modifying_actions'] = true;
         	$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         	$errorbox->setText(implode(BRLF,$message_array));
         	$page->add($errorbox);
         }

      }
   }

   // Load form data from postvars
   if ( !empty($_POST) and !$is_saved) {
      $form->setFormPost($_POST);
   }

   // Load form data from database
   elseif ( isset($room_item) ) {
      $form->setItem($room_item);
   }

   $form->prepareForm();
   $form->loadValues();


   include_once('functions/curl_functions.php');
   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
}
?>