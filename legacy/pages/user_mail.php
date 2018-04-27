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

// Get the translator object
$translator = $environment->getTranslationObject();

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

// cancel edit process
if ( isOption($command,$translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
   $history = $session->getValue('history');
   if (empty($history[1]['module'])) {
      $module = 'home';
   } else {
      $module = $history[1]['module'];
   }
   if (empty($history[1]['function'])) {
      $funct = 'index';
   } else {
      $funct = $history[1]['function'];
   }
   if (empty($history[1]['parameter'])) {
      $param = '';
   } else {
      $param = $history[1]['parameter'];
   }
   redirect($environment->getCurrentContextID(),$module,$funct,$param);
}

// show form or send email
else {
   // include form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(USER_MAIL_FORM,$class_params);
   unset($class_params);

   // Find current search text
   if ( isset($_GET['search']) ) {
      $search = $_GET['search'];
   } elseif ( isset($_POST['search']) ) {
      $search = $_POST['search'];
   } else {
      $search = '';
   }

   // Find current group selection
   if ( isset($_GET['selgroup']) and $_GET['selgroup'] !='-2') {
      $selgroup = $_GET['selgroup'];
   } elseif ( isset($_POST['selgroup']) ) {
      $selgroup = $_POST['selgroup'];
   } else {
      $selgroup = 0;
   }

   // Find current topic selection
   if ( isset($_GET['seltopic']) and $_GET['seltopic'] !='-2') {
      $seltopic = $_GET['seltopic'];
   } elseif ( isset($_POST['seltopic']) ) {
      $seltopic = $_POST['seltopic'];
   } else {
      $seltopic = 0;
   }

   // Find current institution selection
   if ( isset($_GET['selinstitution']) and $_GET['selinstitution'] !='-2') {
      $selinstitution = $_GET['selinstitution'];
   } elseif ( isset($_POST['selinstitution']) ) {
      $selinstitution = $_POST['selinstitution'];
   } else {
      $selinstitution = 0;
   }

   // Find current status selection
   if ( isset($_GET['selstatus']) and $_GET['selstatus'] !='-2') {
      $selstatus = $_GET['selstatus'];
   } elseif ( isset($_POST['selstatus']) ) {
      $selstatus = $_POST['selstatus'];
   } else {
      $selstatus = 0;
   }

   $form->setSearchLimit($search);
   $form->setGroupLimit($selgroup);
   $form->setTopicLimit($seltopic);
   $form->setInsitutionLimit($selinstitution);
   $form->setStatusLimit($selstatus);

   $form->prepareForm();

   // show form
   if (empty($command)) {
      $form->loadValues();
   }

   // send email
   else {
      $form->setFormPost($_POST);
      $form->loadValues();
      if ($form->check()) {
         include_once('classes/cs_mail.php');
         $mail = new cs_mail();
         $receivers = '';
         if (!empty($_POST['receivers'])) {
            $receivers = implode(',',$_POST['receivers']);
         } elseif (!empty($_POST['receiver_email'])) {
            $receivers = $_POST['receiver_email'];
         } else {
            include_once('functions/error_functions.php');
            trigger_error('no reveiver selected',E_USER_ERROR);
         }
         $mail->set_to($receivers);

          global $symfonyContainer;
          $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
          $mail->set_from_email($emailFrom);

         $mail->set_from_name($environment->getCurrentPortalItem()->getTitle());
         $mail->set_subject($_POST['subject']);
         $mail->set_message($_POST['content']);
         $success = $mail->send();
         if ($success) {
            // redirect
            $history = $session->getValue('history');
            if (empty($history[1])) {
               redirect($environment->getCurrentContextID(),'home','index','');
            } else {
               redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$history[1]['parameter']);
            }
         } else {
            // error sending email
         }
      }
   }
}

// display form
$class_params = array();
$class_params['environment'] = $environment;
$class_params['with_modifying_actions'] = true;
$form_view = $class_factory->getClass(FORM_VIEW,$class_params);
unset($class_params);
$form_view->setAction(curl($environment->getCurrentContextID(),'user','mail',''));
$form_view->setForm($form);
$page->add($form_view);

$current_user = $environment->getCurrentUserItem();
$history = $session->getValue('history');
?>