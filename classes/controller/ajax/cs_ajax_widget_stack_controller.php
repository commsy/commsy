<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_widget_stack_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetListContent() {
			$return = array(
				"items"		=> array()
			);
			
			$currentUser = $this->_environment->getCurrentUserItem()->getRelatedPrivateRoomUserItem();
			$itemManager = $this->_environment->getItemManager();
			
			$privateRoomItem = $currentUser->getOwnRoom();
			
			$userIdArray = array($currentUser->getItemID());
			$privateRoomIdArray = array($privateRoomItem->getItemID());
			
			$itemManager->setOrderLimit(true);
			/*
			 * TODO:
			 * 
			if (!empty($sellist) and $sellist != 'new'){
				$item_manager->setListLimit($sellist);
			}
			if (!empty($selbuzzword)){
				$item_manager->setBuzzwordLimit($selbuzzword);
			}
			if (!empty($selmatrix)){
				$item_manager->setMatrixLimit($selmatrix);
			}
			if (!empty($searchtext)){
				$item_manager->setSearchLimit($searchtext);
			}
			if (!empty($last_selected_tag)){
				$item_manager->setTagLimit($last_selected_tag);
			}
			 */
			
			$entryList = $itemManager->getAllPrivateRoomEntriesOfUserList($privateRoomIdArray, $userIdArray);
			
			// ToDo: Nur Einträge in der Liste belassen, die auch angezeigt werden -> sonst gibt es leere Seiten über die geblättert wird!
			
			
			$rubricArray = array(CS_ANNOUNCEMENT_TYPE, CS_DISCUSSION_TYPE, CS_DATE_TYPE, CS_MATERIAL_TYPE, CS_TODO_TYPE);
			
			$filteredList = new cs_list();
			
			$entry = $entryList->getFirst();
			while ($entry) {
				if (in_array($entry->getItemType(), $rubricArray)) $filteredList->add($entry);
				
				$entry = $entryList->getNext();
			}
			
			/*
			$browse_prev = true;
			$browse_next = true;
			$temp_list = new cs_list();
			if($interval != 'all'){
				$temp_start = $pos * $interval;
				$temp_index = 0;
				$temp_item = $new_entry_list->getFirst();
				while($temp_item){
					if(($temp_index >= $temp_start) and ($temp_index < ($temp_start + $interval))){
						$temp_list->add($temp_item);
					}
					$temp_index++;
					$temp_item = $new_entry_list->getNext();
				}
				if($pos == 0){
					$browse_prev = false;
				}
				if(($temp_start + $interval) >= $temp_index){
					$browse_next = false;
				}
				if($temp_index % $interval == 0){
					$max_pos = ($temp_index / $interval) - 1;
				} else {
					$max_pos = (($temp_index - ($temp_index % $interval)) / $interval);
				}
			} else {
				$temp_list = $new_entry_list;
				$browse_prev = false;
				$browse_next = false;
				$pos = 0;
				$max_pos = 0;
			}
			*/
			
			// prepare return
			$entry = $filteredList->getFirst();
			while ($entry) {
				$type = $entry->getItemType();
				if ($type =='label'){
					$type = $entry->getLabelType();
				}
				$manager = $this->_environment->getManager($type);
				$entry = $manager->getItem($entry->getItemID());
				
				
				
				$return["items"][] = array(
					"itemId"		=> $entry->getItemID(),
					"contextId"		=> $entry->getContextID(),
					"module"		=> Type2Module($type),
					"title"			=> $entry->getTitle(),
					"image"			=> $this->getUtils()->getLogoInformationForType($type)
				);
			
				$entry = $filteredList->getNext();
			}
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionGetDetailContent() {
			$module = $this->_data["module"];
			$itemId = $this->_data["itemId"];
			
			$function = "detail";
			
			global $c_smarty;
			if(isset($c_smarty) && $c_smarty === true) {
				require_once('classes/cs_smarty.php');
				global $c_theme;
				if(!isset($c_theme) || empty($c_theme)) $c_theme = 'default';
			
				// room theme
				$color = $this->_environment->getCurrentContextItem()->getColorArray();
				$theme = $color['schema'];
			
				if($theme !== 'default') {
					$c_theme = $theme;
				}
			
				$smarty = new cs_smarty($this->_environment, $c_theme);
			
				global $c_smarty_caching;
				if(isset($c_smarty_caching) && $c_smarty_caching === true) {
					$smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
				}
				
				$smarty->assign("ajax", "onlyContent", true);
				
				// set smarty in environment
				$this->_environment->setTemplateEngine($smarty);
				
				$controller_name = "cs_" . $module . "_" . $function . "_controller";
				require_once("classes/controller/" . $function . "/" . $controller_name . ".php");
				
				$this->_environment->setCurrentFunction("detail");
				$_GET["iid"] = $itemId;
				$privateRoomContextID = $this->_environment->getCurrentUserItem()->getOwnRoom()->getItemID();
				$this->_environment->setCurrentContextID($privateRoomContextID);
				
				$controller = new $controller_name($this->_environment);
				
				$controller->processTemplate();
				
				ob_start();
				$controller->displayTemplate();
				
				$output = ob_get_clean();
				$this->setSuccessfullDataReturn($output);
				echo $this->_return;
			}
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