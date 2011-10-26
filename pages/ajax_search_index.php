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

ini_set('max_execution_time', 0);

/*
 * VERY IMPORTANT NOTE:
 * 	- it is absolutly necessary, that annotations are processed first
 * 	- in general: all subitems of an item(f.e. sections are subitems of materials), need to be processed before the main item
 * 
 * Subitems will take their item type from query by joining the items table.
 * When building indicies for such items, the search time is not written, so that the main item is able to index too and end indexing himself by writing the search time.
 */

$db = $environment->getDBConnector();

$managers = array();
$query = array();
// create indizes for
//	- annotations
$managers[] = $environment->getAnnotationManager();
$query[] = 'SELECT COUNT(*) as count FROM annotations LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- announcements
$managers[] = $environment->getAnnouncementManager();
$query[] = 'SELECT COUNT(*) as count FROM announcement LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- section
$managers[] = $environment->getSectionManager();
$query[] = 'SELECT COUNT(*) as count FROM section LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- materials
$managers[] = $environment->getMaterialManager();
$query[] = 'SELECT COUNT(*) as count FROM materials LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- institutions
$managers[] = $environment->getInstitutionManager();
$query[] = 'SELECT COUNT(*) as count FROM labels LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL AND type = "institution"';

//	- topics
$managers[] = $environment->getTopicManager();
$query[] = 'SELECT COUNT(*) as count FROM labels LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL AND type = "topic"';

//	- user
$managers[] = $environment->getUserManager();
$query[] = 'SELECT COUNT(*) as count FROM user LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- todos
$managers[] = $environment->getTodoManager();
$query[] = 'SELECT COUNT(*) as count FROM todos LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- step
$managers[] = $environment->getStepManager();
$query[] = 'SELECT COUNT(*) as count FROM step LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- date
$managers[] = $environment->getDateManager();
$query[] = 'SELECT COUNT(*) as count FROM dates LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- discussion
$managers[] = $environment->getDiscussionManager();
$query[] = 'SELECT COUNT(*) as count FROM discussions LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- group
$managers[] = $environment->getGroupManager();
$query[] = 'SELECT COUNT(*) as count FROM labels LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL AND type = "group"';

//	- discussionarticle
$managers[] = $environment->getDiscussionArticleManager();
$query[] = 'SELECT COUNT(*) as count FROM discussionarticles LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- task
$managers[] = $environment->getTaskManager();
$query[] = 'SELECT COUNT(*) as count FROM tasks LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- buzzword
$managers[] = $environment->getBuzzwordManager();
$query[] = 'SELECT COUNT(*) as count FROM discussions LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- tag
$managers[] = $environment->getTagManager();
$query[] = 'SELECT COUNT(*) as count FROM tag LEFT JOIN search_time ON item_id = search_time.st_item_id WHERE search_time.st_id IS NULL';

//	- community
//$community_manager = $environment->getCommunityManager();

//	- project
//$project_manager = $environment->getProjectManager();

//	- grouproom
$managers[] = $environment->getGroupRoomManager();
$query[] = 'SELECT COUNT(*) as count FROM room WHERE type = "grouproom"';

//	- privateroom - SKIPPED

//	- portal
//$managers[] = $environment->getPortalManager();

//	- server
//$managers[] = $environment->getServerManager();

if(isset($_GET['do'])){
	if($_GET['do'] == 'getNumManagers') {
		$return['number'] = sizeof($managers);
	} elseif($_GET['do'] == 'truncate') {
		// truncate tables
		$sql = "TRUNCATE `search_index`;";
		$db->performQuery($sql);
		$sql = "TRUNCATE `search_time`;";
		$db->performQuery($sql);
		$sql = "TRUNCATE `search_word`;";
		$db->performQuery($sql);
		
		$return['status'] = 'done';
	} elseif($_GET['do'] == 'index') {
		if(isset($_GET['manager']) && $_GET['manager'] >= 0 && $_GET['manager'] < sizeof($managers)) {
			$managers[$_GET['manager']]->updateSearchIndices(array($_GET['offset'], $_GET['limit']));
			$return['status'] = 'done';
			$return['processed'] = $_GET['offset'] + $_GET['limit'];
		}
	} elseif($_GET['do'] == "getNumItems") {
		if(isset($_GET['manager']) && $_GET['manager'] >= 0 && $_GET['manager'] < sizeof($managers)) {
			$result = $db->performQuery($query[$_GET['manager']]);
			if(sizeof($result) == 0) {
				$return['number'] = 0;
			} else {
				$return['number'] = $result[0]['count'];
			}
		}
	}
	
	echo json_encode($return);
}
exit;
?>