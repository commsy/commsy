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

// Function used for redirecting to connected rubrics
function attach_redirect ($rubric_type, $current_iid) {
   global $session, $environment;
   $infix = '_'.$rubric_type;
   $session->setValue($current_iid.'_post_vars', $_POST);

   $tag_manager = $environment->getTagManager();
   $tag_item = $tag_manager->getItem($current_iid);
   unset($tag_manager);
   $type = $rubric_type;
   $link_item_manager = $environment->getLinkItemManager();
   $link_item_manager->setTypeLimit($type);
   $link_item_manager->setLinkedItemLimit($tag_item);
   unset($tag_item);
   $link_item_manager->select(false);
   $result_list = $link_item_manager->get();
   $id_array = array();
   if ( $result_list->isNotEmpty() ) {
      $link_item = $result_list->getFirst();
      while ($link_item) {
         if ( $link_item->getFirstLinkedItemType() == $type ) {
            $id_array[] = $link_item->getFirstLinkedItemID();
         } else {
            $id_array[] = $link_item->getSecondLinkedItemID();
         }
         unset($link_item);
         $link_item = $result_list->getNext();
      }
   }
   unset($result_list);
   $session->setValue($current_iid.$infix.'_attach_ids', $id_array);

   $session->setValue($current_iid.$infix.'_back_module', $environment->getCurrentModule());
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

// Check access rights
// TBD: config of room
$current_user = $environment->getCurrentUserItem();
$current_context = $environment->getCurrentContextItem();
// Get linked rubric
if ( !empty($_GET['module']) ) {
   $linked_rubric = $_GET['module'];
   $session->setValue($environment->getCurrentModule().'_linked_rubric',$linked_rubric);
} elseif ( $session->issetValue($environment->getCurrentModule().'_linked_rubric') ) {
   $linked_rubric = $session->getValue($environment->getCurrentModule().'_linked_rubric');
} else {
   $linked_rubric = '';
}

if ( !$current_user->isUser()
     or ( !$current_context->isTagEditedByAll()
          and !$current_user->isModerator()
        )
   ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
} elseif ( empty($linked_rubric) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('TAG_MISSING_LINKED_RUBRIC'));
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

   // Coming back from attaching something
   if ( !empty($_GET['backfrom']) ) {
      $backfrom = $_GET['backfrom'];
   } else {
      $backfrom = false;
   }

   $change_id = 0;
   $delete_id = 0;
   $assign_id = 0;
   foreach ($_POST as $key => $post_var){
      $iid = mb_substr(strchr($key,'#'),1);
      if ( !empty($iid) and mb_stristr($key,'option') ) {
         if ( isOption($post_var, getMessage('COMMON_DELETE_BUTTON')) ) {
            $delete_id = $iid;
         } else {
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
      $iid = mb_substr(strchr($key,'#'),1);
      if (!empty($iid) and mb_stristr($key,'option') ) {
         if ( isOption($post_var, $translator->getMessage('BUZZWORDS_ASSIGN_ENTRIES')) ){
            $assign_id = $iid;
            attach_redirect($linked_rubric, $iid);
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
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(TAG_FORM,$class_params);
      unset($class_params);
      // Load form data from postvars
      if ( !empty($_POST) ) {
         $form->setFormPost($_POST);
      }

      // Back from attaching rubric
      elseif ( $backfrom == $linked_rubric ) {
         $attach_ids = attach_return($linked_rubric, $assign_id);
         $tag_manager = $environment->getTagManager();
         $tag_item = $tag_manager->getItem($assign_id);
         $tag_item->saveRubricLinkItemsByIDArray($attach_ids,$linked_rubric);
      }

      $form->prepareForm();
      $form->loadValues();

      // umhängen von Kategorien
      if ( isOption($command, getMessage('TAG_SORT_BUTTON'))
           and $_POST['sort1'] != $_POST['sort2']
         ) {
         $tag2tag_manager = $environment->getTag2TagManager();
         $cat_1 = $_POST['sort1'];
         $children_id_array_cat1 = $tag2tag_manager->getRecursiveChildrenItemIDArray($cat_1);
         if ( !in_array($_POST['sort2'],$children_id_array_cat1) ) {
            if ($_POST['sort_action'] == 3) {
               $cat_2 = $_POST['sort2'];
               $place = 1;
            } else {
               $cat_2 = $tag2tag_manager->getFatherItemID($_POST['sort2']);
               $children_id_array = $tag2tag_manager->getChildrenItemIDArray($cat_2);
               $place = 0;
               foreach ($children_id_array as $children_item_id) {
                  $place++;
                  if ( $children_item_id == $_POST['sort2'] ) {
                     break;
                  }
               }
               if ( $_POST['sort_action'] == 2 ) {
                  $place++;
               }
            }
            $tag2tag_manager->change($cat_1,$cat_2,$place);
         }
         unset($tag2tag_manager);
         $params = array();
         redirect($environment->getCurrentContextID(),'tag', 'edit', $params);
      }

      // Save item
      elseif ( !empty($delete_id) or !empty($change_id) ) {
         if ( !empty($_POST) ) {
            foreach ($_POST as $key => $post_var) {
               $iid = mb_substr(strchr($key,'#'),1);
               $tag_manager = $environment->getTagManager();
               if ( !empty($iid) and mb_stristr($key,'tag') and $iid == $change_id ) {
                  $tag_item = $tag_manager->getItem($iid);
                  $tag_item->setTitle($post_var);
                  $tag_item->save();
                  unset($tag_item);
               } elseif ( !empty($iid) and $iid == $delete_id ) {
                  $tag_item = $tag_manager->getItem($iid);
                  $tag_item->delete();
                  unset($tag_item);
               }
               unset($tag_manager);
            }
         }

         $params = array();
         if ( empty($delete_id) ) {
            $params['focus_element_onload'] = $change_id;
         }
         redirect($environment->getCurrentContextID(),'tag', 'edit', $params);

      } elseif (!empty($command) and isOption($command, getMessage('COMMON_ADD_BUTTON'))){
          if ( isset($_POST['new_tag'])
               and !empty($_POST['new_tag'])
               and isset($_POST['father_id'])
               and !empty($_POST['father_id'])
             ) {
             $tag_manager = $environment->getTagManager();
             $tag_item = $tag_manager->getNewItem();
             $tag_item->setTitle($_POST['new_tag']);
             $tag_item->setContextID($environment->getCurrentContextID());
             $user = $environment->getCurrentUserItem();
             $tag_item->setCreatorItem($user);
             unset($user);
             $tag_item->setCreationDate(getCurrentDateTimeInMySQL());
             $tag_item->setPosition($_POST['father_id'],1);
             $tag_item->save();

             unset($tag_item);
             unset($tag_manager);

             $params = array();
             $params['focus_element_onload'] = 'new_tag';
             redirect($environment->getCurrentContextID(),'tag', 'edit', $params);
          }
       }

      // Display form
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      unset($class_params);
      $form_view->setWithoutDescription();
      $form_view->setAction(curl($environment->getCurrentContextID(),'tag','edit',''));

      if (isset($_GET['focus_element_onload'])) {
         if (is_numeric($_GET['focus_element_onload'])) {
            // it would be a lot nicer if this concatenation could be done before refreshing
            // but the '#' breaks the url.
            $form_view->setFocusElementOnLoad('tag#'.$_GET['focus_element_onload']);
         } else {
            $form_view->setFocusElementOnLoad($_GET['focus_element_onload']);
         }
      }

      $form_view->setForm($form);
      $page->add($form_view);
   }
}
unset($current_context);
unset($current_user);
?>