	<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_section_controller implements cs_rubric_popup_controller {

    private $_environment = null;
    private $_popup_controller = null;
    private $_sections = array();
    private $_material_item = null;

    /**
     * constructor
     */
    public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
        $this->_environment = $environment;
        $this->_popup_controller = $popup_controller;
    }

    public function initPopup($item, $data) {
        $this->_sections = array();
        // assign template vars
        $this->assignTemplateVars();
        $current_context = $this->_environment->getCurrentContextItem();
        
        $material_ref_id = $data['ref_iid'];
        
        $material_manager = $this->_environment->getMaterialManager();
        
        if(isset($data['version_id'])){
           $material_item = $material_manager->getItemByVersion($material_ref_id, $data['version_id']);
        } else {
           $material_item = $material_manager->getItem($material_ref_id);
        }

        $section_list = $material_item->getSectionList();
        $section = $section_list->getFirst();
        while($section) {
            $this->_sections[] = $section;

            $section = $section_list->getNext();
        }

        $this->_popup_controller->assign('popup', 'sections', $this->_sections);

        if($item !== null) {
            // edit mode

            // TODO: check rights

            // files
            $attachment_infos = array();

            $converter = $this->_environment->getTextConverter();
            $file_list = $item->getFileList();

            $file = $file_list->getFirst();
            while($file) {
                #$info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
            	$info['file_name']	= $converter->filenameFormatting($file->getDisplayName());
            	$info['file_icon']	= $file->getFileIcon();
                $info['file_id']	= $file->getFileID();

                $attachment_infos[] = $info;
                $file = $file_list->getNext();
            }
            $this->_popup_controller->assign('item', 'files', $attachment_infos);
            $this->_popup_controller->assign('item', 'title', $item->getTitle());
            
			$this->_popup_controller->assign('item', 'description', $item->getDescription());
            
            $this->_popup_controller->assign('item', 'number', $item->getNumber());
        }
    }

    public function assignTemplateVars() {
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        // general information
        $general_information = array();

        // max upload size
        $val = $current_context->getMaxUploadSizeInBytes();
        $meg_val = round($val / 1048576);
        $general_information['max_upload_size'] = $meg_val;

        $this->_popup_controller->assign('popup', 'general', $general_information);

        // user information
        $user_information = array();
        $user_information['fullname'] = $current_user->getFullName();
        $this->_popup_controller->assign('popup', 'user', $user_information);


        // config information
        $config_information = array();
        $config_information['with_activating'] = $current_context->withActivatingContent();
        $this->_popup_controller->assign('popup', 'config', $config_information);
    }

    public function getFieldInformation($sub = '') {
    	#if ($this->_edit_type == 'normal'){
    	$return = array(
    			'general'	=> array(
    					array(	'name'		=> 'title',
    							'type'		=> 'text',
    							'mandatory' => true),
    					array(	'name'		=> 'description',
    							'type'		=> 'textarea',
    							'mandatory'	=> false)
    			)
    		);
    	return $return[$sub];
    #	}
    }

    public function save($form_data, $additional = array()) {
    	
    	
    	
    	
        $environment = $this->_environment;
        $user = $environment->getCurrentUserItem();
        $material_manager = $this->_environment->getMaterialManager();
        
        $current_iid = $form_data['iid'];
        if($current_iid === 'NEW') {
        	$section_item = null;
        } else {
        	$section_manager = $this->_environment->getSectionManager();
        	$section_item = $section_manager->getItem($current_iid);
        }
        
        $check_passed = $this->_popup_controller->checkFormData('general');
        
        if($check_passed === true){
        
	        $material_ref_id = $additional['ref_iid'];
	        
	        if(isset($additional['version_id'])){
	           $material_item = $material_manager->getItemByVersion($material_ref_id, $additional['version_id']);
	        } else {
	           $material_item = $material_manager->getItem($material_ref_id);
	        }
	        
	        // Create new item
	        if ( !isset($section_item) ) {
	            $section_manager = $environment->getSectionManager();
	            $section_item = $section_manager->getNewItem();
	            $section_item->setContextID($environment->getCurrentContextID());
	            $user = $environment->getCurrentUserItem();
	            $section_item->setCreatorItem($user);
	            $section_item->setCreationDate(getCurrentDateTimeInMySQL());
	        }
	
	        // new version? 
	        /*
	        if ((!empty($command) AND isOption($command,$translator->getMessage('MATERIAL_VERSION_BUTTON')))
	        or ($form_data['material_modification_date'] != $this->_material_item->getModificationDate())) {
	            $version = $this->_material_item->getVersionID()+1;
	            $this->_material_item->save();
	            $this->_material_item = $this->_material_item->cloneCopy();
	            $this->_material_item->setVersionID($version);
	            $infoBox_forAutoNewVersion = "&autoVersion=true";
	        } */
	
	        // Set modificator and modification date
	        $user = $environment->getCurrentUserItem();
	        $section_item->setModificatorItem($user);
	        $section_item->setModificationDate(getCurrentDateTimeInMySQL());
	
	        // Set attributes
	        if (isset($form_data['title'])) {
	            $section_item->setTitle($form_data['title']);
	        }
	        if (isset($form_data['description'])) {
	            $section_item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));
	        }
	        //TODO: Nummer auslesen (weil Eintragsordnung per drag & drop veränderbar)
	        if (isset($form_data['number'])) {
	            $section_item->setNumber($form_data['number']);
	        }
	        if (isset($material_item) ) {
	            $section_item->setLinkedItemID($material_item->getItemID());
	        }
	
	        // Set links to connected rubrics
	        if ( isset($form_data[CS_MATERIAL_TYPE]) ) {
	            $section_item->setMaterialListByID($form_data[CS_MATERIAL_TYPE]);
	        } else {
	            $section_item->setMaterialListByID(array());
	        }
	
	        // Update the material regarding the latest section informations...
	        // (this takes care of saving the section itself, too)
	        
	        $material_item->setModificatorItem($user);
	        if (!$material_item->isNotActivated()){
	            $material_item->setModificationDate($section_item->getModificationDate());
	        }else{
	            $material_item->setModificationDate($material_item->getModificationDate());
	        }
	        $section_list = $material_item->getSectionList();
	        
	        // already attached files
	        $file_ids = array();
	        foreach($form_data as $key => $value) {
	        	if(mb_substr($key, 0, 5) === 'file_') {
	        		$file_ids[] = $value;
	        	}
	        }
	        
	        // this will handle already attached files as well as adding new files
	        $this->_popup_controller->getUtils()->setFilesForItem($section_item, $file_ids, $form_data["files"]);
	
	        $section_list->set($section_item);
	        $material_item->setSectionList($section_list);
	        $material_item->setSectionSaveID($section_item->getItemId());
	
	        $external_view_array = $material_item->getExternalViewerArray();
	        $material_item->setExternalViewerAccounts($external_view_array);
	
	        $material_item->save();
	        
	        // set return
	        $this->_popup_controller->setSuccessfullItemIDReturn($material_item->getItemID());
        }
    }

    public function cleanup_session($current_iid) {

        $environment = $this->_environment;
        $session = $this->_environment->getSessionItem();

        $session->unsetValue($environment->getCurrentModule().'_add_files');
        $session->unsetValue($current_iid.'_post_vars');
        $session->unsetValue($current_iid.'_material_attach_ids');
        $session->unsetValue($current_iid.'_material_back_module');
    }
}