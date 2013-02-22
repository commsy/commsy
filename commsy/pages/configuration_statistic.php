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
$current_user = $environment->getCurrentUserItem();
if (!$current_user->isRoot()) {
   redirect($environment->getCurrentContextID(),'home','index','');
} else {
   set_time_limit(0); // so script can run more than 30 seconds

   // initialize day1
   if ( isset($_POST['day1']) ) {
      $day1 = $_POST['day1'];
   } elseif ( isset($_GET['day1']) ) {
      $day1 = $_GET['day1'];
   } else {
      $day1 = date('d');
   }
   if ( strlen($day1) == 1 ) {
      $day1 = '0'.$day1;
   }

   // initialize month1
   if ( isset($_POST['month1']) ) {
      $month1 = $_POST['month1'];
   } elseif ( isset($_GET['month1']) ) {
      $month1 = $_GET['month1'];
   } else {
      $month1 = date('m');
   }
   if ( strlen($month1) == 1 ) {
      $month1 = '0'.$month1;
   }

   // initialize year1
   if ( isset($_POST['year1']) ) {
      $year1 = $_POST['year1'];
   } elseif ( isset($_GET['year1']) ) {
      $year1 = $_GET['year1'];
   } else {
      $year1 = date('Y');
   }

   // initialize day2
   if ( isset($_POST['day2']) ) {
      $day2 = $_POST['day2'];
   } elseif ( isset($_GET['day2']) ) {
      $day2 = $_GET['day2'];
   } else {
      $day2 = date('d');
   }
   if ( strlen($day2) == 1 ) {
      $day2 = '0'.$day2;
   }

   // initialize month2
   if ( isset($_POST['month2']) ) {
      $month2 = $_POST['month2'];
   } elseif ( isset($_GET['month2']) ) {
      $month2 = $_GET['month2'];
   } else {
      $month2 = date('m');
   }
   if ( strlen($month2) == 1 ) {
      $month2 = '0'.$month2;
   }

   // initialize year2
   if ( isset($_POST['year2']) ) {
      $year2 = $_POST['year2'];
   } elseif ( isset($_GET['year2']) ) {
      $year2 = $_GET['year2'];
   } else {
      $year2 = date('Y');
   }

   // initialize room status
   if ( isset($_POST['room_status']) ) {
      $room_status = $_POST['room_status'];
   } elseif ( isset($_GET['room_status']) ) {
      $room_status = $_GET['room_status'];
   } else {
      $room_status = 'none';
   }

   $start_date = $year1.'-'.$month1.'-'.$day1.' 00:00:00';
   $end_date =   $year2.'-'.$month2.'-'.$day2.' 23:59:59';

   // get all CommSys (portals)
   $current_context_item = $environment->getServerItem();
   $room_list = $current_context_item->getPortalList();

   // Prepare view object
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $statistic_view = $class_factory->getClass(STATISTIC_VIEW,$params);
   unset($params);

   // Set data for view
   $statistic_view->setList($room_list);
   $statistic_view->setDay1($day1);
   $statistic_view->setMonth1($month1);
   $statistic_view->setYear1($year1);
   $statistic_view->setDay2($day2);
   $statistic_view->setMonth2($month2);
   $statistic_view->setYear2($year2);
   $statistic_view->setRoomStatus($room_status);
   $statistic_view->setInterval($start_date,$end_date);

   // Add list view to page and display page
   if ( ($environment->inPortal() or $environment->inServer()) and !(isset($_GET['mode']) and $_GET['mode']=='print') ){
      $page->addForm($statistic_view);
   } else {
      $page->add($statistic_view);
   }
}
?>