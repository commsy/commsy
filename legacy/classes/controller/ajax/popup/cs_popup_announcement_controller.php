<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');
require_once('classes/controller/ajax/popup/cs_rubric_popup_main_controller.php');


class cs_popup_announcement_controller extends cs_rubric_popup_main_controller implements cs_rubric_popup_controller {
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
		        if ($item->getSeconddateTime() != '') {
					$this->_popup_controller->assign('item', 'dayEnd', mb_substr($item->getSeconddateTime(),0,10));
		            $this->_popup_controller->assign('item', 'timeEnd', getTimeInLang($item->getSeconddateTime()));
		        }else{
            		$time = $current_context->getTimeSpread();
 					$this->_popup_controller->assign('item', 'dayEnd', DateAdd($time,date("Y-m-d"),"Y-m-d"));
		            $this->_popup_controller->assign('item', 'timeEnd', date("H:m"));
		        }

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
			}else{
            	$time = $current_context->getTimeSpread();
 				$this->_popup_controller->assign('item', 'dayEnd', DateAdd($time,date("Y-m-d"),"Y-m-d"));
		        $this->_popup_controller->assign('item', 'timeEnd', date("H:m"));
 				if ($current_context->isCommunityRoom()){
 						$this->_popup_controller->assign('item', 'public', '1');
 				}else{
 						$this->_popup_controller->assign('item', 'public', '0');
 				}

			}
    }

    public function save($form_data, $additional = array()) {
        $environment = $this->_environment;
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();
        $text_converter = $this->_environment->getTextConverter();

        $current_iid = $form_data['iid'];

        if (isset($form_data['editType'])){
			$this->_edit_type = $form_data['editType'];
        }

        $translator = $this->_environment->getTranslationObject();

        if($current_iid === 'NEW') {
            $announcement_item = null;
        } else {
            $announcement_manager = $this->_environment->getAnnouncementManager();
            $announcement_item = $announcement_manager->getItem($current_iid);
        }

        $this->_popup_controller->performChecks($announcement_item, $form_data, $additional);

        // TODO: check rights */
		/****************************/
        if ( $current_iid != 'NEW' and !isset($announcement_item) ) {

        } elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
        ($current_iid != 'NEW' and isset($announcement_item) and
        $announcement_item->mayEdit($current_user))) ) {

		/****************************/


        } elseif($this->_edit_type != 'normal'){
 			$this->cleanup_session($current_iid);
            // Set modificator and modification date
            $current_user = $environment->getCurrentUserItem();
            $announcement_item->setModificatorItem($current_user);

            if ($this->_edit_type == 'buzzwords'){
                // buzzwords
                $announcement_item->setBuzzwordListByID($form_data['buzzwords']);
            }
            if ($this->_edit_type == 'tags'){
                // buzzwords
                $announcement_item->setTagListByID($form_data['tags']);
            }
            $announcement_item->save();
            // save session
            $session = $this->_environment->getSessionItem();
            $this->_environment->getSessionManager()->save($session);

            // Add modifier to all users who ever edited this item
            $manager = $environment->getLinkModifierItemManager();
            $manager->markEdited($announcement_item->getItemID());

            // set return
            $this->_popup_controller->setSuccessfullItemIDReturn($announcement_item->getItemID(),CS_ANNOUNCEMENT_TYPE);

        }else { //Acces granted
			$this->cleanup_session($current_iid);

			// save item
			if($this->_popup_controller->checkFormData()) {
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
                    $announcement_item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));
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

                // already attached files
                $file_ids = array();
                foreach($form_data as $key => $value) {
                	if(mb_substr($key, 0, 5) === 'file_') {
                		$file_ids[] = $value;
                	}
                }

                // this will handle already attached files as well as adding new files
                $this->_popup_controller->getUtils()->setFilesForItem($announcement_item, $file_ids, $form_data["files"]);


            	if(isset($form_data['private_editing'])) {
            		$announcement_item->setPrivateEditing('0');
            	} else {
            		$announcement_item->setPrivateEditing('1');
            	}

                if (isset($form_data['rights_tab'])){
	                if (isset($form_data['public'])) {
	                    $announcement_item->setPublic($form_data['public']);
	                }
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
	                    $announcement_item->setModificationDate($dt_hiding_datetime);
	                }else{
	                    if($announcement_item->isNotActivated()){
	                        $announcement_item->setModificationDate(getCurrentDateTimeInMySQL());
	                    }
	                }
                } else {
                    if (isset($form_data['public'])) {
	                    $announcement_item->setPublic($form_data['public']);
	                }
                }

                // buzzwords
				// save buzzwords
                $this->saveBuzzwords($environment, $announcement_item, $form_data['buzzwords']);

                // tags
                if (isset($form_data['tags_tab'])){
                	$announcement_item->setTagListByID($form_data['tags']);
                }

                // Save item
                $announcement_item->save();

                // this will update the right box list
                if($item_is_new){
	                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.CS_ANNOUNCEMENT_TYPE.'_index_ids')){
	                    $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.CS_ANNOUNCEMENT_TYPE.'_index_ids'));
	                } else {
	                    $id_array =  array();
	                }

                    $id_array[] = $announcement_item->getItemID();
                    $id_array = array_reverse($id_array);
                    $session->setValue('cid'.$environment->getCurrentContextID().'_'.CS_ANNOUNCEMENT_TYPE.'_index_ids',$id_array);
                }

                // save session
                $this->_environment->getSessionManager()->save($session);

                // Add modifier to all users who ever edited this item
                $manager = $environment->getLinkModifierItemManager();
                $manager->markEdited($announcement_item->getItemID());

                // set return
                $this->_popup_controller->setSuccessfullItemIDReturn($announcement_item->getItemID(),CS_ANNOUNCEMENT_TYPE);
            }
        }
    }


    public function isOption( $option, $string ) {
        return (strcmp( $option, $string ) == 0) || (strcmp( htmlentities($option, ENT_NOQUOTES, 'UTF-8'), $string ) == 0 || (strcmp( $option, htmlentities($string, ENT_NOQUOTES, 'UTF-8') )) == 0 );
    }

    private function assignTemplateVars() {
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
							'type'		=> 'textarea',
							'mandatory'	=> false),
					array(	'name'		=> 'dayEnd',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'timeEnd',
							'type'		=> 'text',
							'mandatory'	=> false)
				);
			}else{
				return array();
			}
    }

	public function cleanup_session($current_iid) {
		$environment = $this->_environment;
		$session = $this->_environment->getSessionItem();

		$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
		$session->unsetValue($environment->getCurrentModule().'_add_tags');
		$session->unsetValue($environment->getCurrentModule().'_add_files');
		$session->unsetValue($current_iid.'_post_vars');
	}
}