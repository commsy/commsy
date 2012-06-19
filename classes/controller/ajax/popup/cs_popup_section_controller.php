<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_section_controller implements cs_rubric_popup_controller {

    private $_environment = null;
    private $_popup_controller = null;
    private $_sections = array();

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
        $material_item = $material_manager->getItem($material_ref_id);

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
                $info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
                $info['file_icon']	= $file->getFileIcon();
                $info['file_id']	= $file->getFileID();

                $attachment_infos[] = $info;
                $file = $file_list->getNext();
            }
            $this->_popup_controller->assign('item', 'files', $attachment_infos);
            $this->_popup_controller->assign('item', 'title', $item->getTitle());
            $this->_popup_controller->assign('item', 'description', $item->getDescription());
        }else{
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