<?php
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

// get translation object
$translator = $environment->getTranslationObject();

//process task for material
//accept or reject request to make material worldwide available
// called by
if ( isset($_GET['mode']) and $environment->inCommunityRoom() ){
   $material_manager = $environment->getMaterialManager();
   $item = $material_manager->getItem($_GET['id']);

   $context_item = $environment->getCurrentContextItem();

   if ($_GET['mode']=='public') {

      // SET THE MATERIAL PUBLIC
      $item->setWorldPublic(2);

      // MAIL TO THE MODIFICATOR
      include_once('classes/cs_mail_obj.php');
      $mail_obj = new cs_mail_obj();

      //SENDER
      $sender[$current_user->getFullName()] = $current_user->getEmail();
      $mail_obj->setSender($sender);

      //RECEIVER
      $receiver_item = $item->getModificatorItem();
      $receiver[$receiver_item->getFullName()] = $receiver_item->getEmail();
      $mail_obj->addReceivers($receiver);

      //HEADLINE
      $mail_obj->setMailFormHeadLine($translator->getMessage('ADMIN_MAIL_ARCHIVE_SET_WOLRDPUBLIC_TITLE',$item->getTitle()));

      //SUBJECT AND BODY
      $user_language = $receiver_item->getLanguage();
      $save_language = $translator->getSelectedLanguage();
      $translator->setSelectedLanguage($user_language);

      $mail_subject = $translator->getMessage('MAIL_SUBJECT_MATERIAL_WORLDPUBLIC',$context_item->getTitle());
      $mail_body    = '';
      $mail_body   .= $translator->getEmailMessage('MAIL_BODY_HELLO',$receiver_item->getFullname());
      $mail_body   .= LF.LF;
      $mail_body   .= $translator->getEmailMessage('MAIL_BODY_MATERIAL_WORLDPUBLIC',$item->getTitle(),$context_item->getTitle());
      $mail_body   .= LF.LF;
      $mail_body   .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());

      $translator->setSelectedLanguage($save_language);
      unset($save_language);

      $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$context_item->getItemID().'&mod=material&fct=detail&iid='.$item->getItemID();
      $mail_body .= "\n\n";
      $mail_body .= $url;
      $mail_obj->setSubject($mail_subject);
      $mail_obj->setContent($mail_body);
      $history = $session->getValue('history');
      if ($history[0]['function'] == 'detail') {
         $back_hop = 1;
      } else {
         $back_hop = 0;
      }
      $mail_obj->setBackLink( $environment->getCurrentContextID(),
                              $history[$back_hop]['module'],
                              $history[$back_hop]['function'],
                              '');
   }
   if ($_GET['mode']=='not_public') {
      $item->setWorldPublic(0);

      // MAIL TO THE MODIFICATOR
      include_once('classes/cs_mail_obj.php');
      $mail_obj = new cs_mail_obj();

      //SENDER
      $sender[$current_user->getFullName()] = $current_user->getEmail();
      $mail_obj->setSender($sender);

      //RECEIVER
      $receiver_item = $item->getModificatorItem();
      $receiver[$receiver_item->getFullName()] = $receiver_item->getEmail();
      $mail_obj->addReceivers($receiver);

      //HEADLINE
      $mail_obj->setMailFormHeadLine($translator->getMessage('ADMIN_MAIL_ARCHIVE_SET_NOT_WOLRDPUBLIC_TITLE',$item->getTitle()));

      //SUBJECT AND BODY
      $user_language = $receiver_item->getLanguage();
      $save_language = $translator->getSelectedLanguage();
      $translator->setSelectedLanguage($user_language);

      $mail_subject = $translator->getMessage('MAIL_SUBJECT_MATERIAL_NOT_WORLDPUBLIC',$context_item->getTitle());
      $mail_body    = '';
      $mail_body   .= $translator->getEmailMessage('MAIL_BODY_HELLO',$receiver_item->getFullname());
      $mail_body   .= LF.LF;
      $mail_body   .= $translator->getEmailMessage('MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC',$item->getTitle(),$context_item->getTitle());
      $mail_body   .= LF.LF;
      $mail_body   .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());

      $translator->setSelectedLanguage($save_language);
      unset($save_language);

      $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID().'&mod=material&fct=detail&iid='.$item->getItemID();
      $mail_body .= "\n\n";
      $mail_body .= $url;
      $mail_obj->setSubject($mail_subject);
      $mail_obj->setContent($mail_body);
      $history = $session->getValue('history');
      if ($history[0]['function'] == 'detail') {
         $back_hop = 1;
      } else {
         $back_hop = 0;
      }
      $mail_obj->setBackLink( $environment->getCurrentContextID(),
                              $history[$back_hop]['module'],
                              $history[$back_hop]['function'],
                              '');
   }

   if ( isset($_GET['automail']) ) {
      if ( $_GET['automail'] == 'true' ) {
         $mail_obj->setSendMailAuto(true);
      }
   }
   $mail_obj->toSession();

   // TASK
   // open task can be closed
         $task_manager = $environment->getTaskManager();
         $task_list = $task_manager->getTaskListForItem($item);
         if ($task_list->getCount() > 0) {
            $task_item = $task_list->getFirst();
            while ($task_item) {
               if ($task_item->getStatus() == 'REQUEST' and $task_item->getTitle() == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC'or $task_item->getTitle() == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC_NEW_VERSION') {
                  $task_item->setStatus('CLOSED');
                  $task_item->save();
               }
               $task_item = $task_list->getNext();
            }
         }

   // save item and redirect
   $item->save();
   redirect($environment->getCurrentContextID(), 'mail', 'process', '');
}
?>