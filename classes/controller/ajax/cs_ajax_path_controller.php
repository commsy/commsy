<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_path_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetConnectedEntries() {
			$return = array();
			
			$translator = $this->_environment->getTranslationObject();
			
			// get request data
			$item_id = $this->_data['item_id'];
			
			if($item_id !== 'NEW') {
				// get item
				$manager = $this->_environment->getManager(CS_TOPIC_TYPE);
				$item = $manager->getItem($item_id);
					
				if($this->getUtils()->showNetnavigation() === true) {
					// get connected entries for this item
					$netnavigation = $this->getUtils()->getNetnavigation($item);
				
					$entries_sorted = array();
					$entries_unsorted = array();
					foreach($netnavigation['items'] as $item) {
						$text = '';
						switch(mb_strtoupper($item['module'], 'UTF-8')) {
							case 'ANNOUNCEMENT':
								$text = $translator->getMessage('ANNOUNCEMENT_INDEX');
								break;
							case 'DATE':
								$text = $translator->getMessage('DATE_INDEX');
								break;
							case 'DISCUSSION':
								$text = $translator->getMessage('DISCUSSION_INDEX');
								break;
							case 'GROUP':
								$text = $translator->getMessage('GROUP_INDEX');
								break;
							case 'INSTITUTION':
								$text = $translator->getMessage('INSTITUTION_INDEX');
								break;
							case 'MATERIAL':
								$text = $translator->getMessage('MATERIAL_INDEX');
								break;
							case 'PROJECT':
								$text = $translator->getMessage('PROJECT_INDEX');
								break;
							case 'TODO':
								$text = $translator->getMessage('TODO_INDEX');
								break;
							case 'TOPIC':
								$text = $translator->getMessage('TOPIC_INDEX');
								break;
							case 'USER':
								$text = $translator->getMessage('USER_INDEX');
								break;
							default:
								$text = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' ('.$module.') '.__FILE__.'('.__LINE__.') ' );
								break;
						}
							
						$entry = array(
								'img'				=> $item['img'],
								'text'				=> $item['link_text'],
								'linked_id'			=> $item['linked_iid'],
								'path_active'		=> !empty($item['sorting_place'])
						);
							
						if(empty($item['sorting_place'])) $entries_unsorted[] = $entry;
						else {
							$entry['sorting_place'] = $item['sorting_place'];
							
							$entries_sorted[] = $entry;
						}
					}
					
					// sort by sorting_place
					usort($entries_sorted, array($this, "sortFunction"));
					
					$return = array_merge($entries_sorted, $entries_unsorted);
				}
			}
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		private function sortFunction($a, $b)
		{
			if($a['sorting_place'] == $b['sorting_place']) return 0;
			
			return ($a['sorting_place'] < $b['sorting_place']) ? -1 : 1;
		}
		
		public function actionSavePath() {
			// get request data
			$item_id = $this->_data['item_id'];
			$linked_ids = $this->_data['linked_ids'];
			$onlyUpdate = $this->_data["onlyUpdate"];
			
			// get item
			$manager = $this->_environment->getManager(CS_TOPIC_TYPE);
			$item = $manager->getItem($item_id);
				
			// get link_id for all linked entries
			$linked_id_link_id = array();
			$link_items = $item->getAllLinkItemList();
			$link_item = $link_items->getFirst();
			while($link_item) {
				$linked_item = $link_item->getLinkedItem($item);
				$type = $linked_item->getType();
				if($type === 'label') {
					$type = $linked_item->getLabelType();
				}
			
				switch($type) {
					case CS_DISCARTICLE_TYPE:
						$linked_iid = $linked_item->getDiscussionID();
						$discussion_manager = $this->_environment->getDiscussionManager();
						$linked_item = $discussion_manager->getItem($linked_iid);
						break;
					case CS_SECTION_TYPE:
						$linked_iid = $linked_item->getLinkedItemID();
						$material_manager = $this->_environment->getMaterialManager();
						$linked_item = $material_manager->getItem($linked_iid);
						break;
					default:
						$linked_iid = $linked_item->getItemID();
				}
				
				$linked_id_link_id[$linked_iid] = array(
					"linkItemId"		=> $link_item->getItemID(),
					"sortingPlace"		=> $link_item->getSortingPlace()
				);
			
				$link_item = $link_items->getNext();
			}
			
			// set up array to save
			$item_place_array = array();
			$count = 1;
			
			if ( isset($onlyUpdate) && $onlyUpdate === true )
			{
				/**
				 * When this flag is set, we get all linked items, but don't check them against the selected one. This will catch any case
				 * where linked items are updated, but no path tab was available.
				 */
				foreach ( $linked_id_link_id as $id => $linkArray )
				{
					// only add if previously defined in path - do not include newly added linked entries automatically
					if ( !empty($linkArray["sortingPlace"]) )
					{
						$item_place_array[] = array(
							'item_id'		=> $linkArray["linkItemId"],
							'place'			=> $count++
						);
					}
				}
			}
			else
			{
				foreach( $linked_ids as $id )
				{
					$item_place_array[] = array(
						'item_id'		=> $linked_id_link_id[$id]["linkItemId"],
						'place'			=> $count++
					);
				}
			}
			
			$link_item_manager = $this->_environment->getLinkItemManager();
			$link_item_manager->cleanSortingPlaces($item);
				
			if(!empty($item_place_array)) $link_item_manager->saveSortingPlaces($item_place_array);
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}

		public function actionUpdateLinkedItem() {
			$return = array();

			// get request data
			$item_id = $this->_data['item_id'];
			$link_id = $this->_data['link_id'];
			$checked = $this->_data['checked'];
			// get item
			$item_manager = $this->_environment->getItemManager();
			$temp_item = $item_manager->getItem($item_id);
			if(isset($temp_item)) {
				if($temp_item->getItemType() == 'label'){
					$label_manager = $this->_environment->getLabelManager();
					$label_item = $label_manager->getItem($temp_item->getItemID());
					$manager = $this->_environment->getManager($label_item->getLabelType());
				}else{
					$manager = $this->_environment->getManager($temp_item->getItemType());
				}
				$item = $manager->getItem($item_id);
			}
			// get ids of linked items
			$selected_ids = $this->getLinkedItemIDArray($item);

			// update id array
			if($checked === true) {
				// add
				$selected_ids[] = $link_id;
				$selected_ids = array_unique($selected_ids);	// ensure uniqueness

				// get linked item
				$temp_item = $item_manager->getItem($link_id);

				if(isset($temp_item)) {
					$manager = $this->_environment->getManager($temp_item->getItemType());
					$linked_item = $manager->getItem($link_id);
				}

				// collect new item information
				$entry = array();
				$user = $this->_environment->getCurrentUser();
				$converter = $this->_environment->getTextConverter();
				$translator = $this->_environment->getTranslationObject();

				$type = $linked_item->getType();
				if($type === 'label') {
					$type = $linked_item->getLabelType();
				}

				switch(mb_strtoupper($type, 'UTF-8')) {
					case 'ANNOUNCEMENT':
						$text = $translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
						$img = 'announcement.png';
						break;
					case 'DATE':
						$text = $translator->getMessage('COMMON_ONE_DATE');
						$img = 'date.png';
						break;
					case 'DISCUSSION':
						$text = $translator->getMessage('COMMON_ONE_DISCUSSION');
						$img = 'discussion.png';
						break;
					case 'GROUP':
						$text = $translator->getMessage('COMMON_ONE_GROUP');
						$img = 'group.png';
						break;
					case 'INSTITUTION':
						$text = $translator->getMessage('COMMON_ONE_INSTITUTION');
						$img = '';
						break;
					case 'MATERIAL':
						$text = $translator->getMessage('COMMON_ONE_MATERIAL');
						$img = 'material.png';
						break;
					case 'PROJECT':
						$text = $translator->getMessage('COMMON_ONE_PROJECT');
						$img = '';
						break;
					case 'TODO':
						$text = $translator->getMessage('COMMON_ONE_TODO');
						$img = 'todo.png';
						break;
					case 'TOPIC':
						$text = $translator->getMessage('COMMON_ONE_TOPIC');
						$img = 'topic.png';
						break;
					case 'USER':
						$text = $translator->getMessage('COMMON_ONE_USER');
						$img = 'user.png';
						break;
					default:
						$text = $translator->getMessage('COMMON_MESSAGETAB_ERROR');
						$img = '';
						break;
				}

				$link_creator_text = $text . ' - ' . $translator->getMessage('COMMON_LINK_CREATOR') . ' ' . $entry['creator'];

				switch($type) {
					case CS_DISCARTICLE_TYPE:
						$linked_iid = $linked_item->getDiscussionID();
						$discussion_manager = $this->_environment->getDiscussionManager();
						$linked_item = $discussion_manager->getItem($linked_iid);
						break;
					case CS_SECTION_TYPE:
						$linked_iid = $linked_item->getLinkedItemID();
						$material_manager = $this->_environment->getMaterialManager();
						$linked_item = $material_manager->getItem($linked_iid);
						break;
					default:
						$linked_iid = $linked_item->getItemID();
				}

				$entry['linked_iid'] = $linked_iid;

				$module = Type2Module($type);

				if($linked_item->isNotActivated() && !($linked_item->getCreatorID() === $user->getItemID() || $user->isModerator())) {
					$activating_date = $linked_item->getActivatingDate();
					if(strstr($activating_date, '9999-00-00')) {
						$link_creator_text .= ' (' . $translator->getMessage('COMMON_NOT_ACTIVATED') . ')';
					} else {
						$link_creator_text .= ' (' . $translator->getMessage('COMMON_ACTIVATING_DATE') . ' ' . getDateInLang($linked_item->getActivatingDate()) . ')';
					}

					if($module === CS_USER_TYPE) {
						$title = $linked_item->getFullName();
					} else {
						$title = $linked_item->getTitle();
					}
					$title = $converter->text_as_html_short($title);

					$entry['module'] = $module;
					$entry['img'] = $img;
					$entry['title'] = $link_creator_text;
					$entry['link_text'] = $title;
				} else {
					if($module === CS_USER_TYPE) {
						$title = $linked_item->getFullName();
					} else {
						$title = $linked_item->getTitle();
					}
					$title = $converter->text_as_html_short($title);

					$entry['module'] = $module;
					$entry['img'] = $img;
					$entry['title'] = $link_creator_text;
					$entry['link_text'] = $title;
				}

				$return['linked_item'] = $entry;
			} else {
				// remove
				if(($offset = array_search($link_id, $selected_ids)) !== false) array_splice($selected_ids, $offset, 1);
			}

			// update item
			if(isset($item)) {
				if($item->isA(CS_LABEL_TYPE) && $item->getLabelType() == CS_BUZZWORD_TYPE) {
					$item->saveLinksByIDArray($selected_ids);
				} else {
					$item->setLinkedItemsByIDArray($selected_ids);
					$item->save();
				}
			}

			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}