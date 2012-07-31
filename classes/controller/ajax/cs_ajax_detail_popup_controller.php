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
    
    	// get data
    	$this->_itemId = $this->_data['iid'];
    	$this->_contextId = $this->_data["contextId"];
    	$this->_versionId = $this->_data["version_id"];
    	
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
    			$this->invokeDetailController();
    			
    			// determ the template file
    			$this->_tpl_file = "popups/" . $this->_module . "_detail_popup";
    			
    			// smarty
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
    }
    
    private function invokeDetailController() {
    	$currentUser = $this->_environment->getCurrentUserItem();
    	$privateRoomContextID = $currentUser->getOwnRoom()->getItemID();
    	
    	// get the detail controller
    	$controller_name = "cs_" . $this->_module . "_detail_controller";
    	require_once("classes/controller/detail/" . $controller_name . ".php");
    	 
    	// we need to create a new environment to hide the fact, that we are inside a popup
    	$fakeEnvironment = clone $this->_environment;
    	$fakeEnvironment->setCurrentContextID($privateRoomContextID);
    	$fakeEnvironment->setCurrentModule($this->_module);
    	$fakeEnvironment->setCurrentFunction("detail");
    	
    	$fakeEnvironment->setCurrentUserItem($currentUser->getRelatedPrivateRoomUserItem());
    	
    	$smarty = new cs_smarty($fakeEnvironment, "default");
    	$fakeEnvironment->setTemplateEngine($smarty);
    	
    	// override $_GET - ugly I know
    	$_GET["iid"] = $this->_itemId;
    	$_GET["version_id"] = $this->_versionId;
    	
    	// init controller and process the detail content
    	$controller = new $controller_name($fakeEnvironment);
    	$controller->setTemplateEngine($this->_environment->getTemplateEngine());
    	$controller->actionDetail();
    }
    
    /*
     * every derived class needs to implement an processTemplate function
    */
    public function process() {
    	// call parent
    	parent::process();
    
    }
}