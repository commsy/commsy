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

// Check access rights
// TBD: config of room
$current_user = $environment->getCurrentUserItem();
$current_context = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

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
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
} elseif ( empty($linked_rubric) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('TAG_MISSING_LINKED_RUBRIC'));
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
   $form = $class_factory->getClass(TAG_FORM,$class_params);
   unset($class_params);
   
   // Load form data from postvars
   if ( !empty($_POST) ) {
      $form->setFormPost($_POST);
   }
   $form->prepareForm();
   $form->loadValues();
   
   // umhängen von Kategorien
   if ( isOption($command, $translator->getMessage('TAG_SORT_BUTTON'))
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
   
   // sort alphabetically
   elseif(isOption($command, $translator->getMessage('TAG_SORT_ABC'))) {
      $tag_manager = $environment->getTagManager();
      $root_tag = $tag_manager->getRootTagItem();
      unset($tag_manager);
      
      $tag2tag_manager = $environment->getTag2TagManager();
      $children_id_array = $tag2tag_manager->getRecursiveChildrenItemIDArray($root_tag->getItemID());
      $tag2tag_manager->sortRecursiveABC($root_tag->getItemID());
      unset($tag2tag_manager);
      
      $params = array();
      redirect($environment->getCurrentContextID(),'tag', 'edit', $params);
   }
   
   // combine categories
   elseif(	isOption($command, $translator->getMessage('TAG_COMBINE_BUTTON')) &&
   			$_POST['sel1'] != $_POST['sel2']) {
      $tag2tag_manager = $environment->getTag2TagManager();
      $sel_1 = $_POST['sel1'];
      $sel_2 = $_POST['sel2'];
      $put = $_POST['combine_father_id'];
      $childrenIdArray_1 = $tag2tag_manager->getRecursiveChildrenItemIDArray($sel_1);
      $childrenIdArray_2 = $tag2tag_manager->getRecursiveChildrenItemIDArray($sel_2);
      
      // check whether put id is not a child of the selected categories or itself a selected categorie
      if(   !in_array($put, $childrenIdArray_1) &&
            !in_array($put, $childrenIdArray_2) &&
            $put != $sel_1 &&
         	$put != $sel_2) {
         
      	// combine the selected categories
     	$tag2tag_manager->combine($sel_1, $sel_2, $put);
      } else {
         // show error message
         $session_item = $environment->getSessionItem();
         $session_item->setValue('tag_cannot_combine', 'true');
         unset($session_item);
      }
      
      unset($tag2tag_manager);
      $params = array();
      redirect($environment->getCurrentContextID(), 'tag', 'edit', $params);
   }
 
   // Save item
   elseif ( !empty($delete_id) or !empty($change_id) ) {
      $tag_manager = $environment->getTagManager();
      // delete
      if(isset($delete_id) && !empty($delete_id)) {
         $tag_item = $tag_manager->getItem($delete_id);
         if(!empty($tag_item)) {
            $tag_item->delete();
         }
         unset($delete_id);
         unset($tag_item);
      }
      // change
      else if(isset($change_id) && !empty($change_id)) {
         $tag_item = $tag_manager->getItem($change_id);
         if(!empty($tag_item)) {
            $tag_item->setTitle($_POST['tag#' . $change_id]);
            $tag_item->save();
         }
         unset($change_id);
         unset($tag_item);
      }
      unset($tag_manager);
      
      $params = array();
      if ( empty($delete_id) ) {
         $params['focus_element_onload'] = $change_id;
      }
      redirect($environment->getCurrentContextID(),'tag', 'edit', $params);
   } elseif (!empty($command) and isOption($command, $translator->getMessage('COMMON_ADD_BUTTON'))){
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
         
         // sort alphabetically
//         $tag2tag_manager = $environment->getTag2TagManager();
//         $tag2tag_manager->sortRecursiveABC($_POST['father_id']);
//         unset($tag2tag_manager);
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
unset($current_context);
unset($current_user);
?>