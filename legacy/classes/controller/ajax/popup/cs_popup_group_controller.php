<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_group_controller implements cs_rubric_popup_controller {
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
			$this->assignTemplateVars($item);
			$current_context = $this->_environment->getCurrentContextItem();

			if($item !== null) {
				// edit mode

				// TODO: check rights

				$this->_popup_controller->assign('item', 'name', $item->getName());

				$this->_popup_controller->assign('item', 'description', $item->getDescription());
                
 				$this->_popup_controller->assign('item', 'public', $item->isPublic());
			    $this->_popup_controller->assign('item', 'picture', $item->getPicture());
			    if ($item->isGroupRoomActivated()){
			    	$this->_popup_controller->assign('item','group_room_activate','1');
			    }
			    $this->_popup_controller->assign('item','system_label',$item->isSystemLabel());

      			if($current_context->WikiEnableDiscussionNotificationGroups() == 1){
      				$discussion_array = $current_context->getWikiDiscussionArray();

			        $discussion_notification_array = array();
			        $temp_array['text'] = '*'.$this->_translator->getMessage('PREFERENCES_NO_DISCUSSION_NOTIFICATION');
			        $temp_array['value'] = '-1';
			        $discussion_notification_array[] = $temp_array;
			        $temp_array['text'] = '--------------------';
			        $temp_array['value'] = 'disabled';
			        $discussion_notification_array[] = $temp_array;

			        if ( isset($discussion_array) and !empty($discussion_array) ) {
			           foreach ($discussion_array as $discussion) {
			              $temp_array['text'] = $discussion;
			              $temp_array['value'] = $discussion;
			              $discussion_notification_array[] = $temp_array;
			           }
			        }

      				$_discussion_notification_array = $discussion_notification_array;

			        $discussion_notification_array = array();

   		            $discussion_notification_array = $this->_item->getDiscussionNotificationArray();
			        if (isset($discussion_notification_array[0])) {
			            foreach ($discussion_notification_array as $discussion_notification) {
			               $temp_array['text'] = $discussion_notification;
			               $temp_array['value'] = $discussion_notification;
			               $discussion_notification_array[] = $temp_array;
			            }
			        }
      				$_shown_discussion_notification_array = $discussion_notification_array;
         		   	if ( !empty ($_shown_discussion_notification_array) ) {
            	       $this->_popup_controller->assign('item','discussion_notification_list',$_shown_discussion_notification_array);
         		   	}
         		   	$this->_popup_controller->assign('item','discussion_notification',$_discussion_notification_array);
 			   }

			}else{


			}
    }

    public function save($form_data, $additional = array()) {
        $environment = $this->_environment;
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        if(isset($additional['action']) && $additional['action'] === 'upload_picture') $current_iid = $additional['iid'];
        else $current_iid = $form_data['iid'];

        $translator = $this->_environment->getTranslationObject();

        if($current_iid === 'NEW') {
            $item = null;
        } else {
            $item_manager = $this->_environment->getGroupManager();
            $item = $item_manager->getItem($current_iid);
        }
        
        $this->_popup_controller->performChecks($item, $form_data, $additional);

        if (isset($form_data['editType'])){
			$this->_edit_type = $form_data['editType'];
        }

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
            $this->_popup_controller->setSuccessfullItemIDReturn($item->getItemID(),CS_GROUP_TYPE);

        }else { //Acces granted
			$this->cleanup_session($current_iid);

			// upload picture
			if(isset($additional['action']) && $additional['action'] === 'upload_picture') {
				if($this->_popup_controller->checkFormData('picture_upload')) {
					/* handle group picture upload */
					if (!empty($additional["fileInfo"])) {
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
						$item_manager = $environment->getGroupManager();
						$item = $item_manager->getNewItem();
						$item->setContextID($environment->getCurrentContextID());
						$current_user = $environment->getCurrentUserItem();
						$item->setCreatorItem($current_user);
						$item->setCreationDate(getCurrentDateTimeInMySQL());
						$item->setModificationDate(getCurrentDateTimeInMySQL());
               			$item->setLabelType(CS_GROUP_TYPE);
						$item_is_new = true;
					}

					// Set modificator and modification date
					$current_user = $environment->getCurrentUserItem();
					$item->setModificatorItem($current_user);

					// Set attributes
					if ( isset($form_data['name']) ) {
						$item->setName($form_data['name']);
					}
					if ( isset($form_data['description']) ) {
						$item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));
					}
					if (isset($form_data['public'])) {
						$item->setPublic($form_data['public']);
					}


					if ( isset($form_data['group_room_activate']) ) {
						$item->setGroupRoomActive();
					}else{
						$item->unsetGroupRoomActive();
					}

					if($item->getPicture() && isset($form_data['delete_picture'])) {
						$disc_manager = $this->_environment->getDiscManager();

						if($disc_manager->existsFile($item->getPicture())) $disc_manager->unlinkFile($item->getPicture());
						$item->setPicture('');
					}

					// Foren:
					$discussion_notification_array = array();
					if ( isset($form_data['discussion_notification_list']) ) {
						$discussion_notification_array = $form_data['discussion_notification_list'];
					}
					if ( isset($form_data['discussion_notification'])
							and !in_array($form_data['discussion_notification'],$discussion_notification_array)
							and ($form_data['discussion_notification'] != -1)
							and ($form_data['discussion_notification'] != 'disabled')
					) {
						$discussion_notification_array[] = $form_data['discussion_notification'];
					}

					$item->setDiscussionNotificationArray($discussion_notification_array);
					// Save item
					$item->save();

					if (isset($form_data["grouproom_template"])){
        				if(isset($form_data['group_room_activate'])){
        					$_POST['template_select'] = $form_data["grouproom_template"];
        					if(!($_POST['template_select'] == 'leerer Raum') && !($_POST['template_select'] == 'empty workspace')){
        						$itemBackup = $item;
        						$item = $item->getGroupRoomItem();
        						include_once('include/inc_room_copy.php');
        						$item->setLinkedGroupItemID($itemBackup->getItemId());
        						$item->save();
        						$item = $itemBackup;
        					}
        					
        				}
        			}

					// this will update the right box list
					if($item_is_new){
						if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.CS_GROUP_TYPE.'_index_ids')){
							$id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.CS_GROUP_TYPE.'_index_ids'));
						} else {
							$id_array =  array();
						}

						$id_array[] = $item->getItemID();
						$id_array = array_reverse($id_array);
						$session->setValue('cid'.$environment->getCurrentContextID().'_'.CS_GROUP_TYPE.'_index_ids',$id_array);
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

    private function assignTemplateVars($itemObject) {
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


		
        $current_portal = $this->_environment->getCurrentPortalItem();
        $room_manager = $this->_environment->getRoomManager();
        $room_manager->setContextLimit($current_portal->getItemID());
        // $room_manager->setWithGrouproom();
        $room_manager->setOnlyGrouproom();
        $room_manager->setTemplateLimit();
        $room_manager->select();
        $room_list = $room_manager->get();

        
        //$default_id = $this->_environment->getCurrentPortalItem()->getDefaultProjectTemplateID();
        $default_id = '-1'; // -> #3187
        if ($room_list->isNotEmpty() or $default_id != '-1' ) {
        	$current_user = $this->_environment->getCurrentUser();
        	if ( $default_id != '-1' ) {
        		$default_item = $room_manager->getItem($default_id);
        		if ( isset($default_item) ) {
        			$template_availability = $default_item->getTemplateAvailability();
        			if ( $template_availability == '0' ) {
        				$temp_array['text'] = '*'.$default_item->getTitle();
        				$temp_array['value'] = $default_item->getItemID();
        				$template_array[] = $temp_array;
        				$temp_array = array();
        				$temp_array['text'] = '------------------------';
        				$temp_array['value'] = 'disabled';
        				$template_array[] = $temp_array;
        			}
        		}
        	}
        	$item = $room_list->getFirst();
        	while ($item) {
        		$temp_array = array();
        		$template_availability = $item->getTemplateAvailability(); 
        
        		if( ($template_availability == '0') OR
        		($this->_environment->inCommunityRoom() and $template_availability == '3') OR
        		($this->_environment->inPortal() and $template_availability == '3' and $community_room_member) OR
        		($template_availability == '1' and $item->mayEnter($current_user)) OR
        		($template_availability == '2' and $item->mayEnter($current_user) and ($item->isModeratorByUserID($current_user->getUserID(),$current_user->getAuthSource())))
        		){
        			if ($item->getItemID() != $default_id or $item->getTemplateAvailability() != '0'){
        				$this->_with_template_form_element2 = true;
        				$temp_array['text'] = $item->getTitle();
        				$temp_array['value'] = $item->getItemID();
        				$template_array[] = $temp_array;
       
        				$this->_javascript_array[$item->getItemID()] = nl2br($item->getTemplateDescription());
        			}
        
        		}
        		$item = $room_list->getNext();
        	}
        	unset($current_user);
        }
        
        // show group room templates if item is new or item has no grouproom

        $showTemplateSelect = false;
        if (!$itemObject) {
            $showTemplateSelect = true;
        } else {
            if (!$itemObject->isGroupRoomActivated()) {
                $showTemplateSelect = true;
            }
        }

        if(!empty($template_array) && $showTemplateSelect){
        	$this->_popup_controller->assign('popup', 'withTemplate', '1');
        	$this->_popup_controller->assign('popup', 'template', $template_array);
        } else {
        	$this->_popup_controller->assign('popup', 'withTemplate', '0');
        }
        

		
		$this->_popup_controller->assign("item", "languages", $this->_environment->getAvailableLanguageArray());
    }


    public function getFieldInformation($sub = '') {
		if ($this->_edit_type == 'normal'){
			$return = array(
				'upload_picture'	=> array(
				),

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
				'grouproom_activate'=> array(
					array(	'name'		=> 'grouproom_activate',
							'type'		=> 'check',
							'mandatory' => false)
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