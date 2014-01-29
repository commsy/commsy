<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_topic_detail_controller extends cs_detail_controller {

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'topic_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_TOPIC_TYPE);
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
			$context_item = $this->_environment->getCurrentContextItem();

			//include_once('include/inc_delete_entry.php');

			$translator = $this->_environment->getTranslationObject();

			$type = $this->_item->getItemType();
			if($type !== CS_TOPIC_TYPE) {
				throw new cs_detail_item_type_exception('wrong item type', 0);
			} else {
				/*
				 * $mode='browse';
				   if ( isset($_GET['mode']) and $_GET['mode']=='print'){
				      $mode = 'print';
				   }
				 */

				// used to signal which "creator infos" of annotations are expanded...
				//TODO
				$creatorInfoStatus = array();
				if(!empty($_GET['creator_info_max'])) {
					$creatorInfoStatus = explode('-', $_GET['creator_info_max']);
				}



				/*
				 *


   // initialize objects
   $current_context = $environment->getCurrentContextItem();
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $params['creator_info_status'] = $creatorInfoStatus;
   $detail_view = $class_factory->getClass(TOPIC_DETAIL_VIEW,$params);
   unset($params);
   if ($mode=='print'){
      $detail_view->setPrintableView();
   }



   */
				// check for deletion
				if($this->_item->isDeleted()) {
					throw new cs_detail_item_type_exception('item deleted', 1);
				}

				// check for visibility
				elseif(!$this->_item->maySee($current_user)) {
					//TODO: implement error handling
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
					/*
					 * // Enter or leave Topic
			      if (!empty($_GET['topic_option'])) {
			         $current_user = $environment->getCurrentUser();
			         if ($_GET['topic_option']=='1') {
			            $topic_item->addMember($current_user);
			         } else if ($_GET['topic_option']=='2') {
			            $topic_item->removeMember($current_user);
			         }
			      }

			      $detail_view->setItem($topic_item);
			      */

					// mark as read and noticed
					$this->markRead();
					$this->markNoticed();

					/*

			      // set up browsing
			      if ( $environment->inCommunityRoom() ) {
			         $ids = $topic_item->getLinkedItemIDArray(CS_USER_TYPE);
			         $session->setValue('cid'.$context_item->getItemID().'_contact_index_ids', $ids);
			      }else{
			         $ids = $topic_item->getLinkedItemIDArray(CS_USER_TYPE);
			         $session->setValue('cid'.$context_item->getItemID().'_user_index_ids', $ids);
			      }

			      if ($session->issetValue('cid'.$context_item->getItemID().'_topic_index_ids')) {
			         $topic_ids = $session->getValue('cid'.$context_item->getItemID().'_topic_index_ids');
			      } else {
			         $topic_ids = array();
			      }
			      $detail_view->setBrowseIDs($topic_ids);

			      $context_item = $environment->getCurrentContextItem();
			      $current_room_modules = $context_item->getHomeConf();
			      if ( !empty($current_room_modules) ){
			         $room_modules = explode(',',$current_room_modules);
			      } else {
			         $room_modules = array();
			      }
			      $first = array();
			      $secon = array();
			      foreach ( $room_modules as $module ) {
			         $link_name = explode('_', $module);
			         if ( $link_name[1] != 'none'
			              and $link_name[0] != $_GET['mod']
			              and $link_name[0] != CS_USER_TYPE) {
			            switch ($detail_view->_is_perspective($link_name[0])) {
			               case true:
			                  $first[] = $link_name[0];
			               break;
			               case false:
			                  $second[] = $link_name[0];
			               break;
			            }
			         }
			      }
			      $room_modules = array_merge($first,$second);
			      $rubric_connections = array();
			      foreach ($room_modules as $module){
			         if ($context_item->withRubric($module) ) {
			            $ids = $topic_item->getLinkedItemIDArray($module);
			            $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
			            $rubric_connections[] = $module;
			         }
			      }
			      $detail_view->setRubricConnections($rubric_connections);

			      $annotations = $topic_item->getAnnotationList();
			            $id_array = array();
			            $annotation = $annotations->getFirst();
			            while($annotation){
			               $id_array[] = $annotation->getItemID();
			               $annotation = $annotations->getNext();
			            }
			            $reader_manager->getLatestReaderByIDArray($id_array);
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

			      // Safe information in session for later use
			      $session->setValue('cid'.$context_item->getItemID().'_topic_index_ids', $topic_ids);
					 */
					$this->assign('detail', 'content', $this->getDetailContent());
				}
			}
		}

		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/

		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();

			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_topic_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_topic_index_ids'));
			}
		}

		protected function getAdditionalActions(&$perms) {
		}

		protected function getDetailContent() {
			$converter = $this->_environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();

			$return = array();
			$return['title'] = $this->_item->getTitle();
			$return['show_picture'] = false;
			$return['moredetails'] = $this->getCreatorInformationAsArray($this->_item);

            // description
			$desc = $this->_item->getDescription();
			if(!empty($desc)) {
				$converter->setFileArray($this->getItemFileList());
				
				$desc = $converter->textFullHTMLFormatting($desc);
			}

            $path_shown = false;
            $path_items = array();
        	$item_list = $this->_item->getPathItemList();

        	if(!$item_list->isEmpty()) {
        		$path_shown = true;

        		$linked_item = $item_list->getFirst();
        		while($linked_item) {
        			$entry = array();
        			$entry['iid'] = $linked_item->getItemID();

        			$mod = Type2Module($linked_item->getItemType());
        			$type = $linked_item->getItemType();
        			if($type === 'date') {
        				$type .= 's';
        			}

        			$temp_type = mb_strtoupper($type, 'UTF-8');
        			switch($temp_type) {
        				case 'ANNOUNCEMENT':
        					$type = $translator->getMessage('COMMON_ANNOUNCEMENT');
        					break;
        				case 'DATES':
        					$type = $translator->getMessage('COMMON_DATES');
        					break;
        				case 'DISCUSSION':
        					$type = $translator->getMessage('COMMON_DISCUSSION');
        					break;
        				case 'GROUP':
        					$type = $translator->getMessage('COMMON_GROUP');
        					break;
        				case 'INSTITUTION':
        					$type = $translator->getMessage('COMMON_INSTITUTION');
        					break;
        				case 'MATERIAL':
        					$type = $translator->getMessage('COMMON_MATERIAL');
        					break;
        				case 'PROJECT':
        					$type = $translator->getMessage('COMMON_PROJECT');
        					break;
        				case 'TODO':
        					$type = $translator->getMessage('COMMON_TODO');
        					break;
        				case 'TOPIC':
        					$type = $translator->getMessage('COMMON_TOPIC');
        					break;
        				case 'USER':
        					$type = $translator->getMessage('COMMON_USER');
        					break;
        				default:
        					$type = $translator->getMessage('COMMON_MESSAGETAG_ERROR');
        					break;
        			}

        			if(isset($user) and $linked_item->isNotActivated() && !($linked_item->getCreatorID() === $user->getItemID() || $user->isModerator())) {
        				$activatring_date = $linked_item->getActivatingDate();
        				if(strstr($activating_date, '9999-00-00')) {
        					$link_creator_text = $translator->getMessage('COMMON_NOT_ACTIVATED');
        				} else {
        					$link_creator_text = $translator->getMessage('COMMON_ACTIVATING_DATE') . ' ' . getDateInLang($linked_item->getActivatingDate());
        				}

        				$entry['title'] = $linked_item->getTitle();
        				$entry['link_text'] = $link_creator_text;
        				$entry['not_activated'] = true;
        			} else {
        				$entry['title'] = $linked_item->getTitle();
        				$entry['type'] = $type;
        				$entry['mod'] = $mod;
        				$entry['not_activated'] = false;
        			}

        			$path_items[] = $entry;

        			$linked_item = $item_list->getNext();
        		}
        	}
		    if ($this->_item->isNotActivated()){
		        $activating_date = $this->_item->getActivatingDate();
		        $text = '';
		        if (strstr($activating_date,'9999-00-00')){
		           $activating_text = $this->_environment->getTranslationObject()->getMessage('COMMON_NOT_ACTIVATED');
		        }else{
		           $activating_text = $this->_environment->getTranslationObject()->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($this->_item->getActivatingDate());
		        }
				$temp_array = array();
				$temp_array[] = $this->_environment->getTranslationObject()->getMessage('COMMON_RIGHTS');
				$temp_array[] = $activating_text;
				$formal[] = $temp_array;
		    }
		    $temp_array = array();

			$return = array(
				'formal'		=> $formal,
				'title'			=> $this->_item->getTitle(),
				'files'			=> $this->getFileContent(),
				'description'	=> $desc,
				'item_id'		=> $this->_item->getItemID(),
				'path_shown'	=> $path_shown,
				'path_items'	=> $path_items,
				'moredetails'	=> $this->getCreatorInformationAsArray($this->_item)
			);

			return $return;
		}
	}