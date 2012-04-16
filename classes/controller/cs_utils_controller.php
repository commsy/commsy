<?php
	class cs_utils_controller {
		private $_environment = null;
		
		public function __construct($environment) {
			$this->_environment = $environment;
		}
		
		public function showTags() {
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

		public function showBuzzwords() {
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
		
				/**
		 * wrapper for recursive tag call
		 */
		public function getTags() {
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
		
		/**
		 * get data for buzzword portlet
		 */
		public function getBuzzwords() {
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
		
		public function setFilesForItem(cs_item $item, $post_file_ids) {
			$session = $this->_environment->getSessionItem();
			
			// temp files
			$temp_files_array = array();
			$file_manager = $this->_environment->getFileManager();
			$file_manager->resetLimits();
			$file_manager->setTempUploadSessionIdLimit($this->_environment->getSessionId());
			$file_manager->select();
			$file_list = $file_manager->get();
			$file_item = $file_list->getFirst();
			while($file_item) {
				$temp_files_array[] = $file_item->getFileID();
				$file_manager->resetTempUpload($file_item);
				
				$file_item = $file_list->getNext();
			}
			unset($file_manager);
			
			// files
			if(isset($post_file_ids) && !empty($post_file_ids)) {
				$file_ids = $post_file_ids;
			} else {
				$file_ids = isset($_POST['filelist']) ? $_POST['filelist'] : array();
			}
			
			$files = $session->getValue($this->_environment->getCurrentModule() . '_add_files');
			
			$file_id_array = array();
			

			if ( !empty($files)
				and count($files) >= count($file_ids)
			) {
			$file_man = $this->_environment->getFileManager();
			foreach ( $files as $file_data ) {
				if ( in_array(trim($file_data["file_id"]), $file_ids) ) {
					if ( isset($file_data["tmp_name"]) and file_exists($file_data["tmp_name"]) ) { // create file entries for uploaded files
						$file_item = $file_man->getNewItem();
						$file_item->setTempKey($file_data["file_id"]);
						// trim space
						$file_data['name'] = trim($file_data['name']);
						$file_item->setPostFile($file_data);
						$file_item->save();
						unlink($file_data["tmp_name"]);  // Currently, the file manager does not unlink a file in its _saveOnDisk() method, because it is also used for copying files when copying material.
						$file_id_array[] = $file_item->getFileID();
					} else {
						$file_id_array[] = $file_data["file_id"];
					}
				}
			}
			#$item->setFileIDArray($file_id_array);
			$temp_merge_array = array_merge($file_id_array, $temp_files_array);
			$item->setFileIDArray($temp_merge_array);
			} elseif ( !empty($file_ids) ) {
			$temp_array = array();
			foreach ($file_ids as $file_id) {
				if ( is_numeric($file_id) ) {
					$temp_array[] = $file_id;
				} else {
					if ( !isset($file_manager) ) {
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
				}
			}
			unset($file_manager);
			#$item->setFileIDArray($temp_array);
			$temp_merge_array = array_merge($temp_array, $temp_files_array);
			$item->setFileIDArray($temp_merge_array);
			} else {
			#$item->setFileIDArray(array());
			$item->setFileIDArray($temp_files_array);
			}
		}
	}
?>
