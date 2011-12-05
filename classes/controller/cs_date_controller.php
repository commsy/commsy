<?php
	require_once('classes/controller/cs_list_controller.php');
	
	class cs_date_controller extends cs_list_controller {
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
			$this->assign('date','list_content', $list_content);
			$this->assign('date','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
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
			
			   // Get available buzzwords
			   $buzzword_manager = $environment->getLabelManager();
			   $buzzword_manager->resetLimits();
			   $buzzword_manager->setContextLimit($environment->getCurrentContextID());
			   $buzzword_manager->setTypeLimit('buzzword');
			   $buzzword_manager->setGetCountLinks();
			   $buzzword_manager->select();
			   $buzzword_list = $buzzword_manager->get();
			}
		}
		
		public function getAdditionalListActions() {
			return array();
		}
	}