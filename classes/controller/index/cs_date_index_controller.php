<?php
	require_once('classes/controller/cs_list_controller.php');
	
	class cs_date_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'date_list';
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_DATE_TYPE);
		}
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		
		/**
		 * INDEX
		 */
		public function actionIndex() {
			// init list params
			$this->initListParameters(CS_DATE_TYPE);

			// perform list options
			$this->performListOption(CS_DATE_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('date','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('date','list_content', $list_content);
		}
		
		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$return = array();
			
			if ( isset($_GET['ref_iid']) ) {
			   $ref_iid = $_GET['ref_iid'];
			} elseif ( isset($_POST['ref_iid']) ) {
			   $ref_iid = $_POST['ref_iid'];
			}
			
			if ( isset($_GET['ref_user']) ) {
			   $ref_user = $_GET['ref_user'];
			} elseif ( isset($_POST['ref_user']) ) {
			   $ref_user = $_POST['ref_user'];
			} else{
			   $ref_user ='';
			}
			
			$last_selected_tag = '';
			$seltag_array = array();
			
			// Find current sel_activating_status selection
			if(isset($_GET['selactivationgstatus']) && $_GET['selactivatingstatus'] != '-2') {
				$sel_activating_status = $_GET['selactivatingstatus'];
			} else {
				$sel_activating_status = 2;
			}
			
			// Find current buzzword selection
			if(isset($_GET['selbuzzword']) && $_GET['selbuzzword'] != '-2') {
				$selbuzzword = $_GET['selbuzzword'];
			} else {
				$selbuzzword = 0;
			}
			
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
			$dates_manager = $environment->getDatesManager();
			
			if ( empty($only_show_array) ) {
			   $color_array = $dates_manager->getColorArray();
			   $current_context = $environment->getCurrentContextItem();
			   /*
			   if ($current_context->isPrivateRoom()){
			      $id_array = array();
			      $id_array[] = $environment->getCurrentContextID();
			      $dates_manager->setContextArrayLimit($id_array);
			      $dates_manager->setDateModeLimit(2);
			      $dates_manager->setYearLimit($year);
			      if (!empty($presentation_mode) and $presentation_mode =='2'){
			         $real_month = mb_substr($month,4,2);
			         $first_char = mb_substr($real_month,0,1);
			         if ($first_char == '0'){
			            $real_month = mb_substr($real_month,1,2);
			         }
			         $dates_manager->setMonthLimit($real_month);
			      }else{
			         $real_month = mb_substr($month,4,2);
			         $first_char = mb_substr($real_month,0,1);
			         if ($first_char == '0'){
			            $real_month = mb_substr($real_month,1,2);
			         }
			         $dates_manager->setMonthLimit2($real_month);
			      }
			      $count_all = $dates_manager->getCountAll();
			      $dates_manager->resetLimits();
			      $dates_manager->setSortOrder('time');
			   }elseif (($seldisplay_mode == 'calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
			      $dates_manager->setContextLimit($environment->getCurrentContextID());
			      $dates_manager->setDateModeLimit(2);
			      $dates_manager->setYearLimit($year);
			      if (!empty($presentation_mode) and $presentation_mode =='2'){
			         $real_month = mb_substr($month,4,2);
			         $first_char = mb_substr($real_month,0,1);
			         if ($first_char == '0'){
			            $real_month = mb_substr($real_month,1,2);
			         }
			         $dates_manager->setMonthLimit($real_month);
			      }else{
			         $real_month = mb_substr($month,4,2);
			         $first_char = mb_substr($real_month,0,1);
			         if ($first_char == '0'){
			            $real_month = mb_substr($real_month,1,2);
			         }
			         $dates_manager->setMonthLimit2($real_month);
			      }
			      $count_all = $dates_manager->getCountAll();
			      $dates_manager->resetLimits();
			      $dates_manager->setSortOrder('time');
			   } else {
			   	*/
			      $dates_manager->setContextLimit($environment->getCurrentContextID());
			      $dates_manager->setDateModeLimit(2);
			      $count_all = $dates_manager->getCountAll();
			   /*}*/
			   if ( $sel_activating_status == 2 ) {
			      $dates_manager->showNoNotActivatedEntries();
			   }
			
			   if ( !empty($sel_color) and $sel_color != 2 ) {
			      $dates_manager->setColorLimit('#'.$sel_color);
			   }
			
			   if ( !empty($ref_iid) and $mode == 'attached' ){
			      $dates_manager->setRefIDLimit($ref_iid);
			   }
			   if ( !empty($ref_user) and $mode == 'attached' ){
			      $dates_manager->setRefUserLimit($ref_user);
			   }
			   if ( !empty($sort) and ($seldisplay_mode!='calendar' or $seldisplay_mode == 'calendar_month' or $mode == 'formattach' or $mode == 'detailattach') ) {
			      $dates_manager->setSortOrder($sort);
			   }
			   if ( !empty($search) ) {
			      $dates_manager->setSearchLimit($search);
			   }
			   if ( !empty($selstatus) ) {
			      $dates_manager->setDateModeLimit($selstatus);
			   }
			   if ( !empty($selbuzzword) ) {
			      $dates_manager->setBuzzwordLimit($selbuzzword);
			   }
			   if ( !empty($last_selected_tag) ){
			      $dates_manager->setTagLimit($last_selected_tag);
			   }
			   $dates_manager->select();
			   $list = $dates_manager->get();
			   $ids = $dates_manager->getIDArray();
			   $count_all_shown = count($ids);
			   
			   $this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
			   $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);
			
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
			   	$item_array[] = array(
				'iid'				=> $item->getItemID(),
				'title'				=> $view->_text_as_html_short($item->getTitle()),
				'date'				=> $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate()),
				'creator'			=> $item->getCreatorItem()->getFullName(),
				'assessment_array'  => $assessment_stars_text_array,
				'noticed'			=> $noticed_text,
				'attachment_count'	=> $item->getFileList()->getCount()
//				'attachment_infos'	=>
				);
				
			   	$item = $list->getNext();
			   }
			   
			   
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
			 * $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $hash_manager = $this->_environment->getHashManager();
      $params = $this->_environment->getCurrentParameterArray();
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/abbo.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_ABBO').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/abbo.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_ABBO').'"/>';
      }
      $ical_url = '<a title="'.$this->_translator->getMessage('DATES_ABBO').'"  href="webcal://';
      $ical_url .= $_SERVER['HTTP_HOST'];
      global $c_single_entry_point;
      $ical_url .= str_replace($c_single_entry_point,'ical.php',$_SERVER['PHP_SELF']);
      $ical_url .= '?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      $html .= $ical_url;
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/export.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_EXPORT').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/export.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_EXPORT').'"/>';
      }
      $html .= '<a title="'.$this->_translator->getMessage('DATES_EXPORT').'"  href="ical.php?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      unset($params);
      if ( $this->_environment->inPrivateRoom() ) {
         if ( $this->_with_modifying_actions ) {
            $params['import'] = 'yes';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/import.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_IMS_IMPORT').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/import.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_IMS_IMPORT').'"/>';
            }
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                               'import',
                               $params,
                               $image,
                               $this->_translator->getMessage('COMMON_IMPORT_DATES')).LF;
            unset($params);
         } else {
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/import_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_IMPORT_DATES').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/import_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_IMPORT_DATES').'"/>';
           }
           $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_IMPORT_DATES')).' "class="disabled">'.$image.'</a>'.LF;
         }
      }
      return $html;
			 */
		}

		protected function getAdditionalListActions() {
			return array();
		}
	}