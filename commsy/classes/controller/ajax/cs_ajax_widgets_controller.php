<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_widgets_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetStackConfiguration() {
			$return = array();
			
			$currentUser = $this->_environment->getCurrentUserItem();
			$privateRoomItem = $currentUser->getOwnRoom();
			
			$displayConfig = $privateRoomItem->getMyEntriesDisplayConfig();
			
			// filter the list
			$filteredDisplayConfig = array();
			foreach ($displayConfig as $entry) {
				if (	!( ($entry == "my_tag_box" || $entry == "my_tag_box_preferences") && !$privateRoomItem->withTags() ) &&
						!( ($entry == "my_buzzword_box" || $entry == "my_buzzword_box_preferences") && !$privateRoomItem->withBuzzwords() ) &&
						!( $entry == "null" )
				) {
					
					$filteredDisplayConfig[] = $entry;
				}
			}
			
			// add non-configurable displays
			$filteredDisplayConfig[] = "my_search_box";
			$filteredDisplayConfig[] = "my_stack_box";
			$filteredDisplayConfig[] = "my_new";
			
			$return["displayConfig"] = $filteredDisplayConfig;
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionGetWidgetsConfiguration() {
			$return = array();
			
			$currentUser = $this->_environment->getCurrentUserItem();
			$privateRoomItem = $currentUser->getOwnRoom();
				
			$displayConfig = $privateRoomItem->getHomeConfig();
			
			foreach ($displayConfig as $row => $columnConfig) {
				foreach ($columnConfig as $entry) {
					/* all entries are named like "cs_privateroom_home_..._view" */
					
					// convert name
					$name = $entry;
					
					// remove "cs_privateroom_home_" and "_view"
					$name = mb_substr($name, 20);
					$name = mb_substr($name, 0, sizeof($name) - 6);
					
					// collapse main name
					$nameExplode = explode("_", $name);
					$collapsedMiddle = "";
					foreach ($nameExplode as $part) {
						$collapsedMiddle .= ucfirst($part);
					}
					
					$filteredDisplayConfig[] = "home_" . $collapsedMiddle . "_view";
				}
			}
				
			// add non-configurable displays
			//$filteredDisplayConfig[] = "my_search_box";
				
			$return["displayConfig"] = $filteredDisplayConfig;
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionGetHTMLForWidget() {
			$this->_tpl_file = $this->_data["widgetPath"];
			
			global $c_smarty;
			if($c_smarty === true) {
				ob_start();
					
				$this->displayTemplate();
					
				// setup return
				$output = ob_get_clean();
				$this->setSuccessfullDataReturn($output);
					
				//echo preg_replace('/\s/', '', $this->_return);
				//echo str_replace(array('\n', '\t'), '', $this->_return);		// for some reasons, categories in popup will not work if active
				echo $this->_return;
					
			} else {
				echo json_encode('smarty not enabled');
			}
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