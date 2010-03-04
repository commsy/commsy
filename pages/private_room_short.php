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


if (isset($c_use_new_private_room) and $c_use_new_private_room){

$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $context_item->isOpen();
$title_view = $class_factory->getClass(HOME_TITLE_VIEW,$params);
unset($params);
$page->add($title_view);

$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$portlet_view = $class_factory->getClass(PRIVATEROOM_HOME_PORTLET_VIEW,$params);
unset($params);

/* SEARCH */
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$search_view = $class_factory->getClass(PRIVATEROOM_HOME_SEARCH_VIEW,$params);
unset($params);
$portlet_array[] = $search_view;
/* SEARCH END */

/* ROOMS */
$room_manager = $environment->getMyRoomManager();
$user = $environment->getCurrentUserItem();
$room_manager->setIntervalLimit(0,4);
$room_manager->setSortOrder('activity_rev');
$current_user_item = $environment->getCurrentUserItem();
$room_manager->setUserIDLimit($current_user_item->getUserID());
$room_manager->setAuthSourceLimit($current_user_item->getAuthSource());
$room_manager->select();
$activ_room_list = $room_manager->getRelatedContextListForUser($user->getUserID(),$user->getAuthSource(),$environment->getCurrentPortalID());
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $with_modifying_actions;
$myroom_view = $class_factory->getClass(PRIVATEROOM_HOME_ROOM_VIEW,$params);
$myroom_view->setList($activ_room_list);
$portlet_array[] = $myroom_view;
/* ROOMS END */

/* CLOCK */
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$clock_view = $class_factory->getClass(PRIVATEROOM_HOME_CLOCK_VIEW,$params);
unset($params);
$portlet_array[] = $clock_view;
/* CLOCK END */

/* NEW ENTRIES */
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$new_entries_view = $class_factory->getClass(PRIVATEROOM_HOME_NEW_ENTRIES_VIEW,$params);
unset($params);
$portlet_array[] = $new_entries_view;
/* END NEW ENTRIES */


/* NEWS */
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$news_view = $class_factory->getClass(PRIVATEROOM_HOME_NEWS_VIEW,$params);
unset($params);
$portlet_array[] = $news_view;
/* END NEWS */

/* WEATHER */
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$weather_view = $class_factory->getClass(PRIVATEROOM_HOME_WEATHER_VIEW,$params);
unset($params);
$portlet_array[] = $weather_view;
/* WEATHER END */

/* BUZZWORDS */
if ( $current_context->withBuzzwords() ){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $buzzword_view = $class_factory->getClass(PRIVATEROOM_HOME_BUZZWORD_VIEW,$params);
   unset($params);
   $portlet_array[] = $buzzword_view;
}
/* END BUZZWORDS */

/* NEWS */
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$news_view = $class_factory->getClass(PRIVATEROOM_HOME_NEWS_VIEW,$params);
unset($params);
$portlet_array[] = $news_view;
/* END NEWS */


/* CONFIGURATION */
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$configuration_view = $class_factory->getClass(PRIVATEROOM_HOME_CONFIGURATION_VIEW,$params);
unset($params);
$portlet_array[] = $configuration_view;
/* CONFIGURATION END */

/* NEWS */
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$news_view = $class_factory->getClass(PRIVATEROOM_HOME_NEWS_VIEW,$params);
unset($params);
$portlet_array[] = $news_view;
/* END NEWS */


$portlet_view->setPortletViewArray($portlet_array);



include_once('classes/cs_list.php');

$portlet_view->setList($activ_room_list);
$page->add($portlet_view);






/**************/
/* Alter Code */
/**************/
}else{
$used_rubrics_for_room_array = array();
$shown_room_id_array = array();

if ( isset($_GET['delete_room_id']) and !empty($_GET['delete_room_id']) ){
   $manager = $environment->getPrivateRoomManager();
   $room_item =  $manager->getItem($_GET['delete_room_id']);
   if ( !empty($room_item) ){
      $user = $environment->getCurrentUserItem();
      $room_item->setNotShownInPrivateRoomHome($user->getUserID());
      $room_item->save();
   }
   redirect($environment->getCurrentContextID(),'home','index');
}

$user = $environment->getCurrentUserItem();
$manager = $environment->getPrivateRoomManager();
$current_context_item = $environment->getCurrentContextItem();
$list2 = $current_context_item->getCustomizedRoomList();
if ( !isset($list2) ) {
   // old style (CommSy6)
   $list2 = $manager->getRelatedContextListForUserOnPrivateRoomHome($user);
} else {
   // remove separators
   $list_temp = new cs_list();
   $list_item = $list2->getFirst();
   while($list_item){
      if ( $list_item->getItemID() > 0 ) {
         $list_temp->add($list_item);
      }
      $list_item = $list2->getNext();
   }
   $list2 = $list_temp;
}
if ( isset($_GET['from']) ) {
   $from = $_GET['from'];
}  else {
   $from = 1;
}

$i=1;
$end = $from+5;

$list3 = new cs_list();
$list_item = $list2->getFirst();
while($list_item){
   if ( ($i >= $from) and ($i < $end) ){
      $list3->add($list_item);
      $shown_room_id_array[] = $list_item->getItemID();
   }
   $i++;
   $list_item = $list2->getNext();
}
$countShown = $list2->getCount();


$user = $environment->getCurrentUserItem();
$my_room_manager = $environment->getMyRoomManager();
$list_all = $my_room_manager->getRelatedContextListForUser($user->getUserID(),$user->getAuthSource(),$environment->getCurrentPortalID());
$countAll = $list_all->getCount();


if ( isset($_GET['from']) ) {
   $from = $_GET['from'];
}  else {
   $from = 1;
}

$i=1;
$status = $current_context->getHomeStatus();
$end = $from+5;

$list3 = new cs_list();
$list_item = $list2->getFirst();
while($list_item){
   if ( ($i >= $from) and ($i < $end) ) {
      $list3->add($list_item);
   }
   $i++;
   $list_item = $list2->getNext();
}
$countShown = $list2->getCount();

if (!empty($shown_room_id_array)){
   $current_context = $environment->getCurrentContextItem();
   $item_manager = $environment->getItemManager();
   $item_manager->setAgeLimit($current_context->getTimeSpread());
   $shown_item_rubrics_array = $item_manager->getAllUsedRubricsOfRoomList($shown_room_id_array);

   $used_rubrics_for_room_array = array();
   foreach($shown_room_id_array as $room_id){
      foreach($shown_item_rubrics_array as $entry){
         if ($entry['context_id'] == $room_id){
            if ($entry['type'] == 'label'){
               $type = $entry['subtype'];
            } else {
               $type = $entry['type'];
            }
            $used_rubrics_for_room_array[$room_id][] = $type;
         }
      }
   }
}

$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $context_item->isOpen();
$title_view = $class_factory->getClass(HOME_TITLE_VIEW,$params);
unset($params);
$page->add($title_view);

$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$short_view = $class_factory->getClass(PRIVATEROOM_DETAILED_SHORT_VIEW,$params);
unset($params);
$short_view->setUsedRubricsForRoomsArray($used_rubrics_for_room_array);
$user_manager = $environment->getUserManager();
$short_view->setUserForRoomsArray($user_manager->getAllUsersByUserAndRoomIDLimit($user->getUserID(), $shown_room_id_array,$user->getAuthSource()));

include_once('classes/cs_list.php');

$short_view->setFrom($from);
$short_view->setInterval(5);
$short_view->setCountAll($countAll);
$short_view->setCountAllShown($countShown);
$short_view->setList($list3);
$page->addLeft($short_view);

}
/* Ende alter Code*/
?>