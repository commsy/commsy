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

include_once('classes/cs_list.php');

// get needed object
$translator = $environment->getTranslationObject();
$context_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();

if ( isset($_GET['ref_iid']) ) {
   $ref_iid = $_GET['ref_iid'];
} elseif ( isset($_POST['ref_iid']) ) {
   $ref_iid = $_POST['ref_iid'];
}

if ( isset($_GET['ref_user']) ) {
   $ref_user = $_GET['ref_user'];
} elseif ( isset($_POST['ref_user']) ) {
   $ref_user = $_POST['ref_user'];
} else{
   $ref_user ='';
}

$mode = 'browse';
if ( isset($_GET['mode']) ) {
   $mode = $_GET['mode'];
} elseif ( isset($_POST['mode']) ) {
   $mode = $_POST['mode'];
} else {
   unset($ref_iid);
   unset($ref_user);
}


if ( isset($_GET['material_mode']) ) {
   $material_mode = $_GET['material_mode'];
}else{
   $material_mode = '';
}

// Find current option
if ( isset($_POST['option']) ) {
   $option = $_POST['option'];
} elseif ( isset($_GET['option']) ) {
   $option = $_GET['option'];
} else {
   $option = '';
}

// Check access rights
if ($current_user->isGuest()) {
   if (!$context_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $context_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( !$context_item->isOpen() and !$context_item->isTemplate() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
} else {
   //access granted

   /*** Start of list display ***/

   // Find current browsing starting point
   if ( isset($_GET['from']) ) {
      $from = $_GET['from'];
   } else {
      $from = 1;
   }

   // Find current browsing interval
   // The browsing interval is applied to all rubrics
   if ( isset($_GET['interval']) ) {
      $interval = $_GET['interval'];
   } elseif ( $session->issetValue('interval') ) {
      $interval = $session->getValue('interval');
   } else {
      $interval = CS_LIST_INTERVAL;
   }

   // Find current sort key
   if ( isset($_GET['sort']) ) {
      $sort = $_GET['sort'];
   } else {
      $sort = 'date';
   }

   // Search / select form
   if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {

      $selstatus = 6;
      $search = '';
      $sellabel = '';
      $selbuzzword = '';
   } else {

      // Find current search text
      if ( isset($_GET['search']) ) {
         $search = $_GET['search'];
      } else {
         $search = '';
      }

      // Find current label selection
      if ( isset($_GET['sellabel']) ) {
         $sellabel = $_GET['sellabel'];
      } else {
         $sellabel = 0;
      }

      // Find current buzzword selection
      if ( isset($_GET['selbuzzword']) ) {
         $selbuzzword = $_GET['selbuzzword'];
      } else {
         $selbuzzword = 0;
      }

      // Find current status selection
      if ( isset($_POST['selstatus']) ) {
         $selstatus = $_POST['selstatus'];
         $from = 1;
      } elseif ( isset($_GET['selstatus']) ) {
         $selstatus = $_GET['selstatus'];
      } else {
         $selstatus = 6;
      }

   }


$context_item = $environment->getCurrentContextItem();


// LIST ACTIONS
// initiate selected array of IDs
$selected_ids = array();
if ($mode == '') {
   $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
}elseif ($mode == 'list_actions') {
   if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                 '_selected_ids')) {
      $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_selected_ids');
   }
}
      // Update attached items from cookie (requires JavaScript in browser)
      if ( isset($_COOKIE['attach']) ) {
         foreach ( $_COOKIE['attach'] as $key => $val ) {
            setcookie ('attach['.$key.']', '', time()-3600);
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

      // Update attached items from form post (works always)
      if ( isset($_POST['attach']) ) {
         foreach ( $_POST['shown'] as $shown_key => $shown_val ) {
            if ( array_key_exists($shown_key, $_POST['attach']) ) {
               if ( !in_array($shown_key, $selected_ids) ) {
                  $selected_ids[] = $shown_key;
               }
            } else {
               $idx = array_search($shown_key, $selected_ids);
               if ( $idx !== false ) {
                  unset($selected_ids[$idx]);
               }
            }
         }
      }


   ///////////////////////////////////////
   // perform list actions              //
   ///////////////////////////////////////
   if (!empty($material_mode) and !empty($_GET['id'])){
      $selected_ids = array();
      $selected_ids[] = $_GET['id'];
   }

   if ( ( ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO')) and $_POST['index_view_action'] != '-1' )
        or !empty($material_mode) )
        and !empty($selected_ids)
      ) {
      // prepare action process
      if  ((isset($_POST['index_view_action']) and ($_POST['index_view_action'] == '1')) or $material_mode =='public') {

            $action = 'COMMON_MATERIAL_PUBLISH';

            $material_manager = $environment->getMaterialManager();
            $reader_manager = $environment->getReaderManager();
            $first = true;
            foreach ($selected_ids as $id) {
               $item = $material_manager->getItem($id);
               // SET THE MATERIAL PUBLIC
               $item->setWorldPublic(2);

               // MAIL TO THE MODIFICATOR
               include_once('classes/cs_mail_obj.php');
               $mail_obj = new cs_mail_obj();

               //SENDER
               $sender[$current_user->getFullName()] = $current_user->getEmail();
               $mail_obj->setSender($sender);

               //RECEIVER
               $receiver_item = $item->getModificatorItem();
               $receiver[$receiver_item->getFullName()] = $receiver_item->getEmail();
               $mail_obj->addReceivers($receiver);

               //HEADLINE
               $mail_obj->setMailFormHeadLine($translator->getMessage('ADMIN_MAIL_ARCHIVE_SET_WOLRDPUBLIC_TITLE',$item->getTitle()));

               //SUBJECT AND BODY
               $user_language = $receiver_item->getLanguage();
               $save_language = $translator->getSelectedLanguage();
               $translator->setSelectedLanguage($user_language);

               $mail_subject = $translator->getMessage('MAIL_SUBJECT_MATERIAL_WORLDPUBLIC',$context_item->getTitle());
               $mail_body    = '';
               $mail_body   .= $translator->getEmailMessage('MAIL_BODY_HELLO',$receiver_item->getFullname());
               $mail_body   .= LF.LF;
               $mail_body   .= $translator->getEmailMessage('MAIL_BODY_MATERIAL_WORLDPUBLIC',$item->getTitle(),$context_item->getTitle());
               $mail_body   .= LF.LF;
               $mail_body   .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());

               $translator->setSelectedLanguage($save_language);
               unset($save_language);

               $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$context_item->getItemID().'&mod=material&fct=detail&iid='.$item->getItemID();
               $mail_body .= LF.LF;
               $mail_body .= $url;
               $mail_obj->setSubject($mail_subject);
               $mail_obj->setContent($mail_body);

               $history = $session->getValue('history');

               $params = array();
               if ($history[0]['function'] == 'detail') {
       $params['iid'] = $item->getItemID();
               }
               $mail_obj->setBackLink( $environment->getCurrentContextID(),
                 $history[0]['module'],$history[0]['function'],$params);
               if ( isset($_GET['automail']) ) {
                  if ( $_GET['automail'] == 'true' ) {
          $mail_obj->setSendMailAuto(true);
       }
               }
               if ($first){
                  $first_mail_obj = $mail_obj;
                  $first = false;
               }
               if (isset($old_mail_obj)){
                  $old_mail_obj->setNextMail($mail_obj);
               }
               $old_mail_obj = $mail_obj;

               // TASK
               // open task can be closed
               $task_manager = $environment->getTaskManager();
               $task_list = $task_manager->getTaskListForItem($item);
               //   $task_list = $item->getTaskList();
               if ($task_list->getCount() > 0) {
       $task_item = $task_list->getFirst();
       while ($task_item) {
          if ($task_item->getStatus() == 'REQUEST' and ($task_item->getTitle() == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC'or $task_item->getTitle() == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC_NEW_VERSION')) {
             $task_item->setStatus('CLOSED');
             $task_item->save();
          }
          $task_item = $task_list->getNext();
       }
               }

               // save item and redirect
               //   $item->setNoFileSave();
               $item->save();
            }
            $first_mail_obj->toSession();
            $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
            redirect($environment->getCurrentContextID(), 'mail', 'process', '');
      }
      elseif  ((isset($_POST['index_view_action']) and ($_POST['index_view_action'] == '2')) or $material_mode =='not_public') {
            $action = 'COMMON_MATERIAL_NOT_PUBLISH';
            $material_manager = $environment->getMaterialManager();
            $reader_manager = $environment->getReaderManager();
            $first = true;
            foreach ($selected_ids as $id) {
               $item = $material_manager->getItem($id);
               $item->setWorldPublic(0);

               // MAIL TO THE MODIFICATOR
               include_once('classes/cs_mail_obj.php');
               $mail_obj = new cs_mail_obj();

               //SENDER
               $sender[$current_user->getFullName()] = $current_user->getEmail();
               $mail_obj->setSender($sender);

               //RECEIVER
               $receiver_item = $item->getModificatorItem();
               $receiver[$receiver_item->getFullName()] = $receiver_item->getEmail();
               $mail_obj->addReceivers($receiver);

               //HEADLINE
               $mail_obj->setMailFormHeadLine($translator->getMessage('ADMIN_MAIL_ARCHIVE_SET_NOT_WOLRDPUBLIC_TITLE',$item->getTitle()));

               //SUBJECT AND BODY
               $user_language = $receiver_item->getLanguage();
               $save_language = $translator->getSelectedLanguage();
               $translator->setSelectedLanguage($user_language);

               $mail_subject = $translator->getMessage('MAIL_SUBJECT_MATERIAL_NOT_WORLDPUBLIC',$context_item->getTitle());
               $mail_body    = '';
               $mail_body   .= $translator->getEmailMessage('MAIL_BODY_HELLO',$receiver_item->getFullname());
               $mail_body   .= LF.LF;
               $mail_body   .= $translator->getEmailMessage('MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC',$item->getTitle(),$context_item->getTitle());
               $mail_body   .= LF.LF;
               $mail_body   .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());

               $translator->setSelectedLanguage($save_language);
               unset($save_language);

               $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID().'&mod=material&fct=detail&iid='.$item->getItemID();
               $mail_body .= "\n\n";
               $mail_body .= $url;
               $mail_obj->setSubject($mail_subject);
               $mail_obj->setContent($mail_body);
               $history = $session->getValue('history');
               if ($history[0]['function'] == 'detail') {
                  if (isset($history[1]) and $history[1]['function'] != 'process') {
          $mail_obj->setBackLink( $environment->getCurrentContextID(),
                                  $history[1]['module'],
                                  $history[1]['function'],
            $history[1]['parameter']);
       } else {
          $mail_obj->setBackLink( $environment->getCurrentContextID(),
                                  'material_admin',
            'index',
            '');
       }
               } else {
       $mail_obj->setBackLink( $environment->getCurrentContextID(),
                    $history[0]['module'],
                    $history[0]['function'],
                    $history[0]['parameter']);
               }

               if ( isset($_GET['automail']) ) {
                  if ( $_GET['automail'] == 'true' ) {
          $mail_obj->setSendMailAuto(true);
       }
               }
               if ($first){
                  $first_mail_obj = $mail_obj;
                  $first = false;
               }
               if (isset($old_mail_obj)){
                  $old_mail_obj->setNextMail($mail_obj);
               }
               $old_mail_obj = $mail_obj;

               // TASK
               // open task can be closed
               $task_manager = $environment->getTaskManager();
               $task_list = $task_manager->getTaskListForItem($item);
               //   $task_list = $item->getTaskList();
               if ($task_list->getCount() > 0) {
       $task_item = $task_list->getFirst();
       while ($task_item) {
          if ($task_item->getStatus() == 'REQUEST' and ($task_item->getTitle() == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC'or $task_item->getTitle() == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC_NEW_VERSION')) {
             $task_item->setStatus('CLOSED');
             $task_item->save();
          }
          $task_item = $task_list->getNext();
       }
               }
               $item->save();
            }
            $first_mail_obj->toSession();
            $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
            redirect($environment->getCurrentContextID(), 'mail', 'process', '');
      }
      $selected_ids = array();
      $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
   } // end if (perform list actions)


   // Get data from database
   $material_manager = $environment->getMaterialManager();
   $material_manager->create_tmp_table($environment->getCurrentContextID());
   $material_manager->setContextLimit($environment->getCurrentContextID());
   $material_manager->setPublicLimit(6);
   $count_all = $material_manager->getCountAll();
   $material_manager->resetData();
   if ( !empty($sort) ) {
      $material_manager->setOrder($sort);
   }
   if ( !empty($search) ) {
      $material_manager->setSearchLimit($search);
   }
   if ( !empty($sellabel) ) {
      $material_manager->setLabelLimit($sellabel);
   }
   if ( !empty($selbuzzword) ) {
      $material_manager->setBuzzwordLimit($selbuzzword);
   }
   if ( $interval > 0 ) {
      $material_manager->setIntervalLimit($from-1, $interval);
   }
   if ( !empty($selstatus) and $selstatus != 6) {
      $material_manager->setPublicLimit($selstatus);
   }
   $ids = $material_manager->getIDs();       // returns an array of item ids
   $material_manager->select();
   $list = $material_manager->get();        // returns a cs_list of material_items
   $material_manager->delete_tmp_table();
   $count_all_shown = count($ids);

   // Get available labels
   $label_manager = $environment->getLabelManager();
   $mat_label_manager = clone $label_manager;
   $mat_label_manager->resetLimits();
   $mat_label_manager->setContextLimit($environment->getCurrentContextID());
   $mat_label_manager->setTypeLimit('label');
   $mat_label_manager->select();
   $label_list = $mat_label_manager->get();

   // Get available buzzwords
   $label_manager = $environment->getLabelManager();
   $buzzword_manager = clone $label_manager;
   $buzzword_manager->resetLimits();
   $buzzword_manager->setContextLimit($environment->getCurrentContextID());
   $buzzword_manager->setTypeLimit('buzzword');
   $buzzword_manager->select();
   $buzzword_list = $buzzword_manager->get();

   $with_modifying_actions = true;     // Community room
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $with_modifying_actions;
   $view = $class_factory->getClass(MATERIAL_ADMIN_INDEX_VIEW,$params);
   unset($params);

if (isset($_GET['select']) and $_GET['select']=='all'){
   $item = $list->getFirst();
   while($item){
      if ( !in_array($item->getItemID(), $selected_ids) ) {
         $selected_ids[] = $item->getItemID();
      }
      $item = $list->getNext();
   }
}
   // Set data for view
   $view->setList($list);
   $view->setCountAllShown($count_all_shown);
   $view->setCountAll($count_all);
   $view->setFrom($from);
   $view->setInterval($interval);
   $view->setSortKey($sort);
   $view->setSearchText($search);
   $view->setSelectedLabel($sellabel);
   $view->setAvailableLabels($label_list);
   $view->setAvailableBuzzwords($buzzword_list);
   $view->setSelectedBuzzword($selbuzzword);
   $view->setSelectedStatus($selstatus);

if ( !empty($ref_iid) and $mode =='attached'){
   $item_manager = $environment->getItemManager();
   $ref_item_type = $item_manager->getItemType($ref_iid);
   $ref_item_manager = $environment->getManager($ref_item_type);
   $ref_item = $ref_item_manager->getItem($ref_iid);
   $view->setRefItem($ref_item);
   $view->setRefIid($ref_iid);
   $view->setIsAttachedList();
} elseif ( !empty($ref_user) and $mode =='attached'){
   $item_manager = $environment->getItemManager();
   $ref_item_type = $item_manager->getItemType($ref_user);
   $ref_item_manager = $environment->getManager(CS_USER_TYPE);
   $ref_item = $ref_item_manager->getItem($ref_user);
   $view->setRefItem($ref_item);
   $view->setRefUser($ref_user);
   $view->setIsAttachedList();
}


if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $view->setRefIID($ref_iid);
   if (isset($ref_user)) {
     $view->setRefUser($ref_user);
   }
   $view->setHasCheckboxes($mode);
   $view->setCheckedIDs($new_attach_ids);
   $view->setDontEditIDs($dontedit_attach_ids);
}elseif ($mode == 'attach'){
   $view->setHasCheckboxes('list_actions');
}else{
   $view->setCheckedIDs($selected_ids);
   $view->setHasCheckboxes('list_actions');
}


   // Add list view to page
   $page->add($view);

   // Safe information in session for later use
// Safe information in session for later use
$session->setValue('interval', $interval); // interval is applied to all rubrics
$session->setValue('cid'.$environment->getCurrentContextID().'_'.'material'.'_selected_ids', $selected_ids);
$session->setValue('cid'.$environment->getCurrentContextID().'_material_admin_index_ids', $ids);
}
?>