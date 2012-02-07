<?php
	require_once('classes/controller/cs_list_controller.php');
	
	class cs_institution_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'institution_list';
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_USER_TYPE);
		}
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		
		/**
		 * INDEX
		 */
		public function actionIndex() {
			// init list params
			$this->initListParameters(CS_INSTITUTION_TYPE);

			// perform list options
			$this->performListOption(CS_INSTITUTION_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('institution','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('institution','list_content', $list_content);
		}
		
		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$return = array();
			$translator = $environment->getTranslationObject();
			
			
			
			
			/*
			 * // Get the translator object



// Search / Select Area
if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   $search = '';
   $seltopic = '';
} else {

   // Find current search text
   if ( isset($_GET['search']) ) {
      $search = $_GET['search'];
   } else {
      $search = '';
   }

   // Find current topic selection
   if ( isset($_GET['seltopic'])   and $_GET['seltopic'] !='-2') {
      $seltopic = $_GET['seltopic'];
   } else {
      $seltopic = 0;
   }
}
$context = $environment->getCurrentContextItem();

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

   $action ='';
   if ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        and $_POST['index_view_action'] != '-1'
        and !empty($selected_ids)
      ) {
      // prepare action process
      switch ($_POST['index_view_action']) {
         case 1:
            $action = 'ENTRY_MARK_AS_READ';
            $error = false;
            $institution_manager = $environment->getInstitutionManager();
            $noticed_manager = $environment->getNoticedManager();
            foreach ($selected_ids as $id) {
               $institution_item = $institution_manager->getItem($id);
               if ( isset($institution_item) ) {
                  $version_id = $institution_item->getVersionID();
                  $noticed_manager->markNoticed($id, $version_id );
                  $annotation_list =$institution_item->getAnnotationList();
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
            include_once('functions/error_functions.php');trigger_error('action ist not defined',E_USER_ERROR);
      }
      $selected_ids = array();
      $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
   } // end if (perform list actions)
   */
			
			// get data from database
			$institution_manager = $environment->getInstitutionManager();
			$institution_manager->setContextLimit($environment->getCurrentContextID());
			$count_all = $institution_manager->getCountAll();
			
			if ( !empty($this->_list_parameter_arrray['ref_iid']) and $this->getViewMode() == 'attached' ){
   				$institution_manager->setRefIDLimit($this->_list_parameter_arrray['ref_iid']);
			}
			
			if ( !empty($this->_list_parameter_arrray['sort']) ) {
   				$institution_manager->setSortOrder($this->_list_parameter_arrray['sort']);
			}
			
			if ( !empty($this->_list_parameter_arrray['search']) ) {
   				$institution_manager->setSearchLimit($this->_list_parameter_arrray['search']);
			}
			
			if ( !empty($this->_list_parameter_arrray['seltopic']) ) {
   				$institution_manager->setTopicLimit($this->_list_parameter_arrray['seltopic']);
			}
			
			if ( $this->_list_parameter_arrray['interval'] > 0 ) {
   				$institution_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
			}
            
            $institution_manager->select();
            $list = $institution_manager->get();
            $ids = $institution_manager->getIDArray();
            $count_all_shown = count($ids);
            
            $id_array = array();
            $item = $list->getFirst();
            while($item) {
            	$id_array[] = $item->getItemID();
            	$item = $list->getNext();
            }
            
            $noticed_manager = $environment->getNoticedManager();
            $noticed_manager->getLatestNoticedByIDArray($id_array);
            $noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
            
            $this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
            $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);
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
*/
            
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
					'modificator'		=> $item->getModificatorItem()->getFullName(),
					'noticed'			=> $noticed_text
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

// Get available topics
$topic_manager = $environment->getTopicManager();
$topic_manager->resetLimits();
$topic_manager->select();
$topic_list = $topic_manager->get();

// Prepare view object
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
$view = $class_factory->getClass(INSTITUTION_INDEX_VIEW,$params);
unset($params);

// Set data for view
$view->setList($list);
$view->setCountAll($count_all);
$view->setCountAllShown($count_all_shown);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);
$view->setSelectedTopic($seltopic);
$view->setAvailableTopics($topic_list);

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

// Safe information in session for later use
$session->setValue('interval', $interval); // interval is applied to all rubrics
$session->setValue('cid'.$environment->getCurrentContextID().'_institution_index_ids', $ids);
if (empty($action)){
   $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);
}else{
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids');
}

$index_search_parameter_array = array();
$index_search_parameter_array['interval'] = $interval;
$index_search_parameter_array['sort'] = $sort;
$index_search_parameter_array['search'] = $search;
$index_search_parameter_array['seltopic'] = $seltopic;
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array',$index_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index','true');

?>
			 */
		}
		
		protected function getAdditionalActions(&$perms) {
		}

		protected function getAdditionalListActions() {
			return array();
		}
	}