<?php
require_once('classes/controller/cs_ajax_controller.php');

class cs_ajax_locking_controller extends cs_ajax_controller {
	/**
	 * constructor
	 */
	public function __construct(cs_environment $environment) {
		// call parent
		parent::__construct($environment);
	}
	
	/*
	 * every derived class needs to implement an processTemplate function
	 */
	public function process() {
		// TODO: check for rights, see cs_ajax_accounts_controller

		// call parent
		parent::process();
	}
	
	/*
	 * updates the editing date of an item
	 */
	public function actionUpdate() {
		$itemId = $this->_data['id'];

		// determ the item type
		$itemManager = $this->_environment->getItemManager();
		$itemType = $itemManager->getItemType($itemId);

		if (!empty($itemType)) {

            if ($itemType == CS_STEP_TYPE) {
                $itemTypeNew = CS_TODO_TYPE;
            } else if ($itemType == CS_SECTION_TYPE){
                $itemTypeNew = CS_MATERIAL_TYPE;
            }

            // get the corresponding manager
            $manager = $this->_environment->getManager($itemType);

            if ($itemType == CS_STEP_TYPE || $itemType == CS_SECTION_TYPE) {
                $item = $manager->getItem($itemId);
                $linkedItem = $item->getLinkedItem();
                $itemId = $linkedItem->getItemId();

                $manager = $this->_environment->getManager($linkedItem->getItemType());
                $itemType = $itemTypeNew;
            }
			if ($manager) {
				// check if the update method exists
				if (method_exists($manager, "updateLocking")) {
					$manager->updateLocking($itemId);
				}
			}
		}

		$this->setSuccessfullDataReturn(array());
		echo $this->_return;
	}

	public function actionStatus()
	{
        $translator = $this->_environment->getTranslationObject();
		$itemId = $this->_data['id'];
		$return = array();

		// determ the item type
		$itemManager = $this->_environment->getItemManager();
		$itemType = $itemManager->getItemType($itemId);

		if (!empty($itemType)) {
            if ($itemType == CS_SECTION_TYPE) {
                $sectionManager = $this->_environment->getSectionManager();
                $sectionItem = $sectionManager->getItem($itemId);

                $itemType = CS_MATERIAL_TYPE;
                $itemId = $sectionItem->getLinkedItemId();
            }

            if ($itemType == CS_STEP_TYPE) {
                $stepManager = $this->_environment->getStepManager();
                $stepItem = $stepManager->getItem($itemId);

                $itemType = CS_TODO_TYPE;
                $itemId = $stepItem->getTodoID();
            }

			// get the corresponding manager
			$manager = $this->_environment->getManager($itemType);
			if ($manager) {
                $item = $manager->getItem($itemId);

                global $symfonyContainer;
                $checkLocking = $symfonyContainer->getParameter('commsy.settings.item_locking');
                
				if ($checkLocking && method_exists($item, "getLockingDate")) {
					$lockingDate = $item->getLockingDate();
					if ($lockingDate) {
						$editDate = new DateTime($lockingDate);
						$compareDate = new DateTime();
						$compareDate->modify("-20 minutes");

						$userManager = $this->_environment->getUserManager();
						$lockingUser = $userManager->getItem($item->getLockingUserId());
                        $currentUser = $this->_environment->getCurrentUser();

						$currentUserId = $currentUser->getRelatedPortalUserItem()->getItemId();
						$lockingUserId = $lockingUser->getRelatedPortalUserItem()->getItemId();
 
						if ($currentUserId != $lockingUserId) {
							$return['locked'] = ($compareDate < $editDate);
							$return['locked_user_name'] = $lockingUser->getFullName();
							$return['locked_date'] = $translator->getDateTimeinLang($lockingDate);
                            $return['locked_message'] = $translator->getMessage("ITEM_LOCKING_DESC", $return['locked_user_name'], $return['locked_date']);
						}
					}
				}
			}
		}

		$this->setSuccessfullDataReturn($return);
		echo $this->_return;


	}

	/*
	 * clears a lock
	 */
	public function actionClear() {
		$itemId = $this->_data['id'];

		// determ the item type
		$itemManager = $this->_environment->getItemManager();
		$itemType = $itemManager->getItemType($itemId);

		if (!empty($itemType)) {
			if ($itemType == CS_STEP_TYPE) {
                $itemTypeNew = CS_TODO_TYPE;
            } else if ($itemType == CS_SECTION_TYPE){
                $itemTypeNew = CS_MATERIAL_TYPE;
            }

            // get the corresponding manager
            $manager = $this->_environment->getManager($itemType);

            if ($itemType == CS_STEP_TYPE || $itemType == CS_SECTION_TYPE) {
                $item = $manager->getItem($itemId);
                $linkedItem = $item->getLinkedItem();
                $itemId = $linkedItem->getItemId();

                $manager = $this->_environment->getManager($linkedItem->getItemType());
                $itemType = $itemTypeNew;
            }
			if ($manager) {
				// check if the clear method exists
				if (method_exists($manager, "clearLocking")) {
					$manager->clearLocking($itemId);
				}
			}
		}

		$this->setSuccessfullDataReturn(array());
		echo $this->_return;
	}
}