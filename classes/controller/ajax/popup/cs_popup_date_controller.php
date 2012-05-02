<?php
class cs_popup_date_controller {

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

        if ( $current_iid == 'NEW' ) {
            $dates_item = NULL;
        } else {
            $dates_manager = $environment->getDatesManager();
            $dates_item = $dates_manager->getItem($current_iid);
        }

        // Check access rights
        if ( $context_item->isProjectRoom() and $context_item->isClosed() ) {

        } elseif ( $current_iid != 'NEW' and !isset($dates_item) ) {

        }  elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
        ($current_iid != 'NEW' and isset($dates_item) and
        $dates_item->mayEdit($current_user))) ) {

        }

        // Access granted
        else {

             

            // Save item
            if ($this->_popup_controller->checkFormData()) {

                // Create new item
                $item_is_new = false;
                if ( !isset($dates_item) ) {
                    $dates_manager = $environment->getdatesManager();
                    $dates_item = $dates_manager->getNewItem();
                    $dates_item->setContextID($environment->getCurrentContextID());
                    $user = $environment->getCurrentUserItem();
                    $dates_item->setCreatorItem($user);
                    $dates_item->setCreationDate(getCurrentDateTimeInMySQL());
                    $item_is_new = true;
                }

                $values_before_change = array();
                $values_before_change['title'] = $dates_item->getTitle();
                $values_before_change['startingTime'] = $dates_item->getStartingTime();
                $values_before_change['endingTime'] = $dates_item->getEndingTime();
                $values_before_change['place'] = $dates_item->getPlace();
                $values_before_change['color'] = $dates_item->getColor();
                $values_before_change['description'] = $dates_item->getDescription();

                // Set modificator and modification date
                $user = $environment->getCurrentUserItem();
                $dates_item->setModificatorItem($user);
                $dates_item->setModificationDate(getCurrentDateTimeInMySQL());

                // Set attributes
                if ( isset($form_data['title']) ) {
                    $dates_item->setTitle($form_data['title']);
                }
                if ( isset($form_data['description']) ) {
                    $dates_item->setDescription($form_data['description']);
                }

                if ( isset($form_data['public']) ) {
                    if ( $dates_item->isPublic() != $form_data['public'] ) {
                        $dates_item->setPublic($form_data['public']);
                    }
                } else {
                    if ( isset($form_data['private_editing']) ) {
                        $dates_item->setPrivateEditing('0');
                    } else {
                        $dates_item->setPrivateEditing('1');
                    }
                }
                if ( isset($form_data['external_viewer']) and isset($form_data['external_viewer_accounts']) ) {
                    $user_ids = explode(" ",$form_data['external_viewer_accounts']);
                    $dates_item->setExternalViewerAccounts($user_ids);
                }else{
                    $dates_item->unsetExternalViewerAccounts();
                }

                if ( isset($form_data['hide']) ) {
                    // variables for datetime-format of end and beginning
                    $dt_hiding_time = '00:00:00';
                    $dt_hiding_date = '9999-00-00';
                    $dt_hiding_datetime = '';
                    $converted_activate_day_start = convertDateFromInput($form_data['dayActivateStart'],$environment->getSelectedLanguage());
                    if ($converted_activate_day_start['conforms'] == TRUE) {
                        $dt_hiding_datetime = $converted_activate_day_start['datetime'].' ';
                        $converted_activate_time_start = convertTimeFromInput($form_data['timeStart']);
                        if ($converted_activate_time_start['conforms'] == TRUE) {
                            $dt_hiding_datetime .= $converted_activate_time_start['datetime'];
                        }else{
                            $dt_hiding_datetime .= $dt_hiding_time;
                        }
                    }else{
                        $dt_hiding_datetime = $dt_hiding_date.' '.$dt_hiding_time;
                    }
                    $dates_item->setModificationDate($dt_hiding_datetime);
                }else{
                    if($dates_item->isNotActivated()){
                        $dates_item->setModificationDate(getCurrentDateTimeInMySQL());
                    }
                }

                if ( isset($form_data['mode']) ) {
                    $dates_item->setDateMode('1');
                }else{
                    $dates_item->setDateMode('0');
                }

                // variables for datetime-format of end and beginning
                $dt_start_time = '00:00:00';
                $dt_end_time = '00:00:00';
                $dt_start_date = '0000-00-00';
                $dt_end_date = '0000-00-00';


                $converted_time_start = convertTimeFromInput($form_data['timeStart']);
                if ($converted_time_start['conforms'] == TRUE) {
                    $dates_item->setStartingTime($converted_time_start['datetime']);
                    $dt_start_time = $converted_time_start['datetime'];
                } else {
                    $dates_item->setStartingTime($converted_time_start['display']);
                }

                $converted_day_start = convertDateFromInput($form_data['dayStart'],$environment->getSelectedLanguage());
                if ($converted_day_start['conforms'] == TRUE) {
                    $dates_item->setStartingDay($converted_day_start['datetime']);
                    $dt_start_date = $converted_day_start['datetime'];
                } else {
                    $dates_item->setStartingDay($converted_day_start['display']);
                }

                if (!empty($form_data['timeEnd'])) {
                    $converted_time_end = convertTimeFromInput($form_data['timeEnd']);
                    if ($converted_time_end['conforms'] == TRUE) {
                        $dates_item->setEndingTime($converted_time_end['datetime']);
                        $dt_end_time = $converted_time_end['datetime'];
                    } else {
                        $dates_item->setEndingTime($converted_time_end['display']);
                    }
                } else {
                    $dates_item->setEndingTime('');
                }

                if (!empty($form_data['dayEnd'])) {
                    $converted_day_end = convertDateFromInput($form_data['dayEnd'],$environment->getSelectedLanguage());
                    if ($converted_day_end['conforms'] == TRUE) {
                        $dates_item->setEndingDay($converted_day_end['datetime']);
                        $dt_end_date = $converted_day_end['datetime'];
                    } else {
                        $dates_item->setEndingDay($converted_day_end['display']);
                    }
                } else {
                    $dates_item->setEndingDay('');
                }

                if ($dt_end_date == '0000-00-00') {
                    $dt_end_date = $dt_start_date;
                }

                $dates_item->setDateTime_start($dt_start_date.' '.$dt_start_time);
                $dates_item->setDateTime_end($dt_end_date.' '.$dt_end_time);

                if (!empty($form_data['place'])) {
                    $dates_item->setPlace($form_data['place']);
                } else {
                    $dates_item->setPlace('');
                }
                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids')){
                    $dates_item->setBuzzwordListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids'));
                    $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
                }
                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids')){
                    $dates_item->setTagListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids'));
                    $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
                }
                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
                    $dates_item->setLinkedItemsByIDArray(array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')));
                    $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
                }

                if(isset($form_data['date_addon_color'])){
                    $dates_item->setColor($form_data['date_addon_color']);
                }

                $item_files_upload_to = $dates_item;
                include_once('include/inc_fileupload_edit_page_save_item.php');

                // Save item
                $dates_item->save();

                // Save recurrent items
                if(isset($form_data['recurring']) or isset($form_data['recurring_date'])){
                    if(isOption($command, $translator->getMessage('DATES_SAVE_BUTTON')) and !isset($form_data['recurring_ignore'])){
                        save_recurring_dates($dates_item, true, array());
                    } elseif (isOption($command, $translator->getMessage('DATES_CHANGE_RECURRING_BUTTON'))){
                        $vales_to_change = array();
                        if($values_before_change['title'] != $dates_item->getTitle()){
                            $vales_to_change[] = 'title';
                        }
                        if($values_before_change['startingTime'] != $dates_item->getStartingTime()){
                            $vales_to_change[] = 'startingTime';
                        }
                        if($values_before_change['endingTime'] != $dates_item->getEndingTime()){
                            $vales_to_change[] = 'endingTime';
                        }
                        if($values_before_change['place'] != $dates_item->getPlace()){
                            $vales_to_change[] = 'place';
                        }
                        if($values_before_change['color'] != $dates_item->getColor()){
                            $vales_to_change[] = 'color';
                        }
                        if($values_before_change['description'] != $dates_item->getDescription()){
                            $vales_to_change[] = 'description';
                        }
                        save_recurring_dates($dates_item, false, $vales_to_change);
                    }
                }

                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids')){
                    $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids'));
                }else{
                    $id_array =  array();
                }
                if ($item_is_new){
                    $id_array[] = $dates_item->getItemID();
                    $id_array = array_reverse($id_array);
                    $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids',$id_array);
                }

                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id')){
                    $mylist_manager = $environment->getMylistManager();
                    $mylist_item = $mylist_manager->getItem($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id'));
                    $id_array = $mylist_item->getAllLinkedItemIDArrayLabelVersion();
                    if (!in_array($dates_item->getItemID(),$id_array)){
                        $id_array[] =  $dates_item->getItemID();
                    }
                    $mylist_item->saveLinksByIDArray($id_array);
                }
                $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id');

                // Redirect
                /*cleanup_session($current_iid);
                 $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
                 $session->unsetValue('buzzword_post_vars');
                 $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
                 $session->unsetValue('tag_post_vars');
                 $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
                 $session->unsetValue('linked_items_post_vars');
                 $context_item = $environment->getCurrentContextItem();
                 $seldisplay_mode = $session->getValue($environment->getCurrentContextID().'_dates_seldisplay_mode');
                 if (empty($seldisplay_mode)){
                 $seldisplay_mode = $context_item->getDatesPresentationStatus();
                 }
                 if (isset($form_data['seldisplay_mode']) or $seldisplay_mode== 'calendar') {
                 if ($seldisplay_mode == 'calendar') {
                 $noticed_manager = $environment->getNoticedManager();
                 $noticed = $noticed_manager->getLatestNoticed($dates_item->getItemID());
                 if ( empty($noticed) or $noticed['read_date'] < $dates_item->getModificationDate() ) {
                 $noticed_manager->markNoticed($dates_item->getItemID(),0);
                 }
                 }
                 $params = array();
                 $params = getCalendarParameterArrayByItem($dates_item);
                 $params['seldisplay_mode'] = $seldisplay_mode;
                 if($params['presentation_mode'] == '1' and !empty($params['week'])){
                 $converted_day_start = convertDateFromInput($form_data['dayStart'],$environment->getSelectedLanguage());
                 if ($converted_day_start['conforms'] == TRUE) {
                 $year = mb_substr($converted_day_start['datetime'],0,4);
                 $month = mb_substr($converted_day_start['datetime'],5,2);
                 $day = mb_substr($converted_day_start['datetime'],8,2);
                 $d_time = mktime ( 3, 0, 0, $month, $day, $year );
                 $wday = date ( "w", $d_time );
                 $parameter_week = mktime ( 3, 0, 0, $month, $day - ( $wday - 1 ), $year );
                 $params['week'] = $parameter_week;
                 }
                 }
                 unsetCalendarSessionArray();
                 /*
                 $history = $session->getValue('history');
                 $i = 1;
                 $j = $i+1;
                 $funct = 'index';
                 while (isset($history[$j]['function']) and $history[$i]['function'] == 'edit'){
                 $funct = $history[$j]['function'];
                 $i++;
                 $j++;
                 }
                 if ($funct !='index'){

                 $params['iid'] = $current_iid;
                 if ( !is_numeric($current_iid) ) {
                 $params['iid'] = $dates_item->getItemID();
                 }
                 redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'detail',$params);
                 /*
                 }else{
                 redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'index',$params);
                 }

                 }else{
                 $params = array();
                 $params['iid'] = $dates_item->getItemID();
                 redirect($environment->getCurrentContextID(),
                 CS_DATE_TYPE, 'detail', $params);
                 } */
                $this->_return = 'success';
            }
        }
    }


    public function assignTemplateVars() {
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

    public function getFieldInformation($sub = '') {
        return array();
    }

    private function cleanup_session ($current_iid) {
        $this->_environment->getSessionItem()->unsetValue($this->_environment->getCurrentModule().'_add_files');
        $this->_environment->getSessionItem()->unsetValue($current_iid.'_post_vars');
    }
    
    public function getReturn() {
        return $this->_return;
    }
}