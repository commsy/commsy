<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_institution_controller implements cs_rubric_popup_controller {
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

				$this->_popup_controller->assign('item', 'name', $item->getName());

								// old formating
			   $c_old_text_formating_array = $this->_environment->getConfiguration('c_old_text_formating_array');
			   if ( !empty($c_old_text_formating_array)
				     and is_array($c_old_text_formating_array)
					  and in_array($this->_environment->getCurrentContextID(),$c_old_text_formating_array)
			      ) {
				   $this->_with_old_text_formating = true;
			   }
				if ( $this->_with_old_text_formating ) {
					$desc_string = $item->getDescription();
					$desc_string = preg_replace('/(?:[ \t]*(?:\n|\r\n?)){2,}/', "\n", $desc_string);
					$desc_string = nl2br($desc_string);
					$desc_string = str_replace('<br /><br />','<br />',$desc_string);
					$this->_popup_controller->assign('item', 'description', $desc_string);
				} else {
				   $this->_popup_controller->assign('item', 'description', $item->getDescription());
				}
				
 				$this->_popup_controller->assign('item', 'public', $item->isPublic());
			    $this->_popup_controller->assign('item', 'picture', $item->getPicture());

			}else{
 				if ($current_context->isCommunityRoom()){
 						$this->_popup_controller->assign('item', 'public', '0');
 				}else{
 						$this->_popup_controller->assign('item', 'public', '1');
 				}

			}
    }

    public function save($form_data, $additional = array()) {
        $environment = $this->_environment;
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();
        $text_converter = $this->_environment->getTextConverter();

        if(isset($additional['action']) && $additional['action'] === 'upload_picture') $current_iid = $additional['iid'];
        else $current_iid = $form_data['iid'];

        if (isset($form_data['editType'])){
			$this->_edit_type = $form_data['editType'];
        }

        $translator = $this->_environment->getTranslationObject();

        if($current_iid === 'NEW') {
            $item = null;
        } else {
            $item_manager = $this->_environment->getInstitutionManager();
            $item = $item_manager->getItem($current_iid);
        }
        
        $this->_popup_controller->performChecks($item, $form_data, $additional);

        // TODO: check rights */
		/****************************/
        if ( $current_iid != 'NEW' and !isset($item) ) {

        } elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
        ($current_iid != 'NEW' and isset($item) and
        $item->mayEdit($current_user))) ) {

		/****************************/


        } elseif($this->_edit_type != 'normal'){
 			$this->cleanup_session($current_iid);
            // Set modificator and modification date
            $current_user = $environment->getCurrentUserItem();
            $item->setModificatorItem($current_user);

            if ($this->_edit_type == 'buzzwords'){
                // buzzwords
                $item->setBuzzwordListByID($form_data['buzzwords']);
            }
            if ($this->_edit_type == 'tags'){
                // buzzwords
                $item->setTagListByID($form_data['tags']);
            }
            $item->save();
            // save session
            $session = $this->_environment->getSessionItem();
            $this->_environment->getSessionManager()->save($session);

            // Add modifier to all users who ever edited this item
            $manager = $environment->getLinkModifierItemManager();
            $manager->markEdited($item->getItemID());

            // set return
            $this->_popup_controller->setSuccessfullItemIDReturn($item->getItemID(),CS_INSTITUTION_TYPE);

        }else { //Acces granted
			$this->cleanup_session($current_iid);


			// upload picture
			if(isset($additional['action']) && $additional['action'] === 'upload_picture') {
				if($this->_popup_controller->checkFormData('picture_upload')) {
					/* handle institution picture upload */
					if(!empty($additional["fileInfo"])) {
						$srcfile = $additional["fileInfo"]["file"];

						// determ new file name
						$filename = 'cid' . $this->_environment->getCurrentContextID() . '_iid' . $item->getItemID() . '_'. $additional["fileInfo"]["name"];

						// copy file and set picture
						$disc_manager = $this->_environment->getDiscManager();

						$disc_manager->copyFile($srcfile, $filename, true);
						$item->setPicture($filename);
						$item->save();

						// set return
						$this->_popup_controller->setSuccessfullDataReturn($filename);
					}
				}
			} else {
				// save item
				if($this->_popup_controller->checkFormData('general')) {
					$session = $this->_environment->getSessionItem();
					$item_is_new = false;
					// Create new item
					if ( !isset($item) ) {
						$item_manager = $environment->getInstitutionManager();
						$item = $item_manager->getNewItem();
						$item->setContextID($environment->getCurrentContextID());
						$current_user = $environment->getCurrentUserItem();
						$item->setCreatorItem($current_user);
						$item->setCreationDate(getCurrentDateTimeInMySQL());
						$item->setModificationDate(getCurrentDateTimeInMySQL());
               			$item->setLabelType(CS_INSTITUTION_TYPE);
						$item_is_new = true;
					}

					// Set modificator and modification date
					$current_user = $environment->getCurrentUserItem();
					$item->setModificatorItem($current_user);

					// Set attributes
					if ( isset($form_data['name']) ) {
						$item->setName($text_converter->sanitizeHTML($form_data['name']));
					}
					if ( isset($form_data['description']) ) {
						$item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));
					}
					if (isset($form_data['public'])) {
						$item->setPublic($form_data['public']);
					}


					if($item->getPicture() && isset($form_data['delete_picture'])) {
						$disc_manager = $this->_environment->getDiscManager();

						if($disc_manager->existsFile($item->getPicture())) $disc_manager->unlinkFile($item->getPicture());
						$item->setPicture('');
					}

					// Save item
					$item->save();

					// this will update the right box list
					if($item_is_new){
						if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.CS_INSTITUTION_TYPE.'_index_ids')){
							$id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.CS_INSTITUTION_TYPE.'_index_ids'));
						} else {
							$id_array =  array();
						}

						$id_array[] = $item->getItemID();
						$id_array = array_reverse($id_array);
						$session->setValue('cid'.$environment->getCurrentContextID().'_'.CS_INSTITUTION_TYPE.'_index_ids',$id_array);
					}

					// save session
					$this->_environment->getSessionManager()->save($session);

					// Add modifier to all users who ever edited this item
					$manager = $environment->getLinkModifierItemManager();
					$manager->markEdited($item->getItemID());

					// set return
                	$this->_popup_controller->setSuccessfullItemIDReturn($item->getItemID());
				}
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

    }


    public function getFieldInformation($sub = '') {
		if ($this->_edit_type == 'normal'){
			$return = array(
				'general'			=> array(
					array(	'name'		=> 'name',
							'type'		=> 'text',
							'mandatory' => true)
				),
				'description'			=> array(
					array(	'name'		=> 'description',
							'type'		=> 'text',
							'mandatory' => false)
				),
				'public'			=> array(
					array(	'name'		=> 'public',
							'type'		=> 'radio',
							'mandatory' => true)
				),
				'upload_picture'	=> array(
				)
			);

			return $return[$sub];
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