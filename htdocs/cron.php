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

/*
function performRoomIDArray ($id_array,$portal_name) {
   global $environment;
   $room_manager = $environment->getRoomManager();
   $room_manager->setCacheOut();
   foreach ($id_array as $item_id) {
      $room = $room_manager->getItem($item_id);
      $type = '';
      if ($room->isCommunityRoom()) {
         $type = 'Community';
         $title = $room->getTitle();
      } elseif ($room->isProjectRoom()) {
         $type = 'Project';
         $title = $room->getTitle();
      } elseif ($room->isGroupRoom()) {
         $type = 'Group';
         $title = $room->getTitle();
      } elseif ($room->isPrivateRoom()) {
         $type = 'Private';
         $user = $room->getOwnerUserItem();
         if (isset($user) and $user->isUser()){
            $title = getMessage('COMMON_PRIVATE_ROOM').': '.$user->getFullName();
         } else {
            $title = getMessage('COMMON_PRIVATE_ROOM').': '.$room->getItemID();
         }
         unset($user);
      }
      echo('<h4>'.$title.' - '.$type.' - '.$portal_name.'<h4>'.LF);
      $array = $room->runCron();
      $html = '';
      foreach ($array as $cron_status => $crons) {
         $html .= '<table border="0" summary="Layout">'.LF;
         $html .= '<tr>'.LF;
         $html .= '<td style="vertical-align:top; width: 4em;">'.LF;
         $html .= '<span style="font-weight: bold;">'.$cron_status.'</span>'.LF;
         $html .= '</td>'.LF;
         $html .= '<td>'.LF;
         if ( !empty($crons) ) {
            foreach ($crons as $cron) {
               $html .= '<div>'.LF;
               $html .= '<span style="font-weight: bold;">'.$cron['title'].'</span>'.BRLF;
               if (!empty($cron['description'])) {
                  $html .= $cron['description'];
                  if ($cron['success']) {
                     $html .= ' [<font color="#00ff00">done</font>]'.BRLF;
                  } else {
                     $html .= ' [<font color="#ff0000>failed</font>]'.BRLF;
                  }
               }
               if ( !empty($cron['success_text']) ) {
                  $html .= $cron['success_text'].BRLF;
               }
               $html .= '</div>'.LF;
            }
         } else {
            $html .= 'no crons defined';
         }
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
         $html .= '</table>'.LF;
      }
      echo($html.BRLF);
      unset($room);
   }
   unset($room_manager);
}
*/

set_time_limit(0);

// pretend, we work from the CommSy basedir to allow
// giving include files without "../" prefix all the time.
chdir('..');

// start of execution time
include_once('functions/misc_functions.php');
$time_start = getmicrotime();

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

/*
$portal_id_array = $server_item->getPortalIDArray();
unset($server_item);

$portal_manager = $environment->getPortalManager();
$room_manager = $environment->getRoomManager();
foreach ( $portal_id_array as $portal_id ) {
   if ( !isset($context_id)
        or $context_id == $portal_id
      ) {

      // portal
      $portal = $portal_manager->getItem($portal_id);
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

      // community rooms
      performRoomIDArray($portal->getCommunityIDArray(),$portal->getTitle());

      // project rooms
      performRoomIDArray($portal->getProjectIDArray(),$portal->getTitle());

      // group rooms
      performRoomIDArray($portal->getGroupIDArray(),$portal->getTitle());

      // private rooms
      performRoomIDArray($portal->getPrivateIDArray(),$portal->getTitle());

      // unset
      unset($portal);
   }
}
*/

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

$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo('<hr/>'.LF);
echo('<h1>CRON END</h1>'.LF);
echo('Total execution time: '.$time.' seconds'.BRLF);
echo('Peak of memory allocated: '.memory_get_peak_usage().BRLF);
echo('Current of memory allocated: '.memory_get_usage().BRLF);
?>