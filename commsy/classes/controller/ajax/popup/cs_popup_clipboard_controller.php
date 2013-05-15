<?php
require_once('classes/controller/ajax/popup/cs_popup_controller.php');

class cs_popup_clipboard_controller implements cs_popup_controller {
	private $_environment = null;
	private $_popup_controller = null;

	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}

	public function save($form_data, $additional = array()) {
		
	}

	public function initPopup($data) {
	   $this->_popup_controller->assign('popup', 'archive', array('status' => $this->_environment->isArchiveMode()));
	   $this->_popup_controller->assign('popup', 'environment', array('inPortal' => $this->_environment->inPortal()));
	}

	public function getFieldInformation($sub) {
		$return = array(
			
		);

		return $return;
	}
}