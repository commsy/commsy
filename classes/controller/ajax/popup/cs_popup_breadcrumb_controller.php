<?php
class cs_popup_breadcrumb_controller {
	private $_environment = null;
	private $_popup_controller = null;
	private $_return = '';
	
	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}
	
	public function edit($item_id) {

	}
	
	public function create($form_data) {
		
	}
	
	public function getReturn() {
		return $this->_return;
	}
	
	public function getFieldInformation() {
		return array(
			array(	'name'		=> 'title',
					'type'		=> 'text',
					'mandatory' => true),
			array(	'name'		=> 'description',
					'type'		=> 'text',
					'mandatory'	=> false)
		);
	}
	
	private function getBreadcrumbInformation() {
		$return = array();
			
		$current_user = $this->_environment->getCurrentUserItem();
		$portal_item = $this->_environment->getCurrentPortalItem();
		$current_context = $this->_environment->getCurrentContextItem();
			
		// server
		if($current_user->isRoot()) {
			$server_item = $this->_environment->getServerItem();
			$return[] = array(
					'id'	=> $server_item->getItemID(),
					'title'	=> $server_item->getTitle()
			);
		}
			
		// portal
		$return[] = array(
				'id'	=> $portal_item->getItemID(),
				'title'	=> $portal_item->getTitle()
		);
			
		// community
		if($this->_environment->inProjectRoom()) {
			$community_list = $current_context->getCommunityList();
			$community_item = $community_list->getFirst();
			if(!empty($community_item)) {
				$return[] = array(
						'id'	=> $community_item->getItemID(),
						'title'	=> $community_item->getTitle()
				);
			}
				
			// group groom
		} elseif($this->_environment->inGroupRoom()) {
			$project_item = $current_context->getLinkedProjectItem();
			$community_list = $project_item->getCommunityList();
			$community_item = $community_list->getFirst();
			if(!empty($community_item)) {
				$return[] = array(
						'id'	=> $community_item->getItemID(),
						'title'	=> $community_item->getTitle()
				);
			}
	
			// project
			$return[] = array(
					'id'	=> $project_item->getItemID(),
					'title'	=> $project_item->getTitle()
			);
		}
			
		// room
		$return[] = array(
				'id'	=> $current_context->getItemID(),
				'title'	=> $current_context->getTitle()
		);
			
		return $return;
	}
	
	public function assignTemplateVars() {
		$translator = $this->_environment->getTranslationObject();
		
		// breadcrumb information
		$breadcrumb_information = array();
		$this->_popup_controller->assign('popup', 'breadcrumb', $this->getBreadcrumbInformation());
	}
	
	private function cleanup_session($current_iid) {
		$environment = $this->_environment;
		$session = $this->_environment->getSessionItem();

		$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
		$session->unsetValue($environment->getCurrentModule().'_add_tags');
		$session->unsetValue($environment->getCurrentModule().'_add_files');
		$session->unsetValue($current_iid.'_post_vars');
	}
}