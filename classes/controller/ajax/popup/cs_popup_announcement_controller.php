<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_announcement_controller implements cs_rubric_popup_controller {
    private $_environment = null;
    private $_popup_controller = null;
    private $_return = '';

    /**
     * constructor
     */
    public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
        $this->_environment = $environment;
        $this->_popup_controller = $popup_controller;
    }

    public function initPopup($item) {
        $this->assignTemplateVars();
    }

    public function save($form_data, $additional = array()) {
        $environment = $this->_environment;
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        $current_iid = $form_data['iid'];

        $translator = $this->_environment->getTranslationObject();

        if($current_iid === 'NEW') {
            $announcement_item = null;
        } else {
            $announcement_manager = $this->_environment->getAnnouncementManager();
            $announcement_item = $announcement_manager->getItem($current_iid);
        }

        if ( $current_iid != 'NEW' and !isset($announcement_item) ) {

        } elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
        ($current_iid != 'NEW' and isset($announcement_item) and
        $announcement_item->mayEdit($current_user))) ) {

        } else { //Acces granted
            // Find out what to do
            /*            if ( isset($_POST['option']) ) {
             $command = $_POST['option'];
             }elseif ( isset($_GET['option']) ) {
             $command = $_GET['option'];
             } else {
             $command = '';
             } */

            // Cancel editing
            /*  if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
             cleanup_session($current_iid);
             $this->_environment->getSessionItem()->unsetValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_buzzword_ids');
             $this->_environment->getSessionItem()->unsetValue('buzzword_post_vars');
             $this->_environment->getSessionItem()->unsetValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_tag_ids');
             $this->_environment->getSessionItem()->unsetValue('tag_post_vars');
             $this->_environment->getSessionItem()->unsetValue('cid'.$this->_environment->getCurrentContextID().'_linked_items_index_selected_ids');
             $this->_environment->getSessionItem()->unsetValue('linked_items_post_vars');
             if ( $current_iid == 'NEW' ) {
             redirect($this->_environment->getCurrentContextID(), CS_ANNOUNCEMENT_TYPE, 'index', '');
             } else {
             $params = array();
             $params['iid'] = $current_iid;
             redirect($this->_environment->getCurrentContextID(), CS_ANNOUNCEMENT_TYPE, 'detail', $params);
             }
             }
             // Show form and/or save item
             else {
             // Save item
             if ( !empty($command) and
             (isOption($command, $translator->getMessage('ANNOUNCEMENT_SAVE_BUTTON'))
             or isOption($command, $translator->getMessage('ANNOUNCEMENT_CHANGE_BUTTON'))) ) { */

            if ($this->_popup_controller->checkFormData()) {
                $session = $this->_environment->getSessionItem();
                $item_is_new = false;
                // Create new item
                if ( !isset($announcement_item) ) {
                    $announcement_manager = $environment->getAnnouncementManager();
                    $announcement_item = $announcement_manager->getNewItem();
                    $announcement_item->setContextID($environment->getCurrentContextID());
                    $current_user = $environment->getCurrentUserItem();
                    $announcement_item->setCreatorItem($current_user);
                    $announcement_item->setCreationDate(getCurrentDateTimeInMySQL());
                    $item_is_new = true;
                }

                // Set modificator and modification date
                $current_user = $environment->getCurrentUserItem();
                $announcement_item->setModificatorItem($current_user);

                // Set attributes
                if ( isset($form_data['title']) ) {
                    $announcement_item->setTitle($form_data['title']);
                }
                if ( isset($form_data['description']) ) {
                    $announcement_item->setDescription($form_data['description']);
                }
                if (isset($form_data['dayEnd'])) {
                    $date2 = convertDateFromInput($form_data['dayEnd'],$environment->getSelectedLanguage());
                    if (!empty($form_data['timeEnd'])) {
                        $time_end = $form_data['timeEnd'];
                    } else {
                        $time_end = '22:00';
                    }
                    //
                    if (!mb_ereg("(([2][0-3])|([01][0-9])):([0-5][0-9])",$time_end)) { //test if end_time is in a valid timeformat
                        $time_end='22:00';
                    }
                    $time2 = convertTimeFromInput($time_end);   // convertTimeFromInput

                    if ($date2['conforms'] == TRUE and $time2['conforms'] == TRUE) {
                        $announcement_item->setSecondDateTime($date2['datetime']. ' '.$time2['datetime']);
                    } else {
                        $announcement_item->setSecondDateTime($date2['display']. ' '.$time2['display']);
                    }
                }
                if (isset($form_data['public'])) {
                    $announcement_item->setPublic($form_data['public']);
                }
                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids')){
                    $announcement_item->setBuzzwordListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids'));
                    $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
                }
                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids')){
                    $announcement_item->setTagListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids'));
                    $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
                }
                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
                    $announcement_item->setLinkedItemsByIDArray(array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')));
                    $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
                }

                // files
                /*$item_files_upload_to = $announcement_item;
                 include_once('include/inc_fileupload_edit_page_save_item.php');

                 if ( isset($form_data['public']) ) {
                 if ( $announcement_item->isPublic() != $form_data['public'] ) {
                 $announcement_item->setPublic($form_data['public']);
                 }
                 } else {
                 if ( isset($form_data['private_editing']) ) {
                 $announcement_item->setPrivateEditing('0');
                 } else {
                 $announcement_item->setPrivateEditing('1');
                 }
                 } */
                // files
                $file_ids = $form_data['files'];
                $this->_popup_controller->getUtils()->setFilesForItem($announcement_item, $file_ids);


                if ( isset($form_data['hide']) ) {
                    // variables for datetime-format of end and beginning
                    $dt_hiding_time = '00:00:00';
                    $dt_hiding_date = '9999-00-00';
                    $dt_hiding_datetime = '';
                    $converted_day_start = convertDateFromInput($form_data['dayStart'],$environment->getSelectedLanguage());
                    if ($converted_day_start['conforms'] == TRUE) {
                        $dt_hiding_datetime = $converted_day_start['datetime'].' ';
                        $converted_time_start = convertTimeFromInput($form_data['timeStart']);
                        if ($converted_time_start['conforms'] == TRUE) {
                            $dt_hiding_datetime .= $converted_time_start['datetime'];
                        }else{
                            $dt_hiding_datetime .= $dt_hiding_time;
                        }
                    }else{
                        $dt_hiding_datetime = $dt_hiding_date.' '.$dt_hiding_time;
                    }
                    $announcement_item->setModificationDate($dt_hiding_datetime);
                }else{
                    if($announcement_item->isNotActivated()){
                        $announcement_item->setModificationDate(getCurrentDateTimeInMySQL());
                    }
                }



                // Save item
                $announcement_item->save();
                $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
                $session->unsetValue('buzzword_post_vars');
                $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
                $session->unsetValue('tag_post_vars');
                $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
                $session->unsetValue('linked_items_post_vars');
                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids')){
                    $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids'));
                }else{
                    $id_array =  array();
                }
                if ($item_is_new){
                    $id_array[] = $announcement_item->getItemID();
                    $id_array = array_reverse($id_array);
                    $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids',$id_array);
                }

                //Add modifier to all users who ever edited this item
                $manager = $environment->getLinkModifierItemManager();
                $manager->markEdited($announcement_item->getItemID());

                // Redirect
                /*cleanup_session($current_iid);
                 $params = array();
                 $params['iid'] = $announcement_item->getItemID();
                 redirect($environment->getCurrentContextID(), CS_ANNOUNCEMENT_TYPE, 'detail', $params);
                 */
                $this->_return = $announcement_item->getItemID();
            }
        }
    }


    public function isOption( $option, $string ) {
        return (strcmp( $option, $string ) == 0) || (strcmp( htmlentities($option, ENT_NOQUOTES, 'UTF-8'), $string ) == 0 || (strcmp( $option, htmlentities($string, ENT_NOQUOTES, 'UTF-8') )) == 0 );
    }

    public function getReturn() {
        return $this->_return;
    }

    private function assignTemplateVars() {
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        // general information
        $general_information = array();

        // max upload size
        $val = ini_get('upload_max_filesize');
        $val = trim($val);
        $last = $val[mb_strlen($val) - 1];
        switch($last) {
            case 'k':
            case 'K':
                $val *= 1024;
                break;
            case 'm':
            case 'M':
                $val *= 1048576;
                break;
        }
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

    private function cleanup_session ($current_iid) {
        $this->_environment->getSessionItem()->unsetValue($this->_environment->getCurrentModule().'_add_files');
        $this->_environment->getSessionItem()->unsetValue($current_iid.'_post_vars');
    }

    public function getFieldInformation($sub = '') {
        return array();
    }

}