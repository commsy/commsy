<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_home_controller extends cs_list_controller {
		
		protected $_with_old_text_formating = false;
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'room_home';

			$this->_with_old_text_formating = false;
		}

		protected function getAdditionalRestrictions(){}

		protected function getAdditionalRestrictionText(){}


		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
		}



		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/

		public function actionIndex() {
			$this->assign('room', 'home_content', $this->getListContent());
			$this->assign('room', 'informationbox' , $this->getInformationBoxContent());
		}

		public function getInformationBoxContent(){
      		$current_context = $this->_environment->getCurrentContextItem();

			$return_array = array();
			$return_array['show'] = $current_context->withInformationBox();
			$return_array['title'] = '';
			$return_array['iid'] = '';
			$return_array['content'] = '';

			if ($return_array['show']) {
				$id = $current_context->getInformationBoxEntryID();
			    $manager = $this->_environment->getItemManager();
			    $item = $manager->getItem($id);
			    $entry_manager = $this->_environment->getManager($item->getItemType());
			    $entry = $entry_manager->getItem($id);
				$return_array['title'] = $entry->getTitle();
				$converter = $this->_environment->getTextConverter();
				
			   
				$desc = '';
            if ( method_exists($entry,'getDescription') ) {
               $desc = $entry->getDescription();
            }
            if(empty($desc)){
            	if($item->getItemType() == 'discussion'){
            		$discussion_article_manager = $this->_environment->getDiscussionArticleManager();
            		$all_disc_entrys = $discussion_article_manager->getAllArticlesForItem($entry);
            		$item = $all_disc_entrys->getFirst();
					$desc = $item->getDescription();
            		unset($all_disc_entrys);
            	}
            }
            
				
				if(!empty($desc)) {
					$converter->setFileArray($this->getItemFileList());
               if ( $this->_with_old_text_formating ) {
                  //$desc = $converter->text_as_html_long($desc);
                  $desc = $converter->textFullHTMLFormatting($desc);
               } else {
                  #$desc = $converter->_text_as_html_long2($desc);
                  #$desc = $converter->cleanDataFromTextArea($desc);
                  $desc = $converter->textFullHTMLFormatting($desc);
               }
				}
				if($entry->getItemType() == 'date'){
					//$return_array['date'] = $entry->getStartingDay();
					$return_array['date'] = getDateInLang($entry->getDateTime_start());
					$time = getTimeInLang($entry->getDateTime_start());
					if($time != '00:00'){
						$return_array['time'] = $time;
					}
					
				}
				
				$return_array['content'] = $desc;
				$return_array['rubric'] = $entry->getItemType();
				$return_array['iid'] = $id;
			}
			return $return_array;


		}


		protected function getItemFileList() {
			if($this->_item_file_list === null) {
	          if ( isset($this->_item) ) {
	            if ( $this->_item->isA(CS_MATERIAL_TYPE) ) {
	               $file_list = $this->_item->getFileListWithFilesFromSections();
	            } elseif ( $this->_item->isA(CS_DISCUSSION_TYPE) ) {
	               $file_list = $this->_item->getFileListWithFilesFromArticles();
	            } elseif ( $this->_item->isA(CS_TODO_TYPE) ) {
	               $file_list = $this->_item->getFileListWithFilesFromSteps();
	            } else {
	               $file_list = $this->_item->getFileList();
	            }
	          } else {
	            if ($this->_environment->getCurrentModule() == 'home') {
	               $current_context_item = $this->_environment->getCurrentContextItem();
	               if ($current_context_item->withInformationBox()){
	                  $id = $current_context_item->getInformationBoxEntryID();
	                  $manager = $this->_environment->getItemManager();
	                  $item = $manager->getItem($id);
	                  $entry_manager = $this->_environment->getManager($item->getItemType());
	                  $entry = $entry_manager->getItem($id);
	                  $file_list = $entry->getFileList();
	               }
	            } else {
	               $file_list = $this->_environment->getCurrentContextItem()->getFileList();
	            }
	         }
	         if ( isset($this->_item) and $this->_item->isA(CS_SECTION_TYPE) ) {
	            $material_item = $this->_item->getLinkedItem();
	            $file_list2 = $material_item->getFileList();
	            if ( isset($file_list2) and !empty($file_list2) and $file_list2->getCount() > 0 ) {
	               $file_list->addList($file_list2);
	            }
	            unset($file_list2);
	            unset($material_item);
	         }
	         if ( !empty($file_list) ) {
	            $file_array = $file_list->to_Array();
	            unset($file_list);
	            $file_name_array = array();
	            foreach ($file_array as $file) {
	               $file_name_array[htmlentities($file->getDisplayName(), ENT_NOQUOTES, 'UTF-8')] = $file;
	            }
	            unset($file_array);
	            $this->_item_file_list = $file_name_array;
	            unset($file_name_array);
	         }
	      }
	      return $this->_item_file_list;
		}

		  /** get the activity of the item
		    * this method returns the item activity in the right formatted style
		    *
		    * @return string title
		    */
		   function _getItemActivity ($item,$room_max_activity) {
		      if ( $room_max_activity != 0 ) {
		         $percentage = $item->getActivityPoints();
		         if ( empty($percentage) ) {
		            $percentage = 0;
		         } else {
		           $teiler = $room_max_activity/20;
		            $percentage = log(($percentage/$teiler)+1);
		          if ($percentage < 0) {
		            $percentage = 0;
		          }
		          $max_activity = log(($room_max_activity/$teiler)+1);
		            $percentage = round(($percentage / $max_activity) * 100,2);
		         }
		      } else {
		         $percentage = 0;
		      }
		      $display_percentage = $percentage;
		      $html = '         <div class="gauge" style="height:5px;">'.LF;
		      $html .= '            <div class="gauge-bar" style="height:5px; width:'.$display_percentage.'%;">&nbsp;</div>'.LF;
		      $html .= '         </div>'.LF;
		      return $html;
		   }



		public function getListContent() {
			$session = $this->_environment->getSessionItem();
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$translator = $environment->getTranslationObject();
			$context_item = $environment->getCurrentContextItem();
			$current_user = $environment->getCurrentUser();
			$converter = $environment->getTextConverter();

			$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

			$id_array = array();
			$v_id_array = array();
			$sub_id_array = array();
			$disc_id_array = array();

			$rubrics = $this->getRubrics();
			$rubric_list = array();
			$rubric_list_array = array();

			// determe rubrics to show on home list
			foreach($rubrics as $rubric) {
				list($rubric_name, $postfix) = explode('_', $rubric);

				// continue if postfix is none or nodisplay
				if($postfix === 'none' || $postfix === 'nodisplay') continue;

				// TODO: where does activity come from?
				// continue if name of rubric is activity
				if($rubric_name === 'activity') continue;
				
				// store hidden state
				$return[$rubric_name]['hidden'] = ($postfix === 'short') ? false : true;
				
				// check rights
				if ($current_user->isUser() && $this->_with_modifying_actions && $rubric_name != CS_USER_TYPE) {
					$return[$rubric_name]['rights']['new'] = true;
				} else {
					$return[$rubric_name]['rights']['new'] = false;
				}

				$rubric_list[] = $rubric_name;

				$list = new cs_list();
				$rubric = '';
	               switch($rubric_name) {
	                  case CS_ANNOUNCEMENT_TYPE:
	                        $manager = $environment->getAnnouncementManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $count_all = $manager->getCountAll();
	                        $manager->setDateLimit(getCurrentDateTimeInMySQL());
	                        $manager->setSortOrder('modified');
	                        $manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        if($home_rubric_limit < $count_select){
	                        	$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
	                        }

	                        $manager->select();
	                        $list = $manager->get();

	                     break;
	                  case CS_DATE_TYPE:
	                        $manager = $environment->getDatesManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $manager->setDateModeLimit(2);
	                        $count_all = $manager->getCountAll();
	                        $manager->setFutureLimit();
	                        $manager->setDateModeLimit(3);
	                        $manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        if($home_rubric_limit < $count_select){
	                        	$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
	                        }

	                        $manager->select();
	                        $list = $manager->get();
	                        $rubric = 'dates';
	                     break;
	                  case CS_PROJECT_TYPE:
	                        $room_type = CS_PROJECT_TYPE;
	                        $manager = $environment->getProjectManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentPortalID());
	                        if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr  ) {
	                           $manager->setCommunityRoomLimit($environment->getCurrentContextID());
	                        } else {
	                           # use redundant infos in community room
	                           $manager->setIDArrayLimit($context_item->getInternalProjectIDArray());
	                        }
	                        $count_all = $manager->getCountAll();
	                        $manager->setSortOrder('activity_rev');
	                        if ( $count_all > 10 ) {
	                           $manager->setIntervalLimit(0,10);
	                        }
	                        $manager->select();
	                        $list = $manager->get();
	                     break;
	                  case CS_GROUP_TYPE:
	                        $manager = $environment->getGroupManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $manager->select();
	                        $list = $manager->get();
	                        $count_all = $list->getCount();
	                     break;
	                  case CS_TODO_TYPE:
	                        $manager = $environment->getTodoManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $count_all = $manager->getCountAll();
	                        $manager->setStatusLimit(4);
	                        $manager->setSortOrder('date');
	                        $manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        if($home_rubric_limit < $count_select){
	                        	$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
	                        }

	                        $manager->select();
	                        $list = $manager->get();
	                        $item = $list->getFirst();
	                        $tmp_id_array = array();
	                        while ($item){
	                           $tmp_id_array[] = $item->getItemID();
	                           $item = $list->getNext();
	                        }
	                        $step_manager = $environment->getStepManager();
	                        $step_list = $step_manager->getAllStepItemListByIDArray($tmp_id_array);
	                        $item = $step_list->getFirst();
	                        while ($item){
	                           $sub_id_array[] = $item->getItemID();
	                           $item = $step_list->getNext();
	                        }
	                        unset($step_list);
	                        unset($step_manager);
	                        unset($manager);
	                        break;
	                  case CS_TOPIC_TYPE:
	                        $manager = $environment->getTopicManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        
	                        $manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
	                        
	                        $manager->select();
	                        $list = $manager->get();
	                        $count_all = $list->getCount();
	                     break;
	                  case CS_USER_TYPE:
	                        $manager = $environment->getUserManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $manager->setUserLimit();
	                        $count_all = $manager->getCountAll();
	                        if (!$current_user->isGuest()){
	                           $manager->setVisibleToAllAndCommsy();
	                        } else {
	                           $manager->setVisibleToAll();
	                        }
	                        $manager->setAgeLimit($context_item->getTimeSpread());
	                        $manager->select();
	                        $list = $manager->get();
	                     break;
	                  case CS_MATERIAL_TYPE:
#	                        $short_list_view = $class_factory->getClass(MATERIAL_SHORT_VIEW,$param_class_array);
	                        $manager = $environment->getMaterialManager();
	                        $manager->reset();
	                        $manager->create_tmp_table($environment->getCurrentContextID());
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $count_all = $manager->getCountAll();
	                        $manager->setOrder('date');
	                        if ($environment->inProjectRoom()){
	                           $manager->setAgeLimit($context_item->getTimeSpread());
	                        } else {
	                           $manager->setIntervalLimit(0,5);
	                           $home_rubric_limit = 5;
	                        }
	                        $manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

	                        if($home_rubric_limit < $count_select){
	                        	$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
	                        }

	                        $manager->select();
	                        $list = $manager->get();
	                        $manager->delete_tmp_table();
	                        $item = $list->getFirst();
	                        $tmp_id_array = array();
	                        while ($item){
	                           $tmp_id_array[] = $item->getItemID();
	                           $item = $list->getNext();
	                        }
	                        $section_manager = $environment->getSectionManager();
	                        $section_list = $section_manager->getAllSectionItemListByIDArray($tmp_id_array);
	                        $item = $section_list->getFirst();
	                        while ($item){
	                           $sub_id_array[] = $item->getItemID();
	                           $v_id_array[$item->getItemID()] = $item->getVersionID();
	                           $item = $section_list->getNext();
	                        }
	                     break;
	                  case CS_DISCUSSION_TYPE:
	                        $manager = $environment->getDiscussionManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $count_all = $manager->getCountAll();
	                        if ($environment->inProjectRoom() or $environment->inGroupRoom() ) {
	                           $manager->setAgeLimit($context_item->getTimeSpread());
	                        } elseif ($environment->inCommunityRoom()) {
	                           $manager->setIntervalLimit(0,5);
	                           $home_rubric_limit = 5;
	                        }
	                        $manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

	                        if($home_rubric_limit < $count_select){
	                        	$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
	                        }

	                        $manager->select();
	                        $list = $manager->get();
	                        $item = $list->getFirst();
	                        $disc_id_array = array();
	                        while ($item){
	                           $disc_id_array[] = $item->getItemID();
	                           $item = $list->getNext();
	                        }
	                        $discarticle_manager = $environment->getDiscussionArticleManager();
	                        $discarticle_list = $discarticle_manager->getAllDiscArticlesItemListByIDArray($disc_id_array);
	                        $item = $discarticle_list->getFirst();
	                        while ($item){
	                           $disc_id_array[] = $item->getItemID();
	                           $item = $discarticle_list->getNext();
	                        }
	                     break;
	        	   }
				  $rubric_list_array[$rubric_name] = $list;
				  $rubric_count_all_array[$rubric_name] = $count_all;
                  $tmp = $list->getFirst();
                  $ids = array();
                  while ($tmp){
	                  $id_array[] = $tmp->getItemID();
	                  if ($rubric_name == CS_MATERIAL_TYPE){
	                     $v_id_array[$tmp->getItemID()] = $tmp->getVersionID();
	                  }
	                  $ids[] = $tmp->getItemID();
	                  $tmp = $list->getNext();
	               }
	               if (empty($rubric)){
	                  $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric_name.'_index_ids', $ids);
	               }else{
	                  $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $ids);
	               }



				}



	      		  $noticed_manager = $environment->getNoticedManager();
			      $id_array = array_merge($id_array, $disc_id_array);
			      $id_array = array_merge($id_array, $sub_id_array);
			      $noticed_manager->getLatestNoticedByIDArray($id_array);
			      $noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
			      $link_manager = $environment->getLinkManager();
			      $file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array, $v_id_array);
			      $file_manager = $environment->getFileManager();
			      $file_manager->setIDArrayLimit($file_id_array);
			      $file_manager->select();
			      $manager = $environment->getProjectManager();
			      $room_max_activity = 0;
			      if ($this->_environment->inCommunityRoom()) {
			         $manager->setContextLimit($environment->getCurrentPortalID());

			         global $c_cache_cr_pr;
			         if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
			         	$room_max_activity = $manager->getMaxActivityPointsInCommunityRoom($environment->getCurrentContextID());
			         } else {
			         	$current_context_item = $environment->getCurrentContextItem();
			         	$room_max_activity = $manager->getMaxActivityPointsInCommunityRoomInternal($current_context_item->getInternalProjectIDArray());
			         	unset($current_context_item);
			         }
			      }

			     $user_manager = $this->_environment->getUserManager();
				 foreach($rubric_list_array as $key=>$list){
					$item_array = array();
				 	$column1_addon = '';
				 	$modificator_id = '';
	               	$item = $list->getFirst();
	               	$recurringDateArray = array();
	               	$params = array();
					$params['environment'] = $environment;
					$params['with_modifying_actions'] = false;
					$view = new cs_view($params);
	           		 while($item) {
	           		 	$may_enter = false;
						$noticed_text = $this->_getItemChangeStatus($item);
#						$noticed_text = '';
	               		switch($key) {
	                  		case CS_ANNOUNCEMENT_TYPE:
								$column1 = $item->getTitle();
								$parse_day_start = convertDateFromInput($item->getSeconddateTime(), $this->_environment->getSelectedLanguage());
								$conforms = $parse_day_start['conforms'];
								if($conforms === true) {
									$column2 = $translator->getDateInLang($parse_day_start['datetime']);
								} else {
									$column2 = $item->getSeconddateTime();
								}
								$column3 = $item->getModificatorItem()->getFullName();
								$modificator_id = $item->getModificatorItem()->getItemID();
								break;
	                  		case CS_DATE_TYPE:
	                  			$displayDate = true;
	                  			$column1_addon = false;
	                  			
								// is this a recurring date?
								if ( $item->getRecurrencePattern() )
								{
									// did we already displayed the first date?
									if ( !isset($recurringDateArray[$item->getRecurrenceId()]) )
									{
										// if not - this is the starting date
										$recurringDateArray[$item->getRecurrenceId()] = $item;
									}
									else
									{
										$displayDate = false;
									}
								}
								
								if ( $displayDate )
								{
									$column1 = $item->getTitle();
									
									if ( $item->getRecurrencePattern() )
									{
										$column1_addon = true;
									}
									
									$parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
									$conforms = $parse_day_start['conforms'];
									if ($conforms == TRUE) {
										$date = $translator->getDateInLang($parse_day_start['datetime']);
									} else {
										$date = $item->getStartingDay();
									}
									$parse_time_start = convertTimeFromInput($item->getStartingTime());
									$conforms = $parse_time_start['conforms'];
									if ($conforms == TRUE) {
										$time = getTimeLanguage($parse_time_start['datetime']);
									} else {
										$time = $item->getStartingTime();
									}
									if (!empty($time)){
										$time = ', '.$time;
									}
									$column2 = $view->_text_as_html_short($date.$time);
									$column3 = $item->getPlace();
									if ($item->getColor() != '') {
									    $color = $item->getColor();
									} else {
    									$color = false;
									}
								}
								else
								{
									// go to next item
									$item = $list->getNext();
									
									/*
									 * the "2" is needed, to continue the while loop an not only
									 * the nested switch statement
									 */
									continue 2;					
								}
								
								break;
	                  		case CS_DISCUSSION_TYPE:
								$column1 = $item->getTitle();
								$column2 = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
								$column3 = $item->getModificatorItem()->getFullName();
								$modificator_id = $item->getModificatorItem()->getItemID();
								$reader_array = $item->getAllAndUnreadArticles();
								$column1_addon = $reader_array['unread'].' / '.$reader_array['count'];
								break;
	                  		case CS_USER_TYPE:
	                  			$column1 = '';
	                  			$title = $item->getTitle();
	                  			if (!empty($title)){
	                  				$column1 = $item->getTitle().' ';
	                  			}
								$column1 .= $view->_text_as_html_short($item->getFullname());
      							##################################################
      							# messenger - MUSS NOCH AUFGERÄUMT WERDEN: HTML INS TEMPLATE
      							##################################################
							    global $c_commsy_domain;
         						$host = $c_commsy_domain;
      							global $c_commsy_url_path;
      							$url_to_img = $host.$c_commsy_url_path.'/images/messenger';
      							$icq_number = $item->getICQ();
      							if ( !empty($icq_number) ){
         							//$column1 .= '   <img style="vertical-align:middle;" src="http://status.icq.com/online.gif?icq='.rawurlencode($icq_number).'&amp;img=5" alt="ICQ Online Status" />'.LF;
      							}
      							$msn_number = $item->getMSN();
      							if ( !empty($msn_number) ){
         							//$column1 .= '<a href="http://www.IMStatusCheck.com/?msn">'.LF;
         							//$column1 .= '   <img style="vertical-align:middle;" src="http://www.IMStatusCheck.com/status/msn/'.rawurlencode($msn_number).'?icons" alt="MSN Online Status" />'.LF;
         							//$column1 .= '</a>'.LF;
      							}
      							$skype_number = $item->getSkype();
      							if ( !empty($skype_number) ){
         							//$column1 .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
         							//$column1 .= '<a href="skype:'.rawurlencode($skype_number).'?chat">'.LF;
         							//$column1 .= '   <img src="http://mystatus.skype.com/smallicon/'.rawurlencode($skype_number).'" style="vertical-align:middle; border: none;" width="16" height="16" alt="Skype Online Status" />'.LF;
         							//$column1 .= '</a>'.LF;
      							}
      							$yahoo_number = $item->getYahoo();
      							if ( !empty($yahoo_number) ){
         							//$column1 .= '<a href="http://messenger.yahoo.com/edit/send/?.target='.rawurlencode($yahoo_number).'">'.LF;
         							//$column1 .= '   <img style="vertical-align:middle;" src="http://opi.yahoo.com/yahooonline/u='.rawurlencode($yahoo_number).'/m=g/t=0/l='.$this->_environment->getSelectedLanguage().'/opi.jpg" alt="Yahoo Online Status Indicator" />'.LF;
         							//$column1 .= '</a>'.LF;
      							}
		      					##################################################
      							# messenger - END
      							##################################################
								$phone = $item->getTelephone();
      							$handy = $item->getCellularphone();
								$column2 = '';
								if ( !empty($phone) ){
         							$column2 .= $view->_text_as_html_short($phone).LF;
      							}
      							if (!empty($phone) and !empty($handy)) {
         							$column2 .= BRLF;
      							}
      							if ( !empty($handy) ){
         							$column2 .= $view->_text_as_html_short($handy).LF;
      							}
     							if ($item->isEmailVisible()) {
         							$email = $item->getEmail();
         							$email_text = $email;
         							$column3 = curl_mailto( $item->getEmail(), $view->_text_as_html_short(chunkText($email_text,20)),$email_text);
     							} else {
         							$column3 = $translator->getMessage('USER_EMAIL_HIDDEN');
     							}
								break;
							case CS_GROUP_TYPE:
								$column1 = $item->getTitle();
								$members = $item->getMemberItemList();
            					$column2 = $translator->getMessage('GROUP_MEMBERS').': '.$members->getCount();
            					$linked_item_array = $item->getAllLinkedItemIDArray();
								$column3 = $translator->getMessage('COMMON_REFERENCED_LATEST_ENTRIES').': '.count($linked_item_array);
								break;
							case CS_TOPIC_TYPE:
								$column1 = $item->getTitle();
								$column2 = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
           						$linked_item_array = $item->getAllLinkedItemIDArray();
								$column3 = $translator->getMessage('COMMON_REFERENCED_LATEST_ENTRIES').': '.count($linked_item_array);
								break;
							case CS_PROJECT_TYPE:
								$column1 = $item->getTitle();
           						$column2 = $translator->getMessage('GROUP_MEMBERS').': '.$item->getAllUsers();
 								$column3 = $this->_getItemActivity ($item,$room_max_activity);
							    $user_manager->setUserIDLimit($current_user->getUserID());
							    $user_manager->setAuthSourceLimit($current_user->getAuthSource());
							    $user_manager->setContextLimit($item->getItemID());
							    $user_manager->select();
							    $user_list = $user_manager->get();
							    if (!empty($user_list)){
							       $room_user = $user_list->getFirst();
							    } else {
							       $room_user = '';
							    }
						    	if ($current_user->isRoot()) {
						           $may_enter = true;
						        } elseif ( !empty($room_user) ) {
						           $may_enter = $item->mayEnter($room_user);
						        } else {
						           $may_enter = false;
						        }

								break;
							case CS_TODO_TYPE:
								$column1 = $item->getTitle();
								$original_date = $item->getDate();
      							$date = getDateInLang($original_date);
      							$status = $item->getStatus();
      							$actual_date = date("Y-m-d H:i:s");
      							if ($status != $translator->getMessage('TODO_DONE') and $original_date < $actual_date){
         							$date = '<span class="required">'.$date.'</span>';
      							}
      							if ($original_date == '9999-00-00 00:00:00'){
      	 							$date = $translator->getMessage('TODO_NO_END_DATE');
      							}
								$column2 = $date;
								$column3 = $this->_getTodoItemProcess($item,$translator);
								break;
							default:
								$column1 = $item->getTitle();
								$column2 = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());

								$modificatorItem = $item->getModificatorItem();
								if ($modificatorItem) {
									$column3 = $modificatorItem->getFullName();
									$modificator_id = $modificatorItem->getItemID();
								} else {
									$column3 = $translator->getMessage('COMMON_DELETED_USER');
									$modificator_id = null;
								}
								
								
	               		}

						// files
						$with_files = false;
						$file_count = 0;
						$attachment_infos = array();
						if(in_array($key, $this->getRubricsWithFiles())) {
							$with_files = true;
							
							if ($key == CS_MATERIAL_TYPE){
								$file_count = $item->getFileListWithFilesFromSections()->getCount();
								$file_list = $item->getFileListWithFilesFromSections();
							}elseif($key == CS_DISCUSSION_TYPE){
								$file_count = $item->getFileListWithFilesFromArticles()->getCount();
								$file_list = $item->getFileListWithFilesFromArticles();
							}else{
								$file_count = $item->getFileList()->getCount();
								$file_list = $item->getFileList();
							}
							$file = $file_list->getFirst();
							while($file) {
								$lightbox = false;
								if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) $lightbox = true;

								$info = array();
								#$info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
								$info['file_name']	= $converter->filenameFormatting($file->getDisplayName());
								$info['file_icon']	= $file->getFileIcon();
								$info['file_url']	= $file->getURL();
								$info['file_size']	= $file->getFileSize();
								$info['lightbox']	= $lightbox;

								$attachment_infos[] = $info;
								$file = $file_list->getNext();
							}
						}

						$item_array[] = array(
							'iid'				=> $item->getItemID(),
							'user_iid'			=> $modificator_id,
							'column_1'			=> $column1,
							'column_1_addon'	=> $column1_addon,
							'column_2'			=> $column2,
							'column_3'			=> $column3,
							'noticed'			=> $noticed_text,
							'has_attachments'	=> $with_files,
							'attachment_count'	=> $file_count,
							'attachment_infos'	=> $attachment_infos,
							'may_enter'			=> $may_enter,
							'color'             => $color
						);

						$item = $list->getNext();
					}
					$return[$key]['items'] = $item_array;

					// message tag
					$message_tag = '';
					//TODO: complete missing tags
					$shown = 0;
					switch($key) {
						case CS_ANNOUNCEMENT_TYPE:
							$message_tag = $translator->getMessage('COMMON_' . mb_strtoupper($key) . '_SHORT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
							break;
						case CS_DATE_TYPE:
							$message_tag = $translator->getMessage('HOME_DATES_SHORT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
							break;
						case CS_PROJECT_TYPE:
							if($this->_environment->inProjectRoom()) {
								$message_tag = $translator->getMessage('PROJECT_SHORT_DESCRIPTION', 5);
							} elseif($this->_environment->inCommunityRoom()) {
								if(isset($list) && $list->isNotEmpty() && $list->getCount() < 10){
									$count = $list->getCount();
								}else{
									$count = '10';
								}
								$message_tag = $translator->getMessage('COMMUNITY_SHORT_DESCRIPTION').' '.$count;
							}
							break;
						case CS_GROUP_TYPE:
							$message_tag = $translator->getMessage('HOME_GROUP_SHORT_VIEW_DESCRIPTION', $list->getCount());
							break;
						case CS_TODO_TYPE:
							$message_tag = $translator->getMessage('TODO_SHORT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
							break;
						case CS_TOPIC_TYPE:
							if(isset($list) && $list->isNotEmpty()) {
								$shown = $list->getCount();
							} else {
								$shown = 0;
							}
							$message_tag = $translator->getMessage('HOME_TOPIC_SHORT_VIEW_DESCRIPTION', $shown);
							break;
						case CS_USER_TYPE:

							if($this->_environment->inProjectRoom()) {
								global $who_is_online;
								if(isset($who_is_online) && $who_is_online) {
									$shown = $list->getCount();
									if($shown > 0) {
										$days = ($context_item->isProjectRoom() ? $context_item->getTimeSpread() : 90);
										$item = $list->getFirst();
										$count_active_now = 0;
										while($item) {
											$lastlogin = $item->getLastLogin();
											if($lastlogin > getCurrentDateTimeMinusMinutesInMySQL($days)) {
												$count_active_now++;
											}
											$item = $list->getNext();
										}
									}

									$message_tag = $translator->getMessage('HOME_USER_SHORT_VIEW_DESCRIPTION2', $shown, $count_active_now, $rubric_count_all_array[$key], $days);
								} else {
									$message_tag = $translator->getMessage('HOME_USER_SHORT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
								}
							} else {
								$message_tag = $translator->getMessage('COMMON_SHORT_CONTACT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
							}
							break;
						case CS_MATERIAL_TYPE:
							if($this->_environment->inProjectRoom()) {
								$period = $context_item->getTimeSpread();
								$message_tag = $translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION', $list->getCount(), $period, $rubric_count_all_array[$key]);
							} else {
								$message_tag = $translator->getMessage('COMMON_SHORT_MATERIAL_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
							}
							break;
						case CS_DISCUSSION_TYPE:
							$shown = $list->getCount();
							if($this->_environment->inProjectRoom() || $this->_environment->inGroupRoom()) {
								$period = $context_item->getTimeSpread();
								$message_tag = $translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION', $shown, $period, $rubric_count_all_array[$key]);
							} elseif($this->_environment->inCommunityRoom()) {
								if($shown != 1) {
									$message_tag = $translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION_CR', $shown, $rubric_count_all_array[$key]);
								} else {
									$message_tag = $translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION_CR_ONE', $shown, $rubric_count_all_array[$key]);
								}
							}
							break;
					}
					$return[$key]['message_tag'] = $message_tag;

				 }


			      // TODO attachment_count...


					// append return
					/*
					$return = array(
						'items'		=> $rubric_array/*,
						'count_all'	=> $count_all_shown*/
					/*);
					*/
			return $return;
		}


   function _getTodoItemProcess($item,$translator){
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

      $tmp_message = $translator->getMessage('COMMON_MINUTES');
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
      $shown_time = $step_minutes_text.' '.$tmp_message;
      $display_time_text = $shown_time.' - '.round($done_percentage).'% '.$translator->getMessage('TODO_DONE');

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



		// TODO: home view does not have any list actions -> actions could be derived into another subclass
		public function getAdditionalListActions() {
			return array();
		}

		protected function getAdditionalActions(&$perms) {
		}
	}