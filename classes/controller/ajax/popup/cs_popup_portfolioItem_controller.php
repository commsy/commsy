<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_portfolioItem_controller implements cs_rubric_popup_controller {
	private $_environment = null;
	private $_popup_controller = null;

	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}

	public function save($form_data, $additional = array()) {
		$current_iid = $form_data['iid'];
		
		$portfolioManager = $this->_environment->getPortfolioManager();
		
		if($current_iid === 'NEW') {
			$item = null;
		} else {
			$item = $portfolioManager->getItem($current_iid);
		}
		
		$currentUser = $this->_environment->getCurrentUser();
		$privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();
		
		if ($this->_popup_controller->checkFormData()) {
			if ($item === null) {
				$item = $portfolioManager->getNewItem();
				$item->setCreationDate(getCurrentDateTimeInMySQL());
				$item->setCreatorItem($privateRoomUser);
			}
			
			$item->setTitle($form_data["title"]);
			$item->setDescription($form_data["description"]);
			$item->setModificationDate(getCurrentDateTimeInMySQL());
			$item->setModificatorItem($privateRoomUser);
			
			$externalViewerString = $form_data["externalViewer"];
			$externalViewerUserIds = explode(" ", $externalViewerString);
			$item->setExternalViewer($userIdArray);
			
			$item->save();
			
			$this->_popup_controller->setSuccessfullDataReturn(array("portfolioID" => $item->getItemID()));
		}
	}

	public function initPopup($item, $data) {
		if($item !== null) {
			$this->_popup_controller->assign("portfolio", "title", $item->getTitle());
			$this->_popup_controller->assign("portfolio", "description", $item->getDescription());
			
			$externalViewer = $item->getExternalViewer();
			$externalViewerString = "";
			
			$external = $externalViewer->getFirst();
			$first = true;
			while ($external) {
				if ($first) {
					$externalViewerString .= $external->getUserID();
					$first = false;
				} else {
					$externalViewerString .= " " . $external->getUserID();
				}
				
				$external = $externalViewer->getNext();
			}
			$this->_popup_controller->assign("portfolio", "externalViewser", $externalViewser);
		}
	}

	public function getFieldInformation($sub = '') {
		$return = array(
			
		);

		return $return;
	}
	
	public function cleanup_session($iid) {
	}
}