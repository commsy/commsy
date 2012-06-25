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
        $this->_material_item = $material_manager->getItem($material_ref_id);
        

        $section_list = $this->_material_item->getSectionList();
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
                $info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
                $info['file_icon']	= $file->getFileIcon();
                $info['file_id']	= $file->getFileID();

                $attachment_infos[] = $info;
                $file = $file_list->getNext();
            }
            $this->_popup_controller->assign('item', 'files', $attachment_infos);
            $this->_popup_controller->assign('item', 'title', $item->getTitle());
            $this->_popup_controller->assign('item', 'description', $item->getDescription());
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
    }

    public function save($form_data, $additional = array()) {
        
        $environment = $this->_environment;
        
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
            $section_item->setDescription($form_data['description']);
        }
        //TODO: Nummer auslesen (weil Eintragsordnung per drag & drop veränderbar)
        if (isset($form_data['number'])) {
            $section_item->setNumber($form_data['number']);
        }
        if (isset($this->_material_item) ) {
            $section_item->setLinkedItemID($this->_material_item->getItemID());
        }

        // Set links to connected rubrics
        if ( isset($form_data[CS_MATERIAL_TYPE]) ) {
            $section_item->setMaterialListByID($form_data[CS_MATERIAL_TYPE]);
        } else {
            $section_item->setMaterialListByID(array());
        }

        // Update the material regarding the latest section informations...
        // (this takes care of saving the section itself, too)
        $user = $environment->getCurrentUserItem();
        //TODO: php erzeugt mit jedem aufruf die Klassen neu und daher nützt es nichts sich das 
        //      material_item beim aufrufen des popups zu merken...
        $this->_material_item->setModificatorItem($user);
        if (!$this->_material_item->isNotActivated()){
            $this->_material_item->setModificationDate($section_item->getModificationDate());
        }else{
            $this->_material_item->setModificationDate($this->_material_item->getModificationDate());
        }
        $section_list = $this->_material_item->getSectionList();

        // files
        $item_files_upload_to = $section_item;
        include_once('include/inc_fileupload_edit_page_save_item.php');

        $section_list->set($section_item);
        $this->_material_item->setSectionList($section_list);
        $this->_material_item->setSectionSaveID($section_item->getItemId());

        $external_view_array = $this->_material_item->getExternalViewerArray();
        $this->_material_item->setExternalViewerAccounts($external_view_array);

        $this->_material_item->save();

        // redirect
        /*
        cleanup_session($current_iid);
        $params = array();
        $params['iid'] = $material_ref_iid;
        if (!empty($infoBox_forAutoNewVersion)) {
            $params['autoVersion'] = 'true';
        }
        redirect($environment->getCurrentContextID(), 'material', 'detail', $params,'anchor'.$section_item->getItemID());
        */
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