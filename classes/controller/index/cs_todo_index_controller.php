<?php
	require_once('classes/controller/cs_list_controller.php');
	
	class cs_todo_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'todo_list';
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
			$this->initListParameters(CS_TODO_TYPE);

			// perform list options
			$this->performListOption(CS_TODO_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('todo','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('todo','list_content', $list_content);
		}
		
		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$translator = $environment->getTranslationObject();
			$converter = $this->_environment->getTextConverter();
			$return = array();

			$last_selected_tag = '';
			$seltag_array = array();

			// Find current topic selection
			if(isset($_GET['seltag']) && $_GET['seltag'] == 'yes') {
				$i = 0;
				while(!isset($_GET['seltag_' . $i])) {
					$i++;
				}
				$seltag_array[] = $_GET['seltag_' . $i];
				$j = 0;
				while(isset($_GET['seltag_' . $i]) && $_GET['seltag_' . $i] != '-2') {
					if(!empty($_GET['seltag_' . $i])) {
						$seltag_array[$i] = $_GET['seltag_' . $i];
						$j++;
					}
					$i++;
				}
				$last_selected_tag = $seltag_array[$j-1];
			}
			
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
				
				$item_array[] = array(
					'iid'				=> $item->getItemID(),
					'title'				=> $view->_text_as_html_short($item->getTitle()),
					'date'				=> $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate()),
					'modificator'		=> $this->getItemModificator($item),
					'noticed'			=> $noticed_text,
					'attachment_count'	=> $file_count,
					'attachment_infos'	=> $attachment_infos
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
			/*
			 * $html  = '';
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $hash_manager = $this->_environment->getHashManager();
      $params = $this->_environment->getCurrentParameterArray();
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/abbo.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_ABBO').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/abbo.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_ABBO').'"/>';
      }
      $ical_url = '<a title="'.$this->_translator->getMessage('TODO_ABBO').'"  href="webcal://';
      $ical_url .= $_SERVER['HTTP_HOST'];
      global $c_single_entry_point;
      $ical_url .= str_replace($c_single_entry_point,'ical.php',$_SERVER['PHP_SELF']);
      $ical_url .= '?cid='.$_GET['cid'].'&amp;mod=todo&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      $html .= $ical_url;
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/export.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_EXPORT').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/export.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_EXPORT').'"/>';
      }
      $html .= '<a title="'.$this->_translator->getMessage('TODO_EXPORT').'"  href="ical.php?cid='.$_GET['cid'].'&amp;mod=todo&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      unset($params);
      return $html;
			 */
		}

		protected function getAdditionalListActions() {
			$return = array();
			$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_COPY, 'display' => '___COMMON_LIST_ACTION_COPY___');
		   $return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_DOWNLOAD, 'display' => '___COMMON_LIST_ACTION_DOWNLOAD___');
			return $return;
		}
	}