<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_portfolio_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetPortfolios() {
			$return = array(
				"myPortfolios"			=> array(),
				"activatedPortfolios"	=> array()
			);
			
			$portfolioManager = $this->_environment->getPortfolioManager();
			
			$currentUser = $this->_environment->getCurrentUser();
				
			$privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();
			
			$portfolioManager->reset();
			$portfolioManager->setUserLimit($privateRoomUser->getItemID());
			$portfolioManager->select();
			$portfolioList = $portfolioManager->get();
			
			$myPortfolios = array();
			$portfolioItem = $portfolioList->getFirst();
			while ($portfolioItem) {
				$myPortfolios[] = array(
					"id"		=> $portfolioItem->getItemID(),
					"title"		=> $portfolioItem->getTitle()
				);
				
				$portfolioItem = $portfolioList->getNext();
			}
			$return["myPortfolios"] = $myPortfolios;
			
			$activatedPortfolios = array();
			$activatedIdArray = $portfolioManager->getActivatedIDArray($privateRoomUser->getItemID());
			
			if (!empty($activatedIdArray)) {
				$portfolioManager->reset();
				$portfolioManager->setIDArrayLimit($activatedIdArray);
				$portfolioManager->select();
				$portfolioList = $portfolioManager->get();
					
					
					
				if (!$portfolioList->isEmpty()) {
					$portfolioItem = $portfolioList->getFirst();
					while ($portfolioItem) {
						$activatedPortfolios[] = array(
								"id"		=> $portfolioItem->getItemID(),
								"title"		=> $portfolioItem->getTitle()
						);
				
						$portfolioItem = $portfolioList->getNext();
					}
				}
			}
			$return["activatedPortfolios"] = $activatedPortfolios;
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionGetPortfolio() {
			// get data
			$portfolioId = $this->_data["portfolioId"];
			
			$portfolioManager = $this->_environment->getPortfolioManager();
			$portfolioItem = $portfolioManager->getItem($portfolioId);
			
			$currentUser = $this->_environment->getCurrentUser();
			$privateRoom = $currentUser->getOwnRoom();
			
			// gather tag information
			$tags = $portfolioManager->getPortfolioTags($portfolioId);
			$tagIdArray = array();
			foreach ($tags as $tag) {
				$tagIdArray[] = $tag["t_id"];
			}
			
			// gather cell information
			$linkManager = $this->_environment->getLinkItemManager();
			$links = $linkManager->getALlLinksByTagIDArray($privateRoom->getItemID(), $tagIdArray);
			
			$cellInformation = array();
			foreach ($links as $link) {
				if ($link["first_item_type"] === CS_TAG_TYPE) {
					$cellInformation[$link["first_item_id"]][$link["second_item_type"]][$link["second_item_id"]] = null;
				} else if($link["second_item_type"] === CS_TAG_TYPE) {
					$cellInformation[$link["second_item_id"]][$link["first_item_type"]][$link["first_item_id"]] = null;
				}
			}
			
			// create a rubric sorted array to group for manager
			$rubricSorted = array();
			foreach ($cellInformation as $tagId => $rubric) {
				foreach ($rubric as $rubricName => $idArray) {
					$rubricSorted[$rubricName]["tagId"] = $tagId;
					
					foreach ($idArray as $id => $null) {
						$rubricSorted[$rubricName]["ids"][] = $id;
					}
				}
			}
			
			// fetch items
			foreach ($rubricSorted as $rubric => $detail) {
				$idArray = $detail["ids"];
				$tagId = $detail["tagId"];
				
				$manager = $this->_environment->getManager($rubric);
				$manager->reset();
				$manager->setIDArrayLimit($idArray);
				$manager->setContextLimit($privateRoom->getItemID());
				$manager->select();
				
				$itemList = $manager->get();
				$item = $itemList->getFirst();
				
				while ($item) {
					$itemInformation = array(
						"itemId"	=> $item->getItemID(),
						"title"		=> $item->getTitle()
					);
					
					$cellInformation[$tagId][$rubric][$item->getItemID()] = $itemInformation;
					
					$item = $itemList->getNext();
				}
			}
			
			$return = array(
				"title"				=> $portfolioItem->getTitle(),
				"description"		=> $portfolioItem->getDescription(),
				"tags"				=> $tags,
				"cells"				=> $cellInformation,
				"numAnnotations"	=> $portfolioManager->getAnnotationCountForPortfolio($portfolioId)
			);
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionAddTagToPortfolio() {
			// get data
			$portfolioId = $this->_data["portfolioId"];
			$tagId = $this->_data["tagId"];
			$position = $this->_data["position"];
			
			$portfolioManager = $this->_environment->getPortfolioManager();
			
			$portfolioTags = $portfolioManager->getPortfolioTags($portfolioId);
			
			// get new index according to position
			$index = 1;
			foreach($portfolioTags as $portfolioTag) {
				if ($portfolioTag["column"] === "0") {
					// this is a row tag
					
					if ($position === "row") $index++;
				} else {
					// this is a column tag
					
					if ($position === "column") $index++;
				}
			}
			
			$portfolioManager->addTagToPortfolio($portfolioId, $tagId, $position, $index);
			
			$this->setSuccessfullDataReturn(array());
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