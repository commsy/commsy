<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_widget_released_entries_for_me_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionQuery()
		{
			$return = array(
				"items"		=> array()
			);

			$start = $this->_data["options"]["start"];
			$numEntries = $this->_data["options"]["numEntries"];

			$itemManager = $this->_environment->getItemManager();
			$currentUser = $this->_environment->getCurrentUserItem();
			$translator = $this->_environment->getTranslationObject();

			$viewable_ids = $itemManager->getExternalViewerEntriesForUser($currentUser->getRelatedPrivateRoomUserItem()->getUserID());
			$item_list = $itemManager->getItemList($viewable_ids);
			$item_list->sortby("modification_date");
			$item_list->reverse();

			/*
			 * $this->_related_user = $user->getRelatedUserItemInContext($this->_environment->getCurrentPortalID());
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed_manager->getLatestNoticedByIDArray($viewable_ids,$this->_related_user->getItemID());
         $noticed_manager->getLatestNoticedAnnotationsByIDArrayAndUser($viewable_ids,$this->_related_user->getItemID());
			 */

			$entry = $item_list->getFirst();
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
						// released from
						$modifierItem = $entry->getModificatorItem();
						$releasedFrom = $translator->getMessage("PRIVATEROOM_RELEASED_FROM") . ": " . $modifierItem->getFullname();

						if ($type === CS_MATERIAL_TYPE) {
							$versionId = $entry->getVersionID();
						} else {
							$versionId = null;
						}

						$return["items"][] = array(
								"itemId"		=> $entry->getItemID(),
								"contextId"		=> $entry->getContextID(),
								"module"		=> Type2Module($type),
								"title"			=> $entry->getTitle(),
								"image"			=> $this->getUtils()->getLogoInformationForType($type),
								"releasedFrom"	=> $releasedFrom,
								"versionId"		=> $versionId
						);
					}
				}

				$count++;
				$entry = $item_list->getNext();
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