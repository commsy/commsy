<?php
	require_once('classes/controller/cs_list_controller.php');
    require_once('etc/cs_stopwords.php');

	class cs_search_controller extends cs_list_controller {
		const		SEARCH_WORDS_LIMIT = 2;
		private 	$_params = array();
		private		$_list = null;
		private 	$_items = array();
		private		$_search_words = array();
		private		$_indexed_search = false;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'room_search';

			$this->_list = new cs_list();
			$this->_addition_selects = true;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
		}

		public function actionIndex() {
			$translator = $this->_environment->getTranslationObject();
			$session = $this->_environment->getSessionItem();
			$current_context = $this->_environment->getCurrentContextItem();
			$user_item = $this->_environment->getCurrentUserItem();
			$db = $this->_environment->getDBConnector();

			// get all parameters
			//$this->getParameters();
			
			if(isset($_GET['option']) && isOption($_GET['option'], $translator->getMessage('COMMON_RESET'))) {
				// TODO: rewrite this
				/*
				 * $this->_params['search'] = '';
				$this->_params['selrubric'] = 'all';
				$this->_params['selrestriction'] = 'all';
				$this->_params['seltopic'] = '';
				$this->_params['last_selected_tag'] = '';
				$this->_params['seltag_array'] = array();
				*/
			} else {
				// get parameters
				if(isset($_GET['back_to_search']) && $session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_search_parameter_array')) {
					$this->_params = $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_search_parameter_array');
				} else {
					$this->getParameters();
				}
			
				// store parameters in session
				$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_search_parameter_array', $this->_params);
			}
			
			// init list params
			$this->initListParameters();
			
			// an array of all rubrics, containing files
			$file_rubric_array = $this->getRubricsWithFiles();
			
			$converter = $this->_environment->getTextConverter();
			
			// setup template variables
			// sanitize search

// 			$this->_params['search'] = $converter->sanitizeHTML($this->_params['search']);
			foreach($this->_params as $key => $value){
				if(!is_array($value)){
					$this->_params[$key] = $converter->sanitizeHTML($value);
				}
			}
			// get selected seltags
			
			$tag_2_tag_manager = $this->_environment->getTag2TagManager();
			
			$seltag_array = array();
			foreach($this->_params['seltag'] as $key => $value) {
				if(substr($key, 0, 7) == 'seltag_'){
					// set seltag array
					$this->_params[$key] = $value;
					$keyArray = explode('_', $key);
					$tempTagList = $tag_2_tag_manager->getRecursiveChildrenItemIDArray($keyArray[1]);
					foreach ($tempTagList as $tempTagId) {
    				    $this->_params['seltag_'.$tempTagId] = "true";
					}
				} elseif(substr($key, 0, 6) == 'seltag'){
					$this->_params[$key.'_'.$value] = "true";
				}
			}
			
            unset($this->_params['seltag']);
            
            // $this->assign('search', 'seltag', $seltag_array);

            $this->assign('search', 'parameters', $this->_params);
			
			$this->assign("search", "indexed_search", $this->_indexed_search);

			// find current option
			$option = '';
			if(isset($_POST['form_data']['option'])) {
				$option = $_POST['form_data']['option'];
			} elseif(isset($_GET['option'])) {
				$option = $_GET['option'];
			}

			$rubrics = $this->getRubrics();

			$sel_array = array();
			foreach($rubrics as $module) {
				list($name, $display) = explode('_', $module);
				if($display !== 'none') {
					if($this->isPerspective($name) && $current_context->withRubric($name)) {
						$string = 'sel' . $name;
						if(isset($_GET[$string]) && $_GET[$string] !== '-2') {
							$sel_array[$name] = $_GET[$string];
						} else {
							$sel_array[$name] = 0;
						}
					}
				}

			}

            $emptyTagsParam = true;
            foreach ($this->_params as $key => $value) {
                if (stristr($key, 'seltag_')) {
                    $emptyTagsParam = false;
                }
            }

			$rubric_array = array();

			foreach($rubrics as $rubric) {
				list($name, $view) = explode('_', $rubric);

				if($view !== 'none') {
					if(!($this->_environment->inPrivateRoom() && $name === 'user') && (empty($selfiles) || in_array($name, $file_rubric_array))) {
						if((empty($this->_params['selbuzzword']) && empty($this->_params['selfiles']) && $emptyTagsParam) || (!in_array($name, array(CS_USER_TYPE, CS_GROUP_TYPE, CS_TOPIC_TYPE, CS_PROJECT_TYPE)))) {
							$rubric_array[] = $name;
						}
					}
				}
			}

			if(!empty($this->_params['selrubric']) && $this->_params['selrubric'] !== 'all' && $this->_params['selrubric'] !== 'campus_search') {
				$rubric_array = array();
				$rubric_array[] = $this->_params['selrubric'];
			}

			// convert search_rubric to item type
			$item_types = array();
			foreach($rubric_array as $rubric) {
				$item_types[] = encode(AS_DB, $this->rubric2ItemType($rubric));
			}
			
			$converter = $this->_environment->getTextConverter();

			// sanitize search words
            $search_words = $converter->sanitizeHTML($this->_params['search']);
            $search_words = strtr($search_words, [
                '-' => ' ',
            ]);
			$search_words = explode(' ', $search_words);

			$search_words_tmp = array();
			global $stopwords;
			foreach($search_words as $word) {
				if(strlen($word) >= 1) {
				    $word = strtolower($word);

                    // filter stopwords
                    $match = false;
                    foreach ($stopwords as $language => $words) {
                        if (in_array($word, $words)) {
                            $match = true;
                            break;
                        }
                    }

                    if (!$match) {
                        $search_words_tmp[] = $word;
                    }
                }
			}
			$this->_search_words = $search_words_tmp;

            $search_words_num = (self::SEARCH_WORDS_LIMIT > sizeof($this->_search_words) ? sizeof($this->_search_words) : self::SEARCH_WORDS_LIMIT);
            $this->_search_words = array_slice($this->_search_words, 0, $search_words_num);

#			if (!empty($this->_search_words)) {
				/************************************************************************************
				 * When using the indexed search method, ...
				************************************************************************************/
				if($this->_indexed_search === true) {
				}

				/************************************************************************************
				 * When NOT using the indexed search method, ...
				************************************************************************************/
				else {
					$count_all = 0;

					$campus_search_ids = array();
					$result_list = new cs_list();

                    $tempSeltags = array();
                    foreach ($this->_params as $key => $value) {
                        if (stristr($key, 'seltag_')) {
                            $tempSeltagArray = explode('_', $key);
                            $tempSeltags[] = $tempSeltagArray[1];
                        }
                    }
                    
					if(!empty($tempSeltags)) {
						if (in_array('user', $rubric_array)) {
    						unset($rubric_array['user']);
						}
						$temp_rubric_array = array();
						foreach ($rubric_array as $temp_rubric) {
    						if ($temp_rubric != 'user') {
        						$temp_rubric_array[] = $temp_rubric;
    						}
						}
						$rubric_array = $temp_rubric_array;
					}

					global $c_plugin_array;
					foreach($rubric_array as $rubric) {
						if(!isset($c_plugin_array) || !in_array(strtolower($rubric), $c_plugin_array)) {
							$rubric_ids = array();
							$rubric_list = new cs_list();
							$rubric_manager = $this->_environment->getManager($rubric);

							/*
							 * TODO:	the main idea is to limit requests by the previous detected item ids and only get detailed information for those,
							* 			but db managers do not act as expected
							*
							*			for now, items are filtered afterwards
							*/

							// set id array limit
							//$rubric_manager->setIDArrayLimit(array_keys($items[$rubric]));

							if($rubric === CS_PROJECT_TYPE) {
								$rubric_manager->setQueryWithoutExtra();
							}

							// context limit
							if($rubric !== CS_PROJECT_TYPE && $rubric !== CS_MYROOM_TYPE) {
								$rubric_manager->setContextLimit($this->_environment->getCurrentContextID());
							} elseif($rubric === CS_PROJECT_TYPE && $this->_environment->inCommunityRoom()) {
								$rubric_manager->setContextLimit($this->_environment->getCurrentPortalID());
								$current_community_item = $this->_environment->getCurrentContextItem();
								$rubric_manager->setIDArrayLimit(($current_community_item->getInternalProjectIDArray()));
								unset($current_community_item);
							}

							// date
							if($rubric === CS_DATE_TYPE && $this->_params['selstatus'] === 2) {
								$rubric_manager->setWithoutDateModeLimit();
							} elseif($rubric === CS_DATE_TYPE && $this->_params['selstatus'] !== 2) {
								$rubric_manager->setDateModeLimit($this->_params['selstatus']);
							}

							if ($this->_params['selgroup'] ){
								$rubric_manager->setGroupLimit($this->_params['selgroup']);
							}

							// user
							if($rubric === CS_USER_TYPE) {
								$rubric_manager->setUserLimit();
								$current_user = $this->_environment->getCurrentUser();
								if($current_user->isUser()) {
									$rubric_manager->setVisibleToAllAndCommsy();
								} else {
									$rubric_manager->setVisibleToAll();
								}
							}

							$count_all = $count_all + $rubric_manager->getCountAll();

							foreach($sel_array as $rubric => $value) {
								if(!empty($value)) {
									$rubric_manager->setRubricLimit($rubric, $value);
								}
							}

							// activating status
							if($this->_params['sel_activating_status'] !== '1') {
								$rubric_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
							}

							$rubric_manager->setSearchLimit($this->_search_words[0]);

							$rubric_manager->setAttributeLimit($this->_params['selrestriction']);

							// apply filters
							if(!empty($this->_params['selbuzzword'])) {
								$rubric_manager->setBuzzwordLimit($this->_params['selbuzzword']);
							}

// 							if(!empty($this->_params['seltag'])) {
// 								$rubric_manager->setTagLimit($this->_params['seltag']);
// 							}

							if(!empty($tempSeltags)) {
								$rubric_manager->setTagArrayLimit($tempSeltags);
							}

							/*
							 *
							if ( !empty($selcolor) and $selcolor != '2' and $selrubric == "date") {
							$rubric_manager->setColorLimit('#'.$selcolor);
							}

							if ( ($selrubric == "todo") and !empty($selstatus)) {
							$rubric_manager->setStatusLimit($selstatus);
							}

							if (!empty($seluser)) {
							$rubric_manager->setUserLimit($seluser);
							}

							if ( !empty($selfiles) ) {
							$rubric_manager->setOnlyFilesLimit();
							}
							*/
							if($rubric != CS_MYROOM_TYPE) {
								$rubric_manager->selectDistinct();
								$rubric_list = $rubric_manager->get();
								$temp_rubric_ids = $rubric_manager->getIDArray();
							} else {
								//$rubric_list = $rubric_manager->getRelatedContextListForUser($current_user->getUserID(),$current_user->getAuthSource(),$environment->getCurrentPortalID());;
								//$temp_rubric_ids = $rubric_list->getIDArray();
							}


							/*


							if (isset($_GET['select']) and $_GET['select']=='all'){
							if(get_class($rubric_manager) == 'cs_user_manager'){
							$selected_ids = $temp_rubric_ids;
							}
							}
							*/
							$result_list->addList($rubric_list);
							if(!empty($temp_rubric_ids)) {
								$rubric_ids = $temp_rubric_ids;
							}

							$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_' . $rubric . '_index_ids', $rubric_ids);
							$campus_search_ids = array_merge($campus_search_ids, $rubric_ids);
							/*

							$search_list->addList($rubric_list);
							if (!empty($temp_rubric_ids)){
							$rubric_ids = $temp_rubric_ids;
							}
							$session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $rubric_ids);
							$campus_search_ids = array_merge($campus_search_ids, $rubric_ids);
							*/
						}
						#$session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array', $campus_search_parameter_array);
						#            $session = $this->_environment->getSessionItem();
					}

					// no filtering for result list
					$this->_list = $result_list;
				}
#			}

			if($this->_indexed_search === true) {
				$this->assign('room', 'search_content', $this->getListContent($ftItemIdArray));
			}
			else
			{
				$this->assign('room', 'search_content', $this->getListContent());
			}
			

			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list', 'restriction_text_parameters', $this->_getRestrictionTextAsHTML());
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
		}

		protected function getListContent($ftItemIdArray = array()) {
			$return = array();
			$converter = $this->_environment->getTextConverter();

			$session = $this->_environment->getSessionItem();

			// find max count for relevanz bar
			if($this->_indexed_search === true) {
				$max_count = 0;
				$entry = $this->_list->getFirst();
				while($entry) {
					if($this->_items[$entry->getType()][$entry->getItemID()] > $max_count) $max_count = $this->_items[$entry->getType()][$entry->getItemID()];

					$entry = $this->_list->getNext();
				}
			}

			$id_array = array();
			$disc_id_array = array();

			$item = $this->_list->getFirst();
			while ($item){
   				$id_array[] = $item->getItemID();
   				if($item->getType() == CS_DISCUSSION_TYPE){
   					$disc_id_array[] = $item->getItemID();
   				}elseif($item->getType() == CS_MATERIAL_TYPE){
   					$section_id_array[] = $item->getItemID();
   				}if($item->getType() == CS_TODO_TYPE){
   					$step_id_array[] = $item->getItemID();
   				}
   				$item = $this->_list->getNext();
			}

			$discarticle_manager = $this->_environment->getDiscussionArticleManager();
			$discarticle_list = $discarticle_manager->getAllDiscArticlesItemListByIDArray($disc_id_array);
			$item = $discarticle_list->getFirst();
			$disc_id_array = array();
			while ($item){
			   $disc_id_array[] = $item->getItemID();
			   $item = $discarticle_list->getNext();
			}

			$section_manager = $this->_environment->getSectionManager();
			$section_list = $section_manager->getAllSectionItemListByIDArray($section_id_array);
			$item = $section_list->getFirst();
			$section_id_array = array();
			while ($item){
			   $section_id_array[] = $item->getItemID();
			   $item = $section_list->getNext();
			}

			$step_manager = $this->_environment->getStepManager();
			$step_list = $step_manager->getAllStepItemListByIDArray($step_id_array);
			$item = $step_list->getFirst();
			$step_id_array = array();
			while ($item){
			   $step_id_array[] = $item->getItemID();
			   $item = $step_list->getNext();
			}


		    $link_manager = $this->_environment->getLinkManager();
		    $file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array);
		    $file_id_array = array_merge($file_id_array, $link_manager->getAllFileLinksForListByIDs($disc_id_array));
		    $file_id_array = array_merge($file_id_array, $link_manager->getAllFileLinksForListByIDs($section_id_array));
		    $file_id_array = array_merge($file_id_array, $link_manager->getAllFileLinksForListByIDs($step_id_array));

		    $file_manager = $this->_environment->getFileManager();
		    $file_manager->setIDArrayLimit($file_id_array);
		    $file_manager->select();

		    // search in indexed files
		    $ftsearch_manager = $this->_environment->getFTSearchManager();
		    $ftsearch_manager->setSearchStatus(true);
		    $ftsearch_manager->setWords($this->_search_words[0]);
		    
		    $maxRelevanzPoints = 0;
		    
			$entry = $this->_list->getFirst();
			while($entry) {
				$file_count = 0;
				$type = $entry->getType() === CS_LABEL_TYPE ? $entry->getLabelType() : $entry->getType();


				// files
				$attachment_infos = array();


				if ($entry->getItemType() == CS_MATERIAL_TYPE){
					$file_count = $entry->getFileListWithFilesFromSections()->getCount();
					$file_list = $entry->getFileListWithFilesFromSections();
				}elseif ($entry->getItemType() == CS_DISCUSSION_TYPE){
					$file_count = $entry->getFileListWithFilesFromArticles()->getCount();
					$file_list = $entry->getFileListWithFilesFromArticles();
				}elseif ($entry->getItemType() == CS_TODO_TYPE){
					$file_count = $entry->getFileListWithFilesFromSteps()->getCount();
					$file_list = $entry->getFileListWithFilesFromSteps();
				}else{
					$file_count = $entry->getFileList()->getCount();
					$file_list = $entry->getFileList();
				}

				$file = $file_list->getFirst();
				while($file) {
					$lightbox = false;
					if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) $lightbox = true;

					$info = array();
					#$info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
					$info['file_name']	= $converter->filenameFormatting($file->getDisplayName());
					$info['file_icon']	= $file->getFileIcon();
					$info['file_url']	= $file->getURL();
					$info['file_size']	= $file->getFileSize();
					$info['lightbox']	= $lightbox;

					$attachment_infos[] = $info;
					$file = $file_list->getNext();
				}

				$relevanz = 0;
				
				$title = $entry->getType() === CS_USER_TYPE ? $entry->getFullname() : $entry->getTitle();
				$modificationDate = $entry->getModificationDate();
				
				if ($this->_indexed_search === true) {
					
					$isInAttachedFiles = in_array($entry->getItemID(), $ftItemIdArray);
					$titleCount = 0;
					
					foreach ( $this->_search_words as $searchWord )
					{
						if (mb_stristr($title, $searchWord) !== false )
						{
							$titleCount++;
						}
					}
					
					$contextCount = $this->_items[$entry->getType()][$entry->getItemID()] - $titleCount;
					
					$modificationTimestamp = datetime2Timestamp($modificationDate);
					$timestampDiffToday = time() - $modificationTimestamp;
					$modificationPoints = 0;
					
					if ( $timestampDiffToday <= 604800 )
					{
						$modificationPoints = 4;
					}
					else if ( $timestampDiffToday <= 604800 * 4 )
					{
						$modificationPoints = 3;
					}
					else if ( $timestampDiffToday <= 604800 * 4 * 6 )
					{
						$modificationPoints = 2;
					}
					else if ( $timestampDiffToday <= 604800 * 4 * 6 * 2)
					{
						$modificationPoints = 1;
					}
					
					$relevanzPoints =	$titleCount * 10 +
										round(4 * $contextCount / $max_count) +
										$modificationPoints +
										($isInAttachedFiles ? 1 : 0);
					
					if ( $relevanzPoints > $maxRelevanzPoints )
					{
						$maxRelevanzPoints = $relevanzPoints;
					}
				}

				$return['items'][] = array(
					'title'						=> $this->_compareWithSearchText($title),
					'type'						=> $type,
					'type_sort'					=> $this->_environment->getTranslationObject()->getMessage(strtoupper($type).'_INDEX'),
					'relevanz'					=> $relevanzPoints,
					'item_id'					=> $entry->getItemID(),
					'attachment_count'			=> $file_count,
					'attachment_infos'			=> $attachment_infos,
					'activated'					=> !$entry->isNotActivated(),
					'modificator'				=> $this->_compareWithSearchText($this->getItemModificator($entry)),
					'modification_date'			=> $modificationDate,
					'modification_date_print'	=> $this->_environment->getTranslationObject()->getDateInLang($entry->getModificationDate())
				);

				$entry = $this->_list->getNext();
			}
			
			foreach ( $return["items"] as &$item )
			{
				$item["relevanz"] = (100 * $item["relevanz"]) / $maxRelevanzPoints;
			}

			/************************************************************************************
			 * Now we can sort results
			 * This must be done here, because we need to sort the results from all managers
			 * (at least when not using the indexed search)
			************************************************************************************/
			if ( !empty($this->_list_parameter_arrray['sort']) ) {
				$sortBy = $this->_list_parameter_arrray['sort'];

				// look for "_" and determ sort order
				list($sortBy, $reverse) = explode("_", $sortBy);

				$reverse = ($reverse === null) ? false : true;

				// different sort cases
				switch($sortBy) {
					case "title":
						usort($return['items'], array($this, "sortByTitle"));
						break;
					case "rubric":
						usort($return['items'], array($this, "sortByRubric"));
						break;
					case "modified":
						usort($return['items'], array($this, "sortByModified"));
						$return['items'] = array_reverse($return['items']);
						break;
					case "modificator":
						usort($return['items'], array($this, "sortByModificator"));
						break;
					case "relevanz":
						usort($return['items'], array($this, "sortByRelevanz"));
						break;
				}

				if($reverse === true) {
					$return['items'] = array_reverse($return['items']);
				}
			}

			// create id array
			$ids = array();
			foreach($return['items'] as $entry) {
				$ids[] = $entry['item_id'];
			}
			$session->setValue('cid'.$this->_environment->getCurrentContextID().'_campus_search_index_ids', $ids);

			$this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'],sizeof($ids));
			$return['count_all'] = sizeof($ids);

			// limit output
			$limited_return = array();
			$count = 0;
			foreach($return['items'] as $entry) {
				if($this->_list_parameter_arrray['interval'] === "all") {
					if($count >= $this->_list_parameter_arrray['from'] - 1) {
						$limited_return[] = $entry;
					}
				} else {
					if($count >= $this->_list_parameter_arrray['from'] - 1 && sizeof($limited_return) < $this->_list_parameter_arrray['interval']) {
						$limited_return[] = $entry;
					}
				}

				$count++;
			}
			$return['items'] = $limited_return;

			return $return;
		}

		private function sortByTitle($a, $b) {
			return strcasecmp($a["title"], $b["title"]);
		}

		private function sortByRubric($a, $b) {
			return strcasecmp($a["type_sort"], $b["type_sort"]);
		}

		private function sortByModified($a, $b) {
			return strcasecmp($a["modification_date"], $b["modification_date"]);
		}

		private function sortByModificator($a, $b) {
			$aExplode = explode(" ", $a["modificator"]); end($aExplode);
			$bExplode = explode(" ", $b["modificator"]); end($bExplode);


			return strcasecmp(current($aExplode), current($bExplode));
		}

		private function sortByRelevanz($a, $b) {
			if($a["relevanz"] === $b["relevanz"]) return 0;

			return ($a["relevanz"] < $b["relevanz"]) ? -1 : 1;
		}


			/*
			 * TODO:
   }
}
if($interval == 0){
	$interval = $search_list->getCount();
}
// Set data for view
$sublist = $search_list->getSubList($from-1,$interval);
$view->setList($sublist);
$view->setCountAllShown($search_list->getCount());
$view->setCountAll($count_all);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSearchText($search);
$view->setSelectedRestriction($selrestriction);
$view->setSelectedFile($selfiles);
$view->setAvailableBuzzwords($buzzword_list);
$view->setChoosenRubric($selrubric);
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);
$view->setActivationLimit($sel_activating_status);
$view->setSelectedUser($seluser);
$view->setSelectedGroup($selgroup);
$view->setSelectedStatus($selstatus);
$view->setSelectedColor($selcolor);

if ( !empty($ref_iid) and $mode =='attached'){
      $item_manager = $environment->getItemManager();
      $ref_item_type = $item_manager->getItemType($ref_iid);
      $ref_item_manager = $environment->getManager($ref_item_type);
      $ref_item = $ref_item_manager->getItem($ref_iid);
      $view->setRefItem($ref_item);
      $view->setRefIid($ref_iid);
      $view->setIsAttachedList();
   }


   if ( isset($mode) && ($mode == 'formattach' or $mode == 'detailattach' )) {
      $view->setRefIID($ref_iid);
      $view->setHasCheckboxes($mode);
      $view->setCheckedIDs($new_attach_ids);
      $view->setDontEditIDs($dontedit_attach_ids);
   }elseif (isset($mode) && $mode == 'attach'){
      $view->setHasCheckboxes('list_actions');
   }else{
      $view->setCheckedIDs($selected_ids);
      $view->setHasCheckboxes('list_actions');
   }

// Add list view to page
$page->add($view);

$ftsearch_manager = $environment->getFTSearchManager();
if ($ftsearch_manager->getSearchStatus()) {
   // get fids from cs_ftsearch_manager
   $ft_file_ids = $ftsearch_manager->getFileIDs();
   if ( !empty($ft_file_ids) ) {
      $campus_search_parameter_array['file_id_array'] = $ft_file_ids;
   }
}
unset($ftsearch_manager);

		}*/

		private function rubric2ItemType($rubric_name) {
			switch($rubric_name) {
				case "institution":
				case "group":
				case "topic":
				case "buzzword":
					return 'label';
					break;
				default:
					return $rubric_name;
			}
		}

		private function getParameters() {
			// find current search text
			$this->_params['search'] = '';
			if(isset($_POST['form_data']['keywords'])) {
				$text_converter = $this->_environment->getTextConverter();
				$this->_params['search'] = $_POST['form_data']['keywords'];
				//$from = 1;
				$search = $this->_params['search'];
				$this->_environment->setCurrentParameter('search', $search);
			} elseif(isset($_GET['search'])) {
				$this->_params['search'] = $_GET['search'];
			}

			// find selected rubric
			$this->_params['selrubric'] = '';
			if(isset($_POST['form_data']['selrubric'])) {
				$this->_params['selrubric'] = $_POST['form_data']['selrubric'];
				//$from = 1;
			} elseif(isset($_GET['selrubric'])) {
				$this->_params['selrubric'] = $_GET['selrubric'];
			}
			if($this->_params['selrubric'] === 'search') {
				$this->_params['selrubric'] = 'all';
			}

			// find selected buzzwords
			$this->_params['selbuzzword'] = 0;
			if(isset($_GET['selbuzzword']) && $_GET['selbuzzword'] !== '-2') {
				$this->_params['selbuzzword'] = $_GET['selbuzzword'];
			} elseif(isset($_POST['form_data']['selbuzzword']) && $_POST['form_data']['selbuzzword'] !== '-2') {
				$this->_params['selbuzzword'] = $_POST['form_data']['selbuzzword'];
			}

			//$this->_params['last_selected_tag'] = '';
// 			$this->_params['seltag'] = '';
// 			if(isset($_GET['seltag'])) {
// 				$this->_params['seltag'] = $_GET['seltag'];
// 			}
			#$this->_params['seltag'] = '';
			$seltag_array = array();
			foreach ($_GET as $key => $value){
				if(substr($key, 0, 7) == 'seltag_'){
					// set seltag array
					$seltag_array[$key] = $value;
				} elseif(substr($key, 0, 6) == 'seltag'){
					$seltag_array[$key.'_'.$value] = "true";
				}
			}
			
			if(!empty($seltag_array)){
				$this->_params['seltag'] = $seltag_array;
			}
			
// 			if(isset($_GET['seltag'])) {
// 				$this->_params['seltag'] = $_GET['seltag'];
// 			}

			// find selected topic
			/*
			if(isset($_GET['seltags']) && !empty($_GET['seltags'])) {
				$this->_params['seltags'] = $_GET['seltags'];
			}

			/*
			if(isset($_GET['seltag']) && $_GET['seltag'] === 'yes') {
				$i = 0;
				while(!isset($_GET['seltag_' . $i])) {
					$i++;
				}
				$this->_params['seltag_array'][] = $_GET['seltag_' . $i];
				$j = 0;
				while(isset($_GET['seltag_' . $i]) && $_GET['seltag_' . $i] != '-2') {
					if(!empty($_GET['seltag_' . $i])) {
						$this->_params['seltag_array'][$i] = $_GET['seltag_' . $i];
						$j++;
					}
					$i++;
				}
				$this->_params['last_selected_tag'] = $this->_params['seltag_array'][$j-1];
			} elseif(isset($_POST['form_data']['seltag']) && $_POST['form_data']['seltag'] === 'yes') {
				// from hidden form
				$i = 0;
				while(!isset($_POST['form_data']['seltag_' . $i])) {
					$i++;
				}
				$this->_params['seltag_array'] = $_POST['form_data']['seltag_' . $i];
				$j = 0;
				while(isset($_POST['form_data']['seltag_' . $i]) && $_POST['form_data']['seltag_' . $i] !== '-2') {
					if(!empty($_POST['form_data']['seltag_' . $i])) {
						$this->_params['seltag_array'][$i] = $_POST['form_data']['seltag_' . $i];
						$j++;
					}
					$i++;
				}
				$this->_params['last_selected_tag'] = $this->_params['seltag_array'][$j-1];
			}*/

			// find selected restrictions
			$this->_params['selrestriction'] = 'all';
			if(isset($_POST['form_data']['selrestriction'])) {
				if($_POST['form_data']['selrestriction'] === 1) {
					$this->_params['selrestriction'] = 'title';
				} elseif($_POST['form_data']['selrestriction'] === 2) {
					$this->_params['selrestriction'] = 'author';
				}
				//$from = 1;
			} elseif(isset($_GET['selrestriction'])) {
				if($_GET['selrestriction'] === 1) {
					$this->_params['selrestriction'] = 'title';
				} elseif($_GET['selrestriction'] === 2) {
					$this->_params['selrestriction'] = 'author';
				}
			}

			// find selected group
			$this->_params['selgroup'] = '';
			if(isset($_POST['form_data']['selgroup'])) {
				$this->_params['selgroup'] = $_POST['form_data']['selgroup'];
			} elseif(isset($_GET['selgroup'])) {
				$this->_params['selgroup'] = $_GET['selgroup'];
			}

			// find selected color
			$this->_params['selcolor'] = '';
			if(isset($_POST['form_data']['selcolor'])) {
				$this->_params['selcolor'] = $_POST['form_data']['selcolor'];
			} elseif(isset($_GET['selcolor'])) {
				$this->_params['selcolor'] = $_GET['selcolor'];
			}

			// find selected user
			$this->_params['seluser'] = '';
			if(isset($_POST['form_data']['seluser'])) {
				$this->_params['seluser'] = $_POST['form_data']['seluser'];
			} elseif(isset($_GET['seluser'])) {
				$this->_params['seluser'] = $_GET['seluser'];
			}

			// find selected status
			$this->_params['selstatus'] = 2;
			if(isset($_POST['form_data']['selstatus'])) {
				$this->_params['selstatus'] = $_POST['form_data']['selstatus'];
			} elseif(isset($_GET['selstatus'])) {
				$this->_params['selstatus'] = $_GET['selstatus'];
			}

			// find selected only files
			$this->_params['selfiles'] = '';
			if(isset($_POST['form_data']['only_files'])) {
				$this->_params['selfiles'] = $_POST['form_data']['only_files'];
				//$from = 1;
			} elseif(isset($_GET['only_files'])) {
				$this->_params['selfiles'] = $_GET['only_files'];
			}

			// find selected activating status
			$this->_params['sel_activating_status'] = 2;
			if(isset($_GET['sel_activating_status']) && $_GET['sel_activating_status'] !== '-2') {
				$this->_params['sel_activating_status'] = $_GET['sel_activating_status'];
			} elseif(isset($_POST['form_data']['sel_activating_status']) && $_POST['form_data']['sel_activating_status'] !== '-2') {
				$this->_params['sel_activating_status'] = $_POST['form_data']['sel_activating_status'];
			}
		}

		protected function getAdditionalActions(&$perms) {
		}

		protected function getAdditionalListActions() {
		   $return = array();
		   return $return;
		}

		protected function getAdditionalRestrictions() {
			$return = array();
			return $return;
		}

		protected function getAdditionalRestrictionText() {
			$return = array();

			$translator = $this->_environment->getTranslationObject();

			// search word
			if(isset($this->_params['search']) and!empty($this->_params['search'])) {
				$return[0] = array(
					'name'				=> $translator->getMessage('COMMON_SEARCH_IN_ENTRIES') . ': ',
					'type'				=> '',
					'link_parameter'	=> ''
				);

				$return[0]['name'] .= implode(', ', $this->_search_words);

				// set link parameter
				$params = $this->_params;
				unset($params['search']);
				$link_parameter_text = '';
				if ( count($params) > 0 ) {
					foreach ($params as $key => $parameter) {
						$link_parameter_text .= '&'.$key.'='.$parameter;
					}
				}
				$return[0]['link_parameter'] = $link_parameter_text;
			}

			// rubric
			if(isset($this->_params['selrubric']) && !empty($this->_params['selrubric'])) {
				$return[1] = array(
					'name'				=> $translator->getMessage('COMMON_RUBRICS') . ': ' . $translator->getMessage('COMMON_' . mb_strtoupper($this->_params['selrubric']) . '_INDEX'),
					'type'				=> '',
					'link_parameter'	=> ''
				);

				// set link parameter
				$params = $this->_params;
				unset($params['selrubric']);
				$link_parameter_text = '';
				if ( count($params) > 0 ) {
					foreach ($params as $key => $parameter) {
						$link_parameter_text .= '&'.$key.'='.$parameter;
					}
				}
				$return[1]['link_parameter'] = $link_parameter_text;
			}

			return $return;
		}

		/**
		 * gets information for displaying room rubrics in navigation bar
		 */
		protected function getRubricInformation() {
			$selected_rubric = $this->_params['selrubric'];
			if(empty($selected_rubric) || $selected_rubric === 'all') $selected_rubric = 'home';

			// init return with home
			$return = array();
			$return[] = array(
					'name'			=> 'home',
					'translate'		=> false,
					'active'		=> $selected_rubric == 'home',
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
					CS_PROJECT_TYPE			=>	'pr'
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
					$return[] = array(
							'name'			=> $name,
							'translate'		=> $translate,
							'active'		=> $selected_rubric == $name,
							'span_prefix'	=> $span_lookup[$name]);
				}
			}

			return $return;
		}

		function _compareWithSearchText($value, $bold = true) {
	      if ( !empty($this->_search_words) ) {
	         foreach ($this->_search_words as $search_text) {
	            if ( mb_stristr($value,$search_text) ) {
	               // CSS Klasse erstellen für Farbmarkierung
	               include_once('functions/misc_functions.php');
	               if ( getMarkerColor() == 'green') {
	                  $replace = '<span class="searchtext2">$0</span>';
	               }
	               else if (getMarkerColor() == 'yellow') {
	                  $replace = '<span class="searchtext1">$0</span>';
	               }
	               // $replace = '(:mainsearch_text:)$0(:mainsearch_text_end:)';
	               // $replace = '*$0*';
	               if ( !$bold ) {
	                  if ( getMarkerColor() == 'green') {
	                    $replace = '<span class="searchtext2">$0</span>';
	                }
	                else if (getMarkerColor() == 'yellow') {
	                    $replace = '<span class="searchtext1">$0</span>';
	                }

	                  // $replace = '(:search:)$0(:search_end:)';
	               }
	               if ( stristr($value,'<!-- KFC TEXT') ) {
	                   if(getMarkerColor() == 'green'){
	                      $replace = '<span class="searched_text_green">$0</span>';
	                   }
	                   else if(getMarkerColor() == 'yellow'){
	                      $replace = '<span class="searched_text_yellow">$0</span>';
	                   }

	                  // $replace = '<span class="bold">$0</span>';
	                  if ( !$bold ) {
	                    $replace = '<span class="italic" style="font-style: italic;">$0</span>';
	                  }
	               }
	               $value = preg_replace('~'.preg_quote($search_text,'/').'~iu',$replace,$value);
	            }
	         }
	      }
	      return $value;
	   }

	}

