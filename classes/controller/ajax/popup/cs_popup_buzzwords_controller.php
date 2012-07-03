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
		//$this->assignTemplateVars();
	}
	
	public function save($form_data, $additional = array()) {
		
	}
}