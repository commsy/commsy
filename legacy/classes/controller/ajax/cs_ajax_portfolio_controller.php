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
			    $externalViewer = $portfolioManager->getExternalViewer($portfolioItem->getItemID());
			    $externalViewerString = implode(";", $externalViewer);
			    
				$myPortfolios[] = array(
					"id"		=> $portfolioItem->getItemID(),
					"title"		=> $portfolioItem->getTitle(),
				    "external"  => $externalViewerString != "" ? $externalViewer : array()
				);
				
				$portfolioItem = $portfolioList->getNext();
			}
			$return["myPortfolios"] = $myPortfolios;
			
			$activatedPortfolios = array();
			$activatedIdArray = $portfolioManager->getActivatedIDArray($privateRoomUser->getUserID());
			
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
		
		public function actionGetTemplates()
		{
			$return = array();
			
			$currentUser = $this->_environment->getCurrentUser();
			$portfolioManager = $this->_environment->getPortfolioManager();
			$templateId = $portfolioManager->getPortfolioForExternalTemplate($currentUser->getUserID());
			$itemArray = array();
			
			foreach ($templateId as $Id) {
				$item = $portfolioManager->getItem($Id);
				$itemArray['id'] = $Id;
				$itemArray['title'] = $item->getTitle();
				$return['templates'][] = $itemArray;
			}
			$privateRoom = $currentUser->getOwnRoom();
			$privateRoomUserItem = $currentUser->getRelatedUserItemInContext($privateRoom->getItemID());
			
			$ownTemplates = $portfolioManager->getTemplatePortfoliosByCreatorID($privateRoomUserItem->getItemID());
			foreach ($ownTemplates as $template => $key) {
				$itemArray['id'] = $template;
				$itemArray['title'] = $key;
				$return['templates'][] = $itemArray;
			}
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionGetPortfolio() {
			// get data
			$portfolioId = $this->_data["portfolioId"];
			
			$portfolioManager = $this->_environment->getPortfolioManager();
			$portfolioItem = $portfolioManager->getItem($portfolioId);
			
			$userManager = $this->_environment->getUserManager();
			$userItem = $userManager->getItem($portfolioItem->getCreatorId());
			$privateRoom = $userItem->getOwnRoom();
			
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
			
			$translator = $this->_environment->getTranslationObject();
			$creatorItem = $portfolioItem->getCreatorItem();
			if (isset($creatorItem) && !$creatorItem->isDeleted()) {
				if ($creatorItem->isGuest() && $modificator->isVisibleForLoggedIn()) {
					$fullname = $translator->getMessage("COMMON_USER_NOT_VISIBLE");
				} else {
					$fullname = $creatorItem->getFullName();
				}
			} else {
				$fullname = $translator->GetMessage("COMMON_DELETED_USER");
			}
			
			$externalViewer = $portfolioManager->getExternalViewer($portfolioId);
			$externalViewerString = implode(";", $externalViewer);
			
			$externalTemplate = $portfolioManager->getExternalTemplate($portfolioId);
			$externalTemplateString = implode(";", $externalTemplate);
			
			$template = $portfolioItem->isTemplate();
			
			$return = array(
				"contextId"			=> $privateRoom->getItemID(),
				"title"				=> $portfolioItem->getTitle(),
				"description"		=> $portfolioItem->getDescription(),
				"externalViewer"	=> $externalViewerString,
				"externalTemplate"	=> $externalTemplateString,
				"template"			=> $template,
				"creator"			=> $fullname,
				"tags"				=> $tags,
				"links"				=> $linkArray,
				"numAnnotations"	=> $portfolioManager->getAnnotationCountForPortfolio($portfolioId)
			);
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionGetPortfolioList() {
			$portfolioId = $this->_data["portfolioId"];
			$itemIdArray = $this->_data["itemIdArray"];
			$row = $this->_data["row"];
			$column = $this->_data["column"];
			
			$portfolioManager = $this->_environment->getPortfolioManager();
			$item = $portfolioManager->getItem($portfolioId);
			
			$itemManager = $this->_environment->getItemManager();
			$currentUser = $this->_environment->getCurrentUser();
			
			$userManager = $this->_environment->getUserManager();
			$userItem = $userManager->getItem($item->getCreatorId());
			$privateRoom = $userItem->getOwnRoom();
			
			$itemIdRubricArray = array();
			foreach ( $itemIdArray as $id )
			{
				$type = $itemManager->getItemType($id);
				$itemIdRubricArray[$type][] = $id;
			}
			
			$itemArray = array();
			foreach ( $itemIdRubricArray as $rubric => $idArray )
			{
				$manager = $this->_environment->getManager($rubric);
				$manager->resetLimits();
				$manager->setIDArrayLimit($idArray);
				$manager->setContextLimit($privateRoom->getItemID());
				$manager->select();
					
				$rubricList = $manager->get();
				$rubricItem = $rubricList->getFirst();
				while ( $rubricItem )
				{
					$moddate = $rubricItem->getModificationDate();
					if ( $item->getCreationDate() <> $item->getModificationDate() and !strstr($moddate,'9999-00-00') )
					{
						$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
					}
					else
					{
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
			
			$annotationIdArray = $portfolioManager->getAnnotationIdsForPortfolioCell($item->getItemID(), $row, $column);
			
			$annotationManager = $this->_environment->getAnnotationManager();
			
			$annotationArray = array();
			$annotationManager->resetLimits();
			$annotationManager->setLinkedItemID($portfolioId);
			$annotationManager->setContextLimit($privateRoom->getItemID());
			$annotationManager->select();
			
			$annotationList = $annotationManager->get();
			$annotationItem = $annotationList->getFirst();
			
			while ( $annotationItem )
			{
				if ( !in_array($annotationItem->getItemID(), $annotationIdArray) )
				{
					$annotationItem = $annotationList->getNext();
					continue;
				}
					
				$moddate = $annotationItem->getModificationDate();
				if ( $item->getCreationDate() <> $item->getModificationDate() and !strstr($moddate,'9999-00-00') )
				{
					$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
				}
				else
				{
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
			
			$this->setSuccessfullDataReturn(array("items" => $itemArray, "annotationItems" => $annotationArray));
			echo $this->_return;
			exit;
		}
		
		public function actionSavePortfolio()
		{
			// get data
			$portfolioId = $this->_data["id"];
			$portfolioTitle = $this->_data["title"];
			$portfolioDescription = $this->_data["description"];
			$portfolioExternalViewer = $this->_data["externalViewer"];
			$template = $this->_data["template"];
			$externalTemplate = $this->_data["externalTemplate"];
			$fromTemplate = $this->_data["fromTemplate"];

			$portfolioManager = $this->_environment->getPortfolioManager();
			
			$currentUser = $this->_environment->getCurrentUser();
			$privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();
			
			$item = null;
			if ( $portfolioId !== "NEW")
			{
				$item = $portfolioManager->getItem($portfolioId);
				
				/*
				// check access rights
				if ( !$item->mayEdit($privateRoomUser) )
				{
					$this->setErrorReturn("000", "insufficent rights", array());
					echo $this->_return;
					exit;
				}
				*/
			}
			
			$editMode = true;
			
			if ( $item === null )
			{
				$item = $portfolioManager->getNewItem();
				$item->setCreationDate(getCurrentDateTimeInMySQL());
				$item->setCreatorItem($privateRoomUser);
				
				$editMode = false;
			}
			
			$item->setTitle($portfolioTitle);
			$item->setDescription($portfolioDescription);
			$item->setModificationDate(getCurrentDateTimeInMySQL());
			$item->setModificatorItem($privateRoomUser);
			
			$externalViewerUserIds = explode(";", trim($portfolioExternalViewer));
			$item->setExternalViewer($externalViewerUserIds);
 
			if($template){
				$item->setTemplate();
			} else {
				$item->unsetTemplate();
			}

			$externalTemplateUserIds = explode(";", trim($externalTemplate));
			$item->setExternalTemplate($externalTemplateUserIds);
			
			$item->save();
			
			if ($editMode == false)
			{
				// create from template
				if ($fromTemplate !== false) {
					// get the template portfolio
					$templatePortfolioItem = $portfolioManager->getItem($fromTemplate);
					$templatePortfolioCreator = $templatePortfolioItem->getCreator();
					$templatePortfolioContext = $templatePortfolioCreator->getOwnRoom();
						
					// create a portfolio tag under "ROOT" for the template in the users "private" context
					$tagManager = $this->_environment->getTagManager();
						
					$privateRoom = $privateRoomUser->getOwnRoom();
					$rootTagItem = $tagManager->getRootTagItemFor($privateRoom->getItemId());
						
					$this->_environment->changeContextToPrivateRoom($privateRoom->getItemId());
						
					$newPortfolioTag = $tagManager->getNewItem();
					$newPortfolioTag->setTitle("Portfolio Import: " . $templatePortfolioItem->getTitle());
					$newPortfolioTag->setContextID($privateRoom->getItemId());
					$newPortfolioTag->setCreatorItem($privateRoomUser);
					$newPortfolioTag->setCreationDate(getCurrentDateTimeInMySQL());
					$newPortfolioTag->setPosition($rootTagItem->getItemID(), $rootTagItem->getChildrenList()->getCount() + 1);
					$newPortfolioTag->save();
						
					// gather template tag information and create new tags for all
					// portfolio template tags under the created one
					$templatePortfolioTags = $portfolioManager->getPortfolioTags($fromTemplate);
					$templateTagIdArray = array();
					$tagMapping = array();
					foreach ($templatePortfolioTags as $templatePortfolioTag) {
						$templateTag = $tagManager->getItem($templatePortfolioTag['t_id']);
						
						$newTag = $tagManager->getNewItem();
						$newTag->setTitle($templateTag->getTitle());
						$newTag->setContextID($privateRoom->getItemId());
						$newTag->setCreatorItem($privateRoomUser);
						$newTag->setCreationDate(getCurrentDateTimeInMySQL());
						$newTag->setPosition($newPortfolioTag->getItemID(), $position);
						$newTag->save();
						
						// add tags to new portfolio
						$portfolioManager->addTagToPortfolio(	$item->getItemID(),
																$newTag->getItemID(),
																$templatePortfolioTag['column'] == "0" ? "row" : "column",
																$templatePortfolioTag['column'] == "0" ? (int) $templatePortfolioTag['row'] : (int) $templatePortfolioTag['column'],
																$templatePortfolioTag['description']);
						
						$templateTagIdArray[] = $templatePortfolioTag['t_id'];
						$tagMapping[$templatePortfolioTag['t_id']] = $newTag->getItemID();
					}
					
					// gather linked cell information
					$linkManager = $this->_environment->getLinkItemManager();
					$links = $linkManager->getALlLinksByTagIDArray($templatePortfolioContext->getItemID(), $templateTagIdArray);
						
					$rubricArray = array();
						
					// structure links by rubric
					foreach ($links as $link) {
						if ($link["first_item_type"] === CS_TAG_TYPE) {
							if (!in_array($link["second_item_id"], $rubricArray[$link["second_item_type"]])) {
								$rubricArray[$link["second_item_type"]][] = $link["second_item_id"];
							}
						} else if($link["second_item_type"] === CS_TAG_TYPE) {
							if (!in_array($link["first_item_id"], $rubricArray[$link["first_item_type"]])) {
								$rubricArray[$link["first_item_type"]][] = $link["first_item_id"];
							}
						}
					}
					
					// copy items
					$linkArray = array();
					foreach ($rubricArray as $rubric => $itemArray) {
						foreach($itemArray as $itemId) {
							$this->_environment->changeContextToPrivateRoom($templatePortfolioContext->getItemId());
							
							$manager = $this->_environment->getManager($rubric);
							$templateItem = $manager->getItem($itemId);
							$templateItemTagList = $templateItem->getTagList();
							
							$this->_environment->changeContextToPrivateRoom($privateRoom->getItemId());
							
							$copyItem = $templateItem->copy();
							
							$templateItemTag = $templateItemTagList->getFirst();
							
							$copyTagArray = array();
							while ($templateItemTag) {
								$templateItemTagId = $templateItemTag->getItemID();
								
								if ($tagMapping[$templateItemTagId]) {
									$copyTagArray[] = $tagMapping[$templateItemTagId];
								}
									
								$templateItemTag = $templateItemTagList->getNext();
							}
							
							$copyItem->setTagListByID($copyTagArray);
							$copyItem->save();
						}
					}
				}
			}
			
			$this->setSuccessfullDataReturn(array("portfolioId" => $item->getItemID()));
			echo $this->_return;
			exit;
		}

		public function actionUnsubscribePortfolio()
		{
			// get data
			$portfolioId = $this->_data["portfolioId"];
			
			$portfolioManager = $this->_environment->getPortfolioManager();
			
			$item = $portfolioManager->getItem($portfolioId);
			
			if ( $item === null )
			{
				$this->setErrorReturn("010", "item not found ", array());
				echo $this->_return;
				exit;
			}

			$currentUser = $this->_environment->getCurrentUser();
			$privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();

			$portfolioManager->removeExternalViewer($portfolioId, $currentUser->getUserID());

			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
			exit;
		}
		
		public function actionDeletePortfolio()
		{
			// get data
			$portfolioId = $this->_data["id"];
			
			$portfolioManager = $this->_environment->getPortfolioManager();
			
			$item = $portfolioManager->getItem($portfolioId);
			
			if ( $item === null )
			{
				$this->setErrorReturn("010", "item not found ", array());
				echo $this->_return;
				exit;
			}
			
			$currentUser = $this->_environment->getCurrentUser();
			$privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();
			
			// check access rights
			/*
			if ( !$item->mayEdit($privateRoomUser) )
			{
				$this->setErrorReturn("000", "insufficent rights", array());
				echo $this->_return;
				exit;
			}
			*/
			
			$portfolioManager->delete($portfolioId);
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
			exit;
		}
		
		public function actionDeletePortfolioTag() {
			// get data
			$portfolioId = $this->_data["portfolioId"];
			$tagId = $this->_data["tagId"];
			
			$portfolioManager = $this->_environment->getPortfolioManager();
				
			$portfolioTags = $portfolioManager->getPortfolioTags($portfolioId);
			
			// get the tag we want to delete
			$deleteTag = null;
			foreach ($portfolioTags as $portfolioTag) {
				if ($portfolioTag["t_id"] == $tagId) {
					$deleteTag = $portfolioTag;
					break;
				}
			}
			
			// determe if this is a row or column tag
			$isRow = false;
			if ($deleteTag["row"] > 0) $isRow = true;
			
			// delete the tag
			$portfolioManager->deletePortfolioTag($portfolioId, $tagId);
			
			// if there are rows or column after this tag, we need to decrease their positions
			foreach ($portfolioTags as $portfolioTag) {
				if ($isRow) {
					if ($portfolioTag["row"] > $deleteTag["row"]) {
						$portfolioManager->updatePortfolioTagPosition($portfolioId, $portfolioTag["t_id"], $portfolioTag["row"] - 1, 0);
					}
				} else {
					if ($portfolioTag["column"] > $deleteTag["column"]) {
						$portfolioManager->updatePortfolioTagPosition($portfolioId, $portfolioTag["t_id"], 0, $portfolioTag["column"] - 1);
					}
				}
			}
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}
		
		public function actionUpdatePortfolioTag() {
			// get data
			$portfolioId = $this->_data["portfolioId"];
			$tagId = $this->_data["tagId"];
			$position = $this->_data["position"];
			$oldTagId = $this->_data["oldTagId"];
			$description = $this->_data["description"];
			
			$portfolioManager = $this->_environment->getPortfolioManager();
			
			$portfolioTags = $portfolioManager->getPortfolioTags($portfolioId);
			
			if ($oldTagId !== null && $oldTagId !== "NEW") {
				// replace the old tag
				
				// check if this tag already exists
				$double = false;
				
				// ignore double, if old tag is new tag
				if ( $oldTagId !== $tagId )
				{
					foreach ($portfolioTags as $tag) {
						if ($tag["t_id"] == $tagId) {
							$double = true;
							break;
						}
					}
				}
				
				if ($double) {
					$this->setErrorReturn("902", "tag already exists", array());
					echo $this->_return;
				} else {
					$portfolioManager->replaceTagForPortfolio($portfolioId, $tagId, $oldTagId, $description);
					
					$this->setSuccessfullDataReturn(array());
					echo $this->_return;
				}
			} else {
				// add tag
				
				// check if this tag already exists
				$double = false;
				foreach ($portfolioTags as $tag) {
					if ($tag["t_id"] == $tagId) {
						$double = true;
						break;
					}
				}
				if ($double) {
					$this->setErrorReturn("902", "tag already exists", array());
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
					
					$portfolioManager->addTagToPortfolio($portfolioId, $tagId, $position, $index, $description);
						
					$this->setSuccessfullDataReturn(array());
					echo $this->_return;
				}
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