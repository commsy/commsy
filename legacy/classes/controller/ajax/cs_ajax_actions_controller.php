<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_actions_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionAddToClipboard() {
			$session = $this->_environment->getSessionItem();
			$item_manager = $this->_environment->getItemManager();
			
			$itemId = $this->_data['itemId'];
			
			$copyRubricArray = $this->getUtils()->getCopyRubrics();
			
			// get type of item
			$type = $item_manager->getItemType($itemId);
			if($type === CS_LABEL_TYPE) {
				$this->setErrorReturn("104", "can't copy labels", array("item_id" => $itemId));
				
			} elseif(!in_array($type, $copyRubricArray)) {
				$this->setErrorReturn("106", "can't copy this item - not allowed", array("item_id" => $itemId));
				
			} else {
				// get current clipboard content
				$clipboardIdArray = array();
				if($session->issetValue($type . "_clipboard")) {
					$clipboardIdArray = $session->getValue($type . "_clipboard");
				}
				
				// if not already set, add id to clipboard
				if(!in_array($itemId, $clipboardIdArray)) {
					$clipboardIdArray[] = $itemId;
					$session->setValue($type . "_clipboard", $clipboardIdArray);
					
					$this->_environment->getSessionManager()->save($session);
					$this->setSuccessfullDataReturn(array());
				} else {
					$this->setErrorReturn("105", "item was already added to clipboard", array("item_id" => $itemId));
				}
			}
			
			echo $this->_return;
		}

		public function actionVersionMakeNew() {
		   $material_manager = $this->_environment->getMaterialManager();
		   $latest_version_item = $material_manager->getItem($this->_data['itemId']);
		   $old_version_item = $material_manager->getItemByVersion($this->_data['itemId'], $this->_data['versionID']);
		   $clone_item = $old_version_item->cloneCopy(true);
		   $latest_version_id = $latest_version_item->getVersionID();
		   $clone_item->setVersionID($latest_version_id+1);
		   $clone_item->save();
		   $old_version_item->delete();
		   $this->setSuccessfullDataReturn(array());
		   echo $this->_return;
		}
		
		public function actionExportToWordpress() {
		   $item_manager = $this->_environment->getItemManager();
		   $temp_item = $item_manager->getItem($this->_data['itemId']);
		   
		   $wordpress_manager = $this->_environment->getWordpressManager();
		   $wordpress_manager->exportItemToWordpress($this->_data['itemId'],$temp_item->getItemType());
		   $this->setSuccessfullDataReturn(array());
		   echo $this->_return;
		}
		
		public function actionExportToWiki() {
		   $item_manager = $this->_environment->getItemManager();
		   $temp_item = $item_manager->getItem($this->_data['itemId']);
		   
		   $wiki_manager = $this->_environment->getWikiManager();
		   $wiki_manager->exportItemToWiki($this->_data['itemId'],$temp_item->getItemType());
		   $this->setSuccessfullDataReturn(array());
		   echo $this->_return;
		}
		
		public function actionSendXHRErrorReporting() {
			$error = $this->_data["error"];
			$ioArgs = $this->_data["ioargs"];
			
			global $c_xhr_error_reporting;
			if (isset($c_xhr_error_reporting) && !empty($c_xhr_error_reporting)) {
				$currentUser = $this->_environment->getCurrentUserItem();
				$browserInfo = get_browser(null, true);
				
				// setup mail
				$receivers = implode(", ", $c_xhr_error_reporting);
				$subject = "CommSy XHR Error";
				
				$message = "
					Fehler
					========================
					Servername: " . $_SERVER['SERVER_NAME'] . "
					" . $error["message"] . "
					Beschreibung: " . $error["description"] . "
					
					
					Benutzer
					========================
					UserID: " . $currentUser->getUserID() . "
					ItemID: " . $currentUser->getItemID() . "
					Agent: " . $_SERVER["HTTP_USER_AGENT"] . "
					
					
					Aufruf
					========================
					URL: " . $ioArgs["args"]["url"] . "
					PostData: " . $ioArgs["args"]["postData"] . "
				";
				
				mail($receivers, $subject, $message);
			}
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}
		
		public function actionImportDate()
		{
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}