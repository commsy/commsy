<?php
require_once('classes/controller/ajax/popup/cs_popup_controller.php');

class cs_popup_configuration_controller implements cs_popup_controller {
	private $_environment = null;
	private $_popup_controller = null;
	private $_config = array();
	private $_data = array();
	private $_time_array = array();
	private $_community_room_array = array();
	private $_shown_community_room_array = array();
	private $_color_array = array();

	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}

	public function save($form_data, $additional = array()) {
		$current_context = $this->_environment->getCurrentContextItem();
		$current_user = $this->_environment->getCurrentUserItem();
		$text_converter = $this->_environment->getTextConverter();

		// check access rights
		if($current_user->isGuest()) {
			// TODO:
			/*
			 * if (!$context_item->isOpenForGuests()) {
		      redirect($environment->getCurrentPortalId(),'home','index','');
		   } else {
		      $params = array() ;
		      $params['cid'] = $context_item->getItemId();
		      redirect($environment->getCurrentPortalId(),'home','index',$params);
		   }
			 */
		}

		// check context
		//elseif(!$current_context->isOpen() && !$current_context->isTemplate()) {
		
			/**
			 * temporary out-commented. before commsy8 it seems not to be possible to edit archived rooms settings(unlock only via portal?).
			 * however, to re-activate a room we have to process this save method
			 */
		
			// TODO:
			/*
			 *  $params = array();
			   $params['environment'] = $environment;
			   $params['with_modifying_actions'] = true;
			   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
			   unset($params);
			   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
			   $page->add($errorbox);
			   $command = 'error';
			 */
		//}

		elseif(!$current_user->isModerator()) {
			/*
			 * $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $command = 'error';
			 */
		}

		// access granted
		else {
			$tab = $additional['part'];

			switch($tab) {
				/**** ROOM CONFIGURATION ****/
				case "room_configuration":
					if($this->_popup_controller->checkFormData('room_configuration')) {
						// title
						$title = $form_data['room_name'];
						if (isset($title) && trim($title) != "") {
							$current_context->setTitle($form_data['room_name']);
						} else {
							$this->_popup_controller->setErrorReturn('101', 'mandetory missing', array());
							return false;
						}

						// show title
						if(isset($form_data['room_show_name']) && $form_data['room_show_name'] == '1') $current_context->setShowTitle();
						else $current_context->setNotShowTitle();

						// language
						if(isset($form_data['language'])) {
							$old_language = $current_context->getLanguage();

							if($old_language != $form_data['language']) {
								$current_context->setLanguage($form_data['language']);
								$this->_environment->unsetSelectedLanguage();
							}
						}



						// assignment
						if($current_context->isProjectRoom()) {
							$community_room_array = array();

							// get community room ids
							foreach($form_data as $key => $value) {
								if(mb_substr($key, 0, 18) === 'communityroomlist_') $community_room_array[] = $value;
							}
							
							/*
							 * if assignment is mandatory, the array must not be empty
							 */
							if (	$this->_environment->getCurrentPortalItem()->getProjectRoomLinkStatus() !== "mandatory" ||
									sizeof($community_room_array) > 0 )
							{
								$current_context->setCommunityListByID($community_room_array);
							}
						} elseif($current_context->isCommunityRoom()) {
							if(isset($form_data['room_assignment'])) {
								if($form_data['room_assignment'] === 'open') $current_context->setAssignmentOpenForAnybody();
								elseif($form_data['room_assignment'] === 'closed') $current_context->setAssignmentOnlyOpenForRoomMembers();
							}
						}

						// delete logo
						if(isset($form_data['delete_logo']) && $form_data['delete_logo'] == '1') {
							$disc_manager = $this->_environment->getDiscManager();

							if($disc_manager->existsFile($current_context->getLogoFIlename())) {
								$disc_manager->unlinkFile($current_context->getLogoFilename());
							}

							$current_context->setLogoFilename();
						}

						// time pulses
						$time_array = array();
						foreach($form_data as $key => $value) {
							if(mb_substr($key, 0, 10) === 'room_time_') {
								$time_array[] = $value;
							}
						}

						if(!empty($time_array)) {
							if(in_array('cont', $time_array)) {
								$current_context->setContinuous();
							} else {
								$current_context->setTimeListByID($time_array);
								$current_context->setNotContinuous();
							}
						} elseif($current_context->isProjectRoom()) {
							$current_context->setTimeListByID(array());
							$current_context->setNotContinuous();
						}

						// scheme
						if(isset($form_data['color_choice'])) {
							$schema = array();

							// set color scheme
							$schema['schema'] = $form_data['color_choice'];

							if($form_data['color_choice'] === 'individual') {
								$schema['schema'] = 'individual';

								// set own color values
								if(isset($form_data['color_active_menu'])) $schema['color_active_menu'] = $form_data['color_active_menu'];
								if(isset($form_data['color_menu'])) $schema['color_menu'] = $form_data['color_menu'];
								if(isset($form_data['color_right_column'])) $schema['color_right_column'] = $form_data['color_right_column'];
								if(isset($form_data['color_content_bg'])) $schema['color_content_bg'] = $form_data['color_content_bg'];
								if(isset($form_data['color_link'])) $schema['color_link'] = $form_data['color_link'];
								if(isset($form_data['color_link_hover'])) $schema['color_link_hover'] = $form_data['color_link_hover'];
								if(isset($form_data['color_action_bg'])) $schema['color_action_bg'] = $form_data['color_action_bg'];
								if(isset($form_data['color_action_icon'])) $schema['color_action_icon'] = $form_data['color_action_icon'];
								if(isset($form_data['color_action_icon_hover'])) $schema['color_action_icon_hover'] = $form_data['color_action_icon_hover'];
								if(isset($form_data['color_bg'])) $schema['color_bg'] = $form_data['color_bg'];

								// delete bg image
								if(isset($form_data['delete_bg_image']) && $form_data['delete_bg_image'] == '1') {
									$disc_manager = $this->_environment->getDiscManager();

									if($disc_manager->existsFile($current_context->getBGImageFilename())) {
										$disc_manager->unlinkFile($current_context->getBGImageFilename());
									}

									$current_context->setBGImageFilename('');
								}

								// bg image repeat
								if(isset($form_data['color_bg_image_repeat']) && $form_data['color_bg_image_repeat'] == '1') $current_context->setBGImageRepeat();
								else $current_context->unsetBGImageRepeat();
								if(isset($form_data['color_bg_image_fixed']) && $form_data['color_bg_image_fixed'] == '1') $current_context->setBGImageFixed();
								else $current_context->unsetBGImageFixed();

								// create individual css for room context
								$this->_popup_controller->getUtils()->createOwnCSSForRoomContext($current_context, $schema);
							}

							// store scheme
							$current_context->setColorArray($schema);
						}

						// description
						if(isset($form_data['description'])) $current_context->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));
						else $current_context->setDescription('');


						// rubric selection form check
						if(!empty($form_data['rubric_0'])) {
							$default_rubrics = $current_context->getAvailableDefaultRubricArray();

							if(count($default_rubrics) > 8) $count = 8;
							else $count = count($default_rubrics);

							if(isset($form_data['rubric_0'])) {
								$post_array = array();

								for($j=0; $j < $count; $j++) {
									$post_array[] = $form_data['rubric_' . $j];
								}

								$value = true;
								for($k=0; $k < $count; $k++) {
									for($l=0; $l < $count; $l++) {
										if($k != $l) {
											if($post_array[$l] == $post_array[$k] && $post_array[$l] != 'none') {
												$value = false;
											}
										}
									}
								}
							}

							if(!$value) {
								// error
								$this->_popup_controller->setErrorReturn('102', 'doubled rubric entries', array());

								return false;
							}
						}

						// rubric selection
						$temp_array = array();
						$j = 0;
						if(!empty($form_data['rubric_0'])) {
							$count = 0;
							while(isset($form_data['rubric_' . $count])) $count++;
						} else {
							$default_rubrics = $current_context->getAvailableDefaultRubricArray();

							if(count($default_rubrics) > 8) $count = 8;
							else $count = count($default_rubrics);
						}

						$rubric_array_for_plugin = array();
						for($i=0; $i < $count; $i++) {
							$rubric = '';

							if(!empty($form_data['rubric_' . $i])) {
								if($form_data['rubric_' . $i] != 'none') {
									$rubric_array_for_plugin[] = $form_data['rubric_' . $i];
									$temp_array[$i] = $form_data['rubric_' . $i] . '_';

									if(!empty($form_data['show_' . $i])) {
										$temp_array[$i] .= $form_data['show_' . $i];
									} else {
										$temp_array[$i] .= 'nodisplay';
									}
									$j++;
								}
							}
						}

						$current_context->setHomeConf(implode($temp_array, ','));

				         // check member
				         if ( isset($form_data['member_check']) ) {
				            if ($form_data['member_check'] == 'never') {
				               $requested_user_manager = $this->_environment->getUserManager();
				               $requested_user_manager->setContextLimit($this->_environment->getCurrentContextID());
				               $requested_user_manager->setRegisteredLimit();
				               $requested_user_manager->select();
				               $requested_user_list = $requested_user_manager->get();
				               if (!empty($requested_user_list)){
				                  $requested_user = $requested_user_list->getFirst();
				                  while($requested_user){
				                     $requested_user->makeUser();
				                     $requested_user->save();
				                     $task_manager = $this->_environment->getTaskManager();
				                     $task_list = $task_manager->getTaskListForItem($requested_user);
				                     if (!empty($task_list)){
				                        $task = $task_list->getFirst();
				                        while($task){
				                           if ($task->getStatus() == 'REQUEST' and ($task->getTitle() == 'TASK_USER_REQUEST' or $task->getTitle() == 'TASK_PROJECT_MEMBER_REQUEST')) {
				                              $task->setStatus('CLOSED');
				                              $task->save();
				                           }
				                           $task = $task_list->getNext();
				                        }
				                     }
				                     $requested_user = $requested_user_list->getNext();
				                  }
				               }
				               $current_context->setCheckNewMemberNever();
				            } elseif ($form_data['member_check'] == 'always') {
				               $current_context->setCheckNewMemberAlways();
				            } elseif ($form_data['member_check'] == 'sometimes') {
				               $current_context->setCheckNewMemberSometimes();
				            } elseif ($form_data['member_check'] == 'withcode') {
				               $current_context->setCheckNewMemberWithCode();
				               $current_context->setCheckNewMemberCode($form_data['code']);
				            }
				         }

				         // open for guests
				         if ( isset($form_data['open_for_guests']) ) {
				            if ($form_data['open_for_guests'] == 'open') {
				                $current_context->setOpenForGuests();
				            } elseif ($form_data['open_for_guests'] == 'closed') {
				               $current_context->setClosedForGuests();
				            }
				         }
				         // material open for guests
				         if ( isset($form_data['material_guests'])){
				         	if($form_data['material_guests'] == 'open'){
				         		$current_context->setMaterialOpenForGuests();
				         	} elseif ($form_data['material_guests'] == 'closed'){
				         		$current_context->setMaterialClosedForGuests();
				         	}
				         }


						// save
						$current_context->save();

						// generate layout images
						// TODO: outdated?
						$current_context->generateLayoutImages();

						if ($additional['action'] == 'delete_room'){
                          $current_context->delete();
                          $current_context->save();

                          if ($current_context->isGroupRoom()) {
                            $group_item = $current_context->getLinkedGroupItem();
                            $group_item->unsetGroupRoomActive();
                            $group_item->unsetGroupRoomItemID();
                            $group_item->save();
                            $this->_popup_controller->setSuccessfullItemIDReturn($current_context->getLinkedProjectItemID());
                          } else {
                          	$this->_popup_controller->setSuccessfullItemIDReturn($this->_environment->getCurrentPortalID());
                          }
                       } else {
                          // set return
                          $this->_popup_controller->setSuccessfullItemIDReturn($current_context->getItemID());
                       }
					}

					break;

				case 'additional_configuration':
					if($this->_popup_controller->checkFormData('additional_configuration')) {

					    if ( isset($form_data['dates_status']) ) {
					        $current_context->setDatesPresentationStatus($form_data['dates_status']);
					    }

						if ( isset($form_data['action_bar_visibility'])) {
							$current_context->setActionBarVisibilityDefault($form_data['action_bar_visibility']);
						}
						if ( isset($form_data['reference_bar_visibility'])) {
							$current_context->setReferenceBarVisibilityDefault($form_data['reference_bar_visibility']);
						}
						if ( isset($form_data['details_bar_visibility'])) {
							$current_context->setDetailsBarVisibilityDefault($form_data['details_bar_visibility']);
						}
						if ( isset($form_data['annotations_bar_visibility'])) {
							$current_context->setAnnotationsBarVisibilityDefault($form_data['annotations_bar_visibility']);
						}


						// rss
						// TODO: move
						if(isset($form_data['rss'])) {
							if($form_data['rss'] === 'yes') $current_context->turnRSSOn();
							elseif($form_data['rss'] === 'no') $current_context->turnRSSOff();
						}

				        /*********save buzzword options******/
				        if ( isset($form_data['buzzword']) and !empty($form_data['buzzword']) and $form_data['buzzword'] == 'yes') {
				           $current_context->setWithBuzzwords();
				        } else {
				          $current_context->setWithoutBuzzwords();
				        }
				        if ( isset($form_data['buzzword_mandatory']) and !empty($form_data['buzzword_mandatory']) and $form_data['buzzword_mandatory'] == 'yes' ) {
				           $current_context->setBuzzwordMandatory();
				        } else {
				           $current_context->unsetBuzzwordMandatory();
				        }
				        if ( isset($form_data['buzzword_fadeout']) and !empty($form_data['buzzword_fadeout']) and $form_data['buzzword_fadeout'] == 'yes' ) {
				           $current_context->setBuzzwordShowExpanded();
				        } else {
				           $current_context->unsetBuzzwordShowExpanded();
				        }

				        
				        /**********save tag options*******/
				        if ( isset($form_data['tags']) and !empty($form_data['tags']) and $form_data['tags'] == 'yes') {
				           $current_context->setWithTags();
				        } else {
				           $current_context->setWithoutTags();
				        }
				        if ( isset($form_data['tags_mandatory']) and !empty($form_data['tags_mandatory']) and $form_data['tags_mandatory'] == 'yes' ) {
				           $current_context->setTagMandatory();
				        } else {
				           $current_context->unsetTagMandatory();
				        }
				        if ( isset($form_data['tags_edit']) and !empty($form_data['tags_edit']) and $form_data['tags_edit'] == 'yes' ) {
				           $current_context->setTagEditedByModerator();
				        } else {
				           $current_context->setTagEditedByAll();
				        }
				        if ( isset($form_data['tags_fadeout']) and !empty($form_data['tags_fadeout']) and $form_data['tags_fadeout'] == 'yes' ) {
				           $current_context->setTagsShowExpanded();
				        } else {
				           $current_context->unsetTagsShowExpanded();

				        }
				        
				        if ( isset($form_data['announcement_date']) and !empty($form_data['announcement_date']) and $form_data['announcement_date'] == 'yes') {
				        	$current_context->setWithAnnouncementDates();
				        } else {
				        	$current_context->setWithoutAnnouncementDates();
				        }

						if (!empty($form_data['time_spread'])) {
				            $current_context->setTimeSpread($form_data['time_spread']);
				        }

				         if ( isset($form_data['template'])
				              and !empty($form_data['template'])
				            ) {
				            if ( $form_data['template'] == 1 ) {
				               $current_context->setTemplate();
				            } else {
				               $current_context->setNotTemplate();
				            }
				         } elseif ( $current_context->isProjectRoom()
				                    or $current_context->isCommunityRoom()
				                    or $current_context->isPrivateRoom()
				                    or $current_context->isGroupRoom()
				                  ) {
				            $current_context->setNotTemplate();
				         }
				         if ( isset($form_data['template_availability'])){
				            if ( $current_context->isCommunityRoom() ){
				               $current_context->setCommunityTemplateAvailability($form_data['template_availability']);
				            }else{
				               $current_context->setTemplateAvailability($form_data['template_availability']);
				            }
				         }
				         if ( !empty($form_data['template_title']) ) {
				            $current_context->setTemplateTitle($form_data['template_title']);
				         }
				         if ( isset($form_data['template_description'])){
				            $current_context->setTemplateDescription($text_converter->sanitizeHTML($form_data['template_description']));
				         }

				         global $c_use_soap_for_wiki;
				         if ( isset($form_data['room_status']) ) {
				            if ($form_data['room_status'] == '') {

				            	// archive
				            	if ( $this->_environment->isArchiveMode() ) {
				            		$current_context->backFromArchive();
				            		$this->_environment->deactivateArchiveMode();
				            	}
				            	// archive
				            	
				            	// old: should be impossible
				            	else {
				            		// Fix: Find Group-Rooms if existing
				            		if( $current_context->isGrouproomActive() ) {  // GrouproomActive schmeißt fehler gucken ob er hier rein rennt wegen Kategorie einstellungen
				            			$groupRoomList = $current_context->getGroupRoomList();
				            			 
				            			if( !$groupRoomList->isEmpty() ) {
				            				$room_item = $groupRoomList->getFirst();
				            				 
				            				while($room_item) {
				            					// All GroupRooms have to be opened too
				            					$room_item->open();
				            					$room_item->save();
				            					 
				            					$room_item = $groupRoomList->getNext();
				            				}
				            			}
				            		}
				            		// ~Fix
				            		 
				            		$current_context->open();
				            	}
				            	
				            	// wiki
				            	if($current_context->existWiki() and $c_use_soap_for_wiki){
				            		$wiki_manager = $this->_environment->getWikiManager();
				            		$wiki_manager->openWiki();
				            	}
				            	 
				            } elseif ($form_data['room_status'] == 2) {
				               // template or not: template close, others archive
				               if ( !$current_context->isTemplate() ) {				               	
	   			               // close wiki
				            	   if($current_context->existWiki() and $c_use_soap_for_wiki){
				                     $wiki_manager = $this->_environment->getWikiManager();
				                     $wiki_manager->closeWiki();
				                  }
				               
				               	    $current_context->moveToArchive();
                                    $this->_environment->activateArchiveMode();
				               } else {
				               	// templates can not closed / archived
				               	// so do nothing
				               }
				            }
				         }
				         
				         // status != 2 and =! empty
				         else {
				            // archive
				            if ( $this->_environment->isArchiveMode() ) {
                                $current_context->backFromArchive();
                                $this->_environment->deactivateArchiveMode();
                            }
	                                 	            
                                // wiki
                                if($current_context->existWiki() and $c_use_soap_for_wiki){
                                   $wiki_manager = $this->_environment->getWikiManager();
                                   $wiki_manager->openWiki();
                                }
                         }

                     $agbtext_array = [];
                         $languages = $this->_environment->getAvailableLanguageArray();
                         foreach ($languages as $language) {
                            if (!empty($form_data['agb_text_'.mb_strtolower($language, 'UTF-8')])) {
                               $agbtext_array[mb_strtoupper($language, 'UTF-8')] = $form_data['agb_text_'.mb_strtolower($language, 'UTF-8')];
                            } else {
                               $agbtext_array[mb_strtoupper($language, 'UTF-8')] = '';
                            }
                         }

				         if(($agbtext_array != $current_context->getAGBTextArray()) or ($form_data['agb_status'] != $current_context->getAGBStatus())) {
				            $current_context->setAGBStatus($form_data['agb_status']);
				            $current_context->setAGBTextArray($agbtext_array);
				            $current_context->setAGBChangeDate();
				            $current_user->setAGBAcceptance();
				            $current_user->save();
				         }
				         
				         $text_converter = $this->_environment->getTextConverter();

				         // extra todo status
				         $status_array = array();
				         foreach($form_data as $key => $value) {
				         	if(mb_substr($key, 0, 18) === 'additional_status_') {
				         		$status_array[mb_substr($key, 18)] = $text_converter->sanitizeHTML($value);
				         	}
				         }

				         $current_context->setExtraToDoStatusArray($status_array);

						// save
						$current_context->save();

						// set return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_context->getItemID());
					}
					break;

				case 'addon_configuration':
					if($this->_popup_controller->checkFormData('addon_configuration')) {
						if(isset($form_data['assessment']) && !empty($form_data['assessment']) && $form_data['assessment'] == 1) {
							$current_context->setAssessmentActive();
						} else {
							$current_context->setAssessmentInactive();
						}


				        $isset_workflow = false;

				        if ( isset($form_data['workflow_trafic_light']) and !empty($form_data['workflow_trafic_light']) and $form_data['workflow_trafic_light'] == 'yes') {
				           $current_context->setWithWorkflowTrafficLight();
				           $isset_workflow = true;
				        } else {
				           $current_context->setWithoutWorkflowTrafficLight();
				        }
				        if ( isset($form_data['workflow_resubmission']) and !empty($form_data['workflow_resubmission']) and $form_data['workflow_resubmission'] == 'yes' ) {
				           $current_context->setWithWorkflowResubmission();
				           $isset_workflow = true;
				        } else {
				           $current_context->setWithoutWorkflowResubmission();
				        }
				        if ( isset($form_data['workflow_reader']) and !empty($form_data['workflow_reader']) and $form_data['workflow_reader'] == 'yes' ) {
				           $current_context->setWithWorkflowReader();
				           $isset_workflow = true;
				        } else {
				           $current_context->setWithoutWorkflowReader();
				        }
				        if ( isset($form_data['workflow_trafic_light_default']) and !empty($form_data['workflow_trafic_light_default'])) {
				           $current_context->setWorkflowTrafficLightDefault($text_converter->sanitizeHTML($form_data['workflow_trafic_light_default']));
				        }

				        if ( isset($form_data['workflow_trafic_light_green_text']) and !empty($form_data['workflow_trafic_light_green_text'])) {
				           $current_context->setWorkflowTrafficLightTextGreen($text_converter->sanitizeHTML($form_data['workflow_trafic_light_green_text']));
				        }
				        if ( isset($form_data['workflow_trafic_light_yellow_text']) and !empty($form_data['workflow_trafic_light_yellow_text'])) {
				           $current_context->setWorkflowTrafficLightTextYellow($text_converter->sanitizeHTML($form_data['workflow_trafic_light_yellow_text']));
				        }
				        if ( isset($form_data['workflow_trafic_light_red_text']) and !empty($form_data['workflow_trafic_light_red_text'])) {
				           $current_context->setWorkflowTrafficLightTextRed($text_converter->sanitizeHTML($form_data['workflow_trafic_light_red_text']));
				        }

				        if ( isset($form_data['workflow_reader_group']) and !empty($form_data['workflow_reader_group'])) {
				           $current_context->setWithWorkflowReaderGroup();
				        } else {
				           $current_context->setWithoutWorkflowReaderGroup();
				        }
				        if ( isset($form_data['workflow_reader_person']) and !empty($form_data['workflow_reader_person'])) {
				           $current_context->setWithWorkflowReaderPerson();
				        } else {
				           $current_context->setWithoutWorkflowReaderPerson();
				        }

				        if ( isset($form_data['workflow_resubmission_show_to']) and !empty($form_data['workflow_resubmission_show_to'])) {
				           $current_context->setWorkflowReaderShowTo($form_data['workflow_resubmission_show_to']);
				        }

				        if ( isset($form_data['workflow_validity']) and !empty($form_data['workflow_validity']) and $form_data['workflow_validity'] == 'yes' ) {
				           $current_context->setWithWorkflowValidity();
				           $isset_workflow = true;
				        } else {
				           $current_context->setWithoutWorkflowValidity();
				        }

				        if($isset_workflow){
				           $current_context->setWithWorkflow();
				        } else {
				           $current_context->setWithoutWorkflow();
				        }

						// save
						$current_context->save();

						// set return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_context->getItemID());
					}
					break;

				/**** MODERATION CONFIGURATION ****/
				case 'moderation_configuration':
					if($this->_popup_controller->checkFormData('moderation_configuration')) {
						// information on home
						$info_array = array();
						if(is_array($current_context->_getExtra('INFORMATIONBOX'))) {
							$info_array = $current_context->_getExtra('INFORMATIONBOX');
						}

						if(!empty($form_data['item_id'])) $current_context->setInformationBoxEntryID($form_data['item_id']);

						if($form_data['show_information_box'] == '1') $current_context->setwithInformationBox('yes');
						else $current_context->setwithInformationBox('no');

						// get usage information
				        $info_array = array();
				        if (is_array($current_context->_getExtra('USAGE_INFO'))) {
				        	$info_array = $current_context->_getExtra('USAGE_INFO');
				        }

				        // get selected rubric from form
				        $info_rubric = $form_data["array_info_text_rubric"];

				        if (!empty($info_rubric)) {
				        	// if info array is empty, add rubric
				        	if (empty($info_array)) {
				        		$info_array[] = $info_rubric;
				        		$current_context->setUsageInfoArray($info_array);
				        	}

				        	/*
				        	 * Note: Why adding twice? Why differ between empty and !in_array?
				        	 */

				        	// if rubric is not in array push it
				        	elseif (!in_array($info_rubric . "_no", $info_array)) {
				        		array_push($info_array, $info_rubric . "no");
				        		$current_context->setUsageInfoArray($info_array);
				        	}

				        	// if rubric is in array remove it
				        	elseif (in_array($info_rubric . "_no", $info_array)) {
				        		$temp = array($info_rubric . "_no");
				        		$newArray = array_diff($info_array, $temp);
				        		$current_context->setUsageInfoArray($newArray);
				        	}

				        	// set title
				        	if (!empty($form_data["moderation_title_" . $info_rubric])) {
				        		$text_converter = $this->_environment->getTextConverter();
				        		$current_context->setUsageInfoHeaderForRubric($info_rubric, $text_converter->sanitizeHTML($form_data["moderation_title_" . $info_rubric]));
				        	}

				        	// set text
				        	if (!empty($form_data["moderation_description_" . $info_rubric])) {
				        		$current_context->setUsageInfoTextForRubric($info_rubric, $form_data["moderation_description_" . $info_rubric]);
				        	} else {
				        		$current_context->setUsageInfoTextForRubric($info_rubric, "");
				        	}
				        }
				        
				        // Mail
				        $store = array();
				        foreach ($form_data as $name => $value )
				        {
				        	if ( substr($name, 0, 20) === "moderation_mail_body" )
				        	{
				        		$lang = substr($name, 21, 2);
				        		$num = substr($name, 24);
				        		
				        		switch ( $num )
				        		{
				        			case 2: $messageTag	= "MAIL_BODY_HELLO";							break;
				        			case 3: $messageTag = "MAIL_BODY_CIAO";								break;
				        			case 5: $messageTag = "MAIL_BODY_USER_ACCOUNT_DELETE";			break;
				        			case 6: $messageTag = "MAIL_BODY_USER_ACCOUNT_LOCK";				break;
				        			case 7: $messageTag = "MAIL_BODY_USER_STATUS_USER";				break;
				        			case 8: $messageTag = "MAIL_BODY_USER_STATUS_MODERATOR";			break;
				        			case 9: $messageTag = "MAIL_BODY_USER_MAKE_CONTACT_PERSON";			break;
				        			case 10: $messageTag = "MAIL_BODY_USER_UNMAKE_CONTACT_PERSON";		break;
                                    case 11: $messageTag = "MAIL_BODY_USER_STATUS_USER_READ_ONLY";     break;
				        			case 12: $messageTag = "MAIL_BODY_USER_ACCOUNT_PASSWORD";			break;
				        			case 13: $messageTag = "MAIL_BODY_USER_ACCOUNT_MERGE";				break;
                                    
				        		}
				        		
				        		$languages = $this->_environment->getAvailableLanguageArray();
				        		if ( in_array($lang, $languages ))
				        		{
				        			$store[$messageTag][$lang] = $value;
				        		}
				        	}
				        }
				        
				        foreach ( $store as $tag => $values )
				        {
				        	$current_context->setEmailText($tag, $values);
				        }

						// save
						$current_context->save();

						// genereate layout images
						$current_context->generateLayoutImages();

						// set return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_context->getItemID());
				    }

					break;



				/**** ROOM PICTURE ****/
				case 'room_logo':
					if($this->_popup_controller->checkFormData('room_picture')) {
						/* handle room picture upload */
						if(!empty($additional["fileInfo"])) {
							$logo = $current_context->getLogoFilename();
							$disc_manager = $this->_environment->getDiscManager();

							$session = $this->_environment->getSessionItem();
							$session->unsetValue("add_files");

							// delete old if set
							if(!empty($logo)) {
								if($disc_manager->existsFile($current_context->getLogoFilename())) {
									$disc_manager->unlinkFile($current_context->getLogoFilename());
								}

								$current_context->setLogoFilename('');
							}

							// $filename = 'cid' . $this->_environment->getCurrentContextID() . '_logo_' . $additional["fileInfo"]["name"];

							$filename_info = pathinfo($additional["fileInfo"]["name"]);
							$filename = 'cid' . $this->_environment->getCurrentContextID() . '_logo_' . $filename_info['extension'];

							$disc_manager->copyFile($additional["fileInfo"]["file"], $filename, true);
							$current_context->setLogoFilename($filename);

							// save
							$current_context->save();
						}

						// set return
						$this->_popup_controller->setSuccessfullDataReturn($filename);
					}
					break;

				/**** ROOM BG IMAGE ****/
				case 'room_bg':
					if($this->_popup_controller->checkFormData('room_background')) {
						/* handle room picture upload */
						if(!empty($additional["fileInfo"])) {
							$bg_image = $current_context->getBGImageFilename();
							$disc_manager = $this->_environment->getDiscManager();

							$session = $this->_environment->getSessionItem();
							$session->unsetValue("add_files");

							// delete old if set
							if(!empty($bg_image)) {
								if($disc_manager->existsFile($current_context->getBGImageFilename())) {
									$disc_manager->unlinkFile($current_context->getBGImageFilename());
								}

								$current_context->setBGImageFilename('');
							}

							$filename = 'cid' . $this->_environment->getCurrentContextID() . '_bgimage_' . $additional["fileInfo"]["name"];
							$disc_manager->copyFile($additional["fileInfo"]["file"], $filename, true);
							$current_context->setBGImageFilename($filename);

							// save
							$current_context->save();

							// create css because of the new bg image
							$this->_popup_controller->getUtils()->createOwnCSSForRoomContext($current_context, $current_context->getColorArray());
						}

						// set return
						$this->_popup_controller->setSuccessfullDataReturn($filename);
					}
					break;

				case 'external_configuration':
				   if($this->_popup_controller->checkFormData('external_configuration')) {
				      $current_user = $this->_environment->getCurrentUserItem();
				      $current_context->setModificatorItem($current_user);
				      $current_context->setModificationDate(getCurrentDateTimeInMySQL());
				      $wordpress_manager = $this->_environment->getWordpressManager();
                  	  $wiki_manager = $this->_environment->getWikiManager();

				      if($additional['action'] == 'create_wordpress'){
                          if ( isset($form_data['use_comments']) and !empty($form_data['use_comments']) and $form_data['use_comments'] == 'yes') {
      				         $current_context->setWordpressUseComments();
      				      } else {
      				         $current_context->unsetWordpressUseComments();
      				      }

      				      if ( isset($form_data['use_comments_moderation']) and !empty($form_data['use_comments_moderation']) and $form_data['use_comments_moderation'] == 'yes') {
      				         $current_context->setWordpressUseCommentsModeration();
      				      } else {
      				         $current_context->unsetWordpressUseCommentsModeration();
      				      }

      				      if ( isset($form_data['wordpresslink']) and !empty($form_data['wordpresslink']) and $form_data['wordpresslink'] == 'yes') {
      				         $current_context->setWordpressHomeLink();
      				      } else {
      				         $current_context->unsetWordpressHomeLink();
      				      }

      				      if ( isset($form_data['skin_choice']) and !empty($form_data['skin_choice']) ) {
      				         $current_context->setWordpressSkin($form_data['skin_choice']);
      				      }

      				      if ( isset($form_data['wordpresstitle']) and !empty($form_data['wordpresstitle']) ) {
      				         $current_context->setWordpressTitle($form_data['wordpresstitle']);
      				      } else {
      				         $current_context->setWordpressTitle($current_context->getTitle());
      				      }

      				      if ( isset($form_data['wordpressdescription']) and !empty($form_data['wordpressdescription']) ) {
      				         $current_context->setWordpressDescription($form_data['wordpressdescription']);
      				      } else {
      				         $current_context->setWordpressDescription('');
      				      }

      				      if ( isset($form_data['member_role']) and !empty($form_data['member_role']) ) {
      				         $current_context->setWordpressMemberRole($form_data['member_role']);
      				      } else {
      				         $current_context->setWordpressMemberRole();
      				      }

      				      $current_context->setWithWordpressFunctions();
      				      $current_context->setWordpressExists();
      				      $current_context->setWordpressActive();
      				      // save
      				      $current_context->save();
      				      // create or change new wordpress
      				      $success = $wordpress_manager->createWordpress($current_context);
				      } else if ($additional['action'] == 'delete_wordpress') {
				         if($wordpress_manager->deleteWordpress($current_context->getWordpressId())){
      				         $current_user = $this->_environment->getCurrentUserItem();
      				         $current_context->setModificatorItem($current_user);
      				         $current_context->setModificationDate(getCurrentDateTimeInMySQL());
      				         $current_context->unsetWordpressExists();
      				         $current_context->setWordpressInActive();
      				         $current_context->setWordpressSkin('twentyten');
      				         $current_context->setWordpressTitle($current_context->getTitle());
      				         $current_context->setWordpressDescription('');
      				         $current_context->setWordpressId(0);
      				         // Save item
      				         $current_context->save();
				         }
				      } else if($additional['action'] == 'create_wiki'){
				         // Set modificator and modification date
                     #if ( isset($form_data['wikilink']) and !empty($form_data['wikilink']) and $form_data['wikilink'] == 'yes') {
                        $current_context->setWikiHomeLink();
                     #} else {
                     #   $current_context->unsetWikiHomeLink();
                     #}
                     if ( isset($form_data['wikilink2']) and !empty($form_data['wikilink2']) and $form_data['wikilink2'] == 'yes') {
                        $current_context->setWikiPortalLink();
                     } else {
                        $current_context->unsetWikiPortalLink();
                     }
                     if ( isset($form_data['wiki_skin_choice']) and !empty($form_data['wiki_skin_choice']) ) {
                        $current_context->setWikiSkin($form_data['wiki_skin_choice']);
                     }
                     if ( isset($form_data['wikititle']) and !empty($form_data['wikititle']) ) {
                        $current_context->setWikiTitle($form_data['wikititle']);
                     } else {
                        $current_context->setWikiTitle($current_context->getTitle());
                     }

                     if ( isset($form_data['admin']) and !empty($form_data['admin']) ) {
                        $current_context->setWikiAdminPW($form_data['admin']);
                     }

                     if ( isset($form_data['edit']) and !empty($form_data['edit']) ) {
                        $current_context->setWikiEditPW($form_data['edit']);
                     } else {
                        $current_context->setWikiEditPW('');
                     }

                     if ( isset($form_data['read']) and !empty($form_data['read']) ) {
                        $current_context->setWikiReadPW($form_data['read']);
                     } else {
                        $current_context->setWikiReadPW('');
                     }

                     #if ( isset($form_data['use_commsy_login']) ) {
                        $current_context->setWikiUseCommSyLogin();
                     #} else {
                     #   $current_context->unsetWikiUseCommSyLogin();
                     #}

                     if ( isset($form_data['community_read_access']) ) {
                        $current_context->setWikiCommunityReadAccess();
                     } else {
                        $current_context->unsetWikiCommunityReadAccess();
                     }

                     if ( isset($form_data['community_write_access']) ) {
                        $current_context->setWikiCommunityWriteAccess();
                     } else {
                        $current_context->unsetWikiCommunityWriteAccess();
                     }

                     if ( isset($form_data['portal_read_access']) ) {
                        $current_context->setWikiPortalReadAccess();
                     } else {
                        $current_context->unsetWikiPortalReadAccess();
                     }

                     if ( isset($form_data['room_mod_write_access']) ) {
                        $current_context->setWikiRoomModWriteAccess();
                     } else {
                        $current_context->unsetWikiRoomModWriteAccess();
                     }

                     if ( isset($form_data['show_login_box']) ) {
                        $current_context->setWikiShowCommSyLogin();
                     } else {
                        $current_context->unsetWikiShowCommSyLogin();
                     }

                     #if ( isset($form_data['enable_fckeditor']) ) {
                        $current_context->setWikiEnableFCKEditor();
                     #} else {
                     #   $current_context->unsetWikiEnableFCKEditor();
                     #}

                     #if ( isset($form_data['enable_sitemap']) ) {
                        $current_context->setWikiEnableSitemap();
                     #} else {
                     #   $current_context->unsetWikiEnableSitemap();
                     #}

                     #if ( isset($form_data['enable_statistic']) ) {
                        $current_context->setWikiEnableStatistic();
                     #} else {
                     #   $current_context->unsetWikiEnableStatistic();
                     #}

                     #if ( isset($form_data['enable_search']) ) {
                        $current_context->setWikiEnableSearch();
                     #} else {
                     #   $current_context->unsetWikiEnableSearch();
                     #}

                     #if ( isset($form_data['enable_rss']) ) {
                        $current_context->setWikiEnableRss();
                     #} else {
                     #   $current_context->unsetWikiEnableRss();
                     #}

                     if ( isset($form_data['enable_calendar']) ) {
                        $current_context->setWikiEnableCalendar();
                     } else {
                        $current_context->unsetWikiEnableCalendar();
                     }

                     if ( isset($form_data['enable_gallery']) ) {
                        $current_context->setWikiEnableGallery();
                     } else {
                        $current_context->unsetWikiEnableGallery();
                     }

                     if ( isset($form_data['enable_notice']) ) {
                        $current_context->setWikiEnableNotice();
                     } else {
                        $current_context->unsetWikiEnableNotice();
                     }

                     #if ( isset($form_data['enable_pdf']) ) {
                        $current_context->setWikiEnablePdf();
                     #} else {
                     #   $current_context->unsetWikiEnablePdf();
                     #}

                     if ( isset($form_data['enable_rater']) ) {
                        $current_context->setWikiEnableRater();
                     } else {
                        $current_context->unsetWikiEnableRater();
                     }

                     #if ( isset($form_data['enable_listcategories']) ) {
                        $current_context->setWikiEnableListCategories();
                     #} else {
                     #   $current_context->unsetWikiEnableListCategories();
                     #}

                     if ((isset($form_data['new_page_template'])) &&  ($_POST['new_page_template'] != '')) {
                        $current_context->setWikiNewPageTemplate($_POST['new_page_template']);
                     } else {
                        $current_context->unsetWikiNewPageTemplate();
                     }

                     if ( isset($form_data['enable_swf']) ) {
                        $current_context->setWikiEnableSwf();
                     } else {
                        $current_context->unsetWikiEnableSwf();
                     }

                     if ( isset($form_data['enable_wmplayer']) ) {
                        $current_context->setWikiEnableWmplayer();
                     } else {
                        $current_context->unsetWikiEnableWmplayer();
                     }

                     if ( isset($form_data['enable_quicktime']) ) {
                        $current_context->setWikiEnableQuicktime();
                     } else {
                        $current_context->unsetWikiEnableQuicktime();
                     }

                     if ( isset($form_data['enable_youtube_google_vimeo']) ) {
                        $current_context->setWikiEnableYoutubeGoogleVimeo();
                     } else {
                        $current_context->unsetWikiEnableYoutubeGoogleVimeo();
                     }

                     include_once('functions/development_functions.php');

                     // Discussion
                     #if ( isset($form_data['enable_discussion']) ) {
                        $current_context->setWikiEnableDiscussion();
                        if ( isset($form_data['new_discussion']) ) {
                           $_POST['new_discussion'] = $form_data['new_discussion'];
                           $current_context->WikiSetNewDiscussion($form_data['new_discussion']);
                        }
                     #} else {
                     #   $current_context->unsetWikiEnableDiscussion();
                     #}

                     $enable_discussion_discussions = array();
                     $form_data_keys = array_keys($form_data);
                     foreach($form_data_keys as $form_data_key){
                        if(stristr($form_data_key, 'enable_discussion_discussions_')){;
                           $enable_discussion_discussions[] = $form_data[$form_data_key];
                        }
                     }
                     $_POST['enable_discussion_discussions'] = $enable_discussion_discussions;

                     if ( isset($form_data['enable_discussion_notification']) ) {
                        $current_context->setWikiEnableDiscussionNotification();
                     } else {
                        $current_context->unsetWikiEnableDiscussionNotification();
                     }

                    if ( isset($form_data['enable_discussion_notification_groups']) ) {
                        $current_context->setWikiEnableDiscussionNotificationGroups();
                    } else {
                        $current_context->unsetWikiEnableDiscussionNotificationGroups();
                    }

                    if ( isset($form_data['wiki_section_edit']) ) {
                        $current_context->setWikiWithSectionEdit();
                    } else {
                        $current_context->setWikiWithoutSectionEdit();
                    }

                    if ( isset($form_data['wiki_section_edit_header']) ) {
                        $current_context->setWikiWithHeaderForSectionEdit();
                    } else {
                        $current_context->setWikiWithoutHeaderForSectionEdit();
                    }

                     $current_context->setWikiExists();
                     $current_context->setWikiActive();

                     $wiki_manager->createWiki($current_context);

                     // Save item - after createWiki() -> old discussions might be deleted
                     $current_context->save();

                     $enable_wiki_groups = array();
                     $form_data_keys = array_keys($form_data);
                     foreach($form_data_keys as $form_data_key){
                        if(stristr($form_data_key, 'enable_wiki_groups_')){;
                           $enable_wiki_groups[] = $form_data[$form_data_key];
                        }
                     }

                     // WSDL-xml hier noch nicht zugreifbar, daher weiterhin die alte Variante
                     if ( !empty($enable_wiki_groups)){
                        //global $c_use_soap_for_wiki;
                        //if(!$c_use_soap_for_wiki){
                           $wiki_manager->setWikiGroupsAsPublic($enable_wiki_groups);
                        //} else {
                        //   $wiki_manager->setWikiGroupsAsPublic_soap($_POST['enable_wiki_groups']);
                        //}
                     } else {
                        //global $c_use_soap_for_wiki;
                        //if(!$c_use_soap_for_wiki){
                           $wiki_manager->setWikiGroupsAsPublic(array());
                        //} else {
                        //   $wiki_manager->setWikiGroupsAsPublic_soap(array());
                        //}
                     }
				      } else if ($additional['action'] == 'delete_wiki'){
				         $current_user = $this->_environment->getCurrentUserItem();
                     $current_context->setModificatorItem($current_user);
                     $current_context->setModificationDate(getCurrentDateTimeInMySQL());
                     $current_context->unsetWikiExists();
                     $current_context->setWikiInActive();
                     $current_context->setWikiSkin('pmwiki');
                     $current_context->setWikiTitle($current_context->getTitle());
                     $current_context->unsetWikiEnableDiscussion();
                     $current_context->unsetWikiEnableDiscussionNotification();
                     $current_context->unsetWikiEnableDiscussionNotificationGroups();
                     $current_context->unsetWikiDiscussionArray();
                     // Save item
                     $current_context->save();
                     // delete wiki
                     $wiki_manager->deleteWiki($current_context);
				      } else if ($additional['action'] == 'chat'){
				         if ( isset($form_data['chatlink']) and !empty($form_data['chatlink']) and $form_data['chatlink'] == 'yes') {
                            $current_context->setChatLinkActive();
                         } else {
                            $current_context->setChatLinkInactive();
                         }
                         $current_context->save();
				      }
				      
				      // limesurvey
				      elseif ( $additional['action'] == 'save_limesurvey' )
				      {
				      	if ( isset($form_data['limesurvey_room']) && $form_data['limesurvey_room'] === "yes" )
				      	{
				      		$current_context->setLimeSurveyActive();
				      	}
				      	else
				      	{
				      		$current_context->setLimeSurveyInactive();
				      	}
				      	
				      	$current_context->save();
				      }

                      // mdo
                      elseif ($additional['action'] == 'save_mdo') {
                      	global $c_media_integration_pw_api;
                      	// global $c_media_integration_authcode;
                      	global $c_media_integration;

                      	if ($c_media_integration) {
	                      	// check password if mdo is active
	                      	$dsnr = $form_data['dsnr'];
	                      	$password = $form_data['pw'];

	                      	$requestUrl = $c_media_integration_pw_api . '?action=verifyPWD&dstnr='.$dsnr.'&pwd='.md5($password); //&authCode='.$c_media_integration_authcode
	                      	$response = file_get_contents($requestUrl);
	                      	$xml = new SimpleXMLElement($response);

							$result = (string) $xml->result;
							$pwd = (string) $xml->schule->pwd;

	                      	if($result == "OK" && $pwd == "OK") {
	                      		$saveFlag = true;
	                      	} else {
	                      		$saveFlag = false;
	                      		$this->_popup_controller->setErrorReturn('900', 'authentification failed', array());

								return false;
	                      	}
						} else {
							$saveFlag = true;
						}

                      	if($saveFlag || !isset($form_data['mdo_room'])) {
                      		if( isset($form_data['mdo_room']) && $form_data['mdo_room'] === "yes") {
                            	$current_context->setMDOActive(true);
	                        } else {
	                            $current_context->setMDOActive(false);
	                        }
	                        if( isset($form_data['mdo_key'])) {
	                            $current_context->setMDOKey($form_data['mdo_key']);
	                        } else {
	                            $current_context->setMDOKey();
	                        }
	                        $current_context->save();
                      	}
                        

                      }
				      
				      // plugins
				      elseif ( substr($additional['action'],0,7) == 'plugin_' ) {
				         $plugin = substr($additional['action'],7);

		               $plugin_class = $this->_environment->getPluginClass($plugin);
				         if ( !empty($plugin_class)
				              and method_exists($plugin_class,'isConfigurableInPortal')
				            ) {
				            if ( ( $this->_environment->inPortal()
				                   and $plugin_class->isConfigurableInPortal()
				                 )
				                 or
				                 ( !$this->_environment->inServer()
				                   and method_exists($plugin_class,'isConfigurableInRoom')
				                   and $plugin_class->isConfigurableInRoom($current_context->getItemType())
				                 )
				               ) {
				               if ( !empty($form_data[$plugin.'_on'])
				                    and $form_data[$plugin.'_on'] == 'yes'
				                  ) {
				                  $current_context->setPluginOn($plugin);
				               } else {
				                  $current_context->setPluginOff($plugin);
				               }
				               
				               $values = $form_data;
				               $values['current_context_item'] = $current_context;
				               if ( $this->_environment->inPortal()
				                    and method_exists($plugin_class,'configurationAtPortal')
				                  ) {
				                  $plugin_class->configurationAtPortal('save_config',$values);
				               } elseif ( !$this->_environment->inServer()
				                          and method_exists($plugin_class,'configurationAtRoom')
				                        ) {
				                  $plugin_class->configurationAtRoom('save_config',$values);
				               }
				            }
				         }
				         $current_context->save();
				      }
				      // plugins
				      
				      // set return
				      $this->_popup_controller->setSuccessfullItemIDReturn($current_context->getItemID());
				   }
				   break;
			}
		}
	}

	public function initPopup($data) {
		$current_context = $this->_environment->getCurrentContextItem();
		$current_portal = $this->_environment->getCurrentPortalItem();
		$current_user = $this->_environment->getCurrentUser();
		$translator = $this->_environment->getTranslationObject();

		//rubric_choice
		$room = $this->_environment->getCurrentContextItem();
		$default_rubrics = $room->getAvailableDefaultRubricArray();
		$rubric_array = array();
		$i = 1;
		$select_array[0]['text'] = '----------';
		$select_array[0]['value'] = 'none';
		foreach ($default_rubrics as $rubric){
			if ($this->_environment->inPrivateRoom() and $rubric =='user' ){
				$select_array[$i]['text'] = $this->_translator->getMessage('COMMON_MY_USER_DESCRIPTION');
			} else {
				switch ( mb_strtoupper($rubric, 'UTF-8') ){
					case 'ANNOUNCEMENT':
						$select_array[$i]['text'] = $translator->getMessage('ANNOUNCEMENT_INDEX');
						break;
					case 'DATE':
						$select_array[$i]['text'] = $translator->getMessage('DATE_INDEX');
						break;
					case 'DISCUSSION':
						$select_array[$i]['text'] = $translator->getMessage('DISCUSSION_INDEX');
						break;
					case 'GROUP':
						$select_array[$i]['text'] = $translator->getMessage('GROUP_INDEX');
						break;
					case 'INSTITUTION':
						$select_array[$i]['text'] = $translator->getMessage('INSTITUTION_INDEX');
						break;
					case 'MATERIAL':
						$select_array[$i]['text'] = $translator->getMessage('MATERIAL_INDEX');
						break;
					case 'PROJECT':
						$select_array[$i]['text'] = $translator->getMessage('PROJECT_INDEX');
						break;
					case 'TODO':
						$select_array[$i]['text'] = $translator->getMessage('TODO_INDEX');
						break;
					case 'TOPIC':
						$select_array[$i]['text'] = $translator->getMessage('TOPIC_INDEX');
						break;
					case 'USER':
						$select_array[$i]['text'] = $translator->getMessage('USER_INDEX');
						break;
					default:
						$text = '';
						if ( $this->_environment->isPlugin($rubric) ) {
							$text = plugin_hook_output($rubric,'getDisplayName');
						}
						if ( !empty($text) ) {
							$select_array[$i]['text'] = $text;
						} else {
							$select_array[$i]['text'] = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_configuration_rubric_form('.__LINE__.') ');
						}
						break;
				}
			}
			$select_array[$i]['value'] = $rubric;
			$i++;
		}
		// sorting
		$sort_by = 'text';
		usort($select_array,create_function('$a,$b','return strnatcasecmp($a[\''.$sort_by.'\'],$b[\''.$sort_by.'\']);'));
		$this->_rubric_array = $select_array;

		// time pulses
		if (
				( $current_context->isProjectRoom() and $this->_environment->inProjectRoom() )
				or ( $current_context->isProjectRoom()
						and $this->_environment->inCommunityRoom()
						and $current_context->showTime()
				)
				or ( $this->_environment->getCurrentModule() == CS_PROJECT_TYPE
						and ( $this->_environment->inCommunityRoom() or $this->_environment->inPortal() )
						and $current_context->showTime()
				)
				or ( $this->_environment->inGroupRoom()
						and $current_portal->showTime()
				)
		) {
			if($this->_environment->inPortal()) {
				$portal_item = $current_context;
			} else {
				$portal_item = $current_context->getContextItem();
			}

			if($portal_item->showTime()) {
				$current_time_title = $portal_item->getTitleOfCurrentTime();

				if(isset($current_context)) {
					$time_list = $current_context->getTimeList();

					if($time_list->isNotEmpty()) {
						$time_item = $time_list->getFirst();
						$linked_time_title = $time_item->getTitle();
					}
				}

				if(!empty($linked_time_title) && $linked_time_title < $current_time_title) {
					$start_time_title = $linked_time_title;
				} else {
					$start_time_title = $current_time_title;
				}
				$time_list = $portal_item->getTimeList();

				if($time_list->isNotEmpty()) {
					$time_item = $time_list->getFirst();

					$context_time_list = $current_context->getTimeList();

					while($time_item) {
						// check if checked
						$checked = false;
						if($context_time_list->isNotEmpty()) {
							$context_time_item = $context_time_list->getFirst();

							while($context_time_item) {
								if($context_time_item->getItemID() === $time_item->getItemID()) {
									$checked = true;
									break;
								}

								$context_time_item = $context_time_list->getNext();
							}
						}

						if($time_item->getTitle() >= $start_time_title) {
							$this->_time_array[] = array(
								'text'		=> $translator->getTimeMessage($time_item->getTitle()),
								'value'		=> $time_item->getItemID(),
								'checked'	=> $checked
							);
						}

						$time_item = $time_list->getNext();
					}

					// continuous
					$this->_time_array[] = array(
						'text'		=> $translator->getMessage('COMMON_CONTINUOUS'),
						'value'		=> 'cont',
						'checked'	=> $current_context->isContinuous()
					);
				}
			}
		}

		// assignment
		if($this->_environment->inProjectRoom()) {
			$community_room_array = array();

			// get community list and build up select options
			$community_list = $current_portal->getCommunityList();

			$community_room_array[] = array(
				'text'		=> $translator->getMessage('PREFERENCES_NO_COMMUNITY_ROOM'),
				'value'		=> '-1',
				'checked'	=> false
			);
			$community_room_array[] = array(
				'text'		=> '--------------------',
				'value'		=> 'disabled',
				'checked'	=> false,
				'disabled'	=> true
			);

			if($community_list->isNotEmpty()) {
				$community_item = $community_list->getFirst();

				while($community_item) {
					if($community_item->isAssignmentOnlyOpenForRoomMembers()) {
						if(!$community_item->isUser($current_user)) {
							$community_room_array[] = array(
								'text'		=> $community_item->getTitle(),
								'value'		=> 'disabled',
								'disabled'	=> true
							);
						} else {
							$community_room_array[] = array(
								'text'		=> $community_item->getTitle(),
								'value'		=> $community_item->getItemID(),
								'disabled'	=> false
							);
						}
					} else {
						$community_room_array[] = array(
							'text'		=> $community_item->getTitle(),
							'value'		=> $community_item->getItemID(),
							'disabled'	=> false
						);
					}

					$community_item = $community_list->getNext();
				}
			}

			$this->_community_room_array = $community_room_array;

			$shown_community_room_array = array();
			/*
			if (!empty($this->_session_community_room_array)) {
				foreach ( $this->_session_community_room_array as $community_room ) {
					$temp_array['text'] = $community_room['name'];
					$temp_array['value'] = $community_room['id'];
					$community_room_array[] = $temp_array;
				}
			} else{
			*/
			$community_room_list = $current_context->getCommunityList();
			if($community_room_list->getCount() > 0) {
				$community_room_item = $community_room_list->getFirst();

				while($community_room_item) {
					$shown_community_room_array[] = array(
						'text'	=> $community_room_item->getTitle(),
						'value'	=> $community_room_item->getItemID()
					);

					$community_room_item = $community_room_list->getNext();
				}
			}
			/*
			}
			*/

			$this->_shown_community_room_array = $shown_community_room_array;
		}

		global $symfonyContainer;
		$c_theme = $symfonyContainer->getParameter('commsy.themes.default');

		$default_color_value = 'default';
		if(isset($c_theme) and !empty($c_theme) and $c_theme != 'default'){
			$default_color_value = $c_theme;
		}
		
		global $theme_array;
		foreach($theme_array as $theme){
			$temp_color_array[$theme['value']] = $theme;
		}

		ksort($temp_color_array);
		$this->_color_array = array_merge($this->_color_array, $temp_color_array);
		
		$this->_color_array[] = array(
				'text'		=> $translator->getMessage('COMMON_COLOR_DEFAULT'),
				'value'		=> $default_color_value,
				'disabled'	=> false
		);

		$this->_color_array[] = array(
			'text'		=> '-----',
			'value'		=> '-1',
			'disabled'	=> true
		);
		$this->_color_array[] = array(
			'text'		=> $translator->getMessage('COMMON_COLOR_SCHEMA_OWN'),
			'value'		=> 'individual',
			'disabled'	=> false
		);
		/*



		$current_portal_item = $this->_environment->getCurrentPortalItem();

		/*
		// set configuration
		$account = array();

		// set user item
		if($this->_environment->inCommunityRoom() || $this->_environment->inProjectRoom()) {
			$this->_user = $this->_environment->getPortalUserItem();
		} else {
			$this->_user = $this->_environment->getCurrentUserItem();
		}

		// disable merge form only for root
		$this->_config['show_merge_form'] = true;
		if(isset($this->_user) && $this->_user->isRoot()) {
			$this->_config['show_merge_form'] = false;
		}

		// auth source
		if(!isset($current_portal_item)) $current_portal_item = $this->_environment->getServerItem();

		#$this->_show_auth_source = $current_portal_item->showAuthAtLogin();
		# muss angezeigt werden, sonst koennen mit der aktuellen Programmierung
		# keine Acounts mit gleichen Kennungen aber unterschiedlichen Quellen
		# zusammengelegt werden
		$this->_config['show_auth_source'] = true;

		$auth_source_list = $current_portal_item->getAuthSourceListEnabled();
		if(isset($auth_source_list) && !$auth_source_list->isEmpty()) {
			$auth_source_item = $auth_source_list->getFirst();

			while($auth_source_item) {
				$this->_data['auth_source_array'][] = array(
					'value'		=> $auth_source_item->getItemID(),
					'text'		=> $auth_source_item->getTitle());

				$auth_source_item = $auth_source_list->getNext();
			}
		}
		$this->_data['default_auth_source'] = $current_portal_item->getAuthDefault();

		// password change form
		$this->_config['show_password_change_form'] = false;
		$current_auth_source_item = $current_portal_item->getAuthSource($this->_user->getAuthSource());
		if(	(isset($current_auth_source_item) && $current_auth_source_item->allowChangePassword()) ||
			$this->_user->isRoot()) {

			$this->_config['show_password_change_form'] = true;
		}

		// account change form
		$this->_config['show_account_change_form'] = false;
		if(	(isset($current_auth_source_item) && $current_auth_source_item->allowChangeUserID()) ||
			$this->_user->isRoot()) {

			$this->_config['show_account_change_form'] = true;
		}

		// mail form
		$this->_config['show_mail_change_form'] = false;
		if($this->_user->isModerator()) {
			$this->_config['show_mail_change_form'] = true;
		}

		*/

		// assign template vars
		$this->assignTemplateVars();
	}

	public function getFieldInformation($sub) {


		// TODO
		// form_data[communityrooms} is mendatory if the following is true
		/*
		 * if($this->_environment->inProjectRoom()) {
			// project room
			if(!empty($this->_community_room_array)) {
				$portal_item = $this->_environment->getCurrentPortalItem();
				$project_room_link_status = $portal_item->getProjectRoomLinkStatus();
		 */

		$return = array(
			'newsletter'	=> array(
				array('name' => 'newsletter', 'type' => 'radio', 'mandatory' => true)
			),
			'merge'	=> array(
				array('name' => 'merge_user_id', 'type' => 'text', 'mandatory' => false),
				array('name' => 'merge_user_password', 'type' => 'text', 'mandatory' => false)
			),
			'account'	=> array(
				array('name' => 'forename', 'type' => 'text', 'mandatory' => true),
				array('name' => 'surname', 'type' => 'text', 'mandatory' => true),
				array('name' => 'user_id', 'type' => 'text', 'mandatory' => true),
				array('name' => 'old_password', 'type' => 'text', 'mandatory' => false),
				array('name' => 'new_password', 'type' => 'text', 'mandatory' => false, 'same_as' => 'new_password_confirm'),
				array('name' => 'new_password_confirm', 'type' => 'text', 'mandatory' => true),
				array('name' => 'language', 'type' => 'select', 'mandatory' => true),
				array('name' => 'mail_account', 'type' => 'checkbox', 'mandatory' => false),
				array('name' => 'mail_room', 'type' => 'checkbox', 'mandatory' => false),
				array('name' => 'upload', 'type' => 'radio', 'mandatory' => true),
				array('name' => 'auto_save', 'type' => 'checkbox', 'mandatory' => true),
			)
		);

		return $return[$sub];
	}

	private function assignTemplateVars() {
		$translator = $this->_environment->getTranslationObject();
		$current_user = $this->_environment->getCurrentUserItem();
		$portal_user = $this->_environment->getPortalUserItem();

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

		// room information
		$this->_popup_controller->assign('popup', 'room', $this->getRoomInformation());
		$this->_popup_controller->assign('popup', 'additional', $this->getAdditionalInformation());
		$this->_popup_controller->assign('popup', 'moderation', $this->getModerationInformation());
		$this->_popup_controller->assign('popup', 'addon', $this->getAddonInformation());
		$this->_popup_controller->assign('popup', 'external', $this->getExternalInformation());
	}

	private function getModerationInformation() {
		$return = array();
		$current_context = $this->_environment->getCurrentContextItem();
		$translator = $this->_environment->getTranslationObject();

		//Informationbox
        $return['item_id'] = $current_context->getInformationBoxEntryID();
        if ( $current_context->withInformationBox() ) {
           $return['show_information_box'] = '1';
        } else {
           $return['show_information_box'] = '0';
        }

		//Usage Infos
        $default_rubrics = $current_context->getAvailableRubrics();
        $array_info_text = array();
        $rubric_array = array();
        $temp_array['rubric']  = $translator->getMessage('HOME_INDEX');
        $temp_array['key'] = 'home';
	    $temp_array['title'] = $current_context->getUsageInfoHeaderForRubric('home');
	    $temp_array['text'] = $current_context->getUsageInfoTextForRubricInForm('home');
        $array_info_text[] = $temp_array;
        foreach ($default_rubrics as $rubric) {
             $temp_array = array();
             switch ( mb_strtoupper($rubric, 'UTF-8') ){
                case 'ANNOUNCEMENT':
                   $temp_array['rubric'] = $translator->getMessage('ANNOUNCEMENT_INDEX');
                   break;
                case 'DATE':
                   $temp_array['rubric'] = $translator->getMessage('DATE_INDEX');
                   break;
                case 'DISCUSSION':
                   $temp_array['rubric'] = $translator->getMessage('DISCUSSION_INDEX');
                   break;
                case 'INSTITUTION':
                   $temp_array['rubric'] = $translator->getMessage('INSTITUTION_INDEX');
                   break;
                case 'GROUP':
                   $temp_array['rubric'] = $translator->getMessage('GROUP_INDEX');
                   break;
                case 'MATERIAL':
                   $temp_array['rubric'] = $translator->getMessage('MATERIAL_INDEX');
                   break;
                case 'PROJECT':
                   $temp_array['rubric'] = $translator->getMessage('PROJECT_INDEX');
                   break;
                case 'TODO':
                   $temp_array['rubric'] = $translator->getMessage('TODO_INDEX');
                   break;
                case 'TOPIC':
                   $temp_array['rubric'] = $translator->getMessage('TOPIC_INDEX');
                   break;
                case 'USER':
                   $temp_array['rubric'] = $translator->getMessage('USER_INDEX');
                   break;
                default:
                   $temp_array['rubric'] = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_configuration_usageinfo_form(113) ');
                   break;
              }
              $temp_array['key'] = $rubric;
	          $temp_array['title'] = $current_context->getUsageInfoHeaderForRubric($rubric);
	          $temp_array['text'] = $current_context->getUsageInfoTextForRubricInForm($rubric);
              $array_info_text[] = $temp_array;
              unset($temp_array);
         }
		 $return['array_info_text'] = $array_info_text;

	      // mail text choice
	      $array_mail_text[0]['text']  = '*'.$translator->getMessage('MAIL_CHOICE_CHOOSE_TEXT');
	      $array_mail_text[0]['value'] = -1;

	      // mail salutation
	      $array_mail_text[1]['text']  = '----------------------';
	      $array_mail_text[1]['value'] = 'disabled';
	      $array_mail_text[2]['text']  = $translator->getMessage('MAIL_CHOICE_HELLO');
	      $array_mail_text[2]['value'] = 'MAIL_CHOICE_HELLO';

	      $array_mail_text[3]['text']  = $translator->getMessage('MAIL_CHOICE_CIAO');
	      $array_mail_text[3]['value'] = 'MAIL_CHOICE_CIAO';

	      // user
	      $array_mail_text[4]['text']  = '----------------------';
	      $array_mail_text[4]['value'] = 'disabled';
	      $array_mail_text[5]['text']  = $translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_DELETE');
	      $array_mail_text[5]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_DELETE';
	      $array_mail_text[6]['text']  = $translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_LOCK');
	      $array_mail_text[6]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_LOCK';
	      $array_mail_text[7]['text']  = $translator->getMessage('MAIL_CHOICE_USER_STATUS_USER');
	      $array_mail_text[7]['value'] = 'MAIL_CHOICE_USER_STATUS_USER';
	      $array_mail_text[8]['text']  = $translator->getMessage('MAIL_CHOICE_USER_STATUS_MODERATOR');
	      $array_mail_text[8]['value'] = 'MAIL_CHOICE_USER_STATUS_MODERATOR';
	      $array_mail_text[9]['text']  = $translator->getMessage('MAIL_CHOICE_USER_MAKE_CONTACT_PERSON');
	      $array_mail_text[9]['value'] = 'MAIL_CHOICE_USER_MAKE_CONTACT_PERSON';
	      $array_mail_text[10]['text']  = $translator->getMessage('MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON');
	      $array_mail_text[10]['value'] = 'MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON';
          $array_mail_text[11]['text']  = $translator->getMessage('MAIL_CHOICE_USER_STATUS_READ_ONLY_USER');
          $array_mail_text[11]['value'] = 'MAIL_CHOICE_USER_STATUS_READ_ONLY_USER';
	      if ($this->_environment->inCommunityRoom()) {
	         $array_mail_text[12]['text']  = $translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_PASSWORD');
	         $array_mail_text[12]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_PASSWORD';
	         $array_mail_text[13]['text']  = $translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_MERGE');
	         $array_mail_text[13]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_MERGE';
	      }
          

	      $languages = $this->_environment->getAvailableLanguageArray();
	      foreach($array_mail_text as $index => $array) {
	      	switch($array['value']) {
	      		case -1:										$message_tag = ''; break;
	      		case 'MAIL_CHOICE_HELLO':						$message_tag = 'MAIL_BODY_HELLO'; break;
	      		case 'MAIL_CHOICE_CIAO':						$message_tag = 'MAIL_BODY_CIAO'; break;
	      		case 'MAIL_CHOICE_USER_ACCOUNT_DELETE':			$message_tag = 'MAIL_BODY_USER_ACCOUNT_DELETE'; break;
	      		case 'MAIL_CHOICE_USER_ACCOUNT_LOCK':			$message_tag = 'MAIL_BODY_USER_ACCOUNT_LOCK'; break;
	      		case 'MAIL_CHOICE_USER_STATUS_USER':			$message_tag = 'MAIL_BODY_USER_STATUS_USER'; break;
	      		case 'MAIL_CHOICE_USER_STATUS_MODERATOR':		$message_tag = 'MAIL_BODY_USER_STATUS_MODERATOR'; break;
	      		case 'MAIL_CHOICE_USER_MAKE_CONTACT_PERSON':	$message_tag = 'MAIL_BODY_USER_MAKE_CONTACT_PERSON'; break;
	      		case 'MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON':	$message_tag = 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON'; break;
	      		case 'MAIL_CHOICE_USER_ACCOUNT_PASSWORD':		$message_tag = 'MAIL_BODY_USER_ACCOUNT_PASSWORD'; break;
	      		case 'MAIL_CHOICE_USER_ACCOUNT_MERGE':			$message_tag = 'MAIL_BODY_USER_ACCOUNT_MERGE'; break;
	      		case 'MAIL_CHOICE_USER_PASSWORD_CHANGE':		$message_tag = 'MAIL_BODY_USER_PASSWORD_CHANGE'; break;
	      		case 'MAIL_CHOICE_MATERIAL_WORLDPUBLIC':		$message_tag = 'MAIL_BODY_MATERIAL_WORLDPUBLIC'; break;
	      		case 'MAIL_CHOICE_MATERIAL_NOT_WORLDPUBLIC':	$message_tag = 'MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC'; break;
	      		case 'MAIL_CHOICE_ROOM_LOCK':					$message_tag = 'MAIL_BODY_ROOM_LOCK'; break;
	      		case 'MAIL_CHOICE_ROOM_UNLOCK':					$message_tag = 'MAIL_BODY_ROOM_UNLOCK'; break;
	      		case 'MAIL_CHOICE_ROOM_UNLINK':					$message_tag = 'MAIL_BODY_ROOM_UNLINK'; break;
	      		case 'MAIL_CHOICE_ROOM_DELETE':					$message_tag = 'MAIL_BODY_ROOM_DELETE'; break;
	      		case 'MAIL_CHOICE_ROOM_OPEN':					$message_tag = 'MAIL_BODY_ROOM_OPEN'; break;
                case 'MAIL_CHOICE_USER_STATUS_READ_ONLY_USER':  $message_tag = 'MAIL_BODY_USER_STATUS_USER_READ_ONLY'; break;
	      	}

	      	foreach ($languages as $language) {
	      		if (!empty($message_tag)) {
	      			$array_mail_text[$index]['body_' . $language] = $translator->getEmailMessageInLang($language,$message_tag);
	      		} else {
	      			$array_mail_text[$index]['body_' . $language] = '';
	      		}
	      	}

	      }

		 $return['array_mail_text'] = $array_mail_text;


		 return $return;
	}

	private function getAdditionalInformation() {
		$return = array();
		$current_context = $this->_environment->getCurrentContextItem();
		$translator = $this->_environment->getTranslationObject();
		$return['dates_status'] = $current_context->getDatesPresentationStatus();

	    $todo_status_array = $current_context->getExtraToDoStatusArray();
	    $status_array = array();
	    foreach ($todo_status_array as $key=>$value){
	       $temp_array['text']  = $value;
	       $temp_array['value'] = $key;
	       $status_array[] = $temp_array;
	    }
	    $return['additional_extra_status_array']  = $status_array;

		// rss
		if($current_context->isRSSOn()) {
			$return['rss'] = 'yes';
		} else {
			$return['rss'] = 'no';
		}
		
		// announcement date
		if ($current_context->withAnnouncementDates()){
			$return['announcement_date'] = 'yes';
		}

         //buzzwords
         if ($current_context->withBuzzwords()){
            $return['buzzword'] = 'yes';
         }
         if ($current_context->isBuzzwordMandatory()){
            $return['buzzword_mandatory'] = 'yes';
         }

         if ($current_context->isBuzzwordShowExpanded()){
         	$return['buzzword_fadeout'] = 'yes';
         }

         if ($current_context->isTagsShowExpanded()){
         	$return['tags_fadeout'] = 'yes';
         }

         //tags
         if ($current_context->withTags()){
            $return['tags'] = 'yes';
         }
         if ($current_context->isTagMandatory()){
            $return['tags_mandatory'] = 'yes';
         }
         if (!$current_context->isTagEditedByAll()){
            $return['tags_edit'] = 'yes';
         }

         $return['time_spread'] = $current_context->getTimeSpread();

         if ($current_context->isTemplate()) {
            $return['template'] = 1;
         }
         if ( $current_context->isCommunityRoom() ){
            $return['template_availability'] = $current_context->getCommunityTemplateAvailability();
         }else{
            $return['template_availability'] = $current_context->getTemplateAvailability();
         }
         $return['template_description'] = $current_context->getTemplateDescription();

         if ( $current_context->isOpen() ) {
            $return['room_status'] = '';
         } else {
            $return['room_status'] = '2';
         }
         
         $return['with_archiving_rooms'] = true;

         $agb_text_array = $current_context->getAGBTextArray();
         $languages = $this->_environment->getAvailableLanguageArray();
         foreach ($languages as $language) {
            if (!empty($agb_text_array[cs_strtoupper($language)])) {
               $return['agb_text_'.cs_strtoupper($language)] = $agb_text_array[cs_strtoupper($language)];
            } else {
               $return['agb_text_'.cs_strtoupper($language)] = '';
            }
         }

         $return['agb_status'] = $current_context->getAGBStatus();
         if ($return['agb_status'] != '1'){
         	$return['agb_status'] = '2';
         }

		if ( $current_context->isActionBarVisibleAsDefault()) {
			$return['action_bar_visibility'] = '1';
		}else{
			$return['action_bar_visibility'] = '-1';
		}

		if ( $current_context->isReferenceBarVisibleAsDefault()) {
			$return['reference_bar_visibility'] = '1';
		}else{
			$return['reference_bar_visibility'] = '-1';
		}

		if ( $current_context->isDetailsBarVisibleAsDefault()) {
			$return['details_bar_visibility'] = '1';
		}else{
			$return['details_bar_visibility'] = '-1';
		}

		if ( $current_context->isAnnotationsBarVisibleAsDefault()) {
			$return['annotations_bar_visibility'] = '1';
		}else{
			$return['annotations_bar_visibility'] = '-1';
		}

		return $return;
	}

	private function getAddonInformation() {
		$return = array();
		$current_context = $this->_environment->getCurrentContextItem();
		$translator = $this->_environment->getTranslationObject();

		$return['assessment'] = $current_context->isAssessmentActive();

         if ($current_context->withWorkflowTrafficLight()){
            $return['workflow_trafic_light'] = 'yes';
         }
         $return['workflow_trafic_light_default'] = $current_context->getWorkflowTrafficLightDefault();
         if($current_context->getWorkflowTrafficLightTextGreen() != ''){
            $return['workflow_trafic_light_green_text'] = $current_context->getWorkflowTrafficLightTextGreen();
         } else {
            $return['workflow_trafic_light_green_text'] = $translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT');
         }
         if($current_context->getWorkflowTrafficLightTextYellow() != ''){
            $return['workflow_trafic_light_yellow_text'] = $current_context->getWorkflowTrafficLightTextYellow();
         } else {
            $return['workflow_trafic_light_yellow_text'] = $translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT');
         }
         if($current_context->getWorkflowTrafficLightTextRed() != ''){
            $return['workflow_trafic_light_red_text'] = $current_context->getWorkflowTrafficLightTextRed();
         } else {
            $return['workflow_trafic_light_red_text'] = $translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT');
         }

         // resubmission
         if ($current_context->withWorkflowResubmission()){
            $return['workflow_resubmission'] = 'yes';
         }

         // validity
         $return['workflow_validity'] = 'no';
         if ($current_context->withWorkflowValidity()) {
         	$return['workflow_validity'] = 'yes';
         }

         // reader
         if ($current_context->withWorkflowReader()){
            $return['workflow_reader'] = 'yes';
         }

         // reader
         $return['workflow_reader_group'] = 'no';
         if ($current_context->getWorkflowReaderGroup()) {
         	$return['workflow_reader_group'] = 'yes';
         }

         $return['workflow_reader_person'] = 'no';
         if ($current_context->getWorkflowReaderPerson()) {
         	$return['workflow_reader_person'] = 'yes';
         }

         $return['workflow_resubmission_show_to'] = $current_context->getWorkflowReaderShowTo();

		return $return;
	}

	private function getExternalInformation() {
	   global $c_pmwiki;
	   global $c_etchat_enable;

	   $return = array();
	   $current_context = $this->_environment->getCurrentContextItem();
	   $current_portal = $this->_environment->getCurrentPortalItem();
	   $translator = $this->_environment->getTranslationObject();

	   // Wordpress
	   if($current_portal->getWordpressPortalActive()){
   	   $wordpress_manager = $this->_environment->getWordpressManager();
	      $wordpress = array();
   	   if($current_context->isWordpressActive()){
            $wordpress['wordpress_active'] = 'yes';
   	   }
	      $wordpress['wordpresstitle'] = $current_context->getWordpressTitle();
	      $wordpress['wordpressdescription'] = $current_context->getWordpressDescription();

   	   $wordpress_skins = array();
   	   foreach($wordpress_manager->getSkins() as $key => $value){
   	      $temp_array['text']  = $key;
   	      $temp_array['value'] = $value;
   	      $wordpress_skins[] = $temp_array;
   	   }
   	   $wordpress['skin_array'] = $wordpress_skins;
   	   $wordpress['skin_choice'] = $current_context->getWordpressSkin();

   	   $wordpress_member_roles = array();
   	   $wordpress_member_roles[] = array('text' => $translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_SUBSCRIBER'), 'value' => 'subscriber');
   	   $wordpress_member_roles[] = array('text' => $translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_AUTHOR'), 'value' => 'author');
   	   $wordpress_member_roles[] = array('text' => $translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_EDITOR'), 'value' => 'editor');
   	   $wordpress_member_roles[] = array('text' => $translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_ADMINISTRATOR'), 'value' => 'administrator');
   	   $wordpress['member_role_array'] = $wordpress_member_roles;
   	   $wordpress['member_role'] = $current_context->getWordpressMemberRole();

   	   if($current_context->getWordpressUseComments() == '1'){
   	      $wordpress['use_comments'] = 'yes';
   	   }
   	   if($current_context->getWordpressUseCommentsModeration() == '1'){
   	      $wordpress['use_comments_moderation'] = 'yes';
   	   }

   	   if($current_context->getWordpressHomeLink() == '1'){
            $wordpress['wordpresslink'] = 'yes';
   	   }
      	$return['wordpress'] = $wordpress;
	   } else {
	      $return['wordpress'] = false;
	   }

	   // Wiki
	   if(!empty($c_pmwiki) && $c_pmwiki) {
	      $wiki_manager = $this->_environment->getWikiManager();
	      $wiki = array();

	      $wiki_skins = array();
         global $c_pmwiki_path_file;
         $directory_handle = @opendir($c_pmwiki_path_file.'/pub/skins');
         if ($directory_handle) {
            while (false !== ($dir = readdir($directory_handle))) {
               if ( $dir != 'home'
                    and $dir != '...'
                    and $dir != '..'
                    and $dir != '.'
                    and $dir != 'print'
                    and $dir != 'jsMath'
                    and $dir != 'CVS'
                  ) {
                  $wiki_skins[] = $dir;
               }
            }
         }
         $directory_handle = @opendir($c_pmwiki_path_file.'/wikis/'.$this->_environment->getCurrentPortalID().'/'.$this->_environment->getCurrentContextID().'/pub/skins');
         if ($directory_handle) {
            while (false !== ($dir = readdir($directory_handle))) {
               if ( $dir != 'home'
                    and $dir != '...'
                    and $dir != '..'
                    and $dir != '.'
                    and $dir != 'print'
                    and $dir != 'jsMath'
                    and $dir != 'CVS'
                  ) {
                  $wiki_skins[] = $dir;
               }
            }
         }
   	   $wiki['wiki_skin_array'] = $wiki_skins;

   	   if($current_context->isWikiActive()){
            $wiki['wiki_active'] = 'yes';

            $wiki['wikititle'] = $current_context->getWikiTitle();
            if ($current_context->getWikiHomeLink() == '1'){
               $wiki['wikilink'] = 'yes';
            }
            if ($current_context->getWikiPortalLink() == '1'){
               $wiki['wikilink2'] = 'yes';
            }
            if ($current_context->WikiShowCommSyLogin() == "1"){
               $wiki['show_login_box'] = 'yes';
            }
            if ($current_context->WikiEnableFCKEditor() == "1"){
               $wiki['enable_fckeditor'] = 'yes';
            }
            if ($current_context->WikiEnableSitemap() == "1"){
               $wiki['enable_sitemap'] = 'yes';
            }
            if ($current_context->WikiEnableStatistic() == "1"){
               $wiki['enable_statistic'] = 'yes';
            }
            if ($current_context->WikiEnableSearch() == "1"){
               $wiki['enable_search'] = 'yes';
            }
            if ($current_context->WikiEnableRss() == "1"){
               $wiki['enable_rss'] = 'yes';
            }
            if ($current_context->WikiEnableCalendar() == "1"){
               $wiki['enable_calendar'] = 'yes';
            }
            if ($current_context->WikiEnableGallery() == "1"){
               $wiki['enable_gallery'] = 'yes';
            }
            if ($current_context->WikiEnableNotice() == "1"){
               $wiki['enable_notice'] = 'yes';
            }
            if ($current_context->WikiEnablePdf() == "1"){
               $wiki['enable_pdf'] = 'yes';
            }
            if ($current_context->WikiEnableRater() == "1"){
               $wiki['enable_rater'] = 'yes';
            }
            if ($current_context->WikiEnableListCategories() == "1"){
               $wiki['enable_listcategories'] = 'yes';
            }
            if ($current_context->WikiNewPageTemplate() != "-1"){
               $wiki['new_page_template'] = $this->_item->WikiNewPageTemplate();
            }
            if ($current_context->WikiEnableSwf() == "1"){
               $wiki['enable_swf'] = 'yes';
            }
            if ($current_context->WikiEnableWmplayer() == "1"){
               $wiki['enable_wmplayer'] = 'yes';
            }
            if ($current_context->WikiEnableQuicktime() == "1"){
               $wiki['enable_quicktime'] = 'yes';
            }
            if ($current_context->WikiEnableYoutubeGoogleVimeo() == "1"){
               $wiki['enable_youtube_google_vimeo'] = 'yes';
            }
            if ($current_context->WikiEnableDiscussion() == "1"){
               $wiki['enable_discussion'] = 'yes';
            }
            $wiki['enable_discussion_discussions'] = $current_context->getWikiDiscussionArray();
            if ($current_context->WikiEnableDiscussionNotification() == "1"){
               $wiki['enable_discussion_notification'] = 'yes';
            }
            if ($current_context->WikiEnableDiscussionNotificationGroups() == "1"){
               $wiki['enable_discussion_notification_groups'] = 'yes';
            }
            if ($current_context->WikiUseCommSyLogin() == "1"){
               $wiki['use_commsy_login'] = 'yes';
            }
            if ($current_context->WikiCommunityReadAccess() == "1"){
               $wiki['community_read_access'] = 'yes';
            }
            if ($current_context->WikiCommunityWriteAccess() == "1"){
               $wiki['community_write_access'] = 'yes';
            }
            if ($current_context->WikiPortalReadAccess() == "1"){
               $wiki['portal_read_access'] = 'yes';
            }
            if ($current_context->isWikiRoomModWriteAccess() ) {
               $wiki['room_mod_write_access'] = 'yes';
            }
            $wiki['new_discussion'] = '';
            if ($current_context->wikiWithSectionEdit() ) {
               $wiki['wiki_section_edit'] = 'yes';
            }
            if ($current_context->wikiWithHeaderForSectionEdit() ) {
               $wiki['wiki_section_edit_header'] = 'yes';
            }

            $wiki['wiki_skin_choice'] = $current_context->getWikiSkin();
            $wiki['admin'] = $current_context->getWikiAdminPW();
            $wiki['edit'] = $current_context->getWikiEditPW();
            $wiki['read'] = $current_context->getWikiReadPW();

            $wiki_groups_array = $wiki_manager->getGroupsForWiki(false);
            $temp_wiki_groups_array = array();
            for ($index = 0; $index < sizeof($wiki_groups_array['groups']); $index++) {
               $temp_array = array();
               $temp_array['group'] = $wiki_groups_array['groups'][$index];
               $temp_array['public'] = $wiki_groups_array['public'][$index];
               $temp_wiki_groups_array[] = $temp_array;
            }

            $wiki['enable_wiki_groups'] = $temp_wiki_groups_array;

   	   } else {
      	   $wiki['wikititle'] = $current_context->getWikiTitle();
            $wiki['wiki_skin_choice'] = 'pmwiki';
            $wiki['admin'] = 'admin';
            $wiki['edit'] = 'edit';
            $wiki['read'] = 'read';
            $wiki['show_login_box'] = 'yes';
            $wiki['wikilink'] = 'yes';
            $wiki['use_commsy_login'] = 'yes';
   	   }
	      $return['wiki'] = $wiki;
	   } else {
	      $return['wiki'] = false;
	   }

	   // Chat
	   if(!empty($c_etchat_enable) && $c_etchat_enable) {
	      $return['chat'] = true;
	      if($current_context->isChatLinkActive() == '1'){
	         $return['chatlink'] = 'yes';
	      }
	   }
	   
	   // Limesurvey
	   $portalItem = $this->_environment->getCurrentPortalItem();
	   if ( $portalItem->withLimeSurveyFunctions() && $portalItem->isLimeSurveyActive() )
	   {
	   	 $return['limesurvey'] = true;
	   	 if ( $current_context->isLimeSurveyActive() )
	   	 {
	   	 	$return['limesurvey_room'] = true;
	   	 }
	   	 else
	   	 {
	   	 	$return['limesurvey_room'] = false;
	   	 }
	   }
	   else
	   {
	   	$return['limesurvey'] = false;
	   }


        // Medien Distribution Online
       global $c_media_integration;

       if($c_media_integration && $current_context->isCommunityRoom()) {
        $return['mdo'] = true;
        if($current_context->getMDOActive() == 1) {
            $return['mdo_room'] = true;
        } else {
            $return['mdo_room'] = false;
        }
        if($current_context->getMDOKey()) {
            $return['mdo_key'] = $current_context->getMDOKey();
        } else {
            $return['mdo_key'] = '';
        }
       } else {
        $return['mdo'] = false;
       }
       
	   
	   // plugins - TODO
	   $c_plugin_array = $this->_environment->getConfiguration('c_plugin_array');
	   if (isset($c_plugin_array) and !empty($c_plugin_array)) {
	      $current_portal_item = $this->_environment->getCurrentPortalItem();
	      foreach ($c_plugin_array as $plugin) {
	         $plugin_class = $this->_environment->getPluginClass($plugin);
	         $current_context_item = $this->_environment->getCurrentContextItem();
	         if ( (
	                $this->_environment->inPortal()
	                and method_exists($plugin_class,'isConfigurableInPortal')
	                and $plugin_class->isConfigurableInPortal()
	              )
	              or
	              (
	                !$this->_environment->inServer()
	                and $current_portal_item->isPluginOn($plugin)
	                and method_exists($plugin_class,'isConfigurableInRoom')
	                and $plugin_class->isConfigurableInRoom($current_context_item->getItemType())
	              )
	            ) {
	            $array_plugins[$plugin_class->getIdentifier()]['title'] = $plugin_class->getTitle();
	            if ( method_exists($plugin_class,'getDescription') ) {
	               $array_plugins[$plugin_class->getIdentifier()]['description'] = $plugin_class->getDescription();
	            }
	            if ( method_exists($plugin_class,'getHomepage') ) {
	               $homepage = $plugin_class->getHomepage();
	               if ( !empty($homepage) ) {
	                  $array_plugins[$plugin_class->getIdentifier()]['homepage'] = '___CONFIGURATION_PLUGIN_HOMEPAGE___: <a href="'.$homepage.'" target="_blank" title="___CONFIGURATION_PLUGIN_HOMEPAGE___: '.$plugin_class->getTitle().'">'.$homepage.'</a>';
	               }
	            }
               if ( $current_context_item->isPluginOn($plugin) ) {
                  $array_plugins[$plugin_class->getIdentifier()]['on'] = 'yes';
               } else {
                  $array_plugins[$plugin_class->getIdentifier()]['on'] = 'no';
               }
	         }
	      }
	   }
	   if ( !empty($array_plugins) ) {
	      ksort($array_plugins);
	      $return['plugins'] = true;
	      $return['plugins_array'] = $array_plugins;
	   }
	   // plugins
	   
	   return $return;
	}

	private function getRoomInformation() {
		$return = array();

		$current_context = $this->_environment->getCurrentContextItem();
		$translator = $this->_environment->getTranslationObject();

		$return['room_name'] = htmlspecialchars($current_context->getTitle() , ENT_QUOTES, 'UTF-8');
		$return['room_show_name'] = $current_context->showTitle();

		// language
		$languages = array();

		$languages[] = array(
			'text'		=> $translator->getMessage('CONTEXT_LANGUAGE_USER'),
			'value'		=> 'user'
		);

		$languages[] = array(
			'text'		=> '-------',
			'value'		=> 'disabled',
			'disabled'	=> true
		);

		$language_array = $this->_environment->getAvailableLanguageArray();
		foreach($language_array as $entry) {
			switch ( mb_strtoupper($entry, 'UTF-8') ){
				case 'DE':
					$languages[] = array(
						'text'		=> $translator->getMessage('DE'),
						'value'		=> $entry
					);
					break;
				case 'EN':
					$languages[] = array(
						'text'		=> $translator->getMessage('EN'),
						'value'		=> $entry
					);
					break;
				default:
					break;
			}
		}
		$return['languages'] = $languages;
		$return['language'] = $current_context->getLanguage();

		// logo
		if($current_context->getLogoFilename()) {
			$return['logo'] = $current_context->getLogoFilename();
		}

		// time pulses
		if(!empty($this->_time_array)) {
			$return['time_array'] = $this->_time_array;
		}

		// project / community room
		$return['in_project_room'] = $this->_environment->inProjectRoom();
		$return['in_community_room'] = $this->_environment->inCommunityRoom();

		// assignment
		$assignments = array();
		if($this->_environment->inProjectRoom()) {
			// project room
			if(!empty($this->_community_room_array)) {
				$portal_item = $this->_environment->getCurrentPortalItem();
				$project_room_link_status = $portal_item->getProjectRoomLinkStatus();
				$return['link_status'] = $project_room_link_status;

				if(!empty($this->_shown_community_room_array)) $return['assigned_community_room_array'] = $this->_shown_community_room_array;
				if(count($this->_community_room_array) > 2) $return['community_room_array'] = $this->_community_room_array;
			}
		} else {
			if($current_context->isAssignmentOnlyOpenForRoomMembers()) $return['assignment'] = 'closed';
			else $return['assignment'] = 'open';
		}

		// colors
		$return['color_array'] = $this->_color_array;

		$color = $current_context->getColorArray();
		$return['color_schema'] = $color['schema'];
		$return['color_active_menu'] = $color['color_active_menu'];
		$return['color_menu'] = $color['color_menu'];
		$return['color_right_column'] = $color['color_right_column'];
		$return['color_content_bg'] = $color['color_content_bg'];
		$return['color_link'] = $color['color_link'];
		$return['color_link_hover'] = $color['color_link_hover'];
		$return['color_action_bg'] = $color['color_action_bg'];
		$return['color_action_icon'] = $color['color_action_icon'];
		$return['color_action_icon_hover'] = $color['color_action_icon_hover'];
		$return['color_bg'] = $color['color_bg'];
		$return['color_bg_image'] = $current_context->getBGImageFilename();
		$return['color_bg_image_repeat'] = $current_context->issetBGImageRepeat();
		$return['color_bg_image_fixed'] = $current_context->issetBGImageFixed();


		// description
		$return['description'] = $current_context->getDescription();

		//rubric choice
		$home_conf = $current_context->getHomeConf();
		$home_conf_array = explode(',',$home_conf);
		$rubric_configuration_array = array();
		$i=0;
		$count =8;
		if ($this->_environment->inCommunityRoom()){
			$count =7;
		}
		foreach ($home_conf_array as $rubric_conf) {
			$rubric_conf_array = explode('_',$rubric_conf);
			if ($rubric_conf_array[1] != 'none') {
				$temp_array = array();
				$temp_array['key'] = 'rubric_'.$i;
				$temp_array['value'] = $rubric_conf_array[0];
				$temp_array['show'] = $rubric_conf_array[1];
				$i++;
				$rubric_configuration_array[] = $temp_array;
			}
		}
		for ($j=$i; $j<$count; $j++) {
			$temp_array = array();
			$temp_array['key'] = 'rubric_'.$j;
			$temp_array['value'] = 'none';
			$temp_array['show'] = 'nodisplay';
			$rubric_configuration_array[] = $temp_array;
		}
	#	pr($rubric_configuration_array);

		$first = true;
		$second = false;
		$third = false;
		$count = 8;
		$nameArray = array();
		if ( $this->_environment->inCommunityRoom()
				or $this->_environment->inGroupRoom()
		) {
			$count = 7;
		}
		for ( $i = 0; $i < $count; $i++ ) {
			$desc = '';
			if ($first) {
				$first = false;
				$desc = $translator->getMessage('INTERNAL_MODULE_CONF_DESC_SHORT',$translator->getMessage('MODULE_CONFIG_SHORT'));
				$second = true;
			} elseif ($second) {
				$second = false;
				$desc = $translator->getMessage('INTERNAL_MODULE_CONF_DESC_TINY',$translator->getMessage('MODULE_CONFIG_TINY'));
				$third = true;
			} elseif ($third) {
				$third = false;
				$desc = $translator->getMessage('INTERNAL_MODULE_CONF_DESC_NONE',$translator->getMessage('MODULE_CONFIG_NONE'));
			}
			$nameArray[] = $desc;
		}


		$return['rubric_array'] = $this->_rubric_array;
		$return['rubric_conf_array'] = $rubric_configuration_array;
		$return['rubric_display_array'] = $nameArray;

         if ($current_context->checkNewMembersNever()) {
            $return['member_check'] = 'never';
         } elseif ($current_context->checkNewMembersAlways()) {
            $return['member_check'] = 'always';
         } elseif ($current_context->checkNewMembersSometimes()) {
            $return['member_check'] = 'sometimes';
         } elseif ($current_context->checkNewMembersWithCode()) {
            $return['member_check'] = 'withcode';
         }

         $code = $current_context->getCheckNewMemberCode();
         if ( !empty($code) ) {
            $return['code'] = $code;
         }

         if ($current_context->isOpenForGuests()) {
            $return['open_for_guests'] = 'open';
         } else {
            $return['open_for_guests'] = 'closed';
         }
         
         if($current_context->isMaterialOpenForGuests()){
         	$return['material_guests'] = 'open';
         } else {
         	$return['material_guests'] = 'closed';
         }


		return $return;
	}
}
