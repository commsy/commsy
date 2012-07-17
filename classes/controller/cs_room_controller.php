<?php
	require_once('classes/controller/cs_base_controller.php');

	abstract class cs_room_controller extends cs_base_controller {
		protected $_with_modifying_actions = true;
		protected $_sidebar_configuration = array();
		protected $_command = null;
		protected $_list_command = null;
		protected $_list_command_confirm = null;
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
			if(isset($_POST['form_data']['confirm'])) {
			   foreach($_POST['form_data']['confirm'] as $option => $value) {
			      $this->_list_command_confirm = $option;
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
				!$this->_environment->inGroupRoom()) {
				die('you are not in room context, so no room template should be processed');
			}

			// rubric information for room navigation
			$this->assign('room', 'rubric_information', $this->getRubricInformation());

			// room information
			$this->assign('room', 'room_information', $this->getRoomInformation());

			// sidebar information
			$this->setupSidebarInformation();

			// addon information
			$this->assign('room', 'addon_information', $this->getAddonInformation());

			// second navigation information
			$this->assign('room', 'second_navigation', $this->getSecondNavigationInformation());

			// set assessment status
			$this->assign('room', 'assessment', $current_context->isAssessmentActive());

			// set workflow status
			$this->assign('room', 'workflow', $current_context->withWorkflow());

			$this->assign('room', 'usage_info_content', $this->getUsageInfoContent());
		}


		private function getUsageInfoContent(){
      		$current_context = $this->_environment->getCurrentContextItem();
			$return_array = array();
			$return_array['show'] = false;

	        $act_rubric = $this->_environment->getCurrentModule();
	        $info_text = $current_context->getUsageInfoTextForRubric($act_rubric);
	        if (!empty($info_text) ){
				$return_array['title'] = $current_context->getUsageInfoHeaderForRubric($act_rubric);
				$return_array['content'] = $current_context->getUsageInfoTextForRubric($act_rubric);
				$return_array['show'] = true;
			}
			return $return_array;
		}


		private function getAddonInformation() {
			$return = array(
				'wiki'		=> array(
					'active'	=> false
				),
				'chat'		=> array(
					'active'	=> false
				),
				'wordpress'	=> array(
					'active'	=> false
				),
				'rss'		=> array(
					'active'	=> false
				),
				'rows'		=> 0
			);

			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
			$count = 0;

			// wiki
			if($current_context->showWikiLink() && $current_context->existWiki() && $current_context->issetWikiHomeLink()) {
					global $c_pmwiki_path_url;

					$count++;
					$return['wiki']['active'] = true;
					$return['wiki']['title'] = $current_context->getWikiTitle();
					$return['wiki']['path'] = $c_pmwiki_path_url;
					$return['wiki']['portal_id'] = $this->_environment->getCurrentPortalID();
					$return['wiki']['item_id'] = $current_context->getItemID();

					$url_session_id = '';
					if($current_context->withWikiUseCommSyLogin()) {
						$session_item = $this->_environment->getSessionItem();
						$url_session_id = '?commsy_session_id=' . $session_item->getSessionID();
						unset($session_item);
					}
					$return['wiki']['session'] = $url_session_id;
			}

			// chat
			if($current_context->showChatLink()) {
				global $c_etchat_enable;
				if(!empty($c_etchat_enable) && $c_etchat_enable) {
					if(isset($current_user) && $current_user->isReallyGuest()) {
						// TODO:

						/*
						 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $image = '<img src="images/commsyicons_msie6/etchat_grey_home.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'" title="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
                  } else {
                     $image = '<img src="images/etchat_grey_home.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'" title="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
                  }
                  $html .= ' '.$image;
                  // TBD: icon ausgrauen
						 */
					} else {
						$count++;
						$return['chat']['active'] = true;
					}
				}
			}

			// wordpress
			if($current_context->showWordpressLink() && $current_context->existWordpress() && $current_context->issetWordpressHomeLink()) {
				global $c_wordpress_path_url;

				$count++;
				$return['wordpress']['active'] = true;
				$return['wordpress']['title'] = $current_context->getWordpressTitle();
				$return['wordpress']['path'] = $c_wordpress_path_url;
				$return['wordpress']['item_id'] = $current_context->getItemID();

				$url_session_id = '';
				if($current_context->withWordpressUseCommSyLogin()) {
					$session_item = $this->_environment->getSessionItem();
					$url_session_id = '?commsy_session_id=' . $session_item->getSessionID();
					unset($session_item);
				}
				$return['wordpress']['session'] = $url_session_id;
			}

			// plugins for moderators and users
			// TODO: $html .= plugin_hook_output_all('getExtraActionAsHTML',array(),LF).LF;

			// rss
			$show_rss_link = false;
			if($current_context->isLocked() || $current_context->isClosed()) {
				// do nothing
			} elseif($current_context->isOpenForGuests()) {
				$show_rss_link = true;
			} elseif($current_user->isUser()) {
				$show_rss_link = true;
			}

			$hash_string = '';
			if(!$current_context->isOpenForGuests() && $current_user->isUser()) {
				$hash_manager = $this->_environment->getHashManager();
				$hash_string = '&amp;hid=' . $hash_manager->getRSSHashForUser($current_user->getItemID());
			}

			if(!$current_context->isRSSOn()) {
				$show_rss_link = false;
			}

			if($show_rss_link) {
				$count++;
				$return['rss']['active'] = true;
				$return['rss']['item_id'] = $current_context->getItemID();
				$return['rss']['hash'] = $hash_string;
			}

			$return['rows'] = ceil($count / 2);

			return $return;
		}

		private function setupSidebarInformation() {
			$context_item = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();

			// buzzwords
			if($context_item->isBuzzwordShowExpanded()) $this->_sidebar_configuration['hidden']['buzzwords'] = false;
			if($this->getUtils()->showBuzzwords()) {
				$this->_sidebar_configuration['active']['buzzwords'] = true;
				$this->_sidebar_configuration['editable']['buzzwords'] = false;

				if($current_user->isUser() && $this->_with_modifying_actions) {
					$this->_sidebar_configuration['editable']['buzzwords'] = true;
				}

				$this->assign('room', 'buzzwords', $this->getBuzzwords());
			}

			// tags
			if($context_item->isTagsShowExpanded()) $this->_sidebar_configuration['hidden']['tags'] = false;
			if($this->getUtils()->showTags()) {
				$this->_sidebar_configuration['active']['tags'] = true;
				$this->_sidebar_configuration['editable']['tags'] = false;

				if($current_user->isUser() && $this->_with_modifying_actions && ($context_item->isTagEditedByAll() || $current_user->isModerator())) {
					$this->_sidebar_configuration['editable']['tags'] = true;
				}
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

		protected function isPerspective($rubric) {
			$in_array = in_array($rubric, array(CS_GROUP_TYPE, CS_TOPIC_TYPE, CS_INSTITUTION_TYPE));
			if($rubric === CS_INSTITUTION_TYPE) {
				$context = $this->_environment->getCurrentContextItem();
				$in_array = $context->withRubric(CS_INSTITUTION_TYPE);
			}

			return $in_array;
		}

		protected function getItemModificator($item) {
			$modificator = $item->getModificatorItem();
			$translator = $this->_environment->getTranslationObject();
			$converter = $this->_environment->getTextConverter();

			if(isset($modificator) && !$modificator->isDeleted()) {
				$current_user = $this->_environment->getCurrentUserItem();

				if($current_user->isGuest() && $modificator->isVisibleForLoggedIn()) {
					$fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
				} else {
					$fullname = $modificator->getFullName();
				}
			} else {
				$fullname = $translator->getMessage('COMMON_DELETED_USER');
			}

			return $converter->text_as_html_short($fullname);
		}

		private function getSecondNavigationInformation() {
			// configuration
			$return['config'] = array(
				'access'		=> false,
				'active'		=> false,
				'span_prefix'	=> 'co'
			);

			// access
			$current_user = $this->_environment->getCurrentUser();
			if($current_user->isModerator() && !$current_user->isOnlyReadUser()) $return['config']['access'] = true;

			// active
			if($this->_environment->getCurrentModule() === 'configuration') $return['config']['active'] = true;

			/*
			* this method provides information, if user rubric is not active,
			* needed for editing profil settings for this room
			*/
			$return['profil'] = array(
				'access'		=> false,
				'active'		=> false,
				'span_prefix'	=> 'spe'
			);

			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();

			// my profile(if user rubric is not active)
			$available_rubrics = $current_context->getAvailableRubrics();
			if(!in_array('user', $available_rubrics)) {
				// user rubric is not active, so add link here
				if(!$current_context->isOpenForGuests() && $current_user->isUser() && $this->_with_modifying_actions) {
					$return['profil']['access'] = true;
					$return['profil']['item_id'] = $current_user->getItemID();
				}
			}

			// active
			if($this->_environment->getCurrentModule() === 'user') $return['profil']['active'] = true;

			return $return;
		}

		/**
		 * gets information for displaying room rubrics in navigation bar
		 */
		protected function getRubricInformation() {
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
				CS_PROJECT_TYPE			=>	'pr',
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
			$time_spread = $context_item->getTimeSpread();

			// time spread
			$return['time_spread'] = $time_spread;

			// active persons
			$active = $context_item->getActiveMembers($time_spread);
			$return['active_persons'] = $active;
			$return['all_persons'] = $context_item->getAllUsers();

			// new entries
			$return['new_entries'] = $context_item->getNewEntries($time_spread);

			// page impressions
			$return['page_impressions'] = $context_item->getPageImpressions($time_spread);

			// room name
			$return['room_name'] = $context_item->getTitle();

			// portal name
			$return['portal_name'] = $this->_environment->getCurrentPortalItem()->getTitle();

			return $return;
		}

		protected function getRubricsWithFiles() {
			// an array of all rubrics, containing files
			$file_rubric_array = array();
			$file_rubric_array[] = CS_DISCUSSION_TYPE;
			$file_rubric_array[] = CS_MATERIAL_TYPE;
			$file_rubric_array[] = CS_DATE_TYPE;
			$file_rubric_array[] = CS_ANNOUNCEMENT_TYPE;
			$file_rubric_array[] = CS_TODO_TYPE;

			return $file_rubric_array;
		}

		protected function getWorkflowInformation($item) {
			$return = array(
				'light'		=> '',
				'title'		=> '',
				'show'		=> true
			);

			$current_context = $this->_environment->getCurrentContextItem();
			$translator = $this->_environment->getTranslationObject();

			if($current_context->withWorkflow()) {
				switch($item->getWorkflowTrafficLight()) {
					case '3_none':
						$return['show'] = false;
						break;

					case '0_green':
						$return['light'] = 'green';
						$return['title'] = $translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT');

						if($current_context->getWorkflowTrafficLightTextGreen() != '') {
							$return['title'] = $current_context->getWorkflowTrafficLightTextGreen();
						}
						break;

					case '1_yellow':
						$return['light'] = 'yellow';
						$return['title'] = $translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT');

						if($current_context->getWorkflowTrafficLightTextYellow() != '') {
							$return['title'] = $current_context->getWorkflowTrafficLightTextYellow();
						}
						break;

					case '2_red':
						$return['light'] = 'red';
						$return['title'] = $translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT');

						if($current_context->getWorkflowTrafficLightTextRed() != '') {
							$return['title'] = $current_context->getWorkflowTrafficLightTextRed();
						}
						break;

					default:
						$return['show'] = false;
						break;
				}
			}

			return $return;
		}

		/**
		 * get data for buzzword portlet
		 */
		protected function getBuzzwords() {
			return $this->getUtils()->getBuzzwords();
		}

		/**
		 * wrapper for recursive tag call
		 */
		protected function getTags() {
			return $this->getUtils()->getTags();
		}

		protected function _getItemChangeStatus($item) {
      		$current_user = $this->_environment->getCurrentUserItem();
      		$translator = $this->_environment->getTranslationObject();
      		$info_text = array();
      		$info_text['show_info'] = false;
      		if ($current_user->isUser()) {
         		$noticed_manager = $this->_environment->getNoticedManager();
         		$noticed = $noticed_manager->getLatestNoticed($item->getItemID());
         		if ( empty($noticed) ) {
            		$info_text['item_info'] = $translator->getMessage('COMMON_NEW_ENTRY');
         			$info_text['show_info'] = true;
         			$info_text['status'] = 'new';
         		} elseif ( $noticed['read_date'] < $item->getModificationDate() ) {
            		$info_text['item_info'] = $translator->getMessage('COMMON_CHANGED_ENTRY');
         			$info_text['show_info'] = true;
         			$info_text['status'] = 'modified';
         		} else {
            		$info_text['item_info'] = '';
         		}
         		// Add change info for annotations (TBD)
      		} else {
         		$info_text['item_info'] = '';
      		}
      		if ($item->getType() == CS_MATERIAL_TYPE){
      			$info_text['section_info'] = $this->_getItemSectionChangeStatus($item);
      		}
      		if ($item->getType() == CS_TODO_TYPE){
      			$info_text['step_info'] = $this->_getItemStepChangeStatus($item);
      		}
      		if ($item->getType() == CS_DISCUSSION_TYPE){
      			$info_text['article_info'] = $this->_getItemArticleChangeStatus($item);
      		}
      		$info_text['annotation_info'] = $this->_getItemAnnotationChangeStatus($item);
            if (!empty($info_text['annotation_info']['count_new']) or !empty($info_text['annotation_info']['count_changed'])){
            	$info_text['show_info'] = true;
            }
      		return $info_text;
   		}


		protected function _getItemArticleChangeStatus($item) {
      		$translator = $this->_environment->getTranslationObject();
      		$current_user = $this->_environment->getCurrentUserItem();
      		$info_text = array();
      		$info_text['count_new'] = 0;
      		$info_text['count_changed'] = 0;
      		if ($current_user->isUser()) {
         		$noticed_manager = $this->_environment->getNoticedManager();
         		$article_list = $item->getAllArticles();
         		$article_item = $article_list->getFirst();
         		$new = false;
         		$changed = false;
         		$date = "0000-00-00 00:00:00";
         		while ( $article_item ) {
            		$noticed = $noticed_manager->getLatestNoticed($article_item->getItemID());
            		$temp_array = array();
            		if ( empty($noticed) ) {
               			if ($date < $article_item->getModificationDate() ) {
                   			$info_text['count_new']++;
                   			$temp_array['iid'] = $article_item->getItemID();
                   			$temp_array['date'] = $article_item->getModificationDate();
                   			$temp_array['date'] = $this->_environment->getTranslationObject()->getDateInLang($article_item->getModificationDate());
                   			$temp_array['title'] = $article_item->getTitle();
                    		$temp_array['ref_iid'] = $article_item->getDiscussionID();
                    		$info_text['article_new_items'][] = $temp_array;
               			}
            		} elseif ( $noticed['read_date'] < $article_item->getModificationDate() ) {
               			if ($date < $article_item->getModificationDate() ) {
                   			$info_text['count_changed']++;
                   			$temp_array['iid'] = $article_item->getItemID();
                   			$temp_array['date'] = $this->_environment->getTranslationObject()->getDateInLang($article_item->getModificationDate());
                   			$temp_array['modificator'] = $article_item->getModificatorItem()->getFullname();
                   			$temp_array['title'] = $article_item->getTitle();
                    		$temp_array['ref_iid'] = $article_item->getDiscussionID();
                    		$info_text['article_changed_items'][] = $temp_array;
                			}
            		}
            		$article_item = $article_list->getNext();
         		}
      		}
      		return $info_text;
  	 	}


		protected function _getItemSectionChangeStatus($item) {
      		$translator = $this->_environment->getTranslationObject();
      		$current_user = $this->_environment->getCurrentUserItem();
      		$info_text = array();
      		$info_text['count_new'] = 0;
      		$info_text['count_changed'] = 0;
      		if ($current_user->isUser()) {
         		$noticed_manager = $this->_environment->getNoticedManager();
         		$section_list = $item->getSectionList();
         		$section_item = $section_list->getFirst();
         		$new = false;
         		$changed = false;
         		$date = "0000-00-00 00:00:00";
         		while ( $section_item ) {
            		$noticed = $noticed_manager->getLatestNoticed($section_item->getItemID());
            		$temp_array = array();
            		if ( empty($noticed) ) {
               			if ($date < $section_item->getModificationDate() ) {
                   			$info_text['count_new']++;
                   			$temp_array['iid'] = $section_item->getItemID();
                   			$temp_array['date'] = $section_item->getModificationDate();
                   			$temp_array['date'] = $this->_environment->getTranslationObject()->getDateInLang($section_item->getModificationDate());
                   			$temp_array['title'] = $section_item->getTitle();
                    		$temp_array['ref_iid'] = $section_item->getLinkedItemID();
                    		$info_text['section_new_items'][] = $temp_array;
               			}
            		} elseif ( $noticed['read_date'] < $section_item->getModificationDate() ) {
               			if ($date < $section_item->getModificationDate() ) {
                   			$info_text['count_changed']++;
                   			$temp_array['iid'] = $section_item->getItemID();
                   			$temp_array['date'] = $this->_environment->getTranslationObject()->getDateInLang($section_item->getModificationDate());
                   			$temp_array['modificator'] = $section_item->getModificatorItem()->getFullname();
                   			$temp_array['title'] = $section_item->getTitle();
                    		$temp_array['ref_iid'] = $section_item->getLinkedItemID();
                    		$info_text['section_changed_items'][] = $temp_array;
                			}
            		}
            		$section_item = $section_list->getNext();
         		}
      		}
      		return $info_text;
  	 	}

		protected function _getItemStepChangeStatus($item) {
      		$translator = $this->_environment->getTranslationObject();
      		$current_user = $this->_environment->getCurrentUserItem();
      		$info_text = array();
      		$info_text['count_new'] = 0;
      		$info_text['count_changed'] = 0;
      		if ($current_user->isUser()) {
         		$noticed_manager = $this->_environment->getNoticedManager();
         		$step_list = $item->getStepItemList();
         		$step_item = $step_list->getFirst();
         		$new = false;
         		$changed = false;
         		$date = "0000-00-00 00:00:00";
         		while ( $step_item ) {
            		$noticed = $noticed_manager->getLatestNoticed($step_item->getItemID());
            		$temp_array = array();
            		if ( empty($noticed) ) {
               			if ($date < $step_item->getModificationDate() ) {
                   			$info_text['count_new']++;
                   			$temp_array['iid'] = $step_item->getItemID();
                   			$temp_array['date'] = $step_item->getModificationDate();
                   			$temp_array['date'] = $this->_environment->getTranslationObject()->getDateInLang($step_item->getModificationDate());
                   			$temp_array['title'] = $step_item->getTitle();
                    		$temp_array['ref_iid'] = $step_item->getTodoID();
                    		$info_text['step_new_items'][] = $temp_array;
               			}
            		} elseif ( $noticed['read_date'] < $step_item->getModificationDate() ) {
               			if ($date < $step_item->getModificationDate() ) {
                   			$info_text['count_changed']++;
                   			$temp_array['iid'] = $step_item->getItemID();
                   			$temp_array['date'] = $this->_environment->getTranslationObject()->getDateInLang($step_item->getModificationDate());
                   			$temp_array['modificator'] = $step_item->getModificatorItem()->getFullname();
                   			$temp_array['title'] = $step_item->getTitle();
                    		$temp_array['ref_iid'] = $step_item->getTodoID();
                    		$info_text['step_changed_items'][] = $temp_array;
                			}
            		}
            		$step_item = $step_list->getNext();
         		}
      		}
      		return $info_text;
  	 	}


		protected function _getItemAnnotationChangeStatus($item) {
      		$translator = $this->_environment->getTranslationObject();
      		$current_user = $this->_environment->getCurrentUserItem();
      		$info_text = array();
      		$info_text['count_new'] = 0;
      		$info_text['count_changed'] = 0;
      		if ($current_user->isUser()) {
         		$noticed_manager = $this->_environment->getNoticedManager();
         		$annotation_list = $item->getItemAnnotationList();
         		$anno_item = $annotation_list->getFirst();
         		$new = false;
         		$changed = false;
         		$date = "0000-00-00 00:00:00";
         		while ( $anno_item ) {
            		$noticed = $noticed_manager->getLatestNoticed($anno_item->getItemID());
            		$temp_array = array();
            		if ( empty($noticed) ) {
               			if ($date < $anno_item->getModificationDate() ) {
                   			$info_text['count_new']++;
                   			$temp_array['iid'] = $anno_item->getItemID();
                   			$temp_array['date'] = $this->_environment->getTranslationObject()->getDateInLang($anno_item->getModificationDate());
                   			$temp_array['title'] = $anno_item->getTitle();
                    		$temp_array['ref_iid'] = $anno_item->getLinkedItemID();
                    		$info_text['anno_new_items'][] = $temp_array;
               			}
            		} elseif ( $noticed['read_date'] < $anno_item->getModificationDate() ) {
               			if ($date < $anno_item->getModificationDate() ) {
                   			$info_text['count_changed']++;
                   			$temp_array['iid'] = $anno_item->getItemID();
                   			$temp_array['date'] = $this->_environment->getTranslationObject()->getDateInLang($anno_item->getModificationDate());
   #                			$temp_array['modificator'] = $anno_item->getModificatorItem()->getFullname();
                   			$temp_array['title'] = $anno_item->getTitle();
                    		$temp_array['ref_iid'] = $anno_item->getLinkedItemID();
                    		$info_text['anno_changed_items'][] = $temp_array;
                			}
            		}
            		$anno_item = $annotation_list->getNext();
         		}
      		}
      		return $info_text;
  	 	}

		protected function _getAnnotationChangeStatus($annotation) {
      		$translator = $this->_environment->getTranslationObject();
      		$current_user = $this->_environment->getCurrentUserItem();
      		if ($current_user->isUser()) {
				$noticed_manager = $this->_environment->getNoticedManager();
				$new = false;
         		$changed = false;
         		$date = "0000-00-00 00:00:00";

				$noticed = $noticed_manager->getLatestNoticed($annotation->getItemID());

				if ( empty($noticed) ) {
					if ($date < $annotation->getModificationDate() ) {
						$new = true;
						$changed = false;
						$date = $annotation->getModificationDate();
					}
				} elseif ( $noticed['read_date'] < $annotation->getModificationDate() ) {
					if ($date < $annotation->getModificationDate() ) {
						$new = false;
						$changed = true;
						$date = $annotation->getModificationDate();
					}
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

		protected function showNetnavigation() {
	      return false;
		}
	}