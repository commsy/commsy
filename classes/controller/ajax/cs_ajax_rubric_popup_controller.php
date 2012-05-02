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
			// item id
			$this->_item_id = $this->_data['iid'];
			$this->assign('popup', 'item_id', $this->_item_id);
				
			// item
			if($this->_item_id !== null && $this->_item_id !== 'NEW') {
				$item_manager = $this->_environment->getItemManager();
				$type = $item_manager->getItemType($this->_item_id);
				if($type === CS_LABEL_TYPE) {
					$label_manager = $this->_environment->getLabelManager();
					$type = $label_manager->getItem($this->_item_id)->getLabelType();
				}
				$manager = $this->_environment->getManager($type);
				$this->_item = $manager->getItem($this->_item_id);
			}
			
			// new / edit
			if($this->_item === null) {
				$this->assign('popup', 'edit', false);
			} else {
				$this->assign('popup', 'edit', true);
			}
			
			// set Buzzword Information
			if($this->getUtils()->showBuzzwords() === true) {
				$this->assign('popup', 'buzzwords', $this->getBuzzwords(true));
			}
				
			// set Tag Information
			if($this->getUtils()->showTags() === true) {
				$tag_array = $this->getUtils()->getTags();
			
				if($this->_item !== null) {
					$item_tag_list = $this->_item->getTagList();
					$item_tag_id_array = $item_tag_list->getIDArray();
						
					$this->getUtils()->markTags($tag_array, $item_tag_id_array);
				}
			
				$this->assign('popup', 'tags', $tag_array);
			}
			
			/*
			 * if($this->_item !== null) {
					$this->_popup_controller->edit($this->_item_id);
				}
			 */
			
			// call parent
			parent::actiongetHTML();
		}
		
		protected function initPopup() {
			$this->_popup_controller->initPopup($this->_item);
		}
		
		private function getBuzzwords($return_empty) {
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
			$buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
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
	}