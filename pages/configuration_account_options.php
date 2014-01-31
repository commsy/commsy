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
//
include_once('functions/text_functions.php');

// Get the translator object
$translator = $environment->getTranslationObject();

$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

$context_item = $room_item;
$_GET['iid'] = $context_item->getItemID();
include_once('include/inc_delete_entry.php');

// Find out what to do
if ( isset($_POST['option']) and $_POST['option'] == $translator->getMessage('COMMON_DELETE_ROOM')) {
   $_GET['action'] = 'delete';
}
if ( isset($_GET['action']) and $_GET['action'] == 'delete' ) {
   $current_user_item = $environment->getCurrentUserItem();
   if ( !empty($context_item) ) {
      if ( $current_user_item->isModerator()
           or ( isset($context_item)
                and $context_item->isModeratorByUserID($current_user_item->getUserID(),$current_user_item->getAuthSource())
              )
         ) {
         $params = $environment->getCurrentParameterArray();
         $page->addDeleteBox(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params));
      }
   }
   unset($current_user_item);
}

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
}else{
   // option contains the name of the submit button, if this
   // script is called as result of a form post
   if (!empty($_POST['option'])) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }


   /* setup the form */
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(CONFIGURATION_ACCOUNT_OPTIONS_FORM,$class_params);
   unset($class_params);
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);


   $form_view->setAction(curl($environment->getCurrentContextID(),'configuration','account_options',''));

   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      redirect($environment->getCurrentContextID(),'configuration', 'index', '');
   }

   /* we called ourself as result of a form post */
   elseif ( isOption($command,$translator->getMessage('PREFERENCES_SAVE_BUTTON'))) {
      $form->setFormPost($_POST);
      $form->prepareForm();
      $form->loadValues();
      if ( $form->check() ) {
         global $c_use_soap_for_wiki;
         if ( isset($_POST['status']) ) {
            if ($_POST['status'] == '') {
	            if ( $environment->isArchiveMode() ) {
	            	$current_context->backFromArchive();
	            	$environment->deactivateArchiveMode();
	            }
	            
	            // old: should be impossible
	            else {
	            	/*
	            	// Fix: Find Group-Rooms if existing
	            	if ( $current_context->isProjectRoom()
	            			and $current_context->isGrouproomActive()
	            	   ) {
	            		$groupRoomList = $current_context->getGroupRoomList();
	            		 
	            		if( !$groupRoomList->isEmpty() ) {
	            			$room_item = $groupRoomList->getFirst();
	            	
	            			while($room_item) {
	            				// All GroupRooms have to be opened too
	            				$room_item->open();
	            				$room_item->save();
	            				if ( $environment->isArchiveMode() ) {
	            					$room_item->backFromArchive();
	            				}
	            				 
	            				$room_item = $groupRoomList->getNext();
	            			}
	            		}
	            	}
	            	// ~Fix
	            	*/
	            }
	            
	         	$current_context->open();
	            if($current_context->existWiki() and $c_use_soap_for_wiki){
	               $wiki_manager = $environment->getWikiManager();
	               $wiki_manager->openWiki();
	            }            
            	               
            } elseif ($_POST['status'] == 2) {
            	if ( !$current_context->isTemplate() ) {
	               if($current_context->existWiki() and $c_use_soap_for_wiki){
	                  $wiki_manager = $environment->getWikiManager();
	                  $wiki_manager->closeWiki();
	               }
	               $current_context->moveToArchive();
            	   $environment->activateArchiveMode();
            	}
            }
         }else{
            
            if ( $environment->isArchiveMode() ) {
            	$current_context->backFromArchive();
            	$environment->deactivateArchiveMode();
            }
            
            // old: should be impossible
            else {
            	/*
            	// Fix: Find Group-Rooms if existing
            	if ( $current_context->isProjectRoom()
            			and $current_context->isGrouproomActive()
            	   ) {
            		$groupRoomList = $current_context->getGroupRoomList();
            		 
            		if( !$groupRoomList->isEmpty() ) {
            			$room_item = $groupRoomList->getFirst();
            	
            			while($room_item) {
            				// All GroupRooms have to be opened too
            				$room_item->open();
            				$room_item->save();
            				if ( $environment->isArchiveMode() ) {
            					$room_item->backFromArchive();
            				}
            				 
            				$room_item = $groupRoomList->getNext();
            			}
            		}
            	}
            	// ~Fix
            	*/
            }
            
         	$current_context->open();
            if($current_context->existWiki() and $c_use_soap_for_wiki){
               $wiki_manager = $environment->getWikiManager();
               $wiki_manager->openWiki();
            }            
         }


         $commsy = $environment->getCurrentContextItem();
         $languages = $environment->getAvailableLanguageArray();
         foreach ($languages as $language) {
            if (!empty($_POST['agb_text_'.mb_strtoupper($language, 'UTF-8')])) {
               $agbtext_array[mb_strtoupper($language, 'UTF-8')] = $_POST['agb_text_'.mb_strtoupper($language, 'UTF-8')];
            } else {
               $agbtext_array[mb_strtoupper($language, 'UTF-8')] = '';
            }
         }

         if(($agbtext_array != $commsy->getAGBTextArray()) or ($_POST['agb_status'] != $commsy->getAGBStatus())) {
            $commsy->setAGBStatus($_POST['agb_status']);
            $commsy->setAGBTextArray($agbtext_array);
            $commsy->setAGBChangeDate();
         }
         // check member
         if ( isset($_POST['member_check']) ) {
            if ($_POST['member_check'] == 'never') {
               $requested_user_manager = $environment->getUserManager();
               $requested_user_manager->setContextLimit($environment->getCurrentContextID());
               $requested_user_manager->setRegisteredLimit();
               $requested_user_manager->select();
               $requested_user_list = $requested_user_manager->get();
               if (!empty($requested_user_list)){
                  $requested_user = $requested_user_list->getFirst();
                  while($requested_user){
                     $requested_user->makeUser();
                     $requested_user->save();
                     $task_manager = $environment->getTaskManager();
                     $task_list = $task_manager->getTaskListForItem($requested_user);
                     if (!empty($task_list)){
                        $task = $task_list->getFirst();
                        while($task){
                           if ($task->getStatus() == 'REQUEST' and ($task->getTitle() == 'TASK_USER_REQUEST' or $task->getTitle() == 'TASK_PROJECT_MEMBER_REQUEST')) {
                              $task->setStatus('CLOSED');
                              $task->save();
                           }
                           $task = $task_list->getNext();
                        }
                     }
                     $requested_user = $requested_user_list->getNext();
                  }
               }
               $commsy->setCheckNewMemberNever();
            } elseif ($_POST['member_check'] == 'always') {
               $commsy->setCheckNewMemberAlways();
            } elseif ($_POST['member_check'] == 'sometimes') {
               $commsy->setCheckNewMemberSometimes();
            } elseif ($_POST['member_check'] == 'withcode') {
               $commsy->setCheckNewMemberWithCode();
               $commsy->setCheckNewMemberCode($_POST['code']);
            }
         }
         // open for guests
         if ( isset($_POST['open_for_guests']) ) {
            if ($_POST['open_for_guests'] == 'open') {
                $commsy->setOpenForGuests();
            } elseif ($_POST['open_for_guests'] == 'closed') {
               $commsy->setClosedForGuests();
            }
         }
         if ( !$environment->isArchiveMode() ) {
            $commsy->save();
         }
#         redirect($environment->getCurrentContextID(),'configuration', 'index', '');
         $form_view->setItemIsSaved();
         $is_saved = true;

         // editor agb acceptance
         $current_user = $environment->getCurrentUserItem();
         $current_user->setAGBAcceptance();
         if ( !$environment->isArchiveMode() ) {
            $current_user->save();
         }
      }
   } else {
      $current_context = $environment->getCurrentContextItem();
      $form->setItem($current_context);
      if ( !empty($_POST)) {
         $form->setFormPost($_POST);
      }
      $form->prepareForm();
      $form->loadValues();
   }

   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
}
?>