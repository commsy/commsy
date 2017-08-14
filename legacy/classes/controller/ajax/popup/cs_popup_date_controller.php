<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_main_controller.php');
class cs_popup_date_controller extends cs_rubric_popup_main_controller {

    private $_environment = null;
    private $_popup_controller = null;
    private $_edit_type = 'normal';

    /**
     * constructor
     */
    public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
        $this->_environment = $environment;
        $this->_popup_controller = $popup_controller;
    }

    public function initPopup($item, $data) {
			if (!empty($data['editType'])){
				$this->_edit_type = $data['editType'];
				$this->_popup_controller->assign('item', 'edit_type', $data['editType']);
			}
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
				
 				$this->_popup_controller->assign('item', 'public', $item->isPublic());
         		$this->_popup_controller->assign('item', 'mode', $item->getDateMode());

				if ($data["contextId"]) {
					$this->_popup_controller->assign('item', 'external_viewer', $item->issetExternalViewerStatus());
					$this->_popup_controller->assign('item', 'external_viewer_accounts', $item->getExternalViewerString());
				}

		        $temp = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
		        if ($temp['conforms']) {
		           //$this->_popup_controller->assign('item', 'dayStart', getDateInLang($item->getStartingDay()));
		           $this->_popup_controller->assign('item', 'dayStart', $item->getStartingDay());
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
		           //$this->_popup_controller->assign('item', 'dayEnd', getDateInLang($item->getEndingDay()));
		        	$this->_popup_controller->assign('item', 'dayEnd', $item->getEndingDay());
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
						if (!stristr($activating_date,'9999')){
							$this->_popup_controller->assign('item', 'activating_date', mb_substr($activating_date,0,10));
							$this->_popup_controller->assign('item', 'activating_time', mb_substr($activating_date, -8));
						}
					}
				}


				$this->_popup_controller->assign('popup', 'activating', $activating);

				$this->_popup_controller->assign('item', 'date_addon_color', $item->getColor());

				if($item->getRecurrenceId() != '' and $item->getRecurrenceId() != 0){
				   $recurrence_pattern = $item->getRecurrencePattern();
				   $this->_popup_controller->assign('item', 'is_recurring_date', $recurrence_pattern['recurring_select']);
				   if($recurrence_pattern['recurring_select'] == 'daily'){
				      $this->_popup_controller->assign('item', 'recurring_day', $recurrence_pattern['recurring_day']);
				   } else if($recurrence_pattern['recurring_select'] == 'weekly'){
   				   $this->_popup_controller->assign('item', 'recurring_week', $recurrence_pattern['recurring_week']);
   				   $this->_popup_controller->assign('item', 'recurring_week_days_monday', $recurrence_pattern['recurring_week_days_monday']);
   				   foreach($recurrence_pattern['recurring_week_days'] as $day){
   				      if($day == 'monday'){
   				         $this->_popup_controller->assign('item', 'recurring_week_days_monday', 'yes');
   				      }
   				      if($day == 'tuesday'){
   				         $this->_popup_controller->assign('item', 'recurring_week_days_tuesday', 'yes');
   				      }
   				      if($day == 'wednesday'){
   				         $this->_popup_controller->assign('item', 'recurring_week_days_wednesday', 'yes');
   				      }
   				      if($day == 'thursday'){
   				         $this->_popup_controller->assign('item', 'recurring_week_days_thusday', 'yes');
   				      }
   				      if($day == 'friday'){
   				         $this->_popup_controller->assign('item', 'recurring_week_days_friday', 'yes');
   				      }
   				      if($day == 'saturday'){
   				         $this->_popup_controller->assign('item', 'recurring_week_days_saturday', 'yes');
   				      }
   				      if($day == 'sunday'){
   				         $this->_popup_controller->assign('item', 'recurring_week_days_sunday', 'yes');
   				      }
   				   }
				   } else if($recurrence_pattern['recurring_select'] == 'monthly'){
				      $this->_popup_controller->assign('item', 'recurring_month', $recurrence_pattern['recurring_month']);
				      $this->_popup_controller->assign('item', 'recurring_month_every', $recurrence_pattern['recurring_month_every']);
				      $this->_popup_controller->assign('item', 'recurring_month_day_every', $recurrence_pattern['recurring_month_day_every']);
				   } else if($recurrence_pattern['recurring_select'] == 'yearly'){
				      $this->_popup_controller->assign('item', 'recurring_year', $recurrence_pattern['recurring_year']);
				      $this->_popup_controller->assign('item', 'recurring_year_every', $recurrence_pattern['recurring_year_every']);
				   }
				   $this->_popup_controller->assign('item', 'recurring_end_date', getDateInLang($recurrence_pattern['recurring_end_date']));
				}

			}else{
 				$val = ($this->_environment->inProjectRoom() || $this->_environment->inGroupRoom()) ? '1': '0';
 				$this->_popup_controller->assign('item', 'public', $val);
 				$val = ($this->_environment->inProjectRoom() || $this->_environment->inGroupRoom()) ? false : true;
	       		$this->_popup_controller->assign('item', 'private_editing', $val);
         		if(!empty($data['date_new'])){
         		   $this->_popup_controller->assign('item', 'date_new_date', date('Y-m-d', $data['date_new']));
         		   $this->_popup_controller->assign('item', 'date_new_time', date('H:i', $data['date_new']));
         		}
			}
    }

    public function save($form_data, $additional = array()) {

        $environment = $this->_environment;
        $text_converter = $this->_environment->getTextConverter();

        if ($additional["contextId"]) {
        	$itemManager = $this->_environment->getItemManager();
        	$type = $itemManager->getItemType($additional["contextId"]);

        	$manager = $this->_environment->getManager($type);
        	$current_context = $manager->getItem($additional["contextId"]);

        	if ($type === CS_PRIVATEROOM_TYPE) {
        		$this->_environment->changeContextToPrivateRoom($current_context->getItemID());
        	}
        }

        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        $current_iid = $form_data['iid'];

        if (isset($form_data['editType'])){
			$this->_edit_type = $form_data['editType'];
        }

        $translator = $this->_environment->getTranslationObject();

        if($current_iid === 'NEW') {
            $date_item = null;
        } else {
            $date_manager = $this->_environment->getDateManager();
            $date_item = $date_manager->getItem($current_iid);
        }

        $this->_popup_controller->performChecks($date_item, $form_data, $additional);

        // TODO: check rights */
		/****************************/
        if ( $current_iid != 'NEW' and !isset($date_item) ) {

        } elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
        ($current_iid != 'NEW' and isset($date_item) and
        $date_item->mayEdit($current_user))) ) {

		/****************************/


        } elseif($this->_edit_type != 'normal'){
 			$this->cleanup_session($current_iid);
            // Set modificator and modification date
            $current_user = $environment->getCurrentUserItem();
            $date_item->setModificatorItem($current_user);

            if ($this->_edit_type == 'buzzwords'){
                // buzzwords
                $date_item->setBuzzwordListByID($form_data['buzzwords']);
            }
            if ($this->_edit_type == 'tags'){
                // buzzwords
                $date_item->setTagListByID($form_data['tags']);
            }
            $date_item->save();
            // save session
            $session = $this->_environment->getSessionItem();
            $this->_environment->getSessionManager()->save($session);

            // Add modifier to all users who ever edited this item
            $manager = $environment->getLinkModifierItemManager();
            $manager->markEdited($date_item->getItemID());

            // set return
            $this->_popup_controller->setSuccessfullItemIDReturn($date_item->getItemID(),CS_DATE_TYPE);

        }else { //Acces granted
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
                $date_item->setModificatorItem($current_user);

                // Set attributes
                if ( isset($form_data['title']) ) {
                    $date_item->setTitle($form_data['title']);
                }
                if ( isset($form_data['description']) ) {
                    $date_item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));
                }
                if ( isset($form_data['external_viewer']) and isset($form_data['external_viewer_accounts']) ) {
                    $user_ids = explode(" ",$form_data['external_viewer_accounts']);
                    $date_item->setExternalViewerAccounts($user_ids);
                }else{
                    $date_item->unsetExternalViewerAccounts();
                }

            	if(isset($form_data['private_editing'])) {
            		$date_item->setPrivateEditing('0');
            	} else {
            		$date_item->setPrivateEditing('1');
            	}

                 if (isset($form_data['rights_tab'])){
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

	                if ( isset($form_data['hide']) ) {
	                    // variables for datetime-format of end and beginning
	                    $dt_hiding_time = '00:00:00';
	                    $dt_hiding_date = '9999-00-00';
	                    $dt_hiding_datetime = '';
		                $converted_activating_time_start = convertTimeFromInput($form_data['activating_time']);
		                if ($converted_activating_time_start['conforms'] == TRUE) {
		                    $dt_hiding_time= $converted_activating_time_start['datetime'];
		                }

	                    $converted_activate_day_start = convertDateFromInput($form_data['activating_date'],$environment->getSelectedLanguage());
	                    if ($converted_activate_day_start['conforms'] == TRUE) {
	                        $dt_hiding_date = $converted_activate_day_start['datetime'];
	                    }
	                    $dt_hiding_datetime = $dt_hiding_date.' '.$dt_hiding_time;
	                    $date_item->setModificationDate($dt_hiding_datetime);
	                }else{
	                    if($date_item->isNotActivated()){
	                        $date_item->setModificationDate(getCurrentDateTimeInMySQL());
	                    }
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

                if (!empty($form_data['dayEnd'])) {
                	$converted_day_end = convertDateFromInput($form_data['dayEnd'],$environment->getSelectedLanguage());
                	if ($converted_day_end['conforms'] == TRUE) {

                		if ($converted_day_end["timestamp"] < $converted_day_start["timestamp"]) {
                			$converted_day_end["datetime"] = $converted_day_start["datetime"];
                		}

                		$date_item->setEndingDay($converted_day_end['datetime']);
                		$dt_end_date = $converted_day_end['datetime'];
                	} else {
                		$date_item->setEndingDay($converted_day_end['display']);
                	}
                } else {
                	$date_item->setEndingDay('');
                }

                if (!empty($form_data['timeEnd'])) {
                    $converted_time_end = convertTimeFromInput($form_data['timeEnd']);
                    if ($converted_time_end['conforms'] == TRUE) {

                        if ($converted_time_end["timestamp"] < $converted_time_start["timestamp"]) {
                            $adjust = true;

                            if (!empty($form_data['dayEnd'])) {
                                $converted_day_end = convertDateFromInput($form_data['dayEnd'],$environment->getSelectedLanguage());
                                if ($converted_day_start['conforms'] == TRUE && $converted_day_end['conforms'] == TRUE) {
                                    if ($converted_day_end['timestamp'] > $converted_day_start['timestamp']) {
                                        $adjust = false;
                                    }
                                }
                            }

                            if ($adjust) {
                                $converted_time_end["datetime"] = $converted_time_start["datetime"];
                            }
                        }

                        $date_item->setEndingTime($converted_time_end['datetime']);
                        $dt_end_time = $converted_time_end['datetime'];
                    } else {
                        $date_item->setEndingTime($converted_time_end['display']);
                    }
                } else {
                    $date_item->setEndingTime('');
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

                // color
                if ( isset($form_data['date_addon_color']) ) {
                   $date_item->setColor($form_data['date_addon_color']);
                }

                // buzzwords
				// save buzzwords
				$this->saveBuzzwords($environment, $date_item, $form_data['buzzwords']);

                // tags
                if (isset($form_data['tags_tab'])){
                	$date_item->setTagListByID($form_data['tags']);
                }

                // Save item
                #$date_item->save();

                // Save recurrent items

                $errors = array();

                if(isset($form_data['recurring']) or isset($form_data['recurring_date']) or ($date_item->getRecurrenceId() != '' and $date_item->getRecurrenceId() != 0)){
                    if(isset($form_data['recurring_week_days_monday'])){
                       $form_data['recurring_week_days'][] = $form_data['recurring_week_days_monday'];
                    }
                    if(isset($form_data['recurring_week_days_tuesday'])){
                       $form_data['recurring_week_days'][] = $form_data['recurring_week_days_tuesday'];
                    }
                    if(isset($form_data['recurring_week_days_wednesday'])){
                       $form_data['recurring_week_days'][] = $form_data['recurring_week_days_wednesday'];
                    }
                    if(isset($form_data['recurring_week_days_thusday'])){
                       $form_data['recurring_week_days'][] = $form_data['recurring_week_days_thusday'];
                    }
                    if(isset($form_data['recurring_week_days_friday'])){
                       $form_data['recurring_week_days'][] = $form_data['recurring_week_days_friday'];
                    }
                    if(isset($form_data['recurring_week_days_saturday'])){
                       $form_data['recurring_week_days'][] = $form_data['recurring_week_days_saturday'];
                    }
                    if(isset($form_data['recurring_week_days_sunday'])){
                       $form_data['recurring_week_days'][] = $form_data['recurring_week_days_sunday'];
                    }

                    $errors = $this->checkValues($form_data);

                    if(empty($errors)){
                       $date_item->save();

                       if($additional['part'] == 'all' and !isset($form_data['recurring_ignore'])){
                           $this->save_recurring_dates($date_item, true, array(), $form_data);
                       } elseif ($additional['part'] == 'recurring'){
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
                           $this->save_recurring_dates($date_item, false, $vales_to_change, $form_data);
                       }
                    }
                } else {
                   $date_item->save();
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
                if(empty($errors)){
                   $this->_popup_controller->setSuccessfullItemIDReturn($date_item->getItemID());
                } else {
                   $this->_popup_controller->setErrorReturn(101, '', $errors);
                }
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
			if ($this->_edit_type == 'normal'){
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
			}else{
				return array();
			}
    }

    function save_recurring_dates($dates_item, $is_new_date, $values_to_change, $form_data){
       global $environment;
       if($is_new_date){
          /*
           * Check for php version 5.3.0, which is required for the DateInterval object
          */
          if(strnatcmp(phpversion(), '5.3.0') >= 0) {
             #############################################################
             ########## with php 5.3.0 support
             ######################
             ######
             ###
             #
             #
             #
             $recurrent_id = $dates_item->getItemID();
          $recurring_date_array = array();
          $recurring_pattern_array = array();

          $start_date = new DateTime($dates_item->getStartingDay());
          $end_date = new DateTime($form_data['recurring_end_date']);

          $recurring_pattern_array['recurring_select'] = $form_data['recurring_select'];

          // daily recurring
          if($form_data['recurring_select'] == 'daily') {
             $date_interval = new DateInterval('P' . $form_data['recurring_day'] . 'D');

             $day = clone $start_date;
             $day->add($date_interval);
             while($day <= $end_date) {
                $recurring_date_array[] = clone $day;

                $day->add($date_interval);
             }
             $recurring_pattern_array['recurring_day'] = $form_data['recurring_day'];

             unset($date_interval);

             // weekly recurring
          } else if($form_data['recurring_select'] == 'weekly') {
             // go back to last monday(if day is not monday)
             $monday = clone $start_date;
             if($start_date->format('w') == 0) {
                $monday->sub(new DateInterval('P6D'));
             } else {
                $monday->sub(new DateInterval('P' . ($start_date->format('w')-1) . 'D'));
             }

             while($monday <= $end_date) {
                foreach($form_data['recurring_week_days'] as $day) {
                   if($day == 'monday') {
                      $addon_days = 0;
                   } elseif($day == 'tuesday') {
                      $addon_days = 1;
                   } elseif($day == 'wednesday') {
                      $addon_days = 2;
                   } elseif($day == 'thursday') {
                      $addon_days = 3;
                   } elseif($day == 'friday') {
                      $addon_days = 4;
                   } elseif($day == 'saturday') {
                      $addon_days = 5;
                   } elseif($day == 'sunday') {
                      $addon_days = 6;
                   }

                   $temp = clone $monday;
                   $temp->add(new DateInterval('P' . $addon_days . 'D'));

                   if($temp > $start_date && $temp <= $end_date) {
                      $recurring_date_array[] = $temp;
                   }

                   unset($temp);
                }

                $monday->add(new DateInterval('P' . $form_data['recurring_week'] . 'W'));
             }
             $recurring_pattern_array['recurring_week_days'] = $form_data['recurring_week_days'];
             $recurring_pattern_array['recurring_week'] = $form_data['recurring_week'];

             unset($monday);

             // monthly recurring
          } else if($form_data['recurring_select'] == 'monthly') {
             $month_count = $start_date->format('m');
             $year_count = $start_date->format('Y');
             $month_to_add = $form_data['recurring_month'] % 12;
             $years_to_add = ($form_data['recurring_month'] - $month_to_add) / 12;
             $month = new DateTime($year_count . '-' . $month_count . '-01');

             while($month <= $end_date) {
                $dates_occurence_array = array();

                // loop through every day of this month
                for($index = 0; $index < $month->format('t'); $index++) {
                   $temp = clone $month;
                   $temp->add(new DateInterval('P' . $index . 'D'));

                   // if the actual day is a correct week day, add it to possible dates
                   $week_day = $temp->format('w');
                   if($week_day == $form_data['recurring_month_day_every']) {
                      $dates_occurence_array[] = $temp;
                   }

                   unset($temp);
                }

                // add only days, that match the right week
                if($form_data['recurring_month_every'] != 'last') {
                   if($form_data['recurring_month_every'] <= count($dates_occurence_array)) {
                      if(   $dates_occurence_array[$form_data['recurring_month_every']-1] >= $start_date &&
                      $dates_occurence_array[$form_data['recurring_month_every']-1] <= $end_date) {
                         $recurring_date_array[] = $dates_occurence_array[$form_data['recurring_month_every']-1];
                      }
                   }
                } else {
                   if(   $dates_occurence_array[count($form_data['recurring_month_every'])-1] >= $start_date &&
                   $dates_occurence_array[count($form_data['recurring_month_every'])-1] <= $end_date) {
                      $recurring_date_array[] = $dates_occurence_array[count($form_data['recurring_month_every'])-1];
                   }
                }

                // go to next month
                if($month_count + $month_to_add > 12) {
                   $month_count += $month_to_add - 12;
                   $year_count += $years_to_add + 1;
                } else {
                   $month_count += $month_to_add;
                }

                unset($month);
                $month = new DateTime($year_count . '-' . $month_count . '-01');
             }

             $recurring_pattern_array['recurring_month'] = $form_data['recurring_month'];
             $recurring_pattern_array['recurring_month_day_every'] = $form_data['recurring_month_day_every'];
             $recurring_pattern_array['recurring_month_every'] = $form_data['recurring_month_every'];

             unset($month);

             // yearly recurring
          } else if($form_data['recurring_select'] == 'yearly') {
             $year_count = $start_date->format('Y');
             $year = new DateTime($year_count . '-01-01');
             while($year <= $end_date) {
                $date = new DateTime($form_data['recurring_year'] . '-' . $form_data['recurring_year_every'] . '-' . $year_count);
                if($date > $start_date && date <= $end_date) {
                   $recurring_date_array[] = $date;
                }
                unset($date);

                unset($year);
                $year_count++;
                $year = new DateTime($year_count . '-01-01');
             }

             $recurring_pattern_array['recurring_year'] = $form_data['recurring_year'];
             $recurring_pattern_array['recurring_year_every'] = $form_data['recurring_year_every'];
          }

          unset($start_date);
          unset($end_date);

          $recurring_pattern_array['recurring_start_date'] = $dates_item->getStartingDay();
          //$recurring_pattern_array['recurring_end_date'] = $year_recurring.'-'.$month_recurring.'-'.$day_recurring;
          $recurring_pattern_array['recurring_end_date'] = $form_data['recurring_end_date'];

          foreach($recurring_date_array as $date) {
             $temp_date = clone $dates_item;
             $temp_date->setItemID('');
             $temp_date->setStartingDay(date('Y-m-d', $date->getTimestamp()));

             if($dates_item->getStartingTime() != '') {
                $temp_date->setDateTime_start(date('Y-m-d', $date->getTimestamp()) . ' ' . $dates_item->getStartingTime());
             } else {
                $temp_date->setDateTime_start(date('Y-m-d 00:00:00', $date->getTimestamp()));
             }

             if($dates_item->getEndingDay() != '') {
                $temp_starting_day = new DateTime($dates_item->getStartingDay());
                $temp_ending_day = new DateTime($dates_item->getEndingDay());

                $temp_date->setEndingDay(date('Y-m-d', $date->getTimestamp() + ($temp_ending_day->getTimestamp() - $temp_starting_day->getTimestamp())));

                unset($temp_starting_day);
                unset($temp_ending_day);

                if($dates_item->getEndingTime() != '') {
                   $temp_date->setDateTime_end(date('Y-m-d', $date->getTimestamp()) . ' ' . $dates_item->getEndingTime());
                } else {
                   $temp_date->setDateTime_end(date('Y-m-d 00:00:00', $date->getTimestamp()));
                }
             } else {
                if($dates_item->getEndingTime() != '')  {
                   $temp_date->setDateTime_end(date('Y-m-d', $date->getTimestamp()) . ' ' . $dates_item->getEndingTime());
                } else {
                   $temp_date->setDateTime_end(date('Y-m-d 00:00:00', $date->getTimestamp()));
                }
             }
             $temp_date->setRecurrenceId($dates_item->getItemID());
             $temp_date->setRecurrencePattern($recurring_pattern_array);
             $temp_date->save();
          }
          $dates_item->setRecurrenceId($dates_item->getItemID());
          $dates_item->setRecurrencePattern($recurring_pattern_array);
          $dates_item->save();
          #
          #
          #
          ###
          ######
          ######################
          ########## ~with php 5.3.0 support
          #############################################################
          } else {
          // TODO: this may be removed in future times
          #############################################################
          ########## without php 5.3.0 support
          ######################
          ######
          ###
          #
          #
          #

          $recurring_date_array = array();
          $recurring_pattern_array = array();

          $recurrent_id = $dates_item->getItemID();
          
          // first next date is starting date
          $next_date = $dates_item->getStartingDay();
          
          // convert date to timestamp - zero hour
          $month_date = mb_substr($next_date,5,2);
          $day_date = mb_substr($next_date,8,2);
          $year_date = mb_substr($next_date,0,4);
          $next_date_time = mktime(0,0,0,$month_date,$day_date,$year_date);
          
          // calculate timestamp for recurring date
          $month_recurring = mb_substr($form_data['recurring_end_date'],5,2);
          $day_recurring = mb_substr($form_data['recurring_end_date'],8,2);
          $year_recurring = mb_substr($form_data['recurring_end_date'],0,4);
          $recurring_end_time = mktime(0,0,0,$month_recurring,$day_recurring,$year_recurring);

          $recurring_pattern_array['recurring_select'] = $form_data['recurring_select'];
    	      if($form_data['recurring_select'] == 'daily') {
    	         $next_date_time = strtotime('+' . $form_data['recurring_day'] . ' day', $next_date_time);
          while($next_date_time <= $recurring_end_time) {
          $recurring_date_array[] = $next_date_time;

             $next_date_time = strtotime('+' . $form_data['recurring_day'] . ' day', $next_date_time);
    	         }
    	         $recurring_pattern_array['recurring_day'] = $form_data['recurring_day'];
          } elseif($form_data['recurring_select'] == 'weekly') {
             $weekday = date('w', $next_date_time);
             if($weekday == 0) {
             $week = strtotime('-6 days', $next_date_time);
             } else {
             $week = strtotime('-' . ($weekday - 1) . ' day', $next_date_time);
             }

             while($week <= $recurring_end_time) {
             foreach($form_data['recurring_week_days'] as $day) {
             if($day == 'monday') {
    	                  $addon_days = 0;
             } elseif($day == 'tuesday') {
             $addon_days = 1;
             } elseif($day == 'wednesday') {
             $addon_days = 2;
             } elseif($day == 'thursday') {
             $addon_days = 3;
             } elseif($day == 'friday') {
             $addon_days = 4;
             } elseif($day == 'saturday') {
                $addon_days = 5;
             } elseif($day == 'sunday') {
             $addon_days = 6;
             }

             $str = '+' . $addon_days . ' day';
             if(   strtotime($str, $week) > $next_date_time &&
    	                     strtotime($str, $week) <= $recurring_end_time) {
             $recurring_date_array[] = strtotime($str, $week);
             }
             }

             $week = strtotime('+' . $form_data['recurring_week'] . ' week', $week);
             }
             $recurring_pattern_array['recurring_week_days'] = $form_data['recurring_week_days'];
             $recurring_pattern_array['recurring_week'] = $form_data['recurring_week'];
    	      } elseif($form_data['recurring_select'] == 'monthly') {
             $month_count = $month_date;
    	         $year_count = $year_date;
    	         $month_to_add = $form_data['recurring_month'] % 12;
             $years_to_add = ($form_data['recurring_month'] - $month_to_add) / 12;
    	         $selected_day = $form_data['recurring_month_day_every'];
             $selected_occurence = $form_data['recurring_month_every'];
    	         $month = mktime(0,0,0,$month_count,1,$year_count);
    	         while($month <= $recurring_end_time) {
             $dates_occurence_array = array();
             for ($index = 0; $index < date('t',$month); $index++) {
             $str = '+' . $index . ' day';
             $week_day = date('w', strtotime($str, $month));
             if($week_day == $selected_day) {
             $dates_occurence_array[] = strtotime($str, $month);
    	               }
             }
             if($selected_occurence != 'last') {
             if($selected_occurence <= count($dates_occurence_array)) {
             if(($dates_occurence_array[$selected_occurence-1] >= $next_date_time) and ($dates_occurence_array[$selected_occurence-1] <= $recurring_end_time)) {
             $recurring_date_array[] = $dates_occurence_array[$selected_occurence-1];
             }
             }
             } else {
             if(($dates_occurence_array[count($dates_occurence_array)-1] >= $next_date_time ) and ($dates_occurence_array[count($dates_occurence_array)-1] <= $recurring_end_time)) {
                $recurring_date_array[] = $dates_occurence_array[count($dates_occurence_array)-1];
             }
             }
             if($month_count + $month_to_add > 12) {
                $month_count = $month_count + $month_to_add - 12;
                $year_count = $year_count + $years_to_add + 1;
             } else {
                $month_count = $month_count + $month_to_add;
                }
                $month = mktime(0,0,0,$month_count,1,$year_count);
                }
                $recurring_pattern_array['recurring_month'] = $form_data['recurring_month'];
                   $recurring_pattern_array['recurring_month_day_every'] = $form_data['recurring_month_day_every'];
                   $recurring_pattern_array['recurring_month_every'] = $form_data['recurring_month_every'];
                   } elseif($form_data['recurring_select'] == 'yearly') {
                   $year_count = $year_date;
    	         $year = mktime(0,0,0,1,1,$year_count);
                   while($year <= $recurring_end_time) {
    	            if((mktime(0,0,0,$form_data['recurring_year_every'],$form_data['recurring_year'],$year_count) > $next_date_time) and (mktime(0,0,0,$form_data['recurring_year_every'],$form_data['recurring_year'],$year_count) <= $recurring_end_time)) {
                   $recurring_date_array[] = mktime(0,0,0,$form_data['recurring_year_every'],$form_data['recurring_year'],$year_count);
                   }
    	            $year_count++;
    	            $year = mktime(0,0,0,1,1,$year_count);
                   }
                   $recurring_pattern_array['recurring_year'] = $form_data['recurring_year'];
                   $recurring_pattern_array['recurring_year_every'] = $form_data['recurring_year_every'];
                   }

    	      $recurring_pattern_array['recurring_start_date'] = $dates_item->getStartingDay();
                   $recurring_pattern_array['recurring_end_date'] = $year_recurring.'-'.$month_recurring.'-'.$day_recurring;

                   foreach($recurring_date_array as $date){
                   $temp_date = clone $dates_item;
                   $temp_date->setItemID('');
    	         $temp_date->setStartingDay(date('Y-m-d', $date));
                   if($dates_item->getStartingTime() != ''){
                   $temp_date->setDateTime_start(date('Y-m-d', $date) . ' ' . $dates_item->getStartingTime());
                   } else {
                   $temp_date->setDateTime_start(date('Y-m-d 00:00:00', $date));
                   }
                   if($dates_item->getEndingDay() != ''){
                   $temp_starting_day = $dates_item->getStartingDay();
    	            $temp_starting_day_month = mb_substr($temp_starting_day,5,2);
                   $temp_starting_day_day = mb_substr($temp_starting_day,8,2);
                   $temp_starting_day_year = mb_substr($temp_starting_day,0,4);
                      $temp_starting_day_time = mktime(0,0,0,$temp_starting_day_month,$temp_starting_day_day,$temp_starting_day_year);

                      $temp_ending_day = $dates_item->getEndingDay();
                      $temp_ending_day_month = mb_substr($temp_ending_day,5,2);
                      $temp_ending_day_day = mb_substr($temp_ending_day,8,2);
                      $temp_ending_day_year = mb_substr($temp_ending_day,0,4);
                      $temp_ending_day_time = mktime(0,0,0,$temp_ending_day_month,$temp_ending_day_day,$temp_ending_day_year);

                      $temp_date->setEndingDay(date('Y-m-d', $date+($temp_ending_day_time - $temp_starting_day_time)));
                      if($dates_item->getEndingTime() != ''){
                      $temp_date->setDateTime_end(date('Y-m-d', $date) . ' ' . $dates_item->getEndingTime());
                   } else {
                      $temp_date->setDateTime_end(date('Y-m-d 00:00:00', $date));
                   }
                   } else {
                      if($dates_item->getEndingTime() != ''){
                      $temp_date->setDateTime_end(date('Y-m-d', $date) . ' ' . $dates_item->getEndingTime());
    	            } else {
                      $temp_date->setDateTime_end(date('Y-m-d 00:00:00', $date));
    	            }
                      }
                      $temp_date->setRecurrenceId($dates_item->getItemID());
                      $temp_date->setRecurrencePattern($recurring_pattern_array);
    	         $temp_date->save();
                      }
                      $dates_item->setRecurrenceId($dates_item->getItemID());
                      $dates_item->setRecurrencePattern($recurring_pattern_array);
                      $dates_item->save();

                      #
                      #
                      #
                      ###
                      ######
                      ######################
                      ########## ~without php 5.3.0 support
                      #############################################################
             }
       } else {
          $dates_manager = $environment->getDatesManager();
          $dates_manager->resetLimits();
          $dates_manager->setRecurrenceLimit($dates_item->getRecurrenceId());
          $dates_manager->setWithoutDateModeLimit();
          $dates_manager->select();
          $dates_list = $dates_manager->get();
          $temp_date = $dates_list->getFirst();
          while($temp_date){
          if(in_array('title',$values_to_change)){
          $temp_date->setTitle($dates_item->getTitle());
          }
          if(in_array('startingTime',$values_to_change)){
                      $temp_date->setStartingTime($dates_item->getStartingTime());
          $temp_date->setDateTime_start(mb_substr($temp_date->getDateTime_start(),0,10) . ' ' . $dates_item->getStartingTime());
          }
          if(in_array('endingTime',$values_to_change)){
          $temp_date->setEndingTime($dates_item->getEndingTime());
          $temp_date->setDateTime_end(mb_substr($temp_date->getDateTime_end(),0,10) . ' ' . $dates_item->getEndingTime());
          }
          if(in_array('place',$values_to_change)){
          $temp_date->setPlace($dates_item->getPlace());
          }
          if(in_array('color',$values_to_change)){
          $temp_date->setColor($dates_item->getColor());
          }
          if(in_array('description',$values_to_change)){
          $temp_date->setDescription($dates_item->getDescription());
          }
             $temp_date->save();
             $temp_date = $dates_list->getNext();
          }
       }
    }

   function checkValues ($form_data) {
      $translator = $this->_environment->getTranslationObject();
      $result = array();
      if(!isset($form_data['recurring_ignore'])){
         if ( !empty($form_data['recurring'])){
            if($form_data['recurring_select'] == 'daily'){
               if(empty($form_data['recurring_day'])){
                     $result[] = $translator->getMessage('DATES_RECURRING_DAY_ERROR');
               } else {
                  if(!is_numeric($form_data['recurring_day'])){
                     $result[] = $translator->getMessage('DATES_RECURRING_NUMERIC_ERROR');
                  }
               }
            } elseif($form_data['recurring_select'] == 'weekly'){
               if(empty($form_data['recurring_week'])){
                     $result[] = $translator->getMessage('DATES_RECURRING_WEEK_ERROR');
               } else {
                  if(!is_numeric($form_data['recurring_week'])){
                     $result[] = $translator->getMessage('DATES_RECURRING_NUMERIC_ERROR');
                  }
               }
               if(empty($form_data['recurring_week_days'])){
                     $result[] = $translator->getMessage('DATES_RECURRING_WEEKDAYS_ERROR');
               }
            } elseif($form_data['recurring_select'] == 'monthly'){
               if(empty($form_data['recurring_month'])){
                     $result[] = $translator->getMessage('DATES_RECURRING_MONTH_ERROR');
               } else {
                  if(!is_numeric($form_data['recurring_month'])){
                     $result[] = $translator->getMessage('DATES_RECURRING_NUMERIC_ERROR');
                  }
               }
            } elseif($form_data['recurring_select'] == 'yearly'){
               if(empty($form_data['recurring_year'])){
                     $result[] = $translator->getMessage('DATES_RECURRING_YEAR_ERROR');
               } else {
                  if(!is_numeric($form_data['recurring_year'])){
                     $result[] = $translator->getMessage('DATES_RECURRING_NUMERIC_ERROR');
                  } else {
                     if(($form_data['recurring_year_every'] == '1'
                         or $form_data['recurring_year_every'] == '3'
                         or $form_data['recurring_year_every'] == '5'
                         or $form_data['recurring_year_every'] == '7'
                         or $form_data['recurring_year_every'] == '8'
                         or $form_data['recurring_year_every'] == '10'
                         or $form_data['recurring_year_every'] == '12') and ($form_data['recurring_year'] > 31)){
                        $result[] = $translator->getMessage('DATES_RECURRING_YEAR_TO_MANY_DAYS_ERROR');
                     }
                     if(($form_data['recurring_year_every'] == '4'
                         or $form_data['recurring_year_every'] == '6'
                         or $form_data['recurring_year_every'] == '9'
                         or $form_data['recurring_year_every'] == '11') and ($form_data['recurring_year'] > 30)){
                        $result[] = $translator->getMessage('DATES_RECURRING_YEAR_TO_MANY_DAYS_ERROR');
                     }
                     if(($form_data['recurring_year_every'] == '2') and ($form_data['recurring_year'] > 29)){
                        $result[] = $translator->getMessage('DATES_RECURRING_YEAR_TO_MANY_DAYS_ERROR');
                     }
                  }
               }
            }
            if(empty($form_data['recurring_end_date'])){
                  $result[] = $translator->getMessage('DATES_DATE_NOT_VALID');
            } else {
               if ( !isDatetimeCorrect($this->_environment->getSelectedLanguage(),$form_data['recurring_end_date'],'00:00')) {
                  $result[] = $translator->getMessage('DATES_DATE_NOT_VALID');
               }
            }
         }
      }
      return $result;
   }
}