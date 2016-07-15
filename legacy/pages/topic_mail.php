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

include_once('classes/cs_mail.php');
include_once('functions/text_functions.php');

// Get the translator object
$translator = $environment->getTranslationObject();

// option contains the name of the submit button, if this
// script is called as result of a form post
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

if ( isOption($command,$translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
   $history = $session->getValue('history');
   redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$history[1]['parameter']);
} else {
   /* setup the form */
   // Construct the form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(TOPIC_MAIL_FORM,$class_params);
   unset($class_params);
   $form->prepareForm();

   if ( isOption($command,$translator->getMessage('COMMON_MAIL_SEND_BUTTON')) ) { // send mail

      $form->setFormPost($_POST);
      $form->loadValues();
      if($form->check()) {
         $manager = $environment->getTopicManager();
         $topic_iids = $_POST['topic'];
   $recipients = array();
         $recipients_display = array();
   $recipients_bcc = array();
         $recipients_display_bcc = array();
   $counter = 0;
   $name_array = array();

         foreach ($topic_iids as $iid) {
         $counter++;
      // get selected topics for inclusion in recipient list
      $item = $manager->getItem($iid);
      $name_array[] = $item->getTitle();
      $user_list = $item->getMemberItemList();
      $user_item = $user_list->getFirst();
      while($user_item) {
         if ( $user_item->isUser() ){
            if ($user_item->isEmailVisible()) {
               $recipients[] = $user_item->getFullName()." <".$user_item->getEmail().">";
               $recipients_display[] = $user_item->getFullName()." &lt;".$user_item->getEmail()."&gt;";
            } else {
               $recipients_bcc[] = $user_item->getFullName()." <".$user_item->getEmail().">";
               $recipients_display_bcc[] = $user_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
            }
         }
         $user_item = $user_list->getNext();
      }
         }

         $recipients = array_unique($recipients);
         $recipients_display = array_unique($recipients_display);
         $recipients_bcc = array_unique($recipients_bcc);
         $recipients_display_bcc = array_unique($recipients_display_bcc);

         $current_user = $environment->getCurrentUser();

         $mail['from_name'] = $current_user->getFullName();
         $mail['from_email'] = $current_user->getEmail();
         $mail['to'] = implode(", ", $recipients);
         $mail['subject'] = $_POST['subject'];
         $mail['message'] = $_POST['mailcontent'];

         $email = new cs_mail();
         //$email->set_from_email($mail['from_email']);
         //$email->set_from_name($mail['from_name']);
         $email->set_from_email($environment->getServerItem()->getDefaultSenderAddress());
         $email->set_from_name($environment->getCurrentPortalItem()->getTitle());
   $email->set_to($mail['to']);
   $email->set_subject($mail['subject']);
         $email->set_message($mail['message']);
         if ($translator->getMessage('COMMON_YES') == $_POST['copytosender']) {
            $email->set_cc_to($current_user->getEmail());
         }
   if ( !empty($recipients_bcc) ) {
            $email->set_bcc_to(implode(",",$recipients_bcc));
   }

   $add_message = '';
   $current_context = $environment->getCurrentContextItem();
   $context_title = str_ireplace('&amp;', '&', $current_context->getTitle());
   if ($counter == 1) {
      if ($current_context->isProjectroom()) {
         $add_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PROJECT_TOPIC_S',$context_title,$name_array[0]);
      } else {
         $add_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_COMMUNITY_TOPIC_S',$context_title,$name_array[0]);
      }
   } elseif ($counter > 1) {
      if ($current_context->isProjectroom()) {
         $add_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PROJECT_TOPIC_PL',$context_title,implode(','.LF,$name_array));
      } else {
         $add_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_COMMUNITY_TOPIC_PL',$context_title,implode(','.LF,$name_array));
      }
   }

   if (!empty($add_message)) {
      $add_message = LF.LF.'---'.LF.$add_message;
   }
         $email->set_message($mail['message'].$add_message);

         // prepare formal data
         $tmp = array($translator->getMessage('MAIL_FROM'), $mail['from_name']." &lt;".$mail['from_email']."&gt;");
         $formal_data[] = $tmp;

         $tmp = array($translator->getMessage('REPLY_TO'), $mail['from_email']);
         $formal_data[] = $tmp;

   $tmp = array($translator->getMessage('MAIL_TO'), implode(",", $recipients_display));
         $formal_data[] = $tmp;

         if ($translator->getMessage('COMMON_YES') == $_POST['copytosender']) {
            $tmp = array($translator->getMessage('CC_TO'), $mail['from_name']." &lt;".$mail['from_email']."&gt;");
            $formal_data[] = $tmp;
         }

   if ( !empty($recipients_bcc) ) {
            $tmp = array($translator->getMessage('MAIL_BCC_TO'), implode(",<br/>",$recipients_display_bcc));
            $formal_data[] = $tmp;
   }

         $tmp = array($translator->getMessage('MAIL_SUBJECT'), $_POST['subject']);
         $formal_data[] = $tmp;

         $tmp = array($translator->getMessage('MAIL_BODY'), $_POST['mailcontent'].$add_message);
         $formal_data[] = $tmp;

         if ($email->send()) {
            // send aknowledgement
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = false;
            $detail_view = $class_factory->getClass(MAIL_VIEW,$params);
            unset($params);
            $detail_view->setFormalData($formal_data);
            $page->add($detail_view);
         } // ~email->send()
         else { // Mail could not be send: display error message.
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = true;
            $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            unset($params);
            $error_array = $email->getErrorArray();
            if ( !empty($error_array) ) {
               $error_string = $translator->getMessage('ERROR_SEND_EMAIL_TO');
               foreach ($error_array as $error) {
                  $error = htmlentities($error, ENT_NOQUOTES, 'UTF-8');
                  $error = str_replace(',',BRLF,$error);
                  $error_string .= BRLF.$error;
               }
            } else {
               $error_string = $translator->getMessage('ERROR_SEND_MAIL');
            }

            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = false;
            $detail_view = $class_factory->getClass(MAIL_VIEW,$params);
            unset($params);
            $detail_view->setFormalData($formal_data);
            $errorbox->setText($error_string);
            $page->add($errorbox);
            $page->add($detail_view);
         }
      }  // ~form->check()
      else {
         $class_params = array();
         $class_params['environment'] = $environment;
         $class_params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
         unset($class_params);
         if ( isset($_GET['iid']) ){
            $label_manager =  $environment->getLabelManager();
            $topic_item = $label_manager->getItem($_GET['iid']);
            $params = array();
            $params['iid'] = $topic_item->getItemID();
            $form_view->setAction(curl($environment->getCurrentContextID(),CS_TOPIC_TYPE,'mail',$params));
            unset($params);
         } else {
            $form_view->setAction(curl($environment->getCurrentContextID(),CS_TOPIC_TYPE,'mail',''));
         }
         $form_view->setForm($form);
         $page->add($form_view);
      }
   } else {  // first call of this page
      $form->loadValues();
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      unset($class_params);
      if ( isset($_GET['iid']) ){
         $label_manager =  $environment->getLabelManager();
         $topic_item = $label_manager->getItem($_GET['iid']);
         $params = array();
         $params['iid'] = $topic_item->getItemID();
         $form_view->setAction(curl($environment->getCurrentContextID(),CS_TOPIC_TYPE,'mail',$params));
         unset($params);
      }else{
         $form_view->setAction(curl($environment->getCurrentContextID(),CS_TOPIC_TYPE,'mail',''));
      }
      $form_view->setForm($form);
      $page->add($form_view);
   }
}
?>