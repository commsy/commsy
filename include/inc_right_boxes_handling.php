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

/*** Neue Schlagw�rter und Tags***/
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
            // Set data for buzzword_view
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

/*** Neue Schlagw�rter und Tags***/
?>