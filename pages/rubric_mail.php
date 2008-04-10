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

include_once('classes/cs_rubric_mail_form.php');
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
if (!empty($_GET['iid'])) {
   $iid = $_GET['iid'];
   $manager = $environment->getItemManager();
   $rubric_item = $manager->getItem($iid);
} else {
   $command = '';
}


if ( isOption($command,getMessage('COMMON_CANCEL_BUTTON')) ) {
   $history = $session->getValue('history');
   redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$history[1]['parameter']);
} else {
   /* setup the form */
   // Construct the form
   $form = new cs_rubric_mail_form($environment);
   $form->setItem($rubric_item);

   $group_list = $rubric_item->getLinkedItemList(CS_GROUP_TYPE);
   $group_item = $group_list->getFirst();
   $groups = array();
   while ($group_item){
      $groups[] = $group_item->getItemID();
      $group_item = $group_list->getNext();
   }
   $form->setGroups($groups);

   $institution_list = $rubric_item->getLinkedItemList(CS_INSTITUTION_TYPE);
   $institution_item = $institution_list->getFirst();
   $institutions = array();
   while ($institution_item){
      $institutions[] .= $institution_item->getItemID();
      $institution_item = $institution_list->getNext();
   }
   $form->setInstitutions($institutions);

   $form->prepareForm();

   if ( isOption($command,getMessage('COMMON_MAIL_SEND_BUTTON')) ) { // send mail

      $form->setFormPost($_POST);
      $form->loadValues();
      if($form->check()) {
         $user_manager = $environment->getUserManager();
         $user_manager->resetLimits();
         $user_manager->setUserLimit();
         $recipients = array();
         $recipients_display = array();
         $recipients_bcc = array();
         $recipients_display_bcc = array();
         $label_manager = $environment->getLabelManager();
         $topic_list = new cs_list();

         if (isset($_POST['send_to_all'])) {	//send to all members of a community room, if no institutions and topics are availlable
            $cid = $environment->getCurrentContextId();
            $user_manager->setContextLimit($cid);
            $user_manager->select();
            $user_list = $user_manager->get();
            $user_item = $user_list->getFirst();
            while($user_item) {
               if ($user_item->isEmailVisible()) {
                     $recipients[] = $user_item->getFullName()." <".$user_item->getEmail().">";
                     $recipients_display[] = $user_item->getFullName()." &lt;".$user_item->getEmail()."&gt;";
               } else {
                     $recipients_bcc[] = $user_item->getFullName()." <".$user_item->getEmail().">";
                     $recipients_display_bcc[] = $user_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
               }
               $user_item = $user_list->getNext();
            }
         }

         if ( isset($_POST[CS_TOPIC_TYPE]) and !empty($_POST[CS_TOPIC_TYPE]) ){
            $topic_list = $label_manager->getItemList($_POST[CS_TOPIC_TYPE]);
         }
         $topic_item = $topic_list->getFirst();
         while ($topic_item){
            // get selected rubrics for inclusion in recipient list
            $user_manager->setTopicLimit($topic_item->getItemID());
            $user_manager->select();
            $user_list = $user_manager->get();
            $user_item = $user_list->getFirst();
            while($user_item) {
               if ($user_item->isEmailVisible()) {
                     $recipients[] = $user_item->getFullName()." <".$user_item->getEmail().">";
                     $recipients_display[] = $user_item->getFullName()." &lt;".$user_item->getEmail()."&gt;";
               } else {
                     $recipients_bcc[] = $user_item->getFullName()." <".$user_item->getEmail().">";
                     $recipients_display_bcc[] = $user_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
               }
               $user_item = $user_list->getNext();
            }
            $topic_item = $topic_list->getNext();
         }
         $user_manager->resetLimits();
         $label_manager = $environment->getLabelManager();
         $group_list = new cs_list();
         if ( isset($_POST['groups']) and !empty($_POST['groups']) ){
            $group_list = $label_manager->getItemList($_POST['groups']);
         }
         $group_item = $group_list->getFirst();
         while ($group_item){
            // get selected rubrics for inclusion in recipient list
            $user_manager->setGroupLimit($group_item->getItemID());
            $user_manager->select();
            $user_list = $user_manager->get();
            $user_item = $user_list->getFirst();
            while($user_item) {
               if ($user_item->isEmailVisible()) {
                     $recipients[] = $user_item->getFullName()." <".$user_item->getEmail().">";
                     $recipients_display[] = $user_item->getFullName()." &lt;".$user_item->getEmail()."&gt;";
               } else {
                     $recipients_bcc[] = $user_item->getFullName()." <".$user_item->getEmail().">";
                     $recipients_display_bcc[] = $user_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
               }
               $user_item = $user_list->getNext();
            }
            $group_item = $group_list->getNext();
         }
         $user_manager->resetLimits();
         $label_manager = $environment->getLabelManager();
         $institution_list = new cs_list();
         if ( isset($_POST['institutions']) and !empty($_POST['institutions']) ){
            $institution_list = $label_manager->getItemList($_POST['institutions']);
         }
         $institution_item = $institution_list->getFirst();
         while ($institution_item){
            // get selected rubrics for inclusion in recipient list
            $user_manager->setInstitutionLimit($institution_item->getItemID());
            $user_manager->select();
            $user_list = $user_manager->get();
            $user_item = $user_list->getFirst();
            while($user_item) {
               if ($user_item->isEmailVisible()) {
                     $recipients[] = $user_item->getFullName()." <".$user_item->getEmail().">";
                     $recipients_display[] = $user_item->getFullName()." &lt;".$user_item->getEmail()."&gt;";
               } else {
                     $recipients_bcc[] = $user_item->getFullName()." <".$user_item->getEmail().">";
                     $recipients_display_bcc[] = $user_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
               }
               $user_item = $user_list->getNext();
            }
            $institution_item = $institution_list->getNext();
         }

         $recipients = array_unique($recipients);
         $recipients_display = array_unique($recipients_display);
         if ( $environment->inGroupRoom() and empty($recipients_display) ) {
            $cid = $environment->getCurrentContextId();
            $user_manager->setContextLimit($cid);
            $count = $user_manager->getCountAll();
            unset($user_manager);
            if ( $count == 1 ) {
               $text = getMessage('COMMON_MAIL_ALL_ONE_IN_ROOM',$count);
            } else {
               $text = getMessage('COMMON_MAIL_ALL_IN_ROOM',$count);
            }
            $recipients_display[] = $text;
         }
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

         if ($email->send()) {
            // send aknowledgement

            $detail_view = new cs_mail_view($environment, false);

            // prepare formal data
            $tmp = array(getMessage('MAIL_FROM'), $mail['from_name']." <".$mail['from_email'].">");
            $formal_data[] = $tmp;

            $tmp = array(getMessage('REPLY_TO'), $mail['from_email']);
            $formal_data[] = $tmp;

            $tmp = array(getMessage('MAIL_TO'), implode(", ", $recipients_display));
            $formal_data[] = $tmp;

            if (getMessage('COMMON_YES') == $_POST['copytosender']) {
               $tmp = array(getMessage('CC_TO'), $mail['from_name']." <".$mail['from_email'].">");
               $formal_data[] = $tmp;
            }

            if ( !empty($recipients_bcc) ) {
               $tmp = array(getMessage('MAIL_BCC_TO'), implode(",<br/>",$recipients_display_bcc));
               $formal_data[] = $tmp;
            }

            $tmp = array(getMessage('MAIL_SUBJECT'), $_POST['subject']);
            $formal_data[] = $tmp;

            $tmp = array(getMessage('MAIL_BODY'), $_POST['mailcontent']);
            $formal_data[] = $tmp;
            $detail_view->setFormalData($formal_data);

            $page->add($detail_view);

         } // ~email->send()
         else { // Mail could not be send: display error message.
            $form = new cs_form();
            include_once('classes/cs_errorbox_view.php');
            $errorbox = new cs_errorbox_view($environment, true);
            $error_string = "*". getMessage('ERROR_SEND_MAIL')."*<br />";
            $error_string .= "<br />*".getMessage('ERROR_MAIL_FROM')."*<br />".$mail['from_name'];
            $error_string .= "<br />*".getMessage('ERROR_MAIL_REPLY_TO')."*<br />".$mail['from_email'];
            $error_string .= "<br />*".getMessage('ERROR_MAIL_TO')."*<br />".implode(", ", $recipients_display);
            if (getMessage('COMMON_YES') == $_POST['copytosender']) {
               $error_string .= "<br />*".getMessage('ERROR_MAIL_CC')."*<br />".$mail['from_email'];
            }
            if ( !empty($recipients_bcc) ) {
               $error_string .= "<br />*".getMessage('MAIL_BCC_TO')."*<br />".implode(", ", $recipients_display_bcc);
            }
            $error_string .= "<br />*".getMessage('ERROR_MAIL_SUBJECT')."*<br />".$_POST['subject'];
            $error_string .= "<br />*".getMessage('ERROR_MAIL_CONTENT')."*<br />".$_POST['mailcontent'];
            $errorbox->setText($error_string);
            $page->add($errorbox);
         }
      }  // ~form->check()
      else {
         $form_view = new cs_form_view($environment);
         if ( isset($_GET['iid']) ){
            $params = array();
            $params['iid'] = $_GET['iid'];
            $form_view->setAction(curl($environment->getCurrentContextID(),'rubric','mail',$params));
            unset($params);
         } else {
            $form_view->setAction(curl($environment->getCurrentContextID(),'rubric','mail',''));
         }
         $form_view->setForm($form);
         $page->add($form_view);
      }
   } else {  // first call of this page
      $form->loadValues();
      $form_view = new cs_form_view($environment);
      if ( isset($_GET['iid']) ){
         $params = array();
         $params['iid'] = $_GET['iid'];
         $form_view->setAction(curl($environment->getCurrentContextID(),'rubric','mail',$params));
         unset($params);
      } else {
         $form_view->setAction(curl($environment->getCurrentContextID(),'rubric','mail',''));
      }

      $form_view->setForm($form);
      $page->add($form_view);
   }
}
$page->setPageName(getMessage('COMMON_PAGETITLE_MAIL'));
?>