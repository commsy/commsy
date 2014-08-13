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
			// get the corresponding manager
			$manager = $this->_environment->getManager($itemType);
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

	/*
	 * clears a lock
	 */
	public function actionClear() {
		$itemId = $this->_data['id'];

		// determ the item type
		$itemManager = $this->_environment->getItemManager();
		$itemType = $itemManager->getItemType($itemId);

		if (!empty($itemType)) {
			// get the corresponding manager
			$manager = $this->_environment->getManager($itemType);
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