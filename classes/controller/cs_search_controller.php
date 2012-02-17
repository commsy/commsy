<?php
	require_once('classes/controller/cs_list_controller.php');
	
	class cs_search_controller extends cs_list_controller {
		const		SEARCH_WORDS_LIMIT = 2;
		private 	$_params = array();
		private		$_list = null;
		private 	$_items = array();
		private		$_search_words = array();
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'room_search';
			
			$this->_list = new cs_list();
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
			
			if(isset($_GET['back_to_search']) && $session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_campus_search_parameter_array')) {
				$campuser_search_parameter_array = $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_compus_search_parameter_array');
				$params['search'] = $campus_search_parameter_array['search'];
				$params['selrestriction'] = $campus_search_parameter_array['selrestriction'];
				$params['selrubric'] = $campus_search_parameter_array['selrubric'];
				$params['selbuzzword'] = $campus_search_parameter_array['selbuzzword'];
				$params['seltag_array'] = $campus_search_parameter_array['seltag_array'];
				$params['selfiles'] = $campus_search_parameter_array['selfiles'];
				$params['interval'] = $campus_search_parameter_array['interval'];
				$params['sel_activating_status'] = $campus_search_parameter_array['sel_activating_status'];
				$sel_array = $campus_search_parameter_array['sel_array'];
				foreach($sel_array as $key => $value){
					$params['sel'.$key] = $value;
				}
				$session->unsetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
				$session->unsetValue('cid'.$environment->getCurrentContextID().'_campus_search_index_ids');
				redirect($environment->getCurrentContextID(),'campus_search', 'index', $params);
			}
			
			// an array of all rubrics, containing files
			$file_rubric_array = array();
			$file_rubric_array[] = CS_DISCUSSION_TYPE;
			$file_rubric_array[] = CS_MATERIAL_TYPE;
			$file_rubric_array[] = CS_DATE_TYPE;
			$file_rubric_array[] = CS_ANNOUNCEMENT_TYPE;
			$file_rubric_array[] = CS_TODO_TYPE;
			
			
			
			
			/*
			 *

// Find current browsing starting point
if ( isset($_GET['from']) ) {
   $from = $_GET['from'];
}  else {
   $from = 1;
}

// Find current browsing interval
// The browsing interval is applied to all rubrics!
if ( isset($_GET['interval']) ) {
   $interval = $_GET['interval'];
}  else {
   $interval = CS_LIST_INTERVAL;
}

*/
			// search / select area
			if(isset($_GET['option']) && isOption($_GET['option'], $translator->getMessage('COMMON_RESET'))) {
				$this->_params['search'] = '';
				$this->_params['selrubric'] = 'all';
				$this->_params['selrestriction'] = 'all';
				$this->_params['seltopic'] = '';
				$this->_params['last_selected_tag'] = '';
				$this->_params['seltag_array'] = array();
			} else {
				// get parameters
				$this->getParameters();
			}

			// find current option
			$option = '';
			if(isset($_POST['form_data']['option'])) {
				$option = $_POST['form_data']['option'];
			} elseif(isset($_GET['option'])) {
				$option = $_GET['option'];
			}
			
			/*
			 * // Handle attaching
			   if ( isset($mode) && ($mode == 'formattach' or $mode == 'detailattach') ) {
			      $attach_type = CS_USER_TYPE;
			      include('pages/index_attach_inc.php');
			   }
			 */
			
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
			
			$rubric_array = array();
			
			foreach($rubrics as $rubric) {
				list($name, $view) = explode('_', $rubric);
				
				if($view !== 'none') {
					if(!($this->_environment->inPrivateRoom() && $name === 'user') && (empty($selfiles) || in_array($name, $file_rubric_array))) {
						if((empty($selbuzzword) && empty($selfiles) && empty($last_selected_tag)) || (!in_array($name, array(CS_USER_TYPE, CS_GROUP_TYPE, CS_INSTITUTION_TYPE, CS_PROJECT_TYPE)))) {
							$rubric_array[] = $name;
						}
					}
				}
			}
			
			if(!empty($this->_params['selrubric']) && $this->_params['selrubric'] !== 'all' && $this->_params['selrubric'] !== 'campus_search') {
				$rubric_array = array();
				$rubric_array[] = $this->_params['selrubric'];
			}
			
			/*
			 * /*
			 * 
// Find current search text
if ( isset($_GET['attribute_limit']) ) {
   $attribute_limit = $_GET['attribute_limit'];
   switch( $attribute_limit  ){
     case 1 :
         $attribute_limit = 'title';
         break;
     case 2 :
         $attribute_limit = 'author';
         break;
     case 3 :
         $attribute_limit = 'file';
         break;
   }
} else {
   $attribute_limit = '';
}


   // LIST ACTIONS
   // initiate selected array of IDs
   $selected_ids = array();
   if ( isset($mode) && $mode == '') {
      $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
   }elseif ( isset($mode) && $mode == 'list_actions') {
      if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                 '_selected_ids')) {
         $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_selected_ids');
      }
   }
      // Update attached items from cookie (requires JavaScript in browser)
      if ( isset($_COOKIE['attach']) ) {
         foreach ( $_COOKIE['attach'] as $key => $val ) {
            setcookie ('attach['.$key.']', '', time()-3600);
            if ( $val == '1' ) {
               if ( !in_array($key, $selected_ids) ) {
                  $selected_ids[] = $key;
               }
            } else {
               $idx = array_search($key, $selected_ids);
               if ( $idx !== false ) {
                  unset($selected_ids[$idx]);
               }
            }
         }
      }

      // Update attached items from form post (works always)
      if ( isset($_POST['attach']) ) {
         foreach ( $_POST['shown'] as $shown_key => $shown_val ) {
            if ( array_key_exists($shown_key, $_POST['attach']) ) {
               if ( !in_array($shown_key, $selected_ids) ) {
                  $selected_ids[] = $shown_key;
               }
            } else {
               $idx = array_search($shown_key, $selected_ids);
               if ( $idx !== false ) {
                  unset($selected_ids[$idx]);
               }
            }
         }
      }


   ///////////////////////////////////////
   // perform list actions              //
   ///////////////////////////////////////
   #pr($_POST);
   if ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        and $_POST['index_view_action'] != '-1'
        and !empty($selected_ids)
      ) {
      // prepare action process
      switch ($_POST['index_view_action']) {
         case 1:
            $action = 'ENTRY_MARK_AS_READ';
            $error = false;
            $user_manager = $environment->getUserManager();
            $noticed_manager = $environment->getNoticedManager();
            foreach ($selected_ids as $id) {
               $user_item = $user_manager->getItem($id);
               $version_id = $user_item->getVersionID();
               // Nur die UserItemID in die noticed DB einfügen??
               $noticed_manager->markNoticed($id, $version_id );
               $annotation_list =$user_item->getAnnotationList();
               if ( !empty($annotation_list) ){
                  $annotation_item = $annotation_list->getFirst();
                  while($annotation_item){
                     $noticed_manager->markNoticed($annotation_item->getItemID(),'0');
                     $annotation_item = $annotation_list->getNext();
                  }
               }
            }
            break;
         case 2:
            $action = 'USER_EMAIL_SEND';

         $current_user = $environment->getCurrentUser();
         $user_item_id = $current_user->getItemID();
         $action_array = array();
         $action_array['user_item_id'] = $user_item_id;
         $action_array['action'] = $action;
         $action_array['backlink']['cid'] = $environment->getCurrentContextID();
         $action_array['backlink']['mod'] = $environment->getCurrentModule();
         $action_array['backlink']['fct'] = $environment->getCurrentFunction();
         $action_array['backlink']['par'] = '';
         $action_array['selected_ids'] = $selected_ids;
         $params = array();
         $params['step'] = 1;
         $session->setValue('index_action',$action_array);
         redirect( $environment->getCurrentContextID(),
                   'user',
                   'action',
                   $params);
            break;
         default:
            include_once('functions/error_functions.php');
            trigger_error('action ist not defined',E_USER_ERROR);
      }
      $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
      $selected_ids = array();
   } // end if (perform list actions)








// Get available buzzwords
$buzzword_manager = $environment->getLabelManager();
$buzzword_manager->resetLimits();
$buzzword_manager->setContextLimit($environment->getCurrentContextID());
$buzzword_manager->setTypeLimit('buzzword');
$buzzword_manager->setGetCountLinks();
$buzzword_manager->select();
$buzzword_list = $buzzword_manager->get();
$count_all = 0;

// Durchführung möglicher Einschränkungen
foreach($sel_array as $rubric => $value){
   $label_manager = $environment->getManager($rubric);
   $label_manager->setContextLimit($environment->getCurrentContextID());
   $label_manager->select();
   $rubric_list = $label_manager->get();
   $temp_rubric_list = clone $rubric_list;
   $view->setAvailableRubric($rubric,$temp_rubric_list);
   $view->setSelectedRubric($rubric,$value);
   unset($rubric_list);
}

// translation of entry to rubrics for new private room
if ( $environment->inPrivateRoom()
     and in_array(CS_ENTRY_TYPE,$rubric_array)
   ) {
   $temp_array = array();
   $temp_array2 = array();
   $rubric_array2 = array();
   $temp_array[] = CS_ANNOUNCEMENT_TYPE;
   $temp_array[] = CS_TODO_TYPE;
   $temp_array[] = CS_DISCUSSION_TYPE;
   $temp_array[] = CS_MATERIAL_TYPE;
   $temp_array[] = CS_DATE_TYPE;
   foreach ( $temp_array as $temp_rubric ) {
      if ( !in_array($temp_rubric,$rubric_array) ) {
         $temp_array2[] = $temp_rubric;
      }
   }
   foreach ( $rubric_array as $temp_rubric ) {
      if ( $temp_rubric != CS_ENTRY_TYPE ) {
         $rubric_array2[] = $temp_rubric;
      } else {
         $rubric_array2 = array_merge($rubric_array2,$temp_array2);
      }
   }
   $rubric_array = $rubric_array2;
   unset($rubric_array2);
}
*/
			
			// convert search_rubric to item type
			$item_types = array();
			foreach($rubric_array as $rubric) {
				$item_types[] = encode(AS_DB, $this->rubric2ItemType($rubric));
			}
			
			$search_words = explode(' ', $this->_params['search']);
			$search_words_num = (self::SEARCH_WORDS_LIMIT > sizeof($search_words) ? sizeof($search_words) : self::SEARCH_WORDS_LIMIT);
			
			$search_words = array_slice($search_words, 0, $search_words_num);
			
			$search_words_tmp = array();
			foreach($search_words as $word) {
				if(strlen($word) >= 3) $search_words_tmp[] = $word;
			}
			$this->_search_words = $search_words_tmp;
			
			/////////////////////////////////////////
			// 1. get ids of search words
			/////////////////////////////////////////
			$query = '
				SELECT
					sw_id
				FROM
					search_word
				WHERE
			';
			
			$size = sizeof($this->_search_words);
			for($i = 0; $i < $size; $i++) {
				$query .= '
						sw_word LIKE "' . encode(AS_DB, $this->_search_words[$i]) . '%"
				';
				
				if($i < $size - 1) $query .= ' OR ';
			}
			$word_ids = $db->performQuery($query);
			
			//echo sizeof($word_ids) . " words matched this search</br>\n"; //pr($word_ids);
			
			/////////////////////////////////////////
			// 2. find items matching these words
			/////////////////////////////////////////
			
			/*
			 * fortunately, it is possible to limit this selection by rubrics, because all indexed entries are
			 * associated to their proper main item
			 * f.e.	- discussionarticles are listed as discussions
			 * 		- annotations are listed as the item they belong to
			 * 		- ...
			 */
			
			$query = '
				SELECT
					si_item_id,
					si_item_type,
					si_count
				FROM
					search_index
				WHERE
					(
			';
			
			$size = sizeof($rubric_array);
			for($i = 0; $i < $size; $i++) {
				$query .= '
					si_item_type = "' . mysql_real_escape_string($rubric_array[$i]) . '"';
				
				if($i < $size - 1) $query .= ' OR ';
			}
			$query .= ') AND (';
			
			$size = sizeof($word_ids);
			for($i = 0; $i < $size; $i++) {
				$query .= '
					si_sw_id = ' . $word_ids[$i]['sw_id'];
				
				if($i < $size - 1) $query .= ' OR ';
			}
			$query .= ')';
			
			$query .= '
				ORDER BY
					si_count
				DESC
			';
			
			$results = $db->performQuery($query);
			
			//echo sizeof($results) . " indexed items matched this search</br>\n";
			
			/////////////////////////////////////////
			// 3. order items by rubric
			/////////////////////////////////////////
			
			foreach($results as $result) {
				$this->_items[$result['si_item_type']][$result['si_item_id']] = $result['si_count'];
			}
			
			//pr($items);
			
			$count_all = 0;
			
			$campus_search_ids = array();
			$result_list = new cs_list();
			
			/////////////////////////////////////////
			// 4. get all needed item information
			/////////////////////////////////////////
			
			// get data from database
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
						$rubric_manager->showNoNotActivatedEntries();
					}
					
					$rubric_manager->setAttributeLimit($this->_params['selrestriction']);
					
					
					
					/*
					 * 
      if ( !empty($selbuzzword) ) {
         $rubric_manager->setBuzzwordLimit($selbuzzword);
      }
      if ( !empty($last_selected_tag) ){
         $rubric_manager->setTagLimit($last_selected_tag);
      }
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
					$campus_search_ids = array_merge($compus_search_ids, $rubric_ids);
					
					/*

      $search_list->addList($rubric_list);
      if (!empty($temp_rubric_ids)){
         $rubric_ids = $temp_rubric_ids;
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $rubric_ids);
      $campus_search_ids = array_merge($campus_search_ids, $rubric_ids);
					 */
				}
			}
			
			
			/*
			 * 
if($interval == 0){
	$interval = $search_list->getCount();
}
			 */
			//echo $result_list->getCount() . " results before id filtering<br>\n";
			
			
			/////////////////////////////////////////
			// 5. sort list by relevanz and filter item ids
			/////////////////////////////////////////
			
			$entry = $result_list->getFirst();
			while($entry) {
				if(isset($this->_items[$entry->getType()][$entry->getItemID()])) $this->_list->add($entry);
				
				$entry = $result_list->getNext();
			}
			
			//echo $this->_list->getCount() . " final results<br>\n";
			
			$this->assign('room', 'search_content', $this->getListContent());
			$this->assign('room', 'search_sidebar', $this->getSidebarContent());
		}
		
		protected function getListContent() {
			$return = array();
			
			$entry = $this->_list->getFirst();
			while($entry) {
				$return[$entry->getType()][] = array(
					'title'			=> $entry->getType() === CS_USER_TYPE ? $entry->getFullname() : $entry->getTitle(),
					'count'			=> $this->_items[$entry->getType()][$entry->getItemID()],
					'type'			=> $entry->getType(),
					'item_id'		=> $entry->getItemID()
				);
				
				$entry = $this->_list->getNext();
			}
			
			// sort return by relevanz
			foreach($return as $type => $entries) {
				usort($entries, array($this, 'sortByRelevanz'));
				$return[$type] = array_reverse($entries);
			}
			
			return $return;
		}
		
		private function getSidebarContent() {
			$return = array();
			
			$return['search_words'] = $this->_search_words;
			
			return $return;
		}
		
		private function sortByRelevanz($a, $b) {
			if($a['count']	=== $b['count']) return 0;
			
			return ($a['count'] < $b['count']) ? -1 : 1;
		}
		
		
			/*
			 * // Get data from database
global $c_plugin_array;
foreach ($rubric_array as $rubric) {
   if ( !isset($c_plugin_array)
        or !in_array(strtolower($rubric),$c_plugin_array)
      ) {
      $rubric_ids = array();
      $rubric_list = new cs_list();
      $rubric_manager = $environment->getManager($rubric);
      if ( $rubric == CS_PROJECT_TYPE ) {
         $rubric_manager->setQueryWithoutExtra();
      }
      
      			';
      			
      			
      			
	
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

// Safe information in session for later use
$campus_search_parameter_array = array();
$campus_search_parameter_array['search'] = $search;
$campus_search_parameter_array['selrestriction'] = $selrestriction;
$campus_search_parameter_array['selrubric'] = $selrubric;
$campus_search_parameter_array['selbuzzword'] = $selbuzzword;
$campus_search_parameter_array['selstatus'] = $selstatus;
$campus_search_parameter_array['selcolor'] = $selcolor;
$campus_search_parameter_array['selgroup'] = $selgroup;
$campus_search_parameter_array['seluser'] = $seluser;
$campus_search_parameter_array['seltag_array'] = $seltag_array;
$campus_search_parameter_array['selfiles'] = $selfiles;
$campus_search_parameter_array['sel_array'] = $sel_array;
$campus_search_parameter_array['interval'] = $interval;
$campus_search_parameter_array['sel_activating_status'] = $sel_activating_status;

$ftsearch_manager = $environment->getFTSearchManager();
if ($ftsearch_manager->getSearchStatus()) {
   // get fids from cs_ftsearch_manager
   $ft_file_ids = $ftsearch_manager->getFileIDs();
   if ( !empty($ft_file_ids) ) {
      $campus_search_parameter_array['file_id_array'] = $ft_file_ids;
   }
}
unset($ftsearch_manager);

$session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array', $campus_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_index_ids', $campus_search_ids);
		}*/
		
		private function rubric2ItemType($rubric_name) {
			switch($value) {
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
			$this->_params['search'];
			if(isset($_POST['form_data']['keywords'])) {
				$this->_params['search'] = $_POST['form_data']['keywords'];
				//$from = 1;
				$this->_environment->setCurrentParameter('search', $this->_params['search']);
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
			if($this->_params['selrubric'] === 'campus_search') {
				$this->_params['selrubric'] = 'all';
			}
			
			// find selected buzzwords
			$this->_params['selbuzzword'] = 0;
			if(isset($_GET['selbuzzword']) && $_GET['selbuzzword'] !== '-2') {
				$this->_params['selbuzzword'] = $_GET['selbuzzword'];
			} elseif(isset($_POST['form_data']['selbuzzword']) && $_POST['form_data']['selbuzzword'] !== '-2') {
				$this->_params['selbuzzword'] = $_POST['form_data']['selbuzzword'];
			}
			
			$this->_params['last_selected_tag'] = '';
			$this->_params['seltag_array'] = array();
			
			// find selected topic
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
			}
			
			// find selected restrictions
			$_POST['form_data']['selrestriction'] = 'all';
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
			$this->_params['selactivatingstatus'] = 2;
			if(isset($_GET['selactivatingstatus']) && $_GET['selactivatingstatus'] !== '-2') {
				$this->_params['selactivatingstatus'] = $_GET['selactivatingstatus'];
			} elseif(isset($_POST['form_data']['selactivatingstatus']) && $_POST['form_data']['selactivatingstatus'] !== '-2') {
				$this->_params['selactivatingstatus'] = $_POST['form_data']['selactivatingstatus'];
			}
		}
		
		protected function getAdditionalActions(&$perms) {
		}

		protected function getAdditionalListActions() {
		   $return = array();
		   return $return;
		}
	}