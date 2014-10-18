<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_user_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'user_list';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_USER_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/

		/**
		 * INDEX
		 */
		public function actionIndex() {
			// init list params
			$this->initListParameters(CS_USER_TYPE);

			// perform list options
			$this->performListOption(CS_USER_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('user','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('user','list_content', $list_content);
		}

		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$return = array();

			$last_selected_tag = '';
			$seltag_array = array();

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
			$user_manager = $environment->getUserManager();
			$user_manager->reset();
			$user_manager->setContextLimit($environment->getCurrentContextID());
			$user_manager->setUserLimit();
			$count_all = $user_manager->getCountAll();

			if ( !empty($this->_list_parameter_arrray['ref_iid']) and $this->getViewMode() == 'attached' ){
   				$user_manager->setRefIDLimit($this->_list_parameter_arrray['ref_iid']);
			}
			if ( !empty($this->_list_parameter_arrray['sort']) ) {
   				$user_manager->setSortOrder($this->_list_parameter_arrray['sort']);
			}
			if ( !empty($this->_list_parameter_arrray['search']) ) {
   				$user_manager->setSearchLimit($this->_list_parameter_arrray['search']);
			}
			if ( !empty($this->_list_parameter_arrray['selgroup']) ) {
   				$user_manager->setGroupLimit($this->_list_parameter_arrray['selgroup']);
			}
			if ( !empty($this->_list_parameter_arrray['seltopic']) ) {
   				$user_manager->setTopicLimit($this->_list_parameter_arrray['seltopic']);
			}

			// Find current status selection
			if(isset($_GET['selstatus']) && $_GET['selstatus'] != 2 && $_GET['selstatus'] != '-2') {
				$selstatus = $_GET['selstatus'];
			} else {
				$selstatus = '';
			}

			if(!empty($selstatus)) {
				if($selstatus == 11) {
					$user_manager->setUserInProjectLimit();
				} elseif($selstatus == 12) {
					$user_manager->setcontactMOderatorInProjectLimit();
				} else {
					$user_manager->setStatusLimit($selstatus);
				}
			}

			if($environment->inCommunityRoom()) {
				$current_user = $environment->getCurrentUser();
				if($current_user->isUser()) {
					$user_manager->setVisibleToAllAndCommsy();
				} else {
					$user_manager->setVisibleToAll();
				}
			}

			if ( $this->_list_parameter_arrray['interval'] > 0 ) {
   				$user_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
			}
			$user_manager->select();
			$list = $user_manager->get();
			$ids = $user_manager->getIDArray();
			$count_all_shown = count($ids);

			$this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
            $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

            $session = $this->_environment->getSessionItem();
            $session->setValue('cid' . $environment->getCurrentContextID() . '_user_index_ids', $ids);

			$id_array = array();
			$item = $list->getFirst();
			while ($item){
   				$id_array[] = $item->getItemID();
   				$item = $list->getNext();
			}
			$noticed_manager = $environment->getNoticedManager();
			$noticed_manager->getLatestNoticedByIDArray($id_array);
			$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);

			// prepare item array
			$item = $list->getFirst();
			$item_array = array();
			$params = array();
			$params['environment'] = $environment;
			$params['with_modifying_actions'] = false;
			$view = new cs_view($params);
			while($item) {
				$phone = $item->getTelephone();
				$handy = $item->getCellularphone();
				$mail = '';

				if(!empty($phone)) {
					//TODO:
					//$phone = $converter->compareWithSearchText($phone);
					$phone = $view->_text_as_html_short($phone);
				}

				if(!empty($handy)) {
					//TODO
					//$handy = $converter->compareWithSearchText($handy);
					$handy = $view->_text_as_html_short($handy);
				}

				if($item->isEmailVisible() || $this->_environment->getCurrentUser()->isModerator()) {
					$mail = $item->getEmail();
					//TODO:
					//$mail = $converter->compareWithSearchText($mail);
					$mail = $view->_text_as_html_short($mail);
				}

				$noticed_text = $this->_getItemChangeStatus($item);
				$item_array[] = array(
					'iid'				=> $item->getItemID(),
					'title'				=> $item->getFullname(),
					'phone'				=> $phone,
					'handy'				=> $handy,
					'mail'				=> $mail,
					'noticed'			=> $noticed_text
				);

				$item = $list->getNext();
			}

			// append return
			$return = array(
				'items'		=> $item_array,
				'count_all'	=> $count_all_shown
			);
			return $return;
		}

		protected function getAdditionalActions(&$perms) {
		}

		protected function getAdditionalListActions() {
			return array();
		}

		protected function getAdditionalRestrictionText(){
			$return = array();

			$params = $this->_environment->getCurrentParameterArray();
			if(isset($params['selstatus']) && !empty($params['selstatus']) && $params['selstatus'] === '3') {
				$return = array(
					'name'				=> '',
					'type'				=> '',
					'link_parameter'	=> ''
				);

				$translator = $this->_environment->getTranslationObject();

				// set name
				if($params['selstatus'] === '3') {
					$return['name'] = $translator->getMessage('USER_MODERATORS');
				} else {
					$return['name'] = $translator->getMessage('COMMON_USERS');
				}

				// set link parameter
				unset($params['selstatus']);
				$link_parameter_text = '';
				if ( count($params) > 0 ) {
					foreach ($params as $key => $parameter) {
						$link_parameter_text .= '&'.$key.'='.$parameter;
					}
				}
				$return['link_parameter'] = $link_parameter_text;
			}

			return array($return);
		}

		protected function getAdditionalRestrictions() {
			$return[0] = array(
				'item'		=> array(),
				'action'	=> '',
				'hidden'	=> array(),
				'tag'		=> '',
				'name'		=> '',
				'custom'	=> true
			);

			if(isset($_GET['selstatus']) && $_GET['selstatus'] != 2 && $_GET['selstatus'] != '-2') {
				$selstatus = $_GET['selstatus'];
			} else {
				$selstatus = '';
			}

			$translator = $this->_environment->getTranslationObject();

			// set tag and name
			$tag = $translator->getMessage('COMMON_STATUS');
			$return[0]['tag'] = $tag;
			$return[0]['name'] = 'status';

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
			$return[0]['action'] = 'commsy.php?cid='.$this->_environment->getCurrentContextID().'&mod='.$this->_environment->getCurrentModule().'&fct='.$this->_environment->getCurrentFunction().'&'.$link_parameter_text;

			// set hidden
			$return[0]['hidden'] = $hidden_array;

			// set items
			$items = array();

			// no selection
			$item = array(
				'id'		=> 2,
				'name'		=> $translator->getMessage('COMMON_NO_SELECTION'),
				'selected'	=> $selstatus
			);
			$items[] = $item;

			// moderators
			$item = array(
				'id'		=> 3,
				'name'		=> $translator->getMessage('USER_MODERATORS'),
				'selected'	=> $selstatus
			);
			$items[] = $item;

			$current_context = $this->_environment->getCurrentContextItem();
			if($current_context->isCommunityRoom()) {
				// disabled
				$item = array(
					'id'		=> -2,
					'name'		=> '------------------------------',
					'selected'	=> $selstatus,
					'disabled'	=> true
				);
				$items[] = $item;

				// project user
				$item = array(
					'id'		=> 11,
					'name'		=> $translator->getMessage('USER_PROJECT_USER'),
					'selected'	=> $selstatus
				);
				$items[] = $item;

				// project moderator
				$item = array(
					'id'		=> 12,
					'name'		=> $translator->getMessage('USER_PROJECT_CONTACT_MODERATOR'),
					'selected'	=> $selstatus
				);
				$items[] = $item;
			}

			$return[0]['items'] = $items;

			return $return;
		}
	}