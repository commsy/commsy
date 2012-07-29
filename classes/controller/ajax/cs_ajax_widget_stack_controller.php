<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_widget_stack_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetListContent() {
			$return = array(
				"items"		=> array()
			);
			
			$currentUser = $this->_environment->getCurrentUserItem()->getRelatedPrivateRoomUserItem();
			$itemManager = $this->_environment->getItemManager();
			
			$privateRoomItem = $currentUser->getOwnRoom();
			
			$userIdArray = array($currentUser->getItemID());
			$privateRoomIdArray = array($privateRoomItem->getItemID());
			
			$itemManager->setOrderLimit(true);
			/*
			 * TODO:
			 * 
			if (!empty($sellist) and $sellist != 'new'){
				$item_manager->setListLimit($sellist);
			}
			if (!empty($selbuzzword)){
				$item_manager->setBuzzwordLimit($selbuzzword);
			}
			if (!empty($selmatrix)){
				$item_manager->setMatrixLimit($selmatrix);
			}
			if (!empty($searchtext)){
				$item_manager->setSearchLimit($searchtext);
			}
			if (!empty($last_selected_tag)){
				$item_manager->setTagLimit($last_selected_tag);
			}
			 */
			
			$entryList = $itemManager->getAllPrivateRoomEntriesOfUserList($privateRoomIdArray, $userIdArray);
			
			// ToDo: Nur Einträge in der Liste belassen, die auch angezeigt werden -> sonst gibt es leere Seiten über die geblättert wird!
			
			
			$rubricArray = array(CS_ANNOUNCEMENT_TYPE, CS_DISCUSSION_TYPE, CS_DATE_TYPE, CS_MATERIAL_TYPE, CS_TODO_TYPE);
			
			$filteredList = new cs_list();
			
			$entry = $entryList->getFirst();
			while ($entry) {
				if (in_array($entry->getItemType(), $rubricArray)) $filteredList->add($entry);
				
				$entry = $entryList->getNext();
			}
			
			/*
			$browse_prev = true;
			$browse_next = true;
			$temp_list = new cs_list();
			if($interval != 'all'){
				$temp_start = $pos * $interval;
				$temp_index = 0;
				$temp_item = $new_entry_list->getFirst();
				while($temp_item){
					if(($temp_index >= $temp_start) and ($temp_index < ($temp_start + $interval))){
						$temp_list->add($temp_item);
					}
					$temp_index++;
					$temp_item = $new_entry_list->getNext();
				}
				if($pos == 0){
					$browse_prev = false;
				}
				if(($temp_start + $interval) >= $temp_index){
					$browse_next = false;
				}
				if($temp_index % $interval == 0){
					$max_pos = ($temp_index / $interval) - 1;
				} else {
					$max_pos = (($temp_index - ($temp_index % $interval)) / $interval);
				}
			} else {
				$temp_list = $new_entry_list;
				$browse_prev = false;
				$browse_next = false;
				$pos = 0;
				$max_pos = 0;
			}
			*/
			
			// prepare return
			$entry = $filteredList->getFirst();
			while ($entry) {
				$type = $entry->getItemType();
				if ($type == CS_LABEL_TYPE) {
					$labelManager = $this->_environment->getLabelManager();
					$entry = $labelManager->getItem($entry->getItemID());
					$type = $entry->getLabelType();
				} else {
					$manager = $this->_environment->getManager($type);
					$entry = $manager->getItem($entry->getItemID());
				}
				
				$return["items"][] = array(
					"itemId"		=> $entry->getItemID(),
					"contextId"		=> $entry->getContextID(),
					"module"		=> Type2Module($type),
					"title"			=> $entry->getTitle(),
					"image"			=> $this->getUtils()->getLogoInformationForType($type)
				);
			
				$entry = $filteredList->getNext();
			}
			
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