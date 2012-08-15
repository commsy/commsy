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
			
			// gather linked cell information
			$linkManager = $this->_environment->getLinkItemManager();
			$links = $linkManager->getALlLinksByTagIDArray($privateRoom->getItemID(), $tagIdArray);
			
			$rubricArray = array();
			
			// structure links by rubric
			foreach ($links as $link) {
				if ($link["first_item_type"] === CS_TAG_TYPE) {
					$rubricArray[$link["second_item_type"]][$link["first_item_id"]][] = $link["second_item_id"];
				} else if($link["second_item_type"] === CS_TAG_TYPE) {
					$rubricArray[$link["first_item_type"]][$link["second_item_id"]][] = $link["first_item_id"];
				}
			}
			
			// fetch items
			$linkArray = array();
			foreach ($rubricArray as $rubric => $tagArray) {
				foreach($tagArray as $tagId => $idArray) {
					$manager = $this->_environment->getManager($rubric);
					$manager->resetLimits();
					$manager->setIDArrayLimit($idArray);
					$manager->setContextLimit($privateRoom->getItemID());
					$manager->select();
					
					$itemList = $manager->get();
					$item = $itemList->getFirst();
					
					while ($item) {
						$itemInformation = array(
							"itemId"	=> $item->getItemId(),
							"title"		=> $item->getTitle()
						);
						
						$linkArray[$tagId][] = $itemInformation;
						
						$item = $itemList->getNext();
					}
				}
			}
			
			$return = array(
				"title"				=> $portfolioItem->getTitle(),
				"description"		=> $portfolioItem->getDescription(),
				"tags"				=> $tags,
				"links"				=> $linkArray,
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
			
			// check if this tag already exists
			$double = false;
			foreach ($portfolioTags as $tag) {
				if ($tag["t_id"] == $tagId) {
					$double = true;
					break;
				}
			}
			if ($double) {
				$this->setErrorReturn("115", "tag already exists", array());
				echo $this->_return;
			} else {
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