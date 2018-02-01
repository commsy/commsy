<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');
require_once('classes/controller/ajax/popup/cs_rubric_popup_main_controller.php');

class cs_popup_material_controller extends cs_rubric_popup_main_controller implements cs_rubric_popup_controller {
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
    		$translator = $this->_environment->getTranslationObject();
    		$current_context = $this->_environment->getCurrentContextItem();
    		$current_user = $this->_environment->getCurrentUserItem();

			// assign template vars
			$this->assignTemplateVars();

			if ($current_context->withWorkflow()){
				$this->_popup_controller->assign('item', 'with_workflow', true);

				// workflow traffic light
				if($current_context->withWorkflowTrafficLight()) {
					$this->_popup_controller->assign('item', 'with_workflow_traffic_light', true);

					$description = array(
						'green'		=>	($current_context->getWorkflowTrafficLightTextGreen() != '') ?
											$current_context->getWorkflowTrafficLightTextGreen() :
												$translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT'),
						'yellow'	=>	($current_context->getWorkflowTrafficLightTextYellow() != '') ?
											$current_context->getWorkflowTrafficLightTextYellow() :
												$translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT'),
						'red'		=>	($current_context->getWorkflowTrafficLightTextRed() != '') ?
											$current_context->getWorkflowTrafficLightTextRed() :
												$translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT'),
					);

					$this->_popup_controller->assign('item', 'workflow_traffic_light_description', $description);
				} else {
					$this->_popup_controller->assign('item', 'with_workflow_traffic_light', false);
				}

				// workflow resubmission
				if($current_context->withWorkflowResubmission()) {
					$this->_popup_controller->assign('item', 'with_workflow_resubmission', true);

					// creator
					if($item !== null) {
						$creator_item = $item->getCreatorItem();
					} else {
						$creator_item = $current_user;
					}

					$this->_popup_controller->assign('item', 'workflow_creator_id', $creator_item->getItemID());
					$this->_popup_controller->assign('item', 'workflow_creator_fullname', $creator_item->getFullName());



					// modifier
					$modifier_array = array();

					if($item !== null) {
						$link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
						$user_manager = $this->_environment->getUserManager();
						$modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());


						foreach($modifiers as $modifier_id) {
							$modificator = $user_manager->getItem($modifier_id);

							// links only at accessible contact pages
							if(isset($modificator) && $modificator->isRoot()) {
								$modifier_array[]['name'] = $modificator->getFullname();
							} elseif($modificator->getContextID() == $item->getContextID()) {
								if($this->_environment->inProjectRoom()) {
									if(isset($modificator) && !empty($modificator) && $modificator->isUser() && !$modificator->isDeleted() && $modificator->maySee($current_user)) {
										$modifier_array[] = array(
											'name'		=> $modificator->getFullname(),
											'id'		=> $modificator->getItemID()
										);
									} elseif(isset($modificator) && !$modificator->isDeleted()) {
										$modifier_array[]['name'] = $translator->getMessage('COMMON_DELETED_USER');
									} else {
										$modifier_array[]['name'] = $translator->getMessage('COMMON_DELETED_USER');
									}
								} elseif(	($current_user->isUser() && isset($modificator) && $modificator->isVisibleForLoggedIn()) ||
											(!$current_user->isUser() && isset($modificator) && $modificator->isVisibleForAll()) ||
											(isset($modificator) && $this->_environment->getCurrentUserID() == $modificator->getItemID())) {
									if(!$modificator->isDeleted() && $modificator->maySee($current_user)) {
										if(!$this->_environment->inPortal()) {
											$modifier_array[] = array(
												'name'		=> $modificator->getFullname(),
												'id'		=> $modificator->getItemID()
											);
										} else {
											$modifier_array[]['name'] = $modificator->getFullname();
										}
									} else {
										$modifier_array[]['name'] = $translator->getMessage('COMMON_DELETED_USER');
									}
								} elseif($item->mayExternalSee($current_user)) {
									$modifier_array[] = $modificator->getFullname();
								} else {
									if(isset($modificator) && !$modificator->isDeleted()) {
										if($current_user->isGuest()) {
											$modifier_array[]['name'] = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
										} else {
											$modifier_array[]['name'] = $modificator->getFullname();
										}
									} else {
										$modifier_array[]['name'] = $translator->getMessage('COMMON_DELETED_USER');
									}
								}
							}
						}

						$modifier_array = array_unique($modifier_array);
					}

					$this->_popup_controller->assign('item', 'workflow_modifier', $modifier_array);
				} else {
					$this->_popup_controller->assign('item', 'with_workflow_resubmission', false);
				}

				$this->_popup_controller->assign('item', 'with_workflow_validity', $current_context->withWorkflowValidity());
			} else {
				$this->_popup_controller->assign('item', 'with_workflow', false);
			}

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

				if ($data["contextId"]) {
					$this->_popup_controller->assign('item', 'external_viewer', $item->issetExternalViewerStatus());
					$this->_popup_controller->assign('item', 'external_viewer_accounts', $item->getExternalViewerString());
				}

				$this->_popup_controller->assign('item', 'title', $item->getTitle());
				
				$this->_popup_controller->assign('item', 'description', $item->getDescription());
 				
 				$this->_popup_controller->assign('item', 'public', $item->isPublic());
		        $this->_popup_controller->assign('item', 'author',$item->getAuthor());
		        $this->_popup_controller->assign('item', 'bib_kind', $item->getBibKind());
		        $this->_popup_controller->assign('item', 'publisher', $item->getPublisher());
		        $this->_popup_controller->assign('item', 'address', $item->getAddress());
		        $this->_popup_controller->assign('item', 'edition', $item->getEdition());
		        $this->_popup_controller->assign('item', 'series', $item->getSeries());
		        $this->_popup_controller->assign('item', 'volume', $item->getVolume());
		        $this->_popup_controller->assign('item', 'isbn', $item->getISBN());
		        $this->_popup_controller->assign('item', 'issn', $item->getISSN());
		        $this->_popup_controller->assign('item', 'editor', $item->getEditor());
		        $this->_popup_controller->assign('item', 'booktitle', $item->getBooktitle());
		        $this->_popup_controller->assign('item', 'pages', $item->getPages());
		        $this->_popup_controller->assign('item', 'journal', $item->getJournal());
		        $this->_popup_controller->assign('item', 'issue', $item->getIssue());
		        $this->_popup_controller->assign('item', 'thesis_kind', $item->getThesisKind());
		        $this->_popup_controller->assign('item', 'university', $item->getUniversity());
		        $this->_popup_controller->assign('item', 'faculty', $item->getFaculty());
		        $this->_popup_controller->assign('item', 'common', $item->getBibliographicValues());
		        $this->_popup_controller->assign('item', 'url', $item->getURL());
		        $this->_popup_controller->assign('item', 'url_date', $item->getURLDate());
		        $this->_popup_controller->assign('item', 'publishing_date', $item->getPublishingDate());
		        
		        /** Foto Dokumententyp **/
		        $this->_popup_controller->assign('item', 'foto_copyright', $item->getFotoCopyright());
		        $this->_popup_controller->assign('item', 'foto_reason', $item->getFotoReason());
		        $this->_popup_controller->assign('item', 'foto_date', $item->getFotoDate());
		        /** Foto Dokumenttyp **/
		        

		        /** Start Dokumentenverwaltung **/
		        $this->_popup_controller->assign('item', 'document_editor', $item->getDocumentEditor());
		        $this->_popup_controller->assign('item', 'document_maintainer', $item->getDocumentMaintainer());
		        $this->_popup_controller->assign('item', 'document_release_number', $item->getDocumentReleaseNumber());
		        $this->_popup_controller->assign('item', 'document_release_date', $item->getDocumentReleaseDate());
		     	 /** Ende Dokumentenverwaltung **/

		        if ($current_context->withWorkflow()){
		           $this->_popup_controller->assign('item', 'workflow_traffic_light', $item->getWorkflowTrafficLight());
		           $this->_popup_controller->assign('item', 'workflow_resubmission', $item->getWorkflowResubmission());
		           if($item->getWorkflowResubmissionDate() != '' and $item->getWorkflowResubmissionDate() != '0000-00-00 00:00:00'){
		              $this->_popup_controller->assign('item', 'workflow_resubmission_date', substr($item->getWorkflowResubmissionDate(),0,10));
		           } else {
		              $this->_popup_controller->assign('item', 'workflow_resubmission_date', '');
		           }
		           $this->_popup_controller->assign('item', 'workflow_resubmission_who', $item->getWorkflowResubmissionWho());
		           $this->_popup_controller->assign('item', 'workflow_resubmission_who_additional', $item->getWorkflowResubmissionWhoAdditional());
		           $this->_popup_controller->assign('item', 'workflow_resubmission_traffic_light', $item->getWorkflowResubmissionTrafficLight());
				   $this->_popup_controller->assign('item', 'workflow_validity', $item->getWorkflowValidity());
		           if($item->getWorkflowValidityDate() != '' and $item->getWorkflowValidityDate() != '0000-00-00 00:00:00'){
		              $this->_popup_controller->assign('item', 'workflow_validity_date', substr($item->getWorkflowValidityDate(),0,10));
		           } else {
		              $this->_popup_controller->assign('workflow_validity_date', '');
		           }
		           $this->_popup_controller->assign('item', 'workflow_validity_who', $item->getWorkflowValidityWho());
		           $this->_popup_controller->assign('item', 'workflow_validity_who_additional', $item->getWorkflowValidityWhoAdditional());
		           $this->_popup_controller->assign('item', 'workflow_validity_traffic_light', $item->getWorkflowValidityTrafficLight());
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
 				$val = ($this->_environment->inCommunityRoom() || $this->_environment->inProjectRoom() || $this->_environment->inGroupRoom()) ? '1': '0';
 				$this->_popup_controller->assign('item', 'public', $val);
				$val = ($this->_environment->inCommunityRoom() || $this->_environment->inProjectRoom() || $this->_environment->inGroupRoom()) ? false : true;
		    	$this->_popup_controller->assign('item', 'private_editing', $val);
		        if ($current_context->withWorkflow()){
		           $this->_popup_controller->assign('item', 'workflow_traffic_light',$current_context->getWorkflowTrafficLightDefault());
		           $this->_popup_controller->assign('item', 'workflow_resubmission', false);
		           $this->_popup_controller->assign('item', 'workflow_resubmission_who', 'creator');
		           $this->_popup_controller->assign('item', 'workflow_resubmission_traffic_light', '3_none');
		           $this->_popup_controller->assign('item', 'workflow_validity', false);
		           $this->_popup_controller->assign('item', 'workflow_validity_who', 'creator');
		           $this->_popup_controller->assign('item', 'workflow_validity_traffic_light', '3_none');
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
            $item = null;
        } else {
            $manager = $this->_environment->getMaterialManager();
            if(isset($additional['version_id']) and ($additional['part'] != 'version')){
               $item = $manager->getItemByVersion($current_iid, $additional['version_id']);
            } else {
               $item = $manager->getItem($current_iid);
            }
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
            	$new_buzzword = '';
            	$buzzwords = array();
            	$buzzword_manager = $this->_environment->getLabelManager();
            	$buzzword_manager->resetLimits();
            	$buzzword_manager->setContextLimit($environment->getCurrentContextID());
            	$buzzword_manager->setTypeLimit('buzzword');
            	$buzzword_manager->select();
            	$buzzword_list = $buzzword_manager->get();
            	$buzzword_ids = $buzzword_manager->getIDArray();
            	if (isset($form_data['buzzwords'])){
            		foreach($form_data['buzzwords'] as $buzzword){
            			if (!in_array($buzzword,$buzzword_ids)){
            				$new_buzzword = $buzzword;
            			}else{
            				$buzzwords[] =	$buzzword;
            			}
            		}
            	}

                // buzzwords
                $item->setBuzzwordListByID($buzzwords);
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
            $this->_popup_controller->setSuccessfullItemIDReturn($item->getItemID(),CS_MATERIAL_TYPE);

        } else { //Acces granted
			$this->cleanup_session($current_iid);

			$check_passed = $this->_popup_controller->checkFormData('general');
			if($check_passed === true && $form_data['bib_kind'] !== 'none') {
				$check_passed = $this->_popup_controller->checkFormData($form_data['bib_kind']);
			}

			// save item
			if($check_passed === true) {
                $session = $this->_environment->getSessionItem();
                $item_is_new = false;
                // Create new item
                if ( !isset($item) ) {
                    $manager = $environment->getMaterialManager();
                    $item = $manager->getNewItem();
                    $item->setContextID($current_context->getItemID());
                    $item->setCreatorItem($current_user);
                    $item->setCreationDate(getCurrentDateTimeInMySQL());
                    $item_is_new = true;
                }

                // Create new version button pressed
                if($additional['part'] == 'version') {
                   $new_version_id = $item->getVersionID()+1;
                   $new_version = true;
                   $item = $item->cloneCopy($new_version);
                   $item->setVersionID($new_version_id);
                   $infoBox_forAutoNewVersion = '';
                }

                // Set modificator and modification date
                $item->setModificatorItem($current_user);

                // Set attributes
                if ( isset($form_data['title']) ) {
                    $item->setTitle($form_data['title']);
                }
                if ( isset($form_data['description']) ) {
                    $item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));
                }


                // already attached files
                $file_ids = array();
                foreach($form_data as $key => $value) {
                	if(mb_substr($key, 0, 5) === 'file_') {
                		$file_ids[] = $value;
                	}
                }

                // this will handle already attached files as well as adding new files
                $this->_popup_controller->getUtils()->setFilesForItem($item, $file_ids, $form_data["files"]);

                if (isset($form_data["external_viewer"])) {
                	$item->setPrivateEditing('0');
                } else {
                	if(isset($form_data['private_editing'])) {
                		$item->setPrivateEditing('0');
                	} else {
                		$item->setPrivateEditing('1');
                	}
                }

                if (isset($form_data['rights_tab'])){
	                if (isset($form_data['public'])) {
	                    $item->setPublic($form_data['public']);
	                }
	                if ( isset($form_data['public']) ) {
	                    if ( $item->isPublic() != $form_data['public'] ) {
	                        $item->setPublic($form_data['public']);
	                    }
	                } else {
	                    if ( isset($form_data['private_editing']) ) {
	                        $item->setPrivateEditing('0');
	                    } else {
	                        $item->setPrivateEditing('1');
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
	                    $item->setModificationDate($dt_hiding_datetime);
	                }else{
	                    if($item->isNotActivated()){
	                        $item->setModificationDate(getCurrentDateTimeInMySQL());
	                    }
	                }
                }

                // set bibliographic
                $this->setBibliographic($form_data, $item);

	            /** Start Dokumentenverwaltung **/
	            if ( isset( $form_data['document_editor']) and $item->getDocumentEditor() !=  $form_data['document_editor'] ) {
	               $item->setDocumentEditor( $form_data['document_editor']);
	            }
	            if ( isset( $form_data['document_maintainer']) and $item->getDocumentMaintainer() !=  $form_data['document_maintainer'] ) {
	               $item->setDocumentMaintainer( $form_data['document_maintainer']);
	            }
	            if ( isset( $form_data['document_release_number']) and $item->getDocumentReleaseNumber() !=  $form_data['document_release_number'] ) {
	               $item->setDocumentReleaseNumber( $form_data['document_release_number']);
	            }
	            if ( isset( $form_data['document_release_date']) and $item->getDocumentReleaseDate() !=  $form_data['document_release_date'] ) {
	               $item->setDocumentReleaseDate( $form_data['document_release_date']);
	            }
	            /** Ende Dokumentenverwaltung **/
	            if ( isset( $form_data['foto_copyright']) and $item->getDocumentReleaseDate() !=  $form_data['foto_copyright'] ) {
	            	$item->setFotoCopyright( $form_data['foto_copyright']);
	            }
	            if ( isset( $form_data['foto_reason']) and $item->getDocumentReleaseDate() !=  $form_data['foto_reason'] ) {
	            	$item->setFotoReason( $form_data['foto_reason']);
	            }
	            if ( isset( $form_data['foto_date']) and $item->getDocumentReleaseDate() !=  $form_data['foto_date'] ) {
	            	$item->setFotoDate( $form_data['foto_date']);
	            }

	            if ( isset( $form_data['external_viewer']) and isset( $form_data['external_viewer_accounts']) ) {
	               $user_ids = explode(" ", $form_data['external_viewer_accounts']);
	               $item->setExternalViewerAccounts($user_ids);
	            }else{
	               $item->unsetExternalViewerAccounts();
	            }

	            // workflow
	            if ( isset( $form_data['workflow_traffic_light']) and $item->getWorkflowTrafficLight() !=  $form_data['workflow_traffic_light'] ) {
	               $item->setWorkflowTrafficLight( $form_data['workflow_traffic_light']);
	            }
	            if ( isset( $form_data['workflow_resubmission']) and $item->getWorkflowResubmission() !=  $form_data['workflow_resubmission'] ) {
	               $item->setWorkflowResubmission( $form_data['workflow_resubmission']);
	            } else if (!isset( $form_data['workflow_resubmission'])) {
	               $item->setWorkflowResubmission(0);
	            }
	            if ( isset( $form_data['workflow_resubmission_date']) and $item->getWorkflowResubmissionDate() !=  $form_data['workflow_resubmission_date'] ) {
	               $dt_workflow_resubmission_time = '00:00:00';
	               $dt_workflow_resubmission_date =  $form_data['workflow_resubmission_date'];
	               $dt_workflow_resubmission_datetime = '';
	               $converted_day_start = convertDateFromInput( $form_data['workflow_resubmission_date'],$environment->getSelectedLanguage());
	               if ($converted_day_start['conforms'] == TRUE) {
	                  $dt_workflow_resubmission_datetime = $converted_day_start['datetime'].' ';
	                  $dt_workflow_resubmission_datetime .= $dt_workflow_resubmission_time;
	               }
	               $item->setWorkflowResubmissionDate($dt_workflow_resubmission_datetime);
	            } else {
	               $item->setWorkflowResubmissionDate('');
	            }
	            if ( isset( $form_data['workflow_resubmission_who']) and $item->getWorkflowResubmissionWho() !=  $form_data['workflow_resubmission_who'] ) {
	               $item->setWorkflowResubmissionWho( $form_data['workflow_resubmission_who']);
	            }
	            if ( isset( $form_data['workflow_resubmission_who_additional']) and !empty( $form_data['workflow_resubmission_who_additional'])) {
	               $item->setWorkflowResubmissionWhoAdditional( $form_data['workflow_resubmission_who_additional']);
	            }
	            if ( isset( $form_data['workflow_resubmission_traffic_light']) and $item->getWorkflowResubmissionTrafficLight() !=  $form_data['workflow_resubmission_traffic_light'] ) {
	               $item->setWorkflowResubmissionTrafficLight( $form_data['workflow_resubmission_traffic_light']);
	            }

	            if ( isset( $form_data['workflow_validity']) and $item->getWorkflowValidity() !=  $form_data['workflow_validity'] ) {
	               $item->setWorkflowValidity( $form_data['workflow_validity']);
	            } else if (!isset( $form_data['workflow_validity'])) {
	               $item->setWorkflowValidity(0);
	            }
	            if ( isset( $form_data['workflow_validity_date']) and $item->getWorkflowValidityDate() !=  $form_data['workflow_validity_date'] ) {
	               $dt_workflow_validity_time = '00:00:00';
	               $dt_workflow_validity_date =  $form_data['workflow_validity_date'];
	               $dt_workflow_validity_datetime = '';
	               $converted_day_start = convertDateFromInput( $form_data['workflow_validity_date'],$environment->getSelectedLanguage());
	               if ($converted_day_start['conforms'] == TRUE) {
	                  $dt_workflow_validity_datetime = $converted_day_start['datetime'].' ';
	                  $dt_workflow_validity_datetime .= $dt_workflow_resubmission_time;
	               }
	               $item->setWorkflowValidityDate($dt_workflow_validity_datetime);
	            } else {
	               $item->setWorkflowValidityDate('');
	            }
	            if ( isset( $form_data['workflow_validity_who']) and $item->getWorkflowValidityWho() !=  $form_data['workflow_validity_who'] ) {
	               $item->setWorkflowValidityWho( $form_data['workflow_validity_who']);
	            }
	            if ( isset( $form_data['workflow_validity_who_additional']) and !empty( $form_data['workflow_validity_who_additional'])) {
	               $item->setWorkflowValidityWhoAdditional( $form_data['workflow_validity_who_additional']);
	            }
	            if ( isset( $form_data['workflow_validity_traffic_light']) and $item->getWorkflowValidityTrafficLight() !=  $form_data['workflow_validity_traffic_light'] ) {
	               $item->setWorkflowValidityTrafficLight( $form_data['workflow_validity_traffic_light']);
	            }

	            if ( $current_context->isCommunityRoom() and $current_context->isOpenForGuests() ) {
	               $old_world_public = $item->getWorldPublic();
	               if ( ( isset( $form_data['world_public']) and $old_world_public == 0) or
	                    ( !isset( $form_data['world_public']) and $old_world_public == 2 and !$current_user->isModerator())  ){               // Request for world public
	                  $item->setWorldPublic(1);
	                  $createATask = 'TASK_REQUEST_MATERIAL_WORLDPUBLIC';
	               } elseif ( isset( $form_data['world_public']) and $old_world_public == 1 ) {
	                  $item->setWorldPublic(0);
	                  $createATask = 'TASK_CANCEL_MATERIAL_WORLDPUBLIC';
	               } elseif ( isset( $form_data['world_public']) and $old_world_public == 2 ) {
	                  $item->setWorldPublic(0);
	                  $createATask = '';
	               } else {
	                  $createATask = '';
	               }
	            } else {
	               $createATask = '';
	            }

                // buzzwords
                // save buzzwords
				$this->saveBuzzwords($environment, $item, $form_data['buzzwords']);

                // tags
                if (isset($form_data['tags_tab'])){
                	$item->setTagListByID($form_data['tags']);
                }

                // Save item
                $item->save();

               // workflow - unset read markers
               $item_manager = $environment->getItemManager();
               $item_manager->markItemAsWorkflowNotReadForAllUsers($item->getItemID());
               $item_manager->markItemAsWorkflowRead($item->getItemID(), $current_user->getItemID());

               // send notifications if world public status is requested
               if ( $item->getWorldPublic() == 1
                    and isset($current_context)
                    and $current_context->isCommunityRoom()
                  ) {

                  // Get receiving moderators
                  $modList = $current_context->getModeratorList();
                  $moderator = $modList->getFirst();
                  $mailSendTo = '';
                  while ( $moderator ) {
                     if ( $moderator->getPublishMaterialWantMail() == 'yes' ) {
                        $mailSendTo .= $moderator->getFullName().LF;
                     }
                     $moderator = $modList->getNext();
                  }

                  // Send mails // Warum werden die einzeln verschickt ???
                  $moderator = $modList->getFirst();
                  $translator = $environment->getTranslationObject();
                  while ( $moderator ) {
                     if ( $moderator->getPublishMaterialWantMail() == 'yes' ) {
                        include_once('classes/cs_mail.php');
                        $mail = new cs_mail();
                        $sender = $item->getModificatorItem();

                         global $symfonyContainer;
                         $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                         $mail->set_from_email($emailFrom);

                        $mail->set_from_name($environment->getCurrentPortalItem()->getTitle());
                        $mail->set_reply_to_name($sender->getFullName());
                        $mail->set_reply_to_email($sender->getEMail());
                        $mail->set_to($moderator->getEMail());
                        $language = $moderator->getLanguage();
                        $translator->setSelectedLanguage($language);
                        $mail_subject = $translator->getMessage('ADMIN_MAIL_MATERIAL_SHOULD_BE_WORLDPUBLIC_SUBJECT',$current_context->getTitle());
                        $mail_body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                        $mail_body.= "\n\n";
                        $mail_body.= $translator->getMessage('ADMIN_MAIL_MATERIAL_SHOULD_BE_WORLDPUBLIC_BODY',$item->getTitle(),$current_context->getTitle(),$sender->getFullName());
                        $mail_body.= "\n\n";
                        $mail_body.= $translator->getMessage('MAIL_SEND_TO',$mailSendTo);
                        $mail_body.= "\n";
                        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID().'&mod=material_admin&fct=index&iid='.$item->getItemID().'&selstatus=1';
                        $mail_body.= $url;
                        $mail->set_subject($mail_subject);
                        $mail->set_message($mail_body);
                        $mail->send();
                     }
                     $moderator = $modList->getNext();
                  }
               }

               // Create tasks for world public status
               if ( $createATask == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC' ) {
                  $task_manager = $environment->getTaskManager();
                  $task_item = $task_manager->getNewItem();
                  $task_item->setTitle('TASK_REQUEST_MATERIAL_WORLDPUBLIC');
                  $task_item->setStatus('REQUEST');
                  $user = $environment->getCurrentUserItem();
                  $task_item->getCreatorItem($user);
                  $task_item->setItem($item);
                  $task_item->save();
               } elseif ( $createATask == 'TASK_CANCEL_MATERIAL_WORLDPUBLIC' ) {
                  $task_manager = $environment->getTaskManager();

                  // Close any open requests
                  $task_list = $task_manager->getTaskListForItem($item);
                  if ( !$task_list->isEmpty() ) {
                     $task_item = $task_list->getFirst();
                     while ( $task_item ) {
                        if ( $task_item->getStatus() == 'REQUEST'
                             and $task_item->getTitle() == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC' ) {
                           $task_item->setStatus('CLOSED');
                           $task_item->save();
                        }
                        $task_item = $task_list->getNext();
                     }
                  }

                  // Create new task
                  $task_item = $task_manager->getNewItem();
                  $task_item->setTitle('TASK_CANCEL_MATERIAL_WORLDPUBLIC');
                  $task_item->setStatus('CLOSED');
                  $user = $environment->getCurrentUserItem();
                  $task_item->getCreatorItem($user);
                  $task_item->setItem($item);
                  $task_item->save();
               }

                // this will update the right box list
                if($item_is_new){
	                if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.CS_MATERIAL_TYPE.'_index_ids')){
	                    $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.CS_MATERIAL_TYPE.'_index_ids'));
	                } else {
	                    $id_array =  array();
	                }

                    $id_array[] = $item->getItemID();
                    $id_array = array_reverse($id_array);
                    $session->setValue('cid'.$environment->getCurrentContextID().'_'.CS_MATERIAL_TYPE.'_index_ids',$id_array);
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

    private function setBibliographic($form_data, $item) {
    	$config = array(
    		array(	'get'		=> 'getAuthor',
 					'set'		=> 'setAuthor',
 					'value'		=> $form_data['author']),
    		array(	'get'		=> 'getPublishingDate',
    	 			'set'		=> 'setPublishingDate',
    	 			'value'		=> $form_data['publishing_date']),
    		array(	'get'		=> 'getBibliographicValues',
    	    	 	'set'		=> 'setBibliographicValues',
    	    	 	'value'		=> $form_data['common']),
    		array(	'get'		=> 'getBibKind',
    	    	    'set'		=> 'setBibKind',
    	    	    'value'		=> $form_data['bib_kind']),
    		array(	'get'		=> 'getPublisher',
    	    	    'set'		=> 'setPublisher',
    	    	    'value'		=> $form_data['publisher']),
    		array(	'get'		=> 'getAddress',
    	    	    'set'		=> 'setAddress',
    	    	    'value'		=> $form_data['address']),
    		array(	'get'		=> 'getEdition',
    	    	    'set'		=> 'setEdition',
    	    	    'value'		=> $form_data['edition']),
    		array(	'get'		=> 'getSeries',
    	    	    'set'		=> 'setSeries',
    	    	    'value'		=> $form_data['series']),
    		array(	'get'		=> 'getVolume',
    	    	    'set'		=> 'setVolume',
    	    	    'value'		=> $form_data['volume']),
    		array(	'get'		=> 'getISBN',
    	    	    'set'		=> 'setISBN',
    	    	    'value'		=> $form_data['isbn']),
    		array(	'get'		=> 'getURL',
    	    	    'set'		=> 'setURL',
    	    	    'value'		=> $form_data['url']),
    		array(	'get'		=> 'getURLDate',
    	    	    'set'		=> 'setURLDate',
    	    	    'value'		=> $form_data['url_date']),
    		array(	'get'		=> 'getEditor',
    	    	    'set'		=> 'setEditor',
    	    	    'value'		=> $form_data['editor']),
    		array(	'get'		=> 'getBooktitle',
    	    	    'set'		=> 'setBooktitle',
    	    	    'value'		=> $form_data['booktitle']),
    		array(	'get'		=> 'getISSN',
    	    	    'set'		=> 'setISSN',
    	    	    'value'		=> $form_data['issn']),
    		array(	'get'		=> 'getPages',
    	    	    'set'		=> 'setPages',
    	    	    'value'		=> $form_data['pages']),
    		array(	'get'		=> 'getJournal',
    	    	    'set'		=> 'setJournal',
    	    	    'value'		=> $form_data['journal']),
    		array(	'get'		=> 'getIssue',
    	    	    'set'		=> 'setIssue',
    	    	    'value'		=> $form_data['issue']),
    		array(	'get'		=> 'getThesisKind',
    	    	    'set'		=> 'setThesisKind',
    	    	   	'value'		=> $form_data['thesis_kind']),
    		array(	'get'		=> 'getUniversity',
    	    	    'set'		=> 'setUniversity',
    	    	    'value'		=> $form_data['university']),
    		array(	'get'		=> 'getFaculty',
    	    	    'set'		=> 'setFaculty',
    	    	    'value'		=> $form_data['faculty'])
    	);

    	foreach($config as $method => $detail) {
    		if($detail['value'] != call_user_func_array(array($item, $detail['get']), array())) {

    			call_user_func_array(array($item, $detail['set']), array($detail['value']));
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
			$return = array(
				'general'	=> array(
					array(	'name'		=> 'title',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'description',
							'type'		=> 'textarea',
							'mandatory'	=> false)
				),

				'common'	=> array(
				),

				'book'	=> array(
					array(	'name'		=> 'author',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'publishing_date',
							'type'		=> 'numeric',
							'mandatory'	=> true),
					array(	'name'		=> 'publisher',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'address',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'edition',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'series',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'volume',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'isbn',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'url',
							'type'		=> 'url',
							'mandatory'	=> false),
					array(	'name'		=> 'url_date',
							'type'		=> 'date',
							'mandatory'	=> false)
				),

				'collection'	=> array(
					array(	'name'		=> 'author',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'publishing_date',
							'type'		=> 'numeric',
							'mandatory'	=> true),
					array(	'name'		=> 'publisher',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'address',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'edition',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'series',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'volume',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'isbn',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'url',
							'type'		=> 'url',
							'mandatory'	=> false),
					array(	'name'		=> 'url_date',
							'type'		=> 'date',
							'mandatory'	=> false)
				),

				'incollection'	=> array(
					array(	'name'		=> 'author',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'publishing_date',
							'type'		=> 'numeric',
							'mandatory'	=> true),
					array(	'name'		=> 'editor',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'booktitle',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'address',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'publisher',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'edition',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'series',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'volume',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'isbn',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'pages',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'url',
							'type'		=> 'url',
							'mandatory'	=> false),
					array(	'name'		=> 'url_date',
							'type'		=> 'date',
							'mandatory'	=> false)
				),

				'article'	=> array(
					array(	'name'		=> 'author',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'publishing_date',
							'type'		=> 'numeric',
							'mandatory'	=> true),
					array(	'name'		=> 'journal',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'volume',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'issue',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'pages',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'address',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'publisher',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'issn',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'url',
							'type'		=> 'url',
							'mandatory'	=> false),
					array(	'name'		=> 'url_date',
							'type'		=> 'date',
							'mandatory'	=> false)
				),

				'chapter'	=> array(
					array(	'name'		=> 'author',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'publishing_date',
							'type'		=> 'numeric',
							'mandatory'	=> true),
					array(	'name'		=> 'address',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'edition',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'series',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'volume',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'isbn',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'url',
							'type'		=> 'url',
							'mandatory'	=> false),
					array(	'name'		=> 'url_date',
							'type'		=> 'date',
							'mandatory'	=> false)
				),

				'inpaper'	=> array(
					array(	'name'		=> 'author',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'publishing_date',
							'type'		=> 'numeric',
							'mandatory'	=> true),
					array(	'name'		=> 'journal',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'issue',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'pages',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'address',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'publisher',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'url',
							'type'		=> 'url',
							'mandatory'	=> false),
					array(	'name'		=> 'url_date',
							'type'		=> 'date',
							'mandatory'	=> false)
				),

				'thesis'	=> array(
					array(	'name'		=> 'author',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'publishing_date',
							'type'		=> 'numeric',
							'mandatory'	=> true),
					array(	'name'		=> 'thesis_kind',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'address',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'university',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'faculty',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'url',
							'type'		=> 'url',
							'mandatory'	=> false),
					array(	'name'		=> 'url_date',
							'type'		=> 'date',
							'mandatory'	=> false)
				),

				'manuscript'	=> array(
					array(	'name'		=> 'author',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'publishing_date',
							'type'		=> 'numeric',
							'mandatory'	=> true),
					array(	'name'		=> 'address',
							'type'		=> 'text',
							'mandatory'	=> true),
					array(	'name'		=> 'url',
							'type'		=> 'url',
							'mandatory'	=> false),
					array(	'name'		=> 'url_date',
							'type'		=> 'date',
							'mandatory'	=> false)
				),

				'website'	=> array(
					array(	'name'		=> 'author',
							'type'		=> 'text',
							'mandatory' => true),
					array(	'name'		=> 'url',
							'type'		=> 'url',
							'mandatory'	=> true),
					array(	'name'		=> 'url_date',
							'type'		=> 'date',
							'mandatory'	=> false)
				),

				'document'	=> array(
					array(	'name'		=> 'document_editor',
							'type'		=> 'text',
							'mandatory' => false),
					array(	'name'		=> 'document_maintainer',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'document_release_number',
							'type'		=> 'text',
							'mandatory'	=> false),
					array(	'name'		=> 'document_release_date',
							'type'		=> 'date',
							'mandatory'	=> false)
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