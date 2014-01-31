<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos√© Manuel Gonz√°lez V√°zquez
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

//mb_internal_encoding('UTF-8');

global $environment;

function performRoomIDArray ($id_array,$portal_name,$privatrooms = false) {
   global $environment;
   if ( $privatrooms ) {
      $room_manager = $environment->getPrivateRoomManager();
   } else {
      $room_manager = $environment->getRoomManager();
   }
   $room_manager->setCacheOff();
   foreach ($id_array as $item_id) {
      $room = $room_manager->getItem($item_id);
      $type = '';
      $active = true;
      if ($room->isCommunityRoom()) {
         $type = 'Community';
         $title = $environment->getTextConverter()->text_as_html_short($room->getTitle());
         if ( $room->isOpen() ) {
            $active = $room->isActiveDuringLast99Days();
         } else {
            $active = false;
         }
      } elseif ($room->isProjectRoom()) {
         $type = 'Project';
         $title = $environment->getTextConverter()->text_as_html_short($room->getTitle());
         if ( $room->isOpen() ) {
            $active = $room->isActiveDuringLast99Days();
         } else {
            $active = false;
         }
      } elseif ($room->isGroupRoom()) {
         $type = 'Group';
         $title = $environment->getTextConverter()->text_as_html_short($room->getTitle());
         if ( $room->isOpen() ) {
            $active = $room->isActiveDuringLast99Days();
         } else {
            $active = false;
         }
      } elseif ($room->isPrivateRoom()) {
         $type = 'Private';
         $user = $room->getOwnerUserItem();
         if (isset($user) and $user->isUser()){
            $title = getMessage('COMMON_PRIVATE_ROOM').': '.$environment->getTextConverter()->text_as_html_short($user->getFullName()).' ('.$room->getItemID().')';
            $portal_user_item = $user->getRelatedCommSyUserItem();
            if ( isset($portal_user_item) and $portal_user_item->isUser() ) {
               $active = $portal_user_item->isActiveDuringLast99Days();
            } else {
               $active = false;
            }
            unset($portal_user_item);
         } else {
            $title = getMessage('COMMON_PRIVATE_ROOM').': '.$room->getItemID();
            $active = false;
         }
         unset($user);
      }
      echo('<h4>'.$title.' - '.$type.' - '.$environment->getTextConverter()->text_as_html_short($portal_name).'<h4>'.LF);
      if ( $active ) {
         displayCronResults($room->runCron());
      } else {
         echo('not active'.BRLF);
      }
      unset($room);
   }
   unset($room_manager);
}

function displayCronResults ( $array ) {
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
            if ( !empty($cron['time']) ) {
               $time = $cron['time'];
               if ( $time < 60 ) {
                  $time_text = 'Total execution time: '.$time.' seconds';
               } elseif ( $time < 3600 ) {
                  $time2 = floor($time / 60);
                  $sec2 = $time % 60;
                  $time_text = 'Total execution time: '.$time2.' minutes '.$sec2.' seconds';
               } else {
                  $hour = floor($time / 3600);
                  $sec = $time % 3660;
                  if ( $sec > 60 ) {
                     $minutes = floor($sec / 60);
                     $sec = $sec % 60;
                  }
                  $time_text = 'Total execution time: '.$hour.' hours '.$minutes.' minutes '.$sec.' seconds';
               }
               $html .= $time_text.BRLF;
            } elseif ( isset($cron['time']) ) {
               $time_text = 'Total execution time: 0 seconds';
               $html .= $time_text.BRLF;
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
   flush();
}

set_time_limit(0);
header("Content-Type: text/html; charset=utf-8");

$memory_limit2 = 640 * 1024 * 1024;
$memory_limit = ini_get('memory_limit');
if ( !empty($memory_limit) ) {
   if ( strstr($memory_limit,'M') ) {
      $memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
      $memory_limit = $memory_limit * 1024 * 1024;
   } elseif ( strstr($memory_limit,'K') ) {
      $memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
      $memory_limit = $memory_limit * 1024;
   }
}
if ( $memory_limit < $memory_limit2 ) {
   ini_set('memory_limit',$memory_limit2);
   $memory_limit3 = ini_get('memory_limit');
   if ( $memory_limit3 != $memory_limit2 ) {
      echo('Waring: Can not set memory limit. Script may stop. Please try 640M in your php.ini.');
   }
}

// start of execution time
include_once('functions/misc_functions.php');
$time_start = getmicrotime();
$start_time = date('d.m.Y H:i:s');

// setup commsy-environment
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');
include_once('classes/cs_environment.php');
$environment = new cs_environment();
$environment->setCacheOff();
$result_array = array();
if ( !empty($_GET['cid']) ) {
   $context_id = $_GET['cid'];
}

echo('<h1>CommSy Cron Jobs</h1>'.LF);

// server
$server_item = $environment->getServerItem();
// server cron job must be run AFTER all other portral crons

// portals and rooms
$result_array['portal'] = array();
$portal_id_array = $server_item->getPortalIDArray();

$portal_manager = $environment->getPortalManager();
$room_manager = $environment->getRoomManager();
foreach ( $portal_id_array as $portal_id ) {
   if ( !isset($context_id)
        or $context_id == $portal_id
      ) {

      // portal
      $portal = $portal_manager->getItem($portal_id);
      echo('<h4>'.$environment->getTextConverter()->text_as_html_short($portal->getTitle()).' - Portal<h4>'.LF);
      displayCronResults($portal->runCron());
      echo('<hr/>'.LF);

      // private rooms
      echo('<h4>Private Rooms</h4>'.LF);
      performRoomIDArray($portal->getPrivateIDArray(),$portal->getTitle(),true);
      echo('<hr/>'.LF);

      // community rooms
      echo('<h4>Community Rooms</h4>'.LF);
      performRoomIDArray($portal->getCommunityIDArray(),$portal->getTitle());
      echo('<hr/>'.LF);

      // project rooms
      echo('<h4>Project Rooms</h4>'.LF);
      performRoomIDArray($portal->getProjectIDArray(),$portal->getTitle());
      echo('<hr/>'.LF);

      // group rooms
      echo('<h4>Group Rooms</h4>'.LF);
      performRoomIDArray($portal->getGroupIDArray(),$portal->getTitle());
      echo('<hr/>'.LF);

      // unset
      unset($portal);
   }
}

// server cron jobs must be run AFTER all other portal crons
if ( !isset($context_id)
     or ($context_id == $environment->getServerID())
   ) {
   echo('<h4>'.$environment->getTextConverter()->text_as_html_short($server_item->getTitle()).' - Server<h4>'.LF);
   displayCronResults($server_item->runCron());
   echo('<hr/>'.BRLF);
}
unset($server_item);

$time_end = getmicrotime();
$end_time = date('d.m.Y H:i:s');
$time = round($time_end - $time_start,0);
echo('<hr/>'.LF);
echo('<h1>CRON END</h1>'.LF);
echo('<h2>Time</h2>'.LF);
echo('Start: '.$start_time.BRLF);
echo('End: '.$end_time.BRLF);
if ( $time < 60 ) {
   echo('Total execution time: '.$time.' seconds'.LF);
} elseif ( $time < 3600 ) {
   $time2 = floor($time / 60);
   $sec2 = $time % 60;
   echo('Total execution time: '.$time2.' minutes '.$sec2.' seconds'.LF);
} else {
   $hour = floor($time / 3600);
   $sec = $time % 3660;
   if ( $sec > 60 ) {
      $minutes = floor($sec / 60);
      $sec = $sec % 60;
   }
   echo('Total execution time: '.$hour.' hours '.$minutes.' minutes '.$sec.' seconds'.LF);
}
echo('<h2>Memory</h2>'.LF);
echo('Peak of memory allocated: '.memory_get_peak_usage().BRLF);
echo('Current of memory allocated: '.memory_get_usage().BRLF);
?>