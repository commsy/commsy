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
					
			$search = $this->_data["search"];
			$start = $this->_data["start"];
			$numEntries = $this->_data["numEntries"];
			$buzzwordIds = $this->_data["buzzwordRestrictions"];
			$tagIds = $this->_data["tagRestrictions"];
			
			$currentUser = $this->_environment->getCurrentUserItem()->getRelatedPrivateRoomUserItem();
			$itemManager = $this->_environment->getItemManager();
			
			$privateRoomItem = $currentUser->getOwnRoom();
			
			$userIdArray = array($currentUser->getItemID());
			$privateRoomIdArray = array($privateRoomItem->getItemID());
			
			$itemManager->setOrderLimit(true);
			
			if ($search) $itemManager->setSearchLimit($search);
			if ($buzzwordIds) $itemManager->setBuzzwordLimit($buzzwordIds[0]);
			if ($tagIds) $itemManager->setTagLimit($tagIds[0]);
			
			/*
			 * finaly removed?
			 * 
			if (!empty($sellist) and $sellist != 'new'){
				$item_manager->setListLimit($sellist);
			}
			if (!empty($selmatrix)){
				$item_manager->setMatrixLimit($selmatrix);
			}
			 */
			
			$entryList = $itemManager->getAllPrivateRoomEntriesOfUserList($privateRoomIdArray, $userIdArray);
			#pr($entryList);
			// ToDo: Nur Einträge in der Liste belassen, die auch angezeigt werden -> sonst gibt es leere Seiten über die geblättert wird!
			
			
			$rubricArray = array(CS_ANNOUNCEMENT_TYPE, CS_DISCUSSION_TYPE, CS_DATE_TYPE, CS_MATERIAL_TYPE, CS_TODO_TYPE);
			
			$filteredList = new cs_list();
			
			$entry = $entryList->getFirst();
			while ($entry) {
				if (in_array($entry->getItemType(), $rubricArray)) $filteredList->add($entry);
				
				$entry = $entryList->getNext();
			}
			
			// prepare return
			$entry = $filteredList->getFirst();
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
					
					if ($entry != null) {
						$moddate = $entry->getModificationDate();
						if ( $entry->getCreationDate() <> $entry->getModificationDate() and !strstr($moddate,'9999-00-00')){
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
								//"creator"			=> $entry->getCreatorItem()->getFullName(),
								"versionId"			=> $versionId
						);
					}
				}
				
				$count++;
				$entry = $filteredList->getNext();
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