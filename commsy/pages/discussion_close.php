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


// Get the current user and context
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Get item to be edited
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else {
   $current_iid = 'NEW';
}

// Load item from database
if ( $current_iid == 'NEW' ) {
   include_once('functions/error_functions.php');trigger_error('A discussion item id must be given.', E_USER_ERROR);
} else {
   $discussion_manager = $environment->getDiscussionManager();
   $discussion_item = $discussion_manager->getItem($current_iid);
}

// Check access rights
if ( $context_item->isProjectRoom() and $context_item->isClosed() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
} elseif ( !isset($discussion_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !$discussion_item->mayEditIgnoreClose($current_user) ) {
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

   //Do the stuff that may be done while discussion is open
   if ($discussion_item->mayEdit($current_user)) {
       // Cancel editing
       if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
          $params = array();
          $params['iid'] = $current_iid;
          redirect($environment->getCurrentContextID(), 'discussion', 'detail', $params);
       }

       // Show form and/or save item
       else {

          // Initialize the form
          $class_params= array();
          $class_params['environment'] = $environment;
          $form = $class_factory->getClass(DISCUSSION_CLOSE_FORM,$class_params);
          unset($class_params);

          if ( !empty($_POST) ) {
             $form->setFormPost($_POST);
          } elseif ( isset($discussion_item) ) {

             $form->setItem($discussion_item);

             // Calculate initial summary
             $discarticle_manager = $environment->getDiscussionArticlesManager();
             $discarticle_manager->setDiscussionLimit($current_iid);
             $discarticle_manager->select();
             $article_list = $discarticle_manager->get();

             $summary = '';
             $article = $article_list->getFirst();
             while ( $article ) {
                $creator = $article->getCreatorItem();
                $position_length =  count(explode('.',$article->getPosition()));
                $nbsp ='';
                for ($i=1; $i < $position_length; $i++){
                   $nbsp .= '&nbsp;&nbsp;&nbsp;';
                }
                $creator_fullname = $creator->getFullName();
                $summary .= $nbsp.$article->getSubject()." (".$creator_fullname.")\n\n";
                $summary .= $nbsp.$article->getDescription()."\n\n";
                $summary .= $nbsp."__________\n\n\n\n";
                $article = $article_list->getNext();
             }
             $form->setSummary($summary);

          } else {
             include_once('functions/error_functions.php');trigger_error('discussion_close was called in an unknown manner', E_USER_ERROR);
          }

          $form->prepareForm();
          $form->loadValues();

          // Save item
          if ( !empty($command) and
               isOption($command, $translator->getMessage('DISCUSSION_CLOSE_BUTTON')) ) {

             $correct = $form->check();
             if ( $correct ) {

                // Delete all existing articles
                $discarticle_manager = $environment->getDiscussionArticlesManager();
                $discarticle_manager->setDiscussionLimit($current_iid);
                $discarticle_manager->select();
                $articles_list = $discarticle_manager->get();
                if ( !$articles_list->isEmpty() ) {
                   $articles_item = $articles_list->getFirst();
                   while ( $articles_item ) {
                      $discarticle_manager->delete($articles_item->getItemID());
                      $articles_item = $articles_list->getNext();
                   }
                }

                // Create final discussion article
                $discarticle_item = $discarticle_manager->getNewItem();
                $discarticle_item->setContextID($environment->getCurrentContextID());
                $discarticle_item->setCreatorItem($environment->getCurrentUserItem());
                $discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
                $discarticle_item->setDiscussionID($current_iid);

                if ( !empty($_POST['summary'])) {
                   $discarticle_item->setDescription($_POST['summary']);
                }

                if (!empty($_POST['subject'])) {
                   $discarticle_item->setSubject($_POST['subject']);
                }

                // Save discarticle
                $discarticle_item->save();

                // Update the discussion regarding the latest article information
                $discussion_item->setLatestArticleID($discarticle_item->getItemID());
                $discussion_item->setLatestArticleModificationDate($discarticle_item->getCreationDate());
                $discussion_item->close();
                $discussion_item->save();

                // Redirect
                $params = array();
                $params['iid'] = $current_iid;
                redirect($environment->getCurrentContextID(),
                         'discussion', 'detail', $params);
             }
          }

          // display form
         $class_params = array();
         $class_params['environment'] = $environment;
         $class_params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
         unset($class_params);
         $form_view->setAction(curl($environment->getCurrentContextID(),'discussion','close',''));
         $form_view->setForm($form);
         $page->add($form_view);
      }
   }
   //When discussion is closed, it may still be copied to a material
   else {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('DISCUSSION_UNKNOWN_ACCESS'));
      $page->add($errorbox);
   }
}
?>