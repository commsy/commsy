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

include_once('classes/cs_list.php');

if ( isset($_GET['ref_attach_iid']) ) {
   $ref_iid = $_GET['ref_attach_iid'];
} elseif ( isset($_POST['ref_attach_iid']) ) {
   $ref_iid = $_POST['ref_attach_iid'];
}

if ( isset($_POST['option']) ) {
   $option = $_POST['option'];
} elseif ( isset($_GET['option']) ) {
   $option = $_GET['option'];
} else {
   $option = '';
}

if ( isset($_POST['return_attach_buzzword_list']) ) {
   $second_call = true;
} elseif ( isset($_GET['return_attach_buzzword_list']) ) {
   $second_call = true;
} else {
   $second_call = false;
}

$clean = true;
if(isset($_GET['rem_item_text'])) {
   $clean = false;
   $session_item = $environment->getSessionItem();
   if($session_item->issetValue('buzzword_add')) {
      $buzzword_attach_list = $session_item->getValue('buzzword_add');
	  $buzzword = $buzzword_attach_list->getFirst();
	  $temp_list = new cs_list;
	  while($buzzword) {
	     if(strcmp($buzzword, $_GET['rem_item_text']) != 0) $temp_list->add($buzzword);
	     $buzzword = $buzzword_attach_list->getNext();
	  }
	  $session_item->setValue('buzzword_add', $temp_list);
	  unset($temp_list);
   }
   unset($session_item);
   unset($_GET['rem_item_text']);
}

if ($environment->getCurrentFunction() != 'edit'){
   $params = $environment->getCurrentParameterArray();
   $item_manager = $environment->getItemManager();
   $tmp_item = $item_manager->getItem($params['iid']);
   $manager = $environment->getManager($tmp_item->getItemType());
   $item = $manager->getItem($params['iid']);
}

$session_item = $environment->getSessionItem();
if($session_item->issetValue('buzzword_add_duplicated')) {
   $session_item->unsetValue('buzzword_add_duplicated');
}

if (!empty($option) and (isOption($option, $translator->getMessage('COMMON_BUZZWORD_ATTACH')))) {
    // get state of existing buzzwords
    $buzzword_array = array();
    if (isset($_POST['buzzwordlist'])){
       $selected_id_array = $_POST['buzzwordlist'];
       foreach($selected_id_array as $id => $value){
          $buzzword_array[] = $id;
       }
    }
    
    // add buzzword attach list
    $session_item = $environment->getSessionItem();
    if($session_item->issetValue('buzzword_add')) {
       $buzzword_attach_list = $session_item->getValue('buzzword_add');
       $buzzword_manager = $environment->getLabelManager();
       $buzzword_manager->reset();
       $user = $environment->getCurrentUserItem();
       
       // iterate attach list
       $attach_item = $buzzword_attach_list->getFirst();
       while($attach_item) {
          // create new item
          $buzzword_item = $buzzword_manager->getNewItem();
          $buzzword_item->setLabelType('buzzword');
          $buzzword_item->setTitle($attach_item);
          $buzzword_item->setContextID($environment->getCurrentContextID());
          $buzzword_item->setCreatorItem($user);
          $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
          $buzzword_item->save();
          $buzzword_array[] = $buzzword_item->getItemID();
          
          $attach_item = $buzzword_attach_list->getNext();
       }
    }
    $session_item->unsetValue('buzzword_add');
    
    $item->setBuzzwordListByID($buzzword_array);
    $item->save();
    unset($params['attach_view']);
    unset($params['attach_type']);   
    redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
} elseif(!empty($option) and (isOption($option, $translator->getMessage('COMMON_BUZZWORD_ADD')))) {
   if(!empty($_POST['attach_new_buzzword'])) {
	   // set session item
	   $session_item = $environment->getSessionItem();
	   $buzzword_attach_list = $session_item->getValue('buzzword_add');
	   $exist = false;
	   if(!$session_item->issetValue('buzzword_add')) {
	      $buzzword_attach_list = new cs_list();
	   } else {
		  // check for duplicated entries in new buzzword list
		  $buzzword_manager = $environment->getLabelManager();
		  if (!empty($buzzword_attach_list) ){
		     $buzzword = $buzzword_attach_list->getFirst();
		     while ( $buzzword ){
		        if(strcmp($buzzword, ltrim($_POST['attach_new_buzzword'])) == 0) {
		           $exist = true;
		           break;
		        }
		        $buzzword = $buzzword_attach_list->getNext();
		     }
		  }
	   }
	   
	   // check for duplicated entries in existing buzzword list
       $buzzword_manager = $environment->getLabelManager();
       $buzzword_manager->reset();
       $buzzword_manager->setContextLimit($environment->getCurrentContextID());
       $buzzword_manager->setTypeLimit('buzzword');
       $buzzword_manager->select();
       $buzzword_list = $buzzword_manager->get();
       if ( !empty($buzzword_list) ){
          $buzzword = $buzzword_list->getFirst();
          while ( $buzzword ){
             if ( strcmp($buzzword->getName(), ltrim($_POST['attach_new_buzzword'])) == 0 ){
                $exist = true;
             }
             $buzzword = $buzzword_list->getNext();
          }
       }
	   
	   if($exist) {
	      // duplicated entry
	      $session_item->setValue('buzzword_add_duplicated', 'true');
	   } else {
	      $buzzword_attach_list->add(ltrim($_POST['attach_new_buzzword']));
	   }
	   
	   $session_item->setValue('buzzword_add', $buzzword_attach_list);
	   unset($session_item);
   }
} else {
   if($clean) {
	  // delete attach list when opening window
	  $session_item = $environment->getSessionItem();
	  if($session_item->issetValue('buzzword_add')) {
	     $session_item->unsetValue('buzzword_add');
	  }
   }
}

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

$ids = $buzzword_list->getIDArray();
$count_all_shown = count($ids);

// Set data for buzzword_view
$buzzword_view->setList($buzzword_list);
$buzzword_view->setItem($item);
$buzzword_view->setCountAllShown($count_all_shown);
$buzzword_view->setCountAll($count_all);
?>