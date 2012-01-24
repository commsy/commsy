<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_todo_detail_controller extends cs_detail_controller {
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'todo_detail';
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
		public function actionDetail() {
			// try to set the item
			$this->setItem();
			
			$this->setupInformation();
			
			$session = $this->_environment->getSessionItem();
			
			$current_user = $this->_environment->getCurrentUserItem();
			
			// TODO: include_once('include/inc_delete_entry.php');
			
			// check for deleted
			if($this->_item->isDeleted()) {
				// TODO: implement error handling
				/*
				 * $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
   $page->add($errorbox);
				 */
			}
			
			// check for visibility
			elseif(!$this->_item->maySee($current_user)) {
				// TODO: implement error handling
				/*
				 * $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
				 */
			}
			
			else {
				// get clipboard
				$clipboard_id_array = array();
				if($session->issetValue('todo_clipboard')) {
					$clipboard_id_array = $session->getValue('todo_clipboard');
				}
				
				// copy to clipboard
				if(isset($_GET['add_to_todo_clipboard']) && !in_array($this->_item->getItemID(), $clipboard_id_array)) {
					$clipboard_id_array[] = $this->_item->getItemID();
					$session->setValue('todo_clipboard', $clipboard_id_array);
				}
				
				// current context
				$current_context = $this->_environment->getCurrentContextItem();
				$context_open = $current_context->isOpen();
				
				// enter or leave topic
				if(!empty($_GET['todo_option'])) {
					if($_GET['todo_option'] === '1') {
						$this->_item->addProcessor($current_user);
					} elseif($_GET['todo_option'] === '2') {
						$this->_item->removeProcessor($current_user);
					}
				}
				
				// used to signal which "creator infos" of todos are expanded...
				$creatorInfoStatus = array();
				if(!empty($_GET['creator_info_max'])) {
					$creatorInfoStatus = explode('-', $_GET['creator_info_max']);
				}
				
				/*
				 * 

   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $context_open;
   $params['creator_info_status'] = $creatorInfoStatus;
   $detail_view = $class_factory->getClass(TODO_DETAIL_VIEW,$params);
   unset($params);
   */
				
				/*
				 * // set the view's item
   $detail_view->setItem($todo_item);
   $detail_view->setClipboardIDArray($clipboard_id_array);
   $detail_view->setRubricConnections(array(CS_GROUP_TYPE,CS_MATERIAL_TYPE));
				 */
				
				// mark as read and noticed
				$this->markRead();
				$this->markNoticed();
				
				// set up browsing
				/*
				$todo_ids = array();
				if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_todo_index_ids')) {
					$todo_ids = $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_todo_index_ids');
				}
				
				/*
				 *  $detail_view->setBrowseIDs($todo_ids);
				   if ( isset($_GET['pos']) ) {
				      $detail_view->setPosition($_GET['pos']);
				   }
				 */
				
				/*


   

   $current_room_modules = $context_item->getHomeConf();
   if ( !empty($current_room_modules) ){
      $room_modules = explode(',',$current_room_modules);
   } else {
      $room_modules =  $default_room_modules;
   }
   $first = '';
   foreach ( $room_modules as $module ) {
      $link_name = explode('_', $module);
      if ( $link_name[1] != 'none' ) {
         switch ($link_name[0]) {
            case 'group':
            if (empty($first)){
               $first = 'group';
            }
            break;
            case CS_TOPIC_TYPE:
            if (empty($first)){
               $first = CS_TOPIC_TYPE;
            }
            break;
         }
      }
   }
   // set up ids of linked items
   $material_ids = $todo_item->getLinkedItemIDArray(CS_MATERIAL_TYPE);
   $session->setValue('cid'.$environment->getCurrentContextID().'_material_index_ids', $material_ids);
   if ($context_item->withRubric(CS_TOPIC_TYPE) ) {
      $ids = $todo_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
      $session->setValue('cid'.$environment->getCurrentContextID().'_topics_index_ids', $ids);
   }
   if ( $context_item->withRubric(CS_GROUP_TYPE) ) {
      $ids = $todo_item->getLinkedItemIDArray(CS_GROUP_TYPE);
      $session->setValue('cid'.$environment->getCurrentContextID().'_group_index_ids', $ids);
   }
   $rubric_connections = array();
   if ($first == CS_TOPIC_TYPE){
      $rubric_connections = array(CS_TOPIC_TYPE);
      if ($context_item->withRubric(CS_GROUP_TYPE) ){
         $rubric_connections[] = CS_GROUP_TYPE;
      }
   }elseif($first == 'group'){
      $rubric_connections = array(CS_GROUP_TYPE);
      if ($context_item->withRubric(CS_TOPIC_TYPE) ){
         $rubric_connections[] = CS_TOPIC_TYPE;
      }
   }
   $rubric_connections[] = CS_MATERIAL_TYPE;
   $detail_view->setRubricConnections($rubric_connections);
   
   */
				
				// annotations
				$annotations = $this->_item->getAnnotationList();
				$this->assign('detail', 'annotations', $this->getAnnotationInformation(&$annotations));
				
				
				/*
				 * $reader_manager->getLatestReaderByIDArray($id_array);
				   $noticed_manager->getLatestNoticedByIDArray($id_array);
				   $annotation = $annotations->getFirst();
				   while($annotation ){
				      $reader = $reader_manager->getLatestReader($annotation->getItemID());
				      if ( empty($reader) or $reader['read_date'] < $annotation->getModificationDate() ) {
				         $reader_manager->markRead($annotation->getItemID(),0);
				      }
				      $noticed = $noticed_manager->getLatestNoticed($annotation->getItemID());
				      if ( empty($noticed) or $noticed['read_date'] < $annotation->getModificationDate() ) {
				         $noticed_manager->markNoticed($annotation->getItemID(),0);
				      }
				      $annotation = $annotations->getNext();
				   }
				   $detail_view->setAnnotationList($annotations);
				 */
				
				/*

   // highlight search words in detail views
   $session_item = $environment->getSessionItem();
   if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
      $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
      if ( !empty($search_array['search']) ) {
         $detail_view->setSearchText($search_array['search']);
      }
      unset($search_array);
   }
   
   $page->add($detail_view);
				 */
				
				$this->assign('detail', 'content', $this->getDetailContent());
			}
		}
		
		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/
		
		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();
			
			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_todo_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_todo_index_ids'));
			}
		}
		
		protected function getDetailContent() {
            $converter = $this->_environment->getTextConverter();
            
            // description
            $desc = $this->_item->getDescription();
            if(!empty($desc)) {
            	$desc = $converter->cleanDataFromTextArea($desc);
            	//TODO:
            	//$desc = $converter->compareWithSearchText($desc);
            	$converter->setFileArray($this->getItemFileList());
            	$desc = $converter->text_as_html_long($desc);
            	//$html .= $this->getScrollableContent($desc,$item,'',true).LF;
            }
            
			$return = array(
				'title'			=> $this->_item->getTitle(),
				'formal'		=> $this->getFormalData(),
				'description'	=> $desc
			/*
				'creator'		=> $this->_item->getCreatorItem()->getFullName(),
				'creation_date'	=> getDateTimeInLang($this->_item->getCreationDate()),
				'version'		=> $this->_item->getVersionID(),
				'formal'		=> $this->getFormalData(),
				'sections'		=> $this->getSections(),
				
				*/
				//'material'			=> $this->getMaterialContent()
			);
			
			return $return;
		}
		
		private function getFormalData() {
			$return = array();
			
			$user = $this->_environment->getCurrentUser();
			$context = $this->_environment->getCurrentContextItem();
			$translator = $this->_environment->getTranslationObject();
			$converter = $this->_environment->getTextConverter();
			
			$formal_data = array();
			
			// date
			$original_date = $this->_item->getDate();
			$date = getDateTimeInLang($original_date);
			$status = $this->_item->getStatus();
			$actual_date = date("Y-m-d H:i:s");
			
			if($status !== $translator->getMessage('TODO_DONE') && $original_date < $actual_date) {
				// in progress
				// TODO:
				//$date = '<span class="required">'.$date.'</span>';
			}
			
			if($original_date === '9999-00-00 00:00:00') {
				// no end date
				$date = 'no_end';
			}
			
			$return['date'] = $date;
			
			// status
			$return['status'] = $this->_item->getStatus();
			
			// todo management
			if($context->withTodoManagement()) {
				$step_html = '';
				$step_minutes = 0;
				$step_item_list = $this->_item->getStepItemList();
				if($step_item_list->isEmpty()) {
					$step_html .= '';
				} else {
					$step = $step_item_list->getFirst();
					$count = $step_item_list->getCount();
					$counter = 0;
					
					while($step) {
						$counter++;
						$step_minutes = $step_minutes + $step->getMinutes();
						
						$fileicons = '';
						$files = $step->getFileList();
						$file = $files->getFirst();
						while($file) {
							$url = $file->getUrl();
							$displayname = $file->getDisplayName();
							$filesize = $file->getFileSize();
							$fileicon = $file->getFileIcon();
							if($this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() && ($step->isPublished() || $user->isUser()))) {
								if(isset($_GET['mode']) && $_GET['mode'] === 'print') {
									$fileicons .= '<span class="disabled">' . $fileicon . '</span>' . "\n";
								} else {
									if(in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
										// TODO
										/*
										 * $this->_with_slimbox = true;
                   // jQuery
                   //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                   $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                   // jQuery
										 */
									} else {
										$fileicons .= '<a href="' . $url . '" title="' . $converter->text_as_html_short($displayname) . ' (' . $filesize . ' kb)" target="blank" >' . $fileicon . '</a>';
									}
								}
							} else {
								$fileicons .= '<span class="disabled">' . $fileicon . '</span>' . "\n";
							}
							
							$file = $files->getNext();
						}
						
						if(!empty($fileicons)) {
							$fileicons = '&nbsp;' . $fileicons;
						}
						
						$params = array();
						$params['iid'] = $this->_item->getItemID();
						$hover = str_replace('"', '&quot;', $converter->text_as_html_short($step->getTitle()));
						$param_zip = $this->_environment->getValueOfParameter('download');
						if(empty($param_zip) || $param_zip != 'zip') {
							$linktext = $step->getTitle();
							//TODO:
							//$linktext = $converter->compareWithSearchText($linktext);
							$linktext = $converter->text_as_html_short($linktext);
							
							$title = ahref_curl(
								$this->_environment->getCurrentContextID(),
								CS_TODO_TYPE,
								'detail',
								$params,
								$linktext,
								$hover, '',
								'anchor' . $step->getItemID());
						} else {
							$title = $step->getTitle();
							//TODO:
							//$title = $converter->compareWithSearchText($title);
							$title = $converter->text_as_html_short($title);
						}
						
						$step_html .= $counter . '. ' . $title . $fileicons;
						
						if($user->isUser()) {
							$noticed_manager = $this->_environment->getNoticedManager();
							$noticed = $notcied_manager->getLatestNoticed($step->getItemID());
							
							if(empty($noticed)) {
								$step_html .= ' <span class="changed">[' . $translator->getMessage('COMMON_NEW') . ']</span>';
							} elseif($noticed['read_date'] < $step->getModificationDate()) {
								$step_html .= ' <span class="changed">[' . $translator->getMessage('COMMON_CHANGED') . ']</span>';
							}
						}
						$step_html .= ' ' . '<br/>';
						
						$step = $step_item_list->getNext();
					}
				}
				$done_time = '';
				
				$done_percentage = 100;
				if($this->_item->getPlannedTime() > 0) {
					$done_percentage = $step_minutes / $this->_item->getPlannedTime() * 100;
				}
				
				$time_type = $this->_item->getTimeType();
				$tmp_message = $translator->getMessage('COMMON_MINUTES');
				$step_minutes_text = $step_minutes;
				switch($time_type) {
					case 2:
						$step_minutes_text = '';
						$exact_minutes = $step_minutes / 60;
						$step_minutes = round($exact_minutes, 1);
						if($step_minutes !== $exact_minutes) {
							$step_minutes_text .= 'ca. ';
						}
						if($translator->getSelectedLanguage() === 'de') {
							$step_minutes = str_replace('.', ',', $step_minutes);
						}
						$step_minutes_text .= $step_minutes;
						$tmp_message = $translator->getMessage('COMMON_HOURS');
						if($step_minutes === 1) {
							$tmp_message = $translator->getMessage('COMMON_HOUR');
						}
						break;
					case 3:
						$exact_minutes = ($step_minutes / 60) / 8;
						$step_minutes = round($exact_minutes, 1);
						$step_minutes_text = '';
						if($step_minutes !== $exact_minutes) {
							$step_minutes_text .= 'ca. ';
						}
						if($translator->getSelectedLanguage() === 'de') {
							$step_minutes = str_replace('.', ',', $step_minutes);
						}
						$step_minutes_text .= $step_minutes;
						$tmp_message = $translator->getMessage('COMMON_DAYS');
						if($step_minutes === 1) {
							$tmp_message = $translator->getMessage('COMMON_DAY');
						}
						break;
					default:
						$step_minutes = round($step_minutes, 1);
						if($translator->getSelectedLanguage() === 'de') {
							$step_minutes = str_replace('.', ',', $step_minutes);
						}
						break;
				}
	
				

/**/
         if($done_percentage <= 100){
            $style = ' height: 16px; background-color: #75ab05; ';
            $done_time .= '      <div style="border: 1px solid #444;  margin-left: 0px; height: 16px; width: 300px;">'.LF;
            if ( $done_percentage >= 30 ) {
               $done_time .= '         <div style="font-size: 10pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">'.$step_minutes_text.' '.$tmp_message.'</div>'.LF;
            } else {
               $done_time .= '<div style="float:right; font-size: 10pt;">'.$step_minutes_text.' '.$tmp_message.'</div>';
               $done_time .= '         <div style="font-size: 10pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
            }
            $done_time .= '      </div>'.LF;
         }elseif($done_percentage <= 120){
            $done_percentage = (100 / $done_percentage) *100;
            $style = ' height: 16px; border: 1px solid #444; background-color: #f2f030; ';
            $done_time .= '         <div style="width: 300px; font-size: 10pt; '.$style.' color:#000000;">'.LF;
            $done_time .= '      <div style="border-right: 1px solid #444; padding-top:0px; margin-left: 0px; height:16px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
            $done_time .= '&nbsp;'.$step_minutes_text.' '.$tmp_message;
            $done_time .= '      </div>'.LF;
            $done_time .= '</div>'.LF;
         }else{
            $done_percentage = (100 / $done_percentage) *100;
            $style = ' height: 16px; border: 1px solid #444; background-color: #f23030; ';
            $done_time .= '         <div style="width: 300px; font-size: 10pt; '.$style.' color:#000000;">'.LF;
            $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:16px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
            $done_time .= '&nbsp;'.$step_minutes_text.' '.$tmp_message;
            $done_time .= '      </div>'.LF;
            $done_time .= '</div>'.LF;
         }
         
  /**/
         		if($this->_item->getPlannedTime() > 0) {
         			$minutes = $this->_item->getPlannedTime();
		         	$time_type = $this->_item->getTimeType();
		         	$tmp_message = $translator->getMessage('COMMON_MINUTES');
		         	
		         	switch($time_type) {
		         		case 2:
		         			$minutes = $minutes / 60;
		         			$tmp_message = $translator->getMessage('COMMON_HOURS');
		         			if($minutes === 1) {
		         				$tmp_message = $translator->getMesasge('COMMON_HOUR');
		         			}
		         			break;
		         		case 3:
		         			$minutes = ($minutes / 60) / 9;
		         			$tmp_message = $translator->getMessage('COMMON_DAYS');
		         			if($minutes === 1) {
		         				$tmp_message = $translator->getMessage('COMMON_DAY');
		         			}
		         			break;
		         	}
		         	
		         	if($translator->getSelectedLanguage() === 'de') {
		         		$minutes = str_replace('.', ',', $minutes);
		         	}
		         	
		         	$return['management'][0] = $minutes . ' ' . $tmp_message;
		        } elseif($this->_item->getPlannedTime() === 0 && $done_percentage > 0) {
		        	$tmp_message = $translator->getMessage('COMMON_MINUTES');
		        	$done_time = $step_minutes;
		        	
		        	if(($step_minutes / 60) > 1 && ($step_minutes / 60) <= 8) {
		        		$step_minutes_text = '';
		        		$exact_minutes = $step_minutes / 60;
		        		$step_minutes = round($exact_minutes, 1);
		        		$done_time = '';
		        		if($step_minutes !== $exact_minutes) {
		        			$done_time .= 'ca. ';
		        			if($translator->getSelectedLanguage() === 'de') {
		        				$step_minutes = str_replace('.', ',', $step_minutes);
		        			}
		        			$done_time .= $step_minutes;
		        			$tmp_message = $translator->getMessage('COMMON_HOURS');
		        			if($step_minutes === 1) {
		        				$tmp_message = $translator->getMessage('COMMON_HOUR');
		        			}
		        		}
		        	} elseif(($step_minutes / 60) > 8) {
		        		$exact_minutes = ($step_minutes / 60) / 8;
		        		$step_minutes = round($exact_minutes, 1);
		        		$done_time = '';
		        		if($step_minutes != $exact_minutes) {
		        			$done_time .= 'ca. ';
		        		}
		        		$tmp_message = $translator->getMessage('COMMON_DAYS');
		        		if($step_minutes === 1) {
		        			$tmp_message = $translator->getMessage('COMMON_DAY');
		        		}
		        		if($translator->getSelectedLanguage() === 'de') {
		        			$step_minutes = str_replace('.', ',', $step_minutes);
		        		}
		        		$done_time .= $step_minutes;
		        	} else {
		        		$step_minutes = round($step_minutes, 1);
		        		if($translator->getSelectedLanguage() === 'de') {
		        			$step_minutes = str_replace('.', ',', $step_minutes);
		        		}
		        	}
		        	
		        	$done_time .= ' ' . $tmp_message;
		        }
		        
				if($done_percentage > 0 || $this->_item->getPlannedTime() > 0) {
					$return['management'][1] = $done_time;
				}
			}
			
			// members
			$members = $this->_item->getProcessorItemList();
			if(!$members->isEmpty()) {
				$member = $members->getFirst();
				$count = $members->getCount();
				$counter = 0;
				while($member) {
					$counter++;
					if($member->isUser()) {
						$linktext = $member->getFullname();
						//TODO:
						//$linktext = $converter->compareWithSearchText($linktext);
						$linktext = $converter->text_as_html_short($linktext);
						
						if($member->maySee($user)) {
							$params = array();
							$params['iid'] = $member->getItemID();
							$param_zip = $this->_environment->getValueOfParameter('download');
							if(empty($param_zip) || $param_zip != 'zip') {
								$member_html .= ahref_curl(
									$this->_environment->getCurrentContextID(),
									'user',
									'detail',
									$params,
									$linktext);
							} else {
								$member_html .= $linktext;
							}
							unset($params);
						} else {
							$member_html .= '<span class="disabled">'.$linktext.'</span>'.LF;
						}
						
						if($counter != $count) {
							$member_html .= ', ';
						}
					} else {
						$linktext = chunkText($member->getFullname(), 35);
						//TODO:
						//$linktext = $converter->compareWithSearchText($linktext);
						$linktext = $converter->text_as_html_short($linktext);
						$param_zip = $this->_environment->getValueOfParameter('download');
						if(empty($param_zip) || $param_zip != 'zip') {
							$member_html .= ahref_curl(
								$this->_environment->getCurrentContextID(),
								$this->_environment->getCurrentModule(),
								$this->_environment->getCurrentFunction(),
								array(),
								$link_text,
								$translator->getMessage('USER_STATUS_REJECTED'),
								'_self', '', '', '', '',
								'class="disabled"', '', '',
								true);
						} else {
							$member_html .= $link_title;
						}
						
						if($counter !== $count) {
							$member_html .= ', ';
						}
					}
					
					$member = $members->getNext();
				}
				
				$return['members'] = $member_html;
			}
			
			// files
			$files = array();
			$file_list = $this->_item->getFileList();
			if(!$file_list->isEmpty()) {
				$file = $file_list->getFirst();
				while($file) {
					if(!(isset($_GET['mode']) && $_GET['mode'] === 'print') || (isset($_GET['download']) && $_GET['download'] === 'zip')) {
						if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
							// TODO
							/*
							 * $this->_with_slimbox = true;
                  // jQuery
                  //$file_string = '<a href="'.$file->getUrl().'" rel="lightbox[gallery'.$item->getItemID().']">'.
                  //$file_string = '<a href="'.$file->getUrl().'" rel="lightbox-gallery_'.$item->getItemID().'">'.
                  $displayname = $file->getDisplayName();
                  $filesize = $file->getFileSize();
                  $fileicon = $file->getFileIcon();
                  $file_string = '<a href="'.$file->getUrl().'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)">'.

                  // jQuery
                  $file->getFileIcon().' '.($this->_text_as_html_short($this->_compareWithSearchText($file->getDisplayName()))).'</a> ('.$file->getFileSize().' KB)';
							 */
						} else {
							$file_string = '<a href="' . $file->getUrl() . '" target ="balnk">';
							$file_string = $file->getDisplayName();
							//TODO:
							//$file_string = $converter->compareWithSearchText($file_string);
							$file_string = $converter->text_as_html_short($file_string);
							$file_string = $file->getFileIcon() . ' ' . ($file_string) . '</a> (' . $file->getFileSize() . ' KB';
						}
					} else {
						$file_string = $file->getDisplayName();
						//TODO:
						//$file_string = $converter->compareWithSearchText($file_string);
						$file_string = $converter->text_as_html_short($file_string);
						$file_string = $file->getFileIcon() . ' ' . ($file_string);
					}
					
					$files[] = $file_string;
					
					$file = $file_list->getNext();
				}
			}
			
			if(!empty($files)) {
				$return['files'] = implode(BRLF, $files);
			}
			
			// steps
			if($context->withTodoManagement()) {
				$return['steps'] = $step_html;
			}
			
			
			return $return;
		}
		
		/*
		 * 
      // creator, modificator and reference number for printing
      if(isset($_GET['mode']) and $_GET['mode']=='print'){
	      $modificator = $item->getModificatorItem();
	      $creator = $item->getCreatorItem();

	      if(isset($modificator) and !$modificator->isDeleted()){
	      	  $current_user_item = $this->_environment->getCurrentUserItem();
	          if ( $current_user_item->isGuest() ) {
	             $temp_modificator = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
	          } else {
	             $temp_modificator = $modificator->getFullname();
	          }
              unset($current_user_item);
	      } else {
	      	  $temp_modificator = $this->_translator->getMessage('COMMON_DELETED_USER');
	      }

	      if(isset($creator) and !$creator->isDeleted()){
	      	$current_user_item = $this->_environment->getCurrentUserItem();
	            if ( $current_user_item->isGuest() ) {
	               $temp_creator = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
	            } else {
	               $temp_creator = $creator->getFullname();
	            }
            unset($current_user_item);
	      } else {
	      	  $temp_creator = $this->_translator->getMessage('COMMON_DELETED_USER');
	      }

	      $html .= '<table class="creator_info" summary="Layout" style="padding-top:20px">'.LF;

      	  // Modificator information

      	  $html .= '   <tr>'.LF;
      	  $html .= '      <td></td>'.LF;
      	  $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      	  $html .= '         '.$this->_translator->getMessage('COMMON_LAST_MODIFIED_BY').':&nbsp;'.LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '      <td class="value">'.LF;
      	  $html .= '         '.$temp_modificator.', '.$this->_translator->getDateTimeInLang($item->getModificationDate()).LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '   </tr>'.LF;

      	  // Creator information

	      $html .= '   <tr>'.LF;
      	  $html .= '      <td></td>'.LF;
      	  $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      	  $html .= '         '.$this->_translator->getMessage('COMMON_CREATED_BY').':&nbsp;'.LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '      <td class="value">'.LF;
      	  $html .= '         '.$temp_creator.', '.$this->_translator->getDateTimeInLang($item->getCreationDate()).LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '   </tr>'.LF;

      	  // Reference number

      	  $html .= '   <tr>'.LF;
      	  $html .= '      <td></td>'.LF;
      	  $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      	  $html .= '         '.$this->_translator->getMessage('COMMON_REFNUMBER').':&nbsp;'.LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '      <td class="value">'.LF;
      	  $html .= '         '.$item->getItemID();
      	  $html .= '      </td>'.LF;
      	  $html .= '   </tr>'.LF;
      	  $html .= '</table>'.LF;

      }

      $html  .= '<!-- END OF TODO ITEM DETAIL -->'.LF.LF;
      return $html;
		 */
	}