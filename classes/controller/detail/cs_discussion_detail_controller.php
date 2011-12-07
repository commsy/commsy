<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_discussion_detail_controller extends cs_detail_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'discussion_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_DISCUSSION_TYPE);
		}
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionDetail() {
			// try to set the item
			$this->setItem();
			
			$this->setupInformation();
			
			$session = $this->_environment->getSessionItem();
			if(isset($_GET['export_to_wiki'])){
		         $wiki_manager = $this->_environment->getWikiManager();
		         //$wiki_manager->exportItemToWiki($current_item_iid,CS_DISCUSSION_TYPE);
		         global $c_use_soap_for_wiki;
		         if(!$c_use_soap_for_wiki){
		            $wiki_manager->exportItemToWiki($current_item_iid,CS_DISCUSSION_TYPE);
		         } else {
		            $wiki_manager->exportItemToWiki_soap($current_item_iid,CS_DISCUSSION_TYPE);
		         }
		         $params = $this->_environment->getCurrentParameterArray();
		         unset($params['export_to_wiki']);
		         redirect($this->_environment->getCurrentContextID(),CS_DISCUSSION_TYPE, 'detail', $params);
		      }
		
		      if(isset($_GET['remove_from_wiki'])){
		         $wiki_manager = $this->_environment->getWikiManager();
		         global $c_use_soap_for_wiki;
		         if($c_use_soap_for_wiki){
		            $wiki_manager->removeItemFromWiki_soap($current_item_iid,CS_DISCUSSION_TYPE);
		         }
		         $params = $this->_environment->getCurrentParameterArray();
		         unset($params['remove_from_wiki']);
		         redirect($this->_environment->getCurrentContextID(),CS_DISCUSSION_TYPE, 'detail', $params);
		      }
		
		      // Get clipboard
		      if ( $session->issetValue('discussion_clipboard') ) {
		         $clipboard_id_array = $session->getValue('discussion_clipboard');
		      } else {
		         $clipboard_id_array = array();
		      }
		
		      // Copy to clipboard
		      if ( isset($_GET['add_to_discussion_clipboard'])
		           and !in_array($current_item_id, $clipboard_id_array) ) {
		         $clipboard_id_array[] = $current_item_id;
		         $session->setValue('discussion_clipboard', $clipboard_id_array);
		      }
			
			
			$this->assign('detail', 'content', $this->getDetailContent());
		}
		
		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/
		
		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();
			
			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_discussion_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_discussion_index_ids'));
			}
		}
		
		protected function getDetailContent() {
			$return = array(
				'discussion'		=> $this->getDiscussionContent(),
				'disc_articles'		=> array()
			);
			
			return $return;
		}
		
		private function getDiscussionContent() {
			$return = array();
			
			// append return
			$return = array(
				'title'		=> $this->_item->getTitle(),
				'creator'	=> $this->_item->getCreatorItem()->getFullName()
			);
			
			return $return;
		}
	}