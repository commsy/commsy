	<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_annotation_controller implements cs_rubric_popup_controller {

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
        }
        
        $this->_popup_controller->assign('item', 'is_new', ($item === null));
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
    	
    	// get the current user and room
    	$current_user = $this->_environment->getCurrentUserItem();
    	$room_item = $this->_environment->getCurrentContextItem();
    	
    	// get history from session
    	$history = $session->getValue('history');
    	
    	// load item from database
    	$annotation_item = null;
    	if($form_data["iid"] !== 'NEW') {
    		$annotation_manager = $this->_environment->getAnnotationManager();
    		$annotation_item = $annotation_manager->getItem($form_data["iid"]);
    	}
    		
    	// save the history
    	if(isset($_GET['mode']) && $_GET['mode'] === 'annotate' && $history[0]['module'] !== 'annotation') {
    		$session->setValue('annotation_history_context', $history[0]['context']);
    		$session->setValue('annotation_history_module', $history[0]['module']);
    		$session->setValue('annotation_history_function', $history[0]['function']);
    		$session->setValue('annotation_history_parameter', $history[0]['parameter']);
    	}
    	
    	// check access rights
    	$item_manager = $this->_environment->getItemManager();
    	if($form_data["iid"] !== 'NEW' && !isset($annotation_item)) {
    		/*
    		 * $params = array();
    		$params['environment'] = $environment;
    		$params['with_modifying_actions'] = true;
    		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
    		unset($params);
    		$errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
    		$page->add($errorbox);
    		*/
    	} elseif(	!(($form_data["iid"] === 'NEW' && $current_user->isUser()) ||
    			($form_data["iid"] !== 'NEW' && isset($annotation_item) && $annotation_item->mayEdit($current_user)) ||
    			($form_data["iid"] === 'NEW' && isset($_GET['ref_iid']) && $item_manager->getExternalViewerForItem($_GET['ref_iid'], $current_user->getUserID())))) {
    		/*
    		 *    $params = array();
    		$params['environment'] = $environment;
    		$params['with_modifying_actions'] = true;
    		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
    		unset($params);
    		$errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
    		$page->add($errorbox);
    		*/
    		
    	} else {
    		$translator = $this->_environment->getTranslationObject();
    	
    		// load form data from postvars
    		if(!empty($_POST)) {
    			$session_post_vars = $_POST;
    			if(isset($post_file_ids) && !empty($post_file_ids)) {
    				$session_post_vars['filelist'] = $post_file_ids;
    			}
    			//$form->setFormPost($session_post_vars);
    		}
    	
    		// load form data from database
    		elseif(isset($annotation_item)) {
    			/*
    			 * $form->setItem($annotation_item);
    	
    			// Files
    			$file_list = $annotation_item->getFileList();
    			if ( !$file_list->isEmpty() ) {
    			$file_array = array();
    			$file_item = $file_list->getFirst();
    			while ( $file_item ) {
    			$temp_array = array();
    			$temp_array['name'] = $file_item->getDisplayName();
    			$temp_array['file_id'] = (int)$file_item->getFileID();
    			$file_array[] = $temp_array;
    			$file_item = $file_list->getNext();
    			}
    			if ( !empty($file_array)) {
    			$session->setValue($environment->getCurrentModule().'_add_files', $file_array);
    			}
    			}
    			*/
    		}
    	
    		// create data for a new item
    		elseif($form_data["iid"] === 'NEW') {
    			/*
    			 * $form->setRefID($_GET['ref_iid']);
    			if ( !empty($_GET['version']) ) {
    			$form->setVersion($_GET['version']);
    			}
    			*/
    		}
    	
    		else {
    			include_once('functions/error_functions.php');
    			trigger_error('annotation_edit was called in an unknown manner', E_USER_ERROR);
    		}
    	
    		if($session->issetValue($this->_environment->getCurrentModule() . '_add_files')) {
    			//$form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
    		}
    	
    		/*
    		 * $form->prepareForm();
    		$form->loadValues();
    		*/
    	
    		// save item
    			if($this->_popup_controller->checkFormData()) {
    				$user = $this->_environment->getCurrentUserItem();
    	
    				// create new item
    				$isNew = false;
    				if($annotation_item === null) {
    					$annotation_manager = $this->_environment->getAnnotationManager();
    					$annotation_item = $annotation_manager->getNewItem();
    					$annotation_item->setContextID($this->_environment->getCurrentContextID());
    					$annotation_item->setCreatorItem($user);
    					$annotation_item->setCreationDate(getCurrentDateTimeInMySQL());
    					
    					if ($additional["annotatedId"]) {
    						$annotation_item->setLinkedItemID($additional["annotatedId"]);
    					} else if (isset($additional["portfolioId"])) {
    						$annotation_item->setLinkedItemID($additional["portfolioId"]);
    					}
    					
    					if ($additional["versionId"]) {
    						$annotation_item->setLinkedVersionItemID($additional["versionId"]);
    					}
    					
    					$isNew = true;
    				}
    	
    				// set modificator and modification date
    				$annotation_item->setModificatorItem($user);
    				$annotation_item->setModificationDate(getCurrentDateTimeInMySQL());
    	
    				// set attributes
    				if(isset($form_data['title'])) {
    					$annotation_item->setTitle($form_data['title']);
    				}
    	
    				if(isset($form_data['description'])) {
    					$annotation_item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));
    				}
    	
    				// already attached files
		            $file_ids = array();
		            foreach($form_data as $key => $value) {
		            	if(mb_substr($key, 0, 5) === 'file_') {
		            		$file_ids[] = $value;
		            	}
		            }
		            
		            // this will handle already attached files as well as adding new files
		            $this->_popup_controller->getUtils()->setFilesForItem($annotation_item, $file_ids, $form_data["files"]);
    	
    				// add modifier to all users who ever edited this item
    				$manager = $this->_environment->getLinkModifierItemManager();
    				$manager->markEdited($annotation_item->getItemID());
    				
    				$annotation_item->save();
    				
    				// reset id array
    				$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_annotation_index_ids', array($annotation_item->getItemID()));
    				
    				// check for portfolio link
    				if (isset($additional["portfolioId"])) {
    					
    					if ($isNew === true) {
    						$portfolioManager = $this->_environment->getPortfolioManager();
    						$portfolioManager->setPortfolioAnnotation($additional["portfolioId"], $annotation_item->getItemID(), $additional["portfolioRow"], $additional["portfolioColumn"]);
    					}
    					
    					$this->_popup_controller->setSuccessfullItemIDReturn($annotation_item->getItemID());
    				} else {
    					$this->_popup_controller->setSuccessfullItemIDReturn($annotation_item->getLinkedItemID());
    				}
    				
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