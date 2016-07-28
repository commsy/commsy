	<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_step_controller implements cs_rubric_popup_controller {

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
    	$current_context = $this->_environment->getCurrentContextItem();

        // assign template vars
        $this->assignTemplateVars();

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

			// TODO: check rights
			$this->_popup_controller->assign('item', 'title', $item->getTitle());
			
			$this->_popup_controller->assign('item', 'description', $item->getDescription());

			$this->_popup_controller->assign('item', 'timeType', $item->getTimeType());
			$this->_popup_controller->assign('item', 'minutes', $item->getMinutes());
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
    	return array(
    			array(	'name'		=> 'title',
    					'type'		=> 'text',
    					'mandatory' => true),
    			array(	'name'		=> 'description',
    					'type'		=> 'text',
    					'mandatory'	=> false)
    	);
    }

    public function save($form_data, $additional = array()) {
    	$session = $this->_environment->getSessionItem();

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

    	// get the current user and room
    	$room_item = $this->_environment->getCurrentContextItem();

    	// get history from session
    	$history = $session->getValue('history');

    	// load item from database
    	$step_item = null;
    	if($form_data["iid"] !== 'NEW') {
    		$step_manager = $this->_environment->getStepManager();
    		$step_item = $step_manager->getItem($form_data["iid"]);
    	}


       if ( $form_data["iid"] != 'NEW' and !isset($step_item) ) {

        } elseif ( !(($form_data["iid"] == 'NEW' and $current_user->isUser()) or
        ($form_data["iid"] != 'NEW' and isset($step_item) and
        $step_item->mayEdit($current_user))) ) {
        }else{

		$translator = $this->_environment->getTranslationObject();
		if($this->_popup_controller->checkFormData()) {
				// Create new item
				if ( !isset($step_item) ) {
					$step_manager = $this->_environment->getStepManager();
					$step_item = $step_manager->getNewItem();
					$step_item->setContextID($this->_environment->getCurrentContextID());
					$step_item->setCreatorItem($current_user);
					$step_item->setCreationDate(getCurrentDateTimeInMySQL());
					$step_item->setTodoID($additional["ref_iid"]);
				}

				// set modificator and modification date
				$step_item->setModificatorItem($current_user);
				$step_item->setModificationDate(getCurrentDateTimeInMySQL());

				// set attributes
				if(isset($form_data["title"])) $step_item->setTitle($form_data["title"]);

				if(isset($form_data["description"])) $step_item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));

				if(isset($form_data["minutes"])) {
					$minutes = $form_data["minutes"];
					$minutes = str_replace(",", ".", $minutes);

					if(isset($form_data["time_type"])) {
						$step_item->setTimeType($form_data["time_type"]);

						switch($form_data["time_type"]) {
							case 2: $minutes = $minutes * 60; break;
							case 3: $minutes = $minutes * 60 * 8; break;
						}
					}

					$step_item->setMinutes($minutes);
				}

			    // already attached files
			    $file_ids = array();
			    foreach($form_data as $key => $value) {
			    	if(mb_substr($key, 0, 5) === 'file_') {
			    		$file_ids[] = $value;
			    	}
			    }

			    // this will handle already attached files as well as adding new files
			    $this->_popup_controller->getUtils()->setFilesForItem($step_item, $file_ids, $form_data["files"]);


				// save
				$step_item->save();

				$todo_manager = $this->_environment->getTodoManager();
				$todo_item = $todo_manager->getItem($additional["ref_iid"]);

				$status = $todo_item->getStatus();
				if($status == $translator->getMessage("TODO_NOT_STARTED")) {
					$todo_item->setStatus(2);
				}
				$todo_item->setModificationDate(getCurrentDateTimeInMySQL());
				$todo_item->save();

				$this->_popup_controller->setSuccessfullItemIDReturn($todo_item->getItemID());
				}
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