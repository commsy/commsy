<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_accounts_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}

		public function actionUpdateLinkedItem() {
			$return = array();

			// get request data
			$item_id = $this->_data['item_id'];
			$link_id = $this->_data['link_id'];
			$checked = $this->_data['checked'];

			

			$return['success'] = true;

			echo json_encode($return);
		}

		public function actionPerformRequest() {
			$return = array();

			$user_manager = $this->_environment->getUserManager();
			$translator = $this->_environment->getTranslationObject();

			// get request data
			$current_page = $this->_data['current_page'];
			$restrictions = $this->_data['restrictions'];
			
			// get data from db
			$user_manager->reset();
			$user_manager->setContextLimit($this->_environment->getCurrentContextID());
			$count_all = $user_manager->getCountAll();
			
			// set restrictions
			/*
			 * if ( !empty($sort) ) {
			      $user_manager->setSortOrder($sort);
			   }
			   
			   if ( !empty($sel_auth_source)
			        and  $sel_auth_source != -1
			      ) {
			      $user_manager->setAuthSourceLimit($sel_auth_source);
			   }
			 */
			
			if(!empty($restrictions['search'])) $user_manager->setSearchLimit($restrictions['search']);
			
			if(!empty($restrictions['status'])) {
				if($restrictions['status'] == '10') $user_manager->setContactModeratorLimit();
				else $user_manager->setStatusLimit($restrictions['status']);
			}
			
			$ids = $user_manager->getIDArray();
			$count_all_shown = count($ids);
			
			$interval = CS_LIST_INTERVAL;
			$from = $current_page * $interval;
			
			$user_manager->setIntervalLimit($from, $interval);
			$user_manager->select();
			
			// get user list
			$user_list = $user_manager->get();
			
			// prepare return
			$return['list'] = array();
			$item = $user_list->getFirst();
			while($item) {
				$entry = array();
				
				$status = '';
				if($item->isModerator()) $status = $translator->getMessage('USER_STATUS_MODERATOR');
				elseif($item->isUser()) $status = $translator->getMessage('USER_STATUS_USER');
				elseif($item->isRequested()) $status = $translator->getMessage('USER_STATUS_REQUESTED');
				else {
					$status = $translator->getMessage('USER_STATUS_REJECTED');
				}
				
				if($item->isContact()) $status .= ' [' . $translator->getMessage('USER_STATUS_CONTACT_SHORT') . ']';
				
				$entry['item_id']			= $item->getItemID();
				$entry['fullname']			= $item->getFullName();
				$entry['email']				= $item->getEmail();
				$entry['status']			= $status;
			
				$entry['checked'] = false;
			
				$return['list'][] = $entry;
				$item = $user_list->getNext();
			}
			$return['paging']['pages'] = ceil($count_all/ $interval);

			$return['success'] = true;

			echo json_encode($return);
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// check rights
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
				
			$error = false;
			if(isset($current_context) && !$current_context->isOpen() && !$current_context->isTemplate()) {
				// room is closed
				$error = true;
			} elseif(!$current_user->isModerator()) {
				// not allowed
				$error = true;
			}
				
			if($error === true) exit;
			
			// call parent
			parent::process();
		}
	}