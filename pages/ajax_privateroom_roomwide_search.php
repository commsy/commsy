<?php
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

include_once('functions/development_functions.php');

debugToFile('roomwide search');
debugToFile($_GET['search']);

$privateroom_item = $environment->getCurrentContextItem();

$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel1', 'type' => 'material', 'iid' => '559', 'cid' => '554', 'hover' => 'title (Material / Raum2)');

$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel2', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');

$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');
$dummy_array[] = array('title' => 'titel3', 'type' => 'material', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Material / Raum2)');

$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel4', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');

$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');
$dummy_array[] = array('title' => 'titel5', 'type' => 'date', 'iid' => '560', 'cid' => '554', 'hover' => 'title (Termin / Raum2)');

if($_GET['interval'] == 0){
	for ($index = 0; $index < 20; $index++) {
		$info_array = array('interval' => '0', 'last' => '2', 'from' => '0', 'to' => '20', 'count' => '50');
		$result_array[] = $dummy_array[$index];
	}
} elseif($_GET['interval'] == 1){
   for ($index = 20; $index < 40; $index++) {
   	$info_array = array('interval' => '1', 'last' => '2', 'from' => '21', 'to' => '40', 'count' => '50');
      $result_array[] = $dummy_array[$index];
   }
} elseif($_GET['interval'] == 2){
   for ($index = 40; $index < 49; $index++) {
   	$info_array = array('interval' => '2', 'last' => '2', 'from' => '41', 'to' => '50', 'count' => '50');
      $result_array[] = $dummy_array[$index];
   }
} 

$page->add('roomwide_search_info', $info_array);
$page->add('roomwide_search_results', $result_array);
?>