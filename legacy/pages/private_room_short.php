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

$room_id_array = array();
$grouproom_list = $current_user_item->getUserRelatedGroupList();
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
$project_list = $current_user_item->getUserRelatedProjectList();
if ( isset($project_list) and $project_list->isNotEmpty()) {
   $project_item = $project_list->getFirst();
   while ($project_item) {
       $room_id_array[] = $project_item->getItemID();
       $project_item = $project_list->getNext();
   }
}
$community_list = $current_user_item->getUserRelatedCommunityList();
if ( isset($community_list) and $community_list->isNotEmpty()) {
   $community_item = $community_list->getFirst();
   while ($community_item) {
       $room_id_array[] = $community_item->getItemID();
       $community_item = $community_list->getNext();
   }
}
$room_id_array_without_privateroom = $room_id_array;
#$room_id_array[] = $environment->getCurrentContextID();
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
   if(isset($room_id_array_without_privateroom) and !empty($room_id_array_without_privateroom)){
      $new_entry_array = $item_manager->getAllNewPrivateRoomEntriesOfRoomList($room_id_array_without_privateroom);
      $new_entry_list = $item_manager->getPrivateRoomHomeItemList($new_entry_array);
   } else {
      $new_entry_list = new cs_list();
   }
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


/* ROOMWIDE SEARCH */
if ($current_context->getPortletShowRoomWideSearchBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $search_view = $class_factory->getClass(PRIVATEROOM_HOME_ROOMWIDE_SEARCH_VIEW,$params);
   unset($params);
   $portlet_array[] = $search_view;
}
/* ROOMWIDE SEARCH END */

/* WEATHER */
if ($current_context->getPortletShowWeatherBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $weather_view = $class_factory->getClass(PRIVATEROOM_HOME_WEATHER_VIEW,$params);
   $weather_view->setLocation($current_context->getPortletWeatherLocation());
   unset($params);
   $portlet_array[] = $weather_view;
}
/* WEATHER END */

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
   //$youtube_view->setChannelID('zdf');
   $youtube_view->setChannelID($current_context->getPortletYouTubeAccount());
   unset($params);
   $portlet_array[] = $youtube_view;
}
/* END YOUTUBE */

/* NEW_ITEM */
if ($current_context->getPortletShowNewItemBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $search_view = $class_factory->getClass(PRIVATEROOM_HOME_NEW_ITEM_VIEW,$params);
   unset($params);
   $portlet_array[] = $search_view;
}
/* NEW_ITEM END */

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

/* NOTE */
if ($current_context->getPortletShowNoteBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $note_view = $class_factory->getClass(PRIVATEROOM_HOME_NOTE_VIEW,$params);
   unset($params);
   $portlet_array[] = $note_view;
}
/* NOTE END */

/* RELEASED_ENTRIES */
if ($current_context->getPortletShowReleasedEntriesBox()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $released_entries_view = $class_factory->getClass(PRIVATEROOM_HOME_RELEASED_ENTRIES_VIEW,$params);
   unset($params);
   $portlet_array[] = $released_entries_view;
}
/* RELEASED_ENTRIES END */

/* TAG */
if ($current_context->getPortletShowTagBox() and  $current_context->withTags()){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $tag_view = $class_factory->getClass(PRIVATEROOM_HOME_TAG_VIEW,$params);
   unset($params);
   $portlet_array[] = $tag_view;
}
/* TAG END */

$portlet_view->setPortletViewArray($portlet_array);



include_once('classes/cs_list.php');

$portlet_view->setColumnCount($current_context->getPortletColumnCount());
$page->add($portlet_view);