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

set_time_limit(0);

// pretend, we work from the CommSy basedir to allow
// giving include files without "../" prefix all the time.
chdir('..');

// setup commsy-environment
include_once('classes/cs_cron_view.php');
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');
include_once('classes/cs_environment.php');
$environment = new cs_environment();
$result_array = array();
if ( !empty($_GET['cid']) ) {
   $context_id = $_GET['cid'];
}

// server
// cron for server are:
// - handle page impressions
// - handle activity
// - handle logs
$server_item = $environment->getServerItem();
if ( !isset($context_id)
     or ($context_id == $environment->getServerID())
   ) {
   $temp_array = array();
   $temp_array['crons'] = $server_item->runCron();
   $temp_array['title'] = $server_item->getTitle();
   $result_array['server'][0] = $temp_array;
   unset($temp_array);
}

if ( !empty($result_array) ) {
   $view = new cs_cron_view();
   $view->setCronResult($result_array);
   echo($view->asHTML());
   flush();
   unset($view);
   unset($result_array);
   $result_array = array();
}

// portals and rooms
$result_array['portal'] = array();
$portal_list = $server_item->getPortalList();
unset($server_item);
$portal = $portal_list->getFirst();
while ($portal) {

   if ( !isset($context_id)
        or ($context_id == $portal->getItemID())
      ) {

      $temp_array['crons'] = $portal->runCron();
      $temp_array['title'] = $portal->getTitle();
      $result_array['portal'][] = $temp_array;
      unset($temp_array);

      if ( !empty($result_array) ) {
         $view = new cs_cron_view();
         $view->setCronResult($result_array);
         echo($view->asHTML());
         flush();
         unset($view);
         unset($result_array);
         $result_array = array();
      }

      $room_list = $portal->getRoomList();
      if (isset($room_list) and $room_list->isNotEmpty()) {
         $room = $room_list->getFirst();
         while ($room) {
            $temp_array['crons'] = $room->runCron();
            $temp_array['title'] = $room->getTitle();
            $result_array['room'][] = $temp_array;
            unset($temp_array);
            unset($room);
            $room = $room_list->getNext();
         }
         unset($room_list);

         // display results
         if ( !empty($result_array) ) {
            $view = new cs_cron_view();
            $view->setCronResult($result_array);
            echo($view->asHTML());
            flush();
            unset($view);
            $result_array = array();
         }
      }

      // crons for private rooms are:
      // - personal newsletter
      $room_list = $portal->getPrivateRoomList();
      if (isset($room_list) and $room_list->isNotEmpty()) {
         $room = $room_list->getFirst();
         while ($room) {
            $temp_array['crons'] = $room->runCron();
            $user = $room->getOwnerUserItem();
            if (isset($user) and $user->isUser()){
               $temp_array['title'] = getMessage('COMMON_PRIVATE_ROOM').': '.$user->getFullName().': '.$portal->getTitle();
            } else {
               $temp_array['title'] = getMessage('COMMON_PRIVATE_ROOM').': '.$room->getItemID().': '.$portal->getTitle();
            }
            unset($user);
            $result_array['room'][] = $temp_array;
            unset($temp_array);

            unset($room);
            $room = $room_list->getNext();
         }
         unset($room_list);

         // display results
         if ( !empty($result_array) ) {
            $view = new cs_cron_view();
            $view->setCronResult($result_array);
            echo($view->asHTML());
            flush();
            unset($view);
            $result_array = array();
         }
      }
   }

   unset($portal);
   $portal = $portal_list->getNext();
}
unset($portal_list);
?>