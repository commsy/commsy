<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_tagPortfolio_controller implements cs_rubric_popup_controller {
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
	
	public function initPopup($item, $data) {
	}
	
	public function save($form_data, $additional = array()) {
		
	}
	
	public function getFieldInformation($sub = '') {}
	
	public function cleanup_session($iid) {}
}