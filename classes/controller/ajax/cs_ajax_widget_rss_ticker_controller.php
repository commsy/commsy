<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_widget_rss_ticker_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetRssFeeds() {
			$return = array(
				"feeds"		=> array()
			);
			
			$currentUser = $this->_environment->getCurrentUserItem();
			$privateRoomItem = $currentUser->getOwnRoom();
			
			$rssArray = $privateRoomItem->getPortletRSSArray();
			
			foreach ($rssArray as $rssItem) {
				$return["feeds"][] = $rssItem;
			}
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionGetFeed() {
			$address = $this->_data["address"];
			
			$doc = new DOMDocument();
			$doc->load($address);
			
			$feedArray = array();
			
			foreach($doc->getElementsByTagName("item") as $node) {
				$feedArray[] = array(
					"title"		=> $node->getElementsByTagName("title")->item(0)->nodeValue,
					"desc"		=> $node->getElementsByTagName("desc")->item(0)->nodeValue,
					"link"		=> $node->getElementsByTagName("link")->item(0)->nodeValue,
					"date"		=> $node->getElementsByTagName("pubDate")->item(0)->nodeValue,
				);
			}
			
			$this->setSuccessfullDataReturn($feedArray);
			echo $this->_return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// TODO: check for rights, see cs_ajax_accounts_controller
			
			// call parent
			parent::process();
		}
	}