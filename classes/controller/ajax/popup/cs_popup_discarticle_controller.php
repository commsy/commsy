<?php
class cs_popup_discarticle_controller {
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
	
	public function getHTML() {
	
	}
	
	public function edit($item_id) {
		$discarticle_manager = $this->_environment->getDiscussionArticleManager();
		$discarticle_item = $discarticle_manager->getItem($item_id);
		
		// TODO: check rights
		
		$this->_popup_controller->assign('item', 'title', $discarticle_item->getTitle());
		$this->_popup_controller->assign('item', 'description', $discarticle_item->getDescription());
	}
	
	public function create($form_data) {
		$current_user = $this->_environment->getCurrentUserItem();
		$current_context = $this->_environment->getCurrentContextItem();
		
		$current_iid = $form_data['iid'];
		
		$discarticle_manager = $this->_environment->getDiscussionArticleManager();
		$discarticle_item = $discarticle_manager->getItem($current_iid);
		
		// check access rights
		if($current_context->isProjectRoom() && $current_context->isClosed()) {
			/*
			 * $params = array();
			   $params['environment'] = $environment;
			   $params['with_modifying_actions'] = true;
			   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
			   unset($params);
			   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
			   $page->add($errorbox);
			 */
		} elseif($current_iid !== 'NEW' && !isset($discarticle_item)) {
			/*
			 * $params = array();
			   $params['environment'] = $environment;
			   $params['with_modifying_actions'] = true;
			   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
			   unset($params);
			   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
			   $page->add($errorbox);
			 */
		} elseif(	!(($current_iid === 'NEW' && $current_user->isUser()) ||
					($current_iid !== 'NEW' && isset($discarticle_item) && $discarticle_item->mayEdit($current_user)))) {
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
		
		// access granted
		else {
			// save item
			if($this->_popup_controller->checkFormData()) {
				// set modificator and modification date
				$discarticle_item->setModificatorItem($current_user);
				$discarticle_item->setModificationDate(getCurrentDateTimeInMySQL());
				
				// set attributes
				if(isset($form_data['title'])) $discarticle_item->setSubject($form_data['title']);
				if(isset($form_data['description'])) $discarticle_item->setDescription($form_data['description']);
				
				// save item
				$discarticle_item->save();
				
				$this->_return = 'success';
			}
		}
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
	
	public function assignTemplateVars() {
		$current_user = $this->_environment->getCurrentUserItem();
		$current_context = $this->_environment->getCurrentContextItem();
		
		// general information
		$general_information = array();
		
		// max upload size
		$val = ini_get('upload_max_filesize');
		$val = trim($val);
		$last = $val[mb_strlen($val) - 1];
		switch($last) {
			case 'k':
			case 'K':
				$val *= 1024;
				break;
			case 'm':
			case 'M':
				$val *= 1048576;
				break;
		}
		$meg_val = round($val / 1048576);
		$general_information['max_upload_size'] = $meg_val;
		
		$this->_popup_controller->assign('popup', 'general', $general_information);
		
		// user information
		$user_information = array();
		$user_information['fullname'] = $current_user->getFullName();
		$this->_popup_controller->assign('popup', 'user', $user_information);
		
		
		// config information
		$config_information = array();
		$config_information['with_activating'] = $current_context->withActivatingContent();
		$this->_popup_controller->assign('popup', 'config', $config_information);
	}
}