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

if(isset($_GET['do'])){
	if($_GET['do'] == 'vote') {
		$item_id = $_GET['item_id'];
		$vote = $_GET['vote'];
		
		$assessment_manager = $environment->getAssessmentManager();
		$item_manager = $environment->getItemManager();
		$item = $item_manager->getItem($item_id);
		
		// check if user has already votet
		$voted = $assessment_manager->hasCurrentUserAlreadyVoted($item);
		if(!$voted) {
			$assessment_manager->addAssessmentForItem($item, $vote);
		}
		
		unset($assessment_manager);
	} else if($_GET['do'] == 'delete_own') {
		$item_link_id = $_GET['item_id'];
		
		$assessment_manager = $environment->getAssessmentManager();
		$item_id = $assessment_manager->getItemIDForOwn($item_link_id);
		$assessment_manager->delete($item_id);
	}
}
?>