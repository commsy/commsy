<?php
	require_once('classes/controller/cs_base_controller.php');

	abstract class cs_room_controller extends cs_base_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function processTemplate() {
			// call parent
			parent::processTemplate();

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

			// buzzwords
			$this->assign('room', 'buzzwords', $this->getBuzzwords());

			// tags
			$this->assign('room', 'tags', $this->getTags());
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
				CS_TOPIC_TYPE			=>	'th'
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
		private function getBuzzwords() {
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
		private function getBuzzwordSizeLogarithmic($count, $mincount = 0, $maxcount = 30, $minsize = 10, $maxsize = 20, $tresholds = 0) {
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
		private function getTags() {
			$tag_manager = $this->_environment->getTagManager();
			$root_item = $tag_manager->getRootTagItem();

			return $this->buildTagArray($root_item);
		}

		/**
		 * this method goes through the tree structure and generates a nested array of information
		 * @param cs_tag_item $item
		 */
		private function buildTagArray(cs_tag_item $item) {
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
	}