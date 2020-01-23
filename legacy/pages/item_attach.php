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

// ATTENTION
// _linked_items_index_selected_ids2 is for CommSy 7
// must be refactored when CommSy 6 ist gone
// 24.07.2009 ij

include_once('classes/cs_list.php');

if ( isset($_GET['iid']) ) {
   $ref_iid = $_GET['iid'];
} elseif ( isset($_POST['iid']) ) {
   $ref_iid = $_POST['iid'];
}

if ( isset($_POST['option']) ) {
   $option = $_POST['option'];
} elseif ( isset($_GET['option']) ) {
   $option = $_GET['option'];
} else {
   $option = '';
}

if ( isset($_POST['return_attach_item_list']) ) {
   $second_call = true;
} elseif ( isset($_GET['return_attach_item_list']) ) {
   $second_call = true;
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2');
} else {
   $second_call = false;
}

if ( isset($_GET['search']) ) {
   $search = $_GET['search'];
} elseif ( isset($_POST['search']) ) {
   $search = $_POST['search'];
} else {
   $search = '';
}
if ( isset($_POST['mode']) ) {
   $mode = $_POST['mode'];
} elseif ( isset($_GET['mode']) ) {
   $mode = $_GET['mode'];
} else {
   $mode = '';
}
$sel_activating_status = '';
if ( isset($_GET['selactivatingstatus']) and $_GET['selactivatingstatus'] !='-2') {
   $sel_activating_status = $_GET['selactivatingstatus'];
} elseif ( isset($_POST['selactivatingstatus']) and $_POST['selactivatingstatus'] !='-2') {
   $sel_activating_status = $_POST['selactivatingstatus'];
}else {
   $sel_activating_status = 2;
}
if ( isset($_POST['selrubric']) ) {
   $selrubric = $_POST['selrubric'];
   $from = 1;
} elseif ( isset($_GET['selrubric']) ) {
   $selrubric = $_GET['selrubric'];
}  else {
   $selrubric = '';
}

if ( !empty($_POST['linked_only']) and $_POST['linked_only'] == 1 ) {
   $linked_only = true;
} else {
   $linked_only = false;
}

// Get the translator object
$translator = $environment->getTranslationObject();

$params = $environment->getCurrentParameterArray();
$item_manager = $environment->getItemManager();
$tmp_item = $item_manager->getItem($ref_iid);
if ( isset($tmp_item) ) {
   $manager = $environment->getManager($tmp_item->getItemType());
   $item = $manager->getItem($ref_iid);
}
if ( isset($item) ) {
   if ( $item->isA(CS_LABEL_TYPE)
        and $item->getLabelType() == CS_GROUP_TYPE
      ) {
      $group_manager = $environment->getGroupManager();
      $item = $group_manager->getItem($ref_iid);
      unset($group_manager);
   } elseif ( $item->isA(CS_LABEL_TYPE)
        and $item->getLabelType() == CS_BUZZWORD_TYPE
      ) {
      $buzzword_manager = $environment->getBuzzwordManager();
      $item = $buzzword_manager->getItem($ref_iid);
      unset($buzzword_manager);
   }

   if ($environment->getCurrentModule() == CS_USER_TYPE){
      if (!$environment->inCommunityRoom()){
          $selected_ids = $item->getLinkedItemIDArray(CS_GROUP_TYPE);
      }
   } elseif ( $item->isA(CS_LABEL_TYPE)
              and $item->getLabelType() == CS_BUZZWORD_TYPE
            ) {
      $selected_ids = $item->getAllLinkedItemIDArrayLabelVersion();
   } else {
      $selected_ids = $item->getAllLinkedItemIDArray();
   }
}
if ( !isset($selected_ids) ) {
   $selected_ids = array();
}

// initial
if ( !$session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2') ) {
   $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2',$selected_ids);
}

if ( !empty($_POST['itemlist'])
     or !empty($_POST['shown'])
   ) {
   $sess_selected_ids = array();
   if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2')) {
      $sess_selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2');
   }
   if ( !empty($_POST['itemlist']) ) {
      foreach ($_POST['itemlist'] as $key => $id) {
         $sess_selected_ids[] = $key;
      }
   }
   if ( !empty($_POST['shown']) ) {
      $drop_array = array();
      foreach ( $_POST['shown'] as $id => $value) {
         if ( in_array($id,$sess_selected_ids)
              and ( empty($_POST['itemlist'])
                    or !array_key_exists($id,$_POST['itemlist'])
                  )
            ) {
            $drop_array[] = $id;
         }
      }
      if ( !empty($drop_array) ) {
         $temp_array = array();
         foreach ($sess_selected_ids as $id) {
            if ( !in_array($id,$drop_array) ) {
               $temp_array[] = $id;
            }
         }
         $sess_selected_ids = $temp_array;
      }
   }
   $sess_selected_ids = array_unique($sess_selected_ids);
   $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2',$sess_selected_ids);
}

if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2') ) {
   $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2');
}

if ($mode == '') {
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
}

// wie komme ich von einer liste über die actions hier her ???
// 2009.07.24 ij
elseif ( $mode == 'list_actions' ) {
   if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')) {
      $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
   }
}

// wird das noch gebraucht????
// ij 16.10.2009
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

$session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$selected_ids);

if ( isset($_POST['right_box_option2']) ) {
   $right_box_command2 = $_POST['right_box_option2'];
} elseif ( isset($_GET['right_box_option2']) ) {
   $right_box_command2 = $_GET['right_box_option2'];
} else {
   $right_box_command2 = '';
}

$browse_dir = '';
if ( strstr($right_box_command2, '_START') ) {
   $browse_dir = '_start';
   #$right_box_command = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
} elseif ( strstr($right_box_command2, '_LEFT') ) {
   $browse_dir = '_left';
   #$right_box_command = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
} elseif ( strstr($right_box_command2, '_RIGHT') ) {
   $browse_dir = '_right';
   #$right_box_command = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
} elseif ( strstr($right_box_command2, '_END') ) {
   $browse_dir = '_end';
   #$right_box_command = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
}

// Find current browsing starting point
if ( isset($_POST['from'.$browse_dir]) ) {
   $from = $_POST['from'.$browse_dir];
} elseif ( isset($_GET['from']) ) {
   $from = $_GET['from'];
} elseif ( isset($_POST['from']) ) {
   $from = $_POST['from'];
} else {
   $from = 1;
}

// Find current browsing interval
// The browsing interval is applied to all rubrics!
if ( isset($_GET['interval']) ) {
   $interval = $_GET['interval'];
} elseif ( isset($_POST['interval']) ) {
   $interval = $_POST['interval'];
} else {
   $interval = CS_LIST_INTERVAL;
}

if ( !empty($option)
      and (isOption($option, $translator->getMessage('COMMON_ITEM_ATTACH')))
    ) {
    $entry_array = array();
    $entry_new_array = array();
    if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_linked_items_index_selected_ids')) {
       $entry_array = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_linked_items_index_selected_ids');
    }
    if (isset($_POST['itemlist'])){
       $selected_id_array = $_POST['itemlist'];
       foreach($selected_id_array as $id => $value){
          $entry_new_array[] = $id;
       }
    }
    $entry_array = array_merge($entry_array,$entry_new_array);
    $entry_array = array_unique($entry_array);
    if ( isset($item)
         and $item->isA(CS_LABEL_TYPE)
         and $item->getLabelType() == CS_BUZZWORD_TYPE
       ) {
       $item->saveLinksByIDArray($entry_array);
    } elseif ( isset($item) )  {
       $item->setLinkedItemsByIDArray($entry_array);
       $item->save();
    }
    $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
    $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2');
    unset($params['attach_view']);
    unset($params['attach_type']);
    unset($params['from']);
    unset($params['pos']);
    unset($params['mode']);
    unset($params['return_attach_item_list']);
    if ( $environment->getCurrentModule() == type2module(CS_DATE_TYPE) ) {
       unset($params['date_option']);
    }
    if ( $environment->getCurrentModule() == type2module(CS_TODO_TYPE) ) {
       unset($params['todo_option']);
    }
    redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
}

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

if ( !empty($selrubric)
     and $selrubric != 'all'
     and $selrubric != 'campus_search'
     and $selrubric != -1
   ) {
   $rubric_array = array();
   $rubric_array[] = $selrubric;
}
if ($environment->getCurrentModule() == CS_USER_TYPE){
   $rubric_array = array();
   if ($context_item->withRubric(CS_GROUP_TYPE)){
      $rubric_array[] = CS_GROUP_TYPE;
   }
   $interval = 100;
}

// translation of entry to rubrics for new private room
if ( $environment->inPrivateRoom()
     and in_array(CS_ENTRY_TYPE,$rubric_array)
   ) {
   $temp_array = array();
   $temp_array2 = array();
   $rubric_array2 = array();
   $temp_array[] = CS_ANNOUNCEMENT_TYPE;
   $temp_array[] = CS_TODO_TYPE;
   $temp_array[] = CS_DISCUSSION_TYPE;
   $temp_array[] = CS_MATERIAL_TYPE;
   $temp_array[] = CS_DATE_TYPE;
   foreach ( $temp_array as $temp_rubric ) {
      if ( !in_array($temp_rubric,$rubric_array) ) {
         $temp_array2[] = $temp_rubric;
      }
   }
   foreach ( $rubric_array as $temp_rubric ) {
      if ( $temp_rubric != CS_ENTRY_TYPE ) {
         $rubric_array2[] = $temp_rubric;
      } else {
         $rubric_array2 = array_merge($rubric_array2,$temp_array2);
      }
   }
   $rubric_array = $rubric_array2;
   unset($rubric_array2);
}

foreach ($rubric_array as $rubric) {
   $rubric_ids = array();
   $rubric_list = new cs_list();
   $rubric_manager = $environment->getManager($rubric);
   if ( isset($rubric_manager)
        and $rubric != CS_MYROOM_TYPE
      ) {
      if ( $rubric != CS_PROJECT_TYPE
      #     and $rubric!=CS_MYROOM_TYPE
         ){
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
      $rubric_manager->selectDistinct();
      $rubric_list = $rubric_manager->get();

      // show hidded entries only if user is moderator or owner
      if($sel_activating_status != 2 && !$current_user->isModerator()) {
         // check if user is owner
         $entry = $rubric_list->getFirst();
         while($entry) {
            if($entry->isNotActivated() && $entry->getCreatorID() != $current_user->getItemID()) {
               // remove item from list
               $rubric_list->removeElement($entry);
            }

            $entry = $rubric_list->getNext();
         }
      }

      $item_list->addList($rubric_list);
      $temp_rubric_ids = $rubric_manager->getIDArray();
      if (!empty($temp_rubric_ids)){
         $rubric_ids = $temp_rubric_ids;
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_item_attach_index_ids', $rubric_ids);
      $item_ids = array_merge($item_ids, $rubric_ids);
   }
}

$sublist = $item_list->getSubList($from-1,$interval);
$item_attach_index_view->setList($sublist);

$item_attach_index_view->setLinkedItemIDArray($selected_ids);
$item_attach_index_view->setRefItemID($ref_iid);
$item_attach_index_view->setRefItem($item);
$item_attach_index_view->setCountAllShown(count($item_ids));
$item_attach_index_view->setCountAll($count_all);
$item_attach_index_view->setFrom($from);
$item_attach_index_view->setInterval($interval);
$item_attach_index_view->setSearchText($search);
$item_attach_index_view->setChoosenRubric($selrubric);
$item_attach_index_view->setActivationLimit($sel_activating_status);
?>