<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_delete_controller implements cs_rubric_popup_controller {
    private $_environment = null;
    private $_popup_controller = null;

    /**
     * constructor
     */
    public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
        $this->_environment = $environment;
        $this->_popup_controller = $popup_controller;
    }

    public function initPopup($item, $data) {
		if($item->getType() === CS_DATE_TYPE) {
		    $recurrance_id = $item->getRecurrenceId();
		    if(!empty($recurrance_id)){
			   $this->_popup_controller->assign("popup", "recurrence", true);
		    }
		}
    }

    public function save($form_data, $additional = array()) {
    	$item_id = $form_data["iid"];
    	$delType = $additional["delType"];
    	$delVersion = $additional["delVersion"];
    	
    	if(empty($item_id)) {
    		// TODO: throw exception
    		exit;
    	}
    	
    	if($this->checkRights($item_id) === true) {
    		switch($delType) {
    			case "section":
    				$section_manager = $this->_environment->getSectionManager();
    				$section_item = $section_manager->getItem($item_id);
    				$section_item->deleteVersion();
    				
    				$material_item = $section_item->getLinkedItem();
    				$material_item->setModificationDate(getCurrentDateTimeInMySQL());
    				$material_item->save();
    				
    				$this->_popup_controller->setSuccessfullDataReturn(array("redirectToIndex" => false, "item_id" => $material_item->getItemID()));
    				
    				break;
    			
    			case "annotation":
    				$annotation_manager = $this->_environment->getAnnotationManager();
    				$annotation_item = $annotation_manager->getItem($item_id);
    				
    				if (isset($additional["portfolioId"])) {
    					$portfolioManager = $this->_environment->getPortfolioManager();
    					$portfolioManager->deletePortfolioAnnotation($additional["portfolioId"], $annotation_item->getItemID());
    				}
    				
    				$annotation_item->delete();
    				
    				$this->_popup_controller->setSuccessfullDataReturn(array("redirectToIndex" => false, "item_id" => $annotation_item->getLinkedItemID()));
    				break;
    			
    			case "discarticle":
    				$discarticle_manager = $this->_environment->getDiscussionArticlesManager();
    				$discarticle_item = $discarticle_manager->getItem($item_id);
    				
    				$discussion_item = $discarticle_item->getLinkedItem();
    				$disc_type = $discussion_item->getDiscussionType();
    				$delete_discussion = false;
    				if($disc_type == "threaded") {
    					$position = $discarticle_item->getPosition();
    					if($position == 1) $delete_discussion = true;
    				}
    				
    				// delete all childs too
    				if($disc_type == "threaded") {
    					$position = $discarticle_item->getPosition();
    					$position_length = mb_strlen($position);
    					
    					// find discarticles: delete children, rename descendants
    					$discussion_articles = $discarticle_manager->getAllArticlesForItem($discussion_item);
    					
    					$discussion_article = $discussion_articles->getFirst();
    					while($discussion_article) {
    						$discussion_article_position = $discussion_article->getPosition();
    						$discussion_article_position_length = mb_strlen($discussion_article_position);
    						
    						// children
    						if($discussion_article_position_length > $position_length && mb_substr($discussion_article_position, 0, $position_length) == $position) {
    							// delete discarticles
    							$discussion_article->delete();
    						}
    						
    						// descendants
    						elseif($discussion_article_position_length >= $position_length && mb_substr($position, $position_length-4, 4) < mb_substr($discussion_article_position, $position_length-4, 4)) {
    							// rename elements
    							$discussion_article_new_position =
	    							mb_substr($discussion_article_position, 0, $position_length-4) .
	    							((string) ((int) mb_substr($discussion_article_position, $position_length-4, 4))-1) .
	    							mb_substr($discussion_article_position, $position_length);
    							
    							$discussion_article->setPosition($discussion_article_new_position);
    							
    							// don't save modifier and modification date of article item
    							$discussion_article->saveWithoutChangingModificationInformation();
    						}
    						
    						$discussion_article = $discussion_articles->getNext();
    					}
    				}
    				
    				$discarticle_item->delete();
    				
    				$discussion_item->setModificationDate(getCurrentDateTimeInMySQL());
    				$discussion_item->save();
    				
    				if($delete_discussion) {
    					$discussion_item->delete();
    					$this->_popup_controller->setSuccessfullDataReturn(array("redirectToIndex" => true));
    				} else {
    					$this->_popup_controller->setSuccessfullDataReturn(array("redirectToIndex" => false, "item_id" => $discussion_item->getItemID()));
    				}
    				break;
    			
    			case "step":
    				$step_manager = $this->_environment->getStepManager();
    				$step_item = $step_manager->getItem($item_id);
    				$step_item->delete();
    				
    				$todo_item = $step_item->getLinkedItem();
    				$todo_item->setModificationDate(getCurrentDateTimeInMySQL());
    				$todo_item->save();
    				
    				$this->_popup_controller->setSuccessfullDataReturn(array("redirectToIndex" => false, "item_id" => $todo_item->getItemID()));
    				break;
    			
    			case "material":
    				if (isset($additional['version_id'])){
    				   $material_manager = $this->_environment->getMaterialManager();
    				   $item = $material_manager->getItemByVersion($item_id, $additional['version_id']);
    				   $item->delete();
                       $this->deleteFromClipboard("material", $item_id);
    				   $this->_popup_controller->setSuccessfullDataReturn(array("redirectToIndex" => false, "item_id" => $item_id));
    				} else {
    					$material_manager = $this->_environment->getMaterialManager();
    					$material_version_list = $material_manager->getVersionList($item_id);
    					$item = $material_version_list->getFirst();
    					$item->delete(CS_ALL);
                        $this->deleteFromClipboard("material", $item_id);
    					$this->_popup_controller->setSuccessfullDataReturn(array("redirectToIndex" => true));
    				}

    				break;
    			
    			case "date_recurrence":
    				$dates_manager = $this->_environment->getDatesManager();
    				$dates_manager->resetLimits();
    				
    				$date_item = $dates_manager->getItem($item_id);
    				$recurrence_id = $date_item->getRecurrenceId();
    				$dates_manager->setRecurrenceLimit($recurrence_id);
    				
    				$dates_manager->setWithoutDateModeLimit();
    				$dates_manager->select();
    				$dates_list = $dates_manager->get();
    				
    				$temp_date = $dates_list->getFirst();
    				while($temp_date) {
    					$temp_date->delete();
    					
    					$temp_date = $dates_list->getNext();
    				}
    				
    				$this->_popup_controller->setSuccessfullDataReturn(array("redirectToIndex" => true));
    				break;
    			
    			default:
    				$manager = $this->_environment->getManager($delType);
    				$item = $manager->getItem($item_id);
    				$item->delete();
                    $this->deleteFromClipboard($delType, $item_id);
    				
    				$this->_popup_controller->setSuccessfullDataReturn(array("redirectToIndex" => true));
    				break;
    		}
    	} else {
    		$this->_popup_controller->setErrorReturn("000", "insufficent rights");
    	}
    }

    private function deleteFromClipboard($rubric, $itemId) {
        $sessionItem = $this->_environment->getSessionItem();

        // are there items in clipboard for this rubric?
        if ($sessionItem->issetValue($rubric . "_clipboard")) {
            $copyIDs = $sessionItem->getValue($rubric . "_clipboard");

            // search for id and remove
            if ($key = array_search($itemId, $copyIDs)) {
                array_splice($copyIDs, $key, 1);
            }

            // modify and store session
            $sessionItem->setValue($rubric . "_clipboard", $copyIDs);
            $this->_environment->getSessionManager()->save($sessionItem);
        }
    }
    
	private function checkRights($item_id) {
        $item_manager = $this->_environment->getItemManager();
        $type = $item_manager->getItemType($item_id);

        $manager = $this->_environment->getManager($type);
        $item = $manager->getItem($item_id);

        $currentUserItem = $this->_environment->getCurrentUserItem();
        $privateRoomUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();

        $mayDelete = $item->mayEdit($currentUserItem) || $item->mayEdit($privateRoomUserItem);

        if ( $mayDelete === true )
        {
            return true;
        }
        else
        {
            // check for sub-types
            switch ( $type )
            {
                case CS_SECTION_TYPE:
                case CS_STEP_TYPE:
                    $linkedItem = $item->getLinkedItem();
                    return $linkedItem->mayEdit($currentUserItem) || $linkedItem->mayEdit($privateRoomUserItem);
                    break;
            }
        }

        return false;
    } 

    public function getFieldInformation($sub = '') {
			return array();
    }

	public function cleanup_session($current_iid) {
	}
}