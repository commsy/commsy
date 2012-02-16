<?php
	require_once('classes/controller/cs_base_controller.php');

	abstract class cs_room_controller extends cs_base_controller {
		protected $_with_modifying_actions = true;
		protected $_sidebar_configuration = array();
		protected $_command = null;
		protected $_list_command = null;
		protected $_list_attached_ids = array();
		protected $_list_shown_ids = array();
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_sidebar_configuration['active']['buzzwords'] = false;
			$this->_sidebar_configuration['active']['tags'] = false;
			$this->_sidebar_configuration['active']['netnavigation'] = false;
			$this->_sidebar_configuration['hidden']['buzzwords'] = true;
			$this->_sidebar_configuration['hidden']['tags'] = true;
			$this->_sidebar_configuration['hidden']['netnavigation'] = true;
		}

	   protected function getPostData() {
			// get item from url
			if(!empty($_GET['iid'])) {
				$this->_item_id = $_GET['iid'];
			} elseif(!empty($_POST['iid'])) {
				$this->_item_id = $_POST['iid'];
			}
			
			// get command / list_command
			if(isset($_POST['form_data']['option'])) {
				foreach($_POST['form_data']['option'] as $option => $value) {
				   if($option == 'list'){
				      $this->_list_command = $value;
				      if(isset($_POST['form_data']['attach'])) {
				         $this->_list_attached_ids = $_POST['form_data']['attach'];
				         $this->_list_shown_ids = $_POST['form_data']['shown'];
				      }
				      break;
				   } else {
					   $this->_command = $option;
					   break;
				   }
				}
			}
			
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// TODO: implement old commsy check - calling rubrics not set as active
			
			$params = $this->_environment->getCurrentParameterArray();
			if(!empty($params['with_modifying_actions'])) {
				$this->_with_modifying_actions = $params['with_modifying_actions'];
			}
			
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
			if($current_context->isClosed() || $current_user->isOnlyReadUser()) {
				$this->_with_modifying_actions = false;
			}

			// check room context
			if(	!$this->_environment->inProjectRoom() &&
				!$this->_environment->inCommunityRoom() &&
				!$this->_environment->inPrivateRoom() &&
				!$this->environment->inGroupRoom()) {
				die('you are not in room context, so no room template should be processed');
			}

			// rubric information for room navigation
			$this->assign('room', 'rubric_information', $this->getRubricInformation());

			// room information
			$this->assign('room', 'room_information', $this->getRoomInformation());
			
			// sidebar information
			$this->setupSidebarInformation();
		}
		
		private function setupSidebarInformation() {
			$context_item = $this->_environment->getCurrentContextItem();
			
			// buzzwords
			if($context_item->isBuzzwordShowExpanded()) $this->_sidebar_configuration['hidden']['buzzwords'] = false;
			if($this->showBuzzwords()) {
				$this->_sidebar_configuration['active']['buzzwords'] = true;
				$this->assign('room', 'buzzwords', $this->getBuzzwords());
			}
			
			// tags
			if($context_item->isTagsShowExpanded()) $this->_sidebar_configuration['hidden']['tags'] = false;
			if($this->showTags()) {
				$this->_sidebar_configuration['active']['tags'] = true;
				$this->assign('room', 'tags', $this->getTags());
			}
			
			// netnavigation
			if($context_item->isNetnavigationShowExpanded()) $this->_sidebar_configuration['hidden']['netnavigation'] = false;
			if($this->showNetnavigation()) {
				$this->_sidebar_configuration['active']['netnavigation'] = true;
				$this->assign('room', 'netnavigation', $this->getNetnavigation());
			}
			
			$this->assign('room', 'sidebar_configuration', $this->_sidebar_configuration);
		}

		/**
		 * get all rubrics and their configuration encoded in postfixes
		 */
		protected function getRubrics() {
			// get rubric configuration
			$rubric_configuration = $this->_environment->getCurrentContextItem()->getHomeConf();

			$rubrics = array();
			if(!empty($rubric_configuration)) {
				$rubrics = explode(',', $rubric_configuration);
			}

			return $rubrics;
		}
		

		/**
		 * gets information for displaying room rubrics in navigation bar
		 */
		private function getRubricInformation() {
			// init return with home
			$return = array();
			$return[] = array(
				'name'			=> 'home',
				'translate'		=> false,
				'active'		=> $this->_environment->getCurrentModule() == 'home',
				'span_prefix'	=> 'ho');

			// get rubrics
			$rubrics = $this->getRubrics();

			// these prefixes are needed for building up the span id
			$span_lookup = array(
				CS_ANNOUNCEMENT_TYPE	=>	'an',
				CS_DATE_TYPE			=>	'te',
				CS_MATERIAL_TYPE		=>	'ma',
				CS_DISCUSSION_TYPE		=>	'di',
				CS_USER_TYPE			=>	'pe',
				CS_GROUP_TYPE			=>	'gr',
				CS_TODO_TYPE			=>	'au',
				CS_TOPIC_TYPE			=>	'th',
				CS_INSTITUTION_TYPE		=>	'in'
			);

			foreach($rubrics as $rubric) {
				list($suffix, $postfix) = explode('_', $rubric);

				if($postfix !== 'none') {
					$name = '';
					$translate = true;
					if($this->_environment->isPlugin($suffix)) {
						$name = plugin_hook_output($suffix, 'getDisplayName');
						$translate = false;
					} else {
						$name = $suffix;
					}

					if(empty($name)) die('rubric name could not be found');

					// append return
					$return[] = array(	'name'			=> $name,
										'translate'		=> $translate,
										'active'		=> $this->_environment->getCurrentModule() == $name,
										'span_prefix'	=> $span_lookup[$name]);
				}
			}

			return $return;
		}

		/**
		 * get data for room information
		 */
		private function getRoomInformation() {
			$return = array();
			$context_item = $this->_environment->getCurrentContextItem();

			// time spread
			$return['time_spread'] = $context_item->getTimeSpread();

			// active persons
			$time_spread = $context_item->getTimeSpread();
			$return['active_persons'] = round($context_item->getActiveMembers($time_spread) / $context_item->getAllUsers() * 100);

			// new entries
			$return['new_entries'] = $context_item->getNewEntries($time_spread);

			// page impressions
			$return['page_impressions'] = $context_item->getPageImpressions($time_spread);

			//$this->_translator->getMessage('ACTIVITY_ACTIVE_MEMBERS_DESC',$time_spread)
			return $return;
		}

		/**
		 * get data for buzzword portlet
		 */
		protected function getBuzzwords() {
			$return = array();

			$buzzword_manager = $this->_environment->getLabelManager();
			$text_converter = $this->_environment->getTextConverter();
      		$params = $this->_environment->getCurrentParameterArray();

			$buzzword_manager->resetLimits();
			$buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
			$buzzword_manager->setTypeLimit('buzzword');
			$buzzword_manager->setGetCountLinks();
			$buzzword_manager->select();
			$buzzword_list = $buzzword_manager->get();

			$buzzword = $buzzword_list->getFirst();
			while($buzzword) {
				$count = $buzzword->getCountLinks();
				if($count > 0) {
      				if ( isset($params['selbuzzword']) and !empty($params['selbuzzword']) and $buzzword->getItemID() == $params['selbuzzword']){
						$return[] = array(
							'to_item_id'		=> $buzzword->getItemID(),
							'name'				=> $text_converter->text_as_html_short($buzzword->getName()),
							'class_id'			=> $this->getBuzzwordSizeLogarithmic($count, 0, 30, 1, 4),
							'selected_id'		=> $buzzword->getItemID()
						);
      				}else{
						$return[] = array(
							'to_item_id'		=> $buzzword->getItemID(),
							'name'				=> $text_converter->text_as_html_short($buzzword->getName()),
							'class_id'			=> $this->getBuzzwordSizeLogarithmic($count, 0, 30, 1, 4),
							'selected_id'		=> 'no'
						);
      				}
				}

				$buzzword = $buzzword_list->getNext();
			}

			return $return;
		}

		/**
		 * calculates the font size of a buzzword by relevance
		 *
		 * @param int $count
		 * @param int $mincount
		 * @param int $maxcount
		 * @param int $minsize
		 * @param int $maxsize
		 * @param int $tresholds
		 */
		protected function getBuzzwordSizeLogarithmic($count, $mincount = 0, $maxcount = 30, $minsize = 10, $maxsize = 20, $tresholds = 0) {
			if(empty($tresholds)) {
				$tresholds = $maxsize - $minsize;
				$treshold = 1;
			} else {
				$treshold = ($maxsize - $minsize) / ($tresholds - 1);
			}

			$a = $tresholds * log($count - $mincount + 2) / log($maxcount - $mincount + 2) - 1;
			return round($minsize + round($a) * $treshold);
		}

		/**
		 * wrapper for recursive tag call
		 */
		protected function getTags() {
			$tag_manager = $this->_environment->getTagManager();
			$root_item = $tag_manager->getRootTagItem();

			return $this->buildTagArray($root_item);
		}

		/**
		 * this method goes through the tree structure and generates a nested array of information
		 * @param cs_tag_item $item
		 */
		protected function buildTagArray(cs_tag_item $item) {
			$return = array();

			if(isset($item)) {
				$children_list = $item->getChildrenList();

				$item = $children_list->getFirst();
				while($item) {
					// attach to return
					$return[] = array(
						'title'		=> $item->getTitle(),
						'item_id'	=> $item->getItemID(),
						'children'	=> $this->buildTagArray($item)
					);

					$item = $children_list->getNext();
				}
			}

			return $return;
		}

		protected function _getItemChangeStatus($item) {
      		$current_user = $this->_environment->getCurrentUserItem();
      		$translator = $this->_environment->getTranslationObject();
      		if ($current_user->isUser()) {
         		$noticed_manager = $this->_environment->getNoticedManager();
         		$noticed = $noticed_manager->getLatestNoticed($item->getItemID());
         		if ( empty($noticed) ) {
            		$info_text = $translator->getMessage('COMMON_NEW_ENTRY');
         		} elseif ( $noticed['read_date'] < $item->getModificationDate() ) {
            		$info_text = $translator->getMessage('COMMON_CHANGED_ENTRY');
         		} else {
            		$info_text = '';
         		}
         		// Add change info for annotations (TBD)
      		} else {
         		$info_text = '';
      		}
      		$info_text2 = $this->_getItemAnnotationChangeStatus($item);
      		if (!empty($info_text2)){
      			$info_text .= ', ';
      		}
      		return $info_text;
   		}

		protected function _getItemAnnotationChangeStatus($item) {
      		$translator = $this->_environment->getTranslationObject();
      		$current_user = $this->_environment->getCurrentUserItem();
      		if ($current_user->isUser()) {
         		$noticed_manager = $this->_environment->getNoticedManager();
         		$annotation_list = $item->getItemAnnotationList();
         		$anno_item = $annotation_list->getFirst();
         		$new = false;
         		$changed = false;
         		$date = "0000-00-00 00:00:00";
         		while ( $anno_item ) {
            		$noticed = $noticed_manager->getLatestNoticed($anno_item->getItemID());
            		if ( empty($noticed) ) {
               			if ($date < $anno_item->getModificationDate() ) {
                   			$new = true;
                   			$changed = false;
                   			$date = $anno_item->getModificationDate();
               			}
            		} elseif ( $noticed['read_date'] < $anno_item->getModificationDate() ) {
               			if ($date < $anno_item->getModificationDate() ) {
                   			$new = false;
                   			$changed = true;
                   			$date = $anno_item->getModificationDate();
               			}
            		}
            		$anno_item = $annotation_list->getNext();
         		}
         		if ( $new ) {
            		$info_text = $translator->getMessage('COMMON_NEW_ANNOTATION');
         		} elseif ( $changed ) {
            		$info_text = $translator->getMessage('COMMON_CHANGED_ANNOTATION');
         		} else {
            		$info_text = '';
         		}
      		} else {
         		$info_text = '';
      		}
      		return $info_text;
  	 	}
  	 	
		private function showTags() {
			$context_item = $this->_environment->getCurrentContextItem();
			if($context_item->withTags() &&
				( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
	                || $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
	                || $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
	                || $this->_environment->getCurrentModule() == CS_TODO_TYPE
	                || $this->_environment->getCurrentModule() == CS_DATE_TYPE
	                || $this->_environment->getCurrentModule() == 'campus_search'
	                || $this->_environment->getCurrentModule() === 'home')) {
				return true;
			}
			
			return false;
		}
		
		private function showBuzzwords() {
			$context_item = $this->_environment->getCurrentContextItem();
			if($context_item->withBuzzwords() &&
				(	$this->_environment->getCurrentModule() === CS_ANNOUNCEMENT_TYPE ||
					$this->_environment->getCurrentModule() === 'home' ||
					$this->_environment->getCurrentModule() === CS_DATE_TYPE ||
					$this->_environment->getCurrentModule() === CS_MATERIAL_TYPE ||
					$this->_environment->getCurrentModule() === CS_DISCUSSION_TYPE ||
					$this->_environment->getCurrentModule() === CS_TODO_TYPE)) {
				return true;
			}
			
			return false;
		}
		
		protected function showNetnavigation() {
			return false;
		}
	}