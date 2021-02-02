<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_discussion_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'discussion_list';

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
			$this->assign('room', 'rubric', CS_DISCUSSION_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/

		/**
		 * INDEX
		 */
		protected function actionIndex() {
			// init list params
			$this->initListParameters(CS_DISCUSSION_TYPE);

			// perform list options
			$this->performListOption(CS_DISCUSSION_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('discussion','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('discussion','list_content', $list_content);

			// Safe information in session for later use
			/*
			$session->setValue('discussion_clipboard', $clipboard_id_array);
			$session->setValue('interval', $interval); // interval is applied to all rubrics
			$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);
			*/
		}


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
			$discussion_manager = $environment->getDiscussionManager();
			$discussion_manager->setContextLimit($environment->getCurrentContextID());
			$all_ids = $discussion_manager->getIds();
			$count_all = count($all_ids);
			if (isset($all_ids[0])){
   				$newest_id = $all_ids[0];
   				$item = $discussion_manager->getItem($newest_id);
   				$date = $item->getModificationDate();
   				$now = getCurrentDateTimeInMySQL();
   				if ($date <= $now){
      				$sel_activating_status = 1;
   				}
			}elseif($count_all == 0){
   				$sel_activating_status = 1;
			}
			$discussion_manager->resetData();
			if ( !empty($this->_list_parameter_arrray['ref_iid']) and $this->getViewMode() == 'attached' ){
   				$discussion_manager->setRefIDLimit($this->_list_parameter_arrray['ref_iid']);
			}
			if ( !empty($this->_list_parameter_arrray['ref_user']) and $this->getViewMode() == 'attached' ){
   				$discussion_manager->setRefUserLimit($this->_list_parameter_arrray['ref_user']);
			}
			if ( !empty($this->_list_parameter_arrray['sort']) ) {
   				$discussion_manager->setSortOrder($this->_list_parameter_arrray['sort']);
			}
			if ( $this->_list_parameter_arrray['sel_activating_status'] == 2 ) {
   				$discussion_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
			}
			if ( !empty($this->_list_parameter_arrray['search']) ) {
   				$discussion_manager->setSearchLimit($this->_list_parameter_arrray['search']);
			}
			if ( !empty($this->_list_parameter_arrray['selgroup']) ) {
   				$discussion_manager->setGroupLimit($this->_list_parameter_arrray['selgroup']);
			}
			if ( !empty($this->_list_parameter_arrray['seltopic']) ) {
   				$discussion_manager->setTopicLimit($this->_list_parameter_arrray['seltopic']);
			}
			if ( !empty($this->_list_parameter_arrray['selinstitution']) ) {
   				$discussion_manager->setTopicLimit($this->_list_parameter_arrray['selinstitution']);
			}
			if ( !empty($this->_list_parameter_arrray['selbuzzword']) ) {
   				$discussion_manager->setBuzzwordLimit($this->_list_parameter_arrray['selbuzzword']);
			}
			if ( !empty($this->_list_parameter_arrray['last_selected_tag']) ){
   				$discussion_manager->setTagLimit($this->_list_parameter_arrray['last_selected_tag']);
			}
			if ( !empty($seltag_array) ){
   				$discussion_manager->setTagArrayLimit($seltag_array);
			}
			if ( $this->_list_parameter_arrray['interval'] > 0 ) {
   				$discussion_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
			}

			if($this->_list_parameter_arrray['interval'] > 0) {
				$discussion_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1, $this->_list_parameter_arrray['interval']);
			}

			if ( !empty($only_show_array) ) {
   				$discussion_manager->resetLimits();
   				$discussion_manager->setIDArrayLimit($only_show_array);
			}
			$discussion_manager->select();
			$list = $discussion_manager->get();
			$ids = $discussion_manager->getIDArray();
			$count_all_shown = count($ids);

			$this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
            $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

            $session = $this->_environment->getSessionItem();
            $session->setValue('cid'.$environment->getCurrentContextID().'_discussion_index_ids', $ids);

			$id_array = array();
			$item = $list->getFirst();
			while ($item){
   				$id_array[] = $item->getItemID();
   				$item = $list->getNext();
			}
			$assessment_manager = $environment->getAssessmentManager();
			$assessment_manager->getAssessmentForItemAverageByIDArray($id_array);

			$link_manager = $environment->getLinkManager();
			$file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array);
			$file_manager = $environment->getFileManager();
			$file_manager->setIDArrayLimit($file_id_array);
			$file_manager->select();

			$discarticle_manager = $environment->getDiscussionArticleManager();
			$discarticle_list = $discarticle_manager->getAllDiscArticlesItemListByIDArray($id_array);

			$item = $discarticle_list->getFirst();
			while ($item){
			   $id_array[] = $item->getItemID();
			   $item = $discarticle_list->getNext();
			}

			$noticed_manager = $environment->getNoticedManager();
			$noticed_manager->getLatestNoticedByIDArray($id_array);
			$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array); // gibt noch keine Anmerkungen
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
				$noticed_text = $this->_getItemChangeStatus($item);

				$all_and_unread_articles = $item->getAllAndUnreadArticles();

				// files
				$attachment_infos = array();
				$file_count = $item->getFileListWithFilesFromArticles()->getCount();
				$file_list = $item->getFileListWithFilesFromArticles();

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
	            $creator = $item->getCreatorItem();
	            if(empty($creator)){
	            	$creator_id = '';
	            } else {
	            	$creator_id = $item->getCreatorItem()->getItemID();
	            }

				$item_array[] = array(
					'iid'				=> $item->getItemID(),
					'title'				=> $item->getTitle(),
					'date'				=> $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate()),
					'modificator'		=> $this->getItemModificator($item),
					'assessment_array'  => $assessment_stars_text_array,
					'noticed'			=> $noticed_text,
					'article_count'		=> $all_and_unread_articles['count'],
					'article_unread'	=> $all_and_unread_articles['unread'],
					'attachment_count'	=> $file_count,
					'attachment_infos'	=> $attachment_infos,
					'activated_text'	=> $activated_text,
					'creator_id'		=> $creator_id,
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

		protected function getAdditionalActions(&$perms) {
			/*
			 * TODO
			 * $retour = '';
      $retour .= '   <option value="download">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DOWNLOAD').'</option>'.LF;
      include_once('functions/misc_functions.php');
      $retour .= plugin_hook_output_all('getAdditionalViewActionsAsHTML',array('module' => CS_MATERIAL_TYPE),LF);
      return $retour;
			 */
		}

		protected function getAdditionalListActions() {
			$return = array();
			$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_COPY, 'display' => '___COMMON_LIST_ACTION_COPY___');
		   $return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_DOWNLOAD, 'display' => '___COMMON_LIST_ACTION_DOWNLOAD___');
			return $return;
		}

		protected function getAdditionalRestrictionText() {
		}

		protected function getAdditionalRestrictions() {
			$return = array();

			return $return;
		}
	}