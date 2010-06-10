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


$room_id_array = array();
$grouproom_list = $current_user_item->getRelatedGroupList();
if ( isset($grouproom_list) and $grouproom_list->isNotEmpty()) {
   $grouproom_list->reverse();
   $grouproom_item = $grouproom_list->getFirst();
   while ($grouproom_item) {
      $project_room_id = $grouproom_item->getLinkedProjectItemID();
      if ( in_array($project_room_id,$room_id_array) ) {
         $room_id_array_temp = array();
         foreach ($room_id_array as $value) {
            $room_id_array_temp[] = $value;
            if ( $value == $project_room_id) {
                $room_id_array_temp[] = $grouproom_item->getItemID();
            }
         }
         $room_id_array = $room_id_array_temp;
      }
      $grouproom_item = $grouproom_list->getNext();
   }
}
$project_list = $current_user_item->getRelatedProjectList();
if ( isset($project_list) and $project_list->isNotEmpty()) {
   $project_item = $project_list->getFirst();
   while ($project_item) {
       $room_id_array[] = $project_item->getItemID();
       $project_item = $project_list->getNext();
   }
}
$community_list = $current_user_item->getRelatedcommunityList();
if ( isset($community_list) and $community_list->isNotEmpty()) {
   $community_item = $community_list->getFirst();
   while ($community_item) {
       $room_id_array[] = $community_item->getItemID();
       $community_item = $community_list->getNext();
   }
}
$room_id_array_without_privateroom = $room_id_array;
$room_id_array[] = $current_context->getItemID();


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

$portlet_array = array();

if ($current_context->getPortletShowNewEntryList()){
   /* NEW ENTRIES */
   $params = array();
   $params['environment'] = $environment;
   $item_manager = $environment->getItemManager();
   $item_manager->setOrderLimit(true);
   $item_manager->setIntervalLimit($current_context->getPortletNewEntryListCount());
   $new_entry_array = $item_manager->getAllNewPrivateRoomEntriesOfRoomList($room_id_array_without_privateroom);
   $new_entry_list = $item_manager->getPrivateRoomHomeItemList($new_entry_array);
   #$item_manager->setContextArrayLimit($room_id_array);
   #$item_manager->select();
   #$new_entry_list = $item_manager->getList();
   $params['with_modifying_actions'] = $current_context->isOpen();
   $new_entries_view = $class_factory->getClass(PRIVATEROOM_HOME_NEW_ENTRIES_VIEW,$params);
   $new_entries_view->setList($new_entry_list);
   unset($params);
   $portlet_array[] = $new_entries_view;
   /* END NEW ENTRIES */
}

/* ROOMS */
if ($current_context->getPortletShowActiveRoomList()){
   $room_manager = $environment->getMyRoomManager();
   $user = $environment->getCurrentUserItem();
   $room_manager->setIntervalLimit(0,$current_context->getPortletActiveRoomCount());
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
   $portlet_view->setList($activ_room_list);
}
/* ROOMS END */

/* SEARCH */
if ($current_context->getPortletShowSearchBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $search_view = $class_factory->getClass(PRIVATEROOM_HOME_SEARCH_VIEW,$params);
   unset($params);
   $portlet_array[] = $search_view;
}
/* SEARCH END */

/* WEATHER */
if ($current_context->getPortletShowWeatherBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $weather_view = $class_factory->getClass(PRIVATEROOM_HOME_WEATHER_VIEW,$params);
   unset($params);
   $portlet_array[] = $weather_view;
}
/* WEATHER END */


/* DOKUVERSER*/
if ($current_context->getPortletShowDokuverserBox()){
   $params = array();
	$params['environment'] = $environment;
	$params['with_modifying_actions'] = $current_context->isOpen();
	$dokuverser_view = $class_factory->getClass(PRIVATEROOM_HOME_DOKUVERSER_VIEW,$params);
	unset($params);
	$portlet_array[] = $dokuverser_view;
}
/* END DOKUVERSER */


/* CLOCK */
if ($current_context->getPortletShowClockBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $clock_view = $class_factory->getClass(PRIVATEROOM_HOME_CLOCK_VIEW,$params);
   unset($params);
   $portlet_array[] = $clock_view;
}
/* CLOCK END */


/* TWITTER */
if ($current_context->getPortletShowTwitter()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $twitter_view = $class_factory->getClass(PRIVATEROOM_HOME_TWITTER_VIEW,$params);
   unset($params);
   $twitter_view->setTwitterID($current_context->getPortletTwitterAccount());
   $portlet_array[] = $twitter_view;
}
/* END TWITTER */


/* RSS TICKER */
if ($current_context->getPortletShowRSS()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $rss_view = $class_factory->getClass(PRIVATEROOM_HOME_RSS_TICKER_VIEW,$params);
   unset($params);
   $portlet_array[] = $rss_view;
}
/* RSS TICKER */

/* CONFIGURATION */
if ($current_context->getPortletShowConfigurationBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $configuration_view = $class_factory->getClass(PRIVATEROOM_HOME_CONFIGURATION_VIEW,$params);
   unset($params);
   $portlet_array[] = $configuration_view;
}
/* CONFIGURATION END */

/* BUZZWORDS */
if ( $current_context->withBuzzwords() and $current_context->getPortletShowBuzzwordBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $buzzword_view = $class_factory->getClass(PRIVATEROOM_HOME_BUZZWORD_VIEW,$params);
   unset($params);
   $portlet_array[] = $buzzword_view;
}
/* END BUZZWORDS */



/* NEWS
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$news_view = $class_factory->getClass(PRIVATEROOM_HOME_NEWS_VIEW,$params);
unset($params);
$portlet_array[] = $news_view;
 END NEWS */

/* FLICKR */
if ($current_context->getPortletShowFlickr()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $flickr_view = $class_factory->getClass(PRIVATEROOM_HOME_FLICKR_VIEW,$params);
   $flickr_view->setFlickrID($current_context->getPortletFlickrID());
   unset($params);
   $portlet_array[] = $flickr_view;
}
/* END FLICKR */

/* YOUTUBE */
if ($current_context->getPortletShowYouTube()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $youtube_view = $class_factory->getClass(PRIVATEROOM_HOME_YOUTUBE_VIEW,$params);
   $youtube_view->setChannelID('zdf');
   unset($params);
   $portlet_array[] = $youtube_view;
}
/* END YOUTUBE */

/* NEW_ITEM */
if ($current_context->getPortletShowNewItem()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $search_view = $class_factory->getClass(PRIVATEROOM_HOME_NEW_ITEM_VIEW,$params);
   unset($params);
   $portlet_array[] = $search_view;
}
/* NEW_ITEM END */

$portlet_view->setPortletViewArray($portlet_array);



include_once('classes/cs_list.php');

$portlet_view->setColumnCount($current_context->getPortletColumnCount());
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