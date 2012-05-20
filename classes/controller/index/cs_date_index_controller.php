<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_date_index_controller extends cs_list_controller {
		private $_display_mode = '';
		private $_selected_status = '';
		private $_available_color_array = array('#999999','#CC0000','#FF6600','#FFCC00','#FFFF66','#33CC00','#00CCCC','#3366FF','#6633FF','#CC33CC');

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'date_list';

			// set display mode
			$this->setDisplayMode();

			// set selected status
			$this->setSelectedStatus();

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
			$converter = $environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();
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

			// Find current buzzword selection
			if(isset($_GET['selbuzzword']) && $_GET['selbuzzword'] != '-2') {
				$selbuzzword = $_GET['selbuzzword'];
			} else {
				$selbuzzword = 0;
			}
			if ( isset($_GET['sort']) ) {
   				$sort = $_GET['sort'];
			} else {
   				$sort = 'time_rev';
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





				if($this->_list_parameter_arrray['sel_activating_status'] == 2) {
					$dates_manager->showNoNotActivatedEntries();
				}

				if(	!empty($this->_list_parameter_arrray['sort']) &&
					($this->_display_mode !== 'calendar' || $this->_display_mode === 'calendar_month' || $this->getViewMode() === 'formattach' || $this->getViewMode() === 'detailattach')) {
					$dates_manager->setSortOrder($this->_list_parameter_arrray['sort']);
				}

				// find current selected color
				$selected_color = '';
				if(isset($_GET['selcolor']) && $_GET['selcolor'] != '-2') {
					$selected_color = $_GET['selcolor'];
				}

				if(!empty($selected_color) && $selected_color != 2) {
					$dates_manager->setColorLimit('#' . $selected_color);
				}



				/* TODO: convert

			   if ( !empty($ref_iid) and $mode == 'attached' ){
			      $dates_manager->setRefIDLimit($ref_iid);
			   }
			   if ( !empty($ref_user) and $mode == 'attached' ){
			      $dates_manager->setRefUserLimit($ref_user);
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
   			   if ( !empty($sort) ) {
      			 $dates_manager->setSortOrder($sort);
   			   }
   			   */
			$dates_manager->resetData();
			if ( !empty($this->_list_parameter_arrray['ref_iid']) and $this->getViewMode() == 'attached' ){
   				$dates_manager->setRefIDLimit($this->_list_parameter_arrray['ref_iid']);
			}
			if ( !empty($this->_list_parameter_arrray['ref_user']) and $this->getViewMode() == 'attached' ){
   				$dates_manager->setRefUserLimit($this->_list_parameter_arrray['ref_user']);
			}
			if ( !empty($this->_list_parameter_arrray['sort']) ) {
   				$dates_manager->setSortOrder($this->_list_parameter_arrray['sort']);
			}
			if ( $this->_list_parameter_arrray['sel_activating_status'] == 2 ) {
   				$dates_manager->showNoNotActivatedEntries();
			}
			if ( !empty($this->_list_parameter_arrray['search']) ) {
   				$dates_manager->setSearchLimit($this->_list_parameter_arrray['search']);
			}
			if ( !empty($this->_list_parameter_arrray['selgroup']) ) {
   				$dates_manager->setGroupLimit($this->_list_parameter_arrray['selgroup']);
			}
			if ( !empty($this->_list_parameter_arrray['seltopic']) ) {
   				$dates_manager->setTopicLimit($this->_list_parameter_arrray['seltopic']);
			}
			if ( !empty($this->_list_parameter_arrray['selinstitution']) ) {
   				$dates_manager->setTopicLimit($this->_list_parameter_arrray['selinstitution']);
			}
			if ( !empty($this->_list_parameter_arrray['selbuzzword']) ) {
   				$dates_manager->setBuzzwordLimit($this->_list_parameter_arrray['selbuzzword']);
			}
			if ( !empty($this->_list_parameter_arrray['last_selected_tag']) ){
   				$dates_manager->setTagLimit($this->_list_parameter_arrray['last_selected_tag']);
			}
			if ( $this->_list_parameter_arrray['interval'] > 0 ) {
   				$dates_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
			}
			if ( !empty($only_show_array) ) {
   				$dates_manager->resetLimits();
   				$dates_manager->setIDArrayLimit($only_show_array);
			}

			   if ( $this->_list_parameter_arrray['interval'] > 0 ) {
					$dates_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
				}

				/* end TODO */

			   $dates_manager->select();
			   $list = $dates_manager->get();
			   $ids = $dates_manager->getIDArray();
			   $count_all_shown = count($ids);

			   $this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
			   $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

			   $session = $this->_environment->getSessionItem();
				$session->setValue('cid'.$environment->getCurrentContextID().'_date_index_ids', $ids);

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

				$place = $item->getPlace();
				$place = $converter->text_as_html_short($place);

				$parse_time_start = convertTimeFromInput($item->getStartingTime());
				$conforms = $parse_time_start['conforms'];
				if($conforms === true) {
					$time = getTimeLanguage($parse_time_start['datetime']);
				} else {
					$time = $item->getStartingTime();
				}
				$time = $converter->text_as_html_short($time);

				$parse_day_start = convertDateFromInput($item->getStartingDay(), $this->_environment->getSelectedLanguage());
				$conforms = $parse_day_start['conforms'];
				if($conforms === true) {
					$date = $translator->getDateInLang($parse_day_start['datetime']);
				} else {
					$date = $item->getStartingDay();
				}
				$date = $converter->text_as_html_short($date);




				/**
				 *if ($item->isNotActivated()){
					$title = $item->getTitle();
					$title = $this->_compareWithSearchText($title);
					$user = $this->_environment->getCurrentUser();
					if($item->getCreatorID() == $user->getItemID() or $user->isModerator()){
					$params = array();
					$params['iid'] = $item->getItemID();
					$title = ahref_curl( $this->_environment->getCurrentContextID(),
										CS_DATE_TYPE,
										'detail',
										$params,
										$title,
										'','', '', '', '', '', '', '',
										CS_DATE_TYPE.$item->getItemID());
					unset($params);
					}
					$activating_date = $item->getActivatingDate();
					if (strstr($activating_date,'9999-00-00')){
					$title .= BR.$this->_translator->getMessage('COMMON_NOT_ACTIVATED');
					}else{
					$title .= BR.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
					}
					$title = '<span class="disabled">'.$title.'</span>';
					$html .= '      <td '.$style.'>'.$title.LF;
				}else{
					if($with_links) {
					$html .= '      <td '.$style.'>'.$this->_getItemTitle($item).$fileicons.LF;
					} else {
					$title = $this->_text_as_html_short($item->getTitle());
					$html .= '      <td '.$style.'>'.$title.LF;
					}
				}
				 */
			   	$item_array[] = array(
					'iid'				=> $item->getItemID(),
					'title'				=> $view->_text_as_html_short($item->getTitle()),
					'date'				=> $date,
					'time'				=> $time,
					'color'				=> $item->getColor(),
					'show_time'			=> $item->getStartingTime() !== '',
					'place'				=> $place,
					'assessment_array'  => $assessment_stars_text_array,
					'noticed'			=> $noticed_text,
					'attachment_count'	=> $file_count,
					'attachment_infos'	=> $attachment_infos,
					'activated'			=> !$item->isNotActivated()
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

				// save selection
				if($current_context->isPrivateRoom()) {
					$date_sel_status = $current_context->getRubrikSelection(CS_DATE_TYPE, 'status');

					if($date_sel_status != $this->_selected_status) {
						$current_context->setRubrikSelection(CS_DATE_TYPE, 'status', $this->_selected_status);
					}
				}
			} else {
				if(	$this->_display_mode == 'calendar' ||
					// TODO?:
					$this->_display_mode == 'calendar_month' /* || $mode == 'formattach' || $mode == 'detailattach' */ ||
					$this->_environment->inPrivateRoom()) {

					$this->_selected_status = 2;

					if($this->_environment->inPrivateRoom()) {
						$date_sel_status = $current_context->getRubrikSelection(CS_DATE_TYPE, 'status');

						if(!empty($date_sel_status)) {
							$this->_selected_status = $date_sel_status;
						} else {
							$this->_selected_status = 2;
						}
					}
				} else {
					$this->_selected_status = 3;
				}
			}
		}

		private function setDisplayMode() {
			$current_user = $this->_environment->getCurrentUserItem();
			$current_context = $this->_environment->getCurrentContextItem();
			$seldisplay_mod = $current_context->getDatesPresentationStatus();
			$session = $this->_environment->getSessionItem();

			if(isset($_GET['seldisplay_mode'])) {
				$this->_display_mode = $_GET['seldisplay_mode'];
				$session->setValue($this->_environment->getCurrentContextID() . '_dates_seldisplay_mode', $_GET['seldisplay_mode']);
			} elseif(!empty($_POST['seldisplay_mode'])) {
				$this->_display_mode = $_POST['seldisplay_mode'];
				$session->setValue($this->_environment->getCurrentContextID() . '_dates_seldisplay_mode', $_POST['seldisplay_mode']);
			} elseif($session->issetValue($this->_environment->getCurrentContextID() . '_dates_seldisplay_mode')) {
				$this->_display_mode = $session->getValue($this->_environment->getCurrentContextID() . '_dates_seldisplay_mode');
			} else {
				$this->_display_mode = $current_context->getDatesPresentationStatus();
			}
		}

		protected function getAdditionalRestrictionText(){
			$return = array();

			$params = $this->_environment->getCurrentParameterArray();
			$current_context = $this->_environment->getCurrentContextItem();

			if(!isset($params['selstatus']) || $params['selstatus'] === '4' || $params['selstatus'] === '3') {
				$restriction = array(
					'name'				=> '',
					'type'				=> '',
					'link_parameter'	=> ''
				);

				$translator = $this->_environment->getTranslationObject();

				// set name
				if(isset($params['selstatus']) && $params['selstatus'] === '4') {
					$restriction['name'] = $translator->getMessage('DATES_NON_PUBLIC');
				} elseif(!isset($params['selstatus']) || $params['selstatus'] === '3') {
					$restriction['name'] = $translator->getMessage('DATES_PUBLIC');
				}

				// set link parameter
				$params['selstatus'] = 2;
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

			// set tag and name
			$tag = $translator->getMessage('COMMON_DATE_STATUS');
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
				'id'		=> 2,
				'name'		=> $translator->getMessage('COMMON_NO_SELECTION'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;

			// disabled
			$item = array(
				'id'		=> -2,
				'name'		=> '------------------------------',
				'selected'	=> $this->_selected_status,
				'disabled'	=> true
			);
			$items[] = $item;

			// public
			$item = array(
				'id'		=> 3,
				'name'		=> $translator->getMessage('DATES_PUBLIC'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;

			// non public
			// public
			$item = array(
				'id'		=> 4,
				'name'		=> $translator->getMessage('DATES_NON_PUBLIC'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;


			$restriction['items'] = $items;
			$return[] = $restriction;

			// colors
			$color_array = $dates_manager->getColorArray();
			if(isset($color_array[0])) {
				// find current selected color
				$selected_color = '';
				if(isset($_GET['selcolor']) && $_GET['selcolor'] != '-2') {
					$selected_color = $_GET['selcolor'];
				}

				$restriction = array(
					'item'		=> array(),
					'action'	=> '',
					'hidden'	=> array(),
					'tag'		=> '',
					'name'		=> '',
					'custom'	=> true
				);

				// set tag and name
				$tag = $translator->getMessage('COMMON_DATE_COLOR');
				$restriction['tag'] = $tag;
				$restriction['name'] = 'color';

				// set action
				$params = $this->_environment->getCurrentParameterArray();

				if(!isset($params['selcolor'])) {
					unset($params['from']);
				}

				unset($params['selcolor']);
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
					'id'		=> 2,
					'name'		=> $translator->getMessage('COMMON_NO_SELECTION'),
					'selected'	=> $this->_selected_status
				);
				$items[] = $item;

				// disabled
				$item = array(
					'id'		=> -2,
					'name'		=> '------------------------------',
					'selected'	=> $this->_selected_status,
					'disabled'	=> true
				);
				$items[] = $item;

				$color_array = $this->_available_color_array;
				foreach($color_array as $color) {
					$color_text = '';
					switch ($color){
						case '#999999': $color_text = getMessage('DATE_COLOR_GREY');break;
						case '#CC0000': $color_text = getMessage('DATE_COLOR_RED');break;
						case '#FF6600': $color_text = getMessage('DATE_COLOR_ORANGE');break;
						case '#FFCC00': $color_text = getMessage('DATE_COLOR_DEFAULT_YELLOW');break;
						case '#FFFF66': $color_text = getMessage('DATE_COLOR_LIGHT_YELLOW');break;
						case '#33CC00': $color_text = getMessage('DATE_COLOR_GREEN');break;
						case '#00CCCC': $color_text = getMessage('DATE_COLOR_TURQUOISE');break;
						case '#3366FF': $color_text = getMessage('DATE_COLOR_BLUE');break;
						case '#6633FF': $color_text = getMessage('DATE_COLOR_DARK_BLUE');break;
						case '#CC33CC': $color_text = getMessage('DATE_COLOR_PURPLE');break;
						default: $color_text = getMessage('DATE_COLOR_UNKNOWN');
					}

					$item = array(
						'id'		=> str_replace('#', '', $color),
						'name'		=> $color_text,
						'selected'	=> str_replace('#', '', $selected_color)
					);
					$items[] = $item;
				}
			}

			$restriction['items'] = $items;

			$return[] = $restriction;

			return $return;
		}
	}