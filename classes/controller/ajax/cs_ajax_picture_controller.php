<?php
require_once('classes/controller/cs_ajax_controller.php');

class cs_ajax_picture_controller extends cs_ajax_controller {
	/**
	 * constructor
	 */
	public function __construct(cs_environment $environment) {
		// call parent
		parent::__construct($environment);
	}
	
	/*
	 * every derived class needs to implement an processTemplate function
	 */
	public function process() {
		// TODO: check for rights, see cs_ajax_accounts_controller

		// call parent
		parent::process();
	}
	
	/*
	 * 
	 */
	public function actionGetMaterialPictures(){
		$return = array();
		
		$item_id = $this->_data['item_id'];
		
		#pr($this->_data);
		
		$material_manager = $this->_environment->getMaterialManager();
		$material_item = $material_manager->getItem($item_id);
		if(!empty($material_item)){
			$file_list = $material_item->getFileList();
			
			$file = $file_list->getFirst();
			while($file){
				if($file->getMime() == 'image/jpeg' || $file->getMime() == 'image/gif' || $file->getMime() == 'image/png'){
					$file_array = array();
					$file_array['name'] = $file->getFilename();
					$file_array['url'] = $file->getUrl();
					$return[] = $file_array;
				}
				$file = $file_list->getNext();
			}
		}
		$this->setSuccessfullDataReturn($return);
		echo $this->_return;
	}
}
?>