<?php

require_once('classes/controller/ajax/popup/cs_popup_controller.php');

class cs_popup_rss_controller implements cs_popup_controller {
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
			array('name'		=> 'feeds',
				  'type'		=> 'array',
				  'mandatory'	=> true),
			array('name'		=> 'feedsName',
				  'type'		=> 'array',
				  'mandatory'	=> true),
			array('name'		=> 'feedsAddress',
				  'type'		=> 'array',
				  'mandatory'	=> true)
		);
	}

	public function save($form_data, $additional = array()) {
		$feeds = $form_data["feeds"];
		$feedsName = $form_data["feedsName"];
		$feedsAddress = $form_data["feedsAddress"];
		
		$currentUser = $this->_environment->getCurrentUserItem();
		$privateRoomItem = $currentUser->getOwnRoom();
		
		// build new rss array
		$rssFeeds = array();
		
		foreach ($feedsAddress as $index => $feedAddress) {
			$rssFeeds[] = array(
				"title"		=> $feedsName[$index],
				"adress"	=> $feedAddress,
				"display"	=> (in_array("feed_" . $index, $feeds)) ? "1" : "0"
			);
		}
		$privateRoomItem->setPortletRSSArray($rssFeeds);
		$privateRoomItem->save();
		
		$this->_popup_controller->setSuccessfullDataReturn(array());
	}

	public function initPopup($data) {
		$currentUser = $this->_environment->getCurrentUserItem();
		$privateRoomItem = $currentUser->getOwnRoom();
			
		$rssArray = $privateRoomItem->getPortletRSSArray();
		
		$feeds = array();
		foreach ($rssArray as $rssItem) {
			$feeds[] = $rssItem;
		}
		
		$this->_popup_controller->assign("popup", "feeds", $feeds);
	}
}