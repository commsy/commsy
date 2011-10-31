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

function getTagArray($root, $sublist) {
	// prepare return
	$return = array();
	$return['children'] = array();
	
	// add information for root tag
	$return['title'] = $root->getTitle();
	$return['id'] = $root->getItemID();
	
	// add children
	if(isset($sublist) && !empty($sublist)) {
		$child = $sublist->getFirst();
		while($child) {
			$return['children'][] = getTagArray($child, $child->getChildrenList());
			
			$child = $sublist->getNext();
		}
	}
	
	return $return;
}

if(isset($_GET['do'])){
	if($_GET['do'] == 'search') {
		$room_item = $environment->getCurrentContextItem();
		$user_item = $environment->getCurrentUserItem();
		$db = $environment->getDBConnector();
		$ftsearch_manager = $environment->getFTSearchManager();
		$tag_manager = $environment->getTagManager();
		
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
		
        // FTSearchManager
        $ftsearch_manager->setSearchStatus(true);
        $ftsearch_manager->setWords($search_words);
        $ft_result = $ftsearch_manager->performFTSearch();
		
		$params = array();
		$params['environment'] = $environment;
		$view = $class_factory->getClass(INDEX_VIEW,$params);
		$json_return = array();
		$json_return['results'] = array();
		foreach($results as $result) {
			$rubric_manager = $environment->getManager($result['si_item_type']);
			$item = $rubric_manager->getItem($result['item_id']);			// <- this may be null, if item is deleted
			
			if($item) {
				$title = '';
				if($result['si_item_type'] == 'user') {
					$title = $item->getFullName();
				} else {
					$title = $item->getTitle();
				}
				
				// linked tags
				$tag_list = $item->getTagList();
				$tag_array = array();
				$tag = $tag_list->getFirst();
				while($tag) {
					$tag_array[] = $tag->getItemID();
					
					$tag = $tag_list->getNext();
				}
				
				// file list
				$file_list = $item->getFileList();
				$file_array = array();
				$file = $file_list->getFirst();
				while($file) {
					$file_array[] = array(	'icon'		=> $file->getIconURL());
					
					$file = $file_list->getNext();
				}
        		
				$json_return['results'][$result['item_id']] = array(	'title'				=> $title,//$view->_text_as_html_short(compareWithSearchText($search_text, $title)),
																		'modification_date'	=> $item->getModificationDate(),
																		'complete'			=> $result['complete'],
																		//'type'				=> $result['type'],
																		'type'				=> $result['si_item_type'],
																		'file_list'			=> $file_array,
																		'tags'				=> $tag_array);
			}
		}
		
		// go through each FTSearch result
		$ft_new = array();
		foreach($ft_result as $material_id) {
			// check if the referenced material already exists
			if(isset($json_return['results'][$material_id])) {
				// append FTSearch results to array
				$json_return['results'][$material_id]['file_search'] = true;
			} else {
				// create new search result
				$ft_new[$material_id] = array();
			}
		}
		
		// collect item information for new search results - added by FTSearch
		if(!empty($ft_new)) {
			foreach($ft_new as $material_id => $value) {
				$rubric_manager = $environment->getMaterialManager();
				$item = $rubric_manager->getItem($material_id);
				
				if($item) {
					// linked tags
					$tag_list = $item->getTagList();
					$tag_array = array();
					$tag = $tag_list->getFirst();
					while($tag) {
						$tag_array[] = $tag->getItemID();
						
						$tag = $tag_list->getNext();
					}
					
					$json_return['results'][$material_id] = array(	'title'				=> $item->getTitle(),//$view->_text_as_html_short(compareWithSearchText($search_text, $title)),
																	'modification_date'	=> $item->getModificationDate(),
																	'complete'			=> '',
																	//'type'				=> $result['type'],
																	'type'				=> 'material',
																	'tags'				=> $tag_array);
				}
			}
		}
		
		// append categories
		$root = $tag_manager->getRootTagItem();
		$json_return['categories'] = getTagArray($root, $root->getChildrenList());
		
		echo json_encode($json_return);
	}
}
exit;
?>