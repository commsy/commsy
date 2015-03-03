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

$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $room_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( !$room_item->isOpen() and !$room_item->isTemplate() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif (!$current_user->isModerator()) {
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
   } elseif (isset($_POST['mail_text']) ) {
      $command = $translator->getMessage('COMMON_CHOOSE_BUTTON');
   } else {
      $command = '';
   }

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      redirect( $environment->getCurrentContextID(),
                $environment->getCurrentModule(),
                $environment->getCurrentFunction(),
                '' );
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $form = $class_factory->getClass(CONFIGURATION_MAIL_FORM,array('environment' => $environment));
      // Display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
      unset($params);

      if ( isOption($command, $translator->getMessage('COMMON_CHOOSE_BUTTON')) ) {
         $translator = $environment->getTranslationObject();
         $languages = $environment->getAvailableLanguageArray();
         if ($_POST['mail_text'] == -1) {
            $message_tag = '';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_HELLO') {
            $message_tag = 'MAIL_BODY_HELLO';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_CIAO') {
            $message_tag = 'MAIL_BODY_CIAO';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_ACCOUNT_DELETE') {
            $message_tag = 'MAIL_BODY_USER_ACCOUNT_DELETE';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_ACCOUNT_LOCK') {
            $message_tag = 'MAIL_BODY_USER_ACCOUNT_LOCK';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_STATUS_USER') {
            $message_tag = 'MAIL_BODY_USER_STATUS_USER';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_STATUS_MODERATOR') {
            $message_tag = 'MAIL_BODY_USER_STATUS_MODERATOR';
         }elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_MAKE_CONTACT_PERSON') {
            $message_tag = 'MAIL_BODY_USER_MAKE_CONTACT_PERSON';
         }elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON') {
            $message_tag = 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_ACCOUNT_PASSWORD') {
            $message_tag = 'MAIL_BODY_USER_ACCOUNT_PASSWORD';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_ACCOUNT_MERGE') {
            $message_tag = 'MAIL_BODY_USER_ACCOUNT_MERGE';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_PASSWORD_CHANGE') {
            $message_tag = 'MAIL_BODY_USER_PASSWORD_CHANGE';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_MATERIAL_WORLDPUBLIC') {
            $message_tag = 'MAIL_BODY_MATERIAL_WORLDPUBLIC';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_MATERIAL_NOT_WORLDPUBLIC') {
            $message_tag = 'MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_LOCK') {
            $message_tag = 'MAIL_BODY_ROOM_LOCK';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_UNLOCK') {
            $message_tag = 'MAIL_BODY_ROOM_UNLOCK';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_UNLINK') {
            $message_tag = 'MAIL_BODY_ROOM_UNLINK';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_DELETE') {
            $message_tag = 'MAIL_BODY_ROOM_DELETE';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_OPEN') {
            $message_tag = 'MAIL_BODY_ROOM_OPEN';
         } elseif ($_POST['mail_text'] == 'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON') {
            $message_tag = 'EMAIL_BODY_PASSWORD_EXPIRATION_SOON';
         } elseif ($_POST['mail_text'] == 'EMAIL_CHOICE_PASSWORD_EXPIRATION') {
            $message_tag = 'EMAIL_BODY_PASSWORD_EXPIRATION';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_PROJECT_ARCHIVE_INFO') {
            $message_tag = 'PROJECT_MAIL_BODY_ARCHIVE_INFO';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_PROJECT_ARCHIVE') {
            $message_tag = 'PROJECT_MAIL_BODY_ARCHIVE';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_PROJECT_DELETE_INFO') {
            $message_tag = 'PROJECT_MAIL_BODY_DELETE_INFO';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_PROJECT_DELETE') {
            $message_tag = 'PROJECT_MAIL_BODY_DELETE';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_LOCK_NEXT') {
            $message_tag = 'EMAIL_INACTIVITY_LOCK_NEXT_BODY';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_LOCK_TOMORROW') {
            $message_tag = 'EMAIL_INACTIVITY_LOCK_TOMORROW_BODY';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_LOCK_NOW') {
            $message_tag = 'EMAIL_INACTIVITY_LOCK_NOW_BODY';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_DELETE_NEXT') {
            $message_tag = 'EMAIL_INACTIVITY_DELETE_NEXT_BODY';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_DELETE_TOMORROW') {
            $message_tag = 'EMAIL_INACTIVITY_DELETE_TOMORROW_BODY';
         } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_DELETE_NOW') {
            $message_tag = 'EMAIL_INACTIVITY_DELETE_NOW_BODY';
         } else {
            include_once('functions/error_functions.php');
            trigger_error('choice of mail text lost',E_USER_WARNING);
         }
         foreach ($languages as $language) {
            if (!empty($message_tag)) {
               $values[$language] = $translator->getEmailMessageInLang($language,$message_tag);
            } else {
               $values[$language] = '';
            }
         }
      }

      // Load form data from postvars
      if ( !empty($_POST) and !empty($values)) {
         $temp_values = $_POST;
         $temp_values['text'] = $values;
         $form->setFormPost($temp_values);
      } elseif ( !empty($_POST) ) {
         $form->setFormPost($_POST);
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and ( isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) or
                                  isOption($command, $translator->getMessage('COMMON_CHOOSE_BUTTON')) )
         ) {

         $correct = $form->check();
         if ( $correct and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {

           if ($_POST['mail_text'] == 'MAIL_CHOICE_HELLO') {
               $message_tag = 'MAIL_BODY_HELLO';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_CIAO') {
               $message_tag = 'MAIL_BODY_CIAO';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_ACCOUNT_DELETE') {
               $message_tag = 'MAIL_BODY_USER_ACCOUNT_DELETE';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_ACCOUNT_LOCK') {
               $message_tag = 'MAIL_BODY_USER_ACCOUNT_LOCK';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_STATUS_USER') {
               $message_tag = 'MAIL_BODY_USER_STATUS_USER';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_STATUS_MODERATOR') {
               $message_tag = 'MAIL_BODY_USER_STATUS_MODERATOR';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_ACCOUNT_PASSWORD') {
               $message_tag = 'MAIL_BODY_USER_ACCOUNT_PASSWORD';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_ACCOUNT_MERGE') {
               $message_tag = 'MAIL_BODY_USER_ACCOUNT_MERGE';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_PASSWORD_CHANGE') {
               $message_tag = 'MAIL_BODY_USER_PASSWORD_CHANGE';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_MATERIAL_WORLDPUBLIC') {
               $message_tag = 'MAIL_BODY_MATERIAL_WORLDPUBLIC';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_MATERIAL_NOT_WORLDPUBLIC') {
               $message_tag = 'MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_LOCK') {
               $message_tag = 'MAIL_BODY_ROOM_LOCK';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_UNLOCK') {
               $message_tag = 'MAIL_BODY_ROOM_UNLOCK';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_UNLINK') {
               $message_tag = 'MAIL_BODY_ROOM_UNLINK';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_DELETE') {
               $message_tag = 'MAIL_BODY_ROOM_DELETE';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_ROOM_OPEN') {
               $message_tag = 'MAIL_BODY_ROOM_OPEN';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_MAKE_CONTACT_PERSON') {
                $message_tag = 'MAIL_BODY_USER_MAKE_CONTACT_PERSON';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON') {
                $message_tag = 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON';
            } elseif ($_POST['mail_text'] == 'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON') {
                $message_tag = 'EMAIL_BODY_PASSWORD_EXPIRATION_SOON';
            } elseif ($_POST['mail_text'] == 'EMAIL_CHOICE_PASSWORD_EXPIRATION') {
                $message_tag = 'EMAIL_BODY_PASSWORD_EXPIRATION';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_PROJECT_ARCHIVE_INFO') {
                $message_tag = 'PROJECT_MAIL_BODY_ARCHIVE_INFO';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_PROJECT_ARCHIVE') {
                $message_tag = 'PROJECT_MAIL_BODY_ARCHIVE';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_PROJECT_DELETE_INFO') {
                $message_tag = 'PROJECT_MAIL_BODY_DELETE_INFO';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_PROJECT_DELETE') {
                $message_tag = 'PROJECT_MAIL_BODY_DELETE';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_LOCK_NEXT') {
                $message_tag = 'EMAIL_INACTIVITY_LOCK_NEXT_BODY';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_LOCK_TOMORROW') {
                $message_tag = 'EMAIL_INACTIVITY_LOCK_TOMORROW_BODY';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_LOCK_NOW') {
                $message_tag = 'EMAIL_INACTIVITY_LOCK_NOW_BODY';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_DELETE_NEXT') {
                $message_tag = 'EMAIL_INACTIVITY_DELETE_NEXT_BODY';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_DELETE_TOMORROW') {
                $message_tag = 'EMAIL_INACTIVITY_DELETE_TOMORROW_BODY';
            } elseif ($_POST['mail_text'] == 'MAIL_CHOICE_INACTIVITY_DELETE_NOW') {
                $message_tag = 'EMAIL_INACTIVITY_DELETE_NOW_BODY';
            } else {
               include_once('functions/error_functions.php');
               trigger_error('choice of mail text lost',E_USER_WARNING);
            }
            
            $values = $_POST['text'];
            $values_for_form = $_POST['text'];
            $reset = false;
            $translator = $environment->getTranslationObject();
            $languages = $environment->getAvailableLanguageArray();
            foreach ($languages as $language) {
               if (!empty($_POST['reset'][$language])) {
                  unset($values[$language]);
                  $values_for_form[$language] = $translator->getEmailMessageInLang($language,$message_tag);
                  $reset = true;
               }
            }

            $room_item->setEmailText($message_tag,$values);
            $room_item->save();
            $form_view->setItemIsSaved();

            // to display the reseted texts
            $translator->setEmailTextArray($room_item->getEmailTextArray());
            if ($reset) {
               foreach ($languages as $language) {
                  if (!empty($_POST['reset'][$language])) {
                     $values_for_form[$language] = $translator->getEmailMessageInLang($language,$message_tag);
                  }
               }
               // reset form
            $form->reset();
#            $form->prepareForm();
#            $form->loadValues();
               $temp_values = $_POST;
               $temp_values['text'] = $values_for_form;
               $form->setFormPost($temp_values);
               $form->prepareForm();
               $form->loadValues();
            }

         }
      }

      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
      $form_view->setForm($form);
      if ( $environment->inPortal() or $environment->inServer() ){
         $page->addForm($form_view);
      } else {
         $page->add($form_view);
      }
   }
}
?>