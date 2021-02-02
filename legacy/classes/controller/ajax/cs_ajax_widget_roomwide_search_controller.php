<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_widget_roomwide_search_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetSearchFilter()
		{
			$return = array(
				"rooms"		=> array(),
				"rubrics"	=> array()
			);
			
			$return["rubrics"][] = array_merge($this->getUtils()->getLogoInformationForType(CS_MATERIAL_TYPE), array("type" => CS_MATERIAL_TYPE, "active" => true));
			$return["rubrics"][] = array_merge($this->getUtils()->getLogoInformationForType(CS_ANNOUNCEMENT_TYPE), array("type" => CS_ANNOUNCEMENT_TYPE, "active" => true));
			$return["rubrics"][] = array_merge($this->getUtils()->getLogoInformationForType(CS_TOPIC_TYPE), array("type" => CS_TOPIC_TYPE, "active" => true));
			$return["rubrics"][] = array_merge($this->getUtils()->getLogoInformationForType(CS_DISCUSSION_TYPE), array("type" => CS_DISCUSSION_TYPE, "active" => true));
			$return["rubrics"][] = array_merge($this->getUtils()->getLogoInformationForType(CS_DATE_TYPE), array("type" => CS_DATE_TYPE, "active" => true));
			$return["rubrics"][] = array_merge($this->getUtils()->getLogoInformationForType(CS_TODO_TYPE), array("type" => CS_TODO_TYPE, "active" => true));
			
			$currentUserItem = $this->_environment->getCurrentUserItem();
			$privateRoomItem = $currentUserItem->getOwnRoom();
				
			// collect data of interest
			$roomNameArray = array();
			
			// project and group rooms
			$projectRoomList = $currentUserItem->getUserRelatedProjectList();
			$projectRoomItem = $projectRoomList->getFirst();
			while ( $projectRoomItem )
			{
				$return["rooms"][] = array("id" => $projectRoomItem->getItemID(), "title" => $projectRoomItem->getTitle(), "active" => true);
			
				$projectRoomItem = $projectRoomList->getNext();
			}
				
			// community rooms
			$communityRoomList = $currentUserItem->getUserRelatedCommunityList();
			$communityRoomItem = $communityRoomList->getFirst();
			while ( $communityRoomItem )
			{
				$return["rooms"][] = array("id" => $communityRoomItem->getItemID(), "title" => $communityRoomItem->getTitle(), "active" => true);
			
				$communityRoomItem = $communityRoomList->getNext();
			}
				
			// private room
			//$return["rooms"][] = array("id" => $privateRoomItem->getItemID(), "title" => $privateRoomItem->getTitle(), "active" => true);
			
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}

		public function actionQuery() {
			$return = array(
				"items"		=> array()
			);

			$start = $this->_data["options"]["start"];
			$numEntries = $this->_data["options"]["numEntries"];
			$query = $this->_data["query"];
			
			if ( isset($this->_data["options"]["filter"]) )
			{
				$filter = $this->_data["options"]["filter"];
			}
			else
			{
				$filter = array();
			}
			
			$currentUserItem = $this->_environment->getCurrentUserItem();
			$privateRoomItem = $currentUserItem->getOwnRoom();
			
			// collect data of interest
			$roomIdArray = array();
			$roomNameArray = array();
			$rubricArray = array();
			
			if ( !empty($filter) )
			{
				foreach ( $filter["rooms"] as $filterRoom )
				{
					if ( $filterRoom["active"] )
					{
						$roomIdArray[] = $filterRoom["id"];
						$roomNameArray[$filterRoom["id"]] = $filterRoom["title"];
					}
				}
			}
			else
			{
				// project and group rooms
				$projectRoomList = $currentUserItem->getUserRelatedProjectList();
				$projectRoomItem = $projectRoomList->getFirst();
				while ( $projectRoomItem )
				{
					$roomIdArray[] = $projectRoomItem->getItemID();
					$roomNameArray[$projectRoomItem->getItemID()] = $projectRoomItem->getTitle();
				
					$projectRoomItem = $projectRoomList->getNext();
				}
					
				// community rooms
				$communityRoomList = $currentUserItem->getUserRelatedCommunityList();
				$communityRoomItem = $communityRoomList->getFirst();
				while ( $communityRoomItem )
				{
					$roomIdArray[] = $communityRoomItem->getItemID();
					$roomNameArray[$communityRoomItem->getItemID()] = $communityRoomItem->getTitle();
				
					$communityRoomItem = $communityRoomList->getNext();
				}
					
				// private room
				//$roomIdArray[] = $privateRoomItem->getItemID();
				//$roomNameArray[$privateRoomItem->getItemID()] = $privateRoomItem->getTitle();
			}
			
			// determe the rubrics to search in
			if ( !empty($filter) )
			{
				foreach ( $filter["rubrics"] as $filterRubric )
				{
					if ( $filterRubric["active"] && in_array($filterRubric["type"], array(CS_DISCUSSION_TYPE, CS_DATE_TYPE, CS_TODO_TYPE)) )
					{
						$rubricArray[] = $filterRubric["type"];
					}
				}
			}
			else
			{
				$rubricArray[] = CS_DISCUSSION_TYPE;
				$rubricArray[] = CS_DATE_TYPE;
				$rubricArray[] = CS_TODO_TYPE;
			}
			
			
			/*
			 * only three rubrics are handled in the following foreach loop
			 * maybe these are the only managers that are able to handle context arrays???
			 * however, setting context array limits in other managers seems not to work
			 */
			
			// get search results from all relevant managers
			$searchResults = new cs_list();
			foreach ( $rubricArray as $rubric )
			{
				$manager = $this->_environment->getManager($rubric);
				$manager->setContextArrayLimit($roomIdArray);
				
				if ( $rubric == CS_DATE_TYPE )
				{
					$manager->setWithoutDateModeLimit();
				}
				
				if ( !empty($query) )
				{
					$manager->setSearchLimit($query);
				}
				
				$manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
				$manager->select();
				$searchResults->addList($manager->get());
			}
			
			// redefine rubric array
			if ( !empty($filter) )
			{
				$rubricArray = array();
				
				foreach ( $filter["rubrics"] as $filterRubric )
				{
					if ( $filterRubric["active"] && in_array($filterRubric["type"], array(CS_MATERIAL_TYPE, CS_ANNOUNCEMENT_TYPE, CS_TOPIC_TYPE)) )
					{
						$rubricArray[] = $filterRubric["type"];
					}
				}
			}
			else
			{
				$rubricArray = array(CS_MATERIAL_TYPE, CS_ANNOUNCEMENT_TYPE, CS_TOPIC_TYPE);
			}
			
			// materials
			foreach ( $roomIdArray as $roomId )
			{
				foreach ( $rubricArray as $rubric )
				{
					$rubricManager = $this->_environment->getManager($rubric);
					$rubricManager->setContextLimit($roomId);
					
					if ( !empty($query) )
					{
						$rubricManager->setSearchLimit($query);
					}
					
					$rubricManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
					$rubricManager->select();
					$searchResults->addList($rubricManager->get());
				}
				
			}
			
			// announcements
			foreach ( $roomIdArray as $roomId )
			{
				$annoucementManager = $this->_environment->getManager(CS_ANNOUNCEMENT_TYPE);
				$annoucementManager->setContextLimit($roomId);
			}
			
			// sort list
			$searchResults->sortby("modification_date");
			$searchResults->reverse();
			
			// prepare return
			$entry = $searchResults->getFirst();
			$count = 0;
			while ($entry) {
				if ($count >= $start && $count < $start + $numEntries) {
					$type = $entry->getItemType();
					if ($type == CS_LABEL_TYPE) {
						$labelManager = $this->_environment->getLabelManager();
						$entry = $labelManager->getItem($entry->getItemID());
						$type = $entry->getLabelType();
					} else {
						$manager = $this->_environment->getManager($type);
						$entry = $manager->getItem($entry->getItemID());
					}
					if (isset($entry) and !empty($entry)){
			
						// skip portfolio
						if ( $entry->getType() !== CS_PORTFOLIO_TYPE )
						{
							$moddate = $entry->getModificationDate();
							if ( $entry->getCreationDate() != $entry->getModificationDate() && !strstr($moddate,'9999-00-00')){
								$mod_date = $this->_environment->getTranslationObject()->getDateInLang($entry->getModificationDate());
							} else {
								$mod_date = $this->_environment->getTranslationObject()->getDateInLang($entry->getCreationDate());
							}
			
							if ($type === CS_MATERIAL_TYPE) {
								$versionId = $entry->getVersionID();
							} else {
								$versionId = null;
							}
			
							$return["items"][] = array(
								"itemId"			=> $entry->getItemID(),
								"contextId"			=> $entry->getContextID(),
								"module"			=> Type2Module($type),
								"title"				=> $entry->getTitle(),
								"image"				=> $this->getUtils()->getLogoInformationForType($type),
								"fileCount"			=> $entry->getFileList()->getCount(),
								"modificationDate"	=> $mod_date,
								"creator"			=> $entry->getCreatorItem()->getFullName(),
								"versionId"			=> $versionId,
								"roomName"			=> $roomNameArray[$entry->getContextID()]
							);
						}
					}
				}
			
				if ( isset($entry) && !empty($entry) && $entry->getType() !== CS_PORTFOLIO_TYPE )
				{
					$count++;
				}
			
				$entry = $searchResults->getNext();
			}
			
			$return["total"] = $count;
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// TODO: check for rights, see cs_ajax_accounts_controller

			// call parent
			parent::process();
		}
	}