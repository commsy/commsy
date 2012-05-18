<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_material_controller implements cs_rubric_popup_controller {
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
			// assign template vars
			$this->assignTemplateVars();
			$current_context = $this->_environment->getCurrentContextItem();

			if($item !== null) {
				// edit mode

				// TODO: check rights

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

		        /** Start Dokumentenverwaltung **/
		        $this->_popup_controller->assign('item', 'document_editor', $item->getDocumentEditor());
		        $this->_popup_controller->assign('item', 'document_maintainer', $item->getDocumentMaintainer());
		        $this->_popup_controller->assign('item', 'document_release_number', $item->getDocumentReleaseNumber());
		        $this->_popup_controller->assign('item', 'document_release_date', $item->getDocumentReleaseDate());
		     	 /** Ende Dokumentenverwaltung **/

		        if ($current_context->withWorkflow()){
		           $this->_popup_controller->assign('workflow_traffic_light', $item->getWorkflowTrafficLight());
		           $this->_popup_controller->assign('workflow_resubmission', $item->getWorkflowResubmission());
		           if($item->getWorkflowResubmissionDate() != '' and $item->getWorkflowResubmissionDate() != '0000-00-00 00:00:00'){
		              $this->_popup_controller->assign('workflow_resubmission_date_workflow_resubmission_date', getDateInLang($item->getWorkflowResubmissionDate()));
		           } else {
		              $this->_popup_controller->assign('workflow_resubmission_date_workflow_resubmission_date', '');
		           }
		           $this->_popup_controller->assign('workflow_resubmission_who', $item->getWorkflowResubmissionWho());
		           $this->_popup_controller->assign('workflow_resubmission_who_additional', $item->getWorkflowResubmissionWhoAdditional());
		           $this->_popup_controller->assign('workflow_resubmission_traffic_light', $item->getWorkflowResubmissionTrafficLight());
				   $this->_popup_controller->assign('workflow_validity', $item->getWorkflowValidity());
		           if($item->getWorkflowValidityDate() != '' and $item->getWorkflowValidityDate() != '0000-00-00 00:00:00'){
		              $this->_popup_controller->assign('workflow_validity_date_workflow_validity_date',getDateInLang($item->getWorkflowValidityDate()));
		           } else {
		              $this->_popup_controller->assign('workflow_validity_date_workflow_validity_date', '');
		           }
		           $this->_popup_controller->assign('workflow_validity_who', $item->getWorkflowValidityWho());
		           $this->_popup_controller->assign('workflow_validity_who_additional', $item->getWorkflowValidityWhoAdditional());
		           $this->_popup_controller->assign('workflow_validity_traffic_light', $item->getWorkflowValidityTrafficLight());
		        }


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
				$val = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'0':'1';
		    	$this->_popup_controller->assign('item', 'private_editing', $val);
		    	$this->_popup_controller->assign('item', 'public', $val);
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
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        $current_iid = $form_data['iid'];

        $translator = $this->_environment->getTranslationObject();

        if($current_iid === 'NEW') {
            $item = null;
        } else {
            $manager = $this->_environment->getMaterialManager();
            $item = $manager->getItem($current_iid);
        }

        // TODO: check rights */
		/****************************/
        if ( $current_iid != 'NEW' and !isset($item) ) {

        } elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
        ($current_iid != 'NEW' and isset($item) and
        $item->mayEdit($current_user))) ) {

		/****************************/


        } else { //Acces granted
			$this->cleanup_session($current_iid);

			// save item
			if($this->_popup_controller->checkFormData()) {
                $session = $this->_environment->getSessionItem();
                $item_is_new = false;
                // Create new item
                if ( !isset($item) ) {
                    $manager = $environment->getMaterialManager();
                    $item = $manager->getNewItem();
                    $item->setContextID($environment->getCurrentContextID());
                    $current_user = $environment->getCurrentUserItem();
                    $item->setCreatorItem($current_user);
                    $item->setCreationDate(getCurrentDateTimeInMySQL());
                    $item_is_new = true;
                }

                // Set modificator and modification date
                $current_user = $environment->getCurrentUserItem();
                $item->setModificatorItem($current_user);

                // Set attributes
                if ( isset($form_data['title']) ) {
                    $item->setTitle($form_data['title']);
                }
                if ( isset($form_data['description']) ) {
                    $item->setDescription($form_data['description']);
                }
	            if (isset($form_data['author']) and isset($form_data['bib_kind']) and $form_data['bib_kind'] != 'none') {
	               $item->setAuthor($form_data['author']);
	            }else{
	               $item->setAuthor('');
	            }
	            if (isset($form_data['publishing_date']) and $item->getPublishingDate() != $form_data['publishing_date']) {
	               $item->setPublishingDate($form_data['publishing_date']);
	            }

                if (isset($form_data['public'])) {
                    $item->setPublic($form_data['public']);
                }
                $file_ids = $form_data['files'];
                $this->_popup_controller->getUtils()->setFilesForItem($item, $file_ids, CS_MATERAIL_TYPE);


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
                    $item->setModificationDate($dt_hiding_datetime);
                }else{
                    if($item->isNotActivated()){
                        $item->setModificationDate(getCurrentDateTimeInMySQL());
                    }
                }

	            if (isset($form_data['bibliographic']) and $item->getBibliographicValues() != $form_data['bibliographic']) {
	               $item->setBibliographicValues($form_data['bibliographic']);
	            }
	            if (isset($form_data['description']) and $item->getDescription() != $form_data['description']) {
	               $item->setDescription($_POST['description']);
	            }

	            // Detail bibliographic values
	            if ( isset($form_data['bib_kind']) and $item->getBibKind() != $form_data['bib_kind'] ) {
	               $item->setBibKind($form_data['bib_kind']);
	               $item->setBibliographicValues('');
	            }
	            if (isset($form_data['common']) and $item->getBibliographicValues() != $form_data['common']) {
	               $item->setBibliographicValues($form_data['common']);
	            }
	            if ( isset($form_data['publisher']) and $item->getPublisher() != $form_data['publisher'] ) {
	               $item->setPublisher( $form_data['publisher']);
	            }
	            if ( isset($form_data['address']) and $item->getAddress() != $form_data['address'] ) {
	               $item->setAddress($form_data['address']);
	            }
	            if ( isset($form_data['edition']) and $item->getEdition() != $form_data['edition'] ) {
	               $item->setEdition($form_data['edition']);
	            }
	            if ( isset($form_data['series']) and $item->getSeries() != $form_data['series'] ) {
	               $item->setSeries($form_data['series']);
	            }
	            if ( isset($form_data['volume']) and $item->getVolume() != $form_data['volume'] ) {
	               $item->setVolume($form_data['volume']);
	            }
	            if ( isset( $form_data['isbn']) and $item->getISBN() !=  $form_data['isbn'] ) {
	               $item->setISBN( $form_data['isbn']);
	            }
	            if ( isset( $form_data['issn']) and $item->getISSN() !=  $form_data['issn'] ) {
	               $item->setISSN( $form_data['issn']);
	            }
	            if ( isset( $form_data['editor']) and $item->getEditor() !=  $form_data['editor'] ) {
	               $item->setEditor( $form_data['editor']);
	            }
	            if ( isset( $form_data['booktitle']) and $item->getBooktitle() !=  $form_data['booktitle'] ) {
	               $item->setBooktitle( $form_data['booktitle']);
	            }
	            if ( isset( $form_data['pages']) and $item->getPages() !=  $form_data['pages'] ) {
	               $item->setPages( $form_data['pages']);
	            }
	            if ( isset( $form_data['journal']) and $item->getJournal() !=  $form_data['journal'] ) {
	               $item->setJournal( $form_data['journal']);
	            }
	            if ( isset( $form_data['issue']) and $item->getIssue() !=  $form_data['issue'] ) {
	               $item->setIssue( $form_data['issue']);
	            }
	            if ( isset( $form_data['thesis_kind']) and $item->getThesisKind() !=  $form_data['thesis_kind'] ) {
	               $item->setThesisKind( $form_data['thesis_kind']);
	            }
	            if ( isset( $form_data['university']) and $item->getUniversity() !=  $form_data['university'] ) {
	               $item->setUniversity( $form_data['university']);
	            }
	            if ( isset( $form_data['faculty']) and $item->getFaculty() !=  $form_data['faculty'] ) {
	               $item->setFaculty( $form_data['faculty']);
	            }
	            if ( isset( $form_data['url']) and $item->getURL() !=  $form_data['url'] ) {
	               $item->setURL( $form_data['url']);
	            }
	            if ( isset( $form_data['url_date']) and $item->getURL() !=  $form_data['url_date'] ) {
	               $item->setURLDate( $form_data['url_date']);
	            }

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



	            if ( isset( $form_data['external_viewer']) and isset( $form_data['external_viewer_accounts']) ) {
	               $user_ids = explode(" ", $form_data['external_viewer_accounts']);
	               $item->setExternalViewerAccounts($user_ids);
	            }else{
	               $item->unsetExternalViewerAccounts();
	            }

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

	            if ( $context_item->isCommunityRoom() and $context_item->isOpenForGuests() ) {
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
                $item->setBuzzwordListByID($form_data['buzzwords']);

                // tags
                $item->setTagListByID($form_data['tags']);

                // Save item
                $item->save();

               // workflow - unset read markers
               $item_manager = $environment->getItemManager();
               $item_manager->markItemAsWorkflowNotReadForAllUsers($item->getItemID());
               $item_manager->markItemAsWorkflowRead($item->getItemID(), $current_user->getItemID());

               // send notifications if world public status is requested
               if ( $item->getWorldPublic() == 1
                    and isset($context_item)
                    and $context_item->isCommunityRoom()
                  ) {

                  // Get receiving moderators
                  $modList = $context_item->getModeratorList();
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
                        $mail->set_from_name($sender->getFullName());
                        $mail->set_from_email($sender->getEMail());
                        $mail->set_reply_to_name($sender->getFullName());
                        $mail->set_reply_to_email($sender->getEMail());
                        $mail->set_to($moderator->getEMail());
                        $language = $moderator->getLanguage();
                        $translator->setSelectedLanguage($language);
                        $mail_subject = $translator->getMessage('ADMIN_MAIL_MATERIAL_SHOULD_BE_WORLDPUBLIC_SUBJECT',$context_item->getTitle());
                        $mail_body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                        $mail_body.= "\n\n";
                        $mail_body.= $translator->getMessage('ADMIN_MAIL_MATERIAL_SHOULD_BE_WORLDPUBLIC_BODY',$item->getTitle(),$context_item->getTitle(),$sender->getFullName());
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

                    $id_array[] = $announcement_item->getItemID();
                    $id_array = array_reverse($id_array);
                    $session->setValue('cid'.$environment->getCurrentContextID().'_'.CS_MATERIAL_TYPE.'_index_ids',$id_array);
                }

                // save session
                $this->_environment->getSessionManager()->save($session);

                // Add modifier to all users who ever edited this item
                $manager = $environment->getLinkModifierItemManager();
                $manager->markEdited($announcement_item->getItemID());

                // Redirect
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


    public function getFieldInformation($sub = '') {
			return array(
				array(	'name'		=> 'title',
						'type'		=> 'text',
						'mandatory' => true),
				array(	'name'		=> 'vid',
						'type'		=> 'hidden',
						'mandatory' => true),
				array(	'name'		=> 'description',
						'type'		=> 'textarea',
						'mandatory'	=> false),
				array(	'name'		=> 'pages',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'booktitle',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'editor',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'isbn',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'volume',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'series',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'edition',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'address',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'publisher',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'bib_kind',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'author',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'journal',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'issue',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'thesis_kind',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'university',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'faculty',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'dayEnd',
						'type'		=> 'text',
						'mandatory'	=> true),
				array(	'name'		=> 'timeEnd',
						'type'		=> 'text',
						'mandatory'	=> false)
			);
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