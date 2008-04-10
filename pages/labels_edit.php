<?PHP
// $Id:
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

include_once('classes/cs_labels_form.php');
include_once('classes/cs_labels_form_view.php');


// Get the current user and room
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// Check access rights
if ( !$current_user->isUser() ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view($environment, true);
   $errorbox->setText(getMessage('LOGIN_NOT_ALLOWED'));
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


   // Cancel editing
   if ( isOption($command, getMessage('COMMON_BACK_BUTTON')) ) {
      redirect($environment->getCurrentContextID(), 'material', 'index', '');
   }

   // Show form and/or save item
   else {
      // Initialize the form
      $form = new cs_labels_form($environment);
      // Load form data from postvars
      if ( !empty($_POST) ) {
         $form->setFormPost($_POST);
      }

      // Get available labels
      $label_manager = $environment->getLabelManager();
      $label_manager->resetLimits();
      $label_manager->setContextLimit($environment->getCurrentContextID());
      $label_manager->setTypeLimit('label');
      $label_manager->select();
      $label_list = $label_manager->get();
      $label_array = array();
      if ($label_list->getCount() > 0) {
         $label_item =  $label_list->getFirst();
         while ($label_item) {
            $temp_array['text'] = $label_item->getName();
            $temp_array['value'] = $label_item->getItemID();
            $label_array[] = $temp_array;
            $label_item =  $label_list->getNext();
         }
      }
      $form->_label_array = $label_array;
      $form->prepareForm();
      $form->loadValues();
      // Save item
      if ( !empty($delete_id) or !empty($change_id) ){
         if (!empty ($_POST)){
            foreach ($_POST as $key => $post_var){
               $iid = substr(strchr($key,'#'),1);
               if (!empty($iid) and stristr($key,'label') and $iid == $change_id){
                  $label_manager = $environment->getLabelManager();
                  $label_item = $label_manager->getItem($iid);
                  $label_item->setName($post_var);
                  $label_item->save();
               }elseif(!empty($iid) and $iid == $delete_id){
                  $label_manager = $environment->getLabelManager();
                  $label_item = $label_manager->getItem($iid);
                  $label_item->delete();
               }
            }
         }
         $params = array();
         if (empty($delete_id)) {
           $params['focus_element_onload'] = $change_id;
         }
         redirect($environment->getCurrentContextID(),'labels', 'edit', $params);
      } elseif (!empty($command) and isOption($command, getMessage('LABELS_NEW_BUTTON'))){
          if (isset($_POST['new_label']) and !empty($_POST['new_label'])){
             $label_manager = $environment->getLabelManager();
             $label_item = $label_manager->getNewItem();
             $label_item->setLabelType('label');
             $label_item->setName($_POST['new_label']);
             $label_item->setContextID($environment->getCurrentContextID());
             $user = $environment->getCurrentUserItem();
             $label_item->setCreatorItem($user);
             $label_item->setCreationDate(getCurrentDateTimeInMySQL());
             $label_item->save();

             $params = array();
             $params['focus_element_onload'] = 'new_label';
             redirect($environment->getCurrentContextID(),
                'labels', 'edit', $params);
          }
      } elseif (!empty($command) and isOption($command, getMessage('LABELS_COMBINE_BUTTON'))){
         if ( (isset($_POST['sel1']) and !empty($_POST['sel1'])) and
              (isset($_POST['sel2']) and !empty($_POST['sel2'])) and
              (isset($_POST['sel1']) and isset($_POST['sel2']) and $_POST['sel1'] !=$_POST['sel2'])
                  ){
            $link_manager = $environment->getLinkManager();
            $link_manager->combineLabels($_POST['sel1'],$_POST['sel2']);
            $label_manager = $environment->getLabelManager();
            $label_item1 = $label_manager->getItem($_POST['sel1']);
            $label_item2 = $label_manager->getItem($_POST['sel2']);
            $label_item1->setName($label_item1->getName().'/'.$label_item2->getName());
            $label_item1->setModificationDate(getCurrentDateTimeInMySQL());
            $label_item1->save();
            $label_item2->delete();
            $params = array();
            $params['focus_element_onload'] = 'sel1';
            redirect($environment->getCurrentContextID(), 'labels', 'edit', $params);
         }
      }
      // Display form
      $form_view = new cs_labels_form_view($environment,'');
      $form_view->setAction(curl($environment->getCurrentContextID(),'labels','edit',''));

      if (isset($_GET['focus_element_onload'])) {
        if (is_numeric($_GET['focus_element_onload'])) {
          // it would be a lot nicer if this concatenation could be done before refreshing
          // but the '#' breaks the url.
          $form_view->setFocusElementOnLoad('label#'.$_GET['focus_element_onload']);
        } else {
          $form_view->setFocusElementOnLoad($_GET['focus_element_onload']);
        }
      }

      $form_view->setForm($form);
      $page->add($form_view);
   }
}
$page->setPageName(getMessage('COMMON_PAGETITLE_MATERIALTYPE'));
?>