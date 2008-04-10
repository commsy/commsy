<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

include_once('classes/cs_topic_mail_form.php');
include_once('classes/cs_mail_view.php');
include_once('classes/cs_form_view.php');
include_once('classes/cs_mail.php');
include_once('functions/text_functions.php');
include_once('classes/cs_detail_view.php');

// option contains the name of the submit button, if this
// script is called as result of a form post
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

if ( isOption($command,getMessage('COMMON_CANCEL_BUTTON')) ) {
   $history = $session->getValue('history');
   redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$history[1]['parameter']);
} else {
   /* setup the form */
   // Construct the form
   $form = new cs_topic_mail_form($environment);
   $form->prepareForm();

   if ( isOption($command,getMessage('COMMON_MAIL_SEND_BUTTON')) ) { // send mail

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
         $email->set_from_email($mail['from_email']);
         $email->set_from_name($mail['from_name']);
	$email->set_to($mail['to']);
	$email->set_subject($mail['subject']);
         $email->set_message($mail['message']);
         if (getMessage('COMMON_YES') == $_POST['copytosender']) {
            $email->set_cc_to($current_user->getEmail());
         }
	if ( !empty($recipients_bcc) ) {
            $email->set_bcc_to(implode(",",$recipients_bcc));
	}

	$add_message = '';
	if ($counter == 1) {
	   $current_context = $environment->getCurrentContextItem();
	   if ($current_context->isProjectroom()) {
	      $add_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PROJECT_TOPIC_S',$current_context->getTitle(),$name_array[0]);
	   } else {
	      $add_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_COMMUNITY_TOPIC_S',$current_context->getTitle(),$name_array[0]);
	   }
	} elseif ($counter > 1) {
	   $current_context = $environment->getCurrentContextItem();
	   if ($current_context->isProjectroom()) {
	      $add_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PROJECT_TOPIC_PL',$current_context->getTitle(),implode(','.LF,$name_array));
	   } else {
	      $add_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_COMMUNITY_TOPIC_PL',$current_context->getTitle(),implode(','.LF,$name_array));
	   }
	}

	if (!empty($add_message)) {
	   $add_message = LF.LF.'---'.LF.$add_message;
	}
         $email->set_message($mail['message'].$add_message);

         // prepare formal data
         $tmp = array(getMessage('MAIL_FROM'), $mail['from_name']." &lt;".$mail['from_email']."&gt;");
         $formal_data[] = $tmp;

         $tmp = array(getMessage('REPLY_TO'), $mail['from_email']);
         $formal_data[] = $tmp;

	$tmp = array(getMessage('MAIL_TO'), implode(",", $recipients_display));
         $formal_data[] = $tmp;

         if (getMessage('COMMON_YES') == $_POST['copytosender']) {
            $tmp = array(getMessage('CC_TO'), $mail['from_name']." &lt;".$mail['from_email']."&gt;");
            $formal_data[] = $tmp;
         }

	if ( !empty($recipients_bcc) ) {
            $tmp = array(getMessage('MAIL_BCC_TO'), implode(",<br/>",$recipients_display_bcc));
            $formal_data[] = $tmp;
	}

         $tmp = array(getMessage('MAIL_SUBJECT'), $_POST['subject']);
         $formal_data[] = $tmp;

         $tmp = array(getMessage('MAIL_BODY'), $_POST['mailcontent'].$add_message);
         $formal_data[] = $tmp;

         if ($email->send()) {
            // send aknowledgement
            $detail_view = new cs_mail_view($environment, false);
            $detail_view->setFormalData($formal_data);
            $page->add($detail_view);
         } // ~email->send()
         else { // Mail could not be send: display error message.
            include_once('classes/cs_errorbox_view.php');
            $errorbox = new cs_errorbox_view($environment, true);
            $error_array = $email->getErrorArray();
            if ( !empty($error_array) ) {
               $error_string = $translator->getMessage('ERROR_SEND_EMAIL_TO');
	      foreach ($error_array as $error) {
	         $error = htmlentities($error);
	         $error = str_replace(',',BRLF,$error);
	         $error_string .= BRLF.$error;
	      }
            } else {
               $error_string = $translator->getMessage('ERROR_SEND_MAIL');
	   }

	   $detail_view = new cs_mail_view($environment, false);
            $detail_view->setFormalData($formal_data);
            $errorbox->setText($error_string);
            $page->add($errorbox);
	   $page->add($detail_view);
         }
      }  // ~form->check()
      else {
         $form_view = new cs_form_view($environment);
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
      $form_view = new cs_form_view($environment);
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