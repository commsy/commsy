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

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

// define rubric connections

if (!empty($_GET['ref_type'])) {
   $ref_type = $_GET['ref_type'];
   $session->setValue('ref_type',$ref_type);
} elseif ($session->issetValue('ref_type')){
   $ref_type = $session->getValue('ref_type');
} else {
   $ref_type = '';
}

$rubric_link = Type2Module($ref_type);
$type = strtoupper($ref_type);

$cs_rubric_connection = xml2array(CS_RUBIC_CONNECTIONS);
$rubric_array = $cs_rubric_connection['RUBRIC_CONNECTIONS'];

$rubric_connection = array();
foreach ($rubric_array as $key => $rubric) {
   if ($type == $key){
      foreach($rubric as $key => $entry) {
         if ($key == strtoupper(CS_INSTITUTION_TYPE)) {
            $context_item = $environment->getCurrentContextItem();
            if ($context_item->isCommunityRoom() and $context_item->withRubric(CS_INSTITUTION_TYPE)) {
               $rubric_connection[] = strtolower($key);
            }
         } else {
            $rubric_connection[] = strtolower($key);
         }
      }
   }
}

$current_user = $environment->getCurrentUserItem();

// Check access rights
if (!$current_user->isUser()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $error_string = getMessage('LOGIN_NOT_ALLOWED');
   $errorbox->setText($error_string);
   $page->add($errorbox);
   $command = 'error';
}

if ($command != 'error') { // only if user is allowed to edit item
   // include form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(LINK_ITEM_FORM,$class_params);
   unset($class_params);
   $form->setRubricConnections($rubric_connection);

   // cancel edit process
   if ( isOption($command,getMessage('COMMON_CANCEL_BUTTON')) ) {
      $session->unsetValue('attach_module');
      $session->unsetValue('material_http_post_vars');
      $session->unsetValue('material_attachments');
      $session->unsetValue('material_history_attachments');
      $session->unsetValue('material_attachments_ids');
      $session->unsetValue('material_history_attachments_ids');
      $session->unsetValue('material_list_mode');
      $session->unsetValue('rubric_attachments_institution');
      $session->unsetValue('rubric_attachments_user');
      $session->unsetValue('ref_type');
      $params = array();
      $params['iid'] = $_POST['iid'];
      redirect($environment->getCurrentContextID(), $rubric_link, 'detail', $params);
   }

   // save link_items, goto attach materials or search for topics
   else {
      if (in_array(CS_TOPIC_TYPE,$rubric_connection)){
         // search topics
         if (isOption($command,getMessage('RUBRIC_DO_ATTACH_TOPIC_BUTTON'))) {
            $label_array = array();
            $label_id_array = array();
            // old topics attached and checked
            if (isset($_POST[CS_TOPIC_TYPE])) {
               foreach ($_POST[CS_TOPIC_TYPE] as $topic) {
                  $temp_array = XML2Array($topic);
                  $temp_array2 = array();
                  $temp_array2['iid'] = $temp_array['VALUE']['ID'];
                  $temp_array2['vid'] = $temp_array['VALUE']['VERSION'];
                  $label_array[] = $temp_array2;
                  $label_id_array[] = $temp_array['VALUE']['ID'];
               }
            }
            // search new topics
            if (!empty($_POST['topic_search'])) {
               $label_manager = $environment->getTopicManager();
               // not search limit, so only in names will be searched, not in description, ...
               $label_manager->setNameLimit($_POST['topic_search']);
               $label_manager->select();
               $label_list = $label_manager->get();
               if (!$label_list->isEmpty()) {
                  $label_item = $label_list->getFirst();
                  if ($label_list->getCount() == 1) {
                     $add_topic_mark = '<VALUE><ID>'.$label_item->getItemID(NONE).'</ID><VERSION>'.$label_item->getVersionID(NONE).'</VERSION></VALUE>';
                  }
                  while ($label_item) {
                     if (!in_array($label_item->getItemID(NONE),$label_id_array)) {
                        $temp_array = array();
                        $temp_array['iid'] = $label_item->getItemID(NONE);
                        $temp_array['vid'] = $label_item->getVersionID(NONE);
                        $label_array[] = $temp_array;
                     }
                     $label_item = $label_list->getNext();
                  }
                  unset($_POST['topic_search']);
               } else {
                  $context_item = $environment->getCurrentContextItem();
                  $failure['name'] = 'topic_search';
                  $failure['text'] = getMessage('COMMON_ADD_TOPIC_ERROR',$_POST['topic_search'],$context_item->getTitle());
               }
            } else {
               $failure['name'] = 'topic_search';
               $failure['text'] = getMessage('COMMON_ERROR_FIELD',getMessage('RUBRIC_TOPIC'));
            }
            $session->setValue('rubric_attachments_topic',$label_array);
         }
      }

      if (in_array(CS_INSTITUTION_TYPE,$rubric_connection)){
         // search institutions
         if (isOption($command,getMessage('RUBRIC_DO_ATTACH_INSTITUTION_BUTTON'))) {
            $label_array = array();
            $label_id_array = array();
            // old institutions attached and checked
            if (isset($_POST[CS_INSTITUTION_TYPE])) {
               foreach ($_POST[CS_INSTITUTION_TYPE] as $institution) {
                  $temp_array = XML2Array($institution);
                  $temp_array2 = array();
                  $temp_array2['iid'] = $temp_array['VALUE']['ID'];
                  $temp_array2['vid'] = $temp_array['VALUE']['VERSION'];
                  $label_array[] = $temp_array2;
                  $label_id_array[] = $temp_array['VALUE']['ID'];
               }
            }
            // search new institutions
            if (!empty($_POST['institution_search'])) {
               $label_manager = $environment->getInstitutionManager();
               // not search limit, so only in names will be searched, not in description, ...
               $label_manager->setNameLimit($_POST['institution_search']);
               $label_manager->select();
               $label_list = $label_manager->get();
               if (!$label_list->isEmpty()) {
                  $label_item = $label_list->getFirst();
                  if ($label_list->getCount() == 1) {
                     $add_institution_mark = '<VALUE><ID>'.$label_item->getItemID(NONE).'</ID><VERSION>'.$label_item->getVersionID(NONE).'</VERSION></VALUE>';
                  }
                  while ($label_item) {
                     if (!in_array($label_item->getItemID(),$label_id_array)) {
                        $temp_array = array();
                        $temp_array['iid'] = $label_item->getItemID(NONE);
                        $temp_array['vid'] = $label_item->getVersionID(NONE);
                        $label_array[] = $temp_array;
                     }
                     $label_item = $label_list->getNext();
                  }
                  unset($_POST['institution_search']);
               } else {
                  $context_item = $environment->getCurrentContextItem();
                  $failure['name'] = 'institution_search';
                  $failure['text'] = getMessage('COMMON_ADD_INSTITUTION_ERROR',$_POST['institution_search'],$context_item->getTitle());
               }
            } else {
               $failure['name'] = 'institution_search';
               $failure['text'] = getMessage('COMMON_ERROR_FIELD',getMessage('RUBRIC_INSTITUTION'));
            }
            $session->setValue('rubric_attachments_institution',$label_array);
         }
      }

      if (in_array(CS_MATERIAL_TYPE,$rubric_connection)){
         // redirect to attach materials
         if (isOption($command,getMessage('RUBRIC_DO_ATTACH_MATERIAL_BUTTON'))) {
            $session->setValue('material_http_post_vars',$_POST);
            $session->setValue('attach_module','link_item');
            $session->setValue('material_attach_room_id',$environment->getCurrentContextID());
            if (isset($_POST[CS_MATERIAL_TYPE])) {
               $material_ids = array();
               $material_array = array();
               foreach ($_POST[CS_MATERIAL_TYPE] as $material) {
                  $temp_array = XML2Array($material);
                  $temp_array2 = array();
                  $temp_array2['iid'] = $temp_array['VALUE']['ID'];
                  $temp_array2['vid'] = $temp_array['VALUE']['VERSION'];
                  $material_array[] = $temp_array2;
                  $material_ids[] = $temp_array['VALUE']['ID'];
               }
               $session->setValue('material_attachments',$material_array);
               $session->setValue('material_history_attachments',$material_array);
               $session->setValue('material_attachments_ids',$material_ids);
               $session->setValue('material_history_attachments_ids',$material_ids);
            } else {
               $session->unsetValue('material_attachments');
               $session->unsetValue('material_history_attachments');
               $session->unsetValue('material_attachments_ids');
               $session->unsetValue('material_history_attachments_ids');
            }
            $params = array();
            $params['attach'] = 'true';
            $params['material_list_mode'] = 'attach_from_campus';
            redirect($environment->getCurrentContextID(),'material','attach',$params);
         }
      }
      // init data display
      if (!empty($_GET['iid'])) { // change link_items
         $item_manager = $environment->getManager($ref_type);
         $item = $item_manager->getItem($_GET['iid']);
         $form->setItem($item);

         if (in_array(CS_MATERIAL_TYPE,$rubric_connection)){
            $session->unsetValue('material_attachments');
            $session->unsetValue('material_history_attachments');
            $material_array_for_session = array();
            $material_list = $item->getLinkedItemList(CS_MATERIAL_TYPE);
            if ($material_list->getCount() > 0) {
               $material_item = $material_list->getFirst();
               while ($material_item) {
                  $temp_array = array();
                  $temp_array['name'] = $this->_text_as_html_short($material_item->getTitle());
                  $temp_array['iid']  = $material_item->getItemID(NONE);
                  $temp_array['vid']  = $material_item->getVersionID(NONE);
                  $material_array_for_session[] = $temp_array;
                  $material_item = $material_list->getNext();
               }
               if (count($material_array_for_session) > 1) {
                  $field = 'name';
                  usort($material_array_for_session,create_function('$a,$b','return strnatcasecmp($a[\''.$field.'\'],$b[\''.$field.'\']);'));
               }
               if (count($material_array_for_session) > 0) {
                  $session->setValue('material_history_attachments',$material_array_for_session);
                  $session->setValue('material_attachments',$material_array_for_session);
               }
            }
         }

         if (in_array(CS_TOPIC_TYPE,$rubric_connection)){
            // topics
            $session->unsetValue('rubric_attachments_topic');
            $topic_array_for_session = array();
            $topic_list = $item->getLinkedItemList(CS_TOPIC_TYPE);
            if ($topic_list->getCount() > 0) {
               $topic_item = $topic_list->getFirst();
               while ($topic_item) {
                  $temp_array = array();
                  $temp_array['name'] = $topic_item->getName(NONE);
                  $temp_array['iid']  = $topic_item->getItemID(NONE);
                  $topic_array_for_session[] = $temp_array;
                  $topic_item = $topic_list->getNext();
               }
               if (count($topic_array_for_session) > 1) {
                  $field = 'name';
                  usort($topic_array_for_session,create_function('$a,$b','return strnatcasecmp($a[\''.$field.'\'],$b[\''.$field.'\']);'));
               }
               if (count($topic_array_for_session) > 0) {
                  $session->setValue('rubric_attachments_topic',$topic_array_for_session);
               }
            }
         }


         if (in_array(CS_INSTITUTION_TYPE,$rubric_connection)){
            // institutions
            $session->unsetValue('rubric_attachments_institution');
            $institution_array_for_session = array();
            $institution_list = $item->getLinkedItemList(CS_INSTITUTION_TYPE);
            if ($institution_list->getCount() > 0) {
               $institution_item = $institution_list->getFirst();
               while ($institution_item) {
                  $temp_array = array();
                  $temp_array['name'] = $institution_item->getName(NONE);
                  $temp_array['iid']  = $institution_item->getItemID(NONE);
                  $institution_array_for_session[] = $temp_array;
                  $institution_item = $institution_list->getNext();
               }
               if (count($institution_array_for_session) > 1) {
                  $field = 'name';
                  usort($institution_array_for_session,create_function('$a,$b','return strnatcasecmp($a[\''.$field.'\'],$b[\''.$field.'\']);'));
               }
               if (count($institution_array_for_session) > 0) {
                  $session->setValue('rubric_attachments_institution',$institution_array_for_session);
               }
            }
         }

         // delete session data
         $session->unsetValue('material_history_attachments_ids');
         $session->unsetValue('material_attachments_ids');
         $session->unsetValue('material_http_post_vars');
         $session->unsetValue('attach_module');
         $session->unsetValue('material_list_mode');
      }
      elseif (!empty($_POST)) {                         // second call of form: set post vars
         $session_post_vars = $_POST;

         if (in_array(CS_TOPIC_TYPE,$rubric_connection)){
            // if only one topic have been found, mark it (the checkbox) in the form
            if (!empty($add_topic_mark)) {
               $session_post_vars[CS_TOPIC_TYPE][] = $add_topic_mark;
            }
         }

         if (in_array(CS_INSTITUTION_TYPE,$rubric_connection)){
            // if only one institution have been found, mark it (the checkbox) in the form
            if (!empty($add_institution_mark)) {
               $session_post_vars[CS_INSTITUTION_TYPE][] = $add_institution_mark;
            }
         }

         $form->setFormPost($session_post_vars);
      } else {                                                // back from attach mode
         $material_session_post_vars = $session->getValue('material_http_post_vars');
         if (!empty($material_session_post_vars)) {
            if (!empty($material_session_post_vars)){
               $session_post_vars = $session->getValue('material_http_post_vars');
            } else {
               $session_post_vars = array();
            }
            $material_attachments = $session->getValue('material_attachments');
            if (!empty($material_attachments)) {
               $material_mark_array = array();
               foreach ($material_attachments as $material) {
                  $material_mark_array[] = '<VALUE><ID>'.$material['iid'].'</ID><VERSION>'.$material['vid'].'</VERSION></VALUE>';
               }
               $session_post_vars[CS_MATERIAL_TYPE] = $material_mark_array;
            }
            $form->setFormPost($session_post_vars);
         }
      }

      // init form, create form and loadValues
      if ($session->issetValue('material_attachments')) {
         $form->setSessionArray(CS_MATERIAL_TYPE,$session->getValue('material_attachments'));
      }
      if ($session->issetValue('rubric_attachments_topic')) {
         $form->setSessionArray(CS_TOPIC_TYPE,$session->getValue('rubric_attachments_topic'));
      }
      if ($session->issetValue('rubric_attachments_institution')) {
         $form->setSessionArray(CS_INSTITUTION_TYPE,$session->getValue('rubric_attachments_institution'));
      }
      $form->prepareForm();
      $form->loadValues();
      if (isset($failure)) {
         $form->setFailure($failure['name'],'',$failure['text']);
      }

      if (!empty($command) AND isOption($command,getMessage('LINK_ITEM_SAVE_BUTTON')) ) {
         $correct = $form->check();
         if ($correct) {
            $item_manager = $environment->getManager($ref_type);
            if (!empty($_POST['iid'])) { // change link_item
               $item = $item_manager->getItem($_POST['iid']);
               $item->setModificatorItem($environment->getCurrentUserItem());
            }

            if (in_array(CS_TOPIC_TYPE,$rubric_connection)){
               // topics
               $topic_array = array();
               if (!empty($_POST[CS_TOPIC_TYPE])) {
                  foreach ($_POST[CS_TOPIC_TYPE] as $data) {
                     $data_array = XML2Array($data);
                     $topic_array[] = $data_array['VALUE']['ID'];
                  }
               }
               $item->setTopicListByID($topic_array);
            }

            if (in_array(CS_INSTITUTION_TYPE,$rubric_connection)){
               // institutions
               $institution_array = array();
               if (!empty($_POST[CS_INSTITUTION_TYPE])) {
                  foreach ($_POST[CS_INSTITUTION_TYPE] as $data) {
                     $data_array = XML2Array($data);
                     $institution_array[] = $data_array['VALUE']['ID'];
                  }
               }
               $item->setInstitutionListByID($institution_array);
            }

            if (in_array(CS_MATERIAL_TYPE,$rubric_connection)){
               // material
               $material_array = array();
               if (!empty($_POST['material'])) {
                  foreach ($_POST['material'] as $data) {
                     $data_array = XML2Array($data);
                     $temp_array = array();
                     $temp_array['iid'] = $data_array['VALUE']['ID'];
                     $temp_array['vid'] = $data_array['VALUE']['VERSION'];
                     $material_array[] = $temp_array;
                  }
               }
               $item->setMaterialListByID($material_array);
            }

            // save item
            $item->save();

            // delete session variables
            $session->unsetValue('attach_module');
            $session->unsetValue('material_attachments');
            $session->unsetValue('material_history_attachments');
            $session->unsetValue('material_attachments_ids');
            $session->unsetValue('material_history_attachments_ids');
            $session->unsetValue('material_http_post_vars');
            $session->unsetValue('material_list_mode');
            $session->unsetValue('rubric_attachments_topic');
            $session->unsetValue('rubric_attachments_institution');
            $session->unsetValue('rubric_attachments_ids_topic');
            $session->unsetValue('rubric_attachments_user');
            $session->unsetValue('ref_type');


            // redirect, if there is no email to send
            $params = array();
            $params['iid'] = $item->getItemID();
            redirect($environment->getCurrentContextID(), $rubric_link, 'detail', $params);
         }
      }

      // display form
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      unset($class_params);
      $form_view->setAction(curl($environment->getCurrentContextID(),'link_item','edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
      $page->setBoldRubric($rubric_link);
   }
}
?>