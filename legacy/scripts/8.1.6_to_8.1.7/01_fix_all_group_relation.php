<?php
	$this->_flushHeadline('checking user "all"-group relationship');
	$this->_flushHTML(BRLF);
	$success = true;
	
	$serverItem = $this->_environment->getServerItem();
	$portalList = $serverItem->getPortalList();
	
	if ($portalList && $portalList->isNotEmpty()) {
		set_time_limit(0);
		
		$projectRoomManager = $this->_environment->getProjectManager();
		$userManager = $this->_environment->getUserManager();
		$groupManager = $this->_environment->getGroupManager();
		
		$portalItem = $portalList->getFirst();
		while ($portalItem) {
			$this->_flushHTML($portalItem->getTitle() . LF);
			$this->_flushHTML(BRLF);
			$this->_flushHTML(BRLF);
			
			// get list of project rooms
			$projectRoomManager->setContextLimit($portalItem->getItemID());
			$projectRoomManager->select();
			$projectRoomList = $projectRoomManager->get();
			
			if ($projectRoomList && $projectRoomList->isNotEmpty()) {
				$numRooms = $projectRoomList->getCount();
				
				// iterate project rooms
				$projectRoom = $projectRoomList->getFirst();
				$roomCount = 1;
				while ($projectRoom) {
					$this->_flushHTML('project room: ' . $projectRoom->getTitle() . LF);
					$this->_flushHTML(BRLF);

					// get group "ALL"
					$groupManager->reset();
					$groupManager->setContextLimit($projectRoom->getItemID());
					$groupAll = $groupManager->getItemByName('ALL');
					
					// get list of users
					$userManager->reset();
					$userManager->setContextLimit($projectRoom->getItemID());
					$userManager->setUserLimit();
					$userManager->select();
					$userList = $userManager->get();
					
					if ($userList && $userList->isNotEmpty()) {
						$numUnrelated = 0;
						
						// iterate users
						$userItem = $userList->getFirst();
						while ($userItem) {
							if (!$userItem->isRoot()) {
								if (!$userItem->isInGroup($groupAll)) {
									$userItem->setGroup($groupAll);
									$userItem->setChangeModificationOnSave(false);
									$userItem->save();
									
									$numUnrelated++;
								}
							}
							
							$userItem = $userList->getNext();
						}
						
						$this->_flushHTML($numUnrelated . ' relations added' . LF);
						$this->_flushHTML(BRLF);
						$this->_flushHTML(BRLF);
					}
					
					$projectRoom = $projectRoomList->getNext();
				}
			}
			
			$portalItem = $portalList->getNext();
			$this->_flushHTML(BRLF);
		}
	}
	
	$this->_flushHTML(BRLF);