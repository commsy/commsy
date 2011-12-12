<?php
	require_once('classes/controller/cs_room_controller.php');

	abstract class cs_detail_controller extends cs_room_controller {
		protected $_browse_ids = array();
		protected $_position = -1;
		protected $_item = null;
		protected $_manager = null;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			// init variables
			/*
			$this->getViewMode() = 'browse';
			$this->_filter = array();
			$this->_paging = array(
				'offset'	=> 0,
				'limit'		=> 20
			);
			*/
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// mark as read and noticed
			$this->markRead();
			$this->markNoticed();
			
			// set list actions
			//$this->assign('list', 'actions', $this->getListActions());

			/*
			// set paging information
			$paging = array(
				'num_pages'		=> ceil($this->_num_entries / $this->_paging['limit']),
				'actual_page'	=> floor($this->_paging['offset'] / $this->_paging['limit']) + 1,
				'from'			=> $this->_paging['offset'] + 1,
				'to'			=> $this->_paging['offset'] + $this->_paging['limit']
			);
			$this->assign('list', 'paging', $paging);
			$this->assign('list', 'num_entries', $this->_num_entries);
			*/
		}
		
		protected function setupInformation() {
			$session = $this->_environment->getSessionItem();
			
			$ids = array();
			if(isset($_GET['path']) && !emptry($_GET['path'])) {
				$topic_manager = $this->_environment->getTopicManager();
				$topic_item = $topic_manager->getItem($_GET['path']);
				$path_item_list = $topic_item->getPathItemList();
				$path_item = $path_item_list->getFirst();
				
				while($path_item) {
					$ids[] = $path_item->getItem();
					$path_item = $path_item_list->getNext();
				}
				//$params['path'] = $_GET['path'];
	         	//$html .= $this->_getForwardLinkAsHTML($ids,'path');
			} elseif(isset($_GET['search_path']) && !empty($_GET['search_path'])) {
				$ids = $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_campus_search_index_ids');
				//$html .= $this->_getForwardLinkAsHTML($ids,'search');
				//$params['search_path'] = $_GET['search_path'];
			} elseif(isset($_GET['link_item_path']) && !empty($_GET['link_item_path'])) {
				$manager = $this->_environment->getItemManager();
				$item = $manager->getItem($_GET['link_item_path']);
				$ids = $item->getAllLinkeditemIDArray();
				//$html .= $this->_getForwardLinkAsHTML($ids,'link_item');
				//$params['link_item_path'] = $_GET['link_item_path'];
			} else {
				$ids = $this->getBrowseIDs();
				$this->assign('detail', 'browsing_information', $this->getBrowseInformation($ids));
				//$html .= $this->_getForwardLinkAsHTML($ids);
			}
			
			$this->assign('detail', 'forward_information', $this->getForwardInformation($ids));
		}
		
		protected function getAssessmentInformation() {
			$assessment_stars_text_array = array('non_active','non_active','non_active','non_active','non_active');
			$current_context = $this->_environment->getCurrentContextItem();
			if($current_context->isAssessmentActive()) {
				$assessment_manager = $this->_environment->getAssessmentManager();
				$assessment = $assessment_manager->getAssessmentForItemAverage($this->_item);
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
				for ($i=0; $i < $assessment_count_stars; $i++){
					$assessment_stars_text_array[$i] = 'active';
				}
			}
			
			return $assessment_stars_text_array;
		}
		
		protected function setItem() {
			// try to set the item
			if(!empty($_GET['iid'])) {
				$current_item_id = $_GET['iid'];
			} else {
				include_once('functions/error_functions.php');
				trigger_error('A discussion item id must be given.', E_USER_ERROR);
			}
			
			$item_manager = $this->_environment->getItemManager();
			$type = $item_manager->getItemType($_GET['iid']);
			$this->_manager = $this->_environment->getManager($type);
			$this->_item = $this->_manager->getItem($current_item_id);
		}
		
		private function getForwardInformation($ids) {
			$return = array();
			
			$converter = $this->_environment->getTextConverter();
			
			if(empty($ids)) {
				$ids = array();
				$ids[] = $this->_item->getItemID();
			}
			
			// determe item positions for forward box
			$count = 0;
			$pos = 0;
			foreach($ids as $id) {
				if($id == $this->_item->getItemID()) {
					$pos = $count;
				} else {
					$count++;
				}
			}
			
			$start = $pos - 4;
			$end = $pos + 4;
			if($start < 0) {
				$end -= $start;
			}
			if($end > count($ids)) {
				$end = count($ids);
				$start = $end - 9;
				if($start < 0) {
					$start = 0;
				}
			}
			
			// get information
			$listed_ids = array();
			$count_items = 0;
			$i = 1;
			foreach($ids as $id) {
				if($count_items >= $start && $count_items <= $end) {
					$item_manager = $this->_environment->getItemManager();
					$tmp_item = $item_manager->getItem($id);
					//$text = '';
					if(isset($tmp_item)) {
						$manager = $this->_environment->getManager($tmp_item->getItemType());
						$item = $manager->getItem($ids[$count_items]);
						$type = $tmp_item->getItemType();
						if($type == 'label') {
							$label_manager = $this->_environment->getLabelManager();
							$label_item = $label_manager->getItem($tmp_item->getItemID());
							$type = $label_item->getLabelType();
						}
						
						/*
						switch ( mb_strtoupper($type, 'UTF-8') ){
                  case 'ANNOUNCEMENT':
                     $text .= $this->_translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
                     break;
                  case 'DATE':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DATE');
                     break;
                  case 'DISCUSSION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DISCUSSION');
                     break;
                  case 'GROUP':
                     $text .= $this->_translator->getMessage('COMMON_ONE_GROUP');
                     break;
                  case 'INSTITUTION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_INSTITUTION');
                     break;
                  case 'MATERIAL':
                     $text .= $this->_translator->getMessage('COMMON_ONE_MATERIAL');
                     break;
                  case 'PROJECT':
                     $text .= $this->_translator->getMessage('COMMON_ONE_PROJECT');
                     break;
                  case 'TODO':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TODO');
                     break;
                  case 'TOPIC':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TOPIC');
                     break;
                  case 'USER':
                     $text .= $this->_translator->getMessage('COMMON_ONE_USER');
                     break;
                  case 'ACCOUNT':
                     $text .= $this->_translator->getMessage('COMMON_ACCOUNTS');
                     break;
                  default:
                     $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
                     break;
               }
						*/
					}
				}
				
				$link_title = '';
				if(isset($item) && is_object($item) && $item->isA(CS_USER_TYPE)) {
					$link_title = $item->getFullName();
				} elseif(isset($item) && is_object($item)) {
					$link_title = $item->getTitle();
				}
				
				// append to return
				$return[] = array(
					'title'			=> $converter->text_as_html_short($link_title),
					'is_current'	=> $item->getItemID() == $this->_item->getItemID()
				);
				
				/*
				 * 
				
            if ($this->_environment->getCurrentModule() == 'account'){
               $type = 'account';
            } elseif ( $this->_environment->getCurrentModule() == type2module(CS_MYROOM_TYPE) ) {
               $type = CS_MYROOM_TYPE;
            }
            if ($count_items < 9){
               $style='padding:0px 5px 0px 10px;';
            }else{
                $style='padding:0px 5px 0px 5px;';
            }
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( isset($item) and $item->getItemID()== $this->_item->getItemID()){
               $html .='<li class="detail_list_entry" style="'.$style.'">';
               $html .= '<span>'.($count_items+1).'. '.chunkText($link_title,35).'</span>';
               $html .='</li>';
            } elseif ( isset($item) and $item->isNotActivated() and !($item->getCreatorID() == $current_user_item->getItemID()) and !($current_user_item->isModerator())){
              $activating_date = $item->getActivatingDate();
               if (strstr($activating_date,'9999-00-00')){
                  $activating_text = $this->_translator->getMessage('COMMON_NOT_ACTIVATED');
               }else{
                  $activating_text = $this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
               }
               $html .='<li class="disabled" style="'.$style.'">';
               $params['iid'] =   $item->getItemID();
               $html .= ($count_items+1).'. '.ahref_curl( $this->_environment->getCurrentContextID(),
                                 $type,
                                 $this->_environment->getCurrentFunction(),
                                 $params,
                                 chunkText($link_title,35),
                                 $text.' - '.$link_title . '&nbsp;(' . $activating_text . ')',
                                 '',
                                 '',
                                 '',
                                 '',
                                 '',
                                 'class="disabled"',
                                 '',
                                 '',
                                 true);
               $html .='</li>';
            } elseif ( isset($item) ) {
               $html .='<li style="'.$style.'">';
               $params['iid'] =   $item->getItemID();
               $html .= ($count_items+1).'. '.ahref_curl( $this->_environment->getCurrentContextID(),
                                 $type,
                                 $this->_environment->getCurrentFunction(),
                                 $params,
                                 chunkText($link_title,35),
                                 $text.' - '.$link_title,
                                 '',
                                 '',
                                 '',
                                 '',
                                 '',
                                 'class="detail_list"');
               $html .='</li>';
            }
            unset($item);
				 */
				$count_items++;
			}
			
			if(isset($_GET['path']) && !empty($_GET['path'])) {
				$topic_manager = $this->_environment->getTopicManager();
				$topic_item = $topic_manager->getItem($_GET['path']);
				/*
				$params = array();
         $params['iid'] = $_GET['path'];
         $html .= $this->_translator->getMessage('COMMON_BACK_TO_PATH').': '.ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_TOPIC_TYPE,
                           'detail',
                           $params,
                           chunkText($topic_item->getTitle(),30)
                           );
                */
			} elseif(isset($_GET['search_path']) && !empty($_GET['search_path'])) {
				/*
				 $params = array();
         $params['iid'] = $_GET['path'];
         $html .= $this->_translator->getMessage('COMMON_BACK_TO_PATH').': '.ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_TOPIC_TYPE,
                           'detail',
                           $params,
                           chunkText($topic_item->getTitle(),30)
                           );
				 */
			} elseif(isset($_GET['link_item_path']) && !empty($_GET['link_item_path'])) {
				/*
				$params = array();
         $params['iid'] = $_GET['link_item_path'];
         $item_manager = $this->_environment->getItemManager();
         $tmp_item = $item_manager->getItem($_GET['link_item_path']);
         $manager = $this->_environment->getManager($tmp_item->getItemType());
         $item = $manager->getItem($_GET['link_item_path']);
         $type = $tmp_item->getItemType();
         if ($type == 'label'){
            $label_manager = $this->_environment->getLabelManager();
            $label_item = $label_manager->getItem($tmp_item->getItemID());
            $type = $label_item->getLabelType();
         }
         $manager = $this->_environment->getManager($type);
         $item = $manager->getItem($_GET['link_item_path']);
         if($type == CS_USER_TYPE){
             $link_title = $this->_text_as_html_short($item->getFullName());
         } else {
             $link_title = $this->_text_as_html_short($item->getTitle());
         }
         $html .= $this->_translator->getMessage('COMMON_BACK_TO_ITEM').': '.ahref_curl( $this->_environment->getCurrentContextID(),
                           $type,
                           'detail',
                           $params,
                           chunkText($link_title,20),
                           $link_title
                           );
				 */
			} else {
				/*
				  $display_mod = $this->_environment->getValueOfParameter('seldisplay_mode');
         if ( empty($display_mod) ) {
            $session = $this->_environment->getSessionItem();
            if ( $session->issetValue($this->_environment->getCurrentContextID().'_dates_seldisplay_mode') ) {
               $display_mod = $session->getValue($this->_environment->getCurrentContextID().'_dates_seldisplay_mode');
            }
         }
         $params = array();
         $params['back_to_index'] = 'true';
         $link_text = $this->_translator->getMessage('COMMON_BACK_TO_LIST');
         $link_module = $this->_environment->getCurrentModule();
         if ( module2type($this->_environment->getCurrentModule()) == CS_DATE_TYPE
              and !empty($display_mod)
              and $display_mod == 'calendar'
            ) {
            $link_text = $this->_translator->getMessage('DATE_BACK_TO_CALENDAR');
         }
         if ( module2type($this->_environment->getCurrentModule()) == CS_DATE_TYPE
              and $this->_environment->inPrivateRoom()
              and $this->_environment->getConfiguration('c_use_new_private_room')
            ) {
            $link_text = $this->_translator->getMessage('COMMON_BACK_TO_INDEX');
         }
         if ( module2type($this->_environment->getCurrentModule()) == CS_TODO_TYPE
              and $this->_environment->inPrivateRoom()
              and $this->_environment->getConfiguration('c_use_new_private_room')
            ) {
            $link_text = $this->_translator->getMessage('COMMON_BACK_TO_INDEX');
            $link_module = type2module(CS_DATE_TYPE);
         }
         if ( $this->_environment->inPrivateRoom()
              and $this->_environment->getConfiguration('c_use_new_private_room')
              and ( module2type($this->_environment->getCurrentModule()) == CS_MATERIAL_TYPE
                    or module2type($this->_environment->getCurrentModule()) == CS_DISCUSSION_TYPE
                    or module2type($this->_environment->getCurrentModule()) == CS_ANNOUNCEMENT_TYPE
                    or module2type($this->_environment->getCurrentModule()) == CS_TOPIC_TYPE
                  )
            ) {
            $link_text = $this->_translator->getMessage('COMMON_BACK_TO_INDEX');
            $link_module = type2module(CS_ENTRY_TYPE);
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                           $link_module,
                           'index',
                           $params,
                           $link_text
                           );
				 */
			}
			
			return $return;
		}
		
		private function getBrowseInformation($ids) {
			// TODO: see cs_detail_view _getForwardBoxAsHTML() for more to migrate...
			$return = array();
			
			// update position from GET-Vars
			if(isset($_GET['pos'])) {
				$this->_position = $_GET['pos'];
			}
			
			// get all non-active item ids
			$ids_not_activated = array();
			$item_manager = $this->_environment->getItemManager();
			$item_manager->resetLimits();
			$item_manager->setContextLimit($this->_environment->getCurrentContextID());
			$item_manager->setIDArrayLimit($ids);
			$item_manager->select();
			
			$item_list = $item_manager->get();
			$temp_item = $item_list->getFirst();
			while($temp_item) {
				if($temp_item->isNotActivated()) {
					$ids_not_activated[] = $temp_item->getItemID();
				}
				
				$temp_item = $item_list->getNext();
			}
			$item_manager->resetLimits();
			
			$count_all = count($ids);
			
			// determe the position if not (correctly) given
			if($this->_position < 0 || $this->_position >= $count_all) {
				if(empty($ids)) {
					$this->_position = -1;
				} else {
					if(isset($this->_item)) {
						$pos = array_search($this->_item->getItemID(), $ids);
						if($pos === null || $pos === false) {
							$pos = -1;
						}
					} else {
						$pos = -1;
					}
					
					$this->_position = $pos;
				}
			}
			
			// determe index position values
			$pos_index_start = 0;
			$pos_index_left = $this->_position - 1;
			$pos_index_right = $this->_position + 1;
			$pos_index_end = $count_all - 1;
			
			// prepare browsing
			$browse_left = 0;		// 0 means: do not browse
			$browse_start = 0;		// 0 means: do not browse
			if($this->_position > 0) {
				// check for browsing to the left / start
				for($index = $this->_position - 1; $index >= 0; $index--) {
					if(in_array($ids[$index], $ids_not_activated)) {
						$pos_index_left--;
					} else {
						break;
					}
				}
			
				if($pos_index_left >= 0) {
					$browse_left = $ids[$pos_index_left];
				}
				
				for($index = 0, $max_count = $this->_position - 1; $index <= $max_count; $index++) {
					if(in_array($ids[$index], $ids_not_activated)) {
						$pops_index_start++;
					} else {
						break;
					}
				}
				
				if($pos_index_left >= 0) {
					$browse_start = $ids[$pos_index_start];
				}
			}
			
			$browse_right = 0;		// 0 means: do not browse
			$browse_end = 0;		// 0 means: do not browse
			if($this->_position >= 0 && $this->_position < $count_all - 1) {
				// check for browsing to the right / end
				for($index = $this->_position + 1, $max_count = $count_all - 1; $index <= $max_count; $index++) {
					if(in_array($ids[$index], $ids_not_activated)) {
						$pos_index_right++;
					} else {
						break;
					}
				}
				
				if($pos_index_right < sizeof($ids)) {
					$browse_right = $ids[$pos_index_right];
				}
				
				for($index = $count_all - 1, $max_count = $this->_position + 1; $index >= $max_count; $index--) {
					if(in_array($ids[$index], $ids_not_activated)) {
						$pos_index_end--;
					} else {
						break;
					}
				}
				
				if($pos_index_right < sizeof($ids)) {
					$browse_end = $ids[$pos_index_end];
				}
			}
			
			// build return
			$return = array(
				'position'			=> $this->_position + 1,
				'count_all'			=> $count_all
			);
			
			return $return;
			/*


      // create HTML for browsing arrows to left
      $html = '<div style="float:right;">';
      if ( $browse_start > 0 ) {
         $image = '<span class="bold">&lt;&lt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_start;
         $params['pos'] = $pos_index_start;
         if (!empty($forward_type) and ($forward_type =='path' or $forward_type =='search')){
            $item = $item_manager->getItem($browse_start);
            $module = $item->getItemType();
            if ($module == 'label'){
               $label_manager = $this->_environment->getLabelManager();
               $label_item = $label_manager->getItem($item->getItemID());
               $module = $label_item->getLabelType();
            }
         }else{
            $module = $this->_module;
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(),$module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_START_DESC'),
                                   '','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&lt;&lt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_left > 0 ) {
         $image = '<span class="bold">&lt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray(); // $this->_parameter ???
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_left;
         if (!empty($forward_type) and ($forward_type =='path' or $forward_type =='search')){
            $item = $item_manager->getItem($browse_left);
            $module = $item->getItemType();
            if ($module == 'label'){
               $label_manager = $this->_environment->getLabelManager();
               $label_item = $label_manager->getItem($item->getItemID());
               $module = $label_item->getLabelType();
            }
         }else{
            $module = $this->_module;
         }
         //$params['pos'] = $pos-1;
         $params['pos'] = $pos_index_left;
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_LEFT_DESC'),
                                   '','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&lt;</span>'.LF;
      }
      $html .= '|';
      // Show position

      // create HTML for browsing arrows to left
      if ( $browse_right > 0 ) {
         $image = '<span class="bold">&gt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray(); // $this->_parameter ???
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_right;
         if (!empty($forward_type) and ($forward_type =='path' or $forward_type =='search' or $forward_type =='link_item')){
            $item = $item_manager->getItem($browse_right);
            $module = $item->getItemType();
            if ($module == 'label'){
               $label_manager = $this->_environment->getLabelManager();
               $label_item = $label_manager->getItem($item->getItemID());
               $module = $label_item->getLabelType();
            }
         }else{
            $module = $this->_module;
         }

         //$params['pos'] = $pos+1;
         $params['pos'] = $pos_index_right;
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $module, $this->_function,
                                   $params, $image, $this->_translator->getMessage('COMMON_BROWSE_RIGHT_DESC'),'','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&gt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_end > 0 ) {
         $image = '<span class="bold">&gt;&gt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray(); // $this->_parameter ???
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_end;
         if (!empty($forward_type) and ($forward_type =='path' or $forward_type =='search')){
            $item = $item_manager->getItem($browse_end);
            $module = $item->getItemType();
            if ($module == 'label'){
               $label_manager = $this->_environment->getLabelManager();
               $label_item = $label_manager->getItem($item->getItemID());
               $module = $label_item->getLabelType();
            }
         }else{
            $module = $this->_module;
         }
         $params['pos'] = $pos_index_end;
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_END_DESC'),'','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&gt;&gt;</span>'.LF;
      }
      $html .= '</div>';
      $html .= '<div id="right_box_page_numbers">';
      if (!empty($forward_type) and $forward_type =='path'){
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_PATH_ENTRIES').' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_PATH_ENTRIES').' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
      }elseif(!empty($forward_type) and $forward_type =='search'){
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_SEARCH_ENTRIES').' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_SEARCH_ENTRIES').' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
      }elseif(!empty($forward_type) and $forward_type =='link_item'){
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_REFERENCED_ENTRIES').' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_REFERENCED_ENTRIES').' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
       }else{
         switch ( mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') ){
            case 'ANNOUNCEMENT':
               $text = $this->_translator->getMessage('COMMON_ANNOUNCEMENT');
               break;
            case 'DATE':
               $text = $this->_translator->getMessage('COMMON_DATE');
               break;
            case 'DISCUSSION':
               $text = $this->_translator->getMessage('COMMON_DISCUSSION');
               break;
            case 'GROUP':
               $text = $this->_translator->getMessage('COMMON_GROUP');
               break;
            case 'INSTITUTION':
               $text = $this->_translator->getMessage('COMMON_INSTITUTION');
               break;
            case 'MATERIAL':
               $text = $this->_translator->getMessage('COMMON_MATERIAL');
               break;
            case 'MATERIAL_ADMIN':
               $text = $this->_translator->getMessage('COMMON_MATERIAL');
               break;
            case 'PROJECT':
               $text = $this->_translator->getMessage('COMMON_PROJECT');
               break;
            case 'TODO':
               $text = $this->_translator->getMessage('COMMON_TODO');
               break;
            case 'TOPIC':
               $text = $this->_translator->getMessage('COMMON_TOPIC');
               break;
            case 'USER':
               $text = $this->_translator->getMessage('COMMON_USER');
               break;
            case 'MYROOM':
               $text = $this->_translator->getMessage('COMMON_ROOM');
               break;
            case 'ACCOUNT':
               $text = $this->_translator->getMessage('COMMON_ACCOUNTS');
            break;            default:
               $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' '.__FILE__.'('.__LINE__.') ' );
               break;
         }
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$text.' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$text.' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
      }
      $html .= '';
      $html .= '</div>';
*/
//      return /*$this->_text_as_html_short(*/$html/*)*/;
		}
		
		private function getBrowseIDs() {
			if(sizeof($this->_browse_ids) === 0) {
				// set it
				$this->setBrowseIDs();
				
				if(!isset($this->_browse_ids) || sizeof($this->_browse_ids) === 0) {
					$this->_browse_ids[] = $this->_item->getItemID();
				}
			}
			return $this->_browse_ids;
		}
		
		private function markRead() {
			// mark as read
			$reader_manager = $this->_environment->getReaderManager();
			$reader = $reader_manager->getLatestReader($this->_item->getItemID());
			if(empty($reader) || $reader['read_date'] < $this->_item->getModificationDate()) {
				$reader_manager->markRead($this->_item->getItemID(), 0);
			}
		}
		
		private function markNoticed() {
			// mark as noticed
			$noticed_manager = $this->_environment->getNoticedManager();
			$noticed = $noticed_manager->getLatestNoticed($this->_item->getItemID());
			if(empty($noticed) || $noticed['read_date'] < $this->_item->getModificationDate()) {
				$noticed_manager->markNoticed($this->_item->getItemID(), 0);
			}
		}
		
		abstract protected function setBrowseIDs();
		
		abstract protected function getDetailContent();
	}