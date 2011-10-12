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
include_once('functions/misc_functions.php');

$search_text = $_GET['searchtext'];

function compareWithSearchText($search_text, $value) {
	if(mb_stristr($value, $search_text)) {
		$marker_color = getMarkerColor();
		if($marker_color === 'green') {
			$replace = '(:mainsearch_text_green:)$0(:mainsearch_text_green_end:)';
		} else if($marker_color === 'yellow') {
			$replace = '(:mainsearch_text_yellow:)$0(:mainsearch_text_yellow_end:)';
		}
		
		$value = preg_replace('~' . preg_quote($search_text, '/') . '~iu', $replace, $value);
	}
	
	return $value;
}

if(isset($_GET['do'])){
	if($_GET['do'] == 'search') {
		$room_item = $environment->getCurrentContextItem();
		$user_item = $environment->getCurrentUserItem();
		$db = $environment->getDBConnector();
		
		// determe where to search
		$search_rubric = array();
		if(isset($_GET['selrubric']) && !empty($_GET['selrubric'])) {
			$search_rubric[] = $_GET['selrubric'];
		} else {
			$current_room_modules = $room_item->getHomeConf();
			$room_modules = array();
			if(!empty($current_room_modules)) {
				$room_modules = explode(',', $current_room_modules);
			}
			foreach($room_modules as $module) {
				$link_name = explode('_', $module);
				if($link_name[1] !== 'none') {
					$search_rubric[] = $link_name[0];
				}
			}
			
		}
		
		// convert search_rubric to item type
		$item_type = array();
		foreach($search_rubric as $value) {
			switch($value) {
				case "institution":
				case "group":
				case "topic":
				case "buzzword":
					$item_type[] = 'label';
					break;
				default:
					$item_type[] = $value;
			}
		}
		
		// encode for db
		foreach($item_type as $key => $value) {
			$item_type[$key] = encode(AS_DB, $value);
		}
		
		/*
		 * find results - room item gives context
		 * 
		 * limit the amount of words to search for
		 */
		$search_words_limit = 2;
		
		$search_words = explode(' ', $search_text);
		$search_words_num = ($search_words_limit > count($search_words) ? count($search_words) : $search_words_limit);
		$search_words = array_slice($search_words, 0, $search_words_num);
		
		$query = '
			SELECT
				items.*,
				index_0.si_count,
				index_0.si_item_type AS si_item_type,
				word_0.sw_word AS complete
			FROM
				search_index AS index_0
		';
		for($i=1; $i < $search_words_num; $i++) {
			$query .= '
				LEFT JOIN
					search_index AS index_' . $i . '
				ON
					index_0.si_item_id = index_' . $i . '.si_item_id
			';
		}
		for($i=0; $i < $search_words_num; $i++) {
			$query .= '
				LEFT JOIN
					search_word AS word_' . $i . '
				ON
					index_' . $i . '.si_sw_id = word_' . $i . '.sw_id
			';
		}
		$query .= '
			LEFT JOIN
				items
			ON
				items.item_id = index_0.si_item_id
			WHERE
				items.context_id = "' . encode(AS_DB, $room_item->getItemID()) . '" AND
		';
		for($i=0; $i < $search_words_num; $i++) {
			$query .= '
				word_' . $i . '.sw_word LIKE "%' . encode(AS_DB, $search_words[$i]) . '%" AND
			';
		}
		$query .= '
				index_0.si_count IS NOT NULL AND
				items.type IN ("' . implode('", "', $item_type) . '")
			GROUP BY
				items.item_id
			ORDER BY
				index_0.si_count DESC,
				items.modification_date
		';
		
		$results = $db->performQuery($query);
		
		$params = array();
		$params['environment'] = $environment;
		$view = $class_factory->getClass(INDEX_VIEW,$params);
		$json_return = array();
		foreach($results as $result) {
			$rubric_manager = $environment->getManager($result['si_item_type']);
			$item = $rubric_manager->getItem($result['item_id']);			// <- this may be null, if item is deleted
			
			if($item) {
				$json_return[] = array(	'title'				=> $item->getTitle(),//$view->_text_as_html_short(compareWithSearchText($search_text, $title)),
										'modification_date'	=> $item->getModificationDate(),
										'complete'			=> $result['complete'],
										'type'				=> $result['type'],
										'id'				=> $result['item_id']);
			}
		}
		
		/*
		$item = $search_results->getFirst();
		while($item) {
			$json_return[] = array(	'title'			=> $view->_text_as_html_short(compareWithSearchText($search_text, $item->getTitle())),
									'status'		=> '123',
									'type'			=> $item->getItemType(),
									'id'			=> $item->getItemID());
			
			$item = $search_results->getNext();
		}
		*/
		
		
//$page->add('roomwide_search_info', array('page' => $result_page, 'last' => $number_of_pages, 'from' => $from_display, 'to' => $to_display, 'count' => $count));
//$page->add('roomwide_search_results', $result_array);
		
		
		echo json_encode($json_return);
		
		/*
		if(isset($_GET['interval'])){
	$interval = $_GET['interval'];
} else {
   $interval = 20;
}


$complete_list = new cs_list();
foreach($file_rubric_array as $file_rubric){
	$rubric_manager = $environment->getManager($file_rubric);
   $rubric_manager->setContextArrayLimit($context_array);
   if ($file_rubric == CS_DATE_TYPE) {
      $rubric_manager->setWithoutDateModeLimit();
   }
   if(!empty($_GET['search'])){
      $rubric_manager->setSearchLimit($_GET['search']);
   }
   $rubric_manager->showNoNotActivatedEntries();
	$rubric_manager->select();
   $item_list = $rubric_manager->get();
   $complete_list->addList($item_list);
}







$complete_list->sortby('modification_date');
$complete_list->reverse();

$count = $complete_list->getCount();

$number_of_pages = 0;
if($count % $interval == 0){
   $number_of_pages = ($count / $interval)-1;
} else {
   $number_of_pages = (($count - ($count % $interval)) / $interval);
}
if($number_of_pages == -1){
	$number_of_pages++;
}

$result_page = $_GET['page'];
if($result_page > $number_of_pages){
   $result_page = $number_of_pages;
} else if($result_page == -1){
	$result_page = 1;
}

if($count > $interval){
	$from = $interval * $result_page;
	if(($from + ($interval-1)) <= $count){
		$to = $from + ($interval-1);
		$to_display = $to+1;
	} else {
	   $to = $count;
	   $to_display = $to;
	}
} else {
	$from = 0;
	$to = $count;
	$to_display = $count;
}

if($to > 0){
   $from_display = $from+1;
} else {
	$from_display = 0;
}

*/
	}
}
exit;
?>