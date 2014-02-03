<?php
	require_once('classes/controller/ajax/cs_ajax_popup_controller.php');

	class cs_ajax_rubric_popup_controller extends cs_ajax_popup_controller {
		private $_item_id = null;
		private $_item = null;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}

		public function actiongetHTML() {
            $current_user = $this->_environment->getCurrentUser();
            $privateRoomUser = $current_user->getRelatedPrivateRoomUserItem();

			// item id
			$this->_item_id = $this->_data['iid'];

			$this->assign('popup', 'item_id', $this->_item_id);

			// item
			if ( isset($this->_item_id) && !empty($this->_item_id) && $this->_item_id !== null && $this->_item_id !== 'NEW') {
				$item_manager = $this->_environment->getItemManager();
				$type = $item_manager->getItemType($this->_item_id);
				if($type === CS_LABEL_TYPE) {
					$label_manager = $this->_environment->getLabelManager();
					$label_item = $label_manager->getItem($this->_item_id);
					$type = $label_item->getLabelType();
				}
				$manager = $this->_environment->getManager($type);
				if($type === CS_MATERIAL_TYPE){
				   if (isset($this->_data['version_id'])){
				   		$this->_item = $manager->getItemByVersion($this->_item_id, $this->_data['version_id']);
				   }else{
				   		$this->_item = $manager->getItem($this->_item_id);
				   }
				} else {
				   $this->_item = $manager->getItem($this->_item_id);
				}
			}

			// new / edit
			if($this->_item === null) {
				$this->assign('popup', 'edit', false);
				$this->assign('popup', 'is_owner', true);
			} else {
				$is_owner = false;
				$this->assign('popup', 'edit', true);
                $creator = $this->_item->getCreatorItem();

				if($type == CS_USER_TYPE) {
                	if ($current_user->getItemID() == $this->_item->getItemID() || $current_user->isModerator()) {
                		$is_owner = true;
                	}
                } else {
                	if (
                		( isset($creator) && $current_user->getItemID() == $creator->getItemID() ) ||
                		( isset($creator) && isset($privateRoomUser) && $privateRoomUser->getItemID() == $creator->getItemID() ) ||	
                		$current_user->isModerator()
                		)
                	{
                		$is_owner = true;
                	}
                }

				$this->assign('popup','is_owner', $is_owner);
 			}

			// set Buzzword Information
			if($this->getUtils()->showBuzzwords() === true || $this->_data["contextId"] || $this->_data["roomId"]) {
				if ($this->_data["contextId"]) {
					$this->assign('popup', 'buzzwords', $this->getBuzzwords(true, $this->_data["contextId"]));
				} else {
					$this->assign('popup', 'buzzwords', $this->getBuzzwords(true));
				}
			}

			// set Tag Information
			if($this->getUtils()->showTags() === true || $this->_data["contextId"] || $this->_data["roomId"]) {
				$this->assign('popup', 'tags', true);
				/*
				$tag_array = $this->getUtils()->getTags();

				if($this->_item !== null) {
					$item_tag_list = $this->_item->getTagList();
					$item_tag_id_array = $item_tag_list->getIDArray();

					$this->getUtils()->markTags($tag_array, $item_tag_id_array);
				}

				$this->assign('popup', 'tags', $tag_array);
				*/
			}
			
			if($this->_data["contextId"] || $this->_data["roomId"]) {
				$this->assign("popup", "overflow", true);
			} else {
				$this->assign("popup", "overflow", false);
			}

			// set netnavigation information
			if($this->getUtils()->showNetnavigation() === true) {
				if($this->_item !== null) {
					if($this->_item->getItemType()== CS_USER_TYPE){
						$this->assign('popup', 'netnavigation', $this->getUtils()->getNetnavigationForUser($this->_item));
					}else{
						$this->assign('popup', 'netnavigation', $this->getUtils()->getNetnavigation($this->_item));
					}
				}
			}

			// call parent
			parent::actiongetHTML();
		}

		protected function initPopup() {
			$this->_popup_controller->initPopup($this->_item, $this->_data);
		}

		private function getBuzzwords($return_empty, $contextId = null) {
			$return = array();

			$buzzword_manager = $this->_environment->getLabelManager();
			$text_converter = $this->_environment->getTextConverter();

			$item_id_array = array();
			if($this->_item !== null) {
				$item_buzzword_list = $this->_item->getBuzzwordList();

				$buzzword = $item_buzzword_list->getFirst();
				while($buzzword) {

					$item_id_array[] = $buzzword->getItemID();
					$buzzword = $item_buzzword_list->getNext();
				}
			}

			$buzzword_manager->resetLimits();
			if ($contextId) {
				$buzzword_manager->setContextLimit($contextId);
			} else {
				$buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
			}
			$buzzword_manager->setTypeLimit('buzzword');
			$buzzword_manager->setGetCountLinks();
			$buzzword_manager->select();
			$buzzword_list = $buzzword_manager->get();

			$buzzword = $buzzword_list->getFirst();
			while($buzzword) {
				$count = $buzzword->getCountLinks();
				if($count > 0 || $return_empty) {
					$return[] = array(
							'item_id'			=> $buzzword->getItemID(),
							'name'				=> $text_converter->text_as_html_short($buzzword->getName()),
							'assigned'			=> in_array($buzzword->getItemID(), $item_id_array)
					);
				}

				$buzzword = $buzzword_list->getNext();
			}

			return $return;
		}

		protected function cleanup_session($current_iid) {
			$environment = $this->_environment;
			$session = $this->_environment->getSessionItem();

			$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
			$session->unsetValue($environment->getCurrentModule().'_add_tags');
			$session->unsetValue($environment->getCurrentModule().'_add_files');
			$session->unsetValue($current_iid.'_post_vars');
		}

	}