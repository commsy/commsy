<?php
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
if ( empty($iid) ) {
   if ( !empty($_GET['iid']) ) {
      $iid = $_GET['iid'];
   } elseif ( !empty($_POST['iid']) ) {
      $iid = $_POST['iid'];
   }else{
      $iid = 'NEW';
   }
}

if ( isset($_POST['right_box_option']) ) {
   $right_box_command = $_POST['right_box_option'];
}elseif ( isset($_GET['right_box_option']) ) {
   $right_box_command = $_GET['right_box_option'];
} else {
   $right_box_command = '';
}

if ( isOption($command, getMessage('COMMON_BUZZWORD_NEW_ATTACH')) ) {
   if (isset($_POST['return_attach_buzzword_list'])){
      $buzzword_array = array();
      if (isset($_POST['buzzwordlist'])){
         $selected_id_array = $_POST['buzzwordlist'];
         foreach($selected_id_array as $id => $value){
            $buzzword_array[] = $id;
         }
      }
      if ( !empty($_POST['attach_new_buzzword']) ) {
         $buzzword_manager = $environment->getLabelManager();
         $buzzword_manager->reset();
         $buzzword_manager->setContextLimit($environment->getCurrentContextID());
         $buzzword_manager->setTypeLimit('buzzword');
         $buzzword_manager->select();
         $buzzword_list = $buzzword_manager->get();
         $exist = NULL;
         if ( !empty($buzzword_list) ){
            $buzzword = $buzzword_list->getFirst();
            while ( $buzzword ){
               if ( strcmp($buzzword->getName(), ltrim($_POST['attach_new_buzzword'])) == 0 ){
                  $exist = $buzzword->getItemID();
               }
               $buzzword = $buzzword_list->getNext();
            }
         }
         if ( !isset($exist) ) {
            $temp_array = array();
            $buzzword_manager = $environment->getLabelManager();
            $buzzword_manager->reset();
            $buzzword_item = $buzzword_manager->getNewItem();
            $buzzword_item->setLabelType('buzzword');
            $buzzword_item->setTitle(ltrim($_POST['attach_new_buzzword']));
            $buzzword_item->setContextID($environment->getCurrentContextID());
            $user = $environment->getCurrentUserItem();
            $buzzword_item->setCreatorItem($user);
            $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
            $buzzword_item->save();
            $buzzword_array[] = $buzzword_item->getItemID();
         } elseif ( isset($exist) and !in_array($exist,$buzzword_array) ) {
            $temp_array = array();
            $buzzword_manager = $environment->getLabelManager();
            $buzzword_manager->reset();
            $buzzword_item = $buzzword_manager->getItem($exist);
            $buzzword_array[] = $buzzword_item->getItemID();
         }
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids',$buzzword_array);
      $session_post_vars = $session->getValue('buzzword_post_vars');
   }

}
if ( isOption($right_box_command, getMessage('COMMON_BUZZWORD_NEW_ATTACH')) ) {
   $session->setValue('buzzword_post_vars', $_POST);
   $buzzword_array = array();
   $buzzword_manager = $environment->getLabelManager();
   $buzzword_manager->resetLimits();
   $buzzword_manager->setContextLimit($environment->getCurrentContextID());
   $buzzword_manager->setTypeLimit('buzzword');
   $buzzword_manager->setGetCountLinks();
   $buzzword_manager->select();
   $buzzword_list = $buzzword_manager->get();
   $count_all = $buzzword_list->getCount();
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $with_modifying_actions;
   $buzzword_view = $class_factory->getClass(BUZZWORD_INDEX_VIEW,$params);
   unset($params);
   $ids = $buzzword_manager->getIDArray();
   $count_all_shown = count($ids);
   $buzzword_view->setList($buzzword_list);
   $buzzword_view->setCountAllShown($count_all_shown);
   $buzzword_view->setCountAll($count_all);
}



if ( isOption($command, getMessage('COMMON_TAG_NEW_ATTACH')) ) {
   if (isset($_POST['return_attach_tag_list'])){
      $tag_array = array();
      if (isset($_POST['taglist'])){
         $selected_id_array = $_POST['taglist'];
         foreach($selected_id_array as $id => $value){
            $tag_array[] = $id;
         }
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids',$tag_array);
      $session_post_vars = $session->getValue('tag_post_vars');
   }
}
if ( isOption($right_box_command, getMessage('COMMON_TAG_NEW_ATTACH')) ) {
   $session->setValue('tag_post_vars', $_POST);
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $with_modifying_actions;
   $tag_view = $class_factory->getClass(TAG_INDEX_VIEW,$params);
   unset($params);
}


if ( isOption($command, getMessage('COMMON_ITEM_NEW_ATTACH')) or
     isOption($command, getMessage('COMMON_GROUP_ATTACH')) or
     isOption($command, getMessage('COMMON_INSTITUTION_ATTACH'))
 ) {

    $entry_array = array();
    $entry_new_array = array();
    if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_linked_items_index_selected_ids')) {
       $entry_array = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_linked_items_index_selected_ids');
    }
    if (isset($_POST['itemlist'])){
       $selected_ids = $_POST['itemlist'];
       foreach($selected_ids as $id => $value){
          $entry_new_array[] = $id;
       }
    }
    if ( isset($_COOKIE['itemlist']) ) {
       foreach ( $_COOKIE['itemlist'] as $key => $val ) {
          setcookie ('itemlist['.$key.']', '', time()-3600);
          if ( $val == '1' ) {
             if ( !in_array($key, $entry_array) ) {
                $entry_array[] = $key;
             }
          } else {
             $idx = array_search($key, $entry_array);
             if ( $idx !== false ) {
                unset($entry_array[$idx]);
             }
          }
       }
    }
    $entry_array = array_merge($entry_array,$entry_new_array);
    $entry_array = array_unique($entry_array);
    $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$entry_array);
    $session_post_vars = $session->getValue('linked_items_post_vars');
}elseif(empty($command)){
   if ( isset($_POST['mode']) ) {
      $mode = $_POST['mode'];
   } elseif ( isset($_GET['mode']) ) {
      $mode = $_GET['mode'];
   } else {
      $mode = '';
   }
   if ( isset($_GET['from']) ) {
      $from = $_GET['from'];
   }elseif ( isset($_POST['from']) ) {
      $from = $_POST['from'];
   }  else {
      $from = 1;
   }
   if ( isset($_GET['interval']) ) {
      $interval = $_GET['interval'];
   }  else {
      $interval = CS_LIST_INTERVAL;
   }

   $session_post_vars = $session->getValue('linked_items_post_vars');
   $sel_activating_status = '';
   if ( isset($_GET['selactivatingstatus']) and $_GET['selactivatingstatus'] !='-2') {
      $sel_activating_status = $_GET['selactivatingstatus'];
   } elseif ( isset($_POST['selactivatingstatus']) and $_POST['selactivatingstatus'] !='-2') {
      $sel_activating_status = $_POST['selactivatingstatus'];
   }else {
      $sel_activating_status = 2;
   }

   if ( isset($_GET['search']) ) {
      $search = $_GET['search'];
   } elseif ( isset($_POST['search']) ) {
      $search = $_POST['search'];
   } else {
      $search = '';
   }

   if ( !empty($_POST['linked_only']) and $_POST['linked_only'] == 1 ) {
      $linked_only = true;
   } else {
      $linked_only = false;
   }

   if ( isset($_POST['selrubric']) ) {
      $selrubric = $_POST['selrubric'];
      $from = 1;
   } elseif ( isset($_GET['selrubric']) ) {
      $selrubric = $_GET['selrubric'];
   }  else {
      $selrubric = '';
   }
   $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
   if ($mode == 'list_actions') {
      if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')) {
         $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
      }
   }
   if ( isset($_COOKIE['itemlist']) ) {
      foreach ( $_COOKIE['itemlist'] as $key => $val ) {
         setcookie ('itemlist['.$key.']', '', time()-3600);
         if ( $val == '1' ) {
            if ( !in_array($key, $selected_ids) ) {
               $selected_ids[] = $key;
            }
         } else {
            $idx = array_search($key, $selected_ids);
            if ( $idx !== false ) {
               unset($selected_ids[$idx]);
            }
         }
      }
   }
   if (!empty($selected_ids)){
      $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$selected_ids);
   }
   if ( isOption($right_box_command, getMessage('COMMON_ITEM_NEW_ATTACH')) or
        isOption($right_box_command, getMessage('COMMON_GROUP_ATTACH')) or
        isOption($right_box_command, getMessage('COMMON_INSTITUTION_ATTACH'))
   ) {
      $session->setValue('linked_items_post_vars', $_POST);
      $item_list = new cs_list();
      $item_ids = array();
      $count_all = 0;
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $item_attach_index_view = $class_factory->getClass(ITEM_ATTACH_INDEX_VIEW,$params);
      unset($params);
      $context_item = $environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  $default_room_modules;
      }
      $rubric_array = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
            if ( !($environment->inPrivateRoom() and $link_name[0] =='user') and
              !($link_name[0] == CS_USER_TYPE
                and ($environment->getCurrentModule() == CS_MATERIAL_TYPE
                or $environment->getCurrentModule() == CS_DISCUSSION_TYPE
                or $environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                or $environment->getCurrentModule() == CS_TOPIC_TYPE
                )
              )
            ){
               $rubric_array[] = $link_name[0];
            }
         }
      }
      if ( !empty($selrubric) and $selrubric != 'all' and $selrubric != 'campus_search') {
         $rubric_array = array();
         $rubric_array[] = $selrubric;
      }
      if ($environment->getCurrentModule() == CS_USER_TYPE){
         $rubric_array = array();
         if ($context_item->withRubric(CS_GROUP_TYPE)){
            $rubric_array[] = CS_GROUP_TYPE;
         }
         if ($context_item->withRubric(CS_INSTITUTION_TYPE)){
            $rubric_array[] = CS_INSTITUTION_TYPE;
         }
         $interval = 100;
      }
      foreach ($rubric_array as $rubric) {


         if($rubric != CS_USER_TYPE
            or ($environment->getCurrentModule() != CS_MATERIAL_TYPE
                and $environment->getCurrentModule() != CS_DISCUSSION_TYPE
                and $environment->getCurrentModule() != CS_ANNOUNCEMENT_TYPE
                and $environment->getCurrentModule() != CS_TOPIC_TYPE
                )
         ){

         $rubric_ids = array();
         $rubric_list = new cs_list();
         $rubric_manager = $environment->getManager($rubric);
         if ($rubric!=CS_PROJECT_TYPE and $rubric!=CS_MYROOM_TYPE){
            $rubric_manager->setContextLimit($environment->getCurrentContextID());
         }
         if ($rubric == CS_DATE_TYPE) {
            $rubric_manager->setWithoutDateModeLimit();
         }
         if ($rubric==CS_USER_TYPE) {
            $rubric_manager->setUserLimit();
            $current_user= $environment->getCurrentUser();
            if ( $current_user->isUser() ) {
                $rubric_manager->setVisibleToAllAndCommsy();
            } else {
                $rubric_manager->setVisibleToAll();
            }
         }
         $count_all = $count_all + $rubric_manager->getCountAll();

         if ( !empty($search) ) {
            $rubric_manager->setSearchLimit($search);
         }

         if ( $linked_only ) {
            $rubric_manager->setIDArrayLimit($selected_ids);
         }

         if ( $sel_activating_status == 2 ) {
            $rubric_manager->showNoNotActivatedEntries();
         }
         if ( $rubric != CS_MYROOM_TYPE ) {
            $rubric_manager->selectDistinct();
            $rubric_list = $rubric_manager->get();
         } else {
            $rubric_list = $rubric_manager->getRelatedContextListForUser($current_user->getUserID(),$current_user->getAuthSource(),$environment->getCurrentPortalID());;
         }
         $item_list->addList($rubric_list);
         if ($rubric!=CS_MYROOM_TYPE) {
            $temp_rubric_ids = $rubric_manager->getIDArray();
         } else {
            $current_user= $environment->getCurrentUser();
            $temp_rubric_ids = $rubric_manager->getRelatedContextListForUser($current_user->getUserID(),$current_user->getAuthSource(),$environment->getCurrentPortalID(),'id_array');;
         }
         if (!empty($temp_rubric_ids)){
            $rubric_ids = $temp_rubric_ids;
         }
         $session->setValue('cid'.$environment->getCurrentContextID().'_item_attach_index_ids', $rubric_ids);
         $item_ids = array_merge($item_ids, $rubric_ids);
         }
      }
      $sublist = $item_list->getSubList($from-1,$interval);
      $item_attach_index_view->setList($sublist);
      if ( !empty($_POST) ) {
         if ( !empty($_POST['orig_post_keys']) ) {
            $post_values = array();
            $post_array = explode('�',$_POST['orig_post_keys']);
            foreach ( $post_array as $key ) {
               $post_values_orig[$key] = $_POST[$key];
            }
         } else {
            $post_values_orig = $_POST;
         }
         $item_attach_index_view->setHiddenFields($post_values_orig);
      }
      $item_attach_index_view->setLinkedItemIDArray($selected_ids);
      // muss drin bleiben, da sonst ein neues Item angelegt wird
      $item_attach_index_view->setRefItemID($iid);
      // -----------------------------------------
      $item_attach_index_view->setCountAllShown(count($item_ids));
      $item_attach_index_view->setCountAll($count_all);
      $item_attach_index_view->setFrom($from);
      $item_attach_index_view->setInterval($interval);
      $item_attach_index_view->setChoosenRubric($selrubric);
      $item_attach_index_view->setActivationLimit($sel_activating_status);
   }
}
?>