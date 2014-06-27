<?php
/*	$this->_flushHeadline('checking user "all"-group relationship');
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
			
			// get list of project rooms
			$projectRoomManager->setContextLimit($portalItem->getItemID());
			$projectRoomManager->setLastLoginLimit('NULL');
			$projectRoomManager->select();
			$projectRoomList = $projectRoomManager->get();
			
			if ($projectRoomList && $projectRoomList->isNotEmpty()) {
				$this->_flushHTML('project room' . LF);
				$this->_flushHTML(BRLF);
				
				$numRooms = $projectRoomList->getCount();
				$this->_initProgressBar($numRooms);
				
				// iterate project rooms
				$projectRoom = $projectRoomList->getFirst();
				while ($projectRoom) {
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
							if (!$userItem->isInGroup($groupAll)) {
								$userItem->setGroup($groupAll);
								$userItem->setChangeModificationOnSave(false);
								$userItem->save();
								
								$numUnrelated++;
							}
							
							$userItem = $userList->getNext();
						}
						
						$this->_flushHTML($numUnrelated . ' relations added' . LF);
						$this->_flushHTML(BRLF);
					}
					
					$this->_updateProgressBar($count);
					$projectRoom = $projectRoomList->getNext();
				}
			}
			
			$portalItem = $portalList->getNext();
			$this->_flushHTML(BRLF);
		}
	}
	
	$this->_flushHTML(BRLF);*/