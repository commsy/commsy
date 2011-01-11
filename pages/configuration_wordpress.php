<?PHP
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Manuel Gonzalez Vazquez, Johannes Schultze
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

// Get the current user
$current_user = $environment->getCurrentUserItem();
$translator = $environment->getTranslationObject();
$is_saved = false;

// get iid
if ( !empty($_GET['iid']) ) {
  $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
  $current_iid = $_POST['iid'];
} else{
  $current_context_item = $environment->getCurrentContextItem();
  $current_iid = $current_context_item->getItemID();
}


// hier muss auf den aktuellen Kontext referenziert werden,
// da sonst später diese Einstellung wieder überschrieben wird
// in der commsy.php beim Speichern der Aktivität
$current_context_item = $environment->getCurrentContextItem();
if ($current_iid == $current_context_item->getItemID()) {
  $item = $current_context_item;
} else {
  if ($environment->inProjectRoom() or $environment->inCommunityRoom()) {
    $room_manager = $environment->getRoomManager();
  } elseif ($environment->inPortal()) {
    $room_manager = $environment->getPortalManager();
  }
  $item = $room_manager->getItem($current_iid);
}

// Check access rights
if ( isset($item) and !$item->mayEdit($current_user) ) {
  $params = array();
  $params['environment'] = $environment;
  $params['with_modifying_actions'] = true;
  $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
  unset($params);
  $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
  $page->add($errorbox);
} elseif ( isset($item) and !$item->isOpen() and !$item->isTemplate() ) {
  $params = array();
  $params['environment'] = $environment;
  $params['with_modifying_actions'] = true;
  $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
  unset($params);
  $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $item->getTitle()));
  $page->add($errorbox);
  $command = 'error';
} elseif ( isset($item) and !$item->withWordpressFunctions() ) {
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

  // Find out what to do
  if ( isset($_POST['delete_option']) ) {
    $delete_command = $_POST['delete_option'];
  }elseif ( isset($_GET['delete_option']) ) {
    $delete_command = $_GET['delete_option'];
  } else {
    $delete_command = '';
  }

  // Initialize the form
  $form = $class_factory->getClass(CONFIGURATION_WORDPRESS_FORM,array('environment' => $environment));
  // display form
  $params = array();
  $params['environment'] = $environment;
  $params['with_modifying_actions'] = true;
  $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
  unset($params);

  // Load form data from postvars
  if ( !empty($_POST) ) {
    $form->setFormPost($_POST);
  }

  // Load form data from database
  elseif ( isset($item) ) {
    $form->setItem($item);
  }

  //local delete in commsy
  if ( !empty($delete_command) and
  isOption($delete_command, $translator->getMessage('COMMON_DELETE_BUTTON'))   ) {

    // delete wordpress
    $wordpress_manager = $environment->getWordpressManager();
    if ( $wordpress_manager->deleteWordpress($item->getWordpressId()) ) {
       $current_user = $environment->getCurrentUserItem();
       $item->setModificatorItem($current_user);
       $item->setModificationDate(getCurrentDateTimeInMySQL());
       $item->unsetWordpressExists();
       $item->setWordpressInActive();
       $item->setWordpressSkin('twentyten');
       $item->setWordpressTitle($item->getTitle());
       $item->setWordpressDescription('');
       $item->setWordpressId(0);

       // Save item
       $item->save();
       $form_view->setItemIsSaved();
       $form->setDeletionValues();
       $is_saved = true;
       redirect($environment->getCurrentContextID(),$environment->getCurrentModule(),'index','');
    } else {
       $is_saved = false;

       // errorbox
       $params = array();
       $params['environment'] = $environment;
       $params['with_modifying_actions'] = true;
       $params['width'] = 500;
       $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
       unset($params);
       $errorbox->setText($translator->getMessage('WORDPRESS_DELETE_ERROR_TEXT'));
       $page->add($errorbox);

       if ( isset($item) ) {
          $form->setItem($item);
          $form->setFormPost(NULL);
       }
    }
  }
  // Cancel editing
  elseif ( !empty($delete_command) and
  isOption($delete_command, $translator->getMessage('COMMON_CANCEL_BUTTON'))    ) {
    redirect($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),'');
  }

  // get all available skins
  $skin_array = array();
  $wordpress_manager = $environment->getWordpressManager();
  $skin_array = $wordpress_manager->getSkins();

  // delete item
  if ( !empty($command) and isOption($command, $translator->getMessage('WORDPRESS_DELETE_BUTTON')) ) {
    $params = $environment->getCurrentParameterArray();
    $page->addDeleteBox(curl($environment->getCurrentContextID(),module2type($environment->getCurrentModule()),$environment->getCurrentFunction(),$params));
  }


  // Save item
  elseif ( !empty($command) and
  (isOption($command, $translator->getMessage('WORDPRESS_SAVE_BUTTON'))
  or isOption($command, $translator->getMessage('COMMON_CHANGE_BUTTON')) ) ) {

    if ( $form->check() ) {

      // Set modificator and modification date
      $current_user = $environment->getCurrentUserItem();
      $item->setModificatorItem($current_user);
      $item->setModificationDate(getCurrentDateTimeInMySQL());

      if ( isset($_POST['use_comments']) and !empty($_POST['use_comments']) and $_POST['use_comments'] == 1) {
        $item->setWordpressUseComments();
      } else {
        $item->unsetWordpressUseComments();
      }

      if ( isset($_POST['use_comments_moderation']) and !empty($_POST['use_comments_moderation']) and $_POST['use_comments_moderation'] == 1) {
        $item->setWordpressUseCommentsModeration();
      } else {
        $item->unsetWordpressUseCommentsModeration();
      }


      if ( isset($_POST['use_calendar']) and !empty($_POST['use_calendar']) and $_POST['use_calendar'] == 1) {
        $item->setWordpressUseCalendar();
      } else {
        $item->unsetWordpressUseCalendar();
      }

      if ( isset($_POST['use_tagcloud']) and !empty($_POST['use_tagcloud']) and $_POST['use_tagcloud'] == 1) {
        $item->setWordpressUseTagCloud();
      } else {
        $item->unsetWordpressUseTagCloud();
      }

      if ( isset($_POST['wordpresslink']) and !empty($_POST['wordpresslink']) and $_POST['wordpresslink'] == 1) {
        $item->setWordpressHomeLink();
      } else {
        $item->unsetWordpressHomeLink();
      }

      if ( isset($_POST['skin_choice']) and !empty($_POST['skin_choice']) ) {
        $item->setWordpressSkin($_POST['skin_choice']);
      }

      if ( isset($_POST['wordpresstitle']) and !empty($_POST['wordpresstitle']) ) {
        $item->setWordpressTitle($_POST['wordpresstitle']);
      } else {
        $item->setWordpressTitle($item->getTitle());
      }

      if ( isset($_POST['wordpressdescription']) and !empty($_POST['wordpressdescription']) ) {
        $item->setWordpressDescription($_POST['wordpressdescription']);
      } else {
        $item->setWordpressDescription('');
      }

      if ( isset($_POST['member_role']) and !empty($_POST['member_role']) ) {
        $item->setWordpressMemberRole($_POST['member_role']);
      } else {
        $item->setWordpressMemberRole();
      }

      $item_wp_exists = $item->existWordpress();
      $item->setWordpressExists();
      $item->setWordpressActive();

      // create or change new wordpress
      $wordpress_manager = $environment->getWordpressManager();
      $success = $wordpress_manager->createWordpress($item);

      // Save item
      if ( isset($success)
           and !is_soap_fault($success)
           and $success
         ) {
        $item->save();
        $form_view->setItemIsSaved();
        $is_saved = true;
      } else {
        if ( !$item_wp_exists ) {
          $item->unsetWordpressExists();
        }

        $error_message = $success->getMessage();
        if ( stristr($error_message,'existing_user_mail') ) {
          $error_message = $translator->getMessage('WORDPRESS_CREATE_ERROR_EXISTING_USER_EMAIL');
        } elseif ( stristr($error_message,'is not allowed to') ) {
          $error_message = $translator->getMessage('WORDPRESS_MODIFY_ERROR_NOT_ALLOWED');
        }

        // errorbox
        $params = array();
        $params['environment'] = $environment;
        $params['with_modifying_actions'] = true;
        $params['width'] = 500;
        $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
        unset($params);
        $errorbox->setText($error_message);
        $page->add($errorbox);
      }
    }
  }
  $form->setSkinArray($skin_array);
  $form->prepareForm();
  $form->loadValues();

  if (isset($item) and !$item->mayEditRegular($current_user)) {
    $form_view->warnChanger();
    $params = array();
    $params['environment'] = $environment;
    $params['with_modifying_actions'] = true;
    $params['width'] = 500;
    $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
    unset($params);
    $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
    $page->add($errorbox);
  }

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
