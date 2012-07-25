<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_project_detail_controller extends cs_detail_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {

			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'project_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_PROJECT_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionDetail() {
			$session = $this->_environment->getSessionItem();

			// try to set the item
			$this->setItem();

			$this->setupInformation();

			// used to signal which "creator ifnos" of annotations are expanded...
			$creatorInfoStatus = array();
			if(!empty($_GET['creator_info_max'])) {
				$creatorInfoStatus = explode('-', $_GET['creator_info_max']);
			}

			// TODO: implement deletion handling
			//include_once('include/inc_delete_entry.php');

			// check for item type
			$item_manager = $this->_environment->getItemManager();
			$type = $item_manager->getItemType($_GET['iid']);
			if($type !== CS_PROJECT_TYPE) {
				throw new cs_detail_item_type_exception('wrong item type', 0);
			} else {
				$current_context = $this->_environment->getCurrentContextItem();
				$current_user = $this->_environment->getCurrentUser();

				/*
				$params = array();
			   $params['environment'] = $environment;
			   $params['with_modifying_actions'] = $current_context->isOpen();
			   $params['creator_info_status'] = $creatorInfoStatus;
			   $detail_view = $class_factory->getClass(ANNOUNCEMENT_DETAIL_VIEW,$params);
			   unset($params);
			    */

				// check if item exists
				if($this->_item === null) {
					include_once('functions/error_functions.php');
      				trigger_error('Item ' . $_GET['iid'] . ' does not exist!', E_USER_ERROR);
				}

				// check if item is deleted
				elseif($this->_item->isDeleted()) {
					throw new cs_detail_item_type_exception('item deleted', 1);
				}

				// check for access rights
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
				} else {
					// mark as read and noticed
					$this->markRead();
					$this->markNoticed();

					$project_ids = array();
					if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_project_index_ids')) {
						$project_ids = $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_project_index_ids');
					}

					$this->assign('detail', 'content', $this->getDetailContent());
				}
			}
		}

		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/

		protected function getAdditionalActions(&$perms) {

		}

		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();

			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_project_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_project_index_ids'));
			}
		}



		private function getFormalData() {
			$return = array();
			$translator = $this->_environment->getTranslationObject();
			$converter = $this->_environment->getTextConverter();
			$context_item = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();

			$formal_data = array();

#		    $temp_array = array();
#			$temp_array[0] = $translator->getMessage('ANNOUNCEMENT_SHOW_HOME_DATE');
#			$temp_array[1] = getDateTimeInLang($this->_item->getSeconddateTime());
#			$return[] = $temp_array;

			return $return;
		}


		protected function getDetailContent() {
			$translator = $this->_environment->getTranslationObject();
			$converter = $this->_environment->getTextConverter();
			$context_item = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
			$desc = $this->_item->getDescription();

			$item = $this->_item;
			$room_user_status = 'closed';
		    $user_manager = $this->_environment->getUserManager();
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
			         // Eintritt erlaubt
		         if ($may_enter) {
		            $room_user_status = 'open';
		            $room_user_status_text = '';
		         } elseif ( $item->isLocked() ) {
		            $room_user_status = 'closed';
		            $room_user_status_text = $translator->getMessage('CONTEXT_JOIN');
		         } elseif(!empty($room_user) and $room_user->isRequested()) {
		            $room_user_status = 'requested';
		            $room_user_status_text = $translator->getMessage('ACCOUNT_NOT_ACCEPTED_YET');
			         //Erlaubnis verweigert
		         } elseif(!empty($room_user) and $room_user->isRejected()) {
		            $room_user_status = 'rejected';
		            $room_user_status_text = $translator->getMessage('ACCOUNT_NOT_ACCEPTED');
			         // noch nicht angemeldet als Mitglied im Raum
		         } else {
		            if ( $item->isOpen() and !$current_user->isOnlyReadUser() ) {
		            } else {
		            	$room_user_status = 'guest';
		            	$room_user_status_text = $translator->getMessage('ACCOUNT_NOT_ACCEPTED_AS_GUEST');
		            }
		         }

	         $moda = array();
	         $moda_list = $this->_item->getContactModeratorList();
	         $moda_item = $moda_list->getFirst();
	         while ($moda_item) {
	            $moda_item_here = $moda_item->getRelatedUserItemInContext($this->_environment->getCurrentContextID());
	            $current_user_item = $this->_environment->getCurrentUserItem();
	            $tmp_array = array();
	            if ( $current_user_item->isGuest()
	                 and isset($moda_item_here)
	                 and $moda_item_here->isVisibleForLoggedIn()
	               ) {
	               $tmp_array['name'] = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
	               $tmp_array['iid'] = 'none';
	            } else {
	               $tmp_array['name'] = $moda_item->getFullName();
	               $tmp_array['iid'] = $moda_item->getItemID();
	            }
	            $moda[] = $tmp_array;
	            unset($current_user_item);
	            $moda_item = $moda_list->getNext();
	         }

			$fullname = '';
			$creator = $this->_item->getCreatorItem();
			if (isset($creator)){
			   $fullname = $creator->getFullName();
			}
			return array(
				'item_id'				=> $this->_item->getItemID(),
				'formal'				=> $this->getFormalData(),
				'title'					=> $this->_item->getTitle(),
				'creator'				=> $fullname,
				'creation_date'			=> getDateTimeInLang($this->_item->getCreationDate()),
				'description'			=> $desc,
				'room_user_status'		=> $room_user_status,
				'room_user_status_text'	=> $room_user_status_text,
				'moderator_array'		=> $moda,
				'moredetails'			=> $this->getCreatorInformationAsArray($this->_item)
			);
		}
	}