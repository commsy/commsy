<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_user_detail_controller extends cs_detail_controller {
		private $_display_mod = null;
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'user_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_USER_TYPE);
		}
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionDetail() {
			$session = $this->_environment->getSessionItem();
			
			// try to set the item
			$this->setItem();
			
			$this->setupInformation();
			
			// check for item type
			$item_manager = $this->_environment->getItemManager();
			$type = $item_manager->getItemType($_GET['iid']);
			if($type !== CS_USER_TYPE) {
				// TODO: implement error handling
				/*
				 * $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ERROR_ILLEGAL_IID'));
   $page->add($errorbox);
				 */
			} else {
				// TODO: check if unused
				// used to signal which "creator infos" of annotations are expanded...
				$creatorInfoStatus = array();
				if(!empty($_GET['creator_info_max'])) {
					$creatorInfoStatus = explode('-', $_GET['creator_info_max']);
				}
				
				// init
				$user_manager = $this->_environment->getUserManager();
				$current_user = $this->_environment->getCurrentUser();
				$current_module = $this->_environment->getCurrentModule();
				
				// check if item exists
				if($this->_item === null) {
					include_once('functions/error_functions.php');
      				trigger_error('Item ' . $_GET['iid'] . ' does not exist!', E_USER_ERROR);
				}
				
				// check if item is deleted
				elseif($this->_item->isDeleted()) {
					// TODO: implement error handling
					/*
					 * $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
      $page->add($errorbox);
					 */
				}
				
				// check for access rights
				elseif(!$this->_item->maySee($current_user)) {
					// TODO: implement error handling
					/*
					 * $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
					 */
				}
				
				// check for takeover
				elseif(($current_user->isRoot() || $current_user->isModerator()) && $this->_environment->inPortal() && isset($_GET['mode']) && $_GET['mode'] === 'take_over') {
					$history = $session->getValue('history');
					
					$cookie = $session->getValue('cookie');
					$javascript = $session->getValue('javascript');
					$https = $session->getValue('https');
					$flash = $session->getValue('flash');
					
					$session_id = $session->getSessionID();
					$session = new cs_session_item();
					$session->createSessionID($user_item->getUserID());
					$session->setValue('auth_source', $user_item->getAuthSource());
					$session->setValue('root_session_id', $session_id);
					
					// TODO:	checking strings, but setting integers???
					// 			improve to type-secure checks
					
					// set cookie in session, if cookie is empty, do nothing, commsy will try to save it 
					if($cookie == '1') {
						$session->setValue('cookie', 2);
					} elseif(!empty($cookie)) {
						$session->setValue('cookie', 0);
					}
					
					if($javascript == '1') {
						$session->setValue('javascript', 1);
					} elseif($javascript == '-1') {
						$session->setValue('javascript', -1);
					}
					
					if($https == '1') {
						$session->setValue('https', 1);
					} elseif($https == '-1') {
						$session->setValue('https', -1);
					}
					
					if($flash == '1') {
						$session->setValue('flash', 1);
					} elseif($flash == '-1') {
						$session->setValue('flash', -1);
					}
					
					// save portal id in session to ensure, that user didn't switch between portals
					if($this->_environment->inServer()) {
						$session->setValue('commsy_id', $this->_environment->getServerID());
					} else {
						$session->setValue('commsy_id', $this->_environment->getCurrentPortalID());
					}
					
					$this->_environment->setSessionItem($session);
					redirect($this->_environment->getCurrentContextID(), 'home', 'index', array());
					
				} else {
					// mark as read and noticed
					$this->markRead();
					$this->markNoticed();
					
					$current_context = $this->_environment->getCurrentContextItem();
					
					// create view
					/*
					 * $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $current_context->isOpen();
      $params['creator_info_status'] = $creatorInfoStatus;
      $detail_view = $class_factory->getClass(USER_DETAIL_VIEW,$params);
      unset($params);
					 */
					
					// TODO: implement
					/*
			      if ( $user_item->getItemID() == $current_user->getItemID()
			           or ( isset($display_mod) and $display_mod == 'admin' and $current_user->isModerator() )
			         ) {
			         if (!$environment->inPrivateRoom()){
			            $detail_view->setSubItem($user_item);
			         }
			      }
					 */
					
					// TODO: check this, should be handled by parent class
					/*
					 *  // Set up browsing order
				      if ( !isset($_GET['single'])
				           and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$current_module.'_index_ids')) {
				         $user_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$current_module.'_index_ids');
				      } else {
				         $user_ids = array();
				      }
				      $detail_view->setBrowseIDs($user_ids);
				      if ( isset($_GET['pos']) ) {
				         $detail_view->setPosition($_GET['pos']);
				      }
					 */
					
					// set up rubric connections and browsing
					if($this->_environment->getCurrentModule() !== 'account' && ($current_context->isProjectRoom() || $current_context->isCommunityRoom())) {
						$current_room_modules = $current_context->getHomeConf();
						
						$room_modules = array();
						if(!empty($current_room_modules)) {
							$room_modules = explode(',', $current_room_modules);
						}
						
						$first = array();
						$second = array();
						foreach($room_modules as $module) {
							list($module_name, $display_mode) = explode('_', $module);
							
							if($display_mode !== 'none' && $module_name !== CS_USER_TYPE && $module_name !== $this->_environment->getCurrentModule()) {
								// TODO:
								/*
								 * switch ($detail_view->_is_perspective($link_name[0])) {
					               case true:
					                  $first[] = $link_name[0];
					               break;
					               case false:
					                  $second[] = $link_name[0];
					               break;
					            }
								 */
							}
						}
						
						$room_modules = $first;
						$rubric_connections = array();
						
						foreach($room_modules as $module) {
							if($current_context->withRubric($module)) {
								$ids = $this->_item->getLinkedItemIDArray($module);
								$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_' . $module . '_index_ids', $ids);
								
								if($module !== CS_TOPIC_TYPE && $module !== CS_INSTITUTION_TYPE && $module !== CS_GROUP_TYPE) {
									$ids = $this->_item->getModifiedItemIDArray($module, $this->_item->getItemID());
									// TODO: implement
									//$detail_view->addModifiedItemIDArray($module,$ids);
								}
								
								$rubric_connections[] = $module;
							}
						}
						
						$room_modules = $second;
						
						foreach($room_modules as $module) {
							if($current_context->withRubric($module)) {
								if($this->_environment->inPortal()) {
									$ids = array();
									if($module === CS_PROJECT_TYPE) {
										$room_list = $this->_item->getRelatedProjectList();
									} elseif($module === CS_COMMUNITY_TYPE) {
										$room_list = $this->_item->getRelatedCommunityList();
									}
									
									if($room_list->isNotEmpty()) {
										$room_item = $room_list->getFirst();
										
										while($room_item) {
											if($room_item->isOpen()) {
												$ids[] = $room_item->getItemID();
											}
											
											$room_item = $room_list->getNext();
										}
									}
								} else {
									if($module === CS_GROUP_TYPE || $module === CS_INSTITUTION_TYPE || $module === CS_TOPIC_TYPE) {
										$ids = $this->_item->getLinkedItemIDArray($module);
										$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_' . $module . '_index_ids', $ids);
									} else {
										$ids = $this->_item->getModifiedItemIDArray($module, $this->_item->getItemID());
									}
								}
								
								// TODO: implement
								//$detail_view->addModifiedItemIDArray($module,$ids);
							}
						}
						
						// TODO: implement
						//$detail_view->setRubricConnections($rubric_connections);
					}
					
					
					/*
					 * TODO


      // highlight search words in detail views
      $session_item = $environment->getSessionItem();
      if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
         $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
         if ( !empty($search_array['search']) ) {
            $detail_view->setSearchText($search_array['search']);
         }
         unset($search_array);
      }

      if ( $environment->inPortal() or $environment->inServer() ){
         $page->addForm($detail_view);
      }else{
         $page->add($detail_view);
      }
					 */
					
					$this->assign('detail', 'content', $this->getDetailContent());
				}
			}
		}
		
		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/
		
		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();
			
			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_user_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_user_index_ids'));
			}
		}
		
		protected function getDetailContent() {
			$converter = $this->_environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();
			
			$return = array();
			
			################
			## FIRST BLOCK
			#
			
			// title
			$title = $this->_item->getFullname();
			if(!empty($title)) {
				//$temp_array = array();
				//$temp_array[] = $translator->getMessage('USER_TITLE');
				// TODO:
				// $title = compareWithSearchText($title);
				//$temp_array[] = $converter->text_as_html_short($title);
				//$formal_data[] = $temp_array;
				$return['first_block']['fullname'] = $converter->text_as_html_short($title);
			}
			
			// birthday
			$birthday = $this->_item->getBirthday();
			if(!empty($birthday)) {
				//$temp_array = array();
				//$temp_array[] = $translator->getMessage('USER_BIRTHDAY');
				// TODO:
				//$birthday = $converter->compareWithSearchText($birthday);
				//$temp_array[] = $converter->text_as_html_short($birthday);
				//$formal_data[] = $temp_array;
				$return['first_block']['birthday'] = $converter->text_as_html_short($birthday);
			}
			
			#
			##
			################
			################
			## SECOND BLOCK
			#
			
			// email
			$email = $this->_item->getEmail();
			// TODO:
			//$email = $converter->compareWithSearchText...
			$email = $converter->text_as_html_short($email);
			
			$return['hidden']['email'] = false;
			if(!empty($email) && ($this->_item->isEmailVisible() || $this->_display_mod === 'admin')) {
				$return['second_block']['email'] = $email;
			} elseif(!$this->_item->isEmailVisible()) {
				$return['hidden']['email'] = true;
			}
			
			// telephone
			$telephone = $this->_item->getTelephone();
			if(!empty($telephone)) {
				//$temp_array[] = $this->_translator->getMessage('USER_TELEPHONE');
				
				// TODO:
				//$telephone = $converter->compareWithSearchText($telephone);
				$telephone = $converter->text_as_html_short($telephone);
				$return['second_block']['telephone'] = $telephone;
			}
			
			$cellularphone = $this->_item->getCellularphone();
			if(!empty($cellularphone)) {
				//$temp_array[] = $this->_translator->getMessage('USER_CELLULARPHONE');
				// TODO:
				// $cellularphone = $converter->compareWithSearchText($cellularphone);
				$cellularphone = $converter->text_as_html_short($cellularphone);
				$return['second_block']['cellularphone'] = $cellularphone;
			}
			
			#
			##
			################
			################
			## THIRD BLOCK
			#
			
			// street
			$street = $this->_item->getStreet();
			if(!empty($street)) {
				// TODO:
				// $street = $converter->compareWithSearchText($street);
				$street = $converter->text_as_html_short($street);
				$return['third_block']['street'] = $street;
			}
			
			// city
			$city = $this->_item->getCity();
			if(!empty($city)) {
				$zipcode = $this->_item->getZipCode();
				//TODO:
				//$city = $converter->compareWithSearchText($zipcode) . ' ' . $converter->compareWithSearchText($city);
				$city = $zipcode . ' ' . $city;
				$city = $converter->text_as_html_short(trim($city));
				$return['third_block']['city'] = $city;
			}
			
			// room
			$room = $this->_item->getRoom();
			if(!empty($room)) {
				//TODO:
				//$room = $converter->compareWithSearchText($room);
				$room = $converter->text_as_html_short($room);
				$return['third_block']['room'] = $room;
			}
			
			#
			##
			################
			################
			## FOURTH BLOCK
			#
			
			// organisation
			$organisation = $this->_item->getOrganisation();
			if(!empty($organisation)) {
				//TODO:
				//$organisation = $converter->compareWithSearchText($organisation);
				$organisation = $converter->text_as_html_short($organisation);
				$return['fourth_block']['organisation'] = $organisation;
			}
			
			// position
			$position = $this->_item->getPosition();
			if(!empty($position)) {
				//TODO:
				//$position = $converter->compareWithSearchText($position);
				$position = $converter->text_as_html_short($position);
				$return['fourth_block']['position'] = $position;
			}
			
			#
			##
			################
			
			// picture
			$picture = $this->_item->getPicture();
			if(!empty($picture)) {
				$disc_manager = $this->_environment->getDiscManager();
				$width = 150;
				if($disc_manager->existsFile($picture)) {
					$image_array = getimagesize($disc_manager->getFilePath() . $picture);
					$pict_width = $image_array[0];
					if($pict_width < 150) {
						$width = $pict_width;
					}
				}
				
				$return['picture']['src'] = $picture;
			}
			
			################
			## messenger block
			#
			
			$icq = $this->_item->getICQ();
			$jabber = $this->_item->getJabber();
			$msn = $this->_item->getMSN();
			$skype = $this->_item->getSkype();
			$yahoo = $this->_item->getYahoo();
			
			if(!empty($icq) || !empty($jabber) || !empty($msn) || !empty($skype) || !empty($yahoo)) {
				global $c_commsy_domain;
				$host = $c_commsy_domain;
				if(strstr($c_commsy_domain, 'http')) {
					$host = mb_substr($c_commsy_domain, mb_strrpos($c_commsy_domain, '/') + 1);
				}
				
				global $c_commsy_url_path;
				$url_to_img = $host . $c_commsy_url_path . '/img/messenger';
				$url_to_service = 'http://osi.danvic.co.uk';
				
				//$temp_array[] = $this->_translator->getMessage('USER_MESSENGER_NUMBERS');
				
				// icq
				if(!empty($icq)) {
					//TODO:
					//$html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="http://status.icq.com/online.gif?icq='.rawurlencode($icq_number).'&amp;img=2" alt="ICQ Online Status Indicator" />'.LF;
					//$icq = $converter->compareWithSearchText($icq);
					$icq = $converter->text_as_html_short($icq);
					$return['messenger_block']['icq'] = $icq;
				}
				
				// msn
				if(!empty($msn)) {
					//TODO:
					//$html_text .= '<a href="http://www.IMStatusCheck.com/?msn">'.LF;
            		//$html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="http://www.IMStatusCheck.com/status/msn/'.rawurlencode($msn_number).'?icons" alt="MSN Online Status Indicator" />'.LF;
            		//$html_text .= '</a>'.LF;
            		//$msn = $converter->compareWithSearchText($msn);
            		$msn = $converter->text_as_html_short($msn);
            		$return['messenger_block']['msn'] = $msn;
				}
				
				// skype
				if(!empty($skype)) {
					//TODO:
					//$html_text .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
		            //$html_text .= '<a href="skype:'.rawurlencode($skype_number).'?chat">'.LF;
		            //$html_text .= '   <img style="vertical-align:middle; margin-bottom:5px;" src="http://mystatus.skype.com/smallclassic/'.rawurlencode($skype_number).'" alt="Skype Online Status Indicator" />'.LF;
		            //$html_text .= '</a>'.LF;
		            //$skype = $comverter->compareWithSearchText($skype);
		            $skype = $converter->text_as_html_short($skype);
		            $return['messenger_block']['skype'] = $skype;
				}
				
				// yahoo
				if(!empty($yahoo)) {
					//TODO:
					//$html_text .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
		            //$html_text .= '<a href="http://messenger.yahoo.com/edit/send/?.target='.rawurlencode($yahoo_number).'">'.LF;
		            //$html_text .= '   <img style="vertical-align:middle;" src="http://opi.yahoo.com/yahooonline/u='.rawurlencode($yahoo_number).'/m=g/t=1/l='.$this->_environment->getSelectedLanguage().'/opi.jpg" alt="Yahoo Online Status Indicator" />'.LF;
		            //$html_text .= '</a>'.LF;
		            //$yahoo = $converter->compareWithSearchText($converter);
		            $yahoo = $converter->text_as_html_short($yahoo);
		            $return['messenger_block']['yahoo'] = $yahoo;
				}
			}
			
			#
			##
			################
			
			$homepage = $this->_item->getHomepage();
			$homepage = $converter->text_as_html_short($homepage);
			// TODO: in template
			//$homepage_short = chunkText($homepage,60);
			if(!empty($homepage)) {
				if(strstr($homepage, '?')) {
					list($first_part, $second_part) = explode('?', $homepage);
					$homepage = $first_part . '?';
					
					if(strstr($second_part, '&')) {
						$param_array = explode('&', $second_part);
						foreach($param_array as $key => $value) {
							$value = str_replce('=', 'EQUAL', $value);
							$value = rawurlencode($value);
							$value = str_replace('EQUAL', '=', $value);
							$param_array[$key] = $value;
						}
						
						$homepage .= implode('&', $param_array);
					}
					
					//$homepage = '<a href="'.$homepage.'" title="'.str_replace('"','&quot;',$homepage_text).'" target="_blank">'.$this->_text_as_html_short($this->_compareWithSearchText($homepage_short)).'</a>';
				}
				
				// TODO:
				// $homepage = $converter->compareWithSearchText($homepage);
				$homepage = $converter->text_as_html_short($homepage);
				$return['homepage'] = $homepage;
			}
			
			// description of the user
			$desc = $this->_item->getDescription();
			if(!empty($desc)) {
				$desc = $converter->cleanDataFromTextArea($desc);
				//TODO:
				//$desc = $converter->compareWithSearchText($desc);
				$converter->setFileArray($this->getItemFileList());
				$desc = $converter->text_as_html_long($desc);
				$return['description'] = $desc;
			}
			
			return $return;
		}
	}