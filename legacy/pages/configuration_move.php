<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

// Get item to be edited
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('lost room id',E_USER_ERROR);
}

$manager = $environment->getRoomManager();
$item = $manager->getItem($current_iid);
$current_user = $environment->getCurrentUserItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ( !empty($current_iid) and !isset($item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !$environment->inPortal() or !$current_user->isModerator() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
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

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      $params = array();
      $params['room_id'] = $current_iid;
      redirect($environment->getCurrentContextID(),'home', 'index', $params);
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $form = $class_factory->getClass(CONFIGURATION_MOVE_FORM,array('environment' => $environment));

      // Load form data from postvars
      if ( !empty($_POST) ) {
         $form->setFormPost($_POST);
      }

      // Load form data from database
      elseif ( isset($item) ) {
         $form->setItem($item);
      }

      else {
         include_once('functions/error_functions.php');
         trigger_error('configuration_move was called in an unknown manner', E_USER_ERROR);
      }

      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command)
          and isOption($command, $translator->getMessage('PORTAL_MOVE_ROOM_REGISTER_BUTTON'))
        ) {

         $correct = $form->check();
         if ( $correct ) {

           // save item
           if (!empty($_POST['with_linked_rooms'])) {
              $item->lockForMoveWithLinkedRooms();
           } else {
              $item->lockForMove();
           }
           $item->save();

           // save task in portal
            $task_manager = $environment->getTaskManager();
            $task_item = $task_manager->getNewItem();
            $task_item->getCreatorItem($environment->getCurrentUserItem());
            $task_item->setTitle('TASK_ROOM_MOVE');
            $task_item->setStatus('REQUEST');
            $task_item->setContextID($_POST['portal_id']);
            $task_item->setItem($item);
            $task_item->save();

           // send mail to modertors of aim portal
           $current_portal = $environment->getCurrentPortalItem();
            $portal_manager = $environment->getPortalManager();
           $aim_portal = $portal_manager->getItem($_POST['portal_id']);
            $user_list = $aim_portal->getModeratorList();
           $language = $aim_portal->getLanguage();
            $email_addresses = array();
            $user_item = $user_list->getFirst();
            $recipients = '';
            while ($user_item) {
               $want_mail = $user_item->getOpenRoomWantMail();
               if (!empty($want_mail) and $want_mail == 'yes') {
                 if ($language == 'user' and $user_item->getLanguage() == 'browser') {
                     $email_addresses[$environment->getSelectedLanguage()][] = $user_item->getEmail();
                 } elseif ($language == 'user') {
                     $email_addresses[$user_item->getLanguage()][] = $user_item->getEmail();
                 } else {
                     $email_addresses[$language][] = $user_item->getEmail();
                 }
                  $recipients .= $user_item->getFullname().LF;
               }
               $user_item = $user_list->getNext();
            }
           $translator = $environment->getTranslationObject();
            foreach ($email_addresses as $key => $value) {
              $translator->setSelectedLanguage($key);
               if (count($value) > 0) {
                  include_once('classes/cs_mail.php');
                  $mail = new cs_mail();
                  $mail->set_to(implode(',',$value));

                   global $symfonyContainer;
                   $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                   $mail->set_from_email($emailFrom);

                  $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_portal->getTitle()));
                  $mail->set_reply_to_name($current_user->getFullname());
                  $mail->set_reply_to_email($current_user->getEmail());
                  $mail->set_subject($translator->getMessage('MOVE_ROOM_MAIL_SUBJECT',$aim_portal->getTitle()));
                  $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                  $body .= LF.LF;
                  switch ( mb_strtoupper($item->getLanguage(), 'UTF-8') ){
                     case 'DE':
                        $temp_language = $translator->getMessage('DE');
                        break;
                     case 'EN':
                        $temp_language = $translator->getMessage('EN');
                        break;
                     case 'RU':
                        $temp_language = $translator->getMessage('RU');
                        break;
                     case 'USER':
                        $temp_language = $translator->getMessage('CONTEXT_LANGUAGE_USER');
                        break;
                     default:
                        $temp_language = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' configuration_move(183) ');
                        break;
                  }

                  if ($item->isProjectRoom()) {
                     $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_PR',$item->getTitle(),$temp_language,$current_user->getFullname());
                  } else {
                     $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_CR',$item->getTitle(),$temp_language,$current_user->getFullname());
                  }
                  $body .= LF.LF;
                  $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
                  $body .= LF;
                  $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$aim_portal->getItemID();
                  $mail->set_message($body);
                  $mail->send();
               }
            }

            // Redirect
            $params = array();
            $params['room_id'] = $current_iid;
            redirect($environment->getCurrentContextID(),'home', 'index', $params);
         }
      }

      // Display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$params);
      unset($params);
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
      $form_view->setForm($form);
      $page->addForm($form_view);
   }
}
?>