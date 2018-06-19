<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

$this->includeClass(RUBRIC_FORM);
include_once('functions/text_functions.php');

/** class for commsy form: user
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_profile_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing an array of groups in the context
   */
   var $_group_array = array();

  /**
   * array - containing an array of topics in the context
   */
   var $_topic_array = array();

  /**
   * boolean - gibt es ein Bild zu dieser Person?
   */
   var $_with_picture = false;

  /**
   * boolean - ist die Person ein Moderator?
   */
   var $_is_moderator = false;

   var $_profile_page_name = 'account';

   var $_link_item_array = array();

   var $_link_item_check_array = array();

   var $_user = NULL;

   private $_language = NULL;

   private $_show_merge_form = true;
   private $_show_auth_source = true;
   private $_auth_source_list = NULL;
   private $_array_sources_allow_delete = array();
   private $_show_password_change_form = false;
   private $_show_account_change_form = false;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct ($params) {
      cs_rubric_form::__construct($params);
      $this->_language = $this->_environment->getSelectedLanguage();
   }

  /**
    * @param boolean - gibt es ein Bild zum Benutzer?
    */
   function setWithPicture ($value) {
      $this->_with_picture = (boolean)$value;
   }

  /**
    * @param boolean - ist der Benutzer ein Moderator?
    */
   function setIsModerator ($value) {
      $this->_is_moderator = (boolean)$value;
   }

   function setProfilePageName($name){
    $this->_profile_page_name = $name;
   }
   function getProfilePageName(){
      return $this->_profile_page_name;
   }

   public function setLanguage ( $value ) {
      $this->_language = (string)$value;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      if ($this->getProfilePageName() == 'user'){
         // headline
         if (!empty($this->_item)) {
            $this->_headline = $this->_translator->getMessageInLang($this->_language,'USER_EDIT_FORM_TITLE');
         } elseif (!empty($this->_form_post)) {
            if (!empty($this->_form_post['iid'])) {
               $this->_headline = $this->_translator->getMessageInLang($this->_language,'USER_EDIT_FORM_TITLE');
            }
         } else {
            $this->_headline = '';
         }
         $this->setHeadline($this->_headline);
      }elseif($this->getProfilePageName() == 'room_list'){
         $room_manager = $this->_environment->getRoomManager();
         $own_room_item = $this->_environment->getCurrentUserItem()->getOwnRoom();
         $checked_item_array = $own_room_item->getCustomizedRoomIDArray();
         $room_list = $room_manager->getRelatedRoomListForUser($this->_environment->getCurrentUserItem());
         $room_item = $room_list->getFirst();
         $unchecked_link_item_array = array();
         $tmp_link_item_array = array();
         if (isset($checked_item_array[0])){
            $customized_list_exists = true;
         }else{
            $customized_list_exists = false;
         }
         while ($room_item) {
            if ( !$room_item->isPrivateRoom()
                 and $room_item->isUser($this->_environment->getCurrentUserItem())
               ) {
               $temp_array = array();
               $temp_array['text']  = $room_item->getTitle();
               $temp_array['value'] = $room_item->getItemID();
               if (in_array($room_item->getItemID(),$checked_item_array)){
                  $tmp_link_item_array[$room_item->getItemID()] = $room_item->getTitle();
                  $this->_link_item_check_array[] = $room_item->getItemID();
               } else {
                  if (!$customized_list_exists){
                     $this->_link_item_check_array[] = $room_item->getItemID();
                  }
                  $unchecked_link_item_array[] = $temp_array;
               }
            }
            $room_item = $room_list->getNext();
         }
         $count_sep = 0;
         foreach ( $checked_item_array as $value ) {
            if ( $value < 0 ) {
               $this->_link_item_check_array[] = $value;
               $tmp_link_item_array[$value] = '----------------------------';
               $count_sep++;
            }
         }
         for ( $i=$count_sep+1; $i<$count_sep+4; $i++ ) {
            $temp_array = array();
            $temp_array['text']  = '----------------------------';
            $temp_array['value'] = -$i;
            $unchecked_link_item_array[] = $temp_array;
         }
         foreach ($checked_item_array as $id) {
            if ( !empty($tmp_link_item_array[$id]) ) {
               $temp_array = array();
               $temp_array['text']  = $tmp_link_item_array[$id];
               $temp_array['value'] = $id;
               $this->_link_item_array[] = $temp_array;
            }
         }
         $this->_link_item_array = array_merge($this->_link_item_array,$unchecked_link_item_array);
      }else{
         $this->_user = $this->_environment->getPortalUserItem();
      }

      if ( $this->getProfilePageName() == 'account' ) {
         if ( $this->_environment->inCommunityRoom()
              or $this->_environment->inProjectRoom() ) {
            $current_user = $this->_environment->getPortalUserItem();
         } else {
            $current_user = $this->_environment->getCurrentUserItem();
         }
         if ( isset($current_user)
              and $current_user->isRoot()
            ) {
            $this->_show_merge_form = false;
         }

         // auth source
         $current_portal_item = $this->_environment->getCurrentPortalItem();
         if ( !isset($current_portal_item) ) {
            $current_portal_item = $this->_environment->getServerItem();
         }
         #$this->_show_auth_source = $current_portal_item->showAuthAtLogin();
         # muss angezeigt werden, sonst koennen mit der aktuellen Programmierung
         # keine Acounts mit gleichen Kennungen aber unterschiedlichen Quellen
         # zusammengelegt werden
         $this->_show_auth_source = true;
         $auth_source_list = $current_portal_item->getAuthSourceListEnabled();
         if ( isset($auth_source_list) and !$auth_source_list->isEmpty() ) {
            $auth_source_item = $auth_source_list->getFirst();
            while ($auth_source_item) {
               $temp_array = array();
               $temp_array['value'] = $auth_source_item->getItemID();
               $temp_array['text'] = $auth_source_item->getTitle();
               $this->_auth_source_array[] = $temp_array;
               unset($temp_array);
               $auth_source_item = $auth_source_list->getNext();
            }
         }
         $this->_default_auth_source_entry = $current_portal_item->getAuthDefault();

         $current_auth_source_item = $current_portal_item->getAuthSource($current_user->getAuthSource());
         unset($current_portal_item);
         if ( ( isset($current_auth_source_item)
                and $current_auth_source_item->allowChangePassword()
              )
              or $current_user->isRoot()
            ) {
            $this->_show_password_change_form = true;
         }
         if ( ( isset($current_auth_source_item)
                and $current_auth_source_item->allowChangeUserID()
              )
              or $current_user->isRoot()
            ) {
            $this->_show_account_change_form = true;
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      if ($this->getProfilePageName() == 'user'){
         $this->_form->addHidden('uid','');
         $this->_form->addTextField('title','',$this->_translator->getMessageInLang($this->_language,'USER_TITLE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('title_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('birthday','',$this->_translator->getMessageInLang($this->_language,'USER_BIRTHDAY'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('birthday_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addImage('upload','',$this->_translator->getMessageInLang($this->_language,'USER_PICTURE_UPLOADFILE'), $this->_translator->getMessageInLang($this->_language,'USER_PICTURE_FILE_DESC'));
         //delete picture
         if ( $this->_with_picture) {
            $this->_form->combine();
            $this->_form->addCheckbox('deletePicture',$this->_translator->getMessageInLang($this->_language,'USER_DEL_PIC'),false,$this->_translator->getMessageInLang($this->_language,'USER_DEL_PIC'),$this->_translator->getMessageInLang($this->_language,'USER_DEL_PIC_BUTTON'),'');
         }
         $this->_form->combine();
         $this->_form->addCheckbox('picture_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addEmptyline();

         $this->_form->addTextField('email','',$this->_translator->getMessageInLang($this->_language,'USER_EMAIL'),'','','30',true);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('email_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('telephone','',$this->_translator->getMessageInLang($this->_language,'USER_TELEPHONE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('telephone_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('cellularphone','',$this->_translator->getMessageInLang($this->_language,'USER_CELLULARPHONE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('cellularphone_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addEmptyline();

         $this->_form->addTextField('street','',$this->_translator->getMessageInLang($this->_language,'USER_STREET'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('street_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('zipcode','',$this->_translator->getMessageInLang($this->_language,'USER_ZIPCODE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('zipcode_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('city','',$this->_translator->getMessageInLang($this->_language,'USER_CITY'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('city_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('room','',$this->_translator->getMessageInLang($this->_language,'USER_ROOM'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('room_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addEmptyline();

         $this->_form->addTextField('organisation','',$this->_translator->getMessage('USER_ORGANISATION'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('organisation_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('position','',$this->_translator->getMessage('USER_POSITION'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('position_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addEmptyLine();

         $this->_form->addTextField('homepage','',$this->_translator->getMessageInLang($this->_language,'USER_HOMEPAGE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('homepage_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('icq','',$this->_translator->getMessageInLang($this->_language,'USER_MESSENGER_NUMBERS'),'','','15',false,'','','','left',$this->_translator->getMessageInLang($this->_language,'USER_ICQ').':&nbsp;&nbsp;&nbsp;<span style="font-size: 13px;">&nbsp;</span>','',false,'');
         $this->_form->combine('horizontal');
         /*
         $this->_form->addTextField('jabber','',$this->_translator->getMessageInLang($this->_language,'USER_JABBER'),'','','19',false,'','','','left','&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('USER_JABBER').':','',false,'');
         $this->_form->combine();
         */
         $this->_form->addTextField('msn','',$this->_translator->getMessageInLang($this->_language,'USER_MSN'),'','','15',false,'','','','left','&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessageInLang($this->_language,'USER_MSN').':','',false,'');
         $this->_form->combine();
         $this->_form->addTextField('skype','',$this->_translator->getMessageInLang($this->_language,'USER_SKYPE'),'','','15',false,'','','','left',$this->_translator->getMessageInLang($this->_language,'USER_SKYPE').':<span style="font-size: 1px;">&nbsp;</span>','',false,'');
         $this->_form->combine('horizontal');
         $this->_form->addTextField('yahoo','',$this->_translator->getMessageInLang($this->_language,'USER_YAHOO'),'','','15',false,'','','','left','&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessageInLang($this->_language,'USER_YAHOO').':','',false,'');
         $this->_form->combine();
         $this->_form->addText('messenger_text',$this->_translator->getMessageInLang($this->_language,'USER_MESSENGER_NUMBERS'),$this->_translator->getMessageInLang($this->_language,'USER_MESSENGER_NUMBERS_TEXT'));
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('messenger_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');
         ##################################################
         # messenger - END
         ##################################################
         $this->_form->addEmptyline();
         $this->_form->addTextArea('description','',$this->_translator->getMessageInLang($this->_language,'USER_DESCRIPTION'),'','40','10','virtual',false,false,true,1,true,false);
         $this->_form->combine();
         $this->_form->addCheckbox('description_change_all',$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessageInLang($this->_language,'USER_CHANGE_IN_ALL_ROOMS'),'');

         $id = 0;
         if (isset($this->_item)) {
            $id = $this->_item->getItemID();
         } elseif (isset($this->_form_post)) {
            if (isset($this->_form_post['uid'])) {
               $id = $this->_form_post['uid'];
            }
         }
         $this->_form->addEmptyline();
         $this->_form->addButtonBar('option',$this->_translator->getMessageInLang($this->_language,'COMMON_CHANGE_BUTTON'),'','','','');
      }elseif($this->getProfilePageName() == 'newsletter'){
         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_NONE');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_WEEKLY');
         $radio_values[1]['value'] = '2';
         $radio_values[2]['text'] = $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_DAILY');
         $radio_values[2]['value'] = '3';
         $this->_form->addRadioGroup('newsletter',$this->_translator->getMessage('CONFIGURATION_NEWSLETTER'),'',$radio_values,'',true,false);

         $this->_form->addText('newsletter_note', $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_NOTE_LABEL'), $this->_translator->getMessage('CONFIGURATION_NEWSLETTER_NOTE'));
         $this->_form->addEmptyline();
         $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');

      }elseif($this->getProfilePageName() == 'room_list'){
         $this->_form->addText('activate_path','',$this->_translator->getMessageInLang($this->_language,'PROFILE_ROOMLIST_CUSTOMIZING_DESCRIPTION'));
         $this->_form->addCheckboxGroup('sorting',$this->_link_item_array,$this->_link_item_check_array,'','','','','','','','',50,true,false,true);
         $this->_form->addEmptyline();
         $this->_form->addCheckbox('delete',1,false,'',$this->_translator->getMessageInLang($this->_language,'PROFILE_ROOMLIST_DELETE_OPTION'));
         $this->_form->addButtonBar('option',$this->_translator->getMessageInLang($this->_language,'PREFERENCES_SAVE_BUTTON'),'');
      } elseif ( !empty($_POST['option']) and isOption($_POST['option'],$this->_translator->getMessageInLang($this->_language,'PREFERENCES_DELETE_BUTTON')) ) {
         $current_portal_item = $this->_environment->getCurrentPortalItem();
         if ( !$this->_environment->inPortal()
              and !$this->_environment->inPrivateRoom()
            ) {
            $current_context_item = $this->_environment->getCurrentContextItem();
            // datenschutz: overwrite or not (28.08.2012 IJ)
            $overwrite = true;
            global $symfonyContainer;
            $disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
            if ( !empty($disable_overwrite) and $disable_overwrite ) {
            	$overwrite = false;
            }            
            if ($overwrite) {
               $this->_form->addText('delete_text_room','',$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_DESC_ROOM',$current_context_item->getTitle(),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_LOCK_BUTTON_ROOM'),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_BUTTON_ROOM')));
            } else {
               $this->_form->addText('delete_text_room','',$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_DESC_ROOM_NOT_OVERWRITE',$current_context_item->getTitle(),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_LOCK_BUTTON_ROOM'),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_BUTTON_ROOM')));
            }            
            unset($current_context_item);
            $this->_form->addButtonBar('option',$this->_translator->getMessageInLang($this->_language,'PREFERENCES_LOCK_BUTTON_ROOM'),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_BUTTON_ROOM'));
            $this->_form->addEmptyline(true);
            $this->_form->addText('delete_text','',$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_DESC',$current_portal_item->getTitle(),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_LOCK_BUTTON',$current_portal_item->getTitle()),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_BUTTON',$current_portal_item->getTitle())));
         } else {
            // datenschutz: overwrite or not (28.08.2012 IJ)
            $overwrite = true;
            global $symfonyContainer;
            $disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
            if ( !empty($disable_overwrite) and $disable_overwrite ) {
            	$overwrite = false;
            }            
            if ($overwrite) {
         	   $this->_form->addText('delete_text','',$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_DESC_PORTAL',$current_portal_item->getTitle(),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_LOCK_BUTTON',$current_portal_item->getTitle()),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_BUTTON',$current_portal_item->getTitle())));
            } else {
               $this->_form->addText('delete_text','',$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_DESC_PORTAL_NOT_OVERWRITE',$current_portal_item->getTitle(),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_LOCK_BUTTON',$current_portal_item->getTitle()),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_BUTTON',$current_portal_item->getTitle())));
            }         
         }
         $this->_form->addButtonBar('option',$this->_translator->getMessageInLang($this->_language,'PREFERENCES_LOCK_BUTTON',$current_portal_item->getTitle()),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_REALLY_DELETE_BUTTON',$current_portal_item->getTitle()));
         unset($current_portal_item);
      }else{

         // headline and hidden fields
         $this->setHeadline($this->_headline);
         $this->_form->addHidden('iid','');

         $this->_form->addHidden('uid','');
         $this->_form->addTextField('firstname','',$this->_translator->getMessageInLang($this->_language,'USER_FIRSTNAME'),'','','30',true);
         $this->_form->addTextField('lastname','',$this->_translator->getMessageInLang($this->_language,'USER_LASTNAME'),'','','30',true);
         $this->_form->addHidden('lastname_hidden','');
         $this->_form->addHidden('firstname_hidden','');

         // content form fields
         if ( $this->_show_account_change_form ) {
            $this->_form->addTextField('user_id','',$this->_translator->getMessageInLang($this->_language,'USER_USER_ID'),'',100,'30',true);
         } else {
            $this->_form->addHidden('user_id','');
            $this->_form->addText('user_id',$this->_translator->getMessageInLang($this->_language,'USER_USER_ID'),'');
         }
         if ( $this->_show_password_change_form ) {
            $this->_form->addPassword('password_old','',$this->_translator->getMessageInLang($this->_language,'PROFILE_USER_PASSWORD_OLD'),'','','20',false);
            $this->_form->addPassword('password','',$this->_translator->getMessageInLang($this->_language,'PROFILE_USER_PASSWORD'),'','','20',false);
            $this->_form->addPassword('password2','',$this->_translator->getMessageInLang($this->_language,'PROFILE_USER_PASSWORD2'),'','','20',false);
         }

         $i=0;
         $options = array();
         $options[$i]['value'] = 'browser';
         $options[$i]['text'] = $this->_translator->getMessageInLang($this->_language,'USER_BROWSER_LANGUAGE');
         $i++;
         $options[$i]['value'] = 'disabled';
         $options[$i]['text'] = '------------------';
         $i++;
         $languages = $this->_environment->getAvailableLanguageArray();
         foreach ($languages as $language) {
            $options[$i]['value'] = $language;
            $options[$i]['text'] = $this->_translator->getLanguageLabelOriginally($language);
            $i++;
         }
         $this->_form->addSelect('language',$options,'',$this->_translator->getMessageInLang($this->_language,'USER_LANGUAGE'),'','','',true,'','','','','','11.5');
         if ($this->_user->isModerator()){
            $this->_form->addCheckbox('email_account_want','1',false,$this->_translator->getMessageInLang($this->_language,'USER_EMAIL'),$this->_translator->getMessageInLang($this->_language,'USER_MAIL_GET_ACCOUNT'),'','','','','');
            $this->_form->addCheckbox('email_room_want','1',false,$this->_translator->getMessageInLang($this->_language,'USER_EMAIL'),$this->_translator->getMessageInLang($this->_language,'USER_MAIL_OPEN_ROOM_PO'),'','','','','');
         }
         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_NEW_UPLOAD_YES');
         $radio_values[0]['value'] = 'yes';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_NEW_UPLOAD_NO');
         $radio_values[1]['value'] = 'no';
         $this->_form->addRadioGroup('new_upload',$this->_translator->getMessage('CONFIGURATION_NEW_UPLOAD'),'',$radio_values,'',true,false);
         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_AUTO_SAVE_YES');
         $radio_values[0]['value'] = 'yes';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_AUTO_SAVE_NO');
         $radio_values[1]['value'] = 'no';
         $this->_form->addRadioGroup('auto_save',$this->_translator->getMessage('CONFIGURATION_AUTO_SAVE'),'',$radio_values,'',true,false);

         // buttons
         $this->_form->addButtonBar('option',$this->_translator->getMessageInLang($this->_language,'PREFERENCES_SAVE_BUTTON'),$this->_translator->getMessageInLang($this->_language,'PREFERENCES_DELETE_BUTTON'));

         if ($this->_show_merge_form) {
            $this->_form->addEmptyline();
            $delete_source_number = count($this->_array_sources_allow_delete);
            $current_portal = $this->_environment->getCurrentPortalItem();
            $current_user = $this->_environment->getCurrentUserItem();

            // text and options
            // auth source
            $this->_form->addSubHeadline('subheadline',$this->_translator->getMessageInLang($this->_language,'ACCOUNT_MERGE'));
            $current_portal = $this->_environment->getCurrentPortalItem();
            $title = '';
            if ( isset($current_portal) ) {
               $title = $current_portal->getTitle();
               unset($current_portal);
            }
            $this->_form->addText('text',$this->_translator->getMessageInLang($this->_language,'COMMON_HINTS'),$this->_translator->getMessageInLang($this->_language,'ACCOUNT_MERGE_TEXT',$title));
            if ( count($this->_auth_source_array) == 1 ) {
               $this->_form->addHidden('auth_source',$this->_auth_source_array[0]['value']);
            } elseif( $this->_show_auth_source ) {
               $this->_form->addSelect('auth_source', $this->_auth_source_array, $this->_default_auth_source_entry, $this->_translator->getMessageInLang($this->_language,'USER_AUTH_SOURCE'), '', 1 , false, false, false, '', '', '', '', 12);
            }
            $this->_form->addTextfield('user_id_merge','',$this->_translator->getMessageInLang($this->_language,'COMMON_ACCOUNT'),'','',21,false);
            $this->_form->addPassword('password_merge','',$this->_translator->getMessageInLang($this->_language,'USER_PASSWORD'),'','',21,false);
            $this->_form->addText('merge_desc',$this->_translator->getMessageInLang($this->_language,'COMMON_ATTENTION'),$this->_translator->getMessageInLang($this->_language,'COMMON_DONT_STOP'));
            $this->_form->addButtonBar('option',$this->_translator->getMessageInLang($this->_language,'ACCOUNT_MERGE_BUTTON'));
         }
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if ($this->getProfilePageName() == 'user'){
         $this->_values = array();
         if (isset($this->_item)) {
            $this->_values['uid'] = $this->_item->getItemID();
            $this->_values['title'] = $this->_item->getTitle();          // no encode here
            $this->_values['telephone'] = $this->_item->getTelephone();  // encode in form_views
            $this->_values['birthday'] = $this->_item->getBirthday();
            $this->_values['cellularphone'] = $this->_item->getCellularphone();
            $this->_values['homepage'] = $this->_item->getHomepage();
            $this->_values['organisation'] = $this->_item->getOrganisation();
            $this->_values['position'] = $this->_item->getPosition();
            $this->_values['email'] = $this->_item->getEmail();
            $this->_values['street'] = $this->_item->getStreet();
            $this->_values['zipcode'] = $this->_item->getZipcode();
            $this->_values['city'] = $this->_item->getCity();
            $this->_values['room'] = $this->_item->getRoom();
            $this->_values['description'] = $this->_item->getDescription();
            $this->_values['firstname_hidden'] = $this->_item->getFirstname();
            $this->_values['lastname_hidden'] = $this->_item->getLastname();
            $this->_values['icq'] = $this->_item->getICQ();
            $this->_values['msn'] = $this->_item->getMSN();
            $this->_values['skype'] = $this->_item->getSkype();
            $this->_values['jabber'] = $this->_item->getJabber();
            $this->_values['yahoo'] = $this->_item->getYahoo();
            if ($this->_item->isNewUploadOn()) {
               $this->_values['new_upload'] = 'yes';
            } else {
               $this->_values['new_upload'] = 'no';
            }
            if ($this->_item->isAutoSaveOn()) {
               $this->_values['auto_save'] = 'yes';
            } else {
               $this->_values['auto_save'] = 'no';
            }
            $this->_setValuesForRubricConnections();

            if ($this->_item->isModerator()) {
               $this->_values['want_mail_get_account'] = $this->_item->getAccountWantMail();
               $this->_values['is_moderator'] = true;
            } else {
               $this->_values['is_moderator'] = false;
            }
            $picture = $this->_item->getPicture();
            $this->_values['upload'] = $picture;
            if (!empty($picture)) {
               $this->_values['with_picture'] = true;
            } else {
               $this->_values['with_picture'] = false;
            }

            if (!$this->_item->isEmailVisible()) {
               $this->_values['email_visibility'] = 'checked';
            }

         } elseif (isset($this->_form_post)) {
            $this->_values = $this->_form_post;
            if ( !$this->_environment->inPrivateRoom()
                 and isset($this->_values['fullname_hidden'])
               ) {
               $this->_values['fullname'] = $this->_values['fullname_hidden'];
            }
        }
      }elseif($this->getProfilePageName() == 'newsletter'){
           if (isset($this->_form_post)) {
            $this->_values = $this->_form_post;
         }else{
            $room = $this->_environment->getCurrentUserItem()->getOwnRoom();
            $newlsetter = $room->getPrivateRoomNewsletterActivity();
            if ($newlsetter == 'weekly'){
               $this->_values['newsletter'] ='2';
            }elseif ($newlsetter == 'daily'){
               $this->_values['newsletter'] ='3';
            }else{
               $this->_values['newsletter'] ='1';

            }
         }

      }elseif($this->getProfilePageName() == 'room_list'){
      }else{
         if (!empty($this->_form_post)) {
            $this->_values = $this->_form_post;
            $this->_values['user_id_text'] = $this->_values['user_id'];
         } elseif (!empty($this->_item)) {
            if ($this->_user->getAccountWantMail() == 'yes') {
               $this->_values['email_account_want'] = 1;
            }
            if ($this->_user->getOpenRoomWantMail() == 'yes') {
               $this->_values['email_room_want'] = 1;
            }
            $this->_values['auth_source'] = $this->_user->getAuthSource();
            $this->_values['user_id'] = $this->_user->getUserID();
            if ( $this->_user->isRoot() ) {
               $this->_values['user_id_text'] = $this->_user->getUserID();
            }
            $this->_values['iid'] = $this->_item->getItemID();
            $this->_values['firstname'] = $this->_item->getFirstname();
            $this->_values['firstname_hidden'] = $this->_item->getFirstname();
            $this->_values['lastname'] = $this->_item->getLastname();
            $this->_values['lastname_hidden'] = $this->_item->getLastname();
            $this->_values['user_id'] = $this->_item->getUserID();
            $this->_values['user_id_text'] = $this->_item->getUserID();
            $this->_values['language'] = $this->_item->getLanguage();
            if ($this->_item->isNewUploadOn()) {
               $this->_values['new_upload'] = 'yes';
            } else {
               $this->_values['new_upload'] = 'no';
            }
            if ($this->_item->isAutoSaveOn()) {
               $this->_values['auto_save'] = 'yes';
            } else {
               $this->_values['auto_save'] = 'no';
            }
         } else {
            include_once('functions/error_functions.php');
            trigger_error('lost values',E_USER_WARNING);
         }
      }
   }

   function _checkValues () {
      if ($this->getProfilePageName() == 'account'){
         if ( !empty($this->_form_post['option'])
              and isOption($this->_form_post['option'],$this->_translator->getMessageInLang($this->_language,'ACCOUNT_MERGE_BUTTON'))
            ) {
            if ( empty($this->_form_post['user_id_merge']) ) {
               $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'COMMON_ERROR_SELECT',$this->_translator->getMessage('COMMON_ACCOUNT'));
               $this->_form->setFailure('user_id_merge');
            }
            if ( empty($this->_form_post['password_merge']) ) {
               $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'COMMON_ERROR_SELECT',$this->_translator->getMessage('USER_PASSWORD'));
               $this->_form->setFailure('password_merge');
            }
            if ( !empty($this->_form_post['user_id_merge'])
                 and !empty($this->_form_post['password_merge'])
               ) {
               global $c_annonymous_account_array;
               $current_user = $this->_environment->getCurrentUserItem();
               if ( !empty($c_annonymous_account_array[mb_strtolower($current_user->getUserID(), 'UTF-8').'_'.$current_user->getAuthSource()])
                    and $current_user->isOnlyReadUser()
                  ) {
                  $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'ACCOUNT_MERGE_ERROR_ANNONYMOUS',$current_user->getUserID());
               } elseif ( !empty($c_annonymous_account_array[mb_strtolower($this->_form_post['user_id_merge'], 'UTF-8').'_'.$this->_form_post['auth_source']])
                          and !empty($c_read_account_array[mb_strtolower($this->_form_post['user_id_merge'], 'UTF-8').'_'.$this->_form_post['auth_source']])
                        ) {
                  $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'ACCOUNT_MERGE_ERROR_ANNONYMOUS',$this->_form_post['user_id_merge']);
               } elseif ( !empty($this->_form_post['user_id_merge'])
                    and !empty($this->_form_post['password_merge'])
                  ) {
                  if ( $current_user->getUserID() == $this->_form_post['user_id_merge']
                       and ( empty($this->_form_post['auth_source'])
                             or ( $current_user->getAuthSource() == $this->_form_post['auth_source'] )
                           )
                     ) {
                     $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'ACCOUNT_MERGE_ERROR_USER_ID',$this->_form_post['user_id_merge']);
                     $this->_form->setFailure('user_id_merge','');
                  } elseif ( !empty($this->_form_post['auth_source']) ) {
                     $authentication = $this->_environment->getAuthenticationObject();
                     $auth_manager = $authentication->getAuthManager($this->_form_post['auth_source']);
                     if ( !$auth_manager->checkAccount($this->_form_post['user_id_merge'],$this->_form_post['password_merge']) ) {
                        $this->_error_array = array_merge($this->_error_array,$auth_manager->getErrorArray());
                        $this->_form->setFailure('user_id_merge','');
                        $this->_form->setFailure('password_merge','');
                     }
                  } else {
                     $authentication = $this->_environment->getAuthenticationObject();
                     if ( !$authentication->checkAccount($this->_form_post['user_id_merge'],$this->_form_post['password_merge']) ) {
                        $this->_error_array = array_merge($this->_error_array,$authentication->getErrorArray());
                        $this->_form->setFailure('user_id_merge','');
                        $this->_form->setFailure('password_merge','');
                     }
                  }
               }
            }
         } else {
            if ( !empty($this->_form_post['password_old']) ) {
               $current_user = $this->_environment->getCurrentUserItem();
               $authentication = $this->_environment->getAuthenticationObject();
               $auth_success = $authentication->isAccountGranted($current_user->getUserID(),$this->_form_post['password_old'],$current_user->getAuthSource());
               if ( !$auth_success ) {
                  $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_OLD_PASSWORD_ERROR');
                  $this->_form->setFailure('password_old');
               }
            }
            if ( empty($this->_form_post['password_old'])
                 and !empty($this->_form_post['password'])
                 and !empty($this->_form_post['password2'])
               ) {
               $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_OLD_PASSWORD_ERROR2');
               $this->_form->setFailure('password_old');
            }
            if (isset($this->_form_post['password']) and $this->_form_post['password'] != $this->_form_post['password2']) {
               $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_PASSWORD_ERROR');
               $this->_form->setFailure('password');
               $this->_form->setFailure('password2');
            }

            // password security
            if ( !empty($this->_form_post['password_old'])
                 and isset($auth_success)
                 and $auth_success
                 and !empty($this->_form_post['password'])
                 and !empty($this->_form_post['password2'])
                 and $this->_form_post['password'] == $this->_form_post['password2']
               ) {
               if(isset($this->_form_post['auth_source'])) {
                  $auth_source_manager = $this->_environment->getAuthSourceManager();
                  $auth_source_item = $auth_source_manager->getItem($this->_form_post['auth_source']);
                  if ( $auth_source_item->getPasswordLength() > 0 ) {
                     if(strlen($this->_form_post['password']) < $auth_source_item->getPasswordLength()) {
                        $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_NEW_PASSWORD_LENGTH_ERROR');
                        $this->_form->setFailure('password');
                        $this->_form->setFailure('password2');
                     }
                  }
                  if($auth_source_item->getPasswordSecureBigchar() == 1){
                     if(!preg_match('~[A-Z]~u', $this->_form_post['password'])) {
                        $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_NEW_PASSWORD_BIGCHAR_ERROR');
                        $this->_form->setFailure('password');
                        $this->_form->setFailure('password2');
                     }
                  }
                  if($auth_source_item->getPasswordSecureSmallchar() == 1){
                  	if(!preg_match('~[a-z]~u', $this->_form_post['password'])) {
                  		$this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_NEW_PASSWORD_SMALLCHAR_ERROR');
                  		$this->_form->setFailure('password');
                  		$this->_form->setFailure('password2');
                  	}
                  }
                  if($auth_source_item->getPasswordSecureNumber() == 1){
                  	if(!preg_match('~[0-9]~u', $this->_form_post['password'])) {
                  		$this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_NEW_PASSWORD_NUMBER_ERROR');
                  		$this->_form->setFailure('password');
                  		$this->_form->setFailure('password2');
                  	}
                  }
                  if($auth_source_item->getPasswordSecureSpecialchar() == 1){
                     if(!preg_match('~[^a-zA-Z0-9]+~u',$this->_form_post['password'])){
                        $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_NEW_PASSWORD_SPECIALCHAR_ERROR');
                        $this->_form->setFailure('password');
                        $this->_form->setFailure('password2');
                     }
                  }
               }
            }
         }
      }

      // user data
      elseif ( $this->getProfilePageName() == 'user' ) {
         $portal_user = $this->_environment->getPortalUserItem();
         if ( isset($portal_user)
              and !empty($this->_form_post['email'])
              and $portal_user->hasToChangeEmail()
              and $portal_user->getEmail() == $this->_form_post['email']
            ) {
            $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'COMMON_ERROR_FIELD_CORRECT',$this->_translator->getMessageInLang($this->_language,'USER_EMAIL'));
            $this->_form->setFailure('email');
         }
         if(!empty($this->_form_post['icq'])){
            if(!preg_match('~^[0-9]+$~u', $this->_form_post['icq'])){
               $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_ICQ_ERROR');
               $this->_form->setFailure('icq');
            }
         }
      }

      if ( !empty($this->_form_post['user_id']) ) {
         $this->_user = $this->_environment->getPortalUserItem();
         if ( isset($this->_user)
              and $this->_user->getUserID() != $this->_form_post['user_id']
            ) {
            $auth_source = $this->_user->getAuthSource();
            if ( !empty($auth_source) ) {
               $authentication = $this->_environment->getAuthenticationObject();
               if ( !$authentication->is_free($this->_form_post['user_id'],$auth_source) ) {
                  $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_USER_ID_ERROR',$this->_form_post['user_id']);
                  $this->_form->setFailure('user_id','');
               } elseif ( withUmlaut($this->_form_post['user_id']) ) {
                  $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_USER_ID_ERROR_UMLAUT',$this->_form_post['user_id']);
                  $this->_form->setFailure('user_id','');
               }
            } else {
               $this->_error_array[] = $this->_translator->getMessageInLang($this->_language,'USER_AUTH_SOURCE_ERROR');
            }
         }
      }
   }
}
?>