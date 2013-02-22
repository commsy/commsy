<?php
require_once('classes/controller/cs_ajax_controller.php');

class cs_ajax_detail_popup_controller extends cs_ajax_controller {
	private $_itemId = null;
	private $_item = null;
    private $_contextId = null;
    private $_versionId = null;
    private $_module = null;

    /**
     * constructor
     */
    public function __construct(cs_environment $environment) {
        // call parent
		parent::__construct($environment);
    }
    
    public function actiongetHTML() {
    	$current_user = $this->_environment->getCurrentUser();
    
      // archive
      $toggle_archive = false;
      if ( $this->_environment->isArchiveMode() ) {
         $toggle_archive = true;
         $this->_environment->deactivateArchiveMode();
      }
      // archive
    	
      // get data
    	$this->_itemId = $this->_data['iid'];
    	$this->_contextId = $this->_data["contextId"];
    	
    	if ( isset($this->_data["version_id"]) && !empty($this->_data["version_id"]) )
    	{
    		$this->_versionId = $this->_data["version_id"];
    	}
    	else
    	{
    		$this->_versionId = null;
    	}
    	
    	// get the item and set the module(should be of type material, discussion, date or todo - so no label check is needed)
    	if ($this->_itemId !== null && $this->_itemId !== "NEW") {
    		$itemManager = $this->_environment->getItemManager();
    		$this->_module = $itemManager->getItemType($this->_itemId);
    		$manager = $this->_environment->getManager($this->_module);
    		
    		if ($this->_module === CS_MATERIAL_TYPE) {
    			if ($this->_versionId !== null) {
    				$this->_item = $manager->getItemByVersion($this->_itemId, $this->versionId);
    			} else {
    				$this->_item = $manager->getItem($this->_itemId);
    			}
    		} else {
    			$this->_item = $manager->getItem($this->_itemId);
    		}
    		
    		if ($this->_item !== null) {
    			$controller = $this->invokeDetailController();
    			
    			// determ the template file
    			if ($this->_module === CS_DISCUSSION_TYPE) {
    				$controller->_tpl_file = "popups/discussion" . (($this->_item->getDiscussionType() === "threaded") ? "_detail_threaded_popup" : "_detail_popup");
    			} else {
    				$controller->_tpl_file = "popups/" . $this->_module . "_detail_popup";
    			}
    			
    			$controller->assign("popup", "overflow", true);
    			
    			// smarty	
    			global $c_smarty;
    			if($c_smarty === true) {
    				ob_start();
    			
    				$controller->displayTemplate();
    			
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
    	}
    	
    	/*

		// include
		require_once('classes/controller/ajax/popup/cs_popup_' . $module . '_controller.php');
		$class_name = 'cs_popup_' . $module . '_controller';
		$this->_popup_controller = new $class_name($this->_environment, $this);

		// initPopup
		$this->initPopup();

		
		
		
		$this->assign('popup', 'item_id', $this->_itemId);
		
		*/
      // archive
      if ( $toggle_archive ) {
         $this->_environment->activateArchiveMode();
      }
      // archive
    }
    
    private function invokeDetailController() {
    	$currentUser = $this->_environment->getCurrentUserItem();
    	
    	// get context item
    	$itemManager = $this->_environment->getItemManager();
    	$item = $itemManager->getItem($this->_contextId);
    	$type = $item->getItemType();
    	$manager = $this->_environment->getManager($type);
    	$contextItem = $manager->getItem($this->_contextId);
    	
    	//$privateRoomItem = $currentUser->getOwnRoom();
    	//$privateRoomContextID = $privateRoomItem->getItemID();
    	
    	// get the detail controller
    	$controller_name = "cs_" . $this->_module . "_detail_controller";
    	require_once("classes/controller/detail/" . $controller_name . ".php");
    	 
    	// we need to create a new environment to hide the fact, that we are inside a popup
    	$fakeEnvironment = clone $this->_environment;
    	$fakeEnvironment->unsetAllInstancesExceptTranslator();
    	$fakeEnvironment->setCurrentContextID($this->_contextId);
    	$fakeEnvironment->setCurrentModule($this->_module);
    	$fakeEnvironment->setCurrentFunction("detail");
    	
    	$fakeEnvironment->setCurrentContextItem($contextItem);
    	$fakeEnvironment->setCurrentUserItem($currentUser->getRelatedPrivateRoomUserItem());
    	
    	$smarty = new cs_smarty($fakeEnvironment, "default");
    	$fakeEnvironment->setTemplateEngine($smarty);
    	
    	// override $_GET - ugly I know
    	$_GET["iid"] = $this->_itemId;
    	$_GET["fromPortfolio"] = $this->_data["fromPortfolio"];
    	
    	if ( $this->_versionId !== null )
    	{
    		$_GET["version_id"] = $this->_versionId;
    	}
    	
    	// init controller and process the detail content
    	$controller = new $controller_name($fakeEnvironment);
    	//$controller->setTemplateEngine($smarty);//$this->_environment->getTemplateEngine());
    	$controller->processTemplate();
    	
    	return $controller;
    }
    
    /*
     * every derived class needs to implement an processTemplate function
    */
    public function process() {
    	// call parent
    	parent::process();
    
    }
}