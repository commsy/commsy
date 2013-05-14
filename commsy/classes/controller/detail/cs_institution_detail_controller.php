<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_institution_detail_controller extends cs_detail_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'institution_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_INSTITUTION_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionDetail() {
			$session = $this->_environment->getSessionItem();

			// try to set the item
			$this->setItem();

			$this->setupInformation();

			//include_once('include/inc_delete_entry.php');

			// check for item type
			if($this->_item->getItemType() !== CS_INSTITUTION_TYPE) {
				throw new cs_detail_item_type_exception('wrong item type', 0);
			} else {
				// init
				$current_user = $this->_environment->getCurrentUserItem();
				$context_item = $this->_environment->getCurrentContextItem();

				// used to signal which "creator infos" of annotations are expanded...
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
				   $detail_view = $class_factory->getClass(INSTITUTION_DETAIL_VIEW,$params);
				   unset($params);
				   */

				// check for deleted
				if($this->_item->isDeleted()) {
					throw new cs_detail_item_type_exception('item deleted', 1);
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
				} else {
					// enter or leave institution
					if(!empty($_GET['institution_option'])) {
						if($_GET['institution_option'] === '1') {
							$this->_item->addMember($current_user);
						} elseif($_GET['institution_option'] === '2') {
							$this->_item->removeMember($current_user);
						}
					}

					// mark as read and noticed
					$this->markRead();
					$this->markNoticed();

					// set rubric connections
					$current_room_modules = $context_item->getHomeConf();
					$room_modules = explode(',', $current_room_modules);

					$first = array();
					$second = array();
					foreach($room_modules as $module) {
						list($name, $view) = explode('_', $module);

						if($view != 'none' && $name != $_GET['mod'] && $name != CS_USER_TYPE) {
							switch($this->isPerspective($name)) {
								case true:
									$first[] = $name;
									break;
								case false:
									$second[] = $name;
									break;
							}
						}
					}

					$room_modules = array_merge($first, $second);
					$rubric_connections = array();
					foreach($room_modules as $module) {
						if($context_item->withRubric($module)) {
							$ids = $this->_item->getLinkedItemIDArray($module);
							$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_' . $module . '_index_ids', $ids);
							$rubric_connections[] = $module;
						}
					}

					$this->setRubricConnections($rubric_connections);
				}
			}

			$this->assign('detail', 'content', $this->getDetailContent());
		}

		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/

		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();

			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_institution_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_institution_index_ids'));
			}
		}

		protected function getAdditionalActions(&$return) {
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
			$is_in_institution = $this->_item->isMember($current_user);
			if ($is_in_institution) {
				$return['member'] = 'member';
			} else {
				$return['member'] = 'no_member';
			}
			return $return;
		}

		protected function getDetailContent() {
			$converter = $this->_environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();
			$user = $this->_environment->getCurrentUser();

			$return = array();

			// title
			$return['title'] = $this->_item->getTitle();

			// moredetails
			$return['moredetails'] = $this->getCreatorInformationAsArray($this->_item);

			// picture
			$picture = $this->_item->getPicture();
			if(!empty($picture)) {
				$disc_manager = $this->_environment->getDiscManager();
				if($disc_manager->existsFile($picture)) {
					list($pict_width) = getimagesize($disc_manager->getFilePath() . $picture);
					if($pict_width < 150) {
						$width = $pict_width;
					}
					$return['show_picture'] = true;
					$return['picture'] = $picture;
				}
			}

			// description
			$desc = $this->_item->getDescription();
			if(!empty($desc)) {
				$desc = $converter->textFullHTMLFormatting($desc);
				//$desc = $converter->cleanDataFromTextArea($desc);
				//TODO:
				//$desc = $converter->compareWithSearchText($desc);
				$converter->setFileArray($this->getItemFileList());
      		if ( $this->_with_old_text_formating ) {
      			$desc = $converter->textFullHTMLFormatting($desc);
      		}
				$return['description'] = $desc;
				//$html .= $this->getScrollableContent($desc,$item,'',true).LF;
			}

			$members = $this->_item->getMemberItemList();
			$count_member = $members->getCount();
			if(!$members->isEmpty()) {
				$member = $members->getFirst();

				while($member) {
					$member_info = array();

					if($member->isUser()) {
						$firtsname = $member->getFirstname();
						// TODO:
						//$linktext = $converter->compareWithSearchText($linktext);
						$linktext = $converter->text_as_html_short($linktext);

						$member_title = $member->getTitle();
						// TODO:
						//$member_title = $converter->compareWithSearchText($member_title);
						$member_title = $converter->text_as_html_short($member_title);

						if(!empty($member_title)) {
							$linktext .= ', ' . $member_title;
						}
						$member_info['linktext'] = $linktext;
						$member_info['firstname'] = $converter->text_as_html_short($member->getFirstname());
						$member_info['lastname'] = $converter->text_as_html_short($member->getLastname());
						$member_info['title'] = $converter->text_as_html_short($member->getTitle());
						$member_info['picture'] =$member->getPicture();
						$member_info['iid'] = $member->getItemID();
						$return['members'][] = $member_info;
					}


					unset($member);
					$member = $members->getNext();
				}
			}
			return $return;
		}
	}