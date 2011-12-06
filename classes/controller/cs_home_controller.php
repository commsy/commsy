<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_home_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'room_home';
		}

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
		}

		public function getListContent() {
			$session = $this->_environment->getSessionItem();
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$translator = $environment->getTranslationObject();
			$context_item = $environment->getCurrentContextItem();
			$current_user = $environment->getCurrentUser();

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
	                        $manager->showNoNotActivatedEntries();

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

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
	                        $manager->showNoNotActivatedEntries();

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

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
	                        if ( $interval > 0 ) {
	                           $manager->setIntervalLimit(0,5);
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
	                        $manager->showNoNotActivatedEntries();

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

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
	                        $manager->select();
	                        $list = $manager->get();
	                        $count_all = $list->getCount();
	                     break;
	                  case CS_INSTITUTION_TYPE:
	                        $manager = $environment->getInstitutionManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
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
	                        $manager->showNoNotActivatedEntries();

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

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
	                        $manager->showNoNotActivatedEntries();

	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

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
			      $noticed_manager->getLatestNoticedByIDArray($id_array);
			      $noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
			      $id_array = array_merge($id_array, $sub_id_array);
			      $link_manager = $environment->getLinkManager();
			      $file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array, $v_id_array);
			      $file_manager = $environment->getFileManager();
			      $file_manager->setIDArrayLimit($file_id_array);

				 foreach($rubric_list_array as $key=>$list){
					$item_array = array();
	               	$item = $list->getFirst();
	               	$params = array();
					$params['environment'] = $environment;
					$params['with_modifying_actions'] = false;
					$view = new cs_view($params);
	           		 while($item) {
						$noticed_text = $this->_getItemChangeStatus($item);
						
						if($key == CS_ANNOUNCEMENT_TYPE) {
							$parse_day_start = convertDateFromInput($item->getSeconddateTime(), $this->_environment->getSelectedLanguage());
							$conforms = $parse_day_start['conforms'];
							if($conforms === true) {
								$date = $translator->getDateInLang($parse_day_start['datetime']);
							} else {
								$date = $item->getSeconddateTime();
							}
						} else {
							$date = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
						}
						$item_array[] = array(
						'iid'				=> $item->getItemID(),
						'title'				=> $view->_text_as_html_short($item->getTitle()),
						'date'				=> $date,
						'creator'			=> $item->getCreatorItem()->getFullName(),
						'noticed'			=> $noticed_text
					//	'attachment_count'	=> $item->getFileList()->getCount()
		//				'attachment_infos'	=>
						);

						$item = $list->getNext();
					}
					$return[$key]['items'] = $item_array;
					
					// message tag
					$message_tag = '';
					//TODO: complete missing tags
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
							} elseif($this->_environment>inCommunityRoom()) {
								$message_tag = $translator->getMessage('COMMUNITY_SHORT_DESCRIPTION');
							}
							break;
						case CS_GROUP_TYPE:
							$message_tag = $translator->getMessage('HOME_GROUP_SHORT_VIEW_DESCRIPTION', $shown);
							break;
						case CS_TODO_TYPE:
							$message_tag = $translator->getMessage('TODO_SHORT_VIEW_DESCRIPTION', $shown, $rubric_count_all_array[$key]);
							break;
						case CS_TOPIC_TYPE:
							if(isset($list) && $list->isNotEmpty()) {
								$shown = $list->getCount();
							} else {
								$shown = 0;
							}
							$message_tag = $translator->getMessage('HOME_TOPIC_SHORT_VIEW_DESCRIPTION', $shown);
							break;
						case CS_INSTITUTION_TYPE:
							if($rubric_count_all_array[$key] > 0) {
								$message_tag = $translator->getMessage('HOME_INSTITUTION_SHORT_VIEW_DESCRIPTION', $list->getCount());
							}
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
									$message_tag = $translator->getMessage('HOME_USER_SHORT_VIEW_DESCRIPTION', $shown, $rubric_count_all_array[$key]);
								}
							} else {
								$message_tag = $translator->getMessage('COMMON_SHORT_CONTACT_VIEW_DESCRIPTION', $shown, $rubric_count_all_array[$key]);
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
		
		// TODO: home view does not have any list actions -> actions could be derived into another subclass
		public function getAdditionalListActions() {
			return array();
		}
	}