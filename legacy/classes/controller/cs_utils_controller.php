<?php
	class cs_utils_controller {
		private $_environment = null;

		public function __construct($environment) {
			$this->_environment = $environment;
		}

		public function showNetnavigation() {
	      $context_item = $this->_environment->getCurrentContextItem();
	      if ($context_item->withNetnavigation()
	          and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
	                or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
	                or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
	                or $this->_environment->getCurrentModule() == CS_TODO_TYPE
	                or $this->_environment->getCurrentModule() == CS_DATE_TYPE
	                or $this->_environment->getCurrentModule() == CS_GROUP_TYPE
	                or $this->_environment->getCurrentModule() == CS_TOPIC_TYPE
	                or ($this->_environment->getCurrentModule() == CS_USER_TYPE and $context_item->withRubric(CS_GROUP_TYPE))
	                or $this->_environment->getCurrentModule() == 'search'
	          		|| ($this->_environment->getCurrentModule() === 'ajax' && in_array($this->_environment->getCurrentFunction(), array('rubric_popup', 'path'))))
	      ) {
	         return true;
	      }

	      return false;
		}

		public function showTags($module = null) {
			$module = ($module !== null) ? $module : $this->_environment->getCurrentModule();
			
			$context_item = $this->_environment->getCurrentContextItem();
			if($context_item->withTags() &&
				( $module == CS_MATERIAL_TYPE
	                || $module == CS_ANNOUNCEMENT_TYPE
	                || $module == CS_DISCUSSION_TYPE
	                || $module == CS_TODO_TYPE
	                || $module == CS_DATE_TYPE
	                || $module == 'search'
					|| $module == 'ajax'
	                || $module === 'home')) {
				return true;
			}

			return false;
		}

		public function showBuzzwords($module = null) {
			$module = ($module !== null) ? $module : $this->_environment->getCurrentModule();
			
			$context_item = $this->_environment->getCurrentContextItem();
			if($context_item->withBuzzwords() &&
				(	$module === CS_ANNOUNCEMENT_TYPE ||
					$module === 'home' ||
					$module === 'ajax' ||
					$module === 'search' ||
					$module === CS_DATE_TYPE ||
					$module === CS_MATERIAL_TYPE ||
					$module === CS_DISCUSSION_TYPE ||
					$module === CS_TODO_TYPE)) {
				return true;
			}

			return false;
		}


		public function getNetnavigationForUser($item) {
			$return = array();

			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUser();
			$translator = $this->_environment->getTranslationObject();
			$converter = $this->_environment->getTextConverter();

			if ($current_context->isProjectRoom()){
				$link_items = $item->getLinkItemList(CS_GROUP_TYPE);
			}else{
				$link_items = new cs_list();
			}

			// remove items from list the current user is not allowed to see or ???
			$count_item = $link_items->getFirst();
			while($count_item) {
				$linked_item = $count_item->getLinkedItem($item);
				if(isset($linked_item)) {
					$type = $linked_item->getType();
				}

				$module = Type2Module($type);
				if($module === CS_USER_TYPE && ($item->getItemType()== CS_GROUP_TYPE || (!$linked_item->isUser() || !$linked_item->maySee($current_user)))) {
					$link_items->removeElement($count_item);
				}

				$count_item = $link_items->getNext();
			}

			$count_link_item = $link_items->getCount();
			$return['count'] = $count_link_item;
			/*
			 *
			$this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_NETNAVIGATION_ENTRIES').' ('.$count_link_item.')"';
			$this->_right_box_config['desc_string'] .= $separator.'""';
			$this->_right_box_config['size_string'] .= $separator.'"10"';

			if($current_context->isNetnavigationShowExpanded()){
			$this->_right_box_config['config_string'] .= $separator.'true';
			} else {
			$this->_right_box_config['config_string'] .= $separator.'false';
			}
			$html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
			$html .= '<div class="right_box">'.LF;
			*/

			$return['items'] = array();
			if(!$link_items->isEmpty()) {
				$link_item = $link_items->getFirst();

				while($link_item) {
					$entry = array(
							'creator'			=> ''									// TODO: if empty set to COMMON_DELETED_USER
					);

					$link_creator = $link_item->getCreatorItem();
					if(isset($link_creator) && !$link_creator->isDeleted()) {
						$entry['creator'] = $link_creator->getFullname();

						// create the list entry
						$linked_item = $link_item->getLinkedItem($item);
						if(isset($linked_item)) {
							$type = $linked_item->getType();
							if($type === 'label') {
								$type = $linked_item->getLabelType();
							}

							$link_created = $translator->getDateInLang($link_item->getCreationDate());

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

							$link_creator_text = $text . ' - ' . $translator->getMessage('COMMON_LINK_CREATOR') . ' ' . $entry['creator'] . ', ' . $link_created;

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

							$entry['link_id'] = $link_item->getItemID();
							$entry['linked_iid'] = $linked_iid;
							$entry['sorting_place'] = $link_item->getSortingPlace();

							$module = Type2Module($type);
							$user = $this->_environment->getCurrentUser();

							if(!($module == CS_USER_TYPE && (!$linked_item->isUser() || !$linked_item->maySee($user)))) {
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

									/*
									 * TODO: check if working
									$html .= ahref_curl( $this->_environment->getCurrentContextID(),
											$module,
											'detail',
											$params,
											'<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
											$link_creator_text,
											'_self',
											$fragment,
											'',
											'',
											'',
											'class="disabled"',
											'',
											'',
											true);
									$html .= ahref_curl( $this->_environment->getCurrentContextID(),
											$module,
											'detail',
											$params,
											$link_title,
											$link_creator_text,
											'_self',
											$fragment,
											'',
											'',
											'',
											'class="disabled"',
											'',
											'',
											true);
									unset($params);
									*/
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


									/*
									 * TODO: check if needed - $link_creator_text is empty!!!
									*
									*
									*
									$html .= ahref_curl( $this->_environment->getCurrentContextID(),
											$module,
											'detail',
											$params,
											'<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
											$link_creator_text,
											'_self',
											$fragment,
											'',
											'',
											'',
											'style=""');
									$html .= ahref_curl( $this->_environment->getCurrentContextID(),
											$module,
											'detail',
											$params,
											$link_title,
											$link_creator_text,
											'_self',
											$fragment,
											'',
											'',
											'',
											'style=""');
									unset($params);
									*/
								}

								$return['items'][] = $entry;
							}
						}
					}

					$link_item = $link_items->getNext();
				}
			}

			return $return;
		}

		public function getNetnavigation($item) {
			$return = array();

			if ( isset($item) ) {
				$current_context = $this->_environment->getCurrentContextItem();
				$current_user = $this->_environment->getCurrentUser();
				$translator = $this->_environment->getTranslationObject();
				$converter = $this->_environment->getTextConverter();
	
				$link_items = $item->getAllLinkItemList();
	
				// remove items from list the current user is not allowed to see or ???
				$count_item = $link_items->getFirst();
				while($count_item) {
					$linked_item = $count_item->getLinkedItem($item);
					if(isset($linked_item)) {
						$type = $linked_item->getType();
					}
	
// 					$module = Type2Module($type);
// 					if($module === CS_USER_TYPE && ($item->getItemType()== CS_GROUP_TYPE || (!$linked_item->isUser() || !$linked_item->maySee($current_user)))) {
// 						$link_items->removeElement($count_item);
// 					}
	
					$count_item = $link_items->getNext();
				}
	
				$count_link_item = $link_items->getCount();
				$return['count'] = $count_link_item;
				/*
				 *
				$this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_NETNAVIGATION_ENTRIES').' ('.$count_link_item.')"';
				$this->_right_box_config['desc_string'] .= $separator.'""';
				$this->_right_box_config['size_string'] .= $separator.'"10"';
	
				if($current_context->isNetnavigationShowExpanded()){
				$this->_right_box_config['config_string'] .= $separator.'true';
				} else {
				$this->_right_box_config['config_string'] .= $separator.'false';
				}
				$html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
				$html .= '<div class="right_box">'.LF;
				*/
	
				$return['items'] = array();
				if(!$link_items->isEmpty()) {
					$link_item = $link_items->getFirst();
	
					while($link_item) {
						$entry = array(
								'creator'			=> ''									// TODO: if empty set to COMMON_DELETED_USER
						);
	
						$link_creator = $link_item->getCreatorItem();
						if(isset($link_creator) && !$link_creator->isDeleted()) {
							$entry['creator'] = $link_creator->getFullname();
	
							// create the list entry
							$linked_item = $link_item->getLinkedItem($item);
							if(isset($linked_item)) {
								$type = $linked_item->getType();
								if($type === 'label') {
									$type = $linked_item->getLabelType();
								}
	
								$link_created = $translator->getDateInLang($link_item->getCreationDate());
	
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
	
								$link_creator_text = $text . ' - ' . $translator->getMessage('COMMON_LINK_CREATOR') . ' ' . $entry['creator'] . ', ' . $link_created;
	
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
	
								$entry['link_id'] = $link_item->getItemID();
								$entry['linked_iid'] = $linked_iid;
								$entry['sorting_place'] = $link_item->getSortingPlace();
	
								$module = Type2Module($type);
								$user = $this->_environment->getCurrentUser();
	
								if(!($module == CS_USER_TYPE && (!$linked_item->isUser() || !$linked_item->maySee($user)))) {
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
	
										/*
										 * TODO: check if working
										$html .= ahref_curl( $this->_environment->getCurrentContextID(),
												$module,
												'detail',
												$params,
												'<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
												$link_creator_text,
												'_self',
												$fragment,
												'',
												'',
												'',
												'class="disabled"',
												'',
												'',
												true);
										$html .= ahref_curl( $this->_environment->getCurrentContextID(),
												$module,
												'detail',
												$params,
												$link_title,
												$link_creator_text,
												'_self',
												$fragment,
												'',
												'',
												'',
												'class="disabled"',
												'',
												'',
												true);
										unset($params);
										*/
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
	
	
										/*
										 * TODO: check if needed - $link_creator_text is empty!!!
										*
										*
										*
										$html .= ahref_curl( $this->_environment->getCurrentContextID(),
												$module,
												'detail',
												$params,
												'<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
												$link_creator_text,
												'_self',
												$fragment,
												'',
												'',
												'',
												'style=""');
										$html .= ahref_curl( $this->_environment->getCurrentContextID(),
												$module,
												'detail',
												$params,
												$link_title,
												$link_creator_text,
												'_self',
												$fragment,
												'',
												'',
												'',
												'style=""');
										unset($params);
										*/
									}
	
									$return['items'][] = $entry;
								}
							}
						}
	
						$link_item = $link_items->getNext();
					}
				}
			}
			return $return;
		}

		/**
		 * wrapper for recursive tag call
		 */
		public function getTags($room_id = null) {
			$tag_manager = $this->_environment->getTagManager();

			if ($room_id === null) {
				$root_item = $tag_manager->getRootTagItem();
			} else {
				$root_item = $tag_manager->getRootTagItemFor($room_id);
			}


			return $this->buildTagArray($root_item);
		}

		/**
		 * this method goes through the tree structure and generates a nested array of information
		 * @param cs_tag_item $item
		 */
		private function buildTagArray(cs_tag_item $item, $level = 0) {
			$return = array();

			if(isset($item)) {
				$children_list = $item->getChildrenList();
				$level++;
				$item = $children_list->getFirst();
				while($item) {
					// attach to return
					$return[] = array(
						'title'		=> $item->getTitle(),
						'item_id'	=> $item->getItemID(),
						'level'		=> $level,
						'children'	=> $this->buildTagArray($item, $level)
					);

					$item = $children_list->getNext();
				}
			}

			return $return;
		}

		
		public function getSubtree(&$tag, $itemTagIdArray)
		{
			if (empty($tag['children'])) {
				// no children
				return in_array($tag['item_id'], $itemTagIdArray);
			} else {
				// iterate through all children
				foreach($tag['children'] as $key => &$childTag) {
					$matched = $this->getSubtree($childTag, $itemTagIdArray);
					
					if (!$matched) {
						// unset child
						#unset($tag['children'][$key]);
						array_splice($tag['children'], $key, 1);
					}
				}
				
				if (empty($tag['children'])) {
					return in_array($tag['item_id'], $itemTagIdArray);
				}
				
				return true;
			}
		}
		

		public function markTags(&$tag_array, $item_tag_id_array) {
			// compare and mark as highlighted
			foreach($tag_array as &$tag) {
				if(in_array($tag['item_id'], $item_tag_id_array)) {
					$tag['match'] = true;
				} else {
					$tag['match'] = false;
				}

				// look recursive
				if(!empty($tag['children'])) {
					$this->markTags($tag['children'], $item_tag_id_array);
				}
			}

			// break the reference
			unset($tag);
		}

		public function getCopyRubrics() {
			return array(CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE, CS_MATERIAL_TYPE, CS_TODO_TYPE);
		}

		public function getCopyCount(){
			$item_id_count = 0;
			$rubric_copy_array = $this->getCopyRubrics();
			$session = $this->_environment->getSessionItem();
			foreach ($rubric_copy_array as $rubric){
			   $item_id_count = $item_id_count + count($session->getValue($rubric.'_clipboard'));
			}
			return $item_id_count;
		}


		/**
		 * get data for buzzword portlet
		 */
		public function getBuzzwords($return_empty = false, $roomId = null) {
			$return = array();

			$buzzword_manager = $this->_environment->getLabelManager();
			$text_converter = $this->_environment->getTextConverter();
      		$params = $this->_environment->getCurrentParameterArray();

			$buzzword_manager->resetLimits();
			if ($roomId == null) {
				$buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
			} else {
				$buzzword_manager->setContextLimit($roomId);
			}
			$buzzword_manager->setTypeLimit('buzzword');
			$buzzword_manager->setGetCountLinks();
			$buzzword_manager->select();
			$buzzword_list = $buzzword_manager->get();

			$buzzword = $buzzword_list->getFirst();
			while($buzzword) {
				$count = $buzzword->getCountLinks();
				if($count > 0 || $return_empty) {
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
		public function getBuzzwordSizeLogarithmic($count, $mincount = 0, $maxcount = 30, $minsize = 10, $maxsize = 20, $tresholds = 0) {
			if(empty($tresholds)) {
				$tresholds = $maxsize - $minsize;
				$treshold = 1;
			} else {
				$treshold = ($maxsize - $minsize) / ($tresholds - 1);
			}

			$a = $tresholds * log($count - $mincount + 2) / log($maxcount - $mincount + 2) - 1;
			return round($minsize + round($a) * $treshold);
		}

		public function setFilesForItem(cs_item $item, $post_file_ids, $new_file_ids_to_store) {
			$session = $this->_environment->getSessionItem();

			// temp files - uploaded via "Image"-Plugin (ckeditor; custom image-upload/image-browse)
			$temp_files_array = array();
         $file_manager = $this->_environment->getFileManager();
         $file_manager->resetLimits();
         $file_manager->setTempUploadSessionIdLimit($this->_environment->getSessionId());
         $file_manager->select();
         $file_list = $file_manager->get();
         $file_item = $file_list->getFirst();
         while($file_item){
         	$temp_files_array[] = $file_item->getFileID();
         	$file_manager->resetTempUpload($file_item);
         	$file_item = $file_list->getNext();
         }
         unset($file_manager);

			$file_ids = array();

			// new file information are stored in the session object
			$new_files = $session->getValue("add_files");
			$new_file_ids = array();

			if(!empty($new_files)) {
				$file_manager = $this->_environment->getFileManager();

				foreach($new_file_ids_to_store as $file_id) {
					$file = $new_files[$file_id];
					$file["file_id"] = $file_id;

					if(isset($file['tmp_name']) && file_exists($file['tmp_name'])) {
						$file_item = $file_manager->getNewItem();
						$file_item->setTempKey($file['file_id']);

						$file['name'] = trim($file['name']);
						$file_item->setPostFile($file);
						$file_item->save();

						unlink($file['tmp_name']);  // Currently, the file manager does not unlink a file in its _saveOnDisk() method, because it is also used for copying files when copying material.
						$new_file_ids[] = $file_item->getFileID();
					}/* else {
						$new_file_ids[] = $file['file_id'];
					}*/
				}
			}

			$session->unsetValue("add_files");

			// already attach file ids are in $post_file_ids
			$attached_ids = array();
			foreach($post_file_ids as $file_id) {
				if(is_numeric($file_id)) {
					$attached_ids[] = $file_id;
				} else {
					/*
					 * if ( !isset($file_manager) ) {
							$file_manager = $this->_environment->getFileManager();
							$file_manager->setContextLimit($this->_environment->getCurrentContextID());
						}
						$temp_key = $file_manager->getFileIDForTempKey($file_id);
						if ( !empty($temp_key) and is_numeric($temp_key) ) {
							$temp_array[] = $temp_key;
						} elseif ( !empty($files) ) {
							foreach ( $files as $file_data ) {
							if ( $file_data["file_id"] == $file_id ) {
								if ( isset($file_data["tmp_name"]) and file_exists($file_data["tmp_name"]) ) { // create file entries for uploaded files
									$file_item = $file_manager->getNewItem();
									$file_item->setTempKey($file_data["file_id"]);
									$file_item->setPostFile($file_data);
									$file_item->save();
									unlink($file_data["tmp_name"]);  // Currently, the file manager does not unlink a file in its _saveOnDisk() method, because it is also used for copying files when copying material.
									$temp_array[] = $file_item->getFileID();
								}
							}
							}
						}
					 */
				}
			}

			// merge already attached file ids and new ones
			$file_ids = array_merge($new_file_ids, $attached_ids, $temp_files_array);

			// set
			$item->setFileIDArray($file_ids);
		}

		public function createOwnCSSForRoomContext(cs_context_item $room_item, array $schema) {
			$bg_image = $room_item->getBGImageFilename();
			$bg_repeat = ($room_item->issetBGImageRepeat() == true) ? 'repeat' : 'no-repeat';
			$bg_fixed = ($room_item->issetBGImageFixed() == true) ? 'fixed' : 'scroll';

			// set complete path for background image
			global $c_commsy_domain;
			$host = $c_commsy_domain;
			global $c_commsy_url_path;
			global $c_single_entry_point;
			
			$bg_image = $host.$c_commsy_url_path.'/'.$c_single_entry_point.'?cid='.$this->_environment->getCurrentContextID().'&mod=picture&fct=getfile&picture='.$bg_image;

			// In commsy/var abspeichern (für Loadbalacing)
// 			$master = 'htdocs/templates/themes/individual/styles_cid.css';
// 			$path = 'htdocs/templates/themes/individual/styles_' . $room_item->getItemID() . '.css';
			
			if(!file_exists('var/templates/')){
				mkdir('var/templates/');
			}
			if(!file_exists('var/templates/individual/')){
				mkdir('var/templates/individual/');
			}
			if(!file_exists('var/templates/individual/img/')){
				mkdir('var/templates/individual/img/');
			}
			if(!file_exists('var/templates/individual/styles_cid.css')){
			$file_old = 'htdocs/templates/themes/individual/styles_cid.css';
			$file_new = 'var/templates/individual/styles_cid.css';
				if (!copy($file_old, $file_new)) {
					$content = file_get_contents($file_old);
					if(!empty($content)){
						$openfile = fopen($file_new, 'w');
						fwrite($openfile, $content);
						fclose($openfile);
					}
				}
			}
			
			$master = 'var/templates/individual/styles_cid.css';
			$path = 'var/templates/individual/styles_' . $room_item->getItemID() . '.css';
			
			// load master file
			$css_file = file_get_contents($master);

			// replace placeholder
			preg_match_all("/\\{\\$(\S*?)\\}/", $css_file, $matches);

			if(isset($matches[0])) {
				for($i=0; $i < sizeof($matches[0]); $i++) {
					$match = $matches[0][$i];
					$var_name = $matches[1][$i];

					$val = ${$var_name};
					if(!isset($val)) {
						$val = $schema[$var_name];
					}

					// replace - only if not surrounded by /* ... */
					//preg_match_all("=(?<!\\/\\*).*?\\{\\$(.*?)\\}.*?(?!\\*\\/)=s", $css_file, $matches);

					$css_file = str_replace($match, $val, $css_file);
				}
			}

			// store new css file
			file_put_contents($path, $css_file);
		}


		public function getLogoInformationForType($type) {
			$translator = $this->_environment->getTranslationObject();

			$information = array();

			switch(mb_strtoupper($type, 'UTF-8')) {
				case 'ANNOUNCEMENT':
					$information["text"] = $translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
					$information["img"] = 'announcement.png';
					break;
				case 'DATE':
					$information["text"] = $translator->getMessage('COMMON_ONE_DATE');
					$information["img"] = 'date.png';
					break;
				case 'DISCUSSION':
					$information["text"] = $translator->getMessage('COMMON_ONE_DISCUSSION');
					$information["img"] = 'discussion.png';
					break;
				case 'GROUP':
					$information["text"] = $translator->getMessage('COMMON_ONE_GROUP');
					$information["img"] = 'group.png';
					break;
				case 'INSTITUTION':
					$information["text"] = $translator->getMessage('COMMON_ONE_INSTITUTION');
					$information["img"] = '';
					break;
				case 'MATERIAL':
					$information["text"] = $translator->getMessage('COMMON_ONE_MATERIAL');
					$information["img"] = 'material.png';
					break;
				case 'PROJECT':
					$information["text"] = $translator->getMessage('COMMON_ONE_PROJECT');
					$information["img"] = '';
					break;
				case 'TODO':
					$information["text"] = $translator->getMessage('COMMON_ONE_TODO');
					$information["img"] = 'todo.png';
					break;
				case 'TOPIC':
					$information["text"] = $translator->getMessage('COMMON_ONE_TOPIC');
					$information["img"] = 'topic.png';
					break;
				case 'USER':
					$information["text"] = $translator->getMessage('COMMON_ONE_USER');
					$information["img"] = 'user.png';
					break;
				default:
					$information["text"] = $translator->getMessage('COMMON_MESSAGETAB_ERROR');
					$information["img"] = '';
					break;
			}

			return $information;
		}

		public function cleanCKEditor($string) {
			// filter <body>-tags
			$found = preg_match("=(.*)<body.*?>(.*)<\/body>(.*)=is", $string, $matches);
			$pre = $matches[1];
			$inner = $matches[2];
			$post = $matches[3];

			if ($found === 0) {
				return $string;
			} else {
				$string = "";
				for ($i = 1; $i <= 3; $i++) {
					if (isset($matches[$i])) {
						$string .= $matches[$i];
					}
				}

				return $string;
			}
		}
	}
?>
