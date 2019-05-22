<?php

require_once('classes/controller/ajax/popup/cs_popup_controller.php');
require_once ('classes/cs_mail.php');

class cs_popup_userParticipation_controller implements cs_popup_controller {
	private $_receiver_array = null;
	private $_environment = null;
	private $_popup_controller = null;

	/**
	 * constructor
	 */
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}

	public function getFieldInformation() {
		return array(
		);
	}

	public function save($form_data, $additional = array()) {
		switch ($additional['action']){
		   case 'room_lock':
		      $current_user = $this->_environment->getCurrentUserItem();
		      $current_user->reject();
		      $current_user->save();
		      // set return
		      $this->_popup_controller->setSuccessfullItemIDReturn($this->_environment->getCurrentPortalID());
		      break;
		   
		   case 'room_delete':
		      $current_user = $this->_environment->getCurrentUserItem();
		      $current_user->delete();
		      // set return
		      $this->_popup_controller->setSuccessfullItemIDReturn($this->_environment->getCurrentPortalID());
	          break;
	      
	       case 'portal_lock':
	          $current_user = $this->_environment->getCurrentUserItem();
	          $portal_user_item = $current_user->getRelatedCommSyUserItem();
	          $portal_user_item->reject();
	          $portal_user_item->save();
	          // delete session
	          $session_manager = $this->_environment->getSessionManager();
	          $session = $this->_environment->getSessionItem();
	          $session_manager->delete($session->getSessionID());
	          $this->_environment->setSessionItem(null);
	          $this->_popup_controller->setSuccessfullItemIDReturn($this->_environment->getCurrentPortalID());
              break;
         
           case 'portal_delete':
              $current_user = $this->_environment->getCurrentUserItem();
              $authentication = $this->_environment->getAuthenticationObject();
              $authentication->delete($current_user->getItemID());
              // delete session
              $session_manager = $this->_environment->getSessionManager();
              $session = $this->_environment->getSessionItem();
              $session_manager->delete($session->getSessionID());
              $this->_environment->setSessionItem(null);
              $this->_popup_controller->setSuccessfullItemIDReturn($this->_environment->getCurrentPortalID());
              break;
		}
	}

	public function initPopup($data) {
		$current_user = $this->_environment->getCurrentUserItem();
		$context_item = $this->_environment->getCurrentContextItem();
		$portal_item = $this->_environment->getCurrentPortalItem();

		// user information
		$user_information = array();
		$user_information['item_id'] = $current_user->getItemID();
		$this->_popup_controller->assign('popup', 'user', $user_information);

		$context_information = array();
		$context_information['room_id'] = $context_item->getItemID();
		$context_information['room_title'] = $context_item->getTitle();
		$this->_popup_controller->assign('popup', 'room', $context_information);
		
		$portal_information = array();
		$portal_information['portal_id'] = $portal_item->getItemID();
		$portal_information['portal_title'] = $portal_item->getTitle();
		$this->_popup_controller->assign('popup', 'portal', $portal_information);

	   // datenschutz: overwrite or not (04.09.2012 IJ)
	   $datenschutz = array();
		$datenschutz['overwrite'] = true;
		global $symfonyContainer;
		$disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
		if ( !empty($disable_overwrite) and $disable_overwrite === 'TRUE' ) {
			$datenschutz['overwrite'] = false;
		}
		$this->_popup_controller->assign('popup', 'datenschutz', $datenschutz);
	}
}