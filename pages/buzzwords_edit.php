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

include_once('classes/cs_buzzwords_form.php');

// Function used for redirecting to connected rubrics
function attach_redirect ($rubric_type, $current_iid) {
   global $session, $environment;
   $infix = '_'.$rubric_type;
   $session->setValue($current_iid.'_post_vars', $_POST);

   $links_manager = $environment->getLinkManager();
   $links_manager->setItemTypeLimit($rubric_type);
   $links_manager->setItemIDLimit($current_iid);
   $result_array = $links_manager->_performQuery();
   unset($links_manager);
   $id_array = array();
   foreach ($result_array as $link) {
      $id_array[] = $link['from_item_id'];
   }
   $session->setValue($current_iid.$infix.'_attach_ids', $id_array);

   $session->setValue($current_iid.$infix.'_back_module', 'buzzwords');
   $params = array();
   $params['ref_iid'] = $current_iid;
   $params['mode'] = 'formattach';
   redirect($environment->getCurrentContextID(), type2Module($rubric_type), 'index', $params);
}

function attach_return ($rubric_type, $current_iid) {
   global $session;
   $infix = '_'.$rubric_type;
   $attach_ids = $session->getValue($current_iid.$infix.'_attach_ids');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.$infix.'_attach_ids');
   $session->unsetValue($current_iid.$infix.'_back_module');
   return $attach_ids;
}

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

// Coming back from attaching something
if ( !empty($_GET['backfrom']) ) {
   $backfrom = $_GET['backfrom'];
} else {
   $backfrom = false;
}

// Get item to be edited
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else {
   $current_iid = 'NEW';
}

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
   $errorbox->setText(getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}elseif ( empty($linked_rubric) ){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('BUZZWORD_MISSING_LINKED_RUBRIC'));
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

   $change_id = 0;
   $delete_id = 0;
   $assign_id = 0;
   foreach ($_POST as $key => $post_var){
      $iid = substr(strchr($key,'#'),1);
      if (!empty($iid) and stristr($key,'option') ) {
         if ( isOption($post_var, getMessage('COMMON_DELETE_BUTTON')) ){
            $delete_id = $iid;
         }else{
            $change_id = $iid;
         }
      }
   }

   // Get item to be edited
   if ( !empty($_GET['iid']) ) {
      $assign_id = $_GET['iid'];
   } elseif ( !empty($_POST['iid']) ) {
      $assign_id = $_POST['iid'];
   }

   // Redirect to attach material
   foreach ($_POST as $key => $post_var){
      $iid = substr(strchr($key,'#'),1);
      if (!empty($iid) and stristr($key,'option') ) {
         if ( isOption($post_var, getMessage('BUZZWORDS_ASSIGN_ENTRIES')) ){
            $assign_id = $iid;
            attach_redirect($linked_rubric, $iid);
         }
      }
   }

   // Cancel editing
   if ( isOption($command, getMessage('COMMON_BACK_BUTTON')) ) {
      redirect($environment->getCurrentContextID(), $linked_rubric, 'index', '');
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $form = new cs_buzzwords_form($environment);
      // Load form data from postvars
      if ( !empty($_POST) ) {
         $form->setFormPost($_POST);
      }
      // Back from attaching rubric
      elseif ( $backfrom == $linked_rubric ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return($linked_rubric, $current_iid);
         $buzzword_manager = $environment->getBuzzwordManager();
         $buzzword_item = $buzzword_manager->getItem($assign_id);
         $buzzword_item->saveRubricLinksByIDArray($attach_ids,$linked_rubric);
      }

      $form->prepareForm();
      $form->loadValues();
      // Save item
      if ( !empty($delete_id) or !empty($change_id) ){
        if (!empty ($_POST)){
             foreach ($_POST as $key => $post_var){
               $iid = substr(strchr($key,'#'),1);
               if (!empty($iid) and stristr($key,'buzzword') and $iid == $change_id) {
                  $buzzword_manager = $environment->getLabelManager();
                  $buzzword_item = $buzzword_manager->getItem($iid);
                  $buzzword_item->setName($post_var);
                  $buzzword_item->save();
               } elseif(!empty($iid) and $iid == $delete_id) {
                  $buzzword_manager = $environment->getLabelManager();
                  $buzzword_item = $buzzword_manager->getItem($iid);
                  $buzzword_item->delete();
               }
               cleanup_session($iid);
            }
         }

         $params = array();
         if (empty($delete_id)) {
           $params['focus_element_onload'] = $change_id;
         }
         redirect($environment->getCurrentContextID(),'buzzwords', 'edit', $params);
      }elseif (!empty($command) and isOption($command, getMessage('BUZZWORDS_NEW_BUTTON'))){
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
             redirect($environment->getCurrentContextID(),
                'buzzwords', 'edit', $params);
          }
       }elseif (!empty($command) and isOption($command, getMessage('BUZZWORDS_COMBINE_BUTTON'))){
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
       }else{
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

}
?>