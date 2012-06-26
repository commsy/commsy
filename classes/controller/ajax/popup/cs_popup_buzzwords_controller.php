<?php
require_once('classes/controller/ajax/popup/cs_popup_controller.php');

class cs_popup_buzzwords_controller implements cs_popup_controller {
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
	
	public function initPopup($data) {
		// assign template vars
		$this->assignTemplateVars();
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
	
	private function assignTemplateVars() {
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