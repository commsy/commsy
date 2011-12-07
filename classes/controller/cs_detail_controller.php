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
			$this->assign('detail', 'browsing_information', $this->getBrowseInformation());	
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
		
		private function getBrowseInformation() {
			// TODO: see cs_detail_view _getForwardBoxAsHTML() for more to migrate...
			$return = array();
			
			// update position from GET-Vars
			if(isset($_GET['pos'])) {
				$this->_position = $_GET['pos'];
			}
			
			$ids = $this->getBrowseIDs();
			
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
			$pos_index_left = $pos - 1;
			$pos_index_right = $pos + 1;
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
			if($pos >= 0 && $this->_position < $count_all - 1) {
				// check for browsing to the right / end
				for($index = $pos + 1, $max_count = $count_all - 1; $index <= $max_count; $index++) {
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
				
				if(!isset($this->_browse_ids) || sizeof($this->_browse_ids) == 0) {
					$this->_browse_ids[] = $this->_item->getItemID();
				}
			}
			return $this->_browse_ids;
		}
		
		abstract protected function setBrowseIDs();
		
		abstract protected function getDetailContent();
	}