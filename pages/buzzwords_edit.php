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

// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($current_iid) {
   global $session,$environment;
   $session->unsetValue($environment->getCurrentModule().'_add_files');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.'_material_attach_ids');
   $session->unsetValue($current_iid.'_institution_attach_ids');
   $session->unsetValue($current_iid.'_group_attach_ids');
   $session->unsetValue($current_iid.'_topic_attach_ids');
   $session->unsetValue($current_iid.'_material_back_module');
   $session->unsetValue($current_iid.'_institution_back_module');
   $session->unsetValue($current_iid.'_group_back_module');
   $session->unsetValue($current_iid.'_topic_back_module');
}

// Get the current user and room
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// Get linked rubric
if ( !empty($_GET['module']) ) {
   $linked_rubric = $_GET['module'];
   $session->setValue($environment->getCurrentModule().'_linked_rubric',$linked_rubric);
} elseif ( $session->issetValue($environment->getCurrentModule().'_linked_rubric') ) {
   $linked_rubric = $session->getValue($environment->getCurrentModule().'_linked_rubric');
} else {
   $linked_rubric = '';
}

// Check access rights
if ( !$current_user->isUser() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}elseif ( empty($linked_rubric) ){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('BUZZWORD_MISSING_LINKED_RUBRIC'));
   $page->add($errorbox);
}

// Access granted
else {
   // Find out what to do
   $iid = 0;
   $delete_iid = 0;
   $command = '';
   $delete_option = '';
   if(isset($_POST)) {
      foreach($_POST as $key => $value) {
         if(empty($command) && mb_substr($key, 0, 6) == 'option') {
            $command = $value;
            $iid = mb_substr($key, 7);
         }
         
         if(empty($delete_option) && mb_substr($key, 0, 13) == 'delete_option') {
            $delete_option = $value;
            $delete_iid = mb_substr($key, 14);
         }
         
         if(!empty($command) && !empty($delete_option)) {
            break;
         }
      }
   }
   
   // delete box
   $deleteOverlay = false;
   if(isOption($command, $translator->getMessage('COMMON_DELETE_BUTTON'))) {
      $params = $environment->getCurrentParameterArray();
      $params['delete_id'] = $iid;
	  $page->addDeleteBox(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params));
	  $deleteOverlay = true;
   }
   // change option
   else if(isOption($command, $translator->getMessage('BUZZWORDS_CHANGE_BUTTON'))) {
      $change_id = $iid;
   }
   
   ##########################################
   ## handle messages from delete box
   #
   // delete option
   if(isOption($delete_option, $translator->getMessage('COMMON_DELETE_BUTTON'))) {
      if(isset($_GET['delete_id'])) {
         $delete_id = $_GET['delete_id'];
      } else {
         $delete_id = $delete_iid;
      }
      
   }
   // cancel option
   else if(isOption($delete_option, $translator->getMessage('COMMON_CANCEL_BUTTON'))) {
      $params = $environment->getCurrentParameterArray();
      unset($params['delete_id']);
      redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
   }
   #
   ##
   ##########################################

   // attach items
   if ( !empty($_POST) && !$deleteOverlay ) {
      $link_items = false;
      foreach ( $_POST as $key => $value ) {
         if ( $value == $translator->getMessage('COMMON_ITEM_NEW_ATTACH')
              and strstr($key,'right_box_option')
            ) {
            $tag_id = substr($key,strpos($key,'#')+1);
            $_GET['iid'] = $tag_id;
            if ( !empty($_POST['module'])
                 and $_POST['module'] != 'home'
               ) {
               $_GET['selrubric'] = $_POST['module'];
            }
            $_POST['right_box_option'] = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
            $link_items = true;
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2');
            break;
         }
      }
      if ( !$link_items
           and !empty($_POST['option'])
           and isOption($_POST['option'], $translator->getMessage('COMMON_ITEM_ATTACH'))
         ) {
         $link_items = true;
      }
      if ( !$link_items
           and !empty($_POST)
           and empty($_POST['option'])
           and empty($change_id)
           and empty($delete_id)
         ) {
         $_GET['attach_view'] = 'yes';
         $_GET['attach_type'] = 'item';
         $link_items = true;
      }
      if ( $link_items ) {
         include_once('pages/item_attach.php');
      }
   }
   
   // Show form and/or save item
   // Initialize the form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(BUZZWORDS_FORM,$class_params);
   unset($class_params);
   
   // Load form data from postvars
   if ( !empty($_POST) ) {
      $form->setFormPost($_POST);
   }
   
   $form->prepareForm();
   $form->loadValues();
   
   // Save item
   if ( !empty($delete_id) or !empty($change_id) ) {
      $buzzword_manager  = $environment->getLabelManager();
      // delete
      if(isset($delete_id) && !empty($delete_id)) {
         $buzzword_item = $buzzword_manager->getItem($delete_id);
         if(!empty($buzzword_item)) {
            $buzzword_item->delete();
         }
         unset($delete_id);
         unset($tag_item);
      }
      // change
      else if(isset($change_id) && !empty($change_id)) {
         $buzzword_item = $buzzword_manager->getItem($change_id);
         if(!empty($buzzword_item)) {
            $buzzword_item->setName($_POST['buzzword#' . $change_id]);
            $buzzword_item->save();
         }
         unset($change_id);
         unset($tag_item);
      }
      unset($tag_manager);
      
      $params = array();
      if (empty($delete_id)) {
         $params['focus_element_onload'] = $change_id;
      }
      redirect($environment->getCurrentContextID(),'buzzwords', 'edit', $params);
   }elseif (!empty($command) and isOption($command, $translator->getMessage('BUZZWORDS_NEW_BUTTON'))){
      if (isset($_POST['new_buzzword']) and !empty($_POST['new_buzzword'])){
         $buzzword_manager = $environment->getLabelManager();
         $buzzword_item = $buzzword_manager->getNewItem();
         $buzzword_item->setLabelType('buzzword');
         $buzzword_item->setName($_POST['new_buzzword']);
         $buzzword_item->setContextID($environment->getCurrentContextID());
         $user = $environment->getCurrentUserItem();
         $buzzword_item->setCreatorItem($user);
         $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
         $buzzword_item->save();
         $params = array();
         $params['focus_element_onload'] = 'new_buzzword';
         redirect($environment->getCurrentContextID(), 'buzzwords', 'edit', $params);
      }
   }elseif (!empty($command) and isOption($command, $translator->getMessage('BUZZWORDS_COMBINE_BUTTON'))){
      if ( (isset($_POST['sel1']) and !empty($_POST['sel1'])) and
           (isset($_POST['sel2']) and !empty($_POST['sel2'])) and
           (isset($_POST['sel1']) and isset($_POST['sel2']) and $_POST['sel1'] !=$_POST['sel2'])
           ){
         $link_manager = $environment->getLinkManager();
         $link_manager->combineBuzzwords($_POST['sel1'],$_POST['sel2']);
         $buzzword_manager = $environment->getLabelManager();
         $buzzword_item1 = $buzzword_manager->getItem($_POST['sel1']);
         $buzzword_item2 = $buzzword_manager->getItem($_POST['sel2']);
         $buzzword_item1->setName($buzzword_item1->getName().'/'.$buzzword_item2->getName());
         $buzzword_item1->setModificationDate(getCurrentDateTimeInMySQL());
         $buzzword_item1->save();
         $buzzword_item2->delete();
         
         $params = array();
         $params['focus_element_onload'] = 'sel1';
         redirect($environment->getCurrentContextID(), 'buzzwords', 'edit', $params);
      }
   }

   // Display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(FORM_VIEW,$params);
   unset($params);
   $form_view->setWithoutDescription();
   $form_view->setAction(curl($environment->getCurrentContextID(),'buzzwords','edit',''));
   
   if (isset($_GET['focus_element_onload'])) {
      if (is_numeric($_GET['focus_element_onload'])) {
         // it would be a lot nicer if this concatenation could be done before refreshing
         // but the '#' breaks the url.
         $form_view->setFocusElementOnLoad('buzzword#'.$_GET['focus_element_onload']);
      } else {
         $form_view->setFocusElementOnLoad($_GET['focus_element_onload']);
      }
   }

   $form_view->setForm($form);
   $page->add($form_view);
}
?>