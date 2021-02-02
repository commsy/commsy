<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_announcement_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'announcement_list';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_ANNOUNCEMENT_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/

		/**
		 * INDEX
		 */
		public function actionIndex() {
			// init list params
			$this->initListParameters(CS_ANNOUNCEMENT_TYPE);

			// perform list options
			$this->performListOption(CS_ANNOUNCEMENT_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('announcement','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('announcement','list_content', $list_content);
		}

		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/

		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$converter = $this->_environment->getTextConverter();
			$params = $this->_environment->getCurrentParameterArray();
			$return = array();

			$last_selected_tag = '';
			$seltag_array = array();

// 			// Find current topic selection
// 			if(isset($_GET['seltag']) && $_GET['seltag'] == 'yes') {
// 				$i = 0;
// 				while(!isset($_GET['seltag_' . $i])) {
// 					$i++;
// 				}
// 				$seltag_array[] = $_GET['seltag_' . $i];
// 				$j = 0;
// 				while(isset($_GET['seltag_' . $i]) && $_GET['seltag_' . $i] != '-2') {
// 					if(!empty($_GET['seltag_' . $i])) {
// 						$seltag_array[$i] = $_GET['seltag_' . $i];
// 						$j++;
// 					}
// 					$i++;
// 				}
// 				$last_selected_tag = $seltag_array[$j-1];
// 			}
			
			// get selected seltags
			$seltag_array = array();
			foreach($params as $key => $value) {
				if(substr($key, 0, 7) == 'seltag_'){
					// set seltag array
					$seltag_array[$key] = $value;
				} elseif(substr($key, 0, 6) == 'seltag'){
					$seltag_array[$key.'_'.$value] = "true";
				}
			}

			// Get data from database
			$announcement_manager = $environment->getAnnouncementManager();
			$announcement_manager->setContextLimit($environment->getCurrentContextID());
			$all_ids = $announcement_manager->getIds();
			$count_all = count($all_ids);
			if (isset($all_ids[0])){
   				$newest_id = $all_ids[0];
   				$item = $announcement_manager->getItem($newest_id);
   				$date = $item->getModificationDate();
   				$now = getCurrentDateTimeInMySQL();
   				if ($date <= $now){
      				$sel_activating_status = 1;
   				}
			}elseif($count_all == 0){
   				$sel_activating_status = 1;
			}
			$announcement_manager->resetData();
			if ( !empty($this->_list_parameter_arrray['ref_iid']) and $this->getViewMode() == 'attached' ){
   				$announcement_manager->setRefIDLimit($this->_list_parameter_arrray['ref_iid']);
			}
			if ( !empty($this->_list_parameter_arrray['ref_user']) and $this->getViewMode() == 'attached' ){
   				$announcement_manager->setRefUserLimit($this->_list_parameter_arrray['ref_user']);
			}
			if ( !empty($this->_list_parameter_arrray['sort']) ) {
   				$announcement_manager->setSortOrder($this->_list_parameter_arrray['sort']);
			}
			if ( $this->_list_parameter_arrray['sel_activating_status'] == 2 ) {
   				$announcement_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
			}
			if ( !empty($this->_list_parameter_arrray['search']) ) {
   				$announcement_manager->setSearchLimit($this->_list_parameter_arrray['search']);
			}
			if ( !empty($this->_list_parameter_arrray['selgroup']) ) {
   				$announcement_manager->setGroupLimit($this->_list_parameter_arrray['selgroup']);
			}
			if ( !empty($this->_list_parameter_arrray['seltopic']) ) {
   				$announcement_manager->setTopicLimit($this->_list_parameter_arrray['seltopic']);
			}
			if ( !empty($this->_list_parameter_arrray['selinstitution']) ) {
   				$announcement_manager->setTopicLimit($this->_list_parameter_arrray['selinstitution']);
			}
			if ( !empty($this->_list_parameter_arrray['selbuzzword']) ) {
   				$announcement_manager->setBuzzwordLimit($this->_list_parameter_arrray['selbuzzword']);
			}
			if ( !empty($this->_list_parameter_arrray['last_selected_tag']) ){
   				$announcement_manager->setTagLimit($this->_list_parameter_arrray['last_selected_tag']);
			}
			if ( !empty($seltag_array) ){
   				$announcement_manager->setTagArrayLimit($seltag_array);
			}
			if ( $this->_list_parameter_arrray['interval'] > 0 ) {
   				$announcement_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
			}
			if ( !empty($only_show_array) ) {
   				$announcement_manager->resetLimits();
   				$announcement_manager->setIDArrayLimit($only_show_array);
			}
			$announcement_manager->select();
			$list = $announcement_manager->get();
			$ids = $announcement_manager->getIDArray();
			$count_all_shown = count($ids);

			$this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
            $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

            $session = $this->_environment->getSessionItem();
            $session->setValue('cid'.$environment->getCurrentContextID().'_announcement_index_ids', $ids);

			$id_array = array();
			$item = $list->getFirst();
			while ($item){
   				$id_array[] = $item->getItemID();
   				$item = $list->getNext();
			}
			$assessment_manager = $environment->getAssessmentManager();
			$assessment_manager->getAssessmentForItemAverageByIDArray($id_array);

			$noticed_manager = $environment->getNoticedManager();
			$noticed_manager->getLatestNoticedByIDArray($id_array);
			$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
			$link_manager = $environment->getLinkManager();
			$file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array);
			$file_manager = $environment->getFileManager();
			$file_manager->setIDArrayLimit($file_id_array);
			$file_manager->select();

			// prepare item array
			$item = $list->getFirst();
			$item_array = array();
			$params = array();
			$params['environment'] = $environment;
			$params['with_modifying_actions'] = false;
			$view = new cs_view($params);
			while($item) {
				$assessment_stars_text_array = array('non_active','non_active','non_active','non_active','non_active');
				$current_context = $environment->getCurrentContextItem();
				if($current_context->isAssessmentActive()) {
					$assessment_manager = $environment->getAssessmentManager();
					$assessment = $assessment_manager->getAssessmentForItemAverage($item);
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
					for ($i=0; $i< $assessment_count_stars; $i++){
						$assessment_stars_text_array[$i] = 'active';
					}
				}

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

				$noticed_text = $this->_getItemChangeStatus($item);
				$item_array[] = array(
				'iid'				=> $item->getItemID(),
				'title'				=> $item->getTitle(),
				'date'				=> $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate()),
				'modificator'		=> $this->getItemModificator($item),
				'assessment_array'  => $assessment_stars_text_array,
				'noticed'			=> $noticed_text,
				'attachment_count'	=> $file_count,
				'attachment_infos'	=> $attachment_infos,
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
		}

		protected function getAdditionalActions(&$perms) {
		}

		protected function getAdditionalListActions() {
			$return = array();
			$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_COPY, 'display' => '___COMMON_LIST_ACTION_COPY___');
		   $return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_DOWNLOAD, 'display' => '___COMMON_LIST_ACTION_DOWNLOAD___');
			return $return;
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

/*

//*******************************
// Prepare view object

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
$view = $class_factory->getClass(ANNOUNCEMENT_INDEX_VIEW,$params);
unset($params);

foreach($sel_array as $rubric => $value){
   if (!empty($value)){
      $announcement_manager->setRubricLimit($rubric,$value);
   }
   $label_manager = $environment->getManager($rubric);
   $label_manager->setContextLimit($environment->getCurrentContextID());
   $label_manager->select();
   $rubric_list = $label_manager->get();
   $temp_rubric_list = clone $rubric_list;
   $view->setAvailableRubric($rubric,$temp_rubric_list);
   $view->setSelectedRubric($rubric,$value);
   unset($rubric_list);
}
//********************************


$id_array = array();
$item = $list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $list->getNext();
}
$noticed_manager = $environment->getNoticedManager();
$noticed_manager->getLatestNoticedByIDArray($id_array);
$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
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
if (isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO')) and $_POST['index_view_action'] != '3'){
     $selected_ids = array();
}




// Set data for view
$view->setList($list);
$view->setCountAllShown($count_all_shown);
$view->setCountAll($count_all);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);
$view->setClipboardIDArray($clipboard_id_array);

$view->setAvailableBuzzwords($buzzword_list);
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);
$view->setActivationLimit($sel_activating_status);

if ( !empty($ref_iid) and $mode =='attached' ) {
   $item_manager = $environment->getItemManager();
   $ref_item_type = $item_manager->getItemType($ref_iid);
   $ref_item_manager = $environment->getManager($ref_item_type);
   $ref_item = $ref_item_manager->getItem($ref_iid);
   $view->setRefItem($ref_item);
   $view->setRefIid($ref_iid);
   $view->setIsAttachedList();
} elseif ( !empty($ref_user) and $mode =='attached' ) {
   $item_manager = $environment->getItemManager();
   $ref_item_type = $item_manager->getItemType($ref_user);
   $ref_item_manager = $environment->getManager(CS_USER_TYPE);
   $ref_item = $ref_item_manager->getItem($ref_user);
   $view->setRefItem($ref_item);
   $view->setRefUser($ref_user);
   $view->setIsAttachedList();
}


if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $view->setRefIID($ref_iid);
   if (isset($ref_user)) {
     $view->setRefUser($ref_user);
   }
   $view->setHasCheckboxes($mode);
   $view->setCheckedIDs($new_attach_ids);
   $view->setDontEditIDs($dontedit_attach_ids);
}elseif ($mode == 'attach'){
   $view->setHasCheckboxes('list_actions');
}else{
   $view->setCheckedIDs($selected_ids);
   $view->setHasCheckboxes('list_actions');
}

// @segment-end 50396

// @segment-begin 40245 add-view-object-to-page-object

// Add list view to page
$page->add($view);

// @segment-end 40245


$session->setValue('announcement_clipboard', $clipboard_id_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_announcement_index_ids', $ids);
$session->setValue('interval', $interval);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);

$index_search_parameter_array = array();
$index_search_parameter_array['interval'] = $interval;
$index_search_parameter_array['sort'] = $sort;
$index_search_parameter_array['search'] = $search;
$index_search_parameter_array['sel_array'] = $sel_array;
$index_search_parameter_array['selbuzzword'] = $selbuzzword;
$index_search_parameter_array['seltag_array'] = $seltag_array;
$index_search_parameter_array['sel_activating_status'] = $sel_activating_status;
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array',$index_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index','true');

*/



