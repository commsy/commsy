<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_myCalendar_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		/** \brief	Json store query implementation
		 *
		 * Performes query requests for the personal calendar. Form parameters are taken from parent class.
		 */
		public function actionQuery() {
			$query = $this->_data["query"];
			$startISO = $this->_data["options"]["startISOTime"];
			$endISO = $this->_data["options"]["endISOTime"];
			
			$parameters = array();
			$parameters["activatingStatus"] = /*isset($this->_data["parameters"]["activatingStatus"]) ? $this->_data["parameters"]["activatingStatus"] :*/ "2";
			$parameters["selColor"] = /*isset($this->_data["parameters"]["selColor"]) ? $this->_data["parameters"]["selColor"] :*/ "2";
			$parameters["selRoom"] = /*isset($this->_data["parameters"]["selRoom"]) ? $this->_data["parameters"]["selRoom"] :*/ "2";
			$parameters["todoSelRoom"] = /*isset($this->_data["parameters"]["todoSelRoom"]) ? $this->_data["parameters"]["todoSelRoom"] :*/ "2";
			$parameters["selStatus"] = /*isset($this->_data["parameters"]["selStatus"]) ? $this->_data["parameters"]["selStatus"] :*/ "2";
			$parameters["assignedToMe"] = isset($this->_data["options"]["assignedToMe"]) ? $this->_data["options"]["assignedToMe"] : false;
			
			$month = "09";
			$year = "2012";
			$displayMode = "month"; // month | year | day ...
			
			$currentUserItem = $this->_environment->getCurrentUser();
			$privateContextItem = $currentUserItem->getOwnRoom();
			
			/* get data from database */
			$datesManager = $this->_environment->getDatesManager();
			$todoManager = $this->_environment->getTodoManager();
			
			$colorArray = $datesManager->getColorArray();
			
			$datesManager->resetLimits();
			$datesManager->setSortOrder('time');
			
			/* set paramter limits */
			if ( $parameters["activatingStatus"] == "2" )
			{
				$datesManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
			}
			
			if ( $parameters["selColor"] != "2" )
			{
				$datesManager->setColorLimit("#" . $parameters["selColor"]);
			}
			
			if ( $paramters["selStatus"] != "2" )
			{
				$datesManager->setDateModeLimit($parameters["selStatus"]);
			}
			
			/*
			if ( !empty($ref_iid) and $mode == 'attached' ){
				$dates_manager->setRefIDLimit($ref_iid);
			}
			if ( !empty($ref_user) and $mode == 'attached' ){
				$dates_manager->setRefUserLimit($ref_user);
			}
			if ( !empty($sort) and ($seldisplay_mode!='calendar' or $seldisplay_mode == 'calendar_month' or $mode == 'formattach' or $mode == 'detailattach') ) {
				$dates_manager->setSortOrder($sort);
			}
			if ( !empty($search) ) {
				$dates_manager->setSearchLimit($search);
			}
			if ( !empty($selbuzzword) ) {
				$dates_manager->setBuzzwordLimit($selbuzzword);
			}
			if ( !empty($last_selected_tag) ){
				$dates_manager->setTagLimit($last_selected_tag);
			}
			*/
			
			/* get calendar display configuration */
			$configuration = $privateContextItem->getMyCalendarDisplayConfig();
			$datesLimit = array();
			$todoLimit = array();
			
			foreach ($configuration as $entry) {
				$entryExplode = explode("_", $entry);
				if (sizeof($entryExplode) === 2) {
					list($value, $type) = $entryExplode;
					
					if ($type === "dates") $datesLimit[] = $value;
					else $todoLimit[] = $value;
				}
			}
			
			/************************************************************************************
			 * We need to collect all room ids relevant for our calendar
			************************************************************************************/
			$roomIdArray = array();
			
			// privateroom itself
			$roomIdArray[] = $privateContextItem->getItemID();
			
			// related group rooms
			$groupRoomList = $currentUserItem->getRelatedGroupList();
			if (isset($groupRoomList) && $groupRoomList->isNotEmpty()) {
				$groupRoomList->reverse();
				
				$groupRoomItem = $groupRoomList->getFirst();
				while ($groupRoomItem) {
					$projectRoomId = $groupRoomItem->getLinkedProjectItemID();
					
					if (in_array($projectRoomId, $roomIdArray)) {
						$roomIdArrayTemp = array();
						
						foreach ($roomIdArray as $value) {
							$roomIdArrayTemp[] = $value;
							
							if ($value === $projectRoomId) {
								$roomIdArrayTemp[] = $groupRoomItem->getItemID();
							}
						}
						
						$roomIdArray = $roomIdArrayTemp;
					}
					
					$groupRoomItem = $groupRoomList->getNext();
				}
			}
			
			// related project rooms
			$projectList = $currentUserItem->getRelatedProjectList();
			if (isset($projectList) && $projectList->isNotEmpty()) {
				$projectItem = $projectList->getFirst();
				while ($projectItem) {
					$roomIdArray[] = $projectItem->getItemID();
					
					$projectItem = $projectList->getNext();
				}
			}
			
			// related community rooms
			$communityList = $currentUserItem->getRelatedCommunityList();
			if (isset($communityList) && $communityList->isNotEmpty()) {
				$communityItem = $communityList->getFirst();
				while ($communityItem) {
					$roomIdArray[] = $communityItem->getItemID();
					
					$communityItem = $communityList->getNext();
				}
			}
			
			/************************************************************************************
			 * Filter id array
			************************************************************************************/
			$temp = array();
			
			foreach ($datesLimit as $limit) {
				if (in_array($limit, $roomIdArray)) {
					$temp[] = $limit;
				}
			}
			$temp[] = $privateContextItem->getItemID();
			$datesLimit = $temp;
			
			$temp = array();
			foreach ($todoLimit as $limit) {
				if ( in_array($limit, $roomIdArray)) {
					$temp[] = $limit;
				}
			}
			$temp[] = $privateContextItem->getItemID();
			$todoLimit = $temp;
			
			if ($parameters["selRoom"] != "2") {
				$datesManager->setContextArrayLimit($parameters["selRoom"]);
			} else {
				$datesManager->setContextArrayLimit($datesLimit);
			}
			
			if ( isset($startISO) && isset($endISO) )
			{
				$datesManager->setBetweenLimit($startISO, $endISO);
			}
			
			if ($displayMode === "month") {
				$datesManager->selectDistinct();
			} else {
				$datesManager->select();
			}
			
			$dateList = $datesManager->get();
			
			// post date filter
			if ( $parameters["assignedToMe"] === true )
			{
				// check if user is not root
				if ( !$currentUserItem->isRoot() )
				{
					$userList = $currentUserItem->getRelatedUserList();
					$userList->add($currentUserItem);
					
					$dateEntry = $dateList->getFirst();
					
					while ( $dateEntry )
					{
						// check all related users for participation
						$user = $userList->getFirst();
						$isParticipant = false;
						while ( $user )
						{
							if ( $dateEntry->isParticipant($user) )
							{
								$isParticipant = true;
								break;
							}
							
							$user = $userList->getNext();
						}
						
						if ( !$isParticipant )
						{
							$dateList->removeElement($dateEntry);
						}
						
						$dateEntry = $dateList->getNext();
					}
				}
			}
			
			$dateEntry = $dateList->getFirst();
			$dates = array();
			while ($dateEntry) {
				$startDay = $dateEntry->getStartingDay();
				$startTime = $dateEntry->getStartingTime();
				$endDay = $dateEntry->getEndingDay();
				$endTime = $dateEntry->getEndingTime();
				$allDay = false;
				
				// check start time
				if ( empty($startTime) )
				{
					$startTime = "00:00";
					$allDay = true;
				}
				
				// check end day
				if (empty($endDay)) $endDay = $startDay;
				
				// check end time
				if (empty($endTime)) $endTime = $startTime;
				
				// ensure end > start
				if ($endDay < $startDay) {
					$endDay = $startDay;
					$endTime = $startTime;
				} else if ($endDay === $startDay) {
					if ($endTime <= $startTime) {
						$endTime = $startTime;
					}
				}
				
				/* convert into timestamps */
				$convertedStartDate = convertDateFromInput($startDay);
				$convertedStartTime = convertTimeFromInput($startTime);
				$timestampStart = mktime(	mb_substr($convertedStartTime["timestamp"], 0, 2),
											mb_substr($convertedStartTime["timestamp"], 2, 2),
											mb_substr($convertedStartTime["timestamp"], 4, 2),
											mb_substr($convertedStartDate["timestamp"], 4, 2),
											mb_substr($convertedStartDate["timestamp"], 6, 2),
											mb_substr($convertedStartDate["timestamp"], 0, 4)	);
				
				$convertedEndDate = convertDateFromInput($endDay);
				$convertedEndTime = convertTimeFromInput($endTime);
				$timestampEnd = mktime(	mb_substr($convertedEndTime["timestamp"], 0, 2),
						mb_substr($convertedEndTime["timestamp"], 2, 2),
						mb_substr($convertedEndTime["timestamp"], 4, 2),
						mb_substr($convertedEndDate["timestamp"], 4, 2),
						mb_substr($convertedEndDate["timestamp"], 6, 2),
						mb_substr($convertedEndDate["timestamp"], 0, 4)	);
				
				/* if equal add offset */
				if ( $timestampStart === $timestampEnd )
				{
					if ( $allDay === true )
					{
						// if zero hour add one day
						$timestampEnd += 60 * 60 * 24;
					}
					else
					{
						// add one hour
						$timestampEnd += 60 * 60;
					}
				}
				// check for all day events
				if ( $timeStampStart - $timestampEnd >= 60 * 60 * 24 ) {
					$allDay = true;
				}
				
				$context = "public";
				$item_manager = $this->_environment->getItemManager();
				$context_item = $item_manager->getItem($dateEntry->getContextID());
				if ($context_item->getItemType() == 'privateroom') {
   				$context = "private";
				}
				
				$dates[] = array(
					"id"		=> $dateEntry->getItemID(),
					"summary"	=> $dateEntry->getTitle(),
					"startTime"	=> date("c", $timestampStart),
					"endTime"	=> date("c", $timestampEnd),
					"allDay"	=> $allDay,
					"context" => $context,
					"contextID" => $dateEntry->getContextID(),
					"module" => "date"
				);
				
				$dateEntry = $dateList->getNext();
			}
			$this->rawDataReturn($dates);
		}
		
		public function actionGetIcalAdress() {
			$hashManager = $this->_environment->getHashManager();
			
			$currentUserItem = $this->_environment->getCurrentUserItem();
			$privateUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();
			$privateContextItem = $currentUserItem->getOwnRoom();
			
			global $c_single_entry_point;
			
			$cid = $privateContextItem->getItemId();
			
			$baseUrl = '';
			$baseUrl .= $_SERVER['HTTP_HOST'];
			$baseUrl .= str_replace($c_single_entry_point, 'ical.php',$_SERVER['PHP_SELF']);
			
			$dateUrl = $baseUrl . '?cid=' . $cid . '&hid='.$hashManager->getICalHashForUser($privateUserItem->getItemID()).LF;
			$todoUrl = $baseUrl . '?cid=' . $cid . '&mod=todo&hid='.$hashManager->getICalHashForUser($privateUserItem->getItemID());
			
			$this->setSuccessfullDataReturn(array("date" => $dateUrl, "todo" => $todoUrl));
			echo $this->_return;
			exit;
		}
		
		public function actionGet() {
			$id = $this->_data["id"];
			
			$date = array(
					"id"		=> 0,
					"summary"	=> "Event 1",
					"startTime"	=> "2012-09-20T10:00",
					"endTime"	=> "2012-09-20T12:00"
			);
			
			$this->rawDataReturn($date);
		}
		
		/**
		 * \brief	gets calendar config
		 * 
		 * Return the user-specific calendar configuration
		 */
		public function actionGetConfig()
		{
			$currentUserItem = $this->_environment->getCurrentUserItem();
			$privateUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();
			$privateRoomItem = $currentUserItem->getOwnRoom();
			
			$calendarConfiguration = $privateRoomItem->getMyCalendarDisplayConfig();
			
			$return = array(
				"assignedToMe"			=> in_array("mycalendar_dates_assigned_to_me", $calendarConfiguration)
			);
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
			exit;
		}
		
		/** \brief	room list information
		 *
		 * Returns room list information for calendar config
		 */
		public function actionGetRoomList() {
			$currentUserItem = $this->_environment->getCurrentUserItem();
			$privateUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();
			$privateRoomItem = $currentUserItem->getOwnRoom();
			
			$roomManager = $this->_environment->getRoomManager();
			
			$roomList = $roomManager->getAllRelatedRoomListForUser($privateUserItem);
			$roomEntry = $roomList->getFirst();
			
			// get current active rooms
			$configuration = $privateRoomItem->getMyCalendarDisplayConfig();
			$myRoomArray = array();
			
			foreach ($configuration as $entry) {
				$entryExplode = explode("_", $entry);
				
				if (sizeof($entryExplode) === 2) {
					if ($entryExplode[1] === "dates" || $entryExplode[1] === "todo") {
						$myRoomArray[] = $entry;
					}
				}
			}
			
			// process room list
			$roomArray = array();
			while ($roomEntry) {
				$roomArray[] = array(
					"title"				=> $roomEntry->getTitle(),
					"id"				=> $roomEntry->getItemID(),
					"checkedInDates"	=> in_array($roomEntry->getItemID() . "_dates", $myRoomArray),
					"checkedInTodo"		=> in_array($roomEntry->getItemID() . "_todo", $myRoomArray)
				);
			
				$roomEntry = $roomList->getNext();
			}
			
			$this->setSuccessfullDataReturn($roomArray);
			echo $this->_return;
			exit;
		}
		
		/**
		 * \brief	Stores calendar configuration
		 */
		public function actionStoreConfig()
		{
			$config = $this->_data["config"];
			
			$currentUserItem = $this->_environment->getCurrentUserItem();
			$privateUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();
			$privateRoomItem = $currentUserItem->getOwnRoom();
				
			$calendarConfiguration = $privateRoomItem->getMyCalendarDisplayConfig();
			
			$store = false;
			if ( isset($config["assignedToMe"]) )
			{
				if ( !( $key = array_search("mycalendar_dates_assigned_to_me", $calendarConfiguration) ) && $config["assignedToMe"] === true )
				{
					$calendarConfiguration["mycalendar_dates_assigned_to_me"] = true;
					$store = true;
				}
				else
				{
					unset($calendarConfiguration[$key]);
					$store = true;
				}
			}
			
			if ( $store )
			{
				$privateRoomItem->setMyCalendarDisplayConfig($calendarConfiguration);
				$privateRoomItem->save();
			}
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
			exit;
		}
		
		public function actionStoreRoomChange() {
			$roomId = $this->_data["roomId"];
			$type = $this->_data["type"];
			$checked = $this->_data["checked"];
			
			// get calendar display config
			$privateRoomItem = $this->_environment->getCurrentUserItem()->getOwnRoom();
			$displayConfig = $privateRoomItem->getMyCalendarDisplayConfig();
			$lookFor = $roomId . "_" . $type;
			
			if ( !isset($displayConfig) )
			{
				$displayConfig = array();
			}
			
			if (($key = array_search($lookFor, $displayConfig, true)) !== false) {
				if ($checked === false) {
					unset($displayConfig[$key]);
				}
			} else {
				if ($checked === true) {
					$displayConfig[] = $lookFor;
				}
			}
			
			$privateRoomItem->setMyCalendarDisplayConfig($displayConfig);
			$privateRoomItem->save();
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
			exit;
		}
		
		public function actionStoreRoomSelectAll() {
			$type = $this->_data["type"];
			
			$currentUserItem = $this->_environment->getCurrentUserItem();
			$privateUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();
			$privateRoomItem = $currentUserItem->getOwnRoom();
			
			$displayConfig = $privateRoomItem->getMyCalendarDisplayConfig();
				
			$roomManager = $this->_environment->getRoomManager();
			$roomList = $roomManager->getAllRelatedRoomListForUser($privateUserItem);
			$roomEntry = $roomList->getFirst();
			
			while ($roomEntry) {
				$lookUp = $roomEntry->getItemID() . "_" . $type;
				
				if(!in_array($lookUp, $displayConfig)) {
					$displayConfig[] = $lookUp;
				}
					
				$roomEntry = $roomList->getNext();
			}
			
			$privateRoomItem->setMyCalendarDisplayConfig($displayConfig);
			$privateRoomItem->save();
				
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
			exit;
		}
		
		public function actionStoreRoomSelectNone()
		{
			$type = $this->_data["type"];
				
			$currentUserItem = $this->_environment->getCurrentUserItem();
			$privateUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();
			$privateRoomItem = $currentUserItem->getOwnRoom();
				
			$displayConfig = $privateRoomItem->getMyCalendarDisplayConfig();
				
			$roomManager = $this->_environment->getRoomManager();
			$roomList = $roomManager->getAllRelatedRoomListForUser($privateUserItem);
			$roomEntry = $roomList->getFirst();
			
			while ($roomEntry) {
				$lookUp = $roomEntry->getItemID() . "_" . $type;
				
				if ( ($key = array_search($lookUp, $displayConfig, true)) !== false )
				{
					array_splice($displayConfig, $key, 1);
					//$displayConfig[] = $lookUp;
				}
					
				$roomEntry = $roomList->getNext();
			}
			
			$privateRoomItem->setMyCalendarDisplayConfig($displayConfig);
			$privateRoomItem->save();
				
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
			exit;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}
