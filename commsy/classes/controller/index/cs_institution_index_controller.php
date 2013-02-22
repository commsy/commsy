<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_institution_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'institution_list';
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

		/**
		 * INDEX
		 */
		public function actionIndex() {
			// init list params
			$this->initListParameters(CS_INSTITUTION_TYPE);

			// perform list options
			$this->performListOption(CS_INSTITUTION_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('institution','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('institution','list_content', $list_content);
		}

		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$return = array();
			$translator = $environment->getTranslationObject();

			$last_selected_tag = '';
			$seltag_array = array();
			$institution_manager = $environment->getInstitutionManager();
			$institution_manager->resetData();
			$institution_manager->setContextLimit($context_item->getItemID());
			$count_all = $institution_manager->getCountAll();

			if(!empty($this->_list_parameter_arrray['sort'])) {
				$institution_manager->setSortOrder($this->_list_parameter_arrray['sort']);
			}
			$institution_manager->select();
			$list = $institution_manager->get();
			$ids = $institution_manager->getIDArray();
			$count_all_shown = count($ids);

			$this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
            $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

            $session = $this->_environment->getSessionItem();
            $session->setValue('cid'.$environment->getCurrentContextID().'_institution_index_ids', $ids);
			$converter = $environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();
			$id_array = array();
			$item = $list->getFirst();
			while ($item){
   				$id_array[] = $item->getItemID();
   				$item = $list->getNext();
			}
			$noticed_manager = $environment->getNoticedManager();
			$noticed_manager->getLatestNoticedByIDArray($id_array);
			$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);

			$step_manager = $environment->getStepManager();
			$step_list = $step_manager->getAllStepItemListByIDArray($id_array);
			$item = $step_list->getFirst();
			while ($item) {
			   $id_array[] = $item->getItemID();
			   $item = $step_list->getNext();
			}

			// prepare item array
			$item = $list->getFirst();
			$item_array = array();
			$params = array();
			$params['environment'] = $environment;
			$params['with_modifying_actions'] = false;
			$view = new cs_view($params);
			while($item) {
				$noticed_text = $this->_getItemChangeStatus($item);
				$item_array[] = array(
					'iid'				=> $item->getItemID(),
					'title'				=> $item->getTitle(),
					'modificator'		=> $this->getItemModificator($item),
					'noticed'			=> $noticed_text,
					'members_count'		=> $item->getMemberItemList()->getCount(),
					'linked_entries'	=> count($item->getAllLinkedItemIDArray())
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

			return $return;
		}

		protected function getAdditionalRestrictions() {
			$return = array();

			return $return;
		}
	}