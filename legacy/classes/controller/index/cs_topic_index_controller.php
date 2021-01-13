<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_topic_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'topic_list';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_TOPIC_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/

		/**
		 * INDEX
		 */
		public function actionIndex() {
			// init list params
			$this->initListParameters(CS_TOPIC_TYPE);

			// perform list options
			$this->performListOption(CS_TOPIC_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('topic','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('topic','list_content', $list_content);
		}

		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$return = array();
			$translator = $environment->getTranslationObject();

			$last_selected_tag = '';
			$seltag_array = array();

			/*
			 * if (isset($_GET['back_to_index']) and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index')){
			   $index_search_parameter_array = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
			   $params['interval'] = $index_search_parameter_array['interval'];
			   $params['sort'] = $index_search_parameter_array['sort'];
			   $params['interval'] = $index_search_parameter_array['interval'];
			   if ($environment->inCommunityRoom()){
			      $params['selinstitution'] = $index_search_parameter_array['selinstitution'];
			   }else{
			      $params['selgroup'] = $index_search_parameter_array['selgroup'];
			   }
			   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
			   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index');
			   redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'index', $params);
			}
			 */




			/*
// Handle attaching
if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $attach_type = CS_TOPIC_TYPE;
   include('pages/index_attach_inc.php');
}


// Search / Select Area
if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   $search = '';
   if ( $environment->inCommunityRoom() ) {
      $selinstitution = '';
   } else {
      $selgroup = '';
   }
} else {

   // Find current search text
   if ( isset($_GET['search']) ) {
      $search = $_GET['search'];
   } else {
      $search = '';
   }

   if ( $environment->inCommunityRoom() ){
      // Find current institution selection
      if ( isset($_GET['selinstitution'])  and $_GET['selinstitution'] !='-2') {
         $selinstitution = $_GET['selinstitution'];
      } else {
         $selinstitution = 0;
      }
   } else {
      // Find current group selection
      if ( isset($_GET['selgroup'])  and $_GET['selgroup'] !='-2') {
         $selgroup = $_GET['selgroup'];
      } else {
         $selgroup = 0;
      }
   }
}
*/

/*



// LIST ACTIONS
// initiate selected array of IDs
$selected_ids = array();
if ($mode == '') {
   $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
}elseif ($mode == 'list_actions') {
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

   // Cancel editing
   if ( isOption($delete_command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      $params = $environment->getCurrentParameterArray();
      redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
   }

   // Delete item(s)
   elseif ( isOption($delete_command, $translator->getMessage('COMMON_DELETE_BUTTON')) ) {
      if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                     '_'.$environment->getCurrentModule().
                                    '_deleted_ids')) {
         $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                                  '_'.$environment->getCurrentModule().
                                                  '_deleted_ids');
      }
      $manager = $environment->getManager(module2type($environment->getCurrentModule()));
      foreach ($selected_ids as $id) {
         $item = $manager->getItem($id);
         $item->delete();
      }
      unset($manager);
      unset($item);
      $session->unsetValue('cid'.$environment->getCurrentContextID().
                                 '_'.$environment->getCurrentModule().
                                 '_deleted_ids');
      $params = $environment->getCurrentParameterArray();
      unset($params['mode']);
      unset($params['select']);
      $selected_ids = array();
      redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
   }

   if ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        and $_POST['index_view_action'] != '-1'
        and !empty($selected_ids)
      ) {
      // prepare action process
      switch ($_POST['index_view_action']) {
         case 1:
            $action = 'ENTRY_MARK_AS_READ';
            $error = false;
            $topic_manager = $environment->getTopicManager();
            $noticed_manager = $environment->getNoticedManager();
            foreach ($selected_ids as $id) {
               $topic_item = $topic_manager->getItem($id);
               if ( isset($topic_item) ) {
                  $version_id = $topic_item->getVersionID();
                  $noticed_manager->markNoticed($id, $version_id );
                  $annotation_list = $topic_item->getAnnotationList();
                  if ( !empty($annotation_list) ){
                     $annotation_item = $annotation_list->getFirst();
                     while($annotation_item){
                        $noticed_manager->markNoticed($annotation_item->getItemID(),'0');
                        $annotation_item = $annotation_list->getNext();
                     }
                  }
               }
            }
            break;
         case 2:
            $action = 'ENTRY_COPY';
            // Copy to clipboard
            foreach ($selected_ids as $id) {
               if ( !in_array($id, $clipboard_id_array) ) {
                  $clipboard_id_array[] = $id;
               }
            }
            break;
         case 3:
            $user = $environment->getCurrentUserItem();
            if( $user->isModerator() or $environment->inPrivateRoom() ){
                $session->setValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids', $selected_ids);
               $params = $environment->getCurrentParameterArray();
               $params['mode'] = 'list_actions';
               $page->addDeleteBox(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params),'index',$selected_ids);
               unset($params);
            }
            break;
         default:
            include_once('functions/error_functions.php');
            trigger_error('action ist not defined',E_USER_ERROR);
      }
      $selected_ids = array();
      $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
   } // end if (perform list actions)
   */
			// get data from database
			$topic_manager = $environment->getTopicManager();
			$topic_manager->resetData();
			$topic_manager->setContextLimit($context_item->getItemID());
			$count_all = $topic_manager->getCountAll();


			/*
if ( !empty($ref_iid) and $mode == 'attached' ){
   $topic_manager->setRefIDLimit($ref_iid);
}
			*/
			if(!empty($this->_list_parameter_arrray['sort'])) {
				$topic_manager->setSortOrder($this->_list_parameter_arrray['sort']);
			}

			if ( $this->_list_parameter_arrray['sel_activating_status'] == 2 ) {
   				$topic_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
			}


			/*
if ( !empty($search) ) {
   $topic_manager->setSearchLimit($search);
}

if ( !empty($selgroup) ) {
   $topic_manager->setGroupLimit($selgroup);
}

if ( $interval > 0 ) {
   $topic_manager->setIntervalLimit($from-1,$interval);
}



*/
			$topic_manager->select();
			$list = $topic_manager->get();
			$ids = $topic_manager->getIDArray();
			$count_all_shown = count($ids);

			$this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
            $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

            $session = $this->_environment->getSessionItem();
            $session->setValue('cid'.$environment->getCurrentContextID().'_topic_index_ids', $ids);


			/*



if (isset($_GET['select']) and $_GET['select']=='all'){
   $item = $list->getFirst();
   while($item){
      if ( !in_array($item->getItemID(), $selected_ids) ) {
         $selected_ids[] = $item->getItemID();
      }
      $item = $list->getNext();
   }
}


// Get available groups
if (!$environment->inCommunityRoom()) {
   $group_manager = $environment->getGroupManager();
   $group_manager->resetLimits();
   $group_manager->select();
   $group_list = $group_manager->get();
}

$with_modifying_actions = false;
if ( $context_item->isProjectRoom() ) {
   if ($context_item->isOpen() AND $mode != 'detailattach' AND $mode != 'formattach')  {
      $with_modifying_actions = true;
   }
} else {
   if ($context_item->isOpen() AND $mode != 'detailattach' AND $mode != 'formattach')  {
      $with_modifying_actions = true;     // Community room
   }
}
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $with_modifying_actions;
$view = $class_factory->getClass(TOPIC_INDEX_VIEW,$params);
unset($params);
if ($mode=='print'){
   $view->setPrintableView();
}
*/

			$converter = $environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();
			$id_array = array();
			$item = $list->getFirst();
			while ($item){
  				$item = $list->getNext();
			}
			$noticed_manager = $environment->getNoticedManager();
			$noticed_manager->getLatestNoticedByIDArray($id_array);
			$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);

			// prepare item array
			$item = $list->getFirst();
			$item_array = array();
			$params = array();
			$params['environment'] = $environment;
			$params['with_modifying_actions'] = false;
			$view = new cs_view($params);
			while($item) {
   				$id_array[] = $item->getItemID();
				// files
				$attachment_infos = array();
				$file_count = $item->getFileList()->getCount();
				$file_list = $item->getFileList();

				$file = $file_list->getFirst();
				while($file) {
					$lightbox = false;
					if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) $lightbox = true;

					$info = array();
					$info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
					$info['file_icon']	= $file->getFileIcon();
					$info['file_url']	= $file->getURL();
					$info['file_size']	= $file->getFileSize();
					$info['lightbox']	= $lightbox;
					$attachment_infos[] = $info;
					$file = $file_list->getNext();
				}
 				$noticed_text = $this->_getItemChangeStatus($item);
				$moddate = $item->getModificationDate();
				if ( $item->getCreationDate() <> $item->getModificationDate() and !strstr($moddate,'9999-00-00')){
         			$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
      			} else {
         			$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getCreationDate());
      			}
	            $activated_text =  '';
	            $activating_date = $item->getActivatingDate();
	            if (strstr($activating_date,'9999-00-00')){
	               $activated_text = $this->_environment->getTranslationObject()->getMessage('COMMON_NOT_ACTIVATED');
	            }else{
	               $activated_text = $this->_environment->getTranslationObject()->getMessage('COMMON_ACTIVATING_DATE').' '.$this->_environment->getTranslationObject()->getDateInLang($item->getActivatingDate());
	            }
				$item_array[] = array(
					'iid'				=> $item->getItemID(),
					'title'				=> $item->getTitle(),
					'date'				=> $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate()),
					'modificator'		=> $this->getItemModificator($item),
					'noticed'			=> $noticed_text,
					'attachment_infos'	=> $attachment_infos,
					'attachment_count'	=> $item->getFileList()->getCount(),
					'linked_entries'	=> count($item->getAllLinkedItemIDArray()),
					'activated'			=> !$item->isNotActivated(),
					'activated_text'	=> $activated_text,
					'creator_id'		=> $item->getCreatorItem()->getItemID()
				);

				$item = $list->getNext();
			}

			// append return
			$return = array(
				'items'		=> $item_array,
				'count_all'	=> $count_all_shown
			);
			return $return;

/*



// Set data for view
$view->setList($list);
$view->setCountAll($count_all);
$view->setCountAllShown($count_all_shown);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);
if ($environment->inCommunityRoom()){
   $view->setSelectedInstitution($selinstitution);
   $view->setAvailableInstitutions($institution_list);
}else{
   $view->setSelectedGroup($selgroup);
   $view->setAvailableGroups($group_list);
}
if ( !empty($ref_iid) and $mode =='attached'){
   $item_manager = $environment->getItemManager();
   $ref_item_type = $item_manager->getItemType($ref_iid);
   $ref_item_manager = $environment->getManager($ref_item_type);
   $ref_item = $ref_item_manager->getItem($ref_iid);
   $view->setRefItem($ref_item);
   $view->setRefIid($ref_iid);
   $view->setIsAttachedList();
}

if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $view->setRefIID($ref_iid);
   $view->setHasCheckboxes($mode);
   $view->setCheckedIDs($new_attach_ids);
   $view->setDontEditIDs($dontedit_attach_ids);
}elseif ($mode == 'attach'){
   $view->setHasCheckboxes('list_actions');
}else{
   $view->setCheckedIDs($selected_ids);
   $view->setHasCheckboxes('list_actions');
}

// Add list view to page
$page->add($view);

$session->setValue('interval', $interval); // interval is applied to all rubrics
$session->setValue('cid'.$context_item->getItemID().'_topic_index_ids', $ids);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);

$index_search_parameter_array = array();
$index_search_parameter_array['interval'] = $interval;
$index_search_parameter_array['sort'] = $sort;
$index_search_parameter_array['search'] = $search;
if ($environment->inCommunityRoom()){
   $index_search_parameter_array['selinstitution'] = $selinstitution;
}else{
   $index_search_parameter_array['selgroup'] = $selgroup;
}
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array',$index_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index','true');

			 */













			/*
			 * old content from topic_controller to compare
			 *
			// Get data from database
			$todo_manager = $environment->getToDosManager();
			//$todo_manager->reset();

			if(!isset($only_show_array) || empty($only_show_array)) {
				$todo_manager->setContextLimit($environment->getCurrentContextID());
				$all_ids = $todo_manager->getIds();
				$count_all = count($all_ids);

				if(isset($all_ids[0])) {
					$newest_id = $all_ids[0];
					$item = $todo_manager->getItem($newest_id);
					$date = $item->getModificationDate();
					$now = getCurrentDateTimeInMySQL();
					if($date <= $now) {
						$sel_activating_status = 1;
					}
				} elseif($count_all == 0) {
					$sel_activating_status = 1;
				}

				$todo_manager->resetData();

				if ( !empty($this->_list_parameter_arrray['ref_iid']) and $this->getViewMode() == 'attached' ){
	   				$todo_manager->setRefIDLimit($this->_list_parameter_arrray['ref_iid']);
				}
				if ( !empty($this->_list_parameter_arrray['ref_user']) and $this->getViewMode() == 'attached' ){
	   				$todo_manager->setRefUserLimit($this->_list_parameter_arrray['ref_user']);
				}
				if ( !empty($this->_list_parameter_arrray['sort']) ) {
	   				$todo_manager->setSortOrder($this->_list_parameter_arrray['sort']);
				}
				if ( !empty($this->_list_parameter_arrray['search']) ) {
	   				$todo_manager->setSearchLimit($this->_list_parameter_arrray['search']);
				}
				if($sel_activating_status == 2) {
					$todo_manager->showNoNotActivatedEntries();
				}

				// Find current status selection
			   	if ( isset($_GET['selstatus']) and $_GET['selstatus'] !='-2') {
			      	$selstatus = $_GET['selstatus'];
			   	} else {
			      	$selstatus = 4;
			   	}
			   	if(!empty($selstatus)) {
			   		$todo_manager->setStatusLimit($selstatus);
			   	}

				if ( !empty($this->_list_parameter_arrray['selbuzzword']) ) {
	   				$todo_manager->setBuzzwordLimit($this->_list_parameter_arrray['selbuzzword']);
				}

				if(!empty($last_selected_tag)) {
					$todo_manager->setTagLimit($last_selected_tag);
				}

				if ( $this->_list_parameter_arrray['interval'] > 0 ) {
	   				$todo_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
				}
			} else {
				$todo_manager->resetLimits();
				$todo_manager->setIDArrayLimit($only_show_array);
			}



			$todo_manager->select();
			$list = $todo_manager->get();
			$ids = $todo_manager->getIDArray();
			$count_all_shown = count($ids);

			$this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
            $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

			$id_array = array();
			$item = $list->getFirst();
			while ($item){
   				$id_array[] = $item->getItemID();
   				$item = $list->getNext();
			}
			$noticed_manager = $environment->getNoticedManager();
			$noticed_manager->getLatestNoticedByIDArray($id_array);
			$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);

			$step_manager = $environment->getStepManager();
			$step_list = $step_manager->getAllStepItemListByIDArray($id_array);
			$item = $step_list->getFirst();
			while ($item) {
			   $id_array[] = $item->getItemID();
			   $item = $step_list->getNext();
			}

			// caching
			$link_manager = $environment->getLinkManager();
			$file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array);
			$file_manager = $environment->getFileManager();
			$file_manager->setIDArrayLimit($file_id_array);
			$file_manager->select();

			if (isset($_GET['select']) and $_GET['select']=='all'){
			   $item = $list->getFirst();
			   while($item){
			      if ( !in_array($item->getItemID(), $selected_ids) ) {
			         $selected_ids[] = $item->getItemID();
			      }
			      $item = $list->getNext();
			   }
			}

			// Find current option
			if ( isset($_POST['option']) ) {
			   $option = $_POST['option'];
			} elseif ( isset($_GET['option']) ) {
			   $option = $_GET['option'];
			} else {
			   $option = '';
			}

			if (isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO')) and $_POST['index_view_action'] != '3'){
			     $selected_ids = array();
			}

			// prepare item array
			$item = $list->getFirst();
			$item_array = array();
			$params = array();
			$params['environment'] = $environment;
			$params['with_modifying_actions'] = false;
			$view = new cs_view($params);
			while($item) {
				$noticed_text = $this->_getItemChangeStatus($item);
				$item_array[] = array(
				'iid'				=> $item->getItemID(),
				'title'				=> $view->_text_as_html_short($item->getTitle()),
				'date'				=> $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate()),
				'creator'			=> $item->getCreatorItem()->getFullName(),
				'noticed'			=> $noticed_text,
				'attachment_count'	=> $item->getFileList()->getCount()
//				'attachment_infos'	=>
				);

				$item = $list->getNext();
			}

			// append return
			$return = array(
				'items'		=> $item_array,
				'count_all'	=> $count_all_shown
			);
			return $return;

			*/
		}

		protected function getAdditionalActions(&$perms) {
		}

		protected function getAdditionalListActions() {
			return array();
		}

		protected function getAdditionalRestrictionText(){
			$return = array();

			return $return;
		}

		protected function getAdditionalRestrictions() {
			$return = array();

			return $return;
		}
	}