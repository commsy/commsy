<?php
class cs_popup_configuration_controller {
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

	public function save($form_data, $additional) {
		$current_context = $this->_environment->getCurrentContextItem();
		$current_user = $this->_environment->getCurrentUserItem();

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
		elseif(!$current_context->isOpen() && !$current_context->isTemplate()) {
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
		}

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
			$tab = $additional['tab'];

			switch($tab) {
				/**** ROOM CONFIGURATION ****/
				case "room_configuration":
					if($this->_popup_controller->checkFormData('room_configuration')) {
						// title
						if(isset($form_data['room_name'])) $current_context->setTitle($form_data['room_name']);

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

							$current_context->setCommunityListByID($community_room_array);
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
							$schema['schema'] = mb_substr($form_data['color_choice'], 13);

							if($form_data['color_choice'] === 'COMMON_COLOR_SCHEMA_OWN') {
								$schema['schema'] = 'SCHEMA_OWN';

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

								// create individual css for room context
								$this->_popup_controller->getUtils()->createOwnCSSForRoomContext($current_context, $schema);
							}

							// store scheme
							$current_context->setColorArray($schema);
						}

						// description
						if(isset($form_data['description'])) $current_context->setDescription($form_data['description']);
						else $current_context->setDescription('');

						// rss
						// TODO: move
						if(isset($form_data['rss'])) {
							if($form_data['rss'] === 'yes') $current_context->turnRSSOn();
							elseif($form_data['rss'] === 'no') $current_context->turnRSSOff();
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

						// save
						$current_context->save();

						// genereate layout images
						$current_context->generateLayoutImages();

						// set return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_context->getItemID());
					}

					break;

				/**** ROOM PICTURE ****/
				case 'room_picture':
					if($this->_popup_controller->checkFormData('room_picture')) {
						/* handle room picture upload */
						if(!empty($_FILES['form_data']['name'])) {
							$logo = $current_context->getLogoFilename();
							$disc_manager = $this->_environment->getDiscManager();

							// delete old if set
							if(!empty($logo)) {
								if($disc_manager->existsFile($current_context->getLogoFilename())) {
									$disc_manager->unlinkFile($current_context->getLogoFilename());
								}

								$current_context->setLogoFilename('');
							}

							$filename = 'cid' . $this->_environment->getCurrentContextID() . '_logo_' . $_FILES['form_data']['name'];
							$disc_manager->copyFile($_FILES['form_data']['tmp_name'], $filename, true);
							$current_context->setLogoFilename($filename);

							// save
							$current_context->save();
						}

						// set return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_context->getItemID());
					}
					break;

				/**** ROOM BG IMAGE ****/
				case 'room_background':
					if($this->_popup_controller->checkFormData('room_background')) {
						/* handle room picture upload */
						if(!empty($_FILES['form_data']['name'])) {
							$bg_image = $current_context->getBGImageFilename();
							$disc_manager = $this->_environment->getDiscManager();

							// delete old if set
							if(!empty($bg_image)) {
								if($disc_manager->existsFile($current_context->getBGImageFilename())) {
									$disc_manager->unlinkFile($current_context->getBGImageFilename());
								}

								$current_context->setBGImageFilename('');
							}

							$filename = 'cid' . $this->_environment->getCurrentContextID() . '_bgimage_' . $_FILES['form_data']['name'];
							$disc_manager->copyFile($_FILES['form_data']['tmp_name'], $filename, true);
							$current_context->setBGImageFilename($filename);

							// save
							$current_context->save();
						}

						// set return
						$this->_popup_controller->setSuccessfullItemIDReturn($current_context->getItemID());
					}
					break;
			}
		}
	}

	public function initPopup() {
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
						'checked'	=> true
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

		// color schemes
		$this->_color_array[] = array(
			'text'		=> $translator->getMessage('COMMON_COLOR_DEFAULT'),
			'value'		=> 'COMMON_COLOR_DEFAULT',
			'disabled'	=> false
		);
		$this->_color_array[] = array(
			'text'		=> '-----',
			'value'		=> '-1',
			'disabled'	=> true
		);

		$temp_color_array = array();
		for($i=1; $i <= 26; $i++) {
			$translation = $translator->getMessage('COMMON_COLOR_SCHEMA_' . $i);

			$temp_color_array[$translation] = array(
				'text'		=> $translation,
				'value'		=> 'COMMON_COLOR_SCHEMA_' . $i,
				'disabled'	=> false
			);
		}

		ksort($temp_color_array);
		$this->_color_array = array_merge($this->_color_array, $temp_color_array);

		$this->_color_array[] = array(
			'text'		=> '-----',
			'value'		=> '-1',
			'disabled'	=> true
		);
		$this->_color_array[] = array(
			'text'		=> $translator->getMessage('COMMON_COLOR_SCHEMA_OWN'),
			'value'		=> 'COMMON_COLOR_SCHEMA_OWN',
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
			),
			'user'			=> array(
				array('name' => 'title','type' => 'text', 'mandatory' => false), array('name' => 'title_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'birthday','type' => 'text', 'mandatory' => false), array('name' => 'birthday_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'picture','type' => 'file', 'mandatory' => false), array('name' => 'picture_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'mail','type' => 'mail', 'mandatory' => true), array('name' => 'mail_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'telephone','type' => 'text', 'mandatory' => false), array('name' => 'telephone_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'cellularphone','type' => 'text', 'mandatory' => false), array('name' => 'cellularphone_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'street','type' => 'text', 'mandatory' => false), array('name' => 'street_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'zipcode','type' => 'numeric', 'mandatory' => false), array('name' => 'zipcode_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'city','type' => 'text', 'mandatory' => false), array('name' => 'city_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'room','type' => 'text', 'mandatory' => false), array('name' => 'room_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'organisation','type' => 'text', 'mandatory' => false), array('name' => 'organisation_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'position','type' => 'text', 'mandatory' => false), array('name' => 'position_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'icq','type' => 'numeric', 'mandatory' => false),
				array('name' => 'msn','type' => 'text', 'mandatory' => false),
				array('name' => 'skype','type' => 'text', 'mandatory' => false),
				array('name' => 'yahoo','type' => 'text', 'mandatory' => false),
				array('name' => 'jabber','type' => 'text', 'mandatory' => false), array('name' => 'messenger_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'homepage','type' => 'text', 'mandatory' => false), array('name' => 'homepage_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'description','type' => 'text', 'mandatory' => false), array('name' => 'description_all','type' => 'checkbox', 'mandatory' => false),
			),
			'user_picture'	=> array(
			),
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
	}

	private function getRoomInformation() {
		$return = array();

		$current_context = $this->_environment->getCurrentContextItem();
		$translator = $this->_environment->getTranslationObject();

		$return['room_name'] = $current_context->getTitle();
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
		$return['color_schema'] = 'COMMON_COLOR_' . mb_strtoupper($color['schema'], 'UTF-8');
		$return['color_active_menu'] = $color['active_menu'];
		$return['color_menu'] = $color['menu'];
		$return['color_right_column'] = $color['right_column'];
		$return['color_content_bg'] = $color['content_bg'];
		$return['color_link'] = $color['link'];
		$return['color_link_hover'] = $color['link_hover'];
		$return['color_action_bg'] = $color['action_bg'];
		$return['color_action_icon'] = $color['action_icon'];
		$return['color_action_icon_hover'] = $color['action_icon_hover'];
		$return['color_bg'] = $color['bg'];
		$return['color_bg_image'] = $current_context->getBGImageFilename();
		$return['color_bg_image_repeat'] = $current_context->issetBGImageRepeat();

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
			$temp_array['show'] = 'none';
			$rubric_configuration_array[] = $temp_array;
		}

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

		// rss
		if($current_context->isRSSOn()) {
			$return['rss'] = 'yes';
		} else {
			$return['rss'] = 'no';
		}

		return $return;
	}
}