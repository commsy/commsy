<?php
class cs_popup_date_controller {

    private $_environment = null;
    private $_popup_controller = null;

    /**
     * constructor
     */
    public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
        $this->_environment = $environment;
        $this->_popup_controller = $popup_controller;
    }

    public function initPopup($item, $data) {
			// assign template vars
			$this->assignTemplateVars();
			$current_context = $this->_environment->getCurrentContextItem();

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
 				$this->_popup_controller->assign('item', 'public', $item->isPublic());
         		$this->_popup_controller->assign('item', 'mode', $item->getDateMode());

		        $temp = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
		        if ($temp['conforms']) {
		           $this->_popup_controller->assign('item', 'dayStart', getDateInLang($item->getStartingDay()));
		        } else {
		           $this->_popup_controller->assign('item', 'dayStart',  $item->getStartingDay());
		        }
		        $temp = convertTimeFromInput($item->getStartingTime());
		        if ($temp['conforms']) {
		           $this->_popup_controller->assign('item', 'timeStart', getTimeLanguage($item->getStartingTime()));
		        } else {
		           $this->_popup_controller->assign('item', 'timeStart',  $item->getStartingTime());
		        }
         		$temp = convertDateFromInput($item->getEndingDay(),$this->_environment->getSelectedLanguage());
		        if ($temp['conforms']) {
		           $this->_popup_controller->assign('item', 'dayEnd', getDateInLang($item->getEndingDay()));
		        } else {
		           $this->_popup_controller->assign('item', 'dayEnd',  $item->getEndingDay());
		        }
         		$temp = convertTimeFromInput($item->getEndingTime());
		        if ($temp['conforms']) {
		           $this->_popup_controller->assign('item', 'timeEnd', getTimeLanguage($item->getEndingTime()));
		        } else {
		           $this->_popup_controller->assign('item', 'timeEnd',  $item->getEndingTime());
		        }

         		$this->_popup_controller->assign('item', 'place', $item->getPlace());


				$activating = false;
				if($current_context->withActivatingContent()) {
					$activating = true;

					$this->_popup_controller->assign('item', 'private_editing', $item->isPrivateEditing());

					if($item->isNotActivated()) {
						$this->_popup_controller->assign('item', 'is_not_activated', true);

						$activating_date = $item->getActivatingDate();

						$this->_popup_controller->assign('item', 'activating_date', mb_substr($activating_date, 0, 10));
						$this->_popup_controller->assign('item', 'activating_time', mb_substr($activating_date, -8));
					}
				}

				$this->_popup_controller->assign('popup', 'activating', $activating);
			}else{
 				$val = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0';
 				$this->_popup_controller->assign('item', 'public', $val);
         		$this->_popup_controller->assign('item', 'private_editing', $val);
			}
    }

    public function save($form_data, $additional = array()) {

        $environment = $this->_environment;
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        $current_iid = $form_data['iid'];

        $translator = $this->_environment->getTranslationObject();

        if($current_iid === 'NEW') {
            $date_item = null;
        } else {
            $date_manager = $this->_environment->getDateManager();
            $date_item = $date_manager->getItem($current_iid);
        }

        // TODO: check rights */
		/****************************/
        if ( $current_iid != 'NEW' and !isset($date_item) ) {

        } elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
        ($current_iid != 'NEW' and isset($date_item) and
        $date_item->mayEdit($current_user))) ) {

		/****************************/


        } else { //Acces granted
			$this->cleanup_session($current_iid);

			// save item
			if($this->_popup_controller->checkFormData()) {
                $session = $this->_environment->getSessionItem();
                $item_is_new = false;
                // Create new item
                if ( !isset($date_item) ) {
                    $date_manager = $environment->getDateManager();
                    $date_item = $date_manager->getNewItem();
                    $date_item->setContextID($environment->getCurrentContextID());
                    $current_user = $environment->getCurrentUserItem();
                    $date_item->setCreatorItem($current_user);
                    $date_item->setCreationDate(getCurrentDateTimeInMySQL());
                    $item_is_new = true;
                }

	            $values_before_change = array();
	            $values_before_change['title'] = $date_item->getTitle();
	            $values_before_change['startingTime'] = $date_item->getStartingTime();
	            $values_before_change['endingTime'] = $date_item->getEndingTime();
	            $values_before_change['place'] = $date_item->getPlace();
	            $values_before_change['color'] = $date_item->getColor();
	            $values_before_change['description'] = $date_item->getDescription();

                // Set modificator and modification date
                $current_user = $environment->getCurrentUserItem();
                $date_item->setModificatorItem($current_user);

                // Set attributes
                if ( isset($form_data['title']) ) {
                    $date_item->setTitle($form_data['title']);
                }
                if ( isset($form_data['description']) ) {
                    $date_item->setDescription($form_data['description']);
                }
                if (isset($form_data['public'])) {
                    $date_item->setPublic($form_data['public']);
                }
                if ( isset($form_data['public']) ) {
                    if ( $date_item->isPublic() != $form_data['public'] ) {
                        $date_item->setPublic($form_data['public']);
                    }
                } else {
                    if ( isset($form_data['private_editing']) ) {
                        $date_item->setPrivateEditing('0');
                    } else {
                        $date_item->setPrivateEditing('1');
                    }
                }
                if ( isset($form_data['external_viewer']) and isset($form_data['external_viewer_accounts']) ) {
                    $user_ids = explode(" ",$form_data['external_viewer_accounts']);
                    $date_item->setExternalViewerAccounts($user_ids);
                }else{
                    $date_item->unsetExternalViewerAccounts();
                }

                if ( isset($form_data['hide']) ) {
                    // variables for datetime-format of end and beginning
                    $dt_hiding_time = '00:00:00';
                    $dt_hiding_date = '9999-00-00';
                    $dt_hiding_datetime = '';
                    $converted_activate_day_start = convertDateFromInput($form_data['dayActivateStart'],$environment->getSelectedLanguage());
                    if ($converted_activate_day_start['conforms'] == TRUE) {
                        $dt_hiding_datetime = $converted_activate_day_start['datetime'].' ';
                        $converted_activate_time_start = convertTimeFromInput($form_data['timeActivateStart']);
                        if ($converted_activate_time_start['conforms'] == TRUE) {
                            $dt_hiding_datetime .= $converted_activate_time_start['datetime'];
                        }else{
                            $dt_hiding_datetime .= $dt_hiding_time;
                        }
                    }else{
                        $dt_hiding_datetime = $dt_hiding_date.' '.$dt_hiding_time;
                    }
                    $date_item->setModificationDate($dt_hiding_datetime);
                }else{
                    if($date_item->isNotActivated()){
                        $date_item->setModificationDate(getCurrentDateTimeInMySQL());
                    }
                }

                if ( isset($form_data['mode']) ) {
                    $date_item->setDateMode('1');
                }else{
                    $date_item->setDateMode('0');
                }

                // variables for datetime-format of end and beginning
                $dt_start_time = '00:00:00';
                $dt_end_time = '00:00:00';
                $dt_start_date = '0000-00-00';
                $dt_end_date = '0000-00-00';

                // check end after start
                if (!empty($form_data["dayEnd"]) and ($form_data["dayEnd"] < $form_data["dayStart"])) {
                	$form_data["dayEnd"] = $form_data["timeEnd"] = "";
                }

                if ($form_data["dayEnd"] == $form_data["dayStart"] && $form_data["timeEnd"] <= $form_data["timeStart"]) {
                	$form_data["timeEnd"] = "";
                }

                $converted_time_start = convertTimeFromInput($form_data['timeStart']);
                if ($converted_time_start['conforms'] == TRUE) {
                    $date_item->setStartingTime($converted_time_start['datetime']);
                    $dt_start_time = $converted_time_start['datetime'];
                } else {
                    $date_item->setStartingTime($converted_time_start['display']);
                }

               $converted_day_start = convertDateFromInput($form_data['dayStart'],$environment->getSelectedLanguage());
               if ($converted_day_start['conforms'] == TRUE) {
                    $date_item->setStartingDay($converted_day_start['datetime']);
                    $dt_start_date = $converted_day_start['datetime'];
                } else {
                    $date_item->setStartingDay($converted_day_start['display']);
                }

                if (!empty($form_data['timeEnd'])) {
                    $converted_time_end = convertTimeFromInput($form_data['timeEnd']);
                    if ($converted_time_end['conforms'] == TRUE) {
                        $date_item->setEndingTime($converted_time_end['datetime']);
                        $dt_end_time = $converted_time_end['datetime'];
                    } else {
                        $date_item->setEndingTime($converted_time_end['display']);
                    }
                } else {
                    $date_item->setEndingTime('');
                }

                if (!empty($form_data['dayEnd'])) {
        			$converted_day_end = convertDateFromInput($form_data['dayEnd'],$environment->getSelectedLanguage());
                    if ($converted_day_end['conforms'] == TRUE) {
                        $date_item->setEndingDay($converted_day_end['datetime']);
                        $dt_end_date = $converted_day_end['datetime'];
                    } else {
                        $date_item->setEndingDay($converted_day_end['display']);
                    }
                } else {
                    $date_item->setEndingDay('');
                }

                if ($dt_end_date == '0000-00-00') {
                    $dt_end_date = $dt_start_date;
                }

                $date_item->setDateTime_start($dt_start_date.' '.$dt_start_time);
                $date_item->setDateTime_end($dt_end_date.' '.$dt_end_time);

                if (!empty($form_data['place'])) {
                    $date_item->setPlace($form_data['place']);
                } else {
                    $date_item->setPlace('');
                }

                // already attached files
                $file_ids = array();
                foreach($form_data as $key => $value) {
                	if(mb_substr($key, 0, 5) === 'file_') {
                		$file_ids[] = $value;
                	}
                }

                // this will handle already attached files as well as adding new files
                $this->_popup_controller->getUtils()->setFilesForItem($date_item, $file_ids, $form_data["files"]);


                // buzzwords
                $date_item->setBuzzwordListByID($form_data['buzzwords']);

                // tags
                $date_item->setTagListByID($form_data['tags']);

                // Save item
                $date_item->save();

                // Save recurrent items
                if(isset($form_data['recurring']) or isset($form_data['recurring_date'])){
                    if(isOption($command, $translator->getMessage('DATES_SAVE_BUTTON')) and !isset($form_data['recurring_ignore'])){
                        save_recurring_dates($date_item, true, array());
                    } elseif (isOption($command, $translator->getMessage('DATES_CHANGE_RECURRING_BUTTON'))){
                        $vales_to_change = array();
                        if($values_before_change['title'] != $date_item->getTitle()){
                            $vales_to_change[] = 'title';
                        }
                        if($values_before_change['startingTime'] != $date_item->getStartingTime()){
                            $vales_to_change[] = 'startingTime';
                        }
                        if($values_before_change['endingTime'] != $date_item->getEndingTime()){
                            $vales_to_change[] = 'endingTime';
                        }
                        if($values_before_change['place'] != $date_item->getPlace()){
                            $vales_to_change[] = 'place';
                        }
                        if($values_before_change['color'] != $date_item->getColor()){
                            $vales_to_change[] = 'color';
                        }
                        if($values_before_change['description'] != $date_item->getDescription()){
                            $vales_to_change[] = 'description';
                        }
                        save_recurring_dates($date_item, false, $vales_to_change);
                    }
                }

                // this will update the right box list
                if($item_is_new){
	                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.CS_DATE_TYPE.'_index_ids')){
	                    $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.CS_DATE_TYPE.'_index_ids'));
	                } else {
	                    $id_array =  array();
	                }

                    $id_array[] = $date_item->getItemID();
                    $id_array = array_reverse($id_array);
                    $session->setValue('cid'.$environment->getCurrentContextID().'_'.CS_DATE_TYPE.'_index_ids',$id_array);
                }

                // save session
                $this->_environment->getSessionManager()->save($session);

                // Add modifier to all users who ever edited this item
                $manager = $environment->getLinkModifierItemManager();
                $manager->markEdited($date_item->getItemID());

                // set return
                $this->_popup_controller->setSuccessfullItemIDReturn($date_item->getItemID());
            }
        }


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
                 $noticed = $noticed_manager->getLatestNoticed($date_item->getItemID());
                 if ( empty($noticed) or $noticed['read_date'] < $date_item->getModificationDate() ) {
                 $noticed_manager->markNoticed($date_item->getItemID(),0);
                 }
                 }
                 $params = array();
                 $params = getCalendarParameterArrayByItem($date_item);
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
                 $params['iid'] = $date_item->getItemID();
                 }
                 redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'detail',$params);
                 /*
                 }else{
                 redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'index',$params);
                 }

                 }else{
                 $params = array();
                 $params['iid'] = $date_item->getItemID();
                 redirect($environment->getCurrentContextID(),
                 CS_DATE_TYPE, 'detail', $params);
                 }
                $this->_return = 'success';
            }
        }*/
    }

    private function cleanup_session($current_iid) {
    	$environment = $this->_environment;
    	$session = $this->_environment->getSessionItem();

    	$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
    	$session->unsetValue($environment->getCurrentModule().'_add_tags');
    	$session->unsetValue($environment->getCurrentModule().'_add_files');
    	$session->unsetValue($current_iid.'_post_vars');
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
        return array(
    			array(	'name'		=> 'title',
    					'type'		=> 'text',
    					'mandatory' => true),
    			array(	'name'		=> 'description',
    					'type'		=> 'text',
    					'mandatory'	=> false),
        		array(	'name'		=> 'dayStart',
        				'type'		=> 'date',
        				'mandatory'	=> true)
    	);
    }
}