<?php
require_once ('classes/controller/cs_ajax_controller.php');

class cs_ajax_clipboard_controller extends cs_ajax_controller
{
    
    /**
     * constructor
     */
    public function __construct(cs_environment $environment) {
        
        // call parent
        parent::__construct($environment);
    }
    
    private function _actionPerformRequest($archive_toggle = false) {
        
        // archive
        if ($archive_toggle) {
            $this->_environment->toggleArchiveMode();
        }
        
        // archive
        
        $return = array();
        
        $current_context = $this->_environment->getCurrentContextItem();
        $current_user = $this->_environment->getCurrentUser();
        $session = $this->_environment->getSessionItem();
        $project_manager = $this->_environment->getProjectManager();
        $community_manager = $this->_environment->getCommunityManager();
        $private_room_manager = $this->_environment->getPrivateRoomManager();
        $group_room_manager = $this->_environment->getGroupRoomManager();
        $link_manager = $this->_environment->getLinkManager();
        $noticed_manager = $this->_environment->getNoticedManager();
        $room_manager = $this->_environment->getProjectManager();
        
        $current_context = $this->_environment->getCurrentContextItem();
        $session = $this->_environment->getSession();
        $translator = $this->_environment->getTranslationObject();
        
        // get current room modules
        $current_room_modules = $current_context->getHomeConf();
        $room_moduls = array();
        if (!empty($current_room_modules)) {
            $room_modules = explode(",", $current_room_modules);
        }
        
        $modules = array();
        foreach ($room_modules as $module) {
            list($name, $display) = explode("_", $module);
            
            if ($display !== "none") $modules[] = $name;
        }
        
        $rubric_copy_array = $this->getUtils()->getCopyRubrics();
        
        $item_room_ids = array();
        $tmp_id_array = array();
        $item_list = new cs_list();
        
        // collect room and item ids
        foreach ($rubric_copy_array as $rubric) {
            $item_manager = $this->_environment->getManager($rubric);
            $item_id_array = $session->getValue($rubric . "_clipboard");
            $item_list->addList($item_manager->getItemList($item_id_array));
            $item = $item_list->getFirst();
            while ($item) {
                $item_room_ids[] = $item->getContextID();
                
                $item = $item_list->getNext();
            }
            
            if (is_array($item_id_array)) {
                $tmp_id_array = array_merge($tmp_id_array, $item_id_array);
            }
        }
        
        $item_id_array = $tmp_id_array;
        
        // create a list of rooms
        $rooms = new cs_list();
        
        $rooms->addList($project_manager->getSortedItemList($item_room_ids, "title"));
        $rooms->addList($community_manager->getSortedItemList($item_room_ids, "title"));
        $rooms->addList($private_room_manager->getSortedItemList($item_room_ids, "title"));
        $rooms->addList($group_room_manager->getSortedItemList($item_room_ids, "title"));
        
        // get item information
        $new_item_list = new cs_list();
        $checked_ids = array();
        
        if (!empty($item_id_array)) {
            foreach ($item_id_array as $item_id) {
                $item = $item_list->getFirst();
                while ($item) {
                    if ($item->getItemID() == $item_id && $item->getContextID() === 0) {
                        $item_manager = $this->_environment->getManager($item->getItemType());
                        $item = $item_manager->getItem($item->getItemId());
                        
                        $new_item_list->add($item);
                        if ($item->getContextID() !== $current_context->getItemID()) {
                            $checked_ids[] = $item->getItemID();
                        }
                        break;
                    } else {
                        $item = $item_list->getNext();
                    }
                }
            }
        }
        
        $room_sort = $rooms->getFirst();
        
        while ($room_sort) {
            if (!empty($item_id_array)) {
                foreach ($item_id_array as $item_id) {
                    $item = $item_list->getFirst();
                    while ($item) {
                        if ($item->getItemID() == $item_id && $item->getContextID() == $room_sort->getItemId()) {
                            $item_manager = $this->_environment->getManager($item->getItemType());
                            $item = $item_manager->getItem($item->getItemId());
                            
                            $new_item_list->add($item);
                            if (isset($item) && $item->getContextID() !== $current_context->getItemID()) {
                                $checked_ids[] = $item->getItemID();
                            }
                            break;
                        } else {
                            $item = $item_list->getNext();
                        }
                    }
                }
            }
            
            $room_sort = $rooms->getNext();
        }
        
        $item_list = $new_item_list;
        
        // prepare return
        $data = array();
        $entry = $item_list->getFirst();
        
        if ($entry) $last_room_id = $entry->getContextID();
        
        $index = 0;
        while ($entry) {
        	if ($entry->isDeleted()) {
        		$entry = $item_list->getNext();
        		continue;
        	}

            $room_id = $entry->getContextID();
            
            if ($last_room_id != $room_id) {
                $index++;
            }
            $last_room_id = $room_id;
            
            // room data
            if (!isset($data[$index])) {
                $data[$index]["room_id"] = $last_room_id;
                
                $room = $room_manager->getItem($last_room_id);
                
                // if $room is null, try to get a private room
                if ($room === null) {
                    $room = $private_room_manager->getItem($last_room_id);
                }
                
                $headline = "";
                if (empty($room)) {
                    $community_manager->getItem($room_id);
                    $room = $community_manager->getItem($room_id);
                    
                    $headline = $translator->getMessage("COPY_FROM") . " " . $translator->getMessage("COMMON_COMMUNITY_ROOM_TITLE") . " \"" . $room->getTitle() . "\"";
                } elseif ($room->isPrivateRoom()) {
                    $headline = $translator->getMessage("COPY_FROM_PRIVATEROOM") . " \"" . $current_user->getFullname() . "\"";
                } elseif ($room->isGroupRoom()) {
                    $headline = $translator->getMessage("COPY_FROM_GROUPROOM") . " \"" . $room->getTitle() . "\"";
                } else {
                    $headline = $translator->getMessage("COPY_FROM_PROJECTROOM") . " \"" . $room->getTitle() . "\"";
                }
                
                $data[$index]["headline"] = $headline;
            }
            
            // process title
            $title = $entry->getTitle();
            if ($entry->isNotActivated()) {
                
                $activating_date = $entry->getActivatingDate();
                if (mb_strstr($activating_date, "9999-00-00")) {
                    $title.= BR . $translator->getMessage('COMMON_NOT_ACTIVATED');
                } else {
                    $title.= BR . $translator->getMessage('COMMON_NOT_ACTIVATED') . " " . getDateInLang($entry->getActivatingDate());
                }
            }

            $disabled = false;
            if ($entry->isNotActivated() && !($entry->getCreatorID() == $current_user->getItemID() || $current_user->isModerator())) {
            	$disabled = true;
            }
            
            // item data
            $data[$index]["items"][] = array(
            	"disabled"			=> $disabled,
            	"item_id"			=> $entry->getItemID(),
            	"title"				=> $title,
            	"rubric"			=> $this->getUtils()->getLogoInformationForType($entry->getItemType()),
            	"modifier"			=> $entry->getModificatorItem()->getFullName(),
            	"modification_date"	=> getDateInLang($entry->getModificationDate()));
            
            $entry = $item_list->getNext();
        }
        
        // archive
        if ($archive_toggle) {
            $this->_environment->toggleArchiveMode();
        }
        
        // archive
        
        return $data;
    }
    
    public function actionPerformRequest() {
        $data = $this->_actionPerformRequest();
        
        // archive
        $data2 = $this->_actionPerformRequest(true);
        if (!empty($data2)) {
            $data = array_merge($data, $data2);
        }
        
        // archive
        $this->setSuccessfullDataReturn(array("list" => $data));
        echo $this->_return;
    }
    
    public function actionPerformClipboardAction() {
        
        // get request data
        $ids = $this->_data["ids"];
        $action = $this->_data["action"];
        
        $manager = $this->_environment->getItemManager();
        
        $error_array = array();
        
        switch ($action) {
            case "paste":
                
                // archive
                if ($this->_environment->isArchiveMode()) {
                    $error_array[] = 'ERROR: copy items in archived workspaces is not allowed';
                }
                
                // archive
                
                elseif ($this->_environment->inPortal()) {
                    $error_array[] = 'ERROR: copy items in portal is not allowed';
                } else if ($this->_environment->getCurrentUserItem()->isOnlyReadUser()) {
                    $error_array[] = 'ERROR: copy items as read only user is not allowed';
                } elseif (!empty($ids)) {
                    foreach ($ids as $id) {
                        
                        // get item to copy
                        $item = $manager->getItem($id);
                        
                        // archive
                        $toggle_archive = false;
                        if ($item->isArchived() and !$this->_environment->isArchiveMode()) {
                            $toggle_archive = true;
                            $this->_environment->toggleArchiveMode();
                        }
                        
                        // archive
                        
                        $item_manager = $this->_environment->getManager($item->getItemType());
                        $import_item = $item_manager->getItem($id);
                        
                        // archive
                        if ($toggle_archive) {
                            $this->_environment->toggleArchiveMode();
                        }
                        
                        // archive
                        
                        $copy = $import_item->copy();
                        
                        $rubric = $item->getItemType();
                        $iid = $copy->getItemID();
                        
                        $err = $copy->getErrorArray();
                        if (!empty($err)) {
                            $error_array[$copy->getItemID() ] = $err;
                        } else {
                           $reader_manager = $this->_environment->getReaderManager();
                           $reader_manager->markRead($copy->getItemID(), $copy->getVersionID());
                           $noticed_manager = $this->_environment->getNoticedManager();
                           $noticed_manager->markNoticed($copy->getItemID(), $copy->getVersionID());
                        }
                    }
                }
                
                if (!empty($error_array)) {
                    $this->setErrorReturn("106", "something goes wrong while copying", $error_array);
                    echo $this->_return;
                } else {
                    
                    // setup redirect
                    $url = "commsy.php?cid=" . $this->_environment->getCurrentContextID();
                    
                    if (sizeof($ids) > 1) {
                        $url.= "&mod=" . $rubric . "&fct=index";
                    } else {
                        $url.= "&mod=" . $rubric . "&fct=detail&iid=" . $iid;
                    }
                    
                    $this->setSuccessfullDataReturn(array("url" => $url));
                    echo $this->_return;
                }
                break;

            case "paste_stack":
                
                $privateRoomItem = $this->_environment->getCurrentUser()->getOwnRoom();
                $this->_environment->changeContextToPrivateRoom($privateRoomItem->getItemID());
                
                $error_array = array();
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        
                        // get item to copy
                        $item = $manager->getItem($id);
                        
                        // for now, we only copy materials, dates, discussions and todos
                        if (in_array($item->getItemType(), array(CS_MATERIAL_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE, CS_TODO_TYPE))) {
                            
                            // archive
                            $toggle_archive = false;
                            if ($item->isArchived() and !$this->_environment->isArchiveMode()) {
                                $toggle_archive = true;
                                $this->_environment->toggleArchiveMode();
                            }
                            
                            // archive
                            
                            $item_manager = $this->_environment->getManager($item->getItemType());
                            $import_item = $item_manager->getItem($id);
                            
                            // archive
                            if ($toggle_archive) {
                                $this->_environment->toggleArchiveMode();
                            }
                            
                            // archive
                            
                            $copy = $import_item->copy();
                            
                            $rubric = $item->getItemType();
                            $iid = $copy->getItemID();
                            
                            $err = $copy->getErrorArray();
                            if (!empty($err)) {
                                $error_array[$copy->getItemID() ] = $err;
                            }
                        }
                    }
                }
                
                if (!empty($error_array)) {
                    $this->setErrorReturn("106", "something goes wrong while copying", $error_array);
                    echo $this->_return;
                } else {
                    $this->setSuccessfullDataReturn(array());
                    echo $this->_return;
                }
                break;

            case "delete":
                if (!empty($ids)) {
                    $current_context = $this->_environment->getCurrentContextItem();
                    $session = $this->_environment->getSessionItem();
                    $translator = $this->_environment->getTranslationObject();
                    
                    $rubric_copy_array = $this->getUtils()->getCopyRubrics();
                    
                    // collect room and item ids
                    $rubric_item_ids = array();
                    
                    foreach ($rubric_copy_array as $rubric) {
                        $item_manager = $this->_environment->getManager($rubric);
                        $item_id_array = $session->getValue($rubric . "_clipboard");
                        
                        if (is_array($item_id_array)) {
                            $rubric_item_ids[$rubric] = $item_id_array;
                        }
                    }
                    
                    // go through each rubric and remove appropriate entries
                    foreach ($rubric_item_ids as $rubric => $id_array) {
                        
                        $new_id_array = array();
                        foreach ($id_array as $id) {
                            if (!in_array($id, $ids)) $new_id_array[] = $id;
                        }
                        
                        $session->setValue($rubric . "_clipboard", $new_id_array);
                    }
                    
                    $this->_environment->getSessionManager()->save($session);
                    
                    $this->setSuccessfullDataReturn(array());
                    echo $this->_return;
                }
                break;
            }
        }
        
        /*
         * every derived class needs to implement an processTemplate function
        */
        public function process() {
            
            // TODO: check for rights, see cs_ajax_accounts_controller
            
            // call parent
            parent::process();
        }
    }
    