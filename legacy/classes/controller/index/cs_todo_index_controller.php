<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_todo_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			// set selected status
			$this->setSelectedStatus();

			$this->_tpl_file = 'todo_list';

			// this will enable processing of additional restriction texts
			$this->_additional_selects = true;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_TODO_TYPE);
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
			$params = $environment->getCurrentParameterArray();
			$return = array();

			$last_selected_tag = '';
			$seltag_array = array();

			// Find current topic selection
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
				if ( !empty($this->_list_parameter_arrray['selgroup']) ) {
					$todo_manager->setGroupLimit($this->_list_parameter_arrray['selgroup']);
				}
				if ( !empty($this->_list_parameter_arrray['search']) ) {
	   				$todo_manager->setSearchLimit($this->_list_parameter_arrray['search']);
				}
				if ( $this->_list_parameter_arrray['sel_activating_status'] == 2 ) {
	   				$todo_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
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
				if ( !empty($this->_list_parameter_arrray['last_selected_tag']) ){
   					$todo_manager->setTagLimit($this->_list_parameter_arrray['last_selected_tag']);
				}

				if(!empty($seltag_array)) {
					$todo_manager->setTagArrayLimit($seltag_array);
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

			$session = $this->_environment->getSessionItem();
			$session->setValue('cid'.$environment->getCurrentContextID().'_todo_index_ids', $ids);

			$id_array = array();
			$item = $list->getFirst();
			while ($item){
   				$id_array[] = $item->getItemID();
   				$item = $list->getNext();
			}
			$assessment_manager = $environment->getAssessmentManager();
			$assessment_manager->getAssessmentForItemAverageByIDArray($id_array);

			$step_manager = $environment->getStepManager();
			$step_list = $step_manager->getAllStepItemListByIDArray($id_array);
			$item = $step_list->getFirst();
			while ($item) {
			   $id_array[] = $item->getItemID();
			   $item = $step_list->getNext();
			}

			$noticed_manager = $environment->getNoticedManager();
			$noticed_manager->getLatestNoticedByIDArray($id_array);
			$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);

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

				$file_count = $item->getFileListWithFilesFromSteps()->getCount();
				$file_list = $item->getFileListWithFilesFromSteps();
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
				$assessment_stars_text_array = array('non_active','non_active','non_active','non_active','non_active');
				$current_context = $this->_environment->getCurrentContextItem();
				if($current_context->isAssessmentActive()) {
					$assessment_manager = $this->_environment->getAssessmentManager();
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
					'processors'		=> $this->getProcessorArray($item),
					'noticed'			=> $noticed_text,
					'status'			=> $item->getStatus(),
					'process_date'		=> $this->_getDateInLang($item),
					'process'			=> $this->_getProcess($item),
					'assessment_array'	=> $assessment_stars_text_array,
					'attachment_count'	=> $file_count,
					'attachment_infos'	=> $attachment_infos,
					'activated_text'	=> $activated_text,
					'creator_id'		=> $item->getCreatorItem()->getItemID(),
					'activated'			=> !$item->isNotActivated()
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

   private function _getDateInLang($item){
      $translator = $this->_environment->getTranslationObject();
      $original_date = $item->getDate();
      $date = getDateInLang($original_date);
      $status = $item->getStatus();
      $actual_date = date("Y-m-d H:i:s");
      if ($status !=$translator->getMessage('TODO_DONE') and $original_date < $actual_date){
         $date = '<span class="required">'.$date.'</span>';
      }
      if ($original_date == '9999-00-00 00:00:00'){
          $date = $translator->getMessage('TODO_NO_END_DATE');
      }
      return $date;
   }

   function _getProcess($item){
      $translator = $this->_environment->getTranslationObject();
      $step_html = '';
      $step_minutes = 0;
      $step_item_list = $item->getStepItemList();
      if ( $step_item_list->isEmpty() ) {
         $status = $item->getStatus();
      } else {
         $step = $step_item_list->getFirst();
         $count = $step_item_list->getCount();
         $counter = 0;
         while ($step) {
            $counter++;
            $step_minutes = $step_minutes + $step->getMinutes();
            $step = $step_item_list->getNext();
         }
      }
      $done_time = '';
      $done_percentage = 100;
      if ($item->getPlannedTime() > 0){
         $done_percentage = $step_minutes / $item->getPlannedTime() * 100;
      }

      $tmp_message = $translator->getMessage('COMMON_MINUTES_SHORT');
      $step_minutes_text = $step_minutes;
      if (($step_minutes/60)>1 and ($step_minutes/60)<=8){
         $step_minutes_text = '';
         $exact_minutes = $step_minutes/60;
         $step_minutes = round($exact_minutes,1);
         if ($step_minutes != $exact_minutes){
            $step_minutes_text .= 'ca. ';
         }
         if ($translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
         $step_minutes_text .= $step_minutes;
         $tmp_message = $translator->getMessage('COMMON_HOURS');
         if ($step_minutes == 1){
            $tmp_message = $translator->getMessage('COMMON_HOUR');
         }
       }elseif(($step_minutes/60)>8){
         $exact_minutes = ($step_minutes/60)/8;
         $step_minutes = round($exact_minutes,1);
         $step_minutes_text = '';
         if ($step_minutes != $exact_minutes){
            $step_minutes_text .= 'ca. ';
         }
         if ($translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
         $step_minutes_text .= $step_minutes;
         $tmp_message = $translator->getMessage('COMMON_DAYS');
         if ($step_minutes == 1){
            $tmp_message = $translator->getMessage('COMMON_DAY');
         }
      }else{
         $step_minutes = round($step_minutes,1);
         if ($translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
      }

      $display_plannend_time = $item->getPlannedTime();
      $shown_time = $step_minutes_text.' '.$tmp_message;
      $display_time_text_addon = $display_plannend_time.' '.$translator->getMessage('COMMON_MINUTES');
      if (($display_plannend_time/60)>1){
         $display_time_text_addon = round($display_plannend_time/60);
         if ($translator->getSelectedLanguage() == 'de'){
            $display_time_text_addon = str_replace('.',',',$display_time_text_addon);
         }
         if ($display_time_text_addon == 1){
            $display_time_text_addon .= ' '.$translator->getMessage('COMMON_HOUR');
         }else{
            $display_time_text_addon .= ' '.$translator->getMessage('COMMON_HOURS');
         }
      }
      if ($display_plannend_time/60>8){
         $display_time_text_addon = round($display_plannend_time/60/8,1);
         if ($translator->getSelectedLanguage() == 'de'){
            $display_time_text_addon = str_replace('.',',',$display_time_text_addon);
         }
         if ($display_time_text_addon == 1){
            $display_time_text_addon .= ' '.$translator->getMessage('COMMON_DAY');
         }else{
            $display_time_text_addon .= ' '.$translator->getMessage('COMMON_DAYS_AKK');
         }
      }
      $display_time_text = $shown_time.' '.$translator->getMessage('COMMON_FROM2').' '.$display_time_text_addon.' - '.round($done_percentage).'% '.$translator->getMessage('TODO_DONE');
      if($done_percentage <= 100){
         $style = ' height: 5px; background-color: #75ab05; ';
         $done_time .= '      <div title="'.$display_time_text.'" style="border: 1px solid #444;  margin: 10px 3px 0px; height: 5px; width: 60px;">'.LF;
         if ( $done_percentage >= 30 ) {
            $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
         } else {
            $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
         }
         $done_time .= '      </div>'.LF;
      }elseif($done_percentage <= 120){
         $done_percentage = (100 / $done_percentage) *100;
         $style = ' height: 5px; border: 1px solid #444; background-color: #f2f030; ';
         $done_time .= '         <div title="'.$display_time_text.'" style="width: 60px; font-size: 2pt; margin: 10px 3px 0px; height: 5px; '.$style.' color:#000000;">'.LF;
         $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:5px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
         $done_time .= '      </div>'.LF;
         $done_time .= '</div>'.LF;
      }else{
         $done_percentage = (100 / $done_percentage) *100;
         $style = ' height: 5px; border: 1px solid #444; background-color: #f23030; ';
         $done_time .= '         <div title="'.$display_time_text.'" style="width: 60px; font-size: 2pt; margin: 10px 3px 0px; height: 5px; '.$style.' color:#000000;">'.LF;
         $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:5px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
         $done_time .= '      </div>'.LF;
         $done_time .= '</div>'.LF;
      }
      if ($item->getPlannedTime() > 0){
         $process = $done_time;
      }else{
         $process = '<p>'.$shown_time.'</p>';
      }
      return $process;
   }


    private function getProcessorArray($item) {
		$return = array();
		$current_user = $this->_environment->getCurrentUser();
		$converter = $this->_environment->getTextConverter();

		$members = $item->getProcessorItemList();
		if(!$members->isEmpty()) {
			$member = $members->getFirst();
			$count = $members->getCount();
			$counter = 0;

			while($member) {
				$member_array = array();

				$linktext = $member->getFullname();
				// TODO: compareWithSearchText
				$linktext = $converter->text_as_html_short($linktext);
				$member_array['linktext'] = $linktext;

				$param_zip = $this->_environment->getValueOfParameter('download');

				if($member->isUser()) {
					$member_array['is_user'] = true;

					if($member->maySee($current_user)) {
						$member_array['visible'] = true;
						$member_array['as_link'] = false;

						if(empty($param_zip) || $param_zip !== 'zip') {
							$member_array['as_link'] = true;
							$member_array['item_id'] = $member->getItemID();
						}
					} else {
						// disabled
						$member_array['visible'] = false;
					}
				} else {
					$member_array['is_user'] = false;
					$member_array['as_link'] = false;

					if(empty($param_zip) || $param_zip !== 'zip') {
						$member_array['as_link'] = true;
						$member_array['item_id'] = $member->getItemID();
					}
				}

				$return[] = $member_array;

				$member = $members->getNext();
			}
		}

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

		private function setSelectedStatus() {
			$current_context = $this->_environment->getCurrentContextItem();

			// find current status selection
			if(isset($_GET['selstatus']) && $_GET['selstatus'] != '-2') {
				$this->_selected_status = $_GET['selstatus'];

			} else {
				$this->_selected_status = '4';
			}
		}


		protected function getAdditionalRestrictionText(){
			$return = array();

			$params = $this->_environment->getCurrentParameterArray();
			$current_context = $this->_environment->getCurrentContextItem();
			$translator = $this->_environment->getTranslationObject();

			if(isset($params['selstatus'])/* && !empty($params['selstatus'])*/) {
				$restriction = array(
					'name'				=> '',
					'type'				=> '',
					'link_parameter'	=> ''
				);


		        if (isset($params['selstatus']) and $params['selstatus'] == 1){
		           $restriction['name'] = $translator->getMessage('TODO_NOT_STARTED');
		        }elseif(isset($params['selstatus']) and $params['selstatus'] == 2){
		           $restriction['name'] = $translator->getMessage('TODO_IN_POGRESS');
		        }elseif(isset($params['selstatus']) and $params['selstatus'] == 3){
		           $restriction['name'] = $translator->getMessage('TODO_DONE');
		        }elseif(isset($params['selstatus']) and $params['selstatus'] == 4){
		           $restriction['name'] = $translator->getMessage('TODO_NOT_DONE');
		        }elseif(isset($params['selstatus']) and $params['selstatus'] != 0){
		           $context_item = $this->_environment->getCurrentContextItem();
		           $todo_status_array = $context_item->getExtraToDoStatusArray();
		           $status_text = '';
		           if (isset($todo_status_array[$params['selstatus']])){
		              $restriction['name'] = $todo_status_array[$params['selstatus']];
		           }
		        }else{
		           $restriction['name'] = '';
		        }

		        if ($restriction["name"] !== "") {
		        	// set link parameter
		        	$params['selstatus'] = 0;
		        	$link_parameter_text = '';
		        	if ( count($params) > 0 ) {
		        		foreach ($params as $key => $parameter) {
		        			$link_parameter_text .= '&'.$key.'='.$parameter;
		        		}
		        	}
		        	$restriction['link_parameter'] = $link_parameter_text;

		        	$return[] = $restriction;
		        }
			} else {
		        $restriction['name'] = $translator->getMessage('TODO_NOT_DONE');
				// set link parameter
				$params['selstatus'] = 0;
				$link_parameter_text = '';
				if ( count($params) > 0 ) {
					foreach ($params as $key => $parameter) {
						$link_parameter_text .= '&'.$key.'='.$parameter;
					}
				}
				$restriction['link_parameter'] = $link_parameter_text;
				$return[] = $restriction;
			}

			return $return;
		}

		protected function getAdditionalRestrictions() {
			$return = array();

			$restriction = array(
				'item'		=> array(),
				'action'	=> '',
				'hidden'	=> array(),
				'tag'		=> '',
				'name'		=> '',
				'custom'	=> true
			);

			$translator = $this->_environment->getTranslationObject();
			$dates_manager = $this->_environment->getDatesManager();
			$context_item = $this->_environment->getCurrentContextItem();

			// set tag and name
			$tag = $translator->getMessage('TODO_STATUS');
			$restriction['tag'] = $tag;
			$restriction['name'] = 'status';

			// set action
			$params = $this->_environment->getCurrentParameterArray();

			if(!isset($params['selstatus'])) {
				unset($params['from']);
			}

			unset($params['selstatus']);
			$link_parameter_text = '';

			$hidden_array = array();
			if(count($params) > 0) {
				foreach($params as $key => $parameter) {
					$link_parameter_text .= '&'.$key.'='.$parameter;
					$hidden_array[] = array(
						'name'	=> $key,
						'value'	=> $parameter
					);
				}
			}
			$restriction['action'] = 'commsy.php?cid='.$this->_environment->getCurrentContextID().'&mod='.$this->_environment->getCurrentModule().'&fct='.$this->_environment->getCurrentFunction().'&'.$link_parameter_text;

			// set hidden
			$restriction['hidden'] = $hidden_array;

			// set items
			$items = array();

			// no selection
			$item = array(
				'id'		=> 0,
				'name'		=> $translator->getMessage('ALL'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;

			$item = array(
				'id'		=> -2,
				'name'		=> '------------------------------',
				'selected'	=> $this->_selected_status,
				'disabled'	=> true
			);
			$items[] = $item;

			$item = array(
				'id'		=> 1,
				'name'		=> $translator->getMessage('TODO_NOT_STARTED'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;

			$item = array(
				'id'		=> 2,
				'name'		=> $translator->getMessage('TODO_IN_POGRESS'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;

			$item = array(
				'id'		=> 3,
				'name'		=> $translator->getMessage('TODO_DONE'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;

      		$extra_status_array = $context_item->getExtraToDoStatusArray();
      		if (!empty($extra_status_array)){
				$item = array(
					'id'		=> -2,
					'name'		=> '------------------------------',
					'selected'	=> $this->_selected_status,
					'disabled'	=> true
				);
				$items[] = $item;
				foreach ($extra_status_array as $key => $value){
					$item = array(
						'id'		=> $key,
						'name'		=> $value,
						'selected'	=> $this->_selected_status
					);
					$items[] = $item;
				}
      		}

      		$item = array(
      				'id'		=> -2,
      				'name'		=> '------------------------------',
      				'selected'	=> $this->_selected_status,
      				'disabled'	=> true
      		);
      		$items[] = $item;

      		$item = array(
      				'id'		=> 4,
      				'name'		=> $translator->getMessage('TODO_NOT_DONE'),
      				'selected'	=> $this->_selected_status
      		);
      		$items[] = $item;

			$restriction['items'] = $items;
			$return[] = $restriction;

			return $return;
		}
	}