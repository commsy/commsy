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

$params = $environment->getCurrentParameterArray();
$item_manager = $environment->getItemManager();
$tmp_item = $item_manager->getItem($params['iid']);
$manager = $environment->getManager($tmp_item->getItemType());
$item = $manager->getItem($params['iid']);

if ( !empty($option)
      and (isOption($option, getMessage('COMMON_BUZZWORD_ATTACH')))
    ) {
    $buzzword_array = array();
    if (isset($_POST['attach'])){
       $selected_id_array = $_POST['attach'];
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
    $item->setBuzzwordListByID($buzzword_array);
    $item->save();
    unset($params['attach_view']);
    unset($params['attach_type']);
    redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
}

if ( $session->issetValue('announcement_clipboard') ) {
   $clipboard_id_array = $session->getValue('announcement_clipboard');
} else {
   $clipboard_id_array = array();
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

$ids = $buzzword_manager->getIDArray();
$count_all_shown = count($ids);

// Set data for buzzword_view
$buzzword_view->setList($buzzword_list);
$buzzword_view->setItem($item);
$buzzword_view->setCountAllShown($count_all_shown);
$buzzword_view->setCountAll($count_all);
?>