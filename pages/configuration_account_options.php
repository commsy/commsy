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

$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $room_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
}  elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
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

   /* we are not called as a result of a form post, so just display the form */
   if ( empty($command) ) {
      $current_context = $environment->getCurrentContextItem();
      $form->setItem($current_context);
      if ( !empty($_POST)) {
         $form->setFormPost($_POST);
      }
      $form->prepareForm();
      $form->loadValues();
   }
   elseif ( isOption($command, getMessage('COMMON_CANCEL_BUTTON')) ) {
     redirect($environment->getCurrentContextID(),'configuration', 'index', '');
   }

   /* we called ourself as result of a form post */
   elseif ( isOption($command,getMessage('PREFERENCES_SAVE_BUTTON'))) {
      $form->setFormPost($_POST);
      $form->prepareForm();
      $form->loadValues();
      if ( $form->check() ) {


         if ( isset($_POST['status']) ) {
            if ($_POST['status'] == '') {
               $current_context->open();
            } elseif ($_POST['status'] == 2) {
               $current_context->close();
            }
         }else{
            $current_context->open();
         }


         $commsy = $environment->getCurrentContextItem();
         $languages = $environment->getAvailableLanguageArray();
         foreach ($languages as $language) {
            if (!empty($_POST['agb_text_'.strtoupper($language)])) {
               $agbtext_array[strtoupper($language)] = $_POST['agb_text_'.strtoupper($language)];
            } else {
               $agbtext_array[strtoupper($language)] = '';
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
         $commsy->save();
#         redirect($environment->getCurrentContextID(),'configuration', 'index', '');
         $form_view->setItemIsSaved();
         $is_saved = true;

         // editor agb acceptance
         $current_user = $environment->getCurrentUserItem();
         $current_user->setAGBAcceptance();
         $current_user->save();
      }
   }

   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   }else{
      $page->add($form_view);
   }
}

?>