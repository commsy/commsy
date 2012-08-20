<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_portfolioList_controller implements cs_rubric_popup_controller {
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
		/*
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
			$externalViewerUserIds = explode(" ", trim($externalViewerString));
			$item->setExternalViewer($externalViewerUserIds);
			
			$item->save();
			
			$this->_popup_controller->setSuccessfullDataReturn(array("portfolioID" => $item->getItemID()));
		}
		*/
	}

	public function initPopup($item, $data) {
		$itemIdArray = $data["itemIds"];
		
		$this->_popup_controller->assign("popup", "numItems", sizeof($itemIdArray));
		
		$itemManager = $this->_environment->getItemManager();
		$currentUser = $this->_environment->getCurrentUser();
		$privateRoom = $currentUser->getOwnRoom();
		
		$this->_popup_controller->assign("popup", "privateRoomId", $privateRoom->getItemID());
		$this->_popup_controller->assign("popup", "portfolioId", $item->getItemID());
		
		$itemIdRubricArray = array();
		foreach ($itemIdArray as $id) {
			$type = $itemManager->getItemType($id);
			$itemIdRubricArray[$type][] = $id;
		}
		
		
		$itemArray = array();
		foreach ($itemIdRubricArray as $rubric => $idArray) {
			$manager = $this->_environment->getManager($rubric);
			$manager->resetLimits();
			$manager->setIDArrayLimit($idArray);
			$manager->setContextLimit($privateRoom->getItemID());
			$manager->select();
			
			$rubricList = $manager->get();
			$rubricItem = $rubricList->getFirst();
			while ($rubricItem) {
				$moddate = $rubricItem->getModificationDate();
				if ( $item->getCreationDate() <> $item->getModificationDate() and !strstr($moddate,'9999-00-00')){
					$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
				} else {
					$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getCreationDate());
				}
				
				$itemArray[] = array(
					"itemId"			=> $rubricItem->getItemID(),
					"title"				=> $rubricItem->getTitle(),
					"module"			=> $rubric,
					"modificationDate"	=> $moddate,
					"modificator"		=> $rubricItem->getModificatorItem()->getFullName()
				);
				
				$rubricItem = $rubricList->getNext();
			}
		}
		
		$this->_popup_controller->assign("popup", "items", $itemArray);
		
		$annotationManager = $this->_environment->getAnnotationManager();
		
		$annotationArray = array();
		$annotationManager->resetLimits();
		$annotationManager->setLinkedItemID($item->getItemID());
		$annotationManager->setContextLimit($privateRoom->getItemID());
		$annotationManager->select();
		
		$annotationList = $annotationManager->get();
		$annotationItem = $annotationList->getFirst();
		
		while ($annotationItem) {
			$moddate = $annotationItem->getModificationDate();
			if ( $item->getCreationDate() <> $item->getModificationDate() and !strstr($moddate,'9999-00-00')){
				$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
			} else {
				$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getCreationDate());
			}
				
			$annotationArray[] = array(
					"itemId"			=> $annotationItem->getItemID(),
					"title"				=> $annotationItem->getTitle(),
					"modificationDate"	=> $moddate,
					"modificator"		=> $annotationItem->getModificatorItem()->getFullName()
			);
				
			$annotationItem = $annotationList->getNext();
		}
		
		$this->_popup_controller->assign("popup", "numAnnotations", sizeof($annotationArray));
		
		$this->_popup_controller->assign("popup", "annotationItems", $annotationArray);
	}

	public function getFieldInformation($sub = '') {
		$return = array(
			
		);

		return $return;
	}
	
	public function cleanup_session($iid) {
	}
}