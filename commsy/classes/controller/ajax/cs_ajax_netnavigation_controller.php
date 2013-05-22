<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_netnavigation_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
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

			// TODO: implement - users are not allowed to remove themself from the "All Members" group

			// get ids of linked items
			$selected_ids = $this->getLinkedItemIDArray($item);
			
			// get linked item
			$temp_item = $item_manager->getItem($link_id);
			
			if(isset($temp_item)) {
				$manager = $this->_environment->getManager($temp_item->getItemType());
				$linked_item = $manager->getItem($link_id);
			}
			
			$type = $linked_item->getType();
			if($type === 'label') {
				$type = $linked_item->getLabelType();
			}
			
			// update id array
			if($checked === true) {
				// add
				$selected_ids[] = $link_id;
				$selected_ids = array_unique($selected_ids);	// ensure uniqueness

				// collect new item information
				$entry = array();
				$user = $this->_environment->getCurrentUser();
				$converter = $this->_environment->getTextConverter();
				$translator = $this->_environment->getTranslationObject();

				$logoInformation = $this->getUtils()->getLogoInformationForType($type);
				$text = $logoInformation["text"];
				$img = $logoInformation["img"];

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
					case CS_USER_TYPE:
						// if we assign a user...
						if ( isset($item) && $item->getType() === CS_TODO_TYPE )
						{
							// ...to a task
							$item->addProcessor($linked_item);
							break;
						}
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
				if ( $type === CS_USER_TYPE && $item->getType() === CS_TODO_TYPE )
				{
					$item->removeProcessor($linked_item);
				}
				else
				{
					if(($offset = array_search($link_id, $selected_ids)) !== false) array_splice($selected_ids, $offset, 1);
				}
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

		public function actionPerformRequest() {
			$return = array();

			$current_user = $this->_environment->getCurrentUser();
			$session = $this->_environment->getSessionItem();

			// get request data
			$item_id = $this->_data['item_id'];
			$module = $this->_data['module'];
			$current_page = $this->_data['current_page'];
			$restrictions = $this->_data['restrictions'];

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
			
			$current_context = ($item !== null) ? $item->getContextItem() : $this->_environment->getCurrentContextItem();
			
			// get ids of linked items
			$selected_ids = ($item_id !== "NEW") ? $this->getLinkedItemIDArray($item) : array();

			// build item list
			$item_list = new cs_list();
			$item_ids = array();
			$count_all = 0;

			if(!($item_id === "NEW" && $restrictions['only_linked'] === true)) {
				// get current room modules
				$room_modules = array();
				$current_room_modules = $current_context->getHomeConf();
				
				if(!empty($current_room_modules)) $room_modules = explode(',', $current_room_modules);
				
				if ($current_context->isPrivateRoom() )
				{
					$rubric_array = array(
						CS_MATERIAL_TYPE,
						CS_DISCUSSION_TYPE,
						CS_DISCUSSION_TYPE,
						CS_DATE_TYPE
					);
				}
				else
				{
					$rubric_array = array();
					
					foreach($room_modules as $room_module) {
						list($name, $display) = explode('_', $room_module);
					
						if($display != 'none'	&&	!($current_context->isPrivateRoom() && $name == 'user') &&
								!(	$name == CS_USER_TYPE && (
										$module == CS_MATERIAL_TYPE ||
										$module == CS_DISCUSSION_TYPE ||
										$module == CS_ANNOUNCEMENT_TYPE ||
										$module == CS_TOPIC_TYPE))) {
							$rubric_array[] = $name;
						}
					}
					
					if($module == CS_USER_TYPE) {
						$rubric_array = array();
							
						if($current_context->withRubric(CS_GROUP_TYPE)) $rubric_array[] = CS_GROUP_TYPE;
						if($current_context->withRubric(CS_INSTITUTION_TYPE)) $rubric_array[] = CS_INSTITUTION_TYPE;
					}
				}
				
				// perform rubric restriction
				if(!empty($restrictions['rubric']) && $restrictions['rubric'] !== "all") {
					$rubric_array = array();
					$rubric_array[] = $restrictions['rubric'];
				}
					
				if($restrictions['only_linked'] === true && empty($selected_ids)) $rubric_array = array();
				
				foreach($rubric_array as $rubric) {
					$rubric_list = new cs_list();
					$rubric_manager = $this->_environment->getManager($rubric);

					if(isset($rubric_manager) && $rubric != CS_MYROOM_TYPE) {
						if($rubric != CS_PROJECT_TYPE) $rubric_manager->setContextLimit($current_context->getItemID());

						if($rubric == CS_DATE_TYPE) $rubric_manager->setWithoutDateModeLimit();

						if($rubric == CS_USER_TYPE) {
							$rubric_manager->setUserLimit();

							if($current_user->isUser()) $rubric_manager->setVisibleToAllAndCommsy();
							else $rubric_manager->setVisibleToAll();
						}

						$count_all += $rubric_manager->getCountAll();

						// set restrictions
						if(!empty($restrictions['search'])) $rubric_manager->setSearchLimit($restrictions['search']);
						if($restrictions['only_linked'] === true) $rubric_manager->setIDArrayLimit($selected_ids);
						if($restrictions['type'] == 2) $rubric_manager->showNoNotActivatedEntries();

						$rubric_manager->selectDistinct();
						$rubric_list = $rubric_manager->get();
						
						// show hidden entries only if user is moderator or owner
						if($restrictions['type'] != 2 && !$current_user->isModerator()) {
							// check if user is owner
							$entry = $rubric_list->getFirst();
							while($entry) {
								if($entry->isNotActivated() && $entry->getCreatorID() != $current_user->getItemID()) {
									// remove item from list
									$rubric_list->removeElement($entry);
								}

								$entry = $rubric_list->getNext();
							}
						}

						// add rubric list to item list
						$item_list->addList($rubric_list);

						$temp_rubric_ids = $rubric_manager->getIDArray();
						if(!empty($temp_rubric_ids)) {
							//$session->setValue('cid'.$environment->getCurrentContextID().'_item_attach_index_ids', $rubric_ids);
							$item_ids = array_merge($item_ids, $temp_rubric_ids);
						}
					}
				}
			}

			$interval = CS_LIST_INTERVAL;
			$from = $current_page * $interval;

			// get sublist - paging
			$sublist = $item_list->getSublist($from, $interval);

			// prepare return
			$return['list'] = array();
			$item = $sublist->getFirst();
			while($item) {
				if ($item->getItemType() == CS_USER_TYPE){
					$title = $item->getFullName();
				}else{
					$title = $item->getTitle();
				}
				$entry = array();
				$entry['item_id']			= $item->getItemID();
				$entry['title']				= $title;
				$entry['modification_date']	= $item->getModificationDate();
				$entry['modificator']		= $item->getModificatorItem()->getFullName();
				$entry['system_label']		= $item->isSystemLabel();

				$entry['checked'] = false;
				if(in_array($item->getItemID(), $selected_ids)) $entry['checked'] = true;

				$return['list'][] = $entry;
				$item = $sublist->getNext();
			}
			$return['paging']['pages'] = ceil(/*$count_all*/count($item_ids) / $interval);
			$return['num_selected_total'] = count($selected_ids);

			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}

		public function actionGetInitialData() {
			$return = array();

			// get request data
			$module = $this->_data['module'];

			$current_context = $this->_environment->getCurrentContextItem();
			$translator = $this->_environment->getTranslationObject();

			// get available rubrics
			$rubrics = array();

			// add all
			$rubrics[] = array(
				'value'		=> 'all',
				'text'		=> $translator->getMessage('ALL'),
				'disabled'	=> false
			);

			// add disabled
			$rubrics[] = array(
				'value'		=> '-1',
				'text'		=> '-------------------------',
				'disabled'	=> true
			);

			// add rubrics
			$current_room_modules = $current_context->getHomeConf();
			$room_modules = array();
			if(!empty($current_room_modules)) $room_modules = explode(',', $current_room_modules);

			foreach($room_modules as $module) {
				list($name, $display) = explode('_', $module);

				if($display != 'none'	&& (	$name != CS_USER_TYPE || (	$module != CS_MATERIAL_TYPE &&
																			$module != CS_DISCUSSION_TYPE &&
																			$module != CS_ANNOUNCEMENT_TYPE &&
																			$module != CS_TOPIC_TYPE))
										&& $name != CS_PROJECT_TYPE
										&& !$this->_environment->isPlugin($name)
										&& !($this->_environment->inPrivateRoom() && $name == CS_MYROOM_TYPE)) {
					// determ rubric text
					switch(mb_strtoupper($name, 'UTF-8')) {
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
							$text = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' ('.$name.') '.__FILE__.'('.__LINE__.') ' );
							break;
					}

					if (
						!($name == CS_USER_TYPE and (
							$this->_data['module'] != CS_DATE_TYPE and $this->_data['module'] != CS_TODO_TYPE and $this->_data['module'] != CS_GROUP_TYPE)
							)
						)
					// add rubric
					$rubrics[] = array(
						'value'		=> $name,
						'text'		=> $text,
						'disabled'	=> false
					);
				}
			}

			// append to return
			$return['rubrics'] = $rubrics;

			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}

		private function getLinkedItemIDArray($item) {
			$selected_ids = array();
			if(isset($item)) {
				$type = $item->getItemType();
				if($type == CS_USER_TYPE) {
					if($this->_environment->inCommunityRoom()) $selected_ids = $item->getLinkedItemIDArray(CS_INSTITUTION_TYPE);
					else $selected_ids = $item->getLinkedItemIDArray(CS_GROUP_TYPE);
				} elseif(isset($item) && $item->isA(CS_BUZZWORD_TYPE)) {
					$selected_ids = $item->getAllLinkedItemIDArrayLabelVersion();
				} else {
					$selected_ids = $item->getAllLinkedItemIDArray();
				}
			}
			return $selected_ids;
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