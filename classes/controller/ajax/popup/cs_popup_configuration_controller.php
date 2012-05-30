<?php
class cs_popup_configuration_controller {
	private $_environment = null;
	private $_popup_controller = null;
	private $_config = array();
	private $_data = array();
	private $_time_array = array();
	private $_community_room_array = array();
	
	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}
	
	public function save($form_data, $additional) {
	}
	
	public function initPopup() {
		$current_context = $this->_environment->getCurrentContextItem();
		$current_portal = $this->_environment->getCurrentPortalItem();
		$current_user = $this->_environment->getCurrentUser();
		$translator = $this->_environment->getTranslationObject();
		
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
								'disabled'	=> true
							);
						}
					} else {
						$community_room_array[] = array(
							'text'		=> $community_item->getTitle(),
							'value'		=> $community_item->getItemID(),
							'disabled'	=> true
						);
					}
					
					$community_item = $community_list->getNext();
				}
			}
			
			$this->_community_room_array = $community_room_array;
			
			$shown_community_room_array = array();
			//if()
			/*
			
			$community_room_array = array();
			
			if (!empty($this->_session_community_room_array)) {
				foreach ( $this->_session_community_room_array as $community_room ) {
				$temp_array['text'] = $community_room['name'];
				$temp_array['value'] = $community_room['id'];
				$community_room_array[] = $temp_array;
				}
				} else{
				$community_room_list = $current_context_item->getCommunityList();
				if ($community_room_list->getCount() > 0) {
				$community_room_item = $community_room_list->getFirst();
				while ($community_room_item) {
				$temp_array['text'] = $community_room_item->getTitle();
				$temp_array['value'] = $community_room_item->getItemID();
				$community_room_array[] = $temp_array;
				$community_room_item = $community_room_list->getNext();
				}
				}
			}
			$this->_shown_community_room_array = $community_room_array;
			
			*/
		}
		
		


      


      

      /*******Farben********/ /*
      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_DEFAULT');
      $temp_array['value'] = 'COMMON_COLOR_DEFAULT';
      $this->_array_info_text[] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = '-----';
      $temp_array['value'] = '-1';
      $this->_array_info_text[] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_1');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_1';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_1')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_2');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_2';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_2')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_3');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_3';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_3')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_4');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_4';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_4')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_5');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_5';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_5')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_6');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_6';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_6')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_7');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_7';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_7')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_8');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_8';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_8')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_9');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_9';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_9')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_10');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_10';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_10')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_11');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_11';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_11')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_12');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_12';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_12')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_13');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_13';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_13')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_14');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_14';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_14')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_15');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_15';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_15')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_16');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_16';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_16')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_17');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_17';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_17')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_18');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_18';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_18')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_19');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_19';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_19')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_20');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_20';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_20')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_21');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_21';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_21')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_22');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_22';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_22')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_23');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_23';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_23')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_24');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_24';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_24')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_25');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_25';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_25')] = $temp_array;
      
      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_26');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_26';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_26')] = $temp_array;

      ksort($array_info_text_temp);
      foreach($array_info_text_temp as $entry){
         $this->_array_info_text[] = $entry;
      }
      $temp_array = array();
      $temp_array['text']  = '-----';
      $temp_array['value'] = '-1';
      $this->_array_info_text[] = $temp_array;
      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_OWN');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_OWN';
      $this->_array_info_text[] = $temp_array;
		 */
		
		
		
		
		
		
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
				
				if($project_room_link_status == 'optional') {
					
				} else {
					
				}
			}
			
			
			
			/*
			 * if ( !empty($this->_community_room_array) ) {
            $portal_item = $this->_environment->getCurrentPortalItem();
            $project_room_link_status = $portal_item->getProjectRoomLinkStatus();
            if ($project_room_link_status =='optional'){
               if ( !empty ($this->_shown_community_room_array) ) {
                  $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
                  $this->_form->combine();
               }
               if(count($this->_community_room_array) > 2){
                  $this->_form->addSelect('communityrooms',$this->_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,false,false,'','','','',16);
                  $this->_form->combine('horizontal');
                  $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',100);
               }
            }else{
               if ( !empty ($this->_shown_community_room_array) ) {
                  $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
                  $this->_form->combine();
               }
               if(count($this->_community_room_array) > 2){
                  $this->_form->addSelect('communityrooms',$this->_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,true,false,'','','','',16);
                  $this->_form->combine('horizontal');
                  $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',100);
               }
            }
         }
			 */
		}
		
		
		/**********Logo**********/ /*
		$this->_with_bg_image = $current_context_item->getBGImageFilename();
		
		/*
		 * $context_item = $this->_environment->getCurrentContextItem();

      $this->_values = array();
      $color = $context_item->getColorArray();
      $temp_array = array();
      $temp_array['color_1'] = $color['tabs_background'];
      $temp_array['color_2'] = $color['tabs_focus'];
      $temp_array['color_3'] = $color['tabs_title'];
      $temp_array['color_31'] = $color['tabs_separators'];
      $temp_array['color_32'] = $color['tabs_dash'];
      $temp_array['color_4'] = $color['content_background'];
      $temp_array['color_5'] = $color['boxes_background'];
      $temp_array['color_6'] = $color['hyperlink'];
      $temp_array['color_7'] = $color['list_entry_even'];
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if (empty($this->_values['color_choice'])){
            $this->_values['color_choice'] = 'COMMON_COLOR_'.mb_strtoupper($color['schema'], 'UTF-8');
         }
         if ($this->_values['color_choice']=='COMMON_COLOR_SCHEMA_OWN'){
            for ($i=1; $i<8; $i++){
               if ( !empty($this->_form_post['color_'.$i]) ){
                  $this->_values['color_'.$i] = $this->_form_post['color_'.$i];
               }else{
                  $this->_values['color_'.$i] = $temp_array['color_'.$i];
               }
            }
            if(!empty($this->_form_post['color_31'])) {
               $this->_values['color_31'] = $ths->_form_post['color_31'];
            } else {
               $this->_values['color_31'] = $temp_array['color_31'];
            }
            if(!empty($this->_form_post['color_32'])) {
               $this->_values['color_32'] = $ths->_form_post['color_32'];
            } else {
               $this->_values['color_32'] = $temp_array['color_32'];
            }
         }
      } else {
         $color_array = $context_item->getColorArray();
         $this->_values['color_choice'] = 'COMMON_COLOR_'.mb_strtoupper($color['schema'], 'UTF-8');
         $this->_values['color_1'] = $color['tabs_background'];
         $this->_values['color_2'] = $color['tabs_focus'];
         $this->_values['color_3'] = $color['tabs_title'];
         $this->_values['color_31'] = $color['tabs_separators'];
         $this->_values['color_32'] = $color['tabs_dash'];
         $this->_values['color_5'] = $color['boxes_background'];
         $this->_values['color_7'] = $color['list_entry_even'];
         $this->_values['color_6'] = $color['hyperlink'];
         $this->_values['color_4'] = $color['content_background'];
         if ( $context_item->isPrivateRoom() ) {
            if ( $context_item->getTitle() == 'PRIVATEROOM' ) {
               $this->_values['title'] = $this->_translator->getMessage('COMMON_PRIVATEROOM');
            } elseif ( $context_item->isTemplate() ) {
               $this->_values['title'] = $context_item->getTitlePure();
            }
         }
         if ($context_item->isAssignmentOnlyOpenForRoomMembers()) {
            $this->_values['room_assignment'] = 'closed';
         } else {
            $this->_values['room_assignment'] = 'open';
         }
      }
      
      if ($context_item->isRSSOn()) {
         $this->_values['rss'] = 'yes';
      } else {
         $this->_values['rss'] = 'no';
      }
      if ($context_item->getBGImageFilename()){
         $this->_values['bgimage'] = $context_item->getBGImageFilename();
      }
      if ($context_item->issetBGImageRepeat()){
         $this->_values['bg_image_repeat'] = '1';
      }

      

      if ($this->_environment->inProjectRoom()){
         $community_room_array = array();
         if (!empty($this->_session_community_room_array)) {
            foreach ( $this->_session_community_room_array as $community_room ) {
               $community_room_array[] = $community_room['id'];
            }
         }
         $community_room_list = $context_item->getCommunityList();
         if ($community_room_list->getCount() > 0) {
            $community_room_item = $community_room_list->getFirst();
            while ($community_room_item) {
               $community_room_array[] = $community_room_item->getItemID();
               $community_room_item = $community_room_list->getNext();
            }
         }
         if ( isset($this->_form_post['communityroomlist']) ) {
            $this->_values['communityroomlist'] = $this->_form_post['communityroomlist'];
         } else {
            $this->_values['communityroomlist'] = $community_room_array;
         }
      }

      $this->_values['description'] = $context_item->getDescription();
      
      global $c_email_upload;
      if ($c_email_upload && $this->_environment->inPrivateRoom()) {
         if ( isset($this->_form_post['email_to_commsy']) ) {
            $this->_values['email_to_commsy'] = $this->_form_post['email_to_commsy'];
         } else {
            $this->_values['email_to_commsy'] = $context_item->getEmailToCommSy();
         }
         
         if ( isset($this->_form_post['email_to_commsy_secret']) ) {
            $this->_values['email_to_commsy_secret'] = $this->_form_post['email_to_commsy_secret'];
         } else {
            $this->_values['email_to_commsy_secret'] = $context_item->getEmailToCommSySecret();
         }
      }
		 */
		
		return $return;
	}
}