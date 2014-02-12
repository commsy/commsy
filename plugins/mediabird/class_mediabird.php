<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Fabian Gebert (Mediabird), Dr. Iver Jackewitz (CommSy),
//                   Frank Wolf (Mediabird)
//
// This file is part of the mediabird plugin for CommSy.
//
// This plugin is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You have received a copy of the GNU General Public License
// along with the plugin.

include_once('classes/cs_plugin.php');
class class_mediabird extends cs_plugin {
   private $_home_status_default    = 'nodisplay';
   private $_rubric_nav_icon        = 'plugins/mediabird/images_commsy/rubric_nav_icon.png';
   private $_rubric_title_icon      = 'plugins/mediabird/images_commsy/rubric_title_icon.png';
   private $_action_extra_icon      = 'plugins/mediabird/images_commsy/action_extra_icon.png';
   private $_action_make_icon       = 'plugins/mediabird/images_commsy/action_make_icon.png';
   private $_action_user_icon       = 'plugins/mediabird/images_commsy/action_user_icon.png';
   private $_action_group_icon      = 'plugins/mediabird/images_commsy/action_group_icon.png';
   private $_action_not_active_icon = 'plugins/mediabird/images_commsy/action_not_active_icon.png';
   private $_action_csbar_icon      = 'plugins/mediabird/images_commsy/tm_mediabird.png';
   private $_mb_user_id             = array();

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      parent::__construct($environment);
      $this->_translator->addMessageDatFolder('plugins/mediabird');
      $this->_identifier = 'mediabird';
      $this->_title      = 'Mediabird';
   }

   public function isExtraPlugin () {
      return true;
   }

   public function isRubricPlugin () {
      return false;
   }

   public function inStatistics () {
      return true;
   }

   public function isConfigurableInPortal () {
      return true;
   }

   public function configurationAtPortal ( $type = '', $values = array() ) {
      $retour = '';
      if ( $type == 'change_form' ) {
         $retour = true;
      } elseif ( $type == 'create_form' ) {
         if ( !empty($values['form']) ) {
            $retour = $values['form'];
         }
      } elseif ( $type == 'save_config'
                 and !empty($values['current_context_item'])
               ) {
         if ( isset( $values[$this->_identifier.'_show_in_myarea'] ) ) {
            $values['current_context_item']->setPluginConfigForPlugin($this->_identifier,array($this->_identifier.'_show_in_myarea' => $values[$this->_identifier.'_show_in_myarea']));
         }
      } elseif ( $type == 'load_values_item'
                 and !empty($values['current_context_item'])
               ) {
         $retour = array();
         $config = $values['current_context_item']->getPluginConfigForPlugin($this->_identifier);
         if ( !empty($config[$this->_identifier.'_show_in_myarea']) ) {
            $retour[$this->_identifier.'_show_in_myarea'] = $config[$this->_identifier.'_show_in_myarea'];
         }
      }
      return $retour;
   }

   public function isConfigurableInRoom ( $room_type = '' ) {
      $retour = false;
      if ( $room_type == CS_PRIVATEROOM_TYPE ) {
         $retour = true;
      }
      return $retour;
   }

   public function inPrivateRoom () {
      return true;
   }

   public function getHomeStatusDefault () {
      return $this->_home_status_default;
   }

   public function getDisplayName () {
      return $this->_translator->getMessage('MEDIABIRD_RUBRIC_NAME');
   }

   public function getHomepage () {
      return 'http://www.mediabird.net';
   }

   public function getDescription () {
      if ( $this->_environment->inPortal() ) {
         return $this->_translator->getMessage('MEDIABIRD_DESCRIPTION_PORTAL');
      } else {
         return $this->_translator->getMessage('MEDIABIRD_DESCRIPTION_PRIVATEROOM');
      }
   }

   public function getRubricNavIcon () {
      return $this->_rubric_nav_icon;
   }

   public function getRubricTitleIcon () {
      return $this->_rubric_title_icon;
   }

   public function getActionExtraIcon () {
      return $this->_action_extra_icon;
   }

   public function getActionCSBarIcon () {
      return $this->_action_csbar_icon;
   }

   public function getActionMakeIcon () {
      return $this->_action_make_icon;
   }

   public function getActionUserIcon () {
      return $this->_action_user_icon;
   }

   public function getActionNotActiveIcon () {
      return $this->_action_not_active_icon;
   }

   public function getActionGroupIcon () {
      return $this->_action_group_icon;
   }

   private function _getContentLink () {
      $retour  = '';
      global $c_commsy_domain;
      global $c_commsy_url_path;
      $retour .= $c_commsy_domain.$c_commsy_url_path;
      global $c_single_entry_point;
      $retour .= '/'.$c_single_entry_point.'?cid='.$this->_environment->getCurrentContextID();
      $retour .= '&mod='.$this->_environment->getCurrentModule();
      $retour .= '&fct='.$this->_environment->getCurrentFunction();
      $retour .= '&iid='.$this->_environment->getValueOfParameter('iid');
      return $retour;
   }

   private function _getMBUserID ( $commsy_user_item_id = '' ) {
      $retour = '';
      if ( empty($commsy_user_item_id) ) {
         $current_user_item = $this->_environment->getCurrentUserItem();
         $current_user_item_id = $current_user_item->getItemID();
         $portal_user_item = $current_user_item->getRelatedCommSyUserItem();
         $commsy_user_item_id = $portal_user_item->getItemID();
         unset($portal_user_item);
         unset($current_user_item);
      } else {
         $user_manager = $this->_environment->getUserManager();
         $current_user_item = $user_manager->getItem($commsy_user_item_id);
         if ( isset($current_user_item) ) {
            $current_user_item_id = $current_user_item->getItemID();
            $portal_user_item = $current_user_item->getRelatedCommSyUserItem();
            $commsy_user_item_id = $portal_user_item->getItemID();
            unset($portal_user_item);
            unset($current_user_item);
         }
      }
      if ( !empty($commsy_user_item_id)
           and empty($this->_mb_user_id[$commsy_user_item_id])
         ) {

         $external_id_manager = $this->_environment->getExternalIDManager();
         $mb_user_id = $external_id_manager->getExternalId('mediabird',$commsy_user_item_id);
         if ( !empty($mb_user_id) ) {
            $this->_mb_user_id[$commsy_user_item_id] = $mb_user_id;
            if ( !empty($current_user_item_id) ) {
               $this->_mb_user_id[$current_user_item_id] = $mb_user_id;
            }
            $retour = $this->_mb_user_id[$commsy_user_item_id];
         }
         unset($external_id_manager);
      } elseif ( !empty($commsy_user_item_id)
                 and !empty($this->_mb_user_id[$commsy_user_item_id])
               ) {
         $retour = $this->_mb_user_id[$commsy_user_item_id];
      }
      return $retour;
   }

   public function headerAsHTML() {
      $notesTitle = $this->_translator->getMessage('MEDIABIRD_NOTES_TITLE');
      $notesDescription = $this->_translator->getMessage('MEDIABIRD_NOTES_DESCRIPTION');
      $titleIconUrl = $this->getRubricTitleIcon();

      $retour = '';
      $retour .= '<div id="header">'.LF;

      $retour .= '<div class="icon">'.LF;
      $retour .= '<img src="'.$titleIconUrl.'"/>'.LF;
      $retour .= '</div>'.LF;

      $retour .= '<h1>'. $notesTitle.'</h1>';
      $retour .= '<h2>'. $notesDescription.'</h2>';

      $retour .= '</div>';
    return $retour;
   }

   public function footerAsHTML() {
      $retour = '';
   $retour .= '<div id="footer">'.LF;
   $retour .= 'Mediabird Study Notes 2010.08. &copy; Copyright 2008-2010 <a href="http://www.mediabird.net/" target="_blank">Mediabird</a>.'.LF;
   $retour .= '</div>'.LF;
    return $retour;
   }

   public function getDetailActionAsHTML () {
      $retour = '';
      $current_user = $this->_environment->getCurrentUserItem();
      if ( $this->_environment->inPrivateRoom() ) {
         $own_room = $this->_environment->getCurrentContextItem();
      } else {
         $own_room = $current_user->getOwnRoom();
      }
      if ( isset($own_room)
           and !$current_user->isOnlyReadUser()
         ) {

         $system = 'mediabird';

         $url_params = array();
         $url_params['name'] = $system;
         $url_params['SID'] = $this->_environment->getSessionID();
         include_once('functions/security_functions.php');
         $url_params['security_token'] = getToken();
         $url_params['output'] = 'pure';

         $plugin_folder = 'plugins';
         $plugin_name = '/mediabird';
         $plugin_dir = $plugin_folder.$plugin_name;

         $commsyUrl = $this->_getContentLink();

         include_once($plugin_dir.'/config/config_default.php');
         include($plugin_dir.'/config/config.php');
         include_once($plugin_dir.'/server/helper.php');
         include_once($plugin_dir.'/server/utility.php');
         include_once($plugin_dir.'/server/dbo.php');
         include_once($plugin_dir.'/server/db_mysql.php');
         global $mediabirdDb;
         $mediabirdDb = new MediabirdDboMySql();
         $helper = new MediabirdHtmlHelper();

         $userId = $this->_getMBUserID();

         if($mediabirdDb->connect()) {
            $relatedNotes = $helper->findRelatedNotes($commsyUrl,$userId,$mediabirdDb);
            $title = $this->_translator->getMessage('MEDIABIRD_ACTION_ICON_USER_TITLE');
            if($relatedNotes && count($relatedNotes[0])>0) {					// there are own notices
               $card_id = $relatedNotes[0][0];
               $img = ucfirst($this->_translator->getMessage('MEDIABIRD_ACTION_NAME_DETAIL')).' ('.count($relatedNotes[0]).')';
            }
            else { //no related notes yet
               $img = ucfirst($this->_translator->getMessage('MEDIABIRD_ACTION_NAME_DETAIL'));

               //determine the card the user's been to most recently
               if(isset($userId) && ($sessionRecord = $mediabirdDb->getRecord(MediabirdConfig::tableName("Session",true),"user_id=$userId"))) {
                  $card_id = $sessionRecord->card_id;
               }
            }

            $mediabirdDb->disconnect();
         } else {
            $title = $this->_translator->getMessage('MEDIABIRD_ACTION_ICON_USER_TITLE');
            $img = ucfirst($this->_translator->getMessage('MEDIABIRD_ACTION_NAME_DETAIL'));
         }

         $retour = '<a href="javascript:void(0)" id="mediabirdLink" title="'.$title.'">'.$img.'</a>'.LF;

         $frameUrl  = _curl(false,$this->_environment->getCurrentContextID(),$system,'index',$url_params);
         $frameUrl .= "&mb_url=".urlencode($commsyUrl);

         if ( !empty( $card_id ) ) {
            $frameUrl .= "&mb_card_id=".urlencode($card_id);
         }

         $titleHtml = '<div class="title">'.$this->_translator->getMessage('MEDIABIRD_OVERLAY_TITLE').'</div>';

         $retour .= '<div id="mediabirdOverlay" class="mediabird-overlay">'.LF;
         $retour .= '   <div class="bar"><a href="javascript:void(0)" class="closer">X</a><a href="javascript:void(0)" class="expander expanded"></a>'.$titleHtml.'</div>'.LF;
         $retour .= '   <div class="resize-handle right"></div>'.LF;
         $retour .= '   <div class="resize-handle"></div>'.LF;
         $retour .= '   <iframe src="" frameborder="no" scrolling="no" id="mediabirdFrame">'.LF;
         $retour .= '   </iframe>'.LF;
         $retour .= '</div>'.LF;
         $retour .= '<script type="text/javascript" src="'.$plugin_dir.'/js/overlay.js"></script>'.LF;
         $retour .= '<link rel="stylesheet" href="'.$plugin_dir.'/css/overlay_commsy.php?cid='.$this->_environment->getCurrentContextID().'"/>'.LF;
         $retour .= '<script type="text/javascript">'.LF;
         $retour .= '//<![CDATA['.LF;
         $retour .= '   mbOverlay.MAX_HEIGHT = 544;'.LF;
         $retour .= '   mbOverlay.MAX_WIDTH = 685;'.LF;
         $retour .= '   mbOverlay.SIZE_SECURE = 34;'.LF;
         $retour .= '   var url = "'.$frameUrl.'";'.LF;
         $retour .= '   mbOverlay.doIframe(url,document.getElementById("mediabirdLink"),document.getElementById("mediabirdOverlay"),{width:685,height:544},document.getElementById("mediabirdFrame"));'.LF;
         $retour .= '//]]>'.LF;
         $retour .= '</script>'.LF;

      } elseif ( $current_user->isOnlyReadUser() ) {
         $title = $this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('MEDIABIRD_OVERLAY_TITLE'));
         $img =  '<img src="'.$this->getActionNotActiveIcon().'" style="vertical-align:bottom;" title="'.$title.'"/>';
         $retour =  $img.LF;
      } else {
         $title = $this->_translator->getMessage('MEDIABIRD_ACTION_ICON_NOT_ACTIVE_TITLE');
         $img =  '<img src="'.$this->getActionNotActiveIcon().'" style="vertical-align:bottom;" title="'.$title.'"/>';
         $retour =  $img.LF;
      }
      return $retour;
   }

   public function getExtraActionAsHTML () {
      $retour = NULL;

      if ( $this->_environment->inPrivateRoom() ) {

         $current_context_item = $this->_environment->getCurrentContextItem();
         $current_user_item = $this->_environment->getCurrentUserItem();

         if ( $current_context_item->isOpen()
              and $current_user_item->isUser()
            ) {
            $title = $this->_translator->getMessage('MEDIABIRD_ACTION_ICON_USER_TITLE').' ('.$this->getTitle().')';
            $img =  '<img src="'.$this->getActionExtraIcon().'" style="vertical-align:bottom;" title="'.$title.'"/>';
            $link = ahref_curl($this->_environment->getCurrentContextID(),'mediabird','index',array(),$img,$title,'_blank');
            $retour = $link;
         }

         unset($current_context_item);
         unset($current_context_item);
      }
      return $retour;
   }
   
   public function getMyAreaActionAsArray () {
      $retour = array();
      
      $current_context_item = $this->_environment->getCurrentContextItem();
      $current_user_item = $this->_environment->getCurrentUserItem();

      if ( $current_context_item->isOpen()
           and $current_user_item->isUser()
         ) {
         $private_room_manager = $this->_environment->getPrivateRoomManager();
         $own_room = $private_room_manager->getRelatedOwnRoomForUser($current_user_item,$this->_environment->getCurrentPortalID());
         if ( isset($own_room) 
              and $own_room->isPluginOn($this->_identifier)
            ) {
            $own_cid = $own_room->getItemID();
            $title = $this->_translator->getMessage('MEDIABIRD_ACTION_ICON_USER_TITLE').' ('.$this->getTitle().')';
            $img =  '<img src="'.$this->getActionExtraIcon().'" style="vertical-align:bottom;" title="'.$title.'"/>';
            $retour['active'] = true;
            $retour['name'] = $this->getTitle();
            $retour['title'] = $title;
      		$retour['href'] = curl($own_cid,'mediabird','index',array());
      		$retour['text'] = '&nbsp;';
      		$retour['img'] = $this->getActionCSBarIcon();
      		$retour['item_id'] = $own_cid;
         }
         unset($own_room);
         unset($private_room_manager);
      }

      unset($current_context_item);
      unset($current_user_item);
      
      return $retour;
   }

   public function getMyAreaActionAsHTML () {
      $retour = NULL;

      $show_myarea = $this->_getConfigValueFor($this->_identifier.'_show_in_myarea');
      if ( $show_myarea == -1 ) {
         $show_myarea = false;
      } else {
         $show_myarea = true;
      }

      if($show_myarea) {
         $current_context_item = $this->_environment->getCurrentContextItem();
         $current_user_item = $this->_environment->getCurrentUserItem();

         if ( $current_context_item->isOpen()
              and $current_user_item->isUser()
            ) {
            $private_room_manager = $this->_environment->getPrivateRoomManager();
            $own_room = $private_room_manager->getRelatedOwnRoomForUser($current_user_item,$this->_environment->getCurrentPortalID());
            if ( isset($own_room) 
                 and $own_room->isPluginOn($this->_identifier)
               ) {
               $own_cid = $own_room->getItemID();
               $title = $this->_translator->getMessage('MEDIABIRD_ACTION_ICON_USER_TITLE').' ('.$this->getTitle().')';
               $img =  '<img src="'.$this->getActionExtraIcon().'" style="vertical-align:bottom;" title="'.$title.'"/>';
               $link = ahref_curl($own_cid,'mediabird','index',array(),$img,$title,'_blank');
               $retour = $link;
            }
            unset($own_room);
            unset($private_room_manager);
         }

         unset($current_context_item);
         unset($current_context_item);
      }
      return $retour;
   }

   public function accessPageWithCheck ( $page_name ) {
      $retour = true;
      if ( $page_name == 'file' ) {
         $retour = false;
      }
      return $retour;
   }

   public function getPictureUrlForCommSyUserByItemID ( $item_id ) {
      $retour = null;
      if ( !empty($item_id) ) {
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItem($item_id);
         unset($user_manager);
         if ( !empty($user_item) ) {
            $retour = $this->getPictureUrlForCommSyUserByItem($user_item);
         }
      }
      return $retour;
   }

   public function getPictureUrlForCommSyUserByItem ( $user_item ) {
      $retour = null;
      if ( !empty($user_item) ) {
         $portal_user_item = $user_item->getRelatedCommSyUserItem();
         unset($user_item);
         if ( !empty($portal_user_item) ) {
            $retour = $portal_user_item->getPictureUrl(true,false);
            unset($portal_user_item);
            if ( empty($retour) ) {
               $retour = null;
            }
         }
      }
      return $retour;
   }

   public function search ($search_word, $parts_array) {
      $retour = array();

      if ( !empty($search_word)
           and !empty($parts_array)
         ) {
         $current_user_item = $this->_environment->getCurrentUserItem();
         $context_id_array = $current_user_item->getContextIDArray();
         if ( !empty($context_id_array) ) {
            include_once('classes/cs_list.php');
            $search_list = new cs_list();
            foreach ($parts_array as $part) {
               $manager = $this->_getPartManager($part);
               if ( isset($manager) ) {
                  $manager->setContextArrayLimit($context_id_array);
                  $manager->setSearchLimit($search_word);
                  $manager->select();
                  $list = $manager->get();
                  if ( isset($list)
                       and $list->isNotEmpty()
                     ) {
                     $search_list->addList($list);
                  }
                  unset($list);
               }
            }
         }
      }

      $item = $search_list->getFirst();
      while ($item) {
         $item_object = (object) null;
         $item_object->id = $item->getItemID();
         $item_object->type = $item->getItemType();
         if ( $item->isA(CS_USER_TYPE) ) {
            $item_object->title = $item->getFullname();
         } else {
            $item_object->title = $item->getTitle();
         }
         $item_object->url = $item->getItemURL();
         $item_object->context_id = $item->getContextID();
         $item_object->context_title = $item->getContextItem()->getTitle();
         $item_object->user_id = $item->getCreatorID();
         $item_object->user_id_mb = $this->_getMBUserID($item->getCreatorID());
         if ( empty($item_object->user_id_mb)) {
            $creator_item = $item->getCreatorItem();
            $item_object->user_title = $creator_item->getFullname();
            unset($creator_item);
         }
         $item_object->item_id = $item->getItemID();
         if ( $item->isA(CS_USER_TYPE) ) {
            $item_object->item_title = $item->getFullname();
         } else {
            $item_object->item_title = $item->getTitle();
         }
         # TBD: Warum gefunden?
         array_push($retour,$item_object);
         $item = $search_list->getNext();
      }

      return $retour;
   }

   private function _getPartManager ( $value ) {
      $retour = '';
      if ( $value == CS_DISCUSSION_TYPE ) {
         $retour = $this->_environment->getManager(CS_DISCUSSION_TYPE);
      } elseif ( $value == CS_DATE_TYPE ) {
         $retour = $this->_environment->getManager(CS_DATE_TYPE);
         $retour->setWithoutDateModeLimit();
      } elseif ( $value == CS_USER_TYPE ) {
         $retour = $this->_environment->getManager(CS_USER_TYPE);
         $retour->setUserLimit();
         $current_user = $this->_environment->getCurrentUserItem();
         if ( $current_user->isUser() ) {
            $retour->setVisibleToAllAndCommsy();
         } else {
            $retour->setVisibleToAll();
         }
         unset($current_user);
      }
      return $retour;
   }
}
?>
