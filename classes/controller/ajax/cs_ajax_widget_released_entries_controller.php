<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_widget_released_entries_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetListContent() {
			$return = array(
				"releasedItems"		=> array(),
				"viewableItems"		=> array()
			);
			
			$itemManager = $this->_environment->getItemManager();
			$currentUser = $this->_environment->getCurrentUserItem();
			
			$released_ids = $itemManager->getExternalViewerEntriesForRoom($room_id);
			$viewable_ids = $itemManager->getExternalViewerEntriesForUser($currentUser->getItemID());
			
			$select_ids = array_merge($released_ids, $viewable_ids);
			
			$item_list = $itemManager->getItemList($select_ids);
			
			/*
			 * $noticed_manager = $this->_environment->getNoticedManager();
         $noticed_manager->getLatestNoticedByIDArray($released_ids);
         $noticed_manager->getLatestNoticedAnnotationsByIDArray($released_ids);
			 */
			
			// prepare return
			$entry = $item_list->getFirst();
			while ($entry) {
				if (in_array($entry->getItemID(), $released_ids)) {
					$type = $entry->getItemType();
					if ($type == CS_LABEL_TYPE) {
						$labelManager = $this->_environment->getLabelManager();
						$entry = $labelManager->getItem($entry->getItemID());
						$type = $entry->getLabelType();
					} else {
						$manager = $this->_environment->getManager($type);
						$entry = $manager->getItem($entry->getItemID());
					}
					
					$return["releasedItems"][] = array(
							"itemId"		=> $entry->getItemID(),
							"contextId"		=> $entry->getContextID(),
							"module"		=> Type2Module($type),
							"title"			=> $entry->getTitle(),
							"image"			=> $this->getUtils()->getLogoInformationForType($type)
					);
				}
				
				$entry = $item_list->getNext();
			}
			
			
			/*
			 * $this->_related_user = $user->getRelatedUserItemInContext($this->_environment->getCurrentPortalID());
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed_manager->getLatestNoticedByIDArray($viewable_ids,$this->_related_user->getItemID());
         $noticed_manager->getLatestNoticedAnnotationsByIDArrayAndUser($viewable_ids,$this->_related_user->getItemID());
			 */
			
			$entry = $item_list->getFirst();
			while ($entry) {
				if (in_array($entry->getItemID(), $viewable_ids)) {
					$type = $entry->getItemType();
					if ($type == CS_LABEL_TYPE) {
						$labelManager = $this->_environment->getLabelManager();
						$entry = $labelManager->getItem($entry->getItemID());
						$type = $entry->getLabelType();
					} else {
						$manager = $this->_environment->getManager($type);
						$entry = $manager->getItem($entry->getItemID());
					}
						
					$return["viewableItems"][] = array(
							"itemId"		=> $entry->getItemID(),
							"contextId"		=> $entry->getContextID(),
							"module"		=> Type2Module($type),
							"title"			=> $entry->getTitle(),
							"image"			=> $this->getUtils()->getLogoInformationForType($type)
					);
				}
			
				$entry = $item_list->getNext();
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