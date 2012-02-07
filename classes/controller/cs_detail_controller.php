<?php
	require_once('classes/controller/cs_room_controller.php');

	abstract class cs_detail_controller extends cs_room_controller {
		protected $_browse_ids = array();
		protected $_position = -1;
		protected $_item = null;
		protected $_manager = null;
		protected $_item_file_list = null;
		protected $_rubric_connections = array();
		protected $_clipboard_id_array = array();

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			// init variables
			/*
			$this->getViewMode() = 'browse';
			$this->_filter = array();
			$this->_paging = array(
				'offset'	=> 0,
				'limit'		=> 20
			);
			*/
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function processTemplate() {
			// call parent
			parent::processTemplate();

			$this->assign('detail', 'actions', $this->getDetailActions());

			// mark as read and noticed
			//$this->markRead();
			//$this->markNoticed();

			// set list actions
			//$this->assign('list', 'actions', $this->getListActions());

			/*
			// set paging information
			$paging = array(
				'num_pages'		=> ceil($this->_num_entries / $this->_paging['limit']),
				'actual_page'	=> floor($this->_paging['offset'] / $this->_paging['limit']) + 1,
				'from'			=> $this->_paging['offset'] + 1,
				'to'			=> $this->_paging['offset'] + $this->_paging['limit']
			);
			$this->assign('list', 'paging', $paging);
			$this->assign('list', 'num_entries', $this->_num_entries);
			*/
		}

		protected function setupInformation() {
			$session = $this->_environment->getSessionItem();

			$ids = array();
			if(isset($_GET['path']) && !emptry($_GET['path'])) {
				$topic_manager = $this->_environment->getTopicManager();
				$topic_item = $topic_manager->getItem($_GET['path']);
				$path_item_list = $topic_item->getPathItemList();
				$path_item = $path_item_list->getFirst();

				while($path_item) {
					$ids[] = $path_item->getItem();
					$path_item = $path_item_list->getNext();
				}
				//$params['path'] = $_GET['path'];
	         	//$html .= $this->_getForwardLinkAsHTML($ids,'path');
			} elseif(isset($_GET['search_path']) && !empty($_GET['search_path'])) {
				$ids = $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_campus_search_index_ids');
				//$html .= $this->_getForwardLinkAsHTML($ids,'search');
				//$params['search_path'] = $_GET['search_path'];
			} elseif(isset($_GET['link_item_path']) && !empty($_GET['link_item_path'])) {
				$manager = $this->_environment->getItemManager();
				$item = $manager->getItem($_GET['link_item_path']);
				$ids = $item->getAllLinkeditemIDArray();
				//$html .= $this->_getForwardLinkAsHTML($ids,'link_item');
				//$params['link_item_path'] = $_GET['link_item_path'];
			} else {
				$ids = $this->getBrowseIDs();
				$this->assign('detail', 'browsing_information', $this->getBrowseInformation($ids));
				//$html .= $this->_getForwardLinkAsHTML($ids);
			}

			$this->assign('detail', 'item_id', $this->_item->getItemID());
			$this->assign('detail', 'forward_information', $this->getForwardInformation($ids));
		}

		protected function getAssessmentInformation($item = null) {
			$assessment_item =& $this->_item;
			if(isset($item)) $assessment_item = $item;

			$assessment_stars_text_array = array('non_active','non_active','non_active','non_active','non_active');
			$current_context = $this->_environment->getCurrentContextItem();
			if($current_context->isAssessmentActive()) {
				$assessment_manager = $this->_environment->getAssessmentManager();
				$assessment = $assessment_manager->getAssessmentForItemAverage($assessment_item);
				if(isset($assessment[0])) {
					$assessment = sprintf('%1.1f', (float) $assessment[0]);
				} else {
			 		$assessment = 0;
				}
		  		$php_version = explode('.', phpversion());
				if($php_version[0] >= 5 && $php_version[1] >= 3) {
					// if php version is equal to or above 5.3
					$assessment_count_stars = round($assessment, 0, PHP_ROUND_HALF_UP);
				} else {
					// if php version is below 5.3
					$assessment_count_stars = round($assessment);
				}
				for ($i=0; $i < $assessment_count_stars; $i++){
					$assessment_stars_text_array[$i] = 'active';
				}
			}

			return $assessment_stars_text_array;
		}

		protected function setItem() {
			// try to set the item
			if(!empty($_GET['iid'])) {
				$current_item_id = $_GET['iid'];
			} elseif(!empty($_POST['pin_iid'])) {
				$current_item_id = $_POST['pin_iid'];
			} elseif(!empty($_GET['pin_iid'])) {
				$current_item_id = $_GET['pin_iid'];
			} else {
				include_once('functions/error_functions.php');
				trigger_error('An item id must be given.', E_USER_ERROR);
			}

			$item_manager = $this->_environment->getItemManager();
			$type = $item_manager->getItemType($_GET['iid']);
			$this->_manager = $this->_environment->getManager($type);
			$this->_item = $this->_manager->getItem($current_item_id);
		}

		/**
		 * get data for buzzword portlet
		 */
		protected function getBuzzwords() {
			$return = array();

			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
			$text_converter = $this->_environment->getTextConverter();

			$buzzword_list = $this->_item->getBuzzwordList();
			$buzzword_entry = $buzzword_list->getFirst();
			$item_id_array = array();
			while($buzzword_entry) {
				$item_id_array[] = $buzzword_entry->getItemID();

				$buzzword_entry = $buzzword_list->getNext();
			}

			$links_manager = $this->_environment->getLinkManager();
			if(isset($item_id_array[0])) {
				$count_array = $links_manager->getCountLinksFromItemIDArray($item_id_array, 'buzzword');
			}

			$buzzword_entry = $buzzword_list->getFirst();
			while($buzzword_entry) {
				$count = 0;
				if(isset($count_array[$buzzword_entry->getItemID()])) {
					$count = $count_array[$buzzword_entry->getItemID()];
				}
				$return[] = array(
							'to_item_id'		=> $buzzword_entry->getItemID(),
							'name'				=> $text_converter->text_as_html_short($buzzword_entry->getName()),
							'class_id'			=> $this->getBuzzwordSizeLogarithmic($count, 0, 30, 1, 4),
							'selected_id'		=> $buzzword_entry->getItemID()
						);


				$buzzword_entry = $buzzword_list->getNext();
			}

			return $return;
		}

		/**
		 * wrapper for recursive tag call
		 */
		protected function getTags() {
			// get ids of tags associated with this item
			$item_tag_list = $this->_item->getTagList();
			$item_tag = $item_tag_list->getFirst();
			$item_tag_id_array = $item_tag_list->getIDArray();

			// get all tags like common
			$tag_array = parent::getTags();

			// mark tags
			$this->markTags($tag_array, $item_tag_id_array);

			return $tag_array;
		}

		protected function getEditActions($item, $user, $module = '') {
			$return = array(
				'edit'		=> false,
				'delete'	=> false);

			if($item->mayEdit($user) && $this->_with_modifying_actions) {
				$return['edit'] = true;

				if(empty($module)) $module = $this->_environment->getCurrentModule();
				$return['edit_module'] = $module;
			} else {



				/*
				 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_EDIT_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
				 */
			}

			return $return;
		}

		protected function getItemFileList() {
			if($this->_item_file_list === null) {
	          if ( isset($this->_item) ) {
	            if ( $this->_item->isA(CS_MATERIAL_TYPE) ) {
	               $file_list = $this->_item->getFileListWithFilesFromSections();
	            } elseif ( $this->_item->isA(CS_DISCUSSION_TYPE) ) {
	               $file_list = $this->_item->getFileListWithFilesFromArticles();
	            } elseif ( $this->_item->isA(CS_TODO_TYPE) ) {
	               $file_list = $this->_item->getFileListWithFilesFromSteps();
	            } else {
	               $file_list = $this->_item->getFileList();
	            }
	          } else {
	            if ($this->_environment->getCurrentModule() == 'home') {
	               $current_context_item = $this->_environment->getCurrentContextItem();
	               if ($current_context_item->withInformationBox()){
	                  $id = $current_context_item->getInformationBoxEntryID();
	                  $manager = $this->_environment->getItemManager();
	                  $item = $manager->getItem($id);
	                  $entry_manager = $this->_environment->getManager($item->getItemType());
	                  $entry = $entry_manager->getItem($id);
	                  $file_list = $entry->getFileList();
	               }
	            } else {
	               $file_list = $this->_environment->getCurrentContextItem()->getFileList();
	            }
	         }
	         if ( isset($this->_item) and $this->_item->isA(CS_SECTION_TYPE) ) {
	            $material_item = $this->_item->getLinkedItem();
	            $file_list2 = $material_item->getFileList();
	            if ( isset($file_list2) and !empty($file_list2) and $file_list2->getCount() > 0 ) {
	               $file_list->addList($file_list2);
	            }
	            unset($file_list2);
	            unset($material_item);
	         }
	         if ( !empty($file_list) ) {
	            $file_array = $file_list->to_Array();
	            unset($file_list);
	            $file_name_array = array();
	            foreach ($file_array as $file) {
	               $file_name_array[htmlentities($file->getDisplayName(), ENT_NOQUOTES, 'UTF-8')] = $file;
	            }
	            unset($file_array);
	            $this->_item_file_list = $file_name_array;
	            unset($file_name_array);
	         }
	      }
	      return $this->_item_file_list;
		}

		protected function markAnnotationsReadedAndNoticed(&$annotation_list) {
			$reader_manager = $this->_environment->getReaderManager();
			$noticed_manager = $this->_environment->getNoticedManager();

			// collect an array of all ids and precach
			$id_array = array();
			$annotation = $annotation_list->getFirst();
			while($annotation) {
				$id_array[] = $annotation->getItemID();

				$annotation = $annotation_list->getNext();
			}

			$reader_manager->getLatestReaderByIDArray($id_array);
			$noticed_manager->getLatestNoticedByIDArray($id_array);

			// mark if needed
			$annotation = $annotation_list->getFirst();
			while($annotation) {
				$reader = $reader_manager->getLatestReader($annotation->getItemID());
				if(empty($reader) || $reader['read_date'] < $annotation->getModificationDate()) {
					$reader_manager->markRead($annotation->getItemID(), 0);
				}

				$noticed = $noticed_manager->getLatestNoticed($annotation->getItemID());
				if(empty($noticed) || $noticed['read_date'] < $annotation->getModificationDate()) {
					$noticed_manager->markNoticed($annotation->getItemID(), 0);
				}

				$annotation = $annotation_list->getNext();
			}
		}

		protected function getNetnavigation() {
			$return = array();

			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUser();
			$translator = $this->_environment->getTranslationObject();
			$converter = $this->_environment->getTextConverter();
			if($this->_item === null) $this->setItem();
			$link_items = $this->_item->getAllLinkItemList();

			// remove items from list the current user is not allowed to see or ???
			$count_item = $link_items->getFirst();
			while($count_item) {
				$linked_item = $count_item->getLinkedItem($this->_item);
				if(isset($linked_item)) {
					$type = $linked_item->getType();
				}

				$module = Type2Module($type);
				if($module === CS_USER_TYPE && (!$linked_item->isUser() || !$linked_item->maySee($current_user))) {
					$link_items->removeElement($countItem);
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
						$linked_item = $link_item->getLinkedItem($this->_item);
						if(isset($linked_item)) {
							$type = $linked_item->getType();
							if($type === 'label') {
								$type = $linked_item->getLabelType();
							}

							$link_created = $translator->getDateInLang($link_item->getCreationDate());

							switch(mb_strtoupper($type, 'UTF-8')) {
								case 'ANNOUNCEMENT':
									$text = $translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
									$img = 'images/commsyicons/netnavigation/announcement.png';
									break;
								case 'DATE':
									$text = $translator->getMessage('COMMON_ONE_DATE');
									$img = 'images/commsyicons/netnavigation/date.png';
									break;
								case 'DISCUSSION':
									$text = $translator->getMessage('COMMON_ONE_DISCUSSION');
									$img = 'images/commsyicons/netnavigation/discussion.png';
									break;
								case 'GROUP':
									$text = $translator->getMessage('COMMON_ONE_GROUP');
									$img = 'images/commsyicons/netnavigation/group.png';
									break;
								case 'INSTITUTION':
									$text = $translator->getMessage('COMMON_ONE_INSTITUTION');
									$img = '';
									break;
								case 'MATERIAL':
									$text = $translator->getMessage('COMMON_ONE_MATERIAL');
									$img = 'images/commsyicons/netnavigation/material.png';
									break;
								case 'PROJECT':
									$text = $translator->getMessage('COMMON_ONE_PROJECT');
									$img = '';
									break;
								case 'TODO':
									$text = $translator->getMessage('COMMON_ONE_TODO');
									$img = 'images/commsyicons/netnavigation/todo.png';
									break;
								case 'TOPIC':
									$text = $translator->getMessage('COMMON_ONE_TOPIC');
									$img = 'images/commsyicons/netnavigation/topic.png';
									break;
								case 'USER':
									$text = $translator->getMessage('COMMON_ONE_USER');
									$img = 'images/commsyicons/netnavigation/user.png';
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

							$entry['linked_iid'] = $linked_iid;

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

			$return['edit'] = false;
			if($current_user->isUser() && $this->_with_modifying_actions) {
				$return['edit'] = true;

				$params = $this->_environment->getCurrentParameterArray();
				$params['attach_view'] = 'yes';
				$params['attach_type'] = 'item';

				$link = 'commsy.php?cid=' . $this->_environment->getCurrentContextID() . '&mod=' . $this->_environment->getCurrentModule() . '&fct=' . $this->_environment->getCurrentFunction();
				foreach($params as $key => $value) {
					$link .= '&' . $key . '=' . $value;
				}

				$return['edit_link'] = $link;
			}

			return $return;
		}

		protected function getItemPicture($item) {
			$return = array();

			if(isset($item)) {
				$picture = $item->getPicture();
				$linktext = '';

				if(!empty($picture)) {
					$disc_manager = $this->_environment->getDiscManager();
					$height = 60;
					if($disc_manager->existsFile($picture)) {
						$image_array = getimagesize($disc_manager->getFilePath() . $picture);
						$pict_height = $image_array[1];
						if($pict_height > 60) {
							$height = 60;
						} else {
							$height = $pict_height;
						}
					}

					if($item->isA(CS_USER_TYPE)) {
						$linktext = str_replace('"', '&quot;', encode(AS_HTML_SHORT, $item->getFullName()));
					}

					$return = array(
						'picture'			=> $picture,
						'width'				=> $height,
						'linktext'			=> $linktext
					);

					// TODO:	in template file:
					//			if linktext is empty set USER_PICTURE_UPLOADFILE as linktext
				} else {
					// no picture

					if($item->isA(CS_USER_TYPE)) {
						$linktext = str_replace('"', '&quot;', encode(AS_HTML_SHORT, $item->getFullName()));

						// TODO:	in template file
						//			use i18n USER_PICTURE_NO_PICTURE with param1 linktext
						//			or if linktext is empty
						//			USER_PICTURE_UPLOADFILE
					}
				}
			}

			return $return;
		}

		abstract protected function getAdditionalActions($perms);

		private function getDetailActions() {
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();

			$return = array(
				'edit'		=> false,
				'delete'	=> false,
				'mail'		=> false,
				'copy'		=> false,
				'new'		=> false
			);

			// edit
			if($this->_item->mayEdit($current_user) && $this->_with_modifying_actions) {
				$return['edit'] = true;
				/*
				 * if ( empty($module) ) {
		            $module = $this->_environment->getCurrentModule();
		         }
		         $params = array();
		         $params['iid'] = $item->getItemID();
		         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
		            $image = '<img src="images/commsyicons_msie6/22x22/edit.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
		         } else {
		            $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
		         }
		         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
		                                          $module,
		                                          'edit',
		                                          $params,
		                                          $image,
		                                          $this->_translator->getMessage('COMMON_EDIT_ITEM')).LF;
		         unset($params);
				 */
			} else {
				/*
				* if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
		            $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
		         } else {
		            $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
		         }
		         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_EDIT_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
				 */
			}

			// delete
			if($this->_item->mayEdit($current_user) && $this->_with_modifying_actions && (!$this->_item->isA(CS_LABEL_TYPE) || !$this->_item->isSystemLabel())) {
				$return['delete'] = true;

				/*
				 * $params = $this->_environment->getCurrentParameterArray();
         $params['action'] = 'delete';
         if($item->getItemType() == CS_DATE_TYPE){
            if($item->getRecurrenceId() != '' and $item->getRecurrenceId() != 0){
               $params['recurrence_id'] = $item->getRecurrenceId();
            }
         }
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                          $this->_environment->getCurrentModule(),
                              'detail',
                          $params,
                          $image,
                          $this->_translator->getMessage('COMMON_DELETE_ITEM').LF,
                              '',
                              '',
                              '',
                              '',
                              '',
                              '',
                              '',
                              'delete_confirm_entry');
         unset($params);
				 */
			} else {
				/*
				 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_DELETE_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
				 */
			}

			$this->getAdditionalActions($return);

			// mail
			if(!$this->_environment->inPrivateRoom()) {
				$module = 'rubric';
				//$text = $this->_translator->getMessage('COMMON_EMAIL_TO');

				if($current_user->isUser() && $this->_with_modifying_actions) {
					$return['mail'] = true;
					/*
					 * $params = array();
         $params['iid'] = $item->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/mail.gif" style="vertical-align:bottom;" alt="'.$text.'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/mail.png" style="vertical-align:bottom;" alt="'.$text.'"/>';
         }
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                               $module,
                               'mail',
                               $params,
                               $image,
                               $text).LF;
         unset($params);
					 */
				} else {
					/*
					 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/mail_grey.gif" style="vertical-align:bottom;" alt="'.$text.'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/mail_grey.png" style="vertical-align:bottom;" alt="'.$text.'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$text).' "class="disabled">'.$image.'</a>'.LF;
					 */
				}
			}

			// copy
			if($current_user->isUser() && !in_array($this->_item->getItemID(), $this->_clipboard_id_array)) {
				$return['copy'] = true;

				/*
				 * $params = array();
         $params['iid'] = $item->getItemID();
         $params['add_to_'.$this->_environment->getCurrentModule().'_clipboard'] = $item->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/copy.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/copy.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD').'"/>';
         }
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD')).LF;
         unset($params);
				 */
			} else {
				/*
				 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/copy_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/copy_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD')).' "class="disabled">'.$image.'</a>'.LF;
				 */
			}

			// TODO: dont forget print, download - which are always allowed

			// TODO:  // actions from rubric plugins
      		//$html .= plugin_hook_output_all('getDetailActionAsHTML',NULL,LF);

			// new
			$current_module = $this->_environment->getCurrentModule();

			if($current_user->isUser() && $this->_with_modifying_actions && $current_module != CS_USER_TYPE) {
				$return['new'] = true;
				/*
				 * $params = array();
         $params['iid'] = 'NEW';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon"/>';
         }
         $html .= '&nbsp;&nbsp;&nbsp;'.ahref_curl(  $this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'edit',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('COMMON_NEW_ITEM')).LF;
         unset($params);
				 */
			} else {
				//$html .= $this->_getNewActionDisabled();
			}

			//TODO:
			//$html .= $this->_initDropDownMenus();

			return $return;
		}

		protected function getAnnotationInformation($annotation_list) {
			$return = array();

			$item = $this->_item;
			$converter = $this->_environment->getTextConverter();
			$current_user = $this->_environment->getCurrentUser();

			$count = $annotation_list->getCount();
			if(!(isset($_GET['mode']) && $_GET['mode'] === 'print') || $count > 0) {
				// TODO: add annotation heading to template, specified like here
				/*
				 * if ( !empty($this->_annotation_list) ){
            $count = $this->_annotation_list->getCount();
            if ($count == 1){
               $desc = ' ('.$this->_translator->getMessage('COMMON_ONE_ANNOTATION');
            }else{
               $desc = ' ('.$this->_translator->getMessage('COMMON_X_ANNOTATIONS',$count);
            }
         }else{
            $desc = ' ('.$this->_translator->getMessage('COMMON_NO_ANNOTATIONS');
         }
				 */

				// TODO: get read and noticed information
				// use prefetch
				//$reader_manager->getLatestReaderByIDArray($id_array);
   				//$noticed_manager->getLatestNoticedByIDArray($id_array);

				if(!empty($annotation_list)) {
					$annotation = $annotation_list->getFirst();
					$pos_number = 1;

					while($annotation) {
						// get item picture
						$modificator_ref = $annotation->getModificatorItem();

						//$html .= $this->_text_as_html_short($this->_compareWithSearchText($subitem->getTitle()));
						$subitem_title = $annotation->getTitle();
						$subitem_title = $converter->text_as_html_short($subitem_title);

						/*
						 *

		                  if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
		                     $html .='<div style="float:right; height:6px; font-size:2pt;">'.LF;
		                     $html .= $this->_getAnnotationBrowsingIconsAsHTML($current_item, $pos_number,$count);
		                     $html .='</div>'.LF;
		                  }

		                  */




						$annotated_item = $this->_item;
						$desc = $annotation->getDescription();
						if(!empty($desc)) {
							$desc = $converter->cleanDataFromTextArea($desc);
							$converter->setFileArray($this->getItemFileList());
							$desc = $converter->text_as_html_long($desc);
							//$html .= $this->getScrollableContent($desc,$item,'',true);
						}

						$current_version = $annotated_item->getVersionID();
						$annotated_version = $annotation->getAnnotatedVersionID();


						/*
					      if ( $current_version > $annotated_version ) {
					         $text = '('.$this->_translator->getMessage('ANNOTATION_FOR_OLDER_VERSION').')';
					      } elseif ( $current_version < $annotated_version ) {
					         $text = '('.$this->_translator->getMessage('ANNOTATION_FOR_NEWER_VERSION').')';
					      } else {
					         $text = '';
					      }



					      if ( !empty ($text) ) {
					         $html .= '<p class="disabled" style="margin-left:3px;">'.$text.'</p>'.LF;
					      }
					      $html .= '   </div>'.LF;
					     */

						$return[] = array(
							'image'				=> $this->getItemPicture($modificator_ref),
							'pos_number'		=> $pos_number,
							'item_id'			=> $annotation->getItemID(),
							'title'				=> $subitem_title,
							'description'		=> $desc,
							'creator'			=> $annotation->getCreatorItem()->getFullName(),
							'actions'			=> $this->getAnnotationEditActions($annotation),
							'num_attachments'	=> $annotation->getFileList()->getCount()
						);

						$pos_number++;
						$annotation = $annotation_list->getNext();
					}
				}
			}

			return $return;
		}

		private function getAnnotationEditActions($item=null) {
			$return = array(
				'edit'		=> false,
				'delete'	=> false);

			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
			$annotated_item = $this->_item;
			$annotated_item_type = $annotated_item->getItemType();
			$item_manager = $this->_environment->getItemManager();

			if(($item->mayEdit($current_user) || $item_manager->getExternalViewerForItem($annotated_item->getItemID(), $current_user->getUserID())) && $this->_with_modifying_actions === true) {
				// TODO:	insert in template
				//			mod: annotation, fct: edit, params(iid => $item->getItemID(), mode => 'annotate')
				//			message_tag: COMMON_EDIT_ITEM
				$return['edit'] = true;

				// TODO:	insert in template
				//			mod: current_mod, fct: detail, params(current_params, action => detail, annotation_iid => $item->getItemID(),
				//			iid => $annotated_item->getItemID(), annotation_action => delete)
				//			message_tag: COMMON_DELETE_ITEM
				$return['delete'] = true;

			} else {
				/*
				 * else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_EDIT_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
				 */

				/*
				 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_DELETE_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
				 */
			}

			return $return;
		}

		protected function showNetnavigation(){
	      $context_item = $this->_environment->getCurrentContextItem();
	      if ($context_item->withNetnavigation()
	          and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
	                or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
	                or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
	                or $this->_environment->getCurrentModule() == CS_TODO_TYPE
	                or $this->_environment->getCurrentModule() == CS_DATE_TYPE
	                or $this->_environment->getCurrentModule() == CS_GROUP_TYPE
	                or $this->_environment->getCurrentModule() == CS_TOPIC_TYPE
	                or $this->_environment->getCurrentModule() == CS_INSTITUTION_TYPE
	                or ($this->_environment->getCurrentModule() == CS_USER_TYPE and ($context_item->withRubric(CS_GROUP_TYPE) or($context_item->withRubric(CS_INSTITUTION_TYPE))))
	                or $this->_environment->getCurrentModule() == 'campus_search')
	      ) {
	         return true;
	      }

	      return false;
		}

		private function markTags(&$tag_array, $item_tag_id_array) {
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

		private function getForwardInformation($ids) {
			$return = array();

			$converter = $this->_environment->getTextConverter();

			if(empty($ids)) {
				$ids = array();
				$ids[] = $this->_item->getItemID();
			}

			// determe item positions for forward box
			$count = 0;
			$pos = 0;
			foreach($ids as $id) {
				if($id == $this->_item->getItemID()) {
					$pos = $count;
				} else {
					$count++;
				}
			}

			$start = $pos - 4;
			$end = $pos + 4;
			if($start < 0) {
				$end -= $start;
			}
			if($end > count($ids)) {
				$end = count($ids);
				$start = $end - 9;
				if($start < 0) {
					$start = 0;
				}
			}

			// get information
			$listed_ids = array();
			$count_items = 0;
			$i = 1;
			foreach($ids as $id) {
				if($count_items >= $start && $count_items <= $end) {
					$item_manager = $this->_environment->getItemManager();
					$tmp_item = $item_manager->getItem($id);
					//$text = '';
					if(isset($tmp_item)) {
						$manager = $this->_environment->getManager($tmp_item->getItemType());
						$item = $manager->getItem($ids[$count_items]);
						$type = $tmp_item->getItemType();
						if($type == 'label') {
							$label_manager = $this->_environment->getLabelManager();
							$label_item = $label_manager->getItem($tmp_item->getItemID());
							$type = $label_item->getLabelType();
						}

						/*
								switch ( mb_strtoupper($type, 'UTF-8') ){
		                  case 'ANNOUNCEMENT':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
		                     break;
		                  case 'DATE':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_DATE');
		                     break;
		                  case 'DISCUSSION':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_DISCUSSION');
		                     break;
		                  case 'GROUP':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_GROUP');
		                     break;
		                  case 'INSTITUTION':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_INSTITUTION');
		                     break;
		                  case 'MATERIAL':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_MATERIAL');
		                     break;
		                  case 'PROJECT':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_PROJECT');
		                     break;
		                  case 'TODO':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_TODO');
		                     break;
		                  case 'TOPIC':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_TOPIC');
		                     break;
		                  case 'USER':
		                     $text .= $this->_translator->getMessage('COMMON_ONE_USER');
		                     break;
		                  case 'ACCOUNT':
		                     $text .= $this->_translator->getMessage('COMMON_ACCOUNTS');
		                     break;
		                  default:
		                     $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
		                     break;
		               }
						*/
					}

					$link_title = '';
					if(isset($item) && is_object($item) && $item->isA(CS_USER_TYPE)) {
						$link_title = $item->getFullName();
					} elseif(isset($item) && is_object($item)) {
						$link_title = $item->getTitle();
					}

					// append to return
					$return[] = array(
						'title'			=> $converter->text_as_html_short($link_title),
						'is_current'	=> $item->getItemID() == $this->_item->getItemID(),
						'item_id'		=> $item->getItemID(),
						'position'		=> $count_items + 1
					);



					/*
				 *

		            if ($this->_environment->getCurrentModule() == 'account'){
		               $type = 'account';
		            } elseif ( $this->_environment->getCurrentModule() == type2module(CS_MYROOM_TYPE) ) {
		               $type = CS_MYROOM_TYPE;
		            }
		            if ($count_items < 9){
		               $style='padding:0px 5px 0px 10px;';
		            }else{
		                $style='padding:0px 5px 0px 5px;';
		            }
		            */

					$current_user_item = $this->_environment->getCurrentUserItem();
					if(isset($item) && $item->getItemID() === $this->_item->getItemID()) {
						/*
						$html .='<li class="detail_list_entry" style="'.$style.'">';
               $html .= '<span>'.($count_items+1).'. '.chunkText($link_title,35).'</span>';
               $html .='</li>';
               			*/
					} elseif(isset($item) && $item->isNotActivated() && !($item->getCreatorID() === $current_user_item->getItemID()) && !$current_user_item->isModerator()) {
						/*
						 $activating_date = $item->getActivatingDate();
               if (strstr($activating_date,'9999-00-00')){
                  $activating_text = $this->_translator->getMessage('COMMON_NOT_ACTIVATED');
               }else{
                  $activating_text = $this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
               }
               $html .='<li class="disabled" style="'.$style.'">';
               $params['iid'] =   $item->getItemID();
               $html .= ($count_items+1).'. '.ahref_curl( $this->_environment->getCurrentContextID(),
                                 $type,
                                 $this->_environment->getCurrentFunction(),
                                 $params,
                                 chunkText($link_title,35),
                                 $text.' - '.$link_title . '&nbsp;(' . $activating_text . ')',
                                 '',
                                 '',
                                 '',
                                 '',
                                 '',
                                 'class="disabled"',
                                 '',
                                 '',
                                 true);
               $html .='</li>';
						*/
					} elseif(isset($item)) {
						/*
						$html .='<li style="'.$style.'">';
               $params['iid'] =   $item->getItemID();
               $html .= ($count_items+1).'. '.ahref_curl( $this->_environment->getCurrentContextID(),
                                 $type,
                                 $this->_environment->getCurrentFunction(),
                                 $params,
                                 chunkText($link_title,35),
                                 $text.' - '.$link_title,
                                 '',
                                 '',
                                 '',
                                 '',
                                 '',
                                 'class="detail_list"');
               $html .='</li>';
               */
					}

					unset($item);
				}
				$count_items++;
			}

			if(isset($_GET['path']) && !empty($_GET['path'])) {
				$topic_manager = $this->_environment->getTopicManager();
				$topic_item = $topic_manager->getItem($_GET['path']);
				/*
				$params = array();
         $params['iid'] = $_GET['path'];
         $html .= $this->_translator->getMessage('COMMON_BACK_TO_PATH').': '.ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_TOPIC_TYPE,
                           'detail',
                           $params,
                           chunkText($topic_item->getTitle(),30)
                           );
                */
			} elseif(isset($_GET['search_path']) && !empty($_GET['search_path'])) {
				/*
				 $params = array();
         $params['iid'] = $_GET['path'];
         $html .= $this->_translator->getMessage('COMMON_BACK_TO_PATH').': '.ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_TOPIC_TYPE,
                           'detail',
                           $params,
                           chunkText($topic_item->getTitle(),30)
                           );
				 */
			} elseif(isset($_GET['link_item_path']) && !empty($_GET['link_item_path'])) {
				/*
				$params = array();
         $params['iid'] = $_GET['link_item_path'];
         $item_manager = $this->_environment->getItemManager();
         $tmp_item = $item_manager->getItem($_GET['link_item_path']);
         $manager = $this->_environment->getManager($tmp_item->getItemType());
         $item = $manager->getItem($_GET['link_item_path']);
         $type = $tmp_item->getItemType();
         if ($type == 'label'){
            $label_manager = $this->_environment->getLabelManager();
            $label_item = $label_manager->getItem($tmp_item->getItemID());
            $type = $label_item->getLabelType();
         }
         $manager = $this->_environment->getManager($type);
         $item = $manager->getItem($_GET['link_item_path']);
         if($type == CS_USER_TYPE){
             $link_title = $this->_text_as_html_short($item->getFullName());
         } else {
             $link_title = $this->_text_as_html_short($item->getTitle());
         }
         $html .= $this->_translator->getMessage('COMMON_BACK_TO_ITEM').': '.ahref_curl( $this->_environment->getCurrentContextID(),
                           $type,
                           'detail',
                           $params,
                           chunkText($link_title,20),
                           $link_title
                           );
				 */
			} else {
				/*
				  $display_mod = $this->_environment->getValueOfParameter('seldisplay_mode');
         if ( empty($display_mod) ) {
            $session = $this->_environment->getSessionItem();
            if ( $session->issetValue($this->_environment->getCurrentContextID().'_dates_seldisplay_mode') ) {
               $display_mod = $session->getValue($this->_environment->getCurrentContextID().'_dates_seldisplay_mode');
            }
         }
         $params = array();
         $params['back_to_index'] = 'true';
         $link_text = $this->_translator->getMessage('COMMON_BACK_TO_LIST');
         $link_module = $this->_environment->getCurrentModule();
         if ( module2type($this->_environment->getCurrentModule()) == CS_DATE_TYPE
              and !empty($display_mod)
              and $display_mod == 'calendar'
            ) {
            $link_text = $this->_translator->getMessage('DATE_BACK_TO_CALENDAR');
         }
         if ( module2type($this->_environment->getCurrentModule()) == CS_DATE_TYPE
              and $this->_environment->inPrivateRoom()
              and $this->_environment->getConfiguration('c_use_new_private_room')
            ) {
            $link_text = $this->_translator->getMessage('COMMON_BACK_TO_INDEX');
         }
         if ( module2type($this->_environment->getCurrentModule()) == CS_TODO_TYPE
              and $this->_environment->inPrivateRoom()
              and $this->_environment->getConfiguration('c_use_new_private_room')
            ) {
            $link_text = $this->_translator->getMessage('COMMON_BACK_TO_INDEX');
            $link_module = type2module(CS_DATE_TYPE);
         }
         if ( $this->_environment->inPrivateRoom()
              and $this->_environment->getConfiguration('c_use_new_private_room')
              and ( module2type($this->_environment->getCurrentModule()) == CS_MATERIAL_TYPE
                    or module2type($this->_environment->getCurrentModule()) == CS_DISCUSSION_TYPE
                    or module2type($this->_environment->getCurrentModule()) == CS_ANNOUNCEMENT_TYPE
                    or module2type($this->_environment->getCurrentModule()) == CS_TOPIC_TYPE
                  )
            ) {
            $link_text = $this->_translator->getMessage('COMMON_BACK_TO_INDEX');
            $link_module = type2module(CS_ENTRY_TYPE);
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                           $link_module,
                           'index',
                           $params,
                           $link_text
                           );
				 */
			}

			return $return;
		}

		private function getBrowseInformation($ids, $forward_type = '') {
			$return = array();
			$paging = array();
			$paging['first']['active'] = false;
			$paging['prev']['active'] = false;
			$paging['next']['active'] = false;
			$paging['last']['active'] = false;

			// update position from GET-Vars
			if(isset($_GET['pos'])) {
				$this->_position = $_GET['pos'];
			}

			// get all non-active item ids
			$ids_not_activated = array();
			$item_manager = $this->_environment->getItemManager();
			$item_manager->resetLimits();
			$item_manager->setContextLimit($this->_environment->getCurrentContextID());
			$item_manager->setIDArrayLimit($ids);
			$item_manager->select();

			$item_list = $item_manager->get();
			$temp_item = $item_list->getFirst();
			while($temp_item) {
				if($temp_item->isNotActivated()) {
					$ids_not_activated[] = $temp_item->getItemID();
				}

				$temp_item = $item_list->getNext();
			}
			$item_manager->resetLimits();

			$count_all = count($ids);

			// determe the position if not (correctly) given
			if($this->_position < 0 || $this->_position >= $count_all) {
				if(empty($ids)) {
					$this->_position = -1;
				} else {
					if(isset($this->_item)) {
						$pos = array_search($this->_item->getItemID(), $ids);
						if($pos === null || $pos === false) {
							$pos = -1;
						}
					} else {
						$pos = -1;
					}

					$this->_position = $pos;
				}
			}

			// determe index position values
			$pos_index_start = 0;
			$pos_index_left = $this->_position - 1;
			$pos_index_right = $this->_position + 1;
			$pos_index_end = $count_all - 1;

			// prepare browsing
			$browse_left = 0;		// 0 means: do not browse
			$browse_start = 0;		// 0 means: do not browse
			if($this->_position > 0) {
				// check for browsing to the left / start
				for($index = $this->_position - 1; $index >= 0; $index--) {
					if(in_array($ids[$index], $ids_not_activated)) {
						$pos_index_left--;
					} else {
						break;
					}
				}

				if($pos_index_left >= 0) {
					$browse_left = $ids[$pos_index_left];
				}

				for($index = 0, $max_count = $this->_position - 1; $index <= $max_count; $index++) {
					if(in_array($ids[$index], $ids_not_activated)) {
						$pops_index_start++;
					} else {
						break;
					}
				}

				if($pos_index_left >= 0) {
					$browse_start = $ids[$pos_index_start];
				}
			}

			$browse_right = 0;		// 0 means: do not browse
			$browse_end = 0;		// 0 means: do not browse
			if($this->_position >= 0 && $this->_position < $count_all - 1) {
				// check for browsing to the right / end
				for($index = $this->_position + 1, $max_count = $count_all - 1; $index <= $max_count; $index++) {
					if(in_array($ids[$index], $ids_not_activated)) {
						$pos_index_right++;
					} else {
						break;
					}
				}

				if($pos_index_right < sizeof($ids)) {
					$browse_right = $ids[$pos_index_right];
				}

				for($index = $count_all - 1, $max_count = $this->_position + 1; $index >= $max_count; $index--) {
					if(in_array($ids[$index], $ids_not_activated)) {
						$pos_index_end--;
					} else {
						break;
					}
				}

				if($pos_index_right < sizeof($ids)) {
					$browse_end = $ids[$pos_index_end];
				}
			}

			// browse first
			if($browse_start > 0) {
				$params = $this->_environment->getCurrentParameterArray();
				unset($params[$this->_environment->getCurrentModule() . '_option']);
         		unset($params['add_to_' . $this->_environment->getCurrentModule() . '_clipboard']);
         		$params['iid'] = $browse_start;
         		$params['pos'] = $pos_index_start;

         		if(!empty($forward_type) && ($forward_type === 'path' || $forward_type == 'search')) {
         			$item = $item_manager->getItem($browse_start);
         			$module = $item->getItemType();
         			if($module === 'label') {
         				$label_manager = $this->_environment->getLabelManager();
         				$label_item = $label_manager->getItem($item->getItemID());
         				$module = $label_item->getLabelType();
         			}
         		} else {
         			$module = $this->_environment->getCurrentModule();
         		}

         		$paging['first']['active'] = true;
         		$paging['first']['module'] = $module;
         		$paging['first']['params'] = $params;
         		/*
         		$html .= ahref_curl($this->_environment->getCurrentContextID(),$module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_START_DESC'),
                                   '','','','','','class="detail_system_link"').LF;
                unset($params);
                */
			}

			// browse left
			if($browse_left > 0) {
				$params = $this->_environment->getCurrentParameterArray();
				unset($params[$this->_environment->getCurrentModule() . '_option']);
         		unset($params['add_to_' . $this->_environment->getCurrentModule() . '_clipboard']);
         		$params['iid'] = $browse_left;
         		$params['pos'] = $pos_index_left;

         		if(!empty($forward_type) && ($forward_type === 'path' || $forward_type === 'search')) {
         			$item = $item_manager->getItem($browse_left);
         			if($module === 'label') {
         				$label_manager = $this->_environment->getLabelManager();
         				$label_item = $label_manager->getItem($item->getItemID());
         				$module = $label_item->getLabelType();
         			}
         		} else {
         			$module = $this->_environment->getCurrentModule();
         		}

         		$paging['prev']['active'] = true;
         		$paging['prev']['module'] = $module;
         		$paging['prev']['params'] = $params;
			}

			// browse right
			if($browse_right > 0) {
				$params = $this->_environment->getCurrentParameterArray();
				unset($params[$this->_environment->getCurrentModule() . '_option']);
         		unset($params['add_to_' . $this->_environment->getCurrentModule() . '_clipboard']);
         		$params['iid'] = $browse_right;
         		$params['pos'] = $pos_index_right;

         		if(!empty($forward_type) && ($forward_type === 'path' || $forward_type === 'search' || $forward_type === 'link_item')) {
         			$item = $item_manager->getItem($browse_right);
         			if($module === 'label') {
         				$label_manager = $this->_environment->getLabelManager();
         				$label_item = $label_manager->getItem($item->getItemID());
         				$module = $label_item->getLabelType();
         			}
         		} else {
         			$module = $this->_environment->getCurrentModule();
         		}

         		$paging['next']['active'] = true;
         		$paging['next']['module'] = $module;
         		$paging['next']['params'] = $params;
			}

			// browse end
			if($browse_end > 0) {
				$params = $this->_environment->getCurrentParameterArray();
				unset($params[$this->_environment->getCurrentModule() . '_option']);
         		unset($params['add_to_' . $this->_environment->getCurrentModule() . '_clipboard']);
         		$params['iid'] = $browse_end;
         		$params['pos'] = $pos_index_end;

         		if(!empty($forward_type) && ($forward_type === 'path' || $forward_type === 'search')) {
         			$item = $item_manager->getItem($browse_right);
         			if($module === 'label') {
         				$label_manager = $this->_environment->getLabelManager();
         				$label_item = $label_manager->getItem($item->getItemID());
         				$module = $label_item->getLabelType();
         			}
         		} else {
         			$module = $this->_environment->getCurrentModule();
         		}

         		$paging['last']['active'] = true;
         		$paging['last']['module'] = $module;
         		$paging['last']['params'] = $params;
			}

			/**
			 *

      $html .= '</div>';
      $html .= '<div id="right_box_page_numbers">';
      if (!empty($forward_type) and $forward_type =='path'){
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_PATH_ENTRIES').' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_PATH_ENTRIES').' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
      }elseif(!empty($forward_type) and $forward_type =='search'){
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_SEARCH_ENTRIES').' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_SEARCH_ENTRIES').' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
      }elseif(!empty($forward_type) and $forward_type =='link_item'){
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_REFERENCED_ENTRIES').' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_REFERENCED_ENTRIES').' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
       }else{
         switch ( mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') ){
            case 'ANNOUNCEMENT':
               $text = $this->_translator->getMessage('COMMON_ANNOUNCEMENT');
               break;
            case 'DATE':
               $text = $this->_translator->getMessage('COMMON_DATE');
               break;
            case 'DISCUSSION':
               $text = $this->_translator->getMessage('COMMON_DISCUSSION');
               break;
            case 'GROUP':
               $text = $this->_translator->getMessage('COMMON_GROUP');
               break;
            case 'INSTITUTION':
               $text = $this->_translator->getMessage('COMMON_INSTITUTION');
               break;
            case 'MATERIAL':
               $text = $this->_translator->getMessage('COMMON_MATERIAL');
               break;
            case 'MATERIAL_ADMIN':
               $text = $this->_translator->getMessage('COMMON_MATERIAL');
               break;
            case 'PROJECT':
               $text = $this->_translator->getMessage('COMMON_PROJECT');
               break;
            case 'TODO':
               $text = $this->_translator->getMessage('COMMON_TODO');
               break;
            case 'TOPIC':
               $text = $this->_translator->getMessage('COMMON_TOPIC');
               break;
            case 'USER':
               $text = $this->_translator->getMessage('COMMON_USER');
               break;
            case 'MYROOM':
               $text = $this->_translator->getMessage('COMMON_ROOM');
               break;
            case 'ACCOUNT':
               $text = $this->_translator->getMessage('COMMON_ACCOUNTS');
            break;            default:
               $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' '.__FILE__.'('.__LINE__.') ' );
               break;
         }
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$text.' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$text.' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
      }
      $html .= '';
      $html .= '</div>';
*/
//      return /*$this->_text_as_html_short(*/$html/*)*/;





			// build return
			$return = array(
				'position'			=> $this->_position + 1,
				'count_all'			=> $count_all,
				'paging'			=> $paging
			);

			return $return;
		}

		private function getBrowseIDs() {
			if(sizeof($this->_browse_ids) === 0) {
				// set it
				$this->setBrowseIDs();

				if(!isset($this->_browse_ids) || sizeof($this->_browse_ids) === 0) {
					$this->_browse_ids[] = $this->_item->getItemID();
				}
			}
			return $this->_browse_ids;
		}

		protected function isPerspective($rubric) {
			$in_array = in_array($rubric, array(CS_GROUP_TYPE, CS_TOPIC_TYPE, CS_INSTITUTION_TYPE));
			if($rubric === CS_INSTITUTION_TYPE) {
				$context = $this->_environment->getCurrentContextItem();
				$in_array = $context->withRubric(CS_INSTITUTION_TYPE);
			}

			return $in_array;
		}

		protected function markRead() {
			// mark as read
			$reader_manager = $this->_environment->getReaderManager();
			$reader = $reader_manager->getLatestReader($this->_item->getItemID());
			if(empty($reader) || $reader['read_date'] < $this->_item->getModificationDate()) {
				$reader_manager->markRead($this->_item->getItemID(), 0);
			}
		}

		protected function markNoticed() {
			// mark as noticed
			$noticed_manager = $this->_environment->getNoticedManager();
			$noticed = $noticed_manager->getLatestNoticed($this->_item->getItemID());
			if(empty($noticed) || $noticed['read_date'] < $this->_item->getModificationDate()) {
				$noticed_manager->markNoticed($this->_item->getItemID(), 0);
			}
		}

		protected function setRubricConnections($array) {
			$user_manager = $this->_environment->getUserManager();
			$context_id = $this->_environment->getCurrentContextID();
			$context_item = $this->_environment->getCurrentContextItem();
			$current_room_modules = $context_item->getHomeConf();

			if(!$this->_environment->inPortal() && !$this->_environment->inServer() && $this->_environment->getCurrentModule() !== 'account') {
				$user_manager->getRoomUserByIDsForCache($context_id);
			}

			$room_modules = array();
			if(!empty($current_room_modules)) {
				$room_modules = explode(',', $current_room_modules);
			}

			foreach($room_modules as $module) {
				list($name, $view) = explode('_', $module);

				if($view !== 'none' && $context_item->withRubric($name) && $name !== CS_USER_TYPE && $name !== CS_MYROOM_TYPE) {
					$rubric_connections[] = $name;
				}
			}

			$this->_rubric_connections = $rubric_connections;
		}

		protected function setClipboardIDArray($id_array) {
			$this->_clipboard_id_array = $id_array;
		}

		abstract protected function setBrowseIDs();

		abstract protected function getDetailContent();
	}